<?php

function register_taxonomy_endpoints() {
    // Mapeo de endpoints a taxonomías
    $taxonomy_mapping = [
        'tipus-vehicle' => 'types-of-transport',
        'marques-cotxe' => 'marques-coches',
        'marques-moto' => 'marques-de-moto',    // Asegurarnos de que este mapeo esté correcto
        'estat-vehicle' => 'estat-vehicle',
        'tipus-combustible' => 'tipus-combustible',
        'tipus-canvi-cotxe' => 'tipus-de-canvi',
        'tipus-propulsor' => 'tipus-de-propulsor'
    ];

    // Registrar rutas para cada taxonomía
    foreach ($taxonomy_mapping as $endpoint => $taxonomy) {
        register_rest_route('api-motor/v1', '/' . $endpoint, [
            [
                'methods' => 'GET',
                'callback' => function(WP_REST_Request $request) use ($taxonomy, $endpoint) {
                    Vehicle_Debug_Handler::log("Procesando petición para endpoint: $endpoint, taxonomía: $taxonomy");
                    
                    // Manejar tanto marcas de coches como de motos
                    if (in_array($taxonomy, ['marques-coches', 'marques-de-moto'])) {
                        $marca = $request->get_param('marca');
                        Vehicle_Debug_Handler::log("Parámetro marca recibido: " . ($marca ? $marca : 'no marca'));
                        return get_brand_models_hierarchy($taxonomy, $marca);
                    }
                    
                    // Manejar otras taxonomías
                    return get_taxonomy_terms($taxonomy);
                },
                'permission_callback' => '__return_true',
                'args' => [
                    'marca' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
    }
}

function get_brand_models_hierarchy($taxonomy, $marca = null) {
    try {
        Vehicle_Debug_Handler::log("Obteniendo jerarquía para $taxonomy" . ($marca ? " con marca: $marca" : ""));
        
        if ($marca) {
            $parent_term = get_term_by('slug', $marca, $taxonomy);
            if (!$parent_term) {
                return new WP_REST_Response([
                    'status' => 'error',
                    'message' => "Marca no encontrada: $marca"
                ], 404);
            }
            
            // Obtener modelos de la marca
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'parent' => $parent_term->term_id
            ]);
        } else {
            // Obtener solo marcas
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'parent' => 0
            ]);
        }

        if (is_wp_error($terms)) {
            throw new Exception($terms->get_error_message());
        }

        $formatted_terms = array_map(function($term) {
            return [
                'value' => $term->slug,
                'label' => $term->name
            ];
        }, $terms);

        return new WP_REST_Response([
            'status' => 'success',
            'total' => count($formatted_terms),
            'data' => $formatted_terms
        ], 200);

    } catch (Exception $e) {
        Vehicle_Debug_Handler::log("Error en get_brand_models_hierarchy: " . $e->getMessage());
        return new WP_REST_Response([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

/**
 * Obtener términos de una taxonomía
 */
function get_taxonomy_terms($taxonomy)
{
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
        return new WP_Error(
            'taxonomy_error',
            'Error al obtener los términos de la taxonomía',
            ['status' => 500]
        );
    }

    $terms_data = [];
    foreach ($terms as $term) {
        $value = $term->slug;

        // Para tipus-combustible, eliminar el prefijo "combustible-"
        if ($taxonomy === 'tipus-combustible' && strpos($value, 'combustible-') === 0) {
            $value = str_replace('combustible-', '', $value);
        }

        $terms_data[] = [
            'name' => $term->name,
            'value' => $value
        ];
    }

    $response = [
        'status' => 'success',
        'total' => count($terms_data),
        'data' => $terms_data
    ];

    return new WP_REST_Response($response, 200);
}

add_action('rest_api_init', 'register_taxonomy_endpoints');
