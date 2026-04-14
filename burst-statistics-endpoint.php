<?php
/**
 * Custom Burst Statistics endpoint for collecting hits.
 *
 * @deprecated The endpoint structure has changed significantly. Burst Statistics and Burst Pro
 *             now include their own endpoint.php that uses namespaced autoloading
 *             and the Tracking class directly. This file is kept for reference only.
 *
 * @see burst-statistics/endpoint.php for the current implementation.
 */
namespace Burst;

use Burst\Frontend\Tracking\Tracking;

define( 'SHORTINIT', true );
require_once dirname( __DIR__, 3 ) . '/wp-load.php';

define( 'BURST_PATH', trailingslashit( WP_CONTENT_DIR ) . 'plugins/burst-statistics/' );
require_once BURST_PATH . 'includes/autoload.php';

( new Tracking() )->beacon_track_hit();
