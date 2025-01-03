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
        error_log('Filtro aplicado por post_name: ' . $args['name']);
    }

    // Filtro por user_id
    if (isset($params['user_id'])) {
        $args['author'] = intval($params['user_id']);
        error_log('Filtro aplicado por user_id: ' . $args['author']);
    }

    // Filtro por el usuario actual
    $current_user_id = get_current_user_id();
    $args['author'] = $current_user_id;

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

    // Agregar líneas de depuración para la consulta
    error_log('Argumentos de consulta: ' . print_r($args, true));

    $query = new WP_Query($args);
    $vehicles = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $singlecar_id = get_the_ID();

            $vehicles[] = [
                'id' => $singlecar_id,
                'titol' => get_the_title(),
                'tipus' => wp_get_post_terms($singlecar_id, 'types-of-transport', ['fields' => 'names'])[0],
                'preu' => get_post_meta($singlecar_id, 'preu', true)
            ];
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

        // Verificar campos requeridos
        $required_fields = [
            'titol-anunci',
            'descripcio-anunci',
            'marques-cotxe',
            'models-cotxe',
            'preu',
            'tipus-vehicle',
            'estat-vehicle',
            'tipus-combustible',
            'tipus-canvi-cotxe',
            'quilometratge'
        ];

        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                throw new Exception(sprintf('El campo %s es requerido', $field));
            }
        }

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
            throw new Exception('La marca especificada no existe');
        }

        $modelo = get_term_by('slug', $params['models-cotxe'], 'marques-coches');
        if (!$modelo || $modelo->parent != $marca->term_id) {
            throw new Exception('El modelo especificado no existe o no pertenece a la marca indicada');
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
                                $image_parts = explode(";base64,", $image);
                                $image_type_aux = explode("image/", $image_parts[0]);
                                $image_type = $image_type_aux[1];
                                $image_base64 = base64_decode($image_parts[1]);
                                $filename = uniqid() . '.' . $image_type;
                                $file_path = $upload_dir['path'] . '/' . $filename;

                                file_put_contents($file_path, $image_base64);

                                $wp_filetype = wp_check_filetype($filename, null);
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title' => sanitize_file_name($filename),
                                    'post_content' => '',
                                    'post_status' => 'inherit'
                                );

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
        $post_data = array(
            'post_title' => wp_strip_all_tags($params['titol-anunci']),
            'post_content' => $params['descripcio-anunci'],
            'post_status' => 'publish',
            'post_type' => 'singlecar'
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
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
        process_and_save_meta_fields($post_id, $params);

        // Después de crear el post, asignar las imágenes procesadas
        if (!empty($processed_images)) {
            foreach ($processed_images as $field => $value) {
                if ($field === 'imatge-destacada-id') {
                    set_post_thumbnail($post_id, $value);
                } else if ($field === 'galeria-vehicle' && is_array($value)) {
                    update_post_meta($post_id, 'ad_gallery', $value);
                }
            }
        }

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
    if (!function_exists('jet_engine')) {
        throw new Exception("JetEngine no está disponible");
    }

    $glossary_mapping = [
        'traccio' => '59',             // Tracció
        'roda-recanvi' => '60',        // Roda recanvi
        'segment' => '41',             // Carrosseria
        'carrosseria-cotxe' => '41',   // También usar el glosario 41 para carrosseria-cotxe
        'color-vehicle' => '51',       // Color Exterior
        'tipus-canvi' => '46',         // Tipus de canvi coche
        'tipus-tapisseria' => '52',    // Tapisseria
        'color-tapisseria' => '53',    // Color Tapisseria
        'emissions-vehicle' => '58'    // Tipus Emissions
    ];

    // Si el campo es carrosseria-cotxe, usar segment para la validación
    $field_to_validate = ($field === 'carrosseria-cotxe') ? 'segment' : $field;

    if (!isset($glossary_mapping[$field_to_validate])) {
        throw new Exception("Campo de glosario no reconocido: {$field}");
    }

    $glossary_id = $glossary_mapping[$field_to_validate];

    $jet_engine = jet_engine();
    $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

    if (!isset($options[$value])) {
        $valid_values = implode(', ', array_keys($options));
        throw new Exception("Valor no válido para {$field}. Valores permitidos: {$valid_values}");
    }

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
        'tipus-canvi' => 'glossary',
        'tipus-tapisseria' => 'glossary',
        'color-tapisseria' => 'glossary',
        'emissions-vehicle' => 'glossary',
        // Removidos los campos que son taxonomías
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
        'tipus-canvi',
        'tipus-tapisseria',
        'color-tapisseria',
        'emissions-vehicle'
        // Removidos los campos que son taxonomías
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

    // Mapeo específico para campos que necesitan transformación
    $field_mapping = array(
        'any-fabricacio' => 'any',
        'velocitat-max-cotxe' => 'velocitat-maxima',  // Corregido de velocitat-maxima-cotxe
        'numero-maleters-cotxe' => 'maleters',
        'capacitat-maleters-cotxe' => 'capacitat-total',
        'acceleracio-0-100-cotxe' => 'acceleracio-0-100',
        'numero-motors' => 'n-motors',
        'carrosseria-cotxe' => 'segment',
        'traccio' => 'traccio',
        'roda-recanvi' => 'roda-recanvi',
        'anunci-destacat' => 'is-vip'  // Añadido el mapeo de anunci-destacat a is-vip
    );

    // Procesar los campos mapeados primero
    foreach ($field_mapping as $api_field => $db_field) {
        if (isset($params[$api_field])) {
            $value = $params[$api_field];

            // Manejo especial para campos booleanos mapeados
            if ($db_field === 'is-vip') {
                $value = strtolower(trim($value));
                $true_values = ['true', 'si', '1', 'yes', 'on'];
                $false_values = ['false', 'no', '0', 'off'];

                if (in_array($value, $true_values, true)) {
                    $value = 'true';
                    // Cuando is-vip es true, actualizamos data-vip con el timestamp actual
                    update_post_meta($post_id, 'data-vip', current_time('timestamp'));
                } elseif (in_array($value, $false_values, true)) {
                    $value = 'false';
                    // Cuando is-vip es false, limpiamos data-vip
                    update_post_meta($post_id, 'data-vip', '');
                } else {
                    $value = 'false';
                    // Por defecto también limpiamos data-vip
                    update_post_meta($post_id, 'data-vip', '');
                }
            }

            error_log("Guardando campo mapeado {$api_field} como {$db_field} con valor: {$value}");
            update_post_meta($post_id, $db_field, $value);
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

                error_log("Procesando campo: {$field} (DB field: {$db_field}) con valor: {$params[$field]} de tipo: {$type}");

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

                        // Generar metadatos para el adjunto
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
        'carrosseria-cotxe' => 'carrosseria-cotxe', // Cambiado para usar el mismo nombre y validar contra glosario 41
        'traccio' => 'traccio',
        'roda-recanvi' => 'roda-recanvi'
    );

    // Procesar carrosseria-cotxe especialmente ya que su valor va al glosario segment
    if (isset($params['carrosseria-cotxe'])) {
        try {
            // Validar el valor contra el glosario segment
            validate_glossary_field('carrosseria-cotxe', $params['carrosseria-cotxe']);
            // Si es válido, guardarlo en el meta segment
            update_post_meta($post_id, 'segment', $params['carrosseria-cotxe']);
            error_log("Guardando carrosseria-cotxe en segment con valor: " . $params['carrosseria-cotxe']);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    foreach ($glossary_fields as $api_field => $db_field) {
        if (isset($params[$api_field])) {
            try {
                $value = $params[$api_field];
                validate_glossary_field($db_field, $value); // Esto validará contra el glosario correcto
                error_log("Guardando campo de glosario {$api_field} como {$db_field} con valor: {$value}");
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
        $gallery_ids = is_array($params['galeria-vehicle'])
            ? $params['galeria-vehicle']
            : explode(',', $params['galeria-vehicle']);

        $valid_gallery_ids = [];

        foreach ($gallery_ids as $img_id) {
            $img_id = intval($img_id);
            if ($img_id > 0) {
                // Verificar que cada imagen existe y es válida
                if (wp_attachment_is_image($img_id)) {
                    $valid_gallery_ids[] = $img_id;
                } else {
                    error_log("Error: ID de imagen de galería no válido: {$img_id}");
                }
            }
        }

        if (!empty($valid_gallery_ids)) {
            update_post_meta($post_id, 'ad_gallery', $valid_gallery_ids);
            error_log("Galería de imágenes guardada: " . implode(', ', $valid_gallery_ids));
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

        // Transformar el campo carrosseria a segment si existe
        if (isset($params['carrosseria-cotxe'])) {
            $params['segment'] = $params['carrosseria-cotxe'];
            unset($params['carrosseria-cotxe']);
        }

        // Validar taxonomías antes de actualizar
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

        // Si se proporcionan marca y modelo, verificar que son válidos
        if (isset($params['marques-cotxe'])) {
            $marca = get_term_by('slug', $params['marques-cotxe'], 'marques-coches');
            if (!$marca) {
                throw new Exception('La marca especificada no existe');
            }

            // Si hay modelo, verificar que pertenece a la marca
            if (isset($params['models-cotxe'])) {
                $modelo = get_term_by('slug', $params['models-cotxe'], 'marques-coches');
                if (!$modelo || $modelo->parent != $marca->term_id) {
                    throw new Exception('El modelo especificado no existe o no pertenece a la marca indicada');
                }
            }
        }

        // Validar todos los campos antes de actualizar
        validate_all_fields($params);

        // Actualizar datos básicos del post si se proporcionan
        $post_data = array('ID' => $post_id);

        if (isset($params['titol-anunci'])) {
            $post_data['post_title'] = wp_strip_all_tags($params['titol-anunci']);
        }
        if (isset($params['descripcio-anunci'])) {
            $post_data['post_content'] = $params['descripcio-anunci'];
        }
        if (!empty($post_data)) {
            $result = wp_update_post($post_data);
            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }
        }

        // Logging de actualización
        Vehicle_API_Logger::get_instance()->log_action(
            $post_id,
            'update',
            array(
                'title' => $params['titol-anunci'] ?? get_the_title($post_id),
                'user_id' => get_current_user_id()
            )
        );

        // Actualizar taxonomías
        foreach ($taxonomy_fields as $field => $taxonomy) {
            if (isset($params[$field])) {
                $term = get_term_by('slug', $params[$field], $taxonomy);
                $result = wp_set_object_terms($post_id, $term->term_id, $taxonomy);
                if (is_wp_error($result)) {
                    throw new Exception(sprintf('Error al actualizar el término %s', $params[$field]));
                }
            }
        }

        // Actualizar marca y modelo si se proporcionan
        if (isset($params['marques-cotxe'])) {
            $terms = array($marca->term_id);
            if (isset($params['models-cotxe'])) {
                $terms[] = $modelo->term_id;
            }
            $result = wp_set_object_terms($post_id, $terms, 'marques-coches');
            if (is_wp_error($result)) {
                throw new Exception('Error al actualizar marca y modelo');
            }
        }

        // Procesar y guardar campos meta
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
        return new WP_REST_Response(array(
            'status' => 'error',
            'message' => $e->getMessage()
        ), 400);
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
    $terms = wp_get_post_terms($vehicle_id, 'types-of-transport', ['fields' => 'names']);
    $marques_terms = wp_get_post_terms($vehicle_id, 'marques-coches', ['fields' => 'names']);

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
            // Añadir más casos según sea necesario
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
        foreach ((array) $gallery_ids as $gallery_id) {
            $gallery_urls[$gallery_id] = wp_get_attachment_url($gallery_id);
        }
        $response['galeria-vehicle'] = $gallery_ids;
        $response['galeria-vehicle-urls'] = $gallery_urls;
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
