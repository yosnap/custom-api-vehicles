<?php

function get_all_singlecar($request) {
    $params = $request->get_params();
    
    // Cache condicional basado en configuración
    $cache_enabled = get_option('vehicles_api_cache_enabled', '0');
    if ($cache_enabled === '1') {
        $cache_key = 'vehicles_all_list_' . md5(serialize($params));
        $cached_response = get_transient($cache_key);
        if (false !== $cached_response) {
            return new WP_REST_Response($cached_response, 200);
        }
    }
    
    // Construir argumentos básicos SIN filtros por defecto
    $args = [
        'post_type' => 'singlecar',
        'posts_per_page' => isset($params['per_page']) ? (int) $params['per_page'] : 10,
        'paged' => isset($params['page']) ? (int) $params['page'] : 1,
        'post_status' => 'any', // Incluir todos los estados de posts
        'no_found_rows' => false,
        'update_post_term_cache' => true,
        'update_post_meta_cache' => true
    ];
    
    // Agregar ordenamiento si se especifica
    if (isset($params['orderby'])) {
        $args['orderby'] = $params['orderby'];
    }
    if (isset($params['order'])) {
        $args['order'] = $params['order'];
    }
    
    // Solo aplicar filtros si se pasan explícitamente en los parámetros
    if (isset($params['venut'])) {
        $args['meta_query'] = [
            [
                'key' => 'venut',
                'value' => filter_var($params['venut'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
                'compare' => '='
            ]
        ];
    }
    
    if (isset($params['anunci-actiu'])) {
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = [];
        }
        $args['meta_query'][] = [
            'key' => 'anunci-actiu',
            'value' => filter_var($params['anunci-actiu'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            'compare' => '='
        ];
    }
    
    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return new WP_REST_Response([
            'status' => 'success',
            'items' => [],
            'total' => 0,
            'pages' => 0,
            'page' => $args['paged'],
            'per_page' => $args['posts_per_page']
        ], 200);
    }

    $vehicles = process_query_results($query);
    $total_items = $query->found_posts;
    wp_reset_postdata();

    $response = [
        'status' => 'success',
        'items' => $vehicles,
        'total' => $total_items,
        'pages' => ceil($total_items / $args['posts_per_page']),
        'page' => (int) $args['paged'],
        'per_page' => (int) $args['posts_per_page']
    ];

    // Guardar en cache si está habilitado
    if ($cache_enabled === '1') {
        set_transient($cache_key, $response, 300); // 5 minutos
    }

    return new WP_REST_Response($response, 200);
}

function get_singlecar($request) {
    $params = $request->get_params();
    
    // Cache condicional basado en configuración
    $cache_enabled = get_option('vehicles_api_cache_enabled', '0');
    if ($cache_enabled === '1') {
        $cache_key = 'vehicles_list_' . md5(serialize($params));
        $cached_response = get_transient($cache_key);
        if (false !== $cached_response) {
            return new WP_REST_Response($cached_response, 200);
        }
    }
    
    $args = build_query_args($params);
    
    // Debug de la query final
    if (isset($params['anunci-actiu'])) {
        Vehicle_Debug_Handler::log('Final WP_Query args: ' . json_encode($args));
        Vehicle_Debug_Handler::log('Order parameter: ' . (isset($params['order']) ? $params['order'] : 'not set'));
        Vehicle_Debug_Handler::log('Orderby parameter: ' . (isset($params['orderby']) ? $params['orderby'] : 'not set'));
    }
    
    $query = new WP_Query($args);
    
    // Debug de los resultados
    if (isset($params['anunci-actiu'])) {
        $post_ids = wp_list_pluck($query->posts, 'ID');
        Vehicle_Debug_Handler::log('Query returned ' . count($post_ids) . ' posts: ' . implode(', ', $post_ids));
    }

    if (!$query->have_posts()) {
        return new WP_REST_Response([
            'status' => 'success',
            'items' => [],
            'total' => 0,
            'pages' => 0,
            'page' => $args['paged'],
            'per_page' => $args['posts_per_page']
        ], 200);
    }

    $vehicles = process_query_results($query);
    $total_items = $query->found_posts;
    wp_reset_postdata();

    // --- Facetas globales ---
    // Hacemos una consulta idéntica pero sin paginación para calcular los conteos globales
    $args_facets = $args;
    $args_facets['posts_per_page'] = -1;
    $args_facets['paged'] = 1;
    $query_facets = new WP_Query($args_facets);
    $vehicles_for_facets = process_query_results($query_facets);
    wp_reset_postdata();
    $facets = calculate_facets($vehicles_for_facets, $params);

    $response = [
        'status' => 'success',
        'items' => $vehicles,
        'total' => $total_items,
        'pages' => ceil($total_items / $args['posts_per_page']),
        'page' => (int) $args['paged'],
        'per_page' => (int) $args['posts_per_page'],
        'facets' => $facets
    ];

    // Cache condicional basado en configuración
    $cache_enabled = get_option('vehicles_api_cache_enabled', '0');
    if ($cache_enabled === '1') {
        $cache_duration = intval(get_option('vehicles_api_cache_duration', 3600));
        set_transient($cache_key, $response, $cache_duration);
    }

    return new WP_REST_Response($response, 200);
}

function build_query_args($params) {
    $args = [
        'post_type' => 'singlecar',
        'posts_per_page' => isset($params['per_page']) ? (int) $params['per_page'] : 10,
        'paged' => isset($params['page']) ? (int) $params['page'] : 1,
        'post_status' => 'publish',
        'no_found_rows' => false,
        'update_post_term_cache' => true,
        'update_post_meta_cache' => true
    ];

    // Meta queries y Tax queries
    $meta_query = ['relation' => 'AND'];
    $tax_query = ['relation' => 'AND'];
    $apply_meta_query = false;
    $apply_tax_query = false;

    // Lógica de filtrado por usuario
    if (!empty($params['user_id'])) {
        $user_id = (int) $params['user_id'];
        if (!current_user_can('administrator') && get_current_user_id() != $user_id) {
                throw new Exception('No autorizado para ver vehículos de otros usuarios');
        }
        $args['author'] = $user_id;
    } elseif (!current_user_can('administrator')) {
            $current_user_id = get_current_user_id();
            if ($current_user_id) {
                $args['author'] = $current_user_id;
            }
        }

    // Filtros de taxonomías
    $taxonomy_filters = [
        'tipus-vehicle' => 'types-of-transport',
        'tipus-combustible' => 'tipus-combustible',
        'tipus-canvi' => 'tipus-de-canvi',
        'tipus-propulsor' => 'tipus-de-propulsor',
        'estat-vehicle' => 'estat-vehicle',
        'marques-cotxe' => 'marques-coches',
        'marques-autocaravana' => 'marques-coches',
        'marques-comercial' => 'marques-coches',
        'marques-moto' => 'marques-de-moto'
    ];

    foreach ($taxonomy_filters as $param => $taxonomy) {
        if (isset($params[$param]) && !empty($params[$param])) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $params[$param]
            ];
            $apply_tax_query = true;
        }
    }

    // Filtro específico para modelos de coches
    if (isset($params['models-cotxe']) && !empty($params['models-cotxe'])) {
        // Si también se especificó una marca, usamos una relación AND
        if (isset($params['marques-cotxe']) && !empty($params['marques-cotxe'])) {
            $tax_query[] = [
                'relation' => 'AND',
                [
                    'taxonomy' => 'marques-coches',
                    'field' => 'slug',
                    'terms' => $params['marques-cotxe']
                ],
                [
                    'taxonomy' => 'marques-coches',
                    'field' => 'slug',
                    'terms' => $params['models-cotxe']
                ]
            ];
        } else {
            // Si no se especificó marca, solo filtramos por modelo
            $tax_query[] = [
                'taxonomy' => 'marques-coches',
                'field' => 'slug',
                'terms' => $params['models-cotxe']
            ];
        }
        $apply_tax_query = true;
    }

    // Filtro específico para modelos de autocaravanas
    if (isset($params['models-autocaravana']) && !empty($params['models-autocaravana'])) {
        // Si también se especificó una marca de autocaravana, usamos una relación AND
        if (isset($params['marques-autocaravana']) && !empty($params['marques-autocaravana'])) {
            $tax_query[] = [
                'relation' => 'AND',
                [
                    'taxonomy' => 'marques-coches',
                    'field' => 'slug',
                    'terms' => $params['marques-autocaravana']
                ],
                [
                    'taxonomy' => 'marques-coches',
                    'field' => 'slug',
                    'terms' => $params['models-autocaravana']
                ]
            ];
        } else {
            // Si no se especificó marca, solo filtramos por modelo
            $tax_query[] = [
                'taxonomy' => 'marques-coches',
                'field' => 'slug',
                'terms' => $params['models-autocaravana']
            ];
        }
        $apply_tax_query = true;
    }

    // Filtro específico para modelos de vehículos comerciales
    if (isset($params['models-comercial']) && !empty($params['models-comercial'])) {
        // Si también se especificó una marca comercial, usamos una relación AND
        if (isset($params['marques-comercial']) && !empty($params['marques-comercial'])) {
            $tax_query[] = [
                'relation' => 'AND',
                [
                    'taxonomy' => 'marques-coches',
                    'field' => 'slug',
                    'terms' => $params['marques-comercial']
                ],
                [
                    'taxonomy' => 'marques-coches',
                    'field' => 'slug',
                    'terms' => $params['models-comercial']
                ]
            ];
        } else {
            // Si no se especificó marca, solo filtramos por modelo
            $tax_query[] = [
                'taxonomy' => 'marques-coches',
                'field' => 'slug',
                'terms' => $params['models-comercial']
            ];
        }
        $apply_tax_query = true;
    }

    // Filtro específico para modelos de motos
    if (isset($params['models-moto']) && !empty($params['models-moto'])) {
        // Si también se especificó una marca de moto, usamos una relación AND
        if (isset($params['marques-moto']) && !empty($params['marques-moto'])) {
            $tax_query[] = [
                'relation' => 'AND',
                [
                    'taxonomy' => 'marques-de-moto',
                    'field' => 'slug',
                    'terms' => $params['marques-moto']
                ],
                [
                    'taxonomy' => 'marques-de-moto',
                    'field' => 'slug',
                    'terms' => $params['models-moto']
                ]
            ];
        } else {
            // Si no se especificó marca, solo filtramos por modelo
            $tax_query[] = [
                'taxonomy' => 'marques-de-moto',
                'field' => 'slug',
                'terms' => $params['models-moto']
            ];
        }
        $apply_tax_query = true;
    }

    // Filtros de rango numéricos
    $range_filters = [
        'preu' => ['min' => 'preu_min', 'max' => 'preu_max'],
        'quilometratge' => ['min' => 'km_min', 'max' => 'km_max'],
        'any' => ['min' => 'any_min', 'max' => 'any_max'],
        'potencia-cv' => ['min' => 'potencia_cv_min', 'max' => 'potencia_cv_max']
    ];

    foreach ($range_filters as $field => $range_params) {
        if (isset($params[$range_params['min']]) || isset($params[$range_params['max']])) {
            $range_query = [
                'key' => $field,
                'type' => 'NUMERIC'
            ];

            if (isset($params[$range_params['min']]) && isset($params[$range_params['max']])) {
                $range_query['compare'] = 'BETWEEN';
                $range_query['value'] = [
                    floatval($params[$range_params['min']]),
                    floatval($params[$range_params['max']])
                ];
            } elseif (isset($params[$range_params['min']])) {
                $range_query['compare'] = '>=';
                $range_query['value'] = floatval($params[$range_params['min']]);
            } else {
                $range_query['compare'] = '<=';
                $range_query['value'] = floatval($params[$range_params['max']]);
            }

            $meta_query[] = $range_query;
            $apply_meta_query = true;
        }
    }

    // Filtros booleanos
    $boolean_filters = [
        'venut',
        'llibre-manteniment',
        'revisions-oficials',
        'impostos-deduibles',
        'vehicle-a-canvi',
        'garantia',
        'vehicle-accidentat',
        'aire-acondicionat',
        'climatitzacio',
        'vehicle-fumador'
    ];

    foreach ($boolean_filters as $filter) {
        if ($filter === 'venut') {
            if (array_key_exists('venut', $params)) {
                // Si se pasa venut, filtrar solo por el valor exacto
                $meta_query[] = [
                    'key' => 'venut',
                    'value' => filter_var($params['venut'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
                    'compare' => '='
                ];
                $apply_meta_query = true;
            } else {
                // Si NO se pasa venut, mostrar los que no están vendidos o no tienen el campo
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key' => 'venut',
                        'value' => 'false',
                        'compare' => '='
                    ],
                    [
                        'key' => 'venut',
                        'compare' => 'NOT EXISTS'
                    ]
                ];
                $apply_meta_query = true;
            }
        } elseif (isset($params[$filter])) {
            $meta_query[] = [
                'key' => $filter,
                'value' => filter_var($params[$filter], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
                'compare' => '='
            ];
            $apply_meta_query = true;
        }
    }

    // Filtro especial para anunci-destacat (is-vip)
    if (isset($params['anunci-destacat'])) {
        $value = filter_var($params['anunci-destacat'], FILTER_VALIDATE_BOOLEAN) ? ['true'] : ['false'];
        $meta_query[] = [
            'key' => 'is-vip',
            'value' => $value,
            'compare' => 'IN'
        ];
        $meta_query[] = [
            'key' => 'is-vip',
            'compare' => 'EXISTS'
        ];
        $apply_meta_query = true;
    }

    // Filtros de glosario
    $glossary_filters = [
        'venedor',
        'traccio',
        'roda-recanvi',
        'segment',
        'color-vehicle',
        'tipus-tapisseria',
        'color-tapisseria',
        'emissions-vehicle',
        'extres-cotxe',
        'cables-recarrega',
        'connectors'
    ];

    foreach ($glossary_filters as $filter) {
        if (isset($params[$filter]) && !empty($params[$filter])) {
            $meta_query[] = [
                'key' => $filter,
                'value' => $params[$filter],
                'compare' => 'LIKE'
            ];
            $apply_meta_query = true;
        }
    }

    // Búsqueda por texto
    if (isset($params['search']) && !empty($params['search'])) {
        $args['s'] = sanitize_text_field($params['search']);
    }

    // Ordenamiento
    if (isset($params['orderby'])) {
        switch ($params['orderby']) {
            case 'featured':
                // Asegurar que todos los vehículos tengan el campo is-vip (aunque sea 0)
                $meta_query[] = [
                    'key' => 'is-vip',
                    'compare' => 'EXISTS'
                ];
                $apply_meta_query = true;
                $args['orderby'] = [
                    'meta_value' => 'DESC', // is-vip 'true' primero
                    'date' => 'DESC'
                ];
                $args['meta_key'] = 'is-vip';
                break;
            case 'price':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'preu';
                $args['order'] = isset($params['order']) && in_array(strtoupper($params['order']), ['ASC', 'DESC']) 
                    ? strtoupper($params['order']) 
                    : 'ASC';
                break;
            case 'date':
                $args['orderby'] = 'date';
                $args['order'] = isset($params['order']) && in_array(strtoupper($params['order']), ['ASC', 'DESC']) 
                    ? strtoupper($params['order']) 
                    : 'DESC';
                break;
            case 'title':
                $args['orderby'] = 'title';
                $args['order'] = isset($params['order']) && in_array(strtoupper($params['order']), ['ASC', 'DESC']) 
                    ? strtoupper($params['order']) 
                    : 'ASC';
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }
    } else {
        // Por defecto, destacados primero
        // Asegurar que todos los vehículos tengan el campo is-vip para poder ordenar
        $meta_query[] = [
            'key' => 'is-vip',
            'compare' => 'EXISTS'
        ];
        $apply_meta_query = true;
        $args['orderby'] = [
            'meta_value_num' => 'DESC',
            'date' => 'DESC'
        ];
        $args['meta_key'] = 'is-vip';
    }

    // Estado activo del anuncio
    if (isset($params['anunci-actiu']) && $params['anunci-actiu'] !== '') {
        // Convertir string 'true'/'false' a boolean
        $is_active = $params['anunci-actiu'] === 'true' || $params['anunci-actiu'] === true;
        Vehicle_Debug_Handler::log('anunci-actiu filter: param=' . $params['anunci-actiu'] . ', is_active=' . ($is_active ? 'true' : 'false'));
        add_active_status_query($meta_query, $is_active);
        $apply_meta_query = true;
    }

    // Aplicar queries
    if ($apply_meta_query) {
        $args['meta_query'] = $meta_query;
    }
    if ($apply_tax_query) {
        $args['tax_query'] = $tax_query;
    }
    
    return $args;
}

function add_active_status_query(&$meta_query, $is_active) {
    if ($is_active) {
        // Solo filtrar por anuncios activos
        $meta_query[] = [
            'key' => 'anunci-actiu',
            'value' => 'true',
            'compare' => '='
        ];
        Vehicle_Debug_Handler::log('Query meta added for active: anunci-actiu = true');
    } else {
        // Filtrar por anuncios inactivos o sin el campo definido
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key' => 'anunci-actiu',
                'value' => 'false',
                'compare' => '='
            ],
            [
                'key' => 'anunci-actiu',
                'compare' => 'NOT EXISTS'
            ]
        ];
        Vehicle_Debug_Handler::log('Query meta added for inactive: anunci-actiu = false OR NOT EXISTS');
    }
}

