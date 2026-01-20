<?php
/**
 * =============================================================
 * MÓDULO 27: SISTEMA DE UBICACIONES HÍBRIDO
 * =============================================================
 * 
 * Arquitectura óptima para WordPress:
 * - Taxonomía 'ubicacion' para departamentos (relaciones + SEO + filtrado rápido)
 * - Meta fields para datos detallados (provincia, distrito, dirección)
 * - UX simplificada: el usuario solo ve selectores, no taxonomías
 * - Fuente única: peru-locations.php
 * 
 * @package Agrochamba
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 0. FUNCIÓN AUXILIAR - TRANSFORMAR FORMATO
// ==========================================

if (!function_exists('agrochamba_get_locations_for_js')) {
    /**
     * Transforma el array de ubicaciones al formato que necesita JavaScript
     * 
     * De:  [['departamento' => 'Ica', 'provincias' => [['provincia' => 'Ica', 'distritos' => [...]]]]
     * A:   ['Ica' => ['Ica' => ['Subtanjalla', 'La Tinguiña', ...]]]
     */
    function agrochamba_get_locations_for_js() {
        static $formatted = null;
        
        if ($formatted !== null) {
            return $formatted;
        }
        
        $raw_locations = agrochamba_get_peru_locations();
        $formatted = array();
        
        foreach ($raw_locations as $dep) {
            $dep_name = $dep['departamento'];
            $formatted[$dep_name] = array();
            
            foreach ($dep['provincias'] as $prov) {
                $prov_name = $prov['provincia'];
                $formatted[$dep_name][$prov_name] = $prov['distritos'];
            }
        }
        
        return $formatted;
    }
}

// ==========================================
// 1. REGISTRAR META FIELDS PARA DETALLES
// ==========================================

