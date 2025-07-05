<?php
/*
Plugin Name: Custom API Vehicles
Plugin URI: https://github.com/yosnap/custom-api-vehicles
Description: API personalizada para gestión de vehículos en WordPress.
Version: 2.2.1
Author: Yosnap
Author URI: https://github.com/yosnap
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
     */
    public function fix_rest_api_permissions($result) {
        // Si el usuario ya está autenticado, no hacer nada.
        if (!empty($result)) {
            return $result;
        }

        // Permitir que las solicitudes a nuestros endpoints específicos pasen
        // para que los permission_callback de cada ruta se encarguen de la autorización.
        $current_route = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (strpos($current_route, $this->get_api_namespace()) !== false) {
            // Si no hay un usuario logueado, y es una ruta de nuestra API,
            // WordPress por defecto bloquearía la petición. Devolvemos null
            // para que la comprobación de permisos se delegue al endpoint.
            // Esto es necesario para que Basic Auth funcione.
            if (!is_user_logged_in()) {
                return null;
            }
        }

        // Para cualquier otra ruta de la API, si no hay usuario, se devuelve el error por defecto.
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                'No estás conectado.',
                array('status' => 401)
            );
        }
        
        return $result;
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
        require_once plugin_dir_path(__FILE__) . 'admin/class-admin-menu.php';          
        require_once plugin_dir_path(__FILE__) . 'includes/class-api-logger.php';       
        require_once plugin_dir_path(__FILE__) . 'includes/class-debug-handler.php';     // Nuevo manejador de debug
        require_once plugin_dir_path(__FILE__) . 'includes/class-vehicle-fields.php';   
        require_once plugin_dir_path(__FILE__) . 'includes/class-glossary-fields.php';
        require_once plugin_dir_path(__FILE__) . 'admin/class-glossary-mappings.php';
        require_once plugin_dir_path(__FILE__) . 'includes/taxonomy-endpoints.php';      
        require_once plugin_dir_path(__FILE__) . 'includes/singlecar-endpoint.php';     // Funciones de vehículos
        require_once plugin_dir_path(__FILE__) . 'includes/author-endpoint.php';        // Funciones de autores
        require_once plugin_dir_path(__FILE__) . 'includes/singlecar-endpoints/routes.php';  // Rutas
        
        // Cargar el controlador de vehículos
        require_once plugin_dir_path(__FILE__) . 'includes/api/class-vehicle-controller.php';
        
        // Cargar el nuevo endpoint para opciones de glosario
        require_once plugin_dir_path(__FILE__) . 'includes/api/get-glossary-options-endpoint.php';
    }
}

// Inicializar el plugin
$custom_api_vehicles = new Custom_API_Vehicles();

// Agregar verificación de dependencias
function check_plugin_dependencies() {
    if (!function_exists('jet_engine')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>Custom API Vehicles requiere JetEngine para funcionar correctamente.</p></div>';
        });
    }
}
add_action('admin_init', 'check_plugin_dependencies');

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
    } else {
        Vehicle_Debug_Handler::log('Error: JetEngine no está disponible');
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
