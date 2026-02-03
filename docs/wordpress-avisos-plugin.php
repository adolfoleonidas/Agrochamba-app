<?php
/**
 * Agrochamba - Avisos Operativos
 *
 * Este código debe agregarse al plugin principal de Agrochamba en WordPress.
 * Proporciona:
 * - Custom Post Type para Avisos Operativos
 * - Endpoints REST API para CRUD de avisos
 * - Permisos para Admin y Empresas
 *
 * @package Agrochamba
 * @version 1.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * =====================================================
 * 1. REGISTRO DEL CUSTOM POST TYPE
 * =====================================================
 */
add_action('init', 'agrochamba_register_aviso_post_type');

function agrochamba_register_aviso_post_type() {
    $labels = array(
        'name'               => 'Avisos Operativos',
        'singular_name'      => 'Aviso Operativo',
        'menu_name'          => 'Avisos',
        'add_new'            => 'Agregar Aviso',
        'add_new_item'       => 'Agregar Nuevo Aviso',
        'edit_item'          => 'Editar Aviso',
        'new_item'           => 'Nuevo Aviso',
        'view_item'          => 'Ver Aviso',
        'search_items'       => 'Buscar Avisos',
        'not_found'          => 'No se encontraron avisos',
        'not_found_in_trash' => 'No hay avisos en la papelera'
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'aviso'),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 6,
        'menu_icon'           => 'dashicons-megaphone',
        'show_in_rest'        => true,
        'rest_base'           => 'avisos',
        'supports'            => array('title', 'editor', 'author', 'thumbnail')
    );

    register_post_type('aviso_operativo', $args);
}

/**
 * =====================================================
 * 2. META FIELDS PARA AVISOS
 * =====================================================
 */
add_action('init', 'agrochamba_register_aviso_meta');

function agrochamba_register_aviso_meta() {
    // Tipo de aviso: resumen_trabajos, horario_ingreso, alerta_clima, anuncio
    register_post_meta('aviso_operativo', '_tipo_aviso', array(
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => 'anuncio'
    ));

    // Ubicación (para resumen_trabajos y alerta_clima)
    register_post_meta('aviso_operativo', '_ubicacion', array(
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => ''
    ));

    // Preview (texto corto para resumen_trabajos)
    register_post_meta('aviso_operativo', '_preview', array(
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => ''
    ));

    // Hora operativos (para horario_ingreso)
    register_post_meta('aviso_operativo', '_hora_operativos', array(
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => '06:00 AM'
    ));

    // Hora administrativos (para horario_ingreso)
    register_post_meta('aviso_operativo', '_hora_administrativos', array(
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => '08:00 AM'
    ));

    // Fecha de expiración
    register_post_meta('aviso_operativo', '_fecha_expiracion', array(
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => ''
    ));

    // Empresa asociada (opcional)
    register_post_meta('aviso_operativo', '_empresa_id', array(
        'type'          => 'integer',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => 0
    ));

    // Estado activo/inactivo
    register_post_meta('aviso_operativo', '_activo', array(
        'type'          => 'boolean',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => true
    ));
}

/**
 * =====================================================
 * 3. ENDPOINTS REST API
 * =====================================================
 */
add_action('rest_api_init', 'agrochamba_register_avisos_endpoints');

function agrochamba_register_avisos_endpoints() {
    // GET /agrochamba/v1/avisos - Listar avisos activos
    register_rest_route('agrochamba/v1', '/avisos', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_get_avisos',
        'permission_callback' => '__return_true', // Público
        'args'                => array(
            'ubicacion' => array(
                'type'        => 'string',
                'required'    => false,
                'description' => 'Filtrar por ubicación'
            ),
            'tipo' => array(
                'type'        => 'string',
                'required'    => false,
                'description' => 'Filtrar por tipo de aviso'
            ),
            'per_page' => array(
                'type'        => 'integer',
                'default'     => 10,
                'description' => 'Cantidad de resultados'
            )
        )
    ));

    // POST /agrochamba/v1/avisos - Crear aviso (requiere autenticación)
    register_rest_route('agrochamba/v1', '/avisos', array(
        'methods'             => 'POST',
        'callback'            => 'agrochamba_create_aviso',
        'permission_callback' => 'agrochamba_can_create_aviso'
    ));

    // PUT /agrochamba/v1/avisos/{id} - Actualizar aviso
    register_rest_route('agrochamba/v1', '/avisos/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'callback'            => 'agrochamba_update_aviso',
        'permission_callback' => 'agrochamba_can_edit_aviso',
        'args'                => array(
            'id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            )
        )
    ));

    // DELETE /agrochamba/v1/avisos/{id} - Eliminar aviso
    register_rest_route('agrochamba/v1', '/avisos/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'callback'            => 'agrochamba_delete_aviso',
        'permission_callback' => 'agrochamba_can_edit_aviso'
    ));
}

