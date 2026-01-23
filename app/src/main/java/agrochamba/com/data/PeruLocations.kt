package agrochamba.com.data

import java.text.Normalizer
import java.util.UUID

/**
 * =============================================================================
 * PERU LOCATIONS - Fuente única de verdad para ubicaciones del Perú
 * =============================================================================
 * 
 * Este archivo contiene:
 * - Datos completos de departamentos, provincias y distritos del Perú
 * - Funciones de búsqueda inteligente con fuzzy matching
 * - Funciones de resolución (reverse lookup)
 * - Funciones de validación y normalización
 * - Modelos de datos para ubicaciones y sedes
 */

// =============================================================================
// MODELOS DE DATOS
// =============================================================================

/**
 * Tipo de ubicación en la jerarquía
 */
enum class LocationType {
    DEPARTAMENTO,
    PROVINCIA,
    DISTRITO
}

/**
 * Ubicación completa con los 3 niveles jerárquicos
 * 
 * @param nivel Indica el nivel de especificidad que el usuario seleccionó:
 *              - DEPARTAMENTO: Solo se especificó el departamento
 *              - PROVINCIA: Se especificó departamento y provincia  
 *              - DISTRITO: Se especificó la ubicación completa (departamento, provincia, distrito)
 */
data class UbicacionCompleta(
    val departamento: String,
    val provincia: String,
    val distrito: String,
    val direccion: String? = null,
    val coordenadas: Coordenadas? = null,
    // Campos alternativos para compatibilidad con backend que guarda lat/lng directamente
    val lat: Double? = null,
    val lng: Double? = null,
    // Nivel de especificidad seleccionado por el usuario
    val nivel: LocationType = LocationType.DISTRITO
) {
    /**
     * Obtiene las coordenadas, ya sea del objeto coordenadas o de lat/lng directos
     */
    fun obtenerCoordenadas(): Coordenadas? {
        return coordenadas ?: if (lat != null && lng != null && lat != 0.0 && lng != 0.0) {
            Coordenadas(lat, lng)
        } else null
    }
    
    /**
     * Formato para mostrar en cards (solo departamento)
     */
    fun formatForCard(): String = departamento
    
    /**
     * Formato para selector de sedes
     */
    fun formatForSedeSelector(): String {
        val nivelEfectivo = getNivelEfectivo()
        return when (nivelEfectivo) {
            LocationType.DEPARTAMENTO -> departamento
            LocationType.PROVINCIA -> "$provincia, $departamento"
            LocationType.DISTRITO -> "$distrito, $provincia"
        }
    }
    
    /**
     * Formato en una línea según el nivel de especificidad
     * Usa getNivelEfectivo() para detectar automáticamente el nivel correcto
     * basándose en los datos disponibles
     */
    fun formatOneLine(includeDireccion: Boolean = false): String {
        val nivelEfectivo = getNivelEfectivo()
        val base = when (nivelEfectivo) {
            LocationType.DEPARTAMENTO -> departamento
            LocationType.PROVINCIA -> "$provincia, $departamento"
            LocationType.DISTRITO -> "$distrito, $provincia, $departamento"
        }
        return if (includeDireccion && direccion != null) "$base - $direccion" else base
    }
    
    /**
     * Formato para mostrar en los detalles del trabajo
     * Muestra solo los niveles que corresponden según la especificidad seleccionada
     */
    fun formatForJobDetail(): String = formatOneLine()
    
    /**
     * Detecta el nivel basándose en los datos disponibles
     * Útil para datos legacy que no tienen el campo nivel
     */
    fun detectarNivel(): LocationType {
        return when {
            // Si provincia está vacía o es igual a departamento, es solo departamento
            provincia.isBlank() || provincia == departamento -> LocationType.DEPARTAMENTO
            // Si distrito está vacío o es igual a provincia, es solo provincia
            distrito.isBlank() || distrito == provincia -> LocationType.PROVINCIA
            // Si tiene los tres diferentes, es distrito
            else -> LocationType.DISTRITO
        }
    }
    
    /**
     * Obtiene el nivel efectivo: usa el nivel explícito si es válido, sino lo detecta
     */
    fun getNivelEfectivo(): LocationType {
        // Si el nivel es DISTRITO pero los datos sugieren algo diferente, detectar
        return if (nivel == LocationType.DISTRITO) {
            detectarNivel()
        } else {
            nivel
        }
    }
}

data class Coordenadas(
    val lat: Double,
    val lng: Double
)

/**
 * Departamento popular para sugerencias rápidas
 */
data class PopularDepartamento(
    val departamento: String,
    val description: String
)

/**
 * Resultado de búsqueda de ubicación
 */
data class LocationSearchResult(
    val texto: String,
    val tipo: LocationType,
    val departamento: String,
    val provincia: String? = null,
    val distrito: String? = null,
    val displayLabel: String,
    val score: Int // Score de relevancia (mayor = más relevante)
) {
    /**
     * Convierte el resultado a UbicacionCompleta
     * IMPORTANTE: Respeta el nivel de especificidad seleccionado por el usuario
     * - Si seleccionó departamento, solo guarda departamento
     * - Si seleccionó provincia, guarda departamento y provincia
     * - Si seleccionó distrito, guarda todo
     */
    fun toUbicacionCompleta(): UbicacionCompleta? {
        return when (tipo) {
            LocationType.DISTRITO -> UbicacionCompleta(
                departamento = departamento,
                provincia = provincia ?: return null,
                distrito = distrito ?: return null,
                nivel = LocationType.DISTRITO
            )
            LocationType.PROVINCIA -> {
                // Solo guardar departamento y provincia, SIN rellenar distrito
                UbicacionCompleta(
                    departamento = departamento,
                    provincia = provincia ?: return null,
                    distrito = "", // Vacío - el usuario no especificó distrito
                    nivel = LocationType.PROVINCIA
                )
            }
            LocationType.DEPARTAMENTO -> {
                // Solo guardar departamento, SIN rellenar provincia ni distrito
                UbicacionCompleta(
                    departamento = departamento,
                    provincia = "", // Vacío - el usuario no especificó provincia
                    distrito = "", // Vacío - el usuario no especificó distrito
                    nivel = LocationType.DEPARTAMENTO
                )
            }
        }
    }
}

/**
 * Sede de empresa
 */
data class SedeEmpresa(
    val id: String,
    val nombre: String,
    val ubicacion: UbicacionCompleta,
    val esPrincipal: Boolean,
    val activa: Boolean = true
)

/**
 * Estructura interna para provincia
 */
data class Provincia(
    val provincia: String,
    val distritos: List<String>
)

/**
 * Estructura interna para departamento
 */
data class Departamento(
    val departamento: String,
    val provincias: List<Provincia>
)

// =============================================================================
// OBJETO PRINCIPAL CON DATOS Y FUNCIONES
// =============================================================================

object PeruLocations {
    
    // =========================================================================
    // DATOS DE DEPARTAMENTOS, PROVINCIAS Y DISTRITOS
    // =========================================================================
    
