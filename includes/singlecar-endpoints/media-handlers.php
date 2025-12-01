<?php

function process_vehicle_images($post_id, $params) {
    Vehicle_Debug_Handler::log('=== INICIO PROCESAMIENTO DE IMÁGENES ===');
    Vehicle_Debug_Handler::log('Post ID: ' . $post_id);
    Vehicle_Debug_Handler::log('Parámetros recibidos: ' . print_r($params, true));

    try {
        // Procesar imagen destacada
        if (isset($params['imatge-destacada-id']) || 
            isset($params['imatge-destacada-url']) || 
            isset($params['imatge-destacada']) || 
            (isset($_FILES['imatge-destacada']) && !empty($_FILES['imatge-destacada']['tmp_name']))) {
            
            // Eliminar imagen destacada existente si hay una nueva
            delete_post_thumbnail($post_id);
            Vehicle_Debug_Handler::log('Imagen destacada anterior eliminada');

            // Procesar nueva imagen destacada
            if (isset($params['imatge-destacada-id'])) {
                process_featured_image($post_id, $params['imatge-destacada-id']);
            } elseif (isset($_FILES['imatge-destacada']) && !empty($_FILES['imatge-destacada']['tmp_name'])) {
                process_uploaded_featured_image($post_id, $_FILES['imatge-destacada']);
            } elseif (isset($params['imatge-destacada-url']) && !empty($params['imatge-destacada-url'])) {
                process_featured_image_url($post_id, $params['imatge-destacada-url']);
            } elseif (isset($params['imatge-destacada']) && !empty($params['imatge-destacada'])) {
                process_featured_image($post_id, $params['imatge-destacada']);
            }
        }

        // Procesar galería
        if (isset($params['galeria-vehicle']) || 
            isset($params['galeria-vehicle-urls']) || 
            (isset($_FILES['galeria-vehicle']) && !empty($_FILES['galeria-vehicle']['tmp_name']))) {
            
            // Eliminar galería existente
            delete_post_meta($post_id, 'ad_gallery');
            Vehicle_Debug_Handler::log('Galería anterior eliminada');

            // Procesar nueva galería
            if (isset($params['galeria-vehicle']) && !empty($params['galeria-vehicle'])) {
                process_gallery_images($post_id, $params['galeria-vehicle']);
            } elseif (isset($params['galeria-vehicle-urls']) && !empty($params['galeria-vehicle-urls'])) {
                process_gallery_urls($post_id, $params['galeria-vehicle-urls']);
            } elseif (isset($_FILES['galeria-vehicle']) && !empty($_FILES['galeria-vehicle']['tmp_name'])) {
                process_uploaded_gallery_images($post_id, $_FILES['galeria-vehicle']);
            }
        }

        Vehicle_Debug_Handler::log('=== FIN PROCESAMIENTO DE IMÁGENES ===');
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log('ERROR en procesamiento de imágenes: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Procesa una URL de imagen destacada, incluso si es una ruta local
 */
function process_featured_image_url($post_id, $image_url) {
    try {
        Vehicle_Debug_Handler::log('Procesando URL de imagen destacada: ' . $image_url);
        
        // Verificar si es una URL web o una ruta local
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            // Es una URL web
            $attach_id = handle_image_url($image_url, $post_id);
        } else {
            // Es una ruta local, verificar si el archivo existe
            if (file_exists($image_url)) {
                $attach_id = handle_local_file($image_url, $post_id);
            } else {
                throw new Exception('El archivo local no existe: ' . $image_url);
            }
        }
        
        if ($attach_id && wp_attachment_is_image($attach_id)) {
            set_post_thumbnail($post_id, $attach_id);
            Vehicle_Debug_Handler::log('Imagen destacada establecida correctamente con ID: ' . $attach_id);
        } else {
            throw new Exception('ID de imagen destacada no válido');
        }
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log("Error procesando URL de imagen destacada: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Maneja un archivo local y lo importa a la biblioteca de medios
 */
function handle_local_file($file_path, $post_id) {
    // Obtener información del archivo
    $file_name = basename($file_path);
    $file_info = wp_check_filetype($file_name);
    
    if (empty($file_info['ext'])) {
        throw new Exception('Tipo de archivo no válido');
    }
    
    // Copiar el archivo a un directorio temporal
    $temp_file = wp_tempnam($file_name);
    if (!copy($file_path, $temp_file)) {
        throw new Exception('No se pudo copiar el archivo local');
    }
    
    $file_array = [
        'name' => wp_unique_filename(
            wp_upload_dir()['path'], 
            'vehicle-image-' . time() . '.' . $file_info['ext']
        ),
        'tmp_name' => $temp_file,
        'type' => $file_info['type']
    ];
    
    // Manejar el sideload
    $attach_id = media_handle_sideload($file_array, $post_id);
    @unlink($temp_file); // Limpiar archivo temporal
    
    if (is_wp_error($attach_id)) {
        throw new Exception('Error procesando imagen local: ' . $attach_id->get_error_message());
    }
    
    return $attach_id;
}

function process_uploaded_featured_image($post_id, $image_file) {
    try {
        Vehicle_Debug_Handler::log('Procesando imagen destacada subida: ' . print_r($image_file, true));
        $attach_id = handle_uploaded_image($image_file, $post_id);
        if ($attach_id && wp_attachment_is_image($attach_id)) {
            set_post_thumbnail($post_id, $attach_id);
            Vehicle_Debug_Handler::log('Imagen destacada establecida correctamente con ID: ' . $attach_id);
        } else {
            throw new Exception('ID de imagen destacada no válido');
        }
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log("Error procesando imagen destacada subida: " . $e->getMessage());
        throw $e;
    }
}

function process_uploaded_gallery_images($post_id, $gallery_files) {
    try {
        Vehicle_Debug_Handler::log('=== INICIO PROCESAMIENTO GALERÍA SUBIDA ===');
        Vehicle_Debug_Handler::log('Post ID: ' . $post_id);
        Vehicle_Debug_Handler::log('Archivos de galería recibidos: ' . print_r($gallery_files, true));

        $gallery_ids = [];
        foreach ($gallery_files['tmp_name'] as $index => $tmp_name) {
            if (empty($tmp_name)) {
                Vehicle_Debug_Handler::log('Archivo vacío, continuando con el siguiente');
                continue;
            }

            Vehicle_Debug_Handler::log('Procesando archivo de galería: ' . $gallery_files['name'][$index]);

            try {
                $attach_id = handle_uploaded_image($gallery_files, $post_id, $index);
                if ($attach_id) {
                    Vehicle_Debug_Handler::log('Archivo de galería procesado, ID: ' . $attach_id);
                    $gallery_ids[] = $attach_id;
                }
            } catch (Exception $e) {
                Vehicle_Debug_Handler::log('Error procesando archivo de galería: ' . $e->getMessage());
                continue;
            }
        }

        Vehicle_Debug_Handler::log('IDs de galería recolectados: ' . print_r($gallery_ids, true));

        if (!empty($gallery_ids)) {
            save_gallery_meta($post_id, $gallery_ids);
            Vehicle_Debug_Handler::log('Galería guardada exitosamente');
        } else {
            Vehicle_Debug_Handler::log('No se encontraron IDs válidos para guardar en la galería');
        }

        Vehicle_Debug_Handler::log('=== FIN PROCESAMIENTO GALERÍA SUBIDA ===');
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log('ERROR CRÍTICO en proceso de galería subida: ' . $e->getMessage());
        throw $e;
    }
}

function handle_uploaded_image($image_file, $post_id, $index = null) {
    // Determinar si estamos manejando un solo archivo o un array de archivos
    $is_multiple = isset($index) && is_array($image_file['name']);
    
    // Obtener nombre del archivo
    $file_name = $is_multiple ? $image_file['name'][$index] : $image_file['name'];
    
    // Obtener extensión del archivo
    $file_info = wp_check_filetype($file_name);
    if (empty($file_info['ext'])) {
        throw new Exception('Tipo de archivo no válido');
    }

    $file_array = [
        'name' => wp_unique_filename(
            wp_upload_dir()['path'], 
            'vehicle-image-' . time() . '.' . $file_info['ext']
        ),
        'tmp_name' => $is_multiple ? $image_file['tmp_name'][$index] : $image_file['tmp_name'],
        'type' => $file_info['type']
    ];

    // Registrar información para depuración
    Vehicle_Debug_Handler::log('Procesando archivo: ' . print_r($file_array, true));

    // Manejar el sideload
    $attach_id = media_handle_sideload($file_array, $post_id);

    if (is_wp_error($attach_id)) {
        throw new Exception('Error procesando imagen: ' . $attach_id->get_error_message());
    }

    return $attach_id;
}

function process_featured_image($post_id, $image_data) {
    try {
        if (is_string($image_data)) {
            if (filter_var($image_data, FILTER_VALIDATE_URL)) {
                $attach_id = handle_image_url($image_data, $post_id);
            } elseif (strpos($image_data, 'data:image') === 0) {
                $attach_id = handle_base64_image($image_data, $post_id);
            } else {
                $attach_id = intval($image_data);
            }
        } else {
            $attach_id = intval($image_data);
        }

        if ($attach_id && wp_attachment_is_image($attach_id)) {
            set_post_thumbnail($post_id, $attach_id);
        } else {
            throw new Exception('ID de imagen destacada no válido');
        }
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log("Error procesando imagen destacada: " . $e->getMessage());
        throw $e;
    }
}

function process_gallery_images($post_id, $gallery_data) {
    try {
        Vehicle_Debug_Handler::log('=== INICIO PROCESAMIENTO GALERÍA ===');
        Vehicle_Debug_Handler::log('Post ID: ' . $post_id);
        Vehicle_Debug_Handler::log('Datos de galería recibidos: ' . print_r($gallery_data, true));

        $gallery_ids = [];
        $gallery = is_array($gallery_data) ? $gallery_data : [$gallery_data];

        foreach ($gallery as $image_url) {
            if (empty($image_url)) {
                Vehicle_Debug_Handler::log('URL de imagen vacía, continuando con la siguiente');
                continue;
            }

            Vehicle_Debug_Handler::log('Procesando URL de imagen: ' . $image_url);

            try {
                // Verificar si la imagen ya existe en la biblioteca de medios
                $existing_attachment = get_attachment_by_url($image_url);
                
                if ($existing_attachment) {
                    Vehicle_Debug_Handler::log('Imagen ya existe en la biblioteca, usando ID: ' . $existing_attachment);
                    $gallery_ids[] = $existing_attachment;
                } else {
                    Vehicle_Debug_Handler::log('Descargando nueva imagen...');
                    $attach_id = handle_image_url($image_url, $post_id);
                    if ($attach_id) {
                        Vehicle_Debug_Handler::log('Nueva imagen procesada, ID: ' . $attach_id);
                        $gallery_ids[] = $attach_id;
                    }
                }
            } catch (Exception $e) {
                Vehicle_Debug_Handler::log('Error procesando imagen: ' . $e->getMessage());
                continue;
            }
        }

        Vehicle_Debug_Handler::log('IDs de galería recolectados: ' . print_r($gallery_ids, true));

        if (!empty($gallery_ids)) {
            save_gallery_meta($post_id, $gallery_ids);
            Vehicle_Debug_Handler::log('Galería guardada exitosamente');
        } else {
            Vehicle_Debug_Handler::log('No se encontraron IDs válidos para guardar en la galería');
        }

        Vehicle_Debug_Handler::log('=== FIN PROCESAMIENTO GALERÍA ===');
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log('ERROR CRÍTICO en proceso de galería: ' . $e->getMessage());
        throw $e;
    }
}

function handle_image_url($url, $post_id) {
    // Validar URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('URL no válida: ' . $url);
    }

    // Descargar imagen
    $temp_file = download_url($url);
    if (is_wp_error($temp_file)) {
        throw new Exception('Error descargando imagen: ' . $temp_file->get_error_message());
    }

    // Obtener información del archivo
    $file_info = wp_check_filetype(basename(parse_url($url, PHP_URL_PATH)));
    
    // Si no se puede determinar la extensión desde la URL, intentar detectarla del archivo
    if (empty($file_info['ext'])) {
        // Intentar determinar el tipo de archivo usando mime_content_type
        $mime_type = mime_content_type($temp_file);
        Vehicle_Debug_Handler::log('MIME detectado: ' . $mime_type);
        
        // Mapeo de MIME types comunes a extensiones
        $mime_to_ext = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp'
        ];
        
        if (isset($mime_to_ext[$mime_type])) {
            $file_info['ext'] = $mime_to_ext[$mime_type];
            $file_info['type'] = $mime_type;
        } else {
            @unlink($temp_file);
            throw new Exception('Tipo de archivo no válido o no soportado: ' . $mime_type);
        }
    }

    $file_array = [
        'name' => wp_unique_filename(
            wp_upload_dir()['path'], 
            'gallery-image-' . time() . '.' . $file_info['ext']
        ),
        'tmp_name' => $temp_file,
        'type' => $file_info['type']
    ];

    // Registrar información para depuración
    Vehicle_Debug_Handler::log('Procesando archivo desde URL: ' . print_r($file_array, true));

    // Manejar el sideload
    $attach_id = media_handle_sideload($file_array, $post_id);
    @unlink($temp_file); // Limpiar archivo temporal

    if (is_wp_error($attach_id)) {
        throw new Exception('Error procesando imagen: ' . $attach_id->get_error_message());
    }

    return $attach_id;
}

function handle_base64_image($base64_string, $post_id) {
    $upload_dir = wp_upload_dir();
    
    // Extraer información de la imagen
    $image_parts = explode(";base64,", $base64_string);
    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1];
    $image_base64 = base64_decode($image_parts[1]);

    // Generar nombre único
    $filename = uniqid() . '.' . $image_type;
    $file_path = $upload_dir['path'] . '/' . $filename;

    // Guardar archivo
    if (!file_put_contents($file_path, $image_base64)) {
        throw new Exception('Error guardando imagen');
    }

    // Preparar attachment
    $filetype = wp_check_filetype($filename, null);
    $attachment = [
        'post_mime_type' => $filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    ];

    // Insertar attachment
    $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
    if (is_wp_error($attach_id)) {
        @unlink($file_path);
        throw new Exception('Error creando attachment');
    }

    // Generar metadatos
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}

function save_gallery_meta($post_id, $gallery_ids) {
    Vehicle_Debug_Handler::log('Guardando galería para post ' . $post_id);
    Vehicle_Debug_Handler::log('IDs de galería a guardar: ' . print_r($gallery_ids, true));

    try {
        // Asegurarse de que los IDs son válidos
        $valid_ids = array_filter($gallery_ids, function($id) {
            return is_numeric($id) && wp_attachment_is_image($id);
        });

        if (empty($valid_ids)) {
            Vehicle_Debug_Handler::log('No se encontraron IDs válidos para guardar en la galería');
            return;
        }

        // Convertir los IDs a string si es necesario
        $gallery_ids_string = is_array($valid_ids) ? implode(',', $valid_ids) : $valid_ids;
        
        // Actualizar el meta
        $result = update_post_meta($post_id, 'ad_gallery', $gallery_ids_string);
        
        if ($result === false) {
            throw new Exception('Error al guardar la galería en la base de datos');
        }

        Vehicle_Debug_Handler::log('Galería guardada exitosamente: ' . $gallery_ids_string);
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log('ERROR guardando galería: ' . $e->getMessage());
        throw $e;
    }
}

function get_attachment_by_url($url) {
    // Limpiar la URL
    $url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $url);
    
    global $wpdb;
    
    // Intentar encontrar por URL directa
    $attachment = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE guid = %s AND post_type = 'attachment'",
        $url
    ));

    if ($attachment) {
        return $attachment;
    }

    // Intentar encontrar por nombre de archivo
    $upload_dir = wp_upload_dir();
    $file_path = str_replace($upload_dir['baseurl'] . '/', '', $url);
    
    $attachment = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
        $file_path
    ));

    return $attachment;
}

