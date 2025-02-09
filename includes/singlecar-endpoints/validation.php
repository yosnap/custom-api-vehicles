<?php

function validate_all_fields($params) {
    $errors = [];

    try {
        // Validar campos requeridos
        validate_required_fields($params);

        // Validar campos meta
        validate_meta_fields($params);

        // Validar campos de glosario
        validate_glossary_fields($params);

        // Validar campos booleanos
        validate_boolean_fields($params);

        // Validar campos numéricos
        validate_numeric_fields($params);

    } catch (Exception $e) {
        throw new Exception("Errores de validación:\n" . $e->getMessage());
    }
}

function validate_required_fields($params) {
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

    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($params[$field]) || empty($params[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception('Campos requeridos faltantes: ' . implode(', ', $missing_fields));
    }
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
            if (!in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'])) {
                throw new Exception("El campo {$field} debe ser un valor booleano válido");
            }
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
    $true_values = ['true', 'si', '1', 'yes', 'on'];
    $false_values = ['false', 'no', '0', 'off'];
    
    if (!in_array(strtolower($value), array_merge($true_values, $false_values))) {
        throw new Exception("El campo {$field} debe ser un valor booleano válido");
    }
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
