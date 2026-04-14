<?php
/**
 * Exclude hits containing specific URL parameters from tracking.
 *
 * Add this to wp-config.php to ensure it is loaded before the plugin.
 *
 * @param array<string, mixed> $sanitized_data The tracking data being processed.
 *
 * @return array<string, mixed> Modified tracking data with referrer set to 'spammer' when matched.
 */
function set_parameter_to_spammer( array $sanitized_data ): array {
    if (
        str_contains( $sanitized_data['parameters'] ?? '', 'id=' )
        || str_contains( $sanitized_data['parameters'] ?? '', 'autoupdater_nonce=' )
    ) {
        $sanitized_data['referrer'] = 'spammer';
    }

    return $sanitized_data;
}
add_filter( 'burst_before_track_hit', 'set_parameter_to_spammer' );
