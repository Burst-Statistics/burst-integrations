<?php
/**
 * Plugin Name: Clear Burst Goal Statistics Table
 * Description: A mu-plugin to clear the wp_burst_goal_statistics, triggered by ?resetgoals=1.
 * Version: 1.0
 * Author: Rogier Lankhorst
 */

add_action('init', function() {
    if (!defined('ABSPATH') || !is_admin() || !isset($_GET['resetgoals']) ) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'burst_goal_statistics';
    $wpdb->query("TRUNCATE TABLE `$table_name`");
});