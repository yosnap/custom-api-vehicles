<?php
/*
Plugin Name: Custom API Vehicles
Description: API personalizada para gestionar vehículos
Version: 1.3
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
