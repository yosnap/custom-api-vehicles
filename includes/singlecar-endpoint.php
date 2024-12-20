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
                    'field'    => 'slug',
                    'terms'    => $term_slugs,
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
                'key'   => $key,
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
    $singlecars = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $singlecar_id = get_the_ID();
            $meta = get_post_meta($singlecar_id);

            // Obtener metadatos del usuario
            $user_id = get_post_field('post_author', $singlecar_id);
            $user_meta = get_user_meta($user_id);

            // Si no hay metadatos de usuario, crear un array vacío
            if (empty($user_meta)) {
                $user_meta = [];
            }

            // Eliminar campos específicos de los metadatos del usuario
            $fields_to_remove = [
                'rich_editing',
                'syntax_highlighting',
                'comment_shortcuts',
                'use_ssl',
                'show_admin_bar_front',
                'hgg_capabilities',
                'hgg_user_level',
                '_application_passwords',
                'jwt_auth_pass',
                'session_tokens',
                'hgg_dashboard_quick_press_last_post_id',
                'community-events-location',
                'elementor_admin_notices',
                'edit_singlecar_per_page',
                'hgg_user-settings',
                'sendPassword',
                'smack_uci_import',
                'admin_color',
                '_edit_lock',
                'hgg_user-settings-time',
                'current_sns_tab',
                'closedpostboxes_singlecar',
                'metaboxhidden_singlecar',
                'hgg_persisted_preferences',
                'elementor_introduction',
                'meta-box-order_singlecar',
                'screen_layout_singlecar',
                '_capability-manager-enhanced_wp_reviews_dismissed_triggers',
                '_capability-manager-enhanced_wp_reviews_last_dismissed',
                'thumbpress_notice_display_count_combined'
            ];

            foreach ($fields_to_remove as $field) {
                if (isset($user_meta[$field])) {
                    unset($user_meta[$field]);
                }
            }

            // Reestructurar metadatos del autor
            $user_data = [
                'ID' => $user_id,
                'display_name' => get_the_author_meta('display_name', $user_id),
                'nickname' => get_the_author_meta('nickname', $user_id),
                'first_name' => get_the_author_meta('first_name', $user_id),
                'last_name' => get_the_author_meta('last_name', $user_id),
            ];

            // Extraer metadatos al mismo nivel de "meta"
            if (is_array($user_meta)) {
                foreach ($user_meta as $key => $value) {
                    if (!in_array($key, $fields_to_remove)) {
                        $user_data[$key] = maybe_unserialize($value[0]);
                    }
                }
            }

            // Preparar datos del post
            $singlecar = [
                'id' => $singlecar_id,
                'slug' => get_post_field('post_name', $singlecar_id), // Añadir el slug aquí
                'title' => get_the_title(),
                'content' => get_the_content(),
                'author' => $user_data,  // Añadir los datos del autor al resultado
                'post_date' => get_the_date('Y-m-d H:i:s'), // Añadir la fecha de creación del post
            ];

            // Convertir '_thumbnail_id' a URL y añadir al array principal
            if (isset($meta['_thumbnail_id'][0])) {
                $singlecar['thumbnail_id'] = wp_get_attachment_url($meta['_thumbnail_id'][0]); // Cambiar el nombre a 'thumbnail_id'
                unset($meta['_thumbnail_id']);
            }


            // Convertir 'ad_gallery' a URLs y añadir al array principal
            if (isset($meta['ad_gallery'][0])) {
                $ad_gallery_ids = explode(',', $meta['ad_gallery'][0]);
                $ad_gallery_urls = [];
                foreach ($ad_gallery_ids as $id) {
                    $ad_gallery_urls[] = wp_get_attachment_url($id);
                }
                $singlecar['ad_gallery'] = $ad_gallery_urls;
                unset($meta['ad_gallery']);
            }

            // Convertir 'galeria-professionals' a URLs y añadir al array principal
            if (isset($meta['galeria-professionals'][0])) {
                // Dividir el string de IDs en un array
                $galeria_ids = explode(',', $meta['galeria-professionals'][0]);
                $galeria_urls = [];

                // Recorrer cada ID y convertirlo en URL
                foreach ($galeria_ids as $id) {
                    $url = wp_get_attachment_url(trim($id));
                    if ($url) {
                        $galeria_urls[] = $url;
                    } else {
                        error_log('No se pudo encontrar URL para ID: ' . $id);
                    }
                }

                // Asignar las URLs convertidas al campo galeria-professionals
                $singlecar['galeria-professionals'] = $galeria_urls;
            }

            // Deserializar el campo 'extres-cotxe' y añadir al array principal
            if (isset($meta['extres-cotxe'][0])) {
                $singlecar['extres-cotxe'] = unserialize($meta['extres-cotxe'][0]);
                unset($meta['extres-cotxe']);
            }

            // Añadir otros metadatos al array principal
            foreach ($meta as $key => $value) {
                $singlecar[$key] = maybe_unserialize($value[0]);
            }

            // Añadir las taxonomías al array principal
            $terms = wp_get_post_terms($singlecar_id, get_object_taxonomies('singlecar'));
            foreach ($terms as $term) {
                if (!isset($singlecar[$term->taxonomy])) {
                    $singlecar[$term->taxonomy] = [];
                }
                $singlecar[$term->taxonomy][] = $term->name;
            }

            array_push($singlecars, $singlecar);
        }
    }

    wp_reset_postdata();

    // Obtener total de páginas
    $total_posts = $query->found_posts;
    $total_pages = $query->max_num_pages;

    // Preparar respuesta con paginación
    $response = [
        'posts' => $singlecars,
        'total_posts' => $total_posts,
        'total_pages' => $total_pages,
        'current_page' => $paged,
    ];

    return new WP_REST_Response($response, 200);
}

