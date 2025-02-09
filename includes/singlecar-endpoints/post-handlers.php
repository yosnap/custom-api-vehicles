<?php

function create_singlecar($request) {
    global $wpdb;
    $wpdb->query('START TRANSACTION');

    try {
        $params = $request->get_params();

        // Validar campos requeridos
        $required_fields = [
            'marques-cotxe',
            'models-cotxe',
            'versio',
            'tipus-vehicle',
            'tipus-combustible',
            'tipus-canvi-cotxe',
            'tipus-propulsor',
            'estat-vehicle',
            'preu'
        ];

        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                throw new Exception('El campo ' . $field . ' es obligatorio');
            }
        }

        // Crear título automático
        $marca_term = get_term_by('slug', $params['marques-cotxe'], 'marques-coches');
        $model_term = get_term_by('slug', $params['models-cotxe'], 'marques-coches');
        
        $marca_name = $marca_term ? $marca_term->name : $params['marques-cotxe'];
        $model_name = $model_term ? $model_term->name : $params['models-cotxe'];
        $params['titol-anunci'] = ucfirst($marca_name) . ' ' . strtoupper($model_name) . ' ' . $params['versio'];

        // Validar marca y modelo
        validate_brand_and_model($params['marques-cotxe'], $params['models-cotxe']);

        // Validar taxonomías
        validate_taxonomies($params);

        // Validar todos los campos
        validate_all_fields($params);

        // Crear el post
        $post_id = create_vehicle_post($params);

        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

        // Procesar imágenes
        process_vehicle_images($post_id, $params);

        // Procesar campos meta
        process_and_save_meta_fields($post_id, $params);

        // Procesar taxonomías
        assign_vehicle_taxonomies($post_id, $params);

        // Establecer valores por defecto
        set_default_values($post_id, $params);

        $wpdb->query('COMMIT');

        // Preparar respuesta
        return prepare_vehicle_response($post_id, $params);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');

        if (isset($post_id) && $post_id) {
            wp_delete_post($post_id, true);
        }

        return new WP_REST_Response([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 400);
    }
}

function validate_brand_and_model($marca_slug, $model_slug) {
    $marca = get_term_by('slug', $marca_slug, 'marques-coches');
    if (!$marca) {
        throw new Exception("La marca especificada no existe");
    }

    $modelo = get_term_by('slug', $model_slug, 'marques-coches');
    if (!$modelo || $modelo->parent != $marca->term_id) {
        throw new Exception("El modelo especificado no existe o no pertenece a la marca indicada");
    }
}

function validate_taxonomies($params) {
    $taxonomy_fields = Vehicle_Fields::get_taxonomy_fields();
    $allowed_values = Vehicle_Fields::get_allowed_taxonomy_values();

    foreach ($taxonomy_fields as $field => $taxonomy) {
        if (isset($params[$field])) {
            if (isset($allowed_values[$field]) && !in_array($params[$field], $allowed_values[$field])) {
                throw new Exception(sprintf(
                    'El valor "%s" no es válido para %s. Valores válidos: %s',
                    $params[$field],
                    $field,
                    implode(', ', $allowed_values[$field])
                ));
            }

            $term = get_term_by('slug', $params[$field], $taxonomy);
            if (!$term) {
                throw new Exception(sprintf(
                    'El término "%s" no existe en la taxonomía %s',
                    $params[$field],
                    $taxonomy
                ));
            }
        }
    }
}

function create_vehicle_post($params) {
    $post_data = [
        'post_title' => wp_strip_all_tags($params['titol-anunci']),
        'post_content' => $params['descripcio-anunci'] ?? '',
        'post_status' => 'publish',
        'post_type' => 'singlecar'
    ];

    return wp_insert_post($post_data);
}

function assign_vehicle_taxonomies($post_id, $params) {
    $taxonomy_fields = Vehicle_Fields::get_taxonomy_fields();
    
    foreach ($taxonomy_fields as $field => $taxonomy) {
        if (isset($params[$field])) {
            $term = get_term_by('slug', $params[$field], $taxonomy);
            if ($term) {
                $result = wp_set_object_terms($post_id, [$term->term_id], $taxonomy);
                if (is_wp_error($result)) {
                    throw new Exception(sprintf('Error al asignar el término %s', $params[$field]));
                }
            }
        }
    }

    // Asignar marca y modelo
    $marca = get_term_by('slug', $params['marques-cotxe'], 'marques-coches');
    $modelo = get_term_by('slug', $params['models-cotxe'], 'marques-coches');
    
    if ($marca && $modelo) {
        $result = wp_set_object_terms($post_id, [$marca->term_id, $modelo->term_id], 'marques-coches');
        if (is_wp_error($result)) {
            throw new Exception('Error al asignar marca y modelo');
        }
    }
}

function set_default_values($post_id, $params) {
    // Establecer anunci-actiu y dies-caducitat
    $anunci_actiu = isset($params['anunci-actiu']) ? 
        filter_var($params['anunci-actiu'], FILTER_VALIDATE_BOOLEAN) : 
        true;

    $dies_caducitat = $anunci_actiu ? 
        (current_user_can('administrator') && isset($params['dies-caducitat']) ? 
            intval($params['dies-caducitat']) : 365) : 
        0;

    update_post_meta($post_id, 'dies-caducitat', $dies_caducitat);
    update_post_meta($post_id, 'anunci-actiu', $anunci_actiu ? 'true' : 'false');
    update_post_meta($post_id, 'venedor', 'professional');
}

function prepare_vehicle_response($post_id, $params) {
    $post = get_post($post_id);
    $response = [
        'status' => 'success',
        'message' => 'Vehículo creado exitosamente',
        'post_id' => $post_id,
        'titol-anunci' => $post->post_title,
        'descripcio-anunci' => $post->post_content,
        'status' => $post->post_status
    ];

    // Agregar campos de taxonomía
    $taxonomy_fields = Vehicle_Fields::get_taxonomy_fields();
    foreach ($taxonomy_fields as $field => $taxonomy) {
        $terms = wp_get_object_terms($post_id, $taxonomy);
        if (!is_wp_error($terms) && !empty($terms)) {
            $response[$field] = $terms[0]->slug;
        }
    }

    // Agregar marca y modelo
    $terms = wp_get_object_terms($post_id, 'marques-coches');
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            if ($term->parent === 0) {
                $response['marca'] = $term->slug;
            } else {
                $response['modelo'] = $term->slug;
            }
        }
    }

    $response['anunci-actiu'] = get_post_meta($post_id, 'anunci-actiu', true);

    return new WP_REST_Response($response, 201);
}
