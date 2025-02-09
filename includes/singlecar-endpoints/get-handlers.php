<?php

function get_singlecar($request) {
    $params = $request->get_params();
    $paged = isset($params['page']) ? (int) $params['page'] : 1;
    $posts_per_page = isset($params['per_page']) ? (int) $params['per_page'] : 10;

    $args = array(
        'post_type' => 'singlecar',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'post_status' => 'publish'
    );

    if (!empty($params['search'])) {
        $args['s'] = sanitize_text_field($params['search']);
    }

    if (isset($params['anunci-actiu'])) {
        $anunci_actiu = filter_var($params['anunci-actiu'], FILTER_VALIDATE_BOOLEAN);
        $args['meta_query'] = array(
            array(
                'key' => 'anunci-actiu',
                'value' => $anunci_actiu ? 'true' : 'false',
                'compare' => '='
            )
        );
    }

    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return new WP_REST_Response(array(
            'items' => array(),
            'total' => 0,
            'pages' => 0,
            'page' => $paged
        ), 200);
    }

    $vehicles = array();
    while ($query->have_posts()) {
        $query->the_post();
        $vehicle_id = get_the_ID();
        $vehicle_details = get_vehicle_details_common($vehicle_id);
        if (!is_wp_error($vehicle_details)) {
            $vehicles[] = $vehicle_details->get_data();
        }
    }
    wp_reset_postdata();

    return new WP_REST_Response(array(
        'items' => $vehicles,
        'total' => (int) $query->found_posts,
        'pages' => (int) $query->max_num_pages,
        'page' => (int) $paged
    ), 200);
}

function get_vehicle_details($request) {
    $vehicle_id = $request['id'];
    return get_vehicle_details_common($vehicle_id);
}

function get_vehicle_details_by_slug($request) {
    $slug = $request['slug'];
    $post = get_page_by_path($slug, OBJECT, 'singlecar');
    if (!$post) {
        return new WP_Error('no_vehicle', 'Vehicle not found', ['status' => 404]);
    }
    return get_vehicle_details_common($post->ID);
}

function get_vehicle_details_common($vehicle_id) {
    if (!verify_post_ownership($vehicle_id)) {
        return new WP_Error('forbidden_access', 'No tienes permiso para ver este vehículo', ['status' => 403]);
    }

    $post = get_post($vehicle_id);
    if (!$post || $post->post_type !== 'singlecar') {
        return new WP_Error('no_vehicle', 'Vehicle not found', ['status' => 404]);
    }

    $meta = get_post_meta($vehicle_id);
    $terms = wp_get_post_terms($vehicle_id, 'types-of-transport', ['fields' => 'all']);
    $marques_terms = wp_get_post_terms($vehicle_id, 'marques-coches', ['fields' => 'all']);

    $response = [
        'id' => $vehicle_id,
        'slug' => $post->post_name,
        'titol-anunci' => get_the_title($vehicle_id),
        'anunci-actiu' => true,
        'descripcio-anunci' => $post->post_content
    ];

    if (!empty($terms)) {
        $response['tipus-vehicle'] = $terms[0]->name;
    }

    $estat_vehicle = wp_get_post_terms($vehicle_id, 'estat-vehicle');
    if (!empty($estat_vehicle) && !is_wp_error($estat_vehicle)) {
        $response['estat-vehicle'] = $estat_vehicle[0]->name;
    }

    if (!empty($marques_terms)) {
        foreach ($marques_terms as $term) {
            if ($term->parent === 0) {
                $response['marques-cotxe'] = $term->name;
            } else {
                $response['models-cotxe'] = $term->name;
            }
        }
    }

    $dies_caducitat = intval(get_post_meta($vehicle_id, 'dies-caducitat', true));
    $data_creacio = strtotime($post->post_date);
    $data_actual = current_time('timestamp');
    $dies_transcorreguts = floor(($data_actual - $data_creacio) / (60 * 60 * 24));
    
    if ($dies_transcorreguts > $dies_caducitat) {
        $response['anunci-actiu'] = false;
    }

    process_meta_fields($meta, $response);
    add_image_data($vehicle_id, $response);

    if (!current_user_can('administrator')) {
        unset($response['dies-caducitat']);
    }

    return new WP_REST_Response($response, 200);
}

function process_meta_fields($meta, &$response) {
    foreach ($meta as $key => $value) {
        if (!Vehicle_Fields::should_exclude_field($key)) {
            $meta_value = is_array($value) ? $value[0] : $value;
            $mapped_key = map_field_key($key);
            
            if (should_get_field_label($mapped_key)) {
                $response[$mapped_key] = get_field_label($key, $meta_value);
            } else {
                $response[$mapped_key] = $meta_value;
            }
        }
    }
}

function add_image_data($vehicle_id, &$response) {
    $response['imatge-destacada-url'] = get_the_post_thumbnail_url($vehicle_id, 'full');

    $gallery_ids = get_post_meta($vehicle_id, 'ad_gallery', true);
    $gallery_urls = [];
    
    if (!empty($gallery_ids)) {
        $gallery_ids = is_array($gallery_ids) ? $gallery_ids : explode(',', $gallery_ids);
        foreach ($gallery_ids as $gallery_id) {
            $url = wp_get_attachment_url(trim($gallery_id));
            if ($url) {
                $gallery_urls[] = $url;
            }
        }
    }
    
    if (!empty($gallery_urls)) {
        $response['galeria-vehicle-urls'] = $gallery_urls;
    }
}

function debug_vehicle_fields() {
    $post_type = 'vehicle';
    $meta_fields = jet_engine()->meta_boxes->get_meta_fields_for_object($post_type);

    return new WP_REST_Response([
        'fields' => $meta_fields
    ], 200);
}
