<?php
/**
 * Fix para problemas de permisos en endpoints de API REST
 */

// Asegúrate de que este archivo sea incluido en el plugin principal o en functions.php

/**
 * Modifica el registro del endpoint para permitir acceso adecuado
 */
function fix_vehicle_types_endpoint_permissions() {
    // Elimina el endpoint actual si existe
    if (function_exists('unregister_rest_route')) {
        unregister_rest_route('api-motor/v1', '/vehicles/types-of-transport');
    }

    // Vuelve a registrar el endpoint con permisos correctos
    register_rest_route('api-motor/v1', '/vehicles/types-of-transport', array(
        'methods' => 'GET',
        'callback' => 'get_vehicle_types_callback', // Asegúrate de que esta función existe en tu código
        'permission_callback' => function() {
            return true; // Permite acceso público para pruebas iniciales
            // Para producción, puedes usar: return current_user_can('read');
        }
    ));
}
add_action('rest_api_init', 'fix_vehicle_types_endpoint_permissions', 99); // Prioridad alta para sobrescribir el registro original

/**
 * Si necesitas reemplazar la función de callback también
 */
function get_vehicle_types_callback($request) {
    // Implementación de ejemplo - reemplaza esto con tu lógica actual
    $types = array(
        array('id' => 1, 'name' => 'Coche'),
        array('id' => 2, 'name' => 'Moto'),
        array('id' => 3, 'name' => 'Furgoneta'),
        // Agrega los tipos que necesites
    );
    
    return rest_ensure_response($types);
}

/**
 * Función de diagnóstico para verificar permisos
 */
function diagnose_rest_api_permissions() {
    // Añade esto para depuración
    add_filter('rest_request_before_callbacks', function($response, $handler, $request) {
        if (strpos($request->get_route(), 'api-motor/v1/vehicles/types-of-transport') !== false) {
            error_log('REST API Debug - Request route: ' . $request->get_route());
            error_log('REST API Debug - Current user: ' . get_current_user_id());
            error_log('REST API Debug - User can read: ' . (current_user_can('read') ? 'true' : 'false'));
            
            // Inspeccionar el handler para ver el callback de permisos
            if (isset($handler['permission_callback'])) {
                error_log('REST API Debug - Has permission callback: true');
            } else {
                error_log('REST API Debug - Has permission callback: false');
            }
        }
        return $response;
    }, 10, 3);
}
add_action('init', 'diagnose_rest_api_permissions');

/**
 * Para forzar que todos los endpoints estén disponibles durante las pruebas
 * SOLO PARA DEPURACIÓN - Elimina en producción
 */
function debug_allow_all_rest_endpoints() {
    add_filter('rest_authentication_errors', function($errors) {
        // Log the errors for debugging
        if ($errors) {
            error_log('REST Auth Error: ' . print_r($errors, true));
        }
        return null; // Permite todas las solicitudes durante depuración
    });
}
// Descomenta la línea siguiente solo si necesitas depuración extrema
// add_action('init', 'debug_allow_all_rest_endpoints');