if (!function_exists('agrochamba_register_location_meta_fields')) {
    function agrochamba_register_location_meta_fields() {
        // Meta fields para provincia y distrito (indexados para búsqueda)
        register_post_meta('trabajo', '_ubicacion_provincia', array(
            'type' => 'string',
            'description' => 'Provincia de la ubicación',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        register_post_meta('trabajo', '_ubicacion_distrito', array(
            'type' => 'string',
            'description' => 'Distrito de la ubicación',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        register_post_meta('trabajo', '_ubicacion_direccion', array(
            'type' => 'string',
            'description' => 'Dirección exacta',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        // Meta field objeto completo (para la app y UI)
        if (!registered_meta_key_exists('post', '_ubicacion_completa', 'trabajo')) {
            register_post_meta('trabajo', '_ubicacion_completa', array(
                'type' => 'object',
                'description' => 'Ubicación completa del trabajo con nivel de especificidad',
                'single' => true,
                'auth_callback' => '__return_true', // Permitir lectura sin autenticación
                'show_in_rest' => array(
                    'schema' => array(
                        'type' => 'object',
                        'properties' => array(
                            'departamento' => array('type' => 'string'),
                            'provincia' => array('type' => 'string'),
                            'distrito' => array('type' => 'string'),
                            'direccion' => array('type' => 'string'),
                            'lat' => array('type' => 'number'),
                            'lng' => array('type' => 'number'),
                            'nivel' => array(
                                'type' => 'string',
                                'enum' => array('DEPARTAMENTO', 'PROVINCIA', 'DISTRITO'),
                                'description' => 'Nivel de especificidad de la ubicación',
                            ),
                        ),
                    ),
                ),
            ));
        }
    }
    add_action('init', 'agrochamba_register_location_meta_fields');
}

// ==========================================
// 2. METABOX EN EL EDITOR DE TRABAJOS
// ==========================================

if (!function_exists('agrochamba_add_location_metabox')) {
    function agrochamba_add_location_metabox() {
        add_meta_box(
            'agrochamba_location_metabox',
            '📍 Ubicación del Trabajo',
            'agrochamba_render_location_metabox',
            'trabajo',
            'normal',
            'high'
        );
    }
    add_action('add_meta_boxes', 'agrochamba_add_location_metabox');
}

if (!function_exists('agrochamba_render_location_metabox')) {
    function agrochamba_render_location_metabox($post) {
        // Nonce para seguridad
        wp_nonce_field('agrochamba_location_nonce', 'agrochamba_location_nonce_field');
        
        // Obtener departamento desde taxonomía
        $ubicacion_terms = wp_get_post_terms($post->ID, 'ubicacion', array('fields' => 'names'));
        $departamento = !empty($ubicacion_terms) ? $ubicacion_terms[0] : '';
        
        // Obtener provincia y distrito desde meta fields
        $provincia = get_post_meta($post->ID, '_ubicacion_provincia', true);
        $distrito = get_post_meta($post->ID, '_ubicacion_distrito', true);
        $direccion = get_post_meta($post->ID, '_ubicacion_direccion', true);
        
        // Obtener datos de ubicaciones y transformar al formato para JS
        $peru_locations = agrochamba_get_locations_for_js();
        $departamentos = array_keys($peru_locations);
        
        ?>
        <style>
            .agrochamba-location-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin-bottom: 15px;
            }
            .agrochamba-location-field {
                display: flex;
                flex-direction: column;
            }
            .agrochamba-location-field label {
                font-weight: 600;
                margin-bottom: 5px;
                color: #1d2327;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .agrochamba-location-field label .icon {
                font-size: 16px;
            }
            .agrochamba-location-field select,
            .agrochamba-location-field input {
                padding: 10px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                font-size: 14px;
                background: #fff;
            }
            .agrochamba-location-field select:focus,
            .agrochamba-location-field input:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
            }
            .agrochamba-location-field select:disabled {
                background: #f0f0f1;
                color: #a7aaad;
            }
            .agrochamba-location-address {
                margin-top: 15px;
            }
            .agrochamba-location-address input {
                width: 100%;
            }
            .agrochamba-location-preview {
                background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
                border: 1px solid #81c784;
                border-radius: 8px;
                padding: 15px;
                margin-top: 15px;
                display: none;
            }
            .agrochamba-location-preview.active {
                display: block;
            }
            .agrochamba-location-preview-title {
                font-weight: 600;
                color: #2e7d32;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .agrochamba-location-preview-text {
                color: #1b5e20;
                font-size: 15px;
            }
            .agrochamba-location-note {
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 4px;
                padding: 10px 12px;
                margin-top: 15px;
                font-size: 13px;
                color: #856404;
            }
            @media (max-width: 782px) {
                .agrochamba-location-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        
        <div class="agrochamba-location-wrapper">
            <div class="agrochamba-location-grid">
                <div class="agrochamba-location-field">
                    <label for="ubicacion_departamento">
                        <span class="icon">🏛️</span> Departamento <span style="color: #d63638;">*</span>
                    </label>
                    <select id="ubicacion_departamento" name="ubicacion_departamento" required>
                        <option value="">Seleccionar departamento...</option>
                        <?php foreach ($departamentos as $dep) : ?>
                            <option value="<?php echo esc_attr($dep); ?>" <?php selected($departamento, $dep); ?>>
                                <?php echo esc_html($dep); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="agrochamba-location-field">
                    <label for="ubicacion_provincia">
                        <span class="icon">🏘️</span> Provincia <span style="color: #d63638;">*</span>
                    </label>
                    <select id="ubicacion_provincia" name="ubicacion_provincia" required>
                        <option value="">Primero seleccione departamento...</option>
                    </select>
                </div>
                
                <div class="agrochamba-location-field">
                    <label for="ubicacion_distrito">
                        <span class="icon">📌</span> Distrito <span style="color: #d63638;">*</span>
                    </label>
                    <select id="ubicacion_distrito" name="ubicacion_distrito" required>
                        <option value="">Primero seleccione provincia...</option>
                    </select>
                </div>
            </div>
            
            <div class="agrochamba-location-address">
                <div class="agrochamba-location-field">
                    <label for="ubicacion_direccion">
                        <span class="icon">📮</span> Dirección (opcional)
                    </label>
                    <input type="text" 
                           id="ubicacion_direccion" 
                           name="ubicacion_direccion" 
                           value="<?php echo esc_attr($direccion); ?>"
                           placeholder="Ej: Av. Principal 123, frente al parque, cerca al mercado...">
                </div>
            </div>
            
            <div id="ubicacion_preview" class="agrochamba-location-preview">
                <div class="agrochamba-location-preview-title">
                    <span>✅</span> Ubicación seleccionada
                </div>
                <div id="ubicacion_preview_text" class="agrochamba-location-preview-text"></div>
            </div>
            
            <div class="agrochamba-location-note">
                <strong>💡 Consejo:</strong> Una ubicación precisa ayuda a los trabajadores a encontrar ofertas cerca de ellos.
                El departamento se usará para filtrar en búsquedas, mientras que el distrito y dirección se mostrarán en los detalles del trabajo.
            </div>
        </div>
        
        <!-- Datos para JavaScript -->
        <script>
            var agrochamba_locations = <?php echo json_encode($peru_locations); ?>;
            var agrochamba_current = {
                departamento: <?php echo json_encode($departamento); ?>,
                provincia: <?php echo json_encode($provincia); ?>,
                distrito: <?php echo json_encode($distrito); ?>
            };
        </script>
        <?php
    }
}

// ==========================================
// 3. JAVASCRIPT PARA SELECTORES EN CASCADA
// ==========================================

if (!function_exists('agrochamba_location_admin_scripts')) {
    function agrochamba_location_admin_scripts($hook) {
        global $post_type;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'trabajo') {
            wp_add_inline_script('jquery', '
                jQuery(document).ready(function($) {
                    var locations = window.agrochamba_locations || {};
                    var current = window.agrochamba_current || {};
                    
                    var $departamento = $("#ubicacion_departamento");
                    var $provincia = $("#ubicacion_provincia");
                    var $distrito = $("#ubicacion_distrito");
                    var $direccion = $("#ubicacion_direccion");
                    var $preview = $("#ubicacion_preview");
                    var $previewText = $("#ubicacion_preview_text");
                    
                    function updatePreview() {
                        var dep = $departamento.val();
                        var prov = $provincia.val();
                        var dist = $distrito.val();
                        var dir = $direccion.val();
                        
                        if (dep && prov && dist) {
                            var text = dist + ", " + prov + ", " + dep;
                            if (dir) {
                                text += "\\n📮 " + dir;
                            }
                            $previewText.html(text.replace(/\\n/g, "<br>"));
                            $preview.addClass("active");
                        } else {
                            $preview.removeClass("active");
                        }
                    }
                    
                    function loadProvincias(departamento, selectedProvincia) {
                        $provincia.html("<option value=\"\">Cargando provincias...</option>").prop("disabled", true);
                        $distrito.html("<option value=\"\">Primero seleccione provincia...</option>").prop("disabled", true);
                        
                        if (!departamento || !locations[departamento]) {
                            $provincia.html("<option value=\"\">Primero seleccione departamento...</option>");
                            return;
                        }
                        
                        var provincias = Object.keys(locations[departamento]).sort();
                        var options = "<option value=\"\">Seleccionar provincia...</option>";
                        
                        provincias.forEach(function(prov) {
                            var selected = (prov === selectedProvincia) ? " selected" : "";
                            options += "<option value=\"" + prov + "\"" + selected + ">" + prov + "</option>";
                        });
                        
                        $provincia.html(options).prop("disabled", false);
                        
                        if (selectedProvincia && locations[departamento][selectedProvincia]) {
                            loadDistritos(departamento, selectedProvincia, current.distrito);
                        }
                    }
                    
                    function loadDistritos(departamento, provincia, selectedDistrito) {
                        $distrito.html("<option value=\"\">Cargando distritos...</option>").prop("disabled", true);
                        
                        if (!departamento || !provincia || !locations[departamento] || !locations[departamento][provincia]) {
                            $distrito.html("<option value=\"\">Primero seleccione provincia...</option>");
                            return;
                        }
                        
                        var distritos = locations[departamento][provincia].slice().sort();
                        var options = "<option value=\"\">Seleccionar distrito...</option>";
                        
                        distritos.forEach(function(dist) {
                            var selected = (dist === selectedDistrito) ? " selected" : "";
                            options += "<option value=\"" + dist + "\"" + selected + ">" + dist + "</option>";
                        });
                        
                        $distrito.html(options).prop("disabled", false);
                        updatePreview();
                    }
                    
                    // Event listeners
                    $departamento.on("change", function() {
                        loadProvincias($(this).val(), null);
                        updatePreview();
                    });
                    
                    $provincia.on("change", function() {
                        loadDistritos($departamento.val(), $(this).val(), null);
                        updatePreview();
                    });
                    
                    $distrito.on("change", function() {
                        updatePreview();
                    });
                    
                    $direccion.on("input", function() {
                        updatePreview();
                    });
                    
                    // Cargar valores iniciales
                    if (current.departamento) {
                        loadProvincias(current.departamento, current.provincia);
                    }
                    
                    // Actualizar preview inicial
                    setTimeout(updatePreview, 500);
                });
            ');
        }
    }
    add_action('admin_enqueue_scripts', 'agrochamba_location_admin_scripts');
}

// ==========================================
// 4. GUARDAR UBICACIÓN (TAXONOMÍA + META)
// ==========================================

if (!function_exists('agrochamba_save_location_data')) {
    function agrochamba_save_location_data($post_id) {
        // Verificar nonce
        if (!isset($_POST['agrochamba_location_nonce_field']) || 
            !wp_verify_nonce($_POST['agrochamba_location_nonce_field'], 'agrochamba_location_nonce')) {
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
        
        // Obtener y sanitizar valores
        $departamento = isset($_POST['ubicacion_departamento']) ? sanitize_text_field($_POST['ubicacion_departamento']) : '';
        $provincia = isset($_POST['ubicacion_provincia']) ? sanitize_text_field($_POST['ubicacion_provincia']) : '';
        $distrito = isset($_POST['ubicacion_distrito']) ? sanitize_text_field($_POST['ubicacion_distrito']) : '';
        $direccion = isset($_POST['ubicacion_direccion']) ? sanitize_text_field($_POST['ubicacion_direccion']) : '';
        
        // Validar ubicación
        if ($departamento && $provincia && $distrito) {
            $ubicacion = array(
                'departamento' => $departamento,
                'provincia' => $provincia,
                'distrito' => $distrito,
            );
            
            if (!agrochamba_is_valid_location($ubicacion)) {
                return; // Ubicación inválida
            }
        }
        
        // =============================================
        // GUARDAR EN TAXONOMÍA (para filtrado rápido)
        // =============================================
        if ($departamento) {
            // Buscar o crear el término del departamento
            $term = get_term_by('name', $departamento, 'ubicacion');
            
            if (!$term) {
                // Crear el término si no existe
                $result = wp_insert_term($departamento, 'ubicacion', array(
                    'slug' => sanitize_title($departamento)
                ));
                
                if (!is_wp_error($result)) {
                    $term_id = $result['term_id'];
                } else {
                    $term_id = null;
                }
            } else {
                $term_id = $term->term_id;
            }
            
            // Asignar taxonomía al post
            if ($term_id) {
                wp_set_post_terms($post_id, array($term_id), 'ubicacion');
            }
        } else {
            // Si no hay departamento, remover la taxonomía
            wp_set_post_terms($post_id, array(), 'ubicacion');
        }
        
        // =============================================
        // GUARDAR EN META FIELDS (para detalles)
        // =============================================
        update_post_meta($post_id, '_ubicacion_provincia', $provincia);
        update_post_meta($post_id, '_ubicacion_distrito', $distrito);
        update_post_meta($post_id, '_ubicacion_direccion', $direccion);
        
        // Guardar objeto completo para la app
        // Determinar el nivel de especificidad basándose en los datos
        $nivel = 'DISTRITO'; // Por defecto
        if (empty($provincia) || $provincia === $departamento) {
            $nivel = 'DEPARTAMENTO';
        } elseif (empty($distrito) || $distrito === $provincia) {
            $nivel = 'PROVINCIA';
        }

        $ubicacion_completa = array(
            'departamento' => $departamento,
            'provincia' => $provincia,
            'distrito' => $distrito,
            'direccion' => $direccion,
            'lat' => 0,
            'lng' => 0,
            'nivel' => $nivel,
        );
        update_post_meta($post_id, '_ubicacion_completa', $ubicacion_completa);
    }
    add_action('save_post_trabajo', 'agrochamba_save_location_data');
}

// ==========================================
// 5. OCULTAR METABOX NATIVO DE TAXONOMÍA
// ==========================================

if (!function_exists('agrochamba_hide_ubicacion_taxonomy_metabox')) {
    function agrochamba_hide_ubicacion_taxonomy_metabox() {
        // Ocultar el metabox nativo de la taxonomía ubicacion
        // porque usamos nuestro selector personalizado
        remove_meta_box('ubicaciondiv', 'trabajo', 'side');
    }
    add_action('add_meta_boxes', 'agrochamba_hide_ubicacion_taxonomy_metabox', 99);
}

// ==========================================
// 6. COLUMNA DE UBICACIÓN EN ADMIN
// ==========================================

if (!function_exists('agrochamba_add_location_column')) {
    function agrochamba_add_location_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['ubicacion_completa'] = '📍 Ubicación';
            }
        }
        // Remover la columna de taxonomía ubicacion si existe
        unset($new_columns['taxonomy-ubicacion']);
        return $new_columns;
    }
    add_filter('manage_trabajo_posts_columns', 'agrochamba_add_location_column');
}

if (!function_exists('agrochamba_render_location_column')) {
    function agrochamba_render_location_column($column, $post_id) {
        if ($column === 'ubicacion_completa') {
            $ubicacion = get_post_meta($post_id, '_ubicacion_completa', true);
            
            if (!empty($ubicacion) && !empty($ubicacion['departamento'])) {
                $distrito = $ubicacion['distrito'] ?? '';
                $provincia = $ubicacion['provincia'] ?? '';
                $departamento = $ubicacion['departamento'] ?? '';
                
                if ($distrito) {
                    echo '<strong>' . esc_html($distrito) . '</strong><br>';
                    echo '<small style="color: #666;">' . esc_html($provincia) . ', ' . esc_html($departamento) . '</small>';
                } else {
                    echo '<strong>' . esc_html($departamento) . '</strong>';
                }
            } else {
                // Fallback a taxonomía
                $terms = wp_get_post_terms($post_id, 'ubicacion', array('fields' => 'names'));
                if (!empty($terms)) {
                    echo '<strong>' . esc_html($terms[0]) . '</strong>';
                    echo '<br><small style="color: #999;">Solo departamento</small>';
                } else {
                    echo '<span style="color: #d63638;">⚠️ Sin ubicación</span>';
                }
            }
        }
    }
    add_action('manage_trabajo_posts_custom_column', 'agrochamba_render_location_column', 10, 2);
}

// ==========================================
// 7. FILTRO RÁPIDO EN ADMIN (USA TAXONOMÍA)
// ==========================================

// El filtro por departamento ya está disponible nativamente
// gracias a la taxonomía 'ubicacion'

// ==========================================
// 7.5. ASEGURAR _ubicacion_completa EN REST API
// ==========================================
// Filtro para asegurar que _ubicacion_completa esté incluido en la respuesta
// del endpoint estándar wp/v2/trabajos, ya que el meta field registrado
// debería aparecer en 'meta', pero a veces no se incluye correctamente

if (!function_exists('agrochamba_ensure_ubicacion_in_rest_response')) {
    function agrochamba_ensure_ubicacion_in_rest_response($response, $post, $request) {
        $data = $response->get_data();

        // Obtener _ubicacion_completa desde post meta
        $ubicacion_completa = get_post_meta($post->ID, '_ubicacion_completa', true);

        // Si no existe en meta, inicializar desde taxonomía (migración/fallback)
        if (empty($ubicacion_completa) || !is_array($ubicacion_completa)) {
            $ubicacion_terms = wp_get_post_terms($post->ID, 'ubicacion', array('fields' => 'names'));
            $provincia = get_post_meta($post->ID, '_ubicacion_provincia', true);
            $distrito = get_post_meta($post->ID, '_ubicacion_distrito', true);
            $direccion = get_post_meta($post->ID, '_ubicacion_direccion', true);

            if (!empty($ubicacion_terms)) {
                $departamento = $ubicacion_terms[0];

                // Determinar nivel
                $nivel = 'DISTRITO';
                if (empty($provincia) || $provincia === $departamento) {
                    $nivel = 'DEPARTAMENTO';
                } elseif (empty($distrito) || $distrito === $provincia) {
                    $nivel = 'PROVINCIA';
                }

                $ubicacion_completa = array(
                    'departamento' => $departamento,
                    'provincia' => $provincia ?: '',
                    'distrito' => $distrito ?: '',
                    'direccion' => $direccion ?: '',
                    'lat' => 0,
                    'lng' => 0,
                    'nivel' => $nivel,
                );

                // Guardar para futuras consultas
                update_post_meta($post->ID, '_ubicacion_completa', $ubicacion_completa);
            }
        }

        // Asegurar que el array meta exista
        if (!isset($data['meta'])) {
            $data['meta'] = array();
        }

        // Agregar _ubicacion_completa al meta si existe
        if (!empty($ubicacion_completa) && is_array($ubicacion_completa)) {
            // Asegurar que tenga el campo nivel
            if (!isset($ubicacion_completa['nivel'])) {
                $ubicacion_completa['nivel'] = 'DISTRITO';
                $dep = $ubicacion_completa['departamento'] ?? '';
                $prov = $ubicacion_completa['provincia'] ?? '';
                $dist = $ubicacion_completa['distrito'] ?? '';

                if (empty($prov) || $prov === $dep) {
                    $ubicacion_completa['nivel'] = 'DEPARTAMENTO';
                } elseif (empty($dist) || $dist === $prov) {
                    $ubicacion_completa['nivel'] = 'PROVINCIA';
                }
            }
            $data['meta']['_ubicacion_completa'] = $ubicacion_completa;
        }

        $response->set_data($data);
        return $response;
    }
    add_filter('rest_prepare_trabajo', 'agrochamba_ensure_ubicacion_in_rest_response', 20, 3);
}

// ==========================================
// 8. REST API - ENDPOINTS DE UBICACIONES
// ==========================================

if (!function_exists('agrochamba_register_location_endpoints')) {
    function agrochamba_register_location_endpoints() {
        // Endpoint para obtener todos los departamentos
        register_rest_route('agrochamba/v1', '/locations/departamentos', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_api_get_departamentos',
            'permission_callback' => '__return_true',
        ));
        
        // Endpoint para obtener provincias de un departamento
        register_rest_route('agrochamba/v1', '/locations/provincias/(?P<departamento>[^/]+)', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_api_get_provincias',
            'permission_callback' => '__return_true',
        ));
        
        // Endpoint para obtener distritos
        register_rest_route('agrochamba/v1', '/locations/distritos/(?P<departamento>[^/]+)/(?P<provincia>[^/]+)', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_api_get_distritos',
            'permission_callback' => '__return_true',
        ));
        
        // Endpoint para búsqueda inteligente
        register_rest_route('agrochamba/v1', '/locations/search', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_api_search_locations',
            'permission_callback' => '__return_true',
            'args' => array(
                'q' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }
    add_action('rest_api_init', 'agrochamba_register_location_endpoints');
}

if (!function_exists('agrochamba_api_get_departamentos')) {
    function agrochamba_api_get_departamentos() {
        $peru_locations = agrochamba_get_peru_locations();
        $departamentos = array_keys($peru_locations);
        sort($departamentos);
        return new WP_REST_Response($departamentos, 200);
    }
}

if (!function_exists('agrochamba_api_get_provincias')) {
    function agrochamba_api_get_provincias($request) {
        $departamento = urldecode($request->get_param('departamento'));
        $peru_locations = agrochamba_get_peru_locations();
        
        if (!isset($peru_locations[$departamento])) {
            return new WP_Error('not_found', 'Departamento no encontrado', array('status' => 404));
        }
        
        $provincias = array_keys($peru_locations[$departamento]);
        sort($provincias);
        return new WP_REST_Response($provincias, 200);
    }
}

if (!function_exists('agrochamba_api_get_distritos')) {
    function agrochamba_api_get_distritos($request) {
        $departamento = urldecode($request->get_param('departamento'));
        $provincia = urldecode($request->get_param('provincia'));
        $peru_locations = agrochamba_get_peru_locations();
        
        if (!isset($peru_locations[$departamento][$provincia])) {
            return new WP_Error('not_found', 'Provincia no encontrada', array('status' => 404));
        }
        
        $distritos = $peru_locations[$departamento][$provincia];
        sort($distritos);
        return new WP_REST_Response($distritos, 200);
    }
}

if (!function_exists('agrochamba_api_search_locations')) {
    function agrochamba_api_search_locations($request) {
        $query = $request->get_param('q');
        $results = agrochamba_search_location($query);
        return new WP_REST_Response($results, 200);
    }
}

// ==========================================
// 9. FILTRAR TRABAJOS POR UBICACIÓN EN API
// ==========================================

if (!function_exists('agrochamba_filter_jobs_by_location')) {
    /**
     * Permite filtrar trabajos por departamento, provincia o distrito
     * 
     * Ejemplos de uso:
     * - /wp/v2/trabajos?departamento=Ica          → Todos los de Ica
     * - /wp/v2/trabajos?provincia=Ica             → Solo provincia Ica
     * - /wp/v2/trabajos?distrito=Subtanjalla      → SOLO Subtanjalla
     * - /wp/v2/trabajos?distrito=Subtanjalla&departamento=Ica → Más específico
     */
    function agrochamba_filter_jobs_by_location($args, $request) {
        // Inicializar meta_query si no existe
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = array();
        }
        
        // Filtrar por DEPARTAMENTO (usa taxonomía para velocidad)
        $departamento = $request->get_param('departamento');
        if (!empty($departamento)) {
            // Usar tax_query para departamento (más rápido)
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = array();
            }
            $args['tax_query'][] = array(
                'taxonomy' => 'ubicacion',
                'field' => 'name',
                'terms' => sanitize_text_field($departamento),
            );
        }
        
        // Filtrar por PROVINCIA (meta field)
        $provincia = $request->get_param('provincia');
        if (!empty($provincia)) {
            $args['meta_query'][] = array(
                'key' => '_ubicacion_provincia',
                'value' => sanitize_text_field($provincia),
                'compare' => '=',
            );
        }
        
        // Filtrar por DISTRITO (meta field) - EL MÁS ESPECÍFICO
        $distrito = $request->get_param('distrito');
        if (!empty($distrito)) {
            $args['meta_query'][] = array(
                'key' => '_ubicacion_distrito',
                'value' => sanitize_text_field($distrito),
                'compare' => '=',
            );
        }
        
        // Búsqueda flexible: si el usuario busca "Subtanjalla", 
        // buscar en distrito, provincia o departamento
        $ubicacion_search = $request->get_param('ubicacion_search');
        if (!empty($ubicacion_search)) {
            $search_term = sanitize_text_field($ubicacion_search);
            
            // Buscar en cualquier nivel
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => '_ubicacion_distrito',
                    'value' => $search_term,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_ubicacion_provincia',
                    'value' => $search_term,
                    'compare' => 'LIKE',
                ),
            );
        }
        
        return $args;
    }
    add_filter('rest_trabajo_query', 'agrochamba_filter_jobs_by_location', 10, 2);
}

