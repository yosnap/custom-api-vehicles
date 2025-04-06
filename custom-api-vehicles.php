<?php
/*
Plugin Name: Custom API Vehicles for Motoraldia
Description: API personalizada para gestionar vehículos de Motoraldia
Version: 1.7.6
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
        error_log("Inicializando Custom API Vehicles...");
        
        // Asegurarnos de que los endpoints REST son accesibles
        add_filter('rest_authentication_errors', array($this, 'fix_rest_api_permissions'), 100);
        
        // Cargar dependencias
        $this->load_dependencies();
        
        error_log("Custom API Vehicles inicializado correctamente");
    }
    
    /**
     * Elimina restricciones de la API REST que puedan causar error 403
     */
    public function fix_rest_api_permissions($errors) {
        // Durante la depuración, permite todas las solicitudes a nuestra API
        $current_route = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (strpos($current_route, $this->get_api_namespace()) !== false) {
            return null; // Permitir todas las solicitudes a nuestro namespace
        }
        return $errors;
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
        error_log('Error: JetEngine no está disponible');
    }
}, 20);

// Inicializar el menú de administración
if (is_admin()) {
    $admin_menu = new Vehicles_Admin_Menu();
}

// Scripts y estilos
function enqueue_custom_scripts() {
    if (!is_admin()) {
        wp_register_script('react-js', plugins_url('js/react.js', __FILE__), array(), '1.0.0', true);
        wp_register_script('react-product-js', plugins_url('js/react-product.js', __FILE__), array('react-js'), '1.0.0', true);
        wp_enqueue_script('my-react-app', plugins_url('js/my-react-app.js', __FILE__), array('react-product-js'), '1.0.0', true);
        wp_enqueue_style('my-product-css', plugins_url('css/my-product.css', __FILE__), array(), '1.0.0', 'all');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

// Scripts del admin
function enqueue_admin_scripts() {
    wp_register_script('react-js', plugins_url('js/react.js', __FILE__), array(), '1.0.0', true);
    wp_register_script('react-product-js', plugins_url('js/react-product.js', __FILE__), array('react-js'), '1.0.0', true);
    wp_enqueue_script('my-react-app', plugins_url('js/my-react-app.js', __FILE__), array('react-product-js'), '1.0.0', true);
    wp_enqueue_style('my-product-css', plugins_url('css/my-product.css', __FILE__), array(), '1.0.0', 'all');
}
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');

// Añadir la acción para depurar cualquier error en la API REST
add_action('rest_api_init', function() {
    // Depurar errores específicos de la API REST
    add_filter('rest_request_before_callbacks', function($response, $handler, $request) {
        if (strpos($request->get_route(), 'api-motor/v1') !== false) {
            error_log('REST API Debug - Endpoint solicitado: ' . $request->get_route());
            
            // Verificar si hay errores de permisos
            if (is_wp_error($response)) {
                error_log('REST API Error: ' . $response->get_error_message());
            }
        }
        return $response;
    }, 10, 3);
}, 999);