    val departamentosPeru: List<Departamento> = listOf(
        Departamento(
            departamento = "Amazonas",
            provincias = listOf(
                Provincia("Bagua", listOf("Aramango", "Bagua", "Copallin", "El Parco", "Imaza", "La Peca")),
                Provincia("Bongara", listOf("Chisquilla", "Churuja", "Corosha", "Cuispes", "Florida", "Jazan", "Jumbilla", "Recta", "San Carlos", "Shipasbamba", "Valera", "Yambrasbamba")),
                Provincia("Chachapoyas", listOf("Asuncion", "Balsas", "Chachapoyas", "Cheto", "Chiliquin", "Chuquibamba", "Granada", "Huancas", "La Jalca", "Leimebamba", "Levanto", "Magdalena", "Mariscal Castilla", "Molinopampa", "Montevideo", "Olleros", "Quinjalca", "San Francisco de Daguas", "San Isidro de Maino", "Soloco", "Sonche")),
                Provincia("Condorcanqui", listOf("El Cenepa", "Nieva", "Rio Santiago")),
                Provincia("Luya", listOf("Camporredondo", "Cocabamba", "Colcamar", "Conila", "Inguilpata", "Lamud", "Longuita", "Lonya Chico", "Luya", "Luya Viejo", "Maria", "Ocalli", "Ocumal", "Pisuquia", "Providencia", "San Cristobal", "San Francisco de Yeso", "San Jeronimo", "San Juan de Lopecancha", "Santa Catalina", "Santo Tomas", "Tingo", "Trita")),
                Provincia("Rodriguez de Mendoza", listOf("Chirimoto", "Cochamal", "Huambo", "Limabamba", "Longar", "Mariscal Benavides", "Milpuc", "Omia", "San Nicolas", "Santa Rosa", "Totora", "Vista Alegre")),
                Provincia("Utcubamba", listOf("Bagua Grande", "Cajaruro", "Cumba", "El Milagro", "Jamalca", "Lonya Grande", "Yamon"))
            )
        ),
        Departamento(
            departamento = "Ancash",
            provincias = listOf(
                Provincia("Aija", listOf("Aija", "Coris", "Huacllan", "La Merced", "Succha")),
                Provincia("Antonio Raymondi", listOf("Aczo", "Chaccho", "Chingas", "Llamellin", "Mirgas", "San Juan de Rontoy")),
                Provincia("Asuncion", listOf("Acochaca", "Chacas")),
                Provincia("Bolognesi", listOf("Abelardo Pardo Lezameta", "Antonio Raymondi", "Aquia", "Cajacay", "Canis", "Chiquian", "Colquioc", "Huallanca", "Huasta", "Huayllacayan", "La Primavera", "Mangas", "Pacllon", "San Miguel de Corpanqui", "Ticllos")),
                Provincia("Carhuaz", listOf("Acopampa", "Amashca", "Anta", "Ataquero", "Carhuaz", "Marcara", "Pariahuanca", "San Miguel de Aco", "Shilla", "Tinco", "Yungar")),
                Provincia("Carlos Fermin Fitzcarrald", listOf("San Luis", "San Nicolas", "Yauya")),
                Provincia("Casma", listOf("Buena Vista Alta", "Casma", "Comandante Noel", "Yautan")),
                Provincia("Corongo", listOf("Aco", "Bambas", "Corongo", "Cusca", "La Pampa", "Yanac", "Yupan")),
                Provincia("Huaraz", listOf("Cochabamba", "Colcabamba", "Huanchay", "Huaraz", "Independencia", "Jangas", "La Libertad", "Olleros", "Pampas Grande", "Pariacoto", "Pira", "Tarica")),
                Provincia("Huari", listOf("Anra", "Cajay", "Chavin de Huantar", "Huacachi", "Huacchis", "Huachis", "Huantar", "Huari", "Masin", "Paucas", "Ponto", "Rahuapampa", "Rapayan", "San Marcos", "San Pedro de Chana", "Uco")),
                Provincia("Huarmey", listOf("Cochapeti", "Culebras", "Huarmey", "Huayan", "Malvas")),
                Provincia("Huaylas", listOf("Caraz", "Huallanca", "Huata", "Huaylas", "Mato", "Pamparomas", "Pueblo Libre", "Santa Cruz", "Santo Toribio", "Yuracmarca")),
                Provincia("Mariscal Luzuriaga", listOf("Casca", "Eleazar Guzman Barron", "Fidel Olivas Escudero", "Llama", "Llumpa", "Lucma", "Musga", "Piscobamba")),
                Provincia("Ocros", listOf("Acas", "Cajamarquilla", "Carhuapampa", "Cochas", "Congas", "Llipa", "Ocros", "San Cristobal de Rajan", "San Pedro", "Santiago de Chilcas")),
                Provincia("Pallasca", listOf("Bolognesi", "Cabana", "Conchucos", "Huacaschuque", "Huandoval", "Lacabamba", "Llapo", "Pallasca", "Pampas", "Santa Rosa", "Tauca")),
                Provincia("Pomabamba", listOf("Huayllan", "Parobamba", "Pomabamba", "Quinuabamba")),
                Provincia("Recuay", listOf("Catac", "Cotaparaco", "Huayllapampa", "Llacllin", "Marca", "Pampas Chico", "Pararin", "Recuay", "Tapacocha", "Ticapampa")),
                Provincia("Santa", listOf("Caceres del Peru", "Chimbote", "Coishco", "Macate", "Moro", "Nepeña", "Nuevo Chimbote", "Samanco", "Santa")),
                Provincia("Sihuas", listOf("Acobamba", "Alfonso Ugarte", "Cashapampa", "Chingalpo", "Huayllabamba", "Quiches", "Ragash", "San Juan", "Sicsibamba", "Sihuas")),
                Provincia("Yungay", listOf("Cascapara", "Mancos", "Matacoto", "Quillo", "Ranrahirca", "Shupluy", "Yanama", "Yungay"))
            )
        ),
        Departamento(
            departamento = "Apurimac",
            provincias = listOf(
                Provincia("Abancay", listOf("Abancay", "Chacoche", "Circa", "Curahuasi", "Huanipaca", "Lambrama", "Pichirhua", "San Pedro de Cachora", "Tamburco")),
                Provincia("Andahuaylas", listOf("Andahuaylas", "Andarapa", "Chiara", "Huancarama", "Huancaray", "Huayana", "Jose Maria Arguedas", "Kaquiabamba", "Kishuara", "Pacobamba", "Pacucha", "Pampachiri", "Pomacocha", "San Antonio de Cachi", "San Jeronimo", "San Miguel de Chaccrampa", "Santa Maria de Chicmo", "Talavera", "Tumay Huaraca", "Turpo")),
                Provincia("Antabamba", listOf("Antabamba", "El Oro", "Huaquirca", "Juan Espinoza Medrano", "Oropesa", "Pachaconas", "Sabaino")),
                Provincia("Aymaraes", listOf("Capaya", "Caraybamba", "Chalhuanca", "Chapimarca", "Colcabamba", "Cotaruse", "Huayllu", "Justo Apu Sahuaraura", "Lucre", "Pocohuanca", "San Juan de Chacña", "Sañayca", "Soraya", "Tapairihua", "Tintay", "Toraya", "Yanaca")),
                Provincia("Chincheros", listOf("Ahuayro", "Anco-huallo", "Chincheros", "Cocharcas", "El Porvenir", "Huaccana", "Los Chankas", "Ocobamba", "Ongoy", "Ranracancha", "Rocchacc", "Uranmarca")),
                Provincia("Cotabambas", listOf("Challhuahuacho", "Cotabambas", "Coyllurqui", "Haquira", "Mara", "Tambobamba")),
                Provincia("Grau", listOf("Chuquibambilla", "Curasco", "Curpahuasi", "Huayllati", "Mamara", "Mariscal Gamarra", "Micaela Bastidas", "Pataypampa", "Progreso", "San Antonio", "Santa Rosa", "Turpay", "Vilcabamba", "Virundo"))
            )
        ),
        Departamento(
            departamento = "Arequipa",
            provincias = listOf(
                Provincia("Arequipa", listOf("Alto Selva Alegre", "Arequipa", "Cayma", "Cerro Colorado", "Characato", "Chiguata", "Jacobo Hunter", "Jose Luis Bustamante y Rivero", "La Joya", "Mariano Melgar", "Miraflores", "Mollebaya", "Paucarpata", "Pocsi", "Polobaya", "Quequeña", "Sabandia", "Sachaca", "San Juan de Siguas", "San Juan de Tarucani", "Santa Isabel de Siguas", "Santa Rita de Siguas", "Socabaya", "Tiabaya", "Uchumayo", "Vitor", "Yanahuara", "Yarabamba", "Yura")),
                Provincia("Camana", listOf("Camana", "Jose Maria Quimper", "Mariano Nicolas Valcarcel", "Mariscal Caceres", "Nicolas de Pierola", "Ocoña", "Quilca", "Samuel Pastor")),
                Provincia("Caraveli", listOf("Acari", "Atico", "Atiquipa", "Bella Union", "Cahuacho", "Caraveli", "Chala", "Chaparra", "Huanuhuanu", "Jaqui", "Lomas", "Quicacha", "Yauca")),
                Provincia("Castilla", listOf("Andagua", "Aplao", "Ayo", "Chachas", "Chilcaymarca", "Choco", "Huancarqui", "Machaguay", "Orcopampa", "Pampacolca", "Tipan", "Uraca", "Uñon", "Viraco")),
                Provincia("Caylloma", listOf("Achoma", "Cabanaconde", "Callalli", "Caylloma", "Chivay", "Coporaque", "Huambo", "Huanca", "Ichupampa", "Lari", "Lluta", "Maca", "Madrigal", "Majes", "San Antonio de Chuca", "Sibayo", "Tapay", "Tisco", "Tuti", "Yanque")),
                Provincia("Condesuyos", listOf("Andaray", "Cayarani", "Chichas", "Chuquibamba", "Iray", "Rio Grande", "Salamanca", "Yanaquihua")),
                Provincia("Islay", listOf("Cocachacra", "Dean Valdivia", "Islay", "Mejia", "Mollendo", "Punta de Bombon")),
                Provincia("La Union", listOf("Alca", "Charcana", "Cotahuasi", "Huaynacotas", "Pampamarca", "Puyca", "Quechualla", "Sayla", "Tauria", "Tomepampa", "Toro"))
            )
        ),
        Departamento(
            departamento = "Ayacucho",
            provincias = listOf(
                Provincia("Cangallo", listOf("Cangallo", "Chuschi", "Los Morochucos", "Maria Parado de Bellido", "Paras", "Totos")),
                Provincia("Huamanga", listOf("Acocro", "Acos Vinchos", "Andres Avelino Caceres Dorregaray", "Ayacucho", "Carmen Alto", "Chiara", "Jesus Nazareno", "Ocros", "Pacaycasa", "Quinua", "San Jose de Ticllas", "San Juan Bautista", "Santiago de Pischa", "Socos", "Tambillo", "Vinchos")),
                Provincia("Huanca Sancos", listOf("Carapo", "Sacsamarca", "Sancos", "Santiago de Lucanamarca")),
                Provincia("Huanta", listOf("Ayahuanco", "Canayre", "Chaca", "Huamanguilla", "Huanta", "Iguain", "Llochegua", "Luricocha", "Pucacolpa", "Putis", "Santillana", "Sivia", "Uchuraccay")),
                Provincia("La Mar", listOf("Anchihuay", "Anco", "Ayna", "Chilcas", "Chungui", "Luis Carranza", "Ninabamba", "Oronccoy", "Patibamba", "Rio Magdalena", "Samugari", "San Miguel", "Santa Rosa", "Tambo", "Union Progreso")),
                Provincia("Lucanas", listOf("Aucara", "Cabana", "Carmen Salcedo", "Chaviña", "Chipao", "Huac-huas", "Laramate", "Leoncio Prado", "Llauta", "Lucanas", "Ocaña", "Otoca", "Puquio", "Saisa", "San Cristobal", "San Juan", "San Pedro", "San Pedro de Palco", "Sancos", "Santa Ana de Huaycahuacho", "Santa Lucia")),
                Provincia("Parinacochas", listOf("Chumpi", "Coracora", "Coronel Castañeda", "Pacapausa", "Pullo", "Puyusca", "San Francisco de Ravacayco", "Upahuacho")),
                Provincia("Paucar del Sara Sara", listOf("Colta", "Corculla", "Lampa", "Marcabamba", "Oyolo", "Pararca", "Pausa", "San Javier de Alpabamba", "San Jose de Ushua", "Sara Sara")),
                Provincia("Sucre", listOf("Belen", "Chalcos", "Chilcayoc", "Huacaña", "Morcolla", "Paico", "Querobamba", "San Pedro de Larcay", "San Salvador de Quije", "Santiago de Paucaray", "Soras")),
                Provincia("Victor Fajardo", listOf("Alcamenca", "Apongo", "Asquipata", "Canaria", "Cayara", "Colca", "Huamanquiquia", "Huancapi", "Huancaraylla", "Huaya", "Sarhua", "Vilcanchos")),
                Provincia("Vilcas Huaman", listOf("Accomarca", "Carhuanca", "Concepcion", "Huambalpa", "Independencia", "Saurama", "Vilcas Huaman", "Vischongo"))
            )
        ),
        Departamento(
            departamento = "Cajamarca",
            provincias = listOf(
                Provincia("Cajabamba", listOf("Cachachi", "Cajabamba", "Condebamba", "Sitacocha")),
                Provincia("Cajamarca", listOf("Asuncion", "Cajamarca", "Chetilla", "Cospan", "Encañada", "Jesus", "Llacanora", "Los Baños del Inca", "Magdalena", "Matara", "Namora", "San Juan")),
                Provincia("Celendin", listOf("Celendin", "Chumuch", "Cortegana", "Huasmin", "Jorge Chavez", "Jose Galvez", "La Libertad de Pallan", "Miguel Iglesias", "Oxamarca", "Sorochuco", "Sucre", "Utco")),
                Provincia("Chota", listOf("Anguia", "Chadin", "Chalamarca", "Chiguirip", "Chimban", "Choropampa", "Chota", "Cochabamba", "Conchan", "Huambos", "Lajas", "Llama", "Miracosta", "Paccha", "Pion", "Querocoto", "San Juan de Licupis", "Tacabamba", "Tocmoche")),
                Provincia("Contumaza", listOf("Chilete", "Contumaza", "Cupisnique", "Guzmango", "San Benito", "Santa Cruz de Toledo", "Tantarica", "Yonan")),
                Provincia("Cutervo", listOf("Callayuc", "Choros", "Cujillo", "Cutervo", "La Ramada", "Pimpingos", "Querocotillo", "San Andres de Cutervo", "San Juan de Cutervo", "San Luis de Lucma", "Santa Cruz", "Santo Domingo de la Capilla", "Santo Tomas", "Socota", "Toribio Casanova")),
                Provincia("Hualgayoc", listOf("Bambamarca", "Chugur", "Hualgayoc")),
                Provincia("Jaen", listOf("Bellavista", "Chontali", "Colasay", "Huabal", "Jaen", "Las Pirias", "Pomahuaca", "Pucara", "Sallique", "San Felipe", "San Jose del Alto", "Santa Rosa")),
                Provincia("San Ignacio", listOf("Chirinos", "Huarango", "La Coipa", "Namballe", "San Ignacio", "San Jose de Lourdes", "Tabaconas")),
                Provincia("San Marcos", listOf("Chancay", "Eduardo Villanueva", "Gregorio Pita", "Ichocan", "Jose Manuel Quiroz", "Jose Sabogal", "Pedro Galvez")),
                Provincia("San Miguel", listOf("Bolivar", "Calquis", "Catilluc", "El Prado", "La Florida", "Llapa", "Nanchoc", "Niepos", "San Gregorio", "San Miguel", "San Silvestre de Cochan", "Tongod", "Union Agua Blanca")),
                Provincia("San Pablo", listOf("San Bernardino", "San Luis", "San Pablo", "Tumbaden")),
                Provincia("Santa Cruz", listOf("Andabamba", "Catache", "Chancaybaños", "La Esperanza", "Ninabamba", "Pulan", "Santa Cruz", "Saucepampa", "Sexi", "Uticyacu", "Yauyucan"))
            )
        ),
        Departamento(
            departamento = "Callao",
            provincias = listOf(
                Provincia("Callao", listOf("Bellavista", "Callao", "Carmen de la Legua-reynoso", "La Perla", "La Punta", "Mi Peru", "Ventanilla"))
            )
        ),
        Departamento(
            departamento = "Cusco",
            provincias = listOf(
                Provincia("Acomayo", listOf("Acomayo", "Acopia", "Acos", "Mosoc Llacta", "Pomacanchi", "Rondocan", "Sangarara")),
                Provincia("Anta", listOf("Ancahuasi", "Anta", "Cachimayo", "Chinchaypujio", "Huarocondo", "Limatambo", "Mollepata", "Pucyura", "Zurite")),
                Provincia("Calca", listOf("Calca", "Coya", "Lamay", "Lares", "Pisac", "San Salvador", "Taray", "Yanatile")),
                Provincia("Canas", listOf("Checca", "Kunturkanki", "Langui", "Layo", "Pampamarca", "Quehue", "Tupac Amaru", "Yanaoca")),
                Provincia("Canchis", listOf("Checacupe", "Combapata", "Marangani", "Pitumarca", "San Pablo", "San Pedro", "Sicuani", "Tinta")),
                Provincia("Chumbivilcas", listOf("Capacmarca", "Chamaca", "Colquemarca", "Livitaca", "Llusco", "Quiñota", "Santo Tomas", "Velille")),
                Provincia("Cusco", listOf("Ccorca", "Cusco", "Poroy", "San Jeronimo", "San Sebastian", "Santiago", "Saylla", "Wanchaq")),
                Provincia("Espinar", listOf("Alto Pichigua", "Condoroma", "Coporaque", "Espinar", "Ocoruro", "Pallpata", "Pichigua", "Suyckutambo")),
                Provincia("La Convencion", listOf("Cielo Punco", "Echarate", "Huayopata", "Inkawasi", "Kimbiri", "Kumpirushiato", "Manitea", "Maranura", "Megantoni", "Ocobamba", "Pichari", "Quellouno", "Santa Ana", "Santa Teresa", "Union Ashaninka", "Vilcabamba", "Villa Kintiarina", "Villa Virgen")),
                Provincia("Paruro", listOf("Accha", "Ccapi", "Colcha", "Huanoquite", "Omacha", "Paccaritambo", "Paruro", "Pillpinto", "Yaurisque")),
                Provincia("Paucartambo", listOf("Caicay", "Challabamba", "Colquepata", "Huancarani", "Kosñipata", "Paucartambo")),
                Provincia("Quispicanchi", listOf("Andahuaylillas", "Camanti", "Ccarhuayo", "Ccatca", "Cusipata", "Huaro", "Lucre", "Marcapata", "Ocongate", "Oropesa", "Quiquijana", "Urcos")),
                Provincia("Urubamba", listOf("Chinchero", "Huayllabamba", "Machupicchu", "Maras", "Ollantaytambo", "Urubamba", "Yucay"))
            )
        ),
        Departamento(
            departamento = "Huancavelica",
            provincias = listOf(
                Provincia("Acobamba", listOf("Acobamba", "Andabamba", "Anta", "Caja", "Marcas", "Paucara", "Pomacocha", "Rosario")),
                Provincia("Angaraes", listOf("Anchonga", "Callanmarca", "Ccochaccasa", "Chincho", "Congalla", "Huanca-huanca", "Huayllay Grande", "Julcamarca", "Lircay", "San Antonio de Antaparco", "Santo Tomas de Pata", "Secclla")),
                Provincia("Castrovirreyna", listOf("Arma", "Aurahua", "Capillas", "Castrovirreyna", "Chupamarca", "Cocas", "Huachos", "Huamatambo", "Mollepampa", "San Juan", "Santa Ana", "Tantara", "Ticrapo")),
                Provincia("Churcampa", listOf("Anco", "Chinchihuasi", "Churcampa", "Cosme", "El Carmen", "La Merced", "Locroja", "Pachamarca", "Paucarbamba", "San Miguel de Mayocc", "San Pedro de Coris")),
                Provincia("Huancavelica", listOf("Acobambilla", "Acoria", "Ascension", "Conayca", "Cuenca", "Huachocolpa", "Huancavelica", "Huando", "Huayllahuara", "Izcuchaca", "Laria", "Manta", "Mariscal Caceres", "Moya", "Nuevo Occoro", "Palca", "Pilchaca", "Vilca", "Yauli")),
                Provincia("Huaytara", listOf("Ayavi", "Cordova", "Huayacundo Arma", "Huaytara", "Laramarca", "Ocoyo", "Pilpichaca", "Querco", "Quito Arma", "San Antonio de Cusicancha", "San Francisco de Sangayaico", "San Isidro", "Santiago de Chocorvos", "Santiago de Quirahuara", "Santo Domingo de Capillas", "Tambo")),
                Provincia("Tayacaja", listOf("Acostambo", "Acraquia", "Ahuaycha", "Andaymarca", "Cochabamba", "Colcabamba", "Daniel Hernandez", "Huachocolpa", "Huaribamba", "Lambras", "Pampas", "Pazos", "Pichos", "Quichuas", "Quishuar", "Roble", "Salcabamba", "Salcahuasi", "San Marcos de Rocchac", "Santiago de Tucuma", "Surcubamba", "Tintay Puncu", "Ñahuimpuquio"))
            )
        ),
        Departamento(
            departamento = "Huanuco",
            provincias = listOf(
                Provincia("Ambo", listOf("Ambo", "Cayna", "Colpas", "Conchamarca", "Huacar", "San Francisco", "San Rafael", "Tomay Kichwa")),
                Provincia("Dos de Mayo", listOf("Chuquis", "La Union", "Marias", "Pachas", "Quivilla", "Ripan", "Shunqui", "Sillapata", "Yanas")),
                Provincia("Huacaybamba", listOf("Canchabamba", "Cochabamba", "Huacaybamba", "Pinra")),
                Provincia("Huamalies", listOf("Arancay", "Chavin de Pariarca", "Jacas Grande", "Jircan", "Llata", "Miraflores", "Monzon", "Punchao", "Puños", "Singa", "Tantamayo")),
                Provincia("Huanuco", listOf("Amarilis", "Chinchao", "Churubamba", "Huanuco", "Margos", "Pillco Marca", "Quisqui", "San Francisco de Cayran", "San Pablo de Pillao", "San Pedro de Chaulan", "Santa Maria del Valle", "Yacus", "Yarumayo")),
                Provincia("Lauricocha", listOf("Baños", "Jesus", "Jivia", "Queropalca", "Rondos", "San Francisco de Asis", "San Miguel de Cauri")),
                Provincia("Leoncio Prado", listOf("Castillo Grande", "Daniel Alomia Robles", "Hermilio Valdizan", "Jose Crespo y Castillo", "Luyando", "Mariano Damaso Beraun", "Pucayacu", "Pueblo Nuevo", "Rupa-rupa", "Santo Domingo de Anda")),
                Provincia("Marañon", listOf("Cholon", "Huacrachuco", "La Morada", "San Buenaventura", "Santa Rosa de Alto Yanajanca")),
                Provincia("Pachitea", listOf("Chaglla", "Molino", "Panao", "Umari")),
                Provincia("Puerto Inca", listOf("Codo del Pozuzo", "Honoria", "Puerto Inca", "Tournavista", "Yuyapichis")),
                Provincia("Yarowilca", listOf("Aparicio Pomares", "Cahuac", "Chacabamba", "Chavinillo", "Choras", "Jacas Chico", "Obas", "Pampamarca"))
            )
        ),
        Departamento(
            departamento = "Ica",
            provincias = listOf(
                Provincia("Chincha", listOf("Alto Laran", "Chavin", "Chincha Alta", "Chincha Baja", "El Carmen", "Grocio Prado", "Pueblo Nuevo", "San Juan de Yanac", "San Pedro de Huacarpana", "Sunampe", "Tambo de Mora")),
                Provincia("Ica", listOf("Ica", "La Tinguiña", "Los Aquijes", "Ocucaje", "Pachacutec", "Parcona", "Pueblo Nuevo", "Salas", "San Jose de los Molinos", "San Juan Bautista", "Santiago", "Subtanjalla", "Tate", "Yauca del Rosario")),
                Provincia("Nazca", listOf("Changuillo", "El Ingenio", "Marcona", "Nazca", "Vista Alegre")),
                Provincia("Palpa", listOf("Llipata", "Palpa", "Rio Grande", "Santa Cruz", "Tibillo")),
                Provincia("Pisco", listOf("Huancano", "Humay", "Independencia", "Paracas", "Pisco", "San Andres", "San Clemente", "Tupac Amaru Inca"))
            )
        ),
        Departamento(
            departamento = "Junin",
            provincias = listOf(
                Provincia("Chanchamayo", listOf("Chanchamayo", "Perene", "Pichanaqui", "San Luis de Shuaro", "San Ramon", "Vitoc")),
                Provincia("Chupaca", listOf("Ahuac", "Chongos Bajo", "Chupaca", "Huachac", "Huamancaca Chico", "San Juan de Jarpa", "San Juan de Yscos", "Tres de Diciembre", "Yanacancha")),
                Provincia("Concepcion", listOf("Aco", "Andamarca", "Chambara", "Cochas", "Comas", "Concepcion", "Heroinas Toledo", "Manzanares", "Mariscal Castilla", "Matahuasi", "Mito", "Nueve de Julio", "Orcotuna", "San Jose de Quero", "Santa Rosa de Ocopa")),
                Provincia("Huancayo", listOf("Carhuacallanga", "Chacapampa", "Chicche", "Chilca", "Chongos Alto", "Chupuro", "Colca", "Cullhuas", "El Tambo", "Huacrapuquio", "Hualhuas", "Huancan", "Huancayo", "Huasicancha", "Huayucachi", "Ingenio", "Pariahuanca", "Pilcomayo", "Pucara", "Quichuay", "Quilcas", "San Agustin", "San Jeronimo de Tunan", "Santo Domingo de Acobamba", "Sapallanga", "Saño", "Sicaya", "Viques")),
                Provincia("Jauja", listOf("Acolla", "Apata", "Ataura", "Canchayllo", "Curicaca", "El Mantaro", "Huamali", "Huaripampa", "Huertas", "Janjaillo", "Jauja", "Julcan", "Leonor Ordoñez", "Llocllapampa", "Marco", "Masma", "Masma Chicche", "Molinos", "Monobamba", "Muqui", "Muquiyauyo", "Paca", "Paccha", "Pancan", "Parco", "Pomacancha", "Ricran", "San Lorenzo", "San Pedro de Chunan", "Sausa", "Sincos", "Tunan Marca", "Yauli", "Yauyos")),
                Provincia("Junin", listOf("Carhuamayo", "Junin", "Ondores", "Ulcumayo")),
                Provincia("Satipo", listOf("Coviriali", "Llaylla", "Mazamari", "Pampa Hermosa", "Pangoa", "Rio Negro", "Rio Tambo", "Satipo", "Vizcatan del Ene")),
                Provincia("Tarma", listOf("Acobamba", "Huaricolca", "Huasahuasi", "La Union", "Palca", "Palcamayo", "San Pedro de Cajas", "Tapo", "Tarma")),
                Provincia("Yauli", listOf("Chacapalpa", "Huay-huay", "La Oroya", "Marcapomacocha", "Morococha", "Paccha", "Santa Barbara de Carhuacayan", "Santa Rosa de Sacco", "Suitucancha", "Yauli"))
            )
        ),
        Departamento(
            departamento = "La Libertad",
            provincias = listOf(
                Provincia("Ascope", listOf("Ascope", "Casa Grande", "Chicama", "Chocope", "Magdalena de Cao", "Paijan", "Razuri", "Santiago de Cao")),
                Provincia("Bolivar", listOf("Bambamarca", "Bolivar", "Condormarca", "Longotea", "Uchumarca", "Ucuncha")),
                Provincia("Chepen", listOf("Chepen", "Pacanga", "Pueblo Nuevo")),
                Provincia("Gran Chimu", listOf("Cascas", "Lucma", "Marmot", "Sayapullo")),
                Provincia("Julcan", listOf("Calamarca", "Carabamba", "Huaso", "Julcan")),
                Provincia("Otuzco", listOf("Agallpampa", "Charat", "Huaranchal", "La Cuesta", "Mache", "Otuzco", "Paranday", "Salpo", "Sinsicap", "Usquil")),
                Provincia("Pacasmayo", listOf("Guadalupe", "Jequetepeque", "Pacasmayo", "San Jose", "San Pedro de Lloc")),
                Provincia("Pataz", listOf("Buldibuyo", "Chillia", "Huancaspata", "Huaylillas", "Huayo", "Ongon", "Parcoy", "Pataz", "Pias", "Santiago de Challas", "Taurija", "Tayabamba", "Urpay")),
                Provincia("Sanchez Carrion", listOf("Chugay", "Cochorco", "Curgos", "Huamachuco", "Marcabal", "Sanagoran", "Sarin", "Sartimbamba")),
                Provincia("Santiago de Chuco", listOf("Angasmarca", "Cachicadan", "Mollebamba", "Mollepata", "Quiruvilca", "Santa Cruz de Chuca", "Santiago de Chuco", "Sitabamba")),
                Provincia("Trujillo", listOf("Alto Trujillo", "El Porvenir", "Florencia de Mora", "Huanchaco", "La Esperanza", "Laredo", "Moche", "Poroto", "Salaverry", "Simbal", "Trujillo", "Victor Larco Herrera")),
                Provincia("Viru", listOf("Chao", "Guadalupito", "Viru"))
            )
        ),
        Departamento(
            departamento = "Lambayeque",
            provincias = listOf(
                Provincia("Chiclayo", listOf("Cayalti", "Chiclayo", "Chongoyape", "Eten", "Eten Puerto", "Jose Leonardo Ortiz", "La Victoria", "Lagunas", "Monsefu", "Nueva Arica", "Oyotun", "Patapo", "Picsi", "Pimentel", "Pomalca", "Pucala", "Reque", "Santa Rosa", "Saña", "Tuman")),
                Provincia("Ferreñafe", listOf("Cañaris", "Ferreñafe", "Incahuasi", "Manuel Antonio Mesones Muro", "Pitipo", "Pueblo Nuevo")),
                Provincia("Lambayeque", listOf("Chochope", "Illimo", "Jayanca", "Lambayeque", "Mochumi", "Morrope", "Motupe", "Olmos", "Pacora", "Salas", "San Jose", "Tucume"))
            )
        ),
        Departamento(
            departamento = "Lima",
            provincias = listOf(
                Provincia("Barranca", listOf("Barranca", "Paramonga", "Pativilca", "Supe", "Supe Puerto")),
                Provincia("Cajatambo", listOf("Cajatambo", "Copa", "Gorgor", "Huancapon", "Manas")),
                Provincia("Canta", listOf("Arahuay", "Canta", "Huamantanga", "Huaros", "Lachaqui", "San Buenaventura", "Santa Rosa de Quives")),
                Provincia("Cañete", listOf("Asia", "Calango", "Cerro Azul", "Chilca", "Coayllo", "Imperial", "Lunahuana", "Mala", "Nuevo Imperial", "Pacaran", "Quilmana", "San Antonio", "San Luis", "San Vicente de Cañete", "Santa Cruz de Flores", "Zuñiga")),
                Provincia("Huaral", listOf("Atavillos Alto", "Atavillos Bajo", "Aucallama", "Chancay", "Huaral", "Ihuari", "Lampian", "Pacaraos", "San Miguel de Acos", "Santa Cruz de Andamarca", "Sumbilca", "Veintisiete de Noviembre")),
                Provincia("Huarochiri", listOf("Antioquia", "Callahuanca", "Carampoma", "Chicla", "Cuenca", "Huachupampa", "Huanza", "Huarochiri", "Lahuaytambo", "Langa", "Mariatana", "Matucana", "Ricardo Palma", "San Andres de Tupicocha", "San Antonio", "San Bartolome", "San Damian", "San Juan de Iris", "San Juan de Tantaranche", "San Lorenzo de Quinti", "San Mateo", "San Mateo de Otao", "San Pedro de Casta", "San Pedro de Huancayre", "San Pedro Laraos", "Sangallaya", "Santa Cruz de Cocachacra", "Santa Eulalia", "Santiago de Anchucaya", "Santiago de Tuna", "Santo Domingo de los Olleros", "Surco")),
                Provincia("Huaura", listOf("Ambar", "Caleta de Carquin", "Checras", "Huacho", "Hualmay", "Huaura", "Leoncio Prado", "Paccho", "Santa Leonor", "Santa Maria", "Sayan", "Vegueta")),
                Provincia("Lima", listOf("Ancon", "Ate", "Barranco", "Breña", "Carabayllo", "Chaclacayo", "Chorrillos", "Cieneguilla", "Comas", "El Agustino", "Independencia", "Jesus Maria", "La Molina", "La Victoria", "Lima", "Lince", "Los Olivos", "Lurigancho", "Lurin", "Magdalena del Mar", "Miraflores", "Pachacamac", "Pucusana", "Pueblo Libre", "Puente Piedra", "Punta Hermosa", "Punta Negra", "Rimac", "San Bartolo", "San Borja", "San Isidro", "San Juan de Lurigancho", "San Juan de Miraflores", "San Luis", "San Martin de Porres", "San Miguel", "Santa Anita", "Santa Maria del Mar", "Santa Rosa", "Santiago de Surco", "Surquillo", "Villa el Salvador", "Villa Maria del Triunfo")),
                Provincia("Oyon", listOf("Andajes", "Caujul", "Cochamarca", "Navan", "Oyon", "Pachangara")),
                Provincia("Yauyos", listOf("Alis", "Ayauca", "Ayaviri", "Azangaro", "Cacra", "Carania", "Catahuasi", "Chocos", "Cochas", "Colonia", "Hongos", "Huampara", "Huancaya", "Huangascar", "Huantan", "Huañec", "Laraos", "Lincha", "Madean", "Miraflores", "Omas", "Putinza", "Quinches", "Quinocay", "San Joaquin", "San Pedro de Pilas", "Tanta", "Tauripampa", "Tomas", "Tupe", "Vitis", "Viñac", "Yauyos"))
            )
        ),
        Departamento(
            departamento = "Loreto",
            provincias = listOf(
                Provincia("Alto Amazonas", listOf("Balsapuerto", "Jeberos", "Lagunas", "Santa Cruz", "Teniente Cesar Lopez Rojas", "Yurimaguas")),
                Provincia("Datem del Marañon", listOf("Andoas", "Barranca", "Cahuapanas", "Manseriche", "Morona", "Pastaza")),
                Provincia("Loreto", listOf("Nauta", "Parinari", "Tigre", "Trompeteros", "Urarinas")),
                Provincia("Mariscal Ramon Castilla", listOf("Pebas", "Ramon Castilla", "San Pablo", "Santa Rosa de Loreto", "Yavari")),
                Provincia("Maynas", listOf("Alto Nanay", "Belen", "Fernando Lores", "Indiana", "Iquitos", "Las Amazonas", "Mazan", "Napo", "Punchana", "San Juan Bautista", "Torres Causana")),
                Provincia("Putumayo", listOf("Putumayo", "Rosa Panduro", "Teniente Manuel Clavero", "Yaguas")),
                Provincia("Requena", listOf("Alto Tapiche", "Capelo", "Emilio San Martin", "Jenaro Herrera", "Maquia", "Puinahua", "Requena", "Saquena", "Soplin", "Tapiche", "Yaquerana")),
                Provincia("Ucayali", listOf("Contamana", "Inahuaya", "Padre Marquez", "Pampa Hermosa", "Sarayacu", "Vargas Guerra"))
            )
        ),
        Departamento(
            departamento = "Madre de Dios",
            provincias = listOf(
                Provincia("Manu", listOf("Fitzcarrald", "Huepetuhe", "Madre de Dios", "Manu")),
                Provincia("Tahuamanu", listOf("Iberia", "Iñapari", "Tahuamanu")),
                Provincia("Tambopata", listOf("Inambari", "Laberinto", "Las Piedras", "Tambopata"))
            )
        ),
        Departamento(
            departamento = "Moquegua",
            provincias = listOf(
                Provincia("General Sanchez Cerro", listOf("Chojata", "Coalaque", "Ichuña", "La Capilla", "Lloque", "Matalaque", "Omate", "Puquina", "Quinistaquillas", "Ubinas", "Yunga")),
                Provincia("Ilo", listOf("El Algarrobal", "Ilo", "Pacocha")),
                Provincia("Mariscal Nieto", listOf("Carumas", "Cuchumbaya", "Moquegua", "Samegua", "San Antonio", "San Cristobal", "Torata"))
            )
        ),
        Departamento(
            departamento = "Pasco",
            provincias = listOf(
                Provincia("Daniel Alcides Carrion", listOf("Chacayan", "Goyllarisquizga", "Paucar", "San Pedro de Pillao", "Santa Ana de Tusi", "Tapuc", "Vilcabamba", "Yanahuanca")),
                Provincia("Oxapampa", listOf("Chontabamba", "Constitucion", "Huancabamba", "Oxapampa", "Palcazu", "Pozuzo", "Puerto Bermudez", "Villa Rica")),
                Provincia("Pasco", listOf("Chaupimarca", "Huachon", "Huariaca", "Huayllay", "Ninacaca", "Pallanchacra", "Paucartambo", "San Francisco de Asis de Yarusyacan", "Simon Bolivar", "Ticlacayan", "Tinyahuarco", "Vicco", "Yanacancha"))
            )
        ),
        Departamento(
            departamento = "Piura",
            provincias = listOf(
                Provincia("Ayabaca", listOf("Ayabaca", "Frias", "Jilili", "Lagunas", "Montero", "Pacaipampa", "Paimas", "Sapillica", "Sicchez", "Suyo")),
                Provincia("Huancabamba", listOf("Canchaque", "El Carmen de la Frontera", "Huancabamba", "Huarmaca", "Lalaquiz", "San Miguel de el Faique", "Sondor", "Sondorillo")),
                Provincia("Morropon", listOf("Buenos Aires", "Chalaco", "Chulucanas", "La Matanza", "Morropon", "Salitral", "San Juan de Bigote", "Santa Catalina de Mossa", "Santo Domingo", "Yamango")),
                Provincia("Paita", listOf("Amotape", "Arenal", "Colan", "La Huaca", "Paita", "Tamarindo", "Vichayal")),
                Provincia("Piura", listOf("Castilla", "Catacaos", "Cura Mori", "El Tallan", "La Arena", "La Union", "Las Lomas", "Piura", "Tambo Grande", "Veintiseis de Octubre")),
                Provincia("Sechura", listOf("Bellavista de la Union", "Bernal", "Cristo Nos Valga", "Rinconada-llicuar", "Sechura", "Vice")),
                Provincia("Sullana", listOf("Bellavista", "Ignacio Escudero", "Lancones", "Marcavelica", "Miguel Checa", "Querecotillo", "Salitral", "Sullana")),
                Provincia("Talara", listOf("El Alto", "La Brea", "Lobitos", "Los Organos", "Mancora", "Pariñas"))
            )
        ),
        Departamento(
            departamento = "Puno",
            provincias = listOf(
                Provincia("Azangaro", listOf("Achaya", "Arapa", "Asillo", "Azangaro", "Caminaca", "Chupa", "Jose Domingo Choquehuanca", "Muñani", "Potoni", "Saman", "San Anton", "San Jose", "San Juan de Salinas", "Santiago de Pupuja", "Tirapata")),
                Provincia("Carabaya", listOf("Ajoyani", "Ayapata", "Coasa", "Corani", "Crucero", "Ituata", "Macusani", "Ollachea", "San Gaban", "Usicayos")),
                Provincia("Chucuito", listOf("Desaguadero", "Huacullani", "Juli", "Kelluyo", "Pisacoma", "Pomata", "Zepita")),
                Provincia("El Collao", listOf("Capazo", "Conduriri", "Ilave", "Pilcuyo", "Santa Rosa")),
                Provincia("Huancane", listOf("Cojata", "Huancane", "Huatasani", "Inchupalla", "Pusi", "Rosaspata", "Taraco", "Vilque Chico")),
                Provincia("Lampa", listOf("Cabanilla", "Calapuja", "Lampa", "Nicasio", "Ocuviri", "Palca", "Paratia", "Pucara", "Santa Lucia", "Vilavila")),
                Provincia("Melgar", listOf("Antauta", "Ayaviri", "Cupi", "Llalli", "Macari", "Nuñoa", "Orurillo", "Santa Rosa", "Umachiri")),
                Provincia("Moho", listOf("Conima", "Huayrapata", "Moho", "Tilali")),
                Provincia("Puno", listOf("Acora", "Amantani", "Atuncolla", "Capachica", "Chucuito", "Coata", "Huata", "Mañazo", "Paucarcolla", "Pichacani", "Plateria", "Puno", "San Antonio", "Tiquillaca", "Vilque")),
                Provincia("San Antonio de Putina", listOf("Ananea", "Pedro Vilca Apaza", "Putina", "Quilcapuncu", "Sina")),
                Provincia("San Roman", listOf("Cabana", "Cabanillas", "Caracoto", "Juliaca", "San Miguel")),
                Provincia("Sandia", listOf("Alto Inambari", "Cuyocuyo", "Limbani", "Patambuco", "Phara", "Quiaca", "San Juan del Oro", "San Pedro de Putina Punco", "Sandia", "Yanahuaya")),
                Provincia("Yunguyo", listOf("Anapia", "Copani", "Cuturapi", "Ollaraya", "Tinicachi", "Unicachi", "Yunguyo"))
            )
        ),
        Departamento(
            departamento = "San Martin",
            provincias = listOf(
                Provincia("Bellavista", listOf("Alto Biavo", "Bajo Biavo", "Bellavista", "Huallaga", "San Pablo", "San Rafael")),
                Provincia("El Dorado", listOf("Agua Blanca", "San Jose de Sisa", "San Martin", "Santa Rosa", "Shatoja")),
                Provincia("Huallaga", listOf("Alto Saposoa", "El Eslabon", "Piscoyacu", "Sacanche", "Saposoa", "Tingo de Saposoa")),
                Provincia("Lamas", listOf("Alonso de Alvarado", "Barranquita", "Caynarachi", "Cuñumbuqui", "Lamas", "Pinto Recodo", "Rumisapa", "San Roque de Cumbaza", "Shanao", "Tabalosos", "Zapatero")),
                Provincia("Mariscal Caceres", listOf("Campanilla", "Huicungo", "Juanjui", "Pachiza", "Pajarillo")),
                Provincia("Moyobamba", listOf("Calzada", "Habana", "Jepelacio", "Moyobamba", "Soritor", "Yantalo")),
                Provincia("Picota", listOf("Buenos Aires", "Caspizapa", "Picota", "Pilluana", "Pucacaca", "San Cristobal", "San Hilarion", "Shamboyacu", "Tingo de Ponasa", "Tres Unidos")),
                Provincia("Rioja", listOf("Awajun", "Elias Soplin Vargas", "Nueva Cajamarca", "Pardo Miguel", "Posic", "Rioja", "San Fernando", "Yorongos", "Yuracyacu")),
                Provincia("San Martin", listOf("Alberto Leveau", "Cacatachi", "Chazuta", "Chipurana", "El Porvenir", "Huimbayoc", "Juan Guerra", "La Banda de Shilcayo", "Morales", "Papaplaya", "San Antonio", "Sauce", "Shapaja", "Tarapoto")),
                Provincia("Tocache", listOf("Nuevo Progreso", "Polvora", "Santa Lucia", "Shunte", "Tocache", "Uchiza"))
            )
        ),
        Departamento(
            departamento = "Tacna",
            provincias = listOf(
                Provincia("Candarave", listOf("Cairani", "Camilaca", "Candarave", "Curibaya", "Huanuara", "Quilahuani")),
                Provincia("Jorge Basadre", listOf("Ilabaya", "Ite", "Locumba")),
                Provincia("Tacna", listOf("Alto de la Alianza", "Calana", "Ciudad Nueva", "Coronel Gregorio Albarracin Lanchipa", "Inclan", "La Yarada los Palos", "Pachia", "Palca", "Pocollay", "Sama", "Tacna")),
                Provincia("Tarata", listOf("Estique", "Estique Pampa", "Heroes Albarracin", "Sitajara", "Susapaya", "Tarata", "Tarucachi", "Ticaco"))
            )
        ),
        Departamento(
            departamento = "Tumbes",
            provincias = listOf(
                Provincia("Contralmirante Villar", listOf("Canoas de Punta Sal", "Casitas", "Zorritos")),
                Provincia("Tumbes", listOf("Corrales", "La Cruz", "Pampas de Hospital", "San Jacinto", "San Juan de la Virgen", "Tumbes")),
                Provincia("Zarumilla", listOf("Aguas Verdes", "Matapalo", "Papayal", "Zarumilla"))
            )
        ),
        Departamento(
            departamento = "Ucayali",
            provincias = listOf(
                Provincia("Atalaya", listOf("Raimondi", "Sepahua", "Tahuania", "Yurua")),
                Provincia("Coronel Portillo", listOf("Calleria", "Campoverde", "Iparia", "Manantay", "Masisea", "Nueva Requena", "Yarinacocha")),
                Provincia("Padre Abad", listOf("Alexander Von Humboldt", "Boqueron", "Curimana", "Huipoca", "Irazola", "Neshuya", "Padre Abad")),
                Provincia("Purus", listOf("Purus"))
            )
        )
    )
    