/**
 * GET: Listar avisos activos
 */
function agrochamba_get_avisos($request) {
    $ubicacion = $request->get_param('ubicacion');
    $tipo = $request->get_param('tipo');
    $per_page = $request->get_param('per_page') ?: 10;

    $args = array(
        'post_type'      => 'aviso_operativo',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'     => '_activo',
                'value'   => '1',
                'compare' => '='
            )
        )
    );

    // Filtrar por ubicación
    if (!empty($ubicacion)) {
        $args['meta_query'][] = array(
            'key'     => '_ubicacion',
            'value'   => $ubicacion,
            'compare' => 'LIKE'
        );
    }

    // Filtrar por tipo
    if (!empty($tipo)) {
        $args['meta_query'][] = array(
            'key'     => '_tipo_aviso',
            'value'   => $tipo,
            'compare' => '='
        );
    }

    // Excluir avisos expirados
    $args['meta_query'][] = array(
        'relation' => 'OR',
        array(
            'key'     => '_fecha_expiracion',
            'value'   => '',
            'compare' => '='
        ),
        array(
            'key'     => '_fecha_expiracion',
            'value'   => current_time('Y-m-d'),
            'compare' => '>='
        )
    );

    $query = new WP_Query($args);
    $avisos = array();

    foreach ($query->posts as $post) {
        $avisos[] = agrochamba_format_aviso($post);
    }

    return rest_ensure_response($avisos);
}

/**
 * POST: Crear nuevo aviso
 */
function agrochamba_create_aviso($request) {
    $params = $request->get_json_params();
    $user_id = get_current_user_id();

    // Validar campos obligatorios
    if (empty($params['title'])) {
        return new WP_Error('missing_title', 'El título es obligatorio', array('status' => 400));
    }

    $tipo_aviso = sanitize_text_field($params['tipo_aviso'] ?? 'anuncio');

    // Validaciones específicas por tipo
    if ($tipo_aviso === 'resumen_trabajos') {
        if (empty($params['ubicacion'])) {
            return new WP_Error('missing_ubicacion', 'La ubicación es obligatoria para resumen de trabajos', array('status' => 400));
        }
        if (empty($params['preview'])) {
            return new WP_Error('missing_preview', 'El preview es obligatorio para resumen de trabajos', array('status' => 400));
        }
    }

    // Crear el post
    $post_data = array(
        'post_title'   => sanitize_text_field($params['title']),
        'post_content' => wp_kses_post($params['content'] ?? ''),
        'post_type'    => 'aviso_operativo',
        'post_status'  => 'publish',
        'post_author'  => $user_id
    );

    $post_id = wp_insert_post($post_data, true);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    // Guardar meta fields
    update_post_meta($post_id, '_tipo_aviso', $tipo_aviso);
    update_post_meta($post_id, '_activo', true);

    if (!empty($params['ubicacion'])) {
        update_post_meta($post_id, '_ubicacion', sanitize_text_field($params['ubicacion']));
    }

    if (!empty($params['preview'])) {
        update_post_meta($post_id, '_preview', sanitize_textarea_field($params['preview']));
    }

    if (!empty($params['hora_operativos'])) {
        update_post_meta($post_id, '_hora_operativos', sanitize_text_field($params['hora_operativos']));
    }

    if (!empty($params['hora_administrativos'])) {
        update_post_meta($post_id, '_hora_administrativos', sanitize_text_field($params['hora_administrativos']));
    }

    if (!empty($params['fecha_expiracion'])) {
        update_post_meta($post_id, '_fecha_expiracion', sanitize_text_field($params['fecha_expiracion']));
    }

    // Asociar empresa si es usuario de empresa
    $empresa_id = get_user_meta($user_id, 'empresa_id', true);
    if ($empresa_id) {
        update_post_meta($post_id, '_empresa_id', intval($empresa_id));
    }

    $post = get_post($post_id);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Aviso creado exitosamente',
        'post_id' => $post_id,
        'aviso'   => agrochamba_format_aviso($post)
    ));
}