// Registrar los parámetros de query para la REST API
if (!function_exists('agrochamba_register_location_query_params')) {
    function agrochamba_register_location_query_params($params) {
        $params['departamento'] = array(
            'description' => 'Filtrar por departamento',
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        );
        $params['provincia'] = array(
            'description' => 'Filtrar por provincia',
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        );
        $params['distrito'] = array(
            'description' => 'Filtrar por distrito',
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        );
        $params['ubicacion_search'] = array(
            'description' => 'Buscar en cualquier nivel de ubicación',
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        );
        return $params;
    }
    add_filter('rest_trabajo_collection_params', 'agrochamba_register_location_query_params');
}

// ==========================================
// 10. INCLUIR UBICACIÓN EN RESPUESTA REST
// ==========================================

if (!function_exists('agrochamba_get_job_location_display_data')) {
    function agrochamba_get_job_location_display_data($post_id) {
        $ubicacion = get_post_meta($post_id, '_ubicacion_completa', true);
        
        if (!empty($ubicacion) && !empty($ubicacion['departamento'])) {
            $departamento = $ubicacion['departamento'];
            $provincia = $ubicacion['provincia'] ?? '';
            $distrito = $ubicacion['distrito'] ?? '';
            $direccion = $ubicacion['direccion'] ?? '';
            
            // Obtener el nivel de especificidad (DEPARTAMENTO, PROVINCIA, DISTRITO)
            $nivel = strtoupper($ubicacion['nivel'] ?? '');
            if (!in_array($nivel, array('DEPARTAMENTO', 'PROVINCIA', 'DISTRITO'))) {
                // Detectar nivel automáticamente si no está definido
                if (empty($provincia) || $provincia === $departamento) {
                    $nivel = 'DEPARTAMENTO';
                } elseif (empty($distrito) || $distrito === $provincia) {
                    $nivel = 'PROVINCIA';
                } else {
                    $nivel = 'DISTRITO';
                }
            }
            
            // Formatear según el nivel de especificidad
            switch ($nivel) {
                case 'DEPARTAMENTO':
                    $full = $departamento;
                    break;
                case 'PROVINCIA':
                    $full = $provincia . ', ' . $departamento;
                    break;
                case 'DISTRITO':
                default:
                    $full = implode(', ', array_filter(array($distrito, $provincia, $departamento)));
                    break;
            }
            
            return array(
                'departamento' => $departamento,
                'provincia' => $provincia,
                'distrito' => $distrito,
                'direccion' => $direccion,
                'nivel' => $nivel,
                'card' => $departamento, // Para cards: solo departamento
                'full' => $full, // Para detalles: ubicación formateada según nivel
            );
        }
        
        // Fallback a taxonomía (solo departamento)
        $terms = wp_get_post_terms($post_id, 'ubicacion', array('fields' => 'names'));
        if (!empty($terms)) {
            return array(
                'departamento' => $terms[0],
                'provincia' => '',
                'distrito' => '',
                'direccion' => '',
                'nivel' => 'DEPARTAMENTO',
                'card' => $terms[0],
                'full' => $terms[0],
            );
        }
        
        return null;
    }
}

