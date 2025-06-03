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

    // Obtener metadatos de usuario para los teléfonos
    $user_meta = get_user_meta($user_id);

    $response = [
        'status' => 'success',
        'data' => [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'registered_date' => $user->user_registered,
            'role' => $user->roles[0],
            'logo-empresa' => isset($user_meta['logo-empresa'][0]) && $user_meta['logo-empresa'][0] ? wp_get_attachment_url($user_meta['logo-empresa'][0]) : '',
            'logo-empresa-home' => isset($user_meta['logo-empresa-home'][0]) && $user_meta['logo-empresa-home'][0] ? wp_get_attachment_url($user_meta['logo-empresa-home'][0]) : '',
            'telefon-mobile-professional' => $user_meta['telefon-mobile-professional'][0] ?? '', // Professional mobile phone
            'telefon-comercial' => $user_meta['telefon-comercial'][0] ?? '', // Commercial phone
            'telefon-whatsapp' => $user_meta['telefon-whatsapp'][0] ?? '', // Whatsapp phone
            'localitat-professional' => $user_meta['localitat-professional'][0] ?? '', // Professional locality
            'adreca-professional' => $user_meta['adreca-professional'][0] ?? '', // Professional address
            'nom-contacte' => $user_meta['nom-contacte'][0] ?? '', // Contact name
            'cognoms-contacte' => $user_meta['cognoms-contacte'][0] ?? '', // Contact surname
            'galeria-professionals' => !empty($user_meta['galeria-professionals'][0]) ? array_map('wp_get_attachment_url', explode(',', $user_meta['galeria-professionals'][0])) : [], // Professional gallery
            'descripcio-empresa' => $user_meta['descripcio-empresa'][0] ?? '', // Company description
            'pagina-web' => $user_meta['pagina-web'][0] ?? '', // Website
            'total_vehicles' => count($vehicles),
            'active_vehicles' => count(array_filter($vehicles, function($v) {
                return $v['anunci-actiu'];
            }))
        ]
    ];

    return new WP_REST_Response($response, 200);
}

function get_all_sellers_data() {
    exit('PRUEBA DE EJECUCIÓN');

    $users = get_users([
        'role__in' => ['administrator', 'subscriber', 'contributor', 'author']
    ]);

    $sellers_data = array_map(function($user) {
        $vehicles = get_seller_vehicles($user->ID);
        $user_meta = get_user_meta($user->ID);

        // Obtener el ID del logo-empresa-home
        $logo_empresa_home_id = isset($user_meta['logo-empresa-home'][0]) ? $user_meta['logo-empresa-home'][0] : '';
        $logo_empresa_home_url = '';
        if ($logo_empresa_home_id && is_numeric($logo_empresa_home_id)) {
            $url = wp_get_attachment_url($logo_empresa_home_id);
            if ($url) {
                $logo_empresa_home_url = $url;
            }
        }

        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'registered_date' => $user->user_registered,
            'role' => $user->roles[0],
            'logo-empresa-home' => $logo_empresa_home_url, // SIEMPRE presente
            'total_vehicles' => count($vehicles),
            'active_vehicles' => count(array_filter($vehicles, function($v) {
                return get_post_meta($v, 'anunci-actiu', true) === 'true';
            })),
        ];
    }, $users);

    var_dump($sellers_data); exit;

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