function process_query_results($query) {
    $vehicles = [];
    $posts = $query->posts;
    $post_ids = wp_list_pluck($posts, 'ID');

    if (empty($post_ids)) {
        return $vehicles;
    }

    // Pre-fetch all post meta
    $all_meta = get_post_meta_by_ids($post_ids);

    // Pre-fetch all terms
    $taxonomies = [
        'types-of-transport',
        'marques-coches',
        'estat-vehicle',
        'tipus-de-propulsor',
        'tipus-combustible',
        'marques-de-moto',
        'tipus-de-canvi'
    ];
    $all_terms = wp_get_object_terms($post_ids, $taxonomies, ['fields' => 'all_with_object_id']);

    foreach ($posts as $post) {
        $vehicle_id = $post->ID;
        $vehicle_details = get_vehicle_details_common($vehicle_id, $post, $all_meta[$vehicle_id], $all_terms, true);
        if (!is_wp_error($vehicle_details)) {
            $vehicles[] = $vehicle_details->get_data();
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
    return get_vehicle_details_common($post->ID, $post);
}

function get_vehicle_details_common($vehicle_id, $post = null, $meta = null, $terms = null, $skip_permission_check = false) {
    // Cache condicional basado en configuración
    $cache_enabled = get_option('vehicles_api_cache_enabled', '0');
    if ($cache_enabled === '1') {
        $cache_key = 'vehicle_details_' . $vehicle_id;
        $cached_vehicle = get_transient($cache_key);

        if (false !== $cached_vehicle) {
            return new WP_REST_Response($cached_vehicle, 200);
        }
    }

    if (!function_exists('jet_engine')) {
        return new WP_Error('jet_engine_missing', 'JetEngine no está activo');
    }

    if (null === $post) {
        $post = get_post($vehicle_id);
    }

    if (!$post || $post->post_type !== 'singlecar') {
        return new WP_Error('no_vehicle', 'Vehicle not found', ['status' => 404]);
    }

    if (null === $meta) {
        $meta = get_post_meta($vehicle_id);
    }

    $taxonomies = [
        'types-of-transport' => 'tipus-vehicle',
        'marques-coches' => 'marques-cotxe',
        'estat-vehicle' => 'estat-vehicle',
        'tipus-de-propulsor' => 'tipus-propulsor',
        'tipus-combustible' => 'tipus-combustible',
        'marques-de-moto' => 'tipus-de-moto',
        'tipus-de-canvi' => 'tipus-canvi'
    ];

    $terms_data = [];
    if (null === $terms) {
        foreach ($taxonomies as $taxonomy => $field_name) {
            $post_terms = wp_get_post_terms($vehicle_id, $taxonomy, ['fields' => 'all']);
            if (!is_wp_error($post_terms) && !empty($post_terms)) {
                $terms_data[$field_name] = $post_terms[0];
            }
        }
    } else {
        foreach ($terms as $term) {
            if ($term->object_id == $vehicle_id && isset($taxonomies[$term->taxonomy])) {
                $field_name = $taxonomies[$term->taxonomy];
                $terms_data[$field_name] = $term;
            }
        }
    }
    

    if (!$skip_permission_check && !verify_post_ownership($vehicle_id)) {
        return new WP_Error('forbidden_access', 'No tienes permiso para ver este vehículo', ['status' => 403]);
    }

    Vehicle_Debug_Handler::log('About to create response for vehicle ' . $vehicle_id);
    
    $response = [
        'id' => $vehicle_id,
        'author_id' => $post->post_author,
        'data-creacio' => $post->post_date,
        'status' => $post->post_status,
        'slug' => $post->post_name,
        'titol-anunci' => get_the_title($vehicle_id),
        'descripcio-anunci' => $post->post_content,
        'anunci-actiu' => 'false', // Valor temporal, se sobrescribirá en process_expiry()
        'anunci-destacat' => (function() use ($meta, $vehicle_id) {
            Vehicle_Debug_Handler::log('Processing anunci-destacat for vehicle ' . $vehicle_id);
            return get_anunci_destacat_value($meta, $vehicle_id);
        })()
    ];

    if (isset($terms_data['tipus-vehicle'])) {
        $response['tipus-vehicle'] = $terms_data['tipus-vehicle']->name;
    }

    // Process car/caravan/commercial vehicle brands and models (all use marques-coches taxonomy)
    $marques_terms = wp_get_post_terms($vehicle_id, 'marques-coches', ['fields' => 'all']);
    if (!is_wp_error($marques_terms) && !empty($marques_terms)) {
        foreach ($marques_terms as $term) {
            if ($term->parent === 0) {
                // Determine vehicle type to assign correct field names
                $vehicle_type = isset($response['tipus-vehicle']) ? $response['tipus-vehicle'] : '';
                
                if (strpos(strtolower($vehicle_type), 'autocaravana') !== false || strpos(strtolower($vehicle_type), 'camper') !== false) {
                    $response['marques-autocaravana'] = $term->name;
                    $marca_field = 'models-autocaravana';
                } elseif (strpos(strtolower($vehicle_type), 'comercial') !== false) {
                    $response['marques-comercial'] = $term->name;
                    $marca_field = 'models-comercial';
                } else {
                    // Default to car
                    $response['marques-cotxe'] = $term->name;
                    $marca_field = 'models-cotxe';
                }
                
                // Find child model terms
                foreach ($marques_terms as $model_term) {
                    if ($model_term->parent === $term->term_id) {
                        $response[$marca_field] = $model_term->name;
                        break;
                    }
                }
                break;
            }
        }
    }

    // Process motorcycle brands and models
    $moto_terms = wp_get_post_terms($vehicle_id, 'marques-de-moto', ['fields' => 'all']);
    if (!is_wp_error($moto_terms) && !empty($moto_terms)) {
        foreach ($moto_terms as $term) {
            if ($term->parent === 0) {
                $response['marques-moto'] = $term->name;
                // Find child model terms
                foreach ($moto_terms as $model_term) {
                    if ($model_term->parent === $term->term_id) {
                        $response['models-moto'] = $model_term->name;
                        break;
                    }
                }
                break;
            }
        }
    }

    foreach ($terms_data as $field => $term) {
        if (!in_array($field, ['tipus-vehicle', 'marques-cotxe', 'models-cotxe', 'marques-moto', 'models-moto'])) {
            $response[$field] = $term->name;
        }
    }

    // Process segment field based on vehicle type
    if (isset($meta['segment']) && !empty($meta['segment'][0])) {
        $segment_value = $meta['segment'][0];
        $vehicle_type = isset($response['tipus-vehicle']) ? $response['tipus-vehicle'] : '';
        
        if (strpos(strtolower($vehicle_type), 'comercial') !== false) {
            $segment_field = 'carroseria-comercial';
        } else {
            // Default to car
            $segment_field = 'carroseria-cotxe';
        }
        
        if (function_exists('should_get_field_label') && should_get_field_label('segment')) {
            $response[$segment_field] = get_field_label('segment', $segment_value);
        } else {
            $response[$segment_field] = $segment_value;
        }
    }
    
    // Process other carroceria fields
    $carroceria_fields = [
        'carroseria-cotxe' => 'carroseria-cotxe',
        'carrosseria-cotxe' => 'carrosseria-cotxe',
        'carroseria-vehicle-comercial' => 'carroseria-comercial',
        'carrosseria-caravana' => 'carrosseria-caravana',
        'tipus-carroseria-caravana' => 'carroseria-caravana'
    ];
    foreach ($carroceria_fields as $field => $output_field) {
        if (isset($meta[$field]) && !empty($meta[$field][0])) {
            $value = $meta[$field][0];
            
            if (function_exists('should_get_field_label') && should_get_field_label($field)) {
                $response[$output_field] = get_field_label($field, $value);
            } else {
                $response[$output_field] = $value;
            }
        }
    }
    
    // Process tipus-de-moto separately
    if (isset($meta['tipus-de-moto']) && !empty($meta['tipus-de-moto'][0])) {
        $value = $meta['tipus-de-moto'][0];
        if (function_exists('should_get_field_label') && should_get_field_label('tipus-de-moto')) {
            $response['tipus-de-moto'] = get_field_label('tipus-de-moto', $value);
        } else {
            $response['tipus-de-moto'] = $value;
        }
    }

    process_meta_fields($meta, $response);
    add_image_data($vehicle_id, $response, $meta);
    process_expiry($vehicle_id, $post, $response);

    if (!current_user_can('administrator')) {
        unset($response['dies-caducitat']);
    }

    $response['anunci-destacat'] = ($response['anunci-destacat'] === 1) ? 1 : 0;

    // Cache condicional basado en configuración
    $cache_enabled = get_option('vehicles_api_cache_enabled', '0');
    if ($cache_enabled === '1') {
        $cache_duration = intval(get_option('vehicles_api_cache_duration', 3600));
        set_transient($cache_key, $response, $cache_duration);
    }

    return new WP_REST_Response($response, 200);
}

function get_post_meta_by_ids($post_ids) {
    global $wpdb;
    $meta_cache = [];

    if (empty($post_ids)) {
        return $meta_cache;
    }

    $post_ids_placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($post_ids_placeholders)",
        $post_ids
    ), ARRAY_A);

    foreach ($results as $row) {
        $meta_cache[$row['post_id']][$row['meta_key']][] = $row['meta_value'];
    }

    return $meta_cache;
}


