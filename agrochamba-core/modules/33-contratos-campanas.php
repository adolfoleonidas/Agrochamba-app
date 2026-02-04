<?php
/**
 * Módulo: Sistema de Contratos y Campañas
 *
 * Gestiona contratos laborales entre empresas y trabajadores.
 * Implementa el traspaso automático de perfiles cuando finaliza un contrato.
 *
 * Principio base: Los trabajadores son dueños de su historial.
 * Las empresas solo acceden a datos de trabajadores con contrato activo.
 *
 * @package AgroChamba
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// ==========================================
// CUSTOM POST TYPES
// ==========================================

add_action('init', 'agrochamba_register_contratos_cpt');

function agrochamba_register_contratos_cpt()
{
    // CPT: Campaña (ofertas de trabajo de temporada)
    register_post_type('campana', array(
        'labels' => array(
            'name'               => 'Campañas',
            'singular_name'      => 'Campaña',
            'menu_name'          => 'Campañas',
            'add_new'            => 'Nueva Campaña',
            'add_new_item'       => 'Crear Nueva Campaña',
            'edit_item'          => 'Editar Campaña',
            'view_item'          => 'Ver Campaña',
            'search_items'       => 'Buscar Campañas',
            'not_found'          => 'No se encontraron campañas',
        ),
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => false,
        'capability_type'     => 'post',
        'has_archive'         => false,
        'hierarchical'        => false,
        'menu_position'       => 26,
        'menu_icon'           => 'dashicons-calendar-alt',
        'supports'            => array('title', 'editor'),
        'show_in_rest'        => false,
    ));

    // CPT: Contrato (relación trabajador-empresa-campaña)
    register_post_type('contrato', array(
        'labels' => array(
            'name'               => 'Contratos',
            'singular_name'      => 'Contrato',
            'menu_name'          => 'Contratos',
            'add_new'            => 'Nuevo Contrato',
            'add_new_item'       => 'Crear Nuevo Contrato',
            'edit_item'          => 'Editar Contrato',
            'view_item'          => 'Ver Contrato',
            'search_items'       => 'Buscar Contratos',
            'not_found'          => 'No se encontraron contratos',
        ),
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => false,
        'capability_type'     => 'post',
        'has_archive'         => false,
        'hierarchical'        => false,
        'menu_position'       => 27,
        'menu_icon'           => 'dashicons-media-text',
        'supports'            => array('title'),
        'show_in_rest'        => false,
    ));
}

// ==========================================
// ESTADOS DE CONTRATO
// ==========================================

/**
 * Estados posibles de un contrato:
 * - pendiente: Empresa ofreció, trabajador aún no acepta
 * - activo: Trabajador aceptó, contrato vigente
 * - finalizado: Campaña terminó normalmente
 * - cancelado: Cancelado por alguna de las partes
 * - rechazado: Trabajador rechazó la oferta
 */
function agrochamba_get_estados_contrato()
{
    return array(
        'pendiente'  => 'Pendiente',
        'activo'     => 'Activo',
        'finalizado' => 'Finalizado',
        'cancelado'  => 'Cancelado',
        'rechazado'  => 'Rechazado',
    );
}

// ==========================================
// META BOXES - CAMPAÑA
// ==========================================

add_action('add_meta_boxes', 'agrochamba_campana_meta_boxes');

function agrochamba_campana_meta_boxes()
{
    add_meta_box(
        'campana_datos',
        'Datos de la Campaña',
        'agrochamba_campana_meta_box_callback',
        'campana',
        'normal',
        'high'
    );
}

