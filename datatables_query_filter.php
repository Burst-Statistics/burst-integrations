<?php

/**
 * Filter the datatables query output to show post titles instead of urls.
 * This may be too slow in some cases and should be used with caution.
 *
 * @param $data
 * @param $start
 * @param $end
 * @param $metrics
 * @param $filters
 * @param $group_by
 * @param $order_by
 * @param $limit
 * @return array
 */
function my_datatable_data_override( $data, $start, $end, $metrics, $filters, $group_by, $order_by, $limit) {
    foreach ($data as $index => $item) {
        //maybe add a condition here to limit the number of times this code runs
        $page = get_page_by_path($item['page_url']);
        if ( $page ){
            $data[$index]['page_url'] = $page->post_title;
        }
    }
    return $data;
}
add_filter("burst_datatable_data", 'my_datatable_data_override', 10, 8 );