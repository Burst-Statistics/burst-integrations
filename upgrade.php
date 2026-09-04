<?php
/**
 * Plugin Name: Burst – 3.6.3 repair (dictionary duplicates + Referrers query)
 * Description: One-off repair for a Burst Pro 3.6.3.x site: removes duplicate browser / browser-version / platform / device names that block the 3.6.3 UNIQUE migration, applies that migration, and replaces the 31K-character source-category fallback so the Referrers table loads again. Drop this file in wp-content/mu-plugins/ (or paste into a code-snippets plugin). Remove it once a Burst release containing the fix is installed.
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
 * Part 2 – one-off database repair. Runs once for an administrator, then marks
 * itself as done (option burst_repair_363_done). Delete that option to run again.
 */
add_action(
    'admin_init',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) || get_option( 'burst_repair_363_done' ) ) {
            return;
        }
        global $wpdb;

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

            // 2a. The new column is varchar(191); shorten any longer (junk) names first.
            $wpdb->query( "UPDATE `{$table}` SET name = LEFT(name, 191) WHERE CHAR_LENGTH(name) > 191" );

            // 2b. Every duplicate name -> the lowest ID that carries the same name.
            $map = $wpdb->get_results(
                "SELECT d.ID AS old_id, d.name, k.keep_id
				 FROM `{$table}` d
				 JOIN ( SELECT name, MIN(ID) AS keep_id FROM `{$table}` GROUP BY name HAVING COUNT(*) > 1 ) k ON k.name = d.name
				 WHERE d.ID <> k.keep_id",
                ARRAY_A
            );

            if ( ! empty( $map ) ) {
                $old_ids = implode( ',', array_map( 'intval', array_column( $map, 'old_id' ) ) );

                // Re-point sessions (and legacy statistics rows) from the duplicate to the kept ID – one pass per table.
                $remap = "JOIN ( SELECT d.ID AS old_id, k.keep_id FROM `{$table}` d
				          JOIN ( SELECT name, MIN(ID) AS keep_id FROM `{$table}` GROUP BY name HAVING COUNT(*) > 1 ) k ON k.name = d.name
				          WHERE d.ID <> k.keep_id ) m";
                $wpdb->query( "UPDATE `{$sessions}` s {$remap} ON s.`{$fk}` = m.old_id SET s.`{$fk}` = m.keep_id" );
                if ( in_array( $fk, $stat_columns, true ) ) {
                    $wpdb->query( "UPDATE `{$statistics}` st {$remap} ON st.`{$fk}` = m.old_id SET st.`{$fk}` = m.keep_id" );
                }

                // Remove the duplicates.
                $wpdb->query( "DELETE FROM `{$table}` WHERE ID IN ({$old_ids})" );

                // Tracking caches name -> ID for 5 minutes; drop the entries that may point at a deleted ID.
                foreach ( $map as $row ) {
                    wp_cache_delete( 'burst_' . $item . '_lookup_' . crc32( (string) $row['name'] ), 'burst' );
                }
            }

            // 2c. Apply the 3.6.3 schema change that dbDelta could not: varchar(191) + UNIQUE on name.
            $has_unique = $wpdb->get_var( "SHOW INDEX FROM `{$table}` WHERE Column_name = 'name' AND Non_unique = 0" );
            if ( ! $has_unique ) {
                $wpdb->query( "ALTER TABLE `{$table}` MODIFY `name` varchar(191) NOT NULL, ADD UNIQUE KEY `name` (`name`)" );
                if ( '' !== $wpdb->last_error ) {
                    error_log( 'Burst repair (' . $table . '): ' . $wpdb->last_error );
                }
            }
        }

        // 2d. Re-run the Pro source backfill so historic sessions get a stored source_category
        // (idempotent: it only touches sessions whose source is still NULL).
        update_option( 'burst_db_upgrade_pro_referrers_normalization', true, false );

        update_option( 'burst_repair_363_done', time(), false );
    }
);
