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
        // Debug temporal para filtro anunci-actiu
        if (strpos($message, 'anunci-actiu') !== false || strpos($message, 'Query') !== false) {
            error_log('[Vehicle Debug] ' . $message);
        }
        return true;
    }
}
