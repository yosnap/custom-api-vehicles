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
            'callback' => 'create_singlecar',
            'permission_callback' => function () {
                return is_user_logged_in();
            },
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

    // Agregar ruta para vendedores
    register_rest_route('api-motor/v1', '/sellers', [
        [
            'methods' => 'GET',
            'callback' => 'get_sellers',
            'permission_callback' => '__return_true',
        ]
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



