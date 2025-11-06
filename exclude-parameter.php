<?php

/**
 * Include the below in the wp-config.php, to ensure it is loaded before the plugin is loaded.
 * @param array $sanitized_data
 * @return array
 */
function set_parameter_to_spammer(array $sanitized_data ): array
{
    if ( str_contains( $sanitized_data['parameters'], 'id=')!==false || str_contains( $sanitized_data['parameters'], 'autoupdater_nonce=')!==false ) {
        $sanitized_data['referrer'] = 'spammer';
    }

    return $sanitized_data;
}
add_filter( 'before_burst_track_hit', 'set_parameter_to_spammer' );