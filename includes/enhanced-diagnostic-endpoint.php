<?php
/**
 * Endpoint de diagnóstico mejorado
 */
function register_enhanced_diagnostic_endpoint() {
    register_rest_route('api-motor/v1', '/diagnostic', [
        'methods' => 'GET',
        'callback' => function() {
            // Verificar permisos
            if (!current_user_can('administrator')) {
                return new WP_REST_Response([
                    'error' => 'No autorizado',
                    'message' => 'Solo administradores pueden acceder a este endpoint'
                ], 403);
            }

            try {
                // Información básica del sistema
                $diagnostic_data = [
                    'timestamp' => current_time('mysql'),
                    'plugin_version' => '2.2.0',
                    'system_info' => [
                        'wp_version' => get_bloginfo('version'),
                        'php_version' => PHP_VERSION,
                        'mysql_version' => $GLOBALS['wpdb']->get_var('SELECT VERSION()'),
                        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
                    ],
                    'dependencies' => Vehicle_Plugin_Dependencies::get_diagnostic_info(),
                    'jetengine_info' => [],
                    'api_endpoints' => [],
                    'database_info' => [],
                    'recent_errors' => []
                ];

                // Información de JetEngine si está disponible
                if (Vehicle_Plugin_Dependencies::is_jetengine_ready()) {
                    $diagnostic_data['jetengine_info'] = [
                        'version' => defined('JET_ENGINE_VERSION') ? JET_ENGINE_VERSION : 'Unknown',
                        'active' => true,
                        'taxonomies' => get_taxonomies(['_builtin' => false], 'names'),
                        'post_types' => get_post_types(['_builtin' => false], 'names'),
                        'meta_boxes' => method_exists(jet_engine()->meta_boxes, 'get_registered_fields') 
                            ? jet_engine()->meta_boxes->get_registered_fields() 
                            : [],
                        'glossaries' => isset(jet_engine()->glossaries) 
                            ? jet_engine()->glossaries->get_glossaries_for_js() 
                            : []
                    ];
                } else {
                    $diagnostic_data['jetengine_info'] = [
                        'active' => false,
                        'message' => 'JetEngine no está disponible o no está configurado correctamente'
                    ];
                }

                // Verificar endpoints de la API
                $diagnostic_data['api_endpoints'] = [
                    'base_url' => rest_url('api-motor/v1/'),
                    'vehicles_endpoint' => rest_url('api-motor/v1/vehicles'),
                    'test_connection' => DiagnosticHelpers::test_api_connection(),
                    'registered_routes' => DiagnosticHelpers::get_registered_routes()
                ];

                // Información de la base de datos
                $diagnostic_data['database_info'] = [
                    'vehicles_count' => wp_count_posts('singlecar'),
                    'published_vehicles' => count(get_posts([
                        'post_type' => 'singlecar',
                        'post_status' => 'publish',
                        'numberposts' => -1,
                        'fields' => 'ids'
                    ])),
                    'log_table_exists' => DiagnosticHelpers::check_log_table_exists()
                ];

                // Errores recientes del log si está disponible
                if (class_exists('Vehicle_API_Logger')) {
                    $diagnostic_data['recent_errors'] = DiagnosticHelpers::get_recent_errors();
                }

                return new WP_REST_Response($diagnostic_data, 200);

            } catch (Exception $e) {
                Vehicle_Debug_Handler::log('Error en endpoint de diagnóstico: ' . $e->getMessage(), 'error');
                
                return new WP_REST_Response([
                    'error' => 'Error interno',
                    'message' => 'Ocurrió un error al generar el diagnóstico',
                    'debug_info' => WP_DEBUG ? $e->getMessage() : null
                ], 500);
            }
        },
        'permission_callback' => '__return_true'
    ]);
}

add_action('rest_api_init', 'register_enhanced_diagnostic_endpoint');