if (!function_exists('agrochamba_add_location_to_rest_response')) {
    function agrochamba_add_location_to_rest_response() {
        // Campo para mostrar ubicación formateada
        register_rest_field('trabajo', 'ubicacion_display', array(
            'get_callback' => function($post) {
                return agrochamba_get_job_location_display_data($post['id']);
            },
            'schema' => array(
                'type' => 'object',
                'description' => 'Ubicación formateada para UI con nivel de especificidad',
            ),
        ));
    }
    add_action('rest_api_init', 'agrochamba_add_location_to_rest_response');
}

// ==========================================
// 10. SHORTCODE PARA TEMPLATES
// ==========================================

if (!function_exists('agrochamba_ubicacion_shortcode')) {
    function agrochamba_ubicacion_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'format' => 'full', // 'full', 'card', 'detailed'
        ), $atts);
        
        $post_id = intval($atts['id']);
        $ubicacion = get_post_meta($post_id, '_ubicacion_completa', true);
        
        if (empty($ubicacion) || empty($ubicacion['departamento'])) {
            // Fallback a taxonomía
            $terms = wp_get_post_terms($post_id, 'ubicacion', array('fields' => 'names'));
            if (!empty($terms)) {
                return '<span class="ubicacion-card">📍 ' . esc_html($terms[0]) . '</span>';
            }
            return '';
        }
        
        $departamento = $ubicacion['departamento'];
        $provincia = $ubicacion['provincia'] ?? '';
        $distrito = $ubicacion['distrito'] ?? '';
        $direccion = $ubicacion['direccion'] ?? '';
        
        switch ($atts['format']) {
            case 'card':
                return '<span class="ubicacion-card">📍 ' . esc_html($departamento) . '</span>';
                
            case 'detailed':
                $html = '<div class="ubicacion-detailed">';
                $full = implode(', ', array_filter(array($distrito, $provincia, $departamento)));
                $html .= '<div class="ubicacion-line">📍 ' . esc_html($full) . '</div>';
                if ($direccion) {
                    $html .= '<div class="ubicacion-direccion">📮 ' . esc_html($direccion) . '</div>';
                }
                $html .= '</div>';
                return $html;
                
            case 'full':
            default:
                $full = implode(', ', array_filter(array($distrito, $provincia, $departamento)));
                if ($direccion) {
                    $full .= ' - ' . $direccion;
                }
                return '<span class="ubicacion-full">📍 ' . esc_html($full) . '</span>';
        }
    }
    add_shortcode('ubicacion', 'agrochamba_ubicacion_shortcode');
}

