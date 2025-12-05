<?php
/**
 * =============================================================
 * MÓDULO 1: CUSTOM POST TYPE Y TAXONOMÍAS
 * =============================================================
 * 
 * Define la estructura base del sistema:
 * - Custom Post Type: 'trabajo'
 * - Taxonomías: ubicacion, empresa, tipo_puesto, cultivo
 * - Meta fields personalizados
 * - URLs jerárquicas
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. CUSTOM POST TYPE: TRABAJOS
// ==========================================
if (!function_exists('agrochamba_registrar_cpt_trabajos')) {
    function agrochamba_registrar_cpt_trabajos() {
        $labels = array(
            'name'                  => 'Trabajos Agrícolas',
            'singular_name'         => 'Trabajo',
            'menu_name'             => 'Trabajos',
            'all_items'             => 'Todos los Trabajos',
            'add_new'               => 'Agregar Nuevo',
            'add_new_item'          => 'Agregar Nuevo Trabajo',
            'edit_item'             => 'Editar Trabajo',
            'new_item'              => 'Nuevo Trabajo',
            'view_item'             => 'Ver Trabajo',
            'view_items'            => 'Ver Trabajos',
            'search_items'          => 'Buscar Trabajos',
            'not_found'             => 'No se encontraron trabajos',
            'not_found_in_trash'    => 'No hay trabajos en la papelera',
            'featured_image'        => 'Imagen del Trabajo',
            'set_featured_image'    => 'Establecer imagen',
            'remove_featured_image' => 'Eliminar imagen',
            'use_featured_image'    => 'Usar como imagen destacada',
            'archives'              => 'Archivo de Trabajos',
            'insert_into_item'      => 'Insertar en trabajo',
            'uploaded_to_this_item' => 'Subido a este trabajo',
            'filter_items_list'     => 'Filtrar lista de trabajos',
            'items_list_navigation' => 'Navegación de trabajos',
            'items_list'            => 'Lista de trabajos',
        );

        $args = array(
            'labels'              => $labels,
            'description'         => 'Ofertas de trabajo agrícola',
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'rest_base'           => 'trabajos',
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-businessman',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields'),
            'has_archive'         => true,
            'rewrite'             => array(
                'slug'       => 'trabajos/%ubicacion%',
                'with_front' => false,
            ),
            'query_var'           => true,
            'can_export'          => true,
            'delete_with_user'    => false,
        );

        register_post_type('trabajo', $args);
    }
    add_action('init', 'agrochamba_registrar_cpt_trabajos', 0);
}

// ==========================================
// 1.1. CUSTOM POST TYPE: EMPRESAS
// ==========================================
// Solo registrar el CPT legacy si la clase moderna NO está disponible
// Si la clase moderna está disponible, ella se encargará del registro del CPT
if (!function_exists('agrochamba_registrar_cpt_empresas') && !class_exists('AgroChamba\\PostTypes\\EmpresaPostType')) {
    function agrochamba_registrar_cpt_empresas() {
        $labels = array(
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
        );

        $args = array(
            'labels'              => $labels,
            'description'         => 'Información extendida de empresas agrícolas',
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'rest_base'           => 'empresas',
            'menu_position'       => 4,
            'menu_icon'           => 'dashicons-building',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields'),
            'has_archive'         => true,
            'rewrite'             => array(
                'slug'       => 'empresas',
                'with_front' => false,
            ),
            'query_var'           => true,
            'can_export'          => true,
            'delete_with_user'    => false,
        );

        register_post_type('empresa', $args);
    }
    add_action('init', 'agrochamba_registrar_cpt_empresas', 0);
}

// Cargar template personalizado para single empresa
if (!function_exists('agrochamba_load_empresa_template')) {
    function agrochamba_load_empresa_template($template) {
        if (is_singular('empresa')) {
            $custom_template = AGROCHAMBA_TEMPLATES_DIR . '/empresa-perfil.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }
    add_filter('single_template', 'agrochamba_load_empresa_template');
}

// Si el código moderno está disponible, también cargarlo para tener los meta boxes completos
// IMPORTANTE: No usar 'plugins_loaded' aquí porque este módulo se carga DURANTE 'plugins_loaded'
// Usar 'init' con prioridad temprana para asegurar que se ejecute después de que WordPress esté listo
if (class_exists('AgroChamba\\PostTypes\\EmpresaPostType')) {
    add_action('init', function() {
        if (class_exists('AgroChamba\\PostTypes\\EmpresaPostType')) {
            \AgroChamba\PostTypes\EmpresaPostType::register();
        }
    }, 5);
}

// Cargar template personalizado para single trabajo
if (!function_exists('agrochamba_load_trabajo_template')) {
    function agrochamba_load_trabajo_template($template) {
        if (is_singular('trabajo')) {
            $custom_template = AGROCHAMBA_TEMPLATES_DIR . '/single-trabajo.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }
    add_filter('single_template', 'agrochamba_load_trabajo_template');
}

// ==========================================
// 2. TAXONOMÍAS PERSONALIZADAS
// ==========================================
if (!function_exists('agrochamba_registrar_taxonomias')) {
    function agrochamba_registrar_taxonomias() {
        // UBICACIÓN
        register_taxonomy('ubicacion', 'trabajo', array(
            'labels' => array(
                'name'              => 'Ubicaciones',
                'singular_name'     => 'Ubicación',
                'search_items'      => 'Buscar Ubicaciones',
                'all_items'         => 'Todas las Ubicaciones',
                'parent_item'       => 'Ubicación Padre',
                'parent_item_colon' => 'Ubicación Padre:',
                'edit_item'         => 'Editar Ubicación',
                'update_item'       => 'Actualizar Ubicación',
                'add_new_item'      => 'Agregar Nueva Ubicación',
                'new_item_name'     => 'Nombre de Nueva Ubicación',
                'menu_name'         => 'Ubicaciones',
            ),
            'hierarchical'          => true,
            'public'                => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => true,
            'show_tagcloud'         => false,
            'show_in_quick_edit'    => true,
            'show_in_rest'          => true,
            'rest_base'             => 'ubicacion',
            'rewrite'               => array('slug' => 'ubicacion'),
        ));

        // EMPRESA
        register_taxonomy('empresa', 'trabajo', array(
            'labels' => array(
                'name'              => 'Empresas',
                'singular_name'     => 'Empresa',
                'search_items'      => 'Buscar Empresas',
                'all_items'         => 'Todas las Empresas',
                'edit_item'         => 'Editar Empresa',
                'update_item'       => 'Actualizar Empresa',
                'add_new_item'      => 'Agregar Nueva Empresa',
                'new_item_name'     => 'Nombre de Nueva Empresa',
                'menu_name'         => 'Empresas',
            ),
            'hierarchical'          => true,
            'public'                => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => true,
            'show_tagcloud'         => false,
            'show_in_quick_edit'    => true,
            'show_in_rest'          => true,
            'rest_base'             => 'empresa',
            'rewrite'               => array('slug' => 'empresa'),
        ));

        // TIPO DE PUESTO
        register_taxonomy('tipo_puesto', 'trabajo', array(
            'labels' => array(
                'name'              => 'Tipos de Puesto',
                'singular_name'     => 'Tipo de Puesto',
                'search_items'      => 'Buscar Tipos',
                'all_items'         => 'Todos los Tipos',
                'edit_item'         => 'Editar Tipo',
                'update_item'       => 'Actualizar Tipo',
                'add_new_item'      => 'Agregar Nuevo Tipo',
                'new_item_name'     => 'Nombre de Nuevo Tipo',
                'menu_name'         => 'Tipos de Puesto',
            ),
            'hierarchical'          => true,
            'public'                => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => true,
            'show_tagcloud'         => false,
            'show_in_quick_edit'    => true,
            'show_in_rest'          => true,
            'rest_base'             => 'tipo_puesto',
            'rewrite'               => array('slug' => 'tipo-puesto'),
        ));

        // TIPO DE CULTIVO
        register_taxonomy('cultivo', 'trabajo', array(
            'labels' => array(
                'name'              => 'Cultivos',
                'singular_name'     => 'Cultivo',
                'search_items'      => 'Buscar Cultivos',
                'all_items'         => 'Todos los Cultivos',
                'edit_item'         => 'Editar Cultivo',
                'update_item'       => 'Actualizar Cultivo',
                'add_new_item'      => 'Agregar Nuevo Cultivo',
                'new_item_name'     => 'Nombre de Nuevo Cultivo',
                'menu_name'         => 'Cultivos',
            ),
            'hierarchical'          => true,
            'public'                => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => true,
            'show_tagcloud'         => false,
            'show_in_quick_edit'    => true,
            'show_in_rest'          => true,
            'rest_base'             => 'cultivo',
            'rewrite'               => array('slug' => 'cultivo'),
        ));
    }
    add_action('init', 'agrochamba_registrar_taxonomias', 0);
}

// ==========================================
// 3. CAMPOS PERSONALIZADOS (META FIELDS)
// ==========================================
if (!function_exists('agrochamba_registrar_meta_fields')) {
    function agrochamba_registrar_meta_fields() {
        $meta_fields = array(
            'salario_min' => array('type' => 'number', 'description' => 'Salario mínimo ofrecido'),
            'salario_max' => array('type' => 'number', 'description' => 'Salario máximo ofrecido'),
            'vacantes' => array('type' => 'number', 'description' => 'Número de vacantes disponibles'),
            'fecha_inicio' => array('type' => 'string', 'description' => 'Fecha de inicio del trabajo'),
            'fecha_fin' => array('type' => 'string', 'description' => 'Fecha de fin del trabajo'),
            'duracion_dias' => array('type' => 'number', 'description' => 'Duración de la campaña en días'),
            'requisitos' => array('type' => 'string', 'description' => 'Requisitos del trabajo'),
            'beneficios' => array('type' => 'string', 'description' => 'Beneficios ofrecidos'),
            'tipo_contrato' => array('type' => 'string', 'description' => 'Tipo de contrato'),
            'jornada' => array('type' => 'string', 'description' => 'Jornada laboral'),
            'contacto_whatsapp' => array('type' => 'string', 'description' => 'Número de WhatsApp para contacto'),
            'contacto_email' => array('type' => 'string', 'description' => 'Email de contacto'),
            'google_maps_url' => array('type' => 'string', 'description' => 'URL de Google Maps o dirección'),
            'alojamiento' => array('type' => 'boolean', 'description' => 'Si incluye alojamiento'),
            'transporte' => array('type' => 'boolean', 'description' => 'Si incluye transporte'),
            'alimentacion' => array('type' => 'boolean', 'description' => 'Si incluye alimentación'),
            'estado' => array('type' => 'string', 'description' => 'Estado de la oferta', 'default' => 'activa'),
            'experiencia' => array('type' => 'string', 'description' => 'Experiencia requerida'),
            'genero' => array('type' => 'string', 'description' => 'Género requerido', 'default' => 'indiferente'),
            'edad_minima' => array('type' => 'number', 'description' => 'Edad mínima requerida'),
            'edad_maxima' => array('type' => 'number', 'description' => 'Edad máxima requerida'),
            'gallery_ids' => array('type' => 'array', 'description' => 'IDs de imágenes de la galería'),
            'empresa_id' => array('type' => 'integer', 'description' => 'ID del CPT Empresa asociado'),
        );

        foreach ($meta_fields as $field_name => $field_config) {
            register_post_meta('trabajo', $field_name, array(
                'type'         => $field_config['type'],
                'description'  => $field_config['description'],
                'single'       => true,
                'show_in_rest' => true,
                'default'      => isset($field_config['default']) ? $field_config['default'] : null,
            ));
        }
    }
    add_action('init', 'agrochamba_registrar_meta_fields');
}

// ==========================================
// 7. ASEGURAR QUE FEATURED MEDIA SE INCLUYA EN _EMBEDDED
// ==========================================
if (!function_exists('agrochamba_ensure_featured_media_in_embedded')) {
    function agrochamba_ensure_featured_media_in_embedded($response, $post, $request) {
        // Solo para el tipo de post 'trabajo'
        if ($post->post_type !== 'trabajo') {
            return $response;
        }
        
        // Verificar si se está solicitando _embed
        $embed = $request->get_param('_embed');
        if ($embed) {
            $data = $response->get_data();
            
            // Obtener featured media ID
            $featured_media_id = get_post_thumbnail_id($post->ID);
            
            if ($featured_media_id) {
                // Obtener información del media
                $media_post = get_post($featured_media_id);
                if ($media_post) {
                    $media_data = array(
                        'id' => $featured_media_id,
                        'date' => $media_post->post_date,
                        'slug' => $media_post->post_name,
                        'type' => $media_post->post_mime_type,
                        'link' => get_permalink($featured_media_id),
                        'title' => array(
                            'rendered' => $media_post->post_title,
                            'raw' => $media_post->post_title
                        ),
                        'author' => $media_post->post_author,
                        'comment_status' => $media_post->comment_status,
                        'ping_status' => $media_post->ping_status,
                        'template' => '',
                        'meta' => array(),
                        'description' => array(
                            'rendered' => $media_post->post_content,
                            'raw' => $media_post->post_content
                        ),
                        'caption' => array(
                            'rendered' => $media_post->post_excerpt,
                            'raw' => $media_post->post_excerpt
                        ),
                        'alt_text' => get_post_meta($featured_media_id, '_wp_attachment_image_alt', true),
                        'media_type' => wp_attachment_is_image($featured_media_id) ? 'image' : 'file',
                        'mime_type' => $media_post->post_mime_type,
                        'media_details' => wp_get_attachment_metadata($featured_media_id),
                        'source_url' => wp_get_attachment_image_url($featured_media_id, 'full'),
                    );
                    
                    // Asegurar que _embedded existe
                    if (!isset($data['_embedded'])) {
                        $data['_embedded'] = array();
                    }
                    
                    // Asegurar que wp:featuredmedia existe
                    if (!isset($data['_embedded']['wp:featuredmedia'])) {
                        $data['_embedded']['wp:featuredmedia'] = array();
                    }
                    
                    // Agregar el media si no está ya presente
                    $existing_ids = array_map(function($item) {
                        return isset($item['id']) ? $item['id'] : 0;
                    }, $data['_embedded']['wp:featuredmedia']);
                    
                    if (!in_array($featured_media_id, $existing_ids)) {
                        $data['_embedded']['wp:featuredmedia'][] = $media_data;
                    }
                    
                    $response->set_data($data);
                }
            }
        }
        
        return $response;
    }
    add_filter('rest_prepare_trabajo', 'agrochamba_ensure_featured_media_in_embedded', 10, 3);
}

// ==========================================
// 8. SINCRONIZAR IMÁGENES DEL EDITOR CON GALLERY_IDS
// ==========================================
// Cuando se agregan imágenes desde el editor de WordPress, actualizar gallery_ids automáticamente
if (!function_exists('agrochamba_sync_editor_images_to_gallery')) {
    function agrochamba_sync_editor_images_to_gallery($post_id) {
        // Solo para posts de tipo 'trabajo'
        if (get_post_type($post_id) !== 'trabajo') {
            return;
        }
        
        // Evitar loops infinitos
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Obtener todas las imágenes asociadas al post
        $all_image_ids = array();
        
        // 1. Imagen destacada
        $featured_id = get_post_thumbnail_id($post_id);
        if ($featured_id) {
            $all_image_ids[] = $featured_id;
        }
        
        // 2. Obtener gallery_ids existente para mantener el orden
        $existing_gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
        if (!empty($existing_gallery_ids) && is_array($existing_gallery_ids)) {
            // Agregar IDs existentes primero para mantener el orden
            foreach ($existing_gallery_ids as $existing_id) {
                if (!in_array($existing_id, $all_image_ids)) {
                    $all_image_ids[] = $existing_id;
                }
            }
        }
        
        // 3. Imágenes adjuntas (post_parent = post_id) - ordenadas por menu_order
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_parent' => $post_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids'
        ));
        
        if (!empty($attachments)) {
            foreach ($attachments as $attachment_id) {
                if (!in_array($attachment_id, $all_image_ids)) {
                    $all_image_ids[] = $attachment_id;
                }
            }
        }
        
        // 4. Buscar imágenes en el contenido HTML (mantener orden de aparición)
        $post = get_post($post_id);
        if ($post && !empty($post->post_content)) {
            preg_match_all('/wp-image-(\d+)/', $post->post_content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $img_id) {
                    $img_id = intval($img_id);
                    if ($img_id > 0 && !in_array($img_id, $all_image_ids)) {
                        $all_image_ids[] = $img_id;
                    }
                }
            }
        }
        
        // 5. Asegurar que la imagen destacada esté al principio
        if ($featured_id && in_array($featured_id, $all_image_ids)) {
            // Remover de cualquier posición
            $all_image_ids = array_values(array_diff($all_image_ids, array($featured_id)));
            // Agregar al principio
            array_unshift($all_image_ids, $featured_id);
        }
        
        // Actualizar gallery_ids con todas las imágenes encontradas (manteniendo orden)
        if (!empty($all_image_ids)) {
            update_post_meta($post_id, 'gallery_ids', array_values($all_image_ids));
        } elseif (empty($all_image_ids) && empty($featured_id)) {
            // Si no hay imágenes, limpiar gallery_ids
            update_post_meta($post_id, 'gallery_ids', array());
        }
    }
    
    // Hook cuando se guarda/actualiza un post
    add_action('save_post_trabajo', 'agrochamba_sync_editor_images_to_gallery', 20);
    
    // Hook cuando se adjunta una imagen a un post
    add_action('add_attachment', function($attachment_id) {
        $post_id = wp_get_post_parent_id($attachment_id);
        if ($post_id && get_post_type($post_id) === 'trabajo') {
            agrochamba_sync_editor_images_to_gallery($post_id);
        }
    });
    
    // Hook cuando se actualiza un attachment
    add_action('edit_attachment', function($attachment_id) {
        $post_id = wp_get_post_parent_id($attachment_id);
        if ($post_id && get_post_type($post_id) === 'trabajo') {
            agrochamba_sync_editor_images_to_gallery($post_id);
        }
    });
}

// ==========================================
// 3.1. META BOXES PARA EL EDITOR DE WORDPRESS
// ==========================================
if (!function_exists('agrochamba_add_meta_boxes')) {
    function agrochamba_add_meta_boxes() {
        add_meta_box(
            'agrochamba_job_details',
            '<span class="dashicons dashicons-clipboard" style="vertical-align: middle;"></span> Detalles del Trabajo',
            'agrochamba_job_details_meta_box',
            'trabajo',
            'normal',
            'high'
        );
        
        add_meta_box(
            'agrochamba_job_benefits',
            '<span class="dashicons dashicons-star-filled" style="vertical-align: middle;"></span> Beneficios',
            'agrochamba_job_benefits_meta_box',
            'trabajo',
            'side',
            'default'
        );
        
        add_meta_box(
            'agrochamba_job_contact',
            '<span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span> Información de Contacto',
            'agrochamba_job_contact_meta_box',
            'trabajo',
            'side',
            'default'
        );
        
        add_meta_box(
            'agrochamba_job_empresa',
            '<span class="dashicons dashicons-building" style="vertical-align: middle;"></span> Empresa',
            'agrochamba_job_empresa_meta_box',
            'trabajo',
            'side',
            'high'
        );
    }
    add_action('add_meta_boxes', 'agrochamba_add_meta_boxes');
}

// Meta box: Empresa (CPT)
if (!function_exists('agrochamba_job_empresa_meta_box')) {
    function agrochamba_job_empresa_meta_box($post) {
        wp_nonce_field('agrochamba_save_meta_boxes', 'agrochamba_meta_box_nonce');
        
        $empresa_id = get_post_meta($post->ID, 'empresa_id', true);
        
        // Obtener todas las empresas publicadas
        $empresas = get_posts([
            'post_type' => 'empresa',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        
        ?>
        <p>
            <label for="empresa_id">
                <strong>Empresa:</strong>
            </label>
        </p>
        <select name="empresa_id" id="empresa_id" style="width: 100%;">
            <option value="">-- Seleccionar Empresa --</option>
            <?php foreach ($empresas as $empresa): ?>
                <option value="<?php echo esc_attr($empresa->ID); ?>" <?php selected($empresa_id, $empresa->ID); ?>>
                    <?php echo esc_html($empresa->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($empresa_id): ?>
            <?php $empresa = get_post($empresa_id); ?>
            <?php if ($empresa): ?>
                <p style="margin-top: 10px;">
                    <a href="<?php echo esc_url(get_edit_post_link($empresa_id)); ?>" target="_blank">
                        Editar empresa →
                    </a>
                </p>
                <?php
                $ofertas_count = agrochamba_get_empresa_ofertas_count($empresa_id);
                ?>
                <p style="margin-top: 5px; color: #646970;">
                    <small>Ofertas activas: <strong><?php echo esc_html($ofertas_count); ?></strong></small>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p style="margin-top: 10px; color: #d63638;">
                <small>⚠️ Esta oferta no está asociada a ninguna empresa.</small>
            </p>
        <?php endif; ?>
        <?php
    }
}

// Agregar estilos CSS personalizados para los meta boxes
if (!function_exists('agrochamba_admin_styles')) {
    function agrochamba_admin_styles($hook) {
        // Solo cargar en la página de edición de trabajos
        if ($hook != 'post.php' && $hook != 'post-new.php') {
            return;
        }
        
        global $post_type;
        if ($post_type != 'trabajo') {
            return;
        }
        
        ?>
        <style>
            /* Estilo tipo Computrabajo - Diseño limpio y moderno */
            #agrochamba_job_details .inside,
            #agrochamba_job_benefits .inside,
            #agrochamba_job_contact .inside {
                padding: 20px;
                background: #fff;
            }
            
            /* Contenedor principal tipo Computrabajo */
            .agrochamba-job-editor {
                max-width: 900px;
            }
            
            /* Tabla de detalles mejorada - Estilo Computrabajo */
            #agrochamba_job_details .form-table {
                margin: 0;
                border-collapse: separate;
                border-spacing: 0;
            }
            
            #agrochamba_job_details .form-table th {
                width: 220px;
                padding: 12px 15px;
                font-weight: 600;
                color: #2c3e50;
                vertical-align: middle;
                background: #f8f9fa;
                border-bottom: 2px solid #e9ecef;
            }
            
            #agrochamba_job_details .form-table td {
                padding: 12px 15px;
                background: #fff;
                border-bottom: 1px solid #e9ecef;
            }
            
            /* Campos de entrada estilo Computrabajo */
            #agrochamba_job_details .form-table input[type="text"],
            #agrochamba_job_details .form-table input[type="number"],
            #agrochamba_job_details .form-table input[type="date"],
            #agrochamba_job_details .form-table input[type="email"],
            #agrochamba_job_details .form-table select,
            #agrochamba_job_details .form-table textarea {
                width: 100%;
                max-width: 600px;
                padding: 10px 14px;
                border: 2px solid #dee2e6;
                border-radius: 6px;
                font-size: 14px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                transition: all 0.3s ease;
                background: #fff;
            }
            
            #agrochamba_job_details .form-table input:hover,
            #agrochamba_job_details .form-table select:hover,
            #agrochamba_job_details .form-table textarea:hover {
                border-color: #adb5bd;
            }
            
            #agrochamba_job_details .form-table input:focus,
            #agrochamba_job_details .form-table select:focus,
            #agrochamba_job_details .form-table textarea:focus {
                border-color: #007bff;
                outline: none;
                box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
                background: #fff;
            }
            
            #agrochamba_job_details .form-table textarea {
                min-height: 100px;
                resize: vertical;
                line-height: 1.6;
            }
            
            /* Secciones agrupadas estilo Computrabajo */
            #agrochamba_job_details .form-table tr {
                border-bottom: 1px solid #e9ecef;
            }
            
            #agrochamba_job_details .form-table tr:last-child td {
                border-bottom: none;
            }
            
            /* Descripciones de ayuda estilo Computrabajo */
            #agrochamba_job_details .description {
                display: block;
                margin-top: 6px;
                color: #6c757d;
                font-size: 12px;
                line-height: 1.5;
            }
            
            /* Meta box de beneficios estilo Computrabajo */
            #agrochamba_job_benefits .inside {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
            }
            
            #agrochamba_job_benefits .benefit-item {
                display: flex;
                align-items: center;
                padding: 14px 16px;
                margin: 10px 0;
                background: #fff;
                border: 2px solid #dee2e6;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            #agrochamba_job_benefits .benefit-item:hover {
                background: #f8f9fa;
                border-color: #007bff;
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            #agrochamba_job_benefits .benefit-item.active {
                background: #e7f3ff;
                border-color: #007bff;
                box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            }
            
            #agrochamba_job_benefits input[type="checkbox"] {
                width: 22px;
                height: 22px;
                margin-right: 12px;
                cursor: pointer;
                accent-color: #007bff;
            }
            
            #agrochamba_job_benefits .benefit-label {
                font-weight: 500;
                color: #2c3e50;
                display: flex;
                align-items: center;
                flex: 1;
            }
            
            /* Meta box de contacto estilo Computrabajo */
            #agrochamba_job_contact .inside {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
            }
            
            #agrochamba_job_contact .contact-field {
                margin-bottom: 20px;
            }
            
            #agrochamba_job_contact .contact-field:last-child {
                margin-bottom: 0;
            }
            
            #agrochamba_job_contact label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #2c3e50;
                font-size: 14px;
            }
            
            #agrochamba_job_contact input[type="text"],
            #agrochamba_job_contact input[type="email"] {
                width: 100%;
                padding: 10px 14px;
                border: 2px solid #dee2e6;
                border-radius: 6px;
                font-size: 14px;
                transition: all 0.3s ease;
                background: #fff;
            }
            
            #agrochamba_job_contact input:hover {
                border-color: #adb5bd;
            }
            
            #agrochamba_job_contact input:focus {
                border-color: #007bff;
                outline: none;
                box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            }
            
            #agrochamba_job_contact .field-help {
                margin: 6px 0 0 0;
                font-size: 12px;
                color: #6c757d;
                line-height: 1.5;
            }
            
            /* Iconos en los títulos de meta boxes */
            .postbox-header h2 .dashicons {
                color: #007bff;
                margin-right: 8px;
                font-size: 20px;
            }
            
            /* Agrupar campos relacionados estilo Computrabajo */
            .agrochamba-field-group {
                background: #fff;
                padding: 0;
                margin: 0 0 30px 0;
                border-radius: 8px;
                border: 1px solid #e9ecef;
                overflow: hidden;
            }
            
            .agrochamba-field-group:last-child {
                margin-bottom: 0;
            }
            
            .agrochamba-field-group h4 {
                margin: 0;
                padding: 16px 20px;
                color: #fff;
                background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                font-size: 15px;
                font-weight: 600;
                text-transform: none;
                letter-spacing: 0;
                display: flex;
                align-items: center;
            }
            
            .agrochamba-field-group h4 .dashicons {
                margin-right: 10px;
                font-size: 18px;
            }
            
            .agrochamba-field-group .form-table {
                margin: 0;
            }
            
            .agrochamba-field-group .form-table th {
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
            }
            
            .agrochamba-field-group .form-table td {
                background: #fff;
            }
            
            /* Tip box estilo Computrabajo */
            .agrochamba-tip-box {
                margin-top: 20px;
                padding: 14px 16px;
                background: #e7f3ff;
                border-left: 4px solid #007bff;
                border-radius: 6px;
                font-size: 13px;
                color: #004085;
                line-height: 1.6;
            }
            
            .agrochamba-tip-box strong {
                color: #002752;
            }
            
            /* Responsive */
            @media (max-width: 782px) {
                #agrochamba_job_details .form-table th,
                #agrochamba_job_details .form-table td {
                    display: block;
                    width: 100%;
                    padding: 10px 0;
                }
                
                #agrochamba_job_details .form-table th {
                    background: transparent;
                    border-bottom: none;
                    padding-bottom: 5px;
                }
            }
        </style>
        <?php
    }
    add_action('admin_enqueue_scripts', 'agrochamba_admin_styles');
}

