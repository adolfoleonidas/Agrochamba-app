# 📱 Resumen: Implementación Completa para App Móvil

## Cambios a Implementar

Se han realizado 3 cambios en el backend que requieren actualización en la app móvil:

---

## 1. 💬 Control de Comentarios

### ¿Qué es?
Un switch/toggle para habilitar o deshabilitar comentarios en cada trabajo publicado.

### ¿Dónde agregarlo?
En el formulario de publicación de trabajo, después de todos los campos principales.

### Valor por Defecto
✅ **ACTIVADO** (true)

### UI Sugerida
```
┌────────────────────────────────────┐
│ 💬 Permitir comentarios    [ON ]  │
└────────────────────────────────────┘
```

### Parámetro API
```json
{
  "comentarios_habilitados": true  // o false
}
```

📚 **Documentación completa:** `API-COMENTARIOS-TRABAJOS.md`

---

## 2. 🏢 Selector de Empresas

### ¿Qué es?
Un dropdown para seleccionar la empresa al publicar un trabajo.

### ¿Dónde agregarlo?
**AL MISMO NIVEL** que el selector de Ubicación (misma fila, mismo tamaño).

### UI Requerida
```
┌─────────────────┐ ┌─────────────────┐
│ 📍 Ubicación   ▼│ │ 🏢 Empresa     ▼│  ← MISMA FILA
│ Lima            │ │ AgroFresh S.A. │
└─────────────────┘ └─────────────────┘
```

### Endpoints API

**Cargar lista de empresas:**
```http
GET /wp-json/wp/v2/empresa?per_page=100&hide_empty=true
```

**Respuesta:**
```json
[
  {
    "id": 5,
    "name": "AgroFresh S.A.",
    "slug": "agrofresh-sa",
    "count": 12
  },
  ...
]
```

**Enviar al publicar:**
```json
{
  "empresa_id": 5  // ID de la empresa seleccionada
}
```

📚 **Documentación completa:** `APP-MOVIL-SELECTOR-EMPRESAS.md`

---

## 3. 📍 Texto de Ubicación (Ya Implementado en Backend)

### ¿Qué cambió?
El selector de ubicación en el sitio web ahora muestra:

**Antes:** "Ubicación"  
**Ahora:** "Seleccionando todas las ubicaciones"

### ¿Afecta a la app?
No directamente, pero considera usar el mismo texto para consistencia.

---

## 🎨 Diseño Completo del Formulario

```
┌───────────────────────────────────────────┐
│ PUBLICAR TRABAJO                           │
├───────────────────────────────────────────┤
│                                           │
│ Título del Trabajo *                      │
│ [_____________________________________]    │
│                                           │
│ Descripción *                             │
│ [_____________________________________]    │
│ [_____________________________________]    │
│                                           │
│ ┌─────────────────┐ ┌─────────────────┐  │
│ │ 📍 Ubicación * ▼│ │ 🏢 Empresa *   ▼│  │ ← NUEVO + MISMO NIVEL
│ │ Lima            │ │ AgroFresh S.A.  │  │
│ └─────────────────┘ └─────────────────┘  │
│                                           │
│ Salario (PEN/día)                         │
│ Min: [____]  Max: [____]                  │
│                                           │
│ Vacantes *                                │
│ [_____________________________________]    │
│                                           │
│ ┌───────────────────────────────────────┐ │
│ │ 💬 Permitir comentarios      [ON ]   │ │ ← NUEVO
│ └───────────────────────────────────────┘ │
│                                           │
│ [          📤 PUBLICAR TRABAJO          ] │
└───────────────────────────────────────────┘
```

---

## 📡 Petición Completa de Publicación

### Antes
```json
POST /wp-json/agrochamba/v1/jobs
{
  "title": "Cosecha de Café",
  "content": "Descripción...",
  "ubicacion_id": 5,
  "salario_min": 50,
  "salario_max": 80,
  "vacantes": 10
}
```

### Ahora (Con Nuevos Campos)
```json
POST /wp-json/agrochamba/v1/jobs
{
  "title": "Cosecha de Café",
  "content": "Descripción...",
  "ubicacion_id": 5,
  "empresa_id": 8,                      // ← NUEVO
  "salario_min": 50,
  "salario_max": 80,
  "vacantes": 10,
  "comentarios_habilitados": true       // ← NUEVO
}
```

---

## 🔧 Pasos de Implementación

### Paso 1: Agregar Switch de Comentarios

**Código de Ejemplo (Kotlin):**
```kotlin
var comentariosHabilitados by remember { mutableStateOf(true) }

SwitchRow(
    label = "Permitir comentarios",
    checked = comentariosHabilitados,
    onCheckedChange = { comentariosHabilitados = it }
)
```

