<?php

function get_singlecar($request) {
    $cache_key = 'vehicles_list_' . md5(serialize($request->get_params()));
    delete_transient($cache_key);
    $params = $request->get_params();
    
    // Construir y ejecutar consulta
    $args = build_query_args($params);
    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return new WP_REST_Response([
            'status' => 'success',
            'items' => [],
            'total' => 0,
            'pages' => 0,
            'page' => $args['paged']
        ], 200);
    }

    // Procesar resultados
    $vehicles = process_query_results($query);
    wp_reset_postdata();

    // Contar solo los posts que realmente se procesaron
    $total_processed = count($vehicles);

    $response = [
        'status' => 'success',
        'items' => $vehicles,
        'total' => $total_processed,                    // Usar el contador real
        'pages' => ceil($total_processed / $args['posts_per_page']), // Recalcular páginas
        'page' => (int) $args['paged']
    ];

    // Enviar headers para control de caché
    return new WP_REST_Response($response, 200, [
        'Cache-Control' => 'no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT'
    ]);
}

function build_query_args($params) {
    $args = [
        'post_type' => 'singlecar',
        'posts_per_page' => isset($params['per_page']) ? (int) $params['per_page'] : 10,
        'paged' => isset($params['page']) ? (int) $params['page'] : 1,
        'post_status' => 'publish',
        'orderby' => isset($params['orderby']) ? sanitize_text_field($params['orderby']) : 'date',
        'order' => isset($params['order']) ? sanitize_text_field($params['order']) : 'DESC'
    ];

    // Meta queries solo si hay filtros específicos
    $meta_query = ['relation' => 'AND'];
    $apply_meta_query = false;

    // Lógica de filtrado por usuario
    if (!empty($params['user_id'])) {
        // Si se especifica un user_id
        $user_id = (int) $params['user_id'];
        
        // Verificar permisos
        if (!current_user_can('administrator')) {
            if (get_current_user_id() != $user_id) {
                throw new Exception('No autorizado para ver vehículos de otros usuarios');
            }
        }
        $args['author'] = $user_id;
    } else {
        // Si no se especifica user_id
        if (!current_user_can('administrator')) {
            // Usuario normal: solo ver sus propios vehículos
            $current_user_id = get_current_user_id();
            if ($current_user_id) {
                $args['author'] = $current_user_id;
            }
        }
    }

    // Aplicar filtros solo si se especifican en los parámetros
    if (isset($params['anunci-actiu'])) {
        $is_active = filter_var($params['anunci-actiu'], FILTER_VALIDATE_BOOLEAN);
        add_active_status_query($meta_query, $is_active);
        $apply_meta_query = true;
    }

    if (isset($params['venut'])) {
        $is_sold = filter_var($params['venut'], FILTER_VALIDATE_BOOLEAN);
        $meta_query[] = [
            'key' => 'venut',
            'value' => $is_sold ? 'true' : 'false',
            'compare' => '='
        ];
        $apply_meta_query = true;
    }

    // Aplicar meta queries solo si hay filtros
    if ($apply_meta_query) {
        $args['meta_query'] = $meta_query;
    }
    
    return $args;
}

function add_active_status_query(&$meta_query, $is_active) {
    if ($is_active) {
        $meta_query[] = [
            'relation' => 'AND',
            [
                'key' => 'anunci-actiu',
                'value' => 'true',
                'compare' => '='
            ],
            [
                'key' => 'dies-caducitat',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC'
            ]
        ];
    } else {
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key' => 'anunci-actiu',
                'value' => 'false',
                'compare' => '='
            ],
            [
                'key' => 'dies-caducitat',
                'value' => 0,
                'compare' => '<=',
                'type' => 'NUMERIC'
            ]
        ];
    }
}

