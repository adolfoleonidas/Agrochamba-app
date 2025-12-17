# 🏢 App Móvil: Selector de Empresas al Publicar Trabajo

## Objetivo

Agregar un selector de **Empresas** al mismo nivel visual y funcional que el selector de **Ubicación** en el formulario de publicación de trabajos.

---

## 📐 Diseño Visual

### Layout del Formulario

```
┌─────────────────────────────────────────┐
│ PUBLICAR TRABAJO                         │
├─────────────────────────────────────────┤
│                                         │
│ Título del Trabajo                      │
│ [___________________________________]    │
│                                         │
│ Descripción                             │
│ [___________________________________]    │
│ [___________________________________]    │
│                                         │
│ ┌─────────────────┐ ┌───────────────┐  │
│ │ 📍 Ubicación    │ │ 🏢 Empresa    │  │ ← MISMO NIVEL
│ │ [Lima         ▼]│ │ [AgroFresh  ▼]│  │
│ └─────────────────┘ └───────────────┘  │
│                                         │
│ Salario                                 │
│ Min: [___]  Max: [___]                  │
│                                         │
│ Vacantes: [___]                         │
│                                         │
│ 💬 Permitir comentarios        [ON ]    │
│                                         │
│ [        Publicar Trabajo        ]      │
└─────────────────────────────────────────┘
```

**Características:**
- ✅ Ubicación y Empresa en la **misma fila**
- ✅ Mismo tamaño y altura
- ✅ Mismo estilo visual (dropdowns)
- ✅ Íconos distintivos (📍 para ubicación, 🏢 para empresa)

---

## 🔌 API Endpoints

### 1. Obtener Lista de Empresas

**Endpoint:** `GET /wp-json/wp/v2/empresa`

Este es el endpoint estándar de WordPress para la taxonomía `empresa`.

**Petición:**
```http
GET https://agrochamba.com/wp-json/wp/v2/empresa?per_page=100&hide_empty=true
```

**Parámetros:**
- `per_page`: Número de resultados (default: 10, máximo: 100)
- `hide_empty`: Solo empresas con trabajos asociados (default: false)
- `search`: Buscar empresas por nombre

**Respuesta:**
```json
[
  {
    "id": 5,
    "name": "AgroFresh S.A.",
    "slug": "agrofresh-sa",
    "description": "",
    "count": 12
  },
  {
    "id": 8,
    "name": "Cultivos del Norte",
    "slug": "cultivos-del-norte",
    "description": "",
    "count": 8
  },
  {
    "id": 12,
    "name": "Exportadora Lima",
    "slug": "exportadora-lima",
    "description": "",
    "count": 5
  }
]
```

**Campos importantes:**
- `id`: ID de la empresa (enviar este valor al crear trabajo)
- `name`: Nombre de la empresa (mostrar en el dropdown)
- `slug`: Slug URL
- `count`: Número de trabajos asociados

---

### 2. Crear Trabajo con Empresa

**Endpoint:** `POST /wp-json/agrochamba/v1/jobs`

**Parámetros:**
```json
{
  "title": "Cosecha de Café",
  "content": "Descripción del trabajo...",
  "ubicacion_id": 5,           // ID de ubicación
  "empresa_id": 8,              // ← ID de empresa (NUEVO)
  "salario_min": 50,
  "salario_max": 80,
  "vacantes": 10,
  "comentarios_habilitados": true
}
```

---

## 📱 Implementación en la App

### Paso 1: Crear Modelo de Empresa

```kotlin
// Android - Kotlin
data class Empresa(
    val id: Int,
    val name: String,
    val slug: String,
    val count: Int
)
```

```swift
// iOS - Swift
struct Empresa: Codable {
    let id: Int
    let name: String
    let slug: String
    let count: Int
}
```

```javascript
// React Native
interface Empresa {
  id: number;
  name: string;
  slug: string;
  count: number;
}
```

---

### Paso 2: Cargar Lista de Empresas

