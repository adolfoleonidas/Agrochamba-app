<?php
/**
 * Custom Post Type: Empresa
 * 
 * CPT para información extendida de empresas.
 * La información básica está en usuarios WordPress (rol employer),
 * y la información detallada/extendida está en este CPT.
 *
 * @package AgroChamba\PostTypes
 */

namespace AgroChamba\PostTypes;

if (!defined('ABSPATH')) {
    exit;
}

class EmpresaPostType
{
    /**
     * Registrar el CPT y sus hooks
     */
    public static function register(): void
    {
        add_action('init', [self::class, 'register_post_type']);
        add_action('add_meta_boxes', [self::class, 'add_meta_boxes']);
        add_action('save_post_empresa', [self::class, 'save_meta_boxes']);
        add_filter('manage_empresa_posts_columns', [self::class, 'add_custom_columns']);
        add_action('manage_empresa_posts_custom_column', [self::class, 'render_custom_columns'], 10, 2);
        add_action('rest_api_init', [self::class, 'register_rest_fields']);
        add_filter('single_template', [self::class, 'load_empresa_template']);
        
        // Restricciones de permisos para empresas
        add_action('admin_init', [self::class, 'restrict_empresa_access']);
        add_filter('parse_query', [self::class, 'filter_empresa_posts_by_user']);
        add_action('admin_menu', [self::class, 'add_mi_empresa_menu']);
        
        // Mostrar mensajes de éxito/error después del guardado
        add_action('admin_notices', [self::class, 'show_user_creation_notices']);
    }

    /**
     * Registrar el Custom Post Type
     */
    public static function register_post_type(): void
    {
        // Evitar registrar el CPT dos veces si ya está registrado
        if (post_type_exists('empresa')) {
            return;
        }
        
        $labels = [
            'name'                  => 'Empresas',
            'singular_name'         => 'Empresa',
            'menu_name'             => 'Empresas',
            'all_items'             => 'Todas las Empresas',
            'add_new'               => 'Agregar Nueva',
            'add_new_item'          => 'Agregar Nueva Empresa',
            'edit_item'             => 'Editar Empresa',
            'new_item'              => 'Nueva Empresa',
            'view_item'             => 'Ver Empresa',
            'view_items'            => 'Ver Empresas',
            'search_items'          => 'Buscar Empresas',
            'not_found'             => 'No se encontraron empresas',
            'not_found_in_trash'    => 'No hay empresas en la papelera',
            'featured_image'        => 'Logo de la Empresa',
            'set_featured_image'    => 'Establecer logo',
            'remove_featured_image' => 'Eliminar logo',
            'use_featured_image'    => 'Usar como logo',
            'archives'              => 'Archivo de Empresas',
            'insert_into_item'      => 'Insertar en empresa',
            'uploaded_to_this_item' => 'Subido a esta empresa',
            'filter_items_list'     => 'Filtrar lista de empresas',
            'items_list_navigation' => 'Navegación de empresas',
            'items_list'            => 'Lista de empresas',
        ];

        $args = [
            'labels'              => $labels,
            'description'         => 'Información extendida de empresas agrícolas',
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true, // Visible en el menú principal
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'rest_base'           => 'empresas',
            'menu_position'       => 4, // Posición en el menú (antes de Posts)
            'menu_icon'           => 'dashicons-building',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields'],
            'has_archive'         => true,
            'rewrite'             => [
                'slug'       => 'empresas',
                'with_front' => false,
            ],
            'query_var'           => true,
            'can_export'          => true,
            'delete_with_user'    => false,
        ];

        register_post_type('empresa', $args);
    }

