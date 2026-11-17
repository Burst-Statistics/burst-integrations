<?php
/**
 * Plugin Name: Burst – retry GeoIP download
 * Description: Temporary helper. Open /wp-admin/?burst_retry_geoip=1 as an administrator, then delete this file.
 */

defined( 'ABSPATH' ) || die();

// Priority 1: runs before Burst starts its own import check on admin_init.
add_action(
    'admin_init',
    static function (): void {
        if ( ! isset( $_GET['burst_retry_geoip'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Clear the retry backoff and the 5 minute throttle.
        delete_option( 'burst_geo_ip_failed_attempts' );
        delete_option( 'burst_geo_ip_first_failed_attempt' );
        delete_option( 'burst_geo_ip_last_failed_attempt' );
        delete_option( 'burst_geo_ip_last_attempt' );
        delete_transient( 'burst_importing' );

        // Burst downloads the database later in this same request when this is set.
        update_option( 'burst_import_geo_ip_on_activation', true, false );

        // Report the outcome once Burst has run.
        add_action(
            'admin_notices',
            static function (): void {
                $error = get_option( 'burst_geo_ip_import_error' );
                $file  = (string) get_option( 'burst_geo_ip_file', '' );
                $ok    = ! $error && '' !== $file && file_exists( $file );
                printf(
                    '<div class="notice notice-%s"><p>%s</p></div>',
                    $ok ? 'success' : 'error',
                    esc_html( $ok ? 'Burst GeoIP database downloaded: ' . $file : 'Burst GeoIP download failed: ' . ( $error ?: 'unknown error' ) )
                );
            }
        );
    },
    1
);