    // =========================================================================
    // FUNCIONES DE NORMALIZACIÓN
    // =========================================================================
    
    /**
     * Normaliza texto para búsqueda (quita tildes, minúsculas, espacios extras)
     */
    fun normalizeText(text: String): String {
        return Normalizer.normalize(text.lowercase().trim(), Normalizer.Form.NFD)
            .replace(Regex("[\\p{InCombiningDiacriticalMarks}]"), "")
            .replace("ñ", "n")
            .replace(Regex("\\s+"), " ")
    }
    
    /**
     * Calcula score de similitud entre dos strings (0-100)
     */
    private fun calculateSimilarity(query: String, target: String): Int {
        val normalizedQuery = normalizeText(query)
        val normalizedTarget = normalizeText(target)
        
        return when {
            normalizedTarget == normalizedQuery -> 100
            normalizedTarget.startsWith(normalizedQuery) -> 90
            normalizedTarget.contains(normalizedQuery) -> 70
            normalizedQuery.contains(normalizedTarget) -> 50
            else -> 0
        }
    }
    
    // =========================================================================
    // FUNCIONES DE BÚSQUEDA INTELIGENTE
    // =========================================================================
    
    /**
     * Búsqueda inteligente de ubicaciones
     * Busca en departamentos, provincias y distritos simultáneamente
     */
    fun searchLocation(query: String, limit: Int = 10): List<LocationSearchResult> {
        if (query.isBlank() || query.length < 2) return emptyList()
        
        val results = mutableListOf<LocationSearchResult>()
        val normalizedQuery = normalizeText(query)
        val seen = mutableSetOf<String>()
        
        for (dep in departamentosPeru) {
            val depNormalized = normalizeText(dep.departamento)
            
            // Buscar en departamentos
            if (depNormalized.contains(normalizedQuery) || normalizedQuery.contains(depNormalized)) {
                val score = calculateSimilarity(query, dep.departamento)
                val key = "dep:${dep.departamento}"
                if (score > 0 && key !in seen) {
                    seen.add(key)
                    results.add(LocationSearchResult(
                        texto = dep.departamento,
                        tipo = LocationType.DEPARTAMENTO,
                        departamento = dep.departamento,
                        displayLabel = "📍 ${dep.departamento} (Departamento)",
                        score = score + 10
                    ))
                }
            }
            
            for (prov in dep.provincias) {
                val provNormalized = normalizeText(prov.provincia)
                
                // Buscar en provincias
                if (provNormalized.contains(normalizedQuery) || normalizedQuery.contains(provNormalized)) {
                    val score = calculateSimilarity(query, prov.provincia)
                    val key = "prov:${dep.departamento}:${prov.provincia}"
                    if (score > 0 && key !in seen) {
                        seen.add(key)
                        results.add(LocationSearchResult(
                            texto = prov.provincia,
                            tipo = LocationType.PROVINCIA,
                            departamento = dep.departamento,
                            provincia = prov.provincia,
                            displayLabel = "🏘️ ${prov.provincia}, ${dep.departamento}",
                            score = score + 5
                        ))
                    }
                }
                
                for (dist in prov.distritos) {
                    val distNormalized = normalizeText(dist)
                    
                    // Buscar en distritos
                    if (distNormalized.contains(normalizedQuery) || normalizedQuery.contains(distNormalized)) {
                        val score = calculateSimilarity(query, dist)
                        val key = "dist:${dep.departamento}:${prov.provincia}:$dist"
                        if (score > 0 && key !in seen) {
                            seen.add(key)
                            results.add(LocationSearchResult(
                                texto = dist,
                                tipo = LocationType.DISTRITO,
                                departamento = dep.departamento,
                                provincia = prov.provincia,
                                distrito = dist,
                                displayLabel = "📌 $dist, ${prov.provincia}, ${dep.departamento}",
                                score = score
                            ))
                        }
                    }
                }
            }
        }
        
        return results.sortedByDescending { it.score }.take(limit)
    }
    
