<?php
/**
 * Controlador de Postulaciones
 *
 * Maneja las postulaciones de trabajadores a ofertas de trabajo
 *
 * @package AgroChamba
 * @subpackage API\Applications
 * @since 2.1.0
 */

namespace AgroChamba\API\Applications;

use WP_Error;
use WP_REST_Response;
use WP_REST_Request;

class ApplicationsController {

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'agrochamba/v1';

    /**
     * Meta key para almacenar postulaciones del usuario
     */
    const USER_META_KEY = 'job_applications';

    /**
     * Meta key para almacenar postulantes de un trabajo
     */
    const JOB_META_KEY = '_job_applicants';

    /**
     * Estados de postulación
     */
    const STATUS_PENDING = 'pendiente';
    const STATUS_VIEWED = 'visto';
    const STATUS_IN_PROCESS = 'en_proceso';
    const STATUS_INTERVIEW = 'entrevista';
    const STATUS_FINALIST = 'finalista';
    const STATUS_ACCEPTED = 'aceptado';
    const STATUS_REJECTED = 'rechazado';
    const STATUS_CANCELLED = 'cancelado';

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

        // POST y GET /applications
        if (!isset($routes['/' . self::API_NAMESPACE . '/applications'])) {
            register_rest_route(self::API_NAMESPACE, '/applications', array(
                array(
                    'methods' => 'POST',
                    'callback' => array(__CLASS__, 'create_application'),
                    'permission_callback' => function() {
                        return is_user_logged_in();
                    },
                ),
                array(
                    'methods' => 'GET',
                    'callback' => array(__CLASS__, 'get_my_applications'),
                    'permission_callback' => function() {
                        return is_user_logged_in();
                    },
                ),
            ));
        }

