<?php
function burst_change_decimal_separator_to( $decimal_seperator ) {
    $decimal_seperator = ',';
    // Don't edit below this line
    return $decimal_seperator;
}

add_filter( 'burst_change_decimal_separator', 'burst_change_decimal_separator_to', 10, 1 );


function burst_change_thousand_separator_to( $thousand_seperator )
{
    $thousand_seperator = '.';
    // Don't edit below this line
    return $thousand_seperator;
}

add_filter('burst_change_thousand_separator', 'burst_change_thousand_separator_to', 10, 1);