    /**
     * Búsqueda rápida que devuelve solo distritos
     */
    fun searchDistritos(query: String, limit: Int = 10): List<LocationSearchResult> {
        return searchLocation(query, limit * 2)
            .filter { it.tipo == LocationType.DISTRITO }
            .take(limit)
    }
    
    // =========================================================================
    // FUNCIONES DE RESOLUCIÓN (REVERSE LOOKUP)
    // =========================================================================
    
    /**
     * Resuelve la ubicación completa desde un distrito
     */
    /**
     * Resuelve la ubicación completa desde un distrito con contexto adicional
     * Prioriza coincidencias que matcheen también provincia y/o departamento
     */
    fun resolveFromDistritoWithContext(
        distrito: String,
        provincia: String? = null,
        departamento: String? = null
    ): UbicacionCompleta? {
        val normalizedDistrito = normalizeText(distrito)
        val normalizedProvincia = provincia?.let { normalizeText(it) }
        val normalizedDepartamento = departamento?.let { normalizeText(it) }
        
        var bestMatch: UbicacionCompleta? = null
        var bestScore = 0
        
        for (dep in departamentosPeru) {
            for (prov in dep.provincias) {
                for (dist in prov.distritos) {
                    if (normalizeText(dist) == normalizedDistrito) {
                        var score = 1 // Match por distrito
                        
                        // Bonus si provincia coincide
                        if (normalizedProvincia != null && normalizeText(prov.provincia) == normalizedProvincia) {
                            score += 2
                        }
                        
                        // Bonus si departamento coincide
                        if (normalizedDepartamento != null && normalizeText(dep.departamento) == normalizedDepartamento) {
                            score += 2
                        }
                        
                        if (score > bestScore) {
                            bestScore = score
                            bestMatch = UbicacionCompleta(
                                departamento = dep.departamento,
                                provincia = prov.provincia,
                                distrito = dist
                            )
                        }
                    }
                }
            }
        }
        return bestMatch
    }
    
