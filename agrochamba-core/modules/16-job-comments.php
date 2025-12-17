<?php
/**
 * =============================================================
 * MÓDULO 16: COMENTARIOS DE TRABAJOS
 * =============================================================
 * 
 * Sistema completo de comentarios para trabajos
 * Funciona tanto en web como en app móvil mediante REST API
 * 
 * Funciones:
 * - Endpoints REST API para crear, obtener, editar y eliminar comentarios
 * - Habilitar comentarios en el post type 'trabajo'
 * - Validación y sanitización de comentarios
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. HABILITAR COMENTARIOS PARA TRABAJOS
// ==========================================
if (!function_exists('agrochamba_enable_comments_for_trabajos')) {
    function agrochamba_enable_comments_for_trabajos() {
        // Asegurar que los comentarios estén habilitados para trabajos existentes
        add_action('init', function() {
            global $wpdb;
            // Actualizar posts existentes para habilitar comentarios
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->posts} SET comment_status = 'open' WHERE post_type = %s AND comment_status = 'closed'",
                'trabajo'
            ));
        }, 20);
    }
    add_action('plugins_loaded', 'agrochamba_enable_comments_for_trabajos');
}

// ==========================================
// 2. ENDPOINTS REST API PARA COMENTARIOS
// ==========================================
if (!function_exists('agrochamba_register_comment_routes')) {
    function agrochamba_register_comment_routes() {
        // Obtener comentarios de un trabajo
        register_rest_route('agrochamba/v1', '/jobs/(?P<id>\d+)/comments', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_job_comments',
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
        
        // Crear un nuevo comentario
        register_rest_route('agrochamba/v1', '/jobs/(?P<id>\d+)/comments', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_create_job_comment',
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
                'content' => array(
                    'required' => true,
                    'sanitize_callback' => 'wp_kses_post',
                    'validate_callback' => function($param) {
                        return !empty(trim(strip_tags($param)));
                    }
                ),
                'parent' => array(
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
        
        // Actualizar un comentario
        register_rest_route('agrochamba/v1', '/comments/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => 'agrochamba_update_job_comment',
            'permission_callback' => function($request) {
                if (!is_user_logged_in()) {
                    return false;
                }
                $comment_id = intval($request->get_param('id'));
                $comment = get_comment($comment_id);
                if (!$comment) {
                    return false;
                }
                // Solo el autor del comentario o un admin puede editarlo
                return get_current_user_id() == $comment->user_id || current_user_can('moderate_comments');
            },
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'content' => array(
                    'required' => true,
                    'sanitize_callback' => 'wp_kses_post',
                    'validate_callback' => function($param) {
                        return !empty(trim(strip_tags($param)));
                    }
                ),
            ),
        ));
        
        // Eliminar un comentario
        register_rest_route('agrochamba/v1', '/comments/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => 'agrochamba_delete_job_comment',
            'permission_callback' => function($request) {
                if (!is_user_logged_in()) {
                    return false;
                }
                $comment_id = intval($request->get_param('id'));
                $comment = get_comment($comment_id);
                if (!$comment) {
                    return false;
                }
                // Solo el autor del comentario o un admin puede eliminarlo
                return get_current_user_id() == $comment->user_id || current_user_can('moderate_comments');
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
    add_action('rest_api_init', 'agrochamba_register_comment_routes');
}

/**
 * Obtener comentarios de un trabajo
 */
if (!function_exists('agrochamba_get_job_comments')) {
    function agrochamba_get_job_comments($request) {
        $job_id = intval($request->get_param('id'));
        $page = intval($request->get_param('page')) ?: 1;
        $per_page = min(50, max(1, intval($request->get_param('per_page')) ?: 20));
        
        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        $args = array(
            'post_id' => $job_id,
            'status' => 'approve',
            'orderby' => 'comment_date',
            'order' => 'DESC',
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'hierarchical' => true,
        );

        $comments = get_comments($args);
        $total_comments = get_comments_number($job_id);

        $comments_data = array();
        foreach ($comments as $comment) {
            $user = get_userdata($comment->user_id);
            $avatar_url = $comment->user_id ? get_avatar_url($comment->user_id, array('size' => 64)) : get_avatar_url($comment->comment_author_email, array('size' => 64));
            
            $comments_data[] = array(
                'id' => $comment->comment_ID,
                'content' => wp_kses_post($comment->comment_content),
                'author' => array(
                    'id' => $comment->user_id,
                    'name' => $comment->comment_author ?: ($user ? $user->display_name : 'Anónimo'),
                    'avatar' => $avatar_url,
                ),
                'date' => $comment->comment_date,
                'date_gmt' => $comment->comment_date_gmt,
                'parent' => $comment->comment_parent,
                'can_edit' => is_user_logged_in() && (get_current_user_id() == $comment->user_id || current_user_can('moderate_comments')),
                'can_delete' => is_user_logged_in() && (get_current_user_id() == $comment->user_id || current_user_can('moderate_comments')),
            );
        }

        return new WP_REST_Response(array(
            'comments' => $comments_data,
            'total' => $total_comments,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_comments / $per_page),
        ), 200);
    }
}

/**
 * Crear un nuevo comentario
 */
