<?php
/**
 * Endpoint para obtener las opciones de cada campo de glosario
 */

// No direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra los endpoints para obtener opciones de glosario
 */
function register_glossary_options_routes() {
    register_rest_route('api-motor/v1', '/glossary-options', array(
        'methods' => 'GET',
        'callback' => 'get_all_glossary_options',
        'permission_callback' => '__return_true',
    ));
    
    register_rest_route('api-motor/v1', '/glossary-options/(?P<field>[\w-]+)', array(
        'methods' => 'GET',
        'callback' => 'get_single_glossary_options',
        'permission_callback' => '__return_true',
        'args' => array(
            'field' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param);
                }
            )
        )
    ));
}
add_action('rest_api_init', 'register_glossary_options_routes');

/**
 * Obtiene las opciones de todos los campos de glosario
 */
function get_all_glossary_options() {
    $glossary_fields = array(
        'traccio',
        'roda-recanvi',
        'color-vehicle',
        'segment',
        'tipus-tapisseria',
        'color-tapisseria',
        'emissions-vehicle',
        'cables-recarrega',
        'connectors',
        'extres-cotxe',
        'extres-moto',      // Añadido para motos
        'tipus-de-moto',    // Añadido para motos
        'marques-de-moto'   // Añadido para motos
    );
    
    $results = array();
    
    foreach ($glossary_fields as $field) {
        $results[$field] = get_glossary_field_options($field);
    }
    
    return new WP_REST_Response(array(
        'status' => 'success',
        'data' => $results
    ), 200);
}

/**
 * Obtiene las opciones para un campo de glosario específico
 */
function get_single_glossary_options($request) {
    $field = $request->get_param('field');
    
    $options = get_glossary_field_options($field);
    
    if (empty($options)) {
        return new WP_REST_Response(array(
            'status' => 'error',
            'message' => "No se encontraron opciones para el campo {$field}"
        ), 404);
    }
    
    return new WP_REST_Response(array(
        'status' => 'success',
        'field' => $field,
        'data' => $options
    ), 200);
}

/**
 * Obtiene las opciones para un campo de glosario
 */
function get_glossary_field_options($field) {
    switch ($field) {
        case 'traccio':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_traccio_options();
            }
            break;
            
        case 'roda-recanvi':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_roda_recanvi_options();
            }
            break;
            
        case 'color-vehicle':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_color_vehicle_options();
            }
            break;
            
        case 'segment':
        case 'carrosseria':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_carrosseria_options();
            }
            break;
            
        case 'tipus-tapisseria':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_tipus_tapisseria_options();
            }
            break;
            
        case 'color-tapisseria':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_color_tapisseria_options();
            }
            break;
            
        case 'emissions-vehicle':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_emissions_vehicle_options();
            }
            break;
            
        case 'cables-recarrega':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_cables_recarrega_options();
            }
            break;
            
        case 'connectors':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_connectors_options();
            }
            break;
            
        case 'extres-cotxe':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_extres_cotxe_options();
            }
            break;
            
        case 'extres-moto':
            if (class_exists('Vehicle_Fields')) {
                return Vehicle_Fields::get_extres_moto_options();
            }
            break;
            
        case 'marques-de-moto':
            // Obtener términos de la taxonomía marques-de-moto
            $terms = get_terms(array(
                'taxonomy' => 'marques-de-moto',
                'hide_empty' => false,
            ));
            
            $options = [];
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $options[$term->slug] = $term->name;
                }
            }
            return $options;
            
        case 'models-moto':
            // Obtener términos de la taxonomía models-moto
            $terms = get_terms(array(
                'taxonomy' => 'models-moto',
                'hide_empty' => false,
            ));
            
            $options = [];
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $options[$term->slug] = $term->name;
                }
            }
            return $options;
            
        default:
            // Intentar obtener desde JetEngine si es un glosario dinámico
            if (function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
                $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field);
                if ($glossary_id) {
                    return jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
                }
            }
            break;
    }
    
    return [];
}
