<?php

function get_field_label($field_name, $value) {
    if (empty($value) && $value !== '0' && $value !== 0) {
        return '';
    }
    
    // Para extres-cotxe, manejar el formato de array anidado
    if ($field_name === 'extres-cotxe') {
        Vehicle_Debug_Handler::log("Procesando extres-cotxe con valor: " . print_r($value, true));
        
        // Deserializar si es necesario
        $value = deserialize_if_needed($value);
        
        // Extraer valores del array anidado si es necesario
        if (is_array($value) && isset($value[0]) && is_array($value[0])) {
            $value = $value[0];
            Vehicle_Debug_Handler::log("Extraído array interno de extres-cotxe: " . print_r($value, true));
        }
        
        // Obtener las opciones del glosario
        $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field_name);
        if ($glossary_id && function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
            $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
            Vehicle_Debug_Handler::log("Opciones del glosario para extres-cotxe: " . print_r($options, true));
            
            // Procesar cada valor
            return process_array_value($value, $options);
        }
        
        return $value;
    }

    // Primero intentar obtener del glosario mapeado
    $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field_name);
    if ($glossary_id && function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
        $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
        Vehicle_Debug_Handler::log("Opciones del glosario para {$field_name}: " . print_r($options, true));
        
        if (!empty($options)) {
            if (is_array($value)) {
                return process_array_value($value, $options);
            }
            
            if (isset($options[$value])) {
                Vehicle_Debug_Handler::log("Label encontrado para {$field_name}: {$options[$value]}");
                return $options[$value];
            } elseif (isset($options[trim($value)])) {
                Vehicle_Debug_Handler::log("Label encontrado (después de trim) para {$field_name}: {$options[trim($value)]}");
                return $options[trim($value)];
            }
        }
    }

    // Si es un campo que maneja arrays
    if (is_array_field($field_name)) {
        return process_array_field($field_name, $value);
    }

    // Si es un campo de glosario
    if (is_glossary_field($field_name)) {
        return process_glossary_field($field_name, $value);
    }

    // Si es un campo de taxonomía
    if (is_taxonomy_field($field_name)) {
        return process_taxonomy_field($field_name, $value);
    }

    // Para cualquier otro tipo de campo
    return process_standard_field($field_name, $value);
}

function process_array_value($value, $options) {
    $labels = [];
    
    // Asegurarse de que value sea un array
    if (!is_array($value)) {
        Vehicle_Debug_Handler::log("process_array_value: valor no es un array: " . print_r($value, true));
        return [$value];
    }
    
    foreach ($value as $item) {
        // Verificar que el ítem sea un string o número válido para usar como índice
        if (is_string($item) || is_numeric($item)) {
            if (isset($options[$item])) {
                $labels[] = $options[$item];
            } elseif (isset($options[trim($item)])) {
                $labels[] = $options[trim($item)];
            } else {
                $labels[] = $item;
            }
        } else {
            // Si el ítem no es un string o número, simplemente añadirlo como está
            Vehicle_Debug_Handler::log("process_array_value: ítem no es string ni número: " . print_r($item, true));
            $labels[] = is_array($item) ? json_encode($item) : (string)$item;
        }
    }
    return $labels;
}

function process_array_field($field_name, $value) {
    try {
        // Para extres-cotxe, manejar el formato de array anidado
        if ($field_name === 'extres-cotxe') {
            // Deserializar si es necesario
            $value = deserialize_if_needed($value);
            
            // Extraer valores del array anidado si es necesario
            if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                $value = $value[0];
                Vehicle_Debug_Handler::log("process_array_field: Extraído array interno de extres-cotxe: " . print_r($value, true));
            }
        } else {
            $value = deserialize_if_needed($value);
        }
        
        if (!is_array($value) || empty($value)) {
            return [];
        }

        $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field_name);
        if (!$glossary_id || !function_exists('jet_engine') || !isset(jet_engine()->glossaries)) {
            return $value;
        }

        $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
        return empty($options) ? $value : process_array_value($value, $options);
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log("Error procesando campo array $field_name: " . $e->getMessage());
        return [];
    }
}

