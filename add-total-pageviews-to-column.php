<?php
/**
 * Add total pageviews to the post/page list table columns
 *
 * @param $columns
 * @return mixed
 */
function custom_admin_pageviews_column_title($columns) {
    global $wpdb, $typenow;

    if ($typenow) {
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : $typenow;

        // Construct query arguments from current filters
        $query_args = [
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'fields'         => 'ids', // Get only post IDs
        ];

        // Apply category filter if set
        if (!empty($_GET['category'])) {
            $query_args['cat'] = intval($_GET['category']);
        }

        // Apply taxonomy filters
        if (!empty($_GET['taxonomy']) && !empty($_GET[$_GET['taxonomy']])) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => sanitize_text_field($_GET['taxonomy']),
                    'field'    => 'id',
                    'terms'    => intval($_GET[$_GET['taxonomy']]),
                ],
            ];
        }

        // Apply author filter
        if (!empty($_GET['author'])) {
            $query_args['author'] = intval($_GET['author']);
        }

        // Apply date filter
        if (!empty($_GET['m'])) {
            $query_args['date_query'] = [
                [
                    'year'  => substr($_GET['m'], 0, 4),
                    'month' => substr($_GET['m'], 4, 2),
                ],
            ];
        }

        // Get filtered posts/pages
        $query = new WP_Query($query_args);
        $post_ids = $query->posts;

        // Calculate total pageviews
        if (!empty($post_ids)) {
            $ids_placeholder = implode(',', array_fill(0, count($post_ids), '%d'));
            $total_pageviews = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(meta_value + 0) FROM $wpdb->postmeta WHERE meta_key = %s AND post_id IN ($ids_placeholder)",
                array_merge(['burst_total_pageviews_count'], $post_ids)
            ));
        } else {
            $total_pageviews = 0;
        }

        // Append total to the column title
        $columns['pageviews'] = sprintf(__('Pageviews (%s)', 'burst-statistics'), number_format($total_pageviews));
    }

    return $columns;
}
add_filter('manage_edit-post_columns', 'custom_admin_pageviews_column_title');
add_filter('manage_edit-page_columns', 'custom_admin_pageviews_column_title');