<?php
/**
 * Clase para manejar el registro de debug
 * 
 * Esta clase proporciona métodos para manejar los mensajes de debug
 * y redirigirlos al sistema de registro propio del plugin
 */

class Vehicle_Debug_Handler {
    
    /**
     * Registra un mensaje de debug
     * 
     * Esta función no hace nada por defecto, pero puede ser modificada
     * para redirigir los mensajes al sistema de registro propio del plugin
     * 
     * @param string $message Mensaje a registrar
     * @return bool Siempre devuelve true
     */
    public static function log($message) {
        // No hacer nada - los mensajes de debug son ignorados
        // O redirigir a nuestro propio sistema de registro si es necesario
        
        // Si quieres registrar mensajes críticos, puedes usar el logger del plugin:
        // if (strpos($message, 'ERROR CRÍTICO') !== false) {
        //     Vehicle_API_Logger::get_instance()->log_action(0, 'error', $message);
        // }
        
        return true;
    }
}
