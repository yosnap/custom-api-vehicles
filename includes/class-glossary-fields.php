<?php
/**
 * Class Glossary_Fields
 * 
 * Maneja los campos de glosario para vehículos
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Glossary_Fields {
    private static $instance = null;
    
    /**
     * Obtiene o crea la instancia única de la clase (patrón Singleton)
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Inicializar los campos de glosario
        add_action('init', array($this, 'register_glossary_fields'), 20);
        
        // Agregar validación para los campos de glosario en la API
        add_filter('rest_pre_insert_singlecar', array($this, 'validate_glossary_fields_api'), 10, 2);
        
        // Registrar endpoints de API para glosarios
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Registra los campos de glosario
     */
    public function register_glossary_fields() {
        // Si JetEngine no está disponible, no hacer nada
        if (!function_exists('jet_engine')) {
            return;
        }
        
        // Definir los campos de glosario y sus opciones
        $glossaries = array(
            'tipus-de-moto' => array(
                'label' => 'Tipo de Moto',
                'options' => array('Scooter', 'Custom', 'Deportiva', 'Naked', 'Trail'),
            ),
            'tipus-de-canvi-moto' => array(
                'label' => 'Tipo de Cambio (Moto)',
                'options' => array('Manual', 'Automático', 'Semiautomático'),
            ),
            'extres-moto' => array(
                'label' => 'Extras para Moto',
                'options' => array('ABS', 'Control de tracción', 'Maletas', 'Navegador', 'Calefacción'),
            ),
            'carroseria-vehicle-comercial' => array(
                'label' => 'Carrocería de Vehículo Comercial',
                'options' => array('Furgoneta', 'Camión', 'Pickup', 'Van'),
            ),
            'tipus-carroseria-caravana' => array(
                'label' => 'Tipo de Carrocería (Autocaravana)',
                'options' => array('Perfilada', 'Capuchina', 'Integral', 'Camper'),
            ),
            'extres-autocaravana' => array(
                'label' => 'Extras para Autocaravana',
                'options' => array('Calefacción', 'Energía solar', 'TV', 'Nevera'),
            ),
            'extres-habitacle' => array(
                'label' => 'Extras para Habitáculo',
                'options' => array('Ducha', 'Cocina', 'Baño', 'Cama doble'),
            ),
        );
        
        // No intentar registrar estos campos - ya están en JetEngine
        $skip_fields = [
            'extres-moto',
            'carroseria-vehicle-comercial',
            'tipus-carroseria-caravana',
            'extres-autocaravana',
            'extres-habitacle'
        ];
        
        // Registrar cada glosario como un campo personalizado
        foreach ($glossaries as $key => $glossary) {
            // Saltarse los campos que ya están en JetEngine
            if (in_array($key, $skip_fields)) {
                continue;
            }
            
            // Aquí iría la lógica para registrar el campo con JetEngine o ACF
            if (function_exists('jet_engine')) {
                // Registrar el campo si aún no existe
                $this->maybe_register_meta_field($key, $glossary['label'], 'select', $glossary['options']);
            }
        }
    }
    
    /**
     * Registra un campo de metadatos si aún no existe
     */
    private function maybe_register_meta_field($key, $label, $type, $options = array()) {
        // Verificar si el campo ya existe como meta registrado
        $existing_meta = get_registered_meta_keys('post', 'singlecar');
        
        if (!isset($existing_meta[$key])) {
            // Registrar el campo como meta para la API REST
            register_meta('post', $key, array(
                'show_in_rest' => true,
                'single' => true,
                'type' => 'string',
                'description' => $label,
            ));
            
            // Solo registrar con JetEngine si explícitamente se necesita
            // No intentamos registrar campos que ya existen en JetEngine
            // Las siguientes líneas son comentadas o eliminadas para evitar logs innecesarios
            /*
            if (function_exists('jet_engine')) {
                try {
                    // ... código para registrar con JetEngine ...
                } catch (Exception $e) {
                    Vehicle_Debug_Handler::log("Error al registrar campo con JetEngine: " . $e->getMessage());
                }
            }
            */
        }
    }
    
    /**
     * Valida los campos de glosario en la API REST
     */
    public function validate_glossary_fields_api($prepared_post, $request) {
        $glossary_fields = array(
            'tipus-de-moto',
            'tipus-de-canvi-moto',
            'extres-moto',
            'carroseria-vehicle-comercial',
            'tipus-carroseria-caravana',
            'extres-autocaravana',
            'extres-habitacle',
        );
        
        foreach ($glossary_fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                
                // Obtener opciones válidas para este glosario
                $options = $this->get_glossary_field_options($field);
                
                // Si hay opciones definidas y el valor no está en ellas, devolver error
                if (!empty($options) && !empty($value) && !in_array($value, $options)) {
                    return new WP_Error(
                        'invalid_glossary_value',
                        sprintf(
                            __('El valor "%s" no es válido para el campo "%s". Valores permitidos: %s', 'custom-api-vehicles'),
                            $value,
                            $field,
                            implode(', ', $options)
                        ),
                        array('status' => 400)
                    );
                }
            }
        }
        
        return $prepared_post;
    }
    
    /**
     * Obtiene las opciones válidas para un campo de glosario estático
     * 
     * @param string $field_name Nombre del campo de glosario
     * @return array Lista de opciones válidas
     */
    private function get_glossary_field_options($field_name) {
        // Definición de glosarios
        $glossaries = array(
            'tipus-de-moto' => array('Scooter', 'Custom', 'Deportiva', 'Naked', 'Trail'),
            'tipus-de-canvi-moto' => array('Manual', 'Automático', 'Semiautomático'),
            'extres-moto' => array('ABS', 'Control de tracción', 'Maletas', 'Navegador', 'Calefacción'),
            'carroseria-vehicle-comercial' => array('Furgoneta', 'Camión', 'Pickup', 'Van'),
            'tipus-carroseria-caravana' => array('Perfilada', 'Capuchina', 'Integral', 'Camper'),
            'extres-autocaravana' => array('Calefacción', 'Energía solar', 'TV', 'Nevera'),
            'extres-habitacle' => array('Ducha', 'Cocina', 'Baño', 'Cama doble'),
        );
        
        return isset($glossaries[$field_name]) ? $glossaries[$field_name] : array();
    }

    /**
     * Registra los endpoints de la API
     */
    public function register_routes()
    {
        // Endpoint para listar todos los glosarios disponibles
        register_rest_route(
            'api-motor/v1',
            '/glossaries',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_glossaries_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Extras de coche
        register_rest_route(
            'api-motor/v1',
            '/extras-coche',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_extras_coche_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Extras de moto
        register_rest_route(
            'api-motor/v1',
            '/extras-moto',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_extras_moto_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Extras de autocaravana
        register_rest_route(
            'api-motor/v1',
            '/extras-autocaravana',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_extras_autocaravana_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Extras de habitáculo
        register_rest_route(
            'api-motor/v1',
            '/extras-habitacle',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_extras_habitacle_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Color exterior
        register_rest_route(
            'api-motor/v1',
            '/color-exterior',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_color_exterior_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Tapicería
        register_rest_route(
            'api-motor/v1',
            '/tapisseria',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_tapisseria_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Color tapicería
        register_rest_route(
            'api-motor/v1',
            '/color-tapisseria',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_color_tapisseria_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Carrosseria cotxe
        register_rest_route(
            'api-motor/v1',
            '/carrosseria-cotxe',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_carrosseria_cotxe_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Carrosseria Moto
        register_rest_route(
            'api-motor/v1',
            '/carrosseria-moto',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_carrosseria_moto_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Carrosseria Caravana
        register_rest_route(
            'api-motor/v1',
            '/carrosseria-caravana',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_carrosseria_caravana_endpoint'),
                'permission_callback' => '__return_true'
            )
        );

        // Carrosseria Vehículos Comerciales
        register_rest_route(
            'api-motor/v1',
            '/carrosseria-veh-comercial',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_carrosseria_veh_comercial_endpoint'),
                'permission_callback' => '__return_true'
            )
        );
    }

    /**
     * Endpoint para obtener la lista de todos los glosarios
     */
    public function get_glossaries_endpoint()
    {
        try {
            if (!function_exists('jet_engine')) {
                return new WP_REST_Response(
                    new WP_Error(
                        'jet_engine_missing',
                        'JetEngine no está activo',
                        ['status' => 500]
                    ),
                    500
                );
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries)) {
                return new WP_REST_Response(
                    new WP_Error(
                        'glossaries_missing',
                        'El componente de glosarios no está disponible',
                        ['status' => 500]
                    ),
                    500
                );
            }

            $glossaries = $jet_engine->glossaries->get_glossaries_for_js();

            // Eliminar la primera opción que es "Select glossary"
            if (!empty($glossaries) && $glossaries[0]['value'] === '') {
                array_shift($glossaries);
            }

            return new WP_REST_Response($glossaries, 200);

        } catch (Exception $e) {
            return new WP_REST_Response(
                new WP_Error(
                    'glossaries_error',
                    'Error al obtener los glosarios: ' . $e->getMessage(),
                    [
                        'status' => 500,
                        'error_details' => [
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]
                    ]
                ),
                500
            );
        }
    }

    /**
     * Endpoint para obtener extras de coche
     */
    public function get_extras_coche_endpoint()
    {
        return $this->get_glossary_response(54);
    }

    /**
     * Endpoint para obtener extras de moto
     */
    public function get_extras_moto_endpoint()
    {
        return $this->get_glossary_response(55);
    }

    /**
     * Endpoint para obtener extras de autocaravana
     */
    public function get_extras_autocaravana_endpoint()
    {
        return $this->get_glossary_response(56);
    }

    /**
     * Endpoint para obtener extras de habitáculo
     */
    public function get_extras_habitacle_endpoint()
    {
        return $this->get_glossary_response(57);
    }

    /**
     * Endpoint para obtener colores exteriores
     */
    public function get_color_exterior_endpoint()
    {
        return $this->get_glossary_response(51);
    }

    /**
     * Endpoint para obtener tipos de tapicería
     */
    public function get_tapisseria_endpoint()
    {
        return $this->get_glossary_response(52);
    }

    /**
     * Endpoint para obtener colores de tapicería
     */
    public function get_color_tapisseria_endpoint()
    {
        return $this->get_glossary_response(53);
    }

    /**
     * Endpoint para obtener tipos de carrocería de coche
     */
    public function get_carrosseria_cotxe_endpoint()
    {
        return $this->get_glossary_response(41);
    }

    /**
     * Endpoint para obtener carrocerías de moto
     */
    public function get_carrosseria_moto_endpoint()
    {
        return $this->get_glossary_response(42);
    }

    /**
     * Endpoint para obtener carrocerías de caravana
     */
    public function get_carrosseria_caravana_endpoint()
    {
        return $this->get_glossary_response(43);
    }

    /**
     * Endpoint para obtener carrocerías de vehículos comerciales
     */
    public function get_carrosseria_veh_comercial_endpoint()
    {
        return $this->get_glossary_response(44);
    }

    /**
     * Obtiene y formatea la respuesta de un glosario
     */
    private function get_glossary_response($glossary_id)
    {
        $options = $this->get_glossary_options($glossary_id);

        if (is_wp_error($options)) {
            return new WP_REST_Response($options, $options->get_error_data()['status']);
        }

        return new WP_REST_Response($options, 200);
    }

    /**
     * Obtiene las opciones de un glosario dinámico de JetEngine por ID
     * 
     * @param int $glossary_id ID del glosario en JetEngine
     * @return array|WP_Error Opciones del glosario o error
     */
    private function get_glossary_options($glossary_id)
    {
        try {
            if (!function_exists('jet_engine')) {
                return new WP_Error(
                    'jet_engine_missing',
                    'JetEngine no está activo',
                    ['status' => 500]
                );
            }

            $jet_engine = jet_engine();

            if (!isset($jet_engine->glossaries) || !isset($jet_engine->glossaries->filters)) {
                return new WP_Error(
                    'glossaries_missing',
                    'El componente de glosarios no está disponible',
                    ['status' => 500]
                );
            }

            // Obtener las opciones del glosario
            $options = $jet_engine->glossaries->filters->get_glossary_options($glossary_id);

            if (empty($options)) {
                return new WP_Error(
                    'empty_glossary',
                    'El glosario está vacío o no existe',
                    ['status' => 404]
                );
            }

            // Convertir el array de opciones al formato esperado
            $formatted_options = [];
            foreach ($options as $value => $label) {
                $formatted_options[] = [
                    'value' => $value,
                    'label' => $label
                ];
            }

            return $formatted_options;

        } catch (Exception $e) {
            return new WP_Error(
                'glossary_error',
                'Error al obtener el glosario: ' . $e->getMessage(),
                [
                    'status' => 500,
                    'error_details' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                ]
            );
        }
    }
}