function agrochamba_campana_meta_box_callback($post)
{
    wp_nonce_field('agrochamba_campana_nonce', 'campana_nonce');

    $empresa_id = get_post_meta($post->ID, '_empresa_id', true);
    $fecha_inicio = get_post_meta($post->ID, '_fecha_inicio', true);
    $fecha_fin = get_post_meta($post->ID, '_fecha_fin', true);
    $ubicacion = get_post_meta($post->ID, '_ubicacion', true);
    $cultivo = get_post_meta($post->ID, '_cultivo', true);
    $tipo_trabajo = get_post_meta($post->ID, '_tipo_trabajo', true);
    $vacantes = get_post_meta($post->ID, '_vacantes', true);
    $salario_referencial = get_post_meta($post->ID, '_salario_referencial', true);
    $estado = get_post_meta($post->ID, '_estado', true) ?: 'activa';
    $requisitos = get_post_meta($post->ID, '_requisitos', true);

    $tipos_trabajo = array(
        'cosecha'     => 'Cosecha',
        'embalaje'    => 'Embalaje',
        'seleccion'   => 'Selección',
        'poda'        => 'Poda',
        'fumigacion'  => 'Fumigación',
        'riego'       => 'Riego',
        'siembra'     => 'Siembra',
        'mantenimiento' => 'Mantenimiento',
        'otro'        => 'Otro',
    );
    ?>
    <style>
        .campana-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .campana-form label { display: block; font-weight: 600; margin-bottom: 5px; }
        .campana-form input, .campana-form select, .campana-form textarea { width: 100%; padding: 8px; }
        .campana-form .full-width { grid-column: 1 / -1; }
    </style>
    <div class="campana-form">
        <div>
            <label for="fecha_inicio">Fecha de Inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo esc_attr($fecha_inicio); ?>" required>
        </div>
        <div>
            <label for="fecha_fin">Fecha de Fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>" required>
        </div>
        <div>
            <label for="ubicacion">Ubicación/Fundo</label>
            <input type="text" id="ubicacion" name="ubicacion" value="<?php echo esc_attr($ubicacion); ?>">
        </div>
        <div>
            <label for="cultivo">Cultivo</label>
            <input type="text" id="cultivo" name="cultivo" value="<?php echo esc_attr($cultivo); ?>" placeholder="Ej: Uva, Arándano, Espárrago">
        </div>
        <div>
            <label for="tipo_trabajo">Tipo de Trabajo</label>
            <select id="tipo_trabajo" name="tipo_trabajo">
                <option value="">Seleccionar...</option>
                <?php foreach ($tipos_trabajo as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($tipo_trabajo, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="vacantes">Número de Vacantes</label>
            <input type="number" id="vacantes" name="vacantes" value="<?php echo esc_attr($vacantes); ?>" min="1">
        </div>
        <div>
            <label for="salario_referencial">Salario Referencial</label>
            <input type="text" id="salario_referencial" name="salario_referencial" value="<?php echo esc_attr($salario_referencial); ?>" placeholder="Ej: S/. 50-70 diario">
        </div>
        <div>
            <label for="estado_campana">Estado</label>
            <select id="estado_campana" name="estado_campana">
                <option value="activa" <?php selected($estado, 'activa'); ?>>Activa</option>
                <option value="pausada" <?php selected($estado, 'pausada'); ?>>Pausada</option>
                <option value="finalizada" <?php selected($estado, 'finalizada'); ?>>Finalizada</option>
                <option value="cancelada" <?php selected($estado, 'cancelada'); ?>>Cancelada</option>
            </select>
        </div>
        <div class="full-width">
            <label for="requisitos">Requisitos</label>
            <textarea id="requisitos" name="requisitos" rows="3" placeholder="Experiencia requerida, documentos, etc."><?php echo esc_textarea($requisitos); ?></textarea>
        </div>
    </div>
    <?php
}

add_action('save_post_campana', 'agrochamba_save_campana_meta');

function agrochamba_save_campana_meta($post_id)
{
    if (!isset($_POST['campana_nonce']) || !wp_verify_nonce($_POST['campana_nonce'], 'agrochamba_campana_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $fields = array('fecha_inicio', 'fecha_fin', 'ubicacion', 'cultivo', 'tipo_trabajo', 'vacantes', 'salario_referencial', 'requisitos');

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }

    if (isset($_POST['estado_campana'])) {
        update_post_meta($post_id, '_estado', sanitize_text_field($_POST['estado_campana']));
    }

    // Guardar empresa_id del usuario actual
    $user = wp_get_current_user();
    if (!get_post_meta($post_id, '_empresa_id', true)) {
        update_post_meta($post_id, '_empresa_id', $user->ID);
    }
}

// ==========================================
// META BOXES - CONTRATO
// ==========================================

add_action('add_meta_boxes', 'agrochamba_contrato_meta_boxes');

function agrochamba_contrato_meta_boxes()
{
    add_meta_box(
        'contrato_datos',
        'Datos del Contrato',
        'agrochamba_contrato_meta_box_callback',
        'contrato',
        'normal',
        'high'
    );
}

function agrochamba_contrato_meta_box_callback($post)
{
    wp_nonce_field('agrochamba_contrato_nonce', 'contrato_nonce');

    $empresa_id = get_post_meta($post->ID, '_empresa_id', true);
    $trabajador_id = get_post_meta($post->ID, '_trabajador_id', true);
    $campana_id = get_post_meta($post->ID, '_campana_id', true);
    $estado = get_post_meta($post->ID, '_estado', true) ?: 'pendiente';
    $fecha_oferta = get_post_meta($post->ID, '_fecha_oferta', true);
    $fecha_aceptacion = get_post_meta($post->ID, '_fecha_aceptacion', true);
    $fecha_inicio = get_post_meta($post->ID, '_fecha_inicio', true);
    $fecha_fin = get_post_meta($post->ID, '_fecha_fin', true);
    $puesto = get_post_meta($post->ID, '_puesto', true);
    $salario_acordado = get_post_meta($post->ID, '_salario_acordado', true);
    $notas = get_post_meta($post->ID, '_notas', true);

    $estados = agrochamba_get_estados_contrato();

    // Obtener empresas
    $empresas = get_users(array('role__in' => array('employer', 'administrator')));

    // Obtener campañas activas
    $campanas = get_posts(array(
        'post_type' => 'campana',
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => '_estado', 'value' => array('activa', 'pausada'), 'compare' => 'IN'),
        ),
    ));
    ?>
    <style>
        .contrato-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .contrato-form label { display: block; font-weight: 600; margin-bottom: 5px; }
        .contrato-form input, .contrato-form select, .contrato-form textarea { width: 100%; padding: 8px; }
        .contrato-form .full-width { grid-column: 1 / -1; }
        .estado-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; }
        .estado-pendiente { background: #fff3cd; color: #856404; }
        .estado-activo { background: #d4edda; color: #155724; }
        .estado-finalizado { background: #d1ecf1; color: #0c5460; }
        .estado-cancelado { background: #f8d7da; color: #721c24; }
    </style>
    <div class="contrato-form">
        <div>
            <label for="empresa_id">Empresa</label>
            <select id="empresa_id" name="empresa_id" required>
                <option value="">Seleccionar empresa...</option>
                <?php foreach ($empresas as $emp): ?>
                    <option value="<?php echo $emp->ID; ?>" <?php selected($empresa_id, $emp->ID); ?>><?php echo esc_html($emp->display_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="trabajador_id">ID del Trabajador</label>
            <input type="number" id="trabajador_id" name="trabajador_id" value="<?php echo esc_attr($trabajador_id); ?>" required>
            <?php if ($trabajador_id):
                $trabajador = get_userdata($trabajador_id);
                if ($trabajador): ?>
                    <small style="color: #666;">👤 <?php echo esc_html($trabajador->display_name); ?></small>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div>
            <label for="campana_id">Campaña (opcional)</label>
            <select id="campana_id" name="campana_id">
                <option value="">Sin campaña específica</option>
                <?php foreach ($campanas as $camp): ?>
                    <option value="<?php echo $camp->ID; ?>" <?php selected($campana_id, $camp->ID); ?>><?php echo esc_html($camp->post_title); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="estado_contrato">Estado</label>
            <select id="estado_contrato" name="estado_contrato">
                <?php foreach ($estados as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($estado, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="puesto">Puesto/Cargo</label>
            <input type="text" id="puesto" name="puesto" value="<?php echo esc_attr($puesto); ?>" placeholder="Ej: Operario de embalaje">
        </div>
        <div>
            <label for="salario_acordado">Salario Acordado</label>
            <input type="text" id="salario_acordado" name="salario_acordado" value="<?php echo esc_attr($salario_acordado); ?>" placeholder="Ej: S/. 60 diario">
        </div>
        <div>
            <label for="fecha_inicio_contrato">Fecha de Inicio</label>
            <input type="date" id="fecha_inicio_contrato" name="fecha_inicio_contrato" value="<?php echo esc_attr($fecha_inicio); ?>">
        </div>
        <div>
            <label for="fecha_fin_contrato">Fecha de Fin</label>
            <input type="date" id="fecha_fin_contrato" name="fecha_fin_contrato" value="<?php echo esc_attr($fecha_fin); ?>">
        </div>
        <div class="full-width">
            <label for="notas">Notas</label>
            <textarea id="notas" name="notas" rows="2"><?php echo esc_textarea($notas); ?></textarea>
        </div>
    </div>
    <?php if ($fecha_oferta || $fecha_aceptacion): ?>
    <div style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
        <strong>Historial:</strong><br>
        <?php if ($fecha_oferta): ?>📤 Oferta enviada: <?php echo esc_html($fecha_oferta); ?><br><?php endif; ?>
        <?php if ($fecha_aceptacion): ?>✅ Aceptado: <?php echo esc_html($fecha_aceptacion); ?><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php
}

add_action('save_post_contrato', 'agrochamba_save_contrato_meta');

function agrochamba_save_contrato_meta($post_id)
{
    if (!isset($_POST['contrato_nonce']) || !wp_verify_nonce($_POST['contrato_nonce'], 'agrochamba_contrato_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $fields = array(
        'empresa_id'     => 'empresa_id',
        'trabajador_id'  => 'trabajador_id',
        'campana_id'     => 'campana_id',
        'puesto'         => 'puesto',
        'salario_acordado' => 'salario_acordado',
        'notas'          => 'notas',
    );

    foreach ($fields as $post_field => $meta_key) {
        if (isset($_POST[$post_field])) {
            update_post_meta($post_id, '_' . $meta_key, sanitize_text_field($_POST[$post_field]));
        }
    }

    // Fechas
    if (isset($_POST['fecha_inicio_contrato'])) {
        update_post_meta($post_id, '_fecha_inicio', sanitize_text_field($_POST['fecha_inicio_contrato']));
    }
    if (isset($_POST['fecha_fin_contrato'])) {
        update_post_meta($post_id, '_fecha_fin', sanitize_text_field($_POST['fecha_fin_contrato']));
    }

    // Estado con lógica de transición
    if (isset($_POST['estado_contrato'])) {
        $nuevo_estado = sanitize_text_field($_POST['estado_contrato']);
        $estado_anterior = get_post_meta($post_id, '_estado', true);

        update_post_meta($post_id, '_estado', $nuevo_estado);

        // Si cambió a activo, registrar fecha de aceptación
        if ($nuevo_estado === 'activo' && $estado_anterior !== 'activo') {
            update_post_meta($post_id, '_fecha_aceptacion', current_time('mysql'));
        }

        // Si cambió a finalizado, ejecutar traspaso
        if ($nuevo_estado === 'finalizado' && $estado_anterior === 'activo') {
            agrochamba_ejecutar_traspaso_contrato($post_id);
        }
    }

    // Registrar fecha de oferta si es nuevo
    if (!get_post_meta($post_id, '_fecha_oferta', true)) {
        update_post_meta($post_id, '_fecha_oferta', current_time('mysql'));
    }
}

// ==========================================
// LÓGICA DE TRASPASO AUTOMÁTICO
// ==========================================

/**
 * Ejecutar traspaso cuando un contrato finaliza
 * - El trabajador queda disponible
 * - La empresa anterior pierde acceso a datos activos
 * - Se conserva solo historial (para reportes legales)
 */
function agrochamba_ejecutar_traspaso_contrato($contrato_id)
{
    $trabajador_id = get_post_meta($contrato_id, '_trabajador_id', true);
    $empresa_id = get_post_meta($contrato_id, '_empresa_id', true);

    if (!$trabajador_id || !$empresa_id) {
        return;
    }

    // Marcar al trabajador como disponible
    update_user_meta($trabajador_id, '_disponible', '1');
    update_user_meta($trabajador_id, '_ultimo_contrato_finalizado', $contrato_id);
    update_user_meta($trabajador_id, '_fecha_disponible', current_time('mysql'));

    // Registrar en historial del trabajador
    $historial = get_user_meta($trabajador_id, '_historial_contratos', true) ?: array();
    $historial[] = array(
        'contrato_id' => $contrato_id,
        'empresa_id'  => $empresa_id,
        'fecha_fin'   => current_time('mysql'),
    );
    update_user_meta($trabajador_id, '_historial_contratos', $historial);

    // Hook para extensiones
    do_action('agrochamba_contrato_finalizado', $contrato_id, $trabajador_id, $empresa_id);

    // Log
    error_log("AgroChamba: Contrato $contrato_id finalizado. Trabajador $trabajador_id disponible.");
}

/**
 * Verificar si una empresa tiene acceso a un trabajador
 * Solo si tiene contrato activo con ese trabajador
 */
function agrochamba_empresa_tiene_acceso_trabajador($empresa_id, $trabajador_id)
{
    // Admins siempre tienen acceso
    $user = get_userdata($empresa_id);
    if ($user && in_array('administrator', $user->roles)) {
        return true;
    }

    // Buscar contrato activo
    $contratos = get_posts(array(
        'post_type'      => 'contrato',
        'posts_per_page' => 1,
        'meta_query'     => array(
            'relation' => 'AND',
            array('key' => '_empresa_id', 'value' => $empresa_id),
            array('key' => '_trabajador_id', 'value' => $trabajador_id),
            array('key' => '_estado', 'value' => 'activo'),
        ),
    ));

    return !empty($contratos);
}

/**
 * Obtener trabajadores disponibles (sin contrato activo)
 */
function agrochamba_get_trabajadores_disponibles($args = array())
{
    $defaults = array(
        'role'       => 'subscriber',
        'number'     => 50,
        'meta_query' => array(
            'relation' => 'OR',
            array('key' => '_disponible', 'value' => '1'),
            array('key' => '_disponible', 'compare' => 'NOT EXISTS'),
        ),
    );

    $args = wp_parse_args($args, $defaults);

    // Excluir trabajadores con contrato activo
    $trabajadores_con_contrato = agrochamba_get_trabajadores_con_contrato_activo();

    $users = get_users($args);

    // Filtrar los que tienen contrato activo
    return array_filter($users, function ($user) use ($trabajadores_con_contrato) {
        return !in_array($user->ID, $trabajadores_con_contrato);
    });
}

/**
 * Obtener IDs de trabajadores con contrato activo
 */
function agrochamba_get_trabajadores_con_contrato_activo()
{
    global $wpdb;

    $results = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT pm.meta_value
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE p.post_type = 'contrato'
         AND p.post_status = 'publish'
         AND pm.meta_key = '_trabajador_id'
         AND pm2.meta_key = '_estado'
         AND pm2.meta_value = 'activo'"
    ));

    return array_map('intval', $results);
}

// ==========================================
// COLUMNAS ADMIN - CONTRATOS
// ==========================================

add_filter('manage_contrato_posts_columns', 'agrochamba_contrato_columns');

function agrochamba_contrato_columns($columns)
{
    return array(
        'cb'          => $columns['cb'],
        'title'       => 'Contrato',
        'trabajador'  => 'Trabajador',
        'empresa'     => 'Empresa',
        'estado'      => 'Estado',
        'fechas'      => 'Período',
        'date'        => 'Creado',
    );
}

add_action('manage_contrato_posts_custom_column', 'agrochamba_contrato_column_content', 10, 2);

function agrochamba_contrato_column_content($column, $post_id)
{
    switch ($column) {
        case 'trabajador':
            $trabajador_id = get_post_meta($post_id, '_trabajador_id', true);
            if ($trabajador_id) {
                $user = get_userdata($trabajador_id);
                echo $user ? esc_html($user->display_name) : 'ID: ' . $trabajador_id;
            }
            break;

        case 'empresa':
            $empresa_id = get_post_meta($post_id, '_empresa_id', true);
            if ($empresa_id) {
                $user = get_userdata($empresa_id);
                echo $user ? esc_html($user->display_name) : 'ID: ' . $empresa_id;
            }
            break;

        case 'estado':
            $estado = get_post_meta($post_id, '_estado', true) ?: 'pendiente';
            $estados = agrochamba_get_estados_contrato();
            $clase = 'estado-' . $estado;
            echo "<span class='estado-badge {$clase}'>" . esc_html($estados[$estado] ?? $estado) . "</span>";
            break;

        case 'fechas':
            $inicio = get_post_meta($post_id, '_fecha_inicio', true);
            $fin = get_post_meta($post_id, '_fecha_fin', true);
            if ($inicio && $fin) {
                echo esc_html(date_i18n('d/m/Y', strtotime($inicio))) . ' - ' . esc_html(date_i18n('d/m/Y', strtotime($fin)));
            } elseif ($inicio) {
                echo 'Desde ' . esc_html(date_i18n('d/m/Y', strtotime($inicio)));
            } else {
                echo '-';
            }
            break;
    }
}

// ==========================================
// COLUMNAS ADMIN - CAMPAÑAS
// ==========================================

add_filter('manage_campana_posts_columns', 'agrochamba_campana_columns');

function agrochamba_campana_columns($columns)
{
    return array(
        'cb'       => $columns['cb'],
        'title'    => 'Campaña',
        'empresa'  => 'Empresa',
        'cultivo'  => 'Cultivo',
        'fechas'   => 'Período',
        'vacantes' => 'Vacantes',
        'estado'   => 'Estado',
    );
}

add_action('manage_campana_posts_custom_column', 'agrochamba_campana_column_content', 10, 2);

function agrochamba_campana_column_content($column, $post_id)
{
    switch ($column) {
        case 'empresa':
            $empresa_id = get_post_meta($post_id, '_empresa_id', true);
            if ($empresa_id) {
                $user = get_userdata($empresa_id);
                echo $user ? esc_html($user->display_name) : 'ID: ' . $empresa_id;
            }
            break;

        case 'cultivo':
            echo esc_html(get_post_meta($post_id, '_cultivo', true) ?: '-');
            break;

        case 'fechas':
            $inicio = get_post_meta($post_id, '_fecha_inicio', true);
            $fin = get_post_meta($post_id, '_fecha_fin', true);
            if ($inicio && $fin) {
                echo esc_html(date_i18n('d/m/Y', strtotime($inicio))) . ' - ' . esc_html(date_i18n('d/m/Y', strtotime($fin)));
            }
            break;

        case 'vacantes':
            echo esc_html(get_post_meta($post_id, '_vacantes', true) ?: '-');
            break;

        case 'estado':
            $estado = get_post_meta($post_id, '_estado', true) ?: 'activa';
            $colores = array(
                'activa'     => '#28a745',
                'pausada'    => '#ffc107',
                'finalizada' => '#6c757d',
                'cancelada'  => '#dc3545',
            );
            $color = $colores[$estado] ?? '#6c757d';
            echo "<span style='color: {$color}; font-weight: bold;'>" . ucfirst(esc_html($estado)) . "</span>";
            break;
    }
}

// ==========================================
// REST API ENDPOINTS
// ==========================================

add_action('rest_api_init', 'agrochamba_register_contratos_endpoints');

function agrochamba_register_contratos_endpoints()
{
    // === CAMPAÑAS ===

    // GET /campanas - Listar campañas (públicas o de empresa)
    register_rest_route('agrochamba/v1', '/campanas', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_get_campanas',
        'permission_callback' => '__return_true',
    ));

    // POST /campanas - Crear campaña (empresas)
    register_rest_route('agrochamba/v1', '/campanas', array(
        'methods'             => 'POST',
        'callback'            => 'agrochamba_api_create_campana',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // === CONTRATOS ===

    // GET /contratos - Mis contratos (trabajador)
    register_rest_route('agrochamba/v1', '/contratos', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_get_mis_contratos',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // GET /contratos/empresa - Contratos de mi empresa
    register_rest_route('agrochamba/v1', '/contratos/empresa', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_get_contratos_empresa',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // POST /contratos - Crear oferta de contrato (empresa → trabajador)
    register_rest_route('agrochamba/v1', '/contratos', array(
        'methods'             => 'POST',
        'callback'            => 'agrochamba_api_crear_contrato',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // PUT /contratos/{id}/aceptar - Trabajador acepta contrato
    register_rest_route('agrochamba/v1', '/contratos/(?P<id>\d+)/aceptar', array(
        'methods'             => 'PUT',
        'callback'            => 'agrochamba_api_aceptar_contrato',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // PUT /contratos/{id}/rechazar - Trabajador rechaza contrato
    register_rest_route('agrochamba/v1', '/contratos/(?P<id>\d+)/rechazar', array(
        'methods'             => 'PUT',
        'callback'            => 'agrochamba_api_rechazar_contrato',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // PUT /contratos/{id}/finalizar - Empresa finaliza contrato
    register_rest_route('agrochamba/v1', '/contratos/(?P<id>\d+)/finalizar', array(
        'methods'             => 'PUT',
        'callback'            => 'agrochamba_api_finalizar_contrato',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // === TRABAJADORES DISPONIBLES ===

    // GET /trabajadores/disponibles - CRM de talento
    register_rest_route('agrochamba/v1', '/trabajadores/disponibles', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_get_trabajadores_disponibles',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));
}

// ==========================================
// CALLBACKS API - CAMPAÑAS
// ==========================================

function agrochamba_api_get_campanas(WP_REST_Request $request)
{
    $empresa_id = $request->get_param('empresa_id');
    $estado = $request->get_param('estado') ?: 'activa';
    $per_page = intval($request->get_param('per_page')) ?: 20;

    $meta_query = array();

    if ($empresa_id) {
        $meta_query[] = array('key' => '_empresa_id', 'value' => intval($empresa_id));
    }

    if ($estado !== 'todas') {
        $meta_query[] = array('key' => '_estado', 'value' => $estado);
    }

    $args = array(
        'post_type'      => 'campana',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query($args);
    $campanas = array();

    foreach ($query->posts as $post) {
        $campanas[] = agrochamba_format_campana($post);
    }

    return rest_ensure_response(array(
        'success' => true,
        'data'    => $campanas,
        'total'   => $query->found_posts,
    ));
}

function agrochamba_api_create_campana(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $params = $request->get_json_params();

    if (empty($params['titulo'])) {
        return new WP_Error('missing_title', 'El título es requerido', array('status' => 400));
    }

    $post_data = array(
        'post_type'    => 'campana',
        'post_status'  => 'publish',
        'post_title'   => sanitize_text_field($params['titulo']),
        'post_content' => sanitize_textarea_field($params['descripcion'] ?? ''),
        'post_author'  => $user->ID,
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return new WP_Error('create_failed', 'Error al crear la campaña', array('status' => 500));
    }

    // Guardar meta
    update_post_meta($post_id, '_empresa_id', $user->ID);
    update_post_meta($post_id, '_estado', 'activa');

    $campos = array('fecha_inicio', 'fecha_fin', 'ubicacion', 'cultivo', 'tipo_trabajo', 'vacantes', 'salario_referencial', 'requisitos');
    foreach ($campos as $campo) {
        if (!empty($params[$campo])) {
            update_post_meta($post_id, '_' . $campo, sanitize_text_field($params[$campo]));
        }
    }

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Campaña creada exitosamente',
        'data'    => agrochamba_format_campana(get_post($post_id)),
    ));
}

// ==========================================
// CALLBACKS API - CONTRATOS
// ==========================================

function agrochamba_api_get_mis_contratos(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $estado = $request->get_param('estado');

    $meta_query = array(
        array('key' => '_trabajador_id', 'value' => $user_id),
    );

    if ($estado) {
        $meta_query[] = array('key' => '_estado', 'value' => $estado);
    }

    $args = array(
        'post_type'      => 'contrato',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query($args);
    $contratos = array();

    foreach ($query->posts as $post) {
        $contratos[] = agrochamba_format_contrato($post);
    }

    return rest_ensure_response(array(
        'success' => true,
        'data'    => $contratos,
    ));
}

function agrochamba_api_get_contratos_empresa(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $estado = $request->get_param('estado');

    $meta_query = array(
        array('key' => '_empresa_id', 'value' => $user->ID),
    );

    if ($estado) {
        $meta_query[] = array('key' => '_estado', 'value' => $estado);
    }

    $args = array(
        'post_type'      => 'contrato',
        'posts_per_page' => 100,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query($args);
    $contratos = array();

    foreach ($query->posts as $post) {
        $contratos[] = agrochamba_format_contrato($post, true); // incluir datos del trabajador
    }

    return rest_ensure_response(array(
        'success' => true,
        'data'    => $contratos,
        'total'   => $query->found_posts,
    ));
}

function agrochamba_api_crear_contrato(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $params = $request->get_json_params();

    if (empty($params['trabajador_id'])) {
        return new WP_Error('missing_worker', 'El ID del trabajador es requerido', array('status' => 400));
    }

    $trabajador_id = intval($params['trabajador_id']);
    $trabajador = get_userdata($trabajador_id);

    if (!$trabajador) {
        return new WP_Error('worker_not_found', 'Trabajador no encontrado', array('status' => 404));
    }

    // Verificar que el trabajador no tenga contrato activo
    $contrato_activo = get_posts(array(
        'post_type'      => 'contrato',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array('key' => '_trabajador_id', 'value' => $trabajador_id),
            array('key' => '_estado', 'value' => 'activo'),
        ),
    ));

    if (!empty($contrato_activo)) {
        return new WP_Error('worker_busy', 'El trabajador ya tiene un contrato activo', array('status' => 409));
    }

    // Crear contrato
    $titulo = $trabajador->display_name . ' - ' . $user->display_name;
    $post_data = array(
        'post_type'   => 'contrato',
        'post_status' => 'publish',
        'post_title'  => $titulo,
        'post_author' => $user->ID,
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return new WP_Error('create_failed', 'Error al crear el contrato', array('status' => 500));
    }

    // Guardar meta
    update_post_meta($post_id, '_empresa_id', $user->ID);
    update_post_meta($post_id, '_trabajador_id', $trabajador_id);
    update_post_meta($post_id, '_estado', 'pendiente');
    update_post_meta($post_id, '_fecha_oferta', current_time('mysql'));

    $campos = array('campana_id', 'puesto', 'salario_acordado', 'fecha_inicio', 'fecha_fin', 'notas');
    foreach ($campos as $campo) {
        if (!empty($params[$campo])) {
            update_post_meta($post_id, '_' . $campo, sanitize_text_field($params[$campo]));
        }
    }

    // TODO: Enviar notificación al trabajador

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Oferta de contrato enviada',
        'data'    => agrochamba_format_contrato(get_post($post_id)),
    ));
}

function agrochamba_api_aceptar_contrato(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $contrato_id = intval($request->get_param('id'));

    $contrato = get_post($contrato_id);
    if (!$contrato || $contrato->post_type !== 'contrato') {
        return new WP_Error('not_found', 'Contrato no encontrado', array('status' => 404));
    }

    $trabajador_id = get_post_meta($contrato_id, '_trabajador_id', true);
    if ($trabajador_id != $user_id) {
        return new WP_Error('forbidden', 'No tienes permiso para aceptar este contrato', array('status' => 403));
    }

    $estado = get_post_meta($contrato_id, '_estado', true);
    if ($estado !== 'pendiente') {
        return new WP_Error('invalid_state', 'El contrato no está pendiente', array('status' => 400));
    }

    // Actualizar estado
    update_post_meta($contrato_id, '_estado', 'activo');
    update_post_meta($contrato_id, '_fecha_aceptacion', current_time('mysql'));

    // Marcar trabajador como no disponible
    update_user_meta($user_id, '_disponible', '0');
    update_user_meta($user_id, '_contrato_activo_id', $contrato_id);

    do_action('agrochamba_contrato_aceptado', $contrato_id, $user_id);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Contrato aceptado exitosamente',
        'data'    => agrochamba_format_contrato(get_post($contrato_id)),
    ));
}

function agrochamba_api_rechazar_contrato(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $contrato_id = intval($request->get_param('id'));
    $params = $request->get_json_params();

    $contrato = get_post($contrato_id);
    if (!$contrato || $contrato->post_type !== 'contrato') {
        return new WP_Error('not_found', 'Contrato no encontrado', array('status' => 404));
    }

    $trabajador_id = get_post_meta($contrato_id, '_trabajador_id', true);
    if ($trabajador_id != $user_id) {
        return new WP_Error('forbidden', 'No tienes permiso para rechazar este contrato', array('status' => 403));
    }

    $estado = get_post_meta($contrato_id, '_estado', true);
    if ($estado !== 'pendiente') {
        return new WP_Error('invalid_state', 'El contrato no está pendiente', array('status' => 400));
    }

    update_post_meta($contrato_id, '_estado', 'rechazado');

    if (!empty($params['motivo'])) {
        update_post_meta($contrato_id, '_motivo_rechazo', sanitize_textarea_field($params['motivo']));
    }

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Contrato rechazado',
    ));
}

function agrochamba_api_finalizar_contrato(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $contrato_id = intval($request->get_param('id'));

    $contrato = get_post($contrato_id);
    if (!$contrato || $contrato->post_type !== 'contrato') {
        return new WP_Error('not_found', 'Contrato no encontrado', array('status' => 404));
    }

    $empresa_id = get_post_meta($contrato_id, '_empresa_id', true);
    if ($empresa_id != $user->ID && !in_array('administrator', $user->roles)) {
        return new WP_Error('forbidden', 'No tienes permiso para finalizar este contrato', array('status' => 403));
    }

    $estado = get_post_meta($contrato_id, '_estado', true);
    if ($estado !== 'activo') {
        return new WP_Error('invalid_state', 'Solo se pueden finalizar contratos activos', array('status' => 400));
    }

    update_post_meta($contrato_id, '_estado', 'finalizado');
    update_post_meta($contrato_id, '_fecha_finalizacion', current_time('mysql'));

    // Ejecutar traspaso automático
    agrochamba_ejecutar_traspaso_contrato($contrato_id);

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Contrato finalizado. El trabajador ahora está disponible.',
    ));
}

// ==========================================
// CALLBACKS API - TRABAJADORES DISPONIBLES
// ==========================================

function agrochamba_api_get_trabajadores_disponibles(WP_REST_Request $request)
{
    $ubicacion = $request->get_param('ubicacion');
    $experiencia = $request->get_param('experiencia');
    $per_page = intval($request->get_param('per_page')) ?: 50;

    // Obtener IDs de trabajadores con contrato activo
    $con_contrato = agrochamba_get_trabajadores_con_contrato_activo();

    $args = array(
        'role'    => 'subscriber',
        'number'  => $per_page,
        'exclude' => $con_contrato,
    );

    $users = get_users($args);
    $trabajadores = array();

    foreach ($users as $user) {
        // Obtener historial y ranking
        $historial = get_user_meta($user->ID, '_historial_contratos', true) ?: array();

        // Obtener rendimiento total (usando el sistema ya implementado)
        $rendimiento_total = agrochamba_get_rendimiento_total_trabajador($user->ID);

        $trabajadores[] = array(
            'id'               => $user->ID,
            'nombre'           => $user->display_name,
            'email'            => $user->user_email,
            'foto'             => get_user_meta($user->ID, 'profile_photo_url', true),
            'ubicacion'        => get_user_meta($user->ID, 'ubicacion', true),
            'experiencia'      => count($historial) . ' campañas',
            'rendimiento'      => $rendimiento_total,
            'disponible_desde' => get_user_meta($user->ID, '_fecha_disponible', true),
            'ultimo_empleador' => agrochamba_get_ultimo_empleador($user->ID),
        );
    }

    return rest_ensure_response(array(
        'success' => true,
        'data'    => $trabajadores,
        'total'   => count($trabajadores),
    ));
}

/**
 * Obtener rendimiento total de un trabajador
 */
function agrochamba_get_rendimiento_total_trabajador($user_id)
{
    global $wpdb;

    $user_dni = get_user_meta($user_id, 'dni', true);

    $where_clause = $wpdb->prepare("pm.meta_key = '_trabajador_id' AND pm.meta_value = %d", $user_id);

    if ($user_dni) {
        $where_clause .= $wpdb->prepare(" OR (pm.meta_key = '_trabajador_dni' AND pm.meta_value = %s)", $user_dni);
    }

    $total = $wpdb->get_var("
        SELECT COALESCE(SUM(CAST(pm_valor.meta_value AS DECIMAL(10,2))), 0)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        INNER JOIN {$wpdb->postmeta} pm_valor ON p.ID = pm_valor.post_id AND pm_valor.meta_key = '_valor'
        WHERE p.post_type = 'rendimiento'
        AND p.post_status = 'publish'
        AND ({$where_clause})
    ");

    return floatval($total);
}

/**
 * Obtener último empleador del trabajador
 */
function agrochamba_get_ultimo_empleador($user_id)
{
    $historial = get_user_meta($user_id, '_historial_contratos', true) ?: array();

    if (empty($historial)) {
        return null;
    }

    $ultimo = end($historial);
    $empresa = get_userdata($ultimo['empresa_id']);

    return $empresa ? $empresa->display_name : null;
}

// ==========================================
// FORMATTERS
// ==========================================

function agrochamba_format_campana($post)
{
    $empresa_id = get_post_meta($post->ID, '_empresa_id', true);
    $empresa = get_userdata($empresa_id);

    return array(
        'id'                 => $post->ID,
        'titulo'             => $post->post_title,
        'descripcion'        => $post->post_content,
        'empresa_id'         => $empresa_id,
        'empresa_nombre'     => $empresa ? $empresa->display_name : null,
        'fecha_inicio'       => get_post_meta($post->ID, '_fecha_inicio', true),
        'fecha_fin'          => get_post_meta($post->ID, '_fecha_fin', true),
        'ubicacion'          => get_post_meta($post->ID, '_ubicacion', true),
        'cultivo'            => get_post_meta($post->ID, '_cultivo', true),
        'tipo_trabajo'       => get_post_meta($post->ID, '_tipo_trabajo', true),
        'vacantes'           => intval(get_post_meta($post->ID, '_vacantes', true)),
        'salario_referencial'=> get_post_meta($post->ID, '_salario_referencial', true),
        'requisitos'         => get_post_meta($post->ID, '_requisitos', true),
        'estado'             => get_post_meta($post->ID, '_estado', true) ?: 'activa',
        'created_at'         => $post->post_date,
    );
}

function agrochamba_format_contrato($post, $incluir_trabajador = false)
{
    $empresa_id = get_post_meta($post->ID, '_empresa_id', true);
    $trabajador_id = get_post_meta($post->ID, '_trabajador_id', true);
    $campana_id = get_post_meta($post->ID, '_campana_id', true);

    $empresa = get_userdata($empresa_id);
    $trabajador = get_userdata($trabajador_id);
    $campana = $campana_id ? get_post($campana_id) : null;

    $data = array(
        'id'               => $post->ID,
        'empresa_id'       => $empresa_id,
        'empresa_nombre'   => $empresa ? $empresa->display_name : null,
        'trabajador_id'    => $trabajador_id,
        'trabajador_nombre'=> $trabajador ? $trabajador->display_name : null,
        'campana_id'       => $campana_id,
        'campana_titulo'   => $campana ? $campana->post_title : null,
        'estado'           => get_post_meta($post->ID, '_estado', true) ?: 'pendiente',
        'puesto'           => get_post_meta($post->ID, '_puesto', true),
        'salario_acordado' => get_post_meta($post->ID, '_salario_acordado', true),
        'fecha_oferta'     => get_post_meta($post->ID, '_fecha_oferta', true),
        'fecha_aceptacion' => get_post_meta($post->ID, '_fecha_aceptacion', true),
        'fecha_inicio'     => get_post_meta($post->ID, '_fecha_inicio', true),
        'fecha_fin'        => get_post_meta($post->ID, '_fecha_fin', true),
        'notas'            => get_post_meta($post->ID, '_notas', true),
        'created_at'       => $post->post_date,
    );

    // Incluir datos adicionales del trabajador para empresas
    if ($incluir_trabajador && $trabajador) {
        $data['trabajador'] = array(
            'id'          => $trabajador->ID,
            'nombre'      => $trabajador->display_name,
            'email'       => $trabajador->user_email,
            'foto'        => get_user_meta($trabajador->ID, 'profile_photo_url', true),
            'telefono'    => get_user_meta($trabajador->ID, 'telefono', true),
            'dni'         => get_user_meta($trabajador->ID, 'dni', true),
            'rendimiento' => agrochamba_get_rendimiento_total_trabajador($trabajador->ID),
        );
    }

    return $data;
}

// ==========================================
// CSS ADMIN
// ==========================================

add_action('admin_head', 'agrochamba_contratos_admin_css');

function agrochamba_contratos_admin_css()
{
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, array('contrato', 'campana'))) {
        return;
    }
    ?>
    <style>
        .estado-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .estado-pendiente { background: #fff3cd; color: #856404; }
        .estado-activo { background: #d4edda; color: #155724; }
        .estado-finalizado { background: #d1ecf1; color: #0c5460; }
        .estado-cancelado, .estado-rechazado { background: #f8d7da; color: #721c24; }
    </style>
    <?php
}
