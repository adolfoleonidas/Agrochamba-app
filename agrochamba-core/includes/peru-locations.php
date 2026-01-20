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
                array('provincia' => 'Bagua', 'distritos' => array('Aramango', 'Bagua', 'Copallin', 'El Parco', 'Imaza', 'La Peca')),
                array('provincia' => 'Bongara', 'distritos' => array('Chisquilla', 'Churuja', 'Corosha', 'Cuispes', 'Florida', 'Jazan', 'Jumbilla', 'Recta', 'San Carlos', 'Shipasbamba', 'Valera', 'Yambrasbamba')),
                array('provincia' => 'Chachapoyas', 'distritos' => array('Asuncion', 'Balsas', 'Chachapoyas', 'Cheto', 'Chiliquin', 'Chuquibamba', 'Granada', 'Huancas', 'La Jalca', 'Leimebamba', 'Levanto', 'Magdalena', 'Mariscal Castilla', 'Molinopampa', 'Montevideo', 'Olleros', 'Quinjalca', 'San Francisco de Daguas', 'San Isidro de Maino', 'Soloco', 'Sonche')),
                array('provincia' => 'Condorcanqui', 'distritos' => array('El Cenepa', 'Nieva', 'Rio Santiago')),
                array('provincia' => 'Luya', 'distritos' => array('Camporredondo', 'Cocabamba', 'Colcamar', 'Conila', 'Inguilpata', 'Lamud', 'Longuita', 'Lonya Chico', 'Luya', 'Luya Viejo', 'Maria', 'Ocalli', 'Ocumal', 'Pisuquia', 'Providencia', 'San Cristobal', 'San Francisco de Yeso', 'San Jeronimo', 'San Juan de Lopecancha', 'Santa Catalina', 'Santo Tomas', 'Tingo', 'Trita')),
                array('provincia' => 'Rodriguez de Mendoza', 'distritos' => array('Chirimoto', 'Cochamal', 'Huambo', 'Limabamba', 'Longar', 'Mariscal Benavides', 'Milpuc', 'Omia', 'San Nicolas', 'Santa Rosa', 'Totora', 'Vista Alegre')),
                array('provincia' => 'Utcubamba', 'distritos' => array('Bagua Grande', 'Cajaruro', 'Cumba', 'El Milagro', 'Jamalca', 'Lonya Grande', 'Yamon'))
            ),
        ),

        array(
            'departamento' => 'Ancash',
            'provincias' => array(
                array('provincia' => 'Aija', 'distritos' => array('Aija', 'Coris', 'Huacllan', 'La Merced', 'Succha')),
                array('provincia' => 'Antonio Raymondi', 'distritos' => array('Aczo', 'Chaccho', 'Chingas', 'Llamellin', 'Mirgas', 'San Juan de Rontoy')),
                array('provincia' => 'Asuncion', 'distritos' => array('Acochaca', 'Chacas')),
                array('provincia' => 'Bolognesi', 'distritos' => array('Abelardo Pardo Lezameta', 'Antonio Raymondi', 'Aquia', 'Cajacay', 'Canis', 'Chiquian', 'Colquioc', 'Huallanca', 'Huasta', 'Huayllacayan', 'La Primavera', 'Mangas', 'Pacllon', 'San Miguel de Corpanqui', 'Ticllos')),
                array('provincia' => 'Carhuaz', 'distritos' => array('Acopampa', 'Amashca', 'Anta', 'Ataquero', 'Carhuaz', 'Marcara', 'Pariahuanca', 'San Miguel de Aco', 'Shilla', 'Tinco', 'Yungar')),
                array('provincia' => 'Carlos Fermin Fitzcarrald', 'distritos' => array('San Luis', 'San Nicolas', 'Yauya')),
                array('provincia' => 'Casma', 'distritos' => array('Buena Vista Alta', 'Casma', 'Comandante Noel', 'Yautan')),
                array('provincia' => 'Corongo', 'distritos' => array('Aco', 'Bambas', 'Corongo', 'Cusca', 'La Pampa', 'Yanac', 'Yupan')),
                array('provincia' => 'Huaraz', 'distritos' => array('Cochabamba', 'Colcabamba', 'Huanchay', 'Huaraz', 'Independencia', 'Jangas', 'La Libertad', 'Olleros', 'Pampas Grande', 'Pariacoto', 'Pira', 'Tarica')),
                array('provincia' => 'Huari', 'distritos' => array('Anra', 'Cajay', 'Chavin de Huantar', 'Huacachi', 'Huacchis', 'Huachis', 'Huantar', 'Huari', 'Masin', 'Paucas', 'Ponto', 'Rahuapampa', 'Rapayan', 'San Marcos', 'San Pedro de Chana', 'Uco')),
                array('provincia' => 'Huarmey', 'distritos' => array('Cochapeti', 'Culebras', 'Huarmey', 'Huayan', 'Malvas')),
                array('provincia' => 'Huaylas', 'distritos' => array('Caraz', 'Huallanca', 'Huata', 'Huaylas', 'Mato', 'Pamparomas', 'Pueblo Libre', 'Santa Cruz', 'Santo Toribio', 'Yuracmarca')),
                array('provincia' => 'Mariscal Luzuriaga', 'distritos' => array('Casca', 'Eleazar Guzman Barron', 'Fidel Olivas Escudero', 'Llama', 'Llumpa', 'Lucma', 'Musga', 'Piscobamba')),
                array('provincia' => 'Ocros', 'distritos' => array('Acas', 'Cajamarquilla', 'Carhuapampa', 'Cochas', 'Congas', 'Llipa', 'Ocros', 'San Cristobal de Rajan', 'San Pedro', 'Santiago de Chilcas')),
                array('provincia' => 'Pallasca', 'distritos' => array('Bolognesi', 'Cabana', 'Conchucos', 'Huacaschuque', 'Huandoval', 'Lacabamba', 'Llapo', 'Pallasca', 'Pampas', 'Santa Rosa', 'Tauca')),
                array('provincia' => 'Pomabamba', 'distritos' => array('Huayllan', 'Parobamba', 'Pomabamba', 'Quinuabamba')),
                array('provincia' => 'Recuay', 'distritos' => array('Catac', 'Cotaparaco', 'Huayllapampa', 'Llacllin', 'Marca', 'Pampas Chico', 'Pararin', 'Recuay', 'Tapacocha', 'Ticapampa')),
                array('provincia' => 'Santa', 'distritos' => array('Caceres del Peru', 'Chimbote', 'Coishco', 'Macate', 'Moro', 'Nepeña', 'Nuevo Chimbote', 'Samanco', 'Santa')),
                array('provincia' => 'Sihuas', 'distritos' => array('Acobamba', 'Alfonso Ugarte', 'Cashapampa', 'Chingalpo', 'Huayllabamba', 'Quiches', 'Ragash', 'San Juan', 'Sicsibamba', 'Sihuas')),
                array('provincia' => 'Yungay', 'distritos' => array('Cascapara', 'Mancos', 'Matacoto', 'Quillo', 'Ranrahirca', 'Shupluy', 'Yanama', 'Yungay'))
            ),
        ),

        array(
            'departamento' => 'Apurimac',
            'provincias' => array(
                array('provincia' => 'Abancay', 'distritos' => array('Abancay', 'Chacoche', 'Circa', 'Curahuasi', 'Huanipaca', 'Lambrama', 'Pichirhua', 'San Pedro de Cachora', 'Tamburco')),
                array('provincia' => 'Andahuaylas', 'distritos' => array('Andahuaylas', 'Andarapa', 'Chiara', 'Huancarama', 'Huancaray', 'Huayana', 'Jose Maria Arguedas', 'Kaquiabamba', 'Kishuara', 'Pacobamba', 'Pacucha', 'Pampachiri', 'Pomacocha', 'San Antonio de Cachi', 'San Jeronimo', 'San Miguel de Chaccrampa', 'Santa Maria de Chicmo', 'Talavera', 'Tumay Huaraca', 'Turpo')),
                array('provincia' => 'Antabamba', 'distritos' => array('Antabamba', 'El Oro', 'Huaquirca', 'Juan Espinoza Medrano', 'Oropesa', 'Pachaconas', 'Sabaino')),
                array('provincia' => 'Aymaraes', 'distritos' => array('Capaya', 'Caraybamba', 'Chalhuanca', 'Chapimarca', 'Colcabamba', 'Cotaruse', 'Huayllu', 'Justo Apu Sahuaraura', 'Lucre', 'Pocohuanca', 'San Juan de Chacña', 'Sañayca', 'Soraya', 'Tapairihua', 'Tintay', 'Toraya', 'Yanaca')),
                array('provincia' => 'Chincheros', 'distritos' => array('Ahuayro', 'Anco-huallo', 'Chincheros', 'Cocharcas', 'El Porvenir', 'Huaccana', 'Los Chankas', 'Ocobamba', 'Ongoy', 'Ranracancha', 'Rocchacc', 'Uranmarca')),
                array('provincia' => 'Cotabambas', 'distritos' => array('Challhuahuacho', 'Cotabambas', 'Coyllurqui', 'Haquira', 'Mara', 'Tambobamba')),
                array('provincia' => 'Grau', 'distritos' => array('Chuquibambilla', 'Curasco', 'Curpahuasi', 'Huayllati', 'Mamara', 'Mariscal Gamarra', 'Micaela Bastidas', 'Pataypampa', 'Progreso', 'San Antonio', 'Santa Rosa', 'Turpay', 'Vilcabamba', 'Virundo'))
            ),
        ),

        array(
            'departamento' => 'Arequipa',
            'provincias' => array(
                array('provincia' => 'Arequipa', 'distritos' => array('Alto Selva Alegre', 'Arequipa', 'Cayma', 'Cerro Colorado', 'Characato', 'Chiguata', 'Jacobo Hunter', 'Jose Luis Bustamante y Rivero', 'La Joya', 'Mariano Melgar', 'Miraflores', 'Mollebaya', 'Paucarpata', 'Pocsi', 'Polobaya', 'Quequeña', 'Sabandia', 'Sachaca', 'San Juan de Siguas', 'San Juan de Tarucani', 'Santa Isabel de Siguas', 'Santa Rita de Siguas', 'Socabaya', 'Tiabaya', 'Uchumayo', 'Vitor', 'Yanahuara', 'Yarabamba', 'Yura')),
                array('provincia' => 'Camana', 'distritos' => array('Camana', 'Jose Maria Quimper', 'Mariano Nicolas Valcarcel', 'Mariscal Caceres', 'Nicolas de Pierola', 'Ocoña', 'Quilca', 'Samuel Pastor')),
                array('provincia' => 'Caraveli', 'distritos' => array('Acari', 'Atico', 'Atiquipa', 'Bella Union', 'Cahuacho', 'Caraveli', 'Chala', 'Chaparra', 'Huanuhuanu', 'Jaqui', 'Lomas', 'Quicacha', 'Yauca')),
                array('provincia' => 'Castilla', 'distritos' => array('Andagua', 'Aplao', 'Ayo', 'Chachas', 'Chilcaymarca', 'Choco', 'Huancarqui', 'Machaguay', 'Orcopampa', 'Pampacolca', 'Tipan', 'Uñon', 'Uraca', 'Viraco')),
                array('provincia' => 'Caylloma', 'distritos' => array('Achoma', 'Cabanaconde', 'Callalli', 'Caylloma', 'Chivay', 'Coporaque', 'Huambo', 'Huanca', 'Ichupampa', 'Lari', 'Lluta', 'Maca', 'Madrigal', 'Majes', 'San Antonio de Chuca', 'Sibayo', 'Tapay', 'Tisco', 'Tuti', 'Yanque')),
                array('provincia' => 'Condesuyos', 'distritos' => array('Andaray', 'Cayarani', 'Chichas', 'Chuquibamba', 'Iray', 'Rio Grande', 'Salamanca', 'Yanaquihua')),
                array('provincia' => 'Islay', 'distritos' => array('Cocachacra', 'Dean Valdivia', 'Islay', 'Mejia', 'Mollendo', 'Punta de Bombon')),
                array('provincia' => 'La Union', 'distritos' => array('Alca', 'Charcana', 'Cotahuasi', 'Huaynacotas', 'Pampamarca', 'Puyca', 'Quechualla', 'Sayla', 'Tauria', 'Tomepampa', 'Toro'))
            ),
        ),

        array(
            'departamento' => 'Ayacucho',
            'provincias' => array(
                array('provincia' => 'Cangallo', 'distritos' => array('Cangallo', 'Chuschi', 'Los Morochucos', 'Maria Parado de Bellido', 'Paras', 'Totos')),
                array('provincia' => 'Huamanga', 'distritos' => array('Acocro', 'Acos Vinchos', 'Andres Avelino Caceres Dorregaray', 'Ayacucho', 'Carmen Alto', 'Chiara', 'Jesus Nazareno', 'Ocros', 'Pacaycasa', 'Quinua', 'San Jose de Ticllas', 'San Juan Bautista', 'Santiago de Pischa', 'Socos', 'Tambillo', 'Vinchos')),
                array('provincia' => 'Huanca Sancos', 'distritos' => array('Carapo', 'Sacsamarca', 'Sancos', 'Santiago de Lucanamarca')),
                array('provincia' => 'Huanta', 'distritos' => array('Ayahuanco', 'Canayre', 'Chaca', 'Huamanguilla', 'Huanta', 'Iguain', 'Llochegua', 'Luricocha', 'Pucacolpa', 'Putis', 'Santillana', 'Sivia', 'Uchuraccay')),
                array('provincia' => 'La Mar', 'distritos' => array('Anchihuay', 'Anco', 'Ayna', 'Chilcas', 'Chungui', 'Luis Carranza', 'Ninabamba', 'Oronccoy', 'Patibamba', 'Rio Magdalena', 'Samugari', 'San Miguel', 'Santa Rosa', 'Tambo', 'Union Progreso')),
                array('provincia' => 'Lucanas', 'distritos' => array('Aucara', 'Cabana', 'Carmen Salcedo', 'Chaviña', 'Chipao', 'Huac-huas', 'Laramate', 'Leoncio Prado', 'Llauta', 'Lucanas', 'Ocaña', 'Otoca', 'Puquio', 'Saisa', 'San Cristobal', 'San Juan', 'San Pedro', 'San Pedro de Palco', 'Sancos', 'Santa Ana de Huaycahuacho', 'Santa Lucia')),
                array('provincia' => 'Parinacochas', 'distritos' => array('Chumpi', 'Coracora', 'Coronel Castañeda', 'Pacapausa', 'Pullo', 'Puyusca', 'San Francisco de Ravacayco', 'Upahuacho')),
                array('provincia' => 'Paucar del Sara Sara', 'distritos' => array('Colta', 'Corculla', 'Lampa', 'Marcabamba', 'Oyolo', 'Pararca', 'Pausa', 'San Javier de Alpabamba', 'San Jose de Ushua', 'Sara Sara')),
                array('provincia' => 'Sucre', 'distritos' => array('Belen', 'Chalcos', 'Chilcayoc', 'Huacaña', 'Morcolla', 'Paico', 'Querobamba', 'San Pedro de Larcay', 'San Salvador de Quije', 'Santiago de Paucaray', 'Soras')),
                array('provincia' => 'Victor Fajardo', 'distritos' => array('Alcamenca', 'Apongo', 'Asquipata', 'Canaria', 'Cayara', 'Colca', 'Huamanquiquia', 'Huancapi', 'Huancaraylla', 'Huaya', 'Sarhua', 'Vilcanchos')),
                array('provincia' => 'Vilcas Huaman', 'distritos' => array('Accomarca', 'Carhuanca', 'Concepcion', 'Huambalpa', 'Independencia', 'Saurama', 'Vilcas Huaman', 'Vischongo'))
            ),
        ),

        array(
            'departamento' => 'Cajamarca',
            'provincias' => array(
                array('provincia' => 'Cajabamba', 'distritos' => array('Cachachi', 'Cajabamba', 'Condebamba', 'Sitacocha')),
                array('provincia' => 'Cajamarca', 'distritos' => array('Asuncion', 'Cajamarca', 'Chetilla', 'Cospan', 'Encañada', 'Jesus', 'Llacanora', 'Los Baños del Inca', 'Magdalena', 'Matara', 'Namora', 'San Juan')),
                array('provincia' => 'Celendin', 'distritos' => array('Celendin', 'Chumuch', 'Cortegana', 'Huasmin', 'Jorge Chavez', 'Jose Galvez', 'La Libertad de Pallan', 'Miguel Iglesias', 'Oxamarca', 'Sorochuco', 'Sucre', 'Utco')),
                array('provincia' => 'Chota', 'distritos' => array('Anguia', 'Chadin', 'Chalamarca', 'Chiguirip', 'Chimban', 'Choropampa', 'Chota', 'Cochabamba', 'Conchan', 'Huambos', 'Lajas', 'Llama', 'Miracosta', 'Paccha', 'Pion', 'Querocoto', 'San Juan de Licupis', 'Tacabamba', 'Tocmoche')),
                array('provincia' => 'Contumaza', 'distritos' => array('Chilete', 'Contumaza', 'Cupisnique', 'Guzmango', 'San Benito', 'Santa Cruz de Toledo', 'Tantarica', 'Yonan')),
                array('provincia' => 'Cutervo', 'distritos' => array('Callayuc', 'Choros', 'Cujillo', 'Cutervo', 'La Ramada', 'Pimpingos', 'Querocotillo', 'San Andres de Cutervo', 'San Juan de Cutervo', 'San Luis de Lucma', 'Santa Cruz', 'Santo Domingo de la Capilla', 'Santo Tomas', 'Socota', 'Toribio Casanova')),
                array('provincia' => 'Hualgayoc', 'distritos' => array('Bambamarca', 'Chugur', 'Hualgayoc')),
                array('provincia' => 'Jaen', 'distritos' => array('Bellavista', 'Chontali', 'Colasay', 'Huabal', 'Jaen', 'Las Pirias', 'Pomahuaca', 'Pucara', 'Sallique', 'San Felipe', 'San Jose del Alto', 'Santa Rosa')),
                array('provincia' => 'San Ignacio', 'distritos' => array('Chirinos', 'Huarango', 'La Coipa', 'Namballe', 'San Ignacio', 'San Jose de Lourdes', 'Tabaconas')),
                array('provincia' => 'San Marcos', 'distritos' => array('Chancay', 'Eduardo Villanueva', 'Gregorio Pita', 'Ichocan', 'Jose Manuel Quiroz', 'Jose Sabogal', 'Pedro Galvez')),
                array('provincia' => 'San Miguel', 'distritos' => array('Bolivar', 'Calquis', 'Catilluc', 'El Prado', 'La Florida', 'Llapa', 'Nanchoc', 'Niepos', 'San Gregorio', 'San Miguel', 'San Silvestre de Cochan', 'Tongod', 'Union Agua Blanca')),
                array('provincia' => 'San Pablo', 'distritos' => array('San Bernardino', 'San Luis', 'San Pablo', 'Tumbaden')),
                array('provincia' => 'Santa Cruz', 'distritos' => array('Andabamba', 'Catache', 'Chancaybaños', 'La Esperanza', 'Ninabamba', 'Pulan', 'Santa Cruz', 'Saucepampa', 'Sexi', 'Uticyacu', 'Yauyucan'))
            ),
        ),

        array(
            'departamento' => 'Callao',
            'provincias' => array(
                array('provincia' => 'Callao', 'distritos' => array('Bellavista', 'Callao', 'Carmen de la Legua-reynoso', 'La Perla', 'La Punta', 'Mi Peru', 'Ventanilla'))
            ),
        ),

        array(
            'departamento' => 'Cusco',
            'provincias' => array(
                array('provincia' => 'Acomayo', 'distritos' => array('Acomayo', 'Acopia', 'Acos', 'Mosoc Llacta', 'Pomacanchi', 'Rondocan', 'Sangarara')),
                array('provincia' => 'Anta', 'distritos' => array('Ancahuasi', 'Anta', 'Cachimayo', 'Chinchaypujio', 'Huarocondo', 'Limatambo', 'Mollepata', 'Pucyura', 'Zurite')),
                array('provincia' => 'Calca', 'distritos' => array('Calca', 'Coya', 'Lamay', 'Lares', 'Pisac', 'San Salvador', 'Taray', 'Yanatile')),
                array('provincia' => 'Canas', 'distritos' => array('Checca', 'Kunturkanki', 'Langui', 'Layo', 'Pampamarca', 'Quehue', 'Tupac Amaru', 'Yanaoca')),
                array('provincia' => 'Canchis', 'distritos' => array('Checacupe', 'Combapata', 'Marangani', 'Pitumarca', 'San Pablo', 'San Pedro', 'Sicuani', 'Tinta')),
                array('provincia' => 'Chumbivilcas', 'distritos' => array('Capacmarca', 'Chamaca', 'Colquemarca', 'Livitaca', 'Llusco', 'Quiñota', 'Santo Tomas', 'Velille')),
                array('provincia' => 'Cusco', 'distritos' => array('Ccorca', 'Cusco', 'Poroy', 'San Jeronimo', 'San Sebastian', 'Santiago', 'Saylla', 'Wanchaq')),
                array('provincia' => 'Espinar', 'distritos' => array('Alto Pichigua', 'Condoroma', 'Coporaque', 'Espinar', 'Ocoruro', 'Pallpata', 'Pichigua', 'Suyckutambo')),
                array('provincia' => 'La Convencion', 'distritos' => array('Cielo Punco', 'Echarate', 'Huayopata', 'Inkawasi', 'Kimbiri', 'Kumpirushiato', 'Manitea', 'Maranura', 'Megantoni', 'Ocobamba', 'Pichari', 'Quellouno', 'Santa Ana', 'Santa Teresa', 'Union Ashaninka', 'Vilcabamba', 'Villa Kintiarina', 'Villa Virgen')),
                array('provincia' => 'Paruro', 'distritos' => array('Accha', 'Ccapi', 'Colcha', 'Huanoquite', 'Omacha', 'Paccaritambo', 'Paruro', 'Pillpinto', 'Yaurisque')),
                array('provincia' => 'Paucartambo', 'distritos' => array('Caicay', 'Challabamba', 'Colquepata', 'Huancarani', 'Kosñipata', 'Paucartambo')),
                array('provincia' => 'Quispicanchi', 'distritos' => array('Andahuaylillas', 'Camanti', 'Ccarhuayo', 'Ccatca', 'Cusipata', 'Huaro', 'Lucre', 'Marcapata', 'Ocongate', 'Oropesa', 'Quiquijana', 'Urcos')),
                array('provincia' => 'Urubamba', 'distritos' => array('Chinchero', 'Huayllabamba', 'Machupicchu', 'Maras', 'Ollantaytambo', 'Urubamba', 'Yucay'))
            ),
        ),

        array(
            'departamento' => 'Huancavelica',
            'provincias' => array(
                array('provincia' => 'Acobamba', 'distritos' => array('Acobamba', 'Andabamba', 'Anta', 'Caja', 'Marcas', 'Paucara', 'Pomacocha', 'Rosario')),
                array('provincia' => 'Angaraes', 'distritos' => array('Anchonga', 'Callanmarca', 'Ccochaccasa', 'Chincho', 'Congalla', 'Huanca-huanca', 'Huayllay Grande', 'Julcamarca', 'Lircay', 'San Antonio de Antaparco', 'Santo Tomas de Pata', 'Secclla')),
                array('provincia' => 'Castrovirreyna', 'distritos' => array('Arma', 'Aurahua', 'Capillas', 'Castrovirreyna', 'Chupamarca', 'Cocas', 'Huachos', 'Huamatambo', 'Mollepampa', 'San Juan', 'Santa Ana', 'Tantara', 'Ticrapo')),
                array('provincia' => 'Churcampa', 'distritos' => array('Anco', 'Chinchihuasi', 'Churcampa', 'Cosme', 'El Carmen', 'La Merced', 'Locroja', 'Pachamarca', 'Paucarbamba', 'San Miguel de Mayocc', 'San Pedro de Coris')),
                array('provincia' => 'Huancavelica', 'distritos' => array('Acobambilla', 'Acoria', 'Ascension', 'Conayca', 'Cuenca', 'Huachocolpa', 'Huancavelica', 'Huando', 'Huayllahuara', 'Izcuchaca', 'Laria', 'Manta', 'Mariscal Caceres', 'Moya', 'Nuevo Occoro', 'Palca', 'Pilchaca', 'Vilca', 'Yauli')),
                array('provincia' => 'Huaytara', 'distritos' => array('Ayavi', 'Cordova', 'Huayacundo Arma', 'Huaytara', 'Laramarca', 'Ocoyo', 'Pilpichaca', 'Querco', 'Quito Arma', 'San Antonio de Cusicancha', 'San Francisco de Sangayaico', 'San Isidro', 'Santiago de Chocorvos', 'Santiago de Quirahuara', 'Santo Domingo de Capillas', 'Tambo')),
                array('provincia' => 'Tayacaja', 'distritos' => array('Acostambo', 'Acraquia', 'Ahuaycha', 'Andaymarca', 'Cochabamba', 'Colcabamba', 'Daniel Hernandez', 'Huachocolpa', 'Huaribamba', 'Lambras', 'Ñahuimpuquio', 'Pampas', 'Pazos', 'Pichos', 'Quichuas', 'Quishuar', 'Roble', 'Salcabamba', 'Salcahuasi', 'San Marcos de Rocchac', 'Santiago de Tucuma', 'Surcubamba', 'Tintay Puncu'))
            ),
        ),

        array(
            'departamento' => 'Huanuco',
            'provincias' => array(
                array('provincia' => 'Ambo', 'distritos' => array('Ambo', 'Cayna', 'Colpas', 'Conchamarca', 'Huacar', 'San Francisco', 'San Rafael', 'Tomay Kichwa')),
                array('provincia' => 'Dos de Mayo', 'distritos' => array('Chuquis', 'La Union', 'Marias', 'Pachas', 'Quivilla', 'Ripan', 'Shunqui', 'Sillapata', 'Yanas')),
                array('provincia' => 'Huacaybamba', 'distritos' => array('Canchabamba', 'Cochabamba', 'Huacaybamba', 'Pinra')),
                array('provincia' => 'Huamalies', 'distritos' => array('Arancay', 'Chavin de Pariarca', 'Jacas Grande', 'Jircan', 'Llata', 'Miraflores', 'Monzon', 'Punchao', 'Puños', 'Singa', 'Tantamayo')),
                array('provincia' => 'Huanuco', 'distritos' => array('Amarilis', 'Chinchao', 'Churubamba', 'Huanuco', 'Margos', 'Pillco Marca', 'Quisqui', 'San Francisco de Cayran', 'San Pablo de Pillao', 'San Pedro de Chaulan', 'Santa Maria del Valle', 'Yacus', 'Yarumayo')),
                array('provincia' => 'Lauricocha', 'distritos' => array('Baños', 'Jesus', 'Jivia', 'Queropalca', 'Rondos', 'San Francisco de Asis', 'San Miguel de Cauri')),
                array('provincia' => 'Leoncio Prado', 'distritos' => array('Castillo Grande', 'Daniel Alomia Robles', 'Hermilio Valdizan', 'Jose Crespo y Castillo', 'Luyando', 'Mariano Damaso Beraun', 'Pucayacu', 'Pueblo Nuevo', 'Rupa-rupa', 'Santo Domingo de Anda')),
                array('provincia' => 'Marañon', 'distritos' => array('Cholon', 'Huacrachuco', 'La Morada', 'San Buenaventura', 'Santa Rosa de Alto Yanajanca')),
                array('provincia' => 'Pachitea', 'distritos' => array('Chaglla', 'Molino', 'Panao', 'Umari')),
                array('provincia' => 'Puerto Inca', 'distritos' => array('Codo del Pozuzo', 'Honoria', 'Puerto Inca', 'Tournavista', 'Yuyapichis')),
                array('provincia' => 'Yarowilca', 'distritos' => array('Aparicio Pomares', 'Cahuac', 'Chacabamba', 'Chavinillo', 'Choras', 'Jacas Chico', 'Obas', 'Pampamarca'))
            ),
        ),

        array(
            'departamento' => 'Ica',
            'provincias' => array(
                array('provincia' => 'Chincha', 'distritos' => array('Alto Laran', 'Chavin', 'Chincha Alta', 'Chincha Baja', 'El Carmen', 'Grocio Prado', 'Pueblo Nuevo', 'San Juan de Yanac', 'San Pedro de Huacarpana', 'Sunampe', 'Tambo de Mora')),
                array('provincia' => 'Ica', 'distritos' => array('Ica', 'La Tinguiña', 'Los Aquijes', 'Ocucaje', 'Pachacutec', 'Parcona', 'Pueblo Nuevo', 'Salas', 'San Jose de los Molinos', 'San Juan Bautista', 'Santiago', 'Subtanjalla', 'Tate', 'Yauca del Rosario')),
                array('provincia' => 'Nazca', 'distritos' => array('Changuillo', 'El Ingenio', 'Marcona', 'Nazca', 'Vista Alegre')),
                array('provincia' => 'Palpa', 'distritos' => array('Llipata', 'Palpa', 'Rio Grande', 'Santa Cruz', 'Tibillo')),
                array('provincia' => 'Pisco', 'distritos' => array('Huancano', 'Humay', 'Independencia', 'Paracas', 'Pisco', 'San Andres', 'San Clemente', 'Tupac Amaru Inca'))
            ),
        ),

        array(
            'departamento' => 'Junin',
            'provincias' => array(
                array('provincia' => 'Chanchamayo', 'distritos' => array('Chanchamayo', 'Perene', 'Pichanaqui', 'San Luis de Shuaro', 'San Ramon', 'Vitoc')),
                array('provincia' => 'Chupaca', 'distritos' => array('Ahuac', 'Chongos Bajo', 'Chupaca', 'Huachac', 'Huamancaca Chico', 'San Juan de Jarpa', 'San Juan de Yscos', 'Tres de Diciembre', 'Yanacancha')),
                array('provincia' => 'Concepcion', 'distritos' => array('Aco', 'Andamarca', 'Chambara', 'Cochas', 'Comas', 'Concepcion', 'Heroinas Toledo', 'Manzanares', 'Mariscal Castilla', 'Matahuasi', 'Mito', 'Nueve de Julio', 'Orcotuna', 'San Jose de Quero', 'Santa Rosa de Ocopa')),
                array('provincia' => 'Huancayo', 'distritos' => array('Carhuacallanga', 'Chacapampa', 'Chicche', 'Chilca', 'Chongos Alto', 'Chupuro', 'Colca', 'Cullhuas', 'El Tambo', 'Huacrapuquio', 'Hualhuas', 'Huancan', 'Huancayo', 'Huasicancha', 'Huayucachi', 'Ingenio', 'Pariahuanca', 'Pilcomayo', 'Pucara', 'Quichuay', 'Quilcas', 'San Agustin', 'San Jeronimo de Tunan', 'Santo Domingo de Acobamba', 'Saño', 'Sapallanga', 'Sicaya', 'Viques')),
                array('provincia' => 'Jauja', 'distritos' => array('Acolla', 'Apata', 'Ataura', 'Canchayllo', 'Curicaca', 'El Mantaro', 'Huamali', 'Huaripampa', 'Huertas', 'Janjaillo', 'Jauja', 'Julcan', 'Leonor Ordoñez', 'Llocllapampa', 'Marco', 'Masma', 'Masma Chicche', 'Molinos', 'Monobamba', 'Muqui', 'Muquiyauyo', 'Paca', 'Paccha', 'Pancan', 'Parco', 'Pomacancha', 'Ricran', 'San Lorenzo', 'San Pedro de Chunan', 'Sausa', 'Sincos', 'Tunan Marca', 'Yauli', 'Yauyos')),
                array('provincia' => 'Junin', 'distritos' => array('Carhuamayo', 'Junin', 'Ondores', 'Ulcumayo')),
                array('provincia' => 'Satipo', 'distritos' => array('Coviriali', 'Llaylla', 'Mazamari', 'Pampa Hermosa', 'Pangoa', 'Rio Negro', 'Rio Tambo', 'Satipo', 'Vizcatan del Ene')),
                array('provincia' => 'Tarma', 'distritos' => array('Acobamba', 'Huaricolca', 'Huasahuasi', 'La Union', 'Palca', 'Palcamayo', 'San Pedro de Cajas', 'Tapo', 'Tarma')),
                array('provincia' => 'Yauli', 'distritos' => array('Chacapalpa', 'Huay-huay', 'La Oroya', 'Marcapomacocha', 'Morococha', 'Paccha', 'Santa Barbara de Carhuacayan', 'Santa Rosa de Sacco', 'Suitucancha', 'Yauli'))
            ),
        ),

        array(
            'departamento' => 'La Libertad',
            'provincias' => array(
                array('provincia' => 'Ascope', 'distritos' => array('Ascope', 'Casa Grande', 'Chicama', 'Chocope', 'Magdalena de Cao', 'Paijan', 'Razuri', 'Santiago de Cao')),
                array('provincia' => 'Bolivar', 'distritos' => array('Bambamarca', 'Bolivar', 'Condormarca', 'Longotea', 'Uchumarca', 'Ucuncha')),
                array('provincia' => 'Chepen', 'distritos' => array('Chepen', 'Pacanga', 'Pueblo Nuevo')),
                array('provincia' => 'Gran Chimu', 'distritos' => array('Cascas', 'Lucma', 'Marmot', 'Sayapullo')),
                array('provincia' => 'Julcan', 'distritos' => array('Calamarca', 'Carabamba', 'Huaso', 'Julcan')),
                array('provincia' => 'Otuzco', 'distritos' => array('Agallpampa', 'Charat', 'Huaranchal', 'La Cuesta', 'Mache', 'Otuzco', 'Paranday', 'Salpo', 'Sinsicap', 'Usquil')),
                array('provincia' => 'Pacasmayo', 'distritos' => array('Guadalupe', 'Jequetepeque', 'Pacasmayo', 'San Jose', 'San Pedro de Lloc')),
                array('provincia' => 'Pataz', 'distritos' => array('Buldibuyo', 'Chillia', 'Huancaspata', 'Huaylillas', 'Huayo', 'Ongon', 'Parcoy', 'Pataz', 'Pias', 'Santiago de Challas', 'Taurija', 'Tayabamba', 'Urpay')),
                array('provincia' => 'Sanchez Carrion', 'distritos' => array('Chugay', 'Cochorco', 'Curgos', 'Huamachuco', 'Marcabal', 'Sanagoran', 'Sarin', 'Sartimbamba')),
                array('provincia' => 'Santiago de Chuco', 'distritos' => array('Angasmarca', 'Cachicadan', 'Mollebamba', 'Mollepata', 'Quiruvilca', 'Santa Cruz de Chuca', 'Santiago de Chuco', 'Sitabamba')),
                array('provincia' => 'Trujillo', 'distritos' => array('Alto Trujillo', 'El Porvenir', 'Florencia de Mora', 'Huanchaco', 'La Esperanza', 'Laredo', 'Moche', 'Poroto', 'Salaverry', 'Simbal', 'Trujillo', 'Victor Larco Herrera')),
                array('provincia' => 'Viru', 'distritos' => array('Chao', 'Guadalupito', 'Viru'))
            ),
        ),

        array(
            'departamento' => 'Lambayeque',
            'provincias' => array(
                array('provincia' => 'Chiclayo', 'distritos' => array('Cayalti', 'Chiclayo', 'Chongoyape', 'Eten', 'Eten Puerto', 'Jose Leonardo Ortiz', 'La Victoria', 'Lagunas', 'Monsefu', 'Nueva Arica', 'Oyotun', 'Patapo', 'Picsi', 'Pimentel', 'Pomalca', 'Pucala', 'Reque', 'Santa Rosa', 'Saña', 'Tuman')),
                array('provincia' => 'Ferreñafe', 'distritos' => array('Cañaris', 'Ferreñafe', 'Incahuasi', 'Manuel Antonio Mesones Muro', 'Pitipo', 'Pueblo Nuevo')),
                array('provincia' => 'Lambayeque', 'distritos' => array('Chochope', 'Illimo', 'Jayanca', 'Lambayeque', 'Mochumi', 'Morrope', 'Motupe', 'Olmos', 'Pacora', 'Salas', 'San Jose', 'Tucume'))
            ),
        ),

        array(
            'departamento' => 'Lima',
            'provincias' => array(
                array('provincia' => 'Barranca', 'distritos' => array('Barranca', 'Paramonga', 'Pativilca', 'Supe', 'Supe Puerto')),
                array('provincia' => 'Cajatambo', 'distritos' => array('Cajatambo', 'Copa', 'Gorgor', 'Huancapon', 'Manas')),
                array('provincia' => 'Canta', 'distritos' => array('Arahuay', 'Canta', 'Huamantanga', 'Huaros', 'Lachaqui', 'San Buenaventura', 'Santa Rosa de Quives')),
                array('provincia' => 'Cañete', 'distritos' => array('Asia', 'Calango', 'Cerro Azul', 'Chilca', 'Coayllo', 'Imperial', 'Lunahuana', 'Mala', 'Nuevo Imperial', 'Pacaran', 'Quilmana', 'San Antonio', 'San Luis', 'San Vicente de Cañete', 'Santa Cruz de Flores', 'Zuñiga')),
                array('provincia' => 'Huaral', 'distritos' => array('Atavillos Alto', 'Atavillos Bajo', 'Aucallama', 'Chancay', 'Huaral', 'Ihuari', 'Lampian', 'Pacaraos', 'San Miguel de Acos', 'Santa Cruz de Andamarca', 'Sumbilca', 'Veintisiete de Noviembre')),
                array('provincia' => 'Huarochiri', 'distritos' => array('Antioquia', 'Callahuanca', 'Carampoma', 'Chicla', 'Cuenca', 'Huachupampa', 'Huanza', 'Huarochiri', 'Lahuaytambo', 'Langa', 'Mariatana', 'Matucana', 'Ricardo Palma', 'San Andres de Tupicocha', 'San Antonio', 'San Bartolome', 'San Damian', 'San Juan de Iris', 'San Juan de Tantaranche', 'San Lorenzo de Quinti', 'San Mateo', 'San Mateo de Otao', 'San Pedro de Casta', 'San Pedro de Huancayre', 'San Pedro Laraos', 'Sangallaya', 'Santa Cruz de Cocachacra', 'Santa Eulalia', 'Santiago de Anchucaya', 'Santiago de Tuna', 'Santo Domingo de los Olleros', 'Surco')),
                array('provincia' => 'Huaura', 'distritos' => array('Ambar', 'Caleta de Carquin', 'Checras', 'Huacho', 'Hualmay', 'Huaura', 'Leoncio Prado', 'Paccho', 'Santa Leonor', 'Santa Maria', 'Sayan', 'Vegueta')),
                array('provincia' => 'Lima', 'distritos' => array('Ancon', 'Ate', 'Barranco', 'Breña', 'Carabayllo', 'Chaclacayo', 'Chorrillos', 'Cieneguilla', 'Comas', 'El Agustino', 'Independencia', 'Jesus Maria', 'La Molina', 'La Victoria', 'Lima', 'Lince', 'Los Olivos', 'Lurigancho', 'Lurin', 'Magdalena del Mar', 'Miraflores', 'Pachacamac', 'Pucusana', 'Pueblo Libre', 'Puente Piedra', 'Punta Hermosa', 'Punta Negra', 'Rimac', 'San Bartolo', 'San Borja', 'San Isidro', 'San Juan de Lurigancho', 'San Juan de Miraflores', 'San Luis', 'San Martin de Porres', 'San Miguel', 'Santa Anita', 'Santa Maria del Mar', 'Santa Rosa', 'Santiago de Surco', 'Surquillo', 'Villa el Salvador', 'Villa Maria del Triunfo')),
                array('provincia' => 'Oyon', 'distritos' => array('Andajes', 'Caujul', 'Cochamarca', 'Navan', 'Oyon', 'Pachangara')),
                array('provincia' => 'Yauyos', 'distritos' => array('Alis', 'Ayauca', 'Ayaviri', 'Azangaro', 'Cacra', 'Carania', 'Catahuasi', 'Chocos', 'Cochas', 'Colonia', 'Hongos', 'Huampara', 'Huancaya', 'Huangascar', 'Huantan', 'Huañec', 'Laraos', 'Lincha', 'Madean', 'Miraflores', 'Omas', 'Putinza', 'Quinches', 'Quinocay', 'San Joaquin', 'San Pedro de Pilas', 'Tanta', 'Tauripampa', 'Tomas', 'Tupe', 'Viñac', 'Vitis', 'Yauyos'))
            ),
        ),

        array(
            'departamento' => 'Loreto',
            'provincias' => array(
                array('provincia' => 'Alto Amazonas', 'distritos' => array('Balsapuerto', 'Jeberos', 'Lagunas', 'Santa Cruz', 'Teniente Cesar Lopez Rojas', 'Yurimaguas')),
                array('provincia' => 'Datem del Marañon', 'distritos' => array('Andoas', 'Barranca', 'Cahuapanas', 'Manseriche', 'Morona', 'Pastaza')),
                array('provincia' => 'Loreto', 'distritos' => array('Nauta', 'Parinari', 'Tigre', 'Trompeteros', 'Urarinas')),
                array('provincia' => 'Mariscal Ramon Castilla', 'distritos' => array('Pebas', 'Ramon Castilla', 'San Pablo', 'Santa Rosa de Loreto', 'Yavari')),
                array('provincia' => 'Maynas', 'distritos' => array('Alto Nanay', 'Belen', 'Fernando Lores', 'Indiana', 'Iquitos', 'Las Amazonas', 'Mazan', 'Napo', 'Punchana', 'San Juan Bautista', 'Torres Causana')),
                array('provincia' => 'Putumayo', 'distritos' => array('Putumayo', 'Rosa Panduro', 'Teniente Manuel Clavero', 'Yaguas')),
                array('provincia' => 'Requena', 'distritos' => array('Alto Tapiche', 'Capelo', 'Emilio San Martin', 'Jenaro Herrera', 'Maquia', 'Puinahua', 'Requena', 'Saquena', 'Soplin', 'Tapiche', 'Yaquerana')),
                array('provincia' => 'Ucayali', 'distritos' => array('Contamana', 'Inahuaya', 'Padre Marquez', 'Pampa Hermosa', 'Sarayacu', 'Vargas Guerra'))
            ),
        ),

        array(
            'departamento' => 'Madre de Dios',
            'provincias' => array(
                array('provincia' => 'Manu', 'distritos' => array('Fitzcarrald', 'Huepetuhe', 'Madre de Dios', 'Manu')),
                array('provincia' => 'Tahuamanu', 'distritos' => array('Iberia', 'Iñapari', 'Tahuamanu')),
                array('provincia' => 'Tambopata', 'distritos' => array('Inambari', 'Laberinto', 'Las Piedras', 'Tambopata'))
            ),
        ),

        array(
            'departamento' => 'Moquegua',
            'provincias' => array(
                array('provincia' => 'General Sanchez Cerro', 'distritos' => array('Chojata', 'Coalaque', 'Ichuña', 'La Capilla', 'Lloque', 'Matalaque', 'Omate', 'Puquina', 'Quinistaquillas', 'Ubinas', 'Yunga')),
                array('provincia' => 'Ilo', 'distritos' => array('El Algarrobal', 'Ilo', 'Pacocha')),
                array('provincia' => 'Mariscal Nieto', 'distritos' => array('Carumas', 'Cuchumbaya', 'Moquegua', 'Samegua', 'San Antonio', 'San Cristobal', 'Torata'))
            ),
        ),

        array(
            'departamento' => 'Pasco',
            'provincias' => array(
                array('provincia' => 'Daniel Alcides Carrion', 'distritos' => array('Chacayan', 'Goyllarisquizga', 'Paucar', 'San Pedro de Pillao', 'Santa Ana de Tusi', 'Tapuc', 'Vilcabamba', 'Yanahuanca')),
                array('provincia' => 'Oxapampa', 'distritos' => array('Chontabamba', 'Constitucion', 'Huancabamba', 'Oxapampa', 'Palcazu', 'Pozuzo', 'Puerto Bermudez', 'Villa Rica')),
                array('provincia' => 'Pasco', 'distritos' => array('Chaupimarca', 'Huachon', 'Huariaca', 'Huayllay', 'Ninacaca', 'Pallanchacra', 'Paucartambo', 'San Francisco de Asis de Yarusyacan', 'Simon Bolivar', 'Ticlacayan', 'Tinyahuarco', 'Vicco', 'Yanacancha'))
            ),
        ),

        array(
            'departamento' => 'Piura',
            'provincias' => array(
                array('provincia' => 'Ayabaca', 'distritos' => array('Ayabaca', 'Frias', 'Jilili', 'Lagunas', 'Montero', 'Pacaipampa', 'Paimas', 'Sapillica', 'Sicchez', 'Suyo')),
                array('provincia' => 'Huancabamba', 'distritos' => array('Canchaque', 'El Carmen de la Frontera', 'Huancabamba', 'Huarmaca', 'Lalaquiz', 'San Miguel de el Faique', 'Sondor', 'Sondorillo')),
                array('provincia' => 'Morropon', 'distritos' => array('Buenos Aires', 'Chalaco', 'Chulucanas', 'La Matanza', 'Morropon', 'Salitral', 'San Juan de Bigote', 'Santa Catalina de Mossa', 'Santo Domingo', 'Yamango')),
                array('provincia' => 'Paita', 'distritos' => array('Amotape', 'Arenal', 'Colan', 'La Huaca', 'Paita', 'Tamarindo', 'Vichayal')),
                array('provincia' => 'Piura', 'distritos' => array('Castilla', 'Catacaos', 'Cura Mori', 'El Tallan', 'La Arena', 'La Union', 'Las Lomas', 'Piura', 'Tambo Grande', 'Veintiseis de Octubre')),
                array('provincia' => 'Sechura', 'distritos' => array('Bellavista de la Union', 'Bernal', 'Cristo Nos Valga', 'Rinconada-llicuar', 'Sechura', 'Vice')),
                array('provincia' => 'Sullana', 'distritos' => array('Bellavista', 'Ignacio Escudero', 'Lancones', 'Marcavelica', 'Miguel Checa', 'Querecotillo', 'Salitral', 'Sullana')),
                array('provincia' => 'Talara', 'distritos' => array('El Alto', 'La Brea', 'Lobitos', 'Los Organos', 'Mancora', 'Pariñas'))
            ),
        ),

        array(
            'departamento' => 'Puno',
            'provincias' => array(
                array('provincia' => 'Azangaro', 'distritos' => array('Achaya', 'Arapa', 'Asillo', 'Azangaro', 'Caminaca', 'Chupa', 'Jose Domingo Choquehuanca', 'Muñani', 'Potoni', 'Saman', 'San Anton', 'San Jose', 'San Juan de Salinas', 'Santiago de Pupuja', 'Tirapata')),
                array('provincia' => 'Carabaya', 'distritos' => array('Ajoyani', 'Ayapata', 'Coasa', 'Corani', 'Crucero', 'Ituata', 'Macusani', 'Ollachea', 'San Gaban', 'Usicayos')),
                array('provincia' => 'Chucuito', 'distritos' => array('Desaguadero', 'Huacullani', 'Juli', 'Kelluyo', 'Pisacoma', 'Pomata', 'Zepita')),
                array('provincia' => 'El Collao', 'distritos' => array('Capazo', 'Conduriri', 'Ilave', 'Pilcuyo', 'Santa Rosa')),
                array('provincia' => 'Huancane', 'distritos' => array('Cojata', 'Huancane', 'Huatasani', 'Inchupalla', 'Pusi', 'Rosaspata', 'Taraco', 'Vilque Chico')),
                array('provincia' => 'Lampa', 'distritos' => array('Cabanilla', 'Calapuja', 'Lampa', 'Nicasio', 'Ocuviri', 'Palca', 'Paratia', 'Pucara', 'Santa Lucia', 'Vilavila')),
                array('provincia' => 'Melgar', 'distritos' => array('Antauta', 'Ayaviri', 'Cupi', 'Llalli', 'Macari', 'Nuñoa', 'Orurillo', 'Santa Rosa', 'Umachiri')),
                array('provincia' => 'Moho', 'distritos' => array('Conima', 'Huayrapata', 'Moho', 'Tilali')),
                array('provincia' => 'Puno', 'distritos' => array('Acora', 'Amantani', 'Atuncolla', 'Capachica', 'Chucuito', 'Coata', 'Huata', 'Mañazo', 'Paucarcolla', 'Pichacani', 'Plateria', 'Puno', 'San Antonio', 'Tiquillaca', 'Vilque')),
                array('provincia' => 'San Antonio de Putina', 'distritos' => array('Ananea', 'Pedro Vilca Apaza', 'Putina', 'Quilcapuncu', 'Sina')),
                array('provincia' => 'San Roman', 'distritos' => array('Cabana', 'Cabanillas', 'Caracoto', 'Juliaca', 'San Miguel')),
                array('provincia' => 'Sandia', 'distritos' => array('Alto Inambari', 'Cuyocuyo', 'Limbani', 'Patambuco', 'Phara', 'Quiaca', 'San Juan del Oro', 'San Pedro de Putina Punco', 'Sandia', 'Yanahuaya')),
                array('provincia' => 'Yunguyo', 'distritos' => array('Anapia', 'Copani', 'Cuturapi', 'Ollaraya', 'Tinicachi', 'Unicachi', 'Yunguyo'))
            ),
        ),

        array(
            'departamento' => 'San Martin',
            'provincias' => array(
                array('provincia' => 'Bellavista', 'distritos' => array('Alto Biavo', 'Bajo Biavo', 'Bellavista', 'Huallaga', 'San Pablo', 'San Rafael')),
                array('provincia' => 'El Dorado', 'distritos' => array('Agua Blanca', 'San Jose de Sisa', 'San Martin', 'Santa Rosa', 'Shatoja')),
                array('provincia' => 'Huallaga', 'distritos' => array('Alto Saposoa', 'El Eslabon', 'Piscoyacu', 'Sacanche', 'Saposoa', 'Tingo de Saposoa')),
                array('provincia' => 'Lamas', 'distritos' => array('Alonso de Alvarado', 'Barranquita', 'Caynarachi', 'Cuñumbuqui', 'Lamas', 'Pinto Recodo', 'Rumisapa', 'San Roque de Cumbaza', 'Shanao', 'Tabalosos', 'Zapatero')),
                array('provincia' => 'Mariscal Caceres', 'distritos' => array('Campanilla', 'Huicungo', 'Juanjui', 'Pachiza', 'Pajarillo')),
                array('provincia' => 'Moyobamba', 'distritos' => array('Calzada', 'Habana', 'Jepelacio', 'Moyobamba', 'Soritor', 'Yantalo')),
                array('provincia' => 'Picota', 'distritos' => array('Buenos Aires', 'Caspizapa', 'Picota', 'Pilluana', 'Pucacaca', 'San Cristobal', 'San Hilarion', 'Shamboyacu', 'Tingo de Ponasa', 'Tres Unidos')),
                array('provincia' => 'Rioja', 'distritos' => array('Awajun', 'Elias Soplin Vargas', 'Nueva Cajamarca', 'Pardo Miguel', 'Posic', 'Rioja', 'San Fernando', 'Yorongos', 'Yuracyacu')),
                array('provincia' => 'San Martin', 'distritos' => array('Alberto Leveau', 'Cacatachi', 'Chazuta', 'Chipurana', 'El Porvenir', 'Huimbayoc', 'Juan Guerra', 'La Banda de Shilcayo', 'Morales', 'Papaplaya', 'San Antonio', 'Sauce', 'Shapaja', 'Tarapoto')),
                array('provincia' => 'Tocache', 'distritos' => array('Nuevo Progreso', 'Polvora', 'Santa Lucia', 'Shunte', 'Tocache', 'Uchiza'))
            ),
        ),

        array(
            'departamento' => 'Tacna',
            'provincias' => array(
                array('provincia' => 'Candarave', 'distritos' => array('Cairani', 'Camilaca', 'Candarave', 'Curibaya', 'Huanuara', 'Quilahuani')),
                array('provincia' => 'Jorge Basadre', 'distritos' => array('Ilabaya', 'Ite', 'Locumba')),
                array('provincia' => 'Tacna', 'distritos' => array('Alto de la Alianza', 'Calana', 'Ciudad Nueva', 'Coronel Gregorio Albarracin Lanchipa', 'Inclan', 'La Yarada los Palos', 'Pachia', 'Palca', 'Pocollay', 'Sama', 'Tacna')),
                array('provincia' => 'Tarata', 'distritos' => array('Estique', 'Estique Pampa', 'Heroes Albarracin', 'Sitajara', 'Susapaya', 'Tarata', 'Tarucachi', 'Ticaco'))
            ),
        ),

        array(
            'departamento' => 'Tumbes',
            'provincias' => array(
                array('provincia' => 'Contralmirante Villar', 'distritos' => array('Canoas de Punta Sal', 'Casitas', 'Zorritos')),
                array('provincia' => 'Tumbes', 'distritos' => array('Corrales', 'La Cruz', 'Pampas de Hospital', 'San Jacinto', 'San Juan de la Virgen', 'Tumbes')),
                array('provincia' => 'Zarumilla', 'distritos' => array('Aguas Verdes', 'Matapalo', 'Papayal', 'Zarumilla'))
            ),
        ),

        array(
            'departamento' => 'Ucayali',
            'provincias' => array(
                array('provincia' => 'Atalaya', 'distritos' => array('Raimondi', 'Sepahua', 'Tahuania', 'Yurua')),
                array('provincia' => 'Coronel Portillo', 'distritos' => array('Calleria', 'Campoverde', 'Iparia', 'Manantay', 'Masisea', 'Nueva Requena', 'Yarinacocha')),
                array('provincia' => 'Padre Abad', 'distritos' => array('Alexander Von Humboldt', 'Boqueron', 'Curimana', 'Huipoca', 'Irazola', 'Neshuya', 'Padre Abad')),
                array('provincia' => 'Purus', 'distritos' => array('Purus'))
            ),
        )
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

