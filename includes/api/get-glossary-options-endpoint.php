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

// Endpoint: /wp-json/api-motor/v1/blog-posts
add_action('rest_api_init', function () {
    register_rest_route('api-motor/v1', '/blog-posts', [
        'methods' => 'GET',
        'callback' => 'api_motor_get_blog_posts',
        'permission_callback' => '__return_true',
    ]);
});

function api_motor_get_blog_posts($request) {
    $params = $request->get_params();
    $paged = isset($params['page']) ? max(1, intval($params['page'])) : 1;
    $per_page = isset($params['per_page']) ? max(1, intval($params['per_page'])) : 10;
    $orderby = isset($params['orderby']) && in_array($params['orderby'], ['date', 'title']) ? $params['orderby'] : 'date';
    $order = isset($params['order']) && in_array(strtoupper($params['order']), ['ASC', 'DESC']) ? strtoupper($params['order']) : 'DESC';
    $category = isset($params['category']) ? $params['category'] : '';
    $tag = isset($params['tag']) ? $params['tag'] : '';
    $search = isset($params['search']) ? $params['search'] : '';

    $tax_query = [];
    if ($category) {
        $tax_query[] = [
            'taxonomy' => 'category',
            'field' => is_numeric($category) ? 'term_id' : 'slug',
            'terms' => $category
        ];
    }
    if ($tag) {
        $tax_query[] = [
            'taxonomy' => 'post_tag',
            'field' => is_numeric($tag) ? 'term_id' : 'slug',
            'terms' => $tag
        ];
    }

    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'orderby' => $orderby,
        'order' => $order,
        's' => $search,
        'tax_query' => $tax_query,
        'ignore_sticky_posts' => true,
    ];

    $query = new WP_Query($args);
    $items = [];
    foreach ($query->posts as $post) {
        $categories = array_map(function($cat_id) {
            $cat = get_category($cat_id);
            return $cat ? [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug
            ] : null;
        }, wp_get_post_categories($post->ID));
        $categories = array_filter($categories);

        $tags = array_map(function($tag_id) {
            $tag = get_tag($tag_id);
            return $tag ? [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug
            ] : null;
        }, wp_get_post_tags($post->ID, ['fields' => 'ids']));
        $tags = array_filter($tags);

        $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
        $author = get_the_author_meta('display_name', $post->post_author);
        $excerpt = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_trim_words(strip_tags($post->post_content), 30);

        // SEO meta (Yoast, Rank Math, AIOSEO, etc.)
        $meta_title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
        $meta_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
        if (!$meta_title) $meta_title = get_post_meta($post->ID, 'rank_math_title', true);
        if (!$meta_description) $meta_description = get_post_meta($post->ID, 'rank_math_description', true);
        if (!$meta_title) $meta_title = get_post_meta($post->ID, '_aioseo_title', true);
        if (!$meta_description) $meta_description = get_post_meta($post->ID, '_aioseo_description', true);
        if (!$meta_title) $meta_title = get_the_title($post->ID);
        if (!$meta_description) $meta_description = $excerpt;
        $og_image = $featured_image;
        $canonical_url = get_post_meta($post->ID, '_yoast_wpseo_canonical', true);
        $meta_keywords = get_post_meta($post->ID, '_yoast_wpseo_focuskw', true);

        $items[] = [
            'id' => $post->ID,
            'title' => get_the_title($post->ID),
            'slug' => $post->post_name,
            'featured_image' => $featured_image,
            'categories' => array_values($categories),
            'tags' => array_values($tags),
            'date' => $post->post_date,
            'author' => $author,
            'content' => apply_filters('the_content', $post->post_content),
            'excerpt' => $excerpt,
            'seo' => [
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'og_image' => $og_image,
                'og_type' => 'article',
                'twitter_card' => 'summary_large_image',
                'canonical_url' => $canonical_url,
                'meta_keywords' => $meta_keywords
            ]
        ];
    }

    // Facets: categorías, tags, fechas (por mes)
    $facets = [
        'categories' => [],
        'tags' => [],
        'dates' => []
    ];
    $all_args = $args;
    $all_args['posts_per_page'] = -1;
    $all_query = new WP_Query($all_args);
    foreach ($all_query->posts as $post) {
        // Categorías
        foreach (wp_get_post_categories($post->ID) as $cat_id) {
            $cat = get_category($cat_id);
            if ($cat) {
                $facets['categories'][$cat->slug] = isset($facets['categories'][$cat->slug]) ? $facets['categories'][$cat->slug] + 1 : 1;
            }
        }
        // Tags
        foreach (wp_get_post_tags($post->ID, ['fields' => 'ids']) as $tag_id) {
            $tag_obj = get_tag($tag_id);
            if ($tag_obj) {
                $facets['tags'][$tag_obj->slug] = isset($facets['tags'][$tag_obj->slug]) ? $facets['tags'][$tag_obj->slug] + 1 : 1;
            }
        }
        // Fechas (por mes)
        $month = date('Y-m', strtotime($post->post_date));
        $facets['dates'][$month] = isset($facets['dates'][$month]) ? $facets['dates'][$month] + 1 : 1;
    }

    $total = $query->found_posts;
    $pages = ceil($total / $per_page);

    return new WP_REST_Response([
        'status' => 'success',
        'items' => $items,
        'total' => $total,
        'pages' => $pages,
        'page' => $paged,
        'per_page' => $per_page,
        'facets' => $facets
    ], 200);
}