function process_glossary_field($field_name, $value) {
    try {
        $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field_name);
        if (!$glossary_id) {
            return $value;
        }

        $options = get_glossary_options($glossary_id);
        return process_options($options, $value);
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log("Error procesando glosario $field_name: " . $e->getMessage());
        return $value;
    }
}

function process_taxonomy_field($field_name, $value) {
    Vehicle_Debug_Handler::log("=== DEBUG PROCESS TAXONOMY FIELD ===");
    Vehicle_Debug_Handler::log("Procesando campo: $field_name");
    Vehicle_Debug_Handler::log("Valor recibido: " . print_r($value, true));
    
    $taxonomy = get_taxonomy_for_field($field_name);
    Vehicle_Debug_Handler::log("Taxonomía mapeada: $taxonomy");
    
    if (!$taxonomy) {
        Vehicle_Debug_Handler::log("No se encontró taxonomía para el campo: $field_name");
        return $value;
    }

    $term = get_term_by('slug', $value, $taxonomy);
    Vehicle_Debug_Handler::log("Término encontrado: " . print_r($term, true));
    
    if ($term && !is_wp_error($term)) {
        Vehicle_Debug_Handler::log("Retornando nombre del término: " . $term->name);
        return $term->name;
    } else {
        Vehicle_Debug_Handler::log("No se encontró término, retornando valor original: $value");
        return $value;
    }
}

function process_standard_field($field_name, $value) {
    $field_type = get_field_type($field_name);
    
    if ($field_type === 'boolean' || $field_type === 'switch') {
        // Custom boolean processing for better handling
        return process_boolean_value($value);
    }
    
    if ($field_type === 'number') {
        return $value;
    }
    
    return $value;
}

function process_boolean_value($value) {
    // Handle null, empty values
    if (is_null($value) || $value === '') {
        return false;
    }
    
    // Handle boolean values
    if (is_bool($value)) {
        return $value;
    }
    
    // Handle string values
    if (is_string($value)) {
        $value_lower = strtolower(trim($value));
        if (in_array($value_lower, ['true', 'yes', 'si', 'on', '1'])) {
            return true;
        }
        if (in_array($value_lower, ['false', 'no', 'off', '0'])) {
            return false;
        }
    }
    
    // Handle numeric values
    if (is_numeric($value)) {
        return intval($value) !== 0;
    }
    
    // Default to false for unknown values
    return false;
}

function deserialize_if_needed($value) {
    if (!is_string($value)) {
        return $value;
    }

    if (strpos($value, 'a:') === 0) {
        return unserialize($value);
    }

    $maybe_array = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $maybe_array;
    }

    return $value;
}

function get_glossary_options($glossary_id) {
    if (!function_exists('jet_engine') || !isset(jet_engine()->glossaries)) {
        return [];
    }

    return jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
}

function process_options($options, $value) {
    if (empty($options)) {
        return $value;
    }

    if (isset($options[$value])) {
        return $options[$value];
    }

    if (isset($options[trim($value)])) {
        return $options[trim($value)];
    }

    return $value;
}

function map_field_key($key) {
    $mapping = [
        'maleters' => 'numero-maleters-cotxe',
        'capacitat-total' => 'capacitat-maleters-cotxe',
        'acceleracio-0-100' => 'acceleracio-0-100-cotxe',
        'n-motors' => 'numero-motors',
        'is-vip' => 'anunci-destacat'
    ];

    return isset($mapping[$key]) ? $mapping[$key] : $key;
}

function map_field_value($key, $value) {
    // Special handling for is-vip field
    if ($key === 'is-vip') {
        $processed_value = process_boolean_value($value);
        return $processed_value ? 1 : 0;
    }
    
    // Special handling for array fields (extras)
    if (is_array_field($key)) {
        // Deserializar si es necesario
        if (is_string($value) && strpos($value, 'a:') === 0) {
            $value = unserialize($value);
            Vehicle_Debug_Handler::log("Deserializado campo {$key}: " . print_r($value, true));
        }
        
        // Asegurarse de que sea un array
        if (!is_array($value)) {
            $value = [$value];
        }
        
        // Limpiar valores vacíos
        $value = array_filter($value, function($val) {
            return !empty($val) && $val !== '';
        });
        
        // Reindexar el array
        $value = array_values($value);
        
        return $value;
    }
    
    return $value;
}

