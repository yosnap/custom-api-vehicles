<?php
class Vehicle_Fields
{
    private static $instance = null;
    private static $excluded_fields = [
        'tab-info-general_tab',
        'tab-info-general',
        'equipament-essencials-vehicle_tab',
        'equipament-essencials-vehicle',
        'carroseria-caravana_tab',
        'carroseria-caravana',
        'api_item_id',
        'sync_status',
        'data-vip',
        '_thumbnail_id',
        'ad_gallery',
        '_edit_lock',
        'sn4p_mad_api_id',
        '_edit_last',
        '_wp_trash_meta_status',
        '_wp_trash_meta_time',
        'jet_engine_store_count_ads-views',
        'jet_tax__estat-vehicle',
        'jet_tax__types-of-transport',
        'jet_tax__tipus-de-propulsor',
        'jet_tax__tipus-combustible',
        'jet_tax__marques-coches',
        'jet_tax__marques-de-moto',
        'jet_tax__tipus-de-canvi',
        'rank_math_internal_links_processed',
        'referencia_id',
        '_bricks_template_type',
        '_bricks_page_content_2',
        '_bricks_editor_mode',
        '_wp_desired_post_slug',
        'imatge-destacada-id',
        'tipus-de-vehicle',
        'segment',
        'venedor'
    ];

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
        $this->register_conditional_validation();
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
     * Obtiene la lista de campos excluidos
     */
    public static function get_excluded_fields() {
        return self::$excluded_fields;
    }

    /**
     * Verifica si un campo debe ser excluido
     */
    public static function should_exclude_field($field_name) {
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
            'preu-mensual' => 'text', // Cambiado de 'number' a 'text'
            'preu-diari' => 'text', // Cambiado de 'number' a 'text'
            'preu-antic' => 'text', // Cambiado de 'number' a 'text'
            'quilometratge' => 'number',
            'cilindrada' => 'number',
            'potencia-cv' => 'number',
            'potencia-kw' => 'number',
            'portes-cotxe' => 'number',
            'places-cotxe' => 'number',
            'velocitat-maxima' => 'number',
            'acceleracio-0-60' => 'number',
            'acceleracio-0-100' => 'number',
            'capacitat-total' => 'number',
            'maleters' => 'number',

            // Campos eléctricos
            'autonomia-wltp' => 'text',          // Cambiado de 'number' a 'text'
            'autonomia-urbana-wltp' => 'text',   // Cambiado de 'number' a 'text'
            'autonomia-extraurbana-wltp' => 'text', // Cambiado de 'number' a 'text'
            'autonomia-electrica' => 'text',     // Cambiado de 'number' a 'text'
            'bateria' => 'radio',
            'cables-recarrega' => 'glossary',
            'connectors' => 'glossary',
            'velocitat-recarrega' => 'radio',
            'temps-recarrega-total' => 'number',
            'temps-recarrega-fins-80' => 'number',
            'n-motors' => 'number',
            'potencia-combinada' => 'text',  // Cambiado de 'number' a 'text'
            'frenada-regenerativa' => 'switch',
            'one-pedal' => 'switch',
            'kw-motor-davant' => 'text',  // Cambiado de 'number' a 'text'
            'cv-motor-davant' => 'text',  // Cambiado de 'number' a 'text'
            'kw-motor-darrere' => 'text', // Cambiado de 'number' a 'text'
            'cv-motor-darrere' => 'text', // Cambiado de 'number' a 'text'
            'kw-motor-3' => 'text',       // Cambiado de 'number' a 'text'
            'cv-motor-3' => 'text',       // Cambiado de 'number' a 'text'
            'kw-motor-4' => 'text',       // Cambiado de 'number' a 'text'
            'cv-motor-4' => 'text',       // Cambiado de 'number' a 'text'

            // Campos de texto/selección
            'venedor' => 'glossary',
            'any' => 'text',
            'versio' => 'text',
            'nombre-propietaris' => 'text',
            'traccio' => 'glossary',
            'roda-recanvi' => 'glossary',
            'color-exterior' => 'color',
            'segment' => 'glossary',
            'color-vehicle' => 'glossary',
            'tipus-vehicle' => 'glossary',
            'tipus-combustible' => 'glossary',
            'tipus-canvi' => 'taxonomy',
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
            'tipus-canvi-cotxe' => 'tipus-de-canvi',
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

        return new WP_REST_Response([
            'status' => 'success',
            'total' => count($options),
            'data' => $options
        ], 200);
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
            'extres-cotxe',
            'cables-recarrega',
            'connectors'
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
            'extres-cotxe' => 'glossary',
            'cables-recarrega' => 'glossary',
            'connectors' => 'glossary'
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
        $options = [];
        switch ($field) {
            case 'emissions-vehicle':
                $options = self::get_emissions_vehicle_options();
                break;
            case 'segment':
            case 'carrosseria':
                $options = self::get_carrosseria_options();
                break;
            case 'roda-recanvi':
                $options = self::get_roda_recanvi_options();
                break;
            case 'traccio':
                $options = self::get_traccio_options();
                break;
            case 'color-vehicle':
                $options = self::get_color_vehicle_options();
                break;
            case 'tipus-tapisseria':
                $options = self::get_tipus_tapisseria_options();
                break;
            case 'color-tapisseria':
                $options = self::get_color_tapisseria_options();
                break;
            case 'extres-cotxe':
                $options = self::get_extres_cotxe_options();
                break;
            case 'venedor':
                $options = self::get_venedor_options();
                break;
            case 'cables-recarrega':
                $options = self::get_cables_recarrega_options();
                break;
            case 'connectors':
                $options = self::get_connectors_options();
                break;
            case 'tipus-de-moto':
                $options = self::get_tipus_de_moto_options();
                break;
        }

        return [
            'status' => 'success',
            'total' => count($options),
            'data' => $options
        ];
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
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('emissions-vehicle');

            if (!$glossary_id) {
                return [
                    'euro1' => 'euro1',
                    'euro2' => 'euro2',
                    'euro3' => 'euro3',
                    'euro4' => 'euro4',
                    'euro5' => 'euro5',
                    'euro6' => 'euro6'
                ];
            }

            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
                return [
                    'euro1' => 'euro1',
                    'euro2' => 'euro2',
                    'euro3' => 'euro3',
                    'euro4' => 'euro4',
                    'euro5' => 'euro5',
                    'euro6' => 'euro6'
                ];
            }

