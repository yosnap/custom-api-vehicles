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
            'portes-cotxe' => 'number',
            'places-cotxe' => 'number',
            'velocitat-maxima' => 'number',
            'acceleracio-0-100' => 'number',
            'capacitat-total' => 'number',
            'maleters' => 'number',
            
            // Campos de texto/selección
            'venedor' => 'glossary',
            'any' => 'text',
            'versio' => 'text',
            'nombre-propietaris' => 'text',
            'garantia' => 'text',
            'vehicle-accidentat' => 'text',
            'emissions-vehicle' => 'glossary',
            'data-vip' => 'text',
            'traccio' => 'glossary',
            'roda-recanvi' => 'glossary',
            'color-exterior' => 'color',
            'segment' => 'glossary'
        ];
    }

    /**
     * Obtiene la configuración de los campos de taxonomía
     */
    public static function get_taxonomy_fields() {
        return [
            'tipus-vehicle' => 'types-of-transport',
            'tipus-combustible' => 'tipus-combustible',
            'tipus-canvi' => 'tipus-de-canvi',
            'tipus-propulsor' => 'tipus-de-propulsor',
            'estat-vehicle' => 'estat-vehicle',
            'color-exterior' => 'color'
        ];
    }

    /**
     * Obtiene los valores permitidos para cada taxonomía
     */
    public static function get_allowed_taxonomy_values() {
        return [
            'tipus-vehicle' => [
                'autocaravana-camper',
                'cotxe',
                'moto-quad-atv',
                'vehicle-comercial'
            ],
            'tipus-combustible' => [
                'combustible-altres',
                'combustible-benzina',
                'combustible-biocombustible',
                'combustible-diesel',
                'combustible-electric',
                'combustible-electric-combustible',
                'electriccombustible',
                'combustible-gas-natural-gnc',
                'combustible-gas-liquat-glp',
                'hibrid',
                'hibrid-endollable',
                'combustible-hidrogen',
                'combustible-solar',
                'combustible-solar-hibrid'
            ],
            'tipus-canvi' => [
                'auto-sequencial',
                'automatic',
                'geartronic',
                'manual',
                'semi-automatic',
                'sequencial'
            ],
            'tipus-propulsor' => [
                'combustio',
                'electric',
                'hibrid',
                'hibrid-endollable'
            ],
            'estat-vehicle' => [
                'classic',
                'km0-gerencia',
                'lloguer',
                'nou',
                'ocasio',
                'renting',
                'seminou'
            ]
        ];
    }

    /**
     * Obtiene los valores permitidos para una taxonomía específica
     */
    public static function get_taxonomy_field_name($field) {
        $taxonomy_fields = self::get_taxonomy_fields();
        return isset($taxonomy_fields[$field]) ? $taxonomy_fields[$field] : $field;
    }

    /**
     * Obtiene los valores permitidos para una taxonomía específica
     */
    public static function get_taxonomy_allowed_values($field) {
        $allowed_values = self::get_allowed_taxonomy_values();
        return isset($allowed_values[$field]) ? $allowed_values[$field] : [];
    }

    /**
     * Obtiene las opciones para un campo de glosario específico
     */
    public static function get_glossary_options($field) {
        switch ($field) {
            case 'venedor':
                return self::get_venedor_options();
            case 'segment':
                return self::get_carrosseria_options();
            case 'traccio':
                return self::get_traccio_options();
            case 'roda-recanvi':
                return self::get_roda_recanvi_options();
            case 'emissions-vehicle':
                return [
                    'euro1' => 'Euro1',
                    'euro2' => 'Euro2',
                    'euro3' => 'Euro3',
                    'euro4' => 'Euro4',
                    'euro5' => 'Euro5',
                    'euro6' => 'Euro6'
                ];
            default:
                return [];
        }
    }

    /**
     * Obtiene las opciones para el campo venedor
     */
    public static function get_venedor_options() {
        return [
            'particular' => 'particular',
            'professional' => 'professional'
        ];
    }

    /**
     * Obtiene las opciones para el campo de tracción
     */
    public static function get_traccio_options() {
        return [
            'darrere' => 't_darrere',
            'davant' => 't_davant',
            'integral-connectable' => 'i_integral_connectable',
            'integral' => 't_integral'
        ];
    }

    /**
     * Obtiene las opciones para el campo de rueda de repuesto
     */
    public static function get_roda_recanvi_options() {
        return [
            'roda-substitucio' => 'roda_substitucio',
            'kit-reparacio' => 'kit_reparacio'
        ];
    }

    /**
     * Obtiene las opciones para el campo carrosseria/segment
     */
    public static function get_carrosseria_options() {
        return [
            'utilitari-petit' => 'utilitari-petit',
            'turisme-mig' => 'turisme-mig',
            'sedan-berlina' => 'sedan-berlina',
            'coupe' => 'coupe',
            'gran-turisme' => 'gran-turisme',
            'familiar' => 'familiar',
            'suv' => 'suv',
            '4x4-tot-terreny' => '4x4-tot-terreny',
            'monovolum' => 'monovolum',
            'furgoneta-passatgers' => 'furgoneta-passatgers',
            'cabrio' => 'cabrio',
            'pick-up' => 'pick-up'
        ];
    }

    /**
     * Obtiene los campos que necesitan flags especiales
     */
    public static function get_flag_fields() {
        return [
            'traccio' => [
                'is_jet_engine' => true,
                'meta_key' => '_traccio',
                'flag_key' => 'traccio_flag'
            ],
            'segment' => [
                'is_jet_engine' => true,
                'meta_key' => '_segment',
                'flag_key' => 'segment_flag'
            ],
            'roda-recanvi' => [
                'is_jet_engine' => true,
                'meta_key' => '_roda-recanvi',
                'flag_key' => 'roda_recanvi_flag'
            ]
        ];
    }
}
