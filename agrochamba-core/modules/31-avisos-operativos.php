<?php
/**
 * =============================================================
 * MÓDULO 31: AVISOS OPERATIVOS
 * =============================================================
 *
 * Sistema de avisos operativos para la app móvil:
 * - Custom Post Type: 'aviso_operativo'
 * - Tipos: resumen_trabajos, horario_ingreso, alerta_clima, anuncio
 * - REST API endpoints
 * - Meta boxes para admin
 * - Permisos para empresas
 *
 * @package AgroChamba
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. CUSTOM POST TYPE: AVISOS OPERATIVOS
// ==========================================
add_action('init', 'agrochamba_register_aviso_cpt', 0);

function agrochamba_register_aviso_cpt() {
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
        'rest_base'           => 'avisos-cpt',
        'supports'            => array('title', 'editor', 'author', 'thumbnail')
    );

    register_post_type('aviso_operativo', $args);
}

// ==========================================
// 2. META FIELDS PARA AVISOS
// ==========================================
add_action('init', 'agrochamba_register_aviso_meta');

function agrochamba_register_aviso_meta() {
    $meta_fields = array(
        '_tipo_aviso' => array(
            'type'        => 'string',
            'default'     => 'anuncio',
            'description' => 'Tipo de aviso: resumen_trabajos, horario_ingreso, alerta_clima, anuncio'
        ),
        '_ubicacion' => array(
            'type'        => 'string',
            'default'     => '',
            'description' => 'Ubicación del aviso (para resumen_trabajos y alerta_clima)'
        ),
        '_preview' => array(
            'type'        => 'string',
            'default'     => '',
            'description' => 'Texto corto de preview (para resumen_trabajos)'
        ),
        '_hora_operativos' => array(
            'type'        => 'string',
            'default'     => '06:00 AM',
            'description' => 'Hora de ingreso operativos'
        ),
        '_hora_administrativos' => array(
            'type'        => 'string',
            'default'     => '08:00 AM',
            'description' => 'Hora de ingreso administrativos'
        ),
        '_fecha_expiracion' => array(
            'type'        => 'string',
            'default'     => '',
            'description' => 'Fecha de expiración del aviso'
        ),
        '_empresa_id' => array(
            'type'        => 'integer',
            'default'     => 0,
            'description' => 'ID de la empresa asociada'
        ),
        '_activo' => array(
            'type'        => 'boolean',
            'default'     => true,
            'description' => 'Estado activo del aviso'
        )
    );

    foreach ($meta_fields as $key => $config) {
        register_post_meta('aviso_operativo', $key, array(
            'type'          => $config['type'],
            'single'        => true,
            'show_in_rest'  => true,
            'default'       => $config['default'],
            'description'   => $config['description']
        ));
    }
}

// ==========================================
// 3. REST API ENDPOINTS
// ==========================================
add_action('rest_api_init', 'agrochamba_register_avisos_routes');

function agrochamba_register_avisos_routes() {
    // GET /agrochamba/v1/avisos - Listar avisos activos (público)
    register_rest_route('agrochamba/v1', '/avisos', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_get_avisos',
        'permission_callback' => '__return_true',
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

    // POST /agrochamba/v1/avisos - Crear aviso (requiere auth)
    register_rest_route('agrochamba/v1', '/avisos', array(
        'methods'             => 'POST',
        'callback'            => 'agrochamba_api_create_aviso',
        'permission_callback' => 'agrochamba_can_manage_avisos'
    ));

    // PUT /agrochamba/v1/avisos/{id} - Actualizar aviso
    register_rest_route('agrochamba/v1', '/avisos/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'callback'            => 'agrochamba_api_update_aviso',
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
        'callback'            => 'agrochamba_api_delete_aviso',
        'permission_callback' => 'agrochamba_can_edit_aviso'
    ));
}

/**
 * GET: Listar avisos activos
 */
