<?php
/**
 * Ejemplos de uso del Debug Handler mejorado
 * 
 * Este archivo muestra cómo usar los diferentes métodos
 * del Vehicle_Debug_Handler mejorado
 */

// NO INCLUIR EN PRODUCCIÓN - Solo para referencia

/**
 * Ejemplo 1: Logging básico con niveles
 */
function example_basic_logging() {
    // Error crítico - siempre se registra
    Vehicle_Debug_Handler::log('Error crítico en el sistema', 'error');
    
    // Warning - se registra si debug está habilitado
    Vehicle_Debug_Handler::log('Advertencia: valor inesperado', 'warning');
    
    // Info - solo en modo debug
    Vehicle_Debug_Handler::log('Información general', 'info');
    
    // Debug - solo en modo debug avanzado
    Vehicle_Debug_Handler::log('Detalles técnicos', 'debug');
}

/**
 * Ejemplo 2: Logging de errores de API
 */
function example_api_error_logging() {
    $endpoint = '/vehicles';
    $error_message = 'Falló la validación de campos obligatorios';
    $request_data = ['title' => '', 'price' => 'invalid'];
    
    Vehicle_Debug_Handler::log_api_error($endpoint, $error_message, $request_data);
}

/**
 * Ejemplo 3: Logging de validación
 */
function example_validation_logging() {
    $field = 'price';
    $value = 'abc123';
    $expected = 'número válido';
    
    Vehicle_Debug_Handler::log_validation_error($field, $value, $expected);
}

/**
 * Ejemplo 4: Logging de eventos exitosos
 */
function example_success_logging() {
    $operation = 'Creación de vehículo';
    $details = ['id' => 123, 'title' => 'BMW X3 2024'];
    
    Vehicle_Debug_Handler::log_success($operation, $details);
}

/**
 * Ejemplo 5: Logging de eventos de seguridad
 */
function example_security_logging() {
    $event = 'Intento de acceso no autorizado a endpoint protegido';
    $user_id = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    Vehicle_Debug_Handler::log_security_event($event, $user_id, $ip);
}

/**
 * Ejemplo 6: Logging de rendimiento
 */
function example_performance_logging() {
    $start_time = microtime(true);
    
    // ... código que queremos medir ...
    sleep(1); // Simular operación lenta
    
    $operation = 'Procesamiento de imágenes';
    $additional_info = ['images_count' => 5, 'total_size' => '2.5MB'];
    
    Vehicle_Debug_Handler::log_performance($operation, $start_time, $additional_info);
}

/**
 * Ejemplo 7: Obtener estadísticas de debug
 */
function example_debug_stats() {
    $stats = Vehicle_Debug_Handler::get_debug_stats();
    
    // Usar las estadísticas para mostrar información en admin
    if (is_admin() && current_user_can('administrator')) {
        echo '<pre>';
        print_r($stats);
        echo '</pre>';
    }
}

/**
 * Ejemplo 8: Limpieza automática de logs antiguos
 */
function example_log_cleanup() {
    // Limpiar logs más antiguos de 30 días
    $result = Vehicle_Debug_Handler::cleanup_old_logs(30);
    
    if ($result !== false) {
        Vehicle_Debug_Handler::log_success('Limpieza de logs', "Eliminados $result registros antiguos");
    }
}

/**
 * Ejemplo 9: Usar en un endpoint de API
 */
function example_api_endpoint() {
    $start_time = microtime(true);
    
    try {
        // Verificar autenticación
        if (!is_user_logged_in()) {
            Vehicle_Debug_Handler::log_security_event('Acceso no autorizado a API');
            return new WP_Error('unauthorized', 'Acceso no autorizado', ['status' => 401]);
        }
        
        // Procesar datos
        $data = process_vehicle_data();
        
        // Log de éxito
        Vehicle_Debug_Handler::log_success('API call exitosa', ['endpoint' => '/vehicles']);
        
        return $data;
        
    } catch (Exception $e) {
        // Log de error
        Vehicle_Debug_Handler::log_api_error('/vehicles', $e->getMessage());
        return new WP_Error('processing_error', $e->getMessage(), ['status' => 500]);
        
    } finally {
        // Log de rendimiento
        Vehicle_Debug_Handler::log_performance('API endpoint /vehicles', $start_time);
    }
}

/**
 * Ejemplo 10: Activar limpieza automática
 */
function schedule_automatic_cleanup() {
    // Programar limpieza semanal
    if (!wp_next_scheduled('vehicle_api_cleanup_logs')) {
        wp_schedule_event(time(), 'weekly', 'vehicle_api_cleanup_logs');
    }
}

add_action('vehicle_api_cleanup_logs', function() {
    Vehicle_Debug_Handler::cleanup_old_logs(30);
});

// Activar al cargar el plugin
// add_action('init', 'schedule_automatic_cleanup');