/**
 * PUT: Actualizar aviso
 */
function agrochamba_update_aviso($request) {
    $post_id = $request->get_param('id');
    $params = $request->get_json_params();

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'aviso_operativo') {
        return new WP_Error('not_found', 'Aviso no encontrado', array('status' => 404));
    }

    // Actualizar datos del post
    $update_data = array('ID' => $post_id);

    if (!empty($params['title'])) {
        $update_data['post_title'] = sanitize_text_field($params['title']);
    }

    if (isset($params['content'])) {
        $update_data['post_content'] = wp_kses_post($params['content']);
    }

    wp_update_post($update_data);

    // Actualizar meta fields
    if (isset($params['tipo_aviso'])) {
        update_post_meta($post_id, '_tipo_aviso', sanitize_text_field($params['tipo_aviso']));
    }

    if (isset($params['ubicacion'])) {
        update_post_meta($post_id, '_ubicacion', sanitize_text_field($params['ubicacion']));
    }

    if (isset($params['preview'])) {
        update_post_meta($post_id, '_preview', sanitize_textarea_field($params['preview']));
    }

    if (isset($params['hora_operativos'])) {
        update_post_meta($post_id, '_hora_operativos', sanitize_text_field($params['hora_operativos']));
    }

    if (isset($params['hora_administrativos'])) {
        update_post_meta($post_id, '_hora_administrativos', sanitize_text_field($params['hora_administrativos']));
    }

    if (isset($params['fecha_expiracion'])) {
        update_post_meta($post_id, '_fecha_expiracion', sanitize_text_field($params['fecha_expiracion']));
    }

    if (isset($params['activo'])) {
        update_post_meta($post_id, '_activo', (bool) $params['activo']);
    }

    $post = get_post($post_id);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Aviso actualizado exitosamente',
        'aviso'   => agrochamba_format_aviso($post)
    ));
}

/**
 * DELETE: Eliminar aviso
 */
function agrochamba_delete_aviso($request) {
    $post_id = $request->get_param('id');

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'aviso_operativo') {
        return new WP_Error('not_found', 'Aviso no encontrado', array('status' => 404));
    }

    wp_delete_post($post_id, true);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Aviso eliminado exitosamente'
    ));
}

/**
 * =====================================================
 * 4. PERMISOS
 * =====================================================
 */

/**
 * Verificar si el usuario puede crear avisos
 */
function agrochamba_can_create_aviso($request) {
    $user_id = get_current_user_id();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Debes iniciar sesión', array('status' => 401));
    }

    // Admins siempre pueden
    if (current_user_can('manage_options')) {
        return true;
    }

    // Empresas pueden crear avisos de su empresa
    $user_role = get_user_meta($user_id, 'agrochamba_role', true);
    if ($user_role === 'empresa' || current_user_can('edit_posts')) {
        return true;
    }

    return new WP_Error('forbidden', 'No tienes permisos para crear avisos', array('status' => 403));
}

/**
 * Verificar si el usuario puede editar/eliminar un aviso
 */
function agrochamba_can_edit_aviso($request) {
    $user_id = get_current_user_id();
    $post_id = $request->get_param('id');

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Debes iniciar sesión', array('status' => 401));
    }

    // Admins siempre pueden
    if (current_user_can('manage_options')) {
        return true;
    }

    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error('not_found', 'Aviso no encontrado', array('status' => 404));
    }

    // El autor puede editar su propio aviso
    if ($post->post_author == $user_id) {
        return true;
    }

    // Empresa puede editar avisos de su empresa
    $empresa_id = get_user_meta($user_id, 'empresa_id', true);
    $aviso_empresa_id = get_post_meta($post_id, '_empresa_id', true);

    if ($empresa_id && $empresa_id == $aviso_empresa_id) {
        return true;
    }

    return new WP_Error('forbidden', 'No tienes permisos para editar este aviso', array('status' => 403));
}

/**
 * =====================================================
 * 5. HELPERS
 * =====================================================
 */

/**
 * Formatear aviso para respuesta JSON
 */