function is_array_field($field_name) {
    return in_array($field_name, [
        'extres-cotxe',
        'extres-moto',
        'extres-autocaravana',
        'extres-habitacle',
        'cables-recarrega',
        'connectors'
    ]);
}

function is_glossary_field($field_name) {
    return in_array($field_name, [
        'color-tapisseria',
        'carrosseria-cotxe',
        'traccio',
        'roda-recanvi',
        'color-vehicle',
        'tipus-tapisseria',
        'emissions-vehicle',
        'carroseria-camions',
        'carroseria-vehicle-comercial',
        'tipus-de-moto',
        'tipus-de-canvi-moto',
        'marques-de-moto',  // Añadido para soportar marcas de moto
        'models-moto',      // Añadido para soportar modelos de moto
        'bateria',
        'velocitat-recarrega',
        'extres-cotxe',
        'extres-moto',
        'extres-autocaravana',
        'extres-habitacle',
        'cables-recarrega',
        'connectors',
        'tipus-carroseria-caravana' // Añadido para soportar el tipo de carrocería de caravanas
    ]);
}

function should_get_field_label($field_name) {
    // Lista de campos que deben devolver values en lugar de labels
    $return_values_fields = [
        'extres-cotxe',
        'extres-autocaravana',
        'extres-moto',
        'extres-habitacle'
    ];
    
    // Si es un campo que debe devolver values, devolver false
    if (in_array($field_name, $return_values_fields)) {
        return false;
    }
    
    return is_glossary_field($field_name) || is_taxonomy_field($field_name);
}

function is_taxonomy_field($field_name) {
    Vehicle_Debug_Handler::log("Verificando si el campo '$field_name' es un campo de taxonomía");
    $taxonomy_fields = [
        'tipus-vehicle',
        'tipus-combustible',
        'tipus-propulsor',
        'estat-vehicle',
        'tipus-de-moto',
        'tipus-canvi-cotxe',
        // 'tipus-carroseria-caravana', // Removido porque es un campo meta, no taxonomía
        'marques-cotxe',
        'models-cotxe'
    ];
    $result = in_array($field_name, $taxonomy_fields);
    Vehicle_Debug_Handler::log("Resultado para '$field_name': " . ($result ? 'SÍ es taxonomía' : 'NO es taxonomía'));
    return $result;
}

function get_taxonomy_map() {
    Vehicle_Debug_Handler::log("DEBUG - Obteniendo mapeo de taxonomías");
    $map = [
        'tipus-vehicle' => 'types-of-transport',
        'tipus-combustible' => 'tipus-combustible',
        'tipus-propulsor' => 'tipus-de-propulsor',
        'estat-vehicle' => 'estat-vehicle',
        'tipus-de-moto' => 'marques-de-moto',
        'tipus-canvi-cotxe' => 'tipus-de-canvi',
        // 'tipus-carroseria-caravana' => 'tipus-carroseria-caravana', // Removido porque es un campo meta, no taxonomía
        'marques-cotxe' => 'marques-coches'
    ];
    Vehicle_Debug_Handler::log("DEBUG - Mapeo de taxonomías completo: " . print_r($map, true));
    Vehicle_Debug_Handler::log("DEBUG - Verificando que 'tipus-carroseria-caravana' no esté en el mapeo: " . (!isset($map['tipus-carroseria-caravana']) ? 'NO está incluido' : 'SÍ está incluido'));
    return $map;
}

function get_taxonomy_for_field($field_name) {
    $map = get_taxonomy_map();
    return isset($map[$field_name]) ? $map[$field_name] : null;
}

