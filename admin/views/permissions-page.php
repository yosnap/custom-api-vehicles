<?php
if (!defined('ABSPATH'))
    exit;

if (isset($_POST['save_permissions'])) {
    $allowed_roles = isset($_POST['allowed_roles']) ? $_POST['allowed_roles'] : array();
    update_option('vehicles_api_image_permissions', $allowed_roles);
    echo '<div class="notice notice-success"><p>Permisos actualizados correctamente.</p></div>';
}

$all_roles = get_editable_roles();
$saved_roles = get_option('vehicles_api_image_permissions', array());
?>

<div class="wrap">
    <h1>Permisos de Subida de Imágenes</h1>
    <form method="post">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Roles permitidos</th>
                    <td>
                        <?php foreach ($all_roles as $role_slug => $role_info): ?>
                            <label>
                                <input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr($role_slug); ?>"
                                    <?php checked(in_array($role_slug, $saved_roles)); ?>>
                                <?php echo esc_html($role_info['name']); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button('Guardar Permisos', 'primary', 'save_permissions'); ?>
    </form>
</div>