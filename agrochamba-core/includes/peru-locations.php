<?php
/**
 * =============================================================================
 * PERU LOCATIONS - Fuente única de verdad para ubicaciones del Perú
 * =============================================================================
 * 
 * Este archivo contiene:
 * - Datos completos de departamentos, provincias y distritos del Perú
 * - Funciones de búsqueda inteligente
 * - Funciones de validación y normalización
 * - Funciones para manejo de sedes de empresa
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtiene todos los departamentos del Perú
 * 
 * @return array Lista completa de departamentos con provincias y distritos
 */
function agrochamba_get_peru_locations() {
    static $locations = null;
    
    if ($locations !== null) {
        return $locations;
    }
    
    $locations = array(
        array(
            'departamento' => 'Amazonas',
            'provincias' => array(
                array('provincia' => 'Chachapoyas', 'distritos' => array('Chachapoyas', 'Asunción', 'Balsas', 'Cheto', 'Chiliquín', 'Chuquibamba', 'Granada', 'Huancas', 'La Jalca', 'Leimebamba', 'Levanto', 'Magdalena', 'Mariscal Castilla', 'Molino', 'Montevideo', 'Olleros', 'Quinjalca', 'San Francisco de Daguas', 'San Juan de Lopecancha', 'Santa Leocadia', 'Soloco', 'Sonche')),
                array('provincia' => 'Bagua', 'distritos' => array('Bagua', 'Aramango', 'Copacabana', 'El Parco', 'Imaza', 'La Peca')),
                array('provincia' => 'Bongará', 'distritos' => array('Jumbilla', 'Chisquilla', 'Corosha', 'Cuenca', 'Florida', 'Jazán', 'Recta', 'San Carlos', 'Shipasbamba', 'Valera', 'Yambrasbamba', 'San Jerónimo')),
                array('provincia' => 'Condorcanqui', 'distritos' => array('Nieva', 'El Cenepa', 'Río Santiago')),
                array('provincia' => 'Luya', 'distritos' => array('Lámud', 'Camporredondo', 'Cocabamba', 'Colcamar', 'Conila', 'Inguilpata', 'Longuita', 'Lonya Chico', 'Luya Viejo', 'María', 'Ocalli', 'San Cristóbal', 'San Francisco del Yeso', 'San Jerónimo', 'San Juan de Lopecancha', 'Santa Catalina', 'Trita')),
                array('provincia' => 'Rodríguez de Mendoza', 'distritos' => array('San Nicolás', 'Chirimoto', 'Cochamal', 'Huambo', 'Limabamba', 'Longar', 'Mariscal Benavides', 'Milpuc', 'Omia', 'San Juan de Rioja', 'Santa Rosa', 'Totora', 'Vista Alegre')),
                array('provincia' => 'Utcubamba', 'distritos' => array('Bagua Grande', 'Cajaruro', 'Cumba', 'El Milagro', 'Jamalca', 'Lonya Grande', 'Yamon')),
            ),
        ),
        array(
            'departamento' => 'Áncash',
            'provincias' => array(
                array('provincia' => 'Huaraz', 'distritos' => array('Huaraz', 'Cochabamba', 'Colcabamba', 'Independencia', 'Jangas', 'La Libertad', 'Olleros', 'Pampas Grande', 'Pariacoto', 'Pira', 'Tarica')),
                array('provincia' => 'Aija', 'distritos' => array('Aija', 'Coris', 'Huacllan', 'La Merced', 'Succha')),
                array('provincia' => 'Santa', 'distritos' => array('Chimbote', 'Cáceres del Perú', 'Coishco', 'Nepeña', 'Samanco', 'Santa', 'Nuevo Chimbote')),
            ),
        ),
        array(
            'departamento' => 'Apurímac',
            'provincias' => array(
                array('provincia' => 'Abancay', 'distritos' => array('Abancay', 'Chacoche', 'Circa', 'Curahuasi', 'Huanipaca', 'Lambrama', 'Pichirhua', 'San Pedro de Cachora', 'Tamburco')),
                array('provincia' => 'Andahuaylas', 'distritos' => array('Andahuaylas', 'Andarapa', 'Chiara', 'Huancarama', 'Huancaray', 'Huayana', 'Kishuara', 'Pacobamba', 'Pacucha', 'Pumaqucha', 'San Antonio de Cachi', 'San Jerónimo', 'Talavera', 'Turpo')),
            ),
        ),
        array(
            'departamento' => 'Arequipa',
            'provincias' => array(
                array('provincia' => 'Arequipa', 'distritos' => array('Arequipa', 'Alto Selva Alegre', 'Cayma', 'Cerro Colorado', 'Characato', 'Chiguata', 'Hunter', 'Jacobo Hunter', 'José Luis Bustamante y Rivero', 'La Joya', 'Mariano Melgar', 'Miraflores', 'Mollebaya', 'Paucarpata', 'Pocsi', 'Polobaya', 'Quequeña', 'Sabandía', 'Sachaca', 'San Juan de Siguas', 'San Juan de Tarucani', 'Santa Isabel de Siguas', 'Socabaya', 'Tiabaya', 'Uchumayo', 'Vítor', 'Yanahuara', 'Yura')),
                array('provincia' => 'Camaná', 'distritos' => array('Camaná', 'José María Quimper', 'Mariano Nicolás Valcárcel', 'Mariscal Cáceres', 'Nicolás de Piérola', 'Ocoña', 'Quilca', 'Samuel Pastor')),
                array('provincia' => 'Islay', 'distritos' => array('Mollendo', 'Cocachacra', 'Dean Valdivia', 'Islay', 'Mejía', 'Punta de Bombón')),
            ),
        ),
        array(
            'departamento' => 'Ayacucho',
            'provincias' => array(
                array('provincia' => 'Huamanga', 'distritos' => array('Ayacucho', 'Acocro', 'Acos Vinchos', 'Carmen Alto', 'Chiara', 'Quinua', 'San José de Ticllas', 'San Juan Bautista', 'Santiago de Pischa', 'Socos', 'Tambillo', 'Vinchos')),
                array('provincia' => 'Lucanas', 'distritos' => array('Puquio', 'Aucará', 'Cabana', 'Carmen Salcedo', 'Chaviña', 'Chipao', 'Huac-Huas', 'Laramate', 'Leoncio Prado', 'Llauta', 'Lucanas', 'Ocaña', 'Otoca', 'Saisa', 'San Cristóbal', 'San Juan', 'San Pedro', 'Santa Ana', 'Santa Lucía', 'Saurama', 'Soras', 'Subtanjalla', 'Usquiche')),
            ),
        ),
        array(
            'departamento' => 'Cajamarca',
            'provincias' => array(
                array('provincia' => 'Cajamarca', 'distritos' => array('Cajamarca', 'Asunción', 'Chetilla', 'Cospan', 'La Encañada', 'Llacanora', 'Los Baños del Inca', 'Magdalena', 'Matara', 'Namora', 'San Juan')),
                array('provincia' => 'Jaén', 'distritos' => array('Jaén', 'Bellavista', 'Chontali', 'Colasay', 'Huabal', 'Las Pirias', 'Pomahuaca', 'Pucará', 'Sallique', 'San Felipe', 'San José del Alto', 'Santa Rosa')),
            ),
        ),
        array(
            'departamento' => 'Cusco',
            'provincias' => array(
                array('provincia' => 'Cusco', 'distritos' => array('Cusco', 'Ccorca', 'Poroy', 'San Jerónimo', 'San Sebastián', 'Santiago', 'Saylla', 'Wanchaq')),
                array('provincia' => 'Urubamba', 'distritos' => array('Urubamba', 'Chinchero', 'Huayllabamba', 'Machupicchu', 'Maras', 'Ollantaytambo', 'Yucay')),
            ),
        ),
        array(
            'departamento' => 'Huancavelica',
            'provincias' => array(
                array('provincia' => 'Huancavelica', 'distritos' => array('Huancavelica', 'Acobambilla', 'Acoria', 'Ascensión', 'Conayca', 'Cuenca', 'Huachocolpa', 'Huayllahuara', 'Izcuchaca', 'Laria', 'Manta', 'Mariscal Cáceres', 'Moya', 'Nuevo Occoro', 'Palca', 'Pilchaca', 'Vilca', 'Yauli')),
            ),
        ),
        array(
            'departamento' => 'Huánuco',
            'provincias' => array(
                array('provincia' => 'Huánuco', 'distritos' => array('Huánuco', 'Amarilis', 'Chinchao', 'Churubamba', 'Margos', 'Pillco Marca', 'Quisqui', 'San Francisco de Cayrán', 'San Pedro de Chaulán', 'Santa María del Valle', 'Yarumayo')),
            ),
        ),
        array(
            'departamento' => 'Ica',
            'provincias' => array(
                array('provincia' => 'Ica', 'distritos' => array('Ica', 'La Tinguiña', 'Los Aquijes', 'Ocucaje', 'Pachacútec', 'Parcona', 'Pueblo Nuevo', 'Salas', 'San José de Los Molinos', 'San Juan Bautista', 'Santiago', 'Subtanjalla', 'Tate', 'Yauca del Rosario')),
                array('provincia' => 'Chincha', 'distritos' => array('Chincha Alta', 'Alto Larán', 'Chavín', 'Chincha Baja', 'El Carmen', 'Grocio Prado', 'Pueblo Nuevo', 'San Juan de Yanac', 'San Pedro de Huacarpana', 'Sunampe', 'Tambo de Mora')),
                array('provincia' => 'Nazca', 'distritos' => array('Nazca', 'Changuillo', 'El Ingenio', 'Marcona', 'Vista Alegre')),
                array('provincia' => 'Palpa', 'distritos' => array('Palpa', 'Llipata', 'Río Grande', 'Santa Cruz', 'Tibillo')),
                array('provincia' => 'Pisco', 'distritos' => array('Pisco', 'Huancano', 'Humay', 'Independencia', 'Paracas', 'San Andrés', 'San Clemente', 'Túpac Amaru Inca')),
            ),
        ),
        array(
            'departamento' => 'Junín',
            'provincias' => array(
                array('provincia' => 'Huancayo', 'distritos' => array('Huancayo', 'Carhuacallanga', 'Chacapampa', 'Chicche', 'Chilca', 'Chongos Alto', 'Chupuro', 'El Tambo', 'Huacrapuquio', 'Hualhuas', 'Huancán', 'Huasicancha', 'Huayucachi', 'Ingenio', 'Pariahuanca', 'Pilcomayo', 'Pucará', 'Quichuay', 'Quilcas', 'San Agustín', 'San Jerónimo de Tunán', 'Saño', 'Santo Domingo de Acobamba', 'Sapallanga', 'Sicaya', 'Viques')),
                array('provincia' => 'Satipo', 'distritos' => array('Satipo', 'Coviriali', 'Llaylla', 'Mazamari', 'Pampa Hermosa', 'Pangoa', 'Río Negro', 'Río Tambo')),
            ),
        ),
        array(
            'departamento' => 'La Libertad',
            'provincias' => array(
                array('provincia' => 'Trujillo', 'distritos' => array('Trujillo', 'El Porvenir', 'Florencia de Mora', 'Huanchaco', 'La Esperanza', 'Laredo', 'Moche', 'Poroto', 'Salaverry', 'Simbal', 'Víctor Larco Herrera')),
                array('provincia' => 'Virú', 'distritos' => array('Virú', 'Chao', 'Guadalupito')),
            ),
        ),
        array(
            'departamento' => 'Lambayeque',
            'provincias' => array(
                array('provincia' => 'Chiclayo', 'distritos' => array('Chiclayo', 'Cayaltí', 'Chongoyape', 'Eten', 'Eten Puerto', 'José Leonardo Ortiz', 'La Victoria', 'Lagunas', 'Monsefú', 'Nueva Arica', 'Oyotún', 'Picsi', 'Pimentel', 'Pomalca', 'Pucalá', 'Reque', 'Santa Rosa', 'Saña', 'Tumán')),
                array('provincia' => 'Lambayeque', 'distritos' => array('Lambayeque', 'Chóchope', 'Íllimo', 'Jayanca', 'Mochumí', 'Mórrope', 'Motupe', 'Olmos', 'Pacora', 'Salas', 'San José', 'Túcume')),
            ),
        ),
        array(
            'departamento' => 'Lima',
            'provincias' => array(
                array('provincia' => 'Lima', 'distritos' => array('Lima', 'Ancón', 'Ate', 'Barranco', 'Breña', 'Carabayllo', 'Chaclacayo', 'Chorrillos', 'Cieneguilla', 'Comas', 'El Agustino', 'Independencia', 'Jesús María', 'La Molina', 'La Victoria', 'Lince', 'Los Olivos', 'Lurigancho-Chosica', 'Lurin', 'Magdalena del Mar', 'Miraflores', 'Pachacámac', 'Pucusana', 'Pueblo Libre', 'Puente Piedra', 'Punta Hermosa', 'Punta Negra', 'Rímac', 'San Bartolo', 'San Borja', 'San Isidro', 'San Juan de Lurigancho', 'San Juan de Miraflores', 'San Luis', 'San Martín de Porres', 'San Miguel', 'Santa Anita', 'Santa María del Mar', 'Santa Rosa', 'Santiago de Surco', 'Surquillo', 'Villa El Salvador', 'Villa María del Triunfo')),
                array('provincia' => 'Cañete', 'distritos' => array('San Vicente de Cañete', 'Asia', 'Calango', 'Cerro Azul', 'Chilca', 'Coayllo', 'Imperial', 'Lunahuaná', 'Mala', 'Nuevo Imperial', 'Pacarán', 'Quilmaná', 'San Antonio', 'San Luis', 'Santa Cruz de Flores', 'Zúñiga')),
                array('provincia' => 'Huaral', 'distritos' => array('Huaral', 'Atavillos Alto', 'Atavillos Bajo', 'Aucallama', 'Chancay', 'Ihuarí', 'Lampián', 'Pacaraos', 'San Miguel de Acos', 'Santa Cruz de Andamarca', 'Sumbilca', 'Veintisiete de Noviembre')),
            ),
        ),
        array(
            'departamento' => 'Loreto',
            'provincias' => array(
                array('provincia' => 'Maynas', 'distritos' => array('Iquitos', 'Alto Nanay', 'Fernando Lores', 'Indiana', 'Las Amazonas', 'Mazán', 'Napo', 'Punchana', 'Belén', 'San Juan Bautista', 'Torres Causana')),
            ),
        ),
        array(
            'departamento' => 'Madre de Dios',
            'provincias' => array(
                array('provincia' => 'Tambopata', 'distritos' => array('Tambopata', 'Inambari', 'Las Piedras', 'Laberinto')),
            ),
        ),
        array(
            'departamento' => 'Moquegua',
            'provincias' => array(
                array('provincia' => 'Mariscal Nieto', 'distritos' => array('Moquegua', 'Carumas', 'Cuchumbaya', 'Samegua', 'San Cristóbal', 'Torata')),
                array('provincia' => 'Ilo', 'distritos' => array('Ilo', 'El Algarrobal', 'Pacocha')),
            ),
        ),
        array(
            'departamento' => 'Pasco',
            'provincias' => array(
                array('provincia' => 'Pasco', 'distritos' => array('Chaupimarca', 'Huachón', 'Huariaca', 'Huayllay', 'Ninacaca', 'Pallanchacra', 'Paucartambo', 'San Francisco de Asís de Yarusyacán', 'Simón Bolívar', 'Ticlacayan', 'Tinyahuarco', 'Vicco', 'Yanacancha')),
            ),
        ),
        array(
            'departamento' => 'Piura',
            'provincias' => array(
                array('provincia' => 'Piura', 'distritos' => array('Piura', 'Castilla', 'Catacaos', 'Cura Mori', 'El Tallán', 'La Arena', 'La Unión', 'Las Lomas', 'Tambo Grande', 'Veintiséis de Octubre')),
                array('provincia' => 'Sullana', 'distritos' => array('Sullana', 'Bellavista', 'Ignacio Escudero', 'Lancones', 'Marcavelica', 'Miguel Checa', 'Querecotillo', 'Salitral')),
                array('provincia' => 'Talara', 'distritos' => array('Pariñas', 'El Alto', 'La Brea', 'Lobitos', 'Los Órganos', 'Máncora')),
            ),
        ),
        array(
            'departamento' => 'Puno',
            'provincias' => array(
                array('provincia' => 'Puno', 'distritos' => array('Puno', 'Acora', 'Amantaní', 'Atuncolla', 'Capachica', 'Chucuito', 'Coata', 'Huata', 'Mañazo', 'Paucarcolla', 'Pichacani', 'Platería', 'San Antonio', 'Tiquillaca', 'Vilque')),
                array('provincia' => 'San Román', 'distritos' => array('Juliaca', 'Cabana', 'Cabanillas', 'Caracoto', 'San Miguel')),
            ),
        ),
        array(
            'departamento' => 'San Martín',
            'provincias' => array(
                array('provincia' => 'Moyobamba', 'distritos' => array('Moyobamba', 'Calzada', 'Habana', 'Jepelacio', 'Soritor', 'Yantalo')),
                array('provincia' => 'San Martín', 'distritos' => array('Tarapoto', 'Alberto Leveau', 'Cacatachi', 'Chazuta', 'Chipurana', 'El Porvenir', 'Huimbayoc', 'Juan Guerra', 'La Banda de Shilcayo', 'Morales', 'Papaplaya', 'San Antonio', 'Sauce', 'Shapaja')),
            ),
        ),
        array(
            'departamento' => 'Tacna',
            'provincias' => array(
                array('provincia' => 'Tacna', 'distritos' => array('Tacna', 'Alto de la Alianza', 'Calana', 'Ciudad Nueva', 'Coronel Gregorio Albarracín Lanchipa', 'Inclán', 'Pachía', 'Palca', 'Pocollay', 'Sama')),
            ),
        ),
        array(
            'departamento' => 'Tumbes',
            'provincias' => array(
                array('provincia' => 'Tumbes', 'distritos' => array('Tumbes', 'Corrales', 'La Cruz', 'Pampas de Hospital', 'San Jacinto', 'San Juan de la Virgen')),
                array('provincia' => 'Zarumilla', 'distritos' => array('Zarumilla', 'Aguas Verdes', 'Matapalo', 'Papayal')),
            ),
        ),
        array(
            'departamento' => 'Ucayali',
            'provincias' => array(
                array('provincia' => 'Coronel Portillo', 'distritos' => array('Callería', 'Campoverde', 'Iparia', 'Masisea', 'Yarinacocha', 'Nueva Requena', 'Manantay')),
            ),
        ),
    );
    
    return $locations;
}

