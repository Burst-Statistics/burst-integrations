<?php
/**
 * Customize the minimum archive months for Burst Statistics
 *
 * By default, data older than 12 months can be archived.
 * Use this filter to change the minimum age before archiving.
 *
 * @param int $months Number of months (default: 12)
 * @return int Modified number of months
 */
add_filter( 'burst_minimum_archive_months', function( $months ) {
    // Set to 3 months instead of the default 12
    return 3;
} );