if (!function_exists('agrochamba_create_job_comment')) {
    function agrochamba_create_job_comment($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para comentar.', array('status' => 401));
        }

        $job_id = intval($request->get_param('id'));
        $content = trim($request->get_param('content'));
        $parent = intval($request->get_param('parent')) ?: 0;

        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        if (empty($content)) {
            return new WP_Error('invalid_content', 'El comentario no puede estar vacío.', array('status' => 400));
        }

        // Verificar si el trabajo permite comentarios
        if ($job->comment_status !== 'open') {
            return new WP_Error('comments_closed', 'Los comentarios están cerrados para este trabajo.', array('status' => 403));
        }

        // Si hay un comentario padre, verificar que existe y pertenece al mismo trabajo
        if ($parent > 0) {
            $parent_comment = get_comment($parent);
            if (!$parent_comment || $parent_comment->comment_post_ID != $job_id) {
                return new WP_Error('invalid_parent', 'El comentario padre no es válido.', array('status' => 400));
            }
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        $comment_data = array(
            'comment_post_ID' => $job_id,
            'comment_author' => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url' => '',
            'comment_content' => wp_kses_post($content),
            'comment_type' => 'comment',
            'comment_parent' => $parent,
            'user_id' => $user_id,
            'comment_approved' => 1, // Auto-aprobar comentarios de usuarios logueados
        );

        $comment_id = wp_insert_comment($comment_data);

        if (is_wp_error($comment_id)) {
            return new WP_Error('comment_failed', 'Error al crear el comentario.', array('status' => 500));
        }

        $comment = get_comment($comment_id);
        $avatar_url = get_avatar_url($user_id, array('size' => 64));

        return new WP_REST_Response(array(
            'success' => true,
            'comment' => array(
                'id' => $comment->comment_ID,
                'content' => wp_kses_post($comment->comment_content),
                'author' => array(
                    'id' => $user_id,
                    'name' => $user->display_name,
                    'avatar' => $avatar_url,
                ),
                'date' => $comment->comment_date,
                'date_gmt' => $comment->comment_date_gmt,
                'parent' => $comment->comment_parent,
                'can_edit' => true,
                'can_delete' => true,
            ),
        ), 201);
    }
}

/**
 * Actualizar un comentario
 */
if (!function_exists('agrochamba_update_job_comment')) {
    function agrochamba_update_job_comment($request) {
        $comment_id = intval($request->get_param('id'));
        $content = trim($request->get_param('content'));

        if ($comment_id <= 0) {
            return new WP_Error('invalid_comment_id', 'ID de comentario inválido.', array('status' => 400));
        }

        $comment = get_comment($comment_id);
        if (!$comment) {
            return new WP_Error('comment_not_found', 'Comentario no encontrado.', array('status' => 404));
        }

        if (empty($content)) {
            return new WP_Error('invalid_content', 'El comentario no puede estar vacío.', array('status' => 400));
        }

        // Verificar permisos
        $user_id = get_current_user_id();
        if ($user_id != $comment->user_id && !current_user_can('moderate_comments')) {
            return new WP_Error('rest_forbidden', 'No tienes permiso para editar este comentario.', array('status' => 403));
        }

        $comment_data = array(
            'comment_ID' => $comment_id,
            'comment_content' => wp_kses_post($content),
        );

        $updated = wp_update_comment($comment_data);

        if (is_wp_error($updated)) {
            return new WP_Error('update_failed', 'Error al actualizar el comentario.', array('status' => 500));
        }

        $updated_comment = get_comment($comment_id);
        $user = get_userdata($updated_comment->user_id);
        $avatar_url = get_avatar_url($updated_comment->user_id, array('size' => 64));

        return new WP_REST_Response(array(
            'success' => true,
            'comment' => array(
                'id' => $updated_comment->comment_ID,
                'content' => wp_kses_post($updated_comment->comment_content),
                'author' => array(
                    'id' => $updated_comment->user_id,
                    'name' => $user ? $user->display_name : $updated_comment->comment_author,
                    'avatar' => $avatar_url,
                ),
                'date' => $updated_comment->comment_date,
                'date_gmt' => $updated_comment->comment_date_gmt,
                'parent' => $updated_comment->comment_parent,
                'can_edit' => true,
                'can_delete' => true,
            ),
        ), 200);
    }
}

/**
 * Eliminar un comentario
 */
if (!function_exists('agrochamba_delete_job_comment')) {
    function agrochamba_delete_job_comment($request) {
        $comment_id = intval($request->get_param('id'));

        if ($comment_id <= 0) {
            return new WP_Error('invalid_comment_id', 'ID de comentario inválido.', array('status' => 400));
        }

        $comment = get_comment($comment_id);
        if (!$comment) {
            return new WP_Error('comment_not_found', 'Comentario no encontrado.', array('status' => 404));
        }

        // Verificar permisos
        $user_id = get_current_user_id();
        if ($user_id != $comment->user_id && !current_user_can('moderate_comments')) {
            return new WP_Error('rest_forbidden', 'No tienes permiso para eliminar este comentario.', array('status' => 403));
        }

        $deleted = wp_delete_comment($comment_id, true); // true = eliminar permanentemente

        if (!$deleted) {
            return new WP_Error('delete_failed', 'Error al eliminar el comentario.', array('status' => 500));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Comentario eliminado correctamente.',
        ), 200);
    }
}

