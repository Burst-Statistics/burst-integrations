<?php
/**
 * Filter out datatable rows where the parameters column contains 'burst'.
 *
 * @param array<int, array<string, mixed>> $data The datatable rows.
 * @param object                           $qd   The Query_Data object.
 *
 * @return array<int, array<string, mixed>>
 */
function my_datatable_data_override( array $data, object $qd ): array {
    return array_filter( $data, function ( array $item ): bool {
        return ! ( isset( $item['parameters'] ) && str_contains( $item['parameters'], 'burst' ) );
    } );
}
add_filter( 'burst_datatable_data', 'my_datatable_data_override', 10, 2 );
