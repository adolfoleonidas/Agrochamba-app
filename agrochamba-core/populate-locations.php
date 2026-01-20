<?php
/**
 * =============================================================================
 * SCRIPT PARA POBLAR TAXONOMÍA DE UBICACIONES DEL PERÚ
 * =============================================================================
 * 
 * Este script crea todos los términos de ubicación (departamentos, provincias, distritos)
 * en la taxonomía jerárquica 'ubicacion'.
 * 
 * EJECUTAR UNA SOLA VEZ:
 * 
 * Opción 1: Desde WordPress Admin
 * - Ir a: /wp-json/agrochamba/v1/populate-locations
 * - Método: POST
 * - Requiere: Ser administrador
 * 
 * Opción 2: Desde línea de comandos (WP-CLI)
 * wp eval-file agrochamba-core/populate-locations.php
 * 
 * Opción 3: Desde navegador (una sola vez, luego borrar este archivo)
 * - Acceder a: /wp-content/plugins/agrochamba-core/populate-locations.php
 * - Requiere: Ser administrador
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que el usuario es administrador
if (!current_user_can('manage_options')) {
    die('Error: Solo administradores pueden ejecutar este script.');
}

// Verificar que la función existe
if (!function_exists('agrochamba_populate_all_location_terms')) {
    die('Error: La función agrochamba_populate_all_location_terms no está disponible. Asegúrate de que el plugin está activo.');
}

echo "<h1>Poblando taxonomía de ubicaciones del Perú...</h1>";
echo "<p>Esto puede tomar unos minutos. Por favor espera...</p>";
echo "<hr>";

// Ejecutar la función
$stats = agrochamba_populate_all_location_terms();

// Mostrar resultados
echo "<h2>Resultados:</h2>";
echo "<ul>";
echo "<li><strong>Departamentos creados:</strong> " . $stats['departamentos'] . "</li>";
echo "<li><strong>Provincias creadas:</strong> " . $stats['provincias'] . "</li>";
echo "<li><strong>Distritos creados:</strong> " . $stats['distritos'] . "</li>";
echo "<li><strong>Total de términos:</strong> " . ($stats['departamentos'] + $stats['provincias'] + $stats['distritos']) . "</li>";
echo "</ul>";

if (!empty($stats['errores'])) {
    echo "<h3>Errores encontrados (" . count($stats['errores']) . "):</h3>";
    echo "<ul>";
    foreach ($stats['errores'] as $error) {
        echo "<li style='color: red;'>" . esc_html($error) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'><strong>✓ Todos los términos se crearon correctamente.</strong></p>";
}

echo "<hr>";
echo "<p><strong>¡Listo!</strong> La taxonomía ha sido poblada. Puedes borrar este archivo ahora.</p>";
