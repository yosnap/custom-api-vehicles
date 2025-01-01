<?php
/**
 * Clase para manejar los glosarios de JetEngine
 */
class Glossary_Fields
{
    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Singleton
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
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
        return $this->get_glossary_response(55);
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
     * Obtiene las opciones de un glosario específico
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
