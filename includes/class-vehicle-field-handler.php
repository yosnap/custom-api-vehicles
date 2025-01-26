<?php
class Vehicle_Field_Handler {
    /**
     * Procesa y valida un campo según su tipo
     */
    public static function process_field($field, $value, $type = null) {
        if ($type === null) {
            $type = Vehicle_Fields::get_field_type($field);
        }

        error_log(sprintf(
            'Procesando campo - Campo: %s, Valor: %s, Tipo: %s',
            $field,
            is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? 'true' : 'false') : $value),
            $type
        ));

        try {
            switch ($type) {
                case 'boolean':
                    return self::process_boolean_field($value);
                case 'number':
                    return self::process_number_field($value);
                case 'date':
                    return self::process_date_field($field, $value);
                case 'glossary':
                    // Solo procesar campos de glosario si tienen un valor
                    if ($value === null || $value === '') {
                        return null;
                    }
                    // Comprobar si es un campo especial
                    if (self::is_special_field($field)) {
                        return self::process_special_field($field, $value);
                    }
                    return self::process_glossary_field($field, $value);
                case 'select':
                    return self::process_select_field($field, $value);
                case 'switch':
                    return self::process_switch_field($field, $value);
                case 'text':
                default:
                    return self::process_text_field($value);
            }
        } catch (Exception $e) {
            error_log(sprintf(
                'Error procesando campo - Campo: %s, Error: %s',
                $field,
                $e->getMessage()
            ));
            throw $e;
        }
    }

    /**
     * Procesa un campo booleano
     */
    private static function process_boolean_field($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    /**
     * Procesa un campo numérico
     */
    private static function process_number_field($value) {
        return filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    /**
     * Procesa un campo de glosario
     */
    public static function process_glossary_field($field_name, $value) {
        error_log("Procesando campo de glosario: " . $field_name);
        error_log("Valor recibido: " . print_r($value, true));

        // Campos que siempre deben tratarse como array
        $array_fields = ['extres-cotxe', 'cables-recarrega', 'connectors'];
        
        // Si el campo es de tipo array, siempre tratar como array
        if (in_array($field_name, $array_fields)) {
            if (!is_array($value)) {
                $value = [$value];
            }
        }

        // Obtener el ID del glosario
        $glossary_id = self::get_glossary_id($field_name);
        error_log("ID del glosario obtenido: " . print_r($glossary_id, true));

        if (!$glossary_id) {
            error_log("ERROR: No se encontró ID de glosario para el campo: " . $field_name);
            throw new Exception("Campo de glosario no reconocido: " . $field_name);
        }

        // Obtener las opciones del glosario
        if (!function_exists('jet_engine') || !isset(jet_engine()->glossaries)) {
            error_log("ERROR: JetEngine Glossaries no está disponible");
            throw new Exception("JetEngine Glossaries no está disponible");
        }

        $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
        error_log("Opciones del glosario: " . print_r($options, true));

        if (empty($options)) {
            error_log("ERROR: No se encontraron opciones para el glosario ID: " . $glossary_id);
            throw new Exception("No se encontraron opciones para el glosario");
        }

        // Si es un array, procesar cada valor
        if (is_array($value)) {
            $processed_values = [];
            foreach ($value as $single_value) {
                // Buscar el valor en las opciones del glosario
                if (isset($options[$single_value]) || in_array($single_value, $options)) {
                    error_log("Valor válido encontrado para: " . $single_value);
                    $found_value = array_search($single_value, $options);
                    $processed_values[] = $found_value !== false ? $found_value : $single_value;
                } else {
                    error_log("ERROR: Valor inválido para el campo. Valores válidos: " . implode(', ', array_keys($options)));
                    throw new Exception(sprintf(
                        'Valor inválido "%s" para el campo %s. Valores válidos: %s',
                        $single_value,
                        $field_name,
                        implode(', ', array_keys($options))
                    ));
                }
            }
            return $processed_values;
        }

        // Para valores simples
        if (isset($options[$value]) || in_array($value, $options)) {
            error_log("Valor válido encontrado para: " . $value);
            $found_value = array_search($value, $options);
            return $found_value !== false ? $found_value : $value;
        }

        error_log("ERROR: Valor inválido para el campo. Valores válidos: " . implode(', ', array_keys($options)));
        throw new Exception(sprintf(
            'Valor inválido "%s" para el campo %s. Valores válidos: %s',
            $value,
            $field_name,
            implode(', ', array_keys($options))
        ));
    }

    private static function get_glossary_id($field_name) {
        $glossary_map = [
            'segment' => '41',
            'traccio' => '59',
            'emissions-vehicle' => '58',
            'roda-recanvi' => '60',
            'extres-cotxe' => '54',
            'cables-recarrega' => '50',
            'connectors' => '49'
        ];

        return isset($glossary_map[$field_name]) ? $glossary_map[$field_name] : null;
    }

    private static function is_special_field($field_name) {
        return in_array($field_name, [
            'venedor', 
            'traccio', 
            'roda-recanvi', 
            'segment',
            'color-vehicle',
            'tipus-vehicle',
            'tipus-combustible',
            'tipus-canvi',
            'tipus-propulsor',
            'estat-vehicle',
            'tipus-tapisseria',
            'color-tapisseria',
            'extres-cotxe',
            'cables-recarrega',
            'connectors'
        ]);
    }

    private static function process_special_field($field_name, $value) {
        switch ($field_name) {
            case 'venedor':
                $valid_values = ['professional', 'particular'];
                if (!in_array($value, $valid_values)) {
                    throw new Exception(sprintf(
                        'Valor inválido "%s" para el campo %s. Valores válidos: %s',
                        $value,
                        $field_name,
                        implode(', ', $valid_values)
                    ));
                }
                return $value;
            case 'traccio':
                // Mapeo de valores amigables a valores del sistema
                $traccio_map = [
                    'davant' => 't_davant',
                    'darrere' => 't_darrere',
                    'integral' => 't_integral',
                    'integral_connectable' => 't_integral_connectable',
                    // También aceptar valores del sistema
                    't_davant' => 't_davant',
                    't_darrere' => 't_darrere',
                    't_integral' => 't_integral',
                    't_integral_connectable' => 't_integral_connectable'
                ];

                if (!isset($traccio_map[$value])) {
                    throw new Exception(sprintf(
                        'Valor inválido "%s" para el campo %s. Valores válidos: %s',
                        $value,
                        $field_name,
                        implode(', ', array_unique(array_keys($traccio_map)))
                    ));
                }
                return $traccio_map[$value];

            case 'segment':
                // Mapeo de valores amigables a valores del sistema
                $segment_map = [
                    'sedan-berlina' => 'sedan',
                    'utilitari-petit' => 'utilitari-petit',
                    'turisme-mig' => 'turisme-mig',
                    'coupe' => 'coupe',
                    'familiar' => 'familiar',
                    'gran-turisme' => 'gran-turisme',
                    'suv' => 'suv',
                    '4x4' => '4x4',
                    'monovolum' => 'monovolum',
                    'furgoneta-passatgers' => 'furgo-passatgers',
                    'cabrio' => 'cabrio-descapotable',
                    'pickup' => 'pickup'
                ];

                if (!isset($segment_map[$value])) {
                    throw new Exception(sprintf(
                        'Valor inválido "%s" para el campo %s. Valores válidos: %s',
                        $value,
                        $field_name,
                        implode(', ', array_keys($segment_map))
                    ));
                }
                return $segment_map[$value];

            case 'roda-recanvi':
                // Mapeo de valores amigables a valores del sistema
                $roda_map = [
                    // Valores amigables
                    'roda-substitucio' => 'roda_substitucio',
                    'kit-reparacio' => 'r_kit_reparacio',
                    'sense-roda' => 'sense_roda',
                    // Valores del sistema
                    'roda_substitucio' => 'roda_substitucio',
                    'r_kit_reparacio' => 'r_kit_reparacio',
                    'sense_roda' => 'sense_roda'
                ];

                if (!isset($roda_map[$value])) {
                    throw new Exception(sprintf(
                        'Valor inválido "%s" para el campo %s. Valores válidos: %s',
                        $value,
                        $field_name,
                        implode(', ', array_unique(array_keys($roda_map)))
                    ));
                }
                return $roda_map[$value];

            case 'extres-cotxe':
                // Si el valor es un string, convertirlo a array
                if (is_string($value)) {
                    $value = [$value];
                }
                
                // Validar que sea un array
                if (!is_array($value)) {
                    throw new Exception('El campo extres-cotxe debe ser un array de valores');
                }

                // Obtener las opciones válidas del glosario
                if (!function_exists('jet_engine') || !isset(jet_engine()->glossaries)) {
                    throw new Exception("JetEngine Glossaries no está disponible");
                }

                $options = jet_engine()->glossaries->filters->get_glossary_options('54');
                if (empty($options)) {
                    throw new Exception("No se encontraron opciones para el glosario extres-cotxe");
                }

                // Validar cada valor del array
                $processed_values = [];
                foreach ($value as $extra) {
                    if (!isset($options[$extra]) && !in_array($extra, $options)) {
                        throw new Exception(sprintf(
                            'Valor inválido "%s" para el campo %s. Valores válidos: %s',
                            $extra,
                            $field_name,
                            implode(', ', array_keys($options))
                        ));
                    }
                    // Si el valor es una etiqueta, obtener su clave
                    $found_value = array_search($extra, $options);
                    $processed_values[] = $found_value !== false ? $found_value : $extra;
                }

                return $processed_values;

            case 'cables-recarrega':
            case 'connectors':
                // Si el valor es un string, convertirlo a array
                if (is_string($value)) {
                    $value = [$value];
                }
                
                // Validar que sea un array
                if (!is_array($value)) {
                    throw new Exception("El campo {$field_name} debe ser un array de valores");
                }

                // Obtener las opciones válidas del glosario
                if (!function_exists('jet_engine') || !isset(jet_engine()->glossaries)) {
                    throw new Exception("JetEngine Glossaries no está disponible");
                }

                $glossary_id = self::get_glossary_id($field_name);
                $options = jet_engine()->glossaries->filters->get_glossary_options($glossary_id);
                if (empty($options)) {
                    throw new Exception("No se encontraron opciones para el glosario {$field_name}");
                }

                // Validar cada valor del array
                $processed_values = [];
                foreach ($value as $item) {
                    if (!isset($options[$item]) && !in_array($item, $options)) {
                        throw new Exception(sprintf(
                            'Valor inválido "%s" para el campo %s. Valores válidos: %s',
                            $item,
                            $field_name,
                            implode(', ', array_keys($options))
                        ));
                    }
                    // Si el valor es una etiqueta, obtener su clave
                    $found_value = array_search($item, $options);
                    $processed_values[] = $found_value !== false ? $found_value : $item;
                }

                return $processed_values;

            // Campos que no necesitan transformación
            case 'color-vehicle':
            case 'tipus-vehicle':
            case 'tipus-combustible':
            case 'tipus-canvi':
            case 'tipus-propulsor':
            case 'estat-vehicle':
            case 'tipus-tapisseria':
            case 'color-tapisseria':
                return $value;

            default:
                throw new Exception("Campo especial no reconocido: $field_name");
        }
    }

    /**
     * Procesa un campo de tipo switch (booleano)
     */
    private static function process_switch_field($field, $value) {
        // Asegurarse de que el valor sea booleano
        if (is_string($value)) {
            $value = strtolower($value);
            $value = in_array($value, ['true', '1', 'yes', 'on']);
        }
        
        return (bool) $value;
    }

    /**
     * Procesa campos de selección
     */
    private static function process_select_field($field, $value) {
        $normalized_value = strtolower($value);
        $options = [];
        
        switch ($field) {
            case 'venedor':
                $options = Vehicle_Fields::get_venedor_options();
                break;
            default:
                throw new Exception("Campo de selección no reconocido: {$field}");
        }
        
        if (array_key_exists($normalized_value, $options)) {
            return $normalized_value;
        }
        
        throw new Exception(
            sprintf(
                'El valor %s no es válido para %s. Valores válidos: %s',
                $value,
                $field,
                implode(', ', array_keys($options))
            )
        );
    }

    /**
     * Procesa un campo de texto
     */
    private static function process_text_field($value) {
        return sanitize_text_field($value);
    }

    /**
     * Procesa un campo de fecha
     */
    private static function process_date_field($field_name, $value) {
        if (empty($value)) {
            return null;
        }

        // Validar formato de fecha
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new Exception(sprintf(
                'El campo %s debe tener formato YYYY-MM-DD',
                $field_name
            ));
        }

        // Convertir a timestamp
        return $date->getTimestamp();
    }

    /**
     * Formatea un valor para la respuesta
     */
    public static function format_response_value($field_name, $value, $type) {
        if ($value === null || $value === '') {
            return null;
        }

        switch ($type) {
            case 'date':
                // Convertir timestamp a formato Y-m-d
                return date('Y-m-d', intval($value));
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return floatval($value);
            default:
                return $value;
        }
    }
}
