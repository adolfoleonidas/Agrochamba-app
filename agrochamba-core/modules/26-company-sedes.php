<?php
/**
 * =============================================================
 * MÓDULO 26: SEDES DE EMPRESA
 * =============================================================
 * 
 * Gestión de sedes/ubicaciones de empresas:
 * - Meta fields para almacenar sedes
 * - Endpoints REST para CRUD de sedes
 * - Validación usando peru-locations.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. REGISTRAR META FIELDS PARA SEDES
// ==========================================

if (!function_exists('agrochamba_register_sedes_meta')) {
    function agrochamba_register_sedes_meta() {
        // Meta field para sedes de empresa
        register_post_meta('empresa', '_sedes', array(
            'type' => 'array',
            'description' => 'Sedes de la empresa',
            'single' => true,
            'show_in_rest' => array(
                'schema' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'id' => array('type' => 'string'),
                            'nombre' => array('type' => 'string'),
                            'departamento' => array('type' => 'string'),
                            'provincia' => array('type' => 'string'),
                            'distrito' => array('type' => 'string'),
                            'direccion' => array('type' => 'string'),
                            'es_principal' => array('type' => 'boolean'),
                            'activa' => array('type' => 'boolean'),
                            'lat' => array('type' => 'number'),
                            'lng' => array('type' => 'number'),
                        ),
                    ),
                ),
            ),
            'default' => array(),
            'sanitize_callback' => 'agrochamba_sanitize_sedes',
        ));
        
        // Meta field para ubicación estructurada del trabajo
        // NOTA: Este meta también se registra en 27-location-system.php con la misma estructura
        if (!registered_meta_key_exists('post', '_ubicacion_completa', 'trabajo')) {
            register_post_meta('trabajo', '_ubicacion_completa', array(
                'type' => 'object',
                'description' => 'Ubicación completa del trabajo con nivel de especificidad',
                'single' => true,
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
                'default' => array(),
                'sanitize_callback' => 'agrochamba_sanitize_ubicacion',
            ));
        }
    }
    add_action('init', 'agrochamba_register_sedes_meta');
}

/**
 * Sanitiza el array de sedes
 */
function agrochamba_sanitize_sedes($sedes) {
    if (!is_array($sedes)) {
        return array();
    }
    
    $sanitized = array();
    foreach ($sedes as $sede) {
        if (!is_array($sede)) continue;
        
        // Validar ubicación
        $ubicacion = array(
            'departamento' => isset($sede['departamento']) ? $sede['departamento'] : '',
            'provincia' => isset($sede['provincia']) ? $sede['provincia'] : '',
            'distrito' => isset($sede['distrito']) ? $sede['distrito'] : '',
        );
        
        if (!agrochamba_is_valid_location($ubicacion)) {
            continue; // Saltar sedes con ubicación inválida
        }
        
        // Normalizar ubicación
        $ubicacion_normalizada = agrochamba_normalize_location($ubicacion);
        if (!$ubicacion_normalizada) {
            continue;
        }
        
        $sanitized[] = array(
            'id' => isset($sede['id']) ? sanitize_text_field($sede['id']) : 'sede_' . time() . '_' . wp_rand(),
            'nombre' => isset($sede['nombre']) ? sanitize_text_field($sede['nombre']) : 'Sede Principal',
            'departamento' => $ubicacion_normalizada['departamento'],
            'provincia' => $ubicacion_normalizada['provincia'],
            'distrito' => $ubicacion_normalizada['distrito'],
            'direccion' => isset($sede['direccion']) ? sanitize_text_field($sede['direccion']) : '',
            'es_principal' => isset($sede['es_principal']) ? (bool) $sede['es_principal'] : false,
            'activa' => isset($sede['activa']) ? (bool) $sede['activa'] : true,
            'lat' => isset($sede['lat']) ? floatval($sede['lat']) : null,
            'lng' => isset($sede['lng']) ? floatval($sede['lng']) : null,
        );
    }
    
    // Asegurar que solo una sede sea principal
    $has_principal = false;
    foreach ($sanitized as &$sede) {
        if ($sede['es_principal']) {
            if ($has_principal) {
                $sede['es_principal'] = false;
            } else {
                $has_principal = true;
            }
        }
    }
    
    // Si no hay principal, marcar la primera
    if (!$has_principal && !empty($sanitized)) {
        $sanitized[0]['es_principal'] = true;
    }
    
    return $sanitized;
}

