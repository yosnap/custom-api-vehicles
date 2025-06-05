<?php
/**
 * Archivo de configuración para el plugin Custom API Vehicles
 * 
 * Copia este archivo a wp-config.php o créalo como plugin separado
 * para personalizar el comportamiento del plugin
 */

// Habilitar debug específico del plugin (opcional)
// Solo se activará si WP_DEBUG también está habilitado
define('VEHICLE_API_DEBUG', true);

// Configuración adicional del plugin
// Descomenta las líneas que necesites:

// Habilitar logging extendido (incluye info y debug, no solo errores)
// define('VEHICLE_API_EXTENDED_LOGGING', true);

// Tiempo límite para las consultas de diagnóstico (en segundos)
// define('VEHICLE_API_DIAGNOSTIC_TIMEOUT', 30);

// Número máximo de errores recientes a mostrar en diagnóstico
// define('VEHICLE_API_MAX_RECENT_ERRORS', 20);

// Habilitar cache para consultas de diagnóstico (mejora rendimiento)
// define('VEHICLE_API_CACHE_DIAGNOSTICS', true);

/**
 * Ejemplo de cómo añadir estas constantes en wp-config.php:
 * 
 * // Justo antes de la línea "That's all, stop editing!"
 * define('VEHICLE_API_DEBUG', true);
 * define('VEHICLE_API_EXTENDED_LOGGING', false);
 */
