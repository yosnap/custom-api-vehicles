<?php

function create_singlecar($request) {
    global $wpdb;
    $wpdb->query('START TRANSACTION');

    try {
        Vehicle_Debug_Handler::log('POST - Iniciando creación de vehículo');
        // Obtener parámetros del objeto Request de forma segura
        if (is_object($request) && method_exists($request, 'get_params')) {
            $params = $request->get_params();
        } elseif (is_array($request)) {
            $params = $request;
        } else {
            throw new Exception('Formato de solicitud no válido');
        }

        Vehicle_Debug_Handler::log('POST - Parámetros recibidos: ' . print_r($params, true));

        // Verificar que se ha proporcionado una imagen destacada
        $has_image = false;
        
        // Registrar para depuración
        Vehicle_Debug_Handler::log('Validando imagen destacada en create_singlecar...');
        Vehicle_Debug_Handler::log('$_FILES: ' . print_r($_FILES, true));
        Vehicle_Debug_Handler::log('$params: ' . print_r($params, true));
        
        // Verificar si se ha proporcionado una imagen destacada como archivo
        if (isset($_FILES['imatge-destacada']) && !empty($_FILES['imatge-destacada']['tmp_name'])) {
            Vehicle_Debug_Handler::log('Imagen destacada encontrada en $_FILES[imatge-destacada]');
            $has_image = true;
        }
        // Verificar si se ha proporcionado una imagen destacada como ID
        elseif (isset($params['imatge-destacada-id']) && !empty($params['imatge-destacada-id'])) {
            Vehicle_Debug_Handler::log('Imagen destacada encontrada en imatge-destacada-id: ' . $params['imatge-destacada-id']);
            $has_image = true;
        }
        // Verificar si se ha proporcionado una imagen destacada como URL
        elseif (isset($params['imatge-destacada-url']) && !empty($params['imatge-destacada-url'])) {
            Vehicle_Debug_Handler::log('Imagen destacada encontrada en imatge-destacada-url: ' . $params['imatge-destacada-url']);
            $has_image = true;
        }
        // Verificar si se ha proporcionado una imagen destacada directamente
        elseif (isset($params['imatge-destacada']) && !empty($params['imatge-destacada'])) {
            Vehicle_Debug_Handler::log('Imagen destacada encontrada en imatge-destacada: ' . $params['imatge-destacada']);
            $has_image = true;
        }
        
        if (!$has_image) {
            Vehicle_Debug_Handler::log('No se encontró ninguna imagen destacada');
            throw new Exception('La imagen destacada es obligatoria. Debe proporcionar una imagen a través del campo "imatge-destacada", "imatge-destacada-id" o "imatge-destacada-url"');
        } else {
            Vehicle_Debug_Handler::log('Imagen destacada validada correctamente');
        }

        // Establecer valores por defecto solo si no están presentes (no sobrescribir valores del usuario)
        $defaults = [
            'frenada-regenerativa' => 'no',
            'one-pedal' => 'no',
            'aire-acondicionat' => 'no',
            'climatitzacio' => 'no',
            'vehicle-fumador' => 'no',
            'vehicle-accidentat' => 'no',
            'llibre-manteniment' => 'no',
            'revisions-oficials' => 'no',
            'impostos-deduibles' => 'no',
            'vehicle-a-canvi' => 'no',
            'anunci-destacat' => 'false'
        ];

        // Solo establecer defaults si el campo no existe o está vacío
        foreach ($defaults as $field => $default_value) {
            if (!isset($params[$field]) || $params[$field] === '' || $params[$field] === null) {
                $params[$field] = $default_value;
            }
        }

        // Manejar portes-cotxe con valor por defecto
        if (!isset($params['portes-cotxe']) || !is_numeric($params['portes-cotxe'])) {
            $params['portes-cotxe'] = '5';
        }
        
        // Manejar campos numéricos especiales
        if (empty($params['temps-recarrega-total']) || !is_numeric($params['temps-recarrega-total'])) {
            $params['temps-recarrega-total'] = '0';
        }
        
        if (empty($params['temps-recarrega-fins-80']) || !is_numeric($params['temps-recarrega-fins-80'])) {
            $params['temps-recarrega-fins-80'] = '0';
        }

        // Verificar el tipo de vehículo
        if (!isset($params['tipus-vehicle']) || empty($params['tipus-vehicle'])) {
            throw new Exception('El campo tipus-vehicle es obligatorio');
        }
        
        // Normalizar el tipo de vehículo (usando la función del archivo principal)
        $params['tipus-vehicle'] = normalize_vehicle_type($params['tipus-vehicle']);
        $vehicle_type = strtolower(trim($params['tipus-vehicle']));
        
        // Si el tipo es moto-quad-atv, simplificar para validaciones internas
        $simplified_type = $vehicle_type;
        if ($vehicle_type === 'moto-quad-atv') {
            $simplified_type = 'moto';
        }

        // Validar campos requeridos según el tipo de vehículo
        $required_fields = [
            'tipus-vehicle',
            'estat-vehicle',
        ];
        
        // Eliminamos versio como campo obligatorio
        // if ($simplified_type !== 'moto') {
        //     $required_fields[] = 'versio';
        // }
        
        // Añadir marques-cotxe y models-cotxe como obligatorios para todos EXCEPTO MOTO
        if ($simplified_type !== 'moto') {
            $required_fields[] = 'marques-cotxe';
            $required_fields[] = 'models-cotxe';
        } else {
            // Para motos, agregar marques-de-moto como obligatorio
            $required_fields[] = 'marques-de-moto';
        }

        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                throw new Exception('El campo ' . $field . ' es obligatorio');
            }
        }

        // Crear título automático
        if ($simplified_type !== 'moto') {
            $marca_term = isset($params['marques-cotxe']) ? get_term_by('slug', $params['marques-cotxe'], 'marques-coches') : null;
            $model_term = isset($params['models-cotxe']) ? get_term_by('slug', $params['models-cotxe'], 'marques-coches') : null;
            
            $marca_name = $marca_term ? $marca_term->name : ($params['marques-cotxe'] ?? '');
            $model_name = $model_term ? $model_term->name : ($params['models-cotxe'] ?? '');
            $params['titol-anunci'] = ucfirst($marca_name) . ' ' . strtoupper($model_name) . ' ' . ($params['versio'] ?? '');
        } else {
            // Para motos, usar marca y modelo de moto
            $marca_moto = isset($params['marques-de-moto']) ? $params['marques-de-moto'] : '';
            $model_moto = isset($params['models-moto']) ? $params['models-moto'] : '';
            $tipus_moto = isset($params['tipus-de-moto']) ? $params['tipus-de-moto'] : '';
            
            if (!empty($model_moto)) {
                $params['titol-anunci'] = ucfirst($marca_moto) . ' ' . strtoupper($model_moto);
            } else {
                $params['titol-anunci'] = ucfirst($marca_moto) . ' ' . strtoupper($tipus_moto);
            }
        }

        // Validar marca y modelo - solo si no es moto
        if ($simplified_type !== 'moto' && isset($params['marques-cotxe']) && isset($params['models-cotxe'])) {
            validate_brand_and_model($params['marques-cotxe'], $params['models-cotxe']);
        }

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

        // Los valores booleanos se procesan en process_and_save_meta_fields
        update_post_meta($post_id, 'portes-cotxe', $params['portes-cotxe']); // Guardar portes-cotxe
        update_post_meta($post_id, 'temps-recarrega-total', $params['temps-recarrega-total']);
        update_post_meta($post_id, 'temps-recarrega-fins-80', $params['temps-recarrega-fins-80']);

        // El campo anunci-destacat se procesa en process_and_save_meta_fields

        $wpdb->query('COMMIT');

        clear_vehicle_cache($post_id);

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
    
    // Establecer valor por defecto para frenada-regenerativa
    if (!isset($params['frenada-regenerativa'])) {
        update_post_meta($post_id, 'frenada-regenerativa', 'no');
    }
    
    // Establecer valor por defecto para one-pedal
    if (!isset($params['one-pedal'])) {
        update_post_meta($post_id, 'one-pedal', 'no');
    }
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

    return new WP_REST_Response($response, 201);
}
