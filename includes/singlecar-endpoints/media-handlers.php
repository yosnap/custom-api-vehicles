<?php

function process_vehicle_images($post_id, $params) {
    // Procesar imagen destacada
    if (isset($params['imatge-destacada-id'])) {
        process_featured_image($post_id, $params['imatge-destacada-id']);
    } elseif (isset($_FILES['imatge-destacada']) && !empty($_FILES['imatge-destacada']['tmp_name'])) {
        // Procesar archivo subido directamente
        process_uploaded_featured_image($post_id, $_FILES['imatge-destacada']);
    } elseif (isset($params['imatge-destacada-url']) && !empty($params['imatge-destacada-url'])) {
        // Procesar URL de imagen
        process_featured_image_url($post_id, $params['imatge-destacada-url']);
    } elseif (isset($params['imatge-destacada']) && !empty($params['imatge-destacada'])) {
        // Procesar valor directo (podría ser base64 o ID)
        process_featured_image($post_id, $params['imatge-destacada']);
    }

    // Procesar galería
    if (isset($params['galeria-vehicle']) && !empty($params['galeria-vehicle'])) {
        // Procesar galería de imágenes (URLs, IDs o base64)
        process_gallery_images($post_id, $params['galeria-vehicle']);
    } elseif (isset($params['galeria-vehicle-urls']) && !empty($params['galeria-vehicle-urls'])) {
        // Procesar galería de URLs (incluyendo rutas locales)
        process_gallery_urls($post_id, $params['galeria-vehicle-urls']);
    } elseif (isset($_FILES['galeria-vehicle']) && !empty($_FILES['galeria-vehicle']['tmp_name'])) {
        // Procesar archivos de galería subidos directamente
        process_uploaded_gallery_images($post_id, $_FILES['galeria-vehicle']);
    }
}

/**
 * Procesa una URL de imagen destacada, incluso si es una ruta local
 */
function process_featured_image_url($post_id, $image_url) {
    try {
        error_log('Procesando URL de imagen destacada: ' . $image_url);
        
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
            error_log('Imagen destacada establecida correctamente con ID: ' . $attach_id);
        } else {
            throw new Exception('ID de imagen destacada no válido');
        }
    } catch (Exception $e) {
        error_log("Error procesando URL de imagen destacada: " . $e->getMessage());
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
        error_log('Procesando imagen destacada subida: ' . print_r($image_file, true));
        $attach_id = handle_uploaded_image($image_file, $post_id);
        if ($attach_id && wp_attachment_is_image($attach_id)) {
            set_post_thumbnail($post_id, $attach_id);
            error_log('Imagen destacada establecida correctamente con ID: ' . $attach_id);
        } else {
            throw new Exception('ID de imagen destacada no válido');
        }
    } catch (Exception $e) {
        error_log("Error procesando imagen destacada subida: " . $e->getMessage());
        throw $e;
    }
}

function process_uploaded_gallery_images($post_id, $gallery_files) {
    try {
        error_log('=== INICIO PROCESAMIENTO GALERÍA SUBIDA ===');
        error_log('Post ID: ' . $post_id);
        error_log('Archivos de galería recibidos: ' . print_r($gallery_files, true));

        $gallery_ids = [];
        foreach ($gallery_files['tmp_name'] as $index => $tmp_name) {
            if (empty($tmp_name)) {
                error_log('Archivo vacío, continuando con el siguiente');
                continue;
            }

            error_log('Procesando archivo de galería: ' . $gallery_files['name'][$index]);

            try {
                $attach_id = handle_uploaded_image($gallery_files, $post_id, $index);
                if ($attach_id) {
                    error_log('Archivo de galería procesado, ID: ' . $attach_id);
                    $gallery_ids[] = $attach_id;
                }
            } catch (Exception $e) {
                error_log('Error procesando archivo de galería: ' . $e->getMessage());
                continue;
            }
        }

        error_log('IDs de galería recolectados: ' . print_r($gallery_ids, true));

        if (!empty($gallery_ids)) {
            save_gallery_meta($post_id, $gallery_ids);
            error_log('Galería guardada exitosamente');
        } else {
            error_log('No se encontraron IDs válidos para guardar en la galería');
        }

        error_log('=== FIN PROCESAMIENTO GALERÍA SUBIDA ===');
    } catch (Exception $e) {
        error_log('ERROR CRÍTICO en proceso de galería subida: ' . $e->getMessage());
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
    error_log('Procesando archivo: ' . print_r($file_array, true));

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
        error_log("Error procesando imagen destacada: " . $e->getMessage());
        throw $e;
    }
}

