<?php
// Registrar la ruta REST API para /singlecar
add_action('rest_api_init', function () {
    register_rest_route('api-motor/v1', '/vehicles', [
        [
            'methods' => 'GET',
            'callback' => 'get_singlecar',
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ],
        [
            'methods' => 'POST',
            'callback' => 'create_singlecar',
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ],
        [
            'methods' => 'PUT',
            'callback' => 'update_singlecar',
            'permission_callback' => function ($request) {
                $post_id = $request->get_param('post_id');
                $post = get_post($post_id);
                return $post && current_user_can('edit_post', $post_id);
            },
        ],
        [
            'methods' => 'DELETE',
            'callback' => 'delete_singlecar',
            'permission_callback' => function ($request) {
                $post_id = $request->get_param('post_id');
                $post = get_post($post_id);
                return $post && current_user_can('delete_post', $post_id);
            },
        ],
    ]);

    register_rest_route('api-motor/v1', '/vehicles/(?P<id>\d+)', [
        [
            'methods' => 'GET',
            'callback' => 'get_vehicle_details',
            'permission_callback' => function ($request) {
                if (!is_user_logged_in()) {
                    return false;
                }
                return verify_post_ownership($request['id']);
            },
        ],
        [
            'methods' => 'PUT',
            'callback' => 'update_singlecar',
            'permission_callback' => function ($request) {
                if (!is_user_logged_in()) {
                    return false;
                }
                return verify_post_ownership($request['id']);
            },
        ],
        [
            'methods' => 'DELETE',
            'callback' => 'delete_singlecar',
            'permission_callback' => function ($request) {
                if (!is_user_logged_in()) {
                    return false;
                }
                return verify_post_ownership($request['id']);
            },
        ],
        'args' => [
            'id' => [
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                }
            ]
        ]
    ]);

    register_rest_route('api-motor/v1', '/debug-fields', [
        'methods' => 'GET',
        'callback' => 'debug_vehicle_fields',
        'permission_callback' => '__return_true'
    ]);
});

require_once plugin_dir_path(__FILE__) . 'class-vehicle-fields.php';
require_once plugin_dir_path(__FILE__) . 'class-vehicle-field-handler.php';

// Incluir funciones necesarias para el manejo de medios
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

function get_field_label($field_name, $value) {
    // Si no hay valor, devolver string vacío
    if (empty($value) && $value !== '0' && $value !== 0) {
        return '';
    }

    // Manejar arrays serializados de extras
    $extras_fields = ['extres-cotxe', 'extres-moto', 'extres-autocaravana', 'extres-habitacle', 'cables-recarrega', 'connectors'];
    if (in_array($field_name, $extras_fields)) {
        try {
            // Intentar deserializar si es una cadena serializada
            if (is_string($value)) {
                if (strpos($value, 'a:') === 0) {
                    $value = unserialize($value);
                } else {
                    // Si no es serializado pero es string, podría ser JSON
                    $maybe_array = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $maybe_array;
                    }
                }
            }

            // Si no es array o está vacío, devolver array vacío
            if (!is_array($value)) {
                return [];
            }
            if (empty($value)) {
                return [];
            }

            // Obtener el ID del glosario correspondiente
            $glossary_map = [
                'extres-cotxe' => '54',        // ID del glosario de extras de coche
                'extres-moto' => '55',         // ID del glosario de extras de moto
                'extres-autocaravana' => '56', // ID del glosario de extras de autocaravana
                'extres-habitacle' => '57'     // ID del glosario de extras de habitáculo
            ];

            $glossary_id = $glossary_map[$field_name] ?? null;
            if (!$glossary_id || !function_exists('jet_engine') || !isset(jet_engine()->glossaries)) {
                return $value;
            }

            $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
            if (empty($options)) {
                return $value;
            }

            $labels = [];
            foreach ($value as $extra) {
                if (isset($options[$extra])) {
                    $labels[] = $options[$extra];
                } elseif (isset($options[trim($extra)])) {
                    $labels[] = $options[trim($extra)];
                }
            }
            return $labels;
        } catch (Exception $e) {
            error_log("Error procesando extras $field_name: " . $e->getMessage());
            return [];
        }
    }

    // Obtener el tipo de campo
    $meta_fields = Vehicle_Fields::get_meta_fields();
    $field_type = $meta_fields[$field_name] ?? null;

    // Manejar campos booleanos y switch
    if ($field_type === 'boolean' || $field_type === 'switch') {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    // Si es un campo numérico, devolverlo como está
    if ($field_type === 'number') {
        return $value;
    }

    // Si es un campo de glosario
    if ($field_type === 'glossary') {
        if (!function_exists('jet_engine') || !isset(jet_engine()->glossaries)) {
            return $value;
        }

        try {
            // Obtener las opciones del glosario
            $glossary_id = null;
            switch ($field_name) {
                case 'segment':
                    $glossary_id = '52'; // ID del glosario de segmentos
                    break;
                case 'traccio':
                    $glossary_id = '53'; // ID del glosario de tracción
                    break;
                // Añadir más casos según sea necesario
            }

            if ($glossary_id) {
                $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
                
                // Si es un array (como en extres-cotxe)
                if (is_array($value) || (is_string($value) && strpos($value, 'a:') === 0)) {
                    if (is_string($value)) {
                        $value = unserialize($value);
                    }
                    if (empty($value)) {
                        return [];
                    }
                    $labels = [];
                    foreach ($value as $single_value) {
                        $labels[] = $options[$single_value] ?? $single_value;
                    }
                    return $labels;
                }
                
                // Para valores individuales
                return $options[$value] ?? $value;
            }
            
            return $value;
        } catch (Exception $e) {
            error_log("Error al procesar campo de glosario: " . $e->getMessage());
            return $value;
        }
    }

    // Si es un campo de taxonomía
    $taxonomy_fields = Vehicle_Fields::get_taxonomy_fields();
    if (isset($taxonomy_fields[$field_name])) {
        $term = get_term_by('slug', $value, $taxonomy_fields[$field_name]);
        return $term ? $term->name : ($value ?: '');
    }

    // Si es un campo de selección o radio
    if ($field_type === 'select' || $field_type === 'radio') {
        // Obtener opciones del campo desde JetEngine
        if (function_exists('jet_engine')) {
            $meta_fields = jet_engine()->meta_boxes->get_meta_fields_for_object('singlecar');
            foreach ($meta_fields as $meta_field) {
                if ($meta_field['name'] === $field_name && isset($meta_field['options'])) {
                    return $meta_field['options'][$value] ?? ($value ?: '');
                }
            }
        }
    }

    return $value ?: '';
}

