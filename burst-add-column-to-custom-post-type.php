<?php
/**
 * Add custom post types counts to column
 * @param $roles
 * @return array
 */
function burst_add_column_to_custom_post_type( $post_types ) {
	$add_post_types = array( 'sportpark' );
	return array_merge( $post_types, $add_post_types );
}
add_filter( 'burst_column_post_types', 'burst_add_column_to_custom_post_type', 10, 1 );