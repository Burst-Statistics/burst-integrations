<?php


use Burst\Admin\Statistics\Statistics;
use Burst\Frontend\Sessions;
use Burst\Pro\Pro_Statistics;

function my_install_tables() {
    $upgraded = get_option('burst_upgraded_custom', 0);
    if ( $upgraded<3 ) {
        $upgraded++;
        $sessions = new Sessions();
        $sessions->install_sessions_table();
        $pro_statistics = new Pro_Statistics();
        $pro_statistics->install_locations_table();
        $pro_statistics->install_campaigns_table();
        $pro_statistics->install_parameters_table();
        $stats = new Statistics();
        $stats->install_statistics_table();
    }
    update_option('burst_upgraded_custom', $upgraded, false );
}
add_action('init', 'my_install_tables');
