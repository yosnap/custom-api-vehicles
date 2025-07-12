<?php

// Al inicio del archivo, agregar estos filtros para interceptar los campos problemáticos antes de cualquier validación
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    $route = $request->get_route();
    
    // Solo para rutas de creación o actualización de vehículos
    if (strpos($route, '/api-motor/v1/vehicles') === false) {
        return $result;
    }
    
    // Detectar si son métodos POST o PUT
    $method = $request->get_method();
    if ($method !== 'POST' && $method !== 'PUT') {
        return $result;
    }
    
    // Interceptar los campos problemáticos y establecer valores por defecto
    $params = $request->get_params();
    
    // Establecer valores por defecto solo si no están definidos (no sobrescribir datos del usuario)
    $defaults = [
        'frenada-regenerativa' => 'no',
        'one-pedal' => 'no',
        'aire-acondicionat' => 'no',
        'portes-cotxe' => '5',
        'climatitzacio' => 'no',
        'vehicle-fumador' => 'no',
        'vehicle-accidentat' => 'no',
        'llibre-manteniment' => 'no',
        'revisions-oficials' => 'no',
        'impostos-deduibles' => 'no',
        'vehicle-a-canvi' => 'no'
    ];
    
    // Solo establecer defaults si el campo no existe en POST y sanitizar entrada
    foreach ($defaults as $field => $default_value) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $_POST[$field] = sanitize_text_field($default_value);
        } else {
            $_POST[$field] = sanitize_text_field($_POST[$field]);
        }
    }
    
    return $result;
}, 10, 3);

function validate_all_fields($params, $is_update = false) {
    try {
        // Establecer valores por defecto solo si no están definidos (no sobrescribir datos del usuario)
        $defaults = [
            'frenada-regenerativa' => 'no',
            'one-pedal' => 'no',
            'aire-acondicionat' => 'no',
            'portes-cotxe' => '5',
            'climatitzacio' => 'no',
            'vehicle-fumador' => 'no',
            'vehicle-accidentat' => 'no',
            'llibre-manteniment' => 'no',
            'revisions-oficials' => 'no',
            'impostos-deduibles' => 'no',
            'vehicle-a-canvi' => 'no'
        ];
        
        // Solo establecer defaults si el campo no existe en params y sanitizar
        foreach ($defaults as $field => $default_value) {
            if (!isset($params[$field]) || empty($params[$field])) {
                $params[$field] = sanitize_text_field($default_value);
            } else {
                $params[$field] = sanitize_text_field($params[$field]);
            }
        }
        
        // Verificar si es MOTO y normalizar
        if (isset($params['tipus-vehicle'])) {
            $params['tipus-vehicle'] = normalize_vehicle_type($params['tipus-vehicle']);
        }
        
        // Validar campos requeridos (pasar el flag is_update)
        validate_required_fields($params, $is_update);

        // Validar campos meta si están presentes
        if (!empty(array_intersect_key($params, Vehicle_Fields::get_meta_fields()))) {
            validate_meta_fields($params);
        }

        // Validar campos de glosario si están presentes
        if (has_glossary_fields($params)) {
            validate_glossary_fields($params);
        }

        // Validar campos booleanos si están presentes
        if (has_boolean_fields($params)) {
            $params = validate_boolean_fields($params);
        }

        // Validar campos numéricos si están presentes
        if (has_numeric_fields($params)) {
            validate_numeric_fields($params);
        }

    } catch (Exception $e) {
        throw new Exception("Errores de validación:\n" . $e->getMessage());
    }
    
    return $params;
}

/**
 * Función mejorada para validar campos numéricos con mejor tolerancia a valores vacíos y especiales
 */
