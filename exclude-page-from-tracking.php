<?php
/**
 * Exclude a specific page from tracking by setting the referrer to 'spammer'.
 *
 * Add this to wp-config.php, as an mu-plugin is not loaded during the track hit process.
 *
 * @param array<string, mixed> $sanitized_data The tracking data being processed.
 *
 * @return array<string, mixed> Modified tracking data.
 */
function set_referrer_to_spammer( array $sanitized_data ): array {
    $url = ( $sanitized_data['host'] ?? '' ) . ( $sanitized_data['page_url'] ?? '' );
    if ( str_contains( $url, 'my-page-url' ) ) {
        $sanitized_data['referrer'] = 'spammer';
    }

    return $sanitized_data;
}
add_filter( 'burst_before_track_hit', 'set_referrer_to_spammer' );