/**
 * Normaliza texto para búsqueda (quita tildes, minúsculas)
 */
function agrochamba_normalize_text($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = trim($text);
    
    // Remover tildes
    $replacements = array(
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
        'ñ' => 'n', 'Ñ' => 'n', 'ü' => 'u', 'Ü' => 'u'
    );
    
    $text = strtr($text, $replacements);
    $text = preg_replace('/\s+/', ' ', $text);
    
    return $text;
}

/**
 * Calcula score de similitud entre dos strings
 */
function agrochamba_calculate_similarity($query, $target) {
    $normalized_query = agrochamba_normalize_text($query);
    $normalized_target = agrochamba_normalize_text($target);
    
    if ($normalized_target === $normalized_query) {
        return 100;
    }
    
    if (strpos($normalized_target, $normalized_query) === 0) {
        return 90;
    }
    
    if (strpos($normalized_target, $normalized_query) !== false) {
        return 70;
    }
    
    if (strpos($normalized_query, $normalized_target) !== false) {
        return 50;
    }
    
    return 0;
}

/**
 * Búsqueda inteligente de ubicaciones
 * 
 * @param string $query Texto a buscar
 * @param int $limit Máximo de resultados
 * @return array Lista de resultados
 */
function agrochamba_search_locations($query, $limit = 10) {
    if (empty($query) || strlen($query) < 2) {
        return array();
    }
    
    $locations = agrochamba_get_peru_locations();
    $results = array();
    $normalized_query = agrochamba_normalize_text($query);
    $seen = array();
    
    foreach ($locations as $dep) {
        $dep_normalized = agrochamba_normalize_text($dep['departamento']);
        
        // Buscar en departamentos
        if (strpos($dep_normalized, $normalized_query) !== false || strpos($normalized_query, $dep_normalized) !== false) {
            $score = agrochamba_calculate_similarity($query, $dep['departamento']);
            $key = 'dep:' . $dep['departamento'];
            if ($score > 0 && !isset($seen[$key])) {
                $seen[$key] = true;
                $results[] = array(
                    'texto' => $dep['departamento'],
                    'tipo' => 'departamento',
                    'departamento' => $dep['departamento'],
                    'provincia' => null,
                    'distrito' => null,
                    'displayLabel' => '📍 ' . $dep['departamento'] . ' (Departamento)',
                    'score' => $score + 10,
                );
            }
        }
        
        foreach ($dep['provincias'] as $prov) {
            $prov_normalized = agrochamba_normalize_text($prov['provincia']);
            
            // Buscar en provincias
            if (strpos($prov_normalized, $normalized_query) !== false || strpos($normalized_query, $prov_normalized) !== false) {
                $score = agrochamba_calculate_similarity($query, $prov['provincia']);
                $key = 'prov:' . $dep['departamento'] . ':' . $prov['provincia'];
                if ($score > 0 && !isset($seen[$key])) {
                    $seen[$key] = true;
                    $results[] = array(
                        'texto' => $prov['provincia'],
                        'tipo' => 'provincia',
                        'departamento' => $dep['departamento'],
                        'provincia' => $prov['provincia'],
                        'distrito' => null,
                        'displayLabel' => '🏘️ ' . $prov['provincia'] . ', ' . $dep['departamento'],
                        'score' => $score + 5,
                    );
                }
            }
            
            foreach ($prov['distritos'] as $dist) {
                $dist_normalized = agrochamba_normalize_text($dist);
                
                // Buscar en distritos
                if (strpos($dist_normalized, $normalized_query) !== false || strpos($normalized_query, $dist_normalized) !== false) {
                    $score = agrochamba_calculate_similarity($query, $dist);
                    $key = 'dist:' . $dep['departamento'] . ':' . $prov['provincia'] . ':' . $dist;
                    if ($score > 0 && !isset($seen[$key])) {
                        $seen[$key] = true;
                        $results[] = array(
                            'texto' => $dist,
                            'tipo' => 'distrito',
                            'departamento' => $dep['departamento'],
                            'provincia' => $prov['provincia'],
                            'distrito' => $dist,
                            'displayLabel' => '📌 ' . $dist . ', ' . $prov['provincia'] . ', ' . $dep['departamento'],
                            'score' => $score,
                        );
                    }
                }
            }
        }
    }
    
    // Ordenar por score
    usort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return array_slice($results, 0, $limit);
}

