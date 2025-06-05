<?php
/**
 * Script de verificación post-instalación
 * Para ejecutar después de implementar las mejoras
 */

function vehicle_api_post_installation_check() {
    $results = [];
    
    // 1. Verificar que las nuevas clases existen
    $required_classes = [
        'Vehicle_Plugin_Dependencies',
        'DiagnosticHelpers',
        'Vehicle_Debug_Handler'
    ];
    
    foreach ($required_classes as $class) {
        $results['classes'][$class] = class_exists($class);
    }
    
    // 2. Verificar que los nuevos archivos existen
    $required_files = [
        'includes/class-dependencies.php',
        'includes/class-diagnostic-helpers.php',
        'includes/enhanced-diagnostic-endpoint.php',
        'config-example.php'
    ];
    
    $plugin_path = plugin_dir_path(__FILE__);
    foreach ($required_files as $file) {
        $results['files'][$file] = file_exists($plugin_path . $file);
    }
    
    // 3. Verificar endpoints
    $results['endpoints'] = [
        'diagnostic_registered' => rest_url('api-motor/v1/diagnostic'),
        'vehicles_registered' => rest_url('api-motor/v1/vehicles')
    ];
    
    // 4. Verificar dependencias
    if (class_exists('Vehicle_Plugin_Dependencies')) {
        $results['dependencies'] = Vehicle_Plugin_Dependencies::get_diagnostic_info();
    }
    
    // 5. Verificar permisos de API
    $results['api_security'] = [
        'rest_api_available' => function_exists('rest_url'),
        'authentication_working' => is_user_logged_in(),
        'current_user_can_edit' => current_user_can('edit_posts')
    ];
    
    return $results;
}

// Endpoint temporal para verificar la instalación
add_action('rest_api_init', function() {
    register_rest_route('api-motor/v1', '/installation-check', [
        'methods' => 'GET',
        'callback' => function() {
            if (!current_user_can('administrator')) {
                return new WP_REST_Response(['error' => 'Solo administradores'], 403);
            }
            
            return new WP_REST_Response([
                'status' => 'success',
                'message' => 'Verificación post-instalación completada',
                'timestamp' => current_time('mysql'),
                'results' => vehicle_api_post_installation_check()
            ], 200);
        },
        'permission_callback' => '__return_true'
    ]);
});