function get_field_type($field_name) {
    // Campos booleanos
    $boolean_fields = [
        'is-vip', 'venut', 'llibre-manteniment', 'revisions-oficials',
        'impostos-deduibles', 'vehicle-a-canvi', 'garantia', 'vehicle-accidentat',
        'aire-acondicionat', 'climatitzacio', 'vehicle-fumador',
        'frenada-regenerativa', 'one-pedal'
    ];

    // Campos numéricos
    $number_fields = [
        'places-moto', 'capacitat-total-l', 'dies-caducitat',
        'preu', 'preu-mensual', 'preu-diari', 'preu-antic',
        'quilometratge', 'cilindrada', 'potencia-cv', 'potencia-kw',
        'portes-cotxe', 'places-cotxe', 'velocitat-maxima',
        'acceleracio-0-100', 'capacitat-total', 'maleters',
        'autonomia-wltp', 'autonomia-urbana-wltp', 'autonomia-extraurbana-wltp',
        'autonomia-electrica', 'temps-recarrega-total', 'temps-recarrega-fins-80',
        'n-motors', 'potencia-combinada',
        'kw-motor-davant', 'cv-motor-davant',
        'kw-motor-darrere', 'cv-motor-darrere',
        'kw-motor-3', 'cv-motor-3',
        'kw-motor-4', 'cv-motor-4'
    ];

    // Campos de fecha
    $date_fields = ['data-vip'];

    // Determinar el tipo
    if (in_array($field_name, $boolean_fields)) {
        return 'boolean';
    }
    if (in_array($field_name, $number_fields)) {
        return 'number';
    }
    if (in_array($field_name, $date_fields)) {
        return 'date';
    }
    if (is_glossary_field($field_name)) {
        return 'glossary';
    }

    return 'text';
}

/**
 * Procesa y guarda los campos meta
 */
