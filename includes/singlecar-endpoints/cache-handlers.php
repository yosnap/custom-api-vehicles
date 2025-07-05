<?php

function clear_vehicle_cache($post_id = null) {
    if ($post_id) {
        delete_transient('vehicle_details_' . $post_id);
    }

    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vehicles_list_%' OR option_name LIKE '_transient_timeout_vehicles_list_%'");
}
