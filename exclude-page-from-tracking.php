<?php

/**
 * Exclude a page from tracking by setting the referrer to spammer
 *
 * @param $sanitized_data
 * @return mixed
 */

function set_referrer_to_spammer( $sanitized_data ) {
    $url = 	trailingslashit( $sanitized_data['host']) . $sanitized_data['page_url'];
    if ( str_contains($url, 'my-page-url') ){
        //we use a trick, by setting the referrer to spammer, this page won't get tracked.
        $sanitized_data['referrer'] = 'spammer';
    }

    return $sanitized_data;
}
add_filter( 'before_burst_track_hit', 'set_referrer_to_spammer' );