/**
 * Sanitiza ubicación completa
 */
function agrochamba_sanitize_ubicacion($ubicacion) {
    if (!is_array($ubicacion)) {
        return array();
    }
    
    $sanitized = array(
        'departamento' => isset($ubicacion['departamento']) ? sanitize_text_field($ubicacion['departamento']) : '',
        'provincia' => isset($ubicacion['provincia']) ? sanitize_text_field($ubicacion['provincia']) : '',
        'distrito' => isset($ubicacion['distrito']) ? sanitize_text_field($ubicacion['distrito']) : '',
        'direccion' => isset($ubicacion['direccion']) ? sanitize_text_field($ubicacion['direccion']) : '',
    );
    
    // Validar y normalizar
    if (!empty($sanitized['departamento']) && !empty($sanitized['provincia']) && !empty($sanitized['distrito'])) {
        $normalizada = agrochamba_normalize_location($sanitized);
        if ($normalizada) {
            $sanitized['departamento'] = $normalizada['departamento'];
            $sanitized['provincia'] = $normalizada['provincia'];
            $sanitized['distrito'] = $normalizada['distrito'];
        }
    }
    
    // Coordenadas opcionales
    if (isset($ubicacion['lat'])) {
        $sanitized['lat'] = floatval($ubicacion['lat']);
    }
    if (isset($ubicacion['lng'])) {
        $sanitized['lng'] = floatval($ubicacion['lng']);
    }
    
    return $sanitized;
}

// ==========================================
// 2. ENDPOINTS REST PARA SEDES
// ==========================================

