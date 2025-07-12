<?php
/**
 * Página principal de administración del plugin
 */

// Asegurar codificación UTF-8
header('Content-Type: text/html; charset=UTF-8');

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
}

// Procesar formulario si se envió
if (isset($_POST['vehicles_api_cache_settings'])) {
    check_admin_referer('vehicles_api_cache_settings');
    
    // Guardar configuraciones
    update_option('vehicles_api_cache_enabled', isset($_POST['cache_enabled']) ? '1' : '0');
    update_option('vehicles_api_cache_duration', intval($_POST['cache_duration']));
    update_option('vehicles_api_expiry_enabled', isset($_POST['expiry_enabled']) ? '1' : '0');
    update_option('vehicles_api_default_expiry_days', intval($_POST['default_expiry_days']));
    
    echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada correctamente.</p></div>';
}

// Procesar limpiar cache
if (isset($_POST['clear_cache'])) {
    check_admin_referer('vehicles_api_clear_cache');
    
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vehicle%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vehicle%'");
    
    echo '<div class="notice notice-success is-dismissible"><p>Cache limpiado correctamente.</p></div>';
}

// Obtener valores actuales
$cache_enabled = get_option('vehicles_api_cache_enabled', '0');
$cache_duration = get_option('vehicles_api_cache_duration', 3600);
$expiry_enabled = get_option('vehicles_api_expiry_enabled', '1');
$default_expiry_days = get_option('vehicles_api_default_expiry_days', 365);

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card">
        <h2>Configuración de Cache</h2>
        <form method="post" action="">
            <?php wp_nonce_field('vehicles_api_cache_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Activar Cache</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cache_enabled" value="1" <?php checked($cache_enabled, '1'); ?> />
                            Habilitar sistema de cache para mejorar el rendimiento
                        </label>
                        <p class="description">Desactiva esta opci&oacute;n durante desarrollo para ver cambios inmediatamente.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Duración del Cache</th>
                    <td>
                        <select name="cache_duration">
                            <option value="300" <?php selected($cache_duration, 300); ?>>5 minutos</option>
                            <option value="900" <?php selected($cache_duration, 900); ?>>15 minutos</option>
                            <option value="1800" <?php selected($cache_duration, 1800); ?>>30 minutos</option>
                            <option value="3600" <?php selected($cache_duration, 3600); ?>>1 hora</option>
                            <option value="7200" <?php selected($cache_duration, 7200); ?>>2 horas</option>
                            <option value="21600" <?php selected($cache_duration, 21600); ?>>6 horas</option>
                            <option value="43200" <?php selected($cache_duration, 43200); ?>>12 horas</option>
                            <option value="86400" <?php selected($cache_duration, 86400); ?>>24 horas</option>
                        </select>
                        <p class="description">Tiempo que se mantienen los datos en cache antes de refrescarse.</p>
                    </td>
                </tr>
            </table>
            
            <h2>Configuraci&oacute;n de Caducidad de Anuncios</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Activar Caducidad Autom&aacute;tica</th>
                    <td>
                        <label>
                            <input type="checkbox" name="expiry_enabled" value="1" <?php checked($expiry_enabled, '1'); ?> />
                            Los anuncios se desactivan autom&aacute;ticamente despu&eacute;s del per&iacute;odo configurado
                        </label>
                        <p class="description">Si est&aacute; desactivado, los anuncios solo se desactivan manualmente.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">D&iacute;as de Caducidad por Defecto</th>
                    <td>
                        <input type="number" name="default_expiry_days" value="<?php echo esc_attr($default_expiry_days); ?>" min="1" max="9999" />
                        <p class="description">D&iacute;as por defecto para caducidad si no se especifica en el veh&iacute;culo.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="vehicles_api_cache_settings" class="button-primary" value="Guardar cambios" />
            </p>
        </form>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <h2>Mantenimiento de Cache</h2>
        <form method="post" action="">
            <?php wp_nonce_field('vehicles_api_clear_cache'); ?>
            <p>Limpia todos los datos almacenados en cache del plugin.</p>
            <p class="submit">
                <input type="submit" name="clear_cache" class="button-secondary" value="Limpiar Cache Ahora" onclick="return confirm('&iquest;Est&aacute;s seguro de que quieres limpiar todo el cache?');" />
            </p>
        </form>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <h2>Informaci&oacute;n del Sistema</h2>
        <table class="widefat">
            <tr>
                <td><strong>Versi&oacute;n del Plugin:</strong></td>
                <td>2.2.2</td>
            </tr>
            <tr>
                <td><strong>Estado del Cache:</strong></td>
                <td><?php echo $cache_enabled === '1' ? '<span style="color: green;">Activado</span>' : '<span style="color: red;">Desactivado</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Estado de Caducidad:</strong></td>
                <td><?php echo $expiry_enabled === '1' ? '<span style="color: green;">Activada</span>' : '<span style="color: red;">Desactivada</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Endpoints de API:</strong></td>
                <td>
                    <code>/wp-json/api-motor/v1/vehicles</code><br>
                    <code>/wp-json/api-motor/v1/vehicles/{id}</code><br>
                    <code>/wp-json/api-motor/v1/vehicles/types-of-transport</code>
                </td>
            </tr>
        </table>
    </div>
</div>