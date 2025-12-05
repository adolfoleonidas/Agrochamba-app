<?php
/**
 * CacheService
 * 
 * Servicio centralizado de caché para AgroChamba.
 * Proporciona helpers para get/set/delete con namespacing
 * y TTLs definidos en config/constants.php.
 */

namespace AgroChamba\Services;

if (!defined('ABSPATH')) {
    exit;
}

class CacheService
{
    /**
     * Inicializa hooks o configuraciones necesarias.
     */
    public static function init(): void
    {
        // Por ahora no se requiere hook específico; este servicio expone utilidades estáticas.
        // Si en el futuro se necesita warm-up o invalidaciones globales, colocar aquí.
    }

    /**
     * Genera una clave namespaced para caché.
     */
    public static function key(string $suffix): string
    {
        return 'agrochamba_' . ltrim($suffix, '_');
    }

    /**
     * Obtiene un valor de caché (transient).
     *
     * @param string $key  Sufijo de clave (se aplicará namespacing)
     * @return mixed
     */
    public static function get(string $key)
    {
        return get_transient(self::key($key));
    }

    /**
     * Establece un valor en caché (transient).
     *
     * @param string $key   Sufijo de clave (se aplicará namespacing)
     * @param mixed  $value Valor a guardar
     * @param int    $ttl   Tiempo de vida en segundos
     * @return bool
     */
    public static function set(string $key, $value, int $ttl): bool
    {
        return set_transient(self::key($key), $value, $ttl);
    }

    /**
     * Elimina una entrada de caché.
     */
    public static function delete(string $key): bool
    {
        return delete_transient(self::key($key));
    }
}
