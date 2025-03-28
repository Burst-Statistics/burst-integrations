<?php

/**
 * Exclude a page from tracking by setting the referrer to spammer
 * Add this function in the wp-config.php, as an mu-plugin is not laoded during the track hit process.
 *
 * @param $sanitized_data
 * @return mixed
 */

function set_referrer_to_spammer( $sanitized_data ) {
    $url = 	$sanitized_data['host'] . $sanitized_data['page_url'];
    if ( str_contains($url, 'my-page-url') ){
        //we use a trick, by setting the referrer to spammer, this page won't get tracked.
        $sanitized_data['referrer'] = 'spammer';
    }
    


    return $sanitized_data;
}
add_filter( 'burst_before_track_hit', 'set_referrer_to_spammer' );