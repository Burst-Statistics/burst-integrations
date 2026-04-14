<?php
/**
 * Strip the parameter name from the parameters column, keeping only the value.
 *
 * @param array<int, array<string, mixed>> $data The datatable rows.
 * @param object                           $qd   The Query_Data object.
 *
 * @return array<int, array<string, mixed>>
 */
function my_datatable_data_override( array $data, object $qd ): array {
    foreach ( $data as &$item ) {
        if ( isset( $item['parameters'] ) ) {
            $parts              = explode( '=', $item['parameters'], 2 );
            $item['parameters'] = $parts[1] ?? $item['parameters'];
        }
    }

    return $data;
}
add_filter( 'burst_datatable_data', 'my_datatable_data_override', 10, 2 );