function get_singlecar($request)
{
    $params = $request->get_params();
    $current_user_id = get_current_user_id();

    // Configuración de paginación
    $paged = isset($params['page']) ? intval($params['page']) : 1;
    $posts_per_page = isset($params['per_page']) ? intval($params['per_page']) : -1;

    // Argumentos base de la consulta
    $args = [
        'post_type' => 'singlecar',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'author' => $current_user_id, // Filtrar solo por el autor actual
    ];

    // Si el usuario es administrador y se proporciona un user_id específico
    if (current_user_can('administrator') && isset($params['user_id'])) {
        $args['author'] = intval($params['user_id']);
    }

    // Filtro por post_id
    if (isset($params['post_id'])) {
        $args['p'] = intval($params['post_id']);
    }

    // Filtro por post_name
    if (isset($params['post_name'])) {
        $args['name'] = sanitize_title($params['post_name']);
    }

    // Filtros por taxonomías
    $tax_query = [];
    $taxonomies = get_object_taxonomies('singlecar');
    foreach ($taxonomies as $taxonomy) {
        if (isset($params[$taxonomy])) {
            $terms = explode(',', $params[$taxonomy]);

            // Obtener los slugs de los términos por nombre
            $term_slugs = [];
            foreach ($terms as $term_name) {
                $term = get_term_by('name', $term_name, $taxonomy);
                if ($term) {
                    $term_slugs[] = $term->slug;
                }
            }

            // Agregar filtro de taxonomía solo si se encontraron términos válidos
            if (!empty($term_slugs)) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $term_slugs,
                    'operator' => 'IN',
                ];
            }
        }
    }
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    // Filtros por metadatos
    $meta_query = [];
    foreach ($params as $key => $value) {
        if ($key !== 'page' && $key !== 'per_page' && $key !== 'post_id' && $key !== 'post_name' && $key !== 'user_id' && !in_array($key, $taxonomies)) {
            $meta_query[] = [
                'key' => $key,
                'value' => $value,
                'compare' => 'LIKE',
            ];
        }
    }
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    $query = new WP_Query($args);
    $vehicles = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $vehicle_id = get_the_ID();
            $post = get_post($vehicle_id);
            $meta = get_post_meta($vehicle_id);
            $terms = wp_get_post_terms($vehicle_id, 'types-of-transport', ['fields' => 'all']);
            $marques_terms = wp_get_post_terms($vehicle_id, 'marques-coches', ['fields' => 'all']);

            // Campos básicos del vehículo
            $vehicle = [
                'id' => $vehicle_id,
                'slug' => $post->post_name, // Agregar el slug del post
                'titol-anunci' => get_the_title($vehicle_id),
                'descripcio-anunci' => $post->post_content,
                'tipus-de-vehicle' => $terms[0]->name ?? null,
                'marques-cotxe' => $marques_terms[1]->name ?? null,
                'models-cotxe' => $marques_terms[0]->name ?? null,
            ];

            // Lista de campos a excluir
            $excluded_fields = [
                '_thumbnail_id',
                'ad_gallery',
                '_edit_lock',
                '_edit_last',
                '_bricks_template_type',
                '_bricks_page_content_2',
                '_bricks_editor_mode',
                'jet_engine_store_count_ads-views'
            ];

            // Añadir metadatos filtrados
            foreach ($meta as $key => $value) {
                if (!in_array($key, $excluded_fields) && !Vehicle_Fields::should_exclude_field($key)) {
                    $meta_value = is_array($value) ? $value[0] : $value;
                    $vehicle[$key] = get_field_label($key, $meta_value);
                }
            }

            // Añadir información de imágenes
            $vehicle['imatge-destacada-id'] = get_post_thumbnail_id($vehicle_id);
            $vehicle['imatge-destacada-url'] = get_the_post_thumbnail_url($vehicle_id, 'full');

            // Obtener galería
            $gallery_ids = get_post_meta($vehicle_id, 'ad_gallery', true);
            if (!empty($gallery_ids)) {
                $gallery_urls = [];
                // Si es una cadena, convertir a array
                if (is_string($gallery_ids)) {
                    $gallery_ids = explode(',', $gallery_ids);
                }
                foreach ($gallery_ids as $gallery_id) {
                    $url = wp_get_attachment_url(trim($gallery_id));
                    if ($url) {
                        $gallery_urls[] = $url;
                    }
                }
                $vehicle['galeria-vehicle-urls'] = $gallery_urls;
            }

            // Ocultar dies-caducitat a usuarios no administradores
            if (!current_user_can('administrator')) {
                unset($vehicle['dies-caducitat']);
            }

            $vehicles[] = $vehicle;
        }
    }

    wp_reset_postdata();

    // Obtener total de páginas
    $total_posts = $query->found_posts;
    $total_pages = $query->max_num_pages;

    // Preparar respuesta con paginación
    $response = [
        'vehicles' => $vehicles,
        'total_posts' => $total_posts,
        'total_pages' => $total_pages,
        'current_page' => $paged,
    ];

    return new WP_REST_Response($response, 200);
}

