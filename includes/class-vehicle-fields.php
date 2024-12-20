<?php
class Vehicle_Fields {
    /**
     * Obtiene la configuración de los campos meta
     */
    public static function get_meta_fields() {
        return [
            // Campos booleanos
            'is-vip' => 'boolean',
            'venut' => 'boolean',
            'llibre-manteniment' => 'boolean',
            'revisions-oficials' => 'boolean',
            'impostos-deduibles' => 'boolean',
            'vehicle-a-canvi' => 'boolean',
            
            // Campos numéricos
            'dies-caducitat' => 'number',
            'preu' => 'number',
            'preu-mensual' => 'number',
            'preu-diari' => 'number',
            'preu-antic' => 'number',
            'quilometratge' => 'number',
            'cilindrada' => 'number',
            'potencia-cv' => 'number',
            'potencia-kw' => 'number',
            
            // Campos de texto/selección
            'venedor' => 'text',
            'any' => 'text',
            'versio' => 'text',
            'nombre-propietaris' => 'text',
            'garantia' => 'text',
            'vehicle-accidentat' => 'text',
            'emissions-vehicle' => 'glossary',
            'data-vip' => 'text'
        ];
    }

    /**
     * Obtiene la configuración de los campos de taxonomía
     */
    public static function get_taxonomy_fields() {
        return [
            'tipus-vehicle' => 'tipus',
            'tipus-combustible' => 'combustible',
            'tipus-canvi' => 'canvi',
            'color-exterior' => 'color',
            'tipus-carrosseria' => 'carrosseria'
        ];
    }

    /**
     * Obtiene las opciones para el campo de emisiones
     */
    public static function get_emission_options() {
        return [
            'euro1' => 'Euro1',
            'euro2' => 'Euro2',
            'euro3' => 'Euro3',
            'euro4' => 'Euro4',
            'euro5' => 'Euro5',
            'euro6' => 'Euro6'
        ];
    }
}
