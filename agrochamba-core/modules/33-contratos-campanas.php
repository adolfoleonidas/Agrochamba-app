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
        'expirado'   => 'Expirado', // Auto-expirado cuando trabajador acepta otra oferta
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

    // === TRABAJADORES DISPONIBLES (CRM DE TALENTO) ===

    // GET /trabajadores/disponibles - Lista de trabajadores libres
    register_rest_route('agrochamba/v1', '/trabajadores/disponibles', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_get_trabajadores_disponibles',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // GET /trabajadores/disponibles/resumen - Resumen por ubicación
    register_rest_route('agrochamba/v1', '/trabajadores/disponibles/resumen', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_get_resumen_disponibles',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // GET /trabajadores/mapa - Mapa de disponibilidad por zona
    register_rest_route('agrochamba/v1', '/trabajadores/mapa', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_get_mapa_trabajadores',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // === DISPONIBILIDAD DEL TRABAJADOR (ESTILO UBER) ===

    // GET /me/disponibilidad - Obtener mi estado de disponibilidad
    register_rest_route('agrochamba/v1', '/me/disponibilidad', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_get_mi_disponibilidad',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // PUT /me/disponibilidad - Actualizar mi disponibilidad (toggle tipo Uber)
    register_rest_route('agrochamba/v1', '/me/disponibilidad', array(
        'methods'             => 'PUT',
        'callback'            => 'agrochamba_api_set_mi_disponibilidad',
        'permission_callback' => function () {
            return is_user_logged_in();
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

    // Nota: Ya no bloqueamos si el trabajador tiene contrato activo.
    // El trabajador puede recibir ofertas y al aceptar una, el sistema
    // finalizará automáticamente cualquier contrato previo (traspaso automático).

    // Solo verificamos que no haya una oferta pendiente de la misma empresa
    $oferta_pendiente = get_posts(array(
        'post_type'      => 'contrato',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array('key' => '_trabajador_id', 'value' => $trabajador_id),
            array('key' => '_empresa_id', 'value' => $user->ID),
            array('key' => '_estado', 'value' => 'pendiente'),
        ),
    ));

    if (!empty($oferta_pendiente)) {
        return new WP_Error('offer_exists', 'Ya tienes una oferta pendiente para este trabajador', array('status' => 409));
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

    $nueva_empresa_id = get_post_meta($contrato_id, '_empresa_id', true);

    // ==========================================
    // TRASPASO AUTOMÁTICO
    // Si el trabajador tiene contrato activo con otra empresa,
    // finalizarlo automáticamente antes de activar el nuevo
    // ==========================================
    $contratos_activos = get_posts(array(
        'post_type'      => 'contrato',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array('key' => '_trabajador_id', 'value' => $user_id),
            array('key' => '_estado', 'value' => 'activo'),
        ),
    ));

    $empresa_anterior = null;
    foreach ($contratos_activos as $contrato_anterior) {
        $empresa_anterior_id = get_post_meta($contrato_anterior->ID, '_empresa_id', true);

        // Finalizar el contrato anterior
        update_post_meta($contrato_anterior->ID, '_estado', 'finalizado');
        update_post_meta($contrato_anterior->ID, '_fecha_finalizacion', current_time('mysql'));
        update_post_meta($contrato_anterior->ID, '_motivo_finalizacion', 'Traspaso automático - Trabajador aceptó contrato con otra empresa');

        // Ejecutar traspaso (guardar historial, etc.)
        agrochamba_ejecutar_traspaso_contrato($contrato_anterior->ID);

        // Guardar referencia de empresa anterior
        $empresa_anterior = get_userdata($empresa_anterior_id);

        // Log del traspaso
        error_log("AgroChamba: Traspaso automático - Trabajador $user_id pasó de empresa $empresa_anterior_id a $nueva_empresa_id");
    }

    // Activar el nuevo contrato
    update_post_meta($contrato_id, '_estado', 'activo');
    update_post_meta($contrato_id, '_fecha_aceptacion', current_time('mysql'));

    // Marcar trabajador como no disponible (tiene contrato activo)
    update_user_meta($user_id, '_disponible', '0');
    update_user_meta($user_id, '_contrato_activo_id', $contrato_id);

    // Expirar automáticamente otras ofertas pendientes de otras empresas
    $ofertas_pendientes = get_posts(array(
        'post_type'      => 'contrato',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'post__not_in'   => array($contrato_id),
        'meta_query'     => array(
            array('key' => '_trabajador_id', 'value' => $user_id),
            array('key' => '_estado', 'value' => 'pendiente'),
        ),
    ));

    foreach ($ofertas_pendientes as $oferta) {
        update_post_meta($oferta->ID, '_estado', 'expirado');
        update_post_meta($oferta->ID, '_motivo_expiracion', 'Trabajador aceptó contrato con otra empresa');
        // TODO: Notificar a la empresa que su oferta expiró
    }

    // Hook para extensiones
    do_action('agrochamba_contrato_aceptado', $contrato_id, $user_id);

    // Mensaje según si hubo traspaso o no
    $mensaje = 'Contrato aceptado exitosamente';
    if ($empresa_anterior) {
        $mensaje = 'Contrato aceptado. Tu historial ha sido transferido desde ' . $empresa_anterior->display_name;
    }

    return rest_ensure_response(array(
        'success'           => true,
        'message'           => $mensaje,
        'data'              => agrochamba_format_contrato(get_post($contrato_id)),
        'traspaso_realizado'=> !empty($contratos_activos),
        'empresa_anterior'  => $empresa_anterior ? $empresa_anterior->display_name : null,
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
    $user = wp_get_current_user();
    $ubicacion = $request->get_param('ubicacion');
    $experiencia = $request->get_param('experiencia');
    $per_page = intval($request->get_param('per_page')) ?: 50;

    // Obtener coordenadas de la empresa para calcular distancia
    $empresa_lat = get_user_meta($user->ID, '_ubicacion_lat', true);
    $empresa_lng = get_user_meta($user->ID, '_ubicacion_lng', true);

    // Obtener IDs de trabajadores con contrato activo
    $con_contrato = agrochamba_get_trabajadores_con_contrato_activo();

    // Solo obtener trabajadores que activaron su disponibilidad (tipo Uber)
    $args = array(
        'role'       => 'subscriber',
        'number'     => -1, // Obtener todos, luego paginamos
        'exclude'    => $con_contrato,
        'meta_query' => array(
            array(
                'key'   => '_disponible_para_trabajo',
                'value' => '1',
            ),
        ),
    );

    $users = get_users($args);
    $trabajadores = array();

    foreach ($users as $user_item) {
        // Obtener historial y ranking
        $historial = get_user_meta($user_item->ID, '_historial_contratos', true) ?: array();

        // Obtener rendimiento total
        $rendimiento_total = agrochamba_get_rendimiento_total_trabajador($user_item->ID);

        // Obtener coordenadas del trabajador
        $worker_lat = get_user_meta($user_item->ID, '_ubicacion_lat', true);
        $worker_lng = get_user_meta($user_item->ID, '_ubicacion_lng', true);

        // Calcular distancia si tenemos coordenadas de ambos
        $distancia = null;
        if ($empresa_lat && $empresa_lng && $worker_lat && $worker_lng) {
            $distancia = agrochamba_calcular_distancia(
                floatval($empresa_lat),
                floatval($empresa_lng),
                floatval($worker_lat),
                floatval($worker_lng)
            );
        }

        $trabajadores[] = array(
            'id'               => $user_item->ID,
            'nombre'           => $user_item->display_name,
            'email'            => $user_item->user_email,
            'foto'             => get_user_meta($user_item->ID, 'profile_photo_url', true),
            'ubicacion'        => get_user_meta($user_item->ID, 'ubicacion', true),
            'lat'              => $worker_lat ? floatval($worker_lat) : null,
            'lng'              => $worker_lng ? floatval($worker_lng) : null,
            'distancia_km'     => $distancia,
            'experiencia'      => count($historial) . ' campañas',
            'rendimiento'      => $rendimiento_total,
            'disponible_desde' => get_user_meta($user_item->ID, '_fecha_disponible', true),
            'ultimo_empleador' => agrochamba_get_ultimo_empleador($user_item->ID),
        );
    }

    // Filtrar por ubicación si se especifica
    if ($ubicacion) {
        $trabajadores = array_filter($trabajadores, function ($t) use ($ubicacion) {
            return stripos($t['ubicacion'], $ubicacion) !== false;
        });
        $trabajadores = array_values($trabajadores);
    }

    // Ordenar por distancia (más cercanos primero) si hay coordenadas
    usort($trabajadores, function ($a, $b) {
        // Trabajadores sin distancia van al final
        if ($a['distancia_km'] === null && $b['distancia_km'] === null) return 0;
        if ($a['distancia_km'] === null) return 1;
        if ($b['distancia_km'] === null) return -1;
        return $a['distancia_km'] <=> $b['distancia_km'];
    });

    // Aplicar paginación después de ordenar
    $trabajadores = array_slice($trabajadores, 0, $per_page);

    return rest_ensure_response(array(
        'success' => true,
        'data'    => $trabajadores,
        'total'   => count($trabajadores),
    ));
}

/**
 * Obtener resumen de trabajadores disponibles por ubicación
 * Muestra cuántos trabajadores hay libres en cada zona
 * Solo incluye trabajadores que activaron su disponibilidad (tipo Uber)
 */
function agrochamba_api_get_resumen_disponibles(WP_REST_Request $request)
{
    // Obtener IDs de trabajadores con contrato activo
    $con_contrato = agrochamba_get_trabajadores_con_contrato_activo();

    // Solo trabajadores que activaron su disponibilidad (tipo Uber)
    $args = array(
        'role'       => 'subscriber',
        'number'     => -1, // Todos
        'exclude'    => $con_contrato,
        'meta_query' => array(
            array(
                'key'   => '_disponible_para_trabajo',
                'value' => '1',
            ),
        ),
    );

    $users = get_users($args);

    // Agrupar por ubicación
    $por_ubicacion = array();
    $sin_ubicacion = 0;
    $total_disponibles = 0;

    foreach ($users as $user) {
        $ubicacion = get_user_meta($user->ID, 'ubicacion', true);
        $ubicacion = trim($ubicacion);

        if (empty($ubicacion)) {
            $sin_ubicacion++;
        } else {
            // Normalizar ubicación (primera letra mayúscula)
            $ubicacion_normalizada = ucwords(strtolower($ubicacion));

            if (!isset($por_ubicacion[$ubicacion_normalizada])) {
                $por_ubicacion[$ubicacion_normalizada] = array(
                    'ubicacion'    => $ubicacion_normalizada,
                    'cantidad'     => 0,
                    'con_experiencia' => 0,
                    'rendimiento_promedio' => 0,
                    'trabajadores' => array(),
                );
            }

            $historial = get_user_meta($user->ID, '_historial_contratos', true) ?: array();
            $rendimiento = agrochamba_get_rendimiento_total_trabajador($user->ID);

            $por_ubicacion[$ubicacion_normalizada]['cantidad']++;
            $por_ubicacion[$ubicacion_normalizada]['rendimiento_promedio'] += $rendimiento;

            if (count($historial) > 0) {
                $por_ubicacion[$ubicacion_normalizada]['con_experiencia']++;
            }

            // Guardar referencia del trabajador (solo ID y nombre para no sobrecargar)
            $por_ubicacion[$ubicacion_normalizada]['trabajadores'][] = array(
                'id'     => $user->ID,
                'nombre' => $user->display_name,
            );
        }

        $total_disponibles++;
    }

    // Calcular promedio de rendimiento y ordenar por cantidad
    foreach ($por_ubicacion as $key => &$data) {
        if ($data['cantidad'] > 0) {
            $data['rendimiento_promedio'] = round($data['rendimiento_promedio'] / $data['cantidad'], 1);
        }
        // Limitar lista de trabajadores a los primeros 5
        $data['trabajadores'] = array_slice($data['trabajadores'], 0, 5);
    }

    // Ordenar por cantidad descendente
    uasort($por_ubicacion, function ($a, $b) {
        return $b['cantidad'] - $a['cantidad'];
    });

    return rest_ensure_response(array(
        'success'           => true,
        'total_disponibles' => $total_disponibles,
        'sin_ubicacion'     => $sin_ubicacion,
        'por_ubicacion'     => array_values($por_ubicacion),
    ));
}

/**
 * Obtener mapa de disponibilidad de trabajadores
 * Devuelve datos optimizados para visualizar en un mapa
 * Solo incluye trabajadores que activaron su disponibilidad (tipo Uber)
 */
function agrochamba_api_get_mapa_trabajadores(WP_REST_Request $request)
{
    // Obtener IDs de trabajadores con contrato activo
    $con_contrato = agrochamba_get_trabajadores_con_contrato_activo();

    // Solo trabajadores que activaron su disponibilidad
    $args = array(
        'role'       => 'subscriber',
        'number'     => -1,
        'exclude'    => $con_contrato,
        'meta_query' => array(
            array(
                'key'   => '_disponible_para_trabajo',
                'value' => '1',
            ),
        ),
    );

    $users = get_users($args);

    // Ubicaciones principales del agro peruano con coordenadas aproximadas
    $ubicaciones_peru = array(
        'ica'         => array('lat' => -14.0678, 'lng' => -75.7286, 'nombre' => 'Ica'),
        'lima'        => array('lat' => -12.0464, 'lng' => -77.0428, 'nombre' => 'Lima'),
        'la libertad' => array('lat' => -8.1159,  'lng' => -79.0300, 'nombre' => 'La Libertad'),
        'piura'       => array('lat' => -5.1945,  'lng' => -80.6328, 'nombre' => 'Piura'),
        'lambayeque'  => array('lat' => -6.7011,  'lng' => -79.9065, 'nombre' => 'Lambayeque'),
        'arequipa'    => array('lat' => -16.4090, 'lng' => -71.5375, 'nombre' => 'Arequipa'),
        'ancash'      => array('lat' => -9.5300,  'lng' => -77.5280, 'nombre' => 'Ancash'),
        'tacna'       => array('lat' => -18.0146, 'lng' => -70.2536, 'nombre' => 'Tacna'),
        'moquegua'    => array('lat' => -17.1940, 'lng' => -70.9355, 'nombre' => 'Moquegua'),
        'junin'       => array('lat' => -11.1585, 'lng' => -75.9931, 'nombre' => 'Junín'),
    );

    // Contar trabajadores por ubicación
    $mapa = array();
    $otros = 0;

    foreach ($users as $user) {
        $ubicacion = strtolower(trim(get_user_meta($user->ID, 'ubicacion', true)));

        $encontrado = false;
        foreach ($ubicaciones_peru as $key => $data) {
            if (strpos($ubicacion, $key) !== false) {
                if (!isset($mapa[$key])) {
                    $mapa[$key] = array(
                        'id'        => $key,
                        'nombre'    => $data['nombre'],
                        'lat'       => $data['lat'],
                        'lng'       => $data['lng'],
                        'cantidad'  => 0,
                    );
                }
                $mapa[$key]['cantidad']++;
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            $otros++;
        }
    }

    // Ordenar por cantidad
    uasort($mapa, function ($a, $b) {
        return $b['cantidad'] - $a['cantidad'];
    });

    return rest_ensure_response(array(
        'success'    => true,
        'ubicaciones'=> array_values($mapa),
        'otros'      => $otros,
        'total'      => count($users),
    ));
}

// ==========================================
// CALLBACKS API - DISPONIBILIDAD DEL TRABAJADOR
// ==========================================

/**
 * Obtener mi estado de disponibilidad
 * Tipo Uber: El trabajador indica si está buscando trabajo
 */
function agrochamba_api_get_mi_disponibilidad(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    // Verificar que es trabajador (no empresa)
    if (in_array('employer', $user->roles)) {
        return new WP_Error('not_worker', 'Solo trabajadores pueden gestionar disponibilidad', array('status' => 403));
    }

    $disponible_para_trabajo = get_user_meta($user_id, '_disponible_para_trabajo', true);
    $tiene_contrato_activo = agrochamba_trabajador_tiene_contrato_activo($user_id);
    $ubicacion = get_user_meta($user_id, 'ubicacion', true);
    $ubicacion_lat = get_user_meta($user_id, '_ubicacion_lat', true);
    $ubicacion_lng = get_user_meta($user_id, '_ubicacion_lng', true);

    // Un trabajador está "visible" para empresas si:
    // 1. Activó que está disponible para trabajo
    // 2. No tiene contrato activo
    $visible_para_empresas = ($disponible_para_trabajo === '1') && !$tiene_contrato_activo;

    return rest_ensure_response(array(
        'success'               => true,
        'disponible_para_trabajo' => $disponible_para_trabajo === '1',
        'tiene_contrato_activo' => $tiene_contrato_activo,
        'visible_para_empresas' => $visible_para_empresas,
        'ubicacion'             => $ubicacion ?: null,
        'ubicacion_lat'         => $ubicacion_lat ? floatval($ubicacion_lat) : null,
        'ubicacion_lng'         => $ubicacion_lng ? floatval($ubicacion_lng) : null,
        'mensaje'               => $visible_para_empresas
            ? 'Estás visible para empresas que buscan trabajadores'
            : ($tiene_contrato_activo
                ? 'Tienes un contrato activo. No estás visible para otras empresas.'
                : 'No estás visible. Activa tu disponibilidad para que las empresas te encuentren.'),
    ));
}

/**
 * Actualizar mi disponibilidad (toggle tipo Uber)
 * El trabajador indica si está buscando trabajo activamente
 */
function agrochamba_api_set_mi_disponibilidad(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $params = $request->get_json_params();

    // Verificar que es trabajador (no empresa)
    if (in_array('employer', $user->roles)) {
        return new WP_Error('not_worker', 'Solo trabajadores pueden gestionar disponibilidad', array('status' => 403));
    }

    // Campos actualizables
    if (isset($params['disponible'])) {
        $disponible = $params['disponible'] ? '1' : '0';
        update_user_meta($user_id, '_disponible_para_trabajo', $disponible);

        if ($disponible === '1') {
            update_user_meta($user_id, '_fecha_disponible', current_time('mysql'));
        }
    }

    // Actualizar ubicación si se proporciona
    if (isset($params['ubicacion'])) {
        update_user_meta($user_id, 'ubicacion', sanitize_text_field($params['ubicacion']));
    }

    // Actualizar coordenadas GPS si se proporcionan
    if (isset($params['lat']) && isset($params['lng'])) {
        update_user_meta($user_id, '_ubicacion_lat', floatval($params['lat']));
        update_user_meta($user_id, '_ubicacion_lng', floatval($params['lng']));
    }

    // Obtener estado actualizado
    $disponible_para_trabajo = get_user_meta($user_id, '_disponible_para_trabajo', true);
    $tiene_contrato_activo = agrochamba_trabajador_tiene_contrato_activo($user_id);
    $visible_para_empresas = ($disponible_para_trabajo === '1') && !$tiene_contrato_activo;

    return rest_ensure_response(array(
        'success'               => true,
        'disponible_para_trabajo' => $disponible_para_trabajo === '1',
        'tiene_contrato_activo' => $tiene_contrato_activo,
        'visible_para_empresas' => $visible_para_empresas,
        'mensaje'               => $visible_para_empresas
            ? 'Ahora estás visible para empresas'
            : 'Has desactivado tu disponibilidad',
    ));
}

/**
 * Verificar si un trabajador tiene contrato activo
 */
function agrochamba_trabajador_tiene_contrato_activo($user_id)
{
    $contratos = get_posts(array(
        'post_type'      => 'contrato',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array('key' => '_trabajador_id', 'value' => $user_id),
            array('key' => '_estado', 'value' => 'activo'),
        ),
    ));

    return !empty($contratos);
}

/**
 * Calcular distancia entre dos puntos (fórmula Haversine)
 * Retorna distancia en kilómetros
 */
function agrochamba_calcular_distancia($lat1, $lng1, $lat2, $lng2)
{
    $radio_tierra = 6371; // km

    $lat1_rad = deg2rad($lat1);
    $lat2_rad = deg2rad($lat2);
    $delta_lat = deg2rad($lat2 - $lat1);
    $delta_lng = deg2rad($lng2 - $lng1);

    $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($delta_lng / 2) * sin($delta_lng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return round($radio_tierra * $c, 1);
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

// ==========================================
// EXPIRACIÓN AUTOMÁTICA DE CAMPAÑAS
// ==========================================

/**
 * Registrar cron job para expiración automática
 * Se ejecuta diariamente a las 00:00
 */
add_action('init', 'agrochamba_schedule_expiracion_campanas');

function agrochamba_schedule_expiracion_campanas()
{
    if (!wp_next_scheduled('agrochamba_expiracion_campanas_cron')) {
        wp_schedule_event(strtotime('today midnight'), 'daily', 'agrochamba_expiracion_campanas_cron');
    }
}

add_action('agrochamba_expiracion_campanas_cron', 'agrochamba_ejecutar_expiracion_campanas');

/**
 * Ejecutar expiración automática de campañas
 * - Marca como finalizada las campañas cuya fecha_fin ya pasó
 * - Finaliza contratos asociados
 * - Registra metadata de expiración para analytics
 */
function agrochamba_ejecutar_expiracion_campanas()
{
    $hoy = current_time('Y-m-d');

    // Buscar campañas activas con fecha_fin pasada
    $campanas_expiradas = get_posts(array(
        'post_type'      => 'campana',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_estado',
                'value'   => 'activa',
            ),
            array(
                'key'     => '_fecha_fin',
                'value'   => $hoy,
                'compare' => '<',
                'type'    => 'DATE',
            ),
        ),
    ));

    $campanas_finalizadas = 0;
    $contratos_finalizados = 0;

    foreach ($campanas_expiradas as $campana) {
        // Marcar campaña como finalizada
        update_post_meta($campana->ID, '_estado', 'finalizada');
        update_post_meta($campana->ID, '_fecha_expiracion_auto', current_time('mysql'));
        update_post_meta($campana->ID, '_expirado_automaticamente', '1');
        $campanas_finalizadas++;

        // Finalizar contratos activos asociados a esta campaña
        $contratos = get_posts(array(
            'post_type'      => 'contrato',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array('key' => '_campana_id', 'value' => $campana->ID),
                array('key' => '_estado', 'value' => 'activo'),
            ),
        ));

        foreach ($contratos as $contrato) {
            update_post_meta($contrato->ID, '_estado', 'finalizado');
            update_post_meta($contrato->ID, '_fecha_finalizacion', current_time('mysql'));
            update_post_meta($contrato->ID, '_finalizado_por_expiracion', '1');

            // Ejecutar traspaso
            agrochamba_ejecutar_traspaso_contrato($contrato->ID);
            $contratos_finalizados++;
        }

        // Expirar ofertas pendientes de esta campaña
        $ofertas_pendientes = get_posts(array(
            'post_type'      => 'contrato',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array('key' => '_campana_id', 'value' => $campana->ID),
                array('key' => '_estado', 'value' => 'pendiente'),
            ),
        ));

        foreach ($ofertas_pendientes as $oferta) {
            update_post_meta($oferta->ID, '_estado', 'expirado');
            update_post_meta($oferta->ID, '_fecha_expiracion', current_time('mysql'));
        }
    }

    // Log para debugging
    if ($campanas_finalizadas > 0 || $contratos_finalizados > 0) {
        error_log("AgroChamba: Expiración automática - {$campanas_finalizadas} campañas, {$contratos_finalizados} contratos finalizados");
    }

    return array(
        'campanas_finalizadas'  => $campanas_finalizadas,
        'contratos_finalizados' => $contratos_finalizados,
    );
}

// Limpiar cron al desactivar plugin
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('agrochamba_expiracion_campanas_cron');
});

// ==========================================
// ANALYTICS Y ESTADÍSTICAS HISTÓRICAS
// ==========================================

add_action('rest_api_init', 'agrochamba_register_analytics_routes');

function agrochamba_register_analytics_routes()
{
    // GET /analytics/campanas - Estadísticas de campañas
    register_rest_route('agrochamba/v1', '/analytics/campanas', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_analytics_campanas',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // GET /analytics/contratos - Estadísticas de contratos
    register_rest_route('agrochamba/v1', '/analytics/contratos', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_analytics_contratos',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));

    // GET /analytics/tendencias - Tendencias por período
    register_rest_route('agrochamba/v1', '/analytics/tendencias', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_api_analytics_tendencias',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return in_array('administrator', $user->roles) || in_array('employer', $user->roles);
        },
    ));
}

/**
 * Estadísticas de campañas
 */
function agrochamba_api_analytics_campanas(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $año = $request->get_param('año') ?: date('Y');
    $empresa_id = $request->get_param('empresa_id');

    // Si no es admin, solo puede ver sus propias campañas
    if (!in_array('administrator', $user->roles)) {
        $empresa_id = $user->ID;
    }

    $meta_query = array();
    if ($empresa_id) {
        $meta_query[] = array('key' => '_empresa_id', 'value' => $empresa_id);
    }

    // Todas las campañas
    $todas = get_posts(array(
        'post_type'      => 'campana',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'date_query'     => array(
            array('year' => $año),
        ),
    ));

    // Agrupar por estado
    $por_estado = array(
        'activa'     => 0,
        'pausada'    => 0,
        'finalizada' => 0,
        'cancelada'  => 0,
    );

    // Agrupar por cultivo
    $por_cultivo = array();

    // Agrupar por ubicación
    $por_ubicacion = array();

    // Agrupar por mes
    $por_mes = array_fill(1, 12, 0);

    foreach ($todas as $campana) {
        $estado = get_post_meta($campana->ID, '_estado', true) ?: 'activa';
        $cultivo = get_post_meta($campana->ID, '_cultivo', true) ?: 'Sin especificar';
        $ubicacion = get_post_meta($campana->ID, '_ubicacion', true) ?: 'Sin especificar';
        $mes = intval(date('n', strtotime($campana->post_date)));

        $por_estado[$estado] = ($por_estado[$estado] ?? 0) + 1;
        $por_cultivo[$cultivo] = ($por_cultivo[$cultivo] ?? 0) + 1;
        $por_ubicacion[$ubicacion] = ($por_ubicacion[$ubicacion] ?? 0) + 1;
        $por_mes[$mes]++;
    }

    // Ordenar por cantidad
    arsort($por_cultivo);
    arsort($por_ubicacion);

    return rest_ensure_response(array(
        'success'       => true,
        'año'           => $año,
        'total'         => count($todas),
        'por_estado'    => $por_estado,
        'por_cultivo'   => array_slice($por_cultivo, 0, 10, true),
        'por_ubicacion' => array_slice($por_ubicacion, 0, 10, true),
        'por_mes'       => $por_mes,
    ));
}

/**
 * Estadísticas de contratos
 */
function agrochamba_api_analytics_contratos(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $año = $request->get_param('año') ?: date('Y');
    $empresa_id = $request->get_param('empresa_id');

    if (!in_array('administrator', $user->roles)) {
        $empresa_id = $user->ID;
    }

    $meta_query = array();
    if ($empresa_id) {
        $meta_query[] = array('key' => '_empresa_id', 'value' => $empresa_id);
    }

    $todos = get_posts(array(
        'post_type'      => 'contrato',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'date_query'     => array(
            array('year' => $año),
        ),
    ));

    $por_estado = array(
        'pendiente'  => 0,
        'activo'     => 0,
        'finalizado' => 0,
        'cancelado'  => 0,
        'rechazado'  => 0,
        'expirado'   => 0,
    );

    $total_aceptados = 0;
    $total_rechazados = 0;
    $tiempos_respuesta = array(); // días entre oferta y aceptación/rechazo

    foreach ($todos as $contrato) {
        $estado = get_post_meta($contrato->ID, '_estado', true) ?: 'pendiente';
        $por_estado[$estado] = ($por_estado[$estado] ?? 0) + 1;

        if ($estado === 'activo' || $estado === 'finalizado') {
            $total_aceptados++;

            $fecha_oferta = get_post_meta($contrato->ID, '_fecha_oferta', true);
            $fecha_aceptacion = get_post_meta($contrato->ID, '_fecha_aceptacion', true);

            if ($fecha_oferta && $fecha_aceptacion) {
                $dias = (strtotime($fecha_aceptacion) - strtotime($fecha_oferta)) / 86400;
                $tiempos_respuesta[] = max(0, $dias);
            }
        }

        if ($estado === 'rechazado') {
            $total_rechazados++;
        }
    }

    $tasa_aceptacion = count($todos) > 0
        ? round(($total_aceptados / count($todos)) * 100, 1)
        : 0;

    $tiempo_promedio_respuesta = count($tiempos_respuesta) > 0
        ? round(array_sum($tiempos_respuesta) / count($tiempos_respuesta), 1)
        : 0;

    return rest_ensure_response(array(
        'success'                   => true,
        'año'                       => $año,
        'total'                     => count($todos),
        'por_estado'                => $por_estado,
        'tasa_aceptacion'           => $tasa_aceptacion,
        'tiempo_promedio_respuesta' => $tiempo_promedio_respuesta,
        'total_aceptados'           => $total_aceptados,
        'total_rechazados'          => $total_rechazados,
    ));
}

/**
 * Tendencias históricas
 */
function agrochamba_api_analytics_tendencias(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $años = $request->get_param('años') ?: 3;
    $empresa_id = $request->get_param('empresa_id');

    if (!in_array('administrator', $user->roles)) {
        $empresa_id = $user->ID;
    }

    $año_actual = intval(date('Y'));
    $tendencias = array();

    for ($i = 0; $i < $años; $i++) {
        $año = $año_actual - $i;

        $meta_query = array();
        if ($empresa_id) {
            $meta_query[] = array('key' => '_empresa_id', 'value' => $empresa_id);
        }

        // Contar campañas del año
        $campanas = get_posts(array(
            'post_type'      => 'campana',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => $meta_query,
            'date_query'     => array(array('year' => $año)),
            'fields'         => 'ids',
        ));

        // Contar contratos del año
        $contratos = get_posts(array(
            'post_type'      => 'contrato',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => $meta_query,
            'date_query'     => array(array('year' => $año)),
            'fields'         => 'ids',
        ));

        // Contar trabajadores únicos contratados
        $trabajadores_unicos = array();
        foreach ($contratos as $contrato_id) {
            $trabajador = get_post_meta($contrato_id, '_trabajador_id', true);
            if ($trabajador) {
                $trabajadores_unicos[$trabajador] = true;
            }
        }

        $tendencias[] = array(
            'año'                  => $año,
            'campanas'             => count($campanas),
            'contratos'            => count($contratos),
            'trabajadores_unicos'  => count($trabajadores_unicos),
        );
    }

    // Ordenar por año ascendente
    usort($tendencias, function ($a, $b) {
        return $a['año'] - $b['año'];
    });

    return rest_ensure_response(array(
        'success'    => true,
        'tendencias' => $tendencias,
    ));
}