if (!function_exists('agrochamba_register_sedes_endpoints')) {
    function agrochamba_register_sedes_endpoints() {
        
        // GET/POST sedes de una empresa
        register_rest_route('agrochamba/v1', '/companies/(?P<id>\d+)/sedes', array(
            array(
                'methods' => 'GET',
                'callback' => 'agrochamba_get_company_sedes',
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => 'agrochamba_create_company_sede',
                'permission_callback' => 'agrochamba_can_manage_company',
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ),
                ),
            ),
        ));
        
        // PUT/DELETE sede específica
        register_rest_route('agrochamba/v1', '/companies/(?P<company_id>\d+)/sedes/(?P<sede_id>[a-zA-Z0-9_-]+)', array(
            array(
                'methods' => 'PUT',
                'callback' => 'agrochamba_update_company_sede',
                'permission_callback' => 'agrochamba_can_manage_company',
            ),
            array(
                'methods' => 'DELETE',
                'callback' => 'agrochamba_delete_company_sede',
                'permission_callback' => 'agrochamba_can_manage_company',
            ),
        ));
        
        // Endpoint para búsqueda de ubicaciones
        register_rest_route('agrochamba/v1', '/locations/search', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_api_search_locations',
            'permission_callback' => '__return_true',
            'args' => array(
                'q' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'limit' => array(
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
        
        // Endpoint para resolver ubicación desde distrito
        register_rest_route('agrochamba/v1', '/locations/resolve', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_api_resolve_location',
            'permission_callback' => '__return_true',
            'args' => array(
                'distrito' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'provincia' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'departamento' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Endpoint para obtener lista de departamentos
        register_rest_route('agrochamba/v1', '/locations/departamentos', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_api_get_departamentos',
            'permission_callback' => '__return_true',
        ));
        
        // Endpoint para obtener provincias de un departamento
        register_rest_route('agrochamba/v1', '/locations/provincias', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_api_get_provincias',
            'permission_callback' => '__return_true',
            'args' => array(
                'departamento' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Endpoint para obtener distritos de una provincia
        register_rest_route('agrochamba/v1', '/locations/distritos', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_api_get_distritos',
            'permission_callback' => '__return_true',
            'args' => array(
                'departamento' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'provincia' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }
    add_action('rest_api_init', 'agrochamba_register_sedes_endpoints');
}

/**
 * Verifica si el usuario puede gestionar la empresa
 */
function agrochamba_can_manage_company($request) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }
    
    // Admins pueden gestionar cualquier empresa
    if (current_user_can('administrator')) {
        return true;
    }
    
    // Obtener ID de empresa (puede venir como 'id' o 'company_id')
    $company_id = $request->get_param('id') ?: $request->get_param('company_id');
    if (!$company_id) {
        return false;
    }
    
    // Verificar que el usuario es el autor de la empresa
    $company = get_post($company_id);
    if (!$company || $company->post_type !== 'empresa') {
        return false;
    }
    
    return (int) $company->post_author === $user_id;
}

/**
 * Obtiene las sedes de una empresa
 */
function agrochamba_get_company_sedes($request) {
    $company_id = $request->get_param('id');
    
    $company = get_post($company_id);
    if (!$company || $company->post_type !== 'empresa') {
        return new WP_Error('not_found', 'Empresa no encontrada', array('status' => 404));
    }
    
    $sedes = get_post_meta($company_id, '_sedes', true);
    if (!is_array($sedes)) {
        $sedes = array();
    }
    
    // Filtrar solo sedes activas para usuarios no-autores
    $user_id = get_current_user_id();
    $is_owner = (int) $company->post_author === $user_id || current_user_can('administrator');
    
    if (!$is_owner) {
        $sedes = array_filter($sedes, function($sede) {
            return isset($sede['activa']) && $sede['activa'];
        });
    }
    
    return new WP_REST_Response(array(
        'company_id' => $company_id,
        'company_name' => $company->post_title,
        'sedes' => array_values($sedes),
        'total' => count($sedes),
    ), 200);
}

/**
 * Crea una nueva sede
 */
function agrochamba_create_company_sede($request) {
    $company_id = $request->get_param('id');
    $body = $request->get_json_params();
    
    // Validar datos requeridos
    if (empty($body['nombre']) || empty($body['departamento']) || empty($body['provincia']) || empty($body['distrito'])) {
        return new WP_Error('missing_fields', 'Nombre y ubicación completa son requeridos', array('status' => 400));
    }
    
    // Validar ubicación
    $ubicacion = array(
        'departamento' => $body['departamento'],
        'provincia' => $body['provincia'],
        'distrito' => $body['distrito'],
    );
    
    if (!agrochamba_is_valid_location($ubicacion)) {
        return new WP_Error('invalid_location', 'Ubicación inválida', array('status' => 400));
    }
    
    // Normalizar ubicación
    $ubicacion_normalizada = agrochamba_normalize_location($ubicacion);
    
    // Crear nueva sede
    $new_sede = array(
        'id' => 'sede_' . time() . '_' . wp_rand(1000, 9999),
        'nombre' => sanitize_text_field($body['nombre']),
        'departamento' => $ubicacion_normalizada['departamento'],
        'provincia' => $ubicacion_normalizada['provincia'],
        'distrito' => $ubicacion_normalizada['distrito'],
        'direccion' => isset($body['direccion']) ? sanitize_text_field($body['direccion']) : '',
        'es_principal' => isset($body['es_principal']) ? (bool) $body['es_principal'] : false,
        'activa' => true,
        'lat' => isset($body['lat']) ? floatval($body['lat']) : null,
        'lng' => isset($body['lng']) ? floatval($body['lng']) : null,
    );
    
    // Obtener sedes actuales
    $sedes = get_post_meta($company_id, '_sedes', true);
    if (!is_array($sedes)) {
        $sedes = array();
    }
    
    // Si es principal, desmarcar las demás
    if ($new_sede['es_principal']) {
        foreach ($sedes as &$sede) {
            $sede['es_principal'] = false;
        }
    }
    
    // Si es la primera sede, marcarla como principal
    if (empty($sedes)) {
        $new_sede['es_principal'] = true;
    }
    
    $sedes[] = $new_sede;
    
    update_post_meta($company_id, '_sedes', $sedes);
    
    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Sede creada exitosamente',
        'sede' => $new_sede,
    ), 201);
}

/**
 * Actualiza una sede existente
 */
function agrochamba_update_company_sede($request) {
    $company_id = $request->get_param('company_id');
    $sede_id = $request->get_param('sede_id');
    $body = $request->get_json_params();
    
    $sedes = get_post_meta($company_id, '_sedes', true);
    if (!is_array($sedes)) {
        return new WP_Error('not_found', 'No hay sedes', array('status' => 404));
    }
    
    $index = -1;
    foreach ($sedes as $i => $sede) {
        if ($sede['id'] === $sede_id) {
            $index = $i;
            break;
        }
    }
    
    if ($index === -1) {
        return new WP_Error('not_found', 'Sede no encontrada', array('status' => 404));
    }
    
    // Actualizar campos
    if (isset($body['nombre'])) {
        $sedes[$index]['nombre'] = sanitize_text_field($body['nombre']);
    }
    
    if (isset($body['departamento']) && isset($body['provincia']) && isset($body['distrito'])) {
        $ubicacion = array(
            'departamento' => $body['departamento'],
            'provincia' => $body['provincia'],
            'distrito' => $body['distrito'],
        );
        
        if (agrochamba_is_valid_location($ubicacion)) {
            $ubicacion_normalizada = agrochamba_normalize_location($ubicacion);
            $sedes[$index]['departamento'] = $ubicacion_normalizada['departamento'];
            $sedes[$index]['provincia'] = $ubicacion_normalizada['provincia'];
            $sedes[$index]['distrito'] = $ubicacion_normalizada['distrito'];
        }
    }
    
    if (isset($body['direccion'])) {
        $sedes[$index]['direccion'] = sanitize_text_field($body['direccion']);
    }
    
    if (isset($body['es_principal'])) {
        $is_principal = (bool) $body['es_principal'];
        if ($is_principal) {
            // Desmarcar las demás
            foreach ($sedes as &$sede) {
                $sede['es_principal'] = false;
            }
        }
        $sedes[$index]['es_principal'] = $is_principal;
    }
    
    if (isset($body['activa'])) {
        $sedes[$index]['activa'] = (bool) $body['activa'];
    }
    
    if (isset($body['lat'])) {
        $sedes[$index]['lat'] = floatval($body['lat']);
    }
    
    if (isset($body['lng'])) {
        $sedes[$index]['lng'] = floatval($body['lng']);
    }
    
    update_post_meta($company_id, '_sedes', $sedes);
    
    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Sede actualizada',
        'sede' => $sedes[$index],
    ), 200);
}

