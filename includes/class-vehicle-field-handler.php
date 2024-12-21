<?php
class Vehicle_Field_Handler {
    /**
     * Procesa y valida un campo según su tipo
     */
    public static function process_field($field, $value, $type) {
        error_log(sprintf(
            'Procesando campo - Campo: %s, Tipo: %s, Valor: %s',
            $field,
            $type,
            is_array($value) ? json_encode($value) : $value
        ));

        try {
            switch ($type) {
                case 'boolean':
                    return self::process_boolean_field($value);
                case 'number':
                    return self::process_number_field($value);
                case 'glossary':
                    return self::process_glossary_field($field, $value);
                case 'select':
                    return self::process_select_field($field, $value);
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
    private static function process_glossary_field($field, $value) {
        // Obtener las opciones del glosario según el campo
        $glossary_options = Vehicle_Fields::get_glossary_options($field);
        $inverse_options = array_flip($glossary_options);
        
        // Si el valor está en los valores mapeados, lo convertimos de vuelta
        if (isset($inverse_options[$value])) {
            $value = $inverse_options[$value];
        }
        
        // Debug: Imprimir información del procesamiento
        error_log(sprintf(
            'Procesando campo de glosario - Campo: %s, Valor recibido: %s',
            $field,
            $value
        ));
        error_log(sprintf(
            'Opciones disponibles: %s',
            json_encode($glossary_options)
        ));

        // Si el valor no está en las opciones del glosario
        if (!isset($glossary_options[$value])) {
            error_log(sprintf(
                'Valor inválido para glosario - Campo: %s, Valor: %s, Opciones válidas: %s',
                $field,
                $value,
                implode(', ', array_keys($glossary_options))
            ));
            throw new Exception(sprintf(
                'Valor inválido "%s" para el campo %s. Valores válidos: %s',
                $value,
                $field,
                implode(', ', array_keys($glossary_options))
            ));
        }

        // Retornar el valor mapeado del glosario
        $mapped_value = $glossary_options[$value];
        error_log(sprintf(
            'Valor mapeado para glosario - Campo: %s, Original: %s, Mapeado: %s',
            $field,
            $value,
            $mapped_value
        ));

        return $mapped_value;
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
     * Formatea un valor para la respuesta
     */
    public static function format_response_value($field, $value, $type) {
        if ($type === 'glossary') {
            $glossary_options = Vehicle_Fields::get_glossary_options($field);
            $inverse_options = array_flip($glossary_options);
            
            // Si el valor es un array (caso JetEngine)
            if (is_array($value)) {
                $formatted_value = array();
                foreach ($value as $single_value) {
                    if (isset($inverse_options[$single_value])) {
                        $formatted_value[] = $inverse_options[$single_value];
                    } else {
                        $formatted_value[] = $single_value;
                    }
                }
                return $formatted_value;
            }
            
            // Si es un valor simple
            return isset($inverse_options[$value]) ? $inverse_options[$value] : $value;
        }
        
        return $value;
    }
}
