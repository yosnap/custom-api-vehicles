<?php
/**
 * Clase mejorada para manejar el registro de debug
 */
class Vehicle_Debug_Handler {
    
    private static $debug_enabled = null;
    
    /**
     * Verificar si el debug está habilitado
     */
    private static function is_debug_enabled() {
        if (self::$debug_enabled === null) {
            // Habilitar debug solo en entornos de desarrollo
            self::$debug_enabled = (
                defined('WP_DEBUG') && WP_DEBUG &&
                defined('WP_DEBUG_LOG') && WP_DEBUG_LOG
            ) || (
                // O si hay una constante específica del plugin
                defined('VEHICLE_API_DEBUG') && VEHICLE_API_DEBUG
            );
        }
        return self::$debug_enabled;
    }
    
    /**
     * Registra un mensaje de debug
     * 
     * @param string $message Mensaje a registrar
     * @param string $level Nivel de log: 'error', 'warning', 'info', 'debug'
     * @return bool
     */
    public static function log($message, $level = 'info') {
        
        // Siempre registrar errores críticos
        if ($level === 'error' || strpos($message, 'ERROR') !== false) {
            if (class_exists('Vehicle_API_Logger')) {
                Vehicle_API_Logger::get_instance()->log_action(0, 'error', $message);
            }
            
            // También al log de WordPress si está habilitado
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Vehicle API Error] ' . $message);
            }
            return true;
        }
        
        // Para otros niveles, solo si debug está habilitado
        if (!self::is_debug_enabled()) {
            return true;
        }
        
        // Formatear el mensaje con timestamp y nivel
        $formatted_message = sprintf(
            '[%s] [%s] %s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );
        
        // Registrar según el nivel
        switch ($level) {
            case 'warning':
                if (class_exists('Vehicle_API_Logger')) {
                    Vehicle_API_Logger::get_instance()->log_action(0, 'warning', $message);
                }
                break;
                
            case 'info':
            case 'debug':
            default:
                if (class_exists('Vehicle_API_Logger')) {
                    Vehicle_API_Logger::get_instance()->log_action(0, 'info', $message);
                }
                break;
        }
        
        return true;
    }
    
    /**
     * Log específico para errores de API
     */
    public static function log_api_error($endpoint, $error_message, $request_data = null) {
        $message = "API Error en $endpoint: $error_message";
        if ($request_data) {
            $message .= " | Datos: " . json_encode($request_data);
        }
        self::log($message, 'error');
    }
    
    /**
     * Log específico para validaciones fallidas
     */
    public static function log_validation_error($field, $value, $expected) {
        $message = "Validación fallida - Campo: $field, Valor: $value, Esperado: $expected";
        self::log($message, 'warning');
    }
    
    /**
     * Log para operaciones exitosas importantes
     */
    public static function log_success($operation, $details = null) {
        $message = "Operación exitosa: $operation";
        if ($details) {
            $message .= " | Detalles: " . (is_array($details) ? json_encode($details) : $details);
        }
        self::log($message, 'info');
    }
    
    /**
     * Log para eventos de seguridad
     */
    public static function log_security_event($event, $user_id = null, $ip_address = null) {
        $user_id = $user_id ?: get_current_user_id();
        $ip_address = $ip_address ?: (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown');
        
        $message = "Evento de seguridad: $event | Usuario: $user_id | IP: $ip_address";
        self::log($message, 'warning');
    }
    
    /**
     * Log para rendimiento (tiempo de ejecución)
     */
    public static function log_performance($operation, $start_time, $additional_info = null) {
        $execution_time = microtime(true) - $start_time;
        $message = "Rendimiento - $operation: {$execution_time}s";
        
        if ($additional_info) {
            $message .= " | Info: " . (is_array($additional_info) ? json_encode($additional_info) : $additional_info);
        }
        
        // Solo registrar si es lento (más de 1 segundo) o si debug está habilitado
        if ($execution_time > 1.0 || self::is_debug_enabled()) {
            $level = $execution_time > 2.0 ? 'warning' : 'info';
            self::log($message, $level);
        }
    }
    
    /**
     * Obtener estadísticas de uso del debug
     */
    public static function get_debug_stats() {
        return [
            'debug_enabled' => self::is_debug_enabled(),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'vehicle_api_debug' => defined('VEHICLE_API_DEBUG') && VEHICLE_API_DEBUG,
            'logger_available' => class_exists('Vehicle_API_Logger'),
            'log_file_writable' => is_writable(WP_CONTENT_DIR)
        ];
    }
    
    /**
     * Limpiar logs antiguos (llamar ocasionalmente)
     */
    public static function cleanup_old_logs($days_old = 30) {
        if (!class_exists('Vehicle_API_Logger')) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vehicle_api_logs';
        
        $result = $wpdb->query($wpdb->prepare("
            DELETE FROM $table_name 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days_old));
        
        if ($result !== false) {
            self::log("Limpieza de logs completada: $result registros eliminados", 'info');
        }
        
        return $result;
    }
}