/**
 * =============================================================================
 * FUNCIONES PARA SINCRONIZACIÓN CON TAXONOMÍA JERÁRQUICA
 * =============================================================================
 */

/**
 * Obtiene o crea el término más específico en la taxonomía jerárquica
 * 
 * Prioridad: Distrito > Provincia > Departamento
 * 
 * @param string $departamento Nombre del departamento
 * @param string $provincia Nombre de la provincia (opcional)
 * @param string $distrito Nombre del distrito (opcional)
 * @return int|WP_Error Term ID del término más específico, o error
 */
function agrochamba_get_or_create_location_term($departamento, $provincia = '', $distrito = '') {
    if (empty($departamento)) {
        return new WP_Error('invalid_departamento', 'El departamento es obligatorio');
    }
    
    // Normalizar nombres
    $departamento = trim($departamento);
    $provincia = trim($provincia);
    $distrito = trim($distrito);
    
    // 1. Asegurar que existe el departamento (nivel 0)
    $dept_term = get_term_by('name', $departamento, 'ubicacion');
    if (!$dept_term) {
        $dept_result = wp_insert_term($departamento, 'ubicacion', array(
            'slug' => sanitize_title($departamento),
            'parent' => 0
        ));
        if (is_wp_error($dept_result)) {
            return $dept_result;
        }
        $dept_term_id = $dept_result['term_id'];
    } else {
        $dept_term_id = $dept_term->term_id;
    }
    
    // Si solo hay departamento, retornar
    if (empty($provincia)) {
        return $dept_term_id;
    }
    
    // 2. Asegurar que existe la provincia (hija del departamento)
    // Buscar términos hijos del departamento con este nombre
    $prov_terms = get_terms(array(
        'taxonomy' => 'ubicacion',
        'name' => $provincia,
        'parent' => $dept_term_id,
        'hide_empty' => false
    ));
    
    if (!is_wp_error($prov_terms) && !empty($prov_terms)) {
        $prov_term_id = $prov_terms[0]->term_id;
    } else {
        // Crear nueva provincia
        $prov_slug = sanitize_title($provincia) . '-' . $dept_term_id;
        $prov_result = wp_insert_term($provincia, 'ubicacion', array(
            'slug' => $prov_slug,
            'parent' => $dept_term_id
        ));
        if (is_wp_error($prov_result)) {
            // Si el error es porque el término ya existe con otro slug, buscarlo
            if ($prov_result->get_error_code() === 'term_exists') {
                $prov_terms = get_terms(array(
                    'taxonomy' => 'ubicacion',
                    'name' => $provincia,
                    'parent' => $dept_term_id,
                    'hide_empty' => false
                ));
                if (!is_wp_error($prov_terms) && !empty($prov_terms)) {
                    $prov_term_id = $prov_terms[0]->term_id;
                } else {
                    return $prov_result;
                }
            } else {
                return $prov_result;
            }
        } else {
            $prov_term_id = $prov_result['term_id'];
        }
    }
    
    // Si solo hay provincia, retornar
    if (empty($distrito)) {
        return $prov_term_id;
    }
    
    // 3. Asegurar que existe el distrito (hijo de la provincia)
    // Buscar términos hijos de la provincia con este nombre
    $dist_terms = get_terms(array(
        'taxonomy' => 'ubicacion',
        'name' => $distrito,
        'parent' => $prov_term_id,
        'hide_empty' => false
    ));
    
    if (!is_wp_error($dist_terms) && !empty($dist_terms)) {
        return $dist_terms[0]->term_id;
    } else {
        // Crear nuevo distrito
        $dist_slug = sanitize_title($distrito) . '-' . $prov_term_id;
        $dist_result = wp_insert_term($distrito, 'ubicacion', array(
            'slug' => $dist_slug,
            'parent' => $prov_term_id
        ));
        if (is_wp_error($dist_result)) {
            // Si el error es porque el término ya existe con otro slug, buscarlo
            if ($dist_result->get_error_code() === 'term_exists') {
                $dist_terms = get_terms(array(
                    'taxonomy' => 'ubicacion',
                    'name' => $distrito,
                    'parent' => $prov_term_id,
                    'hide_empty' => false
                ));
                if (!is_wp_error($dist_terms) && !empty($dist_terms)) {
                    return $dist_terms[0]->term_id;
                } else {
                    return $dist_result;
                }
            } else {
                return $dist_result;
            }
        } else {
            return $dist_result['term_id'];
        }
    }
}

