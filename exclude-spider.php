<?php
/**
 * Exclude YisouSpider from tracking and permanently delete historical data.
 *
 * Include in wp-config.php to ensure it is loaded before the plugin.
 *
 * @param array<string, mixed> $sanitized_data The tracking data being processed.
 *
 * @return array<string, mixed> Modified tracking data with referrer set to 'spammer' for the spider.
 */
function set_spider_to_spammer( array $sanitized_data ): array {
    global $wpdb;
    $spider_id = $wpdb->get_var(
        "SELECT ID FROM {$wpdb->prefix}burst_browsers WHERE name = 'YisouSpider'"
    );
    if ( ! $spider_id ) {
        return $sanitized_data;
    }

    if ( (int) ( $sanitized_data['browser_id'] ?? 0 ) === (int) $spider_id ) {
        $sanitized_data['referrer'] = 'spammer';
    }

    return $sanitized_data;
}
add_filter( 'burst_before_track_hit', 'set_spider_to_spammer' );

/**
 * Permanently delete YisouSpider sessions and related statistics.
 *
 * Runs once on init; sets an option flag to prevent re-execution.
 * Note: browser_id is stored on the burst_sessions table.
 *
 * @return void
 */
function burst_exclude_spider(): void {
    if ( get_option( 'burst_yisou_spider_removed' ) ) {
        return;
    }

    global $wpdb;
    $spider_id = $wpdb->get_var(
        "SELECT ID FROM {$wpdb->prefix}burst_browsers WHERE name = 'YisouSpider'"
    );
    if ( ! $spider_id ) {
        return;
    }

    // Remove all sessions related to this spider (browser_id is on burst_sessions).
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}burst_sessions WHERE browser_id = %d",
            $spider_id
        )
    );

    // Remove all statistics linked to those sessions.
    // After deleting sessions, orphaned statistics can be cleaned up via the
    // burst_sessions.ID <-> burst_statistics.session_id relationship.
    $wpdb->query(
        "DELETE s FROM {$wpdb->prefix}burst_statistics s
         LEFT JOIN {$wpdb->prefix}burst_sessions sess ON s.session_id = sess.ID
         WHERE sess.ID IS NULL"
    );

    update_option( 'burst_yisou_spider_removed', true, false );
}
add_action( 'init', 'burst_exclude_spider' );