    fun resolveFromDistrito(distrito: String): UbicacionCompleta? {
        val normalizedDistrito = normalizeText(distrito)
        
        for (dep in departamentosPeru) {
            for (prov in dep.provincias) {
                for (dist in prov.distritos) {
                    if (normalizeText(dist) == normalizedDistrito) {
                        return UbicacionCompleta(
                            departamento = dep.departamento,
                            provincia = prov.provincia,
                            distrito = dist
                        )
                    }
                }
            }
        }
        return null
    }
    
    /**
     * Resuelve la ubicación completa desde una provincia
     */
    fun resolveFromProvincia(provincia: String, departamento: String? = null): UbicacionCompleta? {
        val normalizedProvincia = normalizeText(provincia)
        
        for (dep in departamentosPeru) {
            if (departamento != null && normalizeText(dep.departamento) != normalizeText(departamento)) {
                continue
            }
            
            for (prov in dep.provincias) {
                if (normalizeText(prov.provincia) == normalizedProvincia) {
                    return if (prov.distritos.isNotEmpty()) {
                        UbicacionCompleta(
                            departamento = dep.departamento,
                            provincia = prov.provincia,
                            distrito = prov.distritos[0]
                        )
                    } else null
                }
            }
        }
        return null
    }
    
    // =========================================================================
    // FUNCIONES DE VALIDACIÓN
    // =========================================================================
    
