<?php
class Vehicle_Field_Handler {
    /**
     * Procesa y valida un campo según su tipo
     */
    public static function process_field($field, $value, $type) {
        switch ($type) {
            case 'boolean':
                return self::process_boolean_field($value);
            case 'number':
                return self::process_number_field($value);
            case 'glossary':
                if ($field === 'emissions-vehicle') {
                    return self::process_emission_field($value);
                }
                return sanitize_text_field($value);
            case 'text':
            default:
                return sanitize_text_field($value);
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
     * Procesa el campo de emisiones
     */
    private static function process_emission_field($value) {
        $normalized_value = strtolower($value);
        $field_options = Vehicle_Fields::get_emission_options();
        
        if (array_key_exists($normalized_value, $field_options)) {
            return $normalized_value;
        }
        
        throw new Exception(
            sprintf(
                'El valor %s no es válido para emissions-vehicle. Valores válidos: %s',
                $value,
                implode(', ', array_keys($field_options))
            )
        );
    }

    /**
     * Formatea un valor para la respuesta según su tipo
     */
    public static function format_response_value($field, $value, $type) {
        if (empty($value)) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return $value === 'true';
            case 'glossary':
                if ($field === 'emissions-vehicle') {
                    $field_options = Vehicle_Fields::get_emission_options();
                    return isset($field_options[$value]) ? $field_options[$value] : $value;
                }
                return $value;
            default:
                return $value;
        }
    }
}