function create_singlecar($request) {
    $params = $request->get_params();
    
    // Verificar campos requeridos
    $required_fields = ['title', 'content', 'marca', 'modelo'];
    foreach ($required_fields as $field) {
        if (empty($params[$field])) {
            return new WP_Error(
                'missing_required_field',
                sprintf('El campo %s es requerido', $field),
                ['status' => 400]
            );
        }
    }

    // Verificar marca y modelo antes de crear el post
    $marca = get_term_by('slug', $params['marca'], 'marques-coches');
    if (!$marca) {
        return new WP_Error(
            'invalid_marca',
            'La marca especificada no existe',
            ['status' => 400]
        );
    }

    $modelo = get_term_by('slug', $params['modelo'], 'marques-coches');
    if (!$modelo || $modelo->parent != $marca->term_id) {
        return new WP_Error(
            'invalid_modelo',
            'El modelo especificado no existe o no pertenece a la marca indicada',
            ['status' => 400]
        );
    }

    // Crear el post
    $post_data = [
        'post_title' => sanitize_text_field($params['title']),
        'post_content' => wp_kses_post($params['content']),
        'post_status' => isset($params['status']) ? $params['status'] : 'publish',
        'post_type' => 'singlecar',
        'post_author' => get_current_user_id(),
    ];

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return new WP_Error('failed_insert', 'Error al crear el vehículo', ['status' => 500]);
    }

    // Asignar marca y modelo
    $result = wp_set_object_terms($post_id, [$marca->term_id, $modelo->term_id], 'marques-coches');
    if (is_wp_error($result)) {
        wp_delete_post($post_id, true);
        return new WP_Error(
            'term_assignment_failed',
            'Error al asignar marca y modelo',
            ['status' => 500]
        );
    }

    // Lista de campos que son taxonomías y sus nombres correspondientes
    $taxonomy_fields = [
        'types-of-transport' => 'tipus_transport',
        'estat-vehicle' => 'estat',
        'tipus-combustible' => 'combustible',
        'tipus-de-canvi' => 'canvi',
        'tipus-de-propulsor' => 'propulsor'
    ];

    // Procesar taxonomías
    foreach ($taxonomy_fields as $taxonomy => $field) {
        if (!empty($params[$field])) {
            $term_slug = $params[$field];
            
            // Para tipus-combustible, probar primero sin el prefijo
            if ($taxonomy === 'tipus-combustible') {
                $existing_term = get_term_by('slug', $term_slug, $taxonomy);
                if (!$existing_term && strpos($term_slug, 'combustible-') !== 0) {
                    // Si no existe, intentar con el prefijo
                    $term_slug = 'combustible-' . $term_slug;
                    $existing_term = get_term_by('slug', $term_slug, $taxonomy);
                }
            } else {
                $existing_term = get_term_by('slug', $term_slug, $taxonomy);
            }

            // Verificar si el término existe
            if (!$existing_term) {
                wp_delete_post($post_id, true);
                return new WP_Error(
                    'invalid_term',
                    sprintf('El término %s no existe en la taxonomía %s', $params[$field], $taxonomy),
                    ['status' => 400]
                );
            }

            // Asignar término al post
            $result = wp_set_object_terms($post_id, $existing_term->term_id, $taxonomy);
            if (is_wp_error($result)) {
                wp_delete_post($post_id, true);
                return new WP_Error(
                    'term_assignment_failed',
                    'Error al asignar términos',
                    ['status' => 500]
                );
            }
        }
    }

    // Lista de campos que son meta
    $meta_fields = [
        // Campos booleanos
        'is-vip' => 'boolean',
        'venut' => 'boolean',
        'llibre-manteniment' => 'boolean',
        'revisions-oficials' => 'boolean',
        'impostos-deduibles' => 'boolean',
        'vehicle-a-canvi' => 'boolean',
        
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
        
        // Campos de texto/selección
        'venedor' => 'text',
        'any' => 'text',
        'versio' => 'text',
        'nombre-propietaris' => 'text',
        'garantia' => 'text',
        'vehicle-accidentat' => 'text',
        'emissions-vehicle' => 'glossary',
        'data-vip' => 'text'
    ];

    // Guardar metadatos
    foreach ($meta_fields as $field => $type) {
        if (isset($params[$field])) {
            $value = $params[$field];
            
            // Convertir valores según el tipo
            switch ($type) {
                case 'boolean':
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                    break;
                case 'number':
                    $value = filter_var($value, FILTER_VALIDATE_FLOAT);
                    break;
                case 'glossary':
                    if ($field === 'emissions-vehicle') {
                        // Obtener el valor del campo JetEngine directamente
                        $meta_key = 'emissions-vehicle';
                        
                        // Validar que el valor existe en las opciones del campo
                        $field_options = array(
                            'euro1' => 'Euro1',
                            'euro2' => 'Euro2',
                            'euro3' => 'Euro3',
                            'euro4' => 'Euro4',
                            'euro5' => 'Euro5',
                            'euro6' => 'Euro6'
                        );
                        
                        $normalized_value = strtolower($value);
                        
                        if (array_key_exists($normalized_value, $field_options)) {
                            $value = $normalized_value;
                        } else {
                            return new WP_Error(
                                'invalid_emission',
                                sprintf(
                                    'El valor %s no es válido para emissions-vehicle. Valores válidos: %s', 
                                    $value,
                                    implode(', ', array_keys($field_options))
                                ),
                                ['status' => 400]
                            );
                        }
                    }
                    break;
                case 'text':
                    $value = sanitize_text_field($value);
                    break;
            }
            
            delete_post_meta($post_id, $field);
            add_post_meta($post_id, $field, $value, true);
        }
    }

    // Obtener los datos actualizados del post
    $post = get_post($post_id);
    $response = [
        'message' => 'Vehículo creado exitosamente',
        'post_id' => $post_id,
        'title' => $post->post_title,
        'content' => $post->post_content,
        'status' => $post->post_status,
        'marca' => $marca->slug,
        'modelo' => $modelo->slug
    ];

    // Agregar campos de taxonomía a la respuesta
    foreach ($taxonomy_fields as $taxonomy => $field) {
        $terms = wp_get_object_terms($post_id, $taxonomy);
        if (!is_wp_error($terms) && !empty($terms)) {
            $term_slug = $terms[0]->slug;
            // Para tipus-combustible, eliminar el prefijo
            if ($taxonomy === 'tipus-combustible' && strpos($term_slug, 'combustible-') === 0) {
                $term_slug = str_replace('combustible-', '', $term_slug);
            }
            $response[$field] = $term_slug;
        }
    }

    // Agregar campos meta a la respuesta
    foreach ($meta_fields as $field => $type) {
        $value = get_post_meta($post_id, $field, true);
        if ($type === 'boolean') {
            $response[$field] = $value === 'true';
        } else if ($type === 'glossary' && $field === 'emissions-vehicle' && !empty($value)) {
            // Usar el array de opciones para obtener el nombre visible
            $field_options = array(
                'euro1' => 'Euro1',
                'euro2' => 'Euro2',
                'euro3' => 'Euro3',
                'euro4' => 'Euro4',
                'euro5' => 'Euro5',
                'euro6' => 'Euro6'
            );
            $response[$field] = isset($field_options[$value]) ? $field_options[$value] : $value;
        } else if (!empty($value)) {
            $response[$field] = $value;
        }
    }

    return new WP_REST_Response($response, 201);
}