/**
 * Elimina una sede
 */
function agrochamba_delete_company_sede($request) {
    $company_id = $request->get_param('company_id');
    $sede_id = $request->get_param('sede_id');
    
    $sedes = get_post_meta($company_id, '_sedes', true);
    if (!is_array($sedes)) {
        return new WP_Error('not_found', 'No hay sedes', array('status' => 404));
    }
    
    $new_sedes = array_filter($sedes, function($sede) use ($sede_id) {
        return $sede['id'] !== $sede_id;
    });
    
    if (count($new_sedes) === count($sedes)) {
        return new WP_Error('not_found', 'Sede no encontrada', array('status' => 404));
    }
    
    // Reindexar y asegurar que hay una principal
    $new_sedes = array_values($new_sedes);
    
    if (!empty($new_sedes)) {
        $has_principal = false;
        foreach ($new_sedes as $sede) {
            if ($sede['es_principal']) {
                $has_principal = true;
                break;
            }
        }
        if (!$has_principal) {
            $new_sedes[0]['es_principal'] = true;
        }
    }
    
    update_post_meta($company_id, '_sedes', $new_sedes);
    
    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Sede eliminada',
    ), 200);
}

// ==========================================
// 3. ENDPOINTS DE UBICACIONES
// ==========================================

/**
 * Búsqueda de ubicaciones
 */
