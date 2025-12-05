<?php
/**
 * Módulo 00 migrado a namespace: shim de compatibilidad
 *
 * Este archivo mantiene la compatibilidad de carga desde /modules,
 * pero delega la lógica en la clase \AgroChamba\Security\CORSManager.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('AGROCHAMBA_SECURITY_NAMESPACE_INITIALIZED')) {
    define('AGROCHAMBA_SECURITY_NAMESPACE_INITIALIZED', true);

    // Registrar en log para rastrear migración (opcional)
    if (function_exists('error_log')) {
        error_log('AgroChamba: Cargando seguridad mediante AgroChamba\\Security\\CORSManager (migración namespaces).');
    }

    // Asegurar que el autoloader ya está disponible (lo carga config/bootstrap.php)
    if (class_exists('AgroChamba\\Security\\CORSManager')) {
        \AgroChamba\Security\CORSManager::init();
    } else {
        // Fallback: no hacer nada, pero alertar en log para diagnóstico
        if (function_exists('error_log')) {
            error_log('AgroChamba: No se encontró AgroChamba\\Security\\CORSManager. Verifique el autoloader.');
        }
    }
}

