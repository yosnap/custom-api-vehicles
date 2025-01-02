<?php
class Vehicles_Admin_Menu
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Vehículos API',
            'Vehículos API',
            'manage_options',
            'vehicles-api',
            array($this, 'display_main_page'),
            'dashicons-car',
            30
        );

        add_submenu_page(
            'vehicles-api',
            'Permisos de Imágenes',
            'Permisos',
            'manage_options',
            'vehicles-api-permissions',
            array($this, 'display_permissions_page')
        );

        add_submenu_page(
            'vehicles-api',
            'Logs de Vehículos',
            'Logs',
            'manage_options',
            'vehicles-api-logs',
            array($this, 'display_logs_page')
        );
    }

    public function display_main_page()
    {
        require_once plugin_dir_path(__FILE__) . 'views/main-page.php';
    }

    public function display_permissions_page()
    {
        require_once plugin_dir_path(__FILE__) . 'views/permissions-page.php';
    }

    public function display_logs_page()
    {
        require_once plugin_dir_path(__FILE__) . 'views/logs-page.php';
    }
}
