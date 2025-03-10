<?php
/**
 * Archivo para depurar problemas de validación numérica
 */

// Asegurarse de que se cargue WordPress
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

// Verificar que el usuario tiene permisos de administrador
if (!current_user_can('administrator')) {
    wp_die('Acceso no autorizado');
}

echo "<h1>Depuración de campos numéricos</h1>";

// Simular una solicitud con el campo problemático
$test_params = [
    'potencia-combinada' => '', // Valor vacío
    'preu' => '15000',          // Un campo numérico válido
    'quilometratge' => '50000'  // Otro campo numérico válido
];

echo "<h2>Probando validación de campos numéricos</h2>";
echo "<pre>";
echo "Parámetros de prueba: " . print_r($test_params, true);
echo "</pre>";

try {
    // Cargar la función desde el archivo original
    require_once(plugin_dir_path(__FILE__) . 'includes/singlecar-endpoints/validation.php');
    
    // Llamar a la función de validación
    validate_numeric_fields($test_params);
    
    echo "<p style='color:green'>La validación se completó sin errores</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error en la validación: " . $e->getMessage() . "</p>";
}

// Probar también con incluyendo un valor no numérico en potencia-combinada
$test_params2 = [
    'potencia-combinada' => 'abc', // Valor no numérico
    'preu' => '15000',
    'quilometratge' => '50000'
];

echo "<h2>Probando con un valor no numérico en potencia-combinada</h2>";
echo "<pre>";
echo "Parámetros de prueba: " . print_r($test_params2, true);
echo "</pre>";

try {
    // Llamar a la función de validación nuevamente
    validate_numeric_fields($test_params2);
    
    echo "<p style='color:green'>La validación se completó sin errores</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error en la validación: " . $e->getMessage() . "</p>";
}

// Mostrar lista de todos los campos meta registrados para debug
echo "<h2>Lista de todos los campos meta de vehículos</h2>";

// Si existe la clase Vehicle_Fields, mostrar sus campos
if (class_exists('Vehicle_Fields')) {
    echo "<h3>Campos definidos en Vehicle_Fields</h3>";
    echo "<pre>";
    print_r(Vehicle_Fields::get_meta_fields());
    echo "</pre>";
}

echo "<p><i>Esta herramienta de depuración te ayudará a identificar problemas de validación con los campos numéricos.</i></p>";
