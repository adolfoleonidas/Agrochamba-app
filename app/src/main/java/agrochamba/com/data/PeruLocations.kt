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
 */
data class UbicacionCompleta(
    val departamento: String,
    val provincia: String,
    val distrito: String,
    val direccion: String? = null,
    val coordenadas: Coordenadas? = null,
    // Campos alternativos para compatibilidad con backend que guarda lat/lng directamente
    val lat: Double? = null,
    val lng: Double? = null
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
    fun formatForSedeSelector(): String = "$distrito, $provincia"
    
    /**
     * Formato en una línea completa
     */
    fun formatOneLine(includeDireccion: Boolean = false): String {
        val base = "$distrito, $provincia, $departamento"
        return if (includeDireccion && direccion != null) "$base - $direccion" else base
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
     */
    fun toUbicacionCompleta(): UbicacionCompleta? {
        return when (tipo) {
            LocationType.DISTRITO -> UbicacionCompleta(
                departamento = departamento,
                provincia = provincia ?: return null,
                distrito = distrito ?: return null
            )
            LocationType.PROVINCIA -> {
                // Devolver primera ubicación de esa provincia
                PeruLocations.resolveFromProvincia(provincia ?: return null, departamento)
            }
            LocationType.DEPARTAMENTO -> {
                // Devolver primera ubicación de ese departamento
                val dep = PeruLocations.departamentosPeru.find { it.departamento == departamento }
                if (dep != null && dep.provincias.isNotEmpty()) {
                    val prov = dep.provincias[0]
                    if (prov.distritos.isNotEmpty()) {
                        UbicacionCompleta(
                            departamento = dep.departamento,
                            provincia = prov.provincia,
                            distrito = prov.distritos[0]
                        )
                    } else null
                } else null
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
                Provincia("Chachapoyas", listOf("Chachapoyas", "Asunción", "Balsas", "Cheto", "Chiliquín", "Chuquibamba", "Granada", "Huancas", "La Jalca", "Leimebamba", "Levanto", "Magdalena", "Mariscal Castilla", "Molino", "Montevideo", "Olleros", "Quinjalca", "San Francisco de Daguas", "San Juan de Lopecancha", "Santa Leocadia", "Soloco", "Sonche")),
                Provincia("Bagua", listOf("Bagua", "Aramango", "Copacabana", "El Parco", "Imaza", "La Peca")),
                Provincia("Bongará", listOf("Jumbilla", "Chisquilla", "Corosha", "Cuenca", "Florida", "Jazán", "Recta", "San Carlos", "Shipasbamba", "Valera", "Yambrasbamba", "San Jerónimo")),
                Provincia("Condorcanqui", listOf("Nieva", "El Cenepa", "Río Santiago")),
                Provincia("Luya", listOf("Lámud", "Camporredondo", "Cocabamba", "Colcamar", "Conila", "Inguilpata", "Longuita", "Lonya Chico", "Luya Viejo", "María", "Ocalli", "San Cristóbal", "San Francisco del Yeso", "San Jerónimo", "San Juan de Lopecancha", "Santa Catalina", "Trita")),
                Provincia("Rodríguez de Mendoza", listOf("San Nicolás", "Chirimoto", "Cochamal", "Huambo", "Limabamba", "Longar", "Mariscal Benavides", "Milpuc", "Omia", "San Juan de Rioja", "Santa Rosa", "Totora", "Vista Alegre")),
                Provincia("Utcubamba", listOf("Bagua Grande", "Cajaruro", "Cumba", "El Milagro", "Jamalca", "Lonya Grande", "Yamon"))
            )
        ),
        Departamento(
            departamento = "Áncash",
            provincias = listOf(
                Provincia("Huaraz", listOf("Huaraz", "Cochabamba", "Colcabamba", "Independencia", "Jangas", "La Libertad", "Olleros", "Pampas Grande", "Pariacoto", "Pira", "Tarica")),
                Provincia("Aija", listOf("Aija", "Coris", "Huacllan", "La Merced", "Succha")),
                Provincia("Antonio Raimondi", listOf("Bello Horizonte", "Aczo", "Chaccho", "Chingas", "Mirgas", "San Juan de Rontoy")),
                Provincia("Asunción", listOf("Chacas", "Acochaca")),
                Provincia("Bolognesi", listOf("Chiquián", "Abelardo L. Rodríguez", "Antonio Raymondi", "Aquia", "Cajacay", "Canis", "Colquioc", "Huallanca", "Huasta", "Huayllacán", "Mangas", "Pacllón", "San Miguel de Corpanqui", "Ticllos")),
                Provincia("Carhuaz", listOf("Carhuaz", "Acopampa", "Amashca", "Anta", "Ataquero", "Marcara", "Pariahuanca", "San Miguel de Aco", "Shilla", "Tinco", "Yungar")),
                Provincia("Carlos Fermín Fitzcarrald", listOf("San Luis", "Yauya", "Piscobamba")),
                Provincia("Casma", listOf("Casma", "Buena Vista Alta", "Comandante Noel", "Yaután")),
                Provincia("Corongo", listOf("Corongo", "Aco", "Bambas", "Cusca", "La Pampa", "Yanac", "Yupan")),
                Provincia("Huarí", listOf("Huari", "Anra", "Cajay", "Chavín de Huántar", "Huacachi", "Huacchis", "Huantar", "Masin", "Paucas", "Ponto", "Raparayan", "San Marcos", "San Pedro de Chaná", "Uco")),
                Provincia("Huarmey", listOf("Huarmey", "Cochapeti", "Culebras", "Huayan", "Malvas", "Quian")),
                Provincia("Huaylas", listOf("Caraz", "Huallanca", "Huata", "Huaylas", "Mato", "Pamparomas", "Pueblo Libre", "Santa Cruz", "Santo Toribio", "Yuracmarca")),
                Provincia("Mariscal Luzuriaga", listOf("Piscobamba", "Casca", "Eleazar Guzmán Barrón", "Fidel Olivas Escudero", "Llama", "Llumpa", "Lucma", "Musga")),
                Provincia("Ocros", listOf("Ocros", "Acas", "Cangas", "Carhuapampa", "Cochas", "Congas", "Llipa", "San Cristóbal de Raján", "San Pedro", "Santiago de Chilcas")),
                Provincia("Pallasca", listOf("Cabana", "Bolognesi", "Conchucos", "Huacaschuque", "Huandoval", "Lacabamba", "Llapo", "Pallasca", "Pampas")),
                Provincia("Pomabamba", listOf("Pomabamba", "Huayllan", "Parobamba", "Quinuabamba")),
                Provincia("Recuay", listOf("Recuay", "Catac", "Cotaparaco", "Huayllapampa", "Llacllín", "Marca", "Pampas Chico", "Parcoy", "Tapacocha", "Ticapampa")),
                Provincia("Santa", listOf("Chimbote", "Cáceres del Perú", "Coishco", "Nepeña", "Samanco", "Santa", "Nuevo Chimbote")),
                Provincia("Sihuas", listOf("Sihuas", "Acobamba", "Alfonso Ugarte", "Cashapampa", "Chingalpo", "Huayllabamba", "Quiches", "Ragash", "San Juan", "Sicsibamba")),
                Provincia("Yungay", listOf("Yungay", "Cascapara", "Mancos", "Matacoto", "Quillo", "Ranrahirca", "Shupluy", "Yanama"))
            )
        ),
        Departamento(
            departamento = "Apurímac",
            provincias = listOf(
                Provincia("Abancay", listOf("Abancay", "Chacoche", "Circa", "Curahuasi", "Huanipaca", "Lambrama", "Pichirhua", "San Pedro de Cachora", "Tamburco")),
                Provincia("Andahuaylas", listOf("Andahuaylas", "Andarapa", "Chiara", "Huancarama", "Huancaray", "Huayana", "Kishuara", "Pacobamba", "Pacucha", "Pumaqucha", "San Antonio de Cachi", "San Jerónimo", "Talavera", "Turpo")),
                Provincia("Antabamba", listOf("Antabamba", "El Oro", "Huaquirca", "Juan Espinoza Medrano", "Oropesa", "Pachaconas", "Sabaino")),
                Provincia("Aymaraes", listOf("Chalhuanca", "Capaya", "Caraybamba", "Chapimarca", "Colcabamba", "Cotaruse", "Huayllo", "Lucre", "Pocohuanca", "Sañayca", "Soraya", "Tapairihua", "Tintay", "Toraya", "Yanaca")),
                Provincia("Cotabambas", listOf("Tambobamba", "Cotabambas", "Coyllurqui", "Haquira", "Mara", "Ollachea")),
                Provincia("Chincheros", listOf("Chincheros", "Anco-Huallo", "Cocharcas", "Huaccana", "Ocobamba", "Ongoy", "Uranmarca", "Ranracancha")),
                Provincia("Grau", listOf("Chuquibambilla", "Curpahuasi", "Huayllati", "Mamara", "Micaela Bastidas", "Pataypampa", "Progreso", "San Antonio", "Santa Rosa", "Turpay", "Vilcabamba"))
            )
        ),
        Departamento(
            departamento = "Arequipa",
            provincias = listOf(
                Provincia("Arequipa", listOf("Arequipa", "Alto Selva Alegre", "Cayma", "Cerro Colorado", "Characato", "Chiguata", "Hunter", "Jacobo Hunter", "José Luis Bustamante y Rivero", "La Joya", "Mariano Melgar", "Miraflores", "Mollebaya", "Paucarpata", "Pocsi", "Polobaya", "Quequeña", "Sabandía", "Sachaca", "San Juan de Siguas", "San Juan de Tarucani", "Santa Isabel de Siguas", "Socabaya", "Tiabaya", "Uchumayo", "Vítor", "Yanahuara", "Yura")),
                Provincia("Camaná", listOf("Camaná", "José María Quimper", "Mariano Nicolás Valcárcel", "Mariscal Cáceres", "Nicolás de Piérola", "Ocoña", "Quilca", "Samuel Pastor")),
                Provincia("Caravelí", listOf("Caravelí", "Acarí", "Atico", "Atiquipa", "Bella Unión", "Cahuacho", "Chala", "Chaparra", "Huanuhuanu", "Jaquí", "Lomas", "Quicacha", "Yauca")),
                Provincia("Castilla", listOf("Aplao", "Andagua", "Ayo", "Chachas", "Chilcaymarca", "Choco", "Huancarqui", "Machaguay", "Orcopampa", "Pampacolca", "Tipán", "Uñón", "Viraco")),
                Provincia("Caylloma", listOf("Chivay", "Achoma", "Cabanaconde", "Callalli", "Caylloma", "Coporaque", "Huambo", "Huanca", "Ichupampa", "Lari", "Lluta", "Maca", "Madrigal", "San Antonio de Chuca", "Sibayo", "Tapay", "Tisco", "Tuti", "Yanque")),
                Provincia("Condesuyos", listOf("Chuquibamba", "Andaray", "Cayarani", "Chichas", "Iray", "Río Grande", "Salamanca", "Yanaquihua")),
                Provincia("Islay", listOf("Mollendo", "Cocachacra", "Dean Valdivia", "Islay", "Mejía", "Punta de Bombón")),
                Provincia("La Unión", listOf("Cotahuasi", "Alca", "Charcana", "Huaynacotas", "Pampamarca", "Puyca", "Quechualla", "Sayla", "Tauría", "Tomepampa", "Toro"))
            )
        ),
        Departamento(
            departamento = "Ayacucho",
            provincias = listOf(
                Provincia("Huamanga", listOf("Ayacucho", "Acocro", "Acos Vinchos", "Carmen Alto", "Chiara", "Quinua", "San José de Ticllas", "San Juan Bautista", "Santiago de Pischa", "Socos", "Tambillo", "Vinchos")),
                Provincia("Cangallo", listOf("Cangallo", "Chuschi", "Los Morochucos", "María Parado de Bellido", "Morcolla", "Paras", "Totos")),
                Provincia("Fajardo", listOf("Huancapi", "Alcamenca", "Apongo", "Asquiparo", "Canaria", "Cayara", "Colca", "Huamanquiquia", "Huancaraylla", "Huaya", "Sarhua", "Vilcanchos")),
                Provincia("Huanca Sancos", listOf("Sancos", "Carapo", "Santiago de Lucanamarca", "Huayana")),
                Provincia("Huanta", listOf("Huanta", "Ayahuanco", "Huamanguilla", "Iguain", "Luricocha", "Santillana", "Sivia", "Llochegua")),
                Provincia("La Mar", listOf("San Miguel", "Anco", "Chilcas", "Chungui", "Luis Carranza", "Santa Rosa", "Tambo")),
                Provincia("Lucanas", listOf("Puquio", "Aucará", "Cabana", "Carmen Salcedo", "Chaviña", "Chipao", "Huac-Huas", "Laramate", "Leoncio Prado", "Llauta", "Lucanas", "Ocaña", "Otoca", "Saisa", "San Cristóbal", "San Juan", "San Pedro", "Santa Ana", "Santa Lucía", "Saurama", "Soras", "Subtanjalla", "Usquiche")),
                Provincia("Parinacochas", listOf("Coracora", "Chumpi", "Coronel Castañeda", "Pacapausa", "Pullo", "Puyusca", "San Francisco de Ravacayco", "Upahuacho")),
                Provincia("Páucar del Sara Sara", listOf("Pausa", "Colta", "Corculla", "Lampa", "Marcabamba", "Oyolo", "Pararca", "San Javier de Alpabamba", "San José de Ushua", "Sara Sara")),
                Provincia("Sucre", listOf("Querobamba", "Belén", "Chalcos", "Chilcayoc", "Huacaña", "Morcolla", "Paico", "San Salvador de Quije", "Santiago de Chocorvos", "Soras", "Huayllán")),
                Provincia("Víctor Fajardo", listOf("Huancapi", "Alcamenca", "Apongo", "Asquiparo", "Canaria", "Cayara", "Colca", "Huamanquiquia", "Huancaraylla", "Huaya", "Sarhua", "Vilcanchos"))
            )
        ),
        Departamento(
            departamento = "Cajamarca",
            provincias = listOf(
                Provincia("Cajamarca", listOf("Cajamarca", "Asunción", "Chetilla", "Cospan", "La Encañada", "Llacanora", "Los Baños del Inca", "Magdalena", "Matara", "Namora", "San Juan")),
                Provincia("Cajabamba", listOf("Cajabamba", "Cauday", "Condebamba", "Sitacocha")),
                Provincia("Celendín", listOf("Celendín", "Cortegana", "Huasmin", "Jorge Basadre", "José Gálvez", "Lucma", "Miguel Iglesias", "Oxamarca", "Sorochuco", "Sucre", "Utco")),
                Provincia("Chota", listOf("Chota", "Anguía", "Chadin", "Chiguirip", "Chimban", "Hualgayoc", "Lajas", "Llama", "Miracosta", "Paccha", "Pión", "Querocoto", "Tacabamba", "Tocapampa")),
                Provincia("Contumazá", listOf("Contumazá", "Chilete", "Cupisnique", "Guzmango", "San Benito", "Santa Cruz de Toledo", "Tantarica", "Yonan")),
                Provincia("Cutervo", listOf("Cutervo", "Callayuc", "Choros", "Cujillo", "La Ramada", "Pimpingos", "Querocotillo", "San Andrés de Cutervo", "San Juan de Cutervo", "Santa Cruz", "Santo Tomás")),
                Provincia("Hualgayoc", listOf("Bambamarca", "Chugur", "Hualgayoc")),
                Provincia("Jaén", listOf("Jaén", "Bellavista", "Chontali", "Colasay", "Huabal", "Las Pirias", "Pomahuaca", "Pucará", "Sallique", "San Felipe", "San José del Alto", "Santa Rosa")),
                Provincia("San Ignacio", listOf("San Ignacio", "Chirinos", "Huarango", "La Coipa", "Namballe", "San José de Lourdes", "Tabaconas")),
                Provincia("San Marcos", listOf("San Marcos", "Ichocán", "José Manuel Quiroz", "Paucamarca")),
                Provincia("San Miguel", listOf("San Miguel", "Bolívar", "Calquis", "Catilluc", "El Prado", "Llapa", "Nanchoc", "Niepos", "San Gregorio", "San Silvestre de Cochán", "Tongod", "Unión Agua Blanca")),
                Provincia("Santa Cruz", listOf("Santa Cruz", "Andabamba", "Catache", "Chancaybaños", "La Esperanza", "Ninabamba", "Pulan", "Yauyucan"))
            )
        ),
        Departamento(
            departamento = "Cusco",
            provincias = listOf(
                Provincia("Cusco", listOf("Cusco", "Ccorca", "Poroy", "San Jerónimo", "San Sebastián", "Santiago", "Saylla", "Wanchaq")),
                Provincia("Acomayo", listOf("Acomayo", "Acopia", "Acos", "Mosoc Llacta", "Pomacanchi", "Rondocan", "Sangarará")),
                Provincia("Anta", listOf("Anta", "Ancahuasi", "Cachimayo", "Chinchaypujio", "Huarocondo", "Limatambo", "Mollepata", "Pachar", "Zurite")),
                Provincia("Calca", listOf("Calca", "Coya", "Lamay", "Lares", "Pisac", "San Salvador", "Taray", "Yanatile")),
                Provincia("Canas", listOf("Yanaoca", "Checca", "Kunturkanki", "Langui", "Layo", "Pampamarca", "Quehue", "Tupac Amaru")),
                Provincia("Canchis", listOf("Sicuani", "Checacupe", "Combapata", "Marangani", "Pitumarca", "San Pablo", "San Pedro", "Tinta")),
                Provincia("Chumbivilcas", listOf("Santo Tomás", "Capacmarca", "Chamaca", "Colquemarca", "Livitaca", "Llusco", "Quiñota", "Velille")),
                Provincia("Espinar", listOf("Espinar", "Alto Pichigua", "Condoroma", "Coporaque", "Ocoruro", "Pallpata", "Pichigua", "Suyckutambo")),
                Provincia("La Convención", listOf("Santa Ana", "Echarate", "Huayopata", "Maranura", "Ocobamba", "Quellouno", "Kimbiri", "Santa Teresa", "Vilcabamba", "Pichari", "Inkawasi")),
                Provincia("Paruro", listOf("Paruro", "Accha", "Ccapi", "Colcha", "Huanoquite", "Omacha", "Paccaritambo")),
                Provincia("Paucartambo", listOf("Paucartambo", "Caicay", "Challabamba", "Colquepata", "Huancarani", "Kosñipata")),
                Provincia("Quispicanchi", listOf("Urcos", "Andahuaylillas", "Camanti", "Ccarhuayo", "Cusipata", "Huaro", "Lucre", "Marcapata", "Ocongate", "Quiquijana")),
                Provincia("Urubamba", listOf("Urubamba", "Chinchero", "Huayllabamba", "Machupicchu", "Maras", "Ollantaytambo", "Yucay"))
            )
        ),
        Departamento(
            departamento = "Huancavelica",
            provincias = listOf(
                Provincia("Huancavelica", listOf("Huancavelica", "Acobambilla", "Acoria", "Ascensión", "Conayca", "Cuenca", "Huachocolpa", "Huayllahuara", "Izcuchaca", "Laria", "Manta", "Mariscal Cáceres", "Moya", "Nuevo Occoro", "Palca", "Pilchaca", "Vilca", "Yauli")),
                Provincia("Acobamba", listOf("Acobamba", "Andabamba", "Anta", "Caja", "Marcas", "Paucará", "Pomacocha", "Rosario")),
                Provincia("Angaraes", listOf("Lircay", "Anchonga", "Callanmarca", "Chincho", "Congalla", "Huanca-Huanca", "Huayllay Grande", "Julcamarca", "San Antonio de Antaparco", "Santo Tomás de Pata")),
                Provincia("Castrovirreyna", listOf("Castrovirreyna", "Arma", "Aurahua", "Capillas", "Chupamarca", "Cocas", "Huachos", "Huamatambo", "Mollepampa", "San Juan", "Santa Ana", "Tantara", "Ticrapo")),
                Provincia("Churcampa", listOf("Churcampa", "Anco", "Chinchihuasi", "El Carmen", "La Merced", "Locroja", "Paucarbamba", "San Miguel de Mayocc", "San Pedro de Coris", "Pachamarca")),
                Provincia("Huaytará", listOf("Huaytará", "Ayahuanco", "Córdova", "Huayacundo Arma", "Laramarca", "Ocoyo", "Pilpichaca", "Querco", "Quito-Arma", "San Antonio de Cusicancha", "San Francisco de Sangayaico", "San Isidro", "Santiago de Chocorvos", "Santiago de Quirahuara", "Santo Domingo de Capillas", "Tambo")),
                Provincia("Tayacaja", listOf("Pampas", "Acostambo", "Acraquia", "Ahuaycha", "Colca", "Daniel Hernández", "Huachocolpa", "Huaribamba", "Ñahuimpuquio", "Pazos", "Quishuar", "Salcabamba", "Salcahuasi", "San Marcos de Rocchac", "Surcubamba", "Tintay Puncu"))
            )
        ),
        Departamento(
            departamento = "Huánuco",
            provincias = listOf(
                Provincia("Huánuco", listOf("Huánuco", "Amarilis", "Chinchao", "Churubamba", "Margos", "Pillco Marca", "Quisqui", "San Francisco de Cayrán", "San Pedro de Chaulán", "Santa María del Valle", "Yarumayo")),
                Provincia("Ambo", listOf("Ambo", "Cayna", "Colpas", "Conchamarca", "Huácar", "San Francisco", "San Rafael", "Tomay Kichwa")),
                Provincia("Dos de Mayo", listOf("La Unión", "Chuquis", "Marias", "Pachas", "Quivilla", "Ripan", "Shuypuy", "Sillapata", "Yanas")),
                Provincia("Huacaybamba", listOf("Huacaybamba", "Canchabamba", "Cochabamba", "Pinra")),
                Provincia("Huamalíes", listOf("Llata", "Arancay", "Chavín de Pariarca", "Jacas Grande", "Jircán", "Miraflores", "Monzón", "Punchao", "Puños", "Singa", "Tantamayo")),
                Provincia("Leoncio Prado", listOf("Rupa-Rupa", "Daniel Alomía Robles", "Hermilio Valdizán", "José Crespo y Castillo", "Luyando", "Mariano Dámaso Beraún", "Pucayacu")),
                Provincia("Marañón", listOf("Huacrachuco", "Cholón", "San Buenaventura")),
                Provincia("Pachitea", listOf("Panao", "Chaglla", "Molino", "Umari")),
                Provincia("Puerto Inca", listOf("Puerto Inca", "Codo del Pozuzo", "Honoria", "Tournavista", "Yuyapichis")),
                Provincia("Lauricocha", listOf("Jesús", "Baños", "Jivia", "Queropalca", "Rondos", "San Francisco de Asís", "San Miguel de Cauri")),
                Provincia("Yarowilca", listOf("Chavinillo", "Cahuac", "Chacabamba", "Aparicio Pomares", "Jacas Chico", "Obas", "Pampamarca", "Choras"))
            )
        ),
        Departamento(
            departamento = "Ica",
            provincias = listOf(
                Provincia("Ica", listOf("Ica", "La Tinguiña", "Los Aquijes", "Ocucaje", "Pachacútec", "Parcona", "Pueblo Nuevo", "Salas", "San José de Los Molinos", "San Juan Bautista", "Santiago", "Subtanjalla", "Tate", "Yauca del Rosario")),
                Provincia("Chincha", listOf("Chincha Alta", "Alto Larán", "Chavín", "Chincha Baja", "El Carmen", "Grocio Prado", "Pueblo Nuevo", "San Juan de Yanac", "San Pedro de Huacarpana", "Sunampe", "Tambo de Mora")),
                Provincia("Nazca", listOf("Nazca", "Changuillo", "El Ingenio", "Marcona", "Vista Alegre")),
                Provincia("Palpa", listOf("Palpa", "Llipata", "Río Grande", "Santa Cruz", "Tibillo")),
                Provincia("Pisco", listOf("Pisco", "Huancano", "Humay", "Independencia", "Paracas", "San Andrés", "San Clemente", "Túpac Amaru Inca"))
            )
        ),
        Departamento(
            departamento = "Junín",
            provincias = listOf(
                Provincia("Huancayo", listOf("Huancayo", "Carhuacallanga", "Chacapampa", "Chicche", "Chilca", "Chongos Alto", "Chupuro", "El Tambo", "Huacrapuquio", "Hualhuas", "Huancán", "Huasicancha", "Huayucachi", "Ingenio", "Pariahuanca", "Pilcomayo", "Pucará", "Quichuay", "Quilcas", "San Agustín", "San Jerónimo de Tunán", "Saño", "Santo Domingo de Acobamba", "Sapallanga", "Sicaya", "Viques")),
                Provincia("Concepción", listOf("Concepción", "Aco", "Andamarca", "Chambará", "Cochas", "Comas", "Heroínas Toledo", "Manzanares", "Mariscal Castilla", "Matahuasi", "Mito", "Nueve de Julio", "Orcotuna", "San José de Quero", "Santa Rosa de Ocopa")),
                Provincia("Chanchamayo", listOf("Chanchamayo", "Perené", "Pichanaqui", "San Luis de Shuaro", "San Ramón", "Vitoc")),
                Provincia("Jauja", listOf("Jauja", "Acolla", "Apata", "Ataura", "Canchayllo", "Curicaca", "El Mantaro", "Huamalí", "Huaripampa", "Huertas", "Janjaillo", "Julcán", "Leonor Ordóñez", "Llocllapampa", "Marco", "Masma", "Masma Chicche", "Molinos", "Monobamba", "Muqui", "Muquiyauyo", "Paca", "Paccha", "Pancán", "Parco", "Pomacancha", "Ricran", "San Lorenzo", "San Pedro de Chunán", "Sausa", "Sincos", "Tunan Marca", "Yauli", "Yauyos")),
                Provincia("Junín", listOf("Junín", "Carhuamayo", "Ondores", "Ulcumayo")),
                Provincia("Satipo", listOf("Satipo", "Coviriali", "Llaylla", "Mazamari", "Pampa Hermosa", "Pangoa", "Río Negro", "Río Tambo")),
                Provincia("Tarma", listOf("Tarma", "Acobamba", "Huaricolca", "Huasahuasi", "La Unión", "Palca", "Palcamayo", "San Pedro de Cajas", "Tapo")),
                Provincia("Yauli", listOf("La Oroya", "Chacapalpa", "Huay-Huay", "Marcapomacocha", "Morococha", "Paccha", "Santa Bárbara de Carhuacayán", "Santa Rosa de Sacco", "Suitucancha", "Yauli")),
                Provincia("Chupaca", listOf("Chupaca", "Áhuac", "Chongos Bajo", "Huachac", "Huamancaca Chico", "San Juan de Iscos", "San Juan de Jarpa", "Tres de Diciembre", "Yanacancha"))
            )
        ),
        Departamento(
            departamento = "La Libertad",
            provincias = listOf(
                Provincia("Trujillo", listOf("Trujillo", "El Porvenir", "Florencia de Mora", "Huanchaco", "La Esperanza", "Laredo", "Moche", "Poroto", "Salaverry", "Simbal", "Víctor Larco Herrera")),
                Provincia("Ascope", listOf("Ascope", "Chicama", "Chocope", "Magdalena de Cao", "Paiján", "Rázuri", "Santiago de Cao", "Casa Grande")),
                Provincia("Bolívar", listOf("Bolívar", "Bambamarca", "Condormarca", "Longotea", "Uchumarca", "Ucuncha")),
                Provincia("Chepén", listOf("Chepén", "Pacanga", "Pueblo Nuevo")),
                Provincia("Gran Chimú", listOf("Cascas", "Lucma", "Marmot", "Sayapullo")),
                Provincia("Julcán", listOf("Julcán", "Calamarca", "Carabamba", "Huaso")),
                Provincia("Otuzco", listOf("Otuzco", "Agallpampa", "Charat", "Huaranchal", "La Cuesta", "Mache", "Paranday", "Salpo", "Sinsicap", "Usquil")),
                Provincia("Pacasmayo", listOf("San Pedro de Lloc", "Guadalupe", "Jequetepeque", "Pacasmayo", "San José")),
                Provincia("Pataz", listOf("Tayabamba", "Buldibuyo", "Chillia", "Huancaspata", "Huaylillas", "Huayo", "Ongón", "Parcoy", "Pataz", "Pías", "Santiago de Challas", "Taurija", "Urpay")),
                Provincia("Sánchez Carrión", listOf("Huamachuco", "Chugay", "Cochorco", "Curgos", "Marcabal", "Sanagorán", "Sarín", "Sartimbamba")),
                Provincia("Santiago de Chuco", listOf("Santiago de Chuco", "Angasmarca", "Cachicadán", "Mollebamba", "Mollepata", "Quiruvilca", "Santa Cruz de Chuca", "Sitabamba")),
                Provincia("Virú", listOf("Virú", "Chao", "Guadalupito"))
            )
        ),
        Departamento(
            departamento = "Lambayeque",
            provincias = listOf(
                Provincia("Chiclayo", listOf("Chiclayo", "Cayaltí", "Chongoyape", "Eten", "Eten Puerto", "José Leonardo Ortiz", "La Victoria", "Lagunas", "Monsefú", "Nueva Arica", "Oyotún", "Picsi", "Pimentel", "Pomalca", "Pucalá", "Reque", "Santa Rosa", "Saña", "Tumán")),
                Provincia("Ferreñafe", listOf("Ferreñafe", "Cañaris", "Incahuasi", "Manuel Antonio Mesones Muro", "Pítipo", "Pueblo Nuevo")),
                Provincia("Lambayeque", listOf("Lambayeque", "Chóchope", "Íllimo", "Jayanca", "Mochumí", "Mórrope", "Motupe", "Olmos", "Pacora", "Salas", "San José", "Túcume"))
            )
        ),
        Departamento(
            departamento = "Lima",
            provincias = listOf(
                Provincia("Lima", listOf("Lima", "Ancón", "Ate", "Barranco", "Breña", "Carabayllo", "Chaclacayo", "Chorrillos", "Cieneguilla", "Comas", "El Agustino", "Independencia", "Jesús María", "La Molina", "La Victoria", "Lince", "Los Olivos", "Lurigancho-Chosica", "Lurin", "Magdalena del Mar", "Miraflores", "Pachacámac", "Pucusana", "Pueblo Libre", "Puente Piedra", "Punta Hermosa", "Punta Negra", "Rímac", "San Bartolo", "San Borja", "San Isidro", "San Juan de Lurigancho", "San Juan de Miraflores", "San Luis", "San Martín de Porres", "San Miguel", "Santa Anita", "Santa María del Mar", "Santa Rosa", "Santiago de Surco", "Surquillo", "Villa El Salvador", "Villa María del Triunfo")),
                Provincia("Barranca", listOf("Barranca", "Paramonga", "Pativilca", "Supe", "Supe Puerto")),
                Provincia("Cajatambo", listOf("Cajatambo", "Copa", "Gorgor", "Huancapón", "Manas")),
                Provincia("Canta", listOf("Canta", "Arahuay", "Huamantanga", "Huaros", "Lachaqui", "San Buenaventura", "Santa Rosa de Quives")),
                Provincia("Cañete", listOf("San Vicente de Cañete", "Asia", "Calango", "Cerro Azul", "Chilca", "Coayllo", "Imperial", "Lunahuaná", "Mala", "Nuevo Imperial", "Pacarán", "Quilmaná", "San Antonio", "San Luis", "Santa Cruz de Flores", "Zúñiga")),
                Provincia("Huaral", listOf("Huaral", "Atavillos Alto", "Atavillos Bajo", "Aucallama", "Chancay", "Ihuarí", "Lampián", "Pacaraos", "San Miguel de Acos", "Santa Cruz de Andamarca", "Sumbilca", "Veintisiete de Noviembre")),
                Provincia("Huarochirí", listOf("Matucana", "Antioquia", "Callahuanca", "Carampoma", "Chicla", "Cuenca", "Huachupampa", "Huanza", "Huarochirí", "Lahuaytambo", "Langa", "Laraos", "Mariatana", "Ricardo Palma", "San Andrés de Tupicocha", "San Antonio", "San Bartolomé", "San Damián", "San Juan de Iris", "San Juan de Tantaranche", "San Lorenzo de Quinti", "San Mateo", "San Mateo de Otao", "San Pedro de Casta", "San Pedro de Huancayre", "Sangallaya", "Santa Cruz de Cocachacra", "Santa Eulalia", "Santiago de Anchucaya", "Santiago de Tuna", "Santo Domingo de los Olleros", "Surco")),
                Provincia("Huaura", listOf("Huacho", "Ámbar", "Caleta de Carquín", "Checras", "Hualmay", "Huaura", "Leoncio Prado", "Paccho", "Santa Leonor", "Santa María", "Sayán", "Végueta")),
                Provincia("Oyón", listOf("Oyón", "Andajes", "Caujul", "Cochamarca", "Naván", "Pachangara")),
                Provincia("Yauyos", listOf("Yauyos", "Alis", "Ayauca", "Ayavirí", "Azángaro", "Cacra", "Carania", "Catahuasi", "Chocos", "Cochas", "Colonia", "Hongos", "Huampara", "Huancaya", "Huangáscar", "Huantán", "Huañec", "Laraos", "Lincha", "Madean", "Miraflores", "Omas", "Putinza", "Quinches", "Quinocay", "San Joaquín", "San Pedro de Pilas", "Tanta", "Tauripampa", "Tomas", "Viñac", "Vitis"))
            )
        ),
        Departamento(
            departamento = "Loreto",
            provincias = listOf(
                Provincia("Maynas", listOf("Iquitos", "Alto Nanay", "Fernando Lores", "Indiana", "Las Amazonas", "Mazán", "Napo", "Punchana", "Belén", "San Juan Bautista", "Torres Causana")),
                Provincia("Alto Amazonas", listOf("Yurimaguas", "Balsapuerto", "Jeberos", "Lagunas", "Santa Cruz", "Teniente César López Rojas")),
                Provincia("Loreto", listOf("Nauta", "Parinari", "Tigre", "Trompeteros", "Urarinas")),
                Provincia("Mariscal Ramón Castilla", listOf("Ramón Castilla", "Pebas", "Yavari", "San Pablo")),
                Provincia("Requena", listOf("Requena", "Alto Tapiche", "Capelo", "Emilio San Martín", "Maquia", "Puinahua", "Saquena", "Soplin", "Tapiche", "Jenaro Herrera", "Yaquerana")),
                Provincia("Ucayali", listOf("Contamana", "Inahuaya", "Padre Márquez", "Pampa Hermosa", "Sarayacu", "Vargas Guerra")),
                Provincia("Datem del Marañón", listOf("Barranca", "Cahuapanas", "Manseriche", "Morona", "Pastaza", "Andoas")),
                Provincia("Putumayo", listOf("Putumayo", "Rosa Panduro", "Teniente Manuel Clavero", "Yaguas"))
            )
        ),
        Departamento(
            departamento = "Madre de Dios",
            provincias = listOf(
                Provincia("Tambopata", listOf("Tambopata", "Inambari", "Las Piedras", "Laberinto")),
                Provincia("Manu", listOf("Manu", "Fitzcarrald", "Madre de Dios", "Huepetuhe")),
                Provincia("Tahuamanu", listOf("Iñapari", "Iberia", "Tahuamanu"))
            )
        ),
        Departamento(
            departamento = "Moquegua",
            provincias = listOf(
                Provincia("Mariscal Nieto", listOf("Moquegua", "Carumas", "Cuchumbaya", "Samegua", "San Cristóbal", "Torata")),
                Provincia("General Sánchez Cerro", listOf("Omate", "Chojata", "Coalaque", "Ichuña", "La Capilla", "Lloque", "Matalaque", "Puquina", "Quinistaquillas", "Ubinas", "Yunga")),
                Provincia("Ilo", listOf("Ilo", "El Algarrobal", "Pacocha"))
            )
        ),
        Departamento(
            departamento = "Pasco",
            provincias = listOf(
                Provincia("Pasco", listOf("Chaupimarca", "Huachón", "Huariaca", "Huayllay", "Ninacaca", "Pallanchacra", "Paucartambo", "San Francisco de Asís de Yarusyacán", "Simón Bolívar", "Ticlacayan", "Tinyahuarco", "Vicco", "Yanacancha")),
                Provincia("Daniel Alcides Carrión", listOf("Yanahuanca", "Chacayán", "Goyllarisquizga", "Paucar", "San Pedro de Pillao", "Santa Ana de Tusi", "Tapuc", "Vilcabamba")),
                Provincia("Oxapampa", listOf("Oxapampa", "Chontabamba", "Huancabamba", "Palcazu", "Pozuzo", "Puerto Bermúdez", "Villa Rica", "Constitución"))
            )
        ),
        Departamento(
            departamento = "Piura",
            provincias = listOf(
                Provincia("Piura", listOf("Piura", "Castilla", "Catacaos", "Cura Mori", "El Tallán", "La Arena", "La Unión", "Las Lomas", "Tambo Grande", "Veintiséis de Octubre")),
                Provincia("Ayabaca", listOf("Ayabaca", "Frías", "Jililí", "Lagunas", "Montero", "Pacaipampa", "Paimas", "Sapillica", "Sicchez", "Suyo")),
                Provincia("Huancabamba", listOf("Huancabamba", "Canchaque", "El Carmen de la Frontera", "Huarmaca", "Lalaquiz", "San Miguel de El Faique", "Sóndor", "Sondorillo")),
                Provincia("Morropón", listOf("Chulucanas", "Buenos Aires", "Chalaco", "La Matanza", "Morropón", "Salitral", "San Juan de Bigote", "Santa Catalina de Mossa", "Santo Domingo", "Yamango")),
                Provincia("Paita", listOf("Paita", "Amotape", "Colán", "El Arenal", "La Huaca", "Tamarindo", "Vichayal")),
                Provincia("Sullana", listOf("Sullana", "Bellavista", "Ignacio Escudero", "Lancones", "Marcavelica", "Miguel Checa", "Querecotillo", "Salitral")),
                Provincia("Talara", listOf("Pariñas", "El Alto", "La Brea", "Lobitos", "Los Órganos", "Máncora")),
                Provincia("Sechura", listOf("Sechura", "Bellavista de la Unión", "Bernal", "Cristo Nos Valga", "Rinconada-Llicuar", "Vice"))
            )
        ),
        Departamento(
            departamento = "Puno",
            provincias = listOf(
                Provincia("Puno", listOf("Puno", "Acora", "Amantaní", "Atuncolla", "Capachica", "Chucuito", "Coata", "Huata", "Mañazo", "Paucarcolla", "Pichacani", "Platería", "San Antonio", "Tiquillaca", "Vilque")),
                Provincia("Azángaro", listOf("Azángaro", "Achaya", "Arapa", "Asillo", "Caminaca", "Chupa", "José Domingo Choquehuanca", "Muñani", "Potoni", "Saman", "San Antón", "San José", "San Juan de Salinas", "Santiago de Pupuja", "Tirapata")),
                Provincia("Carabaya", listOf("Macusani", "Ajoyani", "Ayapata", "Coasa", "Corani", "Crucero", "Ituata", "Ollachea", "San Gabán", "Usicayos")),
                Provincia("Chucuito", listOf("Juli", "Desaguadero", "Huacullani", "Kelluyo", "Pisacoma", "Pomata", "Zepita")),
                Provincia("El Collao", listOf("Ilave", "Capazo", "Pilcuyo", "Santa Rosa", "Conduriri")),
                Provincia("Huancané", listOf("Huancané", "Cojata", "Huatasani", "Inchupalla", "Pusi", "Rosaspata", "Taraco", "Vilque Chico")),
                Provincia("Lampa", listOf("Lampa", "Cabanilla", "Calapuja", "Nicasio", "Ocuviri", "Palca", "Paratía", "Pucará", "Santa Lucía", "Vilavila")),
                Provincia("Melgar", listOf("Ayaviri", "Antauta", "Cupi", "Llalli", "Macari", "Nuñoa", "Orurillo", "Santa Rosa", "Umachiri")),
                Provincia("Moho", listOf("Moho", "Conima", "Huayrapata", "Tilali")),
                Provincia("San Antonio de Putina", listOf("Putina", "Ananea", "Pedro Vilca Apaza", "Quilcapuncu", "Sina")),
                Provincia("San Román", listOf("Juliaca", "Cabana", "Cabanillas", "Caracoto", "San Miguel")),
                Provincia("Sandia", listOf("Sandia", "Cuyocuyo", "Limbani", "Patambuco", "Phara", "Quiaca", "San Juan del Oro", "Yanahuaya", "Alto Inambari", "San Pedro de Putina Punco")),
                Provincia("Yunguyo", listOf("Yunguyo", "Anapia", "Copani", "Cuturapi", "Ollaraya", "Tinicachi", "Unicachi"))
            )
        ),
        Departamento(
            departamento = "San Martín",
            provincias = listOf(
                Provincia("Moyobamba", listOf("Moyobamba", "Calzada", "Habana", "Jepelacio", "Soritor", "Yantalo")),
                Provincia("Bellavista", listOf("Bellavista", "Alto Biavo", "Bajo Biavo", "Huallaga", "San Pablo", "San Rafael")),
                Provincia("El Dorado", listOf("San José de Sisa", "Agua Blanca", "San Martín", "Santa Rosa", "Shatoja")),
                Provincia("Huallaga", listOf("Saposoa", "Alto Saposoa", "El Eslabón", "Piscoyacu", "Sacanche", "Tingo de Saposoa")),
                Provincia("Lamas", listOf("Lamas", "Alonso de Alvarado", "Barranquita", "Caynarachi", "Cuñumbuqui", "Pinto Recodo", "Rumisapa", "San Roque de Cumbaza", "Shanao", "Tabalosos", "Zapatero")),
                Provincia("Mariscal Cáceres", listOf("Juanjuí", "Campanilla", "Huicungo", "Pachiza", "Pajarillo")),
                Provincia("Picota", listOf("Picota", "Buenos Aires", "Caspisapa", "Pilluana", "Pucacaca", "San Cristóbal", "San Hilarión", "Shamboyacu", "Tingo de Ponasa", "Tres Unidos")),
                Provincia("Rioja", listOf("Rioja", "Awajun", "Elías Soplín Vargas", "Nueva Cajamarca", "Pardo Miguel", "Posic", "San Fernando", "Yorongos", "Yuracyacu")),
                Provincia("San Martín", listOf("Tarapoto", "Alberto Leveau", "Cacatachi", "Chazuta", "Chipurana", "El Porvenir", "Huimbayoc", "Juan Guerra", "La Banda de Shilcayo", "Morales", "Papaplaya", "San Antonio", "Sauce", "Shapaja")),
                Provincia("Tocache", listOf("Tocache", "Nuevo Progreso", "Pólvora", "Shunte", "Uchiza"))
            )
        ),
        Departamento(
            departamento = "Tacna",
            provincias = listOf(
                Provincia("Tacna", listOf("Tacna", "Alto de la Alianza", "Calana", "Ciudad Nueva", "Coronel Gregorio Albarracín Lanchipa", "Inclán", "Pachía", "Palca", "Pocollay", "Sama")),
                Provincia("Candarave", listOf("Candarave", "Cairani", "Camilaca", "Curibaya", "Huanuara", "Quilahuani")),
                Provincia("Jorge Basadre", listOf("Locumba", "Ilabaya", "Ite")),
                Provincia("Tarata", listOf("Tarata", "Chucatamani", "Estique", "Estique-Pampa", "Sitajara", "Susapaya", "Tarucachi", "Ticaco"))
            )
        ),
        Departamento(
            departamento = "Tumbes",
            provincias = listOf(
                Provincia("Tumbes", listOf("Tumbes", "Corrales", "La Cruz", "Pampas de Hospital", "San Jacinto", "San Juan de la Virgen")),
                Provincia("Contralmirante Villar", listOf("Zorritos", "Casitas", "Canoas de Punta Sal")),
                Provincia("Zarumilla", listOf("Zarumilla", "Aguas Verdes", "Matapalo", "Papayal"))
            )
        ),
        Departamento(
            departamento = "Ucayali",
            provincias = listOf(
                Provincia("Coronel Portillo", listOf("Callería", "Campoverde", "Iparia", "Masisea", "Yarinacocha", "Nueva Requena", "Manantay")),
                Provincia("Atalaya", listOf("Raymondi", "Sepahua", "Tahuanía", "Yurúa")),
                Provincia("Padre Abad", listOf("Padre Abad", "Irazola", "Curimaná", "Neshuya", "Alexander Von Humboldt")),
                Provincia("Purús", listOf("Purús"))
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

