<?php
// Version: 2024-03-19-015
// Sellers endpoint functionality

// Registrar la ruta de sellers
add_action('rest_api_init', function () {
    register_rest_route('api-motor/v1', '/sellers', [
        'methods' => 'GET',
        'callback' => 'get_seller_details',
        'permission_callback' => '__return_true',
    ]);
});

// Asegurarnos de que la función esté disponible globalmente
if (!function_exists('get_seller_details')) {
    function get_seller_details($request) {
        try {
            Vehicle_Debug_Handler::log('DEBUG: Iniciando get_seller_details');
            
            $params = $request->get_params();
            $user_id = isset($params['user_id']) ? intval($params['user_id']) : null;
            $current_user_id = get_current_user_id();
            $is_admin = current_user_can('administrator');

            Vehicle_Debug_Handler::log('DEBUG: Params: ' . print_r($params, true));
            Vehicle_Debug_Handler::log('DEBUG: User ID: ' . $user_id);
            Vehicle_Debug_Handler::log('DEBUG: Current User ID: ' . $current_user_id);
            Vehicle_Debug_Handler::log('DEBUG: Is Admin: ' . ($is_admin ? 'true' : 'false'));

            // Si no es admin, solo puede ver sus propios datos
            if (!$is_admin) {
                if (!$current_user_id) {
                    return new WP_REST_Response([
                        'status' => 'error',
                        'message' => 'No autorizado'
                    ], 401);
                }
                // Forzar user_id al ID del usuario actual
                $user_id = $current_user_id;
            }

            // Si es admin y no proporciona user_id, devolver lista de usuarios
            if ($is_admin && !$user_id) {
                Vehicle_Debug_Handler::log('DEBUG: Obteniendo lista de usuarios');
                $users = get_users(['role__not_in' => ['administrator']]);
                $authors = [];

                foreach ($users as $user) {
                    $user_meta = get_user_meta($user->ID);
                    // Calcular vehículos totales y activos
                    $args = [
                        'post_type' => 'singlecar',
                        'author' => $user->ID,
                        'post_status' => 'publish',
                        'posts_per_page' => -1
                    ];
                    $query = new WP_Query($args);
                    $total_vehicles = $query->found_posts;
                    $active_vehicles = 0;
                    if ($query->have_posts()) {
                        foreach ($query->posts as $post) {
                            if (get_post_meta($post->ID, 'anunci-actiu', true) === 'true') {
                                $active_vehicles++;
                            }
                        }
                    }
                    $authors[] = [
                        'id' => $user->ID,
                        'username' => $user->user_login,
                        'email' => $user->user_email,
                        'name' => $user->display_name,
                        'registered_date' => $user->user_registered,
                        'role' => $user->roles[0] ?? '',
                        'total_vehicles' => $total_vehicles,
                        'active_vehicles' => $active_vehicles
                    ];
                }

                return new WP_REST_Response([
                    'status' => 'success',
                    'total' => count($authors),
                    'data' => $authors
                ], 200);
            }

            Vehicle_Debug_Handler::log('DEBUG: Obteniendo datos del usuario ' . $user_id);

            // Obtener datos del usuario específico
            $user = get_userdata($user_id);
            if (!$user) {
                return new WP_REST_Response([
                    'status' => 'error',
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Obtener metadatos del usuario
            $user_meta = get_user_meta($user_id);
            Vehicle_Debug_Handler::log('DEBUG: User meta: ' . print_r($user_meta, true));

            // Obtener vehículos del usuario
            $args = [
                'post_type' => 'singlecar',
                'author' => $user_id,
                'post_status' => 'publish',
                'posts_per_page' => -1
            ];
            $query = new WP_Query($args);
            $total_vehicles = $query->found_posts;
            $active_vehicles = 0;
            if ($query->have_posts()) {
                foreach ($query->posts as $post) {
                    if (get_post_meta($post->ID, 'anunci-actiu', true) === 'true') {
                        $active_vehicles++;
                    }
                }
            }

            // Construir respuesta base
            $response_data = [
                'id' => $user_id,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'name' => $user->display_name,
                'registered_date' => $user->user_registered,
                'role' => $user->roles[0] ?? '',
                'logo-empresa' => wp_get_attachment_url($user_meta['logo-empresa'][0] ?? ''),
                'logo-empresa-home' => wp_get_attachment_url($user_meta['logo-empresa-home'][0] ?? ''),
                'nom-empresa' => $user_meta['nom-empresa'][0] ?? '',
                'telefon-mobile-professional' => $user_meta['telefon-mobile-professional'][0] ?? '',
                'telefon-comercial' => $user_meta['telefon-comercial'][0] ?? '',
                'telefon-whatsapp' => $user_meta['telefon-whatsapp'][0] ?? '',
                'localitat-professional' => $user_meta['localitat-professional'][0] ?? '',
                'adreca-professional' => $user_meta['adreca-professional'][0] ?? '',
                'nom-contacte' => $user_meta['nom-contacte'][0] ?? '',
                'cognoms-contacte' => $user_meta['cognoms-contacte'][0] ?? '',
                'descripcio-empresa' => $user_meta['descripcio-empresa'][0] ?? '',
                'pagina-web' => $user_meta['pagina-web'][0] ?? '',
                'galeria-professionals' => [],
                'total_vehicles' => $total_vehicles,
                'active_vehicles' => $active_vehicles
            ];

            // Procesar galería de imágenes
            if (!empty($user_meta['galeria-professionals'][0])) {
                $gallery_ids = explode(',', $user_meta['galeria-professionals'][0]);
                $response_data['galeria-professionals'] = array_map('wp_get_attachment_url', $gallery_ids);
            }

            Vehicle_Debug_Handler::log('DEBUG: Respuesta final: ' . print_r($response_data, true));

            return new WP_REST_Response([
                'status' => 'success',
                'data' => $response_data
            ], 200);

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log('ERROR en get_seller_details: ' . $e->getMessage());
            Vehicle_Debug_Handler::log('Stack trace: ' . $e->getTraceAsString());
            
            return new WP_REST_Response([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