// Meta box: Detalles del Trabajo
if (!function_exists('agrochamba_job_details_meta_box')) {
    function agrochamba_job_details_meta_box($post) {
        wp_nonce_field('agrochamba_save_meta_boxes', 'agrochamba_meta_box_nonce');
        
        $salario_min = get_post_meta($post->ID, 'salario_min', true);
        $salario_max = get_post_meta($post->ID, 'salario_max', true);
        $vacantes = get_post_meta($post->ID, 'vacantes', true);
        $fecha_inicio = get_post_meta($post->ID, 'fecha_inicio', true);
        $fecha_fin = get_post_meta($post->ID, 'fecha_fin', true);
        $duracion_dias = get_post_meta($post->ID, 'duracion_dias', true);
        $tipo_contrato = get_post_meta($post->ID, 'tipo_contrato', true);
        $jornada = get_post_meta($post->ID, 'jornada', true);
        $requisitos = get_post_meta($post->ID, 'requisitos', true);
        $beneficios = get_post_meta($post->ID, 'beneficios', true);
        $experiencia = get_post_meta($post->ID, 'experiencia', true);
        $genero = get_post_meta($post->ID, 'genero', true);
        $edad_minima = get_post_meta($post->ID, 'edad_minima', true);
        $edad_maxima = get_post_meta($post->ID, 'edad_maxima', true);
        $estado = get_post_meta($post->ID, 'estado', true);
        if (empty($estado)) $estado = 'activa';
        
        ?>
        <div class="agrochamba-field-group">
            <h4><span class="dashicons dashicons-money-alt"></span> Información Salarial</h4>
            <table class="form-table" style="width: 100%;">
                <tr>
                    <th><label for="salario_min">Salario Mínimo (S/)</label></th>
                    <td>
                        <input type="number" id="salario_min" name="salario_min" value="<?php echo esc_attr($salario_min); ?>" class="regular-text" placeholder="Ej: 1500" />
                        <p class="description">Salario mínimo ofrecido en soles peruanos</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="salario_max">Salario Máximo (S/)</label></th>
                    <td>
                        <input type="number" id="salario_max" name="salario_max" value="<?php echo esc_attr($salario_max); ?>" class="regular-text" placeholder="Ej: 2500" />
                        <p class="description">Salario máximo ofrecido en soles peruanos</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="vacantes">Vacantes Disponibles</label></th>
                    <td>
                        <input type="number" id="vacantes" name="vacantes" value="<?php echo esc_attr($vacantes); ?>" class="regular-text" placeholder="Ej: 10" />
                        <p class="description">Número de puestos disponibles</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="agrochamba-field-group">
            <h4><span class="dashicons dashicons-calendar-alt"></span> Fechas y Duración</h4>
            <table class="form-table" style="width: 100%;">
                <tr>
                    <th><label for="fecha_inicio">Fecha de Inicio</label></th>
                    <td>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo esc_attr($fecha_inicio); ?>" class="regular-text" />
                        <p class="description">Fecha en que inicia el trabajo</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="fecha_fin">Fecha de Fin</label></th>
                    <td>
                        <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>" class="regular-text" />
                        <p class="description">Fecha en que finaliza el trabajo</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="duracion_dias">Duración (días)</label></th>
                    <td>
                        <input type="number" id="duracion_dias" name="duracion_dias" value="<?php echo esc_attr($duracion_dias); ?>" class="regular-text" placeholder="Ej: 30" />
                        <p class="description">Duración estimada en días</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="agrochamba-field-group">
            <h4><span class="dashicons dashicons-businessman"></span> Detalles del Puesto</h4>
            <table class="form-table" style="width: 100%;">
                <tr>
                    <th><label for="tipo_contrato">Tipo de Contrato</label></th>
                    <td>
                        <select id="tipo_contrato" name="tipo_contrato" class="regular-text">
                            <option value="">Seleccionar...</option>
                            <option value="temporal" <?php selected($tipo_contrato, 'temporal'); ?>>Temporal</option>
                            <option value="permanente" <?php selected($tipo_contrato, 'permanente'); ?>>Permanente</option>
                            <option value="por_obra" <?php selected($tipo_contrato, 'por_obra'); ?>>Por Obra</option>
                        </select>
                        <p class="description">Tipo de contrato laboral</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="jornada">Jornada Laboral</label></th>
                    <td>
                        <select id="jornada" name="jornada" class="regular-text">
                            <option value="">Seleccionar...</option>
                            <option value="tiempo_completo" <?php selected($jornada, 'tiempo_completo'); ?>>Tiempo Completo</option>
                            <option value="medio_tiempo" <?php selected($jornada, 'medio_tiempo'); ?>>Medio Tiempo</option>
                            <option value="por_horas" <?php selected($jornada, 'por_horas'); ?>>Por Horas</option>
                        </select>
                        <p class="description">Horario de trabajo</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="experiencia">Experiencia Requerida</label></th>
                    <td>
                        <select id="experiencia" name="experiencia" class="regular-text">
                            <option value="">Seleccionar...</option>
                            <option value="sin_experiencia" <?php selected($experiencia, 'sin_experiencia'); ?>>Sin Experiencia</option>
                            <option value="1-2_anos" <?php selected($experiencia, '1-2_anos'); ?>>1-2 Años</option>
                            <option value="3-5_anos" <?php selected($experiencia, '3-5_anos'); ?>>3-5 Años</option>
                            <option value="mas_5_anos" <?php selected($experiencia, 'mas_5_anos'); ?>>Más de 5 Años</option>
                        </select>
                        <p class="description">Años de experiencia necesarios</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="genero">Género</label></th>
                    <td>
                        <select id="genero" name="genero" class="regular-text">
                            <option value="indiferente" <?php selected($genero, 'indiferente'); ?>>Indiferente</option>
                            <option value="masculino" <?php selected($genero, 'masculino'); ?>>Masculino</option>
                            <option value="femenino" <?php selected($genero, 'femenino'); ?>>Femenino</option>
                        </select>
                        <p class="description">Preferencia de género (si aplica)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="edad_minima">Edad Mínima</label></th>
                    <td>
                        <input type="number" id="edad_minima" name="edad_minima" value="<?php echo esc_attr($edad_minima); ?>" class="regular-text" placeholder="Ej: 18" />
                        <p class="description">Edad mínima requerida</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="edad_maxima">Edad Máxima</label></th>
                    <td>
                        <input type="number" id="edad_maxima" name="edad_maxima" value="<?php echo esc_attr($edad_maxima); ?>" class="regular-text" placeholder="Ej: 65" />
                        <p class="description">Edad máxima requerida</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="estado">Estado de la Oferta</label></th>
                    <td>
                        <select id="estado" name="estado" class="regular-text">
                            <option value="activa" <?php selected($estado, 'activa'); ?>>✅ Activa</option>
                            <option value="pausada" <?php selected($estado, 'pausada'); ?>>⏸️ Pausada</option>
                            <option value="cerrada" <?php selected($estado, 'cerrada'); ?>>🔒 Cerrada</option>
                        </select>
                        <p class="description">Estado actual de la oferta de trabajo</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="agrochamba-field-group">
            <h4><span class="dashicons dashicons-edit"></span> Información Adicional</h4>
            <table class="form-table" style="width: 100%;">
                <tr>
                    <th><label for="requisitos">Requisitos</label></th>
                    <td>
                        <textarea id="requisitos" name="requisitos" rows="5" class="large-text" placeholder="Ej: Experiencia en cosecha, disponibilidad para viajar..."><?php echo esc_textarea($requisitos); ?></textarea>
                        <p class="description">Lista los requisitos necesarios para el puesto</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="beneficios">Beneficios Adicionales</label></th>
                    <td>
                        <textarea id="beneficios" name="beneficios" rows="5" class="large-text" placeholder="Ej: Bonos por producción, seguro de salud..."><?php echo esc_textarea($beneficios); ?></textarea>
                        <p class="description">Describe los beneficios adicionales ofrecidos</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="google_maps_url"><span class="dashicons dashicons-location-alt"></span> Google Maps</label></th>
                    <td>
                        <input type="text" id="google_maps_url" name="google_maps_url" value="<?php echo esc_attr(get_post_meta($post->ID, 'google_maps_url', true)); ?>" class="large-text" placeholder="https://maps.google.com/... o dirección completa" />
                        <p class="description">Pega la URL de Google Maps o escribe la dirección completa del lugar de trabajo</p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}

// Meta box: Beneficios (Alojamiento, Transporte, Alimentación)
if (!function_exists('agrochamba_job_benefits_meta_box')) {
    function agrochamba_job_benefits_meta_box($post) {
        $alojamiento = get_post_meta($post->ID, 'alojamiento', true);
        $transporte = get_post_meta($post->ID, 'transporte', true);
        $alimentacion = get_post_meta($post->ID, 'alimentacion', true);
        
        // Convertir a boolean si viene como string
        $alojamiento = filter_var($alojamiento, FILTER_VALIDATE_BOOLEAN);
        $transporte = filter_var($transporte, FILTER_VALIDATE_BOOLEAN);
        $alimentacion = filter_var($alimentacion, FILTER_VALIDATE_BOOLEAN);
        ?>
        <div>
            <div class="benefit-item <?php echo $alojamiento ? 'active' : ''; ?>">
                <input type="checkbox" name="alojamiento" value="1" id="benefit_alojamiento" <?php checked($alojamiento, true); ?> />
                <label for="benefit_alojamiento" class="benefit-label">
                    <span class="dashicons dashicons-admin-home" style="margin-right: 10px; color: #007bff; font-size: 20px;"></span>
                    <span>Incluye Alojamiento</span>
                </label>
            </div>
            
            <div class="benefit-item <?php echo $transporte ? 'active' : ''; ?>">
                <input type="checkbox" name="transporte" value="1" id="benefit_transporte" <?php checked($transporte, true); ?> />
                <label for="benefit_transporte" class="benefit-label">
                    <span class="dashicons dashicons-car" style="margin-right: 10px; color: #007bff; font-size: 20px;"></span>
                    <span>Incluye Transporte</span>
                </label>
            </div>
            
            <div class="benefit-item <?php echo $alimentacion ? 'active' : ''; ?>">
                <input type="checkbox" name="alimentacion" value="1" id="benefit_alimentacion" <?php checked($alimentacion, true); ?> />
                <label for="benefit_alimentacion" class="benefit-label">
                    <span class="dashicons dashicons-food" style="margin-right: 10px; color: #007bff; font-size: 20px;"></span>
                    <span>Incluye Alimentación</span>
                </label>
            </div>
        </div>
        
        <div class="agrochamba-tip-box">
            <strong>💡 Consejo:</strong> Marca todos los beneficios que incluye este trabajo. Esto ayudará a los trabajadores a encontrar ofertas que se ajusten mejor a sus necesidades y aumentará las postulaciones.
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Agregar clase 'active' cuando se marca un checkbox
            $('.benefit-item input[type="checkbox"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $(this).closest('.benefit-item').addClass('active');
                } else {
                    $(this).closest('.benefit-item').removeClass('active');
                }
            });
            
            // Permitir hacer clic en toda la caja para marcar/desmarcar
            $('.benefit-item').on('click', function(e) {
                if (e.target.type !== 'checkbox') {
                    $(this).find('input[type="checkbox"]').click();
                }
            });
        });
        </script>
        <?php
    }
}