function process_and_save_meta_fields($post_id, $params) {
    $meta_fields = Vehicle_Fields::get_meta_fields();
    
    // SOLUCIÓN DEFINITIVA: Valores por defecto para campos problemáticos
    $params['frenada-regenerativa'] = 'no';
    $params['one-pedal'] = 'no';
    $params['aire-acondicionat'] = 'no';
    $params['portes-cotxe'] = isset($params['portes-cotxe']) && is_numeric($params['portes-cotxe']) ? 
                            $params['portes-cotxe'] : '5';
    $params['climatitzacio'] = 'no';
    $params['vehicle-fumador'] = 'no';
    $params['vehicle-accidentat'] = 'no';
    $params['llibre-manteniment'] = 'no';
    $params['revisions-oficials'] = 'no';
    $params['impostos-deduibles'] = 'no'; // Añadido impostos-deduibles
    $params['vehicle-a-canvi'] = 'no'; // Añadido vehicle-a-canvi
    
    // Para campos numéricos problemáticos, establecer valor 0 si están vacíos
    if (empty($params['temps-recarrega-total']) || !is_numeric($params['temps-recarrega-total'])) {
        $params['temps-recarrega-total'] = '0';
    }
    
    if (empty($params['temps-recarrega-fins-80']) || !is_numeric($params['temps-recarrega-fins-80'])) {
        $params['temps-recarrega-fins-80'] = '0';
    }
    
    // Guardar explícitamente los campos problemáticos con valores por defecto
    update_post_meta($post_id, 'frenada-regenerativa', 'false');
    update_post_meta($post_id, 'one-pedal', 'false');
    update_post_meta($post_id, 'aire-acondicionat', 'false');
    update_post_meta($post_id, 'climatitzacio', 'false');
    update_post_meta($post_id, 'vehicle-fumador', 'false');
    update_post_meta($post_id, 'vehicle-accidentat', 'false');
    update_post_meta($post_id, 'llibre-manteniment', 'false');
    update_post_meta($post_id, 'revisions-oficials', 'false'); // Añadido revisions-oficials
    update_post_meta($post_id, 'impostos-deduibles', 'false'); // Añadido impostos-deduibles
    update_post_meta($post_id, 'vehicle-a-canvi', 'false'); // Añadido vehicle-a-canvi
    update_post_meta($post_id, 'portes-cotxe', $params['portes-cotxe']);
    update_post_meta($post_id, 'temps-recarrega-total', $params['temps-recarrega-total']);
    update_post_meta($post_id, 'temps-recarrega-fins-80', $params['temps-recarrega-fins-80']);
    
    // Procesar campos específicos para autocaravanas
    process_autocaravana_fields($post_id, $params);
    
    // Procesar campos específicos para vehículos comerciales
    process_vehicle_comercial_fields($post_id, $params);
    
    // Procesar todos los campos de glosario para convertir label a value si es necesario
    foreach ($params as $field => $value) {
        if (is_glossary_field($field) && !empty($value)) {
            $params[$field] = convert_glossary_label_to_value($field, $value);
        }
    }
    
    // Manejo especial para extres-cotxe para todos los tipos de vehículos
    if (isset($params['extres-cotxe']) && !empty($params['extres-cotxe'])) {
        $values = $params['extres-cotxe'];
        
        // Si no es un array, convertirlo en array
        if (!is_array($values)) {
            $values = [$values];
        }
        
        // Validar que los valores existan en el glosario correspondiente
        if (function_exists('validate_glossary_values')) {
            $validation = validate_glossary_values('extres-cotxe', $values);
            
            if ($validation['valid']) {
                // Guardar como array simple (formato requerido por JSM)
                update_post_meta($post_id, 'extres-cotxe', $values);
                Vehicle_Debug_Handler::log("Guardado extres-cotxe como array simple: " . print_r($values, true));
                
                // Verificación adicional
                $saved_value = get_post_meta($post_id, 'extres-cotxe', true);
                Vehicle_Debug_Handler::log("Valor guardado de extres-cotxe: " . print_r($saved_value, true));
            } else {
                Vehicle_Debug_Handler::log("Valores inválidos para extres-cotxe: " . print_r($validation['invalid_values'], true));
            }
        }
    }
    
    // Para motos, asignar taxonomías específicas
    $vehicle_type = $params['tipus-vehicle'] ?? '';
    if ($vehicle_type === 'moto-quad-atv' || strtolower($vehicle_type) === 'moto') {
        if (isset($params['marques-de-moto'])) {
            $marca_term = term_exists($params['marques-de-moto'], 'marques-de-moto');
            if ($marca_term) {
                wp_set_object_terms($post_id, intval($marca_term['term_id']), 'marques-de-moto');
            }
        }
        
        if (isset($params['models-moto'])) {
            $model_term = term_exists($params['models-moto'], 'models-moto');
            if ($model_term) {
                wp_set_object_terms($post_id, intval($model_term['term_id']), 'models-moto');
            }
        }
    }
    
    // Procesar todos los demás campos meta
    foreach ($meta_fields as $field => $type) {
        // Saltarnos los campos que ya hemos procesado manualmente
        if ($field === 'frenada-regenerativa' || 
            $field === 'one-pedal' || 
            $field === 'aire-acondicionat' || 
            $field === 'climatitzacio' ||
            $field === 'vehicle-fumador' ||
            $field === 'vehicle-accidentat' ||
            $field === 'llibre-manteniment' ||
            $field === 'revisions-oficials' || // Añadido revisions-oficials
            $field === 'impostos-deduibles' || // Añadido impostos-deduibles
            $field === 'vehicle-a-canvi' || // Añadido vehicle-a-canvi
            $field === 'portes-cotxe' ||  
            $field === 'temps-recarrega-total' || 
            $field === 'temps-recarrega-fins-80' ||
            $field === 'extres-cotxe') { // Añadido extres-cotxe porque ya lo procesamos arriba
            continue;
        }
        
        // Manejo especial para campos de precio - guardarlo siempre como texto plano
        if ($field === 'preu-mensual' || $field === 'preu-diari' || $field === 'preu-antic') {
            if (isset($params[$field])) {
                update_post_meta($post_id, $field, $params[$field]);
            }
            continue;
        }
        
        if (isset($params[$field])) {
            $value = sanitize_meta_value($params[$field], $type);
            update_post_meta($post_id, $field, $value);
        }
    }
}

/**
 * Procesa los campos específicos para autocaravanas
 */