function get_vehicle_images($post_id) {
    $response = [];

    // Obtener imagen destacada
    $featured_image_id = get_post_thumbnail_id($post_id);
    $featured_image_url = get_the_post_thumbnail_url($post_id, 'full');
    if ($featured_image_url) {
        $response['imatge-destacada-url'] = $featured_image_url;
    }
    if ($featured_image_id) {
        $response['imatge-destacada-wp-id'] = (int) $featured_image_id;
    }

    // Obtener galería
    $gallery_ids_string = get_post_meta($post_id, 'ad_gallery', true);
    if (!empty($gallery_ids_string)) {
        $gallery_urls = [];
        $gallery_wp_ids = [];
        $gallery_ids = explode(',', $gallery_ids_string);

        foreach ($gallery_ids as $gallery_id) {
            $gallery_id = trim($gallery_id);
            $url = wp_get_attachment_url($gallery_id);
            if ($url) {
                $gallery_urls[] = $url;
                $gallery_wp_ids[] = (int) $gallery_id;
            }
        }

        if (!empty($gallery_urls)) {
            $response['galeria-vehicle-urls'] = $gallery_urls;
            $response['galeria-vehicle-wp-ids'] = $gallery_wp_ids;
        }
    }

    return $response;
}

/**
 * Procesa una lista de URLs para la galería
 */