function agrochamba_format_aviso($post) {
    $empresa_id = get_post_meta($post->ID, '_empresa_id', true);
    $empresa_nombre = '';

    if ($empresa_id) {
        $empresa_term = get_term($empresa_id, 'empresa');
        if ($empresa_term && !is_wp_error($empresa_term)) {
            $empresa_nombre = $empresa_term->name;
        }
    }

    return array(
        'id'                   => $post->ID,
        'tipo'                 => get_post_meta($post->ID, '_tipo_aviso', true) ?: 'anuncio',
        'titulo'               => $post->post_title,
        'contenido'            => $post->post_content,
        'ubicacion'            => get_post_meta($post->ID, '_ubicacion', true),
        'preview'              => get_post_meta($post->ID, '_preview', true),
        'hora_operativos'      => get_post_meta($post->ID, '_hora_operativos', true),
        'hora_administrativos' => get_post_meta($post->ID, '_hora_administrativos', true),
        'fecha_creacion'       => $post->post_date,
        'fecha_expiracion'     => get_post_meta($post->ID, '_fecha_expiracion', true),
        'empresa_id'           => $empresa_id ? intval($empresa_id) : null,
        'empresa_nombre'       => $empresa_nombre,
        'activo'               => (bool) get_post_meta($post->ID, '_activo', true)
    );
}

/**
 * =====================================================
 * 6. INTEGRACIÓN CON ENDPOINT EXISTENTE DE JOBS
 * =====================================================
 *
 * Agregar este código al handler de POST /agrochamba/v1/jobs
 * para que maneje también el post_type "aviso"
 */

/**
 * Agregar al switch de post_type en el endpoint de crear jobs:
 *
 * case 'aviso':
 *     // Crear como aviso_operativo
 *     $post_data['post_type'] = 'aviso_operativo';
 *
 *     // Guardar meta específicos de aviso
 *     if (!empty($params['tipo_aviso'])) {
 *         update_post_meta($post_id, '_tipo_aviso', sanitize_text_field($params['tipo_aviso']));
 *     }
 *     if (!empty($params['ubicacion'])) {
 *         update_post_meta($post_id, '_ubicacion', sanitize_text_field($params['ubicacion']));
 *     }
 *     if (!empty($params['preview'])) {
 *         update_post_meta($post_id, '_preview', sanitize_textarea_field($params['preview']));
 *     }
 *     if (!empty($params['hora_operativos'])) {
 *         update_post_meta($post_id, '_hora_operativos', sanitize_text_field($params['hora_operativos']));
 *     }
 *     if (!empty($params['hora_administrativos'])) {
 *         update_post_meta($post_id, '_hora_administrativos', sanitize_text_field($params['hora_administrativos']));
 *     }
 *     update_post_meta($post_id, '_activo', true);
 *     break;
 */

/**
 * =====================================================
 * 7. META BOX PARA ADMIN (WordPress Dashboard)
 * =====================================================
 */
add_action('add_meta_boxes', 'agrochamba_add_aviso_meta_box');

function agrochamba_add_aviso_meta_box() {
    add_meta_box(
        'agrochamba_aviso_meta',
        'Configuración del Aviso',
        'agrochamba_aviso_meta_box_callback',
        'aviso_operativo',
        'normal',
        'high'
    );
}