function agrochamba_api_search_locations($request) {
    $query = $request->get_param('q');
    $limit = $request->get_param('limit');
    
    $results = agrochamba_search_locations($query, $limit);
    
    return new WP_REST_Response(array(
        'query' => $query,
        'results' => $results,
        'total' => count($results),
    ), 200);
}

/**
 * Resolver ubicación desde distrito
 */
function agrochamba_api_resolve_location($request) {
    $distrito = $request->get_param('distrito');
    $provincia = $request->get_param('provincia');
    $departamento = $request->get_param('departamento');
    
    if ($distrito) {
        $result = agrochamba_resolve_from_distrito($distrito);
        if ($result) {
            return new WP_REST_Response($result, 200);
        }
    }
    
    // Si se proporcionan los tres, validar
    if ($departamento && $provincia && $distrito) {
        $ubicacion = array(
            'departamento' => $departamento,
            'provincia' => $provincia,
            'distrito' => $distrito,
        );
        
        if (agrochamba_is_valid_location($ubicacion)) {
            $normalizada = agrochamba_normalize_location($ubicacion);
            return new WP_REST_Response($normalizada, 200);
        }
    }
    
    return new WP_Error('not_found', 'Ubicación no encontrada', array('status' => 404));
}

/**
 * Obtener lista de departamentos
 */
function agrochamba_api_get_departamentos($request) {
    $departamentos = agrochamba_get_departamentos();
    return new WP_REST_Response(array(
        'departamentos' => $departamentos,
        'total' => count($departamentos),
    ), 200);
}

/**
 * Obtener provincias de un departamento
 */
function agrochamba_api_get_provincias($request) {
    $departamento = $request->get_param('departamento');
    $provincias = agrochamba_get_provincias($departamento);
    
    if (empty($provincias)) {
        return new WP_Error('not_found', 'Departamento no encontrado', array('status' => 404));
    }
    
    return new WP_REST_Response(array(
        'departamento' => $departamento,
        'provincias' => $provincias,
        'total' => count($provincias),
    ), 200);
}

/**
 * Obtener distritos de una provincia
 */
function agrochamba_api_get_distritos($request) {
    $departamento = $request->get_param('departamento');
    $provincia = $request->get_param('provincia');
    $distritos = agrochamba_get_distritos($departamento, $provincia);
    
    if (empty($distritos)) {
        return new WP_Error('not_found', 'Provincia no encontrada', array('status' => 404));
    }
    
    return new WP_REST_Response(array(
        'departamento' => $departamento,
        'provincia' => $provincia,
        'distritos' => $distritos,
        'total' => count($distritos),
    ), 200);
}

// ==========================================
// 4. HELPER: OBTENER SEDES DEL USUARIO ACTUAL
// ==========================================

/**
 * Obtiene las sedes de las empresas del usuario actual
 */
function agrochamba_get_user_company_sedes($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    // Obtener empresas del usuario
    $empresas = get_posts(array(
        'post_type' => 'empresa',
        'post_status' => 'publish',
        'author' => $user_id,
        'posts_per_page' => -1,
    ));
    
    $all_sedes = array();
    
    foreach ($empresas as $empresa) {
        $sedes = get_post_meta($empresa->ID, '_sedes', true);
        if (is_array($sedes)) {
            foreach ($sedes as $sede) {
                if (isset($sede['activa']) && $sede['activa']) {
                    $sede['empresa_id'] = $empresa->ID;
                    $sede['empresa_nombre'] = $empresa->post_title;
                    $all_sedes[] = $sede;
                }
            }
        }
    }
    
    // Ordenar: principales primero
    usort($all_sedes, function($a, $b) {
        $a_principal = isset($a['es_principal']) && $a['es_principal'] ? 1 : 0;
        $b_principal = isset($b['es_principal']) && $b['es_principal'] ? 1 : 0;
        return $b_principal - $a_principal;
    });
    
    return $all_sedes;
}