    /**
     * Agregar meta boxes
     */
    public static function add_meta_boxes(): void
    {
        // Solo mostrar relación con usuario a administradores
        if (current_user_can('manage_options')) {
            add_meta_box(
                'empresa_user_relation',
                'Usuario WordPress Asociado',
                [self::class, 'render_user_relation_box'],
                'empresa',
                'side',
                'high'
            );
        }

        // Mensaje informativo para empresas (solo si es employer)
        $current_user = wp_get_current_user();
        if (in_array('employer', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            add_meta_box(
                'empresa_info_message',
                '📝 Información',
                [self::class, 'render_info_message_box'],
                'empresa',
                'normal',
                'high'
            );
        }

        add_meta_box(
            'empresa_logo_info',
            '📷 Logo de la Empresa',
            [self::class, 'render_logo_info_box'],
            'empresa',
            'side',
            'high'
        );

        add_meta_box(
            'empresa_basic_info',
            'Información Básica',
            [self::class, 'render_basic_info_box'],
            'empresa',
            'normal',
            'high'
        );

        add_meta_box(
            'empresa_detailed_info',
            'Información Detallada',
            [self::class, 'render_detailed_info_box'],
            'empresa',
            'normal',
            'high'
        );

        // Solo mostrar verificación a administradores
        if (current_user_can('manage_options')) {
            add_meta_box(
                'empresa_verification',
                'Verificación',
                [self::class, 'render_verification_box'],
                'empresa',
                'side',
                'high'
            );
        }
        
        // Meta box de estado y publicación (solo admin)
        if (current_user_can('manage_options')) {
            add_meta_box(
                'empresa_status',
                'Estado de Publicación',
                [self::class, 'render_status_box'],
                'empresa',
                'side',
                'high'
            );
        }

        add_meta_box(
            'empresa_contact_info',
            'Información de Contacto',
            [self::class, 'render_contact_info_box'],
            'empresa',
            'normal',
            'default'
        );

        add_meta_box(
            'empresa_social_media',
            'Redes Sociales',
            [self::class, 'render_social_media_box'],
            'empresa',
            'normal',
            'default'
        );
    }

    /**
     * Renderizar meta box de relación con usuario
     */
    public static function render_user_relation_box($post): void
    {
        wp_nonce_field('empresa_user_relation', 'empresa_user_relation_nonce');
        
        $user_id = get_post_meta($post->ID, '_empresa_user_id', true);
        $user = $user_id ? get_userdata($user_id) : null;
        
        // Obtener usuarios con rol employer que no tienen empresa asociada
        $args = [
            'role'    => 'employer',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ];
        $employers = get_users($args);
        
        ?>
        <div style="padding: 10px 0;">
            <p>
                <label for="empresa_user_mode">
                    <strong>Modo de asociación:</strong>
                </label>
            </p>
            <select name="empresa_user_mode" id="empresa_user_mode" style="width: 100%; margin-bottom: 15px;">
                <option value="existing" <?php selected(true, true); ?>>Seleccionar Usuario Existente</option>
                <option value="create" <?php selected($user_id, 0); ?>>Crear Nuevo Usuario</option>
            </select>

            <!-- Seleccionar usuario existente -->
            <div id="empresa-user-existing" class="empresa-user-mode-section">
                <p>
                    <label for="empresa_user_id">
                        <strong>Usuario WordPress:</strong>
                    </label>
                </p>
                <select name="empresa_user_id" id="empresa_user_id" style="width: 100%;">
                    <option value="">-- Seleccionar Usuario --</option>
                    <?php foreach ($employers as $employer): ?>
                        <?php
                        // Verificar si este usuario ya tiene una empresa asociada (excepto la actual)
                        $existing_empresa = get_posts([
                            'post_type'  => 'empresa',
                            'meta_key'   => '_empresa_user_id',
                            'meta_value' => $employer->ID,
                            'post__not_in' => [$post->ID],
                            'posts_per_page' => 1,
                        ]);
                        ?>
                        <?php if (empty($existing_empresa)): ?>
                            <option value="<?php echo esc_attr($employer->ID); ?>" <?php selected($user_id, $employer->ID); ?>>
                                <?php echo esc_html($employer->display_name . ' (' . $employer->user_email . ')'); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <?php if ($user): ?>
                    <p style="margin-top: 10px;">
                        <strong>Usuario actual:</strong><br>
                        <?php echo esc_html($user->display_name); ?><br>
                        <small><?php echo esc_html($user->user_email); ?></small>
                    </p>
                    <p>
                        <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $user_id)); ?>" target="_blank">
                            Editar usuario →
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Crear nuevo usuario -->
            <div id="empresa-user-create" class="empresa-user-mode-section" style="display: none;">
                <p style="background: #e7f3ff; padding: 10px; border-left: 4px solid #0066cc; margin-bottom: 15px;">
                    <strong>💡 Crear nuevo usuario:</strong><br>
                    Se creará un nuevo usuario con rol "Empresa" automáticamente.
                </p>
                <table class="form-table" style="margin-top: 0;">
                    <tr>
                        <th><label for="new_user_email">Email *</label></th>
                        <td>
                            <input type="email" name="new_user_email" id="new_user_email" 
                                   value="" class="regular-text" 
                                   placeholder="empresa@ejemplo.com" />
                            <p class="description">Email que se usará como nombre de usuario</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="new_user_password">Contraseña *</label></th>
                        <td>
                            <input type="text" name="new_user_password" id="new_user_password" 
                                   value="" class="regular-text" 
                                   placeholder="Generar automáticamente" />
                            <p class="description">Dejar vacío para generar contraseña automática</p>
                            <button type="button" class="button" id="generate-password-btn" style="margin-top: 5px;">
                                Generar Contraseña
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="new_user_display_name">Nombre a Mostrar</label></th>
                        <td>
                            <?php 
                            $nombre_comercial = get_post_meta($post->ID, '_empresa_nombre_comercial', true) ?: $post->post_title;
                            ?>
                            <input type="text" name="new_user_display_name" id="new_user_display_name" 
                                   value="<?php echo esc_attr($nombre_comercial); ?>" 
                                   class="regular-text" />
                            <p class="description">Nombre que aparecerá en el perfil</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            function toggleUserMode() {
                var mode = $('#empresa_user_mode').val();
                if (mode === 'create') {
                    $('#empresa-user-existing').hide();
                    $('#empresa-user-create').show();
                    $('#empresa_user_id').val('');
                } else {
                    $('#empresa-user-existing').show();
                    $('#empresa-user-create').hide();
                }
            }

            $('#empresa_user_mode').on('change', toggleUserMode);
            toggleUserMode();