function agrochamba_api_get_avisos($request) {
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
            'relation' => 'AND',
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
            'compare' => 'NOT EXISTS'
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
        $avisos[] = agrochamba_format_aviso_response($post);
    }

    return rest_ensure_response($avisos);
}

/**
 * POST: Crear nuevo aviso
 */
function agrochamba_api_create_aviso($request) {
    $params = $request->get_json_params();
    $user_id = get_current_user_id();

    // Validar título
    if (empty($params['title'])) {
        return new WP_Error('missing_title', 'El título es obligatorio', array('status' => 400));
    }

    $tipo_aviso = sanitize_text_field($params['tipo_aviso'] ?? 'anuncio');

    // Validaciones por tipo
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

    // Asociar empresa si el usuario es empresa
    $empresa_id = get_user_meta($user_id, 'empresa_id', true);
    if ($empresa_id) {
        update_post_meta($post_id, '_empresa_id', intval($empresa_id));
    }

    $post = get_post($post_id);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Aviso creado exitosamente',
        'post_id' => $post_id,
        'aviso'   => agrochamba_format_aviso_response($post)
    ));
}

/**
 * PUT: Actualizar aviso
 */
function agrochamba_api_update_aviso($request) {
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
    $meta_fields = array(
        'tipo_aviso'          => '_tipo_aviso',
        'ubicacion'           => '_ubicacion',
        'preview'             => '_preview',
        'hora_operativos'     => '_hora_operativos',
        'hora_administrativos' => '_hora_administrativos',
        'fecha_expiracion'    => '_fecha_expiracion',
        'activo'              => '_activo'
    );

    foreach ($meta_fields as $param_key => $meta_key) {
        if (isset($params[$param_key])) {
            $value = $params[$param_key];
            if ($meta_key === '_activo') {
                $value = (bool) $value;
            } else {
                $value = sanitize_text_field($value);
            }
            update_post_meta($post_id, $meta_key, $value);
        }
    }

    $post = get_post($post_id);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Aviso actualizado exitosamente',
        'aviso'   => agrochamba_format_aviso_response($post)
    ));
}

/**
 * DELETE: Eliminar aviso
 */
function agrochamba_api_delete_aviso($request) {
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

// ==========================================
// 4. PERMISOS
// ==========================================

/**
 * Verificar si puede crear avisos
 */
function agrochamba_can_manage_avisos($request) {
    $user_id = get_current_user_id();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Debes iniciar sesión', array('status' => 401));
    }

    // Admins siempre pueden
    if (current_user_can('manage_options')) {
        return true;
    }

    // Empresas pueden crear avisos
    $user_role = get_user_meta($user_id, 'agrochamba_role', true);
    if ($user_role === 'empresa' || current_user_can('edit_posts')) {
        return true;
    }

    return new WP_Error('forbidden', 'No tienes permisos para crear avisos', array('status' => 403));
}

/**
 * Verificar si puede editar un aviso específico
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

// ==========================================
// 5. HELPERS
// ==========================================

/**
 * Formatear aviso para respuesta JSON
 */
