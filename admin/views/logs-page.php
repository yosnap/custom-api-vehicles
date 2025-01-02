<?php
if (!defined('ABSPATH'))
    exit;

global $wpdb;
$table_name = $wpdb->prefix . 'vehicle_api_logs';
$logs = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 100");
?>

<div class="wrap">
    <h1>Logs de Vehículos</h1>
    <table class="widefat fixed" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>ID Vehículo</th>
                <th>Campo</th>
                <th>Valor</th>
                <th>Tipo</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <?php echo esc_html($log->vehicle_id); ?>
                    </td>
                    <td>
                        <?php echo esc_html($log->field_name); ?>
                    </td>
                    <td>
                        <?php echo esc_html($log->field_value); ?>
                    </td>
                    <td>
                        <?php echo esc_html($log->field_type); ?>
                    </td>
                    <td>
                        <?php echo esc_html($log->created_at); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>