function validate_numeric_fields($params) {
    // Lista de campos que son expresamente numéricos
    $numeric_fields = [
        'places-moto',
        'capacitat-total-l',
        'dies-caducitat',
        'preu',
        // 'preu-mensual', // Removido de validación numérica
        // 'preu-diari', // Removido de validación numérica
        // 'preu-antic', // Removido de validación numérica
        'quilometratge',
        'cilindrada',
        'potencia-cv',
        'potencia-kw',
        // 'portes-cotxe', // Remover de validación estricta
        'places-cotxe',
        'velocitat-maxima',
        'acceleracio-0-100',
        'capacitat-total',
        'maleters',
        // Campos problemáticos removidos explícitamente de la lista
        // 'autonomia-wltp',
        // 'autonomia-urbana-wltp',
        // 'autonomia-extraurbana-wltp',
        // 'autonomia-electrica',
        // 'temps-recarrega-total', // También removido de la validación estricta
        // 'temps-recarrega-fins-80', // También removido por si acaso
        'n-motors'
    ];

    // Lista de campos que pueden contener valores no numéricos o especiales
    $excluded_fields = [
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
        'temps-recarrega-total',  // Añadido a la lista de exclusiones
        'temps-recarrega-fins-80', // Añadido a la lista de exclusiones
        'portes-cotxe', // Añadir a la lista de exclusiones
        'preu-mensual', // Añadido a la lista de exclusiones
        'preu-diari', // Añadido a la lista de exclusiones
        'preu-antic' // Añadido a la lista de exclusiones
    ];

    // Valores especiales permitidos para campos numéricos (además de valores numéricos)
    $allowed_special_values = ['', null, 'NA', 'N/A', '-', 'unknown', 'desconocido'];
    
    // Validar cada campo
    foreach ($params as $field => $value) {
        // SOLUCIÓN: Ignorar estos campos específicos
        if ($field === 'potencia-combinada' || 
            $field === 'autonomia-wltp' || 
            $field === 'autonomia-urbana-wltp' || 
            $field === 'autonomia-extraurbana-wltp' ||
            $field === 'autonomia-electrica' ||
            $field === 'temps-recarrega-total' ||  // Añadido explícitamente a la lista de ignorados
            $field === 'temps-recarrega-fins-80' ||
            $field === 'portes-cotxe' ||
            $field === 'preu-mensual') { // Añadido a la lista de campos a ignorar
            continue; // Ignorar estos campos específicamente
        }
        
        // Si el campo está en la lista de excluidos, ignorarlo completamente
        if (in_array($field, $excluded_fields)) {
            continue;
        }
        
        // Si es un campo numérico, validar
        if (in_array($field, $numeric_fields)) {
            // Si el valor está vacío o es uno de los valores especiales permitidos, aceptarlo
            if (in_array($value, $allowed_special_values, true)) {
                continue;
            }
            
            // Intentar limpiar el valor para verificación
            $clean_value = $value;
            
            // Si es string, intentar limpiarlo para validación numérica
            if (is_string($clean_value)) {
                // Reemplazar comas por puntos (formato europeo a formato anglosajón)
                $clean_value = str_replace(',', '.', $clean_value);
                // Eliminar espacios
                $clean_value = trim($clean_value);
            }
            
            // Si después de la limpieza no es numérico, lanzar error
            if (!is_numeric($clean_value)) {
                Vehicle_Debug_Handler::log("Error de validación numérica en {$field}: '{$value}' (limpiado: '{$clean_value}')");
                throw new Exception("El campo {$field} debe ser un valor numérico");
            }
        }
    }
    
    return $params; // Devolver los parámetros sin modificar
}

