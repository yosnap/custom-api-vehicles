<?php

function get_sellers($request) {
    try {
        $params = $request->get_params();
        $seller_id = isset($params['id']) ? intval($params['id']) : 0;
        
        // Si no es administrador, solo puede ver su propia información
        if (!current_user_can('administrator')) {
            $current_user_id = get_current_user_id();
            if (!$current_user_id) {
                return new WP_REST_Response([
                    'status' => 'error',
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            // Si se solicita un ID específico que no es el suyo
            if ($seller_id && $seller_id !== $current_user_id) {
                return new WP_REST_Response([
                    'status' => 'error',
                    'message' => 'No tienes permiso para ver esta información'
                ], 403);
            }
            return get_seller_data($current_user_id);
        }

        // Para administradores
        if ($seller_id) {
            // Devolver información de un vendedor específico
            return get_seller_data($seller_id);
        } else {
            // Devolver lista de todos los vendedores
            return get_all_sellers_data();
        }

    } catch (Exception $e) {
        return new WP_REST_Response([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

function get_seller_data($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        return new WP_REST_Response([
            'status' => 'error',
            'message' => 'Usuario no encontrado'
        ], 404);
    }

    // Obtener vehículos del vendedor
    $vehicles = get_seller_vehicles($user_id);

    $response = [
        'status' => 'success',
        'data' => [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'registered_date' => $user->user_registered,
            'role' => $user->roles[0],
            'total_vehicles' => count($vehicles),
            'active_vehicles' => count(array_filter($vehicles, function($v) {
                return $v['anunci-actiu'];
            }))
        ]
    ];

    return new WP_REST_Response($response, 200);
}

function get_all_sellers_data() {
    $users = get_users([
        'role__in' => ['administrator', 'subscriber', 'contributor', 'author']
    ]);

    $sellers_data = array_map(function($user) {
        $vehicles = get_seller_vehicles($user->ID);
        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'registered_date' => $user->user_registered,
            'role' => $user->roles[0],
            'total_vehicles' => count($vehicles),
            'active_vehicles' => count(array_filter($vehicles, function($v) {
                return $v['anunci-actiu'];
            }))
        ];
    }, $users);

    return new WP_REST_Response([
        'status' => 'success',
        'total' => count($sellers_data),
        'data' => $sellers_data
    ], 200);
}

function get_seller_vehicles($user_id) {
    $args = [
        'post_type' => 'singlecar',
        'author' => $user_id,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ];

    $query = new WP_Query($args);
    $vehicles = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $vehicles[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'anunci-actiu' => get_post_meta(get_the_ID(), 'anunci-actiu', true) === 'true'
            ];
        }
        wp_reset_postdata();
    }

    return $vehicles;
}