// Meta box: Información de Contacto
if (!function_exists('agrochamba_job_contact_meta_box')) {
    function agrochamba_job_contact_meta_box($post) {
        $contacto_whatsapp = get_post_meta($post->ID, 'contacto_whatsapp', true);
        $contacto_email = get_post_meta($post->ID, 'contacto_email', true);
        ?>
        <div>
            <div class="contact-field">
                <label for="contacto_whatsapp">
                    <span class="dashicons dashicons-whatsapp" style="vertical-align: middle; color: #25D366; margin-right: 6px; font-size: 18px;"></span>
                    WhatsApp
                </label>
                <input type="text" id="contacto_whatsapp" name="contacto_whatsapp" value="<?php echo esc_attr($contacto_whatsapp); ?>" placeholder="Ej: +51 999 999 999" />
                <p class="field-help">Número de WhatsApp para contacto directo con los postulantes</p>
            </div>
            
            <div class="contact-field">
                <label for="contacto_email">
                    <span class="dashicons dashicons-email-alt" style="vertical-align: middle; color: #007bff; margin-right: 6px; font-size: 18px;"></span>
                    Email de Contacto
                </label>
                <input type="email" id="contacto_email" name="contacto_email" value="<?php echo esc_attr($contacto_email); ?>" placeholder="Ej: contacto@empresa.com" />
                <p class="field-help">Email donde los postulantes pueden enviar sus consultas</p>
            </div>
        </div>
        <?php
    }
}

