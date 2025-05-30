<?php

// Eliminamos la función duplicada y la reemplazamos por una función que llama a la implementación en field-processors.php
function save_vehicle_meta_fields($post_id, $params) {
    // Redirigir a la implementación en field-processors.php
    if (function_exists('process_and_save_meta_fields')) {
        return process_and_save_meta_fields($post_id, $params);
    } else {
        Vehicle_Debug_Handler::log('Error: La función process_and_save_meta_fields no está disponible');
        return false;
    }
}

// Mantener el resto de funciones relacionadas con meta que no estén duplicadas
function get_vehicle_meta_fields($post_id) {
    // ... código existente ...
}

function format_meta_for_response($post_id, $meta_data) {
    // ... código existente ...
}

function prepare_meta_fields_for_vehicle($post_id, $params) {
    // ... código existente ...
}

// Cualquier otra función relacionada con metadatos que no esté duplicada