**Enviar:**
```kotlin
val jobData = JSONObject().apply {
    // ... otros campos
    put("comentarios_habilitados", comentariosHabilitados)
}
```

### Paso 2: Agregar Selector de Empresas

**A. Cargar lista de empresas:**
```kotlin
val empresas = apiService.getEmpresas(perPage = 100, hideEmpty = true)
```

**B. Mostrar selector:**
```kotlin
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
        onItemSelected = { selectedUbicacion = it }
    )
    
    // Selector de Empresa (NUEVO)
    DropdownField(
        modifier = Modifier.weight(1f),
        label = "🏢 Empresa",
        items = empresas,
        selectedItem = selectedEmpresa,
        onItemSelected = { selectedEmpresa = it }
    )
}
```

**C. Enviar:**
```kotlin
val jobData = JSONObject().apply {
    // ... otros campos
    put("empresa_id", selectedEmpresa?.id)
}
```

---

## ✅ Checklist de Implementación

### Comentarios
- [ ] Agregar variable de estado `comentariosHabilitados` (default: true)
- [ ] Agregar switch/toggle en el formulario
- [ ] Enviar parámetro `comentarios_habilitados` en POST
- [ ] Cargar valor actual en formulario de edición
- [ ] Probar crear trabajo con comentarios habilitados
- [ ] Probar crear trabajo con comentarios deshabilitados

### Empresas
- [ ] Crear modelo `Empresa` (id, name, slug, count)
- [ ] Implementar GET a `/wp/v2/empresa`
- [ ] Cargar lista de empresas al abrir formulario
- [ ] Agregar selector de empresa en el formulario
- [ ] Posicionar selector **al mismo nivel** que ubicación
- [ ] Aplicar mismo estilo visual a ambos selectores
- [ ] Agregar validación (empresa requerida)
- [ ] Enviar parámetro `empresa_id` en POST
- [ ] Probar crear trabajo con empresa
- [ ] Verificar en sitio web que se asoció correctamente

---

## 🧪 Testing

### Test 1: Comentarios Habilitados (Default)
```bash
POST /agrochamba/v1/jobs
{
  "title": "Test 1"
  # No enviar comentarios_habilitados
}
# Resultado esperado: Comentarios habilitados ✅
```

### Test 2: Comentarios Deshabilitados
```bash
POST /agrochamba/v1/jobs
{
  "title": "Test 2",
  "comentarios_habilitados": false
}
# Resultado esperado: Comentarios deshabilitados ❌
```

### Test 3: Cargar Empresas
```bash
GET /wp/v2/empresa?per_page=100&hide_empty=true
# Resultado esperado: Lista de empresas con id, name, slug
```

### Test 4: Publicar con Empresa
```bash
POST /agrochamba/v1/jobs
{
  "title": "Test 3",
  "ubicacion_id": 5,
  "empresa_id": 8
}
# Resultado esperado: Trabajo creado con empresa asociada ✅
```

---

## 📚 Documentación Completa

| Tema | Documento | Contenido |
|------|-----------|-----------|
| Comentarios | `API-COMENTARIOS-TRABAJOS.md` | Guía completa con ejemplos de código |
| Empresas | `APP-MOVIL-SELECTOR-EMPRESAS.md` | Layout, API, código de ejemplo |
| Resumen General | `RESUMEN-CAMBIOS-COMENTARIOS-UBICACIONES.md` | Overview de cambios |
| Este Documento | `RESUMEN-IMPLEMENTACION-APP-MOVIL.md` | Checklist y pasos |

---

## 🎯 Prioridades

### 🔴 Alta Prioridad (Crítico)
1. **Selector de Empresas** - Requerido para asociar trabajos correctamente
2. **Validación de Empresa** - Campo obligatorio

### 🟡 Media Prioridad (Importante)
3. **Switch de Comentarios** - Mejora UX, por defecto funciona
4. **Mismo nivel visual** - Empresas y Ubicación en misma fila

### 🟢 Baja Prioridad (Opcional)
5. **Cache de empresas** - Mejora performance
6. **Búsqueda de empresas** - UX avanzada

---

## 📞 Soporte

Si tienes dudas sobre la implementación:

1. **Revisa la documentación específica** de cada feature
2. **Prueba los endpoints** con Postman/cURL
3. **Contacta al equipo de backend** con detalles específicos

---

## 🚀 Resultado Final

Una vez implementado, el formulario de publicación tendrá:

- ✅ Selector de Ubicación y Empresa al **mismo nivel**
- ✅ Switch para **controlar comentarios**
- ✅ **Validación** de campos requeridos
- ✅ **Diseño consistente** con el sitio web
- ✅ **Experiencia fluida** para el usuario

---

**Última actualización:** Diciembre 2025  
**Estado:** ✅ Backend implementado, esperando integración en app móvil  
**Prioridad:** 🔴 Alta - Requerido para funcionalidad completa

