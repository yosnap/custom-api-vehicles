<?php

function update_singlecar($request) {
    try {
        Vehicle_Debug_Handler::log('PUT - Iniciando update_singlecar');
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $params = $request->get_params();
        Vehicle_Debug_Handler::log('PUT - Parámetros recibidos: ' . print_r($params, true));
        $post_id = isset($params['id']) ? $params['id'] : 0;

        // Verificar existencia y propiedad del vehículo
        validate_vehicle_ownership($post_id);

        Vehicle_Debug_Handler::log('PUT - Valor inicial de anunci-actiu: ' . get_post_meta($post_id, 'anunci-actiu', true));

        // Verificar si hay campos obligatorios vacíos en el vehículo existente
        $empty_required_fields = check_empty_required_fields($post_id);
        
        if (!empty($empty_required_fields)) {
            // Solo validar los campos obligatorios que estén vacíos usando la función del archivo validation.php
            $validation_result = validate_required_fields($params, $empty_required_fields, true);
            if (is_wp_error($validation_result)) {
                throw new Exception($validation_result->get_error_message());
            }
        }

        // Actualizar el post
        update_vehicle_base_data($post_id, $params);

        Vehicle_Debug_Handler::log('PUT - Valor de anunci-actiu después de update_vehicle_base_data: ' . get_post_meta($post_id, 'anunci-actiu', true));

        // Procesar actualizaciones solo para los campos proporcionados
        if (!empty($params)) {
            if (has_taxonomy_updates($params)) {
                validate_taxonomies($params);
                update_vehicle_taxonomies($post_id, $params);
                Vehicle_Debug_Handler::log('PUT - Valor de anunci-actiu después de update_vehicle_taxonomies: ' . get_post_meta($post_id, 'anunci-actiu', true));
            }

            if (has_image_updates($params)) {
                process_vehicle_images($post_id, $params);
                Vehicle_Debug_Handler::log('PUT - Valor de anunci-actiu después de process_vehicle_images: ' . get_post_meta($post_id, 'anunci-actiu', true));
            }

            // Procesar anunci-actiu y anunci-destacat si están presentes
            if (isset($params['anunci-actiu']) || isset($params['anunci-destacat'])) {
                update_vehicle_status($post_id, $params);
                Vehicle_Debug_Handler::log('PUT - Valor de anunci-actiu después de update_vehicle_status: ' . get_post_meta($post_id, 'anunci-actiu', true));
            }

            // Crear una copia de los parámetros sin anunci-actiu y anunci-destacat
            $meta_params = array_diff_key($params, array_flip(['anunci-actiu', 'anunci-destacat', 'id']));
            Vehicle_Debug_Handler::log('PUT - Parámetros para meta fields: ' . print_r($meta_params, true));
            
            // Procesar campos meta solo si hay campos para actualizar
            if (!empty($meta_params)) {
                process_and_save_meta_fields($post_id, $meta_params, true);
                Vehicle_Debug_Handler::log('PUT - Valor de anunci-actiu después de process_and_save_meta_fields: ' . get_post_meta($post_id, 'anunci-actiu', true));
            }
        }

        $wpdb->query('COMMIT');

        clear_vehicle_cache($post_id);

        Vehicle_Debug_Handler::log('PUT - Valor final de anunci-actiu antes de prepare_update_response: ' . get_post_meta($post_id, 'anunci-actiu', true));

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
    $image_fields = [
        'imatge-destacada-id',
        'imatge-destacada-url',
        'imatge-destacada',
        'galeria-vehicle',
        'galeria-vehicle-urls'
    ];

    foreach ($image_fields as $field) {
        if (isset($params[$field]) && !empty($params[$field])) {
            return true;
        }
    }

    // Verificar si hay archivos subidos
    if (isset($_FILES['imatge-destacada']) && !empty($_FILES['imatge-destacada']['tmp_name'])) {
        return true;
    }
    if (isset($_FILES['galeria-vehicle']) && !empty($_FILES['galeria-vehicle']['tmp_name'])) {
        return true;
    }

    return false;
}

function update_vehicle_status($post_id, $params) {
    Vehicle_Debug_Handler::log('PUT - Iniciando update_vehicle_status');
    Vehicle_Debug_Handler::log('PUT - Parámetros en update_vehicle_status: ' . print_r($params, true));
    
    // Manejar el campo anunci-actiu
    if (isset($params['anunci-actiu'])) {
        Vehicle_Debug_Handler::log('PUT - Actualizando anunci-actiu');
        $anunci_actiu = $params['anunci-actiu'];
        Vehicle_Debug_Handler::log('PUT - Valor recibido de anunci-actiu: ' . $anunci_actiu);
        Vehicle_Debug_Handler::log('PUT - Tipo de dato de anunci-actiu: ' . gettype($anunci_actiu));
        
        // Asegurar que el valor sea exactamente "true" o "false" (sin normalizar)
        Vehicle_Debug_Handler::log('PUT - Intentando guardar anunci-actiu con valor exacto: ' . $anunci_actiu);
        delete_post_meta($post_id, 'anunci-actiu');
        $result = add_post_meta($post_id, 'anunci-actiu', $anunci_actiu, true);
        Vehicle_Debug_Handler::log('PUT - Resultado de guardar anunci-actiu: ' . ($result ? 'true' : 'false'));
        Vehicle_Debug_Handler::log('PUT - Valor guardado en anunci-actiu: ' . get_post_meta($post_id, 'anunci-actiu', true));
    }

    // Manejar el campo anunci-destacat
    if (isset($params['anunci-destacat'])) {
        Vehicle_Debug_Handler::log('PUT - Actualizando anunci-destacat');
        $anunci_destacat = $params['anunci-destacat'];
        Vehicle_Debug_Handler::log('PUT - Valor recibido de anunci-destacat: ' . $anunci_destacat);
        Vehicle_Debug_Handler::log('PUT - Tipo de dato de anunci-destacat: ' . gettype($anunci_destacat));
        
        // Asegurar que el valor sea exactamente "true" o "false" (sin normalizar)
        Vehicle_Debug_Handler::log('PUT - Intentando guardar is-vip con valor exacto: ' . $anunci_destacat);
        delete_post_meta($post_id, 'is-vip');
        $result = add_post_meta($post_id, 'is-vip', $anunci_destacat, true);
        Vehicle_Debug_Handler::log('PUT - Resultado de guardar is-vip: ' . ($result ? 'true' : 'false'));
        Vehicle_Debug_Handler::log('PUT - Valor guardado en is-vip: ' . get_post_meta($post_id, 'is-vip', true));
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
            $response[$field] = strtolower($terms[0]->slug);
        }
    }

    // Agregar marca y modelo
    $terms = wp_get_object_terms($post_id, 'marques-coches');
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            if ($term->parent === 0) {
                $response['marca'] = strtolower($term->slug);
            } else {
                $response['modelo'] = strtolower($term->slug);
            }
        }
    }

    // Obtener los valores exactos de los campos booleanos
    $response['anunci-actiu'] = get_post_meta($post_id, 'anunci-actiu', true) === 'true' ? 'true' : 'false';
    $response['anunci-destacat'] = (get_post_meta($post_id, 'is-vip', true) === 'true') ? 1 : 0;

    // Agregar IDs de imágenes de WordPress para tracking en cliente
    $images_data = get_vehicle_images($post_id);
    if (!empty($images_data)) {
        if (isset($images_data['imatge-destacada-wp-id'])) {
            $response['imatge-destacada-wp-id'] = $images_data['imatge-destacada-wp-id'];
        }
        if (isset($images_data['galeria-vehicle-wp-ids'])) {
            $response['galeria-vehicle-wp-ids'] = $images_data['galeria-vehicle-wp-ids'];
        }
    }

    return new WP_REST_Response($response, 200);
}