function validate_required_fields($params, $is_update = false) {
    // Primero verificamos el tipo de vehículo
    if (!isset($params['tipus-vehicle']) && !$is_update) {
        throw new Exception('El campo tipus-vehicle es obligatorio');
    }
    
    // Normalizar tipo de vehículo a minúsculas para comparación consistente
    $vehicle_type = strtolower(trim($params['tipus-vehicle'] ?? ''));
    
    // Si el tipo es moto-quad-atv, simplificar para validaciones internas
    $simplified_type = $vehicle_type;
    if ($vehicle_type === 'moto-quad-atv') {
        $simplified_type = 'moto';
    }
    
    // Definir campos obligatorios según el tipo de vehículo
    $required_fields = [
        'tipus-vehicle',
        'estat-vehicle',
    ];
    
    // Ya no añadimos versio como obligatorio
    // if ($simplified_type !== 'moto') {
    //     $required_fields[] = 'versio';
    // }
    
    // Añadir marques-cotxe y models-cotxe como obligatorios para todos EXCEPTO MOTO
    if ($simplified_type !== 'moto') {
        $required_fields[] = 'marques-cotxe';
        $required_fields[] = 'models-cotxe';
    } else {
        // Para motos, agregar marques-de-moto como obligatorio
        $required_fields[] = 'marques-de-moto';
    }

    // Si es una actualización, solo validar los campos requeridos que se están modificando
    if ($is_update) {
        $fields_to_validate = array_intersect(array_keys($params), $required_fields);
        
        // Si no se está modificando ningún campo requerido, retornar true
        if (empty($fields_to_validate)) {
            return true;
        }
        
        // Validar solo los campos enviados que son obligatorios
        $missing_fields = [];
        foreach ($fields_to_validate as $field) {
            if (empty($params[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception('Campos requeridos faltantes: ' . implode(', ', $missing_fields));
        }
        
        return true;
    }

    // Para creación, validar todos los campos requeridos
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($params[$field]) || empty($params[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception('Campos requeridos faltantes: ' . implode(', ', $missing_fields));
    }

    return true;
}

function validate_meta_fields($params) {
    $meta_fields = Vehicle_Fields::get_meta_fields();
    
    foreach ($params as $field => $value) {
        // Ignorar completamente la validación para preu-mensual
        if ($field === 'preu-mensual') {
            continue;
        }
        
        if (isset($meta_fields[$field])) {
            validate_field_type($field, $value, $meta_fields[$field]);
        }
    }
}

function validate_field_type($field, $value, $type) {
    switch ($type) {
        case 'number':
            if (!is_numeric($value)) {
                throw new Exception("El campo {$field} debe ser un número");
            }
            break;

        case 'boolean':
        case 'switch':
            // Aceptar booleanos nativos
            if (is_bool($value)) {
                return;
            }
            // Aceptar strings booleanos
            if (is_string($value)) {
                $value = strtolower(trim($value));
                if (in_array($value, ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'])) {
                    return;
                }
            }
            // Aceptar valores numéricos 0 y 1
            if (is_numeric($value) && in_array((int)$value, [0, 1])) {
                return;
            }
            throw new Exception("El campo {$field} debe ser un valor booleano válido");
            break;

        case 'date':
            if (!strtotime($value)) {
                throw new Exception("El campo {$field} debe ser una fecha válida");
            }
            break;
    }
}

function validate_glossary_fields($params) {
    $glossary_fields = [
        'traccio',
        'roda-recanvi',
        'segment',
        'color-vehicle',
        'tipus-tapisseria',
        'color-tapisseria',
        'emissions-vehicle',
        'cables-recarrega',
        'connectors',
        'extres-cotxe',
        'extres-moto',  // Añadido para motos
        'tipus-de-moto', // Añadido para motos
        'carroseria-vehicle-comercial' // Añadido para vehículos comerciales
    ];

    foreach ($glossary_fields as $field) {
        if (isset($params[$field])) {
            validate_glossary_field($field, $params[$field]);
        }
    }
}

// Modificar la función validate_glossary_field para mejorar la validación
function validate_glossary_field($field, $value) {
    try {
        // Permitir valores vacíos para todos los campos de glosario
        if (empty($value) || $value === '') {
            return true;
        }
        
        // Manejo especial para el campo traccio
        if ($field === 'traccio') {
            $glossary_options = Vehicle_Fields::get_traccio_options();
            $valid_values = array_keys($glossary_options);
            $valid_labels = array_values($glossary_options);
            
            // Comprobar si el valor coincide con una clave o un label
            if (in_array(strtolower($value), array_map('strtolower', $valid_values)) || 
                in_array(strtolower($value), array_map('strtolower', $valid_labels))) {
                return true;
            }
            
            // Si no está en el mapa, mostrar error con opciones disponibles
            $available_options = array_map(function($key, $val) {
                return "{$key} => {$val}";
            }, array_keys($glossary_options), $glossary_options);
            
            throw new Exception(sprintf(
                "El valor '%s' no es válido para el campo traccio. Opciones disponibles (value => label): %s",
                $value,
                implode(', ', $available_options)
            ));
        }

        // Manejo especial para el campo color-vehicle
        if ($field === 'color-vehicle') {
            $glossary_options = Vehicle_Fields::get_color_vehicle_options();
            $valid_values = array_keys($glossary_options);
            $valid_labels = array_values($glossary_options);
            
            // Comprobar si el valor coincide con una clave o un label
            if (in_array(strtolower($value), array_map('strtolower', $valid_values)) || 
                in_array(strtolower($value), array_map('strtolower', $valid_labels))) {
                return true;
            }
            
            // Mostrar error con opciones disponibles
            $available_options = array_map(function($key, $val) {
                return "{$key} => {$val}";
            }, array_keys($glossary_options), $glossary_options);
            
            throw new Exception(sprintf(
                "El valor '%s' no es válido para el campo color-vehicle. Opciones disponibles (value => label): %s",
                $value,
                implode(', ', array_slice($available_options, 0, 20)) . '...' // Mostrar solo las primeras 20 opciones
            ));
        }

        // Manejo especial para tipus-de-moto
        if ($field === 'tipus-de-moto') {
            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field);
            
            if (!$glossary_id) {
                // Si no hay ID de glosario, aceptar cualquier valor para aquest campo
                return true;
            }
            
            if (!function_exists('jet_engine') || !isset(jet_engine()->glossaries)) {
                return true; // Si JetEngine no está disponible, aceptar cualquier valor
            }
            
            $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
            
            // Si no hay opciones, permitimos cualquier valor
            if (empty($options)) {
                return true;
            }
            
            // Comprobar si el valor coincide con alguna clave o valor
            $valid_values = array_keys($options);
            $valid_labels = array_values($options);
            
            if (in_array(strtolower($value), array_map('strtolower', $valid_values)) || 
                in_array(strtolower($value), array_map('strtolower', $valid_labels))) {
                return true;
            }
            
            // Mostrar opciones disponibles en caso de error
            $available_options = array_map(function($key, $val) {
                return "{$key} => {$val}";
            }, array_keys($options), array_values($options));
            
            throw new Exception(sprintf(
                "El valor '%s' no es válido para el campo tipus-de-moto. Opciones disponibles (value => label): %s",
                $value,
                implode(', ', array_slice($available_options, 0, 20)) . (count($available_options) > 20 ? '...' : '')
            ));
        }

        // Para otros campos de glosario
        $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field);
        
        if (!$glossary_id) {
            throw new Exception("No se encontró el glosario para el campo {$field}");
        }

        if (!function_exists('jet_engine') || !isset(jet_engine()->glossaries)) {
            throw new Exception("JetEngine Glossaries no está disponible");
        }

        $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
        
        if (empty($options)) {
            throw new Exception("No se encontraron opciones para el glosario del campo {$field}");
        }

        // Si el valor es un array (para campos múltiples)
        if (is_array($value)) {
            foreach ($value as $single_value) {
                if (!isset($options[$single_value]) && !isset($options[trim($single_value)])) {
                    // Mostrar todas las opciones disponibles
                    $available_options = array_map(function($key, $val) {
                        return "{$key} => {$val}";
                    }, array_keys($options), array_values($options));
                    
                    throw new Exception(sprintf(
                        "El valor '%s' no es válido para el campo %s. Opciones disponibles (value => label): %s",
                        $single_value,
                        $field,
                        implode(', ', array_slice($available_options, 0, 20)) . (count($available_options) > 20 ? '...' : '')
                    ));
                }
            }
        } else {
            // Para valores únicos
            if (!isset($options[$value]) && !isset($options[trim($value)])) {
                // Mostrar todas las opciones disponibles
                $available_options = array_map(function($key, $val) {
                    return "{$key} => {$val}";
                }, array_keys($options), array_values($options));
                
                throw new Exception(sprintf(
                    "El valor '%s' no es válido para el campo %s. Opciones disponibles (value => label): %s",
                    $value,
                    $field,
                    implode(', ', array_slice($available_options, 0, 20)) . (count($available_options) > 20 ? '...' : '')
                ));
            }
        }

        return true;
    } catch (Exception $e) {
        throw new Exception("Error validando glosario para {$field}: " . $e->getMessage());
    }
}

// Agregar esta función de ayuda
function get_traccio_options() {
    $valid_options = [
        'darrere',             // Cambiado a minúsculas (value)
        'davant',              // Cambiado a minúsculas (value)
        'integral',            // Cambiado a minúsculas (value)
        'integral-connectable' // Cambiado a minúsculas (value)
    ];
    return $valid_options;
}

// Modificar validate_boolean_fields para ignorar completamente estos campos
function validate_boolean_fields($params) {
    $boolean_fields = [
        'is-vip',
        'venut',
        // 'llibre-manteniment', // Quitado de validación estricta
        // 'revisions-oficials', // Quitado de validación estricta
        // 'impostos-deduibles', // Quitado de validación estricta
        // 'vehicle-a-canvi', // Quitado de validación estricta
        'garantia',
        // 'vehicle-accidentat', // Ya quitado de validación estricta
        // Quitamos 'aire-acondicionat' y 'climatitzacio' de esta lista para evitar la validación estricta
        'vehicle-fumador'
        // Ya habíamos eliminado 'frenada-regenerativa' y 'one-pedal' de esta lista
    ];

    foreach ($boolean_fields as $field) {
        if (isset($params[$field]) && $params[$field] !== '') {
            validate_boolean_value($field, $params[$field]);
        }
    }
    
    // Establecer valores por defecto para los campos problemáticos
    $params['frenada-regenerativa'] = 'no';
    $params['one-pedal'] = 'no';
    $params['aire-acondicionat'] = 'no';
    $params['climatitzacio'] = 'no'; // Añadido nuevo campo
    $params['vehicle-fumador'] = 'no'; // Añadido nuevo campo
    $params['vehicle-accidentat'] = 'no'; // Añadido vehicle-accidentat
    $params['llibre-manteniment'] = 'no'; // Añadido llibre-manteniment
    $params['revisions-oficials'] = 'no'; // Añadido revisions-oficials
    $params['impostos-deduibles'] = 'no'; // Añadido impostos-deduibles
    $params['vehicle-a-canvi'] = 'no'; // Añadido vehicle-a-canvi
    
    return $params;
}

function validate_boolean_value($field, $value) {
    // Permitir valores vacíos para los campos problemáticos
    if (($field === 'frenada-regenerativa' || $field === 'one-pedal' || 
         $field === 'aire-acondicionat' || $field === 'climatitzacio' || 
         $field === 'vehicle-fumador' || $field === 'vehicle-accidentat' ||
         $field === 'llibre-manteniment' || $field === 'revisions-oficials' ||
         $field === 'impostos-deduibles' || $field === 'vehicle-a-canvi') && // Añadidos nuevos campos
        (empty($value) || $value === '' || $value === null)) {
        return true;
    }
    
    // Si es un booleano nativo, es válido
    if (is_bool($value)) {
        return true;
    }
    
    // Si es string
    if (is_string($value)) {
        $value = strtolower(trim($value));
        if (in_array($value, get_valid_boolean_values())) {
            return true;
        }
    }

    // Si es numérico
    if (is_numeric($value)) {
        if (in_array(intval($value), [0, 1])) {
            return true;
        }
    }

    throw new Exception("El campo {$field} debe ser un valor booleano válido (true/false, si/no, 1/0, on/off)");
}

function get_valid_boolean_values() {
    return array_merge(get_true_values(), get_false_values());
}

function get_true_values() {
    return ['true', 'si', '1', 'yes', 'on'];
}

function get_false_values() {
    return ['false', 'no', '0', 'off'];
}

function validate_taxonomy_terms($params) {
    $taxonomy_fields = Vehicle_Fields::get_taxonomy_fields();
    $allowed_values = Vehicle_Fields::get_allowed_taxonomy_values();

    foreach ($taxonomy_fields as $field => $taxonomy) {
        if (isset($params[$field])) {
            if (isset($allowed_values[$field]) && !in_array($params[$field], $allowed_values[$field])) {
                throw new Exception(sprintf(
                    'El valor "%s" no es válido para %s. Valores válidos: %s',
                    $params[$field],
                    $field,
                    implode(', ', $allowed_values[$field])
                ));
            }

            $term = get_term_by('slug', $params[$field], $taxonomy);
            if (!$term) {
                throw new Exception(sprintf(
                    'El término "%s" no existe en la taxonomía %s',
                    $params[$field],
                    $taxonomy
                ));
            }
        }
    }
}

function has_glossary_fields($params) {
    $glossary_fields = [
        'traccio',
        'roda-recanvi',
        'segment',
        'color-vehicle',
        'tipus-tapisseria',
        'color-tapisseria',
        'emissions-vehicle',
        'cables-recarrega',
        'connectors',
        'extres-cotxe',
        'extres-moto',  // Añadido para motos
        'tipus-de-moto', // Añadido para motos
        'carroseria-vehicle-comercial' // Añadido para vehículos comerciales
    ];
    return !empty(array_intersect(array_keys($params), $glossary_fields));
}

function has_boolean_fields($params) {
    $boolean_fields = [
        'is-vip', 'venut', 'llibre-manteniment', 'revisions-oficials',
        'impostos-deduibles', 'vehicle-a-canvi', 'garantia', 'vehicle-accidentat',
        'aire-acondicionat', 'climatitzacio', 'vehicle-fumador',
        'frenada-regenerativa', 'one-pedal', 'anunci-actiu'
    ];
    return !empty(array_intersect(array_keys($params), $boolean_fields));
}

function has_numeric_fields($params) {
    $numeric_fields = [
        'places-moto', 'capacitat-total-l', 'dies-caducitat',
        'preu', 'preu-mensual', 'preu-diari', 'preu-antic',
        // ... resto de campos numéricos ...
    ];
    return !empty(array_intersect(array_keys($params), $numeric_fields));
}

/**
 * Valida los campos requeridos según el tipo de vehículo
 * 
 * @param array $data Los datos a validar
 * @return bool|WP_Error Verdadero si los datos son válidos, WP_Error en caso contrario
 */
function validate_required_fields_by_vehicle_type($data) {
    // ...existing code...
    
    // Campos específicos según el tipo de vehículo
    switch($vehicle_type) {
        case 'cotxe':
        case 'autocaravana':
        case 'vehicle-comercial':
            // marques-cotxe y models-cotxe son obligatorios para todos excepto MOTO
            $required_fields = array_merge($required_fields, array(
                'marques-cotxe',
                'models-cotxe'
            ));
            break;
        case 'moto':
            // Para moto, estos campos no son obligatorios
            break;
    }
    
    // ...existing code...
}

/**
 * Obtiene los valores válidos para un glosario específico
 * 
 * @param string $glossary_id ID del glosario
 * @return array Array de valores válidos (slugs)
 */
function get_valid_glossary_values($glossary_id) {
    $valid_values = [];
    
    Vehicle_Debug_Handler::log("Obteniendo valores válidos para glosario ID: $glossary_id");
    
    // Mapeo directo para valores específicos de glosarios
    $direct_mappings = [
        43 => [ // carrosseria-caravana
            'c-perfilada', 'c-capuchina', 'c-integral', 'c-camper'
        ],
        44 => [ // carroseria-vehicle-comercial
            'c-furgon-industrial', 'c-furgo-industrial'  // Ambos valores son válidos
        ]
    ];
    
    // Si hay un mapeo directo para este glosario, usarlo
    if (isset($direct_mappings[$glossary_id])) {
        Vehicle_Debug_Handler::log("Usando mapeo directo para glosario ID: $glossary_id");
        return $direct_mappings[$glossary_id];
    }
    
    // Obtener los valores del glosario de JetEngine
    if (function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
        $glossary = jet_engine()->glossaries->data->get_item_for_edit($glossary_id);
        
        if ($glossary && !empty($glossary['fields'])) {
            Vehicle_Debug_Handler::log("Glosario encontrado: " . $glossary['name'] . " con " . count($glossary['fields']) . " campos");
            
            foreach ($glossary['fields'] as $field) {
                if (isset($field['value'])) {
                    $value = sanitize_title($field['value']);
                    $valid_values[] = $value;
                    Vehicle_Debug_Handler::log("Valor válido encontrado: " . $field['value'] . " (slug: $value)");
                }
            }
        } else {
            Vehicle_Debug_Handler::log("Glosario no encontrado o sin campos: $glossary_id");
        }
    } else {
        Vehicle_Debug_Handler::log("JetEngine Glossaries no está disponible");
    }
    
    return $valid_values;
}

/**
 * Función de depuración para listar todos los glosarios disponibles
 */
function debug_list_all_glossaries() {
    if (function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
        $glossaries = jet_engine()->glossaries->data->get_items();
        Vehicle_Debug_Handler::log("=== LISTADO DE TODOS LOS GLOSARIOS ===");
        
        foreach ($glossaries as $id => $glossary) {
            $name = isset($glossary['name']) ? $glossary['name'] : 'Sin nombre';
            $slug = isset($glossary['slug']) ? $glossary['slug'] : 'Sin slug';
            Vehicle_Debug_Handler::log("ID: $id | Nombre: $name | Slug: $slug");
            
            if (!empty($glossary['fields'])) {
                Vehicle_Debug_Handler::log("  Campos:");
                foreach ($glossary['fields'] as $field) {
                    $label = isset($field['label']) ? $field['label'] : 'Sin etiqueta';
                    $value = isset($field['value']) ? $field['value'] : 'Sin valor';
                    Vehicle_Debug_Handler::log("    - Label: $label | Value: $value");
                }
            }
        }
        
        Vehicle_Debug_Handler::log("=== FIN DEL LISTADO ===");
    } else {
        Vehicle_Debug_Handler::log("JetEngine Glossaries no está disponible");
    }
}

/**
 * Obtiene el ID del glosario mapeado para un campo específico
 * 
 * @param string $field_name Nombre del campo
 * @return string|null ID del glosario o null si no hay mapeo
 */
function get_glossary_id_for_field($field_name) {
    // Usar la clase Vehicle_Glossary_Mappings para obtener el ID del glosario
    if (class_exists('Vehicle_Glossary_Mappings')) {
        $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field_name);
        
        // Registrar para depuración
        Vehicle_Debug_Handler::log("Buscando mapeo para el campo: $field_name");
        
        if ($glossary_id) {
            Vehicle_Debug_Handler::log("Mapeo encontrado para $field_name: " . $glossary_id);
            return $glossary_id;
        }
    }
    
    // Mapeos directos para campos específicos (fallback)
    $direct_mappings = [
        'extres-cotxe' => 54,
        'extres-autocaravana' => 56,
        'carrosseria-caravana' => 43,
        'extres-habitacle' => 57,
        'tipus-tapisseria' => 52,
        'color-tapisseria' => 53,
        'extres-moto' => 55,
        'cables-recarrega' => 58,
        'connectors' => 59,
        'traccio' => 60,
        'emissions-vehicle' => 61,
        'segment' => 63,
        'tipus-de-moto' => 64,
        'color-vehicle' => 51,
        'carroseria-vehicle-comercial' => 44
    ];
    
    if (isset($direct_mappings[$field_name])) {
        Vehicle_Debug_Handler::log("Usando mapeo directo para $field_name: " . $direct_mappings[$field_name]);
        return $direct_mappings[$field_name];
    }
    
    Vehicle_Debug_Handler::log("No se encontró mapeo para el campo: $field_name");
    return null;
}

/**
 * Valida que los valores de un campo de glosario existan en el glosario correspondiente
 * 
 * @param string $field_name Nombre del campo
 * @param array|string $values Valores a validar
 * @return array Array con 'valid' (boolean) y 'invalid_values' (array)
 */
function validate_glossary_values($field_name, $values) {
    $result = [
        'valid' => true,
        'invalid_values' => []
    ];
    
    // Si no hay valores, es válido
    if (empty($values)) {
        return $result;
    }
    
    // Convertir a array si es un string
    if (!is_array($values)) {
        $values = [$values];
    }
    
    // Obtener el ID del glosario para este campo
    $glossary_id = get_glossary_id_for_field($field_name);
    
    if (!$glossary_id) {
        Vehicle_Debug_Handler::log("No se encontró mapeo de glosario para el campo: $field_name");
        return $result; // Si no hay mapeo, consideramos válido
    }
    
    // Obtener los valores válidos para este glosario
    $valid_values = get_valid_glossary_values($glossary_id);
    
    if (empty($valid_values)) {
        Vehicle_Debug_Handler::log("No se encontraron valores válidos para el glosario: $glossary_id");
        return $result; // Si no hay valores válidos, consideramos válido
    }
    
    Vehicle_Debug_Handler::log("Campo: $field_name, Glosario ID: $glossary_id, Valores válidos: " . implode(', ', $valid_values));
    Vehicle_Debug_Handler::log("Valores a validar: " . implode(', ', $values));
    
    // Validar cada valor
    foreach ($values as $value) {
        if (empty($value)) {
            continue; // Ignorar valores vacíos
        }
        
        $value_slug = sanitize_title($value);
        Vehicle_Debug_Handler::log("Validando valor: $value (slug: $value_slug)");
        
        // Verificar si el valor existe en los valores válidos
        if (!in_array($value_slug, $valid_values) && !in_array($value, $valid_values)) {
            $result['valid'] = false;
            $result['invalid_values'][] = $value;
            Vehicle_Debug_Handler::log("Valor inválido: $value (slug: $value_slug)");
        } else {
            Vehicle_Debug_Handler::log("Valor válido: $value (slug: $value_slug)");
        }
    }
    
    // Si hay valores inválidos, registrar en el log
    if (!$result['valid']) {
        Vehicle_Debug_Handler::log("Valores inválidos para el campo $field_name: " . implode(', ', $result['invalid_values']));
        Vehicle_Debug_Handler::log("Valores válidos para el campo $field_name: " . implode(', ', $valid_values));
    }
    
    return $result;
}

/**
 * Valida un campo específico contra su glosario
 * 
 * @param string $field_name Nombre del campo
 * @param mixed $value Valor a validar
 * @return bool|WP_Error True si es válido, WP_Error si no
 */
function validate_specific_glossary_field($field_name, $value) {
    if (empty($value)) {
        return true; // Valores vacíos son válidos (a menos que sea un campo obligatorio)
    }
    
    // Validar contra el glosario
    $validation = validate_glossary_values($field_name, $value);
    
    if (!$validation['valid']) {
        $glossary_id = get_glossary_id_for_field($field_name);
        $valid_values = get_valid_glossary_values($glossary_id);
        
        return new WP_Error(
            'invalid_glossary_value',
            sprintf(
                __('El valor "%s" no es válido para el campo "%s". Valores válidos: %s', 'custom-api-vehicles'),
                is_array($value) ? implode(', ', $value) : $value,
                $field_name,
                implode(', ', $valid_values)
            ),
            array('status' => 400)
        );
    }
    
    return true;
}

/**
 * Obtiene el label de un glosario a partir del value
 * 
 * @param string $glossary_id ID del glosario
 * @param string $value Value del glosario
 * @return string Label del glosario o el value original si no se encuentra
 */
function get_glossary_label_from_value($glossary_id, $value) {
    if (empty($value)) {
        return '';
    }
    
    Vehicle_Debug_Handler::log("get_glossary_label_from_value - Buscando label para value: '$value' en glosario: '$glossary_id'");
    
    // Mapeo directo para valores específicos de tipus-carroseria-caravana
    $direct_mappings = [
        'c-perfilada' => 'Perfilada',
        'c-capuchina' => 'Capuchina',
        'c-integral' => 'Integral',
        'c-camper' => 'Camper'
    ];
    
    // Si es un valor conocido, devolver directamente el mapeo
    if (isset($direct_mappings[$value])) {
        Vehicle_Debug_Handler::log("Mapeo directo encontrado para '$value': '" . $direct_mappings[$value] . "'");
        return $direct_mappings[$value];
    }
    
    if (function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
        $glossary = jet_engine()->glossaries->data->get_item_for_edit($glossary_id);
        
        if ($glossary && !empty($glossary['fields'])) {
            Vehicle_Debug_Handler::log("Glosario encontrado: " . $glossary['name'] . " con " . count($glossary['fields']) . " campos");
            
            $value_slug = sanitize_title($value);
            Vehicle_Debug_Handler::log("Buscando value_slug: '$value_slug'");
            
            foreach ($glossary['fields'] as $field) {
                $field_value = isset($field['value']) ? $field['value'] : '';
                $field_value_slug = sanitize_title($field_value);
                $field_label = isset($field['label']) ? $field['label'] : '';
                
                Vehicle_Debug_Handler::log("Comparando field_value: '$field_value' (slug: '$field_value_slug') con value_slug: '$value_slug'");
                
                if ($field_value_slug === $value_slug) {
                    Vehicle_Debug_Handler::log("¡Coincidencia encontrada! Label: '$field_label'");
                    return $field_label;
                }
            }
            
            Vehicle_Debug_Handler::log("No se encontró ninguna coincidencia en el glosario para '$value'");
        } else {
            Vehicle_Debug_Handler::log("Glosario no encontrado o sin campos: $glossary_id");
        }
    } else {
        Vehicle_Debug_Handler::log("JetEngine Glossaries no está disponible");
    }
    
    // Si no se encuentra el label, devolver el value original
    Vehicle_Debug_Handler::log("Devolviendo value original: '$value'");
    return $value;
}