// ==========================================
// 11. HERRAMIENTA DE MIGRACIÓN
// ==========================================

if (!function_exists('agrochamba_add_migration_tool')) {
    function agrochamba_add_migration_tool() {
        add_management_page(
            'Migrar Ubicaciones',
            'Migrar Ubicaciones',
            'manage_options',
            'agrochamba-migrate-locations',
            'agrochamba_render_migration_page'
        );
    }
    add_action('admin_menu', 'agrochamba_add_migration_tool');
}

if (!function_exists('agrochamba_render_migration_page')) {
    function agrochamba_render_migration_page() {
        $message = '';
        $migrated = 0;
        $skipped = 0;
        
        if (isset($_POST['migrate']) && wp_verify_nonce($_POST['_wpnonce'], 'agrochamba_migrate')) {
            $result = agrochamba_migrate_locations();
            $migrated = $result['migrated'];
            $skipped = $result['skipped'];
        }
        
        // Contar trabajos que necesitan migración
        $needs_migration = get_posts(array(
            'post_type' => 'trabajo',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ubicacion',
                    'operator' => 'EXISTS',
                ),
            ),
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_ubicacion_completa',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_ubicacion_completa',
                    'value' => '',
                    'compare' => '=',
                ),
            ),
            'fields' => 'ids',
        ));
        
        ?>
        <div class="wrap">
            <h1>📍 Migrar Ubicaciones</h1>
            
            <?php if ($migrated > 0 || $skipped > 0) : ?>
                <div class="notice notice-success">
                    <p>
                        ✅ <strong>Migración completada:</strong>
                        <?php echo $migrated; ?> trabajos actualizados,
                        <?php echo $skipped; ?> ya tenían datos.
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 600px; padding: 20px;">
                <h2>Acerca de esta herramienta</h2>
                <p>
                    Esta herramienta sincroniza trabajos que solo tienen la taxonomía <code>ubicacion</code> 
                    con el nuevo sistema de meta fields.
                </p>
                
                <p>
                    <strong>Estado actual:</strong>
                    <?php echo count($needs_migration); ?> trabajos necesitan migración.
                </p>
                
                <h3>¿Qué hace la migración?</h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>Copia el departamento de la taxonomía al campo <code>_ubicacion_completa</code></li>
                    <li>Provincia y distrito quedarán vacíos (para edición manual)</li>
                    <li>Mantiene la taxonomía intacta</li>
                </ul>
                
                <form method="post" style="margin-top: 20px;">
                    <?php wp_nonce_field('agrochamba_migrate'); ?>
                    <input type="submit" 
                           name="migrate" 
                           class="button button-primary" 
                           value="▶️ Ejecutar Migración"
                           <?php echo count($needs_migration) === 0 ? 'disabled' : ''; ?>>
                </form>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('agrochamba_migrate_locations')) {
    function agrochamba_migrate_locations() {
        $migrated = 0;
        $skipped = 0;
        
        $posts = get_posts(array(
            'post_type' => 'trabajo',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));
        
        foreach ($posts as $post) {
            $ubicacion_completa = get_post_meta($post->ID, '_ubicacion_completa', true);
            
            // Si ya tiene datos completos, saltar
            if (!empty($ubicacion_completa) && !empty($ubicacion_completa['departamento'])) {
                $skipped++;
                continue;
            }
            
            // Obtener de taxonomía
            $terms = wp_get_post_terms($post->ID, 'ubicacion', array('fields' => 'names'));
            
            if (!empty($terms)) {
                $departamento = $terms[0];
                
                // Crear estructura básica
                update_post_meta($post->ID, '_ubicacion_completa', array(
                    'departamento' => $departamento,
                    'provincia' => '',
                    'distrito' => '',
                    'direccion' => '',
                    'lat' => 0,
                    'lng' => 0,
                ));
                
                update_post_meta($post->ID, '_ubicacion_provincia', '');
                update_post_meta($post->ID, '_ubicacion_distrito', '');
                update_post_meta($post->ID, '_ubicacion_direccion', '');
                
                $migrated++;
            }
        }
        
        return array(
            'migrated' => $migrated,
            'skipped' => $skipped,
        );
    }
}

