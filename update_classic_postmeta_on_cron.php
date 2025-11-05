<?php

use Burst\Frontend\Frontend_Statistics;

function my_burst_update_postmeta() {
    $post_types = apply_filters( 'burst_column_post_types', [ 'post', 'page' ] );
    $posts      = get_posts(
        [
            'post_type'   => $post_types,
            'post_status' => 'publish',
            'numberposts' => -1,

        ]
    );

    if ( ! empty( $posts ) ) {
        foreach ( $posts as $post ) {
            $statistics = new Burst\Frontend\Frontend();
            $count      = $statistics->get_post_pageviews( $post->ID, 0, time() );
            error_log("count $count");
            update_post_meta( $post->ID, 'burst_total_pageviews_count', $count );
        }
    }
}
add_action('burst_daily', 'my_burst_update_postmeta');