function agrochamba_format_aviso_response($post) {
    $empresa_id = get_post_meta($post->ID, '_empresa_id', true);
    $empresa_nombre = '';

    if ($empresa_id) {
        $empresa_term = get_term($empresa_id, 'empresa');
        if ($empresa_term && !is_wp_error($empresa_term)) {
            $empresa_nombre = $empresa_term->name;
        } else {
            // Intentar obtener de CPT empresa
            $empresa_post = get_post($empresa_id);
            if ($empresa_post) {
                $empresa_nombre = $empresa_post->post_title;
            }
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

// ==========================================
// 6. META BOX PARA ADMIN
// ==========================================
add_action('add_meta_boxes', 'agrochamba_add_aviso_meta_box');

function agrochamba_add_aviso_meta_box() {
    add_meta_box(
        'agrochamba_aviso_config',
        '<span class="dashicons dashicons-megaphone" style="vertical-align: middle;"></span> Configuración del Aviso',
        'agrochamba_aviso_meta_box_callback',
        'aviso_operativo',
        'normal',
        'high'
    );
}

function agrochamba_aviso_meta_box_callback($post) {
    wp_nonce_field('agrochamba_save_aviso_meta', 'agrochamba_aviso_meta_nonce');

    $tipo_aviso = get_post_meta($post->ID, '_tipo_aviso', true) ?: 'anuncio';
    $ubicacion = get_post_meta($post->ID, '_ubicacion', true);
    $preview = get_post_meta($post->ID, '_preview', true);
    $hora_operativos = get_post_meta($post->ID, '_hora_operativos', true) ?: '06:00 AM';
    $hora_administrativos = get_post_meta($post->ID, '_hora_administrativos', true) ?: '08:00 AM';
    $fecha_expiracion = get_post_meta($post->ID, '_fecha_expiracion', true);
    $activo = get_post_meta($post->ID, '_activo', true);
    if ($activo === '') $activo = true;
    ?>
    <style>
        .agrochamba-aviso-table { width: 100%; border-collapse: collapse; }
        .agrochamba-aviso-table th { width: 180px; text-align: left; padding: 12px 10px; font-weight: 600; }
        .agrochamba-aviso-table td { padding: 12px 10px; }
        .agrochamba-aviso-table input[type="text"],
        .agrochamba-aviso-table input[type="date"],
        .agrochamba-aviso-table select,
        .agrochamba-aviso-table textarea { width: 100%; max-width: 500px; }
        .agrochamba-aviso-table textarea { min-height: 80px; }
        .aviso-field { display: none; }
        .aviso-field.active { display: table-row; }
        .agrochamba-tip { background: #e7f3ff; border-left: 4px solid #007bff; padding: 10px 15px; margin-top: 15px; }
    </style>

    <table class="agrochamba-aviso-table">
        <tr>
            <th><label for="tipo_aviso">Tipo de Aviso</label></th>
            <td>
                <select name="tipo_aviso" id="tipo_aviso">
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
                <input type="text" name="ubicacion" id="ubicacion" value="<?php echo esc_attr($ubicacion); ?>" placeholder="Ej: Ica, Lima, Arequipa...">
                <p class="description">Ubicación a la que aplica este aviso</p>
            </td>
        </tr>
        <tr class="aviso-field preview-field">
            <th><label for="preview">Preview</label></th>
            <td>
                <textarea name="preview" id="preview" placeholder="Texto corto que aparece en la tarjeta..."><?php echo esc_textarea($preview); ?></textarea>
                <p class="description">Texto corto que se muestra en la tarjeta del aviso. El contenido completo va en el editor principal.</p>
            </td>
        </tr>
        <tr class="aviso-field horario-field">
            <th><label for="hora_operativos">Hora Operativos</label></th>
            <td>
                <input type="text" name="hora_operativos" id="hora_operativos" value="<?php echo esc_attr($hora_operativos); ?>" placeholder="06:00 AM">
            </td>
        </tr>
        <tr class="aviso-field horario-field">
            <th><label for="hora_administrativos">Hora Administrativos</label></th>
            <td>
                <input type="text" name="hora_administrativos" id="hora_administrativos" value="<?php echo esc_attr($hora_administrativos); ?>" placeholder="08:00 AM">
            </td>
        </tr>
        <tr>
            <th><label for="fecha_expiracion">Fecha de Expiración</label></th>
            <td>
                <input type="date" name="fecha_expiracion" id="fecha_expiracion" value="<?php echo esc_attr($fecha_expiracion); ?>">
                <p class="description">Dejar vacío para que no expire automáticamente</p>
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

    <div class="agrochamba-tip">
        <strong>💡 Consejo:</strong> Los avisos de tipo "Resumen de Trabajos" son ideales para notificar a los usuarios sobre oportunidades de trabajo disponibles en una ubicación específica. El preview se muestra en la tarjeta y el contenido completo aparece al tocar "Leer más".
    </div>

    <script>
    jQuery(document).ready(function($) {
        function toggleAvisoFields() {
            var tipo = $('#tipo_aviso').val();

            // Ocultar todos
            $('.aviso-field').removeClass('active');

            // Mostrar según tipo
            switch(tipo) {
                case 'resumen_trabajos':
                    $('.ubicacion-field, .preview-field').addClass('active');
                    break;
                case 'horario_ingreso':
                    $('.horario-field').addClass('active');
                    break;
                case 'alerta_clima':
                    $('.ubicacion-field').addClass('active');
                    break;
            }
        }

        $('#tipo_aviso').on('change', toggleAvisoFields);
        toggleAvisoFields();
    });
    </script>
    <?php
}

// Guardar meta del aviso
add_action('save_post_aviso_operativo', 'agrochamba_save_aviso_meta');

function agrochamba_save_aviso_meta($post_id) {
    if (!isset($_POST['agrochamba_aviso_meta_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['agrochamba_aviso_meta_nonce'], 'agrochamba_save_aviso_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Guardar campos
    $fields = array('tipo_aviso', 'ubicacion', 'preview', 'hora_operativos', 'hora_administrativos', 'fecha_expiracion');

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Campo booleano
    update_post_meta($post_id, '_activo', isset($_POST['activo']) ? true : false);
}

// ==========================================
// 7. INTEGRACIÓN CON ENDPOINT DE JOBS
// ==========================================
// Manejar post_type "aviso" en el endpoint de crear jobs
add_filter('agrochamba_create_job_post_type', 'agrochamba_handle_aviso_post_type', 10, 2);

function agrochamba_handle_aviso_post_type($post_type, $params) {
    if ($post_type === 'aviso') {
        return 'aviso_operativo';
    }
    return $post_type;
}

// Hook para guardar meta de aviso cuando se crea desde el endpoint de jobs
add_action('agrochamba_after_create_job', 'agrochamba_save_aviso_from_job_endpoint', 10, 3);

function agrochamba_save_aviso_from_job_endpoint($post_id, $params, $post_type) {
    if ($post_type !== 'aviso' && get_post_type($post_id) !== 'aviso_operativo') {
        return;
    }

    // Guardar meta específicos de aviso
    if (!empty($params['tipo_aviso'])) {
        update_post_meta($post_id, '_tipo_aviso', sanitize_text_field($params['tipo_aviso']));
    }
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

    update_post_meta($post_id, '_activo', true);
}

// ==========================================
// 8. COLUMNAS EN ADMIN
// ==========================================
add_filter('manage_aviso_operativo_posts_columns', 'agrochamba_aviso_admin_columns');

function agrochamba_aviso_admin_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['tipo_aviso'] = 'Tipo';
            $new_columns['ubicacion'] = 'Ubicación';
            $new_columns['activo'] = 'Estado';
        }
    }
    return $new_columns;
}

add_action('manage_aviso_operativo_posts_custom_column', 'agrochamba_aviso_admin_column_content', 10, 2);

function agrochamba_aviso_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'tipo_aviso':
            $tipo = get_post_meta($post_id, '_tipo_aviso', true);
            $tipos = array(
                'resumen_trabajos' => '📋 Resumen',
                'horario_ingreso'  => '🕐 Horario',
                'alerta_clima'     => '⚠️ Clima',
                'anuncio'          => '📢 Anuncio'
            );
            echo isset($tipos[$tipo]) ? $tipos[$tipo] : $tipo;
            break;

        case 'ubicacion':
            echo esc_html(get_post_meta($post_id, '_ubicacion', true)) ?: '—';
            break;

        case 'activo':
            $activo = get_post_meta($post_id, '_activo', true);
            echo $activo ? '<span style="color: #00a32a;">✓ Activo</span>' : '<span style="color: #d63638;">✗ Inactivo</span>';
            break;
    }
}
