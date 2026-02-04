<?php
/**
 * Módulo: Sistema de Rendimiento de Trabajadores
 *
 * Permite a las empresas registrar y consultar el rendimiento de trabajadores
 * en categorías como: Embalaje, Selección, Clamshell, etc.
 *
 * @package AgroChamba
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// ==========================================
// CUSTOM POST TYPE: RENDIMIENTO
// ==========================================

add_action('init', 'agrochamba_register_rendimiento_cpt');

function agrochamba_register_rendimiento_cpt()
{
    $labels = array(
        'name'               => 'Rendimientos',
        'singular_name'      => 'Rendimiento',
        'menu_name'          => 'Rendimientos',
        'add_new'            => 'Agregar Nuevo',
        'add_new_item'       => 'Agregar Nuevo Rendimiento',
        'edit_item'          => 'Editar Rendimiento',
        'new_item'           => 'Nuevo Rendimiento',
        'view_item'          => 'Ver Rendimiento',
        'search_items'       => 'Buscar Rendimientos',
        'not_found'          => 'No se encontraron rendimientos',
        'not_found_in_trash' => 'No hay rendimientos en la papelera',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => false,
        'rewrite'             => false,
        'capability_type'     => 'post',
        'has_archive'         => false,
        'hierarchical'        => false,
        'menu_position'       => 28,
        'menu_icon'           => 'dashicons-chart-bar',
        'supports'            => array('title'),
        'show_in_rest'        => false, // Usamos endpoints personalizados
    );

    register_post_type('rendimiento', $args);
}

// ==========================================
// META FIELDS
// ==========================================

/**
 * Campos meta para rendimiento:
 * - empresa_id: ID de la empresa que registra
 * - trabajador_id: ID del usuario trabajador
 * - trabajador_nombre: Nombre del trabajador (para búsqueda rápida)
 * - trabajador_dni: DNI del trabajador
 * - categoria: embalaje, seleccion, clamshell, cosecha, etc.
 * - valor: Número de unidades/cajas
 * - unidad: cajas, bandejas, unidades, kg, etc.
 * - fecha_registro: Fecha del registro de rendimiento
 * - turno: mañana, tarde, noche
 * - observaciones: Notas adicionales
 */

add_action('add_meta_boxes', 'agrochamba_rendimiento_meta_boxes');

function agrochamba_rendimiento_meta_boxes()
{
    add_meta_box(
        'rendimiento_datos',
        'Datos del Rendimiento',
        'agrochamba_rendimiento_meta_box_callback',
        'rendimiento',
        'normal',
        'high'
    );
}