// ==========================================
// ENDPOINT PARA POBLAR TAXONOMÍA COMPLETA
// ==========================================

add_action('rest_api_init', function() {
    register_rest_route('agrochamba/v1', '/populate-locations', array(
        'methods' => 'POST',
        'callback' => 'agrochamba_populate_locations_endpoint',
        'permission_callback' => function() {
            // Solo administradores pueden ejecutar esto
            return current_user_can('manage_options');
        }
    ));
});

if (!function_exists('agrochamba_populate_locations_endpoint')) {
    function agrochamba_populate_locations_endpoint($request) {
        if (!function_exists('agrochamba_populate_all_location_terms')) {
            return new WP_Error(
                'function_not_found',
                'La función agrochamba_populate_all_location_terms no está disponible.',
                array('status' => 500)
            );
        }
        
        $stats = agrochamba_populate_all_location_terms();
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Taxonomía de ubicaciones poblada correctamente.',
            'stats' => $stats,
            'summary' => sprintf(
                'Creados: %d departamentos, %d provincias, %d distritos. Errores: %d',
                $stats['departamentos'],
                $stats['provincias'],
                $stats['distritos'],
                count($stats['errores'])
            )
        ), 200);
    }
}

// ==========================================
// PÁGINA DE ADMIN PARA POBLAR UBICACIONES
// ==========================================