// Nueva función para manejar la caducidad
function process_expiry($vehicle_id, $post, &$response) {
    // Obtener el valor original del campo anunci-actiu
    $anunci_actiu_original = get_post_meta($vehicle_id, 'anunci-actiu', true);
    
    Vehicle_Debug_Handler::log("Vehicle $vehicle_id: anunci-actiu original = '$anunci_actiu_original'");
    
    // Si el anuncio ya está marcado como inactivo, mantenerlo así
    if ($anunci_actiu_original !== 'true') {
        $response['anunci-actiu'] = 'false';
        Vehicle_Debug_Handler::log("Vehicle $vehicle_id: marcado como inactivo (original no era 'true')");
        return;
    }
    
    // Verificar si la caducidad está habilitada
    $expiry_enabled = get_option('vehicles_api_expiry_enabled', '1');
    if ($expiry_enabled !== '1') {
        // Si la caducidad está deshabilitada, mantener el valor original
        $response['anunci-actiu'] = 'true';
        Vehicle_Debug_Handler::log("Vehicle $vehicle_id: manteniéndolo activo (caducidad deshabilitada)");
        return;
    }
    
    // Si está marcado como activo, verificar si ha expirado
    $dies_caducitat = intval(get_post_meta($vehicle_id, 'dies-caducitat', true));
    
    // Si no hay dies-caducitat configurado o es 0, usar valor por defecto
    if ($dies_caducitat <= 0) {
        $dies_caducitat = intval(get_option('vehicles_api_default_expiry_days', 365));
    }
    
    $data_creacio = strtotime($post->post_date);
    $data_actual = current_time('timestamp');
    $dies_transcorreguts = floor(($data_actual - $data_creacio) / (60 * 60 * 24));
    
    Vehicle_Debug_Handler::log("Vehicle $vehicle_id: dies_caducitat=$dies_caducitat, dies_transcorreguts=$dies_transcorreguts");
    
    // Solo marcar como inactivo si estaba activo pero ha expirado
    if ($dies_transcorreguts > $dies_caducitat) {
        $response['anunci-actiu'] = 'false';
        Vehicle_Debug_Handler::log("Vehicle $vehicle_id: marcado como inactivo (expirado)");
    } else {
        // Si está activo y no ha expirado, mantenerlo activo
        $response['anunci-actiu'] = 'true';
        Vehicle_Debug_Handler::log("Vehicle $vehicle_id: manteniéndolo activo");
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
        'models-cotxe',
        'segment',
        'carroseria-vehicle-comercial',
        'tipus-carroseria-caravana',
        'carroseria-camions'
    ];

    foreach ($meta as $key => $value) {
        if (in_array($key, $skip_fields) || isset($response[$key]) || Vehicle_Fields::should_exclude_field($key)) {
            continue;
        }

        $meta_value = is_array($value) ? $value[0] : $value;
        $mapped_key = map_field_key($key);
        
        // Apply field-specific value processing
        $processed_value = function_exists('map_field_value') ? 
            map_field_value($key, $meta_value) : 
            $meta_value;
        
        $response[$mapped_key] = should_get_field_label($mapped_key) ? 
            get_field_label($key, $processed_value) : 
            $processed_value;
    }
}

