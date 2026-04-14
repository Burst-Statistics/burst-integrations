<?php
/**
 * Custom Burst Pro endpoint for collecting hits.
 *
 * @deprecated The endpoint structure has changed significantly. Burst Pro now includes
 *             its own endpoint.php that uses namespaced autoloading and the Tracking
 *             class directly. This file is kept for reference only.
 *
 * @see burst-pro/endpoint.php for the current implementation.
 */
namespace Burst;

use Burst\Frontend\Tracking\Tracking;

define( 'SHORTINIT', true );
require_once dirname( __DIR__, 4 ) . '/wp-load.php';

define( 'BURST_PATH', trailingslashit( WP_CONTENT_DIR ) . 'plugins/burst-pro/' );
require_once BURST_PATH . 'includes/autoload.php';
if ( file_exists( BURST_PATH . 'includes/Pro/Frontend/Tracking/tracking.php' ) ) {
    require_once BURST_PATH . 'includes/Pro/Frontend/Tracking/tracking.php';
}

( new Tracking() )->beacon_track_hit();