function agrochamba_aviso_meta_box_callback($post) {
    wp_nonce_field('agrochamba_aviso_meta', 'agrochamba_aviso_meta_nonce');

    $tipo_aviso = get_post_meta($post->ID, '_tipo_aviso', true) ?: 'anuncio';
    $ubicacion = get_post_meta($post->ID, '_ubicacion', true);
    $preview = get_post_meta($post->ID, '_preview', true);
    $hora_operativos = get_post_meta($post->ID, '_hora_operativos', true) ?: '06:00 AM';
    $hora_administrativos = get_post_meta($post->ID, '_hora_administrativos', true) ?: '08:00 AM';
    $fecha_expiracion = get_post_meta($post->ID, '_fecha_expiracion', true);
    $activo = get_post_meta($post->ID, '_activo', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="tipo_aviso">Tipo de Aviso</label></th>
            <td>
                <select name="tipo_aviso" id="tipo_aviso" class="regular-text">
                    <option value="resumen_trabajos" <?php selected($tipo_aviso, 'resumen_trabajos'); ?>>📋 Resumen de Trabajos</option>
                    <option value="horario_ingreso" <?php selected($tipo_aviso, 'horario_ingreso'); ?>>🕐 Horario de Ingreso</option>
                    <option value="alerta_clima" <?php selected($tipo_aviso, 'alerta_clima'); ?>>⚠️ Alerta de Clima</option>
                    <option value="anuncio" <?php selected($tipo_aviso, 'anuncio'); ?>>📢 Anuncio General</option>
                </select>
            </td>
        </tr>
        <tr class="aviso-field ubicacion-field">
            <th><label for="ubicacion">Ubicación</label></th>
            <td>
                <input type="text" name="ubicacion" id="ubicacion" value="<?php echo esc_attr($ubicacion); ?>" class="regular-text" placeholder="Ej: Ica, Lima, Arequipa...">
            </td>
        </tr>
        <tr class="aviso-field preview-field">
            <th><label for="preview">Preview (texto corto)</label></th>
            <td>
                <textarea name="preview" id="preview" rows="3" class="large-text"><?php echo esc_textarea($preview); ?></textarea>
                <p class="description">Este texto se muestra en la tarjeta del aviso</p>
            </td>
        </tr>
        <tr class="aviso-field horario-field">
            <th><label for="hora_operativos">Hora Operativos</label></th>
            <td>
                <input type="text" name="hora_operativos" id="hora_operativos" value="<?php echo esc_attr($hora_operativos); ?>" class="regular-text" placeholder="06:00 AM">
            </td>
        </tr>
        <tr class="aviso-field horario-field">
            <th><label for="hora_administrativos">Hora Administrativos</label></th>
            <td>
                <input type="text" name="hora_administrativos" id="hora_administrativos" value="<?php echo esc_attr($hora_administrativos); ?>" class="regular-text" placeholder="08:00 AM">
            </td>
        </tr>
        <tr>
            <th><label for="fecha_expiracion">Fecha de Expiración</label></th>
            <td>
                <input type="date" name="fecha_expiracion" id="fecha_expiracion" value="<?php echo esc_attr($fecha_expiracion); ?>" class="regular-text">
                <p class="description">Dejar vacío para que no expire</p>
            </td>
        </tr>
        <tr>
            <th><label for="activo">Estado</label></th>
            <td>
                <label>
                    <input type="checkbox" name="activo" id="activo" value="1" <?php checked($activo, true); ?>>
                    Aviso activo (visible en la app)
                </label>
            </td>
        </tr>
    </table>

    <script>
    jQuery(document).ready(function($) {
        function toggleFields() {
            var tipo = $('#tipo_aviso').val();

            // Ocultar todos los campos específicos
            $('.aviso-field').hide();

            // Mostrar campos según el tipo
            switch(tipo) {
                case 'resumen_trabajos':
                    $('.ubicacion-field, .preview-field').show();
                    break;
                case 'horario_ingreso':
                    $('.horario-field').show();
                    break;
                case 'alerta_clima':
                    $('.ubicacion-field').show();
                    break;
                // anuncio solo usa título y contenido
            }
        }

        $('#tipo_aviso').on('change', toggleFields);
        toggleFields(); // Ejecutar al cargar
    });
    </script>
    <?php
}

add_action('save_post_aviso_operativo', 'agrochamba_save_aviso_meta');

function agrochamba_save_aviso_meta($post_id) {
    if (!isset($_POST['agrochamba_aviso_meta_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['agrochamba_aviso_meta_nonce'], 'agrochamba_aviso_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Guardar campos
    if (isset($_POST['tipo_aviso'])) {
        update_post_meta($post_id, '_tipo_aviso', sanitize_text_field($_POST['tipo_aviso']));
    }

    if (isset($_POST['ubicacion'])) {
        update_post_meta($post_id, '_ubicacion', sanitize_text_field($_POST['ubicacion']));
    }

    if (isset($_POST['preview'])) {
        update_post_meta($post_id, '_preview', sanitize_textarea_field($_POST['preview']));
    }

    if (isset($_POST['hora_operativos'])) {
        update_post_meta($post_id, '_hora_operativos', sanitize_text_field($_POST['hora_operativos']));
    }

    if (isset($_POST['hora_administrativos'])) {
        update_post_meta($post_id, '_hora_administrativos', sanitize_text_field($_POST['hora_administrativos']));
    }

    if (isset($_POST['fecha_expiracion'])) {
        update_post_meta($post_id, '_fecha_expiracion', sanitize_text_field($_POST['fecha_expiracion']));
    }

    update_post_meta($post_id, '_activo', isset($_POST['activo']) ? true : false);
}
