<?php

function get_field_label($field_name, $value) {
    if (empty($value) && $value !== '0' && $value !== 0) {
        return '';
    }

    // Primero intentar obtener del glosario mapeado
    $glossary_id = Vehicle_Glossary_Mappings::get_glossary_id($field_name);
    if ($glossary_id && function_exists('jet_engine') && isset(jet_engine()->glossaries)) {
        $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
        error_log("Opciones del glosario para {$field_name}: " . print_r($options, true));
        
        if (!empty($options)) {
            if (is_array($value)) {
                return process_array_value($value, $options);
            }
            
            if (isset($options[$value])) {
                error_log("Label encontrado para {$field_name}: {$options[$value]}");
                return $options[$value];
            } elseif (isset($options[trim($value)])) {
                error_log("Label encontrado (después de trim) para {$field_name}: {$options[trim($value)]}");
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
    foreach ($value as $item) {
        if (isset($options[$item])) {
            $labels[] = $options[$item];
        } elseif (isset($options[trim($item)])) {
            $labels[] = $options[trim($item)];
        } else {
            $labels[] = $item;
        }
    }
    return $labels;
}

function process_array_field($field_name, $value) {
    try {
        $value = deserialize_if_needed($value);
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
        error_log("Error procesando campo array $field_name: " . $e->getMessage());
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
        error_log("Error procesando glosario $field_name: " . $e->getMessage());
        return $value;
    }
}

function process_taxonomy_field($field_name, $value) {
    $taxonomy = get_taxonomy_for_field($field_name);
    if (!$taxonomy) {
        return $value;
    }

    $term = get_term_by('slug', $value, $taxonomy);
    return ($term && !is_wp_error($term)) ? $term->name : $value;
}

function process_standard_field($field_name, $value) {
    $field_type = get_field_type($field_name);
    
    if ($field_type === 'boolean' || $field_type === 'switch') {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    if ($field_type === 'number') {
        return $value;
    }
    
    return $value;
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
        'tipus-de-canvi-moto', // Asegurarnos que este campo está incluido
        'bateria',
        'velocitat-recarrega',
        'extres-cotxe',
        'extres-moto',
        'extres-autocaravana',
        'extres-habitacle',
        'cables-recarrega',
        'connectors'
    ]);
}

function should_get_field_label($field_name) {
    return is_glossary_field($field_name) || is_taxonomy_field($field_name);
}

function is_taxonomy_field($field_name) {
    return isset(get_taxonomy_map()[$field_name]);
}

function get_taxonomy_map() {
    return [
        'tipus-vehicle' => 'types-of-transport',
        'tipus-combustible' => 'tipus-combustible',
        'tipus-propulsor' => 'tipus-de-propulsor',
        'estat-vehicle' => 'estat-vehicle',
        'tipus-de-moto' => 'tipus-de-moto',
        'tipus-canvi-cotxe' => 'tipus-de-canvi',
        'tipus-carroseria-caravana' => 'tipus-carroseria-caravana'
    ];
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