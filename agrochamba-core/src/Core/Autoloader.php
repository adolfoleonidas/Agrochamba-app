<?php
/**
 * PSR-4 Autoloader for AgroChamba Core
 *
 * @package AgroChamba\Core
 */

namespace AgroChamba\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Autoloader {

    /**
     * Namespace prefix
     */
    private const NAMESPACE_PREFIX = 'AgroChamba\\';

    /**
     * Base directory
     */
    private $base_dir;

    /**
     * Constructor
     */
    public function __construct() {
        $this->base_dir = dirname(dirname(__FILE__)) . '/';
    }

    /**
     * Register autoloader
     */
    public function register() {
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Autoload classes
     *
     * @param string $class Fully qualified class name
     */
    private function autoload($class) {
        // Check if class uses our namespace
        if (strpos($class, self::NAMESPACE_PREFIX) !== 0) {
            return;
        }

        // Remove namespace prefix
        $relative_class = substr($class, strlen(self::NAMESPACE_PREFIX));

        // Convert namespace to file path
        $file = $this->base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // Load file if exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
