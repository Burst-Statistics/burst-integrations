<?php
/**
 * Plugin Name: Burst – 3.6.3 repair (dictionary duplicates + Referrers query)
 * Description: One-off repair for a Burst Pro 3.6.3.x site: removes duplicate browser / browser-version / platform / device names that block the 3.6.3 UNIQUE migration, applies that migration, and replaces the 31K-character source-category fallback so the Referrers table loads again. Drop this file in wp-content/mu-plugins/ (or paste into a code-snippets plugin), then click "Run repair" in the admin notice. Remove it once a Burst release containing the fix is installed.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Part 1 – Referrers table.
 *
 * Burst asks Pro for a CASE expression that classifies a session's referrer on the
 * fly for sessions without a stored source_category. That CASE is ~31,000 characters
 * (hundreds of LIKE patterns) and the Referrers table puts it in both SELECT and
 * GROUP BY, which is what WP Engine kills. Returning a tiny expression here at
 * priority 5 makes Pro use it as-is. Sessions that already have a stored
 * source_category are unaffected (the stored value wins via COALESCE).
 */
add_filter(
    'burst_source_category_case_sql',
    static function ( string $case_sql ): string {
        return "CASE WHEN sessions.referrer IS NULL OR sessions.referrer = '' THEN 'direct' ELSE 'referral' END";
    },
    5
);

/**
 * Part 2 – one-off database repair.
 *
 * Runs on demand (admin notice → "Run repair", or wp-admin/?burst_repair_363=run
 * with a nonce), never implicitly on every admin request: on a large sessions
 * table the re-pointing UPDATE can take a while, and the browser_version_id
 * column has no index, so that one is a full table scan.
 *
 * Every step is checked. If re-pointing the sessions fails (query killed, timeout)
 * the duplicates are kept — deleting them would leave sessions referencing ids that
 * no longer exist — and the run can simply be repeated. The done flag
 * (option burst_repair_363_done) is only set when every dictionary ended up with
 * its unique index. Delete that option to run again.
 */
add_action(
    'admin_init',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) || get_option( 'burst_repair_363_done' ) ) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.
        if ( ! isset( $_GET['burst_repair_363'] ) || 'run' !== $_GET['burst_repair_363'] ) {
            return;
        }
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'burst_repair_363' ) ) {
            return;
        }
        // Two admin requests at once (dashboard + admin-ajax) must not both run the ALTER.
        if ( get_transient( 'burst_repair_363_running' ) ) {
            return;
        }
        set_transient( 'burst_repair_363_running', time(), 15 * MINUTE_IN_SECONDS );
        if ( function_exists( 'set_time_limit' ) ) {
            @set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }
        ignore_user_abort( true );

        $errors = burst_repair_363_run();

        delete_transient( 'burst_repair_363_running' );
        set_transient( 'burst_repair_363_result', $errors, HOUR_IN_SECONDS );

        wp_safe_redirect( remove_query_arg( [ 'burst_repair_363', '_wpnonce' ] ) );
        exit;
    }
);

/**
 * Admin notice: a "Run repair" button while pending, the outcome after a run.
 */
add_action(
    'admin_notices',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $result = get_transient( 'burst_repair_363_result' );
        if ( is_array( $result ) ) {
            delete_transient( 'burst_repair_363_result' );
            if ( empty( $result ) ) {
                echo '<div class="notice notice-success"><p>Burst 3.6.3 repair completed. The Referrers backfill now runs in the background; you can remove the repair mu-plugin once a Burst release with the fix is installed.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Burst 3.6.3 repair did not complete. Nothing was lost; run it again in a quiet moment. Details:</p><ul><li>' . implode( '</li><li>', array_map( 'esc_html', $result ) ) . '</li></ul></div>';
            }
        }
        if ( get_option( 'burst_repair_363_done' ) ) {
            return;
        }
        $url = wp_nonce_url( add_query_arg( 'burst_repair_363', 'run', admin_url() ), 'burst_repair_363' );
        echo '<div class="notice notice-warning"><p>Burst 3.6.3 repair is pending. It re-points sessions and can take a minute on large sites; run it in a quiet moment. <a class="button button-primary" href="' . esc_url( $url ) . '">Run repair</a></p></div>';
    }
);

/**
 * Dedupe the dictionaries, add the unique index and re-trigger the source backfill.
 *
 * @return string[] Error messages; empty when everything succeeded.
 */
