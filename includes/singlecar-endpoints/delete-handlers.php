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

        // Mover a la papelera en lugar de eliminar permanentemente
        $result = wp_trash_post($post_id);

        if (!$result) {
            throw new Exception('Error al mover el vehículo a la papelera');
        }

        $wpdb->query('COMMIT');

        return new WP_REST_Response([
            'status' => 'success',
            'message' => 'Vehículo movido a la papelera exitosamente',
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