function create_singlecar($request)
{
    global $wpdb;
    $wpdb->query('START TRANSACTION');

    try {
        $params = $request->get_params();

        // Agregar el mapeo de campos nuevos a antiguos
        $field_mapping = array(
            'anunci-destacat' => 'is-vip',
            'data-destacat' => 'data-vip',
            'velocitat-max-cotxe' => 'velocitat-maxima',
            'numero-maleters-cotxe' => 'maleters',
            'capacitat-maleters-cotxe' => 'capacitat-total',
            'acceleracio-0-100-cotxe' => 'acceleracio-0-100',
            'numero-motors' => 'n-motors',
            'any-fabricacio' => 'any',
            'galeria-vehicle' => 'ad_gallery',
            'carrosseria-cotxe' => 'segment',
            'traccio' => 'traccio',
            'roda-recanvi' => 'roda-recanvi'
        );

        // Procesar los parámetros con el mapeo
        $processed_params = array();
        foreach ($params as $key => $value) {
            $db_field = isset($field_mapping[$key]) ? $field_mapping[$key] : $key;
            error_log("Mapeando campo {$key} a {$db_field}");
            $processed_params[$db_field] = $value;
        }

        // Campos requeridos para crear/actualizar un vehículo
        $required_fields = array(
            'marques-cotxe',
            'models-cotxe',
            'versio',
            'tipus-vehicle',
            'tipus-combustible',
            'tipus-canvi-cotxe',
            'tipus-propulsor',
            'estat-vehicle',
            'preu'
        );

        // Validar campos requeridos
        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                return new WP_Error('missing_field', 'El campo ' . $field . ' es obligatorio', array('status' => 400));
            }
        }

        // Obtener los nombres de los términos para el título
        $marca_term = get_term_by('slug', $params['marques-cotxe'], 'marques-coches');
        $model_term = get_term_by('slug', $params['models-cotxe'], 'marques-coches');
        
        // Usar los nombres de los términos si existen, si no usar los slugs
        $marca_name = $marca_term ? $marca_term->name : $params['marques-cotxe'];
        $model_name = $model_term ? $model_term->name : $params['models-cotxe'];
        
        // Generar título automáticamente usando los nombres
        $titol_anunci = ucfirst($marca_name) . ' ' . strtoupper($model_name) . ' ' . $params['versio'];
        $params['titol-anunci'] = $titol_anunci;

        // Transformar el campo carrosseria a segment si existe
        if (isset($params['carrosseria'])) {
            $params['segment'] = $params['carrosseria'];
            unset($params['carrosseria']);
        }

        // Validar taxonomías antes de crear el post
        $taxonomy_fields = Vehicle_Fields::get_taxonomy_fields();
        $allowed_values = Vehicle_Fields::get_allowed_taxonomy_values();

        foreach ($taxonomy_fields as $field => $taxonomy) {
            if (isset($params[$field])) {
                // Validar que el valor esté permitido
                if (isset($allowed_values[$field]) && !in_array($params[$field], $allowed_values[$field])) {
                    throw new Exception(sprintf(
                        'El valor "%s" no es válido para %s. Valores válidos: %s',
                        $params[$field],
                        $field,
                        implode(', ', $allowed_values[$field])
                    ));
                }

                // Verificar que el término existe
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

        // Verificar marca y modelo
        $marca = get_term_by('slug', $params['marques-cotxe'], 'marques-coches');
        if (!$marca) {
            throw new Exception("La marca especificada no existe");
        }

        $modelo = get_term_by('slug', $params['models-cotxe'], 'marques-coches');
        if (!$modelo || $modelo->parent != $marca->term_id) {
            throw new Exception("El modelo especificado no existe o no pertenece a la marca indicada");
        }

        // Validar todos los campos antes de crear el post
        validate_all_fields($params);

        // Procesar imágenes antes de crear el post
        $image_fields = ['imatge-destacada-id', 'galeria-vehicle'];
        $processed_images = [];

        foreach ($image_fields as $field) {
            if (isset($params[$field]) && !empty($params[$field])) {
                if (is_string($params[$field])) {
                    // Caso 1: Base64
                    if (strpos($params[$field], 'data:image') === 0) {
                        $upload_dir = wp_upload_dir();

                        // Decodificar la imagen base64
                        $image_parts = explode(";base64,", $params[$field]);
                        $image_type_aux = explode("image/", $image_parts[0]);
                        $image_type = $image_type_aux[1];
                        $image_base64 = base64_decode($image_parts[1]);

                        // Generar nombre único
                        $filename = uniqid() . '.' . $image_type;
                        $file_path = $upload_dir['path'] . '/' . $filename;

                        // Guardar archivo
                        file_put_contents($file_path, $image_base64);

                        // Preparar attachment
                        $wp_filetype = wp_check_filetype($filename, null);
                        $attachment = array(
                            'post_mime_type' => $wp_filetype['type'],
                            'post_title' => sanitize_file_name($filename),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        );

                        // Insertar attachment
                        $attach_id = wp_insert_attachment($attachment, $file_path);
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                        wp_update_attachment_metadata($attach_id, $attach_data);

                        $processed_images[$field] = $attach_id;
                    }
                    // Caso 2: URL
                    else if (filter_var($params[$field], FILTER_VALIDATE_URL)) {
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                        require_once(ABSPATH . 'wp-admin/includes/media.php');
                        require_once(ABSPATH . 'wp-admin/includes/image.php');

                        $temp_file = download_url($params[$field]);

                        if (!is_wp_error($temp_file)) {
                            $file_array = array(
                                'name' => basename($params[$field]),
                                'tmp_name' => $temp_file
                            );

                            $attach_id = media_handle_sideload($file_array, 0);

                            if (!is_wp_error($attach_id)) {
                                $processed_images[$field] = $attach_id;
                            }

                            @unlink($temp_file);
                        }
                    }
                    // Caso 3: ID existente
                    else if (is_numeric($params[$field])) {
                        $processed_images[$field] = intval($params[$field]);
                    }
                }
                // Caso 4: Array de imágenes (galería)
                else if (is_array($params[$field])) {
                    $processed_images[$field] = [];
                    foreach ($params[$field] as $image) {
                        if (is_string($image)) {
                            if (strpos($image, 'data:image') === 0) {
                                // Procesar imagen base64
                                $upload_dir = wp_upload_dir();

                                // Decodificar la imagen base64
                                $image_parts = explode(";base64,", $image);
                                $image_type_aux = explode("image/", $image_parts[0]);
                                $image_type = $image_type_aux[1];
                                $image_base64 = base64_decode($image_parts[1]);

                                // Generar nombre único
                                $filename = uniqid() . '.' . $image_type;
                                $file_path = $upload_dir['path'] . '/' . $filename;

                                // Guardar archivo
                                file_put_contents($file_path, $image_base64);

                                // Preparar attachment
                                $wp_filetype = wp_check_filetype($filename, null);
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title' => sanitize_file_name($filename),
                                    'post_content' => '',
                                    'post_status' => 'inherit'
                                );

                                // Insertar attachment
                                $attach_id = wp_insert_attachment($attachment, $file_path);
                                require_once(ABSPATH . 'wp-admin/includes/image.php');
                                $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                                wp_update_attachment_metadata($attach_id, $attach_data);

                                $processed_images[$field][] = $attach_id;
                            }
                            else if (filter_var($image, FILTER_VALIDATE_URL)) {
                                // Procesar URL
                                require_once(ABSPATH . 'wp-admin/includes/file.php');
                                require_once(ABSPATH . 'wp-admin/includes/media.php');
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                $temp_file = download_url($image);

                                if (!is_wp_error($temp_file)) {
                                    $file_array = array(
                                        'name' => basename($image),
                                        'tmp_name' => $temp_file
                                    );

                                    $attach_id = media_handle_sideload($file_array, 0);

                                    if (!is_wp_error($attach_id)) {
                                        $processed_images[$field][] = $attach_id;
                                    }

                                    @unlink($temp_file);
                                }
                            }
                            else if (is_numeric($image)) {
                                // Usar ID existente
                                $processed_images[$field][] = intval($image);
                            }
                        }
                    }
                }
            }
        }

        // Crear el post
        error_log("Creando post con datos: " . print_r($post_data, true));
        $post_data = array(
            'post_title' => wp_strip_all_tags($params['titol-anunci']),
            'post_content' => $params['descripcio-anunci'],
            'post_status' => 'publish',
            'post_type' => 'singlecar'
        );

        $post_id = wp_insert_post($post_data);
        error_log("Post creado con ID: " . $post_id);

        if (is_wp_error($post_id)) {
            error_log("Error al crear post: " . $post_id->get_error_message());
            throw new Exception($post_id->get_error_message());
        }

        // Logging de creación
        Vehicle_API_Logger::get_instance()->log_action(
            $post_id,
            'create',
            array(
                'title' => $params['titol-anunci'],
                'user_id' => get_current_user_id()
            )
        );

        // Asignar las taxonomías al post creado
        foreach ($taxonomy_fields as $field => $taxonomy) {
            if (isset($params[$field])) {
                $term = get_term_by('slug', $params[$field], $taxonomy);
                $result = wp_set_object_terms($post_id, $term->term_id, $taxonomy);
                if (is_wp_error($result)) {
                    throw new Exception(sprintf('Error al asignar el término %s', $params[$field]));
                }
            }
        }

        // Asignar marca y modelo
        $result = wp_set_object_terms($post_id, [$marca->term_id, $modelo->term_id], 'marques-coches');
        if (is_wp_error($result)) {
            throw new Exception('Error al asignar marca y modelo');
        }

        // Procesar y guardar los campos meta
        error_log("Iniciando procesamiento de campos meta para post_id: " . $post_id);
        error_log("Parámetros a procesar: " . print_r($params, true));
        process_and_save_meta_fields($post_id, $params);
        error_log("Campos meta procesados exitosamente");

        // Después de crear el post, asignar las imágenes procesadas
        if (!empty($processed_images)) {
            error_log("Procesando imágenes: " . print_r($processed_images, true));
            foreach ($processed_images as $field => $value) {
                if ($field === 'imatge-destacada-id') {
                    error_log("Estableciendo imagen destacada: " . $value);
                    set_post_thumbnail($post_id, $value);
                } else if ($field === 'galeria-vehicle' && is_array($value)) {
                    // Convertir array de IDs a string separado por comas
                    $gallery_string = implode(',', $value);
                    error_log("Estableciendo galería: " . $gallery_string);
                    delete_post_meta($post_id, 'ad_gallery');
                    add_post_meta($post_id, 'ad_gallery', $gallery_string);
                }
            }
        }

        // Establecer valor inicial de dies-caducitat
        update_post_meta($post_id, 'dies-caducitat', 365);

        $wpdb->query('COMMIT');

        // Obtener los datos actualizados del post
        $post = get_post($post_id);
        $response = [
            'status' => 'success',
            'message' => 'Vehículo creado exitosamente',
            'post_id' => $post_id,
            'titol-anunci' => $post->post_title,
            'descripcio-anunci' => $post->post_content,
            'status' => $post->post_status
        ];

        // Agregar campos de taxonomía a la respuesta
        foreach ($taxonomy_fields as $field => $taxonomy) {
            $terms = wp_get_object_terms($post_id, $taxonomy);
            if (!is_wp_error($terms) && !empty($terms)) {
                $response[$field] = $terms[0]->slug;
            }
        }

        // Agregar marca y modelo a la respuesta
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

        return new WP_REST_Response($response, 201);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');

        if (isset($post_id) && $post_id) {
            wp_delete_post($post_id, true);
        }

        return new WP_REST_Response(array(
            'status' => 'error',
            'message' => $e->getMessage()
        ), 400);
    }
}