    /**
     * Valida si una ubicación completa es válida
     */
    fun isValidLocation(ubicacion: UbicacionCompleta): Boolean {
        val dep = departamentosPeru.find { 
            normalizeText(it.departamento) == normalizeText(ubicacion.departamento) 
        } ?: return false
        
        val prov = dep.provincias.find { 
            normalizeText(it.provincia) == normalizeText(ubicacion.provincia) 
        } ?: return false
        
        return prov.distritos.any { 
            normalizeText(it) == normalizeText(ubicacion.distrito) 
        }
    }
    
    /**
     * Valida y normaliza una ubicación (corrige mayúsculas/tildes)
     */
    fun normalizeLocation(ubicacion: UbicacionCompleta): UbicacionCompleta? {
        for (dep in departamentosPeru) {
            if (normalizeText(dep.departamento) == normalizeText(ubicacion.departamento)) {
                for (prov in dep.provincias) {
                    if (normalizeText(prov.provincia) == normalizeText(ubicacion.provincia)) {
                        for (dist in prov.distritos) {
                            if (normalizeText(dist) == normalizeText(ubicacion.distrito)) {
                                return UbicacionCompleta(
                                    departamento = dep.departamento,
                                    provincia = prov.provincia,
                                    distrito = dist,
                                    direccion = ubicacion.direccion,
                                    coordenadas = ubicacion.coordenadas
                                )
                            }
                        }
                    }
                }
            }
        }
        return null
    }
    
