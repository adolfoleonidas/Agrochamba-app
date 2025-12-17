# 💬 API: Control de Comentarios en Trabajos

## Cambios Implementados

Se ha agregado la funcionalidad para controlar si los comentarios están habilitados o deshabilitados en cada trabajo publicado.

---

## 🎯 Comportamiento

### Por Defecto
- ✅ Los comentarios están **ACTIVADOS** por defecto
- ✅ Si no se especifica el parámetro, los comentarios quedan habilitados automáticamente

### Control Manual
- ✅ El usuario (admin o empresa) puede **desactivar** los comentarios al publicar
- ✅ El usuario puede **activar/desactivar** los comentarios al editar un trabajo existente

---

## 📡 API Endpoint

### POST `/wp-json/agrochamba/v1/jobs` - Crear Trabajo

**Nuevo parámetro:**

```json
{
  "title": "Título del trabajo",
  "content": "Descripción...",
  "comentarios_habilitados": true,  // ← NUEVO (opcional, default: true)
  // ... otros parámetros existentes
}
```

### Valores Aceptados

| Valor | Tipo | Resultado |
|-------|------|-----------|
| `true` | Boolean | Comentarios **habilitados** |
| `false` | Boolean | Comentarios **deshabilitados** |
| No enviado | - | Comentarios **habilitados** (por defecto) |

---

## 📱 Implementación en la App Móvil

### Paso 1: Agregar Switch/Toggle en el Formulario

En el formulario de publicación de trabajo de la app móvil, agrega un switch para controlar los comentarios:

```kotlin
// Ejemplo en Kotlin (Android)
var comentariosHabilitados by remember { mutableStateOf(true) } // Por defecto: true

SwitchRow(
    label = "Permitir comentarios",
    checked = comentariosHabilitados,
    onCheckedChange = { comentariosHabilitados = it }
)
```

```swift
// Ejemplo en Swift (iOS)
@State private var comentariosHabilitados = true // Por defecto: true

Toggle("Permitir comentarios", isOn: $comentariosHabilitados)
```

```javascript
// Ejemplo en React Native
const [comentariosHabilitados, setComentariosHabilitados] = useState(true); // Por defecto: true

<View>
  <Text>Permitir comentarios</Text>
  <Switch
    value={comentariosHabilitados}
    onValueChange={setComentariosHabilitados}
  />
</View>
```

### Paso 2: Enviar el Valor en la Petición

Al hacer POST para crear el trabajo, incluir el parámetro:

```kotlin
// Android - Kotlin
val jobData = JSONObject().apply {
    put("title", titulo)
    put("content", contenido)
    put("comentarios_habilitados", comentariosHabilitados) // ← Agregar esto
    // ... otros campos
}
```

```swift
// iOS - Swift
let jobData: [String: Any] = [
    "title": titulo,
    "content": contenido,
    "comentarios_habilitados": comentariosHabilitados, // ← Agregar esto
    // ... otros campos
]
```

```javascript
// React Native
const jobData = {
  title: titulo,
  content: contenido,
  comentarios_habilitados: comentariosHabilitados, // ← Agregar esto
  // ... otros campos
};
```

### Paso 3: Actualizar Trabajos Existentes

Para editar un trabajo y cambiar el estado de comentarios:

**PUT** `/wp-json/agrochamba/v1/jobs/{id}`

```json
{
  "comentarios_habilitados": false  // Desactivar comentarios
}
```

---

## 🎨 Diseño Sugerido para la App

### Opción 1: Switch Simple

```
┌─────────────────────────────────────┐
│ Permitir comentarios        [ON ]  │
└─────────────────────────────────────┘
```

### Opción 2: Card con Descripción

```
┌─────────────────────────────────────┐
│ Comentarios                         │
│ Permite que los usuarios comenten   │
│ sobre esta oferta de trabajo        │
│                             [ON ]   │
└─────────────────────────────────────┘
```

### Opción 3: Sección Expandible

```
┌─────────────────────────────────────┐
│ ▼ Configuración de Comentarios      │
│                                     │
│   Permitir comentarios      [ON ]   │
│   Los usuarios podrán comentar y    │
│   hacer preguntas sobre el trabajo  │
└─────────────────────────────────────┘
```

---

## 🧪 Ejemplos de Peticiones

### Ejemplo 1: Crear Trabajo CON Comentarios (Default)

```bash
curl -X POST "https://agrochamba.com/wp-json/agrochamba/v1/jobs" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "title": "Cosecha de Café",
    "content": "Buscamos personal para la cosecha...",
    "ubicacion_id": 5,
    "salario_min": 50,
    "vacantes": 10
  }'
```
**Resultado:** Comentarios habilitados ✅ (por defecto)

### Ejemplo 2: Crear Trabajo SIN Comentarios

```bash
curl -X POST "https://agrochamba.com/wp-json/agrochamba/v1/jobs" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "title": "Cosecha de Café",
    "content": "Buscamos personal para la cosecha...",
    "ubicacion_id": 5,
    "salario_min": 50,
    "vacantes": 10,
    "comentarios_habilitados": false
  }'
```
**Resultado:** Comentarios deshabilitados ❌

