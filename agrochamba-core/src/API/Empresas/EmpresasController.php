<?php
/**
 * Controlador REST API para Empresas
 * 
 * Endpoints para gestionar empresas con sus empleos embebidos
 *
 * @package AgroChamba\API\Empresas
 */

namespace AgroChamba\API\Empresas;

use WP_Error;
use WP_REST_Response;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

class EmpresasController
{
    const API_NAMESPACE = 'agrochamba/v1';

    /**
     * Registrar rutas REST API
     */
    public static function register_routes(): void
    {
        // Obtener todas las empresas con sus empleos
        register_rest_route(self::API_NAMESPACE, '/empresas', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'get_empresas'],
                'permission_callback' => '__return_true',
                'args' => [
                    'per_page' => [
                        'default' => 20,
                        'sanitize_callback' => 'absint',
                    ],
                    'page' => [
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'include_jobs' => [
                        'default' => true,
                        'sanitize_callback' => 'rest_sanitize_boolean',
                    ],
                ],
            ],
        ]);

        // Obtener una empresa específica con sus empleos
        register_rest_route(self::API_NAMESPACE, '/empresas/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'get_empresa'],
                'permission_callback' => '__return_true',
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ],
                    'include_jobs' => [
                        'default' => true,
                        'sanitize_callback' => 'rest_sanitize_boolean',
                    ],
                ],
            ],
        ]);

        // Obtener empresa por slug
        register_rest_route(self::API_NAMESPACE, '/empresas/slug/(?P<slug>[a-zA-Z0-9-]+)', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'get_empresa_by_slug'],
                'permission_callback' => '__return_true',
                'args' => [
                    'slug' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_title',
                    ],
                    'include_jobs' => [
                        'default' => true,
                        'sanitize_callback' => 'rest_sanitize_boolean',
                    ],
                ],
            ],
        ]);

        // Obtener empleos de una empresa
        register_rest_route(self::API_NAMESPACE, '/empresas/(?P<id>\d+)/empleos', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'get_empresa_empleos'],
                'permission_callback' => '__return_true',
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ],
                    'per_page' => [
                        'default' => 20,
                        'sanitize_callback' => 'absint',
                    ],
                    'page' => [
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'estado' => [
                        'default' => 'activa',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Obtener todas las empresas
     */
    public static function get_empresas($request): WP_REST_Response
    {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $include_jobs = $request->get_param('include_jobs');

        $args = [
            'post_type' => 'empresa',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        $query = new WP_Query($args);
        $empresas = [];

        foreach ($query->posts as $post) {
            $empresa_data = self::format_empresa_data($post, $include_jobs);
            $empresas[] = $empresa_data;
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $empresas,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        ], 200);
    }

    /**
     * Obtener una empresa específica
     */
    public static function get_empresa($request): WP_REST_Response|WP_Error
    {
        $empresa_id = intval($request->get_param('id'));
        $include_jobs = $request->get_param('include_jobs');

        $empresa = get_post($empresa_id);

        if (!$empresa || $empresa->post_type !== 'empresa' || $empresa->post_status !== 'publish') {
            return new WP_Error(
                'empresa_not_found',
                'Empresa no encontrada.',
                ['status' => 404]
            );
        }

        $empresa_data = self::format_empresa_data($empresa, $include_jobs);

        return new WP_REST_Response([
            'success' => true,
            'data' => $empresa_data,
        ], 200);
    }

    /**
     * Obtener empresa por slug
     */
    public static function get_empresa_by_slug($request): WP_REST_Response|WP_Error
    {
        $slug = $request->get_param('slug');
        $include_jobs = $request->get_param('include_jobs');

        $empresa = get_page_by_path($slug, OBJECT, 'empresa');

        if (!$empresa || $empresa->post_status !== 'publish') {
            return new WP_Error(
                'empresa_not_found',
                'Empresa no encontrada.',
                ['status' => 404]
            );
        }

        $empresa_data = self::format_empresa_data($empresa, $include_jobs);

        return new WP_REST_Response([
            'success' => true,
            'data' => $empresa_data,
        ], 200);
    }

    /**
     * Obtener empleos de una empresa
     */
    public static function get_empresa_empleos($request): WP_REST_Response|WP_Error
    {
        $empresa_id = intval($request->get_param('id'));
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $estado = $request->get_param('estado');

        $empresa = get_post($empresa_id);

        if (!$empresa || $empresa->post_type !== 'empresa') {
            return new WP_Error(
                'empresa_not_found',
                'Empresa no encontrada.',
                ['status' => 404]
            );
        }

        $args = [
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'meta_key' => 'empresa_id',
            'meta_value' => $empresa_id,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ($estado && $estado !== 'todas') {
            $args['meta_query'] = [
                [
                    'key' => 'estado',
                    'value' => $estado,
                    'compare' => '=',
                ],
            ];
        }

        $query = new WP_Query($args);
        $empleos = [];

        foreach ($query->posts as $post) {
            $empleos[] = self::format_empleo_data($post);
        }

        return new WP_REST_Response([
            'success' => true,
            'empresa' => [
                'id' => $empresa_id,
                'nombre' => $empresa->post_title,
            ],
            'data' => $empleos,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        ], 200);
    }

    /**
     * Formatear datos de empresa
     */
    private static function format_empresa_data($post, $include_jobs = true): array
    {
        $user_id = get_post_meta($post->ID, '_empresa_user_id', true);
        $user = $user_id ? get_userdata($user_id) : null;

        $logo_id = get_post_thumbnail_id($post->ID);
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : null;

        $empresa_data = [
            'id' => $post->ID,
            'nombre_comercial' => get_post_meta($post->ID, '_empresa_nombre_comercial', true) ?: $post->post_title,
            'razon_social' => get_post_meta($post->ID, '_empresa_razon_social', true),
            'ruc' => get_post_meta($post->ID, '_empresa_ruc', true),
            'logo_url' => $logo_url,
            'sector' => get_post_meta($post->ID, '_empresa_sector', true),
            'descripcion' => $post->post_content,
            'descripcion_corta' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
            'verificada' => get_post_meta($post->ID, '_empresa_verificada', true) === '1',
            'fundacion' => get_post_meta($post->ID, '_empresa_fundacion', true),
            'empleados' => get_post_meta($post->ID, '_empresa_empleados', true),
            'website' => get_post_meta($post->ID, '_empresa_website', true),
            'telefono' => get_post_meta($post->ID, '_empresa_telefono', true),
            'celular' => get_post_meta($post->ID, '_empresa_celular', true),
            'email_contacto' => get_post_meta($post->ID, '_empresa_email_contacto', true),
            'direccion' => get_post_meta($post->ID, '_empresa_direccion', true),
            'ciudad' => get_post_meta($post->ID, '_empresa_ciudad', true),
            'provincia' => get_post_meta($post->ID, '_empresa_provincia', true),
            'codigo_postal' => get_post_meta($post->ID, '_empresa_codigo_postal', true),
            'coordenadas' => get_post_meta($post->ID, '_empresa_coordenadas', true),
            'redes_sociales' => [
                'facebook' => get_post_meta($post->ID, '_empresa_facebook', true),
                'instagram' => get_post_meta($post->ID, '_empresa_instagram', true),
                'linkedin' => get_post_meta($post->ID, '_empresa_linkedin', true),
                'twitter' => get_post_meta($post->ID, '_empresa_twitter', true),
                'youtube' => get_post_meta($post->ID, '_empresa_youtube', true),
            ],
            'certificaciones' => get_post_meta($post->ID, '_empresa_certificaciones', true),
            'servicios' => get_post_meta($post->ID, '_empresa_servicios', true),
            'slug' => $post->post_name,
            'url' => get_permalink($post->ID),
            'fecha_registro' => $post->post_date,
            'user_id' => $user_id ? intval($user_id) : null,
            'user_email' => $user ? $user->user_email : null,
        ];

        // Contador de ofertas activas
        $ofertas_count = agrochamba_get_empresa_ofertas_count($post->ID);
        $empresa_data['ofertas_activas_count'] = $ofertas_count;

        // Incluir empleos si se solicita
        if ($include_jobs) {
            $empleos_query = agrochamba_get_empresa_ofertas($post->ID, [
                'posts_per_page' => 10,
                'meta_query' => [
                    [
                        'key' => 'estado',
                        'value' => 'activa',
                        'compare' => '=',
                    ],
                ],
            ]);

            $empleos = [];
            foreach ($empleos_query->posts as $empleo_post) {
                $empleos[] = self::format_empleo_data($empleo_post);
            }

            $empresa_data['empleos'] = $empleos;
            $empresa_data['empleos_total'] = $empleos_query->found_posts;
        }

        return $empresa_data;
    }

    /**
     * Formatear datos de empleo
     */
    private static function format_empleo_data($post): array
    {
        $featured_image_id = get_post_thumbnail_id($post->ID);
        $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'agrochamba_card') : null;

        return [
            'id' => $post->ID,
            'titulo' => $post->post_title,
            'descripcion' => $post->post_content,
            'descripcion_corta' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
            'imagen_url' => $featured_image_url,
            'salario_min' => get_post_meta($post->ID, 'salario_min', true),
            'salario_max' => get_post_meta($post->ID, 'salario_max', true),
            'vacantes' => get_post_meta($post->ID, 'vacantes', true),
            'fecha_inicio' => get_post_meta($post->ID, 'fecha_inicio', true),
            'fecha_fin' => get_post_meta($post->ID, 'fecha_fin', true),
            'tipo_contrato' => get_post_meta($post->ID, 'tipo_contrato', true),
            'jornada' => get_post_meta($post->ID, 'jornada', true),
            'estado' => get_post_meta($post->ID, 'estado', true) ?: 'activa',
            'empresa_id' => get_post_meta($post->ID, 'empresa_id', true),
            'url' => get_permalink($post->ID),
            'fecha_publicacion' => $post->post_date,
        ];
    }
}