/**
 * Verifica si hay campos obligatorios vacíos en el vehículo existente
 */
function check_empty_required_fields($post_id) {
    $empty_fields = [];
    $post = get_post($post_id);
    
    // Obtener el tipo de vehículo actual
    $tipus_vehicle = '';
    $terms = wp_get_object_terms($post_id, 'tipus-vehicle');
    if (!is_wp_error($terms) && !empty($terms)) {
        $tipus_vehicle = $terms[0]->slug;
    }

    // Verificar campos básicos obligatorios
    if (empty($tipus_vehicle)) {
        $empty_fields[] = 'tipus-vehicle';
    }

    // Verificar estado del vehículo
    $estat_terms = wp_get_object_terms($post_id, 'estat-vehicle');
    if (is_wp_error($estat_terms) || empty($estat_terms)) {
        $empty_fields[] = 'estat-vehicle';
    }

    // Verificar marca y modelo según el tipo de vehículo
    if ($tipus_vehicle && $tipus_vehicle !== 'moto-quad-atv') {
        $marques_terms = wp_get_object_terms($post_id, 'marques-cotxe');
        if (is_wp_error($marques_terms) || empty($marques_terms)) {
            $empty_fields[] = 'marques-cotxe';
        }
        
        $models_terms = wp_get_object_terms($post_id, 'models-cotxe');
        if (is_wp_error($models_terms) || empty($models_terms)) {
            $empty_fields[] = 'models-cotxe';
        }
    } elseif ($tipus_vehicle === 'moto-quad-atv') {
        $marques_moto_terms = wp_get_object_terms($post_id, 'marques-de-moto');
        if (is_wp_error($marques_moto_terms) || empty($marques_moto_terms)) {
            $empty_fields[] = 'marques-de-moto';
        }
    }

    return $empty_fields;
}