/**
 * Resuelve ubicación completa desde un distrito
 */
function agrochamba_resolve_from_distrito($distrito) {
    $locations = agrochamba_get_peru_locations();
    $normalized_distrito = agrochamba_normalize_text($distrito);
    
    foreach ($locations as $dep) {
        foreach ($dep['provincias'] as $prov) {
            foreach ($prov['distritos'] as $dist) {
                if (agrochamba_normalize_text($dist) === $normalized_distrito) {
                    return array(
                        'departamento' => $dep['departamento'],
                        'provincia' => $prov['provincia'],
                        'distrito' => $dist,
                    );
                }
            }
        }
    }
    
    return null;
}

/**
 * Valida si una ubicación es válida
 */
function agrochamba_is_valid_location($ubicacion) {
    if (!isset($ubicacion['departamento']) || !isset($ubicacion['provincia']) || !isset($ubicacion['distrito'])) {
        return false;
    }
    
    $locations = agrochamba_get_peru_locations();
    
    foreach ($locations as $dep) {
        if (agrochamba_normalize_text($dep['departamento']) === agrochamba_normalize_text($ubicacion['departamento'])) {
            foreach ($dep['provincias'] as $prov) {
                if (agrochamba_normalize_text($prov['provincia']) === agrochamba_normalize_text($ubicacion['provincia'])) {
                    foreach ($prov['distritos'] as $dist) {
                        if (agrochamba_normalize_text($dist) === agrochamba_normalize_text($ubicacion['distrito'])) {
                            return true;
                        }
                    }
                }
            }
        }
    }
    
    return false;
}