            return $options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener opciones de emissions-vehicle: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las opciones para el campo de tracción
     */
    public static function get_traccio_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('traccio');

            if (!$glossary_id) {
                return [
                    'darrere' => 't_darrere',
                    'davant' => 't_davant',
                    'integral' => 't_integral',
                    'integral-connectable' => 't_integral_connectable'
                ];
            }

            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
                return [
                    'darrere' => 't_darrere',
                    'davant' => 't_davant',
                    'integral' => 't_integral',
                    'integral-connectable' => 't_integral_connectable'
                ];
            }

            return $options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener opciones de traccio: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las opciones para el campo de rueda de repuesto
     */
    public static function get_roda_recanvi_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('roda-recanvi');

            if (!$glossary_id) {
                return [
                    'roda-substitucio' => 'roda_substitucio',
                    'kit-reparacio' => 'r_kit_reparacio'
                ];
            }

            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
                return [
                    'roda-substitucio' => 'roda_substitucio',
                    'kit-reparacio' => 'r_kit_reparacio'
                ];
            }

            return $options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener opciones de roda-recanvi: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las opciones para el campo color-vehicle
     */
    public static function get_color_vehicle_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('color-vehicle');

            if (!$glossary_id) {
                return self::get_color_vehicle_fallback();
            }

            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
                return self::get_color_vehicle_fallback();
            }

            return $options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener opciones de color-vehicle: " . $e->getMessage());
            return [];
        }
    }

    private static function get_color_vehicle_fallback()
    {
        return [
            'bicolor' => 'bicolor', 'blanc' => 'blanc', 'negre' => 'negre',
            'gris' => 'gris', 'antracita' => 'antracita', 'beige' => 'beige',
            'camel' => 'camel', 'marro' => 'marro', 'blau' => 'blau',
            'bordeus' => 'bordeus', 'granat' => 'granat', 'lila' => 'lila',
            'vermell' => 'vermell', 'taronja' => 'taronja', 'groc' => 'groc',
            'verd' => 'verd', 'altres' => 'altres-exterior', 'rosa' => 'rosa',
            'daurat' => 'daurat'
        ];
    }

    /**
     * Obtiene las opciones para el campo tipus-tapisseria
     */
    public static function get_tipus_tapisseria_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('tipus-tapisseria');

            if (!$glossary_id) {
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

            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
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

            return $options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener opciones de tipus-tapisseria: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las opciones para el campo color-tapisseria
     */
    public static function get_color_tapisseria_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('color-tapisseria');

            if (!$glossary_id) {
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

            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
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

            return $options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener opciones de color-tapisseria: " . $e->getMessage());
            return [];
        }
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

    /**
     * Obtiene las opciones para el campo cables-recarrega
     */
    public static function get_cables_recarrega_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('cables-recarrega');

            if (!$glossary_id) {
                return [
                    'tipus-1' => 'tipus-1',
                    'tipus-2' => 'tipus-2',
                    'tipus-3' => 'tipus-3'
                ];
            }

            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
                return [
                    'tipus-1' => 'tipus-1',
                    'tipus-2' => 'tipus-2',
                    'tipus-3' => 'tipus-3'
                ];
            }

            return $options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener opciones de cables-recarrega: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las opciones para el campo connectors
     */
    public static function get_connectors_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('connectors');

            if (!$glossary_id) {
                return [
                    'tipus-1' => 'tipus-1',
                    'tipus-2' => 'tipus-2',
                    'tipus-3' => 'tipus-3'
                ];
            }

            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
                return [
                    'tipus-1' => 'tipus-1',
                    'tipus-2' => 'tipus-2',
                    'tipus-3' => 'tipus-3'
                ];
            }

            return $options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener opciones de connectors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las opciones para el campo tipus-de-moto
     */
    public static function get_tipus_de_moto_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return []; // Si JetEngine no está disponible, devolver array vacío
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return []; // Si no hay glosarios, devolver array vacío
            }

            // Obtener las opciones del glosario de tipus-de-moto (usando el ID correcto)
            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('tipus-de-moto');
            
            if (!$glossary_id) {
                // Valor por defecto si no hay ID de glosario
                return [
                    'scooter' => 'Scooter',
                    'custom' => 'Custom',
                    'deportiva' => 'Deportiva',
                    'naked' => 'Naked',
                    'trail' => 'Trail',
                    'sr1' => 'SR1'
                ];
            }
            
            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
                // Valor por defecto si no hay opciones en el glosario
                return [
                    'scooter' => 'Scooter',
                    'custom' => 'Custom',
                    'deportiva' => 'Deportiva',
                    'naked' => 'Naked',
                    'trail' => 'Trail',
                    'sr1' => 'SR1'
                ];
            }

            return $options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener opciones de tipus-de-moto: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las opciones para el campo extres-moto
     */
    public static function get_extres_moto_options()
    {
        try {
            if (!function_exists('jet_engine')) {
                return [];
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return [];
            }

            // Obtener las opciones del glosario de extras moto (ID: 55)
            $options = $jet_engine->glossaries->filters->get_glossary_options(55);

            if (empty($options)) {
                // Valores por defecto si el glosario está vacío
                return [
                    'abs' => 'ABS',
                    'control-traccion' => 'Control de tracción',
                    'maletas' => 'Maletas',
                    'navegador' => 'Navegador',
                    'calefaccion' => 'Calefacción'
                ];
            }

            // Convertir el array de opciones al formato esperado
            $formatted_options = [];
            foreach ($options as $value => $label) {
                $formatted_options[$value] = $label;
            }

            return $formatted_options;

        } catch (Exception $e) {
            Vehicle_Debug_Handler::log("Error al obtener extres-moto: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Valida los campos obligatorios según el tipo de vehículo
     */
    public function validate_required_fields($prepared_post, $request) {
        // Solo validar si es una petición a la API
        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            return $prepared_post;
        }

        // Obtener la ruta de la petición
        $route = $request->get_route();
        
        // Solo validar si es una petición a nuestra API
        if (strpos($route, '/api-motor/v1') === false) {
            return $prepared_post;
        }

        // Obtener los datos del post
        $post_data = $request->get_params();
        
        // Validar el título del vehículo
        if (empty($post_data['titol-anunci']) && empty($prepared_post->post_title)) {
            return new WP_Error(
                'rest_invalid_field',
                'El campo vehicle_title es obligatorio',
                array('status' => 400)
            );
        }

        return $prepared_post;
    }
    
    /**
     * Registra filtro para validación condicional en REST API
     */
    public function register_conditional_validation() {
        // Registrar la validación solo para peticiones a la API REST
        add_filter('rest_pre_insert_singlecar', [$this, 'validate_required_fields'], 10, 2);
    }

    /**
     * Obtiene los campos que deben ser tratados como numéricos
     */
    public static function get_numeric_fields() {
        return [
            'places-moto',
            'capacitat-total-l',
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
            'acceleracio-0-100',
            'capacitat-total',
            'maleters',
            'temps-recarrega-total',
            'temps-recarrega-fins-80',
            'n-motors'
        ];
    }

    /**
     * Obtiene los campos que pueden contener valores especiales no numéricos
     */
    public static function get_special_numeric_fields() {
        return [
            'kw-motor-davant',
            'cv-motor-davant',
            'kw-motor-darrere',
            'cv-motor-darrere',
            'kw-motor-3',
            'cv-motor-3',
            'kw-motor-4',
            'cv-motor-4',
            'potencia-combinada',
            'autonomia-wltp',
            'autonomia-urbana-wltp',
            'autonomia-extraurbana-wltp',
            'autonomia-electrica',
            'preu-mensual', // Añadido preu-mensual a los campos especiales
            'cv-motor-davant-moto',
            'kw-motor-davant-moto',
            'cv-motor-darrere-moto',
            'kw-motor-darrere-moto'
        ];
    }
}