function update_singlecar($request) {
    $params = $request->get_params();
    $post_id = isset($params['id']) ? $params['id'] : 0;

    // Verificar si el post existe y es del tipo correcto
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'singlecar') {
        return new WP_Error(
            'invalid_vehicle',
            'No se encontró el vehículo especificado',
            ['status' => 404]
        );
    }

    // Actualizar título y contenido si se proporcionan
    $post_data = [];
    if (isset($params['title'])) {
        $post_data['post_title'] = sanitize_text_field($params['title']);
    }
    if (isset($params['content'])) {
        $post_data['post_content'] = wp_kses_post($params['content']);
    }
    if (!empty($post_data)) {
        $post_data['ID'] = $post_id;
        wp_update_post($post_data);
    }

    // Actualizar taxonomías
    $taxonomy_fields = Vehicle_Fields::get_taxonomy_fields();
    foreach ($taxonomy_fields as $taxonomy => $field) {
        if (isset($params[$field])) {
            $term_slug = sanitize_text_field($params[$field]);
            
            // Para tipus-combustible, agregar el prefijo si no está presente
            if ($taxonomy === 'tipus-combustible' && strpos($term_slug, 'combustible-') !== 0) {
                $term_slug = 'combustible-' . $term_slug;
            }
            
            $term = get_term_by('slug', $term_slug, $taxonomy);
            if ($term) {
                wp_set_object_terms($post_id, $term->term_id, $taxonomy);
            }
        }
    }

    // Actualizar metadatos
    $meta_fields = Vehicle_Fields::get_meta_fields();
    foreach ($meta_fields as $field => $type) {
        if (isset($params[$field])) {
            try {
                $value = Vehicle_Field_Handler::process_field($field, $params[$field], $type);
                delete_post_meta($post_id, $field);
                add_post_meta($post_id, $field, $value, true);
            } catch (Exception $e) {
                return new WP_Error(
                    'invalid_field_value',
                    $e->getMessage(),
                    ['status' => 400]
                );
            }
        }
    }

    // Preparar respuesta
    $response = [
        'message' => 'Vehículo actualizado exitosamente',
        'post_id' => $post_id,
        'title' => $post->post_title,
        'content' => $post->post_content,
        'status' => $post->post_status
    ];

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

    // Agregar campos de taxonomía a la respuesta
    foreach ($taxonomy_fields as $taxonomy => $field) {
        $terms = wp_get_object_terms($post_id, $taxonomy);
        if (!is_wp_error($terms) && !empty($terms)) {
            $term_slug = $terms[0]->slug;
            if ($taxonomy === 'tipus-combustible' && strpos($term_slug, 'combustible-') === 0) {
                $term_slug = str_replace('combustible-', '', $term_slug);
            }
            $response[$field] = $term_slug;
        }
    }

    // Agregar campos meta a la respuesta
    foreach ($meta_fields as $field => $type) {
        $value = get_post_meta($post_id, $field, true);
        $response[$field] = Vehicle_Field_Handler::format_response_value($field, $value, $type);
    }

    return new WP_REST_Response($response, 200);
}

// Función para eliminar un singlecar
function delete_singlecar($request) {
    $post_id = $request['id'];
    
    // Verificar que el post existe y es del tipo correcto
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'singlecar') {
        return new WP_Error(
            'invalid_vehicle',
            'El vehículo especificado no existe',
            ['status' => 404]
        );
    }

    // Eliminar el post y sus metadatos
    $result = wp_delete_post($post_id, true);
    if (!$result) {
        return new WP_Error(
            'delete_failed',
            'Error al eliminar el vehículo',
            ['status' => 500]
        );
    }

    return new WP_REST_Response([
        'message' => 'Vehículo eliminado exitosamente',
        'deleted' => true,
        'post_id' => $post_id
    ], 200);
}
