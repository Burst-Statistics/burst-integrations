<?php
/**
 * Strip the parameter name from the parameters column
 * Version: 1.0
 */
function my_datatable_data_override( $data, $start, $end, $metrics, $filters, $group_by, $order_by, $limit) {
    return array_filter($data, function($item) {
        return !(isset($item['parameters']) && strpos($item['parameters'], 'burst') !== false);
    });
}
add_filter("burst_datatable_data", 'my_datatable_data_override', 10, 8 );