<?php
/**
 * Exclude additional user roles from Burst Statistics tracking.
 * Administrator is already excluded by default.
 *
 * @param string[] $roles The current array of excluded user roles.
 *
 * @return string[] Merged array including additional roles.
 */
function burst_add_roles_excluded_from_tracking( array $roles ): array {
    $add_roles = [ 'subscriber', 'contributor', 'author', 'editor' ];
    return array_merge( $roles, $add_roles );
}
add_filter( 'burst_roles_excluded_from_tracking', 'burst_add_roles_excluded_from_tracking', 10, 1 );
