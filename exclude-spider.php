<?php

/**
 * Include the below in the wp-config.php, to ensure it is loaded before the plugin is loaded.
 * @param array $sanitized_data
 * @return array
 */
function set_spider_to_spammer( $sanitized_data ) {
    //check if $sanitized_data['browser'] contains 'YisouSpider'
    if ( str_contains($sanitized_data['browser'], 'YisouSpider') ){
        //we use a trick, by setting the referrer to spammer, this page won't get tracked.
        $sanitized_data['referrer'] = 'spammer';
    }

    return $sanitized_data;
}
add_filter( 'before_burst_track_hit', 'set_spider_to_spammer' );

/**
 * permanently delete yisou spider hits
 * @return void
 */
function burst_exclude_spider(){
    if ( get_option('burst_yisou_spider_removed') ) {
        return;
    }

    global $wpdb;
    $sql = "select ID from {$wpdb->prefix}burst_browsers where name='YisouSpider'";
    $spider_id = $wpdb->get_var($sql);
    if (!$spider_id) {
        return;
    }
    //first, remove all entries from the sessions table that are related to this spider
    $sql = "DELETE FROM {$wpdb->prefix}burst_sessions
            WHERE ID IN (
                SELECT session_id
                FROM {$wpdb->prefix}burst_statistics
                WHERE browser_id = $spider_id
            );";
    $wpdb->query($sql);

    //then, remove all entries from the statistics table that are related to this spider
    $sql = "delete from {$wpdb->prefix}burst_statistics where browser_id={$spider_id}";
    $wpdb->query($sql);

    update_option('burst_yisou_spider_removed', true, false);
}
add_action('init', 'burst_exclude_spider');