```kotlin
// Android - Kotlin
class JobPublicationViewModel : ViewModel() {
    private val _empresas = MutableStateFlow<List<Empresa>>(emptyList())
    val empresas: StateFlow<List<Empresa>> = _empresas
    
    fun loadEmpresas() {
        viewModelScope.launch {
            try {
                val response = apiService.getEmpresas(
                    perPage = 100,
                    hideEmpty = true
                )
                _empresas.value = response
            } catch (e: Exception) {
                Log.e("JobPublication", "Error cargando empresas", e)
            }
        }
    }
}

// API Service
interface ApiService {
    @GET("wp/v2/empresa")
    suspend fun getEmpresas(
        @Query("per_page") perPage: Int = 100,
        @Query("hide_empty") hideEmpty: Boolean = true
    ): List<Empresa>
}
```

```swift
// iOS - Swift
class JobPublicationViewModel: ObservableObject {
    @Published var empresas: [Empresa] = []
    
    func loadEmpresas() {
        let url = URL(string: "https://agrochamba.com/wp-json/wp/v2/empresa?per_page=100&hide_empty=true")!
        
        URLSession.shared.dataTask(with: url) { data, response, error in
            guard let data = data else { return }
            
            do {
                let empresas = try JSONDecoder().decode([Empresa].self, from: data)
                DispatchQueue.main.async {
                    self.empresas = empresas
                }
            } catch {
                print("Error decoding empresas: \(error)")
            }
        }.resume()
    }
}
```

```javascript
// React Native
const useEmpresas = () => {
  const [empresas, setEmpresas] = useState<Empresa[]>([]);
  const [loading, setLoading] = useState(false);
  
  const loadEmpresas = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        'https://agrochamba.com/wp-json/wp/v2/empresa?per_page=100&hide_empty=true'
      );
      const data = await response.json();
      setEmpresas(data);
    } catch (error) {
      console.error('Error loading empresas:', error);
    } finally {
      setLoading(false);
    }
  };
  
  useEffect(() => {
    loadEmpresas();
  }, []);
  
  return { empresas, loading };
};
```

---

### Paso 3: UI - Selector de Empresa

```kotlin
// Android - Kotlin (Jetpack Compose)
@Composable
fun PublishJobScreen(
    viewModel: JobPublicationViewModel = viewModel()
) {
    val empresas by viewModel.empresas.collectAsState()
    val ubicaciones by viewModel.ubicaciones.collectAsState()
    
    var selectedEmpresa by remember { mutableStateOf<Empresa?>(null) }
    var selectedUbicacion by remember { mutableStateOf<Ubicacion?>(null) }
    
    LaunchedEffect(Unit) {
        viewModel.loadEmpresas()
        viewModel.loadUbicaciones()
    }
    
    Column(modifier = Modifier.padding(16.dp)) {
        // Título, Descripción, etc...
        
        // ROW: Ubicación y Empresa al mismo nivel
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            // Selector de Ubicación
            DropdownField(
                modifier = Modifier.weight(1f),
                label = "📍 Ubicación",
                items = ubicaciones,
                selectedItem = selectedUbicacion,
                onItemSelected = { selectedUbicacion = it },
                itemLabel = { it.name }
            )
            
            // Selector de Empresa
            DropdownField(
                modifier = Modifier.weight(1f),
                label = "🏢 Empresa",
                items = empresas,
                selectedItem = selectedEmpresa,
                onItemSelected = { selectedEmpresa = it },
                itemLabel = { it.name }
            )
        }
        
        // Salario, Vacantes, etc...
    }
}
```

```swift
// iOS - Swift (SwiftUI)
struct PublishJobView: View {
    @StateObject var viewModel = JobPublicationViewModel()
    
    @State private var selectedEmpresa: Empresa?
    @State private var selectedUbicacion: Ubicacion?
    
    var body: some View {
        Form {
            Section {
                // Título, Descripción, etc...
            }
            
            Section {
                // ROW: Ubicación y Empresa al mismo nivel
                HStack(spacing: 12) {
                    // Selector de Ubicación
                    Picker("📍 Ubicación", selection: $selectedUbicacion) {
                        Text("Seleccionar").tag(nil as Ubicacion?)
                        ForEach(viewModel.ubicaciones, id: \.id) { ubicacion in
                            Text(ubicacion.name).tag(ubicacion as Ubicacion?)
                        }
                    }
                    .frame(maxWidth: .infinity)
                    
                    // Selector de Empresa
                    Picker("🏢 Empresa", selection: $selectedEmpresa) {
                        Text("Seleccionar").tag(nil as Empresa?)
                        ForEach(viewModel.empresas, id: \.id) { empresa in
                            Text(empresa.name).tag(empresa as Empresa?)
                        }
                    }
                    .frame(maxWidth: .infinity)
                }
            }
            
            Section {
                // Salario, Vacantes, etc...
            }
        }
        .onAppear {
            viewModel.loadEmpresas()
            viewModel.loadUbicaciones()
        }
    }
}
```