function process_autocaravana_fields($post_id, $params) {
    // Mapeo de campos a campos meta
    $autocaravana_fields = [
        'extres-autocaravana' => ['field' => 'extres-autocaravana', 'type' => 'array'],
        'carrosseria-caravana' => ['field' => 'tipus-carroseria-caravana', 'type' => 'single'],
        'extres-habitacle' => ['field' => 'extres-habitacle', 'type' => 'array']
    ];

    $invalid_fields = [];

    foreach ($autocaravana_fields as $input_field => $config) {
        $meta_field = $config['field'];
        $field_type = $config['type'];
        
        if (isset($params[$input_field])) {
            $values = $params[$input_field];
            
            // Si es un campo de tipo single y viene como array, tomar solo el primer valor
            if ($field_type === 'single' && is_array($values)) {
                $values = !empty($values) ? $values[0] : '';
                Vehicle_Debug_Handler::log("Campo $input_field es de tipo single, usando solo el primer valor: $values");
            }
            // Si es un campo de tipo array y no viene como array, convertirlo en array
            else if ($field_type === 'array' && !is_array($values)) {
                $values = [$values];
            }
            
            // Validar que los valores existan en el glosario correspondiente
            if (function_exists('validate_glossary_values')) {
                $validation_values = is_array($values) ? $values : [$values];
                $validation = validate_glossary_values($input_field, $validation_values);
                
                if (!$validation['valid']) {
                    // Recopilar información de valores inválidos
                    $invalid_fields[$input_field] = [
                        'invalid_values' => $validation['invalid_values']
                    ];
                    
                    // Obtener valores válidos para mostrarlos en el mensaje de error
                    $glossary_id = get_glossary_id_for_field($input_field);
                    if ($glossary_id) {
                        $valid_values = get_valid_glossary_values($glossary_id);
                        $invalid_fields[$input_field]['valid_values'] = $valid_values;
                    }
                    
                    // Continuar con el siguiente campo, no guardar valores inválidos
                    continue;
                }
            }
            
            Vehicle_Debug_Handler::log("Procesando campo de glosario $input_field -> $meta_field con valores: " . print_r($values, true));
            
            // Guardar como campo meta (serializado para arrays, valor simple para single)
            update_post_meta($post_id, $meta_field, $values);
            Vehicle_Debug_Handler::log("Campo $meta_field guardado correctamente con valor: " . print_r($values, true));
            
            // Verificación adicional
            if ($input_field === 'extres-autocaravana') {
                Vehicle_Debug_Handler::log("DEBUG SAVE - Verificación adicional para extres-autocaravana");
                $saved_value = get_post_meta($post_id, $meta_field, true);
                Vehicle_Debug_Handler::log("DEBUG SAVE - Valor guardado de extres-autocaravana: " . print_r($saved_value, true));
                
                // Verificar el tipo de datos
                Vehicle_Debug_Handler::log("DEBUG SAVE - Tipo de datos de values: " . gettype($values));
                if (is_array($values)) {
                    Vehicle_Debug_Handler::log("DEBUG SAVE - Es un array con " . count($values) . " elementos");
                } else {
                    Vehicle_Debug_Handler::log("DEBUG SAVE - No es un array, es: " . gettype($values));
                }
                
                // Asegurarse de que se guarde correctamente
                if (empty($saved_value) && !empty($values)) {
                    Vehicle_Debug_Handler::log("DEBUG SAVE - Intentando guardar extres-autocaravana nuevamente");
                    
                    // Eliminar cualquier valor existente
                    delete_post_meta($post_id, $meta_field);
                    
                    // Guardar cada valor individualmente si es un array
                    if (is_array($values)) {
                        foreach ($values as $single_value) {
                            add_post_meta($post_id, $meta_field, $single_value);
                            Vehicle_Debug_Handler::log("DEBUG SAVE - Añadido valor individual: " . $single_value);
                        }
                    } else {
                        // Si no es un array, guardar como un solo valor
                        add_post_meta($post_id, $meta_field, $values);
                        Vehicle_Debug_Handler::log("DEBUG SAVE - Añadido valor único: " . $values);
                    }
                    
                    // Verificar nuevamente
                    $saved_values = get_post_meta($post_id, $meta_field);
                    Vehicle_Debug_Handler::log("DEBUG SAVE - Valores guardados después del segundo intento: " . print_r($saved_values, true));
                }
            }
        }
    }
    
    // Si hay campos inválidos, lanzar una excepción con información detallada
    if (!empty($invalid_fields)) {
        $error_message = "Valores inválidos en campos de glosario:\n";
        
        foreach ($invalid_fields as $field => $info) {
            $error_message .= "- Campo '$field':\n";
            $error_message .= "  - Valores inválidos: " . implode(', ', $info['invalid_values']) . "\n";
            
            if (isset($info['valid_values']) && !empty($info['valid_values'])) {
                $error_message .= "  - Valores válidos: " . implode(', ', $info['valid_values']) . "\n";
            }
        }
        
        throw new Exception($error_message);
    }
}

