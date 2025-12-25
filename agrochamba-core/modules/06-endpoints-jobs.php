<?php
/**
 * =============================================================
 * MÓDULO 6: ENDPOINTS DE TRABAJOS
 * =============================================================
 * 
 * Endpoints:
 * - POST /agrochamba/v1/jobs - Crear nuevo trabajo
 * - GET /agrochamba/v1/me/jobs - Obtener trabajos del usuario actual
 * - GET /agrochamba/v1/companies/{company_name}/jobs - Obtener trabajos de una empresa
 * - GET /agrochamba/v1/companies/{company_name}/profile-with-jobs - Perfil completo de empresa con trabajos
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================
// SHIM DE COMPATIBILIDAD → Delegar a controlador namespaced
// =============================================================
if (!defined('AGROCHAMBA_JOBS_CONTROLLER_NAMESPACE_INITIALIZED')) {
    define('AGROCHAMBA_JOBS_CONTROLLER_NAMESPACE_INITIALIZED', true);

    // Si existe el controlador moderno, delegar y salir para evitar duplicidad
    if (class_exists('AgroChamba\\API\\Jobs\\JobsController')) {
        if (function_exists('error_log')) {
            error_log('AgroChamba: Delegando endpoints de trabajos a AgroChamba\\API\\Jobs\\JobsController (migración namespaces).');
        }
        \AgroChamba\API\Jobs\JobsController::init();
        return; // Evitar registrar endpoints legacy duplicados
    } else {
        if (function_exists('error_log')) {
            error_log('AgroChamba: No se encontró AgroChamba\\API\\Jobs\\JobsController. Usando implementación procedural legacy.');
        }
    }
}

// ==========================================
// 1. CREAR NUEVO TRABAJO (VERSIÓN CONSOLIDADA)
// ==========================================
if (!function_exists('agrochamba_create_job')) {
    function agrochamba_create_job($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para crear trabajos.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $params = $request->get_json_params();

        // Validar datos requeridos con mensajes claros
        if (empty($params['title'])) {
            return new WP_Error(
                'rest_invalid_param',
                'El título es requerido.',
                array('status' => 400, 'code' => 'missing_title')
            );
        }

        // Validación: Límite de 200 caracteres para el título
        $title_length = strlen($params['title']);
        if ($title_length > 200) {
            return new WP_Error(
                'rest_invalid_param',
                sprintf(
                    'El título no puede exceder 200 caracteres. Tu título tiene %d caracteres.',
                    $title_length
                ),
                array(
                    'status' => 400, 
                    'code' => 'title_too_long',
                    'current_length' => $title_length,
                    'max_length' => 200
                )
            );
        }

        // Validar contenido si se proporciona (máximo 10000 caracteres)
        if (isset($params['content']) && strlen($params['content']) > 10000) {
            return new WP_Error(
                'rest_invalid_param',
                'El contenido no puede exceder 10000 caracteres.',
                array('status' => 400, 'code' => 'content_too_long')
            );
        }

        // Preparar datos del post
        // Los trabajos se crean como 'pending' para moderación
        // Solo los administradores pueden publicar directamente
        $post_status = 'pending';
        if (in_array('administrator', $user->roles)) {
            $post_status = 'publish'; // Los admins pueden publicar directamente
        }
        
        // Configurar comentarios (por defecto habilitados)
        $comment_status = 'open'; // Por defecto, comentarios activados
        if (isset($params['comentarios_habilitados'])) {
            // Si se especifica el parámetro, respetar la elección del usuario
            $comentarios = filter_var($params['comentarios_habilitados'], FILTER_VALIDATE_BOOLEAN);
            $comment_status = $comentarios ? 'open' : 'closed';
        }
        
        // Determinar el tipo de post (trabajo o blog/post)
        // Solo admins pueden crear posts de blog
        $post_type = 'trabajo'; // Por defecto
        
        // Debug: verificar parámetros recibidos
        if (function_exists('error_log')) {
            error_log('AgroChamba: post_type recibido: ' . (isset($params['post_type']) ? $params['post_type'] : 'NO DEFINIDO'));
            error_log('AgroChamba: Usuario es admin: ' . (in_array('administrator', $user->roles) ? 'SI' : 'NO'));
            error_log('AgroChamba: Roles del usuario: ' . implode(', ', $user->roles));
        }
        
        if (isset($params['post_type'])) {
            $requested_type = sanitize_text_field($params['post_type']);
            
            // Solo admins pueden crear posts de blog
            if (in_array('administrator', $user->roles)) {
                if ($requested_type === 'post' || $requested_type === 'blog') {
                    $post_type = 'post'; // WordPress post type nativo para blogs
                    if (function_exists('error_log')) {
                        error_log('AgroChamba: Cambiando post_type a: post');
                    }
                }
            } else {
                // Si no es admin, ignorar el parámetro y usar trabajo por defecto
                if (function_exists('error_log')) {
                    error_log('AgroChamba: Usuario no es admin, ignorando post_type y usando trabajo');
                }
            }
        }
        
        if (function_exists('error_log')) {
            error_log('AgroChamba: post_type final: ' . $post_type);
        }
        
        $post_data = array(
            'post_type'       => $post_type,
            'post_title'      => sanitize_text_field($params['title']),
            'post_content'    => isset($params['content']) ? wp_kses_post($params['content']) : '',
            'post_status'     => $post_status,
            'post_author'     => $user_id,
            'comment_status'  => $comment_status, // Configurar estado de comentarios
        );

        // Si hay excerpt, agregarlo
        if (isset($params['excerpt'])) {
            $post_data['post_excerpt'] = sanitize_textarea_field($params['excerpt']);
        }

        // Procesar imágenes: si hay múltiples, agregar las demás al contenido
        $gallery_ids = isset($params['gallery_ids']) && is_array($params['gallery_ids']) ? array_map('intval', $params['gallery_ids']) : array();
        $additional_images_html = '';
        
        if (!empty($gallery_ids)) {
            // Si hay UNA imagen, será la portada
            // Si hay MÚLTIPLES imágenes, la primera será la portada y las demás se agregan al contenido
            if (count($gallery_ids) > 1) {
                // Obtener las imágenes adicionales (todas excepto la primera)
                $additional_image_ids = array_slice($gallery_ids, 1);
                
                // Generar HTML para las imágenes adicionales
                foreach ($additional_image_ids as $img_id) {
                    $img_url = wp_get_attachment_image_url($img_id, 'large');
                    if ($img_url) {
                        $additional_images_html .= '<p><img src="' . esc_url($img_url) . '" alt="" class="aligncenter size-large" /></p>' . "\n";
                    }
                }
                
                // Agregar las imágenes al contenido existente
                if (!empty($additional_images_html)) {
                    $existing_content = isset($post_data['post_content']) ? $post_data['post_content'] : '';
                    $post_data['post_content'] = $existing_content . "\n\n" . $additional_images_html;
                }
            }
            
            // Establecer la primera imagen como portada
            $post_data['meta_input'] = array(
                '_thumbnail_id' => intval($gallery_ids[0])
            );
        }

        // Crear el post
        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return new WP_Error(
                'rest_create_error',
                'Error al crear el trabajo: ' . $post_id->get_error_message(),
                array(
                    'status' => 500,
                    'code' => 'post_creation_failed',
                    'details' => $post_id->get_error_message()
                )
            );
        }

        if (!$post_id || $post_id === 0) {
            return new WP_Error(
                'rest_create_error',
                'No se pudo crear el trabajo. Por favor, intenta nuevamente.',
                array('status' => 500, 'code' => 'post_creation_failed')
            );
        }

        // ==========================================
        // VINCULAR EMPRESA AUTOMÁTICAMENTE (CPT Empresa)
        // ==========================================
        $empresa_id = null;

        // Si el usuario es empresa, buscar su CPT Empresa
        if (in_array('employer', $user->roles) || in_array('administrator', $user->roles)) {
            $empresa_cpt = agrochamba_get_empresa_by_user_id($user_id);
            
            if ($empresa_cpt) {
                $empresa_id = $empresa_cpt->ID;
            } elseif (isset($params['empresa_id']) && !empty($params['empresa_id'])) {
                $empresa_id = intval($params['empresa_id']);
            }
        } else {
            // Si no es employer, usar empresa_id proporcionado
            if (isset($params['empresa_id']) && !empty($params['empresa_id'])) {
                $empresa_id = intval($params['empresa_id']);
            }
        }

        // Solo procesar campos específicos de trabajo si es un trabajo
        if ($post_type === 'trabajo') {
        // Asignar empresa_id al trabajo (meta field)
        if ($empresa_id) {
            // Validar que la empresa existe y es del tipo correcto
            $empresa_post = get_post($empresa_id);
            if (!$empresa_post || $empresa_post->post_type !== 'empresa') {
                // Si la empresa no existe o no es del tipo correcto, limpiar empresa_id
                $empresa_id = null;
            } else {
                update_post_meta($post_id, 'empresa_id', $empresa_id);
                
                // También mantener compatibilidad con taxonomía empresa (legacy)
                $empresa_term = get_term_by('name', $empresa_post->post_title, 'empresa');
                if (!$empresa_term) {
                    $term_result = wp_insert_term(
                        $empresa_post->post_title,
                        'empresa',
                        array(
                            'description' => 'Empresa: ' . $empresa_post->post_title,
                            'slug' => sanitize_title($empresa_post->post_title)
                        )
                    );
                    if (!is_wp_error($term_result)) {
                        wp_set_post_terms($post_id, array($term_result['term_id']), 'empresa', false);
                    }
                } else {
                    wp_set_post_terms($post_id, array($empresa_term->term_id), 'empresa', false);
                }
            }
        }

            // Asignar otras taxonomías (solo para trabajos)
        if (isset($params['ubicacion_id']) && !empty($params['ubicacion_id'])) {
            wp_set_post_terms($post_id, array(intval($params['ubicacion_id'])), 'ubicacion', false);
        }

        if (isset($params['cultivo_id']) && !empty($params['cultivo_id'])) {
            wp_set_post_terms($post_id, array(intval($params['cultivo_id'])), 'cultivo', false);
        }

        if (isset($params['tipo_puesto_id']) && !empty($params['tipo_puesto_id'])) {
            wp_set_post_terms($post_id, array(intval($params['tipo_puesto_id'])), 'tipo_puesto', false);
        }

            // Guardar meta fields específicos de trabajos
        $meta_fields = array(
            'salario_min', 'salario_max', 'vacantes', 'fecha_inicio', 'fecha_fin',
            'duracion_dias', 'requisitos', 'beneficios', 'tipo_contrato', 'jornada',
            'contacto_whatsapp', 'contacto_email', 'google_maps_url', 'alojamiento', 'transporte',
                'alimentacion', 'estado', 'experiencia', 'genero', 'edad_minima', 'edad_maxima',
                'empresa_id'
        );

        foreach ($meta_fields as $field) {
            if (isset($params[$field])) {
                $value = $params[$field];
                // Convertir booleanos
                if (in_array($field, array('alojamiento', 'transporte', 'alimentacion'))) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                // Sanitizar URL de Google Maps
                if ($field === 'google_maps_url') {
                    $value = esc_url_raw($value);
                }
                update_post_meta($post_id, $field, $value);
                }
            }
            
            // Guardar comentarios_habilitados (por defecto true si no se especifica)
            if (isset($params['comentarios_habilitados'])) {
                $comentarios = filter_var($params['comentarios_habilitados'], FILTER_VALIDATE_BOOLEAN);
                update_post_meta($post_id, 'comentarios_habilitados', $comentarios);
            } else {
                // Por defecto, comentarios habilitados
                update_post_meta($post_id, 'comentarios_habilitados', true);
            }
        } else {
            // Para blogs, guardar comentarios_habilitados (por defecto true si no se especifica)
            if (isset($params['comentarios_habilitados'])) {
                $comentarios = filter_var($params['comentarios_habilitados'], FILTER_VALIDATE_BOOLEAN);
                update_post_meta($post_id, 'comentarios_habilitados', $comentarios);
            } else {
                // Por defecto, comentarios habilitados
                update_post_meta($post_id, 'comentarios_habilitados', true);
            }
            
            // Asignar categorías nativas de WordPress (solo para blogs)
            if (isset($params['categories']) && is_array($params['categories']) && !empty($params['categories'])) {
                $category_ids = array_map('intval', $params['categories']);
                // Validar que las categorías existen
                $valid_category_ids = array();
                foreach ($category_ids as $cat_id) {
                    $category = get_category($cat_id);
                    if ($category && !is_wp_error($category)) {
                        $valid_category_ids[] = $cat_id;
                    }
                }
                if (!empty($valid_category_ids)) {
                    wp_set_post_terms($post_id, $valid_category_ids, 'category', false);
                }
            }
        }

        // Imagen destacada (si se especifica directamente, tiene prioridad sobre gallery_ids)
        if (isset($params['featured_media']) && !empty($params['featured_media'])) {
            set_post_thumbnail($post_id, intval($params['featured_media']));
        } elseif (!empty($gallery_ids)) {
            // Si no hay featured_media pero hay gallery_ids, usar la primera
            set_post_thumbnail($post_id, intval($gallery_ids[0]));
        }

        // Guardar gallery_ids (todas las imágenes)
        if (!empty($gallery_ids)) {
            update_post_meta($post_id, 'gallery_ids', array_map('intval', $gallery_ids));
        }

        // Publicar en Facebook SOLO si el usuario lo solicitó explícitamente
        $facebook_result = null;
        $publish_to_facebook = isset($params['publish_to_facebook']) && filter_var($params['publish_to_facebook'], FILTER_VALIDATE_BOOLEAN);
        
        if ($publish_to_facebook && function_exists('agrochamba_post_to_facebook')) {
            // Preparar datos para Facebook incluyendo todas las imágenes y preferencias del usuario
            $job_data_for_facebook = array_merge($params, array(
                'featured_media' => isset($params['featured_media']) ? $params['featured_media'] : get_post_thumbnail_id($post_id),
                'gallery_ids' => !empty($gallery_ids) ? $gallery_ids : array(),
                // Incluir preferencias del usuario desde la app
                'facebook_use_link_preview' => isset($params['facebook_use_link_preview']) ? filter_var($params['facebook_use_link_preview'], FILTER_VALIDATE_BOOLEAN) : false,
                'facebook_shorten_content' => isset($params['facebook_shorten_content']) ? filter_var($params['facebook_shorten_content'], FILTER_VALIDATE_BOOLEAN) : false,
            ));
            $facebook_result = agrochamba_post_to_facebook($post_id, $job_data_for_facebook);
        }

        // Mensaje según el tipo de post
        $post_type_label = ($post_type === 'post') ? 'Artículo de blog' : 'Trabajo';
        $response_data = array(
            'success' => true,
            'message' => $post_status === 'pending' 
                ? $post_type_label . ' creado y enviado para revisión. Será publicado una vez aprobado por un administrador.' 
                : $post_type_label . ' creado correctamente.',
            'post_id' => $post_id,
            'status' => $post_status,
            'post_type' => $post_type
        );

        // Agregar información de Facebook si se publicó
        if ($facebook_result && !is_wp_error($facebook_result)) {
            $response_data['facebook'] = array(
                'published' => true,
                'facebook_post_id' => isset($facebook_result['facebook_post_id']) ? $facebook_result['facebook_post_id'] : null,
            );
        } elseif ($facebook_result && is_wp_error($facebook_result)) {
            $response_data['facebook'] = array(
                'published' => false,
                'error' => $facebook_result->get_error_message(),
            );
        }

        return new WP_REST_Response($response_data, 201);
    }
}

// ==========================================
// 2. ACTUALIZAR TRABAJO EXISTENTE
// ==========================================
if (!function_exists('agrochamba_update_job')) {
    function agrochamba_update_job($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para actualizar trabajos.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $post_id = intval($request->get_param('id'));
        $params = $request->get_json_params();
        
        // Solo employers y administradores pueden actualizar trabajos
        if (!in_array('employer', $user->roles) && !in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo las empresas pueden gestionar trabajos.', array('status' => 403));
        }

        // Verificar que el trabajo existe
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('rest_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        // Verificar que el usuario es el autor o es administrador
        if ($post->post_author != $user_id && !in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'No tienes permiso para actualizar este trabajo.', array('status' => 403));
        }

        // Preparar datos del post
        $post_data = array(
            'ID' => $post_id,
        );

        if (isset($params['title'])) {
            $post_data['post_title'] = sanitize_text_field($params['title']);
        }

        if (isset($params['content'])) {
            $post_data['post_content'] = wp_kses_post($params['content']);
        }

        if (isset($params['excerpt'])) {
            $post_data['post_excerpt'] = sanitize_textarea_field($params['excerpt']);
        }

        // Validación: Límite de 200 caracteres para el título
        if (isset($params['title'])) {
            $title_length = strlen($params['title']);
            if ($title_length > 200) {
            return new WP_Error(
                'rest_invalid_param',
                    sprintf(
                        'El título no puede exceder 200 caracteres. Tu título tiene %d caracteres.',
                        $title_length
                    ),
                    array(
                        'status' => 400, 
                        'code' => 'title_too_long',
                        'current_length' => $title_length,
                        'max_length' => 200
                    )
            );
            }
        }

        // Validar contenido si se actualiza
        if (isset($params['content']) && strlen($params['content']) > 10000) {
            return new WP_Error(
                'rest_invalid_param',
                'El contenido no puede exceder 10000 caracteres.',
                array('status' => 400, 'code' => 'content_too_long')
            );
        }

        // Procesar imágenes: si hay múltiples, agregar las demás al contenido
        $gallery_ids = isset($params['gallery_ids']) && is_array($params['gallery_ids']) ? array_map('intval', $params['gallery_ids']) : array();
        $additional_images_html = '';
        
        if (!empty($gallery_ids)) {
            // Si hay UNA imagen, será la portada
            // Si hay MÚLTIPLES imágenes, la primera será la portada y las demás se agregan al contenido
            if (count($gallery_ids) > 1) {
                // Obtener las imágenes adicionales (todas excepto la primera)
                $additional_image_ids = array_slice($gallery_ids, 1);
                
                // Generar HTML para las imágenes adicionales
                foreach ($additional_image_ids as $img_id) {
                    $img_url = wp_get_attachment_image_url($img_id, 'large');
                    if ($img_url) {
                        // Verificar si la imagen ya está en el contenido para evitar duplicados
                        $existing_content = isset($post_data['post_content']) ? $post_data['post_content'] : $post->post_content;
                        if (strpos($existing_content, $img_url) === false) {
                            $additional_images_html .= '<p><img src="' . esc_url($img_url) . '" alt="" class="aligncenter size-large" /></p>' . "\n";
                        }
                    }
                }
                
                // Agregar las imágenes al contenido existente solo si hay nuevas imágenes
                if (!empty($additional_images_html)) {
                    $existing_content = isset($post_data['post_content']) ? $post_data['post_content'] : $post->post_content;
                    $post_data['post_content'] = $existing_content . "\n\n" . $additional_images_html;
                }
            }
        }

        // Actualizar el post
        $updated = wp_update_post($post_data);

        if (is_wp_error($updated)) {
            return new WP_Error(
                'rest_update_error',
                'Error al actualizar el trabajo: ' . $updated->get_error_message(),
                array(
                    'status' => 500,
                    'code' => 'post_update_failed',
                    'details' => $updated->get_error_message()
                )
            );
        }

        if ($updated === 0) {
            return new WP_Error(
                'rest_update_error',
                'No se pudo actualizar el trabajo. Por favor, verifica los datos e intenta nuevamente.',
                array('status' => 500, 'code' => 'post_update_failed')
            );
        }

        // Actualizar taxonomías
        if (isset($params['ubicacion_id']) && !empty($params['ubicacion_id'])) {
            wp_set_post_terms($post_id, array(intval($params['ubicacion_id'])), 'ubicacion', false);
        }

        if (isset($params['cultivo_id']) && !empty($params['cultivo_id'])) {
            wp_set_post_terms($post_id, array(intval($params['cultivo_id'])), 'cultivo', false);
        }

        if (isset($params['tipo_puesto_id']) && !empty($params['tipo_puesto_id'])) {
            wp_set_post_terms($post_id, array(intval($params['tipo_puesto_id'])), 'tipo_puesto', false);
        }

        if (isset($params['empresa_id']) && !empty($params['empresa_id'])) {
            wp_set_post_terms($post_id, array(intval($params['empresa_id'])), 'empresa', false);
        }

        // Guardar meta fields (excluyendo comentarios_habilitados que se maneja por separado)
        $meta_fields = array(
            'salario_min', 'salario_max', 'vacantes', 'fecha_inicio', 'fecha_fin',
            'duracion_dias', 'requisitos', 'beneficios', 'tipo_contrato', 'jornada',
            'contacto_whatsapp', 'contacto_email', 'google_maps_url', 'alojamiento', 'transporte',
            'alimentacion', 'estado', 'experiencia', 'genero', 'edad_minima', 'edad_maxima', 'empresa_id'
        );

        foreach ($meta_fields as $field) {
            if (isset($params[$field])) {
                $value = $params[$field];
                // Convertir booleanos
                if (in_array($field, array('alojamiento', 'transporte', 'alimentacion'))) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                // Sanitizar según el tipo de campo
                if ($field === 'google_maps_url') {
                    $value = esc_url_raw($value);
                } elseif ($field === 'empresa_id') {
                    // Validar y sanitizar empresa_id
                    $value = intval($value);
                    if ($value > 0) {
                        // Verificar que la empresa existe
                        $empresa = get_post($value);
                        if (!$empresa || $empresa->post_type !== 'empresa') {
                            continue; // Saltar si la empresa no existe o no es del tipo correcto
                        }
                    } else {
                        $value = 0; // Permitir eliminar la asociación estableciendo 0
                    }
                }
                update_post_meta($post_id, $field, $value);
            }
        }
        
        // Manejar comentarios_habilitados por separado para asegurar que siempre se guarde
        if (isset($params['comentarios_habilitados'])) {
            $comentarios = filter_var($params['comentarios_habilitados'], FILTER_VALIDATE_BOOLEAN);
            update_post_meta($post_id, 'comentarios_habilitados', $comentarios);
            
            // Actualizar también el comment_status del post
            $new_comment_status = $comentarios ? 'open' : 'closed';
            wp_update_post(array(
                'ID' => $post_id,
                'comment_status' => $new_comment_status
            ));
        } else {
            // Si no se especifica, mantener el valor existente o establecer por defecto a true
            $current_value = get_post_meta($post_id, 'comentarios_habilitados', true);
            if ($current_value === '' || $current_value === false) {
                // Si no existe el meta field, establecer por defecto a true
                update_post_meta($post_id, 'comentarios_habilitados', true);
                wp_update_post(array(
                    'ID' => $post_id,
                    'comment_status' => 'open'
                ));
            }
        }

        // Actualizar imagen destacada (si se especifica directamente, tiene prioridad sobre gallery_ids)
        if (isset($params['featured_media'])) {
            if (!empty($params['featured_media'])) {
                set_post_thumbnail($post_id, intval($params['featured_media']));
            } else {
                delete_post_thumbnail($post_id);
            }
        } elseif (!empty($gallery_ids)) {
            // Si no hay featured_media pero hay gallery_ids, usar la primera
            set_post_thumbnail($post_id, intval($gallery_ids[0]));
        }

        // Guardar gallery_ids (solo la primera si hay múltiples, ya que las demás están en el contenido)
        if (isset($params['gallery_ids'])) {
            if (is_array($params['gallery_ids']) && !empty($params['gallery_ids'])) {
                $gallery_ids = array_map('intval', $params['gallery_ids']);
                // Guardar solo la primera imagen en gallery_ids para mantener compatibilidad
                // Las demás ya están embebidas en el contenido del post
                update_post_meta($post_id, 'gallery_ids', array($gallery_ids[0]));
            } else {
                // Si gallery_ids está vacío, limpiar
                update_post_meta($post_id, 'gallery_ids', array());
                if (empty($params['featured_media'])) {
                    delete_post_thumbnail($post_id);
                }
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Trabajo actualizado correctamente.',
            'post_id' => $post_id
        ), 200);
    }
}

// ==========================================
// 3. OBTENER TRABAJOS DEL USUARIO ACTUAL (MEJORADO CON TAXONOMÍAS)
// ==========================================
if (!function_exists('agrochamba_get_current_user_jobs')) {
    function agrochamba_get_current_user_jobs($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_not_logged_in', 'No has iniciado sesión.', array('status' => 401));
        }

        $current_user = wp_get_current_user();
        if (0 === $current_user->ID) {
            return new WP_Error('rest_not_logged_in', 'No has iniciado sesión.', array('status' => 401));
        }
        
        // Solo employers y administradores pueden ver sus trabajos
        if (!in_array('employer', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo las empresas pueden gestionar trabajos.', array('status' => 403));
        }

        // Parámetros de paginación
        $page = max(1, intval($request->get_param('page')) ?: 1);
        $per_page = min(100, max(1, intval($request->get_param('per_page')) ?: 20)); // Máximo 100, por defecto 20

        $args = array(
            'post_type' => 'trabajo',
            'author' => $current_user->ID,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => array('publish', 'pending', 'draft')
        );

        $query = new WP_Query($args);
        $posts_data = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post(get_the_ID());
                $post_id = $post->ID;
                
                // Obtener taxonomías
                $ubicaciones = wp_get_post_terms($post_id, 'ubicacion', array('fields' => 'all'));
                $cultivos = wp_get_post_terms($post_id, 'cultivo', array('fields' => 'all'));
                $tipos_puesto = wp_get_post_terms($post_id, 'tipo_puesto', array('fields' => 'all'));
                $empresas = wp_get_post_terms($post_id, 'empresa', array('fields' => 'all'));
                
                // Obtener meta fields
                $salario_min = get_post_meta($post_id, 'salario_min', true);
                $salario_max = get_post_meta($post_id, 'salario_max', true);
                $vacantes = get_post_meta($post_id, 'vacantes', true);
                $alojamiento = get_post_meta($post_id, 'alojamiento', true);
                $transporte = get_post_meta($post_id, 'transporte', true);
                $alimentacion = get_post_meta($post_id, 'alimentacion', true);
                $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
                
                // Formatear taxonomías
                $ubicacion_data = !empty($ubicaciones) && !is_wp_error($ubicaciones) ? array(
                    'id' => $ubicaciones[0]->term_id,
                    'name' => $ubicaciones[0]->name,
                    'slug' => $ubicaciones[0]->slug
                ) : null;
                
                $cultivo_data = !empty($cultivos) && !is_wp_error($cultivos) ? array(
                    'id' => $cultivos[0]->term_id,
                    'name' => $cultivos[0]->name,
                    'slug' => $cultivos[0]->slug
                ) : null;
                
                $tipo_puesto_data = !empty($tipos_puesto) && !is_wp_error($tipos_puesto) ? array(
                    'id' => $tipos_puesto[0]->term_id,
                    'name' => $tipos_puesto[0]->name,
                    'slug' => $tipos_puesto[0]->slug
                ) : null;
                
                $empresa_data = !empty($empresas) && !is_wp_error($empresas) ? array(
                    'id' => $empresas[0]->term_id,
                    'name' => $empresas[0]->name,
                    'slug' => $empresas[0]->slug
                ) : null;
                
                // Obtener imagen destacada
                $featured_media_id = get_post_thumbnail_id($post_id);
                $featured_media_url = $featured_media_id ? wp_get_attachment_image_url($featured_media_id, 'full') : null;
                
                // Construir respuesta
                $post_data = array(
                    'id' => $post_id,
                    'title' => array('rendered' => $post->post_title),
                    'content' => array('rendered' => $post->post_content),
                    'excerpt' => array('rendered' => $post->post_excerpt),
                    'date' => $post->post_date,
                    'modified' => $post->post_modified,
                    'status' => $post->post_status,
                    'link' => get_permalink($post_id),
                    'featured_media' => $featured_media_id,
                    'featured_media_url' => $featured_media_url,
                    'gallery_ids' => is_array($gallery_ids) ? $gallery_ids : array(),
                    'ubicacion' => $ubicacion_data,
                    'cultivo' => $cultivo_data,
                    'tipo_puesto' => $tipo_puesto_data,
                    'empresa' => $empresa_data,
                    'salario_min' => $salario_min,
                    'salario_max' => $salario_max,
                    'vacantes' => $vacantes,
                    'alojamiento' => filter_var($alojamiento, FILTER_VALIDATE_BOOLEAN),
                    'transporte' => filter_var($transporte, FILTER_VALIDATE_BOOLEAN),
                    'alimentacion' => filter_var($alimentacion, FILTER_VALIDATE_BOOLEAN),
                );
                
                $posts_data[] = $post_data;
            }
            wp_reset_postdata();
        }
        
        // Información de paginación
        $total = $query->found_posts;
        $total_pages = $query->max_num_pages;
        
        return new WP_REST_Response(array(
            'data' => $posts_data,
            'pagination' => array(
                'total' => $total,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page,
                'has_next_page' => $page < $total_pages,
                'has_prev_page' => $page > 1
            )
        ), 200);
    }
}

// ==========================================
// 3.5. OBTENER UN TRABAJO INDIVIDUAL
// ==========================================
if (!function_exists('agrochamba_get_single_job')) {
    function agrochamba_get_single_job($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_not_logged_in', 'No has iniciado sesión.', array('status' => 401));
        }

        $current_user = wp_get_current_user();
        if (0 === $current_user->ID) {
            return new WP_Error('rest_not_logged_in', 'No has iniciado sesión.', array('status' => 401));
        }

        $post_id = intval($request->get_param('id'));
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('rest_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        // Solo employers y administradores pueden obtener trabajos para editar
        if (!in_array('employer', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo las empresas pueden gestionar trabajos.', array('status' => 403));
        }
        
        // Verificar que el usuario es el autor o es administrador
        if ($post->post_author != $current_user->ID && !in_array('administrator', $current_user->roles)) {
            return new WP_Error('rest_forbidden', 'No tienes permiso para ver este trabajo.', array('status' => 403));
        }

        // Obtener taxonomías
        $ubicaciones = wp_get_post_terms($post_id, 'ubicacion', array('fields' => 'all'));
        $cultivos = wp_get_post_terms($post_id, 'cultivo', array('fields' => 'all'));
        $tipos_puesto = wp_get_post_terms($post_id, 'tipo_puesto', array('fields' => 'all'));
        $empresas = wp_get_post_terms($post_id, 'empresa', array('fields' => 'all'));
        
        // Obtener meta fields
        $salario_min = get_post_meta($post_id, 'salario_min', true);
        $salario_max = get_post_meta($post_id, 'salario_max', true);
        $vacantes = get_post_meta($post_id, 'vacantes', true);
        $alojamiento = get_post_meta($post_id, 'alojamiento', true);
        $transporte = get_post_meta($post_id, 'transporte', true);
        $alimentacion = get_post_meta($post_id, 'alimentacion', true);
        $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
        $comentarios_habilitados = get_post_meta($post_id, 'comentarios_habilitados', true);
        
        // Formatear taxonomías
        $ubicacion_data = !empty($ubicaciones) && !is_wp_error($ubicaciones) ? array(
            'id' => $ubicaciones[0]->term_id,
            'name' => $ubicaciones[0]->name,
            'slug' => $ubicaciones[0]->slug
        ) : null;
        
        $cultivo_data = !empty($cultivos) && !is_wp_error($cultivos) ? array(
            'id' => $cultivos[0]->term_id,
            'name' => $cultivos[0]->name,
            'slug' => $cultivos[0]->slug
        ) : null;
        
        $tipo_puesto_data = !empty($tipos_puesto) && !is_wp_error($tipos_puesto) ? array(
            'id' => $tipos_puesto[0]->term_id,
            'name' => $tipos_puesto[0]->name,
            'slug' => $tipos_puesto[0]->slug
        ) : null;
        
        $empresa_data = !empty($empresas) && !is_wp_error($empresas) ? array(
            'id' => $empresas[0]->term_id,
            'name' => $empresas[0]->name,
            'slug' => $empresas[0]->slug
        ) : null;
        
        // Obtener imagen destacada
        $featured_media_id = get_post_thumbnail_id($post_id);
        $featured_media_url = $featured_media_id ? wp_get_attachment_image_url($featured_media_id, 'full') : null;
        
        // Construir respuesta
        $post_data = array(
            'id' => $post_id,
            'title' => array('rendered' => $post->post_title),
            'content' => array('rendered' => $post->post_content),
            'excerpt' => array('rendered' => $post->post_excerpt),
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'status' => $post->post_status,
            'post_status' => $post->post_status,
            'link' => get_permalink($post_id),
            'featured_media' => $featured_media_id,
            'featured_media_url' => $featured_media_url,
            'gallery_ids' => is_array($gallery_ids) ? $gallery_ids : array(),
            'ubicacion' => $ubicacion_data,
            'cultivo' => $cultivo_data,
            'tipo_puesto' => $tipo_puesto_data,
            'empresa' => $empresa_data,
            'salario_min' => $salario_min ? intval($salario_min) : 0,
            'salario_max' => $salario_max ? intval($salario_max) : 0,
            'vacantes' => $vacantes ? intval($vacantes) : 1,
            'alojamiento' => ($alojamiento === '1' || $alojamiento === 1 || $alojamiento === true),
            'transporte' => ($transporte === '1' || $transporte === 1 || $transporte === true),
            'alimentacion' => ($alimentacion === '1' || $alimentacion === 1 || $alimentacion === true),
            'comentarios_habilitados' => ($comentarios_habilitados !== '0' && $comentarios_habilitados !== 0 && $comentarios_habilitados !== false),
        );
        
        return new WP_REST_Response($post_data, 200);
    }
}

// ==========================================
// 4. OBTENER TRABAJOS DE UNA EMPRESA
// ==========================================
if (!function_exists('agrochamba_get_company_jobs')) {
    function agrochamba_get_company_jobs($request) {
        $company_name = sanitize_text_field($request->get_param('company_name'));

        if (empty($company_name)) {
            return new WP_Error('rest_invalid_param', 'Nombre de empresa requerido.', array('status' => 400));
        }

        $empresa_term = get_term_by('name', $company_name, 'empresa');
        
        if (!$empresa_term) {
            return new WP_REST_Response(array(
                'data' => array(),
                'pagination' => array(
                    'total' => 0,
                    'total_pages' => 0,
                    'current_page' => 1,
                    'per_page' => 20,
                    'has_next_page' => false,
                    'has_prev_page' => false
                )
            ), 200);
        }

        // Parámetros de paginación
        $page = max(1, intval($request->get_param('page')) ?: 1);
        $per_page = min(100, max(1, intval($request->get_param('per_page')) ?: 20));

        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'tax_query' => array(
                array(
                    'taxonomy' => 'empresa',
                    'field' => 'term_id',
                    'terms' => $empresa_term->term_id,
                ),
            ),
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $query = new WP_Query($args);
        $formatted_jobs = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $job = get_post(get_the_ID());
            $formatted_jobs[] = array(
                'id' => $job->ID,
                'title' => array('rendered' => $job->post_title),
                'content' => array('rendered' => $job->post_content),
                'excerpt' => array('rendered' => $job->post_excerpt),
                'date' => $job->post_date,
                'link' => get_permalink($job->ID),
                'featured_media' => get_post_thumbnail_id($job->ID),
            );
        }
            wp_reset_postdata();
        }

        // Información de paginación
        $total = $query->found_posts;
        $total_pages = $query->max_num_pages;

        return new WP_REST_Response(array(
            'data' => $formatted_jobs,
            'pagination' => array(
                'total' => $total,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page,
                'has_next_page' => $page < $total_pages,
                'has_prev_page' => $page > 1
            )
        ), 200);
    }
}

// ==========================================
// 5. PERFIL COMPLETO DE EMPRESA CON TRABAJOS
// ==========================================
if (!function_exists('agrochamba_get_company_profile_with_jobs')) {
    function agrochamba_get_company_profile_with_jobs($request) {
        $company_name = sanitize_text_field($request->get_param('company_name'));

        if (empty($company_name)) {
            return new WP_Error('rest_invalid_param', 'Nombre de empresa requerido.', array('status' => 400));
        }

        // Buscar usuario por display_name
        $users = get_users(array(
            'meta_key' => 'display_name',
            'meta_value' => $company_name,
            'role__in' => array('employer', 'administrator'),
            'number' => 1
        ));

        if (empty($users)) {
            $user = get_user_by('login', $company_name);
            if (!$user) {
                $user = get_user_by('slug', sanitize_title($company_name));
            }
        } else {
            $user = $users[0];
        }

        if (!$user) {
            return new WP_Error('company_not_found', 'Empresa no encontrada.', array('status' => 404));
        }

        // Obtener información de la empresa
        $profile_photo_id = get_user_meta($user->ID, 'profile_photo_id', true);
        $company_description = get_user_meta($user->ID, 'company_description', true);
        $company_address = get_user_meta($user->ID, 'company_address', true);
        $company_phone = get_user_meta($user->ID, 'company_phone', true);
        $company_website = get_user_meta($user->ID, 'company_website', true);
        $company_facebook = get_user_meta($user->ID, 'company_facebook', true);
        $company_instagram = get_user_meta($user->ID, 'company_instagram', true);
        $company_linkedin = get_user_meta($user->ID, 'company_linkedin', true);
        $company_twitter = get_user_meta($user->ID, 'company_twitter', true);

        $profile_photo_url = null;
        if ($profile_photo_id) {
            $profile_photo_url = wp_get_attachment_image_url($profile_photo_id, 'full');
        }

        // Obtener trabajos activos de la empresa
        $empresa_term = get_term_by('name', $company_name, 'empresa');
        $jobs = array();

        // Parámetros de paginación
        $page = max(1, intval($request->get_param('page')) ?: 1);
        $per_page = min(100, max(1, intval($request->get_param('per_page')) ?: 20));

        if ($empresa_term) {
            $args = array(
                'post_type' => 'trabajo',
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'empresa',
                        'field' => 'term_id',
                        'terms' => $empresa_term->term_id,
                    ),
                ),
                'orderby' => 'date',
                'order' => 'DESC',
            );

            // Intentar obtener del caché (solo para listados públicos)
            if (function_exists('agrochamba_get_cached_jobs_list') && $page === 1) {
                $cache_key_suffix = '_company_' . $empresa_term->term_id;
                $cached = agrochamba_get_cached_jobs_list($args, $cache_key_suffix);
                if ($cached !== false && isset($cached['jobs'])) {
                    $query = new WP_Query($args);
                    $query->posts = $cached['jobs'];
                    $query->found_posts = $cached['total'];
                    $query->max_num_pages = $cached['total_pages'];
                } else {
                    $query = new WP_Query($args);
                }
            } else {
                $query = new WP_Query($args);
            }

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $job = get_post(get_the_ID());
                $featured_image_id = get_post_thumbnail_id($job->ID);
                $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'medium') : null;

                $ubicaciones = wp_get_post_terms($job->ID, 'ubicacion', array('fields' => 'names'));
                $cultivos = wp_get_post_terms($job->ID, 'cultivo', array('fields' => 'names'));
                $tipos_puesto = wp_get_post_terms($job->ID, 'tipo_puesto', array('fields' => 'names'));

                $salario_min = get_post_meta($job->ID, 'salario_min', true);
                $salario_max = get_post_meta($job->ID, 'salario_max', true);

                $jobs[] = array(
                    'id' => $job->ID,
                    'title' => array('rendered' => $job->post_title),
                    'excerpt' => array('rendered' => $job->post_excerpt),
                    'date' => $job->post_date,
                    'link' => get_permalink($job->ID),
                    'featured_image_url' => $featured_image_url,
                    'ubicacion' => !empty($ubicaciones) ? $ubicaciones[0] : null,
                    'cultivo' => !empty($cultivos) ? $cultivos[0] : null,
                    'tipo_puesto' => !empty($tipos_puesto) ? $tipos_puesto[0] : null,
                    'salario_min' => $salario_min,
                    'salario_max' => $salario_max,
                );
                }
                wp_reset_postdata();
            }
        }

        // Información de paginación
        $total = isset($query) && $query->found_posts ? $query->found_posts : 0;
        $total_pages = isset($query) && $query->max_num_pages ? $query->max_num_pages : 0;

        return new WP_REST_Response(array(
            'company' => array(
                'user_id' => $user->ID,
                'company_name' => $user->display_name,
                'profile_photo_url' => $profile_photo_url,
                'description' => $company_description ?: '',
                'address' => $company_address ?: '',
                'phone' => $company_phone ?: '',
                'website' => $company_website ?: '',
                'facebook' => $company_facebook ?: '',
                'instagram' => $company_instagram ?: '',
                'linkedin' => $company_linkedin ?: '',
                'twitter' => $company_twitter ?: '',
                'email' => $user->user_email
            ),
            'jobs' => $jobs,
            'jobs_count' => count($jobs),
            'pagination' => array(
                'total' => $total,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page,
                'has_next_page' => $page < $total_pages,
                'has_prev_page' => $page > 1
            )
        ), 200);
    }
}

// ==========================================
// FUNCIONES DE MODERACIÓN (SOLO ADMINS)
// ==========================================

// Obtener trabajos pendientes de moderación
if (!function_exists('agrochamba_get_pending_jobs')) {
    function agrochamba_get_pending_jobs($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión.', array('status' => 401));
        }

        $user = wp_get_current_user();
        if (!in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo los administradores pueden ver trabajos pendientes.', array('status' => 403));
        }

        // Parámetros de paginación
        $page = max(1, intval($request->get_param('page')) ?: 1);
        $per_page = min(100, max(1, intval($request->get_param('per_page')) ?: 20));

        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'pending',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $query = new WP_Query($args);
        $pending_jobs = array();

        if ($query->have_posts()) {
            $controller = new WP_REST_Posts_Controller('trabajo');
            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post(get_the_ID());
                $data = $controller->prepare_item_for_response($post, $request);
                $post_data = $data->get_data();
                
                // Agregar información adicional
                $author = get_userdata($post->post_author);
                $post_data['author_name'] = $author ? $author->display_name : '';
                $post_data['author_email'] = $author ? $author->user_email : '';
                
                // Agregar taxonomías
                $ubicaciones = wp_get_post_terms($post->ID, 'ubicacion', array('fields' => 'names'));
                $cultivos = wp_get_post_terms($post->ID, 'cultivo', array('fields' => 'names'));
                $empresas = wp_get_post_terms($post->ID, 'empresa', array('fields' => 'names'));
                $tipos_puesto = wp_get_post_terms($post->ID, 'tipo_puesto', array('fields' => 'names'));
                
                $post_data['ubicacion'] = !empty($ubicaciones) ? $ubicaciones[0] : '';
                $post_data['cultivo'] = !empty($cultivos) ? $cultivos[0] : '';
                $post_data['empresa'] = !empty($empresas) ? $empresas[0] : '';
                $post_data['tipo_puesto'] = !empty($tipos_puesto) ? $tipos_puesto[0] : '';
                
                // Agregar meta fields
                $post_data['salario_min'] = get_post_meta($post->ID, 'salario_min', true);
                $post_data['salario_max'] = get_post_meta($post->ID, 'salario_max', true);
                $post_data['vacantes'] = get_post_meta($post->ID, 'vacantes', true);
                
                $pending_jobs[] = $post_data;
            }
            wp_reset_postdata();
        }

        // Información de paginación
        $total = $query->found_posts;
        $total_pages = $query->max_num_pages;

        return new WP_REST_Response(array(
            'data' => $pending_jobs,
            'pagination' => array(
                'total' => $total,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page,
                'has_next_page' => $page < $total_pages,
                'has_prev_page' => $page > 1
            )
        ), 200);
    }
}

// Aprobar un trabajo
if (!function_exists('agrochamba_approve_job')) {
    function agrochamba_approve_job($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión.', array('status' => 401));
        }

        $user = wp_get_current_user();
        if (!in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo los administradores pueden aprobar trabajos.', array('status' => 403));
        }

        $post_id = intval($request->get_param('id'));
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('rest_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        // Cambiar estado a publicado
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish'
        ));

        if (is_wp_error($updated)) {
            return new WP_Error('rest_update_error', 'Error al aprobar el trabajo: ' . $updated->get_error_message(), array('status' => 500));
        }

        // Intentar publicar en Facebook si está habilitado
        if (function_exists('agrochamba_post_to_facebook')) {
            $job_data = array(
                'title' => $post->post_title,
                'content' => $post->post_content,
                'featured_media' => get_post_thumbnail_id($post_id),
            );
            agrochamba_post_to_facebook($post_id, $job_data);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Trabajo aprobado y publicado correctamente.',
            'post_id' => $post_id
        ), 200);
    }
}

// Rechazar un trabajo
if (!function_exists('agrochamba_reject_job')) {
    function agrochamba_reject_job($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión.', array('status' => 401));
        }

        $user = wp_get_current_user();
        if (!in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo los administradores pueden rechazar trabajos.', array('status' => 403));
        }

        $post_id = intval($request->get_param('id'));
        $params = $request->get_json_params();
        $rejection_reason = isset($params['reason']) ? sanitize_text_field($params['reason']) : '';

        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('rest_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        // Cambiar estado a rechazado (draft para que el autor pueda verlo y editarlo)
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft'
        ));

        if (is_wp_error($updated)) {
            return new WP_Error('rest_update_error', 'Error al rechazar el trabajo: ' . $updated->get_error_message(), array('status' => 500));
        }

        // Guardar razón de rechazo en meta
        if (!empty($rejection_reason)) {
            update_post_meta($post_id, 'rejection_reason', $rejection_reason);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Trabajo rechazado correctamente.',
            'post_id' => $post_id
        ), 200);
    }
}

// ==========================================
// REGISTRAR ENDPOINTS
// ==========================================
add_action('rest_api_init', function () {
    $routes = rest_get_server()->get_routes();

    // Crear trabajo
    if (!isset($routes['/agrochamba/v1/jobs'])) {
        register_rest_route('agrochamba/v1', '/jobs', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_create_job',
            'permission_callback' => function () {
                if (!is_user_logged_in()) {
                    return new WP_Error('rest_forbidden', 'Debes iniciar sesión para publicar.', array('status' => 401));
                }
                $user = wp_get_current_user();
                $allowed_roles = array('employer', 'administrator');
                if (array_intersect($allowed_roles, $user->roles)) {
                    return true;
                }
                return new WP_Error('rest_forbidden_role', 'No tienes permiso para publicar.', array('status' => 403));
            }
        ));
    }

    // Obtener un trabajo individual
    if (!isset($routes['/agrochamba/v1/jobs/(?P<id>\d+)'])) {
        register_rest_route('agrochamba/v1', '/jobs/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => 'agrochamba_get_single_job',
                'permission_callback' => function () {
                    if (!is_user_logged_in()) {
                        return false;
                    }
                    $user = wp_get_current_user();
                    return in_array('employer', $user->roles) || in_array('administrator', $user->roles);
                },
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
            array(
                'methods' => 'PUT',
                'callback' => 'agrochamba_update_job',
                'permission_callback' => function () {
                    if (!is_user_logged_in()) {
                        return false;
                    }
                    $user = wp_get_current_user();
                    return in_array('employer', $user->roles) || in_array('administrator', $user->roles);
                },
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
        ));
    }

    // Trabajos del usuario actual
    if (!isset($routes['/agrochamba/v1/me/jobs'])) {
        register_rest_route('agrochamba/v1', '/me/jobs', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_current_user_jobs',
            'permission_callback' => function () {
                if (!is_user_logged_in()) {
                    return false;
                }
                $user = wp_get_current_user();
                return in_array('employer', $user->roles) || in_array('administrator', $user->roles);
            },
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                ),
            ),
        ));
    }

    // Trabajos de una empresa
    if (!isset($routes['/agrochamba/v1/companies/(?P<company_name>[^/]+)/jobs'])) {
        register_rest_route('agrochamba/v1', '/companies/(?P<company_name>[^/]+)/jobs', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_company_jobs',
            'permission_callback' => '__return_true',
            'args' => array(
                'company_name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return !empty($param);
                    }
                ),
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                ),
            ),
        ));
    }

    // Perfil completo de empresa con trabajos
    if (!isset($routes['/agrochamba/v1/companies/(?P<company_name>[^/]+)/profile-with-jobs'])) {
        register_rest_route('agrochamba/v1', '/companies/(?P<company_name>[^/]+)/profile-with-jobs', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_company_profile_with_jobs',
            'permission_callback' => '__return_true',
            'args' => array(
                'company_name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return !empty($param);
                    }
                ),
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                ),
            ),
        ));
    }

    // ==========================================
    // ENDPOINTS DE MODERACIÓN (SOLO ADMINS)
    // ==========================================
    
    // Obtener trabajos pendientes de moderación
    if (!isset($routes['/agrochamba/v1/admin/pending-jobs'])) {
        register_rest_route('agrochamba/v1', '/admin/pending-jobs', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_pending_jobs',
            'permission_callback' => function () {
                if (!is_user_logged_in()) {
                    return false;
                }
                $user = wp_get_current_user();
                return in_array('administrator', $user->roles);
            },
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                ),
            ),
        ));
    }

    // Aprobar un trabajo
    if (!isset($routes['/agrochamba/v1/admin/jobs/(?P<id>\d+)/approve'])) {
        register_rest_route('agrochamba/v1', '/admin/jobs/(?P<id>\d+)/approve', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_approve_job',
            'permission_callback' => function () {
                if (!is_user_logged_in()) {
                    return false;
                }
                $user = wp_get_current_user();
                return in_array('administrator', $user->roles);
            },
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    // Rechazar un trabajo
    if (!isset($routes['/agrochamba/v1/admin/jobs/(?P<id>\d+)/reject'])) {
        register_rest_route('agrochamba/v1', '/admin/jobs/(?P<id>\d+)/reject', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_reject_job',
            'permission_callback' => function () {
                if (!is_user_logged_in()) {
                    return false;
                }
                $user = wp_get_current_user();
                return in_array('administrator', $user->roles);
            },
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }
}, 20);

