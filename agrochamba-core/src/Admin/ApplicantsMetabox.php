<?php
/**
 * Metabox de Postulantes para trabajos
 *
 * Muestra la lista de postulantes en la pantalla de edición de trabajos
 * y permite a las empresas gestionar las postulaciones.
 *
 * @package AgroChamba
 * @subpackage Admin
 * @since 2.1.0
 */

namespace AgroChamba\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class ApplicantsMetabox {

    /**
     * Meta key para almacenar postulantes
     */
    const JOB_META_KEY = '_job_applicants';

    /**
     * Inicializar el metabox
     */
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_metabox'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('wp_ajax_agrochamba_update_applicant_status', array(__CLASS__, 'ajax_update_status'));

        // Añadir columna en la lista de trabajos
        add_filter('manage_trabajo_posts_columns', array(__CLASS__, 'add_applicants_column'));
        add_action('manage_trabajo_posts_custom_column', array(__CLASS__, 'render_applicants_column'), 10, 2);
    }

    /**
     * Añadir metabox
     */
    public static function add_metabox() {
        add_meta_box(
            'agrochamba_applicants',
            '👥 Postulantes',
            array(__CLASS__, 'render_metabox'),
            'trabajo',
            'normal',
            'high'
        );
    }

    /**
     * Encolar scripts y estilos
     */
    public static function enqueue_scripts($hook) {
        global $post;

        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        if (!$post || $post->post_type !== 'trabajo') {
            return;
        }

        wp_enqueue_style(
            'agrochamba-applicants-metabox',
            plugins_url('assets/css/applicants-metabox.css', dirname(dirname(__FILE__))),
            array(),
            AGROCHAMBA_VERSION
        );

        wp_enqueue_script(
            'agrochamba-applicants-metabox',
            plugins_url('assets/js/applicants-metabox.js', dirname(dirname(__FILE__))),
            array('jquery'),
            AGROCHAMBA_VERSION,
            true
        );

        wp_localize_script('agrochamba-applicants-metabox', 'agrochambaApplicants', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('agrochamba_applicants_nonce'),
            'jobId' => $post->ID,
            'strings' => array(
                'confirmAccept' => '¿Aceptar a este postulante?',
                'confirmReject' => '¿Rechazar a este postulante?',
                'updating' => 'Actualizando...',
                'error' => 'Error al actualizar el estado'
            )
        ));
    }

    /**
     * Renderizar metabox
     */
    public static function render_metabox($post) {
        $applicants = get_post_meta($post->ID, self::JOB_META_KEY, true);

        if (!is_array($applicants) || empty($applicants)) {
            echo '<div class="agrochamba-no-applicants">';
            echo '<span class="dashicons dashicons-groups" style="font-size: 48px; color: #ccc; display: block; text-align: center; margin: 20px 0;"></span>';
            echo '<p style="text-align: center; color: #666;">No hay postulantes para este trabajo todavía.</p>';
            echo '</div>';
            return;
        }

        // Filtrar cancelados y ordenar
        $active_applicants = array_filter($applicants, function($a) {
            return isset($a['status']) && $a['status'] !== 'cancelado';
        });

        if (empty($active_applicants)) {
            echo '<div class="agrochamba-no-applicants">';
            echo '<p style="text-align: center; color: #666;">No hay postulantes activos.</p>';
            echo '</div>';
            return;
        }

        // Ordenar por fecha
        uasort($active_applicants, function($a, $b) {
            return strtotime($b['applied_at'] ?? '') - strtotime($a['applied_at'] ?? '');
        });

        // Estadísticas
        $stats = self::get_stats($active_applicants);
        ?>

        <div class="agrochamba-applicants-stats">
            <div class="stat-item stat-total">
                <span class="stat-number"><?php echo $stats['total']; ?></span>
                <span class="stat-label">Total</span>
            </div>
            <div class="stat-item stat-pending">
                <span class="stat-number"><?php echo $stats['pending']; ?></span>
                <span class="stat-label">Pendientes</span>
            </div>
            <div class="stat-item stat-viewed">
                <span class="stat-number"><?php echo $stats['viewed']; ?></span>
                <span class="stat-label">Vistos</span>
            </div>
            <div class="stat-item stat-accepted">
                <span class="stat-number"><?php echo $stats['accepted']; ?></span>
                <span class="stat-label">Aceptados</span>
            </div>
            <div class="stat-item stat-rejected">
                <span class="stat-number"><?php echo $stats['rejected']; ?></span>
                <span class="stat-label">Rechazados</span>
            </div>
        </div>

        <table class="agrochamba-applicants-table widefat">
            <thead>
                <tr>
                    <th width="25%">Postulante</th>
                    <th width="20%">Contacto</th>
                    <th width="20%">Mensaje</th>
                    <th width="15%">Fecha</th>
                    <th width="10%">Estado</th>
                    <th width="10%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($active_applicants as $user_id => $applicant):
                    $user = get_userdata($user_id);
                    if (!$user) continue;

                    $phone = get_user_meta($user_id, 'phone', true);
                    $dni = get_user_meta($user_id, 'dni', true);
                    $profile_photo = get_user_meta($user_id, 'profile_photo_url', true);
                    $status = $applicant['status'] ?? 'pendiente';
                    $applied_at = isset($applicant['applied_at']) ? date_i18n('d/m/Y H:i', strtotime($applicant['applied_at'])) : '-';
                ?>
                <tr class="applicant-row status-<?php echo esc_attr($status); ?>" data-user-id="<?php echo esc_attr($user_id); ?>">
                    <td class="applicant-info">
                        <div class="applicant-avatar">
                            <?php if ($profile_photo): ?>
                                <img src="<?php echo esc_url($profile_photo); ?>" alt="" width="40" height="40">
                            <?php else: ?>
                                <span class="dashicons dashicons-admin-users"></span>
                            <?php endif; ?>
                        </div>
                        <div class="applicant-details">
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <?php if ($dni): ?>
                                <br><small>DNI: <?php echo esc_html($dni); ?></small>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="applicant-contact">
                        <a href="mailto:<?php echo esc_attr($user->user_email); ?>" title="Enviar email">
                            <span class="dashicons dashicons-email"></span>
                            <?php echo esc_html($user->user_email); ?>
                        </a>
                        <?php if ($phone): ?>
                            <br>
                            <a href="tel:<?php echo esc_attr($phone); ?>" title="Llamar">
                                <span class="dashicons dashicons-phone"></span>
                                <?php echo esc_html($phone); ?>
                            </a>
                            <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>" target="_blank" title="WhatsApp" class="whatsapp-link">
                                <span class="dashicons dashicons-whatsapp"></span>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td class="applicant-message">
                        <?php if (!empty($applicant['message'])): ?>
                            <em>"<?php echo esc_html(wp_trim_words($applicant['message'], 15)); ?>"</em>
                        <?php else: ?>
                            <span class="no-message">Sin mensaje</span>
                        <?php endif; ?>
                    </td>
                    <td class="applicant-date">
                        <?php echo esc_html($applied_at); ?>
                    </td>
                    <td class="applicant-status">
                        <?php echo self::get_status_badge($status); ?>
                    </td>
                    <td class="applicant-actions">
                        <?php if ($status !== 'aceptado' && $status !== 'rechazado'): ?>
                            <button type="button" class="button button-small button-primary accept-btn" data-action="accept" title="Aceptar">
                                <span class="dashicons dashicons-yes"></span>
                            </button>
                            <button type="button" class="button button-small reject-btn" data-action="reject" title="Rechazar">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        <?php else: ?>
                            <span class="action-done">✓</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <style>
            .agrochamba-applicants-stats {
                display: flex;
                gap: 15px;
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 8px;
            }
            .stat-item {
                text-align: center;
                padding: 10px 20px;
                background: white;
                border-radius: 6px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .stat-number {
                display: block;
                font-size: 24px;
                font-weight: bold;
            }
            .stat-label {
                font-size: 12px;
                color: #666;
            }
            .stat-pending .stat-number { color: #f0ad4e; }
            .stat-viewed .stat-number { color: #5bc0de; }
            .stat-accepted .stat-number { color: #5cb85c; }
            .stat-rejected .stat-number { color: #d9534f; }

            .agrochamba-applicants-table {
                margin-top: 10px;
            }
            .agrochamba-applicants-table td {
                vertical-align: middle;
                padding: 12px 8px;
            }
            .applicant-info {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .applicant-avatar img,
            .applicant-avatar .dashicons {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
                background: #e0e0e0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                color: #666;
            }
            .applicant-contact a {
                text-decoration: none;
                color: #0073aa;
            }
            .applicant-contact .dashicons {
                font-size: 14px;
                vertical-align: middle;
                margin-right: 3px;
            }
            .whatsapp-link {
                color: #25D366 !important;
                margin-left: 5px;
            }
            .applicant-message em {
                color: #666;
                font-size: 13px;
            }
            .no-message {
                color: #999;
                font-style: italic;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .status-pendiente { background: #fcf8e3; color: #8a6d3b; }
            .status-visto { background: #d9edf7; color: #31708f; }
            .status-aceptado { background: #dff0d8; color: #3c763d; }
            .status-rechazado { background: #f2dede; color: #a94442; }

            .applicant-actions .button {
                padding: 0 8px;
                min-height: 28px;
            }
            .applicant-actions .dashicons {
                font-size: 16px;
                line-height: 28px;
            }
            .accept-btn:hover { background: #46b450 !important; border-color: #46b450 !important; }
            .reject-btn { border-color: #dc3232; color: #dc3232; }
            .reject-btn:hover { background: #dc3232 !important; color: white !important; }

            .applicant-row.status-aceptado { background: #f0fff0; }
            .applicant-row.status-rechazado { background: #fff5f5; opacity: 0.7; }
            .action-done { color: #46b450; font-weight: bold; }
        </style>
        <?php
    }

    /**
     * Obtener estadísticas
     */
    private static function get_stats($applicants) {
        $stats = array(
            'total' => count($applicants),
            'pending' => 0,
            'viewed' => 0,
            'accepted' => 0,
            'rejected' => 0
        );

        foreach ($applicants as $applicant) {
            $status = $applicant['status'] ?? 'pendiente';
            switch ($status) {
                case 'pendiente':
                    $stats['pending']++;
                    break;
                case 'visto':
                    $stats['viewed']++;
                    break;
                case 'aceptado':
                    $stats['accepted']++;
                    break;
                case 'rechazado':
                    $stats['rejected']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Obtener badge de estado
     */
    private static function get_status_badge($status) {
        $labels = array(
            'pendiente' => 'Pendiente',
            'visto' => 'Visto',
            'aceptado' => 'Aceptado',
            'rechazado' => 'Rechazado'
        );
        $label = $labels[$status] ?? $status;
        return sprintf('<span class="status-badge status-%s">%s</span>', esc_attr($status), esc_html($label));
    }

    /**
     * AJAX: Actualizar estado de postulante
     */
    public static function ajax_update_status() {
        check_ajax_referer('agrochamba_applicants_nonce', 'nonce');

        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $action = isset($_POST['status_action']) ? sanitize_text_field($_POST['status_action']) : '';

        if (!$job_id || !$user_id || !$action) {
            wp_send_json_error('Parámetros inválidos');
        }

        // Verificar permisos
        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            wp_send_json_error('Trabajo no encontrado');
        }

        $current_user_id = get_current_user_id();
        if ($job->post_author != $current_user_id && !current_user_can('administrator')) {
            wp_send_json_error('Sin permisos');
        }

        // Actualizar estado
        $new_status = ($action === 'accept') ? 'aceptado' : 'rechazado';

        $job_applicants = get_post_meta($job_id, self::JOB_META_KEY, true);
        if (!is_array($job_applicants) || !isset($job_applicants[$user_id])) {
            wp_send_json_error('Postulante no encontrado');
        }

        $job_applicants[$user_id]['status'] = $new_status;
        $job_applicants[$user_id]['updated_at'] = current_time('mysql');
        update_post_meta($job_id, self::JOB_META_KEY, $job_applicants);

        // También actualizar en el meta del usuario
        $user_applications = get_user_meta($user_id, 'job_applications', true);
        if (is_array($user_applications) && isset($user_applications[$job_id])) {
            $user_applications[$job_id]['status'] = $new_status;
            $user_applications[$job_id]['updated_at'] = current_time('mysql');
            update_user_meta($user_id, 'job_applications', $user_applications);
        }

        wp_send_json_success(array(
            'status' => $new_status,
            'badge' => self::get_status_badge($new_status)
        ));
    }

    /**
     * Añadir columna de postulantes en la lista
     */
    public static function add_applicants_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['applicants'] = '👥 Postulantes';
            }
        }
        return $new_columns;
    }

    /**
     * Renderizar columna de postulantes
     */
    public static function render_applicants_column($column, $post_id) {
        if ($column !== 'applicants') {
            return;
        }

        $applicants = get_post_meta($post_id, self::JOB_META_KEY, true);

        if (!is_array($applicants) || empty($applicants)) {
            echo '<span style="color: #999;">0</span>';
            return;
        }

        // Contar solo activos (no cancelados)
        $active = array_filter($applicants, function($a) {
            return isset($a['status']) && $a['status'] !== 'cancelado';
        });

        $total = count($active);
        $pending = count(array_filter($active, function($a) {
            return in_array($a['status'] ?? '', array('pendiente', 'visto'));
        }));

        if ($pending > 0) {
            echo sprintf(
                '<strong style="color: #0073aa;">%d</strong> <span style="color: #f0ad4e;">(%d nuevos)</span>',
                $total,
                $pending
            );
        } else {
            echo sprintf('<span>%d</span>', $total);
        }
    }
}

// Inicializar
ApplicantsMetabox::init();
