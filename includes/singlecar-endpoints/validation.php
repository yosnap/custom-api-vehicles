<?php

function validate_all_fields($params, $is_update = false) {
    $errors = [];

    try {
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
            validate_boolean_fields($params);
        }

        // Validar campos numéricos si están presentes
        if (has_numeric_fields($params)) {
            validate_numeric_fields($params);
        }

    } catch (Exception $e) {
        throw new Exception("Errores de validación:\n" . $e->getMessage());
    }
}

function validate_required_fields($params, $is_update = false) {
    $required_fields = [
        'marques-cotxe',
        'models-cotxe',
        'versio',
        'tipus-vehicle',
        'tipus-combustible',
        'tipus-canvi-cotxe',
        'tipus-propulsor',
        'estat-vehicle',
        'preu'
    ];

    // Si es una actualización, solo validar los campos requeridos que se están modificando
    if ($is_update) {
        $fields_to_validate = array_intersect(array_keys($params), $required_fields);
        
        // Si no se está modificando ningún campo requerido, retornar true
        if (empty($fields_to_validate)) {
            return true;
        }
        
        // Si se está modificando algún campo relacionado con marca/modelo
        $brand_model_fields = ['marques-cotxe', 'models-cotxe'];
        $updating_brand_model = !empty(array_intersect(array_keys($params), $brand_model_fields));
        
        if ($updating_brand_model) {
            $missing_fields = [];
            foreach ($brand_model_fields as $field) {
                if (!isset($params[$field]) || empty($params[$field])) {
                    $missing_fields[] = $field;
                }
            }
            if (!empty($missing_fields)) {
                throw new Exception('Al modificar marca/modelo, ambos campos son requeridos: ' . implode(', ', $missing_fields));
            }
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
        'extres-cotxe'
    ];

    foreach ($glossary_fields as $field) {
        if (isset($params[$field])) {
            validate_glossary_field($field, $params[$field]);
        }
    }
}

// Agregar esta nueva función
function validate_glossary_field($field, $value) {
    try {
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
                    throw new Exception("El valor '{$single_value}' no es válido para el campo {$field}");
                }
            }
        } else {
            // Para valores únicos
            if (!isset($options[$value]) && !isset($options[trim($value)])) {
                throw new Exception("El valor '{$value}' no es válido para el campo {$field}");
            }
        }

        return true;
    } catch (Exception $e) {
        throw new Exception("Error validando glosario para {$field}: " . $e->getMessage());
    }
}

function validate_boolean_fields($params) {
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
        'vehicle-fumador',
        'frenada-regenerativa',
        'one-pedal'
    ];

    foreach ($boolean_fields as $field) {
        if (isset($params[$field])) {
            validate_boolean_value($field, $params[$field]);
        }
    }
}

function validate_boolean_value($field, $value) {
    // Si es un booleano nativo, es válido
    if (is_bool($value)) {
        return true;
    }
    
    // Si es string
    if (is_string($value)) {
        $value = strtolower(trim($value));
        return in_array($value, get_valid_boolean_values());
    }

    // Si es numérico
    if (is_numeric($value)) {
        return in_array($value, [0, 1]);
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

function validate_numeric_fields($params) {
    $numeric_fields = [
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
        'autonomia-wltp',
        'autonomia-urbana-wltp',
        'autonomia-extraurbana-wltp',
        'autonomia-electrica',
        'temps-recarrega-total',
        'temps-recarrega-fins-80',
        'n-motors',
        'potencia-combinada'
    ];

    foreach ($numeric_fields as $field) {
        if (isset($params[$field]) && !is_numeric($params[$field])) {
            throw new Exception("El campo {$field} debe ser un valor numérico");
        }
    }
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
        'extres-cotxe'
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
