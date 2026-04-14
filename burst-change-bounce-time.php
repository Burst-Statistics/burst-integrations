<?php
/**
 * Customize the bounce time threshold for Burst Statistics.
 *
 * The default bounce detection: if someone leaves the site within 1 pageview
 * and within 5 seconds, the visit is identified as a bounce.
 *
 * The filter value is in milliseconds.
 *
 * @param int $bounce_time_seconds The bounce time threshold in ms (default: 5000).
 *
 * @return int Modified bounce time in seconds.
 */
function burst_change_bounce_time( int $bounce_time_seconds ): int {
    // Change the number below to your preferred time in seconds.
    $bounce_in_seconds = 10;

    // Don't edit below this line
    return $bounce_in_seconds * 1000;
}
add_filter( 'burst_bounce_time', 'burst_change_bounce_time', 10, 1 );
