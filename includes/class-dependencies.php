<?php
/**
 * Clase para manejar dependencias del plugin
 */
class Vehicle_Plugin_Dependencies {
    
    private static $dependencies_checked = false;
    private static $missing_dependencies = [];
    
    /**
     * Verificar todas las dependencias
     */
    public static function check_dependencies() {
        if (self::$dependencies_checked) {
            return empty(self::$missing_dependencies);
        }
        
        self::$dependencies_checked = true;
        self::$missing_dependencies = [];
        
        // Verificar JetEngine
        if (!function_exists('jet_engine')) {
            self::$missing_dependencies[] = [
                'name' => 'JetEngine',
                'description' => 'Requerido para la gestión de campos personalizados y taxonomías',
                'url' => 'https://jetformbuilder.com/jetengine/'
            ];
        }
        
        // Verificar versión de WordPress
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            self::$missing_dependencies[] = [
                'name' => 'WordPress 5.0+',
                'description' => 'Se requiere WordPress 5.0 o superior para la API REST',
                'current' => $wp_version
            ];
        }
        
        // Verificar versión de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            self::$missing_dependencies[] = [
                'name' => 'PHP 7.4+',
                'description' => 'Se requiere PHP 7.4 o superior',
                'current' => PHP_VERSION
            ];
        }
        
        // Si hay dependencias faltantes, mostrar notices
        if (!empty(self::$missing_dependencies)) {
            add_action('admin_notices', [__CLASS__, 'show_dependency_notices']);
            Vehicle_Debug_Handler::log('Dependencias faltantes: ' . json_encode(self::$missing_dependencies), 'error');
        }
        
        return empty(self::$missing_dependencies);
    }
    
    /**
     * Mostrar avisos de dependencias faltantes
     */
    public static function show_dependency_notices() {
        foreach (self::$missing_dependencies as $dependency) {
            $message = sprintf(
                '<strong>Custom API Vehicles:</strong> %s es requerido. %s',
                $dependency['name'],
                $dependency['description']
            );
            
            if (isset($dependency['current'])) {
                $message .= sprintf(' (Actual: %s)', $dependency['current']);
            }
            
            if (isset($dependency['url'])) {
                $message .= sprintf(' <a href="%s" target="_blank">Más información</a>', $dependency['url']);
            }
            
            printf('<div class="error"><p>%s</p></div>', $message);
        }
    }
    
    /**
     * Verificar si JetEngine está disponible y funcional
     */
    public static function is_jetengine_ready() {
        return function_exists('jet_engine') && 
               is_object(jet_engine()) &&
               method_exists(jet_engine(), 'meta_boxes');
    }
    
    /**
     * Obtener información de diagnóstico
     */
    public static function get_diagnostic_info() {
        return [
            'dependencies_ok' => self::check_dependencies(),
            'missing_dependencies' => self::$missing_dependencies,
            'jetengine_ready' => self::is_jetengine_ready(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'rest_api_available' => function_exists('rest_url'),
            'rewrite_rules_flushed' => get_option('rewrite_rules') !== false
        ];
    }
}

/**
 * Función mejorada para verificar dependencias
 */
function check_vehicle_plugin_dependencies() {
    return Vehicle_Plugin_Dependencies::check_dependencies();
}