### Ejemplo 3: Actualizar Trabajo para Desactivar Comentarios

```bash
curl -X PUT "https://agrochamba.com/wp-json/agrochamba/v1/jobs/123" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "comentarios_habilitados": false
  }'
```
**Resultado:** Comentarios deshabilitados ❌

### Ejemplo 4: Actualizar Trabajo para Activar Comentarios

```bash
curl -X PUT "https://agrochamba.com/wp-json/agrochamba/v1/jobs/123" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "comentarios_habilitados": true
  }'
```
**Resultado:** Comentarios habilitados ✅

---

## 📋 Respuesta de la API

Al crear un trabajo, la respuesta incluye el estado de comentarios:

```json
{
  "success": true,
  "message": "Trabajo creado exitosamente.",
  "job_id": 123,
  "job_url": "https://agrochamba.com/trabajos/lima/cosecha-de-cafe",
  "status": "pending",
  "comment_status": "open"  // ← "open" = habilitado, "closed" = deshabilitado
}
```

---

## 🔄 Flujo Completo en la App

### Al Publicar un Trabajo:

```
1. Usuario completa formulario
2. Usuario ve switch "Permitir comentarios" (ACTIVADO por defecto)
3. Usuario puede desactivarlo si lo desea
4. Usuario presiona "Publicar"
5. App envía petición con comentarios_habilitados: true/false
6. API crea el trabajo con el estado de comentarios configurado
7. App muestra mensaje de éxito
```

### Al Editar un Trabajo:

```
1. Usuario abre trabajo existente
2. App carga estado actual de comentarios
3. Usuario cambia el switch
4. Usuario guarda cambios
5. App envía petición PUT con nuevo valor
6. API actualiza el estado de comentarios
7. App muestra mensaje de éxito
```

---

## 🎯 Validación y Lógica

### En el Backend (WordPress)

1. **Por defecto:** `comment_status = 'open'`
2. **Si se envía `comentarios_habilitados: false`:** `comment_status = 'closed'`
3. **Si se envía `comentarios_habilitados: true`:** `comment_status = 'open'`
4. **Si no se envía:** `comment_status = 'open'` (por defecto)

### Comportamiento en el Sitio Web

- **Comentarios habilitados:** Los usuarios pueden comentar en la página del trabajo
- **Comentarios deshabilitados:** No se muestra el formulario de comentarios

---

## 🔧 Almacenamiento

El estado de comentarios se guarda en dos lugares:

1. **`wp_posts.comment_status`** (campo nativo de WordPress)
   - Valores: `'open'` o `'closed'`
   
2. **`wp_postmeta`** (meta field personalizado)
   - Key: `comentarios_habilitados`
   - Value: `true` o `false` (boolean)

Esto permite:
- ✅ Compatibilidad con WordPress nativo
- ✅ Fácil consulta desde la API
- ✅ Control granular por trabajo

---

## 📱 UI/UX Recomendaciones

### Posicionamiento
- Coloca el switch en la sección "Configuración" o "Avanzado"
- NO lo pongas como campo principal (puede confundir)

### Texto Sugerido
- ✅ "Permitir comentarios"
- ✅ "Habilitar comentarios"
- ❌ "Desactivar comentarios" (confuso)

### Valor Por Defecto
- ✅ **SIEMPRE activado por defecto**
- El usuario debe desactivarlo manualmente si lo desea

### Ayuda/Tooltip
```
"Los usuarios podrán hacer preguntas y comentarios sobre esta oferta de trabajo."
```

---

## ✅ Checklist de Implementación

- [ ] Agregar switch/toggle en formulario de publicación
- [ ] Configurar valor por defecto como `true`
- [ ] Enviar parámetro `comentarios_habilitados` en POST
- [ ] Cargar estado actual en formulario de edición
- [ ] Enviar parámetro al actualizar trabajo
- [ ] Probar crear trabajo con comentarios habilitados
- [ ] Probar crear trabajo con comentarios deshabilitados
- [ ] Probar actualizar estado de comentarios
- [ ] Verificar en sitio web que los comentarios se muestran/ocultan correctamente

---

## 🐛 Troubleshooting

### Problema: Los comentarios no se desactivan

**Solución:**
1. Verifica que estás enviando `comentarios_habilitados: false` (no `"false"` como string)
2. Verifica que la petición sea exitosa (status 200)
3. Verifica en WordPress admin que el campo `comment_status` se actualizó

### Problema: El valor por defecto no es `true`

**Solución:**
```kotlin
// Asegúrate de inicializar con true
var comentariosHabilitados by remember { mutableStateOf(true) }
```

### Problema: Al editar, no se carga el estado actual

**Solución:**
```kotlin
// Cargar del trabajo existente
LaunchedEffect(trabajo) {
    comentariosHabilitados = trabajo.comentarios_habilitados ?: true
}
```

---

**Última actualización:** Diciembre 2025  
**Versión API:** 1.0

