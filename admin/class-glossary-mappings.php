<?php

class Vehicle_Glossary_Mappings {
    private static $instance = null;
    private $option_name = 'vehicle_glossary_mappings';
    private $page_slug = 'api-motoraldia-settings';

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'modify_admin_menu'), 20); // Prioridad 20 para asegurar que se ejecute después del menú principal
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function modify_admin_menu() {
        global $menu, $submenu;

        // Renombrar el menú principal de "Vehículos API" a "API Motoraldia"
        foreach ($menu as $key => $item) {
            if ($item[2] === 'api-vehicles') {
                $menu[$key][0] = 'API Motoraldia';
                break;
            }
        }

        // Añadir el submenú de mapeos
        add_submenu_page(
            'api-vehicles', // Parent slug
            'Mapeos Glosarios', // Page title
            'Mapeos Glosarios', // Menu title
            'manage_options', // Capability
            'vehicle-glossary-mappings', // Menu slug
            array($this, 'render_admin_page') // Callback function
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name);
    }

    public function render_admin_page() {
        // Obtener los glosarios disponibles
        $glossaries = [];
        if (function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
            // Usar el método correcto para obtener los glosarios
            $all_glossaries = jet_engine()->glossaries->settings->get();
            if (!empty($all_glossaries)) {
                foreach ($all_glossaries as $glossary) {
                    if (isset($glossary['id']) && isset($glossary['name'])) {
                        $glossaries[$glossary['id']] = $glossary['name'];
                    }
                }
            }
        }

        // Obtener los mapeos guardados
        $saved_mappings = get_option($this->option_name, array());

        // Lista de campos que necesitan mapeo
        $fields_to_map = array(
            'color-tapisseria' => 'Color Tapisseria',
            'carrosseria-cotxe' => 'Carrosseria Cotxe',
            'traccio' => 'Tracció',
            'roda-recanvi' => 'Roda Recanvi',
            'color-vehicle' => 'Color Vehicle',
            'tipus-tapisseria' => 'Tipus Tapisseria',
            'emissions-vehicle' => 'Emissions Vehicle',
            'carroseria-camions' => 'Carroseria Camions',
            'carroseria-vehicle-comercial' => 'Carroseria Vehicle Comercial',
            'carrosseria-caravana' => 'Carrosseria Caravana',
            'tipus-carroseria-caravana' => 'Tipus Carrosseria Caravana', // Añadido para soportar el tipo de carrocería de caravanas
            'tipus-de-moto' => 'Tipus de Moto', // Añadido para soportar el tipo de moto
            'extres-cotxe' => 'Extras Cotxe',
            'extres-moto' => 'Extras Moto',
            'extres-autocaravana' => 'Extras Autocaravana',
            'extres-habitacle' => 'Extras Habitacle',
            'tipus-de-canvi-moto' => 'Tipus de Canvi Moto',
            'bateria' => 'Bateria',
            'cables-recarrega' => 'Cables Recàrrega',
            'connectors' => 'Connectors',
            'velocitat-recarrega' => 'Velocitat Recàrrega'
        );

?>
        <div class="wrap">
            <h1>Mapeos de Glosarios</h1>
            <p>Selecciona el glosario correspondiente para cada campo. Esto permitirá que la API devuelva los labels correctos para cada valor.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_name);
                do_settings_sections($this->option_name);
                ?>
                <table class="form-table">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 200px;">Campo</th>
                            <th scope="col">Glosario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields_to_map as $field_key => $field_label): ?>
                            <tr>
                                <td><strong><?php echo esc_html($field_label); ?></strong></td>
                                <td>
                                    <select
                                        name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field_key); ?>]"
                                        style="min-width: 300px;">
                                        <option value="">Seleccionar glosario</option>
                                        <?php foreach ($glossaries as $id => $name): ?>
                                            <option value="<?php echo esc_attr($id); ?>"
                                                <?php selected(isset($saved_mappings[$field_key]) ? $saved_mappings[$field_key] : '', $id); ?>>
                                                <?php echo esc_html($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php submit_button('Guardar Mapeos'); ?>
            </form>
        </div>
<?php
    }

    public static function get_glossary_id($field_name) {
        // Mapeos por defecto para campos comunes
        $default_mappings = [
            'tipus-de-moto' => '42', // Tipus Moto
            'carrosseria-cotxe' => '41', // Carrosseria
            'tipus-de-canvi-moto' => '62', // Tipus canvi moto
            'color-vehicle' => '51', // Color Exterior
            'color-tapisseria' => '53', // Color Tapisseria
            'tipus-tapisseria' => '52', // Tapisseria
            'traccio' => '59', // Tracció
            'roda-recanvi' => '60', // Roda recanvi
            'extres-cotxe' => '54', // Extres Coche
            'extres-moto' => '55', // Extres Moto
            'extres-autocaravana' => '56', // Extres Autocaravana
            'extres-habitacle' => '57', // Extres Habitacle
            'bateria' => '48', // Bateria
            'cables-recarrega' => '50', // Cables recàrrega
            'connectors' => '49', // Connectors-electric
            'velocitat-recarrega' => '61', // Velocitat de recàrrega
            'emissions-vehicle' => '58', // Tipus Emissions
            'carroseria-camions' => '45', // Carrosseria camions
            'carroseria-vehicle-comercial' => '44', // Carrosseria comercials
            'tipus-carroseria-caravana' => '43', // Carrosseria Caravanes
        ];

        // Primero, verificar mapeos guardados por el usuario
        $mappings = get_option('vehicle_glossary_mappings', array());
        if (isset($mappings[$field_name]) && !empty($mappings[$field_name])) {
            return $mappings[$field_name];
        }

        // Si no hay mapeo del usuario, usar el mapeo por defecto
        return isset($default_mappings[$field_name]) ? $default_mappings[$field_name] : null;
    }
}

// Inicializar la clase
add_action('plugins_loaded', array('Vehicle_Glossary_Mappings', 'get_instance'));
