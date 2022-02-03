<?php
function burst_change_bounce_time( $bounce_time ) {
    // The default way of measuring a bounce is as follows:
    // If someone leaves the site within 1 pageview and within 5 seconds we will identify that data a bounce.
    // Below you can change the 5 seconds
    // Change the number below to your preferred time in seconds.
    $bounce_in_seconds = 10;

    // Don't edit below this line
    return $bounce_in_seconds * 1000;
}

add_filter( 'burst_bounce_time', 'burst_change_bounce_time', 10, 1 );
