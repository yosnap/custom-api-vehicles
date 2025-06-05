<?php
/**
 * Test del Debug Handler Mejorado
 * 
 * Este archivo permite probar todas las funcionalidades del nuevo debug handler
 * SOLO PARA DESARROLLO - Eliminar en producción
 */

add_action('rest_api_init', function() {
    register_rest_route('api-motor/v1', '/debug/test', [
        'methods' => 'GET',
        'callback' => function() {
            if (!current_user_can('administrator')) {
                return new WP_REST_Response(['error' => 'No autorizado'], 403);
            }
            
            if (!defined('WP_DEBUG') || !WP_DEBUG) {
                return new WP_REST_Response([
                    'error' => 'Tests solo disponibles en modo debug',
                    'message' => 'Habilita WP_DEBUG para ejecutar tests'
                ], 400);
            }
            
            $test_results = [];
            
            // Test 1: Logging básico
            Vehicle_Debug_Handler::log('Test: Mensaje de info', 'info');
            Vehicle_Debug_Handler::log('Test: Mensaje de warning', 'warning');
            Vehicle_Debug_Handler::log('Test: Mensaje de error', 'error');
            $test_results['basic_logging'] = 'Completado';
            
            // Test 2: Logging de API
            Vehicle_Debug_Handler::log_api_error('/test', 'Error de prueba', ['test' => true]);
            $test_results['api_logging'] = 'Completado';
            
            // Test 3: Logging de validación
            Vehicle_Debug_Handler::log_validation_error('test_field', 'invalid_value', 'valid_value');
            $test_results['validation_logging'] = 'Completado';
            
            // Test 4: Logging de éxito
            Vehicle_Debug_Handler::log_success('Test operation', ['result' => 'success']);
            $test_results['success_logging'] = 'Completado';
            
            // Test 5: Logging de seguridad
            Vehicle_Debug_Handler::log_security_event('Test security event');
            $test_results['security_logging'] = 'Completado';
            
            // Test 6: Logging de rendimiento
            $start_time = microtime(true);
            usleep(100000); // 0.1 segundos
            Vehicle_Debug_Handler::log_performance('Test performance', $start_time);
            $test_results['performance_logging'] = 'Completado';
            
            // Test 7: Estadísticas
            $stats = Vehicle_Debug_Handler::get_debug_stats();
            $test_results['stats_retrieval'] = $stats;
            
            return new WP_REST_Response([
                'status' => 'success',
                'message' => 'Tests del debug handler completados',
                'test_results' => $test_results,
                'timestamp' => current_time('mysql'),
                'note' => 'Verifica los logs para confirmar que se registraron correctamente'
            ], 200);
        },
        'permission_callback' => '__return_true'
    ]);
});