        // DELETE /applications/{job_id} - Cancelar postulación
        if (!isset($routes['/' . self::API_NAMESPACE . '/applications/(?P<job_id>\d+)'])) {
            register_rest_route(self::API_NAMESPACE, '/applications/(?P<job_id>\d+)', array(
                'methods' => 'DELETE',
                'callback' => array(__CLASS__, 'cancel_application'),
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
                'args' => array(
                    'job_id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ));
        }

        // GET /jobs/{id}/application-status - Ver si ya postulé
        if (!isset($routes['/' . self::API_NAMESPACE . '/jobs/(?P<id>\d+)/application-status'])) {
            register_rest_route(self::API_NAMESPACE, '/jobs/(?P<id>\d+)/application-status', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'check_application_status'),
                'permission_callback' => '__return_true',
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

        // GET /jobs/{id}/applicants - Ver postulantes (solo para dueño del trabajo o admin)
        if (!isset($routes['/' . self::API_NAMESPACE . '/jobs/(?P<id>\d+)/applicants'])) {
            register_rest_route(self::API_NAMESPACE, '/jobs/(?P<id>\d+)/applicants', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_job_applicants'),
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

        // PUT /applications/{job_id}/status - Actualizar estado (para empresas)
        if (!isset($routes['/' . self::API_NAMESPACE . '/applications/(?P<job_id>\d+)/status'])) {
            register_rest_route(self::API_NAMESPACE, '/applications/(?P<job_id>\d+)/status', array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_application_status'),
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
                'args' => array(
                    'job_id' => array(
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
     * Crear postulación
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function create_application($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        // Verificar que no sea una empresa
        if (in_array('employer', (array)$user->roles) || in_array('administrator', (array)$user->roles)) {
            return new WP_Error(
                'not_allowed',
                'Las empresas no pueden postularse a trabajos.',
                array('status' => 403)
            );
        }

        $params = $request->get_json_params();
        $job_id = isset($params['job_id']) ? intval($params['job_id']) : 0;
        $message = isset($params['message']) ? sanitize_textarea_field($params['message']) : '';

        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }

        // Verificar que el trabajo existe
        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        // Verificar que el trabajo esté publicado
        if ($job->post_status !== 'publish') {
            return new WP_Error('job_not_available', 'Este trabajo ya no está disponible.', array('status' => 400));
        }

        // Obtener postulaciones actuales del usuario
        $applications = get_user_meta($user_id, self::USER_META_KEY, true);
        if (!is_array($applications)) {
            $applications = array();
        }

        // Verificar si ya se postuló
        if (isset($applications[$job_id])) {
            return new WP_Error(
                'already_applied',
                'Ya te has postulado a este trabajo.',
                array('status' => 400)
            );
        }

        // Crear la postulación
        $application = array(
            'job_id' => $job_id,
            'status' => self::STATUS_PENDING,
            'message' => $message,
            'applied_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $applications[$job_id] = $application;
        update_user_meta($user_id, self::USER_META_KEY, $applications);

        // También guardar en el meta del trabajo para que la empresa pueda ver los postulantes
        $job_applicants = get_post_meta($job_id, self::JOB_META_KEY, true);
        if (!is_array($job_applicants)) {
            $job_applicants = array();
        }

        $job_applicants[$user_id] = array(
            'user_id' => $user_id,
            'status' => self::STATUS_PENDING,
            'message' => $message,
            'applied_at' => current_time('mysql'),
            'viewed_at' => null,
        );
        update_post_meta($job_id, self::JOB_META_KEY, $job_applicants);

        // Disparar hook para notificaciones push
        do_action('agrochamba_new_application', $job_id, $user_id, $message);

        // Obtener datos del trabajo para la respuesta
        $job_data = self::get_job_summary($job);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Postulación enviada correctamente.',
            'application' => array_merge($application, array(
                'job' => $job_data
            ))
        ), 201);
    }

    /**
     * Obtener mis postulaciones
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_my_applications($request) {
        $user_id = get_current_user_id();
        $applications = get_user_meta($user_id, self::USER_META_KEY, true);

        if (!is_array($applications) || empty($applications)) {
            return new WP_REST_Response(array('applications' => array()), 200);
        }

        $result = array();
        foreach ($applications as $job_id => $application) {
            $job = get_post($job_id);
            if (!$job) {
                continue;
            }

            // Obtener datos actualizados del estado desde el meta del trabajo
            $job_applicants = get_post_meta($job_id, self::JOB_META_KEY, true);
            $current_status = $application['status'];
            if (is_array($job_applicants) && isset($job_applicants[$user_id])) {
                $current_status = $job_applicants[$user_id]['status'];
            }

            $result[] = array(
                'job_id' => $job_id,
                'status' => $current_status,
                'status_label' => self::get_status_label($current_status),
                'message' => $application['message'] ?? '',
                'applied_at' => $application['applied_at'],
                'job' => self::get_job_summary($job)
            );
        }

        // Ordenar por fecha de postulación (más recientes primero)
        usort($result, function($a, $b) {
            return strtotime($b['applied_at']) - strtotime($a['applied_at']);
        });

        return new WP_REST_Response(array('applications' => $result), 200);
    }

    /**
     * Cancelar postulación
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function cancel_application($request) {
        $user_id = get_current_user_id();
        $job_id = intval($request->get_param('job_id'));

        $applications = get_user_meta($user_id, self::USER_META_KEY, true);
        if (!is_array($applications) || !isset($applications[$job_id])) {
            return new WP_Error(
                'application_not_found',
                'No tienes una postulación para este trabajo.',
                array('status' => 404)
            );
        }

        // Solo se puede cancelar en estados iniciales del pipeline
        $job_applicants = get_post_meta($job_id, self::JOB_META_KEY, true);
        $cancellable_statuses = array(self::STATUS_PENDING, self::STATUS_VIEWED, self::STATUS_IN_PROCESS);
        if (is_array($job_applicants) && isset($job_applicants[$user_id])) {
            if (!in_array($job_applicants[$user_id]['status'], $cancellable_statuses)) {
                return new WP_Error(
                    'cannot_cancel',
                    'No puedes cancelar una postulación en estado "' . self::get_status_label($job_applicants[$user_id]['status']) . '".',
                    array('status' => 400)
                );
            }
        }

        // Eliminar de las postulaciones del usuario
        unset($applications[$job_id]);
        update_user_meta($user_id, self::USER_META_KEY, $applications);

        // Actualizar estado en el meta del trabajo
        if (is_array($job_applicants) && isset($job_applicants[$user_id])) {
            $job_applicants[$user_id]['status'] = self::STATUS_CANCELLED;
            update_post_meta($job_id, self::JOB_META_KEY, $job_applicants);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Postulación cancelada correctamente.'
        ), 200);
    }

    /**
     * Verificar estado de postulación para un trabajo
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function check_application_status($request) {
        $job_id = intval($request->get_param('id'));

        if (!is_user_logged_in()) {
            return new WP_REST_Response(array(
                'has_applied' => false,
                'status' => null,
                'status_label' => null
            ), 200);
        }

        $user_id = get_current_user_id();
        $applications = get_user_meta($user_id, self::USER_META_KEY, true);

        if (!is_array($applications) || !isset($applications[$job_id])) {
            return new WP_REST_Response(array(
                'has_applied' => false,
                'status' => null,
                'status_label' => null
            ), 200);
        }

        // Obtener estado actualizado
        $job_applicants = get_post_meta($job_id, self::JOB_META_KEY, true);
        $status = $applications[$job_id]['status'];
        if (is_array($job_applicants) && isset($job_applicants[$user_id])) {
            $status = $job_applicants[$user_id]['status'];
        }

        return new WP_REST_Response(array(
            'has_applied' => true,
            'status' => $status,
            'status_label' => self::get_status_label($status),
            'applied_at' => $applications[$job_id]['applied_at']
        ), 200);
    }

    /**
     * Obtener postulantes de un trabajo (para empresas)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_job_applicants($request) {
        $user_id = get_current_user_id();
        $job_id = intval($request->get_param('id'));

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        // Verificar permisos: debe ser el autor del trabajo o admin
        $user = get_userdata($user_id);
        $is_admin = in_array('administrator', (array)$user->roles);
        $is_owner = intval($job->post_author) === $user_id;

        // También verificar si el usuario es dueño de la empresa que publicó el trabajo
        $empresa_id = get_post_meta($job_id, '_empresa_id', true);
        $is_company_owner = false;
        if ($empresa_id) {
            $empresa = get_post($empresa_id);
            if ($empresa && intval($empresa->post_author) === $user_id) {
                $is_company_owner = true;
            }
        }

        if (!$is_admin && !$is_owner && !$is_company_owner) {
            return new WP_Error(
                'not_authorized',
                'No tienes permiso para ver los postulantes de este trabajo.',
                array('status' => 403)
            );
        }

        $job_applicants = get_post_meta($job_id, self::JOB_META_KEY, true);
        if (!is_array($job_applicants) || empty($job_applicants)) {
            return new WP_REST_Response(array('applicants' => array()), 200);
        }

        $result = array();
        foreach ($job_applicants as $applicant_id => $applicant_data) {
            // Saltar postulaciones canceladas
            if ($applicant_data['status'] === self::STATUS_CANCELLED) {
                continue;
            }

            $applicant_user = get_userdata($applicant_id);
            if (!$applicant_user) {
                continue;
            }

            // Marcar como visto si es la primera vez
            if ($applicant_data['status'] === self::STATUS_PENDING) {
                $job_applicants[$applicant_id]['status'] = self::STATUS_VIEWED;
                $job_applicants[$applicant_id]['viewed_at'] = current_time('mysql');
                update_post_meta($job_id, self::JOB_META_KEY, $job_applicants);
                $applicant_data['status'] = self::STATUS_VIEWED;
            }

            $result[] = array(
                'user_id' => $applicant_id,
                'display_name' => $applicant_user->display_name,
                'email' => $applicant_user->user_email,
                'phone' => get_user_meta($applicant_id, 'phone', true),
                'dni' => get_user_meta($applicant_id, 'dni', true),
                'profile_photo' => get_user_meta($applicant_id, 'profile_photo_url', true) ?: null,
                'status' => $applicant_data['status'],
                'status_label' => self::get_status_label($applicant_data['status']),
                'message' => $applicant_data['message'] ?? '',
                'applied_at' => $applicant_data['applied_at'],
                'viewed_at' => $applicant_data['viewed_at'] ?? null,
            );
        }

        // Ordenar por fecha de postulación (más recientes primero)
        usort($result, function($a, $b) {
            return strtotime($b['applied_at']) - strtotime($a['applied_at']);
        });

        return new WP_REST_Response(array(
            'applicants' => $result,
            'total' => count($result)
        ), 200);
    }

    /**
     * Actualizar estado de postulación (para empresas)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function update_application_status($request) {
        $current_user_id = get_current_user_id();
        $job_id = intval($request->get_param('job_id'));
        $params = $request->get_json_params();

        $applicant_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $new_status = isset($params['status']) ? sanitize_text_field($params['status']) : '';

        if ($applicant_id <= 0) {
            return new WP_Error('invalid_user_id', 'ID de usuario inválido.', array('status' => 400));
        }

        $all_statuses = self::get_all_statuses();
        if (!in_array($new_status, $all_statuses)) {
            return new WP_Error(
                'invalid_status',
                'Estado inválido.',
                array('status' => 400)
            );
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        // Verificar permisos
        $user = get_userdata($current_user_id);
        $is_admin = in_array('administrator', (array)$user->roles);
        $is_owner = intval($job->post_author) === $current_user_id;

        $empresa_id = get_post_meta($job_id, '_empresa_id', true);
        $is_company_owner = false;
        if ($empresa_id) {
            $empresa = get_post($empresa_id);
            if ($empresa && intval($empresa->post_author) === $current_user_id) {
                $is_company_owner = true;
            }
        }

        if (!$is_admin && !$is_owner && !$is_company_owner) {
            return new WP_Error(
                'not_authorized',
                'No tienes permiso para actualizar postulaciones de este trabajo.',
                array('status' => 403)
            );
        }

        // Actualizar estado en el meta del trabajo
        $job_applicants = get_post_meta($job_id, self::JOB_META_KEY, true);
        if (!is_array($job_applicants) || !isset($job_applicants[$applicant_id])) {
            return new WP_Error(
                'application_not_found',
                'Postulación no encontrada.',
                array('status' => 404)
            );
        }

        // Guardar estado anterior para el hook
        $old_status = $job_applicants[$applicant_id]['status'];

        // Validar transición
        $allowed = self::get_allowed_transitions($old_status);
        if (!in_array($new_status, $allowed)) {
            return new WP_Error(
                'invalid_transition',
                sprintf('No se puede cambiar de "%s" a "%s".', self::get_status_label($old_status), self::get_status_label($new_status)),
                array('status' => 400)
            );
        }

        $job_applicants[$applicant_id]['status'] = $new_status;
        $job_applicants[$applicant_id]['updated_at'] = current_time('mysql');
        update_post_meta($job_id, self::JOB_META_KEY, $job_applicants);

        // También actualizar en el meta del usuario
        $user_applications = get_user_meta($applicant_id, self::USER_META_KEY, true);
        if (is_array($user_applications) && isset($user_applications[$job_id])) {
            $user_applications[$job_id]['status'] = $new_status;
            $user_applications[$job_id]['updated_at'] = current_time('mysql');
            update_user_meta($applicant_id, self::USER_META_KEY, $user_applications);
        }

        // Disparar hook para notificaciones push
        do_action('agrochamba_application_status_changed', $applicant_id, $job_id, $old_status, $new_status);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Estado de postulación actualizado.',
            'new_status' => $new_status,
            'status_label' => self::get_status_label($new_status)
        ), 200);
    }

    /**
     * Obtener resumen de un trabajo
     *
     * @param WP_Post $job
     * @return array
     */
    private static function get_job_summary($job) {
        $empresa_id = get_post_meta($job->ID, '_empresa_id', true);
        $empresa_name = '';
        if ($empresa_id) {
            $empresa = get_post($empresa_id);
            if ($empresa) {
                $empresa_name = $empresa->post_title;
            }
        }

        return array(
            'id' => $job->ID,
            'title' => $job->post_title,
            'status' => $job->post_status,
            'date' => $job->post_date,
            'empresa' => $empresa_name,
            'ubicacion' => get_post_meta($job->ID, '_ubicacion', true),
            'salario_min' => get_post_meta($job->ID, '_salario_min', true),
            'salario_max' => get_post_meta($job->ID, '_salario_max', true),
        );
    }

    /**
     * Obtener transiciones permitidas desde un estado
     *
     * @param string $current_status
     * @return array
     */
    public static function get_allowed_transitions($current_status) {
        $transitions = array(
            self::STATUS_PENDING   => array(self::STATUS_VIEWED, self::STATUS_REJECTED, self::STATUS_CANCELLED),
            self::STATUS_VIEWED    => array(self::STATUS_IN_PROCESS, self::STATUS_REJECTED, self::STATUS_CANCELLED),
            self::STATUS_IN_PROCESS => array(self::STATUS_INTERVIEW, self::STATUS_REJECTED, self::STATUS_CANCELLED),
            self::STATUS_INTERVIEW => array(self::STATUS_FINALIST, self::STATUS_REJECTED),
            self::STATUS_FINALIST  => array(self::STATUS_ACCEPTED, self::STATUS_REJECTED),
            self::STATUS_ACCEPTED  => array(),
            self::STATUS_REJECTED  => array(),
            self::STATUS_CANCELLED => array(),
        );
        return $transitions[$current_status] ?? array();
    }

    /**
     * Obtener todos los estados válidos del pipeline
     *
     * @return array
     */
    public static function get_all_statuses() {
        return array(
            self::STATUS_PENDING,
            self::STATUS_VIEWED,
            self::STATUS_IN_PROCESS,
            self::STATUS_INTERVIEW,
            self::STATUS_FINALIST,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        );
    }

    /**
     * Obtener etiqueta de estado
     *
     * @param string $status
     * @return string
     */
    public static function get_status_label($status) {
        $labels = array(
            self::STATUS_PENDING    => 'Postulado',
            self::STATUS_VIEWED     => 'CV Visto',
            self::STATUS_IN_PROCESS => 'En Proceso',
            self::STATUS_INTERVIEW  => 'Entrevista',
            self::STATUS_FINALIST   => 'Finalista',
            self::STATUS_ACCEPTED   => 'Contratado',
            self::STATUS_REJECTED   => 'No Seleccionado',
            self::STATUS_CANCELLED  => 'Cancelado',
        );
        return $labels[$status] ?? 'Desconocido';
    }
}
