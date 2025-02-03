<?php
/*
Plugin Name: Custom API Vehicles for Motoraldia
Description: API personalizada para gestionar vehículos de Motoraldia
Version: 1.6
Author: Sn4p.dev
*/

// No direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

// Incluir archivos necesarios en orden correcto
require_once plugin_dir_path(__FILE__) . 'admin/class-admin-menu.php';          // Primero la clase del menú admin
require_once plugin_dir_path(__FILE__) . 'includes/class-api-logger.php';       // Luego el logger
require_once plugin_dir_path(__FILE__) . 'includes/class-vehicle-fields.php';   // Clases de campos
require_once plugin_dir_path(__FILE__) . 'includes/class-glossary-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/taxonomy-endpoints.php';      // Endpoints
require_once plugin_dir_path(__FILE__) . 'includes/singlecar-endpoint.php';
require_once plugin_dir_path(__FILE__) . 'includes/author-endpoint.php';

// Agregar función de activación del plugin
function activate_vehicle_api_plugin()
{
    // Crear tabla de logs
    Vehicle_API_Logger::get_instance()->create_log_table();

    // Limpiar caché de rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'activate_vehicle_api_plugin');

// Inicializar las clases
add_action('plugins_loaded', function () {
    Vehicle_Fields::get_instance();
    Glossary_Fields::get_instance();
});

// Inicializar el menú de administración
if (is_admin()) {
    $admin_menu = new Vehicles_Admin_Menu();
}

// Registrar rutas de la API
add_action('rest_api_init', function () {
    // Ruta para obtener/crear vehículos
    register_rest_route('api-motor/v1', '/vehicles', [
        [
            'methods' => 'GET',
            'callback' => 'get_singlecar',
            'permission_callback' => '__return_true',
        ],
        [
            'methods' => 'POST',
            'callback' => 'create_singlecar',
            'permission_callback' => '__return_true',
        ]
    ]);

    // Ruta para actualizar/eliminar un vehículo específico
    register_rest_route('api-motor/v1', '/vehicles/(?P<id>\d+)', [
        [
            'methods' => 'PUT',
            'callback' => 'update_singlecar',
            'permission_callback' => '__return_true',
        ],
        [
            'methods' => 'DELETE',
            'callback' => 'delete_singlecar',
            'permission_callback' => '__return_true',
        ]
    ]);
});

// Corregir el registro de scripts
if (!function_exists('enqueue_custom_scripts')) {
    function enqueue_custom_scripts() {
        if (!is_admin()) { // Solo para el frontend
            wp_register_script('react-js', plugins_url('js/react.js', __FILE__), array(), '1.0.0', true);
            wp_register_script('react-product-js', plugins_url('js/react-product.js', __FILE__), array('react-js'), '1.0.0', true);
            wp_enqueue_script('my-react-app', plugins_url('js/my-react-app.js', __FILE__), array('react-product-js'), '1.0.0', true);
            wp_enqueue_style('my-product-css', plugins_url('css/my-product.css', __FILE__), array(), '1.0.0', 'all');
        }
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

// Para scripts del admin
function enqueue_admin_scripts() {
    wp_register_script('react-js', plugins_url('js/react.js', __FILE__), array(), '1.0.0', true);
    wp_register_script('react-product-js', plugins_url('js/react-product.js', __FILE__), array('react-js'), '1.0.0', true);
    wp_enqueue_script('my-react-app', plugins_url('js/my-react-app.js', __FILE__), array('react-product-js'), '1.0.0', true);
    wp_enqueue_style('my-product-css', plugins_url('css/my-product.css', __FILE__), array(), '1.0.0', 'all');
}
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');