```javascript
// React Native
import { Picker } from '@react-native-picker/picker';

const PublishJobScreen = () => {
  const { empresas } = useEmpresas();
  const { ubicaciones } = useUbicaciones();
  
  const [selectedEmpresa, setSelectedEmpresa] = useState(null);
  const [selectedUbicacion, setSelectedUbicacion] = useState(null);
  
  return (
    <ScrollView style={styles.container}>
      {/* Título, Descripción, etc... */}
      
      {/* ROW: Ubicación y Empresa al mismo nivel */}
      <View style={styles.row}>
        {/* Selector de Ubicación */}
        <View style={styles.halfWidth}>
          <Text style={styles.label}>📍 Ubicación</Text>
          <Picker
            selectedValue={selectedUbicacion}
            onValueChange={setSelectedUbicacion}
            style={styles.picker}
          >
            <Picker.Item label="Seleccionar" value={null} />
            {ubicaciones.map(ubicacion => (
              <Picker.Item
                key={ubicacion.id}
                label={ubicacion.name}
                value={ubicacion.id}
              />
            ))}
          </Picker>
        </View>
        
        {/* Selector de Empresa */}
        <View style={styles.halfWidth}>
          <Text style={styles.label}>🏢 Empresa</Text>
          <Picker
            selectedValue={selectedEmpresa}
            onValueChange={setSelectedEmpresa}
            style={styles.picker}
          >
            <Picker.Item label="Seleccionar" value={null} />
            {empresas.map(empresa => (
              <Picker.Item
                key={empresa.id}
                label={empresa.name}
                value={empresa.id}
              />
            ))}
          </Picker>
        </View>
      </View>
      
      {/* Salario, Vacantes, etc... */}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    marginVertical: 8,
  },
  halfWidth: {
    flex: 1,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
  },
  picker: {
    height: 50,
    borderWidth: 1,
    borderColor: '#e0e0e0',
    borderRadius: 8,
  },
});
```

---

### Paso 4: Enviar al Crear Trabajo

```kotlin
// Android - Kotlin
val jobData = JSONObject().apply {
    put("title", titulo)
    put("content", descripcion)
    put("ubicacion_id", selectedUbicacion?.id)
    put("empresa_id", selectedEmpresa?.id)        // ← Agregar empresa_id
    put("salario_min", salarioMin)
    put("salario_max", salarioMax)
    put("vacantes", vacantes)
    put("comentarios_habilitados", comentariosHabilitados)
}

val response = apiService.createJob(jobData)
```

```swift
// iOS - Swift
let jobData: [String: Any] = [
    "title": titulo,
    "content": descripcion,
    "ubicacion_id": selectedUbicacion?.id,
    "empresa_id": selectedEmpresa?.id,          // ← Agregar empresa_id
    "salario_min": salarioMin,
    "salario_max": salarioMax,
    "vacantes": vacantes,
    "comentarios_habilitados": comentariosHabilitados
]

apiService.createJob(jobData)
```

```javascript
// React Native
const jobData = {
  title: titulo,
  content: descripcion,
  ubicacion_id: selectedUbicacion,
  empresa_id: selectedEmpresa,                  // ← Agregar empresa_id
  salario_min: salarioMin,
  salario_max: salarioMax,
  vacantes: vacantes,
  comentarios_habilitados: comentariosHabilitados,
};

await apiService.createJob(jobData);
```

---

## 🎨 Estilos y UX

### Recomendaciones de Diseño

1. **Tamaño:** Ambos selectores deben tener el mismo tamaño
2. **Altura:** Misma altura (48-56dp/pt)
3. **Margen:** Espaciado uniforme entre ellos (8-12dp/pt)
4. **Ícono:** Usar íconos distintivos:
   - 📍 para Ubicación
   - 🏢 para Empresa
