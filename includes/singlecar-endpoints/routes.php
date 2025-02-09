<?php
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
        'permission_callback' => '__return_true'
    ]);
});
