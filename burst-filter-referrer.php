<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Filter the tracking data before it is saved to the database.
 * Example: Remove the referrer from the tracking data.
 *
 * @param array<string, mixed> $sanitized_data The tracking data being processed.
 *
 * @return array<string, mixed> Modified tracking data with referrer cleared.
 */
function filter_burst_before_track_hit( array $sanitized_data ): array {
    $sanitized_data['referrer'] = '';
    return $sanitized_data;
}
add_filter( 'burst_before_track_hit', 'filter_burst_before_track_hit', 10, 1 );