function agrochamba_rendimiento_meta_box_callback($post)
{
    wp_nonce_field('agrochamba_rendimiento_nonce', 'rendimiento_nonce');

    $empresa_id = get_post_meta($post->ID, '_empresa_id', true);
    $trabajador_id = get_post_meta($post->ID, '_trabajador_id', true);
    $trabajador_nombre = get_post_meta($post->ID, '_trabajador_nombre', true);
    $trabajador_dni = get_post_meta($post->ID, '_trabajador_dni', true);
    $categoria = get_post_meta($post->ID, '_categoria', true);
    $valor = get_post_meta($post->ID, '_valor', true);
    $unidad = get_post_meta($post->ID, '_unidad', true);
    $fecha_registro = get_post_meta($post->ID, '_fecha_registro', true);
    $turno = get_post_meta($post->ID, '_turno', true);
    $observaciones = get_post_meta($post->ID, '_observaciones', true);

    $categorias = array(
        'embalaje' => 'Embalaje',
        'seleccion' => 'Selección',
        'clamshell' => 'Clamshell',
        'cosecha' => 'Cosecha',
        'poda' => 'Poda',
        'fumigacion' => 'Fumigación',
        'riego' => 'Riego',
        'otro' => 'Otro',
    );

    $unidades = array(
        'cajas' => 'Cajas',
        'bandejas' => 'Bandejas',
        'unidades' => 'Unidades',
        'kg' => 'Kilogramos',
        'jabas' => 'Jabas',
        'plantas' => 'Plantas',
    );

    $turnos = array(
        'manana' => 'Mañana',
        'tarde' => 'Tarde',
        'noche' => 'Noche',
        'completo' => 'Día Completo',
    );
    ?>
    <style>
        .rendimiento-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .rendimiento-form label { display: block; font-weight: 600; margin-bottom: 5px; }
        .rendimiento-form input, .rendimiento-form select, .rendimiento-form textarea { width: 100%; padding: 8px; }
        .rendimiento-form .full-width { grid-column: 1 / -1; }
    </style>
    <div class="rendimiento-form">
        <div>
            <label for="trabajador_nombre">Nombre del Trabajador</label>
            <input type="text" id="trabajador_nombre" name="trabajador_nombre" value="<?php echo esc_attr($trabajador_nombre); ?>" required>
        </div>
        <div>
            <label for="trabajador_dni">DNI del Trabajador</label>
            <input type="text" id="trabajador_dni" name="trabajador_dni" value="<?php echo esc_attr($trabajador_dni); ?>">
        </div>
        <div>
            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria" required>
                <option value="">Seleccionar...</option>
                <?php foreach ($categorias as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($categoria, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="valor">Valor/Cantidad</label>
            <input type="number" id="valor" name="valor" value="<?php echo esc_attr($valor); ?>" min="0" step="0.01" required>
        </div>
        <div>
            <label for="unidad">Unidad</label>
            <select id="unidad" name="unidad" required>
                <option value="">Seleccionar...</option>
                <?php foreach ($unidades as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($unidad, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="turno">Turno</label>
            <select id="turno" name="turno">
                <option value="">Seleccionar...</option>
                <?php foreach ($turnos as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($turno, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="fecha_registro">Fecha de Registro</label>
            <input type="date" id="fecha_registro" name="fecha_registro" value="<?php echo esc_attr($fecha_registro ?: date('Y-m-d')); ?>" required>
        </div>
        <div>
            <label for="trabajador_id">ID Usuario (opcional)</label>
            <input type="number" id="trabajador_id" name="trabajador_id" value="<?php echo esc_attr($trabajador_id); ?>" placeholder="Si el trabajador tiene cuenta">
        </div>
        <div class="full-width">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="3"><?php echo esc_textarea($observaciones); ?></textarea>
        </div>
    </div>
    <?php
}

add_action('save_post_rendimiento', 'agrochamba_save_rendimiento_meta');

function agrochamba_save_rendimiento_meta($post_id)
{
    if (!isset($_POST['rendimiento_nonce']) || !wp_verify_nonce($_POST['rendimiento_nonce'], 'agrochamba_rendimiento_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array(
        'trabajador_id',
        'trabajador_nombre',
        'trabajador_dni',
        'categoria',
        'valor',
        'unidad',
        'fecha_registro',
        'turno',
        'observaciones',
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Guardar empresa_id del usuario actual
    $user = wp_get_current_user();
    update_post_meta($post_id, '_empresa_id', $user->ID);
}

// ==========================================
// COLUMNAS ADMIN
// ==========================================

add_filter('manage_rendimiento_posts_columns', 'agrochamba_rendimiento_columns');

function agrochamba_rendimiento_columns($columns)
{
    $new_columns = array(
        'cb' => $columns['cb'],
        'title' => 'Trabajador',
        'categoria' => 'Categoría',
        'valor' => 'Valor',
        'fecha' => 'Fecha',
        'empresa' => 'Empresa',
    );
    return $new_columns;
}

add_action('manage_rendimiento_posts_custom_column', 'agrochamba_rendimiento_column_content', 10, 2);

function agrochamba_rendimiento_column_content($column, $post_id)
{
    switch ($column) {
        case 'categoria':
            $categoria = get_post_meta($post_id, '_categoria', true);
            $categorias = array(
                'embalaje' => 'Embalaje',
                'seleccion' => 'Selección',
                'clamshell' => 'Clamshell',
                'cosecha' => 'Cosecha',
                'poda' => 'Poda',
                'fumigacion' => 'Fumigación',
                'riego' => 'Riego',
                'otro' => 'Otro',
            );
            echo esc_html($categorias[$categoria] ?? $categoria);
            break;
        case 'valor':
            $valor = get_post_meta($post_id, '_valor', true);
            $unidad = get_post_meta($post_id, '_unidad', true);
            echo esc_html($valor . ' ' . $unidad);
            break;
        case 'fecha':
            $fecha = get_post_meta($post_id, '_fecha_registro', true);
            echo esc_html($fecha ? date_i18n('d M Y', strtotime($fecha)) : '-');
            break;
        case 'empresa':
            $empresa_id = get_post_meta($post_id, '_empresa_id', true);
            if ($empresa_id) {
                $empresa = get_userdata($empresa_id);
                echo esc_html($empresa ? $empresa->display_name : 'ID: ' . $empresa_id);
            }
            break;
    }
}

// ==========================================
// REST API ENDPOINTS
// ==========================================

add_action('rest_api_init', 'agrochamba_register_rendimiento_endpoints');

function agrochamba_register_rendimiento_endpoints()
{
    // GET /agrochamba/v1/rendimiento - Obtener rendimientos del usuario actual
    register_rest_route('agrochamba/v1', '/rendimiento', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_get_rendimiento',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // GET /agrochamba/v1/rendimiento/resumen - Obtener resumen/totales
    register_rest_route('agrochamba/v1', '/rendimiento/resumen', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_get_rendimiento_resumen',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // POST /agrochamba/v1/rendimiento - Registrar nuevo rendimiento (empresas)
    register_rest_route('agrochamba/v1', '/rendimiento', array(
        'methods'             => 'POST',
        'callback'            => 'agrochamba_create_rendimiento',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // GET /agrochamba/v1/rendimiento/empresa - Obtener rendimientos de trabajadores de una empresa
    register_rest_route('agrochamba/v1', '/rendimiento/empresa', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_get_rendimiento_empresa',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // DELETE /agrochamba/v1/rendimiento/{id}
    register_rest_route('agrochamba/v1', '/rendimiento/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'callback'            => 'agrochamba_delete_rendimiento',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));
}

/**
 * Obtener rendimientos del usuario actual (trabajador)
 */
function agrochamba_get_rendimiento(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $categoria = $request->get_param('categoria');
    $desde = $request->get_param('desde'); // fecha inicio
    $hasta = $request->get_param('hasta'); // fecha fin
    $per_page = intval($request->get_param('per_page')) ?: 50;

    // Buscar por user_id o por DNI del usuario
    $user = get_userdata($user_id);
    $user_dni = get_user_meta($user_id, 'dni', true);

    $meta_query = array(
        'relation' => 'OR',
        array(
            'key'   => '_trabajador_id',
            'value' => $user_id,
        ),
    );

    // Si el usuario tiene DNI, también buscar por DNI
    if ($user_dni) {
        $meta_query[] = array(
            'key'   => '_trabajador_dni',
            'value' => $user_dni,
        );
    }

    $args = array(
        'post_type'      => 'rendimiento',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
        'meta_query'     => array($meta_query),
        'orderby'        => 'meta_value',
        'meta_key'       => '_fecha_registro',
        'order'          => 'DESC',
    );

    // Filtro por categoría
    if ($categoria) {
        $args['meta_query'][] = array(
            'key'   => '_categoria',
            'value' => sanitize_text_field($categoria),
        );
    }

    $query = new WP_Query($args);
    $rendimientos = array();

    foreach ($query->posts as $post) {
        $rendimientos[] = agrochamba_format_rendimiento($post);
    }

    return rest_ensure_response(array(
        'success' => true,
        'data'    => $rendimientos,
        'total'   => $query->found_posts,
    ));
}

/**
 * Obtener resumen de rendimiento del usuario
 */
function agrochamba_get_rendimiento_resumen(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $periodo = $request->get_param('periodo') ?: 'semana'; // semana, mes, año
    $user_dni = get_user_meta($user_id, 'dni', true);

    // Calcular fecha de inicio según período
    $fecha_inicio = date('Y-m-d');
    switch ($periodo) {
        case 'semana':
            $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'mes':
            $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'año':
            $fecha_inicio = date('Y-m-d', strtotime('-365 days'));
            break;
    }

    $meta_query = array(
        'relation' => 'AND',
        array(
            'relation' => 'OR',
            array(
                'key'   => '_trabajador_id',
                'value' => $user_id,
            ),
        ),
        array(
            'key'     => '_fecha_registro',
            'value'   => $fecha_inicio,
            'compare' => '>=',
            'type'    => 'DATE',
        ),
    );

    if ($user_dni) {
        $meta_query[0][] = array(
            'key'   => '_trabajador_dni',
            'value' => $user_dni,
        );
    }

    $args = array(
        'post_type'      => 'rendimiento',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
    );

    $query = new WP_Query($args);

    // Agrupar por categoría
    $resumen = array();
    $total_general = 0;

    foreach ($query->posts as $post) {
        $categoria = get_post_meta($post->ID, '_categoria', true);
        $valor = floatval(get_post_meta($post->ID, '_valor', true));
        $unidad = get_post_meta($post->ID, '_unidad', true);
        $fecha = get_post_meta($post->ID, '_fecha_registro', true);

        if (!isset($resumen[$categoria])) {
            $resumen[$categoria] = array(
                'categoria'     => $categoria,
                'total'         => 0,
                'unidad'        => $unidad,
                'registros'     => 0,
                'ultima_fecha'  => $fecha,
                'tendencia'     => 'estable',
            );
        }

        $resumen[$categoria]['total'] += $valor;
        $resumen[$categoria]['registros']++;
        $total_general += $valor;

        // Actualizar última fecha si es más reciente
        if ($fecha > $resumen[$categoria]['ultima_fecha']) {
            $resumen[$categoria]['ultima_fecha'] = $fecha;
        }
    }

    // Calcular tendencias (comparando con período anterior)
    foreach ($resumen as $cat => &$data) {
        $data['tendencia'] = agrochamba_calcular_tendencia($user_id, $user_dni, $cat, $periodo);
    }

    // Convertir a array indexado
    $resumen_array = array_values($resumen);

    return rest_ensure_response(array(
        'success'       => true,
        'total_general' => $total_general,
        'periodo'       => $periodo,
        'fecha_inicio'  => $fecha_inicio,
        'categorias'    => $resumen_array,
    ));
}

/**
 * Calcular tendencia comparando con período anterior
 */
function agrochamba_calcular_tendencia($user_id, $user_dni, $categoria, $periodo)
{
    // Fechas del período actual
    $hoy = date('Y-m-d');
    $dias = $periodo === 'semana' ? 7 : ($periodo === 'mes' ? 30 : 365);
    $inicio_actual = date('Y-m-d', strtotime("-{$dias} days"));
    $inicio_anterior = date('Y-m-d', strtotime("-" . ($dias * 2) . " days"));

    // Total período actual
    $total_actual = agrochamba_sumar_rendimiento($user_id, $user_dni, $categoria, $inicio_actual, $hoy);

    // Total período anterior
    $total_anterior = agrochamba_sumar_rendimiento($user_id, $user_dni, $categoria, $inicio_anterior, $inicio_actual);

    if ($total_anterior == 0) {
        return $total_actual > 0 ? 'subiendo' : 'estable';
    }

    $diferencia = (($total_actual - $total_anterior) / $total_anterior) * 100;

    if ($diferencia > 5) {
        return 'subiendo';
    } elseif ($diferencia < -5) {
        return 'bajando';
    }
    return 'estable';
}

function agrochamba_sumar_rendimiento($user_id, $user_dni, $categoria, $desde, $hasta)
{
    $meta_query = array(
        'relation' => 'AND',
        array(
            'relation' => 'OR',
            array('key' => '_trabajador_id', 'value' => $user_id),
        ),
        array('key' => '_categoria', 'value' => $categoria),
        array('key' => '_fecha_registro', 'value' => $desde, 'compare' => '>=', 'type' => 'DATE'),
        array('key' => '_fecha_registro', 'value' => $hasta, 'compare' => '<=', 'type' => 'DATE'),
    );

    if ($user_dni) {
        $meta_query[0][] = array('key' => '_trabajador_dni', 'value' => $user_dni);
    }

    $args = array(
        'post_type'      => 'rendimiento',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
    );

    $query = new WP_Query($args);
    $total = 0;

    foreach ($query->posts as $post) {
        $total += floatval(get_post_meta($post->ID, '_valor', true));
    }

    return $total;
}

/**
 * Crear nuevo registro de rendimiento (para empresas)
 */
function agrochamba_create_rendimiento(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $params = $request->get_json_params();

    // Validar campos requeridos
    $required = array('trabajador_nombre', 'categoria', 'valor', 'unidad');
    foreach ($required as $field) {
        if (empty($params[$field])) {
            return new WP_Error('missing_field', "El campo {$field} es requerido", array('status' => 400));
        }
    }

    // Crear el post
    $post_data = array(
        'post_type'   => 'rendimiento',
        'post_status' => 'publish',
        'post_title'  => sanitize_text_field($params['trabajador_nombre']),
        'post_author' => $user->ID,
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return new WP_Error('create_failed', 'Error al crear el registro', array('status' => 500));
    }

    // Guardar meta fields
    update_post_meta($post_id, '_empresa_id', $user->ID);
    update_post_meta($post_id, '_trabajador_nombre', sanitize_text_field($params['trabajador_nombre']));
    update_post_meta($post_id, '_categoria', sanitize_text_field($params['categoria']));
    update_post_meta($post_id, '_valor', floatval($params['valor']));
    update_post_meta($post_id, '_unidad', sanitize_text_field($params['unidad']));
    update_post_meta($post_id, '_fecha_registro', sanitize_text_field($params['fecha_registro'] ?? date('Y-m-d')));

    // Campos opcionales
    if (!empty($params['trabajador_id'])) {
        update_post_meta($post_id, '_trabajador_id', intval($params['trabajador_id']));
    }
    if (!empty($params['trabajador_dni'])) {
        update_post_meta($post_id, '_trabajador_dni', sanitize_text_field($params['trabajador_dni']));
    }
    if (!empty($params['turno'])) {
        update_post_meta($post_id, '_turno', sanitize_text_field($params['turno']));
    }
    if (!empty($params['observaciones'])) {
        update_post_meta($post_id, '_observaciones', sanitize_textarea_field($params['observaciones']));
    }

    $post = get_post($post_id);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Rendimiento registrado exitosamente',
        'data'    => agrochamba_format_rendimiento($post),
    ));
}

/**
 * Obtener rendimientos de trabajadores (para empresas)
 */
function agrochamba_get_rendimiento_empresa(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $trabajador_dni = $request->get_param('dni');
    $trabajador_nombre = $request->get_param('nombre');
    $categoria = $request->get_param('categoria');
    $desde = $request->get_param('desde');
    $hasta = $request->get_param('hasta');
    $per_page = intval($request->get_param('per_page')) ?: 50;

    $meta_query = array(
        array(
            'key'   => '_empresa_id',
            'value' => $user->ID,
        ),
    );

    if ($trabajador_dni) {
        $meta_query[] = array(
            'key'   => '_trabajador_dni',
            'value' => sanitize_text_field($trabajador_dni),
        );
    }

    if ($categoria) {
        $meta_query[] = array(
            'key'   => '_categoria',
            'value' => sanitize_text_field($categoria),
        );
    }

    if ($desde) {
        $meta_query[] = array(
            'key'     => '_fecha_registro',
            'value'   => sanitize_text_field($desde),
            'compare' => '>=',
            'type'    => 'DATE',
        );
    }

    if ($hasta) {
        $meta_query[] = array(
            'key'     => '_fecha_registro',
            'value'   => sanitize_text_field($hasta),
            'compare' => '<=',
            'type'    => 'DATE',
        );
    }

    $args = array(
        'post_type'      => 'rendimiento',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'orderby'        => 'meta_value',
        'meta_key'       => '_fecha_registro',
        'order'          => 'DESC',
    );

    // Búsqueda por nombre
    if ($trabajador_nombre) {
        $args['s'] = $trabajador_nombre;
    }

    $query = new WP_Query($args);
    $rendimientos = array();

    foreach ($query->posts as $post) {
        $rendimientos[] = agrochamba_format_rendimiento($post);
    }

    return rest_ensure_response(array(
        'success' => true,
        'data'    => $rendimientos,
        'total'   => $query->found_posts,
    ));
}

/**
 * Eliminar registro de rendimiento
 */
function agrochamba_delete_rendimiento(WP_REST_Request $request)
{
    $post_id = intval($request->get_param('id'));
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'rendimiento') {
        return new WP_Error('not_found', 'Rendimiento no encontrado', array('status' => 404));
    }

    $user = wp_get_current_user();
    $empresa_id = get_post_meta($post_id, '_empresa_id', true);

    // Solo el admin o la empresa que creó puede eliminar
    if (!in_array('administrator', $user->roles) && $empresa_id != $user->ID) {
        return new WP_Error('forbidden', 'No tienes permiso para eliminar este registro', array('status' => 403));
    }

    wp_delete_post($post_id, true);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Rendimiento eliminado exitosamente',
    ));
}

/**
 * Formatear rendimiento para respuesta API
 */
function agrochamba_format_rendimiento($post)
{
    $categorias_labels = array(
        'embalaje'   => 'Embalaje',
        'seleccion'  => 'Selección',
        'clamshell'  => 'Clamshell',
        'cosecha'    => 'Cosecha',
        'poda'       => 'Poda',
        'fumigacion' => 'Fumigación',
        'riego'      => 'Riego',
        'otro'       => 'Otro',
    );

    $categoria_raw = get_post_meta($post->ID, '_categoria', true);

    return array(
        'id'                 => $post->ID,
        'trabajador_id'      => get_post_meta($post->ID, '_trabajador_id', true),
        'trabajador_nombre'  => get_post_meta($post->ID, '_trabajador_nombre', true) ?: $post->post_title,
        'trabajador_dni'     => get_post_meta($post->ID, '_trabajador_dni', true),
        'categoria'          => $categoria_raw,
        'categoria_label'    => $categorias_labels[$categoria_raw] ?? $categoria_raw,
        'valor'              => floatval(get_post_meta($post->ID, '_valor', true)),
        'unidad'             => get_post_meta($post->ID, '_unidad', true),
        'fecha_registro'     => get_post_meta($post->ID, '_fecha_registro', true),
        'turno'              => get_post_meta($post->ID, '_turno', true),
        'observaciones'      => get_post_meta($post->ID, '_observaciones', true),
        'empresa_id'         => get_post_meta($post->ID, '_empresa_id', true),
        'created_at'         => $post->post_date,
    );
}
