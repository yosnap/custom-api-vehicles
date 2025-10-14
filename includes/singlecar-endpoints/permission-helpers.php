<?php
/**
 * Funciones helper para validación de permisos de la API
 */

/**
 * Verifica si el usuario actual tiene permiso para crear vehículos
 *
 * @return bool True si el usuario tiene permiso, false en caso contrario
 */
function user_can_create_vehicle() {
    // Si no está logueado, no puede crear
    if (!is_user_logged_in()) {
        return false;
    }

    // Los administradores siempre pueden crear
    if (current_user_can('administrator')) {
        return true;
    }

    // Obtener roles permitidos desde la configuración
    $allowed_roles = get_option('vehicles_api_create_permissions', array('administrator'));

    // Si no hay roles configurados, solo permitir administradores
    if (empty($allowed_roles)) {
        return current_user_can('administrator');
    }

    // Verificar si el usuario tiene alguno de los roles permitidos
    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;

    foreach ($user_roles as $role) {
        if (in_array($role, $allowed_roles)) {
            return true;
        }
    }

    return false;
}

/**
 * Verifica si el usuario actual tiene permiso para editar un vehículo específico
 *
 * @param int $post_id ID del post a editar
 * @return bool True si el usuario tiene permiso, false en caso contrario
 */
function user_can_edit_vehicle($post_id = null) {
    // Si no está logueado, no puede editar
    if (!is_user_logged_in()) {
        return false;
    }

    // Los administradores siempre pueden editar
    if (current_user_can('administrator')) {
        return true;
    }

    // Si se proporciona un post_id, verificar que sea el propietario
    if ($post_id) {
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'singlecar') {
            return false;
        }

        // Verificar si es el propietario del post
        if ($post->post_author != get_current_user_id()) {
            return false;
        }
    }

    // Obtener roles permitidos desde la configuración
    $allowed_roles = get_option('vehicles_api_edit_permissions', array('administrator'));

    // Si no hay roles configurados, solo permitir administradores
    if (empty($allowed_roles)) {
        return current_user_can('administrator');
    }

    // Verificar si el usuario tiene alguno de los roles permitidos
    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;

    foreach ($user_roles as $role) {
        if (in_array($role, $allowed_roles)) {
            return true;
        }
    }

    return false;
}

/**
 * Verifica si el usuario actual tiene permiso para subir imágenes
 *
 * @return bool True si el usuario tiene permiso, false en caso contrario
 */
function user_can_upload_images() {
    // Si no está logueado, no puede subir imágenes
    if (!is_user_logged_in()) {
        return false;
    }

    // Los administradores siempre pueden subir imágenes
    if (current_user_can('administrator')) {
        return true;
    }

    // Obtener roles permitidos desde la configuración
    $allowed_roles = get_option('vehicles_api_image_permissions', array('administrator'));

    // Si no hay roles configurados, solo permitir administradores
    if (empty($allowed_roles)) {
        return current_user_can('administrator');
    }

    // Verificar si el usuario tiene alguno de los roles permitidos
    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;

    foreach ($user_roles as $role) {
        if (in_array($role, $allowed_roles)) {
            return true;
        }
    }

    return false;
}

/**
 * Verifica si el usuario actual tiene permiso para eliminar un vehículo específico
 *
 * @param int $post_id ID del post a eliminar
 * @return bool True si el usuario tiene permiso, false en caso contrario
 */
function user_can_delete_vehicle($post_id) {
    // Si no está logueado, no puede eliminar
    if (!is_user_logged_in()) {
        return false;
    }

    // Los administradores siempre pueden eliminar
    if (current_user_can('administrator')) {
        return true;
    }

    // Verificar que el post existe y es del tipo correcto
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'singlecar') {
        return false;
    }

    // Solo el propietario puede eliminar su vehículo (además de admins)
    return $post->post_author == get_current_user_id();
}
