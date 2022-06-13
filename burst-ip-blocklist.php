<?php
function burst_add_ip_blocklist( $ip_blocklist ) {
	$file = file(dirname(__FILE__) . '/burst-ip-blocklist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($file)) {
	    foreach ( $file as $line ) {
		    $ip_blocklist[] = trim( $line );
	    }
    }
	return $ip_blocklist;
}

add_filter( 'burst_ip_blocklist', 'burst_add_ip_blocklist', 10, 1 );
