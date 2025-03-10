<?php
// Cargar todos los archivos necesarios
$base_path = plugin_dir_path(__FILE__) . 'singlecar-endpoints/';

require_once $base_path . 'routes.php';
require_once $base_path . 'get-handlers.php';
require_once $base_path . 'post-handlers.php';
require_once $base_path . 'put-handlers.php';
require_once $base_path . 'delete-handlers.php';
require_once $base_path . 'field-processors.php';
require_once $base_path . 'meta-handlers.php';
require_once $base_path . 'media-handlers.php';
require_once $base_path . 'validation.php';
require_once $base_path . 'utils.php';

// Cargar endpoints de vendedores desde la ubicación correcta
require_once plugin_dir_path(__FILE__) . 'seller-endpoints/get-handlers.php';

// Incluir dependencias de WordPress
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

/**
 * Valida los campos del vehículo antes de procesar la solicitud
 *
 * @param array $data Los datos de la solicitud
 * @return bool|WP_Error True si los datos son válidos, WP_Error en caso contrario
 */
function validate_vehicle_data($data) {
    // Validar el tipo de vehículo
    if (empty($data['tipus-vehicle'])) {
        return new WP_Error(
            'missing_required_field',
            'El campo tipus-vehicle es obligatorio',
            array('status' => 400)
        );
    }
    
    // Normalizar y mapear el tipo de vehículo
    $original_type = $data['tipus-vehicle'];
    $data['tipus-vehicle'] = normalize_vehicle_type($data['tipus-vehicle']);
    
    // Normalizar el tipo de vehículo a minúsculas para comparación consistente
    $vehicle_type = strtolower(trim($data['tipus-vehicle']));
    
    // Simplificar el tipo para validaciones internas
    $simplified_type = $vehicle_type;
    if ($vehicle_type === 'moto-quad-atv') {
        $simplified_type = 'moto';
    }
    
    // Definir campos obligatorios según el tipo de vehículo
    $required_fields = [
        'tipus-vehicle',
        'tipus-combustible',
        'tipus-propulsor',
        'estat-vehicle',
        'preu'
    ];
    
    // Añadir tipus-canvi-cotxe como obligatorio solo si NO es moto
    if ($simplified_type !== 'moto') {
        $required_fields[] = 'tipus-canvi-cotxe';
    }
    
    // Validar versio para todos excepto MOTO
    if ($simplified_type !== 'moto' && empty($data['versio'])) {
        return new WP_Error(
            'missing_required_field',
            'El campo versio es obligatorio para vehículos que no son motos',
            array('status' => 400)
        );
    }
    
    // Validar marques-cotxe y models-cotxe para todos excepto MOTO
    if ($simplified_type !== 'moto') {
        if (empty($data['marques-cotxe'])) {
            return new WP_Error(
                'missing_required_field',
                'El campo marques-cotxe es obligatorio para vehículos que no son motos',
                array('status' => 400)
            );
        }
        
        if (empty($data['models-cotxe'])) {
            return new WP_Error(
                'missing_required_field',
                'El campo models-cotxe es obligatorio para vehículos que no son motos',
                array('status' => 400)
            );
        }
    }
    
    // Validación específica según el tipo de vehículo
    if ($vehicle_type === 'moto' && empty($data['marques-de-moto'])) {
        return new WP_Error(
            'missing_required_field',
            'El campo marques-de-moto es obligatorio',
            array('status' => 400)
        );
    }
    
    if ($vehicle_type === 'autocaravana' && empty($data['marques-autocaravana'])) {
        return new WP_Error(
            'missing_required_field',
            'El campo marques-autocaravana es obligatorio',
            array('status' => 400)
        );
    }
    
    if ($vehicle_type === 'vehicle-comercial' && empty($data['marques-comercial'])) {
        return new WP_Error(
            'missing_required_field',
            'El campo marques-comercial es obligatorio',
            array('status' => 400)
        );
    }
    
    // Otras validaciones según sea necesario
    
    return true;
}

/**
 * Normaliza el tipo de vehículo a un slug válido
 */
function normalize_vehicle_type($type) {
    $type = strtolower(trim($type));
    
    // Mapeo de valores comunes a slugs correctos
    $common_mappings = [
        'moto' => 'moto-quad-atv',
        'coche' => 'cotxe',
        'cotxe' => 'cotxe',
        'autocaravana' => 'autocaravana-camper',
        'camper' => 'autocaravana-camper',
        'caravana' => 'autocaravana-camper',
        'comercial' => 'vehicle-comercial',
        'furgoneta' => 'vehicle-comercial',
        'vehicle comercial' => 'vehicle-comercial',
        'vehículo comercial' => 'vehicle-comercial'
    ];
    
    // Si hay un mapeo directo, usarlo
    if (isset($common_mappings[$type])) {
        return $common_mappings[$type];
    }
    
    // Si no hay un mapeo exacto, buscar coincidencias parciales
    foreach ($common_mappings as $common => $slug) {
        if (strpos($type, $common) !== false) {
            return $slug;
        }
    }
    
    // Si no se encontró un mapeo, mantener el valor original
    return $type;
}

/**
 * Handler para el endpoint de creación de vehículos
 */
function handle_create_vehicle_request($request) {
    $data = $request->get_params();
    
    // Validar los datos antes de continuar
    $validation_result = validate_vehicle_data($data);
    if (is_wp_error($validation_result)) {
        return $validation_result;
    }
    
    // Continuar con el proceso de creación del vehículo
    // ...existing code...
}

// Asegurarse de que el hook para registrar nuestras validaciones esté activo
add_action('rest_api_init', function() {
    register_rest_route('api-motor/v1', '/vehicles', array(
        'methods' => 'POST',
        'callback' => 'handle_create_vehicle_request',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    // Otros endpoints según sea necesario
});