function validate_glossary_field($field, $value)
{
    error_log("Validando campo de glosario: " . $field);
    error_log("Valor a validar: " . print_r($value, true));

    // Mapeo de campos a IDs de glosario
    $glossary_mapping = [
        'segment' => '41',
        'carrosseria-cotxe' => '41',    // Mismo ID que segment
        'traccio' => '59',
        'emissions-vehicle' => '58',
        'roda-recanvi' => '60',
        'extres-cotxe' => '54',
        'cables-recarrega' => '50',
        'connectors' => '49',
        'tipus-tapisseria' => '52',     // ID para tipus-tapisseria (tipos de tapicería)
        'color-tapisseria' => '53',     // ID para color-tapisseria (colores de tapicería)
        'color-vehicle' => '51'         // ID para color-vehicle (colores de vehículo)
    ];

    error_log("Glosarios disponibles: " . print_r($glossary_mapping, true));

    if (!isset($glossary_mapping[$field])) {
        error_log("ERROR: Campo no encontrado en el mapeo de glosarios: " . $field);
        throw new Exception("Campo de glosario no reconocido: " . $field);
    }

    $glossary_id = $glossary_mapping[$field];
    error_log("ID del glosario para el campo: " . $glossary_id);

    $jet_engine = jet_engine();
    $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);
    error_log("Opciones disponibles: " . print_r($options, true));

    // Si el campo acepta múltiples valores
    if (in_array($field, ['cables-recarrega', 'connectors', 'extres-cotxe'])) {
        // Si es string, convertir a array
        if (is_string($value)) {
            $value = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $value = [$value];
            }
        }

        // Validar que sea un array
        if (!is_array($value)) {
            throw new Exception("El campo {$field} debe ser un array de valores");
        }

        // Validar cada valor del array
        foreach ($value as $single_value) {
            if (!is_string($single_value)) {
                error_log("ERROR: El valor debe ser una cadena de texto: " . print_r($single_value, true));
                throw new Exception("Los valores para {$field} deben ser cadenas de texto");
            }

            $valid_keys = array_keys($options);
            if (!in_array($single_value, $valid_keys)) {
                error_log("ERROR: Valor no válido: " . $single_value);
                $valid_values = implode(', ', $valid_keys);
                throw new Exception("Valor no válido para {$field}. Valores permitidos: {$valid_values}");
            }
        }
    } else {
        // Para campos que solo aceptan un valor
        if (!is_string($value)) {
            error_log("ERROR: El valor debe ser una cadena de texto: " . print_r($value, true));
            throw new Exception("El valor para {$field} debe ser una cadena de texto");
        }

        $valid_keys = array_keys($options);
        if (!in_array($value, $valid_keys)) {
            error_log("ERROR: Valor no válido. Valores permitidos: " . implode(', ', $valid_keys));
            $valid_values = implode(', ', $valid_keys);
            throw new Exception("Valor no válido para {$field}. Valores permitidos: {$valid_values}");
        }
    }

    error_log("Validación exitosa para el campo: " . $field);
    return true;
}

