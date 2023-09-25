<?php
/**
 * Burst Statistics endpoint for collecting hits
 */
define( 'SHORTINIT', true );
require_once __DIR__ . '/wp-load.php';
require_once trailingslashit(WP_CONTENT_DIR) . 'plugins/burst-pro/helpers/php-user-agent/UserAgentParser.php';
require_once trailingslashit(WP_CONTENT_DIR) . 'plugins/burst-pro/pro/tracking/tracking.php';
require_once trailingslashit(WP_CONTENT_DIR) . 'plugins/burst-pro/tracking/tracking.php';

burst_beacon_track_hit();