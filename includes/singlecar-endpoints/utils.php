<?php

/**
 * Verifica si el usuario actual tiene permiso para acceder al post
 */
function verify_post_ownership($post_id) {
    if (!is_user_logged_in()) {
        return false;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'singlecar') {
        return false;
    }

    return $post->post_author == get_current_user_id() || current_user_can('administrator');
}

/**
 * Verifica la propiedad del post usando el slug
 */
function verify_post_ownership_by_slug($slug) {
    if (!is_user_logged_in()) {
        return false;
    }

    $post = get_page_by_path($slug, OBJECT, 'singlecar');
    if (!$post) {
        return false;
    }

    return $post->post_author == get_current_user_id() || current_user_can('administrator');
}

/**
 * Obtiene el ID del término padre de una taxonomía
 */
function get_parent_term_id($term_id, $taxonomy) {
    $term = get_term($term_id, $taxonomy);
    return (!is_wp_error($term) && $term) ? $term->parent : 0;
}

/**
 * Convierte un valor a booleano consistente
 */
function normalize_boolean_value($value) {
    if (is_string($value)) {
        $value = strtolower(trim($value));
        $true_values = ['true', 'si', '1', 'yes', 'on'];
        return in_array($value, $true_values);
    }
    return (bool) $value;
}

/**
 * Sanitiza los campos antes de guardarlos
 */
function sanitize_vehicle_fields($params) {
    $sanitized = [];
    foreach ($params as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = array_map('sanitize_text_field', $value);
        } else {
            $sanitized[$key] = sanitize_text_field($value);
        }
    }
    return $sanitized;
}

/**
 * Obtiene la fecha de caducidad para un anuncio
 */
function calculate_expiration_date($creation_date, $days = 365) {
    return strtotime($creation_date . " + {$days} days");
}

/**
 * Verifica si un anuncio está activo
 */
function is_vehicle_active($post_id) {
    $dies_caducitat = intval(get_post_meta($post_id, 'dies-caducitat', true));
    $data_creacio = get_post_field('post_date', $post_id);
    $data_actual = current_time('timestamp');
    $dies_transcorreguts = floor(($data_actual - strtotime($data_creacio)) / (60 * 60 * 24));
    
    return $dies_transcorreguts <= $dies_caducitat;
}

/**
 * Log de errores personalizado
 */
function log_vehicle_error($message, $data = []) {
    error_log(sprintf(
        '[Vehicle API Error] %s | Data: %s',
        $message,
        json_encode($data)
    ));
}

/**
 * Formatea la respuesta de error
 */
function format_error_response($message, $status = 400) {
    return new WP_REST_Response([
        'status' => 'error',
        'message' => $message
    ], $status);
}

/**
 * Formatea la respuesta de éxito
 */
function format_success_response($data, $status = 200) {
    return new WP_REST_Response([
        'status' => 'success',
        'data' => $data
    ], $status);
}

/**
 * Verifica si una imagen existe en la biblioteca de medios
 */
function verify_media_exists($attachment_id) {
    return wp_attachment_is_image($attachment_id);
}

/**
 * Obtiene las URLs de la galería de imágenes
 */
function get_gallery_urls($gallery_ids) {
    if (empty($gallery_ids)) {
        return [];
    }

    $gallery_ids = is_array($gallery_ids) ? $gallery_ids : explode(',', $gallery_ids);
    $urls = [];

    foreach ($gallery_ids as $id) {
        $url = wp_get_attachment_url(trim($id));
        if ($url) {
            $urls[] = $url;
        }
    }

    return $urls;
}

/**
 * Genera un título automático para el vehículo
 */
function generate_vehicle_title($marca_slug, $model_slug, $version) {
    $marca_term = get_term_by('slug', $marca_slug, 'marques-coches');
    $model_term = get_term_by('slug', $model_slug, 'marques-coches');
    
    $marca_name = $marca_term ? $marca_term->name : $marca_slug;
    $model_name = $model_term ? $model_term->name : $model_slug;
    
    return ucfirst($marca_name) . ' ' . strtoupper($model_name) . ' ' . $version;
}