/**
 * Procesa los campos específicos para vehículos comerciales
 */
function process_vehicle_comercial_fields($post_id, $params) {
    // Verificar si es un vehículo comercial
    $vehicle_type = isset($params['tipus-vehicle']) ? $params['tipus-vehicle'] : '';
    if ($vehicle_type !== 'vehicle-comercial') {
        return; // No es un vehículo comercial, salir
    }
    
    // Mapeo de campos a campos meta para vehículos comerciales
    $comercial_fields = [
        'extres-cotxe' => ['field' => 'extres-cotxe', 'type' => 'array'],
        'carroseria-vehicle-comercial' => ['field' => 'carroseria-vehicle-comercial', 'type' => 'single']
    ];

    $invalid_fields = [];

    foreach ($comercial_fields as $input_field => $config) {
        $meta_field = $config['field'];
        $field_type = $config['type'];
        
        if (isset($params[$input_field])) {
            $values = $params[$input_field];
            
            // Si es un campo de tipo single y viene como array, tomar solo el primer valor
            if ($field_type === 'single' && is_array($values)) {
                $values = !empty($values) ? $values[0] : '';
                Vehicle_Debug_Handler::log("Campo $input_field es de tipo single, usando solo el primer valor: $values");
            }
            
            // Validar campos de glosario
            if (function_exists('validate_glossary_values') && is_glossary_field($input_field)) {
                Vehicle_Debug_Handler::log("Validando campo de glosario: $input_field con valor: " . (is_array($values) ? implode(', ', $values) : $values));
                
                // Para validación, siempre convertir a array
                $validation_values = is_array($values) ? $values : [$values];
                $validation = validate_glossary_values($input_field, $validation_values);
                
                if (!$validation['valid']) {
                    $glossary_id = get_glossary_id_for_field($input_field);
                    $valid_values = get_valid_glossary_values($glossary_id);
                    
                    $invalid_fields[$input_field] = [
                        'invalid_values' => $validation['invalid_values'],
                        'valid_values' => $valid_values
                    ];
                    
                    Vehicle_Debug_Handler::log("Campo inválido $input_field: " . print_r($validation['invalid_values'], true));
                    Vehicle_Debug_Handler::log("Valores válidos para $input_field: " . implode(', ', $valid_values));
                    
                    // No guardar valores inválidos
                    continue;
                }
            }
            
            // Guardar el campo según su tipo
            if ($field_type === 'array') {
                // Para campos de tipo array (como extres-cotxe)
                if (is_array($values)) {
                    // Guardar como array simple (formato requerido por JSM)
                    update_post_meta($post_id, $meta_field, $values);
                    Vehicle_Debug_Handler::log("Guardado $meta_field como array simple: " . print_r($values, true));
                } else {
                    // Si no es un array, convertirlo en array
                    $array_value = [$values];
                    update_post_meta($post_id, $meta_field, $array_value);
                    Vehicle_Debug_Handler::log("Guardado $meta_field como array simple (valor único): " . print_r($array_value, true));
                }
            } else {
                // Para campos de tipo escalar (como carroseria-vehicle-comercial)
                update_post_meta($post_id, $meta_field, $values);
                Vehicle_Debug_Handler::log("Guardado $meta_field como valor simple: $values");
            }
        }
    }
    
    // Si hay campos inválidos, registrar en el log
    if (!empty($invalid_fields)) {
        Vehicle_Debug_Handler::log("Campos inválidos en vehículo comercial: " . print_r($invalid_fields, true));
    }
}