/**
 * =============================================================================
 * FUNCIÓN PARA POBLAR TODA LA TAXONOMÍA DE UNA VEZ
 * =============================================================================
 */

/**
 * Crea todos los términos de ubicación del Perú en la taxonomía jerárquica
 * 
 * Esta función debe ejecutarse UNA SOLA VEZ para poblar la taxonomía completa.
 * Crea aproximadamente:
 * - 25 departamentos (parent = 0)
 * - 196 provincias (parent = ID del departamento)
 * - 1,892 distritos (parent = ID de la provincia)
 * Total: ~2,113 términos
 * 
 * Puede ejecutarse desde:
 * - Admin de WordPress (agregar a un plugin de admin)
 * - WP-CLI: wp eval-file populate-locations.php
 * - Hook de activación del plugin
 * 
 * @return array Estadísticas de creación con conteos exactos
 */
function agrochamba_populate_all_location_terms() {
    $locations = agrochamba_get_peru_locations();
    $stats = array(
        'departamentos' => 0,
        'provincias' => 0,
        'distritos' => 0,
        'errores' => array()
    );
    
    foreach ($locations as $dept_data) {
        $departamento = $dept_data['departamento'];
        
        // 1. Crear departamento (parent = 0)
        $dept_term = get_term_by('name', $departamento, 'ubicacion');
        if (!$dept_term || $dept_term->parent != 0) {
            // Si existe pero tiene parent, buscar o crear uno nuevo
            $dept_terms = get_terms(array(
                'taxonomy' => 'ubicacion',
                'name' => $departamento,
                'parent' => 0,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($dept_terms) && !empty($dept_terms)) {
                $dept_term_id = $dept_terms[0]->term_id;
            } else {
                $dept_result = wp_insert_term($departamento, 'ubicacion', array(
                    'slug' => sanitize_title($departamento),
                    'parent' => 0
                ));
                if (is_wp_error($dept_result)) {
                    $stats['errores'][] = "Error creando departamento $departamento: " . $dept_result->get_error_message();
                    continue;
                }
                $dept_term_id = $dept_result['term_id'];
            }
        } else {
            $dept_term_id = $dept_term->term_id;
        }
        $stats['departamentos']++;
        
        // 2. Crear provincias
        foreach ($dept_data['provincias'] as $prov_data) {
            $provincia = $prov_data['provincia'];
            
            $prov_terms = get_terms(array(
                'taxonomy' => 'ubicacion',
                'name' => $provincia,
                'parent' => $dept_term_id,
                'hide_empty' => false
            ));
            
            if (!is_wp_error($prov_terms) && !empty($prov_terms)) {
                $prov_term_id = $prov_terms[0]->term_id;
            } else {
                $prov_slug = sanitize_title($provincia) . '-' . $dept_term_id;
                $prov_result = wp_insert_term($provincia, 'ubicacion', array(
                    'slug' => $prov_slug,
                    'parent' => $dept_term_id
                ));
                if (is_wp_error($prov_result)) {
                    if ($prov_result->get_error_code() !== 'term_exists') {
                        $stats['errores'][] = "Error creando provincia $provincia en $departamento: " . $prov_result->get_error_message();
                        continue;
                    }
                    // Si ya existe, buscarlo
                    $prov_terms = get_terms(array(
                        'taxonomy' => 'ubicacion',
                        'name' => $provincia,
                        'parent' => $dept_term_id,
                        'hide_empty' => false
                    ));
                    if (!is_wp_error($prov_terms) && !empty($prov_terms)) {
                        $prov_term_id = $prov_terms[0]->term_id;
                    } else {
                        continue;
                    }
                } else {
                    $prov_term_id = $prov_result['term_id'];
                }
            }
            $stats['provincias']++;
            
            // 3. Crear distritos
            foreach ($prov_data['distritos'] as $distrito) {
                $dist_terms = get_terms(array(
                    'taxonomy' => 'ubicacion',
                    'name' => $distrito,
                    'parent' => $prov_term_id,
                    'hide_empty' => false
                ));
                
                if (!is_wp_error($dist_terms) && !empty($dist_terms)) {
                    // Ya existe, pero lo contamos igual
                    $stats['distritos']++;
                    continue;
                }
                
                $dist_slug = sanitize_title($distrito) . '-' . $prov_term_id;
                $dist_result = wp_insert_term($distrito, 'ubicacion', array(
                    'slug' => $dist_slug,
                    'parent' => $prov_term_id
                ));
                if (is_wp_error($dist_result)) {
                    if ($dist_result->get_error_code() !== 'term_exists') {
                        $stats['errores'][] = "Error creando distrito $distrito en $provincia, $departamento: " . $dist_result->get_error_message();
                    } else {
                        // Si existe pero no lo encontramos antes, contarlo
                        $stats['distritos']++;
                    }
                    continue;
                }
                $stats['distritos']++;
            }
        }
    }
    
    return $stats;
}
