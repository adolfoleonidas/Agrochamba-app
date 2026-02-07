<?php
/**
 * Dashboard de Postulaciones
 *
 * Panel de administracion para ver y gestionar todas las postulaciones
 * de trabajadores a ofertas de trabajo.
 *
 * @package AgroChamba\Admin
 * @since 2.1.0
 */

namespace AgroChamba\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class ApplicationsDashboard
{
    const JOB_META_KEY = '_job_applicants';
    const USER_META_KEY = 'job_applications';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_menu_page']);
        add_action('wp_ajax_agrochamba_dashboard_update_applicant', [self::class, 'ajax_update_status']);
    }

    public static function add_menu_page(): void
    {
        add_menu_page(
            'Postulaciones',
            'Postulaciones',
            'edit_posts',
            'agrochamba-postulaciones',
            [self::class, 'render_dashboard'],
            'dashicons-groups',
            27
        );
    }

    /**
     * Obtener todas las postulaciones de todos los trabajos
     */
    private static function get_all_applications(): array
    {
        $jobs = get_posts([
            'post_type'      => 'trabajo',
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'draft', 'pending'],
        ]);

        $applications = [];

        foreach ($jobs as $job) {
            $applicants = get_post_meta($job->ID, self::JOB_META_KEY, true);
            if (!is_array($applicants) || empty($applicants)) {
                continue;
            }

            foreach ($applicants as $user_id => $data) {
                $status = $data['status'] ?? 'pendiente';
                if ($status === 'cancelado') {
                    continue;
                }

                $user = get_userdata($user_id);
                if (!$user) {
                    continue;
                }

                $applications[] = [
                    'job_id'     => $job->ID,
                    'job_title'  => $job->post_title,
                    'job_status' => $job->post_status,
                    'user_id'    => $user_id,
                    'user_name'  => $user->display_name,
                    'user_email' => $user->user_email,
                    'phone'      => get_user_meta($user_id, 'phone', true),
                    'dni'        => get_user_meta($user_id, 'dni', true),
                    'photo'      => get_user_meta($user_id, 'profile_photo_url', true),
                    'status'     => $status,
                    'message'    => $data['message'] ?? '',
                    'applied_at' => $data['applied_at'] ?? '',
                    'viewed_at'  => $data['viewed_at'] ?? '',
                ];
            }
        }

        // Ordenar por fecha mas reciente
        usort($applications, function ($a, $b) {
            return strtotime($b['applied_at'] ?: '0') - strtotime($a['applied_at'] ?: '0');
        });

        return $applications;
    }

    private static function get_stats(array $applications): array
    {
        $stats = [
            'total'      => count($applications),
            'pending'    => 0,
            'viewed'     => 0,
            'in_process' => 0,
            'interview'  => 0,
            'finalist'   => 0,
            'accepted'   => 0,
            'rejected'   => 0,
        ];

        foreach ($applications as $app) {
            switch ($app['status']) {
                case 'pendiente':
                    $stats['pending']++;
                    break;
                case 'visto':
                    $stats['viewed']++;
                    break;
                case 'en_proceso':
                    $stats['in_process']++;
                    break;
                case 'entrevista':
                    $stats['interview']++;
                    break;
                case 'finalista':
                    $stats['finalist']++;
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

    private static function get_status_badge(string $status): string
    {
        $map = [
            'pendiente'  => ['label' => 'Postulado',       'class' => 'pending'],
            'visto'      => ['label' => 'CV Visto',         'class' => 'viewed'],
            'en_proceso' => ['label' => 'En Proceso',       'class' => 'in-process'],
            'entrevista' => ['label' => 'Entrevista',       'class' => 'interview'],
            'finalista'  => ['label' => 'Finalista',        'class' => 'finalist'],
            'aceptado'   => ['label' => 'Contratado',       'class' => 'accepted'],
            'rechazado'  => ['label' => 'No Seleccionado',  'class' => 'rejected'],
        ];
        $info = $map[$status] ?? ['label' => $status, 'class' => 'pending'];
        return sprintf(
            '<span class="agro-app-badge agro-app-badge--%s">%s</span>',
            esc_attr($info['class']),
            esc_html($info['label'])
        );
    }

    public static function render_dashboard(): void
    {
        $applications = self::get_all_applications();
        $stats = self::get_stats($applications);
        $filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        if ($filter) {
            $applications = array_filter($applications, function ($a) use ($filter) {
                return $a['status'] === $filter;
            });
        }

        ?>
        <div class="wrap agro-app-admin">
            <div class="agro-app-header">
                <h1 class="agro-app-title">Postulaciones</h1>
                <p class="agro-app-subtitle">Gestiona todas las postulaciones de trabajadores a ofertas de trabajo.</p>
            </div>

            <!-- Estadisticas -->
            <div class="agro-app-stats">
                <a href="<?php echo esc_url(admin_url('admin.php?page=agrochamba-postulaciones')); ?>"
                   class="agro-app-stat <?php echo !$filter ? 'agro-app-stat--active' : ''; ?>">
                    <span class="agro-app-stat__number"><?php echo esc_html($stats['total']); ?></span>
                    <span class="agro-app-stat__label">Total</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agrochamba-postulaciones&status=pendiente')); ?>"
                   class="agro-app-stat agro-app-stat--pending <?php echo $filter === 'pendiente' ? 'agro-app-stat--active' : ''; ?>">
                    <span class="agro-app-stat__number"><?php echo esc_html($stats['pending']); ?></span>
                    <span class="agro-app-stat__label">Postulados</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agrochamba-postulaciones&status=visto')); ?>"
                   class="agro-app-stat agro-app-stat--viewed <?php echo $filter === 'visto' ? 'agro-app-stat--active' : ''; ?>">
                    <span class="agro-app-stat__number"><?php echo esc_html($stats['viewed']); ?></span>
                    <span class="agro-app-stat__label">CV Vistos</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agrochamba-postulaciones&status=en_proceso')); ?>"
                   class="agro-app-stat agro-app-stat--in-process <?php echo $filter === 'en_proceso' ? 'agro-app-stat--active' : ''; ?>">
                    <span class="agro-app-stat__number"><?php echo esc_html($stats['in_process']); ?></span>
                    <span class="agro-app-stat__label">En Proceso</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agrochamba-postulaciones&status=entrevista')); ?>"
                   class="agro-app-stat agro-app-stat--interview <?php echo $filter === 'entrevista' ? 'agro-app-stat--active' : ''; ?>">
                    <span class="agro-app-stat__number"><?php echo esc_html($stats['interview']); ?></span>
                    <span class="agro-app-stat__label">Entrevista</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agrochamba-postulaciones&status=finalista')); ?>"
                   class="agro-app-stat agro-app-stat--finalist <?php echo $filter === 'finalista' ? 'agro-app-stat--active' : ''; ?>">
                    <span class="agro-app-stat__number"><?php echo esc_html($stats['finalist']); ?></span>
                    <span class="agro-app-stat__label">Finalistas</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agrochamba-postulaciones&status=aceptado')); ?>"
                   class="agro-app-stat agro-app-stat--accepted <?php echo $filter === 'aceptado' ? 'agro-app-stat--active' : ''; ?>">
                    <span class="agro-app-stat__number"><?php echo esc_html($stats['accepted']); ?></span>
                    <span class="agro-app-stat__label">Contratados</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agrochamba-postulaciones&status=rechazado')); ?>"
                   class="agro-app-stat agro-app-stat--rejected <?php echo $filter === 'rechazado' ? 'agro-app-stat--active' : ''; ?>">
                    <span class="agro-app-stat__number"><?php echo esc_html($stats['rejected']); ?></span>
                    <span class="agro-app-stat__label">No Seleccionados</span>
                </a>
            </div>

            <!-- Card principal -->
            <div class="agro-app-card">
                <div class="agro-app-card__header">
                    <h2>
                        <?php if ($filter): ?>
                            Postulaciones: <?php echo esc_html(ucfirst($filter)); ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=agrochamba-postulaciones')); ?>" class="agro-app-clear-filter">Ver todas</a>
                        <?php else: ?>
                            Todas las postulaciones
                            <span class="agro-app-badge agro-app-badge--count"><?php echo esc_html(count($applications)); ?></span>
                        <?php endif; ?>
                    </h2>
                </div>

                <!-- Busqueda -->
                <div class="agro-app-search">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" id="agro-app-search" placeholder="Buscar por nombre, trabajo, email..." />
                </div>

                <!-- Tabla -->
                <div class="agro-app-table-wrap">
                    <table class="agro-app-table">
                        <thead>
                            <tr>
                                <th>Postulante</th>
                                <th>Trabajo</th>
                                <th>Contacto</th>
                                <th>Mensaje</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="agro-app-tbody">
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="7" class="agro-app-empty">
                                        <span class="dashicons dashicons-groups"></span>
                                        <p>No hay postulaciones<?php echo $filter ? ' con estado "' . esc_html($filter) . '"' : ''; ?>.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $app): ?>
                                    <tr class="agro-app-row status-<?php echo esc_attr($app['status']); ?>"
                                        data-search="<?php echo esc_attr(strtolower($app['user_name'] . ' ' . $app['job_title'] . ' ' . $app['user_email'] . ' ' . $app['phone'])); ?>"
                                        data-job-id="<?php echo esc_attr($app['job_id']); ?>"
                                        data-user-id="<?php echo esc_attr($app['user_id']); ?>">
                                        <td class="agro-app-cell-user">
                                            <div class="agro-app-user">
                                                <?php if ($app['photo']): ?>
                                                    <img src="<?php echo esc_url($app['photo']); ?>" alt="" class="agro-app-avatar" />
                                                <?php else: ?>
                                                    <span class="dashicons dashicons-admin-users agro-app-avatar-icon"></span>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo esc_html($app['user_name']); ?></strong>
                                                    <?php if ($app['dni']): ?>
                                                        <small>DNI: <?php echo esc_html($app['dni']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="agro-app-cell-job">
                                            <a href="<?php echo esc_url(get_edit_post_link($app['job_id'])); ?>">
                                                <?php echo esc_html(wp_trim_words($app['job_title'], 8)); ?>
                                            </a>
                                            <?php if ($app['job_status'] !== 'publish'): ?>
                                                <small class="agro-app-job-status">(<?php echo esc_html($app['job_status']); ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="agro-app-cell-contact">
                                            <a href="mailto:<?php echo esc_attr($app['user_email']); ?>" title="Email">
                                                <span class="dashicons dashicons-email-alt"></span>
                                                <?php echo esc_html($app['user_email']); ?>
                                            </a>
                                            <?php if ($app['phone']): ?>
                                                <br>
                                                <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $app['phone'])); ?>" target="_blank" title="WhatsApp">
                                                    <span class="dashicons dashicons-phone"></span>
                                                    <?php echo esc_html($app['phone']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="agro-app-cell-message">
                                            <?php if ($app['message']): ?>
                                                <em>"<?php echo esc_html(wp_trim_words($app['message'], 12)); ?>"</em>
                                            <?php else: ?>
                                                <span class="agro-app-muted">Sin mensaje</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="agro-app-cell-date">
                                            <?php echo $app['applied_at'] ? esc_html(date_i18n('d/m/Y H:i', strtotime($app['applied_at']))) : '-'; ?>
                                        </td>
                                        <td class="agro-app-cell-status">
                                            <?php echo self::get_status_badge($app['status']); ?>
                                        </td>
                                        <td class="agro-app-cell-actions">
                                            <?php
                                            $transitions = \AgroChamba\API\Applications\ApplicationsController::get_allowed_transitions($app['status']);
                                            // Remove 'cancelado' from employer actions (only user can cancel)
                                            $transitions = array_filter($transitions, function($t) { return $t !== 'cancelado'; });
                                            if (!empty($transitions)): ?>
                                                <div class="agro-app-dropdown">
                                                    <button type="button" class="agro-app-btn agro-app-dropdown__toggle" title="Acciones">
                                                        <span class="dashicons dashicons-ellipsis"></span>
                                                    </button>
                                                    <div class="agro-app-dropdown__menu">
                                                        <?php foreach ($transitions as $next_status):
                                                            $label = \AgroChamba\API\Applications\ApplicationsController::get_status_label($next_status);
                                                            $css_class = $next_status === 'rechazado' ? 'agro-app-dropdown__item--danger' : '';
                                                        ?>
                                                            <button type="button" class="agro-app-dropdown__item <?php echo $css_class; ?>" data-action="<?php echo esc_attr($next_status); ?>">
                                                                <?php echo esc_html($label); ?>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="agro-app-muted">--</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
        .agro-app-admin { max-width: 1600px; }
        .agro-app-header { margin-bottom: 24px; }
        .agro-app-title { font-size: 32px; font-weight: 700; color: #1a5f2f; margin: 0 0 6px; }
        .agro-app-subtitle { font-size: 14px; color: #666; margin: 0; }

        /* Stats */
        .agro-app-stats { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        .agro-app-stat {
            flex: 1; min-width: 120px; text-align: center; padding: 16px 12px;
            background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08);
            text-decoration: none; color: inherit; border: 2px solid transparent; transition: all .2s;
        }
        .agro-app-stat:hover { border-color: #00a32a; color: inherit; }
        .agro-app-stat--active { border-color: #00a32a; background: #f0fdf4; }
        .agro-app-stat__number { display: block; font-size: 28px; font-weight: 700; }
        .agro-app-stat__label { font-size: 13px; color: #666; }
        .agro-app-stat--pending .agro-app-stat__number { color: #FFA000; }
        .agro-app-stat--viewed .agro-app-stat__number { color: #2196F3; }
        .agro-app-stat--in-process .agro-app-stat__number { color: #283593; }
        .agro-app-stat--interview .agro-app-stat__number { color: #E65100; }
        .agro-app-stat--finalist .agro-app-stat__number { color: #00695C; }
        .agro-app-stat--accepted .agro-app-stat__number { color: #4CAF50; }
        .agro-app-stat--rejected .agro-app-stat__number { color: #F44336; }

        /* Card */
        .agro-app-card { background: #fff; border-radius: 8px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .agro-app-card__header { margin-bottom: 20px; }
        .agro-app-card__header h2 { font-size: 18px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 10px; }
        .agro-app-clear-filter { font-size: 13px; font-weight: 400; text-decoration: none; }

        /* Badge */
        .agro-app-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .agro-app-badge--count { background: #00a32a; color: #fff; }
        .agro-app-badge--pending { background: #FFF3E0; color: #E65100; }
        .agro-app-badge--viewed { background: #E3F2FD; color: #1565C0; }
        .agro-app-badge--in-process { background: #E8EAF6; color: #283593; }
        .agro-app-badge--interview { background: #FBE9E7; color: #BF360C; }
        .agro-app-badge--finalist { background: #E0F2F1; color: #00695C; }
        .agro-app-badge--accepted { background: #E8F5E9; color: #2E7D32; }
        .agro-app-badge--rejected { background: #FFEBEE; color: #C62828; }

        /* Search */
        .agro-app-search { position: relative; max-width: 500px; margin-bottom: 20px; }
        .agro-app-search .dashicons { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; }
        .agro-app-search input {
            width: 100%; padding: 10px 10px 10px 38px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;
        }
        .agro-app-search input:focus { outline: none; border-color: #00a32a; box-shadow: 0 0 0 2px rgba(0,163,42,.15); }

        /* Table */
        .agro-app-table-wrap { overflow-x: auto; }
        .agro-app-table { width: 100%; border-collapse: collapse; }
        .agro-app-table thead { background: #f8f9fa; }
        .agro-app-table th { padding: 12px 14px; text-align: left; font-weight: 600; font-size: 13px; border-bottom: 2px solid #e0e0e0; white-space: nowrap; }
        .agro-app-table td { padding: 14px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; font-size: 13px; }
        .agro-app-table tbody tr:hover { background: #fafafa; }

        /* Row status colors */
        .agro-app-row.status-aceptado { background: #f0fff0; }
        .agro-app-row.status-rechazado { background: #fff5f5; opacity: .75; }
        .agro-app-row.status-en_proceso { background: #f5f5ff; }
        .agro-app-row.status-entrevista { background: #fff8f0; }
        .agro-app-row.status-finalista { background: #f0fffc; }

        /* User cell */
        .agro-app-user { display: flex; align-items: center; gap: 10px; }
        .agro-app-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .agro-app-avatar-icon { width: 36px; height: 36px; font-size: 28px; color: #999; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .agro-app-user strong { display: block; font-size: 14px; }
        .agro-app-user small { color: #888; }

        /* Job cell */
        .agro-app-cell-job a { text-decoration: none; color: #0073aa; font-weight: 500; }
        .agro-app-cell-job a:hover { text-decoration: underline; }
        .agro-app-job-status { color: #999; font-size: 11px; }

        /* Contact */
        .agro-app-cell-contact a { text-decoration: none; color: #666; font-size: 12px; }
        .agro-app-cell-contact a:hover { color: #0073aa; }
        .agro-app-cell-contact .dashicons { font-size: 14px; width: 14px; height: 14px; vertical-align: middle; margin-right: 2px; }

        /* Message */
        .agro-app-cell-message em { color: #555; font-size: 12px; }
        .agro-app-muted { color: #bbb; font-style: italic; font-size: 12px; }

        /* Date */
        .agro-app-cell-date { white-space: nowrap; color: #666; }

        /* Actions & Dropdown */
        .agro-app-cell-actions { white-space: nowrap; position: relative; }
        .agro-app-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border: 1px solid #ddd; border-radius: 4px;
            background: #fff; cursor: pointer; transition: all .15s;
        }
        .agro-app-btn .dashicons { font-size: 18px; width: 18px; height: 18px; }
        .agro-app-dropdown { position: relative; display: inline-block; }
        .agro-app-dropdown__toggle:hover { background: #f0f0f0; border-color: #999; }
        .agro-app-dropdown__menu {
            display: none; position: absolute; right: 0; top: 100%; z-index: 100;
            background: #fff; border: 1px solid #ddd; border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,.12); min-width: 160px; padding: 4px 0;
        }
        .agro-app-dropdown.open .agro-app-dropdown__menu { display: block; }
        .agro-app-dropdown__item {
            display: block; width: 100%; padding: 8px 16px; border: none; background: none;
            text-align: left; cursor: pointer; font-size: 13px; color: #333; transition: background .15s;
        }
        .agro-app-dropdown__item:hover { background: #f5f5f5; }
        .agro-app-dropdown__item--danger { color: #dc3232; }
        .agro-app-dropdown__item--danger:hover { background: #fff0f0; }

        /* Empty state */
        .agro-app-empty { text-align: center; padding: 50px 20px; }
        .agro-app-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #ddd; display: block; margin: 0 auto 10px; }
        .agro-app-empty p { color: #888; font-size: 15px; }

        @media (max-width: 782px) {
            .agro-app-stats { flex-direction: column; }
            .agro-app-stat { min-width: auto; }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Busqueda
            $('#agro-app-search').on('keyup', function() {
                var q = $(this).val().toLowerCase();
                $('.agro-app-row').each(function() {
                    var data = $(this).data('search') || '';
                    $(this).toggle(data.indexOf(q) > -1);
                });
            });

            // Toggle dropdown
            $(document).on('click', '.agro-app-dropdown__toggle', function(e) {
                e.stopPropagation();
                var dropdown = $(this).closest('.agro-app-dropdown');
                $('.agro-app-dropdown').not(dropdown).removeClass('open');
                dropdown.toggleClass('open');
            });
            $(document).on('click', function() { $('.agro-app-dropdown').removeClass('open'); });

            // Status labels for confirm dialog
            var statusLabels = {
                'en_proceso': 'EN PROCESO',
                'entrevista': 'ENTREVISTA',
                'finalista': 'FINALISTA',
                'aceptado': 'CONTRATADO',
                'rechazado': 'NO SELECCIONADO',
                'visto': 'CV VISTO'
            };

            // Dropdown action click
            $(document).on('click', '.agro-app-dropdown__item', function() {
                var btn = $(this);
                var row = btn.closest('tr');
                var newStatus = btn.data('action');
                var jobId = row.data('job-id');
                var userId = row.data('user-id');
                var label = statusLabels[newStatus] || newStatus;

                if (!confirm('¿Cambiar estado a ' + label + '?')) return;

                btn.prop('disabled', true).text('...');
                $('.agro-app-dropdown').removeClass('open');

                $.post(ajaxurl, {
                    action: 'agrochamba_dashboard_update_applicant',
                    nonce: '<?php echo wp_create_nonce('agrochamba_dashboard_applicant'); ?>',
                    job_id: jobId,
                    user_id: userId,
                    new_status: newStatus
                }, function(resp) {
                    if (resp.success) {
                        row.find('.agro-app-cell-status').html(resp.data.badge);
                        if (resp.data.actions_html) {
                            row.find('.agro-app-cell-actions').html(resp.data.actions_html);
                        } else {
                            row.find('.agro-app-cell-actions').html('<span class="agro-app-muted">--</span>');
                        }
                        row.attr('class', 'agro-app-row status-' + newStatus);
                    } else {
                        alert('Error: ' + (resp.data || 'Desconocido'));
                    }
                }).fail(function() {
                    alert('Error de conexion');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Actualizar estado desde el dashboard
     */
    public static function ajax_update_status(): void
    {
        check_ajax_referer('agrochamba_dashboard_applicant', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Sin permisos');
        }

        $job_id     = intval($_POST['job_id'] ?? 0);
        $user_id    = intval($_POST['user_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');

        $valid_statuses = \AgroChamba\API\Applications\ApplicationsController::get_all_statuses();
        if (!$job_id || !$user_id || !in_array($new_status, $valid_statuses)) {
            wp_send_json_error('Parametros invalidos');
        }

        // Actualizar en meta del trabajo
        $job_applicants = get_post_meta($job_id, self::JOB_META_KEY, true);
        if (!is_array($job_applicants) || !isset($job_applicants[$user_id])) {
            wp_send_json_error('Postulacion no encontrada');
        }

        $old_status = $job_applicants[$user_id]['status'];

        // Validar transición
        $allowed = \AgroChamba\API\Applications\ApplicationsController::get_allowed_transitions($old_status);
        if (!in_array($new_status, $allowed)) {
            wp_send_json_error('Transición no permitida de "' . $old_status . '" a "' . $new_status . '"');
        }

        $job_applicants[$user_id]['status']     = $new_status;
        $job_applicants[$user_id]['updated_at']  = current_time('mysql');
        update_post_meta($job_id, self::JOB_META_KEY, $job_applicants);

        // Actualizar en meta del usuario
        $user_apps = get_user_meta($user_id, self::USER_META_KEY, true);
        if (is_array($user_apps) && isset($user_apps[$job_id])) {
            $user_apps[$job_id]['status']     = $new_status;
            $user_apps[$job_id]['updated_at']  = current_time('mysql');
            update_user_meta($user_id, self::USER_META_KEY, $user_apps);
        }

        // Hook para notificaciones
        do_action('agrochamba_application_status_changed', $user_id, $job_id, $old_status, $new_status);

        // Build updated actions HTML
        $next_transitions = \AgroChamba\API\Applications\ApplicationsController::get_allowed_transitions($new_status);
        $next_transitions = array_filter($next_transitions, function($t) { return $t !== 'cancelado'; });
        $actions_html = '';
        if (!empty($next_transitions)) {
            $actions_html .= '<div class="agro-app-dropdown">';
            $actions_html .= '<button type="button" class="agro-app-btn agro-app-dropdown__toggle" title="Acciones"><span class="dashicons dashicons-ellipsis"></span></button>';
            $actions_html .= '<div class="agro-app-dropdown__menu">';
            foreach ($next_transitions as $next) {
                $label = \AgroChamba\API\Applications\ApplicationsController::get_status_label($next);
                $css = $next === 'rechazado' ? 'agro-app-dropdown__item--danger' : '';
                $actions_html .= '<button type="button" class="agro-app-dropdown__item ' . $css . '" data-action="' . esc_attr($next) . '">' . esc_html($label) . '</button>';
            }
            $actions_html .= '</div></div>';
        } else {
            $actions_html = '<span class="agro-app-muted">--</span>';
        }

        wp_send_json_success([
            'status'       => $new_status,
            'badge'        => self::get_status_badge($new_status),
            'actions_html' => $actions_html,
        ]);
    }
}