function process_gallery_urls($post_id, $gallery_urls) {
    try {
        Vehicle_Debug_Handler::log('=== INICIO PROCESAMIENTO GALERÍA URLS ===');
        Vehicle_Debug_Handler::log('Post ID: ' . $post_id);

        // Asegurarse de que tenemos un array
        if (!is_array($gallery_urls)) {
            $gallery_urls = explode(',', $gallery_urls);
        }

        Vehicle_Debug_Handler::log('URLs de galería recibidas: ' . print_r($gallery_urls, true));

        $gallery_ids = [];

        foreach ($gallery_urls as $url) {
            if (empty($url)) {
                Vehicle_Debug_Handler::log('URL vacía, continuando con la siguiente');
                continue;
            }

            Vehicle_Debug_Handler::log('Procesando URL de galería: ' . $url);

            try {
                // Verificar si es una URL web o una ruta local
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    // Es una URL web
                    $attach_id = handle_image_url($url, $post_id);
                } else {
                    // Es una ruta local, verificar si el archivo existe
                    if (file_exists($url)) {
                        $attach_id = handle_local_file($url, $post_id);
                    } else {
                        Vehicle_Debug_Handler::log('El archivo local no existe: ' . $url);
                        continue;
                    }
                }

                if ($attach_id) {
                    Vehicle_Debug_Handler::log('Imagen de galería procesada, ID: ' . $attach_id);
                    $gallery_ids[] = $attach_id;
                }
            } catch (Exception $e) {
                Vehicle_Debug_Handler::log('Error procesando URL de galería: ' . $e->getMessage());
                continue;
            }
        }

        Vehicle_Debug_Handler::log('IDs de galería recolectados: ' . print_r($gallery_ids, true));

        if (!empty($gallery_ids)) {
            save_gallery_meta($post_id, $gallery_ids);
            Vehicle_Debug_Handler::log('Galería guardada exitosamente');
        } else {
            Vehicle_Debug_Handler::log('No se encontraron IDs válidos para guardar en la galería');
        }

        Vehicle_Debug_Handler::log('=== FIN PROCESAMIENTO GALERÍA URLS ===');
    } catch (Exception $e) {
        Vehicle_Debug_Handler::log('ERROR CRÍTICO en proceso de galería URLs: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Elimina imágenes específicas de un vehículo por sus IDs de WordPress
 *
 * @param int $post_id ID del vehículo
 * @param array $params Parámetros con los IDs a eliminar:
 *   - delete-featured-image: bool - si true, elimina la imagen destacada
 *   - delete-gallery-ids: array de IDs de WP a eliminar de la galería
 * @return array Resultado de la operación con las imágenes restantes
 */
function delete_vehicle_images_by_id($post_id, $params) {
    Vehicle_Debug_Handler::log('=== INICIO ELIMINACIÓN DE IMÁGENES POR ID ===');
    Vehicle_Debug_Handler::log('Post ID: ' . $post_id);
    Vehicle_Debug_Handler::log('Parámetros: ' . print_r($params, true));

    $result = [
        'deleted_featured' => false,
        'deleted_gallery_ids' => [],
        'errors' => []
    ];

    try {
        // Eliminar imagen destacada si se solicita
        if (isset($params['delete-featured-image']) && $params['delete-featured-image']) {
            $featured_id = get_post_thumbnail_id($post_id);
            if ($featured_id) {
                // Eliminar el attachment de WordPress
                $deleted = wp_delete_attachment($featured_id, true);
                if ($deleted) {
                    delete_post_thumbnail($post_id);
                    $result['deleted_featured'] = true;
                    Vehicle_Debug_Handler::log('Imagen destacada eliminada: ID ' . $featured_id);
                } else {
                    $result['errors'][] = 'No se pudo eliminar la imagen destacada ID: ' . $featured_id;
                    Vehicle_Debug_Handler::log('Error eliminando imagen destacada ID: ' . $featured_id);
                }
            }
        }

        // Eliminar imágenes específicas de la galería
        if (isset($params['delete-gallery-ids']) && is_array($params['delete-gallery-ids']) && !empty($params['delete-gallery-ids'])) {
            $gallery_ids_to_delete = array_map('intval', $params['delete-gallery-ids']);
            Vehicle_Debug_Handler::log('IDs de galería a eliminar: ' . print_r($gallery_ids_to_delete, true));

            // Obtener la galería actual
            $gallery_ids_string = get_post_meta($post_id, 'ad_gallery', true);
            $current_gallery_ids = [];

            if (!empty($gallery_ids_string)) {
                $current_gallery_ids = array_map('intval', array_map('trim', explode(',', $gallery_ids_string)));
            }

            Vehicle_Debug_Handler::log('Galería actual: ' . print_r($current_gallery_ids, true));

            // Eliminar cada imagen solicitada
            foreach ($gallery_ids_to_delete as $id_to_delete) {
                if (in_array($id_to_delete, $current_gallery_ids)) {
                    // Eliminar el attachment de WordPress
                    $deleted = wp_delete_attachment($id_to_delete, true);
                    if ($deleted) {
                        $result['deleted_gallery_ids'][] = $id_to_delete;
                        // Remover del array de galería actual
                        $current_gallery_ids = array_diff($current_gallery_ids, [$id_to_delete]);
                        Vehicle_Debug_Handler::log('Imagen de galería eliminada: ID ' . $id_to_delete);
                    } else {
                        $result['errors'][] = 'No se pudo eliminar imagen de galería ID: ' . $id_to_delete;
                        Vehicle_Debug_Handler::log('Error eliminando imagen de galería ID: ' . $id_to_delete);
                    }
                } else {
                    $result['errors'][] = 'ID ' . $id_to_delete . ' no encontrado en la galería del vehículo';
                    Vehicle_Debug_Handler::log('ID no encontrado en galería: ' . $id_to_delete);
                }
            }

            // Actualizar la galería con los IDs restantes
            if (!empty($current_gallery_ids)) {
                $new_gallery_string = implode(',', $current_gallery_ids);
                update_post_meta($post_id, 'ad_gallery', $new_gallery_string);
                Vehicle_Debug_Handler::log('Galería actualizada: ' . $new_gallery_string);
            } else {
                delete_post_meta($post_id, 'ad_gallery');
                Vehicle_Debug_Handler::log('Galería vaciada completamente');
            }
        }

        // Devolver el estado actual de las imágenes
        $result['current_images'] = get_vehicle_images($post_id);

        Vehicle_Debug_Handler::log('=== FIN ELIMINACIÓN DE IMÁGENES POR ID ===');
        Vehicle_Debug_Handler::log('Resultado: ' . print_r($result, true));

        return $result;

    } catch (Exception $e) {
        Vehicle_Debug_Handler::log('ERROR CRÍTICO eliminando imágenes: ' . $e->getMessage());
        $result['errors'][] = $e->getMessage();
        return $result;
    }
}

/**
 * Añade nuevas imágenes a la galería existente sin eliminar las actuales
 *
 * @param int $post_id ID del vehículo
 * @param array $new_urls URLs de las nuevas imágenes a añadir
 * @return array Resultado con los nuevos IDs añadidos
 */
function add_images_to_gallery($post_id, $new_urls) {
    Vehicle_Debug_Handler::log('=== INICIO AÑADIR IMÁGENES A GALERÍA ===');
    Vehicle_Debug_Handler::log('Post ID: ' . $post_id);
    Vehicle_Debug_Handler::log('URLs a añadir: ' . print_r($new_urls, true));

    $result = [
        'added_ids' => [],
        'errors' => []
    ];

    try {
        // Obtener la galería actual
        $gallery_ids_string = get_post_meta($post_id, 'ad_gallery', true);
        $current_gallery_ids = [];

        if (!empty($gallery_ids_string)) {
            $current_gallery_ids = array_map('intval', array_map('trim', explode(',', $gallery_ids_string)));
        }

        // Procesar cada nueva URL
        foreach ($new_urls as $url) {
            if (empty($url)) continue;

            try {
                $attach_id = null;

                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $attach_id = handle_image_url($url, $post_id);
                } elseif (file_exists($url)) {
                    $attach_id = handle_local_file($url, $post_id);
                }

                if ($attach_id) {
                    $result['added_ids'][] = $attach_id;
                    $current_gallery_ids[] = $attach_id;
                    Vehicle_Debug_Handler::log('Nueva imagen añadida con ID: ' . $attach_id);
                }
            } catch (Exception $e) {
                $result['errors'][] = 'Error procesando URL ' . $url . ': ' . $e->getMessage();
                Vehicle_Debug_Handler::log('Error procesando URL: ' . $e->getMessage());
            }
        }

        // Guardar la galería actualizada
        if (!empty($current_gallery_ids)) {
            $gallery_string = implode(',', $current_gallery_ids);
            update_post_meta($post_id, 'ad_gallery', $gallery_string);
            Vehicle_Debug_Handler::log('Galería actualizada: ' . $gallery_string);
        }

        $result['current_images'] = get_vehicle_images($post_id);

        Vehicle_Debug_Handler::log('=== FIN AÑADIR IMÁGENES A GALERÍA ===');

        return $result;

    } catch (Exception $e) {
        Vehicle_Debug_Handler::log('ERROR CRÍTICO añadiendo imágenes: ' . $e->getMessage());
        $result['errors'][] = $e->getMessage();
        return $result;
    }
}
