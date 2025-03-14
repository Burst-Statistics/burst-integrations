<?php
/**
 * Strip the parameter name from the parameters column
 * Version: 1.0
 */
function my_datatable_data_override( $data, $start, $end, $metrics, $filters, $group_by, $order_by, $limit) {

    error_log(print_r($data, true));
    foreach ($data as &$item) {
        if (isset($item['parameters'])) {
            $parts = explode('=', $item['parameters'], 2);
            $item['parameters'] = $parts[1] ?? $item['parameters'];
        }
    }
    return $data;
}
add_filter("burst_datatable_data", 'my_datatable_data_override', 10, 8 );