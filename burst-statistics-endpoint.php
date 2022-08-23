<?php
/**
 * Burst Statistics endpoint for collecting hits
 */
define( 'SHORTINIT', true );
require_once __DIR__ . '/wp-load.php';
require_once '/Applications/MAMP/htdocs/60/wp-content/plugins/burst-premium//helpers/php-user-agent/UserAgentParser.php';
require_once '/Applications/MAMP/htdocs/60/wp-content/plugins/burst-premium/tracking/tracking.php';

burst_beacon_track_hit();
