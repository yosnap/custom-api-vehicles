<?php
// Registrar las rutas REST API para taxonomías
add_action('rest_api_init', function () {
    // Mapeo de endpoints a taxonomías
    $taxonomy_mapping = [
        'tipus-de-vehicle' => 'types-of-transport',
        'marques-cotxe' => 'marques-coches',      // Cambiado de 'marques-coches'
        'estat-vehicle' => 'estat-vehicle',
        'tipus-combustible' => 'tipus-combustible',
        'marques-de-moto' => 'marques-de-moto',
        'tipus-canvi-cotxe' => 'tipus-de-canvi',  // Cambiado de 'tipus-de-canvi'
        'tipus-de-propulsor' => 'tipus-de-propulsor'
    ];

    foreach ($taxonomy_mapping as $endpoint => $taxonomy) {
        register_rest_route('api-motor/v1', '/' . $endpoint, [
            'methods' => 'GET',
            'callback' => function ($request) use ($taxonomy) {
                if ($taxonomy === 'marques-coches') {  // Mantener la comparación con el nombre de la taxonomía original
                    $marca = $request->get_param('marca');
                    return get_marques_coches_hierarchy($marca);
                }
                return get_taxonomy_terms($taxonomy);
            },
            'permission_callback' => '__return_true',
        ]);
    }
});

/**
 * Obtener términos de marques-coches con jerarquía
 * @param string|null $marca Slug de la marca para filtrar sus modelos
 */
function get_marques_coches_hierarchy($marca = null)
{
    // Si se proporciona una marca, obtener su ID
    $marca_id = 0;
    if ($marca) {
        $marca_term = get_term_by('slug', $marca, 'marques-coches');
        if (!$marca_term) {
            return new WP_Error(
                'invalid_marca',
                'La marca especificada no existe',
                ['status' => 400]
            );
        }
        $marca_id = $marca_term->term_id;
    }

    // Obtener términos según la marca especificada
    $terms = get_terms([
        'taxonomy' => 'marques-coches',
        'hide_empty' => false,
        'parent' => $marca_id
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
        $modelos = get_terms([
            'taxonomy' => 'marques-coches',
            'hide_empty' => false,
            'parent' => $term->term_id
        ]);

        $modelos_data = [];
        if (!is_wp_error($modelos)) {
            foreach ($modelos as $modelo) {
                $modelos_data[] = [
                    'name' => $modelo->name,
                    'value' => $modelo->slug
                ];
            }
        }

        $term_data = [
            'name' => $term->name,
            'value' => $term->slug
        ];

        // Solo incluir modelos si la marca tiene modelos
        if (!empty($modelos_data)) {
            $term_data['modelos'] = $modelos_data;
        }

        $terms_data[] = $term_data;
    }

    $response = [
        'total' => count($terms),
        'terms' => $terms_data
    ];

    return new WP_REST_Response($response, 200);
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
        'total' => count($terms),
        'terms' => $terms_data
    ];

    return new WP_REST_Response($response, 200);
}