function burst_repair_363_run(): array {
    global $wpdb;
    $errors = [];

    $dictionaries = [
        'browser'         => 'browser_id',
        'browser_version' => 'browser_version_id',
        'platform'        => 'platform_id',
        'device'          => 'device_id',
    ];
    $sessions   = $wpdb->prefix . 'burst_sessions';
    $statistics = $wpdb->prefix . 'burst_statistics';
    // Older installs may still carry the legacy id columns on burst_statistics.
    $stat_columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$statistics}`", 0 );

    foreach ( $dictionaries as $item => $fk ) {
        $table = $wpdb->prefix . 'burst_' . $item . 's';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            continue;
        }
        // Already healed (by an earlier run or a Burst upgrade): nothing to do.
        if ( $wpdb->get_var( "SHOW INDEX FROM `{$table}` WHERE Column_name = 'name' AND Non_unique = 0" ) ) {
            continue;
        }

        // 2a. The new column is varchar(191); shorten any longer (junk) names first.
        $wpdb->query( "UPDATE `{$table}` SET name = LEFT(name, 191) WHERE CHAR_LENGTH(name) > 191" );

        // 2b. Every duplicate name -> the lowest ID that carries the same name.
        $duplicates = "SELECT d.ID AS old_id, d.name, k.keep_id
            FROM `{$table}` d
            JOIN ( SELECT name, MIN(ID) AS keep_id FROM `{$table}` GROUP BY name HAVING COUNT(*) > 1 ) k ON k.name = d.name
            WHERE d.ID <> k.keep_id";
        $map        = $wpdb->get_results( $duplicates, ARRAY_A );

        if ( ! empty( $map ) ) {
            // Re-point sessions (and legacy statistics rows) from the duplicate to the kept ID – one pass per table.
            $targets = [ $sessions ];
            if ( in_array( $fk, $stat_columns, true ) ) {
                $targets[] = $statistics;
            }
            $remapped = true;
            foreach ( $targets as $target ) {
                $result = $wpdb->query( "UPDATE `{$target}` t JOIN ( {$duplicates} ) m ON t.`{$fk}` = m.old_id SET t.`{$fk}` = m.keep_id" );
                if ( false === $result ) {
                    $remapped = false;
                    break;
                }
            }
            if ( ! $remapped ) {
                // Deleting now would orphan the rows still pointing at the duplicates.
                $errors[] = $table . ': re-pointing ' . $target . ' failed, duplicates kept (' . $wpdb->last_error . ')';
                error_log( 'Burst repair: ' . end( $errors ) );
                continue;
            }

            // Remove the duplicates.
            $old_ids = implode( ',', array_map( 'intval', array_column( $map, 'old_id' ) ) );
            if ( false === $wpdb->query( "DELETE FROM `{$table}` WHERE ID IN ({$old_ids})" ) ) {
                $errors[] = $table . ': deleting duplicates failed (' . $wpdb->last_error . ')';
                error_log( 'Burst repair: ' . end( $errors ) );
                continue;
            }

            // Tracking caches name -> ID for 5 minutes; drop the entries that may point at a deleted ID.
            foreach ( $map as $row ) {
                wp_cache_delete( 'burst_' . $item . '_lookup_' . crc32( (string) $row['name'] ), 'burst' );
            }
        }

        // 2c. Apply the 3.6.3 schema change that dbDelta could not: varchar(191) + UNIQUE on name.
        // A tracker can insert a fresh duplicate between the DELETE and this ALTER; then
        // this fails with "Duplicate entry", the done flag stays unset and a re-run fixes it.
        $wpdb->query( "ALTER TABLE `{$table}` MODIFY `name` varchar(191) NOT NULL, ADD UNIQUE KEY `name` (`name`)" );
        if ( '' !== $wpdb->last_error ) {
            $errors[] = $table . ': adding the unique index failed (' . $wpdb->last_error . ')';
            error_log( 'Burst repair: ' . end( $errors ) );
        }
    }

    if ( ! empty( $errors ) ) {
        return $errors;
    }

    // 2d. Re-run the Pro source backfill so historic sessions get a stored source_category
    // (idempotent: it only touches sessions whose source is still NULL). Burst picks it up
    // on the next dashboard visit or daily cron and processes 5000 sessions per minute.
    update_option( 'burst_db_upgrade_pro_referrers_normalization', true, false );

    update_option( 'burst_repair_363_done', time(), false );

    return [];
}
