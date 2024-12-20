<?php
/*
Plugin Name: Custom API Vehicles
Description: API personalizada para gestionar vehículos
Version: 1.0
Author: Your Name
*/

// No direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

// Incluir archivos de los endpoints
require_once plugin_dir_path(__FILE__) . 'includes/taxonomy-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'includes/singlecar-endpoint.php';

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
