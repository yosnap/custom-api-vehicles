<?php
/**
 * Class Vehicle_Controller
 * 
 * Controlador para endpoints de API relacionados con vehículos
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Vehicle_Controller {
    
    /**
     * Namespace de la API
     * 
     * @var string
     */
    protected $namespace = 'api-motor/v1';
    
    /**
     * Ruta base para los endpoints
     * 
     * @var string
     */
    protected $route = 'vehicles';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Registra todos los endpoints para vehículos
     */
    public function register_routes() {
        // Endpoint para tipos de transporte
        register_rest_route($this->namespace, '/' . $this->route . '/types-of-transport', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_vehicle_types'),
            'permission_callback' => '__return_true', // Modificado para garantizar acceso público
        ));
        
        // Endpoint para obtener vehículos
        register_rest_route($this->namespace, '/' . $this->route, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_vehicles'),
            'permission_callback' => '__return_true', // Modificado para garantizar acceso público
            'args' => $this->get_collection_params(),
        ));
        
        // Endpoint para obtener un vehículo específico
        register_rest_route($this->namespace, '/' . $this->route . '/(?P<id>[\d]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_vehicle'),
            'permission_callback' => '__return_true', // Modificado para garantizar acceso público
            'args' => array(
                'id' => array(
                    'description' => __('ID único del vehículo', 'custom-api-vehicles'),
                    'type'        => 'integer',
                    'required'    => true,
                ),
            ),
        ));
        
        // Endpoint para crear un vehículo
        register_rest_route($this->namespace, '/' . $this->route, array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_vehicle'),
            'permission_callback' => array($this, 'create_item_permissions_check'),
            'args' => $this->get_create_item_args(), // Reemplazado por un método que sí existe
        ));
        
        // Endpoint para actualizar un vehículo
        register_rest_route($this->namespace, '/' . $this->route . '/(?P<id>[\d]+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_vehicle'),
            'permission_callback' => array($this, 'update_item_permissions_check'),
            'args' => $this->get_update_item_args(), // Reemplazado por un método que sí existe
        ));
    }
    
    /**
     * Verifica permisos para obtener elementos
     * 
     * @return bool
     */
    public function get_items_permissions_check($request) {
        // Simplificamos para garantizar acceso público siempre
        return true;
    }
    
    /**
     * Obtiene los tipos de vehículos
     * 
     * @param WP_REST_Request $request Objeto de solicitud
     * @return WP_REST_Response
     */
    public function get_vehicle_types($request) {
        $types = array(
            array('id' => 1, 'name' => 'Coche'),
            array('id' => 2, 'name' => 'Moto'),
            array('id' => 3, 'name' => 'Furgoneta'),
            array('id' => 4, 'name' => 'Autocaravana'),
            array('id' => 5, 'name' => 'Camión'),
        );
        
        return rest_ensure_response($types);
    }
    
    /**
     * Obtiene la lista de vehículos con filtros
     * 
     * @param WP_REST_Request $request Objeto de solicitud
     * @return WP_REST_Response
     */
    public function get_vehicles($request) {
        $args = array(
            'post_type'      => 'singlecar', // Ajustar según el post type real
            'posts_per_page' => $request->get_param('per_page') ? $request->get_param('per_page') : 10,
            'paged'          => $request->get_param('page') ? $request->get_param('page') : 1,
        );
        
        // Agregar filtros de taxonomía si existen
        if ($request->get_param('brand')) {
            $args['tax_query'][] = array(
                'taxonomy' => 'brand',
                'field'    => 'term_id',
                'terms'    => $request->get_param('brand'),
            );
        }
        
        $query = new WP_Query($args);
        $vehicles = array();
        
        foreach ($query->posts as $post) {
            $data = $this->prepare_item_for_response($post, $request);
            $vehicles[] = $this->prepare_response_for_collection($data);
        }
        
        $response = rest_ensure_response($vehicles);
        
        $total_posts = $query->found_posts;
        $max_pages = ceil($total_posts / $args['posts_per_page']);
        
        $response->header('X-WP-Total', $total_posts);
        $response->header('X-WP-TotalPages', $max_pages);
        
        return $response;
    }
    
    /**
     * Obtiene un vehículo específico por ID
     * 
     * @param WP_REST_Request $request Objeto de solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function get_vehicle($request) {
        $id = (int) $request['id'];
        $post = get_post($id);
        
        if (empty($post) || $post->post_type !== 'singlecar') { // Ajustar según el post type real
            return new WP_Error(
                'rest_vehicle_not_found',
                __('Vehículo no encontrado.', 'custom-api-vehicles'),
                array('status' => 404)
            );
        }
        
        $data = $this->prepare_item_for_response($post, $request);
        return rest_ensure_response($data);
    }
    
    /**
     * Prepara un vehículo para la respuesta
     * 
     * @param WP_Post $post Objeto post
     * @param WP_REST_Request $request Objeto de solicitud
     * @return WP_REST_Response
     */
    public function prepare_item_for_response($post, $request) {
        // Obtener metadatos necesarios
        $price = get_post_meta($post->ID, 'vehicle_price', true);
        $brand = wp_get_post_terms($post->ID, 'brand', array('fields' => 'names'));
        $model = wp_get_post_terms($post->ID, 'model', array('fields' => 'names'));
        
        // Obtener URL de imagen destacada
        $featured_img_url = get_the_post_thumbnail_url($post->ID, 'full');
        
        // Determinar el tipo de vehículo (moto, autocaravana o vehículo comercial)
        $vehicle_type = get_post_meta($post->ID, 'vehicle_type', true);
        
        // Construir array de datos básicos
        $data = array(
            'id'           => $post->ID,
            'date'         => $post->post_date,
            'title'        => $post->post_title,
            'content'      => $post->post_content,
            'excerpt'      => $post->post_excerpt,
            'featured_img' => $featured_img_url ? $featured_img_url : '',
            'price'        => $price ? floatval($price) : 0,
            'brand'        => !empty($brand) ? $brand[0] : '',
            'model'        => !empty($model) ? $model[0] : '',
            'slug'         => $post->post_name,
            'link'         => get_permalink($post->ID),
            'vehicle_type' => $vehicle_type,
        );

        // Agregar campos comunes específicos que siempre queremos incluir
        $common_fields = [
            'color-vehicle' => 'string',
            'emissions-vehicle' => 'string',
        ];

        foreach ($common_fields as $field => $type) {
            $value = get_post_meta($post->ID, $field, true);
            if ($type === 'number' && $value !== '') {
                $data[$field] = floatval($value);
            } else {
                $data[$field] = $value;
            }
        }
        
        // Agregar campos específicos según el tipo de vehículo
        if ($vehicle_type === 'moto') {
            // Campos específicos para motos
            $moto_fields = [
                'tipus-de-moto' => 'string',
                'tipus-de-canvi-moto' => 'string',
                'places-moto' => 'number',
                'extres-moto' => 'string',
            ];
            
            foreach ($moto_fields as $field => $type) {
                $value = get_post_meta($post->ID, $field, true);
                if ($type === 'number' && $value !== '') {
                    $data[$field] = floatval($value);
                } else {
                    $data[$field] = $value;
                }
            }
            
            // Añadir taxonomías específicas para motos
            $marques_moto = wp_get_post_terms($post->ID, 'marques-de-moto', array('fields' => 'names'));
            $models_moto = wp_get_post_terms($post->ID, 'models-moto', array('fields' => 'names'));
            
            $data['marques-de-moto'] = !empty($marques_moto) ? $marques_moto[0] : '';
            $data['models-moto'] = !empty($models_moto) ? $models_moto[0] : '';
            
        } elseif ($vehicle_type === 'vehicle-comercial') {
            // Campos específicos para vehículos comerciales
            $comercial_fields = [
                'carroseria-vehicle-comercial' => 'string',
            ];
            
            foreach ($comercial_fields as $field => $type) {
                $value = get_post_meta($post->ID, $field, true);
                if ($type === 'number' && $value !== '') {
                    $data[$field] = floatval($value);
                } else {
                    $data[$field] = $value;
                }
            }
            
        } elseif ($vehicle_type === 'autocaravana') {
            // Campos específicos para autocaravanas
            $caravana_fields = [
                'tipus-carroseria-caravana' => 'string',
                'extres-autocaravana' => 'string',
                'extres-habitacle' => 'string',
            ];
            
            foreach ($caravana_fields as $field => $type) {
                $value = get_post_meta($post->ID, $field, true);
                if ($type === 'number' && $value !== '') {
                    $data[$field] = floatval($value);
                } else {
                    $data[$field] = $value;
                }
            }
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Prepara un elemento para la colección
     * 
     * @param WP_REST_Response $response
     * @return array
     */
    public function prepare_response_for_collection($response) {
        return $response->get_data();
    }
    
    /**
     * Obtiene los parámetros de colección
     * 
     * @return array
     */
    public function get_collection_params() {
        return array(
            'page' => array(
                'description'       => __('Página actual de la colección.', 'custom-api-vehicles'),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'per_page' => array(
                'description'       => __('Número máximo de elementos a devolver por página.', 'custom-api-vehicles'),
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'brand' => array(
                'description'       => __('ID de la marca para filtrar.', 'custom-api-vehicles'),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );
    }

    /**
     * Valida un campo de glosario
     * 
     * @param string $field_name Nombre del campo de glosario
     * @param string $value Valor a validar
     * @return bool|WP_Error True si es válido, WP_Error si no
     */
    private function validate_glossary_field($field_name, $value) {
        // Obtener opciones válidas del glosario
        $valid_options = $this->get_glossary_options($field_name);
        
        // Si el campo está vacío, se considera válido
        if (empty($value)) {
            return true;
        }
        
        // Si no hay opciones definidas, también es válido
        if (empty($valid_options)) {
            return true;
        }
        
        // Verificar si el valor está en las opciones válidas
        if (!in_array($value, $valid_options)) {
            return new WP_Error(
                'invalid_glossary_value',
                sprintf(
                    __('El valor "%s" no es válido para el campo "%s". Valores permitidos: %s', 'custom-api-vehicles'),
                    $value,
                    $field_name,
                    implode(', ', $valid_options)
                )
            );
        }
        
        return true;
    }
    
    /**
     * Obtiene las opciones de un glosario
     * 
     * @param string $field_name Nombre del campo de glosario
     * @return array Lista de opciones válidas
     */
    private function get_glossary_options($field_name) {
        // Esta función debería consultar la base de datos o una configuración
        // para obtener las opciones válidas de un glosario específico
        
        // Ejemplo de implementación con opciones de prueba
        $glossaries = [
            'tipus-de-moto' => ['Scooter', 'Custom', 'Deportiva', 'Naked', 'Trail'],
            'tipus-de-canvi-moto' => ['Manual', 'Automático', 'Semiautomático'],
            'extres-moto' => ['ABS', 'Control de tracción', 'Maletas', 'Navegador', 'Calefacción'],
            'carroseria-vehicle-comercial' => ['Furgoneta', 'Camión', 'Pickup', 'Van'],
            'tipus-carroseria-caravana' => ['Perfilada', 'Capuchina', 'Integral', 'Camper'],
            'extres-autocaravana' => ['Calefacción', 'Energía solar', 'TV', 'Nevera'],
            'extres-habitacle' => ['Ducha', 'Cocina', 'Baño', 'Cama doble'],
        ];
        
        // Registrar consulta en el log para depuración
        error_log("Opciones del glosario para $field_name: " . print_r(isset($glossaries[$field_name]) ? $glossaries[$field_name] : [], true));
        
        return isset($glossaries[$field_name]) ? $glossaries[$field_name] : [];
    }

    /**
     * Verifica que los campos obligatorios estén presentes según el tipo de vehículo
     * 
     * @param WP_REST_Request $request La solicitud
     * @return bool|WP_Error Verdadero si pasa la validación, objeto WP_Error si no
     */
    public function validate_vehicle_fields($request) {
        $vehicle_type = $request->get_param('tipus-vehicle');
        
        // Si no se especifica tipo de vehículo, no podemos continuar
        if (empty($vehicle_type)) {
            return new WP_Error(
                'missing_vehicle_type',
                __('El tipo de vehículo (tipus-vehicle) es obligatorio', 'custom-api-vehicles'),
                array('status' => 400)
            );
        }
        
        // Normalizar el tipo de vehículo a minúsculas
        $vehicle_type = strtolower(trim($vehicle_type));
        
        // Validación específica según el tipo de vehículo
        switch ($vehicle_type) {
            case 'cotxe':
                // Eliminada la validación de marques-cotxe como obligatorio
                break;
                
            case 'moto':
                if (empty($request->get_param('marques-de-moto'))) {
                    return new WP_Error(
                        'missing_moto_brand',
                        __('El campo marques-de-moto es obligatorio para vehículos tipo moto', 'custom-api-vehicles'),
                        array('status' => 400)
                    );
                }
                break;
                
            case 'autocaravana':
                if (empty($request->get_param('marques-autocaravana'))) {
                    return new WP_Error(
                        'missing_caravan_brand',
                        __('El campo marques-autocaravana es obligatorio para vehículos tipo autocaravana', 'custom-api-vehicles'),
                        array('status' => 400)
                    );
                }
                break;
                
            case 'vehicle-comercial':
                if (empty($request->get_param('marques-comercial'))) {
                    return new WP_Error(
                        'missing_commercial_brand',
                        __('El campo marques-comercial es obligatorio para vehículos comerciales', 'custom-api-vehicles'),
                        array('status' => 400)
                    );
                }
                break;
        }
        
        return true;
    }

    /**
     * Verifica permisos para crear un elemento
     *
     * @param WP_REST_Request $request Objeto de solicitud
     * @return bool|WP_Error
     */
    public function create_item_permissions_check($request) {
        return current_user_can('publish_posts');
    }

    /**
     * Verifica permisos para actualizar un elemento
     *
     * @param WP_REST_Request $request Objeto de solicitud
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request) {
        $post_id = (int) $request['id'];
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'singlecar') {
            return false;
        }
        
        return current_user_can('edit_post', $post_id);
    }

    /**
     * Obtiene los argumentos para crear un elemento
     * 
     * @return array
     */
    public function get_create_item_args() {
        return [
            'title' => [
                'description' => __('Título del vehículo', 'custom-api-vehicles'),
                'type' => 'string',
                'required' => true,
            ],
            'preu' => [
                'description' => __('Precio del vehículo', 'custom-api-vehicles'),
                'type' => 'number',
                'required' => true,
            ],
            'tipus-vehicle' => [
                'description' => __('Tipo de vehículo', 'custom-api-vehicles'),
                'type' => 'string',
                'required' => true,
                'enum' => ['cotxe', 'moto', 'autocaravana', 'vehicle-comercial'],
            ],
            // Campos opcionales
            'content' => [
                'description' => __('Contenido/descripción del vehículo', 'custom-api-vehicles'),
                'type' => 'string',
            ],
            'quilometratge' => [
                'description' => __('Kilometraje', 'custom-api-vehicles'),
                'type' => 'number',
            ],
        ];
    }

    /**
     * Obtiene los argumentos para actualizar un elemento
     * 
     * @return array
     */
    public function get_update_item_args() {
        $args = $this->get_create_item_args();
        
        // Para actualización, nada es requerido
        foreach ($args as $key => $value) {
            if (isset($args[$key]['required'])) {
                unset($args[$key]['required']);
            }
        }
        
        return $args;
    }

    /**
     * Crea un nuevo vehículo
     * 
     * @param WP_REST_Request $request Objeto de solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function create_vehicle($request) {
        // Implementar la creación del vehículo
        $params = $request->get_params();
        
        $post_data = [
            'post_title' => sanitize_text_field($params['title']),
            'post_content' => isset($params['content']) ? wp_kses_post($params['content']) : '',
            'post_status' => 'publish',
            'post_type' => 'singlecar',
        ];
        
        // Insertar el post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Guardar metadatos
        if (isset($params['preu'])) {
            update_post_meta($post_id, 'preu', floatval($params['preu']));
        }
        
        if (isset($params['tipus-vehicle'])) {
            update_post_meta($post_id, 'tipus-vehicle', sanitize_text_field($params['tipus-vehicle']));
        }
        
        if (isset($params['quilometratge'])) {
            update_post_meta($post_id, 'quilometratge', floatval($params['quilometratge']));
        }
        
        // Devolver el vehículo creado
        $vehicle = get_post($post_id);
        $data = $this->prepare_item_for_response($vehicle, $request);
        
        return rest_ensure_response($data);
    }

    /**
     * Actualiza un vehículo existente
     * 
     * @param WP_REST_Request $request Objeto de solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function update_vehicle($request) {
        $id = (int) $request['id'];
        $params = $request->get_params();
        
        // Verificar que el post existe
        $post = get_post($id);
        if (!$post || $post->post_type !== 'singlecar') {
            return new WP_Error(
                'rest_post_not_found',
                __('Vehículo no encontrado', 'custom-api-vehicles'),
                ['status' => 404]
            );
        }
        
        // Datos para actualizar
        $post_data = [
            'ID' => $id,
        ];
        
        if (isset($params['title'])) {
            $post_data['post_title'] = sanitize_text_field($params['title']);
        }
        
        if (isset($params['content'])) {
            $post_data['post_content'] = wp_kses_post($params['content']);
        }
        
        // Actualizar post si hay campos para actualizar
        if (count($post_data) > 1) {
            wp_update_post($post_data);
        }
        
        // Actualizar metadatos
        if (isset($params['preu'])) {
            update_post_meta($id, 'preu', floatval($params['preu']));
        }
        
        if (isset($params['tipus-vehicle'])) {
            update_post_meta($id, 'tipus-vehicle', sanitize_text_field($params['tipus-vehicle']));
        }
        
        if (isset($params['quilometratge'])) {
            update_post_meta($id, 'quilometratge', floatval($params['quilometratge']));
        }
        
        // Devolver el vehículo actualizado
        $vehicle = get_post($id);
        $data = $this->prepare_item_for_response($vehicle, $request);
        
        return rest_ensure_response($data);
    }
}

// Inicializar el controlador
$vehicle_controller = new Vehicle_Controller();
