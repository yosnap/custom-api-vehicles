<?php
/**
 * Clase para manejar el registro personalizado
 * 
 * Esta clase reemplaza la función error_log estándar de PHP
 * para redirigir los mensajes al sistema de registro propio del plugin
 */

class Custom_Vehicle_Logger {
    
    /**
     * Inicializa el logger personalizado
     */
    public static function init() {
        // Redefine la función error_log en el espacio de nombres global
        if (!function_exists('custom_error_log_replacement')) {
            /**
             * Reemplazo para error_log que no hace nada o redirige a nuestro propio sistema
             * 
             * @param string $message Mensaje a registrar
             * @param int $message_type Tipo de mensaje (ignorado)
             * @param string $destination Destino (ignorado)
             * @param string $extra_headers Cabeceras adicionales (ignorado)
             * @return bool Siempre devuelve true
             */
            function custom_error_log_replacement($message, $message_type = 0, $destination = null, $extra_headers = null) {
                // No hacer nada - los mensajes de debug son ignorados
                // O redirigir a nuestro propio sistema de registro si es necesario
                
                // Si quieres registrar mensajes críticos, puedes usar el logger del plugin:
                // if (strpos($message, 'ERROR CRÍTICO') !== false) {
                //     Vehicle_API_Logger::get_instance()->log_action(0, 'error', $message);
                // }
                
                return true;
            }
        }
        
        // Reemplazar la función error_log global con nuestra versión
        // Nota: Esto solo funciona en ciertos contextos y configuraciones de PHP
        runkit7_function_redefine('error_log', 'custom_error_log_replacement');
    }
}