5. **Placeholder:** Texto claro:
   - "Seleccionar ubicación"
   - "Seleccionar empresa"
6. **Validación:** Ambos campos deben ser **requeridos**

### Estados del Selector

```
┌──────────────────────┐
│ 🏢 Empresa          ▼│  ← Sin seleccionar (gris claro)
└──────────────────────┘

┌──────────────────────┐
│ 🏢 AgroFresh S.A.   ▼│  ← Seleccionado (verde suave)
└──────────────────────┘

┌──────────────────────┐
│ 🏢 [Buscando...]     │  ← Cargando (spinner)
└──────────────────────┘

┌──────────────────────┐
│ 🏢 Empresa          ▼│  ← Error (borde rojo)
│ ⚠️ Campo requerido   │
└──────────────────────┘
```

---

## 🔄 Comportamiento

### Carga Inicial
```
1. App abre formulario de publicación
2. Carga automática de empresas (GET /wp/v2/empresa)
3. Carga automática de ubicaciones (GET /wp/v2/ubicacion)
4. Muestra selectores con datos
```

### Validación
```
Al presionar "Publicar":
1. Verificar que empresa_id no sea null
2. Si es null, mostrar error: "Selecciona una empresa"
3. Si es válido, proceder con la publicación
```

### Cache (Opcional pero Recomendado)
```
// Guardar en cache por 1 hora
- Empresas cambian raramente
- Reducir peticiones al servidor
- Mejorar velocidad de carga
```

---

## 🧪 Testing

### Test 1: Cargar Empresas

```bash
curl "https://agrochamba.com/wp-json/wp/v2/empresa?per_page=100&hide_empty=true"
```

**Resultado esperado:**
```json
[
  {
    "id": 5,
    "name": "AgroFresh S.A.",
    ...
  },
  ...
]
```

### Test 2: Publicar con Empresa

```bash
curl -X POST "https://agrochamba.com/wp-json/agrochamba/v1/jobs" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "title": "Test Empresa",
    "ubicacion_id": 5,
    "empresa_id": 8
  }'
```

**Resultado esperado:** Trabajo creado con empresa asociada

---

## ✅ Checklist de Implementación

- [ ] Crear modelo `Empresa` en la app
- [ ] Implementar llamada GET a `/wp/v2/empresa`
- [ ] Agregar selector de empresa en el formulario
- [ ] Posicionar selector **al mismo nivel** que ubicación
- [ ] Aplicar mismo estilo visual a ambos selectores
- [ ] Agregar validación (empresa requerida)
- [ ] Enviar `empresa_id` al crear trabajo
- [ ] Probar crear trabajo con empresa
- [ ] Verificar en sitio web que la empresa se asoció correctamente

---

## 📐 Wireframe Completo

```
┌─────────────────────────────────────────────┐
│  PUBLICAR TRABAJO                           │
│  ┌─┐                                        │
│  └─┘  Volver                                │
├─────────────────────────────────────────────┤
│                                             │
│  Título *                                   │
│  ┌─────────────────────────────────────┐   │
│  │ Ej: Cosecha de Café en Chanchamayo  │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  Descripción *                              │
│  ┌─────────────────────────────────────┐   │
│  │ Describe el trabajo...               │   │
│  │                                      │   │
│  │                                      │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌──────────────────┐ ┌──────────────────┐ │
│  │ 📍 Ubicación *  ▼│ │ 🏢 Empresa *    ▼│ │
│  │ Lima             │ │ AgroFresh S.A.   │ │
│  └──────────────────┘ └──────────────────┘ │
│                                             │
│  Salario (PEN/día)                          │
│  ┌────────┐          ┌────────┐            │
│  │ Min: 50│          │ Max: 80│            │
│  └────────┘          └────────┘            │
│                                             │
│  Vacantes *                                 │
│  ┌─────────────────────────────────────┐   │
│  │ 10                                   │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │ 💬 Permitir comentarios       [ON ] │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │       📤 PUBLICAR TRABAJO            │   │
│  └─────────────────────────────────────┘   │
│                                             │
└─────────────────────────────────────────────┘
```

---

**Última actualización:** Diciembre 2025  
**Versión:** 1.0

