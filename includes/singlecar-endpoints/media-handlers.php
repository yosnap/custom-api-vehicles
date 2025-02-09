<?php

function process_vehicle_images($post_id, $params) {
    // Procesar imagen destacada
    if (isset($params['imatge-destacada-id'])) {
        process_featured_image($post_id, $params['imatge-destacada-id']);
    }

    // Procesar galería
    if (isset($params['galeria-vehicle'])) {
        process_gallery_images($post_id, $params['galeria-vehicle']);
    }
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
        $gallery_ids = [];
        $gallery = is_array($gallery_data) ? $gallery_data : [$gallery_data];

        foreach ($gallery as $image) {
            if (is_string($image)) {
                if (filter_var($image, FILTER_VALIDATE_URL)) {
                    $attach_id = handle_image_url($image, $post_id);
                    if ($attach_id) {
                        $gallery_ids[] = $attach_id;
                    }
                } elseif (strpos($image, 'data:image') === 0) {
                    $attach_id = handle_base64_image($image, $post_id);
                    if ($attach_id) {
                        $gallery_ids[] = $attach_id;
                    }
                } elseif (is_numeric($image)) {
                    $gallery_ids[] = intval($image);
                }
            } elseif (is_numeric($image)) {
                $gallery_ids[] = intval($image);
            }
        }

        if (!empty($gallery_ids)) {
            save_gallery_meta($post_id, $gallery_ids);
        }
    } catch (Exception $e) {
        error_log("Error procesando galería: " . $e->getMessage());
        throw $e;
    }
}

function handle_image_url($url, $post_id) {
    $temp_file = download_url($url);
    if (is_wp_error($temp_file)) {
        throw new Exception('Error descargando imagen: ' . $temp_file->get_error_message());
    }

    $file_array = [
        'name' => basename($url),
        'tmp_name' => $temp_file
    ];

    $attach_id = media_handle_sideload($file_array, $post_id);
    @unlink($temp_file);

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
    delete_post_meta($post_id, 'ad_gallery');
    $gallery_string = implode(',', $gallery_ids);
    add_post_meta($post_id, 'ad_gallery', $gallery_string);
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
