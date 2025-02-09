<?php

function get_singlecar($request) {
    error_log('=== INICIO GET VEHICLES LIST ===');
    
    // Limpiar caché transient
    $cache_key = 'vehicles_list_' . md5(serialize($request->get_params()));
    delete_transient($cache_key);

    $params = $request->get_params();
    error_log('Parámetros recibidos: ' . print_r($params, true));

    // Construir argumentos de consulta
    $args = build_query_args($params);
    error_log('Argumentos de consulta: ' . print_r($args, true));

    // Ejecutar consulta
    $query = new WP_Query($args);
    error_log('Total posts encontrados: ' . $query->found_posts);

    // Si no hay resultados, devolver respuesta vacía
    if (!$query->have_posts()) {
        error_log('No se encontraron vehículos');
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

    error_log('Total posts encontrados en WP_Query: ' . $query->found_posts);
    error_log('Total posts procesados realmente: ' . $total_processed);
    error_log('=== FIN GET VEHICLES LIST ===');
    
    // Enviar headers para control de caché
    return new WP_REST_Response($response, 200, [
        'Cache-Control' => 'no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT'
    ]);
}

function build_query_args($params) {
    error_log('=== INICIO BUILD QUERY ARGS ===');
    
    $args = [
        'post_type' => 'singlecar',
        'posts_per_page' => isset($params['per_page']) ? (int) $params['per_page'] : 10,
        'paged' => isset($params['page']) ? (int) $params['page'] : 1,
        'post_status' => 'publish',
        'orderby' => isset($params['orderby']) ? sanitize_text_field($params['orderby']) : 'date',
        'order' => isset($params['order']) ? sanitize_text_field($params['order']) : 'DESC'
    ];

    // Meta queries con relación AND por defecto
    $meta_query = ['relation' => 'AND'];

    // Lógica de filtrado por usuario
    if (!empty($params['user_id'])) {
        // Si se especifica un user_id
        $user_id = (int) $params['user_id'];
        error_log("Filtrando por usuario específico ID: $user_id");
        
        // Verificar permisos
        if (!current_user_can('administrator')) {
            if (get_current_user_id() != $user_id) {
                error_log("Usuario no autorizado para ver otros usuarios");
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
                error_log("Usuario normal: mostrando solo vehículos propios (ID: $current_user_id)");
                $args['author'] = $current_user_id;
            } else {
                error_log("Usuario no autenticado: mostrando solo vehículos activos públicos");
                add_active_status_query($meta_query, true);
            }
        } else {
            // Administrador: ver todos los vehículos
            error_log("Administrador: mostrando todos los vehículos");
        }
    }

    // Estado del anuncio
    if (isset($params['anunci-actiu'])) {
        $is_active = filter_var($params['anunci-actiu'], FILTER_VALIDATE_BOOLEAN);
        add_active_status_query($meta_query, $is_active);
    }

    // Aplicar meta queries si hay condiciones
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    error_log('Query final: ' . print_r($args, true));
    error_log('=== FIN BUILD QUERY ARGS ===');
    
    return $args;
}

function add_active_status_query(&$meta_query, $is_active) {
    error_log("Agregando filtro de estado activo: " . ($is_active ? 'true' : 'false'));
    
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
                error_log('Error procesando vehículo ' . $vehicle_id . ': ' . $e->getMessage());
            }
        } else {
            error_log('Post ' . $vehicle_id . ' ignorado por no estar publicado');
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
    error_log('=== INICIO GET VEHICLE DETAILS ===');
    error_log('Vehicle ID: ' . $vehicle_id);

    // Verificar JetEngine
    if (!function_exists('jet_engine')) {
        error_log('JetEngine no está activo');
        return new WP_Error('jet_engine_missing', 'JetEngine no está activo');
    }

    // Verificar post y permisos
    $post = get_post($vehicle_id);
    if (!$post || $post->post_type !== 'singlecar') {
        error_log('Post no encontrado o tipo incorrecto');
        return new WP_Error('no_vehicle', 'Vehicle not found', ['status' => 404]);
    }

    // Obtener meta campos
    $meta = get_post_meta($vehicle_id);
    error_log('Meta campos obtenidos: ' . print_r($meta, true));

    // Obtener términos
    $terms = wp_get_post_terms($vehicle_id, 'types-of-transport', ['fields' => 'all']);
    $marques_terms = wp_get_post_terms($vehicle_id, 'marques-coches', ['fields' => 'all']);
    
    error_log('Términos types-of-transport: ' . print_r($terms, true));
    error_log('Términos marques-coches: ' . print_r($marques_terms, true));

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
        'descripcio-anunci' => $post->post_content,
        'data-creacio' => $post->post_date, // Agregar fecha de creación
        'status' => $post->post_status // Agregar estado del post
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

    // Log final response
    error_log('Respuesta final: ' . print_r($response, true));
    error_log('=== FIN GET VEHICLE DETAILS ===');

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