function add_image_data($vehicle_id, &$response, $meta = null) {
    if (null === $meta) {
        $meta = get_post_meta($vehicle_id);
    }

    $response['imatge-destacada-url'] = get_the_post_thumbnail_url($vehicle_id, 'full');

    $gallery_ids = isset($meta['ad_gallery'][0]) ? $meta['ad_gallery'][0] : null;
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

function calculate_facets($vehicles, $params = []) {
    $facets = [
        'tipus-vehicle' => [],
        'estat-vehicle' => [],
        'marques-cotxe' => [],
        'models-cotxe' => [],
        'marques-autocaravana' => [],
        'models-autocaravana' => [],
        'marques-comercial' => [],
        'models-comercial' => [],
        'marques-moto' => [],
        'models-moto' => [],
        'tipus-combustible' => [],
        'tipus-canvi' => [],
        'tipus-de-canvi-moto' => [],
        'tipus-propulsor' => [],
        'anunci-destacat' => [],
        'revisions-oficials' => [],
        'impostos-deduibles' => [],
        'vehicle-a-canvi' => [],
        'garantia' => [],
        'traccio' => [],
        'roda-recanvi' => [],
        'carroseria-cotxe' => [],
        'carroseria-comercial' => [],
        'carroseria-caravana' => [],
        'tipus-de-moto' => [],
        'color-vehicle' => [],
        'tipus-tapisseria' => [],
        'color-tapisseria' => [],
        'emissions-vehicle' => [],
        'cables-recarrega' => [],
        'connectors' => []
    ];

    // Recorremos los vehículos para calcular los conteos de cada faceta
    foreach ($vehicles as $v) {
        foreach ($facets as $key => &$facet) {
            // Modelos de coche: solo si hay marca seleccionada
            if ($key === 'models-cotxe') {
                if (empty($params['marques-cotxe'])) {
                    $facet = [];
                    continue;
                }
                if (!empty($v['models-cotxe'])) {
                    $model = is_array($v['models-cotxe']) ? $v['models-cotxe'] : [$v['models-cotxe']];
                    foreach ($model as $m) {
                        if (!empty($m)) $facet[$m] = isset($facet[$m]) ? $facet[$m] + 1 : 1;
                    }
                }
                continue;
            }
            // Modelos de autocaravana: solo si hay marca seleccionada
            if ($key === 'models-autocaravana') {
                if (empty($params['marques-autocaravana'])) {
                    $facet = [];
                    continue;
                }
                if (!empty($v['models-autocaravana'])) {
                    $model = is_array($v['models-autocaravana']) ? $v['models-autocaravana'] : [$v['models-autocaravana']];
                    foreach ($model as $m) {
                        if (!empty($m)) $facet[$m] = isset($facet[$m]) ? $facet[$m] + 1 : 1;
                    }
                }
                continue;
            }
            // Modelos de vehículo comercial: solo si hay marca seleccionada
            if ($key === 'models-comercial') {
                if (empty($params['marques-comercial'])) {
                    $facet = [];
                    continue;
                }
                if (!empty($v['models-comercial'])) {
                    $model = is_array($v['models-comercial']) ? $v['models-comercial'] : [$v['models-comercial']];
                    foreach ($model as $m) {
                        if (!empty($m)) $facet[$m] = isset($facet[$m]) ? $facet[$m] + 1 : 1;
                    }
                }
                continue;
            }
            // Modelos de moto: solo si hay marca seleccionada
            if ($key === 'models-moto') {
                if (empty($params['marques-moto'])) {
                    $facet = [];
                    continue;
                }
                if (!empty($v['models-moto'])) {
                    $model = is_array($v['models-moto']) ? $v['models-moto'] : [$v['models-moto']];
                    foreach ($model as $m) {
                        if (!empty($m)) $facet[$m] = isset($facet[$m]) ? $facet[$m] + 1 : 1;
                    }
                }
                continue;
            }
            // Facetas normales
            if (!empty($v[$key])) {
                $values = is_array($v[$key]) ? $v[$key] : [$v[$key]];
                foreach ($values as $val) {
                    if (!empty($val)) $facet[$val] = isset($facet[$val]) ? $facet[$val] + 1 : 1;
                }
            }
        }
    }
    unset($facet);
    return $facets;
}

function get_anunci_destacat_value($meta, $vehicle_id = null) {
    
    if ($vehicle_id) {
        Vehicle_Debug_Handler::log('get_anunci_destacat_value called for vehicle ' . $vehicle_id);
    }
    
    // Verificar si existe el campo is-vip
    if (isset($meta['is-vip'][0])) {
        $is_vip_value = $meta['is-vip'][0];
        $is_vip_value_str = (string)$is_vip_value;
        $is_vip_value_lower = strtolower(trim($is_vip_value_str));
        
        if ($vehicle_id) {
            Vehicle_Debug_Handler::log('Vehicle ' . $vehicle_id . ' - is-vip raw: ' . var_export($is_vip_value, true) . ', processed: ' . $is_vip_value_lower);
        }
        
        
        // Considerar varios valores como "destacado"
        $is_destacado = false;
        
        // Verificar strings comunes
        if ($is_vip_value_lower === 'true' || $is_vip_value_lower === 'yes' || 
            $is_vip_value_lower === 'si' || $is_vip_value_lower === 'on') {
            $is_destacado = true;
        }
        
        // Verificar valores numéricos
        if (!$is_destacado && is_numeric($is_vip_value_str) && intval($is_vip_value_str) > 0) {
            $is_destacado = true;
        }
        
        // Verificar valores booleanos
        if (!$is_destacado && is_bool($is_vip_value) && $is_vip_value === true) {
            $is_destacado = true;
        }
        
        if ($is_destacado) {
            return 1;
        }
    }
    
    // Verificar si existe data-vip (campo de fecha de VIP)
    if (isset($meta['data-vip'][0]) && !empty($meta['data-vip'][0])) {
        $data_vip = $meta['data-vip'][0];
        // Si tiene fecha VIP y no está vacía, considerar como destacado
        if ($data_vip !== '0000-00-00' && $data_vip !== '') {
            // Verificar si la fecha VIP no ha expirado
            $data_vip_timestamp = strtotime($data_vip);
            $current_timestamp = time();
            if ($data_vip_timestamp > $current_timestamp) {
                return 1;
            }
        }
    }
    
    // Verificar también otros campos que podrían indicar destacado
    $destacado_fields = ['destacado', 'featured', 'vip', 'premium'];
    foreach ($destacado_fields as $field) {
        if (isset($meta[$field][0])) {
            $value = trim(strtolower($meta[$field][0]));
            if ($value === 'true' || $value === '1' || $value === 'yes' || $value === 'si') {
                return 1;
            }
        }
    }
    
    if ($vehicle_id) {
        Vehicle_Debug_Handler::log('Vehicle ' . $vehicle_id . ' - Final anunci-destacat result: 0');
    }
    
    return 0;
}