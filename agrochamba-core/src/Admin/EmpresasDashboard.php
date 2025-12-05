<?php
/**
 * Dashboard de Empresas
 * 
 * Panel de administración personalizado para gestionar empresas
 * con estadísticas y acceso rápido.
 *
 * @package AgroChamba\Admin
 */

namespace AgroChamba\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class EmpresasDashboard
{
    /**
     * Inicializar el dashboard
     */
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_menu_page'], 5);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_styles']);
    }

    /**
     * Agregar página de menú principal
     * El CPT "Empresas" ya está en el menú principal, solo agregamos el Dashboard como submenú
     */
    public static function add_menu_page(): void
    {
        // Submenú: Dashboard (aparece primero, antes de "Todas las Empresas")
        // Usamos un hook más temprano para asegurar que se agregue antes que los submenús automáticos del CPT
        global $submenu;
        
        add_submenu_page(
            'edit.php?post_type=empresa', // Parent: CPT Empresas
            'Dashboard',
            'Dashboard',
            'edit_posts',
            'agrochamba-empresas-dashboard',
            [self::class, 'render_dashboard']
        );
        
        // Reordenar el submenú para que Dashboard aparezca primero
        add_filter('custom_menu_order', '__return_true');
        add_filter('menu_order', [self::class, 'reorder_empresas_submenu']);
    }
    
    /**
     * Reordenar submenú de Empresas para que Dashboard aparezca primero
     */
    public static function reorder_empresas_submenu($menu_order): array
    {
        global $submenu;
        
        if (isset($submenu['edit.php?post_type=empresa'])) {
            $empresas_submenu = $submenu['edit.php?post_type=empresa'];
            
            // Buscar Dashboard y moverlo al principio
            foreach ($empresas_submenu as $key => $item) {
                if (isset($item[2]) && $item[2] === 'agrochamba-empresas-dashboard') {
                    $dashboard_item = $empresas_submenu[$key];
                    unset($empresas_submenu[$key]);
                    array_unshift($empresas_submenu, $dashboard_item);
                    $submenu['edit.php?post_type=empresa'] = array_values($empresas_submenu);
                    break;
                }
            }
        }
        
        return $menu_order;
    }

    /**
     * Renderizar el dashboard
     */
    public static function render_dashboard(): void
    {
        // Obtener todas las empresas
        $empresas = get_posts([
            'post_type' => 'empresa',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => ['publish', 'draft', 'pending'],
        ]);
        
        $total_empresas = count($empresas);
        
        ?>
        <div class="wrap agrochamba-empresas-admin">
            <!-- Header -->
            <div class="agrochamba-header-section">
                <h1 class="agrochamba-main-title">Empresas</h1>
                <p class="agrochamba-subtitle">Lista de todas las empresas registradas en la plataforma.</p>
            </div>

            <!-- Card Principal -->
            <div class="agrochamba-main-card">
                <!-- Título de la sección con badge -->
                <div class="agrochamba-section-header">
                    <h2 class="agrochamba-section-title">
                        Empresas
                        <span class="agrochamba-badge"><?php echo esc_html($total_empresas); ?></span>
                    </h2>
                    <p class="agrochamba-section-subtitle">Listado de todas las empresas registradas en la plataforma.</p>
                </div>

                <!-- Botón Nueva Empresa -->
                <div class="agrochamba-new-empresa-wrapper">
                    <a href="<?php echo admin_url('post-new.php?post_type=empresa'); ?>" class="agrochamba-btn-new-empresa">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Nueva Empresa
                    </a>
                </div>

                <!-- Barra de búsqueda -->
                <div class="agrochamba-search-wrapper">
                    <div class="agrochamba-search-box">
                        <span class="dashicons dashicons-search"></span>
                        <input type="text" id="agrochamba-search-input" 
                               placeholder="Buscar por nombre de empresa, email o contacto..." 
                               class="agrochamba-search-input" />
                    </div>
                </div>

                <!-- Tabla de empresas -->
                <div class="agrochamba-table-wrapper">
                    <table class="agrochamba-empresas-table">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Contacto</th>
                                <th>Sector</th>
                                <th>Ubicación</th>
                                <th>Ofertas</th>
                                <th>Verificación</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="agrochamba-empresas-tbody">
                            <?php if (empty($empresas)): ?>
                                <tr>
                                    <td colspan="8" class="agrochamba-empty-state">
                                        <p>No hay empresas registradas aún.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($empresas as $empresa): ?>
                                    <?php
                                    $user_id = get_post_meta($empresa->ID, '_empresa_user_id', true);
                                    $user = $user_id ? get_userdata($user_id) : null;
                                    $email = $user ? $user->user_email : '';
                                    $contacto_email = get_post_meta($empresa->ID, '_empresa_email_contacto', true) ?: $email;
                                    $contacto_telefono = get_post_meta($empresa->ID, '_empresa_telefono', true);
                                    $contacto_celular = get_post_meta($empresa->ID, '_empresa_celular', true);
                                    $sector = get_post_meta($empresa->ID, '_empresa_sector', true);
                                    $ciudad = get_post_meta($empresa->ID, '_empresa_ciudad', true);
                                    $provincia = get_post_meta($empresa->ID, '_empresa_provincia', true);
                                    $ubicacion_completa = trim(($ciudad ? $ciudad : '') . ($provincia ? ', ' . $provincia : ''));
                                    $verificada = get_post_meta($empresa->ID, '_empresa_verificada', true) === '1';
                                    $fecha_registro = get_the_date('d/m/Y', $empresa->ID);
                                    $empresa_id_short = substr(md5($empresa->ID), 0, 8);
                                    
                                    // Contar ofertas activas
                                    $ofertas_count = agrochamba_get_empresa_ofertas_count($empresa->ID);
                                    
                                    // Obtener nombre comercial o título
                                    $nombre_comercial = get_post_meta($empresa->ID, '_empresa_nombre_comercial', true) ?: $empresa->post_title;
                                    ?>
                                    <tr class="agrochamba-empresa-row" data-empresa-id="<?php echo esc_attr($empresa->ID); ?>" 
                                        data-empresa-nombre="<?php echo esc_attr(strtolower($nombre_comercial)); ?>"
                                        data-contacto="<?php echo esc_attr(strtolower($contacto_email . ' ' . $contacto_telefono . ' ' . $contacto_celular)); ?>">
                                        <td class="agrochamba-empresa-cell">
                                            <div class="agrochamba-empresa-info">
                                                <?php 
                                                $logo_id = get_post_thumbnail_id($empresa->ID);
                                                $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : null;
                                                ?>
                                                <?php if ($logo_url): ?>
                                                    <img src="<?php echo esc_url($logo_url); ?>" 
                                                         alt="<?php echo esc_attr($nombre_comercial); ?>" 
                                                         class="agrochamba-empresa-logo" />
                                                <?php else: ?>
                                                    <span class="dashicons dashicons-building agrochamba-empresa-icon"></span>
                                                <?php endif; ?>
                                                <div>
                                                    <strong class="agrochamba-empresa-name"><?php echo esc_html($nombre_comercial); ?></strong>
                                                    <span class="agrochamba-empresa-id">ID: <?php echo esc_html($empresa_id_short); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="agrochamba-contacto-cell">
                                            <div class="agrochamba-contacto-info">
                                                <?php if ($contacto_email): ?>
                                                    <div class="agrochamba-contacto-item">
                                                        <span class="dashicons dashicons-email-alt"></span>
                                                        <span><?php echo esc_html($contacto_email); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($contacto_telefono): ?>
                                                    <div class="agrochamba-contacto-item">
                                                        <span class="dashicons dashicons-phone"></span>
                                                        <span><?php echo esc_html($contacto_telefono); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($contacto_celular): ?>
                                                    <div class="agrochamba-contacto-item">
                                                        <span class="dashicons dashicons-smartphone"></span>
                                                        <span><?php echo esc_html($contacto_celular); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!$contacto_email && !$contacto_telefono && !$contacto_celular): ?>
                                                    <span class="agrochamba-empty-field">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="agrochamba-sector-cell">
                                            <?php echo $sector ? esc_html($sector) : '<span class="agrochamba-empty-field">—</span>'; ?>
                                        </td>
                                        <td class="agrochamba-ubicacion-cell">
                                            <?php echo $ubicacion_completa ? esc_html($ubicacion_completa) : '<span class="agrochamba-empty-field">—</span>'; ?>
                                        </td>
                                        <td class="agrochamba-ofertas-cell">
                                            <span class="agrochamba-ofertas-count"><?php echo esc_html($ofertas_count); ?></span>
                                        </td>
                                        <td class="agrochamba-verificacion-cell">
                                            <?php if ($verificada): ?>
                                                <span class="agrochamba-badge-verificada">
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                    Verificada
                                                </span>
                                            <?php else: ?>
                                                <span class="agrochamba-badge-sin-verificar">
                                                    <span class="dashicons dashicons-warning"></span>
                                                    Sin verificar
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="agrochamba-registro-cell">
                                            <?php echo esc_html($fecha_registro); ?>
                                        </td>
                                        <td class="agrochamba-acciones-cell">
                                            <div class="agrochamba-acciones-buttons">
                                                <a href="<?php echo admin_url('edit.php?post_type=trabajo&meta_key=empresa_id&meta_value=' . $empresa->ID); ?>" 
                                                   class="agrochamba-btn-accion agrochamba-btn-ofertas" 
                                                   title="Ver ofertas">
                                                    <span class="dashicons dashicons-external"></span>
                                                    Ofertas
                                                </a>
                                                <?php if (!$verificada): ?>
                                                    <button type="button" 
                                                            class="agrochamba-btn-accion agrochamba-btn-verificar" 
                                                            data-empresa-id="<?php echo esc_attr($empresa->ID); ?>"
                                                            title="Verificar empresa">
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        Verificar
                                                    </button>
                                                <?php endif; ?>
                                                <a href="<?php echo esc_url(get_edit_post_link($empresa->ID)); ?>" 
                                                   class="agrochamba-btn-accion agrochamba-btn-editar" 
                                                   title="Editar empresa">
                                                    <span class="dashicons dashicons-edit"></span>
                                                    Editar
                                                </a>
                                                <button type="button" 
                                                        class="agrochamba-btn-accion agrochamba-btn-eliminar" 
                                                        data-empresa-id="<?php echo esc_attr($empresa->ID); ?>"
                                                        data-empresa-nombre="<?php echo esc_attr($nombre_comercial); ?>"
                                                        title="Eliminar empresa">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener estadísticas
     */
    private static function get_statistics(): array
    {
        // Total empresas
        $total_empresas = wp_count_posts('empresa');
        $total = $total_empresas->publish ?? 0;

        // Empresas activas (publicadas)
        $activas = $total;

        // Empresas con usuario asociado
        $con_usuario = get_posts([
            'post_type' => 'empresa',
            'meta_key' => '_empresa_user_id',
            'meta_compare' => 'EXISTS',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        $con_usuario_count = count($con_usuario);

        // Nuevas empresas en últimos 7 días
        $nuevas_7d = get_posts([
            'post_type' => 'empresa',
            'date_query' => [
                [
                    'after' => '7 days ago',
                ],
            ],
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        $nuevas_7d_count = count($nuevas_7d);

        // Total ofertas de trabajo
        $total_ofertas = wp_count_posts('trabajo');
        $ofertas_count = ($total_ofertas->publish ?? 0) + ($total_ofertas->draft ?? 0);

        // Usuarios con rol employer
        $usuarios_employer = count_users();
        $employer_count = $usuarios_employer['avail_roles']['employer'] ?? 0;

        return [
            'total_empresas' => $total,
            'empresas_activas' => $activas,
            'con_usuario' => $con_usuario_count,
            'nuevas_7d' => $nuevas_7d_count,
            'total_ofertas' => $ofertas_count,
            'usuarios_employer' => $employer_count,
        ];
    }


    /**
     * Enqueue estilos CSS
     */
    public static function enqueue_styles($hook): void
    {
        // Verificar si estamos en la página del dashboard o en la lista de empresas
        if ($hook !== 'empresas_page_agrochamba-empresas-dashboard' && 
            $hook !== 'edit.php' && 
            (isset($_GET['post_type']) && $_GET['post_type'] !== 'empresa')) {
            return;
        }

        ?>
        <style>
        .agrochamba-empresas-admin {
            max-width: 1600px;
            margin: 0;
        }

        .agrochamba-header-section {
            margin-bottom: 30px;
        }

        .agrochamba-main-title {
            font-size: 32px;
            font-weight: 700;
            color: #1a5f2f;
            margin: 0 0 8px 0;
            line-height: 1.2;
        }

        .agrochamba-subtitle {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .agrochamba-main-card {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .agrochamba-section-header {
            margin-bottom: 25px;
        }

        .agrochamba-section-title {
            font-size: 20px;
            font-weight: 600;
            color: #1d2327;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .agrochamba-badge {
            background: #00a32a;
            color: #fff;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }

        .agrochamba-section-subtitle {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .agrochamba-new-empresa-wrapper {
            margin-bottom: 25px;
            text-align: center;
        }

        .agrochamba-btn-new-empresa {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #00a32a;
            color: #fff;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: background 0.2s;
        }

        .agrochamba-btn-new-empresa:hover {
            background: #008a20;
            color: #fff;
        }

        .agrochamba-btn-new-empresa .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        .agrochamba-search-wrapper {
            margin-bottom: 25px;
        }

        .agrochamba-search-box {
            position: relative;
            max-width: 600px;
        }

        .agrochamba-search-box .dashicons {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        .agrochamba-search-input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .agrochamba-search-input:focus {
            outline: none;
            border-color: #00a32a;
            box-shadow: 0 0 0 3px rgba(0, 163, 42, 0.1);
        }

        .agrochamba-table-wrapper {
            overflow-x: auto;
        }

        .agrochamba-empresas-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        .agrochamba-empresas-table thead {
            background: #f8f9fa;
        }

        .agrochamba-empresas-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #1d2327;
            border-bottom: 2px solid #e0e0e0;
        }

        .agrochamba-empresas-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .agrochamba-empresas-table tbody tr:hover {
            background: #f8f9fa;
        }

        .agrochamba-empresa-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .agrochamba-empresa-logo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .agrochamba-empresa-icon {
            font-size: 24px;
            width: 24px;
            height: 24px;
            color: #666;
        }

        .agrochamba-empresa-name {
            display: block;
            font-size: 15px;
            color: #1d2327;
            margin-bottom: 4px;
        }

        .agrochamba-empresa-id {
            display: block;
            font-size: 12px;
            color: #8b7355;
            background: #f5f3f0;
            padding: 2px 8px;
            border-radius: 4px;
            width: fit-content;
        }

        .agrochamba-contacto-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .agrochamba-contacto-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #666;
        }

        .agrochamba-contacto-item .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            color: #666;
        }

        .agrochamba-sector-cell,
        .agrochamba-ubicacion-cell {
            font-size: 14px;
            color: #666;
        }

        .agrochamba-ofertas-cell {
            text-align: center;
        }

        .agrochamba-ofertas-count {
            display: inline-block;
            background: #e7f3ff;
            color: #0066cc;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 13px;
        }

        .agrochamba-empty-field {
            color: #999;
        }

        .agrochamba-badge-verificada {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #00a32a;
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .agrochamba-badge-sin-verificar {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #ff9800;
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .agrochamba-badge-verificada .dashicons,
        .agrochamba-badge-sin-verificar .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }

        .agrochamba-registro-cell {
            font-size: 14px;
            color: #666;
        }

        .agrochamba-acciones-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .agrochamba-btn-accion {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
            color: #666;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .agrochamba-btn-accion:hover {
            background: #f5f5f5;
            border-color: #999;
        }

        .agrochamba-btn-accion .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        .agrochamba-btn-ofertas {
            border-color: #2271b1;
            color: #2271b1;
        }

        .agrochamba-btn-ofertas:hover {
            background: #2271b1;
            color: #fff;
        }

        .agrochamba-btn-verificar {
            border-color: #ff9800;
            color: #ff9800;
        }

        .agrochamba-btn-verificar:hover {
            background: #ff9800;
            color: #fff;
        }

        .agrochamba-btn-editar {
            border-color: #2271b1;
            color: #2271b1;
        }

        .agrochamba-btn-editar:hover {
            background: #2271b1;
            color: #fff;
        }

        .agrochamba-btn-eliminar {
            border-color: #d63638;
            color: #d63638;
            padding: 6px;
        }

        .agrochamba-btn-eliminar:hover {
            background: #d63638;
            color: #fff;
        }

        .agrochamba-empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        @media (max-width: 782px) {
            .agrochamba-empresas-table {
                font-size: 12px;
            }
            .agrochamba-empresas-table th,
            .agrochamba-empresas-table td {
                padding: 8px;
            }
            .agrochamba-acciones-buttons {
                flex-direction: column;
            }
            .agrochamba-btn-accion {
                width: 100%;
                justify-content: center;
            }
        }
        </style>
        <script>
        jQuery(document).ready(function($) {
            // Búsqueda en tiempo real
            $('#agrochamba-search-input').on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                $('.agrochamba-empresa-row').each(function() {
                    var empresaName = $(this).data('empresa-nombre') || '';
                    var contacto = $(this).data('contacto') || '';
                    if (empresaName.indexOf(searchTerm) > -1 || contacto.indexOf(searchTerm) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Verificar empresa
            $('.agrochamba-btn-verificar').on('click', function() {
                var empresaId = $(this).data('empresa-id');
                if (confirm('¿Estás seguro de que deseas verificar esta empresa?')) {
                    // Aquí puedes agregar la llamada AJAX para verificar
                    alert('Función de verificación próximamente disponible');
                }
            });

            // Eliminar empresa
            $('.agrochamba-btn-eliminar').on('click', function() {
                var empresaId = $(this).data('empresa-id');
                var empresaNombre = $(this).data('empresa-nombre');
                if (confirm('¿Estás seguro de que deseas eliminar la empresa "' + empresaNombre + '"? Esta acción no se puede deshacer.')) {
                    // Aquí puedes agregar la llamada AJAX para eliminar
                    alert('Función de eliminación próximamente disponible');
                }
            });
        });
        </script>
        <?php
    }
}

