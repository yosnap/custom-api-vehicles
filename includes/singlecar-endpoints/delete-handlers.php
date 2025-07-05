<?php

function delete_singlecar($request) {
    try {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $post_id = $request['id'];

        // Verificación adicional de propiedad antes de eliminar
        validate_delete_permissions($post_id);

        // Registrar la acción antes de eliminar
        log_delete_action($post_id);

        // Eliminar imágenes asociadas al vehículo
        delete_vehicle_images($post_id);

        // Mover a la papelera en lugar de eliminar permanentemente
        $result = wp_trash_post($post_id);

        if (!$result) {
            throw new Exception('Error al mover el vehículo a la papelera');
        }

        $wpdb->query('COMMIT');

        clear_vehicle_cache($post_id);

        return new WP_REST_Response([
            'status' => 'success',
            'message' => 'Vehículo y sus imágenes asociadas eliminados exitosamente',
            'post_id' => $post_id
        ], 200);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return new WP_REST_Response([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 403);
    }
}

/**
 * Elimina todas las imágenes asociadas a un vehículo
 * 
 * @param int $post_id ID del vehículo
 */
function delete_vehicle_images($post_id) {
    Vehicle_Debug_Handler::log('Eliminando imágenes asociadas al vehículo ID: ' . $post_id);
    
    // Eliminar imagen destacada
    $featured_image_id = get_post_thumbnail_id($post_id);
    if ($featured_image_id) {
        Vehicle_Debug_Handler::log('Eliminando imagen destacada ID: ' . $featured_image_id);
        wp_delete_attachment($featured_image_id, true);
    }
    
    // Eliminar imágenes de la galería
    $gallery_string = get_post_meta($post_id, 'ad_gallery', true);
    if (!empty($gallery_string)) {
        $gallery_ids = explode(',', $gallery_string);
        foreach ($gallery_ids as $attachment_id) {
            if (!empty($attachment_id) && is_numeric($attachment_id)) {
                Vehicle_Debug_Handler::log('Eliminando imagen de galería ID: ' . $attachment_id);
                wp_delete_attachment($attachment_id, true);
            }
        }
    }
    
    // Eliminar metadatos de la galería
    delete_post_meta($post_id, 'ad_gallery');
    
    Vehicle_Debug_Handler::log('Imágenes asociadas al vehículo eliminadas correctamente');
}

function validate_delete_permissions($post_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'singlecar') {
        throw new Exception('Vehículo no encontrado');
    }

    if (!verify_post_ownership($post_id)) {
        throw new Exception('No tienes permiso para eliminar este vehículo');
    }
}

function log_delete_action($post_id) {
    Vehicle_API_Logger::get_instance()->log_action(
        $post_id,
        'delete',
        array(
            'title' => get_the_title($post_id),
            'user_id' => get_current_user_id()
        )
    );
}
