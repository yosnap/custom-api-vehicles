<?php
/**
 * Funciones auxiliares para el diagnóstico
 */
class DiagnosticHelpers {
    
    public static function test_api_connection() {
        $test_url = rest_url('api-motor/v1/vehicles?per_page=1');
        $response = wp_remote_get($test_url);
        
        if (is_wp_error($response)) {
            return [
                'status' => 'error',
                'message' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return [
            'status' => $status_code === 200 ? 'ok' : 'error',
            'status_code' => $status_code,
            'response_time' => 'N/A' // Se podría implementar medición de tiempo
        ];
    }
    
    public static function get_registered_routes() {
        $routes = rest_get_server()->get_routes();
        $api_routes = [];
        
        foreach ($routes as $route => $handlers) {
            if (strpos($route, '/api-motor/v1') === 0) {
                $api_routes[] = [
                    'route' => $route,
                    'methods' => array_keys($handlers[0]['methods'])
                ];
            }
        }
        
        return $api_routes;
    }
    
    public static function check_log_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_api_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        return [
            'exists' => $table_exists,
            'table_name' => $table_name
        ];
    }
    
    public static function get_recent_errors() {
        if (!class_exists('Vehicle_API_Logger')) {
            return ['message' => 'Logger no disponible'];
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_api_logs';
        
        $recent_errors = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_name 
            WHERE action_type = 'error' 
            ORDER BY created_at DESC 
            LIMIT 10
        "));
        
        return $recent_errors ?: ['message' => 'No hay errores recientes'];
    }
}
