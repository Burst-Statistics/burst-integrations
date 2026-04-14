<?php
/**
 * Add custom post types to the Burst Statistics column tracking.
 *
 * @param string[] $post_types The current array of tracked post types.
 *
 * @return string[] Merged array including the custom post type.
 */
function burst_add_column_to_custom_post_type( array $post_types ): array {
    $add_post_types = [ 'sportpark' ];
    return array_merge( $post_types, $add_post_types );
}
add_filter( 'burst_column_post_types', 'burst_add_column_to_custom_post_type', 10, 1 );