/**
 * Procesa y guarda los campos meta
 */
function convert_glossary_label_to_value($field, $value) {
    // Si el valor es un array, procesamos cada elemento individualmente
    if (is_array($value)) {
        $result = [];
        foreach ($value as $single_value) {
            $result[] = convert_glossary_label_to_value($field, $single_value);
        }
        return $result;
    }
    
    // Si el valor ya es una clave válida, devolverlo tal cual
    if (is_valid_glossary_key($field, $value)) {
        return $value;
    }
    
    // Si el valor no es string o número, devolverlo sin cambios
    if (!is_string($value) && !is_numeric($value)) {
        return $value;
    }
    
    // Obtener el mapa de opciones según el campo
    $options = [];
    switch ($field) {
        case 'traccio':
            $options = Vehicle_Fields::get_traccio_options();
            break;
        case 'color-vehicle':
            $options = Vehicle_Fields::get_color_vehicle_options();
            break;
        case 'tipus-de-moto':
            $options = Vehicle_Fields::get_tipus_de_moto_options();
            break;
        // Añadir más casos según sea necesario...
        default:
            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field);
            if ($glossary_id && function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
                $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
            }
            break;
    }
    
    // Si no hay opciones, devolvemos el valor original
    if (empty($options)) {
        return $value;
    }
    
    // Convertir el valor a string para usar strtolower
    $value_string = (string)$value;
    $value_lower = strtolower($value_string);
    
    // Buscar el valor correspondiente al label (ignorando mayúsculas/minúsculas)
    foreach ($options as $key => $label) {
        // Asegurarse de que $label sea string antes de usar strtolower
        if (is_string($label) && strtolower($label) === $value_lower) {
            return $key;
        }
    }
    
    // Si no se encuentra, devolver el valor original
    return $value;
}

/**
 * Verifica si un valor es una clave válida en el glosario
 */
function is_valid_glossary_key($field, $value) {
    // No procesar si el valor no es una string o un número
    if (!is_string($value) && !is_numeric($value)) {
        return false;
    }
    
    $options = [];
    switch ($field) {
        case 'traccio':
            $options = Vehicle_Fields::get_traccio_options();
            break;
        case 'color-vehicle':
            $options = Vehicle_Fields::get_color_vehicle_options();
            break;
        case 'tipus-de-moto':
            $options = Vehicle_Fields::get_tipus_de_moto_options();
            break;
        // Añadir más casos según sea necesario...
        default:
            $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field);
            if ($glossary_id && function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
                $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
            }
            break;
    }
    
    // Asegurarnos de que $value sea un string antes de usarlo como clave
    $value_key = (string)$value;
    return isset($options[$value_key]);
}

/**
 * Sanitiza un valor booleano para guardarlo en la base de datos
 */
function sanitize_boolean_value($value) {
    // Si el valor es vacío o nulo, tratarlo como falso
    if (empty($value) && $value !== '0' && $value !== 0) {
        return 'false';
    }
    
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    
    if (is_string($value)) {
        $value = strtolower(trim($value));
        if (in_array($value, ['true', 'si', 'yes', '1', 'on'])) {
            return 'true';
        }
    }
    
    if (is_numeric($value) && intval($value) === 1) {
        return 'true';
    }
    
    return 'false';
}

/**
 * Sanitiza un valor meta según su tipo
 */
function sanitize_meta_value($value, $type) {
    switch ($type) {
        case 'boolean':
        case 'switch':
            return sanitize_boolean_value($value);
        
        case 'number':
            return is_numeric($value) ? floatval($value) : 0;
            
        default:
            return sanitize_text_field($value);
    }
}