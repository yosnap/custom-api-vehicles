<?php
if (!defined('ABSPATH'))
    exit;

global $wpdb;
$table_name = $wpdb->prefix . 'vehicle_api_logs';
$logs = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 100");

// Manejar la eliminación de todos los registros
if (isset($_POST['delete_all_logs'])) {
    $wpdb->query("TRUNCATE TABLE {$table_name}");
    echo '<div class="updated"><p>Todos los registros han sido eliminados.</p></div>';
}
?>

<div class="wrap">
    <h1>Registros de la API de Vehículos</h1>

    <form method="get">
        <input type="hidden" name="page" value="vehicles-api-logs">

        <div class="tablenav top">
            <div class="alignleft actions">
                <input type="number" name="vehicle_id" placeholder="ID del vehículo"
                    value="<?php echo esc_attr($filters['vehicle_id']); ?>">
                <input type="number" name="user_id" placeholder="ID del usuario"
                    value="<?php echo esc_attr($filters['user_id']); ?>">
                <select name="action">
                    <option value="">Todas las acciones</option>
                    <option value="create" <?php selected($filters['action'], 'create'); ?>>Crear</option>
                    <option value="update" <?php selected($filters['action'], 'update'); ?>>Actualizar</option>
                    <option value="delete" <?php selected($filters['action'], 'delete'); ?>>Eliminar</option>
                </select>
                <input type="submit" class="button" value="Filtrar">
            </div>
        </div>
    </form>

    <form method="post" id="delete-all-logs-form">
        <input type="hidden" name="delete_all_logs" value="1">
        <input type="submit" class="button button-danger" value="Eliminar todos los registros" onclick="return confirm('¿Estás seguro de que deseas eliminar todos los registros?');">
    </form>

    <table class="fixed wp-list-table widefat striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Vehículo</th>
                <th>Acción</th>
                <th>Detalles</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs_data['logs'] as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->id); ?></td>
                    <td><?php echo esc_html(get_user_by('id', $log->user_id)->display_name); ?></td>
                    <td><?php echo esc_html(get_the_title($log->vehicle_id)); ?></td>
                    <td><?php echo esc_html($log->action); ?></td>
                    <td><?php echo esc_html($log->details); ?></td>
                    <td><?php echo esc_html($log->created_at); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $logs_data['pages'],
                'current' => $current_page
            ));
            ?>
        </div>
    </div>
</div>