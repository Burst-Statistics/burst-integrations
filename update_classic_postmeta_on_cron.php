<?php

use Burst\Frontend\Frontend;

/**
 * Daily cron callback to update `burst_total_pageviews_count` postmeta for all posts.
 *
 * Iterates over all published posts of the configured post types and stores
 * the all-time pageview count as postmeta.
 *
 * @return void
 */
function my_burst_update_postmeta(): void {
    $post_types = apply_filters( 'burst_column_post_types', [ 'post', 'page' ] );
    $posts      = get_posts(
        [
            'post_type'   => $post_types,
            'post_status' => 'publish',
            'numberposts' => -1,
        ]
    );

    if ( ! empty( $posts ) ) {
        $statistics = new Frontend();
        foreach ( $posts as $post ) {
            $count = $statistics->get_post_pageviews( $post->ID, 0, time() );
            update_post_meta( $post->ID, 'burst_total_pageviews_count', $count );
        }
    }
}
add_action( 'burst_daily', 'my_burst_update_postmeta' );