/**
 * Normaliza y corrige una ubicación
 */
function agrochamba_normalize_location($ubicacion) {
    $locations = agrochamba_get_peru_locations();
    
    foreach ($locations as $dep) {
        if (agrochamba_normalize_text($dep['departamento']) === agrochamba_normalize_text($ubicacion['departamento'])) {
            foreach ($dep['provincias'] as $prov) {
                if (agrochamba_normalize_text($prov['provincia']) === agrochamba_normalize_text($ubicacion['provincia'])) {
                    foreach ($prov['distritos'] as $dist) {
                        if (agrochamba_normalize_text($dist) === agrochamba_normalize_text($ubicacion['distrito'])) {
                            return array(
                                'departamento' => $dep['departamento'],
                                'provincia' => $prov['provincia'],
                                'distrito' => $dist,
                                'direccion' => isset($ubicacion['direccion']) ? $ubicacion['direccion'] : null,
                            );
                        }
                    }
                }
            }
        }
    }
    
    return null;
}

/**
 * Obtiene lista simple de departamentos
 */
function agrochamba_get_departamentos() {
    $locations = agrochamba_get_peru_locations();
    return array_map(function($dep) {
        return $dep['departamento'];
    }, $locations);
}

/**
 * Obtiene provincias de un departamento
 */