function process_gallery_images($post_id, $gallery_data) {
    try {
        error_log('=== INICIO PROCESAMIENTO GALERÍA ===');
        error_log('Post ID: ' . $post_id);
        error_log('Datos de galería recibidos: ' . print_r($gallery_data, true));

        $gallery_ids = [];
        $gallery = is_array($gallery_data) ? $gallery_data : [$gallery_data];

        foreach ($gallery as $image_url) {
            if (empty($image_url)) {
                error_log('URL de imagen vacía, continuando con la siguiente');
                continue;
            }

            error_log('Procesando URL de imagen: ' . $image_url);

            try {
                // Verificar si la imagen ya existe en la biblioteca de medios
                $existing_attachment = get_attachment_by_url($image_url);
                
                if ($existing_attachment) {
                    error_log('Imagen ya existe en la biblioteca, usando ID: ' . $existing_attachment);
                    $gallery_ids[] = $existing_attachment;
                } else {
                    error_log('Descargando nueva imagen...');
                    $attach_id = handle_image_url($image_url, $post_id);
                    if ($attach_id) {
                        error_log('Nueva imagen procesada, ID: ' . $attach_id);
                        $gallery_ids[] = $attach_id;
                    }
                }
            } catch (Exception $e) {
                error_log('Error procesando imagen: ' . $e->getMessage());
                continue;
            }
        }

        error_log('IDs de galería recolectados: ' . print_r($gallery_ids, true));

        if (!empty($gallery_ids)) {
            save_gallery_meta($post_id, $gallery_ids);
            error_log('Galería guardada exitosamente');
        } else {
            error_log('No se encontraron IDs válidos para guardar en la galería');
        }

        error_log('=== FIN PROCESAMIENTO GALERÍA ===');
    } catch (Exception $e) {
        error_log('ERROR CRÍTICO en proceso de galería: ' . $e->getMessage());
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

    // Obtener extensión del archivo
    $file_info = wp_check_filetype(basename($url));
    if (empty($file_info['ext'])) {
        @unlink($temp_file);
        throw new Exception('Tipo de archivo no válido');
    }

    $file_array = [
        'name' => wp_unique_filename(
            wp_upload_dir()['path'], 
            'gallery-image-' . time() . '.' . $file_info['ext']
        ),
        'tmp_name' => $temp_file,
        'type' => $file_info['type']
    ];

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
    error_log('Iniciando guardado de galería para post ' . $post_id);
    
    // Asegurarse de que estamos usando la clave correcta 'ad_gallery'
    delete_post_meta($post_id, 'ad_gallery');
    
    if (!empty($gallery_ids)) {
        $gallery_string = implode(',', array_filter($gallery_ids));
        error_log('Intentando guardar galería con IDs: ' . $gallery_string);
        
        // Guardar usando la clave correcta 'ad_gallery'
        $result = add_post_meta($post_id, 'ad_gallery', $gallery_string);
        
        if ($result === false) {
            error_log('Error al guardar la galería');
        } else {
            error_log('Galería guardada exitosamente');
            // Verificar el guardado
            $saved_gallery = get_post_meta($post_id, 'ad_gallery', true);
            error_log('Galería recuperada después de guardar: ' . $saved_gallery);
        }
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
    $featured_image_url = get_the_post_thumbnail_url($post_id, 'full');
    if ($featured_image_url) {
        $response['imatge-destacada-url'] = $featured_image_url;
    }

    // Obtener galería
    $gallery_ids = get_post_meta($post_id, 'ad_gallery', true);
    if (!empty($gallery_ids)) {
        $gallery_urls = [];
        $gallery_ids = explode(',', $gallery_ids);
        
        foreach ($gallery_ids as $gallery_id) {
            $url = wp_get_attachment_url(trim($gallery_id));
            if ($url) {
                $gallery_urls[] = $url;
            }
        }
        
        if (!empty($gallery_urls)) {
            $response['galeria-vehicle-urls'] = $gallery_urls;
        }
    }

    return $response;
}

/**
 * Procesa una lista de URLs para la galería
 */
function process_gallery_urls($post_id, $gallery_urls) {
    try {
        error_log('=== INICIO PROCESAMIENTO GALERÍA URLS ===');
        error_log('Post ID: ' . $post_id);
        
        // Asegurarse de que tenemos un array
        if (!is_array($gallery_urls)) {
            $gallery_urls = explode(',', $gallery_urls);
        }
        
        error_log('URLs de galería recibidas: ' . print_r($gallery_urls, true));
        
        $gallery_ids = [];
        
        foreach ($gallery_urls as $url) {
            if (empty($url)) {
                error_log('URL vacía, continuando con la siguiente');
                continue;
            }
            
            error_log('Procesando URL de galería: ' . $url);
            
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
                        error_log('El archivo local no existe: ' . $url);
                        continue;
                    }
                }
                
                if ($attach_id) {
                    error_log('Imagen de galería procesada, ID: ' . $attach_id);
                    $gallery_ids[] = $attach_id;
                }
            } catch (Exception $e) {
                error_log('Error procesando URL de galería: ' . $e->getMessage());
                continue;
            }
        }
        
        error_log('IDs de galería recolectados: ' . print_r($gallery_ids, true));
        
        if (!empty($gallery_ids)) {
            save_gallery_meta($post_id, $gallery_ids);
            error_log('Galería guardada exitosamente');
        } else {
            error_log('No se encontraron IDs válidos para guardar en la galería');
        }
        
        error_log('=== FIN PROCESAMIENTO GALERÍA URLS ===');
    } catch (Exception $e) {
        error_log('ERROR CRÍTICO en proceso de galería URLs: ' . $e->getMessage());
        throw $e;
    }
}
