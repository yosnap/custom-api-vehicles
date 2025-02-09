<?php

function process_and_save_meta_fields($post_id, $params) {
    $meta_fields = Vehicle_Fields::get_meta_fields();
    $flag_fields = Vehicle_Fields::get_flag_fields();
    $errors = [];

    try {
        // Validar todos los campos antes de procesar
        validate_all_fields($params);

        // Mapeo de campos
        $field_mapping = get_field_mapping();
        process_mapped_fields($post_id, $params, $field_mapping);

        // Procesar venedor por defecto
        update_post_meta($post_id, 'venedor', 'professional');

        // Procesar campos meta estándar
        process_standard_meta_fields($post_id, $params, $meta_fields, $field_mapping);

        // Procesar campos de glosario
        process_glossary_fields($post_id, $params);

        // Procesar campos con flag
        process_flag_fields($post_id, $params, $flag_fields);

        // Procesar dies-caducitat
        process_caducitat_field($post_id, $params);

        // Procesar estado activo
        process_active_status($post_id, $params);

    } catch (Exception $e) {
        throw new Exception("Errores de validación:\n" . $e->getMessage());
    }
}

function get_field_mapping() {
    return [
        'any-fabricacio' => 'any',
        'velocitat-max-cotxe' => 'velocitat-maxima',
        'numero-maleters-cotxe' => 'maleters',
        'capacitat-maleters-cotxe' => 'capacitat-total',
        'acceleracio-0-100-cotxe' => 'acceleracio-0-100',
        'numero-motors' => 'n-motors',
        'galeria-vehicle' => 'ad_gallery',
        'carrosseria-cotxe' => 'segment',
        'traccio' => 'traccio',
        'roda-recanvi' => 'roda-recanvi',
        'anunci-destacat' => 'is-vip'
    ];
}

function process_mapped_fields($post_id, $params, $field_mapping) {
    foreach ($field_mapping as $api_field => $db_field) {
        if (isset($params[$api_field])) {
            $value = $params[$api_field];
            
            if ($db_field === 'is-vip') {
                process_vip_status($post_id, $value);
            } else {
                update_post_meta($post_id, $db_field, $value);
            }
        }
    }
}

function process_vip_status($post_id, $value) {
    $value = strtolower(trim($value));
    $true_values = ['true', 'si', '1', 'yes', 'on'];

    if (in_array($value, $true_values, true)) {
        update_post_meta($post_id, 'is-vip', 'true');
        update_post_meta($post_id, 'data-vip', current_time('timestamp'));
    } else {
        update_post_meta($post_id, 'is-vip', 'false');
        update_post_meta($post_id, 'data-vip', '');
    }
}

function process_standard_meta_fields($post_id, $params, $meta_fields, $field_mapping) {
    $excluded_fields = ['data-vip', 'venedor'];
    
    foreach ($meta_fields as $field => $type) {
        if (isset($params[$field]) && 
            !Vehicle_Fields::should_exclude_field($field) && 
            !isset($field_mapping[$field]) && 
            !in_array($field, $excluded_fields)) {
            
            process_single_meta_field($post_id, $field, $params[$field], $type);
        }
    }
}

function process_single_meta_field($post_id, $field, $value, $type) {
    switch ($type) {
        case 'number':
            update_post_meta($post_id, $field, floatval($value));
            break;
            
        case 'boolean':
        case 'switch':
            $processed_value = process_boolean_value($value);
            update_post_meta($post_id, $field, $processed_value);
            break;
            
        case 'array':
            process_array_meta_field($post_id, $field, $value);
            break;
            
        default:
            update_post_meta($post_id, $field, sanitize_text_field($value));
    }
}

function process_boolean_value($value) {
    if (is_string($value)) {
        $value = strtolower(trim($value));
        $true_values = ['true', 'si', '1', 'yes', 'on'];
        $false_values = ['false', 'no', '0', 'off'];

        if (in_array($value, $true_values, true)) {
            return 'true';
        } elseif (in_array($value, $false_values, true)) {
            return 'false';
        }
    }
    return 'false';
}

function process_array_meta_field($post_id, $field, $value) {
    $processed_value = is_array($value) ? $value : [$value];
    delete_post_meta($post_id, $field);

    $jet_engine_format = [
        0 => array_combine(range(0, count($processed_value) - 1), $processed_value),
        1 => array_combine(range(0, count($processed_value) - 1), $processed_value),
        2 => array_combine(range(0, count($processed_value) - 1), $processed_value)
    ];

    foreach ($jet_engine_format as $value) {
        add_post_meta($post_id, $field, $value);
    }
}

function process_glossary_fields($post_id, $params) {
    $glossary_fields = [
        'carrosseria-cotxe',
        'traccio',
        'roda-recanvi',
        'extres-cotxe',
        'cables-recarrega',
        'connectors'
    ];

    foreach ($glossary_fields as $field) {
        if (isset($params[$field])) {
            save_glossary_field_value($post_id, $field, $params[$field]);
        }
    }
}

// Renombramos esta función para evitar el conflicto
function save_glossary_field_value($post_id, $field, $value) {
    validate_glossary_field($field, $value);
    
    if (is_array($value)) {
        process_array_meta_field($post_id, $field, $value);
    } else {
        update_post_meta($post_id, $field, $value);
    }
}

function process_flag_fields($post_id, $params, $flag_fields) {
    foreach ($flag_fields as $field => $config) {
        if ($field !== 'is-vip' && isset($params[$field])) {
            process_flag_field($post_id, $field, $params[$field], $config);
        }
    }
}

function process_flag_field($post_id, $field, $value, $config) {
    $processed_value = Vehicle_Field_Handler::process_field($field, $value, $config['type']);
    
    if ($processed_value !== null) {
        update_post_meta($post_id, $config['meta_key'], $processed_value);
        if (isset($config['flag_key'])) {
            update_post_meta($post_id, $config['flag_key'], 'true');
        }
    } else {
        delete_post_meta($post_id, $config['meta_key']);
        if (isset($config['flag_key'])) {
            delete_post_meta($post_id, $config['flag_key']);
        }
    }
}

function process_caducitat_field($post_id, $params) {
    $dies_caducitat = 365; // Valor por defecto

    if (current_user_can('administrator') && isset($params['dies-caducitat'])) {
        $dies_caducitat = is_numeric($params['dies-caducitat']) ? 
            intval($params['dies-caducitat']) : $dies_caducitat;
    }

    update_post_meta($post_id, 'dies-caducitat', $dies_caducitat);
}

function process_active_status($post_id, $params) {
    if (isset($params['anunci-actiu'])) {
        $anunci_actiu = filter_var($params['anunci-actiu'], FILTER_VALIDATE_BOOLEAN);
        $dies_caducitat = $anunci_actiu ? 
            (current_user_can('administrator') && isset($params['dies-caducitat']) ? 
                intval($params['dies-caducitat']) : 365) : 
            0;
        
        update_post_meta($post_id, 'dies-caducitat', $dies_caducitat);
        update_post_meta($post_id, 'anunci-actiu', $anunci_actiu ? 'true' : 'false');
    }
}
