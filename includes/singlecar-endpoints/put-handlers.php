<?php

function update_singlecar($request) {
    try {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $params = $request->get_params();
        $post_id = isset($params['id']) ? $params['id'] : 0;

        // Verificar existencia y propiedad del vehículo
        validate_vehicle_ownership($post_id);

        // Actualizar el post
        update_vehicle_base_data($post_id, $params);

        // Validar y actualizar taxonomías
        if (has_taxonomy_updates($params)) {
            validate_taxonomies($params);
            update_vehicle_taxonomies($post_id, $params);
        }

        // Validar y actualizar campos meta
        validate_all_fields($params);
        process_and_save_meta_fields($post_id, $params);

        // Procesar imágenes si se proporcionan
        if (has_image_updates($params)) {
            process_vehicle_images($post_id, $params);
        }

        // Actualizar estado activo si se proporciona
        update_vehicle_status($post_id, $params);

        $wpdb->query('COMMIT');

        // Preparar y enviar respuesta
        return prepare_update_response($post_id);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return new WP_REST_Response([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 400);
    }
}

function validate_vehicle_ownership($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'singlecar') {
        throw new Exception('Vehículo no encontrado');
    }

    if (!verify_post_ownership($post_id)) {
        throw new Exception('No tienes permiso para editar este vehículo');
    }
}

function update_vehicle_base_data($post_id, $params) {
    $post_data = ['ID' => $post_id];
    
    if (isset($params['titol-anunci'])) {
        $post_data['post_title'] = wp_strip_all_tags($params['titol-anunci']);
    }
    
    if (isset($params['descripcio-anunci'])) {
        $post_data['post_content'] = $params['descripcio-anunci'];
    }

    if (count($post_data) > 1) {
        $result = wp_update_post($post_data);
        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }
    }
}

function has_taxonomy_updates($params) {
    $taxonomy_fields = array_merge(
        array_keys(Vehicle_Fields::get_taxonomy_fields()),
        ['marques-cotxe', 'models-cotxe']
    );

    foreach ($taxonomy_fields as $field) {
        if (isset($params[$field])) {
            return true;
        }
    }
    return false;
}

function update_vehicle_taxonomies($post_id, $params) {
    $taxonomy_fields = Vehicle_Fields::get_taxonomy_fields();
    
    // Actualizar taxonomías estándar
    foreach ($taxonomy_fields as $field => $taxonomy) {
        if (isset($params[$field])) {
            $term = get_term_by('slug', $params[$field], $taxonomy);
            if ($term) {
                $result = wp_set_object_terms($post_id, [$term->term_id], $taxonomy);
                if (is_wp_error($result)) {
                    throw new Exception(sprintf('Error al actualizar el término %s: %s', 
                        $params[$field], 
                        $result->get_error_message()
                    ));
                }
            }
        }
    }

    // Actualizar marca y modelo si se proporcionan
    if (isset($params['marques-cotxe'])) {
        update_brand_and_model($post_id, $params);
    }
}

function update_brand_and_model($post_id, $params) {
    $marca = get_term_by('slug', $params['marques-cotxe'], 'marques-coches');
    if (!$marca) {
        throw new Exception("La marca especificada no existe");
    }

    $terms = [$marca->term_id];

    if (isset($params['models-cotxe'])) {
        $modelo = get_term_by('slug', $params['models-cotxe'], 'marques-coches');
        if (!$modelo || $modelo->parent != $marca->term_id) {
            throw new Exception("El modelo especificado no existe o no pertenece a la marca indicada");
        }
        $terms[] = $modelo->term_id;
    }

    $result = wp_set_object_terms($post_id, $terms, 'marques-coches');
    if (is_wp_error($result)) {
        throw new Exception('Error al actualizar marca y modelo: ' . $result->get_error_message());
    }
}

function has_image_updates($params) {
    return isset($params['imatge-destacada-id']) || isset($params['galeria-vehicle']);
}

function update_vehicle_status($post_id, $params) {
    if (isset($params['anunci-actiu'])) {
        $anunci_actiu = strtolower(trim($params['anunci-actiu']));
        $true_values = ['true', 'si', '1', 'yes', 'on'];
        $false_values = ['false', 'no', '0', 'off'];

        if (in_array($anunci_actiu, $true_values, true)) {
            $anunci_actiu = 'true';
        } elseif (in_array($anunci_actiu, $false_values, true)) {
            $anunci_actiu = 'false';
        } else {
            $anunci_actiu = 'false';
        }

        update_post_meta($post_id, 'anunci-actiu', $anunci_actiu);
    }
}

function prepare_update_response($post_id) {
    $post = get_post($post_id);
    $response = [
        'status' => 'success',
        'message' => 'Vehículo actualizado exitosamente',
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

    return new WP_REST_Response($response, 200);
}
