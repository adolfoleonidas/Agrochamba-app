<?php
/**
 * =============================================================
 * MÓDULO 28: MODERACIÓN DE TRABAJOS PARA ADMINISTRADORES
 * =============================================================
 * 
 * Sistema completo de moderación con CRUD para administradores:
 * - Listar todos los trabajos (con paginación y filtros)
 * - Obtener trabajos pendientes de moderación
 * - Vista previa de trabajos
 * - Aprobar trabajos
 * - Rechazar trabajos (con razón)
 * - Editar trabajos
 * - Eliminar trabajos
 * 
 * @package AgroChamba
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. REGISTRAR ENDPOINTS DE MODERACIÓN
// ==========================================

if (!function_exists('agrochamba_register_moderation_endpoints')) {
    function agrochamba_register_moderation_endpoints() {
        
        // Listar TODOS los trabajos con paginación (admin only)
        register_rest_route('agrochamba/v1', '/admin/jobs', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_admin_list_all_jobs',
            'permission_callback' => 'agrochamba_is_admin_or_moderator',
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ),
                'status' => array(
                    'default' => 'all',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'search' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'orderby' => array(
                    'default' => 'date',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'order' => array(
                    'default' => 'DESC',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Obtener un trabajo específico para preview (admin only)
        register_rest_route('agrochamba/v1', '/admin/jobs/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_admin_get_job',
            'permission_callback' => 'agrochamba_is_admin_or_moderator',
        ));
        
        // Aprobar un trabajo pendiente
        register_rest_route('agrochamba/v1', '/admin/jobs/(?P<id>\d+)/approve', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_admin_approve_job',
            'permission_callback' => 'agrochamba_is_admin_or_moderator',
        ));
        
        // Rechazar un trabajo pendiente
        register_rest_route('agrochamba/v1', '/admin/jobs/(?P<id>\d+)/reject', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_admin_reject_job',
            'permission_callback' => 'agrochamba_is_admin_or_moderator',
            'args' => array(
                'reason' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));
        
        // Editar un trabajo (CRUD - Update)
        register_rest_route('agrochamba/v1', '/admin/jobs/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => 'agrochamba_admin_update_job',
            'permission_callback' => 'agrochamba_is_admin_or_moderator',
        ));
        
        // Eliminar un trabajo (CRUD - Delete)
        register_rest_route('agrochamba/v1', '/admin/jobs/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => 'agrochamba_admin_delete_job',
            'permission_callback' => 'agrochamba_is_admin_or_moderator',
        ));
        
        // Obtener estadísticas de moderación
        register_rest_route('agrochamba/v1', '/admin/moderation/stats', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_admin_get_moderation_stats',
            'permission_callback' => 'agrochamba_is_admin_or_moderator',
        ));
        
        // Acción masiva (aprobar/rechazar múltiples)
        register_rest_route('agrochamba/v1', '/admin/jobs/bulk-action', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_admin_bulk_action',
            'permission_callback' => 'agrochamba_is_admin_or_moderator',
        ));
    }
    add_action('rest_api_init', 'agrochamba_register_moderation_endpoints');
}

// ==========================================
// 2. VERIFICAR PERMISOS DE ADMIN/MODERADOR
// ==========================================

if (!function_exists('agrochamba_is_admin_or_moderator')) {
    function agrochamba_is_admin_or_moderator() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        $allowed_roles = array('administrator', 'editor', 'moderator');
        
        foreach ($allowed_roles as $role) {
            if (in_array($role, $user->roles)) {
                return true;
            }
        }
        
        return false;
    }
}

// ==========================================
// 3. LISTAR TODOS LOS TRABAJOS
// ==========================================

if (!function_exists('agrochamba_admin_list_all_jobs')) {
    function agrochamba_admin_list_all_jobs($request) {
        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100); // Max 100
        $status = $request->get_param('status');
        $search = $request->get_param('search');
        $orderby = $request->get_param('orderby');
        $order = strtoupper($request->get_param('order')) === 'ASC' ? 'ASC' : 'DESC';
        
        // Configurar estados
        $post_status = array('publish', 'pending', 'draft', 'private');
        if ($status !== 'all' && in_array($status, array('publish', 'pending', 'draft', 'private', 'trash'))) {
            $post_status = $status;
        }
        
        // Query args
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => $post_status,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        );
        
        // Búsqueda
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new WP_Query($args);
        $jobs = array();
        
        foreach ($query->posts as $post) {
            $jobs[] = agrochamba_format_job_for_admin($post);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $jobs,
            'pagination' => array(
                'page' => $page,
                'per_page' => $per_page,
                'total' => $query->found_posts,
                'total_pages' => $query->max_num_pages,
            ),
        ), 200);
    }
}

// ==========================================
// 4. OBTENER UN TRABAJO ESPECÍFICO (PREVIEW)
// ==========================================

if (!function_exists('agrochamba_admin_get_job')) {
    function agrochamba_admin_get_job($request) {
        $post_id = intval($request->get_param('id'));
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('not_found', 'Trabajo no encontrado.', array('status' => 404));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => agrochamba_format_job_for_admin($post, true), // true = incluir todos los detalles
        ), 200);
    }
}

// ==========================================
// 5. APROBAR UN TRABAJO
// ==========================================

if (!function_exists('agrochamba_admin_approve_job')) {
    function agrochamba_admin_approve_job($request) {
        $post_id = intval($request->get_param('id'));
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('not_found', 'Trabajo no encontrado.', array('status' => 404));
        }
        
        // Actualizar estado a publicado
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish',
        ));
        
        if (is_wp_error($result)) {
            return new WP_Error('update_failed', 'Error al aprobar el trabajo.', array('status' => 500));
        }
        
        // Registrar quién aprobó y cuándo
        $current_user = wp_get_current_user();
        update_post_meta($post_id, '_moderation_approved_by', $current_user->ID);
        update_post_meta($post_id, '_moderation_approved_date', current_time('mysql'));
        update_post_meta($post_id, '_moderation_status', 'approved');
        
        // Notificar al autor (opcional)
        agrochamba_notify_author_moderation($post_id, 'approved');
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Trabajo aprobado y publicado exitosamente.',
            'data' => agrochamba_format_job_for_admin(get_post($post_id)),
        ), 200);
    }
}

// ==========================================
// 6. RECHAZAR UN TRABAJO
// ==========================================

if (!function_exists('agrochamba_admin_reject_job')) {
    function agrochamba_admin_reject_job($request) {
        $post_id = intval($request->get_param('id'));
        $reason = $request->get_param('reason');
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('not_found', 'Trabajo no encontrado.', array('status' => 404));
        }
        
        if (empty($reason)) {
            return new WP_Error('missing_reason', 'Debes proporcionar una razón para el rechazo.', array('status' => 400));
        }
        
        // Actualizar estado a borrador (rechazado)
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft',
        ));
        
        if (is_wp_error($result)) {
            return new WP_Error('update_failed', 'Error al rechazar el trabajo.', array('status' => 500));
        }
        
        // Registrar rechazo
        $current_user = wp_get_current_user();
        update_post_meta($post_id, '_moderation_rejected_by', $current_user->ID);
        update_post_meta($post_id, '_moderation_rejected_date', current_time('mysql'));
        update_post_meta($post_id, '_moderation_rejection_reason', $reason);
        update_post_meta($post_id, '_moderation_status', 'rejected');
        
        // Notificar al autor
        agrochamba_notify_author_moderation($post_id, 'rejected', $reason);
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Trabajo rechazado.',
            'data' => agrochamba_format_job_for_admin(get_post($post_id)),
        ), 200);
    }
}

// ==========================================
// 7. ACTUALIZAR UN TRABAJO (CRUD UPDATE)
// ==========================================

if (!function_exists('agrochamba_admin_update_job')) {
    function agrochamba_admin_update_job($request) {
        $post_id = intval($request->get_param('id'));
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('not_found', 'Trabajo no encontrado.', array('status' => 404));
        }
        
        $params = $request->get_json_params();
        $update_data = array('ID' => $post_id);
        
        // Campos editables
        if (isset($params['title'])) {
            $update_data['post_title'] = sanitize_text_field($params['title']);
        }
        
        if (isset($params['content'])) {
            $update_data['post_content'] = wp_kses_post($params['content']);
        }
        
        if (isset($params['excerpt'])) {
            $update_data['post_excerpt'] = sanitize_textarea_field($params['excerpt']);
        }
        
        if (isset($params['status']) && in_array($params['status'], array('publish', 'pending', 'draft', 'private'))) {
            $update_data['post_status'] = $params['status'];
        }
        
        // Actualizar post
        $result = wp_update_post($update_data, true);
        
        if (is_wp_error($result)) {
            return new WP_Error('update_failed', $result->get_error_message(), array('status' => 500));
        }
        
        // Actualizar meta fields
        $meta_fields = array(
            'salario_min', 'salario_max', 'vacantes', 'fecha_inicio', 'fecha_fin',
            'duracion_dias', 'tipo_contrato', 'jornada', 'requisitos', 'beneficios',
            'experiencia', 'genero', 'edad_minima', 'edad_maxima', 'estado',
            'contacto_whatsapp', 'contacto_email', 'google_maps_url', 'empresa_id',
            'alojamiento', 'transporte', 'alimentacion'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($params[$field])) {
                $value = $params[$field];
                
                // Sanitizar según tipo
                if (in_array($field, array('salario_min', 'salario_max', 'vacantes', 'duracion_dias', 'edad_minima', 'edad_maxima', 'empresa_id'))) {
                    $value = intval($value);
                } elseif (in_array($field, array('alojamiento', 'transporte', 'alimentacion'))) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } else {
                    $value = sanitize_text_field($value);
                }
                
                update_post_meta($post_id, $field, $value);
            }
        }
        
        // Actualizar ubicación si se proporciona
        if (isset($params['ubicacion_completa']) && is_array($params['ubicacion_completa'])) {
            $ubicacion = $params['ubicacion_completa'];
            update_post_meta($post_id, '_ubicacion_completa', $ubicacion);
            
            if (isset($ubicacion['departamento'])) {
                // Actualizar taxonomía
                $term = get_term_by('name', $ubicacion['departamento'], 'ubicacion');
                if ($term) {
                    wp_set_post_terms($post_id, array($term->term_id), 'ubicacion');
                }
            }
            if (isset($ubicacion['provincia'])) {
                update_post_meta($post_id, '_ubicacion_provincia', $ubicacion['provincia']);
            }
            if (isset($ubicacion['distrito'])) {
                update_post_meta($post_id, '_ubicacion_distrito', $ubicacion['distrito']);
            }
            if (isset($ubicacion['direccion'])) {
                update_post_meta($post_id, '_ubicacion_direccion', $ubicacion['direccion']);
            }
        }
        
        // Registrar quién editó
        $current_user = wp_get_current_user();
        update_post_meta($post_id, '_last_edited_by_admin', $current_user->ID);
        update_post_meta($post_id, '_last_edited_by_admin_date', current_time('mysql'));
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Trabajo actualizado exitosamente.',
            'data' => agrochamba_format_job_for_admin(get_post($post_id), true),
        ), 200);
    }
}

// ==========================================
// 8. ELIMINAR UN TRABAJO (CRUD DELETE)
// ==========================================

if (!function_exists('agrochamba_admin_delete_job')) {
    function agrochamba_admin_delete_job($request) {
        $post_id = intval($request->get_param('id'));
        $force = $request->get_param('force') === 'true';
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'trabajo') {
            return new WP_Error('not_found', 'Trabajo no encontrado.', array('status' => 404));
        }
        
        // Guardar info antes de eliminar
        $job_title = $post->post_title;
        
        if ($force) {
            // Eliminar permanentemente
            $result = wp_delete_post($post_id, true);
        } else {
            // Mover a papelera
            $result = wp_trash_post($post_id);
        }
        
        if (!$result) {
            return new WP_Error('delete_failed', 'Error al eliminar el trabajo.', array('status' => 500));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => $force ? 'Trabajo eliminado permanentemente.' : 'Trabajo movido a la papelera.',
            'deleted_job' => array(
                'id' => $post_id,
                'title' => $job_title,
            ),
        ), 200);
    }
}

// ==========================================
// 9. ESTADÍSTICAS DE MODERACIÓN
// ==========================================

if (!function_exists('agrochamba_admin_get_moderation_stats')) {
    function agrochamba_admin_get_moderation_stats($request) {
        // Contar por estado
        $counts = wp_count_posts('trabajo');
        
        // Obtener los últimos moderados
        $recent_approved = get_posts(array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'meta_key' => '_moderation_approved_date',
            'orderby' => 'meta_value',
            'order' => 'DESC',
        ));
        
        $recent_rejected = get_posts(array(
            'post_type' => 'trabajo',
            'post_status' => 'draft',
            'posts_per_page' => 5,
            'meta_query' => array(
                array(
                    'key' => '_moderation_status',
                    'value' => 'rejected',
                ),
            ),
            'orderby' => 'modified',
            'order' => 'DESC',
        ));
        
        // Trabajos esperando más tiempo
        $oldest_pending = get_posts(array(
            'post_type' => 'trabajo',
            'post_status' => 'pending',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'ASC',
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'counts' => array(
                    'pending' => isset($counts->pending) ? $counts->pending : 0,
                    'publish' => isset($counts->publish) ? $counts->publish : 0,
                    'draft' => isset($counts->draft) ? $counts->draft : 0,
                    'trash' => isset($counts->trash) ? $counts->trash : 0,
                    'total' => array_sum((array) $counts) - (isset($counts->trash) ? $counts->trash : 0),
                ),
                'recent_approved' => array_map(function($p) {
                    return array(
                        'id' => $p->ID,
                        'title' => $p->post_title,
                        'approved_date' => get_post_meta($p->ID, '_moderation_approved_date', true),
                    );
                }, $recent_approved),
                'recent_rejected' => array_map(function($p) {
                    return array(
                        'id' => $p->ID,
                        'title' => $p->post_title,
                        'rejection_reason' => get_post_meta($p->ID, '_moderation_rejection_reason', true),
                    );
                }, $recent_rejected),
                'oldest_pending' => array_map(function($p) {
                    $created = strtotime($p->post_date);
                    $now = time();
                    $waiting_hours = round(($now - $created) / 3600);
                    
                    return array(
                        'id' => $p->ID,
                        'title' => $p->post_title,
                        'created_date' => $p->post_date,
                        'waiting_hours' => $waiting_hours,
                    );
                }, $oldest_pending),
            ),
        ), 200);
    }
}

// ==========================================
// 10. ACCIÓN MASIVA
// ==========================================

if (!function_exists('agrochamba_admin_bulk_action')) {
    function agrochamba_admin_bulk_action($request) {
        $params = $request->get_json_params();
        $action = isset($params['action']) ? $params['action'] : '';
        $job_ids = isset($params['job_ids']) ? array_map('intval', $params['job_ids']) : array();
        $reason = isset($params['reason']) ? sanitize_textarea_field($params['reason']) : '';
        
        if (empty($job_ids)) {
            return new WP_Error('no_jobs', 'No se especificaron trabajos.', array('status' => 400));
        }
        
        if (!in_array($action, array('approve', 'reject', 'delete', 'trash'))) {
            return new WP_Error('invalid_action', 'Acción no válida.', array('status' => 400));
        }
        
        $results = array(
            'success' => array(),
            'failed' => array(),
        );
        
        $current_user = wp_get_current_user();
        
        foreach ($job_ids as $job_id) {
            $post = get_post($job_id);
            
            if (!$post || $post->post_type !== 'trabajo') {
                $results['failed'][] = array('id' => $job_id, 'reason' => 'No encontrado');
                continue;
            }
            
            switch ($action) {
                case 'approve':
                    $update = wp_update_post(array('ID' => $job_id, 'post_status' => 'publish'));
                    if (!is_wp_error($update)) {
                        update_post_meta($job_id, '_moderation_approved_by', $current_user->ID);
                        update_post_meta($job_id, '_moderation_approved_date', current_time('mysql'));
                        update_post_meta($job_id, '_moderation_status', 'approved');
                        agrochamba_notify_author_moderation($job_id, 'approved');
                        $results['success'][] = $job_id;
                    } else {
                        $results['failed'][] = array('id' => $job_id, 'reason' => $update->get_error_message());
                    }
                    break;
                    
                case 'reject':
                    $update = wp_update_post(array('ID' => $job_id, 'post_status' => 'draft'));
                    if (!is_wp_error($update)) {
                        update_post_meta($job_id, '_moderation_rejected_by', $current_user->ID);
                        update_post_meta($job_id, '_moderation_rejected_date', current_time('mysql'));
                        update_post_meta($job_id, '_moderation_rejection_reason', $reason ?: 'Rechazado en acción masiva');
                        update_post_meta($job_id, '_moderation_status', 'rejected');
                        agrochamba_notify_author_moderation($job_id, 'rejected', $reason ?: 'Rechazado en acción masiva');
                        $results['success'][] = $job_id;
                    } else {
                        $results['failed'][] = array('id' => $job_id, 'reason' => $update->get_error_message());
                    }
                    break;
                    
                case 'trash':
                    if (wp_trash_post($job_id)) {
                        $results['success'][] = $job_id;
                    } else {
                        $results['failed'][] = array('id' => $job_id, 'reason' => 'Error al mover a papelera');
                    }
                    break;
                    
                case 'delete':
                    if (wp_delete_post($job_id, true)) {
                        $results['success'][] = $job_id;
                    } else {
                        $results['failed'][] = array('id' => $job_id, 'reason' => 'Error al eliminar');
                    }
                    break;
            }
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => sprintf(
                '%d trabajos procesados, %d fallidos.',
                count($results['success']),
                count($results['failed'])
            ),
            'results' => $results,
        ), 200);
    }
}

// ==========================================
// 11. FORMATEAR TRABAJO PARA ADMIN
// ==========================================

if (!function_exists('agrochamba_format_job_for_admin')) {
    function agrochamba_format_job_for_admin($post, $full_details = false) {
        $author = get_userdata($post->post_author);
        
        // Datos básicos
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'status_label' => agrochamba_get_status_label($post->post_status),
            'date' => $post->post_date,
            'date_formatted' => date_i18n('d M Y, H:i', strtotime($post->post_date)),
            'modified' => $post->post_modified,
            'author' => array(
                'id' => $post->post_author,
                'name' => $author ? $author->display_name : 'Desconocido',
                'email' => $author ? $author->user_email : '',
            ),
            'permalink' => get_permalink($post->ID),
            'edit_link' => get_edit_post_link($post->ID, 'raw'),
        );
        
        // Imagen destacada
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        if ($thumbnail_id) {
            $data['featured_image'] = array(
                'id' => $thumbnail_id,
                'thumbnail' => wp_get_attachment_image_url($thumbnail_id, 'thumbnail'),
                'medium' => wp_get_attachment_image_url($thumbnail_id, 'medium'),
                'full' => wp_get_attachment_image_url($thumbnail_id, 'full'),
            );
        }
        
        // Empresa
        $empresa_id = get_post_meta($post->ID, 'empresa_id', true);
        if ($empresa_id) {
            $empresa = get_post($empresa_id);
            if ($empresa) {
                $data['empresa'] = array(
                    'id' => $empresa_id,
                    'name' => $empresa->post_title,
                    'logo' => get_the_post_thumbnail_url($empresa_id, 'thumbnail'),
                );
            }
        }
        
        // Ubicación
        $ubicacion = get_post_meta($post->ID, '_ubicacion_completa', true);
        if ($ubicacion) {
            $data['ubicacion'] = $ubicacion;
        } else {
            $ubicacion_terms = wp_get_post_terms($post->ID, 'ubicacion', array('fields' => 'names'));
            if (!empty($ubicacion_terms)) {
                $data['ubicacion'] = array('departamento' => $ubicacion_terms[0]);
            }
        }
        
        // Estado de moderación
        $moderation_status = get_post_meta($post->ID, '_moderation_status', true);
        $data['moderation'] = array(
            'status' => $moderation_status ?: 'pending',
            'approved_by' => get_post_meta($post->ID, '_moderation_approved_by', true),
            'approved_date' => get_post_meta($post->ID, '_moderation_approved_date', true),
            'rejected_by' => get_post_meta($post->ID, '_moderation_rejected_by', true),
            'rejected_date' => get_post_meta($post->ID, '_moderation_rejected_date', true),
            'rejection_reason' => get_post_meta($post->ID, '_moderation_rejection_reason', true),
        );
        
        // AI moderation
        $ai_moderation = get_post_meta($post->ID, '_ai_moderation_result', true);
        if ($ai_moderation) {
            $data['ai_moderation'] = $ai_moderation;
        }
        
        // Si se requieren todos los detalles
        if ($full_details) {
            $data['content'] = $post->post_content;
            $data['content_html'] = apply_filters('the_content', $post->post_content);
            $data['excerpt'] = $post->post_excerpt;
            
            // Todos los meta fields
            $meta_fields = array(
                'salario_min', 'salario_max', 'vacantes', 'fecha_inicio', 'fecha_fin',
                'duracion_dias', 'tipo_contrato', 'jornada', 'requisitos', 'beneficios',
                'experiencia', 'genero', 'edad_minima', 'edad_maxima', 'estado',
                'contacto_whatsapp', 'contacto_email', 'google_maps_url',
                'alojamiento', 'transporte', 'alimentacion'
            );
            
            $data['meta'] = array();
            foreach ($meta_fields as $field) {
                $value = get_post_meta($post->ID, $field, true);
                if ($value !== '' && $value !== null) {
                    $data['meta'][$field] = $value;
                }
            }
            
            // Galería de imágenes
            $gallery_ids = get_post_meta($post->ID, 'gallery_ids', true);
            if (!empty($gallery_ids) && is_array($gallery_ids)) {
                $data['gallery'] = array();
                foreach ($gallery_ids as $img_id) {
                    $data['gallery'][] = array(
                        'id' => $img_id,
                        'thumbnail' => wp_get_attachment_image_url($img_id, 'thumbnail'),
                        'medium' => wp_get_attachment_image_url($img_id, 'medium'),
                        'full' => wp_get_attachment_image_url($img_id, 'full'),
                    );
                }
            }
            
            // Taxonomías
            $data['taxonomies'] = array(
                'ubicacion' => wp_get_post_terms($post->ID, 'ubicacion', array('fields' => 'names')),
                'cultivo' => wp_get_post_terms($post->ID, 'cultivo', array('fields' => 'names')),
                'tipo_puesto' => wp_get_post_terms($post->ID, 'tipo_puesto', array('fields' => 'names')),
            );
        }
        
        return $data;
    }
}

// ==========================================
// 12. HELPER: ETIQUETA DE ESTADO
// ==========================================

if (!function_exists('agrochamba_get_status_label')) {
    function agrochamba_get_status_label($status) {
        $labels = array(
            'publish' => 'Publicado',
            'pending' => 'Pendiente',
            'draft' => 'Borrador',
            'private' => 'Privado',
            'trash' => 'Papelera',
            'future' => 'Programado',
            'auto-draft' => 'Auto-guardado',
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
}

// ==========================================
// 13. NOTIFICAR AL AUTOR
// ==========================================

if (!function_exists('agrochamba_notify_author_moderation')) {
    function agrochamba_notify_author_moderation($post_id, $action, $reason = '') {
        $post = get_post($post_id);
        if (!$post) return;
        
        $author = get_userdata($post->post_author);
        if (!$author) return;
        
        $site_name = get_bloginfo('name');
        $job_title = $post->post_title;
        
        if ($action === 'approved') {
            $subject = "[{$site_name}] Tu oferta de trabajo ha sido aprobada";
            $message = "Hola {$author->display_name},\n\n";
            $message .= "¡Buenas noticias! Tu oferta de trabajo \"{$job_title}\" ha sido aprobada y ya está publicada.\n\n";
            $message .= "Puedes verla aquí: " . get_permalink($post_id) . "\n\n";
            $message .= "Gracias por publicar en {$site_name}.\n";
        } else {
            $subject = "[{$site_name}] Tu oferta de trabajo requiere cambios";
            $message = "Hola {$author->display_name},\n\n";
            $message .= "Tu oferta de trabajo \"{$job_title}\" ha sido revisada y necesita algunos cambios antes de ser publicada.\n\n";
            if ($reason) {
                $message .= "Razón: {$reason}\n\n";
            }
            $message .= "Por favor, accede a tu panel para editar la oferta.\n\n";
            $message .= "Si tienes dudas, contáctanos.\n";
        }
        
        // Enviar email
        wp_mail($author->user_email, $subject, $message);
    }
}
