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

// Incluir funciones de manejo de medios
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/singlecar-endpoints/media-handlers.php';

// Incluir funciones de procesamiento de campos
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/singlecar-endpoints/field-processors.php';

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
            'permission_callback' => array($this, 'get_items_permissions_check'), // Modificado para permitir acceso durante pruebas
            'args' => $this->get_collection_params(),
        ));
        
        // Endpoint para obtener un vehículo específico
        register_rest_route($this->namespace, '/' . $this->route . '/(?P<id>[\d]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_vehicle'),
            'permission_callback' => array($this, 'get_item_permissions_check'),
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
            'args' => $this->get_create_item_args(),
            // Habilitar subida de archivos
            'upload_files' => true,
        ));
        
        // Endpoint para actualizar un vehículo
        register_rest_route($this->namespace, '/' . $this->route . '/(?P<id>[\d]+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_vehicle'),
            'permission_callback' => array($this, 'update_item_permissions_check'),
            'args' => $this->get_update_item_args(),
            // Habilitar subida de archivos
            'upload_files' => true,
        ));
    }
    
    /**
     * Verifica permisos para obtener elementos
     * 
     * @return bool
     */
    public function get_items_permissions_check($request) {
        // Permitir lectura pública de vehículos (GET request)
        return true;
    }
    
    /**
     * Verifica permisos para obtener un elemento
     * 
     * @return bool
     */
    public function get_item_permissions_check($request) {
        // Permitir lectura pública de vehículos individuales (GET request)
        return true;
        
        // Código original comentado para referencia futura
        /*
        $post_id = (int) $request['id'];
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'singlecar') { // Ajustar según el post type real
            return new WP_Error(
                'rest_post_invalid_id',
                __('ID de vehículo inválido.', 'custom-api-vehicles'),
                array('status' => 404)
            );
        }
        
        if ($post && $post->post_status === 'publish') {
            return true;
        }
        
        return current_user_can('read_post', $post_id);
        */
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
     * Prepare a single item for response.
     *
     * @param WP_Post         $post    Post object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function prepare_item_for_response($post, $request) {
        // Debug para verificar que se ejecuta el método
        if ($post->ID == 278524) {
            Vehicle_Debug_Handler::log("DEBUG Controller 278524 - Ejecutando prepare_item_for_response");
        }
        
        // Obtener todos los campos personalizados
        $meta_fields = get_post_meta($post->ID);
        
        // Añadir ID y título
        $data = array(
            'id' => $post->ID,
            'author_id' => $post->post_author,
            'data-creacio' => $post->post_date,
            'status' => $post->post_status,
            'slug' => $post->post_name,
            'titol-anunci' => $post->post_title,
            'descripcio-anunci' => $post->post_content,
            'plugin_version_test' => 'MIGRATED_PLUGIN_ACTIVE' // Identificador único para confirmar que el plugin migrado está activo
        );
        
        // Verificar si existe el campo extres-autocaravana o extras-autocaravana
        $has_extres_autocaravana = isset($meta_fields['extres-autocaravana']);
        $has_extras_autocaravana = isset($meta_fields['extras-autocaravana']);
        
        if ($has_extras_autocaravana && !$has_extres_autocaravana) {
            // Si solo existe extras-autocaravana, copiarlo a extres-autocaravana
            $meta_fields['extres-autocaravana'] = $meta_fields['extras-autocaravana'];
            $has_extres_autocaravana = true;
        }
        
        // Procesar cada campo personalizado
        foreach ($meta_fields as $field_name => $field_values) {
            // Ignorar campos internos de WordPress
            if (substr($field_name, 0, 1) === '_') {
                continue;
            }
            
            // Procesamiento específico para tipus-carroseria-caravana
            if ($field_name === 'tipus-carroseria-caravana') {
                // Mapeo directo para valores específicos de tipus-carroseria-caravana
                $direct_mappings = [
                    'c-perfilada' => 'Perfilada',
                    'c-caputxina' => 'Caputxina',
                    'c-integral' => 'Integral',
                    'c-camper' => 'Camper'
                ];
                
                // Verificar si hay un mapeo directo
                if (isset($direct_mappings[$field_values[0]])) {
                    $label = $direct_mappings[$field_values[0]];
                    $data[$field_name] = $label;
                    continue; // Importante: salir del bucle para este campo
                }
                
                // Si no hay mapeo directo, intentar obtener del glosario
                $glossary_id = 43; // ID conocido del glosario de tipus-carroseria-caravana
                
                if (function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
                    // Intentar obtener por ID conocido
                    $glossary_data = jet_engine()->glossaries->data->get_item_for_edit($glossary_id);
                    if ($glossary_data && !empty($glossary_data['fields'])) {
                        foreach ($glossary_data['fields'] as $glossary_field) {
                            $field_value = isset($glossary_field['value']) ? $glossary_field['value'] : '';
                            $field_label = isset($glossary_field['label']) ? $glossary_field['label'] : '';
                            
                            if ($field_value === $field_values[0]) {
                                $data[$field_name] = $field_label;
                                continue 2; // Importante: salir de ambos bucles
                            }
                        }
                    }
                }
                
                // Si no se encuentra ninguna coincidencia, devolver el valor original
                $data[$field_name] = $field_values[0];
                continue; // Importante: salir del bucle para este campo
            }
            
            // Procesamiento específico para extres-autocaravana
            if ($field_name === 'extres-autocaravana' || $field_name === 'extras-autocaravana') {
                // Si estamos procesando extras-autocaravana pero ya procesamos extres-autocaravana, saltar
                if ($field_name === 'extras-autocaravana' && isset($data['extres-autocaravana'])) {
                    continue;
                }
                
                // Usar siempre extres-autocaravana como nombre de campo en la respuesta
                $api_field_name = 'extres-autocaravana';
                
                // Verificar si es un campo de glosario
                if (function_exists('is_glossary_field') && is_glossary_field($api_field_name)) {
                    // Obtener el valor como array simple
                    $values = get_post_meta($post->ID, $api_field_name, true);
                    Vehicle_Debug_Handler::log("Valor recuperado para extres-autocaravana: " . print_r($values, true));
                    
                    // Deserializar si es necesario (manejar formato serializado de PHP)
                    if (is_string($values) && strpos($values, 'a:') === 0) {
                        $values = unserialize($values);
                        Vehicle_Debug_Handler::log("Valor deserializado para extres-autocaravana: " . print_r($values, true));
                    }
                    
                    // Asegurarse de que values sea un array
                    if (!is_array($values)) {
                        $values = [$values];
                    }
                    
                    // Limpiar valores vacíos
                    $values = array_filter($values, function($value) {
                        return !empty($value) && $value !== '';
                    });
                    
                    // Reindexar el array para obtener un array simple sin índices
                    $values = array_values($values);
                    
                    // Devolver directamente los values en lugar de los labels
                    $data[$api_field_name] = $values;
                    continue; // Importante: salir del bucle para este campo
                } else {
                    // Obtener el valor directamente
                    $data[$api_field_name] = get_post_meta($post->ID, $api_field_name, true);
                    continue;
                }
            }
            
            // Procesamiento específico para extras-cotxe
            if ($field_name === 'extras-cotxe' || $field_name === 'extres-cotxe') {
                // Si estamos procesando extras-cotxe pero ya procesamos extres-cotxe, saltar
                if ($field_name === 'extras-cotxe' && isset($data['extres-cotxe'])) {
                    continue;
                }
                
                // Usar siempre extres-cotxe como nombre de campo en la respuesta
                $api_field_name = 'extres-cotxe';
                
                // Verificar si es un campo de glosario
                if (function_exists('is_glossary_field') && is_glossary_field($api_field_name)) {
                    // Obtener el valor como array simple
                    $values = get_post_meta($post->ID, $api_field_name, true);
                    Vehicle_Debug_Handler::log("Valor recuperado para extres-cotxe: " . print_r($values, true));
                    
                    // Deserializar si es necesario (manejar formato serializado de PHP)
                    if (is_string($values) && strpos($values, 'a:') === 0) {
                        $values = unserialize($values);
                        Vehicle_Debug_Handler::log("Valor deserializado para extres-cotxe: " . print_r($values, true));
                    }
                    
                    // Asegurarse de que values sea un array
                    if (!is_array($values)) {
                        $values = [$values];
                    }
                    
                    // Limpiar valores vacíos
                    $values = array_filter($values, function($value) {
                        return !empty($value) && $value !== '';
                    });
                    
                    // Reindexar el array para obtener un array simple sin índices
                    $values = array_values($values);
                    
                    // Devolver directamente los values en lugar de los labels
                    $data[$api_field_name] = $values;
                    continue; // Importante: salir del bucle para este campo
                } else {
                    // Obtener el valor directamente
                    $data[$api_field_name] = get_post_meta($post->ID, $api_field_name, true);
                    continue;
                }
            }
            
            // Procesamiento específico para carroseria-vehicle-comercial
            if ($field_name === 'carroseria-vehicle-comercial') {
                $api_field_name = 'carroseria-vehicle-comercial';
                
                // Verificar si es un campo de glosario
                if (function_exists('is_glossary_field') && is_glossary_field($api_field_name)) {
                    // Obtener el ID del glosario
                    $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($api_field_name);
                    
                    // Obtener las opciones del glosario
                    $options = $this->get_glossary_options($api_field_name);
                    
                    // Obtener el valor
                    $value = get_post_meta($post->ID, $api_field_name, true);
                    // Valor recuperado para carroseria-vehicle-comercial
                    
                    // Buscar etiqueta en las opciones del glosario
                    if (!empty($value) && isset($options[$value])) {
                        $data[$api_field_name] = $options[$value];
                        // Label encontrado para carroseria-vehicle-comercial
                    } else {
                        $data[$api_field_name] = $value; // Si no hay etiqueta, usar el valor original
                        // No se encontró label para carroseria-vehicle-comercial
                    }
                    
                    continue; // Importante: salir del bucle para este campo
                } else {
                    // Obtener el valor directamente
                    $data[$api_field_name] = get_post_meta($post->ID, $api_field_name, true);
                    continue;
                }
            }
            
            // Verificar si es un campo de extras que debe procesarse especialmente
            $extras_fields = ['extres-cotxe', 'extres-autocaravana', 'extres-moto', 'extres-habitacle'];
            if (in_array($field_name, $extras_fields)) {
                // Procesar campo de extras
                $value = $field_values[0];
                
                // Deserializar si es necesario (manejar formato serializado de PHP)
                if (is_string($value) && strpos($value, 'a:') === 0) {
                    $value = unserialize($value);
                    Vehicle_Debug_Handler::log("Valor deserializado para {$field_name}: " . print_r($value, true));
                }
                
                // Asegurarse de que sea un array
                if (!is_array($value)) {
                    $value = [$value];
                }
                
                // Limpiar valores vacíos
                $value = array_filter($value, function($val) {
                    return !empty($val) && $val !== '';
                });
                
                // Reindexar el array para obtener un array simple sin índices
                $value = array_values($value);
                
                $data[$field_name] = $value;
            } else {
                // Para el resto de campos, simplemente añadirlos al array de datos
                $data[$field_name] = maybe_unserialize($field_values[0]);
            }
        }
        
        // Añadir URL de imagen destacada si existe
        $featured_image_id = get_post_thumbnail_id($post->ID);
        if ($featured_image_id) {
            $featured_image_url = wp_get_attachment_url($featured_image_id);
            $data['imatge-destacada-url'] = $featured_image_url;
        }
        
        // Procesar estado activo del anuncio con la misma lógica que get_vehicle_details_common
        $this->process_anunci_actiu($post, $data);
        
        // Procesar campo anunci-destacat
        $this->process_anunci_destacat($post, $data, $meta_fields);
        
        // Procesar taxonomías
        $this->process_taxonomies($post, $data);
        
        return rest_ensure_response($data);
    } // Cierre del método prepare_item_for_response
    
    /**
     * Procesa el campo anunci-destacat con la misma lógica que get_vehicle_details_common
     * 
     * @param WP_Post $post
     * @param array &$data
     * @param array $meta_fields
     */
    private function process_anunci_destacat($post, &$data, $meta_fields) {
        // Usar la misma lógica que get_anunci_destacat_value
        $vehicle_id = $post->ID;
        
        // Debug específico para vehículo 278524
        if ($vehicle_id == 278524) {
            Vehicle_Debug_Handler::log("DEBUG Controller 278524 - Meta fields is-vip: " . (isset($meta_fields['is-vip']) ? print_r($meta_fields['is-vip'], true) : 'NO EXISTE'));
        }
        
        // Verificar si existe el campo is-vip
        if (isset($meta_fields['is-vip'][0])) {
            $is_vip_value = $meta_fields['is-vip'][0];
            $is_vip_value_str = (string)$is_vip_value;
            $is_vip_value_lower = strtolower(trim($is_vip_value_str));
            
            if ($vehicle_id == 278524) {
                Vehicle_Debug_Handler::log("DEBUG Controller 278524 - is-vip value: '{$is_vip_value}' (tipo: " . gettype($is_vip_value) . ") (string: '{$is_vip_value_str}') (lowercase: '{$is_vip_value_lower}')");
            }
            
            // Considerar varios valores como "destacado"
            $is_destacado = false;
            
            // Verificar strings comunes
            if ($is_vip_value_lower === 'true' || $is_vip_value_lower === 'yes' || 
                $is_vip_value_lower === 'si' || $is_vip_value_lower === 'on') {
                $is_destacado = true;
            }
            
            // Verificar valores numéricos
            if (!$is_destacado && is_numeric($is_vip_value_str) && intval($is_vip_value_str) > 0) {
                $is_destacado = true;
            }
            
            // Verificar valores booleanos
            if (!$is_destacado && is_bool($is_vip_value) && $is_vip_value === true) {
                $is_destacado = true;
            }
            
            if ($vehicle_id == 278524) {
                Vehicle_Debug_Handler::log("DEBUG Controller 278524 - is_destacado: " . ($is_destacado ? 'true' : 'false'));
            }
            
            if ($is_destacado) {
                if ($vehicle_id == 278524) {
                    Vehicle_Debug_Handler::log("DEBUG Controller 278524 - Estableciendo anunci-destacat a 1");
                }
                $data['anunci-destacat'] = 1;
                return;
            }
        } else {
            if ($vehicle_id == 278524) {
                Vehicle_Debug_Handler::log("DEBUG Controller 278524 - is-vip no existe en meta_fields");
            }
        }
        
        // Verificar si existe data-vip (campo de fecha de VIP)
        if (isset($meta_fields['data-vip'][0]) && !empty($meta_fields['data-vip'][0])) {
            $data_vip = $meta_fields['data-vip'][0];
            // Si tiene fecha VIP y no está vacía, considerar como destacado
            if ($data_vip !== '0000-00-00' && $data_vip !== '') {
                // Verificar si la fecha VIP no ha expirado
                $data_vip_timestamp = strtotime($data_vip);
                $current_timestamp = time();
                if ($data_vip_timestamp > $current_timestamp) {
                    $data['anunci-destacat'] = 1;
                    return;
                }
            }
        }
        
        // Verificar también otros campos que podrían indicar destacado
        $destacado_fields = ['destacado', 'featured', 'vip', 'premium'];
        foreach ($destacado_fields as $field) {
            if (isset($meta_fields[$field][0])) {
                $value = trim(strtolower($meta_fields[$field][0]));
                if ($value === 'true' || $value === '1' || $value === 'yes' || $value === 'si') {
                    $data['anunci-destacat'] = 1;
                    return;
                }
            }
        }
        
        if ($vehicle_id == 278524) {
            Vehicle_Debug_Handler::log("DEBUG Controller 278524 - Estableciendo anunci-destacat a 0 (no se encontraron campos destacados)");
        }
        $data['anunci-destacat'] = 0;
    }
    
    /**
     * Procesa las taxonomías del vehículo
     * 
     * @param WP_Post $post
     * @param array &$data
     */
    private function process_taxonomies($post, &$data) {
        $taxonomies = [
            'types-of-transport' => 'tipus-vehicle',
            'marques-coches' => 'marques-cotxe',
            'estat-vehicle' => 'estat-vehicle',
            'tipus-de-propulsor' => 'tipus-propulsor',
            'tipus-combustible' => 'tipus-combustible',
            'marques-de-moto' => 'marques-moto',
            'tipus-de-canvi' => 'tipus-canvi'
        ];
        
        foreach ($taxonomies as $taxonomy => $field_name) {
            $terms = wp_get_post_terms($post->ID, $taxonomy, ['fields' => 'names']);
            if (!is_wp_error($terms) && !empty($terms)) {
                $data[$field_name] = $terms[0];
            }
        }
    }
    
    /**
     * Procesa el estado activo del anuncio aplicando la lógica de caducidad
     * 
     * @param WP_Post $post
     * @param array &$data
     */
    private function process_anunci_actiu($post, &$data) {
        // Obtener el valor original del campo anunci-actiu
        $anunci_actiu_original = get_post_meta($post->ID, 'anunci-actiu', true);
        
        // Si el anuncio ya está marcado como inactivo, mantenerlo así
        if ($anunci_actiu_original !== 'true') {
            $data['anunci-actiu'] = 'false';
            return;
        }
        
        // Verificar si la caducidad está habilitada
        $expiry_enabled = get_option('vehicles_api_expiry_enabled', '1');
        if ($expiry_enabled !== '1') {
            // Si la caducidad está deshabilitada, mantener el valor original
            $data['anunci-actiu'] = 'true';
            return;
        }
        
        // Si está marcado como activo, verificar si ha expirado
        $dies_caducitat = intval(get_post_meta($post->ID, 'dies-caducitat', true));
        
        // Si no hay dies-caducitat configurado o es 0, usar valor por defecto
        if ($dies_caducitat <= 0) {
            $dies_caducitat = intval(get_option('vehicles_api_default_expiry_days', 365));
        }
        
        $data_creacio = strtotime($post->post_date);
        $data_actual = current_time('timestamp');
        $dies_transcorreguts = floor(($data_actual - $data_creacio) / (60 * 60 * 24));
        
        // Solo marcar como inactivo si estaba activo pero ha expirado
        if ($dies_transcorreguts > $dies_caducitat) {
            $data['anunci-actiu'] = 'false';
        } else {
            // Si está activo y no ha expirado, mantenerlo activo
            $data['anunci-actiu'] = 'true';
        }
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
        // Obtener el ID del glosario desde la configuración de mapeos
        $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field_name);
        
        if (!$glossary_id) {
            return [];
        }
        
        // Verificar si existe la función get_glossary_options en field-processors.php
        if (function_exists('get_glossary_options')) {
            $options = get_glossary_options($field_name);
            return $options;
        }
        
        // Alternativa: obtener directamente del glosario de JetEngine
        if (function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
            $glossary_data = jet_engine()->glossaries->data->get_item_for_edit($glossary_id);
            
            if ($glossary_data && !empty($glossary_data['fields'])) {
                $options = [];
                
                foreach ($glossary_data['fields'] as $glossary_field) {
                    if (isset($glossary_field['value']) && isset($glossary_field['label'])) {
                        $options[$glossary_field['value']] = $glossary_field['label'];
                    }
                }
                
                return $options;
            }
        }
        
        return [];
    }
    
    /**
     * Verifica que los campos obligatorios estén presentes según el tipo de vehículo
     * 
     * @param WP_REST_Request $request La solicitud
     * @return bool|WP_Error Verdadero si pasa la validación, objeto WP_Error si no
     */
    public function validate_vehicle_fields($request) {
        $params = $request->get_params();
        $is_update = isset($params['id']) && !empty($params['id']);
        $vehicle_type = $request->get_param('tipus-vehicle');
        
        // Para actualizaciones (PUT), si no se especifica el tipo de vehículo, intentamos obtenerlo del post existente
        if ($is_update && empty($vehicle_type)) {
            $post_id = (int) $request['id'];
            $stored_vehicle_type = get_post_meta($post_id, 'tipus-vehicle', true);
            if (!empty($stored_vehicle_type)) {
                $vehicle_type = $stored_vehicle_type;
            }
        }
        
        // Si no se especifica tipo de vehículo y es una creación (POST), no podemos continuar
        if (empty($vehicle_type) && !$is_update) {
            return new WP_Error(
                'missing_vehicle_type',
                __('El tipo de vehículo (tipus-vehicle) es obligatorio', 'custom-api-vehicles'),
                array('status' => 400)
            );
        }
        
        // Verificar que se ha proporcionado una imagen destacada (solo para POST)
        if (!$is_update) {
            $has_image = false;
            
            // Verificar si se ha proporcionado una imagen destacada como archivo
            if (isset($_FILES['imatge-destacada']) && !empty($_FILES['imatge-destacada']['tmp_name'])) {
                $has_image = true;
            }
            // Verificar si se ha proporcionado una imagen destacada como ID
            elseif ($request->get_param('imatge-destacada-id') && !empty($request->get_param('imatge-destacada-id'))) {
                $has_image = true;
            }
            // Verificar si se ha proporcionado una imagen destacada como URL
            elseif ($request->get_param('imatge-destacada-url') && !empty($request->get_param('imatge-destacada-url'))) {
                $has_image = true;
            }
            // Verificar si se ha proporcionado una imagen destacada directamente
            elseif ($request->get_param('imatge-destacada') && !empty($request->get_param('imatge-destacada'))) {
                $has_image = true;
            }
            
            if (!$has_image) {
                return new WP_Error(
                    'missing_featured_image',
                    __('La imagen destacada es obligatoria. Debe proporcionar una imagen a través del campo "imatge-destacada"', 'custom-api-vehicles'),
                    array('status' => 400)
                );
            }
            
            // Validar campos obligatorios para POST
            if (empty($request->get_param('title'))) {
                return new WP_Error(
                    'missing_title',
                    __('El título del vehículo es obligatorio', 'custom-api-vehicles'),
                    array('status' => 400)
                );
            }
            
            if (empty($request->get_param('preu'))) {
                return new WP_Error(
                    'missing_price',
                    __('El precio del vehículo es obligatorio', 'custom-api-vehicles'),
                    array('status' => 400)
                );
            }
        }
        
        // Si tenemos el tipo de vehículo, normalizarlo a minúsculas
        if (!empty($vehicle_type)) {
            $vehicle_type = strtolower(trim($vehicle_type));
            
            // Validación específica según el tipo de vehículo (para POST y PUT)
            switch ($vehicle_type) {
                case 'cotxe':
                    // Campos obligatorios para coches (tanto en POST como PUT)
                    $cotxe_required_fields = [
                        'marques-cotxe' => 'La marca del coche es obligatoria',
                        'models-cotxe' => 'El modelo del coche es obligatorio',
                        'estat-vehicle' => 'El estado del vehículo es obligatorio'
                    ];
                    
                    foreach ($cotxe_required_fields as $field => $error_message) {
                        // Para PUT, solo validar si no existe ya en el post
                        if ($is_update) {
                            $post_id = (int) $request['id'];
                            $existing_value = get_post_meta($post_id, $field, true);
                            
                            // Si el campo ya tiene un valor y no se está intentando actualizar, omitir la validación
                            if (!empty($existing_value) && !isset($params[$field])) {
                                continue;
                            }
                            
                            // Si se está intentando actualizar con un valor vacío, mostrar error
                            if (isset($params[$field]) && empty($params[$field])) {
                                return new WP_Error(
                                    'missing_' . str_replace('-', '_', $field),
                                    __($error_message, 'custom-api-vehicles'),
                                    array('status' => 400)
                                );
                            }
                        } 
                        // Para POST, siempre validar
                        else if (empty($request->get_param($field))) {
                            return new WP_Error(
                                'missing_' . str_replace('-', '_', $field),
                                __($error_message, 'custom-api-vehicles'),
                                array('status' => 400)
                            );
                        }
                    }
                    break;
                    
                case 'moto':
                    // Para motos, siempre validar marques-de-moto (tanto en POST como PUT)
                    if (empty($request->get_param('marques-de-moto'))) {
                        return new WP_Error(
                            'missing_moto_brand',
                            __('El campo marques-de-moto es obligatorio para vehículos tipo moto', 'custom-api-vehicles'),
                            array('status' => 400)
                        );
                    }
                    
                    // Validar otros campos específicos de motos
                    $moto_required_fields = [
                        'cilindrada' => 'La cilindrada es obligatoria para motos',
                        'tipus-moto' => 'El tipo de moto es obligatorio'
                    ];
                    
                    foreach ($moto_required_fields as $field => $error_message) {
                        if (empty($request->get_param($field))) {
                            return new WP_Error(
                                'missing_' . str_replace('-', '_', $field),
                                __($error_message, 'custom-api-vehicles'),
                                array('status' => 400)
                            );
                        }
                    }
                    break;
                    
                case 'autocaravana':
                    // Solo validar en POST o si se está actualizando este campo específicamente
                    if (!$is_update || isset($params['marques-autocaravana'])) {
                        if (empty($request->get_param('marques-autocaravana'))) {
                            return new WP_Error(
                                'missing_caravan_brand',
                                __('El campo marques-autocaravana es obligatorio para vehículos tipo autocaravana', 'custom-api-vehicles'),
                                array('status' => 400)
                            );
                        }
                    }
                    break;
                    
                case 'vehicle-comercial':
                    // Solo validar en POST o si se está actualizando este campo específicamente
                    if (!$is_update || isset($params['marques-comercial'])) {
                        if (empty($request->get_param('marques-comercial'))) {
                            return new WP_Error(
                                'missing_commercial_brand',
                                __('El campo marques-comercial es obligatorio para vehículos comerciales', 'custom-api-vehicles'),
                                array('status' => 400)
                            );
                        }
                    }
                    
                    // Validar carroseria-vehicle-comercial
                    if (!$is_update || isset($params['carroseria-vehicle-comercial'])) {
                        if (empty($request->get_param('carroseria-vehicle-comercial'))) {
                            return new WP_Error(
                                'missing_commercial_body',
                                __('El campo carroseria-vehicle-comercial es obligatorio para vehículos comerciales', 'custom-api-vehicles'),
                                array('status' => 400)
                            );
                        }
                        
                        // Validar que el valor exista en el glosario
                        $carroseria_value = $request->get_param('carroseria-vehicle-comercial');
                        if (function_exists('validate_glossary_values')) {
                            $validation = validate_glossary_values('carroseria-vehicle-comercial', [$carroseria_value]);
                            
                            if (!$validation['valid']) {
                                $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id('carroseria-vehicle-comercial');
                                $valid_values = get_valid_glossary_values($glossary_id);
                                
                                return new WP_Error(
                                    'invalid_commercial_body',
                                    __('El valor proporcionado para carroseria-vehicle-comercial no es válido. Valores válidos: ' . implode(', ', $valid_values), 'custom-api-vehicles'),
                                    array('status' => 400)
                                );
                            }
                        }
                    }
                    break;
            }
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
        return user_can_create_vehicle();
    }

    /**
     * Verifica permisos para actualizar un elemento
     *
     * @param WP_REST_Request $request Objeto de solicitud
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request) {
        $post_id = (int) $request['id'];
        return user_can_edit_vehicle($post_id);
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
            'imatge-destacada' => [
                'description' => __('Imagen destacada del vehículo', 'custom-api-vehicles'),
                'required' => true,
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
        // Validar campos obligatorios
        $validation = $this->validate_vehicle_fields($request);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Implementar la creación del vehículo
        $params = $request->get_params();
        
        // Validar campos de glosario
        if (function_exists('validate_glossary_values')) {
            $invalid_fields = [];
            
            // Validar campos de glosario específicos según el tipo de vehículo
            $vehicle_type = isset($params['tipus-vehicle']) ? $params['tipus-vehicle'] : '';
            
            // Campos de glosario comunes a todos los tipos de vehículo
            $common_glossary_fields = [
                'traccio', 'segment', 'color-vehicle', 'emissions-vehicle'
            ];
            
            // Campos de glosario específicos por tipo de vehículo
            $vehicle_specific_fields = [];
            
            switch ($vehicle_type) {
                case 'cotxe':
                    $vehicle_specific_fields = ['extres-cotxe', 'tipus-tapisseria', 'color-tapisseria'];
                    break;
                case 'moto':
                    $vehicle_specific_fields = ['extres-moto', 'tipus-de-moto'];
                    break;
                case 'autocaravana':
                    $vehicle_specific_fields = ['extres-autocaravana', 'carrosseria-caravana', 'extres-habitacle'];
                    break;
                case 'vehicle-comercial':
                    $vehicle_specific_fields = ['carroseria-vehicle-comercial'];
                    
                    // Validación específica para carroseria-vehicle-comercial
                    if (isset($params['carroseria-vehicle-comercial'])) {
                        $carroseria_value = $params['carroseria-vehicle-comercial'];
                        Vehicle_Debug_Handler::log("Validando carroseria-vehicle-comercial: " . $carroseria_value);
                        
                        // Validar que el valor sea válido para el glosario
                        if (function_exists('validate_specific_glossary_field')) {
                            $validation_result = validate_specific_glossary_field('carroseria-vehicle-comercial', $carroseria_value);
                            
                            if (is_wp_error($validation_result)) {
                                Vehicle_Debug_Handler::log("Error de validación para carroseria-vehicle-comercial: " . $validation_result->get_error_message());
                                return $validation_result;
                            }
                        }
                    }
                    break;
            }
            
            // Combinar campos comunes y específicos
            $glossary_fields = array_merge($common_glossary_fields, $vehicle_specific_fields);
            
            // Validar cada campo de glosario
            foreach ($glossary_fields as $field) {
                if (isset($params[$field])) {
                    $field_value = $params[$field];
                    
                    // Si es un array, validar cada valor
                    if (is_array($field_value)) {
                        $validation = validate_glossary_values($field, $field_value);
                    } else {
                        $validation = validate_glossary_values($field, [$field_value]);
                    }
                    
                    if (!$validation['valid']) {
                        $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field);
                        $valid_values = get_valid_glossary_values($glossary_id);
                        
                        $invalid_fields[$field] = [
                            'invalid_values' => $validation['invalid_values'],
                            'valid_values' => $valid_values
                        ];
                    }
                }
            }
            
            // Si hay campos inválidos, devolver error
            if (!empty($invalid_fields)) {
                Vehicle_Debug_Handler::log("Campos inválidos en $vehicle_type: " . print_r($invalid_fields, true));
                
                $error_messages = [];
                foreach ($invalid_fields as $field => $data) {
                    $invalid_values = implode(', ', $data['invalid_values']);
                    $valid_values = implode(', ', $data['valid_values']);
                    $error_messages[] = "Campo $field: valores inválidos ($invalid_values). Valores válidos: $valid_values";
                }
                
                return new WP_Error(
                    'invalid_glossary_values',
                    __('Valores de glosario inválidos: ' . implode('; ', $error_messages), 'custom-api-vehicles'),
                    array('status' => 400)
                );
            }
        }
        
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
        
        // Procesar todos los campos personalizados
        foreach ($params as $key => $value) {
            // Excluir campos que ya se han procesado o que no son metadatos
            if (in_array($key, ['title', 'content'])) {
                continue;
            }
            
            // Manejo especial para extres-cotxe
            if ($key === 'extres-cotxe') {
                if (is_array($value)) {
                    // Guardar como array simple (formato requerido por JSM)
                    update_post_meta($post_id, $key, $value);
                    Vehicle_Debug_Handler::log("Guardado $key como array simple en create_vehicle: " . print_r($value, true));
                } else {
                    // Si no es un array, convertirlo en array
                    $array_value = [$value];
                    update_post_meta($post_id, $key, $array_value);
                    Vehicle_Debug_Handler::log("Guardado $key como array simple (valor único) en create_vehicle: " . print_r($array_value, true));
                }
            }
            // Guardar el campo como metadato
            else if (is_array($value)) {
                // Para campos de tipo array (como extres-autocaravana)
                delete_post_meta($post_id, $key); // Eliminar valores anteriores
                foreach ($value as $single_value) {
                    add_post_meta($post_id, $key, $single_value);
                }
            } else {
                // Para campos de tipo escalar
                update_post_meta($post_id, $key, $value);
            }
        }
        
        // Procesar imágenes (imagen destacada y galería)
        if (function_exists('process_vehicle_images')) {
            process_vehicle_images($post_id, $params);
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
        $post_id = (int) $request['id'];
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'singlecar') {
            return new WP_Error(
                'rest_post_invalid_id',
                __('Vehículo no válido.', 'custom-api-vehicles'),
                array('status' => 404)
            );
        }
        
        // Validar campos obligatorios
        $validation = $this->validate_vehicle_fields($request);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $params = $request->get_params();
        
        // Validar campos de glosario
        if (function_exists('validate_glossary_values')) {
            $invalid_fields = [];
            
            // Obtener el tipo de vehículo del post existente si no se proporciona
            $vehicle_type = isset($params['tipus-vehicle']) ? $params['tipus-vehicle'] : get_post_meta($post_id, 'tipus-vehicle', true);
            
            // Campos de glosario comunes a todos los tipos de vehículo
            $common_glossary_fields = [
                'traccio', 'segment', 'color-vehicle', 'emissions-vehicle'
            ];
            
            // Campos de glosario específicos por tipo de vehículo
            $vehicle_specific_fields = [];
            
            switch ($vehicle_type) {
                case 'cotxe':
                    $vehicle_specific_fields = ['extres-cotxe', 'tipus-tapisseria', 'color-tapisseria'];
                    break;
                case 'moto':
                    $vehicle_specific_fields = ['extres-moto', 'tipus-de-moto'];
                    break;
                case 'autocaravana':
                    $vehicle_specific_fields = ['extres-autocaravana', 'carrosseria-caravana', 'extres-habitacle'];
                    break;
                case 'vehicle-comercial':
                    $vehicle_specific_fields = ['carroseria-vehicle-comercial'];
                    
                    // Validación específica para carroseria-vehicle-comercial
                    if (isset($params['carroseria-vehicle-comercial'])) {
                        $carroseria_value = $params['carroseria-vehicle-comercial'];
                        Vehicle_Debug_Handler::log("Validando carroseria-vehicle-comercial en update: " . $carroseria_value);
                        
                        // Validar que el valor sea válido para el glosario
                        if (function_exists('validate_specific_glossary_field')) {
                            $validation_result = validate_specific_glossary_field('carroseria-vehicle-comercial', $carroseria_value);
                            
                            if (is_wp_error($validation_result)) {
                                Vehicle_Debug_Handler::log("Error de validación para carroseria-vehicle-comercial en update: " . $validation_result->get_error_message());
                                return $validation_result;
                            }
                        }
                    }
                    break;
            }
            
            // Combinar campos comunes y específicos
            $glossary_fields = array_merge($common_glossary_fields, $vehicle_specific_fields);
            
            // Validar cada campo de glosario que se está actualizando
            foreach ($glossary_fields as $field) {
                if (isset($params[$field])) {
                    $field_value = $params[$field];
                    
                    // Si es un array, validar cada valor
                    if (is_array($field_value)) {
                        $validation = validate_glossary_values($field, $field_value);
                    } else {
                        $validation = validate_glossary_values($field, [$field_value]);
                    }
                    
                    if (!$validation['valid']) {
                        $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field);
                        $valid_values = get_valid_glossary_values($glossary_id);
                        
                        $invalid_fields[$field] = [
                            'invalid_values' => $validation['invalid_values'],
                            'valid_values' => $valid_values
                        ];
                    }
                }
            }
            
            // Si hay campos inválidos, devolver error
            if (!empty($invalid_fields)) {
                Vehicle_Debug_Handler::log("Campos inválidos en $vehicle_type: " . print_r($invalid_fields, true));
                
                $error_messages = [];
                foreach ($invalid_fields as $field => $data) {
                    $invalid_values = implode(', ', $data['invalid_values']);
                    $valid_values = implode(', ', $data['valid_values']);
                    $error_messages[] = "Campo $field: valores inválidos ($invalid_values). Valores válidos: $valid_values";
                }
                
                return new WP_Error(
                    'invalid_glossary_values',
                    __('Valores de glosario inválidos: ' . implode('; ', $error_messages), 'custom-api-vehicles'),
                    array('status' => 400)
                );
            }
        }
        
        // Actualizar el título y contenido si se proporcionan
        $post_data = [
            'ID' => $post_id
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
        
        // Procesar todos los campos personalizados
        foreach ($params as $key => $value) {
            // Excluir campos que ya se han procesado o que no son metadatos
            if (in_array($key, ['id', 'title', 'content'])) {
                continue;
            }
            
            // Manejo especial para extres-cotxe
            if ($key === 'extres-cotxe') {
                if (is_array($value)) {
                    // Guardar como array simple (formato requerido por JSM)
                    update_post_meta($post_id, $key, $value);
                    Vehicle_Debug_Handler::log("Actualizado $key como array simple en update_vehicle: " . print_r($value, true));
                } else {
                    // Si no es un array, convertirlo en array
                    $array_value = [$value];
                    update_post_meta($post_id, $key, $array_value);
                    Vehicle_Debug_Handler::log("Actualizado $key como array simple (valor único) en update_vehicle: " . print_r($array_value, true));
                }
            }
            // Guardar el campo como metadato
            else if (is_array($value)) {
                // Para campos de tipo array (como extres-autocaravana)
                delete_post_meta($post_id, $key); // Eliminar valores anteriores
                foreach ($value as $single_value) {
                    add_post_meta($post_id, $key, $single_value);
                }
            } else {
                // Para campos de tipo escalar
                update_post_meta($post_id, $key, $value);
            }
        }
        
        // Procesar imágenes (imagen destacada y galería)
        if (function_exists('process_vehicle_images')) {
            process_vehicle_images($post_id, $params);
        }
        
        // Devolver el vehículo actualizado
        $vehicle = get_post($post_id);
        $data = $this->prepare_item_for_response($vehicle, $request);
        
        return rest_ensure_response($data);
    }
}

// Inicializar el controlador
$vehicle_controller = new Vehicle_Controller();
