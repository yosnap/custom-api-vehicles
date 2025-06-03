<?php
function register_vehicle_routes() {
    register_rest_route('api-motor/v1', '/vehicles', [
        [
            'methods' => 'GET',
            'callback' => 'get_singlecar',
            'permission_callback' => '__return_true',
            'args' => [
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ],
                'per_page' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint'
                ],
                'orderby' => [
                    'default' => 'date',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'order' => [
                    'default' => 'DESC',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ],
        [
            'methods' => 'POST',
            'callback' => function($request) {
                // Obtener parámetros y aplicar valores por defecto
                $params = $request->get_params();
                $params = apply_default_values($params);
                
                // Pasar el objeto Request original
                return create_singlecar($request);
            },
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]
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

    register_rest_route('api-motor/v1', '/vehicles/(?P<slug>[\w-]+)', [
        [
            'methods' => 'GET',
            'callback' => 'get_vehicle_details_by_slug',
            'permission_callback' => function ($request) {
                if (!is_user_logged_in()) {
                    return false;
                }
                return verify_post_ownership_by_slug($request['slug']);
            },
        ],
    ]);

    register_rest_route('api-motor/v1', '/debug-fields', [
        'methods' => 'GET',
        'callback' => 'debug_vehicle_fields',
        'permission_callback' => function() {
            return current_user_can('administrator');
        }
    ]);
}

add_action('rest_api_init', 'register_vehicle_routes');

function register_diagnostic_endpoint() {
    register_rest_route('api-motor/v1', '/diagnostic', [
        'methods' => 'GET',
        'callback' => function() {
            if (!current_user_can('administrator')) {
                return new WP_REST_Response(['error' => 'No autorizado'], 403);
            }

            return new WP_REST_Response([
                'jet_engine_active' => function_exists('jet_engine'),
                'taxonomies' => get_taxonomies(['_builtin' => false], 'names'),
                'post_types' => get_post_types(['_builtin' => false], 'names'),
                'meta_boxes' => jet_engine()->meta_boxes->get_registered_fields(),
                'glossaries' => isset(jet_engine()->glossaries) ? jet_engine()->glossaries->get_glossaries_for_js() : [],
                'php_version' => PHP_VERSION,
                'wp_version' => get_bloginfo('version')
            ]);
        },
        'permission_callback' => '__return_true'
    ]);
}
add_action('rest_api_init', 'register_diagnostic_endpoint');

/**
 * Función para aplicar valores por defecto a los parámetros de la solicitud
 */
function apply_default_values($params) {
    // Asegurarse de que los campos booleanos importantes tengan valores por defecto
    if (!isset($params['frenada-regenerativa']) || $params['frenada-regenerativa'] === '') {
        $params['frenada-regenerativa'] = 'no';
    }
    
    if (!isset($params['one-pedal']) || $params['one-pedal'] === '') {
        $params['one-pedal'] = 'no';
    }
    
    if (!isset($params['aire-acondicionat']) || $params['aire-acondicionat'] === '') {
        $params['aire-acondicionat'] = 'no';
    }
    
    if (!isset($params['climatitzacio']) || $params['climatitzacio'] === '') {
        $params['climatitzacio'] = 'no';
    }
    
    if (!isset($params['vehicle-fumador']) || $params['vehicle-fumador'] === '') {
        $params['vehicle-fumador'] = 'no';
    }
    
    if (!isset($params['vehicle-accidentat']) || $params['vehicle-accidentat'] === '') {
        $params['vehicle-accidentat'] = 'no';
    }
    
    if (!isset($params['llibre-manteniment']) || $params['llibre-manteniment'] === '') {
        $params['llibre-manteniment'] = 'no';
    }
    
    if (!isset($params['revisions-oficials']) || $params['revisions-oficials'] === '') {
        $params['revisions-oficials'] = 'no'; // Añadido revisions-oficials
    }
    
    if (!isset($params['impostos-deduibles']) || $params['impostos-deduibles'] === '') {
        $params['impostos-deduibles'] = 'no'; // Añadido impostos-deduibles
    }
    
    if (!isset($params['vehicle-a-canvi']) || $params['vehicle-a-canvi'] === '') {
        $params['vehicle-a-canvi'] = 'no'; // Añadido vehicle-a-canvi
    }
    
    // Añadir valor por defecto para portes-cotxe
    if (!isset($params['portes-cotxe']) || $params['portes-cotxe'] === '' || !is_numeric($params['portes-cotxe'])) {
        $params['portes-cotxe'] = '5'; // Valor típico para coches
    }
    
    return $params;
}

add_action('rest_api_init', function () {
    register_rest_route('api-motor/v1', '/estat-vehicle/(?P<estat>[\w-]+)', [
        'methods' => 'GET',
        'callback' => 'get_vehicles_by_estat',
        'permission_callback' => '__return_true',
        'args' => [
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint'
            ],
            'per_page' => [
                'default' => 10,
                'sanitize_callback' => 'absint'
            ],
            'orderby' => [
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'order' => [
                'default' => 'DESC',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            // Puedes añadir más parámetros aquí si lo necesitas
        ]
    ]);
});

function get_vehicles_by_estat($request) {
    $estat = $request['estat'];
    $paged = $request->get_param('page') ?: 1;
    $per_page = $request->get_param('per_page') ?: 10;
    $orderby = $request->get_param('orderby') ?: 'date';
    $order = $request->get_param('order') ?: 'DESC';

    $args = [
        'post_type' => 'singlecar',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'orderby' => $orderby,
        'order' => $order,
        'post_status' => 'publish',
        'tax_query' => [
            [
                'taxonomy' => 'estat-vehicle',
                'field' => 'slug',
                'terms' => $estat
            ]
        ]
    ];

    $query = new WP_Query($args);
    $vehicles = process_query_results($query);
    wp_reset_postdata();

    return new WP_REST_Response([
        'status' => 'success',
        'items' => $vehicles,
        'total' => $query->found_posts,
        'pages' => ceil($query->found_posts / $per_page),
        'page' => (int) $paged,
        'per_page' => (int) $per_page
    ], 200);
}

// ENDPOINTS DINÁMICOS PARA TAXONOMÍAS Y MODELOS
add_action('rest_api_init', function () {
    $taxonomies = [
        'tipus-combustible' => 'tipus-combustible',
        'tipus-propulsor' => 'tipus-propulsor',
        'tipus-vehicle' => 'types-of-transport',
        'marques-cotxe' => 'marques-coches',
        'marques-moto' => 'marques-de-moto'
    ];
    foreach ($taxonomies as $endpoint => $taxonomy) {
        register_rest_route('api-motor/v1', '/' . $endpoint . '/(?P<slug>[\w-]+)', [
            'methods' => 'GET',
            'callback' => function($request) use ($taxonomy) {
                $slug = $request['slug'];
                $paged = $request->get_param('page') ?: 1;
                $per_page = $request->get_param('per_page') ?: 10;
                $orderby = $request->get_param('orderby') ?: 'date';
                $order = $request->get_param('order') ?: 'DESC';
                $args = [
                    'post_type' => 'singlecar',
                    'posts_per_page' => $per_page,
                    'paged' => $paged,
                    'orderby' => $orderby,
                    'order' => $order,
                    'post_status' => 'publish',
                    'tax_query' => [
                        [
                            'taxonomy' => $taxonomy,
                            'field' => 'slug',
                            'terms' => $slug
                        ]
                    ]
                ];
                $query = new WP_Query($args);
                $vehicles = process_query_results($query);
                wp_reset_postdata();
                return new WP_REST_Response([
                    'status' => 'success',
                    'items' => $vehicles,
                    'total' => $query->found_posts,
                    'pages' => ceil($query->found_posts / $per_page),
                    'page' => (int) $paged,
                    'per_page' => (int) $per_page
                ], 200);
            },
            'permission_callback' => '__return_true',
            'args' => [
                'page' => [ 'default' => 1, 'sanitize_callback' => 'absint' ],
                'per_page' => [ 'default' => 10, 'sanitize_callback' => 'absint' ],
                'orderby' => [ 'default' => 'date', 'sanitize_callback' => 'sanitize_text_field' ],
                'order' => [ 'default' => 'DESC', 'sanitize_callback' => 'sanitize_text_field' ]
            ]
        ]);
    }

    // Endpoints anidados para modelos bajo marca (coches)
    register_rest_route('api-motor/v1', '/marques-cotxe/(?P<marca>[\w-]+)/(?P<modelo>[\w-]+)', [
        'methods' => 'GET',
        'callback' => function($request) {
            $marca = $request['marca'];
            $modelo = $request['modelo'];
            $paged = $request->get_param('page') ?: 1;
            $per_page = $request->get_param('per_page') ?: 10;
            $orderby = $request->get_param('orderby') ?: 'date';
            $order = $request->get_param('order') ?: 'DESC';
            $args = [
                'post_type' => 'singlecar',
                'posts_per_page' => $per_page,
                'paged' => $paged,
                'orderby' => $orderby,
                'order' => $order,
                'post_status' => 'publish',
                'tax_query' => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'marques-coches',
                        'field' => 'slug',
                        'terms' => $marca
                    ],
                    [
                        'taxonomy' => 'marques-coches',
                        'field' => 'slug',
                        'terms' => $modelo
                    ]
                ]
            ];
            $query = new WP_Query($args);
            $vehicles = process_query_results($query);
            wp_reset_postdata();
            return new WP_REST_Response([
                'status' => 'success',
                'items' => $vehicles,
                'total' => $query->found_posts,
                'pages' => ceil($query->found_posts / $per_page),
                'page' => (int) $paged,
                'per_page' => (int) $per_page
            ], 200);
        },
        'permission_callback' => '__return_true',
        'args' => [
            'page' => [ 'default' => 1, 'sanitize_callback' => 'absint' ],
            'per_page' => [ 'default' => 10, 'sanitize_callback' => 'absint' ],
            'orderby' => [ 'default' => 'date', 'sanitize_callback' => 'sanitize_text_field' ],
            'order' => [ 'default' => 'DESC', 'sanitize_callback' => 'sanitize_text_field' ]
        ]
    ]);
    // Endpoints anidados para modelos bajo marca (motos)
    register_rest_route('api-motor/v1', '/marques-moto/(?P<marca>[\w-]+)/(?P<modelo>[\w-]+)', [
        'methods' => 'GET',
        'callback' => function($request) {
            $marca = $request['marca'];
            $modelo = $request['modelo'];
            $paged = $request->get_param('page') ?: 1;
            $per_page = $request->get_param('per_page') ?: 10;
            $orderby = $request->get_param('orderby') ?: 'date';
            $order = $request->get_param('order') ?: 'DESC';
            $args = [
                'post_type' => 'singlecar',
                'posts_per_page' => $per_page,
                'paged' => $paged,
                'orderby' => $orderby,
                'order' => $order,
                'post_status' => 'publish',
                'tax_query' => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'marques-de-moto',
                        'field' => 'slug',
                        'terms' => $marca
                    ],
                    [
                        'taxonomy' => 'marques-de-moto',
                        'field' => 'slug',
                        'terms' => $modelo
                    ]
                ]
            ];
            $query = new WP_Query($args);
            $vehicles = process_query_results($query);
            wp_reset_postdata();
            return new WP_REST_Response([
                'status' => 'success',
                'items' => $vehicles,
                'total' => $query->found_posts,
                'pages' => ceil($query->found_posts / $per_page),
                'page' => (int) $paged,
                'per_page' => (int) $per_page
            ], 200);
        },
        'permission_callback' => '__return_true',
        'args' => [
            'page' => [ 'default' => 1, 'sanitize_callback' => 'absint' ],
            'per_page' => [ 'default' => 10, 'sanitize_callback' => 'absint' ],
            'orderby' => [ 'default' => 'date', 'sanitize_callback' => 'sanitize_text_field' ],
            'order' => [ 'default' => 'DESC', 'sanitize_callback' => 'sanitize_text_field' ]
        ]
    ]);
});