    // =========================================================================
    // FUNCIONES PARA LISTAS SIMPLES (compatibilidad)
    // =========================================================================
    
    /**
     * Obtiene lista de nombres de departamentos
     */
    fun getDepartamentos(): List<String> = departamentosPeru.map { it.departamento }

    /**
     * Departamentos populares para sugerencias cuando no hay resultados
     */
    fun getPopularDepartamentos(): List<PopularDepartamento> = listOf(
        PopularDepartamento("Ica", "🍇 Zona agrícola principal"),
        PopularDepartamento("Lima", "🏙️ Capital del Perú"),
        PopularDepartamento("La Libertad", "🌾 Agroindustria del norte"),
        PopularDepartamento("Arequipa", "🏔️ Sur productivo"),
        PopularDepartamento("Piura", "🥭 Frutas tropicales"),
        PopularDepartamento("Lambayeque", "🌿 Costa norte")
    )
    
    /**
     * Obtiene provincias de un departamento
     */
    fun getProvincias(departamento: String): List<String> {
        val dep = departamentosPeru.find { 
            normalizeText(it.departamento) == normalizeText(departamento) 
        }
        return dep?.provincias?.map { it.provincia } ?: emptyList()
    }
    
    /**
     * Obtiene distritos de una provincia
     */
    fun getDistritos(departamento: String, provincia: String): List<String> {
        val dep = departamentosPeru.find { 
            normalizeText(it.departamento) == normalizeText(departamento) 
        } ?: return emptyList()
        
        val prov = dep.provincias.find { 
            normalizeText(it.provincia) == normalizeText(provincia) 
        }
        return prov?.distritos ?: emptyList()
    }
    
