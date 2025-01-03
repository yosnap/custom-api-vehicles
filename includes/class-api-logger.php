<?php

class Vehicle_API_Logger
{
    private static $instance = null;
    private $table_name;

    private function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vehicle_api_logs';
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function create_log_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            vehicle_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            details text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function log_action($vehicle_id, $action, $details = '')
    {
        global $wpdb;

        $user_id = get_current_user_id();

        // Asegurar que los detalles sean una cadena JSON válida
        if (is_array($details) || is_object($details)) {
            $details = json_encode($details, JSON_UNESCAPED_UNICODE);
        }

        error_log("Registrando acción: {$action} para vehículo {$vehicle_id}");

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'vehicle_id' => $vehicle_id,
                'action' => $action,
                'details' => $details
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result === false) {
            error_log("Error al insertar log: " . $wpdb->last_error);
            return false;
        }

        return true;
    }

    public function get_logs($per_page = 10, $page = 1, $filters = array())
    {
        global $wpdb;

        $offset = ($page - 1) * $per_page;
        $where = "WHERE 1=1";

        if (!empty($filters['user_id'])) {
            $where .= $wpdb->prepare(" AND user_id = %d", $filters['user_id']);
        }
        if (!empty($filters['vehicle_id'])) {
            $where .= $wpdb->prepare(" AND vehicle_id = %d", $filters['vehicle_id']);
        }
        if (!empty($filters['action'])) {
            $where .= $wpdb->prepare(" AND action = %s", $filters['action']);
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->table_name} 
                 $where 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d";

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $per_page, $offset)
        );

        $total = $wpdb->get_var("SELECT FOUND_ROWS()");

        return array(
            'logs' => $results,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        );
    }
}