function process_query_results($query) {
    $vehicles = [];
    
    while ($query->have_posts()) {
        $query->the_post();
        $vehicle_id = get_the_ID();
        
        // Solo procesar posts publicados y verificar estado activo
        if (get_post_status($vehicle_id) === 'publish') {
            try {
                $vehicle_details = get_vehicle_details_common($vehicle_id);
                if (!is_wp_error($vehicle_details)) {
                    $response_data = $vehicle_details->get_data();
                    // Verificación adicional de estado activo si es necesario
                    $vehicles[] = $response_data;
                }
            } catch (Exception $e) {
            }
        }
    }
    
    return $vehicles;
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
    // Verificar JetEngine y post
    if (!function_exists('jet_engine')) {
        return new WP_Error('jet_engine_missing', 'JetEngine no está activo');
    }

    $post = get_post($vehicle_id);
    if (!$post || $post->post_type !== 'singlecar') {
        return new WP_Error('no_vehicle', 'Vehicle not found', ['status' => 404]);
    }

    // Obtener datos
    $meta = get_post_meta($vehicle_id);
    $taxonomies = [
        'types-of-transport' => 'tipus-vehicle',
        'marques-coches' => 'marques-cotxe',
        'estat-vehicle' => 'estat-vehicle',
        'tipus-de-propulsor' => 'tipus-propulsor',
        'tipus-combustible' => 'tipus-combustible',
        'marques-de-moto' => 'tipus-de-moto',
        'tipus-de-canvi' => 'tipus-canvi-cotxe'
    ];

    $terms_data = [];
    foreach ($taxonomies as $taxonomy => $field_name) {
        $terms = wp_get_post_terms($vehicle_id, $taxonomy, ['fields' => 'all']);
        if (!is_wp_error($terms) && !empty($terms)) {
            $terms_data[$field_name] = $terms[0];
        }
    }

    // Verificar permisos
    if (!verify_post_ownership($vehicle_id)) {
        return new WP_Error('forbidden_access', 'No tienes permiso para ver este vehículo', ['status' => 403]);
    }

    // Construir respuesta base con orden específico
    $response = [
        'id' => $vehicle_id,
        'data-creacio' => $post->post_date,
        'status' => $post->post_status,
        'slug' => $post->post_name,
        'titol-anunci' => get_the_title($vehicle_id),
        'descripcio-anunci' => $post->post_content,
        'anunci-actiu' => true,
        'anunci-destacat' => get_post_meta($vehicle_id, 'is-vip', true) === 'true'
    ];

    // Agregar tipus-vehicle primero
    if (isset($terms_data['tipus-vehicle'])) {
        $response['tipus-vehicle'] = $terms_data['tipus-vehicle']->name;
    }

    // Agregar marca y modelo inmediatamente después
    $marques_terms = wp_get_post_terms($vehicle_id, 'marques-coches', ['fields' => 'all']);
    if (!empty($marques_terms)) {
        foreach ($marques_terms as $term) {
            if ($term->parent === 0) {
                $response['marques-cotxe'] = $term->name;
                foreach ($marques_terms as $model_term) {
                    if ($model_term->parent === $term->term_id) {
                        $response['models-cotxe'] = $model_term->name;
                        break;
                    }
                }
                break;
            }
        }
    }

    // Agregar resto de términos de taxonomías
    foreach ($terms_data as $field => $term) {
        if (!in_array($field, ['tipus-vehicle', 'marques-cotxe', 'models-cotxe'])) {
            $response[$field] = $term->name;
        }
    }

    // Procesar campos adicionales
    process_meta_fields($meta, $response);
    add_image_data($vehicle_id, $response);

    // Procesar caducidad
    process_expiry($vehicle_id, $post, $response);

    // Eliminar campos sensibles para no administradores
    if (!current_user_can('administrator')) {
        unset($response['dies-caducitat']);
    }

    return new WP_REST_Response($response, 200);
}

// Nueva función para manejar la caducidad
function process_expiry($vehicle_id, $post, &$response) {
    $dies_caducitat = intval(get_post_meta($vehicle_id, 'dies-caducitat', true));
    $data_creacio = strtotime($post->post_date);
    $data_actual = current_time('timestamp');
    $dies_transcorreguts = floor(($data_actual - $data_creacio) / (60 * 60 * 24));
    
    if ($dies_transcorreguts > $dies_caducitat) {
        $response['anunci-actiu'] = false;
    }
}

function process_meta_fields($meta, &$response) {
    $skip_fields = [
        'tipus-vehicle',
        'tipus-combustible',
        'tipus-propulsor',
        'estat-vehicle',
        'tipus-de-moto',
        'tipus-canvi-cotxe',
        'marques-cotxe',
        'models-cotxe'
    ];

    foreach ($meta as $key => $value) {
        if (in_array($key, $skip_fields) || isset($response[$key]) || Vehicle_Fields::should_exclude_field($key)) {
            continue;
        }

        $meta_value = is_array($value) ? $value[0] : $value;
        $mapped_key = map_field_key($key);
        
        $response[$mapped_key] = should_get_field_label($mapped_key) ? 
            get_field_label($key, $meta_value) : 
            $meta_value;
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

function debug_vehicle_fields(WP_REST_Request $request) {
    // Verificar si el usuario es administrador
    if (!current_user_can('administrator')) {
        return new WP_REST_Response([
            'status' => 'error',
            'message' => 'No tienes permisos para acceder a esta información'
        ], 403);
    }

    try {
        if (!function_exists('jet_engine')) {
            throw new Exception('JetEngine no está activo');
        }

        // Obtener campos meta
        $meta_fields = [];
        $fields = Vehicle_Fields::get_meta_fields();
        foreach ($fields as $field => $type) {
            $meta_fields[] = [
                'label' => ucfirst(str_replace('-', ' ', $field)),
                'value' => $field,
                'type' => $type
            ];
        }

        // Obtener taxonomías
        $taxonomies = get_object_taxonomies('singlecar', 'objects');
        $taxonomy_fields = [];
        foreach ($taxonomies as $tax_slug => $tax) {
            $taxonomy_fields[] = [
                'label' => $tax->label,
                'value' => $tax_slug,
                'type' => 'taxonomy'
            ];
        }

        // Obtener glosarios
        $glossary_fields = [];
        if (isset(jet_engine()->glossaries)) {
            $glossaries = jet_engine()->glossaries->get_glossaries_for_js();
            foreach ($glossaries as $glossary) {
                $glossary_fields[] = [
                    'label' => $glossary['label'],
                    'value' => $glossary['value'],
                    'type' => 'glossary'
                ];
            }
        }

        // Combinar todos los campos
        $all_fields = array_merge($meta_fields, $taxonomy_fields, $glossary_fields);

        return new WP_REST_Response([
            'status' => 'success',
            'total' => count($all_fields),
            'data' => $all_fields
        ], 200);

    } catch (Exception $e) {
        return new WP_REST_Response([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}