    // =========================================================================
    // FUNCIONES PARA SEDES
    // =========================================================================
    
    /**
     * Crea una nueva sede con ID único
     */
    fun createSede(
        nombre: String,
        ubicacion: UbicacionCompleta,
        esPrincipal: Boolean = false
    ): SedeEmpresa {
        return SedeEmpresa(
            id = "sede_${System.currentTimeMillis()}_${UUID.randomUUID().toString().take(8)}",
            nombre = nombre,
            ubicacion = ubicacion,
            esPrincipal = esPrincipal,
            activa = true
        )
    }
    
    /**
     * Valida una sede completa
     */
    fun isValidSede(sede: SedeEmpresa): Boolean {
        return sede.id.isNotBlank() &&
               sede.nombre.isNotBlank() &&
               isValidLocation(sede.ubicacion)
    }
    
    // =========================================================================
    // ESTADÍSTICAS
    // =========================================================================
    
    data class LocationStats(
        val totalDepartamentos: Int,
        val totalProvincias: Int,
        val totalDistritos: Int
    )
    
    /**
     * Obtiene estadísticas del dataset
     */
    fun getLocationStats(): LocationStats {
        var totalProvincias = 0
        var totalDistritos = 0
        
        for (dep in departamentosPeru) {
            totalProvincias += dep.provincias.size
            for (prov in dep.provincias) {
                totalDistritos += prov.distritos.size
            }
        }
        
        return LocationStats(
            totalDepartamentos = departamentosPeru.size,
            totalProvincias = totalProvincias,
            totalDistritos = totalDistritos
        )
    }
}

