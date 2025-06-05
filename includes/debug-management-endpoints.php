<?php
/**
 * Endpoint administrativo para gestión de logs
 */

add_action('rest_api_init', function() {
    // Endpoint para obtener estadísticas de debug
    register_rest_route('api-motor/v1', '/debug/stats', [
        'methods' => 'GET',
        'callback' => function() {
            if (!current_user_can('administrator')) {
                return new WP_REST_Response(['error' => 'No autorizado'], 403);
            }
            
            return new WP_REST_Response([
                'status' => 'success',
                'stats' => Vehicle_Debug_Handler::get_debug_stats(),
                'timestamp' => current_time('mysql')
            ], 200);
        },
        'permission_callback' => '__return_true'
    ]);
    
    // Endpoint para limpiar logs antiguos manualmente
    register_rest_route('api-motor/v1', '/debug/cleanup', [
        'methods' => 'POST',
        'callback' => function($request) {
            if (!current_user_can('administrator')) {
                return new WP_REST_Response(['error' => 'No autorizado'], 403);
            }
            
            $days = $request->get_param('days') ?: 30;
            $days = max(1, min(365, intval($days))); // Entre 1 y 365 días
            
            $result = Vehicle_Debug_Handler::cleanup_old_logs($days);
            
            if ($result !== false) {
                return new WP_REST_Response([
                    'status' => 'success',
                    'message' => "Limpieza completada: $result registros eliminados",
                    'records_deleted' => $result,
                    'days_threshold' => $days
                ], 200);
            } else {
                return new WP_REST_Response([
                    'status' => 'error',
                    'message' => 'Error al limpiar los logs'
                ], 500);
            }
        },
        'permission_callback' => '__return_true',
        'args' => [
            'days' => [
                'default' => 30,
                'sanitize_callback' => 'absint'
            ]
        ]
    ]);
    
    // Endpoint para obtener logs recientes
    register_rest_route('api-motor/v1', '/debug/recent-logs', [
        'methods' => 'GET',
        'callback' => function($request) {
            if (!current_user_can('administrator')) {
                return new WP_REST_Response(['error' => 'No autorizado'], 403);
            }
            
            $limit = $request->get_param('limit') ?: 50;
            $level = $request->get_param('level') ?: 'all';
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'vehicle_api_logs';
            
            $where_clause = '';
            if ($level !== 'all') {
                $where_clause = $wpdb->prepare(' WHERE action_type = %s', $level);
            }
            
            $logs = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM $table_name 
                $where_clause
                ORDER BY created_at DESC 
                LIMIT %d
            ", $limit));
            
            return new WP_REST_Response([
                'status' => 'success',
                'logs' => $logs,
                'count' => count($logs),
                'filters' => [
                    'limit' => $limit,
                    'level' => $level
                ]
            ], 200);
        },
        'permission_callback' => '__return_true',
        'args' => [
            'limit' => [
                'default' => 50,
                'sanitize_callback' => 'absint'
            ],
            'level' => [
                'default' => 'all',
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ]
    ]);
});
