<?php
/**
 * Add additional user roles to exclude from tracking
 * @param $roles
 * @return array
 */
function burst_add_roles_excluded_from_tracking( $roles ) {
    // Add your roles below
    // Administrator is already excluded from tracking
    $add_roles = array( 'subscriber', 'contributor', 'author', 'editor' );
    return array_merge( $roles, $add_roles );
}
add_filter( 'burst_roles_excluded_from_tracking', 'burst_add_roles_excluded_from_tracking', 10, 1 );