            // Generar contraseña
            $('#generate-password-btn').on('click', function() {
                var password = Math.random().toString(36).slice(-12) + Math.random().toString(36).slice(-12).toUpperCase() + '!@#';
                $('#new_user_password').val(password);
            });
        });
        </script>
        <?php
    }

    /**
     * Renderizar mensaje informativo para empresas
     */
    public static function render_info_message_box($post): void
    {
        ?>
        <div style="background: #e7f3ff; border-left: 4px solid #0066cc; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <p style="margin: 0; color: #004085; line-height: 1.6;">
                <strong>💡 Completa tu perfil de empresa:</strong><br>
                Rellena todos los campos disponibles para que tu empresa tenga un perfil completo y atractivo. 
                Los campos marcados con <strong>*</strong> son obligatorios. 
                Tu información será visible en el perfil público de tu empresa.
            </p>
        </div>
        <?php
    }

    /**
     * Renderizar meta box de información del logo
     */
    public static function render_logo_info_box($post): void
    {
        $logo_id = get_post_thumbnail_id($post->ID);
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : null;
        
        ?>
        <div style="padding: 10px 0;">
            <p><strong>Logo de la Empresa</strong></p>
            <p class="description" style="margin-bottom: 15px;">
                El logo aparecerá en el perfil público de tu empresa y en todas tus ofertas de trabajo.
            </p>
            
            <?php if ($logo_url): ?>
                <div style="margin-bottom: 15px; text-align: center;">
                    <img src="<?php echo esc_url($logo_url); ?>" 
                         alt="Logo actual" 
                         style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px; padding: 5px; background: #fff;" />
                </div>
                <p style="text-align: center; color: #666; font-size: 12px;">
                    Logo actual establecido
                </p>
            <?php else: ?>
                <div style="background: #f5f5f5; border: 2px dashed #ddd; border-radius: 4px; padding: 30px; text-align: center; margin-bottom: 15px;">
                    <p style="margin: 0; color: #999;">
                        <span style="font-size: 32px;">📷</span><br>
                        No hay logo establecido
                    </p>
                </div>
            <?php endif; ?>
            
            <p class="description" style="margin-top: 15px;">
                <strong>Para cambiar el logo:</strong><br>
                1. Haz clic en "Imagen destacada" en el panel derecho<br>
                2. Selecciona o sube una nueva imagen<br>
                3. Guarda los cambios
            </p>
            
            <p class="description" style="margin-top: 10px; font-size: 12px; color: #666;">
                <strong>Recomendaciones:</strong><br>
                • Formato: JPG, PNG o WebP<br>
                • Tamaño recomendado: 400x400px mínimo<br>
                • Tamaño máximo: 5MB<br>
                • Fondo transparente preferible
            </p>
        </div>
        <?php
    }

    /**
     * Renderizar meta box de información básica
     */
    public static function render_basic_info_box($post): void
    {
        wp_nonce_field('empresa_basic_info', 'empresa_basic_info_nonce');
        
        $nombre_comercial = get_post_meta($post->ID, '_empresa_nombre_comercial', true);
        $ruc = get_post_meta($post->ID, '_empresa_ruc', true);
        $razon_social = get_post_meta($post->ID, '_empresa_razon_social', true);
        $descripcion = $post->post_content;
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="empresa_nombre_comercial">Nombre Comercial *</label></th>
                <td>
                    <input type="text" name="empresa_nombre_comercial" id="empresa_nombre_comercial" 
                           value="<?php echo esc_attr($nombre_comercial ?: $post->post_title); ?>" 
                           class="large-text" required />
                    <p class="description">Nombre con el que se conoce comercialmente la empresa</p>
                </td>
            </tr>
            <tr>
                <th><label for="empresa_ruc">RUC *</label></th>
                <td>
                    <input type="text" name="empresa_ruc" id="empresa_ruc" 
                           value="<?php echo esc_attr($ruc); ?>" class="regular-text" 
                           pattern="[0-9]{11}" maxlength="11" />
                    <p class="description">Registro Único de Contribuyente (11 dígitos)</p>
                </td>
            </tr>
            <tr>
                <th><label for="empresa_razon_social">Razón Social *</label></th>
                <td>
                    <input type="text" name="empresa_razon_social" id="empresa_razon_social" 
                           value="<?php echo esc_attr($razon_social); ?>" class="large-text" required />
                    <p class="description">Nombre legal de la empresa</p>
                </td>
            </tr>
            <tr>
                <th><label for="empresa_descripcion">Descripción</label></th>
                <td>
                    <?php
                    wp_editor($descripcion, 'empresa_descripcion', [
                        'textarea_name' => 'content',
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                    ]);
                    ?>
                    <p class="description">Descripción detallada de la empresa, su historia, misión, visión, etc.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar meta box de verificación
     */
    public static function render_verification_box($post): void
    {
        wp_nonce_field('empresa_verification', 'empresa_verification_nonce');
        
        $verificada = get_post_meta($post->ID, '_empresa_verificada', true);
        $fecha_verificacion = get_post_meta($post->ID, '_empresa_fecha_verificacion', true);
        $verificado_por = get_post_meta($post->ID, '_empresa_verificado_por', true);
        
        ?>
        <div style="padding: 10px 0;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                <input type="checkbox" name="empresa_verificada" value="1" 
                       <?php checked($verificada, '1'); ?> 
                       style="width: 18px; height: 18px;" />
                <strong>Empresa Verificada</strong>
            </label>
        </div>
        <p class="description" style="margin-top: 10px;">
            Las empresas verificadas muestran un badge de verificación en su perfil público.
        </p>
        <?php if ($verificada): ?>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                <p style="margin: 5px 0;">
                    <strong>Verificada el:</strong><br>
                    <span style="color: #666;">
                        <?php echo $fecha_verificacion ? esc_html(date_i18n('d/m/Y H:i', strtotime($fecha_verificacion))) : '—'; ?>
                    </span>
                </p>
                <?php if ($verificado_por): ?>
                    <?php $verificador = get_userdata($verificado_por); ?>
                    <p style="margin: 5px 0;">
                        <strong>Por:</strong><br>
                        <span style="color: #666;">
                            <?php echo $verificador ? esc_html($verificador->display_name) : '—'; ?>
                        </span>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Renderizar meta box de información detallada
     */
    public static function render_detailed_info_box($post): void
    {
        wp_nonce_field('empresa_detailed_info', 'empresa_detailed_info_nonce');
        
        $sector = get_post_meta($post->ID, '_empresa_sector', true);
        $fundacion = get_post_meta($post->ID, '_empresa_fundacion', true);
        $empleados = get_post_meta($post->ID, '_empresa_empleados', true);
        $certificaciones = get_post_meta($post->ID, '_empresa_certificaciones', true);
        $servicios = get_post_meta($post->ID, '_empresa_servicios', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="empresa_sector">Sector Industrial *</label></th>
                <td>
                    <input type="text" name="empresa_sector" id="empresa_sector" 
                           value="<?php echo esc_attr($sector); ?>" class="regular-text" required />
                    <p class="description">Ej: Agricultura, Ganadería, Agroindustria, Exportación, etc.</p>
                </td>
            </tr>
            <tr>
                <th><label for="empresa_fundacion">Año de Fundación</label></th>
                <td>
                    <input type="number" name="empresa_fundacion" id="empresa_fundacion" 
                           value="<?php echo esc_attr($fundacion); ?>" min="1900" max="<?php echo date('Y'); ?>" 
                           class="small-text" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_empleados">Número de Empleados</label></th>
                <td>
                    <select name="empresa_empleados" id="empresa_empleados">
                        <option value="">-- Seleccionar --</option>
                        <option value="1-10" <?php selected($empleados, '1-10'); ?>>1-10 empleados</option>
                        <option value="11-50" <?php selected($empleados, '11-50'); ?>>11-50 empleados</option>
                        <option value="51-200" <?php selected($empleados, '51-200'); ?>>51-200 empleados</option>
                        <option value="201-500" <?php selected($empleados, '201-500'); ?>>201-500 empleados</option>
                        <option value="500+" <?php selected($empleados, '500+'); ?>>Más de 500 empleados</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="empresa_certificaciones">Certificaciones</label></th>
                <td>
                    <textarea name="empresa_certificaciones" id="empresa_certificaciones" 
                              rows="3" class="large-text"><?php echo esc_textarea($certificaciones); ?></textarea>
                    <p class="description">Lista las certificaciones de la empresa (ISO, Orgánico, etc.)</p>
                </td>
            </tr>
            <tr>
                <th><label for="empresa_servicios">Servicios Ofrecidos</label></th>
                <td>
                    <textarea name="empresa_servicios" id="empresa_servicios" 
                              rows="4" class="large-text"><?php echo esc_textarea($servicios); ?></textarea>
                    <p class="description">Describe los servicios principales que ofrece la empresa</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar meta box de información de contacto
     */
    public static function render_contact_info_box($post): void
    {
        wp_nonce_field('empresa_contact_info', 'empresa_contact_info_nonce');
        
        $telefono = get_post_meta($post->ID, '_empresa_telefono', true);
        $celular = get_post_meta($post->ID, '_empresa_celular', true);
        $email_contacto = get_post_meta($post->ID, '_empresa_email_contacto', true);
        $direccion = get_post_meta($post->ID, '_empresa_direccion', true);
        $ciudad = get_post_meta($post->ID, '_empresa_ciudad', true);
        $provincia = get_post_meta($post->ID, '_empresa_provincia', true);
        $codigo_postal = get_post_meta($post->ID, '_empresa_codigo_postal', true);
        $coordenadas = get_post_meta($post->ID, '_empresa_coordenadas', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="empresa_telefono">Teléfono</label></th>
                <td>
                    <input type="tel" name="empresa_telefono" id="empresa_telefono" 
                           value="<?php echo esc_attr($telefono); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_celular">Celular</label></th>
                <td>
                    <input type="tel" name="empresa_celular" id="empresa_celular" 
                           value="<?php echo esc_attr($celular); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_email_contacto">Email de Contacto</label></th>
                <td>
                    <input type="email" name="empresa_email_contacto" id="empresa_email_contacto" 
                           value="<?php echo esc_attr($email_contacto); ?>" class="regular-text" />
                    <p class="description">Email específico para contacto (puede ser diferente al del usuario)</p>
                </td>
            </tr>
            <tr>
                <th><label for="empresa_direccion">Dirección *</label></th>
                <td>
                    <textarea name="empresa_direccion" id="empresa_direccion" 
                              rows="3" class="large-text" placeholder="Ej: Av. Principal 123, Mz A Lt 5"><?php echo esc_textarea($direccion); ?></textarea>
                    <p class="description">Dirección completa de la empresa</p>
                </td>
            </tr>
            <tr>
                <th><label for="empresa_ciudad">Ciudad</label></th>
                <td>
                    <input type="text" name="empresa_ciudad" id="empresa_ciudad" 
                           value="<?php echo esc_attr($ciudad); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_provincia">Provincia/Estado</label></th>
                <td>
                    <input type="text" name="empresa_provincia" id="empresa_provincia" 
                           value="<?php echo esc_attr($provincia); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_codigo_postal">Código Postal</label></th>
                <td>
                    <input type="text" name="empresa_codigo_postal" id="empresa_codigo_postal" 
                           value="<?php echo esc_attr($codigo_postal); ?>" class="small-text" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_coordenadas">Coordenadas (Lat, Lng)</label></th>
                <td>
                    <input type="text" name="empresa_coordenadas" id="empresa_coordenadas" 
                           value="<?php echo esc_attr($coordenadas); ?>" class="regular-text" 
                           placeholder="Ej: -12.0464, -77.0428" />
                    <p class="description">Coordenadas GPS para mostrar en mapa</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar meta box de redes sociales
     */
    public static function render_social_media_box($post): void
    {
        wp_nonce_field('empresa_social_media', 'empresa_social_media_nonce');
        
        $facebook = get_post_meta($post->ID, '_empresa_facebook', true);
        $instagram = get_post_meta($post->ID, '_empresa_instagram', true);
        $linkedin = get_post_meta($post->ID, '_empresa_linkedin', true);
        $twitter = get_post_meta($post->ID, '_empresa_twitter', true);
        $youtube = get_post_meta($post->ID, '_empresa_youtube', true);
        $website = get_post_meta($post->ID, '_empresa_website', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="empresa_website">Sitio Web</label></th>
                <td>
                    <input type="url" name="empresa_website" id="empresa_website" 
                           value="<?php echo esc_url($website); ?>" class="large-text" 
                           placeholder="https://ejemplo.com" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_facebook">Facebook</label></th>
                <td>
                    <input type="url" name="empresa_facebook" id="empresa_facebook" 
                           value="<?php echo esc_url($facebook); ?>" class="large-text" 
                           placeholder="https://facebook.com/empresa" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_instagram">Instagram</label></th>
                <td>
                    <input type="url" name="empresa_instagram" id="empresa_instagram" 
                           value="<?php echo esc_url($instagram); ?>" class="large-text" 
                           placeholder="https://instagram.com/empresa" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_linkedin">LinkedIn</label></th>
                <td>
                    <input type="url" name="empresa_linkedin" id="empresa_linkedin" 
                           value="<?php echo esc_url($linkedin); ?>" class="large-text" 
                           placeholder="https://linkedin.com/company/empresa" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_twitter">Twitter/X</label></th>
                <td>
                    <input type="url" name="empresa_twitter" id="empresa_twitter" 
                           value="<?php echo esc_url($twitter); ?>" class="large-text" 
                           placeholder="https://twitter.com/empresa" />
                </td>
            </tr>
            <tr>
                <th><label for="empresa_youtube">YouTube</label></th>
                <td>
                    <input type="url" name="empresa_youtube" id="empresa_youtube" 
                           value="<?php echo esc_url($youtube); ?>" class="large-text" 
                           placeholder="https://youtube.com/@empresa" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Guardar meta boxes
     */
    public static function save_meta_boxes($post_id): void
    {
        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verificar que es el tipo de post correcto
        if (get_post_type($post_id) !== 'empresa') {
            return;
        }

        // Si es employer, verificar que solo edita su propia empresa
        $current_user = wp_get_current_user();
        if (in_array('employer', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            $empresa_user_id = get_post_meta($post_id, '_empresa_user_id', true);
            if ($empresa_user_id != $current_user->ID) {
                wp_die('No tienes permisos para editar esta empresa.');
            }
        }

        // Guardar información básica
        if (isset($_POST['empresa_basic_info_nonce']) && 
            wp_verify_nonce($_POST['empresa_basic_info_nonce'], 'empresa_basic_info')) {
            
            $nombre_comercial = isset($_POST['empresa_nombre_comercial']) ? sanitize_text_field($_POST['empresa_nombre_comercial']) : '';
            $ruc = isset($_POST['empresa_ruc']) ? sanitize_text_field($_POST['empresa_ruc']) : '';
            $razon_social = isset($_POST['empresa_razon_social']) ? sanitize_text_field($_POST['empresa_razon_social']) : '';
            
            if (!empty($nombre_comercial)) {
                update_post_meta($post_id, '_empresa_nombre_comercial', $nombre_comercial);
                // Actualizar título del post si está vacío
                if (empty(get_post($post_id)->post_title)) {
                    wp_update_post(['ID' => $post_id, 'post_title' => $nombre_comercial]);
                }
            }
            
            if (!empty($ruc)) {
                update_post_meta($post_id, '_empresa_ruc', $ruc);
            }
            
            if (!empty($razon_social)) {
                update_post_meta($post_id, '_empresa_razon_social', $razon_social);
            }
        }

        // Guardar relación con usuario (solo administradores pueden cambiar esto)
        if (current_user_can('manage_options') && 
            isset($_POST['empresa_user_relation_nonce']) && 
            wp_verify_nonce($_POST['empresa_user_relation_nonce'], 'empresa_user_relation')) {
            
            $user_mode = isset($_POST['empresa_user_mode']) ? sanitize_text_field($_POST['empresa_user_mode']) : 'existing';
            
            if ($user_mode === 'create') {
                // Crear nuevo usuario
                $new_user_email = isset($_POST['new_user_email']) ? sanitize_email($_POST['new_user_email']) : '';
                $new_user_password = isset($_POST['new_user_password']) ? $_POST['new_user_password'] : '';
                $new_user_display_name = isset($_POST['new_user_display_name']) ? sanitize_text_field($_POST['new_user_display_name']) : '';
                
                if (!empty($new_user_email) && is_email($new_user_email)) {
                    // Verificar si el email ya existe
                    $existing_user = get_user_by('email', $new_user_email);
                    
                    if ($existing_user) {
                        // Usuario ya existe, asociarlo
                        update_post_meta($post_id, '_empresa_user_id', $existing_user->ID);
                        // Asegurar que tenga rol employer
                        $existing_user->set_role('employer');
                    } else {
                        // Generar contraseña si no se proporcionó
                        if (empty($new_user_password)) {
                            $new_user_password = wp_generate_password(16, true, true);
                        }
                        
                        // Crear nuevo usuario
                        $user_data = [
                            'user_login' => $new_user_email,
                            'user_email' => $new_user_email,
                            'user_pass' => $new_user_password,
                            'display_name' => !empty($new_user_display_name) ? $new_user_display_name : $new_user_email,
                            'role' => 'employer',
                        ];
                        
                        $new_user_id = wp_insert_user($user_data);
                        
                        if (!is_wp_error($new_user_id)) {
                            update_post_meta($post_id, '_empresa_user_id', $new_user_id);
                            
                            // Guardar éxito en transiente para mostrar después del redirect
                            set_transient('agrochamba_user_created_' . $post_id, true, 30);
                            
                            // Enviar email de bienvenida con la contraseña
                            if (!empty($new_user_password)) {
                                wp_mail(
                                    $new_user_email,
                                    'Bienvenido a AgroChamba - Credenciales de acceso',
                                    sprintf(
                                        "Hola,\n\nTu cuenta de empresa ha sido creada en AgroChamba.\n\nEmail: %s\nContraseña: %s\n\nPor favor, cambia tu contraseña después de iniciar sesión.\n\nAccede aquí: %s",
                                        $new_user_email,
                                        $new_user_password,
                                        wp_login_url()
                                    )
                                );
                            }
                        } else {
                            // Error al crear usuario - guardar en transiente para mostrar después del redirect
                            set_transient('agrochamba_user_creation_error_' . $post_id, $new_user_id->get_error_message(), 30);
                        }
                    }
                }
            } else {
                // Usar usuario existente
                $user_id = isset($_POST['empresa_user_id']) ? intval($_POST['empresa_user_id']) : 0;
                
                if ($user_id > 0) {
                    update_post_meta($post_id, '_empresa_user_id', $user_id);
                } else {
                    delete_post_meta($post_id, '_empresa_user_id');
                }
            }
        } elseif (in_array('employer', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            // Si es employer y no tiene empresa asociada, asociarla automáticamente
            $existing_user_id = get_post_meta($post_id, '_empresa_user_id', true);
            if (empty($existing_user_id)) {
                update_post_meta($post_id, '_empresa_user_id', $current_user->ID);
            }
        }

        // Guardar verificación
        if (isset($_POST['empresa_verification_nonce']) && 
            wp_verify_nonce($_POST['empresa_verification_nonce'], 'empresa_verification')) {
            
            $verificada = isset($_POST['empresa_verificada']) ? '1' : '0';
            $anterior_verificada = get_post_meta($post_id, '_empresa_verificada', true);
            
            update_post_meta($post_id, '_empresa_verificada', $verificada);
            
            // Si se marca como verificada y antes no lo estaba, guardar fecha y usuario
            if ($verificada === '1' && $anterior_verificada !== '1') {
                update_post_meta($post_id, '_empresa_fecha_verificacion', current_time('mysql'));
                update_post_meta($post_id, '_empresa_verificado_por', get_current_user_id());
            }
        }

        // Guardar información detallada
        if (isset($_POST['empresa_detailed_info_nonce']) && 
            wp_verify_nonce($_POST['empresa_detailed_info_nonce'], 'empresa_detailed_info')) {
            
            $fields = ['sector', 'fundacion', 'empleados', 'certificaciones', 'servicios'];
            foreach ($fields as $field) {
                $key = '_empresa_' . $field;
                $value = isset($_POST['empresa_' . $field]) ? sanitize_text_field($_POST['empresa_' . $field]) : '';
                
                if ($field === 'certificaciones' || $field === 'servicios') {
                    $value = isset($_POST['empresa_' . $field]) ? sanitize_textarea_field($_POST['empresa_' . $field]) : '';
                }
                
                if (!empty($value)) {
                    update_post_meta($post_id, $key, $value);
                } else {
                    delete_post_meta($post_id, $key);
                }
            }
        }

        // Guardar información de contacto
        if (isset($_POST['empresa_contact_info_nonce']) && 
            wp_verify_nonce($_POST['empresa_contact_info_nonce'], 'empresa_contact_info')) {
            
            $contact_fields = [
                'telefono', 'celular', 'email_contacto', 'direccion', 
                'ciudad', 'provincia', 'codigo_postal', 'coordenadas'
            ];
            
            foreach ($contact_fields as $field) {
                $key = '_empresa_' . $field;
                $value = isset($_POST['empresa_' . $field]) ? sanitize_text_field($_POST['empresa_' . $field]) : '';
                
                if ($field === 'email_contacto') {
                    $value = isset($_POST['empresa_' . $field]) ? sanitize_email($_POST['empresa_' . $field]) : '';
                } elseif ($field === 'direccion') {
                    $value = isset($_POST['empresa_' . $field]) ? sanitize_textarea_field($_POST['empresa_' . $field]) : '';
                }
                
                if (!empty($value)) {
                    update_post_meta($post_id, $key, $value);
                } else {
                    delete_post_meta($post_id, $key);
                }
            }
        }

        // Guardar redes sociales
        if (isset($_POST['empresa_social_media_nonce']) && 
            wp_verify_nonce($_POST['empresa_social_media_nonce'], 'empresa_social_media')) {
            
            $social_fields = ['website', 'facebook', 'instagram', 'linkedin', 'twitter', 'youtube'];
            
            foreach ($social_fields as $field) {
                $key = '_empresa_' . $field;
                $value = isset($_POST['empresa_' . $field]) ? esc_url_raw($_POST['empresa_' . $field]) : '';
                
                if (!empty($value)) {
                    update_post_meta($post_id, $key, $value);
                } else {
                    delete_post_meta($post_id, $key);
                }
            }
        }
    }

    /**
     * Agregar columnas personalizadas en la lista
     */
    public static function add_custom_columns($columns): array
    {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['empresa_user'] = 'Usuario WordPress';
        $new_columns['empresa_sector'] = 'Sector';
        $new_columns['empresa_ciudad'] = 'Ciudad';
        $new_columns['empresa_ofertas'] = 'Ofertas Activas';
        $new_columns['empresa_verificada'] = 'Verificada';
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }

    /**
     * Renderizar columnas personalizadas
     */
    public static function render_custom_columns($column, $post_id): void
    {
        switch ($column) {
            case 'empresa_user':
                $user_id = get_post_meta($post_id, '_empresa_user_id', true);
                if ($user_id) {
                    $user = get_userdata($user_id);
                    if ($user) {
                        echo esc_html($user->display_name);
                        echo '<br><small>' . esc_html($user->user_email) . '</small>';
                    } else {
                        echo '<span style="color: #d63638;">Usuario no encontrado</span>';
                    }
                } else {
                    echo '<span style="color: #d63638;">Sin usuario</span>';
                }
                break;
                
            case 'empresa_sector':
                $sector = get_post_meta($post_id, '_empresa_sector', true);
                echo $sector ? esc_html($sector) : '—';
                break;
                
            case 'empresa_ciudad':
                $ciudad = get_post_meta($post_id, '_empresa_ciudad', true);
                echo $ciudad ? esc_html($ciudad) : '—';
                break;
                
            case 'empresa_ofertas':
                $ofertas_count = agrochamba_get_empresa_ofertas_count($post_id);
                echo '<strong>' . esc_html($ofertas_count) . '</strong>';
                break;
                
            case 'empresa_verificada':
                $verificada = get_post_meta($post_id, '_empresa_verificada', true);
                if ($verificada === '1') {
                    echo '<span style="color: #00a32a;">✓ Verificada</span>';
                } else {
                    echo '<span style="color: #d63638;">Sin verificar</span>';
                }
                break;
        }
    }

    /**
     * Cargar template personalizado para empresas
     */
    public static function load_empresa_template($template): string
    {
        if (is_singular('empresa')) {
            $custom_template = AGROCHAMBA_TEMPLATES_DIR . '/empresa-perfil.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }

    /**
     * Registrar campos REST API
     */
    public static function register_rest_fields(): void
    {
        register_rest_field('empresa', 'user_id', [
            'get_callback' => function($post) {
                return get_post_meta($post['id'], '_empresa_user_id', true);
            },
            'schema' => [
                'description' => 'ID del usuario WordPress asociado',
                'type'        => 'integer',
            ],
        ]);

        register_rest_field('empresa', 'nombre_comercial', [
            'get_callback' => function($post) {
                return get_post_meta($post['id'], '_empresa_nombre_comercial', true) ?: $post['title']['rendered'];
            },
            'schema' => [
                'description' => 'Nombre comercial de la empresa',
                'type'        => 'string',
            ],
        ]);

        register_rest_field('empresa', 'razon_social', [
            'get_callback' => function($post) {
                return get_post_meta($post['id'], '_empresa_razon_social', true);
            },
            'schema' => [
                'description' => 'Razón social de la empresa',
                'type'        => 'string',
            ],
        ]);

        register_rest_field('empresa', 'ruc', [
            'get_callback' => function($post) {
                return get_post_meta($post['id'], '_empresa_ruc', true);
            },
            'schema' => [
                'description' => 'RUC de la empresa',
                'type'        => 'string',
            ],
        ]);

        register_rest_field('empresa', 'verificada', [
            'get_callback' => function($post) {
                return get_post_meta($post['id'], '_empresa_verificada', true) === '1';
            },
            'schema' => [
                'description' => 'Si la empresa está verificada',
                'type'        => 'boolean',
            ],
        ]);

        register_rest_field('empresa', 'logo_url', [
            'get_callback' => function($post) {
                $logo_id = get_post_thumbnail_id($post['id']);
                return $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : null;
            },
            'schema' => [
                'description' => 'URL del logo de la empresa',
                'type'        => 'string',
            ],
        ]);

        register_rest_field('empresa', 'ofertas_activas_count', [
            'get_callback' => function($post) {
                return agrochamba_get_empresa_ofertas_count($post['id']);
            },
            'schema' => [
                'description' => 'Número de ofertas activas',
                'type'        => 'integer',
            ],
        ]);

        // Registrar todos los campos meta
        $meta_fields = [
            'sector', 'fundacion', 'empleados', 'certificaciones', 'servicios',
            'telefono', 'celular', 'email_contacto', 'direccion', 'ciudad', 
            'provincia', 'codigo_postal', 'coordenadas',
            'website', 'facebook', 'instagram', 'linkedin', 'twitter', 'youtube'
        ];

        foreach ($meta_fields as $field) {
            register_rest_field('empresa', $field, [
                'get_callback' => function($post) use ($field) {
                    return get_post_meta($post['id'], '_empresa_' . $field, true);
                },
                'update_callback' => function($value, $post) use ($field) {
                    return update_post_meta($post->ID, '_empresa_' . $field, $value);
                },
                'schema' => [
                    'description' => ucfirst(str_replace('_', ' ', $field)),
                    'type'        => 'string',
                ],
            ]);
        }
    }

    /**
     * Obtener empresa por user_id
     */
    public static function get_empresa_by_user_id(int $user_id): ?\WP_Post
    {
        $posts = get_posts([
            'post_type'  => 'empresa',
            'meta_key'   => '_empresa_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft', 'pending'],
        ]);

        return !empty($posts) ? $posts[0] : null;
    }

    /**
     * Obtener user_id por empresa_id
     */
    public static function get_user_id_by_empresa_id(int $empresa_id): ?int
    {
        $user_id = get_post_meta($empresa_id, '_empresa_user_id', true);
        return $user_id ? intval($user_id) : null;
    }

    /**
     * Restringir acceso: empresas solo pueden editar su propia empresa
     */
    public static function restrict_empresa_access(): void
    {
        global $pagenow;
        
        // Solo aplicar en admin
        if (!is_admin()) {
            return;
        }

        $current_user = wp_get_current_user();
        
        // Administradores y editores pueden ver todo
        if (in_array('administrator', $current_user->roles) || in_array('editor', $current_user->roles)) {
            return;
        }

        // Si es employer, solo puede ver/editar su propia empresa
        if (in_array('employer', $current_user->roles)) {
            // En la lista de empresas
            if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'empresa') {
                add_filter('pre_get_posts', function($query) use ($current_user) {
                    if (is_admin() && $query->is_main_query()) {
                        $empresa = agrochamba_get_empresa_by_user_id($current_user->ID);
                        if ($empresa) {
                            $query->set('p', $empresa->ID);
                        } else {
                            // Si no tiene empresa, no mostrar ninguna
                            $query->set('p', -1);
                        }
                    }
                });
            }

            // Prevenir crear nuevas empresas (solo admin puede)
            if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'empresa') {
                wp_die('No tienes permisos para crear nuevas empresas. Contacta al administrador.');
            }

            // Prevenir eliminar empresas
            if ($pagenow === 'post.php' && isset($_GET['action']) && $_GET['action'] === 'delete') {
                $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
                $empresa = agrochamba_get_empresa_by_user_id($current_user->ID);
                
                if (!$empresa || $empresa->ID !== $post_id) {
                    wp_die('No tienes permisos para eliminar esta empresa.');
                }
            }
        }
    }

    /**
     * Filtrar posts de empresa por usuario en la lista
     */
    public static function filter_empresa_posts_by_user($query): void
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $current_user = wp_get_current_user();
        
        // Solo aplicar a empresas y si es employer
        if (isset($query->query_vars['post_type']) && 
            $query->query_vars['post_type'] === 'empresa' && 
            in_array('employer', $current_user->roles) &&
            !in_array('administrator', $current_user->roles)) {
            
            $empresa = agrochamba_get_empresa_by_user_id($current_user->ID);
            if ($empresa) {
                $query->set('p', $empresa->ID);
            } else {
                $query->set('p', -1); // No mostrar ninguna
            }
        }
    }

    /**
     * Agregar menú "Mi Empresa" para usuarios employer
     */
    public static function add_mi_empresa_menu(): void
    {
        $current_user = wp_get_current_user();
        
        if (in_array('employer', $current_user->roles)) {
            $empresa = agrochamba_get_empresa_by_user_id($current_user->ID);
            
            if ($empresa) {
                add_menu_page(
                    'Mi Empresa',
                    'Mi Empresa',
                    'read',
                    'mi-empresa',
                    function() use ($empresa) {
                        wp_redirect(admin_url('post.php?post=' . $empresa->ID . '&action=edit'));
                        exit;
                    },
                    'dashicons-building',
                    3
                );
            } else {
                // Si no tiene empresa, mostrar mensaje
                add_menu_page(
                    'Mi Empresa',
                    'Mi Empresa',
                    'read',
                    'mi-empresa',
                    function() {
                        echo '<div class="wrap"><h1>Mi Empresa</h1>';
                        echo '<div class="notice notice-warning"><p>';
                        echo 'No tienes una empresa asociada. Por favor, contacta al administrador para crear tu perfil de empresa.';
                        echo '</p></div></div>';
                    },
                    'dashicons-building',
                    3
                );
            }
        }
    }

    /**
     * Renderizar meta box de estado
     */
    public static function render_status_box($post): void
    {
        ?>
        <div style="padding: 10px 0;">
            <p><strong>Estado actual:</strong></p>
            <p style="margin: 5px 0;">
                <?php 
                $status = $post->post_status;
                $status_labels = [
                    'publish' => 'Publicada',
                    'draft' => 'Borrador',
                    'pending' => 'Pendiente de revisión',
                ];
                $status_colors = [
                    'publish' => '#00a32a',
                    'draft' => '#d63638',
                    'pending' => '#dba617',
                ];
                ?>
                <span style="color: <?php echo esc_attr($status_colors[$status] ?? '#666'); ?>; font-weight: 600;">
                    <?php echo esc_html($status_labels[$status] ?? ucfirst($status)); ?>
                </span>
            </p>
            <p class="description" style="margin-top: 10px;">
                El estado se puede cambiar desde el panel de publicación arriba.
            </p>
        </div>
        <?php
    }

    /**
     * Mostrar mensajes de éxito/error después de crear usuario
     */
    public static function show_user_creation_notices(): void
    {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'empresa') {
            return;
        }

        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        if (!$post_id) {
            return;
        }

        $error = get_transient('agrochamba_user_creation_error_' . $post_id);
        if ($error) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error al crear usuario:</strong> ' . esc_html($error) . '</p></div>';
            delete_transient('agrochamba_user_creation_error_' . $post_id);
        }
        
        $success = get_transient('agrochamba_user_created_' . $post_id);
        if ($success) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Usuario creado exitosamente.</strong> Las credenciales se han enviado por email al usuario.</p></div>';
            delete_transient('agrochamba_user_created_' . $post_id);
        }
    }
}

