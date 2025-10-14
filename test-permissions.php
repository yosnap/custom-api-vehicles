<?php
/**
 * Script de prueba para verificar el sistema de permisos
 *
 * IMPORTANTE: Ejecutar este script desde el navegador en WordPress
 * URL: /wp-content/plugins/custom-api-vehicles/test-permissions.php
 *
 * Eliminar este archivo después de las pruebas
 */

// Cargar WordPress
require_once('../../../../../wp-load.php');

// Verificar que el usuario está logueado
if (!is_user_logged_in()) {
    wp_die('Debes estar logueado para ejecutar este script de prueba.');
}

// Verificar que es administrador
if (!current_user_can('administrator')) {
    wp_die('Solo los administradores pueden ejecutar este script de prueba.');
}

echo '<h1>Prueba del Sistema de Permisos - Custom API Vehicles</h1>';
echo '<hr>';

// Verificar que el archivo de permisos está cargado
echo '<h2>1. Verificando que las funciones están disponibles</h2>';

$functions_to_check = [
    'user_can_create_vehicle',
    'user_can_edit_vehicle',
    'user_can_upload_images',
    'user_can_delete_vehicle'
];

$all_functions_exist = true;
foreach ($functions_to_check as $function) {
    if (function_exists($function)) {
        echo "✅ Función <code>{$function}()</code> está disponible<br>";
    } else {
        echo "❌ Función <code>{$function}()</code> NO está disponible<br>";
        $all_functions_exist = false;
    }
}

if (!$all_functions_exist) {
    echo '<p style="color: red;"><strong>ERROR: Algunas funciones no están disponibles. Verifica que el archivo permission-helpers.php está siendo cargado correctamente.</strong></p>';
    exit;
}

echo '<hr>';

// Verificar la configuración actual
echo '<h2>2. Configuración Actual de Permisos</h2>';

$create_permissions = get_option('vehicles_api_create_permissions', array());
$edit_permissions = get_option('vehicles_api_edit_permissions', array());
$image_permissions = get_option('vehicles_api_image_permissions', array());

echo '<h3>Crear Vehículos (POST)</h3>';
if (empty($create_permissions)) {
    echo '<p style="color: orange;">⚠️ No hay roles configurados (solo administradores)</p>';
} else {
    echo '<ul>';
    foreach ($create_permissions as $role) {
        echo "<li>✅ {$role}</li>";
    }
    echo '</ul>';
}

echo '<h3>Editar Vehículos (PUT)</h3>';
if (empty($edit_permissions)) {
    echo '<p style="color: orange;">⚠️ No hay roles configurados (solo administradores)</p>';
} else {
    echo '<ul>';
    foreach ($edit_permissions as $role) {
        echo "<li>✅ {$role}</li>";
    }
    echo '</ul>';
}

echo '<h3>Subir Imágenes</h3>';
if (empty($image_permissions)) {
    echo '<p style="color: orange;">⚠️ No hay roles configurados (solo administradores)</p>';
} else {
    echo '<ul>';
    foreach ($image_permissions as $role) {
        echo "<li>✅ {$role}</li>";
    }
    echo '</ul>';
}

echo '<hr>';

// Probar permisos del usuario actual
echo '<h2>3. Probando Permisos del Usuario Actual</h2>';

$current_user = wp_get_current_user();
echo '<p><strong>Usuario actual:</strong> ' . $current_user->user_login . '</p>';
echo '<p><strong>Roles:</strong> ' . implode(', ', $current_user->roles) . '</p>';

echo '<h3>Resultados de las pruebas:</h3>';
echo '<ul>';
echo '<li>' . (user_can_create_vehicle() ? '✅' : '❌') . ' Puede crear vehículos</li>';
echo '<li>' . (user_can_edit_vehicle() ? '✅' : '❌') . ' Puede editar vehículos (sin especificar ID)</li>';
echo '<li>' . (user_can_upload_images() ? '✅' : '❌') . ' Puede subir imágenes</li>';
echo '</ul>';

echo '<hr>';

// Información adicional
echo '<h2>4. Roles Disponibles en WordPress</h2>';
$all_roles = get_editable_roles();
echo '<ul>';
foreach ($all_roles as $role_slug => $role_info) {
    echo '<li><strong>' . $role_info['name'] . '</strong> (slug: ' . $role_slug . ')</li>';
}
echo '</ul>';

echo '<hr>';

echo '<h2>5. Instrucciones</h2>';
echo '<ol>';
echo '<li>Ve a <strong>WP Admin → API Motoraldia → Permisos</strong></li>';
echo '<li>Marca los checkboxes para los roles que quieres permitir (ej: Professional, Particular)</li>';
echo '<li>Guarda los cambios</li>';
echo '<li>Vuelve a ejecutar este script para verificar los cambios</li>';
echo '</ol>';

echo '<hr>';
echo '<p><strong>IMPORTANTE:</strong> Elimina este archivo (test-permissions.php) después de terminar las pruebas de seguridad.</p>';
?>