function agrochamba_get_provincias($departamento) {
    $locations = agrochamba_get_peru_locations();
    
    foreach ($locations as $dep) {
        if (agrochamba_normalize_text($dep['departamento']) === agrochamba_normalize_text($departamento)) {
            return array_map(function($prov) {
                return $prov['provincia'];
            }, $dep['provincias']);
        }
    }
    
    return array();
}

/**
 * Obtiene distritos de una provincia
 */
function agrochamba_get_distritos($departamento, $provincia) {
    $locations = agrochamba_get_peru_locations();
    
    foreach ($locations as $dep) {
        if (agrochamba_normalize_text($dep['departamento']) === agrochamba_normalize_text($departamento)) {
            foreach ($dep['provincias'] as $prov) {
                if (agrochamba_normalize_text($prov['provincia']) === agrochamba_normalize_text($provincia)) {
                    return $prov['distritos'];
                }
            }
        }
    }
    
    return array();
}

/**
 * Formatea ubicación para mostrar en cards (solo departamento)
 */
function agrochamba_format_location_for_card($ubicacion) {
    if (is_array($ubicacion) && isset($ubicacion['departamento'])) {
        return $ubicacion['departamento'];
    }
    return '';
}

/**
 * Formatea ubicación completa en una línea
 */
function agrochamba_format_location_one_line($ubicacion, $include_direccion = false) {
    if (!is_array($ubicacion)) {
        return '';
    }
    
    $parts = array();
    if (isset($ubicacion['distrito'])) $parts[] = $ubicacion['distrito'];
    if (isset($ubicacion['provincia'])) $parts[] = $ubicacion['provincia'];
    if (isset($ubicacion['departamento'])) $parts[] = $ubicacion['departamento'];
    
    $result = implode(', ', $parts);
    
    if ($include_direccion && isset($ubicacion['direccion']) && !empty($ubicacion['direccion'])) {
        $result .= ' - ' . $ubicacion['direccion'];
    }
    
    return $result;
}

