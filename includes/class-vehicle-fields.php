<?php
class Vehicle_Fields
{
    private static $instance = null;

    /**
     * Obtiene la instancia única de la clase
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registra los endpoints de la API
     */
    public function register_routes()
    {
        register_rest_route(
            'api-motor/v1',
            '/carrosseria',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_carrosseria_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            'api-motor/v1',
            '/estat-vehicle',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_estat_vehicle_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            'api-motor/v1',
            '/tipus-vehicle',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_tipus_vehicle_endpoint'),
                'permission_callback' => '__return_true'
            )
        );
    }

    /**
     * Lista de campos a excluir del procesamiento
     */
    private static $excluded_fields = [
        'tab-info-general_tab',
        'tab-info-general',
        'equipament-essencials-vehicle_tab',
        'equipament-essencials-vehicle',
        'carroseria-caravana_tab',
        'carroseria-caravana',
        'api_item_id',
        'sync_status'
    ];

    /**
     * Verifica si un campo debe ser excluido
     */
    public static function should_exclude_field($field_name)
    {
        return in_array($field_name, self::$excluded_fields);
    }

    /**
     * Obtiene la configuración de los campos meta
     */
    public static function get_meta_fields()
    {
        $fields = [
            // Campos booleanos
            'is-vip' => 'boolean',
            'venut' => 'boolean',
            'llibre-manteniment' => 'boolean',
            'revisions-oficials' => 'boolean',
            'impostos-deduibles' => 'boolean',
            'vehicle-a-canvi' => 'boolean',
            'garantia' => 'boolean',
            'vehicle-accidentat' => 'boolean',
            'aire-acondicionat' => 'boolean',
            'climatitzacio' => 'boolean',
            'vehicle-fumador' => 'boolean',

            // Campos numéricos
            'places-moto' => 'number',       // Nuevo campo
            'capacitat-total-l' => 'number', // Nuevo campo
            'dies-caducitat' => 'number',
            'preu' => 'number',
            'preu-mensual' => 'number',
            'preu-diari' => 'number',
            'preu-antic' => 'number',
            'quilometratge' => 'number',
            'cilindrada' => 'number',
            'potencia-cv' => 'number',
            'potencia-kw' => 'number',
            'portes-cotxe' => 'number',
            'places-cotxe' => 'number',
            'velocitat-maxima' => 'number',
            'acceleracio-0-60' => 'number',  // Agregar este campo
            'acceleracio-0-100' => 'number',
            'capacitat-total' => 'number',
            'maleters' => 'number',

            // Campos eléctricos
            'autonomia-wltp' => 'number',
            'autonomia-urbana-wltp' => 'number',
            'autonomia-extraurbana-wltp' => 'number',
            'autonomia-electrica' => 'number',
            'bateria' => 'radio',
            'cables-recarrega' => 'select',
            'connectors' => 'select',
            'velocitat-recarrega' => 'radio',
            'temps-recarrega-total' => 'number',
            'temps-recarrega-fins-80' => 'number',
            'n-motors' => 'number',
            'potencia-combinada' => 'number',
            'frenada-regenerativa' => 'switch',
            'one-pedal' => 'switch',
            'kw-motor-davant' => 'number',
            'cv-motor-davant' => 'number',
            'kw-motor-darrere' => 'number',
            'cv-motor-darrere' => 'number',
            'kw-motor-3' => 'number',
            'cv-motor-3' => 'number',
            'kw-motor-4' => 'number',
            'cv-motor-4' => 'number',

            // Campos de texto/selección
            'venedor' => 'glossary',
            'any' => 'text',
            'versio' => 'text',
            'nombre-propietaris' => 'text',
            'data-vip' => 'date',
            'traccio' => 'glossary',
            'roda-recanvi' => 'glossary',
            'color-exterior' => 'color',
            'segment' => 'glossary',
            'color-vehicle' => 'glossary',
            'tipus-vehicle' => 'glossary',
            'tipus-combustible' => 'glossary',
            'tipus-canvi' => 'glossary',
            'tipus-propulsor' => 'glossary',
            'estat-vehicle' => 'glossary',
            'tipus-tapisseria' => 'glossary',
            'color-tapisseria' => 'glossary',
            'emissions-vehicle' => 'glossary',
            'extres-cotxe' => 'glossary'
        ];

        // Filtrar los campos excluidos
        return array_diff_key($fields, array_flip(self::$excluded_fields));
    }

    /**
     * Obtiene la configuración de los campos de taxonomía
     */
    public static function get_taxonomy_fields()
    {
        return [
            'tipus-vehicle' => 'types-of-transport',
            'tipus-combustible' => 'tipus-combustible',
            'tipus-canvi' => 'tipus-de-canvi',
            'tipus-propulsor' => 'tipus-de-propulsor',
            'estat-vehicle' => 'estat-vehicle',
            'color-exterior' => 'color'
        ];
    }

    /**
     * Obtiene los valores permitidos para cada taxonomía
     */
    public static function get_allowed_taxonomy_values()
    {
        return [
            'tipus-vehicle' => [
                'autocaravana-camper',
                'cotxe',
                'moto-quad-atv',
                'vehicle-comercial'
            ],
            'tipus-combustible' => [
                'combustible-altres',
                'combustible-benzina',
                'combustible-biocombustible',
                'combustible-diesel',
                'combustible-electric',
                'combustible-electric-combustible',
                'electriccombustible',
                'combustible-gas-natural-gnc',
                'combustible-gas-liquat-glp',
                'hibrid',
                'hibrid-endollable',
                'combustible-hidrogen',
                'combustible-solar',
                'combustible-solar-hibrid'
            ],
            'tipus-canvi' => [
                'auto-sequencial',
                'automatic',
                'geartronic',
                'manual',
                'semi-automatic',
                'sequencial'
            ],
            'tipus-propulsor' => [
                'combustio',
                'electric',
                'hibrid',
                'hibrid-endollable'
            ],
            'estat-vehicle' => [
                'classic',
                'km0-gerencia',
                'lloguer',
                'nou',
                'ocasio',
                'renting',
                'seminou'
            ]
        ];
    }

    /**
     * Obtiene los valores permitidos para una taxonomía específica
     */
    public static function get_taxonomy_field_name($field)
    {
        $taxonomy_fields = self::get_taxonomy_fields();
        return isset($taxonomy_fields[$field]) ? $taxonomy_fields[$field] : $field;
    }

    /**
     * Obtiene los valores permitidos para una taxonomía específica
     */
    public static function get_taxonomy_allowed_values($field)
    {
        $allowed_values = self::get_allowed_taxonomy_values();
        return isset($allowed_values[$field]) ? $allowed_values[$field] : [];
    }

    /**
     * Obtiene las opciones para el campo carrosseria
     */
    public static function get_carrosseria_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return new WP_Error(
                    'jet_engine_missing',
                    'JetEngine no está activo',
                    array('status' => 500)
                );
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return new WP_Error(
                    'jet_engine_glossaries_missing',
                    'El módulo de glosarios de JetEngine no está disponible',
                    array('status' => 500)
                );
            }

            // Obtener las opciones del glosario de carrosseria (ID: 53)
            $options = $jet_engine->glossaries->filters->get_glossary_options(53);

            if (empty($options)) {
                return new WP_Error(
                    'no_options',
                    'No se encontraron opciones en el glosario',
                    array('status' => 404)
                );
            }

            // Convertir el array de opciones al formato esperado
            $formatted_options = array();
            foreach ($options as $value => $label) {
                $formatted_options[] = array(
                    'value' => $value,
                    'label' => $label
                );
            }

            return $formatted_options;

        } catch (Exception $e) {
            return new WP_Error(
                'error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Endpoint para obtener los tipos de carrosseria
     */
    public function get_carrosseria_endpoint()
    {
        $options = self::get_carrosseria_options();

        if (is_wp_error($options)) {
            return new WP_REST_Response($options, $options->get_error_data()['status']);
        }

        return new WP_REST_Response($options, 200);
    }

    /**
     * Obtiene el tipo de un campo específico
     */
    public static function get_field_type($field_name)
    {
        // Campos booleanos
        $boolean_fields = [
            'is-vip',
            'venut',
            'llibre-manteniment',
            'revisions-oficials',
            'impostos-deduibles',
            'vehicle-a-canvi',
            'garantia',
            'vehicle-accidentat',
            'aire-acondicionat',
            'climatitzacio',
            'vehicle-fumador'
        ];

        // Campos numéricos
        $number_fields = [
            'places-moto',          // Nuevo campo
            'capacitat-total-l',    // Nuevo campo
            'dies-caducitat',
            'preu',
            'preu-mensual',
            'preu-diari',
            'preu-antic',
            'quilometratge',
            'cilindrada',
            'potencia-cv',
            'potencia-kw',
            'portes-cotxe',
            'places-cotxe',
            'velocitat-maxima',
            'acceleracio-0-60',
            'acceleracio-0-100',
            'capacitat-total',
            'maleters',
            'autonomia-wltp',
            'autonomia-urbana-wltp',
            'autonomia-extraurbana-wltp',
            'autonomia-electrica',
            'temps-recarrega-total',
            'temps-recarrega-fins-80',
            'n-motors',
            'potencia-combinada',
            'kw-motor-davant',
            'cv-motor-davant',
            'kw-motor-darrere',
            'cv-motor-darrere',
            'kw-motor-3',
            'cv-motor-3',
            'kw-motor-4',
            'cv-motor-4'
        ];

        // Campos de fecha
        $date_fields = [
            'data-vip'
        ];

        // Campos de glosario
        $glossary_fields = [
            'venedor',
            'traccio',
            'roda-recanvi',
            'segment',
            'color-vehicle',
            'tipus-vehicle',
            'tipus-combustible',
            'tipus-canvi',
            'tipus-propulsor',
            'estat-vehicle',
            'tipus-tapisseria',
            'color-tapisseria',
            'emissions-vehicle',
            'extres-cotxe'
        ];

        if (in_array($field_name, $boolean_fields)) {
            return 'boolean';
        }
        if (in_array($field_name, $number_fields)) {
            return 'number';
        }
        if (in_array($field_name, $date_fields)) {
            return 'date';
        }
        if (in_array($field_name, $glossary_fields)) {
            return 'glossary';
        }

        // Por defecto, tratar como texto
        return 'text';
    }

    /**
     * Obtiene los campos con flag
     */
    public static function get_flag_fields()
    {
        return [
            'is-vip' => [
                'meta_key' => 'data-vip',
                'flag_key' => 'is-vip',
                'type' => 'date'
            ]
        ];
    }

    /**
     * Obtiene los tipos de campos
     */
    public static function get_field_types()
    {
        return [
            'traccio' => 'glossary',
            'roda-recanvi' => 'glossary',
            'color-exterior' => 'color',
            'segment' => 'glossary',
            'color-vehicle' => 'glossary',
            'aire-acondicionat' => 'switch',
            'climatitzacio' => 'switch',
            'vehicle-fumador' => 'switch',
            'tipus-tapisseria' => 'glossary',
            'color-tapisseria' => 'glossary',
            'extres-cotxe' => 'glossary'
        ];
    }

    /**
     * Obtiene los campos que son booleanos (switches)
     */
    public static function get_switch_fields()
    {
        return [
            'aire-acondicionat',
            'climatitzacio',
            'vehicle-fumador',
            'frenada-regenerativa',
            'one-pedal'
        ];
    }

    /**
     * Obtiene las opciones para un campo de glosario específico
     */
    public static function get_glossary_options($field)
    {
        switch ($field) {
            case 'emissions-vehicle':
                return self::get_emissions_vehicle_options();
            case 'segment':
            case 'carrosseria':
                return self::get_carrosseria_options();
            case 'roda-recanvi':
                return self::get_roda_recanvi_options();
            case 'traccio':
                return self::get_traccio_options();
            case 'color-vehicle':
                return self::get_color_vehicle_options();
            case 'tipus-tapisseria':
                return self::get_tipus_tapisseria_options();
            case 'color-tapisseria':
                return self::get_color_tapisseria_options();
            case 'extres-cotxe':
                return self::get_extres_cotxe_options();
            case 'venedor':
                return self::get_venedor_options();
            default:
                return [];
        }
    }

    /**
     * Obtiene las opciones para el campo venedor
     */
    public static function get_venedor_options()
    {
        return [
            'particular' => 'particular',
            'professional' => 'professional'
        ];
    }

    /**
     * Obtiene las opciones para el campo emissions-vehicle
     */
    public static function get_emissions_vehicle_options()
    {
        return [
            'euro1' => 'euro1',
            'euro2' => 'euro2',
            'euro3' => 'euro3',
            'euro4' => 'euro4',
            'euro5' => 'euro5',
            'euro6' => 'euro6'
        ];
    }

    /**
     * Obtiene las opciones para el campo de tracción
     */
    public static function get_traccio_options()
    {
        return [
            'darrere' => 't_darrere',
            'davant' => 't_davant',
            'integral-connectable' => 't_integral_connectable',
            'integral' => 't_integral'
        ];
    }

    /**
     * Obtiene las opciones para el campo de rueda de repuesto
     */
    public static function get_roda_recanvi_options()
    {
        return [
            'roda-substitucio' => 'roda_substitucio',
            'kit-reparacio' => 'r_kit_reparacio'
        ];
    }

    /**
     * Obtiene las opciones para el campo color-vehicle
     */
    public static function get_color_vehicle_options()
    {
        return [
            'bicolor' => 'bicolor',
            'blanc' => 'blanc',
            'negre' => 'negre',
            'gris' => 'gris',
            'antracita' => 'antracita',
            'beige' => 'beige',
            'camel' => 'camel',
            'marro' => 'marro',
            'blau' => 'blau',
            'bordeus' => 'bordeus',
            'granat' => 'granat',
            'lila' => 'lila',
            'vermell' => 'vermell',
            'taronja' => 'taronja',
            'groc' => 'groc',
            'verd' => 'verd',
            'altres' => 'altres-exterior',
            'rosa' => 'rosa',
            'daurat' => 'daurat'
        ];
    }

    /**
     * Obtiene las opciones para el campo tipus-tapisseria
     */
    public static function get_tipus_tapisseria_options()
    {
        return [
            'alcantara' => 'alcantara',
            'cuir' => 'cuir',
            'cuir-alcantara' => 'cuir-alcantara',
            'cuir-sintetic' => 'cuir-sintetic',
            'teixit' => 'teixit',
            'teixit-alcantara' => 'teixit-alcantara',
            'teixit-cuir' => 'teixit-cuir',
            'altres' => 'altres-tipus-tapisseria'
        ];
    }

    /**
     * Obtiene las opciones para el campo color-tapisseria
     */
    public static function get_color_tapisseria_options()
    {
        return [
            'bicolor' => 'tapisseria-bicolor',
            'negre' => 'tapisseria-negre',
            'antracita' => 'tapisseria-antracita',
            'gris' => 'tapisseria-gris',
            'blanc' => 'tapisseria-blanc',
            'beige' => 'tapisseria-beige',
            'camel' => 'tapisseria-camel',
            'marro' => 'tapisseria-marro',
            'bordeus' => 'tapisseria-bordeus',
            'granat' => 'tapisseria-granat',
            'blau' => 'tapisseria-blau',
            'lila' => 'tapisseria-lila',
            'vermell' => 'tapisseria-vermell',
            'taronja' => 'tapisseria-taronja',
            'groc' => 'tapisseria-groc',
            'verd' => 'tapisseria-verd',
            'altres' => 'altres-tapisseria'
        ];
    }

    /**
     * Obtiene las opciones para el campo extres-cotxe desde el glosario
     */
    public static function get_extres_cotxe_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            // Obtener las opciones del glosario de extras (ID: 54)
            $options = $jet_engine->glossaries->filters->get_glossary_options(54);

            if (empty($options)) {
                return [];
            }

            // Convertir el array de opciones al formato esperado
            $formatted_options = [];
            foreach ($options as $value => $label) {
                $formatted_options[$value] = $label;
            }

            return $formatted_options;

        } catch (Exception $e) {
            return [];
        }
    }
}
