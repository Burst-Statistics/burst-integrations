<?php
/**
 * Load an IP blocklist from an external text file and merge it with the existing blocklist.
 *
 * @param string[] $ip_blocklist The current array of blocked IP addresses.
 *
 * @return string[] Merged blocklist including IPs from the text file.
 */
function burst_add_ip_blocklist( array $ip_blocklist ): array {
    $file = file( __DIR__ . '/burst-ip-blocklist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
    if ( is_array( $file ) ) {
        foreach ( $file as $line ) {
            $ip_blocklist[] = trim( $line );
        }
    }
    return $ip_blocklist;
}
add_filter( 'burst_ip_blocklist', 'burst_add_ip_blocklist', 10, 1 );
