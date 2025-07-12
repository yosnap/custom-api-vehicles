<?php
/**
 * Script temporal para limpiar el cache de vehículos
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Limpiar todos los transients que empiecen con 'vehicles_list_'
global $wpdb;

$deleted = $wpdb->query(
    "DELETE FROM {$wpdb->options} 
     WHERE option_name LIKE '_transient_vehicles_list_%' 
     OR option_name LIKE '_transient_timeout_vehicles_list_%'"
);

echo "Cache limpiado. Transients eliminados: " . $deleted;

// También limpiar cache de objeto si está disponible
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "<br>Object cache también limpiado.";
}

// Eliminar este archivo después de usarlo
//unlink(__FILE__);
echo "<br>Script ejecutado correctamente.";
?>