// Guardar meta boxes
if (!function_exists('agrochamba_save_meta_boxes')) {
    function agrochamba_save_meta_boxes($post_id) {
        // Verificar nonce
        if (!isset($_POST['agrochamba_meta_box_nonce']) || !wp_verify_nonce($_POST['agrochamba_meta_box_nonce'], 'agrochamba_save_meta_boxes')) {
            return;
        }
        
        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Verificar que es el tipo de post correcto
        if (get_post_type($post_id) !== 'trabajo') {
            return;
        }
        
        // Lista de campos a guardar
        $fields = array(
            'salario_min', 'salario_max', 'vacantes', 'fecha_inicio', 'fecha_fin',
            'duracion_dias', 'tipo_contrato', 'jornada', 'requisitos', 'beneficios',
            'experiencia', 'genero', 'edad_minima', 'edad_maxima', 'estado',
            'contacto_whatsapp', 'contacto_email', 'google_maps_url', 'empresa_id'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                // Sanitizar según el tipo de campo
                if ($field === 'google_maps_url') {
                    update_post_meta($post_id, $field, esc_url_raw($_POST[$field]));
                } elseif ($field === 'empresa_id') {
                    $empresa_id = intval($_POST[$field]);
                    if ($empresa_id > 0) {
                        update_post_meta($post_id, $field, $empresa_id);
                    } else {
                        delete_post_meta($post_id, $field);
                    }
                } else {
                    update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
                }
            } else {
                // Solo eliminar si no es empresa_id (puede estar vacío intencionalmente)
                if ($field !== 'empresa_id') {
                    delete_post_meta($post_id, $field);
                }
            }
        }
        
        // Campos booleanos (checkboxes)
        $boolean_fields = array('alojamiento', 'transporte', 'alimentacion');
        foreach ($boolean_fields as $field) {
            if (isset($_POST[$field]) && $_POST[$field] == '1') {
                update_post_meta($post_id, $field, true);
            } else {
                update_post_meta($post_id, $field, false);
            }
        }
    }
    add_action('save_post', 'agrochamba_save_meta_boxes');
}

