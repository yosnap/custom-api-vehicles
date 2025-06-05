<?php
/*
Plugin Name: Custom API Vehicles for Motoraldia
Description: API personalizada para gestionar vehículos de Motoraldia
Version: 2.3.0
Author: Sn4p.dev
*/

// No direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del plugin
 */
class Custom_API_Vehicles {

    /**
     * Constructor
     */
    public function __construct() {
        // Asegurarnos de que los endpoints REST son accesibles
        add_filter('rest_authentication_errors', array($this, 'fix_rest_api_permissions'), 100);

        // Cargar dependencias
        $this->load_dependencies();
    }

    /**
     * Elimina restricciones de la API REST que puedan causar error 403
     * VERSIÓN MEJORADA CON SEGURIDAD
     */
    public function fix_rest_api_permissions($errors) {
        // Obtener la ruta actual de la solicitud
        $current_route = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // Solo permitir acceso libre a nuestro namespace específico
        if (strpos($current_route, $this->get_api_namespace()) !== false) {
            
            // Verificar si es una ruta que requiere autenticación
            $protected_routes = [
                '/vehicles', // POST, PUT, DELETE requieren auth
                '/debug-fields',
                '/sellers'
            ];
            
            $method = $_SERVER['REQUEST_METHOD'] ?? '';
            
            foreach ($protected_routes as $route) {
                if (strpos($current_route, $route) !== false) {
                    // Permitir GET sin autenticación para la mayoría de endpoints
                    if ($method === 'GET' && !strpos($current_route, '/debug-fields')) {
                        return null;
                    }
                    
                    // Para métodos que modifican datos, verificar autenticación
                    if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                        if (!is_user_logged_in()) {
                            return new WP_Error(
                                'rest_not_logged_in',
                                'Debes estar autenticado para realizar esta acción.',
                                array('status' => 401)
                            );
                        }
                    }
                    
                    return null; // Permitir la solicitud
                }
            }
            
            return null; // Permitir todas las solicitudes a nuestro namespace no protegidas
        }
        
        return $errors; // Mantener restricciones para otros namespaces
    }

    /**
     * Devuelve el namespace de la API
     */
    private function get_api_namespace() {
        return 'api-motor/v1';
    }

    /**
     * Carga dependencias necesarias
     */
    private function load_dependencies() {
        // Incluir archivos necesarios en orden correcto
        require_once plugin_dir_path(__FILE__) . 'includes/class-dependencies.php';        // Nueva validación de dependencias
        require_once plugin_dir_path(__FILE__) . 'admin/class-admin-menu.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-api-logger.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-debug-handler.php';      // Handler de debug mejorado
        require_once plugin_dir_path(__FILE__) . 'includes/class-diagnostic-helpers.php'; // Helpers para diagnóstico
        require_once plugin_dir_path(__FILE__) . 'includes/enhanced-diagnostic-endpoint.php'; // Endpoint de diagnóstico mejorado
        require_once plugin_dir_path(__FILE__) . 'includes/debug-management-endpoints.php'; // Endpoints de gestión de debug
        require_once plugin_dir_path(__FILE__) . 'includes/class-vehicle-fields.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-glossary-fields.php';
        require_once plugin_dir_path(__FILE__) . 'admin/class-glossary-mappings.php';
        require_once plugin_dir_path(__FILE__) . 'includes/taxonomy-endpoints.php';
        require_once plugin_dir_path(__FILE__) . 'includes/singlecar-endpoint.php';       // Funciones de vehículos
        require_once plugin_dir_path(__FILE__) . 'includes/author-endpoint.php';          // Funciones de autores
        require_once plugin_dir_path(__FILE__) . 'includes/singlecar-endpoints/routes.php';  // Rutas

        // Cargar el controlador de vehículos
        require_once plugin_dir_path(__FILE__) . 'includes/api/class-vehicle-controller.php';

        // Cargar el nuevo endpoint para opciones de glosario
        require_once plugin_dir_path(__FILE__) . 'includes/api/get-glossary-options-endpoint.php';
        
        // Cargar script de verificación post-instalación (temporal - remover en producción)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            require_once plugin_dir_path(__FILE__) . 'installation-check.php';
            require_once plugin_dir_path(__FILE__) . 'debug-handler-test.php';
        }
    }
}

// Inicializar el plugin
$custom_api_vehicles = new Custom_API_Vehicles();

// Sustituir la función original por la nueva versión mejorada
add_action('admin_init', 'check_vehicle_plugin_dependencies');

// Agregar función de activación del plugin
function activate_vehicle_api_plugin()
{
    // Crear tabla de logs
    Vehicle_API_Logger::get_instance()->create_log_table();

    // Limpiar caché de rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'activate_vehicle_api_plugin');

// Modificar la inicialización de las clases
add_action('plugins_loaded', function () {
    if (function_exists('jet_engine')) {
        Vehicle_Fields::get_instance();
        Glossary_Fields::get_instance();
        
        // Programar limpieza automática de logs
        if (!wp_next_scheduled('vehicle_api_cleanup_logs')) {
            wp_schedule_event(time(), 'weekly', 'vehicle_api_cleanup_logs');
        }
    } else {
        Vehicle_Debug_Handler::log('Error: JetEngine no está disponible', 'error');
    }
}, 20);

// Inicializar el menú de administración
if (is_admin()) {
    $admin_menu = new Vehicles_Admin_Menu();
}

// Scripts y estilos
function enqueue_custom_scripts() {
    if (!is_admin()) {
        // Se eliminaron las referencias a los archivos JS y CSS inexistentes
        // que estaban causando errores 404
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

// Scripts del admin
function enqueue_admin_scripts() {
    // Se eliminaron las referencias a los archivos JS y CSS inexistentes
    // que estaban causando errores 404
}
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');

// Añadir la acción para depurar cualquier error en la API REST
add_action('rest_api_init', function() {
    // Depurar errores específicos de la API REST
    add_filter('rest_request_before_callbacks', function($response, $handler, $request) {
        if (strpos($request->get_route(), 'api-motor/v1') !== false) {
            // Verificar si hay errores de permisos
            if (is_wp_error($response)) {
                Vehicle_Debug_Handler::log('REST API Error: ' . $response->get_error_message());
            }
        }
        return $response;
    }, 10, 3);
}, 999);

// Agregar acción para limpieza automática de logs
add_action('vehicle_api_cleanup_logs', function() {
    Vehicle_Debug_Handler::cleanup_old_logs(30);
    Vehicle_Debug_Handler::log_success('Limpieza automática de logs', 'Limpieza semanal ejecutada');
});

// Desactivar eventos programados al desactivar el plugin
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('vehicle_api_cleanup_logs');
});
