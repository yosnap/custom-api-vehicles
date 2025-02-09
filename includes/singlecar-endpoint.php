<?php
// Cargar todos los archivos necesarios
$base_path = plugin_dir_path(__FILE__) . 'singlecar-endpoints/';

require_once $base_path . 'routes.php';
require_once $base_path . 'get-handlers.php';
require_once $base_path . 'post-handlers.php';
require_once $base_path . 'put-handlers.php';
require_once $base_path . 'delete-handlers.php';
require_once $base_path . 'field-processors.php';
require_once $base_path . 'meta-handlers.php';
require_once $base_path . 'media-handlers.php';
require_once $base_path . 'validation.php';
require_once $base_path . 'utils.php';

// Incluir dependencias de WordPress
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
