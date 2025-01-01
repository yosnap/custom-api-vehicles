<?php
// Registrar la ruta REST API para /singlecar
add_action('rest_api_init', function () {
    register_rest_route('api-motor/v1', '/vehicles', [
        [
            'methods' => 'GET',
            'callback' => 'get_singlecar',
            'permission_callback' => '__return_true',
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
        'methods' => 'GET',
        'callback' => 'get_vehicle_details',
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

    // Configuración de paginación
    $paged = isset($params['page']) ? intval($params['page']) : 1;
    $posts_per_page = isset($params['per_page']) ? intval($params['per_page']) : -1;

    // Argumentos base de la consulta
    $args = [
        'post_type' => 'singlecar',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
    ];

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
    try {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $params = $request->get_params();

        // Verificar campos requeridos
        $required_fields = ['title', 'content', 'marca', 'modelo'];
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
        $marca = get_term_by('slug', $params['marca'], 'marques-coches');
        if (!$marca) {
            throw new Exception('La marca especificada no existe');
        }

        $modelo = get_term_by('slug', $params['modelo'], 'marques-coches');
        if (!$modelo || $modelo->parent != $marca->term_id) {
            throw new Exception('El modelo especificado no existe o no pertenece a la marca indicada');
        }

        // Validar todos los campos antes de crear el post
        validate_all_fields($params);

        // Crear el post
        $post_data = array(
            'post_title' => wp_strip_all_tags($params['title']),
            'post_content' => $params['content'],
            'post_status' => 'publish',
            'post_type' => 'singlecar'
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

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

        $wpdb->query('COMMIT');

        // Obtener los datos actualizados del post
        $post = get_post($post_id);
        $response = [
            'status' => 'success',
            'message' => 'Vehículo creado exitosamente',
            'post_id' => $post_id,
            'title' => $post->post_title,
            'content' => $post->post_content,
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

function validate_all_fields($params)
{
    // Lista de campos a validar con sus tipos
    $fields_to_validate = [
        'venedor' => 'glossary',
        'traccio' => 'glossary',
        'roda-recanvi' => 'glossary',
        'segment' => 'glossary',
        'color-vehicle' => 'glossary',
        'tipus-vehicle' => 'glossary',
        'tipus-combustible' => 'glossary',
        'tipus-canvi' => 'glossary',
        'tipus-propulsor' => 'glossary',
        'estat-vehicle' => 'glossary',
        'tipus-tapisseria' => 'glossary',
        'color-tapisseria' => 'glossary',
        'emissions-vehicle' => 'glossary',
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

    // Procesar campos normales
    foreach ($meta_fields as $field => $type) {
        if (isset($params[$field]) && !Vehicle_Fields::should_exclude_field($field)) {
            try {
                error_log("Procesando campo: {$field} con valor: {$params[$field]} de tipo: {$type}");

                // Procesar campos numéricos
                if ($type === 'number') {
                    $value = floatval($params[$field]);
                    $result = update_post_meta($post_id, $field, $value);
                    error_log("Resultado de guardar {$field}: " . ($result ? 'éxito' : 'fallo'));
                }
                // Procesar campos switch/boolean
                else if ($type === 'switch') {
                    $value = filter_var($params[$field], FILTER_VALIDATE_BOOLEAN);
                    update_post_meta($post_id, $field, $value ? 'true' : 'false');
                }
                // Procesar campos select/radio
                else if (in_array($type, ['select', 'radio'])) {
                    update_post_meta($post_id, $field, sanitize_text_field($params[$field]));
                }
                // Manejo especial para extres-cotxe
                else if ($field === 'extres-cotxe') {
                    $processed_value = (array) $params[$field];

                    // Eliminar valores antiguos
                    delete_post_meta($post_id, $field);

                    // Crear la estructura que espera JetEngine con tres índices
                    $jet_engine_format = [
                        0 => array_combine(range(0, count($processed_value) - 1), $processed_value),
                        1 => array_combine(range(0, count($processed_value) - 1), $processed_value),
                        2 => array_combine(range(0, count($processed_value) - 1), $processed_value)
                    ];

                    // Guardar cada array como una entrada separada
                    foreach ($jet_engine_format as $value_array) {
                        add_post_meta($post_id, $field, $value_array);
                    }
                }
                // Para otros arrays
                else if (is_array($params[$field])) {
                    $processed_value = array_filter($params[$field], function ($value) {
                        return $value !== null && $value !== '';
                    });

                    if (!empty($processed_value)) {
                        delete_post_meta($post_id, $field);
                        foreach ($processed_value as $value) {
                            add_post_meta($post_id, $field, $value);
                        }
                    } else {
                        delete_post_meta($post_id, $field);
                    }
                } else {
                    // Para valores simples
                    if ($params[$field] !== null) {
                        update_post_meta($post_id, $field, $params[$field]);
                    } else {
                        delete_post_meta($post_id, $field);
                    }
                }
            } catch (Exception $e) {
                error_log("Error al procesar campo {$field}: " . $e->getMessage());
                $errors[] = $e->getMessage();
            }
        }
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

        // Transformar el campo carrosseria a segment si existe
        if (isset($params['carrosseria'])) {
            $params['segment'] = $params['carrosseria'];
            unset($params['carrosseria']);
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
        if (isset($params['marca'])) {
            $marca = get_term_by('slug', $params['marca'], 'marques-coches');
            if (!$marca) {
                throw new Exception('La marca especificada no existe');
            }

            // Si hay modelo, verificar que pertenece a la marca
            if (isset($params['modelo'])) {
                $modelo = get_term_by('slug', $params['modelo'], 'marques-coches');
                if (!$modelo || $modelo->parent != $marca->term_id) {
                    throw new Exception('El modelo especificado no existe o no pertenece a la marca indicada');
                }
            }
        }

        // Validar todos los campos antes de actualizar
        validate_all_fields($params);

        // Actualizar datos básicos del post si se proporcionan
        $post_data = array('ID' => $post_id);

        if (isset($params['title'])) {
            $post_data['post_title'] = wp_strip_all_tags($params['title']);
        }
        if (isset($params['content'])) {
            $post_data['post_content'] = $params['content'];
        }
        if (!empty($post_data)) {
            $result = wp_update_post($post_data);
            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }
        }

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
        if (isset($params['marca'])) {
            $terms = array($marca->term_id);
            if (isset($params['modelo'])) {
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
            'title' => $post->post_title,
            'content' => $post->post_content,
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

        // Verificar que el post existe y es del tipo correcto
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'singlecar') {
            throw new Exception('Vehículo no encontrado');
        }

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
        ], 400);
    }
}

function get_vehicle_details($request)
{
    $vehicle_id = $request['id'];
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
        'titol' => get_the_title($vehicle_id),
        'content' => $post->post_content,
        'tipus' => get_glossary_label($terms[0] ?? null),
        'marques-coches' => get_glossary_label($marques_terms[0] ?? null),
        'model' => get_glossary_label($marques_terms[1] ?? null),
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