function validate_all_fields($params)
{
    // Lista de campos a validar con sus tipos
    $fields_to_validate = [
        'venedor' => 'glossary',
        'traccio' => 'glossary',
        'roda-recanvi' => 'glossary',
        'segment' => 'glossary',
        'color-vehicle' => 'glossary',
        'tipus-tapisseria' => 'glossary',
        'color-tapisseria' => 'glossary',
        'emissions-vehicle' => 'glossary',
        'cables-recarrega' => 'glossary',
        'connectors' => 'glossary',
        'extres-cotxe' => 'glossary',
        // Campos booleanos
        'is-vip' => 'boolean',
        'venut' => 'boolean',
        'llibre-manteniment' => 'boolean',
        'revisions-oficials' => 'boolean',
        'impostos-deduibles' => 'boolean',
        'vehicle-a-canvi' => 'boolean',
        'garantia' => 'boolean',
        'vehicle-accidentat' => 'boolean',
        // Campos numéricos
        'dies-caducitat' => 'number',
        'preu' => 'number',
        'preu-mensual' => 'number',
        'preu-diari' => 'number',
        'preu-antic' => 'number',
        'quilometratge' => 'number',
        'cilindrada' => 'number',
        'potencia-cv' => 'number',
        'potencia-kw' => 'number',
        'portes-cotxe' => 'number',
        'places-cotxe' => 'number',
        'velocitat-maxima' => 'number',
        'acceleracio-0-100' => 'number',
        'capacitat-total' => 'number',
        'maleters' => 'number'
    ];

    $errors = [];

    // Validar cada campo
    foreach ($fields_to_validate as $field => $type) {
        if (isset($params[$field])) {
            try {
                Vehicle_Field_Handler::process_field($field, $params[$field], $type);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    // Validar campos de glosario
    $glossary_fields = [
        'traccio',
        'roda-recanvi',
        'segment',
        'color-vehicle',
        'tipus-tapisseria',
        'color-tapisseria',
        'emissions-vehicle',
        'cables-recarrega',
        'connectors',
        'extres-cotxe'
    ];

    foreach ($glossary_fields as $field) {
        if (isset($params[$field])) {
            try {
                validate_glossary_field($field, $params[$field]);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    // Si hay errores, lanzar una excepción con todos los errores
    if (!empty($errors)) {
        throw new Exception("Errores de validación:\n" . implode("\n", $errors));
    }
}

function process_and_save_meta_fields($post_id, $params)
{
    $meta_fields = Vehicle_Fields::get_meta_fields();
    $flag_fields = Vehicle_Fields::get_flag_fields();
    $errors = [];

    // Agregar log de depuración
    error_log('Procesando campos meta para post_id: ' . $post_id);
    error_log('Parámetros recibidos: ' . print_r($params, true));
    error_log('Meta fields configurados: ' . print_r($meta_fields, true));
    error_log('Flag fields configurados: ' . print_r($flag_fields, true));

    try {
        // Validar todos los campos antes de procesar
        error_log("Iniciando validación de campos");
        validate_all_fields($params);
        error_log("Validación de campos exitosa");
    } catch (Exception $e) {
        error_log("Error al validar campos: " . $e->getMessage());
        throw $e;
    }

    // Mapeo específico para campos que necesitan transformación
    $field_mapping = array(
        'any-fabricacio' => 'any',
        'velocitat-max-cotxe' => 'velocitat-maxima',
        'numero-maleters-cotxe' => 'maleters',
        'capacitat-maleters-cotxe' => 'capacitat-total',
        'acceleracio-0-100-cotxe' => 'acceleracio-0-100',
        'numero-motors' => 'n-motors',
        'any-fabricacio' => 'any',
        'galeria-vehicle' => 'ad_gallery',
        'carrosseria-cotxe' => 'segment',
        'traccio' => 'traccio',
        'roda-recanvi' => 'roda-recanvi',
        'anunci-destacat' => 'is-vip'
    );

    error_log("Mapeo de campos: " . print_r($field_mapping, true));

    // Procesar los campos mapeados primero
    foreach ($field_mapping as $api_field => $db_field) {
        if (isset($params[$api_field])) {
            $value = $params[$api_field];
            error_log("Procesando campo mapeado - API: {$api_field}, DB: {$db_field}, Valor: " . print_r($value, true));

            // Manejo especial para campos booleanos mapeados
            if ($db_field === 'is-vip') {
                $value = strtolower(trim($value));
                $true_values = ['true', 'si', '1', 'yes', 'on'];

                if (in_array($value, $true_values, true)) {
                    $value = 'true';
                    error_log("Campo is-vip establecido a true, actualizando data-vip");
                    update_post_meta($post_id, 'data-vip', current_time('timestamp'));
                } else {
                    $value = 'false';
                    error_log("Campo is-vip establecido a false, limpiando data-vip");
                    update_post_meta($post_id, 'data-vip', '');
                }
            }

            $result = update_post_meta($post_id, $db_field, $value);
            error_log("Resultado de actualización de {$db_field}: " . ($result ? "exitoso" : "fallido"));
        }
    }

    // Excluir data-vip y venedor de los campos a procesar ya que se manejan automáticamente
    $excluded_fields = ['data-vip', 'venedor'];

    // Establecer venedor como "professional" por defecto
    update_post_meta($post_id, 'venedor', 'professional');

    // Procesar campos normales con el mapeo
    foreach ($meta_fields as $field => $type) {
        if (isset($params[$field]) && !Vehicle_Fields::should_exclude_field($field) && !isset($field_mapping[$field]) && !in_array($field, $excluded_fields)) {
            try {
                // Determinar el nombre real del campo en la base de datos
                $db_field = isset($field_mapping[$field]) ? $field_mapping[$field] : $field;

                $log_value = is_array($params[$field]) ? json_encode($params[$field]) : $params[$field];
                error_log("Procesando campo: {$field} (DB field: {$db_field}) con valor: {$log_value} de tipo: {$type}");

                // El resto del procesamiento usando $db_field en lugar de $field para guardar
                if ($type === 'number') {
                    $value = floatval($params[$field]);
                    $result = update_post_meta($post_id, $db_field, $value);
                    error_log("Resultado de guardar {$db_field}: " . ($result ? 'éxito' : 'fallo'));
                }
                // Procesar campos switch/boolean
                else if ($type === 'switch' || $type === 'boolean') {
                    $value = strtolower(trim($params[$field]));
                    $true_values = ['true', 'si', '1', 'yes', 'on'];
                    $false_values = ['false', 'no', '0', 'off'];

                    if (in_array($value, $true_values, true)) {
                        $value = 'true';
                    } elseif (in_array($value, $false_values, true)) {
                        $value = 'false';
                    } else {
                        $value = 'false'; // valor por defecto si no coincide con ninguno
                    }

                    update_post_meta($post_id, $db_field, $value);
                }
                // Procesar campos select/radio
                else if (in_array($type, ['select', 'radio'])) {
                    update_post_meta($post_id, $db_field, sanitize_text_field($params[$field]));
                }
                // Manejo especial para extres-cotxe
                else if ($field === 'extres-cotxe') {
                    $processed_value = is_array($params[$field]) ? $params[$field] : [$params[$field]];

                    // Eliminar valores antiguos
                    delete_post_meta($post_id, $db_field);

                    // Crear la estructura que espera JetEngine con tres índices
                    $jet_engine_format = [
                        0 => array_combine(range(0, count($processed_value) - 1), $processed_value),
                        1 => array_combine(range(0, count($processed_value) - 1), $processed_value),
                        2 => array_combine(range(0, count($processed_value) - 1), $processed_value)
                    ];

                    // Guardar cada array como una entrada separada
                    foreach ($jet_engine_format as $index => $value) {
                        add_post_meta($post_id, $db_field, $value);
                    }
                }
                // Manejo especial para imágenes en base64
                else if ($field === 'imatge-destacada-id' || $field === 'galeria-vehicle') {
                    if (strpos($params[$field], 'data:image') === 0) {
                        // Es una imagen en base64, la procesamos
                        $upload_dir = wp_upload_dir();
                        $upload_path = $upload_dir['path'];
                        $upload_url = $upload_dir['url'];

                        // Decodificar la imagen base64
                        $image_parts = explode(";base64,", $params[$field]);
                        $image_type_aux = explode("image/", $image_parts[0]);
                        $image_type = $image_type_aux[1];
                        $image_base64 = base64_decode($image_parts[1]);

                        // Generar nombre único para el archivo
                        $filename = uniqid() . '.' . $image_type;
                        $file_path = $upload_path . '/' . $filename;

                        // Guardar el archivo
                        file_put_contents($file_path, $image_base64);

                        // Preparar la información del archivo para WordPress
                        $wp_filetype = wp_check_filetype($filename, null);
                        $attachment = array(
                            'post_mime_type' => $wp_filetype['type'],
                            'post_title' => sanitize_file_name($filename),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        );

                        // Insertar el adjunto en la biblioteca de medios
                        $attach_id = wp_insert_attachment($attachment, $file_path);
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                        wp_update_attachment_metadata($attach_id, $attach_data);

                        // Guardar el ID del adjunto como meta
                        update_post_meta($post_id, $db_field, $attach_id);
                    } else {
                        // Si no es base64, asumimos que es un ID válido
                        update_post_meta($post_id, $db_field, sanitize_text_field($params[$field]));
                    }
                }
                // Procesar el resto de campos como texto normal
                else {
                    update_post_meta($post_id, $db_field, sanitize_text_field($params[$field]));
                }
            } catch (Exception $e) {
                error_log("Error al procesar campo {$field}: " . $e->getMessage());
                $errors[] = $e->getMessage();
            }
        }
    }

    // Procesar los campos de glosario primero
    $glossary_fields = array(
        'carrosseria-cotxe' => 'carrosseria-cotxe',
        'traccio' => 'traccio',
        'roda-recanvi' => 'roda-recanvi',
        'extres-cotxe' => 'extres-cotxe',
        'cables-recarrega' => 'cables-recarrega',
        'connectors' => 'connectors'
    );

    // Función helper para procesar campos de array de glosario
    function process_glossary_array_field($post_id, $field_name, $value) {
        error_log("Procesando campo de array de glosario {$field_name} con valor: " . print_r($value, true));
        
        // Validar el valor contra el glosario
        validate_glossary_field($field_name, $value);
        
        // Procesar el valor
        $processed_value = is_array($value) ? $value : [$value];
        
        error_log("Valor procesado de {$field_name}: " . print_r($processed_value, true));

        // Eliminar valores antiguos
        delete_post_meta($post_id, $field_name);

        // Crear la estructura que espera JetEngine con tres índices
        $jet_engine_format = [
            0 => array_combine(range(0, count($processed_value) - 1), $processed_value),
            1 => array_combine(range(0, count($processed_value) - 1), $processed_value),
            2 => array_combine(range(0, count($processed_value) - 1), $processed_value)
        ];

        error_log("Formato JetEngine para {$field_name}: " . print_r($jet_engine_format, true));

        // Guardar cada array como una entrada separada
        foreach ($jet_engine_format as $index => $value) {
            $result = add_post_meta($post_id, $field_name, $value);
            error_log("Resultado de guardar {$field_name} índice {$index}: " . ($result ? "exitoso" : "fallido"));
        }
    }

    // Procesar campos que requieren formato de array especial
    $array_fields = ['extres-cotxe', 'cables-recarrega', 'connectors'];
    foreach ($array_fields as $field) {
        if (isset($params[$field])) {
            try {
                process_glossary_array_field($post_id, $field, $params[$field]);
            } catch (Exception $e) {
                error_log("Error al procesar {$field}: " . $e->getMessage());
                $errors[] = $e->getMessage();
            }
        }
    }

    // Procesar carrosseria-cotxe especialmente ya que su valor va al glosario segment
    if (isset($params['carrosseria'])) {
        $params['segment'] = $params['carrosseria'];
        unset($params['carrosseria']);
    }

    foreach ($glossary_fields as $field => $db_field) {
        if (isset($params[$field])) {
            try {
                $value = $params[$field];
                validate_glossary_field($db_field, $value); // Esto validará contra el glosario correcto
                error_log("Guardando campo de glosario {$field} como {$db_field} con valor: {$value}");
                update_post_meta($post_id, $db_field, $value);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    // También procesar el campo any-fabricacio si existe pero no está en meta_fields
    if (isset($params['any-fabricacio']) && !isset($meta_fields['any-fabricacio'])) {
        update_post_meta($post_id, 'any', $params['any-fabricacio']);
        error_log("Guardando any-fabricacio como any: " . $params['any-fabricacio']);
    }

    // Manejar el campo data-vip y is-vip
    if (isset($params['data-vip'])) {
        try {
            $date_value = Vehicle_Field_Handler::process_field('data-vip', $params['data-vip'], 'date');
            if ($date_value !== null) {
                update_post_meta($post_id, 'data-vip', $date_value);
                update_post_meta($post_id, 'is-vip', 'true');
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    // Procesar otros campos con flag
    foreach ($flag_fields as $field => $config) {
        if ($field !== 'is-vip' && isset($params[$field])) {
            try {
                $processed_value = Vehicle_Field_Handler::process_field($field, $params[$field], $config['type']);
                if ($processed_value !== null) {
                    update_post_meta($post_id, $config['meta_key'], $processed_value);
                    if (isset($config['flag_key'])) {
                        update_post_meta($post_id, $config['flag_key'], 'true');
                    }
                } else {
                    delete_post_meta($post_id, $config['meta_key']);
                    if (isset($config['flag_key'])) {
                        delete_post_meta($post_id, $config['flag_key']);
                    }
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    // Manejar el campo dies-caducitat
    if (current_user_can('administrator') && isset($params['dies-caducitat'])) {
        $dies_caducitat = is_numeric($params['dies-caducitat']) ? intval($params['dies-caducitat']) : 365;
        update_post_meta($post_id, 'dies-caducitat', $dies_caducitat);
    } else if (!get_post_meta($post_id, 'dies-caducitat', true)) {
        // Si no existe el valor, establecer el valor por defecto
        update_post_meta($post_id, 'dies-caducitat', 365);
    }

    // Procesar imagen destacada
    if (isset($params['imatge-destacada-id'])) {
        $image_id = intval($params['imatge-destacada-id']);
        if ($image_id > 0) {
            // Verificar que la imagen existe y es una imagen válida
            $image = get_post($image_id);
            if ($image && wp_attachment_is_image($image_id)) {
                set_post_thumbnail($post_id, $image_id);
                error_log("Imagen destacada establecida: {$image_id}");
            } else {
                error_log("Error: ID de imagen no válido o no existe: {$image_id}");
                throw new Exception("ID de imagen destacada no válido");
            }
        }
    }

    // Procesar galería de imágenes
    if (isset($params['galeria-vehicle'])) {
        $gallery_ids = [];
        $gallery = $params['galeria-vehicle'];
        
        if (!is_array($gallery)) {
            $gallery = [$gallery];
        }

        foreach ($gallery as $image) {
            if (is_string($image)) {
                if (filter_var($image, FILTER_VALIDATE_URL)) {
                    // Es una URL
                    $upload = media_sideload_image($image, $post_id, '', 'id');
                    if (!is_wp_error($upload)) {
                        $gallery_ids[] = $upload;
                    }
                } else if (strpos($image, 'data:image') === 0) {
                    // Es base64
                    $upload = upload_base64_image($image, $post_id);
                    if (!is_wp_error($upload)) {
                        $gallery_ids[] = $upload;
                    }
                }
            } else if (is_numeric($image)) {
                // Es un ID de imagen existente
                $gallery_ids[] = $image;
            }
        }

        if (!empty($gallery_ids)) {
            // Convertir array de IDs a string separado por comas y guardarlo como array con un elemento
            $gallery_string = implode(',', $gallery_ids);
            delete_post_meta($post_id, 'ad_gallery');
            add_post_meta($post_id, 'ad_gallery', $gallery_string);
            error_log("Guardando galería: " . print_r(array($gallery_string), true));
        }
    }

    if (!empty($errors)) {
        throw new Exception("Errores de validación:\n" . implode("\n", $errors));
    }
}

function update_singlecar($request)
{
    try {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $params = $request->get_params();
        $post_id = isset($params['id']) ? $params['id'] : 0;

        // Verificar que el post existe y es del tipo correcto
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'singlecar') {
            throw new Exception('Vehículo no encontrado');
        }

        // Verificar propiedad
        if (!verify_post_ownership($post_id)) {
            throw new Exception('No tienes permiso para editar este vehículo');
        }

        // Procesar imagen destacada si se proporciona
        if (isset($params['imatge-destacada-id'])) {
            $featured_image = $params['imatge-destacada-id'];
            
            // Si es una URL o base64
            if (is_string($featured_image)) {
                if (filter_var($featured_image, FILTER_VALIDATE_URL)) {
                    // Es una URL
                    $upload = media_sideload_image($featured_image, $post_id, '', 'id');
                    if (!is_wp_error($upload)) {
                        set_post_thumbnail($post_id, $upload);
                    } else {
                        throw new Exception('Error al procesar la imagen destacada: ' . $upload->get_error_message());
                    }
                } else if (strpos($featured_image, 'data:image') === 0) {
                    // Es base64
                    $upload = upload_base64_image($featured_image, $post_id);
                    if (!is_wp_error($upload)) {
                        set_post_thumbnail($post_id, $upload);
                    } else {
                        throw new Exception('Error al procesar la imagen destacada: ' . $upload->get_error_message());
                    }
                }
            } else if (is_numeric($featured_image)) {
                // Es un ID de imagen existente
                set_post_thumbnail($post_id, $featured_image);
            }
        }

        // Procesar galería si se proporciona
        if (isset($params['galeria-vehicle'])) {
            $gallery_images = [];
            $gallery = $params['galeria-vehicle'];
            
            if (!is_array($gallery)) {
                $gallery = [$gallery];
            }

            foreach ($gallery as $image) {
                if (is_string($image)) {
                    if (filter_var($image, FILTER_VALIDATE_URL)) {
                        // Es una URL
                        $upload = media_sideload_image($image, $post_id, '', 'id');
                        if (!is_wp_error($upload)) {
                            $gallery_images[] = $upload;
                        }
                    } else if (strpos($image, 'data:image') === 0) {
                        // Es base64
                        $upload = upload_base64_image($image, $post_id);
                        if (!is_wp_error($upload)) {
                            $gallery_images[] = $upload;
                        }
                    }
                } else if (is_numeric($image)) {
                    // Es un ID de imagen existente
                    $gallery_images[] = $image;
                }
            }

            if (!empty($gallery_images)) {
                // Convertir array de IDs a string separado por comas y guardarlo como array con un elemento
                $gallery_string = implode(',', $gallery_images);
                delete_post_meta($post_id, 'ad_gallery');
                add_post_meta($post_id, 'ad_gallery', $gallery_string);
                error_log("Guardando galería: " . print_r(array($gallery_string), true));
            }
        }

        // Transformar el campo carrosseria a segment si existe
        if (isset($params['carrosseria'])) {
            $params['segment'] = $params['carrosseria'];
            unset($params['carrosseria']);
        }

        // Validar taxonomías si se están actualizando
        $taxonomy_fields = Vehicle_Fields::get_taxonomy_fields();
        $allowed_values = Vehicle_Fields::get_allowed_taxonomy_values();

        foreach ($taxonomy_fields as $field => $taxonomy) {
            if (isset($params[$field])) {
                // Validar que el valor esté permitido
                if (isset($allowed_values[$field])) {
                    if (!in_array($params[$field], $allowed_values[$field])) {
                        throw new Exception(sprintf(
                            'El valor "%s" no es válido para %s. Valores permitidos: %s',
                            $params[$field],
                            $field,
                            implode(', ', $allowed_values[$field])
                        ));
                    }
                }

                // Verificar que el término existe
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

        // Validar marca y modelo si se proporcionan
        if (isset($params['marques-cotxe'])) {
            $marca = get_term_by('slug', $params['marques-cotxe'], 'marques-coches');
            if (!$marca) {
                $marcas_disponibles = get_terms([
                    'taxonomy' => 'marques-coches',
                    'parent' => 0,
                    'hide_empty' => false,
                    'fields' => 'slugs'
                ]);
                throw new Exception(sprintf(
                    'La marca "%s" no existe. Marcas disponibles: %s',
                    $params['marques-cotxe'],
                    implode(', ', $marcas_disponibles)
                ));
            }

            // Si hay modelo, verificar que pertenece a la marca
            if (isset($params['models-cotxe'])) {
                $modelo = get_term_by('slug', $params['models-cotxe'], 'marques-coches');
                if (!$modelo || $modelo->parent != $marca->term_id) {
                    $modelos_disponibles = get_terms([
                        'taxonomy' => 'marques-coches',
                        'parent' => $marca->term_id,
                        'hide_empty' => false,
                        'fields' => 'slugs'
                    ]);
                    throw new Exception(sprintf(
                        'El modelo "%s" no existe o no pertenece a la marca %s. Modelos disponibles para esta marca: %s',
                        $params['models-cotxe'],
                        $marca->name,
                        implode(', ', $modelos_disponibles)
                    ));
                }
            }
        }

        // Validar los campos que se están actualizando
        validate_all_fields($params);

        // Actualizar datos básicos del post solo si se proporcionan
        $post_data = ['ID' => $post_id];
        
        if (isset($params['titol-anunci'])) {
            $post_data['post_title'] = wp_strip_all_tags($params['titol-anunci']);
        }
        if (isset($params['descripcio-anunci'])) {
            $post_data['post_content'] = $params['descripcio-anunci'];
        }

        if (count($post_data) > 1) { // Si hay más campos además del ID
            $result = wp_update_post($post_data);
            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }
        }

        // Actualizar taxonomías que se proporcionan
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
            $terms = [$marca->term_id];
            if (isset($params['models-cotxe'])) {
                $terms[] = $modelo->term_id;
            }
            $result = wp_set_object_terms($post_id, $terms, 'marques-coches');
            if (is_wp_error($result)) {
                throw new Exception('Error al actualizar marca y modelo: ' . $result->get_error_message());
            }
        }

        // Procesar y guardar campos meta e imágenes
        process_and_save_meta_fields($post_id, $params);

        $wpdb->query('COMMIT');

        // Obtener los datos actualizados del post
        $post = get_post($post_id);
        $response = [
            'status' => 'success',
            'message' => 'Vehículo actualizado exitosamente',
            'post_id' => $post_id,
            'titol-anunci' => $post->post_title,
            'descripcio-anunci' => $post->post_content,
            'status' => $post->post_status
        ];

        // Agregar campos de taxonomía a la respuesta
        foreach ($taxonomy_fields as $field => $taxonomy) {
            $terms = wp_get_object_terms($post_id, $taxonomy);
            if (!is_wp_error($terms) && !empty($terms)) {
                $response[$field] = $terms[0]->slug;
            }
        }

        // Agregar marca y modelo a la respuesta
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

        return new WP_REST_Response($response, 200);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return new WP_REST_Response([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 400);
    }
}

function delete_singlecar($request)
{
    try {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $post_id = $request['id'];

        // Verificación adicional de propiedad antes de eliminar
        if (!verify_post_ownership($post_id)) {
            throw new Exception('No tienes permiso para eliminar este vehículo');
        }

        // Verificar que el post existe y es del tipo correcto
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'singlecar') {
            throw new Exception('Vehículo no encontrado');
        }

        // Logging antes de eliminar
        Vehicle_API_Logger::get_instance()->log_action(
            $post_id,
            'delete',
            array(
                'title' => get_the_title($post_id),
                'user_id' => get_current_user_id()
            )
        );

        // Mover a la papelera en lugar de eliminar permanentemente
        $result = wp_trash_post($post_id);

        if (!$result) {
            throw new Exception('Error al mover el vehículo a la papelera');
        }

        $wpdb->query('COMMIT');

        return new WP_REST_Response([
            'status' => 'success',
            'message' => 'Vehículo movido a la papelera exitosamente',
            'post_id' => $post_id
        ], 200);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return new WP_REST_Response([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 403); // Cambiado a 403 para indicar acceso prohibido
    }
}

function get_vehicle_details($request)
{
    $vehicle_id = $request['id'];

    // Verificar propiedad
    if (!verify_post_ownership($vehicle_id)) {
        return new WP_Error(
            'forbidden_access',
            'No tienes permiso para ver este vehículo',
            ['status' => 403]
        );
    }

    $post = get_post($vehicle_id);

    if (!$post || $post->post_type !== 'singlecar') {
        return new WP_Error('no_vehicle', 'Vehicle not found', ['status' => 404]);
    }

    $meta = get_post_meta($vehicle_id);
    $terms = wp_get_post_terms($vehicle_id, 'types-of-transport', ['fields' => 'all']);
    $marques_terms = wp_get_post_terms($vehicle_id, 'marques-coches', ['fields' => 'all']);

    function get_glossary_label($value)
    {
        $glossary = [
            "39" => "Estat Vehicle Part",
            "40" => "Estat del vehícle pro",
            "41" => "Carrosseria",
            "42" => "Tipus Moto",
            "43" => "Carrosseria Caravanes",
            "44" => "Carrosseria comercials",
            "45" => "Carrosseria camions",
            "46" => "Tipus de canvi coche",
            "47" => "Autonomia",
            "48" => "Bateria",
            "49" => "Connectors-electric",
            "50" => "Cables recàrrega",
            "51" => "Color Exterior",
            "52" => "Tapisseria",
            "53" => "Color Tapisseria",
            "54" => "Extres Coche",
            "55" => "Extres Moto",
            "56" => "Extres Autocaravana",
            "57" => "Extres Habitacle",
            "58" => "Tipus Emissions",
            "59" => "Tracció",
            "60" => "Roda recanvi",
            "61" => "Velocitat de recàrrega",
            "62" => "Tipus canvi moto",
            "63" => "Tipus canvi elèctric",
            "euro1" => "Euro 1",
            "euro2" => "Euro 2",
            "euro3" => "Euro 3",
            "euro4" => "Euro 4",
            "euro5" => "Euro 5",
            "euro6" => "Euro 6",
            "t_davant" => "Tracción Delantera",
            "t_darrera" => "Tracción Trasera",
            "t_4x4" => "Tracción 4x4"
        ];
        return $glossary[$value] ?? $value;
    }

    function get_glossary_value($field, $value)
    {
        if (!function_exists('jet_engine')) {
            return $value;
        }

        $jet_engine = jet_engine();
        if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
            return $value;
        }

        // Obtener el ID del glosario según el campo
        $glossary_id = '';
        switch ($field) {
            case 'traccio':
                $glossary_id = '59'; // ID del glosario de tracción
                break;
            case 'emissions-vehicle':
                $glossary_id = '58'; // ID del glosario de emisiones
                break;
            case 'roda-recanvi':
                $glossary_id = '60'; // ID del glosario de rueda de recambio
                break;
        }

        if (!$glossary_id) {
            return $value;
        }

        // Obtener las opciones del glosario
        $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

        // Devolver la etiqueta si existe, si no devolver el valor original
        return isset($options[$value]) ? $options[$value] : $value;
    }

    $response = [
        'id' => $vehicle_id,
        'titol-anunci' => get_the_title($vehicle_id),
        'descripcio-anunci' => $post->post_content,
        'tipus-de-vehicle' => get_glossary_label($terms[0] ?? null),
        'marques-cotxe' => get_glossary_label($marques_terms[1] ?? null),
        'models-cotxe' => get_glossary_label($marques_terms[0] ?? null),
        'preu' => $meta['preu'][0] ?? null,
        'emissions-vehicle' => get_glossary_value('emissions-vehicle', $meta['emissions-vehicle'][0] ?? null),
        'traccio' => get_glossary_value('traccio', $meta['traccio'][0] ?? null),
        'roda-recanvi' => get_glossary_value('roda-recanvi', $meta['roda-recanvi'][0] ?? null)
    ];

    foreach ($meta as $key => $value) {
        if (!in_array($key, ['emissions-vehicle', 'traccio', 'roda-recanvi'])) {
            $response[$key] = $value[0];
        }
    }

    // Añadir información de imágenes a la respuesta
    $response['imatge-destacada-id'] = get_post_thumbnail_id($vehicle_id);
    $response['imatge-destacada-url'] = get_the_post_thumbnail_url($vehicle_id, 'full');

    // Obtener galería
    $gallery_ids = get_post_meta($vehicle_id, 'ad_gallery', true);
    if (!empty($gallery_ids)) {
        $gallery_urls = [];
        // Si es una cadena, convertir a array
        if (is_string($gallery_ids)) {
            $gallery_ids = explode(',', $gallery_ids);
        }
        foreach ($gallery_ids as $gallery_id) {
            $url = wp_get_attachment_url(trim($gallery_id));
            if ($url) {
                $gallery_urls[] = $url;
            }
        }
        $response['galeria-vehicle-urls'] = $gallery_urls;
    }

    // Ocultar dies-caducitat a usuarios no administradores
    if (!current_user_can('administrator')) {
        unset($response['dies-caducitat']);
    }

    // Corrección del manejo de metadatos
    $meta = get_post_meta($vehicle_id);
    foreach ($meta as $key => $value) {
        if (!Vehicle_Fields::should_exclude_field($key)) {
            // Asegurarnos de que el valor no esté en un array
            $meta_value = is_array($value) ? $value[0] : $value;
            $response[$key] = get_field_label($key, $meta_value);
        }
    }

    return new WP_REST_Response($response, 200);
}

function debug_vehicle_fields()
{
    $post_type = 'vehicle'; // o el nombre que uses para el post type
    $meta_fields = jet_engine()->meta_boxes->get_meta_fields_for_object($post_type);

    return new WP_REST_Response([
        'fields' => $meta_fields
    ], 200);
}

// Función helper para verificar propiedad del post
function verify_post_ownership($post_id)
{
    if (!is_user_logged_in()) {
        return false;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'singlecar') {
        return false;
    }

    // Permitir acceso si el usuario es el autor o es administrador
    return $post->post_author == get_current_user_id() || current_user_can('administrator');
}

function upload_base64_image($base64_string, $post_id = 0) {
    // Extraer la información del base64
    $data = explode(',', $base64_string);
    if (count($data) !== 2) {
        return new WP_Error('invalid_base64', 'Invalid base64 string format');
    }

    // Obtener el tipo MIME y los datos
    preg_match('/data:(.*?);/', $data[0], $matches);
    $mime_type = $matches[1];
    $base64_data = $data[1];

    // Decodificar los datos
    $decoded_data = base64_decode($base64_data);
    if (!$decoded_data) {
        return new WP_Error('decode_failed', 'Failed to decode base64 data');
    }

    // Crear un nombre de archivo temporal
    $upload_dir = wp_upload_dir();
    $filename = wp_unique_filename($upload_dir['path'], 'image.jpg');
    $file_path = $upload_dir['path'] . '/' . $filename;

    // Guardar archivo
    if (!file_put_contents($file_path, $decoded_data)) {
        return new WP_Error('save_failed', 'Failed to save image file');
    }

    // Preparar los datos del archivo para WordPress
    $filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    // Insertar el archivo en la biblioteca de medios
    $attach_id = wp_insert_attachment($attachment, $file_path);
    if (is_wp_error($attach_id)) {
        unlink($file_path);
        return $attach_id;
    }

    // Generar los metadatos y miniaturas
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}