// ==========================================
// 4. URLS JERÁRQUICAS POR UBICACIÓN
// ==========================================
if (!function_exists('agrochamba_permalink_jerarquico')) {
    function agrochamba_permalink_jerarquico($post_link, $post) {
        if ($post->post_type !== 'trabajo') {
            return $post_link;
        }
        
        if (strpos($post_link, '%ubicacion%') === false) {
            return $post_link;
        }
        
        $ubicaciones = wp_get_post_terms($post->ID, 'ubicacion');
        
        if (!empty($ubicaciones) && !is_wp_error($ubicaciones)) {
            $ubicacion_slug = $ubicaciones[0]->slug;
        } else {
            $ubicacion_slug = 'sin-ubicacion';
        }
        
        $post_link = str_replace('%ubicacion%', $ubicacion_slug, $post_link);
        
        return $post_link;
    }
    add_filter('post_type_link', 'agrochamba_permalink_jerarquico', 10, 2);
}

// ==========================================
// 5. REGLA DE REESCRITURA PARA URLS JERÁRQUICAS
// ==========================================
if (!function_exists('agrochamba_rewrite_rules')) {
    function agrochamba_rewrite_rules() {
        add_rewrite_rule(
            '^trabajos/([^/]+)/([^/]+)/?$',
            'index.php?trabajo=$matches[2]',
            'top'
        );
    }
    add_action('init', 'agrochamba_rewrite_rules');
}

// ==========================================
// 6. MENSAJES PERSONALIZADOS
// ==========================================
if (!function_exists('agrochamba_mensajes_personalizados')) {
    function agrochamba_mensajes_personalizados($messages) {
        $messages['trabajo'] = array(
            0  => '',
            1  => 'Trabajo actualizado.',
            2  => 'Campo personalizado actualizado.',
            3  => 'Campo personalizado eliminado.',
            4  => 'Trabajo actualizado.',
            5  => isset($_GET['revision']) ? 'Trabajo restaurado a revisión' : false,
            6  => 'Trabajo publicado.',
            7  => 'Trabajo guardado.',
            8  => 'Trabajo enviado.',
            9  => 'Trabajo programado.',
            10 => 'Borrador de trabajo actualizado.',
        );
        
        return $messages;
    }
    add_filter('post_updated_messages', 'agrochamba_mensajes_personalizados');
}

