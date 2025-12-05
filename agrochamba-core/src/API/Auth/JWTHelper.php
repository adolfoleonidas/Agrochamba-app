<?php
/**
 * Helper para JWT
 *
 * Maneja la generación de tokens JWT
 *
 * @package AgroChamba
 * @subpackage API\Auth
 * @since 2.0.0
 */

namespace AgroChamba\API\Auth;

class JWTHelper {

    /**
     * Generar token JWT
     *
     * @param string $username
     * @param string $password
     * @return string|null Token JWT o null si falla
     */
    public static function generate_token($username, $password) {
        $login_credentials = array(
            'username' => $username,
            'password' => $password
        );

        // Usar el endpoint de JWT Auth
        $token_url = rest_url('jwt-auth/v1/token');
        $token_response = wp_remote_post($token_url, array(
            'body' => json_encode($login_credentials),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 10
        ));

        if (!is_wp_error($token_response)) {
            $response_code = wp_remote_retrieve_response_code($token_response);
            if ($response_code === 200) {
                $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
                if (isset($token_body['token'])) {
                    return $token_body['token'];
                }
            }
        }

        return null;
    }
}
