<?php
/**
 * Filter the datatables query output to show post titles instead of URLs.
 * This may be too slow in some cases and should be used with caution.
 *
 * @param array<int, array<string, mixed>> $data     The datatable rows.
 * @param object                           $qd       The Query_Data object.
 *
 * @return array<int, array<string, mixed>>
 */
function my_datatable_data_override( array $data, object $qd ): array {
    foreach ( $data as $index => $item ) {
        $page = get_page_by_path( $item['page_url'] ?? '' );
        if ( $page ) {
            $data[ $index ]['page_url'] = $page->post_title;
        }
    }
    return $data;
}
add_filter( 'burst_datatable_data', 'my_datatable_data_override', 10, 2 );
