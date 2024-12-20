<?php
// Registrar la ruta REST API para /author
add_action('rest_api_init', function () {
    register_rest_route('api-motor/v1', '/sellers', [
        'methods' => 'GET',
        'callback' => 'get_author_details',
        'permission_callback' => '__return_true',
    ]);
});

function get_author_details($request)
{
    $params = $request->get_params();

    // Definir los campos a eliminar
    $fields_to_remove = [
        'rich_editing',
        'syntax_highlighting',
        'comment_shortcuts',
        'use_ssl',
        'show_admin_bar_front',
        'hgg_capabilities',
        'hgg_user_level',
        '_application_passwords',
        'smack_uci_import',
        'jwt_auth_pass',
        'session_tokens',
        'hgg_dashboard_quick_press_last_post_id',
        'community-events-location',
        'elementor_admin_notices',
        'edit_singlecar_per_page',
        'hgg_user-settings',
        'hgg_user-settings-time',
        'current_sns_tab',
        'sendPassword',
        'dismissed_wp_pointers',
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

    if (isset($params['user_id'])) {
        // Si se proporciona un user_id, devuelve los detalles del autor
        $user_id = intval($params['user_id']);
        $user_meta = get_user_meta($user_id);

        // Obtener la fecha de registro del usuario
        $user = get_userdata($user_id);
        $user_registered = $user->user_registered;

        // Procesar los campos "logo-empresa-home", "logo-empresa" y "galeria-professionals" para convertir los IDs en URLs
        if (isset($user_meta['logo-empresa-home'][0])) {
            $logo_home_id = $user_meta['logo-empresa-home'][0];
            $user_meta['logo-empresa-home'] = wp_get_attachment_url($logo_home_id);
        }
        if (isset($user_meta['logo-empresa'][0])) {
            $logo_empresa_id = $user_meta['logo-empresa'][0];
            $user_meta['logo-empresa'] = wp_get_attachment_url($logo_empresa_id);
        }
        if (isset($user_meta['galeria-professionals'][0])) {
            $galeria_ids = explode(',', $user_meta['galeria-professionals'][0]);
            $galeria_urls = [];
            foreach ($galeria_ids as $id) {
                $url = wp_get_attachment_url(trim($id));
                if ($url) {
                    $galeria_urls[] = $url;
                }
            }
            $user_meta['galeria-professionals'] = $galeria_urls;
        }

        foreach ($fields_to_remove as $field) {
            if (isset($user_meta[$field])) {
                unset($user_meta[$field]);
            }
        }

        // Reestructurar metadatos del autor
        $user_data = [
            'ID' => $user_id,
            'display_name' => get_the_author_meta('display_name', $user_id),
            'user_registered' => $user_registered, // Añadir la fecha de registro del usuario
            'meta' => $user_meta // Devolver todos los metadatos restantes como un array
        ];

        return new WP_REST_Response($user_data, 200);
    } else {
        // Si no se proporciona un user_id, devuelve un listado de autores
        $users = get_users(['who' => 'authors']);
        $authors = [];

        foreach ($users as $user) {
            $user_id = $user->ID;
            $user_meta = get_user_meta($user_id);

            // Obtener la fecha de registro del usuario
            $user_registered = $user->user_registered;

            // Procesar los campos "logo-empresa-home", "logo-empresa" y "galeria-professionals" para convertir los IDs en URLs
            if (isset($user_meta['logo-empresa-home'][0])) {
                $logo_home_id = $user_meta['logo-empresa-home'][0];
                $user_meta['logo-empresa-home'] = wp_get_attachment_url($logo_home_id);
            }
            if (isset($user_meta['logo-empresa'][0])) {
                $logo_empresa_id = $user_meta['logo-empresa'][0];
                $user_meta['logo-empresa'] = wp_get_attachment_url($logo_empresa_id);
            }
            if (isset($user_meta['galeria-professionals'][0])) {
                $galeria_ids = explode(',', $user_meta['galeria-professionals'][0]);
                $galeria_urls = [];
                foreach ($galeria_ids as $id) {
                    $url = wp_get_attachment_url(trim($id));
                    if ($url) {
                        $galeria_urls[] = $url;
                    }
                }
                $user_meta['galeria-professionals'] = $galeria_urls;
            }

            foreach ($fields_to_remove as $field) {
                if (isset($user_meta[$field])) {
                    unset($user_meta[$field]);
                }
            }

            // Reestructurar metadatos del autor
            $user_data = [
                'ID' => $user_id,
                'display_name' => $user->display_name,
                'user_registered' => $user_registered, // Añadir la fecha de registro del usuario
                'meta' => $user_meta // Devolver todos los metadatos restantes como un array
            ];

            $authors[] = $user_data;
        }

        return new WP_REST_Response($authors, 200);
    }
}
