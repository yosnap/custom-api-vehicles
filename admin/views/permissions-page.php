<?php
if (!defined('ABSPATH'))
    exit;

if (isset($_POST['save_permissions'])) {
    // Guardar roles permitidos para crear vehículos
    $create_roles = isset($_POST['create_vehicle_roles']) ? $_POST['create_vehicle_roles'] : array();
    update_option('vehicles_api_create_permissions', $create_roles);

    // Guardar roles permitidos para editar vehículos
    $edit_roles = isset($_POST['edit_vehicle_roles']) ? $_POST['edit_vehicle_roles'] : array();
    update_option('vehicles_api_edit_permissions', $edit_roles);

    // Guardar roles permitidos para subir imágenes (legacy, por compatibilidad)
    $image_roles = isset($_POST['upload_image_roles']) ? $_POST['upload_image_roles'] : array();
    update_option('vehicles_api_image_permissions', $image_roles);

    echo '<div class="notice notice-success"><p>Permisos actualizados correctamente.</p></div>';
}

$all_roles = get_editable_roles();
$create_roles = get_option('vehicles_api_create_permissions', array('administrator'));
$edit_roles = get_option('vehicles_api_edit_permissions', array('administrator'));
$image_roles = get_option('vehicles_api_image_permissions', array('administrator'));
?>

<div class="wrap">
    <h1>Permisos de la API de Vehículos</h1>
    <p>Configura qué roles de usuario pueden realizar diferentes acciones en la API de vehículos.</p>

    <form method="post">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label>Crear Vehículos (POST)</label>
                        <p class="description">Roles que pueden crear nuevos vehículos a través de la API</p>
                    </th>
                    <td>
                        <?php foreach ($all_roles as $role_slug => $role_info): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="create_vehicle_roles[]" value="<?php echo esc_attr($role_slug); ?>"
                                    <?php checked(in_array($role_slug, $create_roles)); ?>>
                                <?php echo esc_html($role_info['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label>Editar Vehículos (PUT)</label>
                        <p class="description">Roles que pueden editar vehículos existentes a través de la API</p>
                    </th>
                    <td>
                        <?php foreach ($all_roles as $role_slug => $role_info): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="edit_vehicle_roles[]" value="<?php echo esc_attr($role_slug); ?>"
                                    <?php checked(in_array($role_slug, $edit_roles)); ?>>
                                <?php echo esc_html($role_info['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label>Subir Imágenes</label>
                        <p class="description">Roles que pueden subir imágenes a través de la API (incluye imagen destacada y galería)</p>
                    </th>
                    <td>
                        <?php foreach ($all_roles as $role_slug => $role_info): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="upload_image_roles[]" value="<?php echo esc_attr($role_slug); ?>"
                                    <?php checked(in_array($role_slug, $image_roles)); ?>>
                                <?php echo esc_html($role_info['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="description">
            <strong>Nota:</strong> Los usuarios solo podrán editar o eliminar sus propios vehículos,
            excepto los administradores que pueden gestionar todos los vehículos.
        </p>

        <?php submit_button('Guardar Permisos', 'primary', 'save_permissions'); ?>
    </form>
</div>