<?php
/**
 * Controlador de Trabajos
 *
 * Maneja la creación, actualización y consulta de trabajos
 *
 * @package AgroChamba
 * @subpackage API\Jobs
 * @since 2.0.0
 */

namespace AgroChamba\API\Jobs;

use WP_Error;
use WP_REST_Response;
use WP_REST_Request;
use WP_Query;

class JobsController {

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'agrochamba/v1';

    /**
     * Inicializar el controlador
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'), 20);
    }

    /**
     * Registrar rutas de la API
     */
    public static function register_routes() {
        $routes = rest_get_server()->get_routes();

        // Crear trabajo
        if (!isset($routes['/' . self::API_NAMESPACE . '/jobs'])) {
            register_rest_route(self::API_NAMESPACE, '/jobs', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'create_job'),
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
            ));
        }

        // Actualizar trabajo
        if (!isset($routes['/' . self::API_NAMESPACE . '/jobs/(?P<id>\d+)'])) {
            register_rest_route(self::API_NAMESPACE, '/jobs/(?P<id>\d+)', array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_job'),
                'permission_callback' => function() {
                    return is_user_logged_in();
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

        // Obtener trabajos del usuario actual
        if (!isset($routes['/' . self::API_NAMESPACE . '/me/jobs'])) {
            register_rest_route(self::API_NAMESPACE, '/me/jobs', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_current_user_jobs'),
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
            ));
        }

        // Obtener trabajos de una empresa
        if (!isset($routes['/' . self::API_NAMESPACE . '/companies/(?P<company_name>[^/]+)/jobs'])) {
            register_rest_route(self::API_NAMESPACE, '/companies/(?P<company_name>[^/]+)/jobs', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_company_jobs'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'company_name' => array(
                        'required' => true,
                    ),
                ),
            ));
        }

        // Obtener perfil de empresa con trabajos
        if (!isset($routes['/' . self::API_NAMESPACE . '/companies/(?P<company_name>[^/]+)/profile-with-jobs'])) {
            register_rest_route(self::API_NAMESPACE, '/companies/(?P<company_name>[^/]+)/profile-with-jobs', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_company_profile_with_jobs'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'company_name' => array(
                        'required' => true,
                    ),
                ),
            ));
        }

        // Obtener trabajos pendientes (admin)
        if (!isset($routes['/' . self::API_NAMESPACE . '/admin/pending-jobs'])) {
            register_rest_route(self::API_NAMESPACE, '/admin/pending-jobs', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_pending_jobs'),
                'permission_callback' => function() {
                    if (!is_user_logged_in()) {
                        return false;
                    }
                    $user = wp_get_current_user();
                    return in_array('administrator', $user->roles);
                },
            ));
        }

        // Aprobar trabajo (admin)
        if (!isset($routes['/' . self::API_NAMESPACE . '/admin/jobs/(?P<id>\d+)/approve'])) {
            register_rest_route(self::API_NAMESPACE, '/admin/jobs/(?P<id>\d+)/approve', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'approve_job'),
                'permission_callback' => function() {
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

        // Rechazar trabajo (admin)
        if (!isset($routes['/' . self::API_NAMESPACE . '/admin/jobs/(?P<id>\d+)/reject'])) {
            register_rest_route(self::API_NAMESPACE, '/admin/jobs/(?P<id>\d+)/reject', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'reject_job'),
                'permission_callback' => function() {
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
    }

    /**
     * Crear nuevo trabajo
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function create_job($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $params = $request->get_json_params();

        // Validaciones
        if (empty($params['title'])) {
            return new WP_Error(
                'rest_invalid_param',
                'El título es requerido.',
                array('status' => 400, 'code' => 'missing_title')
            );
        }

        if (strlen($params['title']) > 200) {
            return new WP_Error(
                'rest_invalid_param',
                'El título no puede exceder 200 caracteres.',
                array('status' => 400, 'code' => 'title_too_long')
            );
        }

        if (isset($params['content']) && strlen($params['content']) > 10000) {
            return new WP_Error(
                'rest_invalid_param',
                'El contenido no puede exceder 10000 caracteres.',
                array('status' => 400, 'code' => 'content_too_long')
            );
        }

        // Determinar estado del post
        $post_status = 'pending';
        if (in_array('administrator', $user->roles)) {
            $post_status = 'publish';
        }

        // Preparar datos del post
        $post_content = isset($params['content']) ? wp_kses_post($params['content']) : '';
        
        // Embebir imágenes de la galería en el contenido HTML para SEO y visibilidad
        if (!empty($params['gallery_ids']) && is_array($params['gallery_ids'])) {
            $gallery_ids = array_map('intval', $params['gallery_ids']);
            $images_html = '';
            $job_title = sanitize_text_field($params['title']);
            
            foreach ($gallery_ids as $img_id) {
                $img_url = wp_get_attachment_image_url($img_id, 'large');
                $img_full_url = wp_get_attachment_image_url($img_id, 'full');
                $img_alt = get_post_meta($img_id, '_wp_attachment_image_alt', true);
                
                // Si no hay alt text, usar el título del trabajo
                if (empty($img_alt)) {
                    $img_alt = $job_title;
                }
                
                if ($img_url) {
                    // Crear HTML de imagen con atributos SEO-friendly
                    $images_html .= '<figure class="wp-block-image size-large">' . "\n";
                    $images_html .= '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" class="wp-image-' . $img_id . ' aligncenter size-large" />' . "\n";
                    $images_html .= '</figure>' . "\n\n";
                }
            }
            
            // Agregar las imágenes al contenido (después del texto descriptivo)
            if (!empty($images_html)) {
                // Verificar si las imágenes ya están en el contenido para evitar duplicados
                $first_img_id = $gallery_ids[0];
                $img_url_check = wp_get_attachment_image_url($first_img_id, 'large');
                
                if ($img_url_check && strpos($post_content, $img_url_check) === false) {
                    $post_content .= "\n\n" . $images_html;
                }
            }
        }
        
        $post_data = array(
            'post_type'    => 'trabajo',
            'post_title'   => sanitize_text_field($params['title']),
            'post_content' => $post_content,
            'post_status'  => $post_status,
            'post_author'  => $user_id,
        );

        if (isset($params['excerpt'])) {
            $post_data['post_excerpt'] = sanitize_textarea_field($params['excerpt']);
        }

        // Si hay gallery_ids, establecer la primera como imagen destacada
        if (!empty($params['gallery_ids']) && is_array($params['gallery_ids'])) {
            $post_data['meta_input'] = array(
                '_thumbnail_id' => intval($params['gallery_ids'][0])
            );
        }

        // Crear el post
        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return new WP_Error(
                'rest_create_error',
                'Error al crear el trabajo: ' . $post_id->get_error_message(),
                array('status' => 500, 'code' => 'post_creation_failed')
            );
        }

        if (!$post_id || $post_id === 0) {
            return new WP_Error(
                'rest_create_error',
                'No se pudo crear el trabajo.',
                array('status' => 500, 'code' => 'post_creation_failed')
            );
        }

        // Vincular empresa automáticamente
        $empresa_term_id = null;
        $is_admin = in_array('administrator', $user->roles);
        $is_employer = in_array('employer', $user->roles);

        if ($is_employer && !$is_admin) {
            // Empresa normal: siempre usar su empresa automáticamente (ignorar empresa_id del request)
            $empresa_term_id_from_user = get_user_meta($user_id, 'empresa_term_id', true);
            
            if ($empresa_term_id_from_user) {
                $empresa_term_id = intval($empresa_term_id_from_user);
            } else {
                // Si no tiene empresa_term_id, buscar por display_name (razón social)
                $company_name = $user->display_name;
                if (!empty($company_name)) {
                    $empresa_term = get_term_by('name', $company_name, 'empresa');
                    if ($empresa_term) {
                        $empresa_term_id = $empresa_term->term_id;
                        // Guardar para futuras referencias
                        update_user_meta($user_id, 'empresa_term_id', $empresa_term_id);
                    }
                }
            }
        } elseif ($is_admin) {
            // Admin: puede especificar empresa_id o usar la automática
            if (isset($params['empresa_id']) && !empty($params['empresa_id'])) {
                $empresa_term_id = intval($params['empresa_id']);
            } else {
                // Si no especifica, usar su empresa si es employer también
                $empresa_term_id_from_user = get_user_meta($user_id, 'empresa_term_id', true);
                if ($empresa_term_id_from_user) {
                    $empresa_term_id = intval($empresa_term_id_from_user);
                }
            }
        } else {
            // Usuario normal (trabajador): debe especificar empresa_id
            if (isset($params['empresa_id']) && !empty($params['empresa_id'])) {
                $empresa_term_id = intval($params['empresa_id']);
            }
        }

        // Asignar la empresa al trabajo
        if ($empresa_term_id) {
            wp_set_post_terms($post_id, array($empresa_term_id), 'empresa', false);
        }

        // Asignar otras taxonomías
        if (isset($params['ubicacion_id']) && !empty($params['ubicacion_id'])) {
            wp_set_post_terms($post_id, array(intval($params['ubicacion_id'])), 'ubicacion', false);
        }

        if (isset($params['cultivo_id']) && !empty($params['cultivo_id'])) {
            wp_set_post_terms($post_id, array(intval($params['cultivo_id'])), 'cultivo', false);
        }

        if (isset($params['tipo_puesto_id']) && !empty($params['tipo_puesto_id'])) {
            wp_set_post_terms($post_id, array(intval($params['tipo_puesto_id'])), 'tipo_puesto', false);
        }

        // Guardar meta fields
        $meta_fields = array(
            'salario_min', 'salario_max', 'vacantes', 'fecha_inicio', 'fecha_fin',
            'duracion_dias', 'requisitos', 'beneficios', 'tipo_contrato', 'jornada',
            'contacto_whatsapp', 'contacto_email', 'google_maps_url', 'alojamiento',
            'transporte', 'alimentacion', 'estado', 'experiencia', 'genero',
            'edad_minima', 'edad_maxima'
        );

        foreach ($meta_fields as $field) {
            if (isset($params[$field])) {
                $value = $params[$field];
                if (in_array($field, array('alojamiento', 'transporte', 'alimentacion'))) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if ($field === 'google_maps_url') {
                    $value = esc_url_raw($value);
                }
                update_post_meta($post_id, $field, $value);
            }
        }

        // Imagen destacada
        if (isset($params['featured_media']) && !empty($params['featured_media'])) {
            set_post_thumbnail($post_id, intval($params['featured_media']));
        }

        // Galería de imágenes
        if (isset($params['gallery_ids']) && is_array($params['gallery_ids'])) {
            update_post_meta($post_id, 'gallery_ids', array_map('intval', $params['gallery_ids']));
        }

        // Publicar en Facebook SOLO si el usuario lo solicitó explícitamente
        $facebook_result = null;
        $publish_to_facebook = isset($params['publish_to_facebook']) && filter_var($params['publish_to_facebook'], FILTER_VALIDATE_BOOLEAN);
        
        // Log para debugging
        error_log('AgroChamba Facebook Debug - publish_to_facebook: ' . ($publish_to_facebook ? 'true' : 'false'));
        error_log('AgroChamba Facebook Debug - post_status: ' . $post_status);
        error_log('AgroChamba Facebook Debug - function exists: ' . (function_exists('agrochamba_post_to_facebook') ? 'yes' : 'no'));
        
        // Publicar en Facebook SOLO si el usuario lo solicitó explícitamente
        if ($publish_to_facebook && function_exists('agrochamba_post_to_facebook')) {
            error_log('AgroChamba Facebook Debug - Intentando publicar en Facebook...');
            
            // Preparar datos para Facebook incluyendo todas las imágenes
            $job_data_for_facebook = array_merge($params, array(
                'featured_media' => isset($params['featured_media']) ? $params['featured_media'] : get_post_thumbnail_id($post_id),
                'gallery_ids' => isset($params['gallery_ids']) && is_array($params['gallery_ids']) ? $params['gallery_ids'] : array(),
            ));
            
            $facebook_result = agrochamba_post_to_facebook($post_id, $job_data_for_facebook);
            
            if (is_wp_error($facebook_result)) {
                error_log('AgroChamba Facebook Error: ' . $facebook_result->get_error_message());
            } else {
                error_log('AgroChamba Facebook Success: Post ID ' . $post_id . ' publicado en Facebook');
            }
        } else {
            if (!$publish_to_facebook) {
                error_log('AgroChamba Facebook Debug - No se publicará en Facebook. El usuario no lo solicitó.');
            } else {
                error_log('AgroChamba Facebook Error: La función agrochamba_post_to_facebook no existe');
            }
        }

        $response_data = array(
            'success' => true,
            'message' => $post_status === 'pending'
                ? 'Trabajo creado y enviado para revisión.'
                : 'Trabajo creado correctamente.',
            'post_id' => $post_id,
            'status' => $post_status
        );

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

    /**
     * Actualizar trabajo existente
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function update_job($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $post_id = intval($request->get_param('id'));
        $params = $request->get_json_params();

        // Verificar que el trabajo existe
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('rest_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        // Verificar permisos
        if ($post->post_author != $user_id && !in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'No tienes permiso para actualizar este trabajo.', array('status' => 403));
        }

        // Preparar datos del post
        $post_data = array('ID' => $post_id);
        $post_content = isset($params['content']) ? wp_kses_post($params['content']) : $post->post_content;

        if (isset($params['title'])) {
            if (strlen($params['title']) > 200) {
                return new WP_Error('rest_invalid_param', 'El título no puede exceder 200 caracteres.', array('status' => 400));
            }
            $post_data['post_title'] = sanitize_text_field($params['title']);
        }

        if (isset($params['content'])) {
            if (strlen($params['content']) > 10000) {
                return new WP_Error('rest_invalid_param', 'El contenido no puede exceder 10000 caracteres.', array('status' => 400));
            }
            $post_content = wp_kses_post($params['content']);
        }

        // Embebir imágenes de la galería en el contenido HTML para SEO y visibilidad
        if (isset($params['gallery_ids']) && is_array($params['gallery_ids']) && !empty($params['gallery_ids'])) {
            $gallery_ids = array_map('intval', $params['gallery_ids']);
            $images_html = '';
            $job_title = isset($params['title']) ? sanitize_text_field($params['title']) : $post->post_title;
            
            // Remover imágenes existentes del contenido antes de agregar las nuevas
            // Esto evita duplicados cuando se actualiza
            foreach ($gallery_ids as $img_id) {
                $img_url = wp_get_attachment_image_url($img_id, 'large');
                if ($img_url) {
                    // Remover cualquier referencia previa a esta imagen
                    $post_content = preg_replace('/<figure[^>]*>.*?wp-image-' . $img_id . '.*?<\/figure>/s', '', $post_content);
                    $post_content = preg_replace('/<img[^>]*wp-image-' . $img_id . '[^>]*>/', '', $post_content);
                }
            }
            
            // Agregar todas las imágenes al contenido
            foreach ($gallery_ids as $img_id) {
                $img_url = wp_get_attachment_image_url($img_id, 'large');
                $img_alt = get_post_meta($img_id, '_wp_attachment_image_alt', true);
                
                // Si no hay alt text, usar el título del trabajo
                if (empty($img_alt)) {
                    $img_alt = $job_title;
                }
                
                if ($img_url) {
                    // Crear HTML de imagen con atributos SEO-friendly
                    $images_html .= '<figure class="wp-block-image size-large">' . "\n";
                    $images_html .= '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" class="wp-image-' . $img_id . ' aligncenter size-large" />' . "\n";
                    $images_html .= '</figure>' . "\n\n";
                }
            }
            
            // Agregar las imágenes al contenido (después del texto descriptivo)
            if (!empty($images_html)) {
                // Limpiar espacios múltiples y agregar imágenes
                $post_content = trim($post_content);
                $post_content .= "\n\n" . trim($images_html);
            }
        }
        
        $post_data['post_content'] = $post_content;

        if (isset($params['excerpt'])) {
            $post_data['post_excerpt'] = sanitize_textarea_field($params['excerpt']);
        }

        // Actualizar el post
        $updated = wp_update_post($post_data);

        if (is_wp_error($updated)) {
            return new WP_Error('rest_update_error', 'Error al actualizar el trabajo.', array('status' => 500));
        }

        // Actualizar taxonomías
        if (isset($params['ubicacion_id'])) {
            wp_set_post_terms($post_id, array(intval($params['ubicacion_id'])), 'ubicacion', false);
        }

        if (isset($params['cultivo_id'])) {
            wp_set_post_terms($post_id, array(intval($params['cultivo_id'])), 'cultivo', false);
        }

        if (isset($params['tipo_puesto_id'])) {
            wp_set_post_terms($post_id, array(intval($params['tipo_puesto_id'])), 'tipo_puesto', false);
        }

        // Actualizar meta fields
        $meta_fields = array(
            'salario_min', 'salario_max', 'vacantes', 'fecha_inicio', 'fecha_fin',
            'duracion_dias', 'requisitos', 'beneficios', 'tipo_contrato', 'jornada',
            'contacto_whatsapp', 'contacto_email', 'google_maps_url', 'alojamiento',
            'transporte', 'alimentacion', 'estado', 'experiencia', 'genero',
            'edad_minima', 'edad_maxima'
        );

        foreach ($meta_fields as $field) {
            if (isset($params[$field])) {
                $value = $params[$field];
                if (in_array($field, array('alojamiento', 'transporte', 'alimentacion'))) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if ($field === 'google_maps_url') {
                    $value = esc_url_raw($value);
                }
                update_post_meta($post_id, $field, $value);
            }
        }

        // Actualizar imagen destacada
        if (isset($params['featured_media'])) {
            set_post_thumbnail($post_id, intval($params['featured_media']));
        }

        // Actualizar galería
        if (isset($params['gallery_ids']) && is_array($params['gallery_ids'])) {
            update_post_meta($post_id, 'gallery_ids', array_map('intval', $params['gallery_ids']));
        }

        // Publicar en Facebook SOLO si el usuario lo solicitó explícitamente
        $facebook_result = null;
        $publish_to_facebook = isset($params['publish_to_facebook']) && filter_var($params['publish_to_facebook'], FILTER_VALIDATE_BOOLEAN);
        
        if ($publish_to_facebook && function_exists('agrochamba_post_to_facebook')) {
            // Preparar datos para Facebook incluyendo todas las imágenes
            $job_data_for_facebook = array_merge($params, array(
                'featured_media' => isset($params['featured_media']) ? $params['featured_media'] : get_post_thumbnail_id($post_id),
                'gallery_ids' => isset($params['gallery_ids']) && is_array($params['gallery_ids']) ? $params['gallery_ids'] : array(),
            ));
            $facebook_result = agrochamba_post_to_facebook($post_id, $job_data_for_facebook);
        }

        $response_data = array(
            'success' => true,
            'message' => 'Trabajo actualizado correctamente.',
            'post_id' => $post_id
        );

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

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Obtener trabajos del usuario actual
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_current_user_jobs($request) {
        $user_id = get_current_user_id();
        $page = $request->get_param('page') ? intval($request->get_param('page')) : 1;
        $per_page = $request->get_param('per_page') ? intval($request->get_param('per_page')) : 10;

        $args = array(
            'post_type'      => 'trabajo',
            'author'         => $user_id,
            'post_status'    => array('publish', 'pending'),
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query = new WP_Query($args);

        $jobs = array();
        foreach ($query->posts as $post) {
            $jobs[] = self::format_job_data($post);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'jobs' => $jobs,
            'total' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page
        ), 200);
    }

    /**
     * Obtener trabajos de una empresa
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_company_jobs($request) {
        $company_name = $request->get_param('company_name');
        $page = $request->get_param('page') ? intval($request->get_param('page')) : 1;
        $per_page = $request->get_param('per_page') ? intval($request->get_param('per_page')) : 10;

        $empresa_term = get_term_by('slug', sanitize_title($company_name), 'empresa');
        if (!$empresa_term) {
            $empresa_term = get_term_by('name', $company_name, 'empresa');
        }

        if (!$empresa_term) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Empresa no encontrada.',
                'jobs' => array(),
                'total' => 0
            ), 404);
        }

        $args = array(
            'post_type'      => 'trabajo',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'empresa',
                    'field'    => 'term_id',
                    'terms'    => $empresa_term->term_id,
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query = new WP_Query($args);

        $jobs = array();
        foreach ($query->posts as $post) {
            $jobs[] = self::format_job_data($post);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'jobs' => $jobs,
            'total' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page
        ), 200);
    }

    /**
     * Obtener perfil de empresa con trabajos
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_company_profile_with_jobs($request) {
        $company_name = $request->get_param('company_name');

        $empresa_term = get_term_by('slug', sanitize_title($company_name), 'empresa');
        if (!$empresa_term) {
            $empresa_term = get_term_by('name', $company_name, 'empresa');
        }

        if (!$empresa_term) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Empresa no encontrada.'
            ), 404);
        }

        // Obtener trabajos de la empresa
        $args = array(
            'post_type'      => 'trabajo',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'empresa',
                    'field'    => 'term_id',
                    'terms'    => $empresa_term->term_id,
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query = new WP_Query($args);

        $jobs = array();
        foreach ($query->posts as $post) {
            $jobs[] = self::format_job_data($post);
        }

        // Obtener información de la empresa
        $company_data = array(
            'id' => $empresa_term->term_id,
            'name' => $empresa_term->name,
            'slug' => $empresa_term->slug,
            'description' => $empresa_term->description,
            'jobs_count' => $query->found_posts,
        );

        return new WP_REST_Response(array(
            'success' => true,
            'company' => $company_data,
            'jobs' => $jobs
        ), 200);
    }

    /**
     * Obtener trabajos pendientes (admin)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_pending_jobs($request) {
        $args = array(
            'post_type'      => 'trabajo',
            'post_status'    => 'pending',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query = new WP_Query($args);

        $jobs = array();
        foreach ($query->posts as $post) {
            $jobs[] = self::format_job_data($post);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'jobs' => $jobs,
            'total' => $query->found_posts
        ), 200);
    }

    /**
     * Aprobar trabajo (admin)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function approve_job($request) {
        $post_id = intval($request->get_param('id'));

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('rest_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish'
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Trabajo aprobado y publicado.'
        ), 200);
    }

    /**
     * Rechazar trabajo (admin)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function reject_job($request) {
        $post_id = intval($request->get_param('id'));

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('rest_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        wp_delete_post($post_id, false); // Mover a papelera

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Trabajo rechazado y movido a papelera.'
        ), 200);
    }

    /**
     * Formatear datos de un trabajo
     *
     * @param \WP_Post $post
     * @return array
     */
    private static function format_job_data($post) {
        $data = array(
            'id' => $post->ID,
            'title' => array('rendered' => $post->post_title),
            'content' => array('rendered' => $post->post_content),
            'excerpt' => array('rendered' => $post->post_excerpt),
            'status' => $post->post_status,
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'link' => get_permalink($post->ID),
            'author_id' => $post->post_author,
        );

        // Taxonomías - formatear como objetos con id, name, slug
        $empresas = wp_get_post_terms($post->ID, 'empresa');
        if (!empty($empresas) && !is_wp_error($empresas)) {
            $empresa = $empresas[0];
            $data['empresa'] = array(
                'id' => $empresa->term_id,
                'name' => $empresa->name,
                'slug' => $empresa->slug
            );
        } else {
            $data['empresa'] = null;
        }

        $ubicaciones = wp_get_post_terms($post->ID, 'ubicacion');
        if (!empty($ubicaciones) && !is_wp_error($ubicaciones)) {
            $ubicacion = $ubicaciones[0];
            $data['ubicacion'] = array(
                'id' => $ubicacion->term_id,
                'name' => $ubicacion->name,
                'slug' => $ubicacion->slug
            );
        } else {
            $data['ubicacion'] = null;
        }

        $cultivos = wp_get_post_terms($post->ID, 'cultivo');
        if (!empty($cultivos) && !is_wp_error($cultivos)) {
            $cultivo = $cultivos[0];
            $data['cultivo'] = array(
                'id' => $cultivo->term_id,
                'name' => $cultivo->name,
                'slug' => $cultivo->slug
            );
        } else {
            $data['cultivo'] = null;
        }

        $tipos_puesto = wp_get_post_terms($post->ID, 'tipo_puesto');
        if (!empty($tipos_puesto) && !is_wp_error($tipos_puesto)) {
            $tipo_puesto = $tipos_puesto[0];
            $data['tipo_puesto'] = array(
                'id' => $tipo_puesto->term_id,
                'name' => $tipo_puesto->name,
                'slug' => $tipo_puesto->slug
            );
        } else {
            $data['tipo_puesto'] = null;
        }

        // Meta fields
        $meta_fields = array(
            'salario_min', 'salario_max', 'vacantes', 'fecha_inicio', 'fecha_fin',
            'duracion_dias', 'requisitos', 'beneficios', 'tipo_contrato', 'jornada',
            'contacto_whatsapp', 'contacto_email', 'google_maps_url', 'estado', 
            'experiencia', 'genero', 'edad_minima', 'edad_maxima'
        );

        foreach ($meta_fields as $field) {
            $data[$field] = get_post_meta($post->ID, $field, true);
        }

        // Campos booleanos - convertir explícitamente a boolean
        $boolean_fields = array('alojamiento', 'transporte', 'alimentacion');
        foreach ($boolean_fields as $field) {
            $value = get_post_meta($post->ID, $field, true);
            // Convertir a boolean: '1', 'true', 1, true -> true, todo lo demás -> false
            if ($value === '1' || $value === 'true' || $value === 1 || $value === true || $value === 'yes') {
                $data[$field] = true;
            } elseif ($value === '0' || $value === 'false' || $value === 0 || $value === false || $value === 'no' || $value === '') {
                $data[$field] = false;
            } else {
                $data[$field] = (bool) $value;
            }
        }

        // Imagen destacada
        $featured_media_id = get_post_thumbnail_id($post->ID);
        $data['featured_media'] = $featured_media_id ? $featured_media_id : null;
        $featured_media_url = get_the_post_thumbnail_url($post->ID, 'full');
        // Asegurar que sea string o null, nunca boolean (get_the_post_thumbnail_url puede devolver false)
        $data['featured_media_url'] = ($featured_media_url && $featured_media_url !== false) ? (string) $featured_media_url : null;

        // Galería
        $gallery_ids = get_post_meta($post->ID, 'gallery_ids', true);
        $data['gallery_ids'] = !empty($gallery_ids) ? $gallery_ids : array();

        // Facebook post ID (para saber si ya fue publicado en Facebook)
        $facebook_post_id = get_post_meta($post->ID, 'facebook_post_id', true);
        $data['facebook_post_id'] = !empty($facebook_post_id) ? $facebook_post_id : null;

        return $data;
    }
}
