<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * Filter the tracking data before it is saved to the database.
 * Example: Remove the referrer from the tracking data.
 */
add_filter('burst_before_track_hit', 'filter_burst_before_track_hit', 10, 1);
function filter_burst_before_track_hit($tracking_data) {
	$tracking_data['referrer'] = '';
	return $tracking_data;
}