add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=trabajo',
        'Poblar Ubicaciones del Peru',
        'Poblar Ubicaciones',
        'manage_options',
        'agrochamba-populate-locations',
        'agrochamba_populate_locations_page'
    );
});

function agrochamba_populate_locations_page() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para acceder a esta página.');
    }

    // Procesar acción si se envió el formulario
    $message = '';
    $message_type = '';
    
    if (isset($_POST['populate_locations']) && wp_verify_nonce($_POST['_wpnonce'], 'populate_locations_action')) {
        // Aumentar límites para operación larga
        set_time_limit(300); // 5 minutos
        wp_raise_memory_limit('admin');
        
        if (function_exists('agrochamba_populate_all_location_terms')) {
            $stats = agrochamba_populate_all_location_terms();
            $message = sprintf(
                'Taxonomia poblada correctamente:<br>
                - Departamentos: %d<br>
                - Provincias: %d<br>
                - Distritos: %d<br>
                - Errores: %d',
                $stats['departamentos'],
                $stats['provincias'],
                $stats['distritos'],
                count($stats['errores'])
            );
            $message_type = 'success';
            
            if (!empty($stats['errores'])) {
                $message .= '<br><br>Errores encontrados:<br>' . implode('<br>', array_slice($stats['errores'], 0, 10));
            }
        } else {
            $message = 'Error: La funcion agrochamba_populate_all_location_terms no esta disponible.';
            $message_type = 'error';
        }
    }

    // Contar términos existentes
    $term_count = wp_count_terms(array('taxonomy' => 'ubicacion', 'hide_empty' => false));
    if (is_wp_error($term_count)) {
        $term_count = 0;
    }
    
    ?>
    <div class="wrap">
        <h1>Poblar Ubicaciones del Peru</h1>
        
        <?php if ($message): ?>
            <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                <p><?php echo wp_kses_post($message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 600px; padding: 20px;">
            <h2>Estado Actual</h2>
            <p><strong>Terminos en taxonomia "ubicacion":</strong> <?php echo intval($term_count); ?></p>
            
            <hr>
            
            <h2>Que hace este script?</h2>
            <p>Crea la estructura jerarquica completa de ubicaciones del Peru:</p>
            <ul>
                <li><strong>25</strong> Departamentos</li>
                <li><strong>196</strong> Provincias</li>
                <li><strong>1,892</strong> Distritos</li>
            </ul>
            <p><em>Total: ~2,113 terminos jerarquicos</em></p>
            
            <hr>
            
            <h2>Importante</h2>
            <ul>
                <li>Este proceso puede tomar <strong>2-5 minutos</strong></li>
                <li>No cierres el navegador mientras se ejecuta</li>
                <li>Los terminos existentes NO se duplicaran</li>
                <li>Es seguro ejecutarlo multiples veces</li>
            </ul>
            
            <hr>
            
            <form method="post" onsubmit="return confirm('Estas seguro de que deseas poblar las ubicaciones? Este proceso tomara unos minutos.');">
                <?php wp_nonce_field('populate_locations_action'); ?>
                <p>
                    <button type="submit" name="populate_locations" class="button button-primary button-hero" style="width: 100%;">
                        Poblar Ubicaciones del Peru
                    </button>
                </p>
            </form>
        </div>
        
        <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
            <h2>Alternativas</h2>
            <p><strong>API REST:</strong></p>
            <code>POST <?php echo esc_url(rest_url('agrochamba/v1/populate-locations')); ?></code>
            <p style="margin-top: 15px;"><strong>WP-CLI:</strong></p>
            <code>wp eval "agrochamba_populate_all_location_terms();"</code>
        </div>
    </div>
    <?php
}
