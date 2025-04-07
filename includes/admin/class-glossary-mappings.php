<?php

/**
 * Clase para manejar los mapeos de glosarios
 */
class Vehicle_Glossary_Mappings {
    
    /**
     * Obtiene el ID de glosario para un campo específico
     * 
     * @param string $field_name Nombre del campo
     * @return int|null ID del glosario o null si no existe
     */
    public static function get_glossary_id($field_name) {
        $mappings = self::get_glossary_mappings();
        return isset($mappings[$field_name]) ? $mappings[$field_name] : null;
    }
    
    /**
     * Obtiene todos los mapeos de campos a glosarios
     * 
     * @return array Array asociativo de [campo => id_glosario]
     */
    public static function get_glossary_mappings() {
        return [
            'segment' => 41,
            'tipus-de-moto' => 42,
            'carrosseria-caravana' => 43,
            'carroseria-vehicle-comercial' => 44,
            'connectors' => 49,
            'cables-recarrega' => 50,
            'color-vehicle' => 51,
            'tipus-tapisseria' => 52,
            'color-tapisseria' => 53,
            'extres-cotxe' => 54,
            'extres-moto' => 55,
            'extres-autocaravana' => 56,
            'extres-habitacle' => 57,
            'emissions-vehicle' => 58,
            'traccio' => 59,
            'roda-recanvi' => 60,
            'tipus-canvi-moto' => 62,
            'tipus-canvi-electric' => 63
        ];
    }
}
