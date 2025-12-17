# 📝 Selector de Tipo de Publicación (Blog/Trabajo)

## Resumen

Se ha implementado un selector que permite a los **administradores** elegir entre publicar un **Trabajo** o un **Blog** desde la misma pantalla de creación en la app móvil.

---

## 🎯 Características Implementadas

### 1. Selector de Tipo de Publicación
- ✅ Visible **solo para administradores**
- ✅ Dos opciones: **📋 Trabajo** y **📝 Blog**
- ✅ Diseño con `FilterChip` de Material3
- ✅ Cambia dinámicamente el título de la pantalla

### 2. Campos Condicionales
- ✅ Cuando se selecciona **Trabajo**: Muestra todos los campos (ubicación, empresa, salario, etc.)
- ✅ Cuando se selecciona **Blog**: Oculta campos específicos de trabajo

### 3. Backend Adaptado
- ✅ Endpoint acepta parámetro `post_type` (`trabajo` o `post`)
- ✅ Solo admins pueden crear posts de blog
- ✅ Validaciones adaptadas según el tipo

---

## 📱 Interfaz de Usuario

### Selector (Solo Admins)

```
┌─────────────────────────────────────────┐
│ Tipo de Publicación                      │
│                                          │
│ ┌──────────────┐  ┌──────────────┐       │
│ │ 📋 Trabajo   │  │ 📝 Blog      │       │
│ │   [SELECTED] │  │              │       │
│ └──────────────┘  └──────────────┘       │
└─────────────────────────────────────────┘
```

### Campos Mostrados por Tipo

#### Trabajo (tipoPublicacion == "trabajo")
- ✅ Título
- ✅ Descripción
- ✅ Ubicación *
- ✅ Empresa *
- ✅ Salario Mín/Máx
- ✅ Vacantes
- ✅ Cultivo
- ✅ Tipo de Puesto
- ✅ Beneficios (Alojamiento, Transporte, Alimentación)
- ✅ Comentarios habilitados
- ✅ Publicar en Facebook

#### Blog (tipoPublicacion == "post")
- ✅ Título
- ✅ Descripción
- ✅ Comentarios habilitados
- ✅ Publicar en Facebook
- ❌ **NO muestra**: Ubicación, Empresa, Salario, Vacantes, etc.

---

## 🔧 Implementación Técnica

### Frontend (App Móvil)

#### Archivo: `CreateJobScreen.kt`

```kotlin
// Variable de estado
val isAdmin = AuthManager.isUserAdmin()
var tipoPublicacion by remember { mutableStateOf("trabajo") } // "trabajo" o "post"

// Selector (solo para admins)
if (isAdmin) {
    Column(modifier = Modifier.padding(horizontal = 16.dp)) {
        Text("Tipo de Publicación", ...)
        Row(...) {
            FilterChip(
                selected = tipoPublicacion == "trabajo",
                onClick = { tipoPublicacion = "trabajo" },
                label = { Text("📋 Trabajo") },
                modifier = Modifier.weight(1f)
            )
            FilterChip(
                selected = tipoPublicacion == "post",
                onClick = { tipoPublicacion = "post" },
                label = { Text("📝 Blog") },
                modifier = Modifier.weight(1f)
            )
        }
    }
}

// Campos condicionales
if (tipoPublicacion == "trabajo") {
    // Mostrar campos de trabajo
    Row(...) {
        CategoryDropdown(label = "📍 Ubicación *", ...)
        CategoryDropdown(label = "🏢 Empresa *", ...)
    }
    // ... más campos
}

// Validación y envío
if (tipoPublicacion == "trabajo") {
    val jobData = mapOf(
        "post_type" to "trabajo",
        "title" to title.trim(),
        "ubicacion_id" to ubicacionId,
        "empresa_id" to empresaId,
        // ... campos de trabajo
    )
    viewModel.createJob(jobData, context)
} else {
    val blogData = mapOf(
        "post_type" to "post",
        "title" to title.trim(),
        "content" to description.textToHtml(),
        "comentarios_habilitados" to comentariosHabilitados
    )
    viewModel.createJob(blogData, context)
}
```

### Backend (WordPress)

#### Archivo: `06-endpoints-jobs.php`

```php
// Determinar el tipo de post
$post_type = 'trabajo'; // Por defecto
if (isset($params['post_type']) && in_array('administrator', $user->roles)) {
    $requested_type = sanitize_text_field($params['post_type']);
    if ($requested_type === 'post' || $requested_type === 'blog') {
        $post_type = 'post'; // WordPress post type nativo
    }
}

$post_data = array(
    'post_type' => $post_type,
    'post_title' => sanitize_text_field($params['title']),
    'post_content' => wp_kses_post($params['content']),
    // ...
);

// Solo procesar campos específicos de trabajo si es un trabajo
if ($post_type === 'trabajo') {
    // Asignar taxonomías (ubicacion, empresa, cultivo, tipo_puesto)
    // Guardar meta fields (salario, vacantes, etc.)
} else {
    // Para blogs, solo guardar comentarios_habilitados si se especifica
    if (isset($params['comentarios_habilitados'])) {
        update_post_meta($post_id, 'comentarios_habilitados', $comentarios);
    }
}
```

---

## 📊 Flujo de Datos

### Crear Trabajo

```
App Móvil
  ↓
tipoPublicacion = "trabajo"
  ↓
jobData = {
  post_type: "trabajo",
  title: "...",
  ubicacion_id: 5,
  empresa_id: 8,
  ...
}
  ↓
Backend
  ↓
post_type = "trabajo"
  ↓
Crea post tipo "trabajo"
  ↓
Asigna taxonomías y meta fields
```

### Crear Blog

```
App Móvil
  ↓
tipoPublicacion = "post"
  ↓
blogData = {
  post_type: "post",
  title: "...",
  content: "...",
  comentarios_habilitados: true
}
  ↓
Backend
  ↓
post_type = "post"
  ↓
Crea post tipo "post" (WordPress nativo)
  ↓
Solo guarda comentarios_habilitados
```

---

## 🔐 Seguridad

### Validaciones Implementadas

1. **Solo Admins pueden crear blogs:**
   ```php
   if (isset($params['post_type']) && in_array('administrator', $user->roles)) {
       // Permitir crear blog
   }
   ```

2. **Usuarios no-admin siempre crean trabajos:**
   - El selector no se muestra para usuarios no-admin
   - El backend ignora `post_type` si no es admin
   - Por defecto siempre es `trabajo`

3. **Validaciones específicas por tipo:**
   - **Trabajo**: Requiere ubicación y empresa
   - **Blog**: Solo requiere título y contenido

---

## 🎨 Cambios en la UI

### Título Dinámico

```kotlin
TopAppBar(
    title = { 
        Text(if (tipoPublicacion == "trabajo") "Nuevo Trabajo" else "Nuevo Blog") 
    }
)
```

### Mensaje de Éxito Dinámico

```kotlin
val mensaje = if (tipoPublicacion == "trabajo") {
    "¡Trabajo creado con éxito! Está pendiente de revisión por un administrador."
} else {
    "¡Artículo de blog creado con éxito!"
}
Toast.makeText(context, mensaje, Toast.LENGTH_LONG).show()
```

---

## 📝 Respuesta del Backend

### Trabajo Creado

```json
{
  "success": true,
  "message": "Trabajo creado correctamente.",
  "post_id": 123,
  "status": "publish",
  "post_type": "trabajo"
}
```

### Blog Creado

```json
{
  "success": true,
  "message": "Artículo de blog creado correctamente.",
  "post_id": 124,
  "status": "publish",
  "post_type": "post"
}
```

---

## ✅ Checklist de Implementación

- [x] Agregar selector de tipo de publicación (solo admins)
- [x] Ocultar campos específicos de trabajo cuando se selecciona Blog
- [x] Modificar endpoint para aceptar `post_type`
- [x] Validar que solo admins pueden crear blogs
- [x] Adaptar validaciones según el tipo
- [x] Actualizar mensajes de éxito
- [x] Actualizar título de la pantalla dinámicamente
- [x] Probar creación de trabajo
- [x] Probar creación de blog

---

## 🧪 Testing

### Test 1: Admin crea Trabajo

```
1. Login como admin
2. Ir a "Nuevo Trabajo"
3. Verificar que aparece selector "Trabajo/Blog"
4. Seleccionar "Trabajo"
5. Completar formulario (ubicación, empresa, etc.)
6. Publicar
7. Verificar que se creó como tipo "trabajo"
```

**Resultado esperado:** ✅ Trabajo creado correctamente

### Test 2: Admin crea Blog

```
1. Login como admin
2. Ir a "Nuevo Trabajo"
3. Seleccionar "Blog" en el selector
4. Verificar que desaparecen campos de trabajo
5. Completar solo título y descripción
6. Publicar
7. Verificar que se creó como tipo "post"
```

**Resultado esperado:** ✅ Blog creado correctamente

### Test 3: Usuario no-admin

```
1. Login como empresa normal
2. Ir a "Nuevo Trabajo"
3. Verificar que NO aparece selector
4. Verificar que siempre es tipo "trabajo"
5. Completar formulario
6. Publicar
```

**Resultado esperado:** ✅ Solo puede crear trabajos

### Test 4: Intento de crear blog como no-admin

```
1. Login como empresa normal
2. Intentar enviar post_type="post" (si fuera posible)
3. Verificar que el backend lo ignora
```

**Resultado esperado:** ✅ Se crea como trabajo (ignora post_type)

---

## 📚 Referencias

- **Material3 FilterChip**: [Documentación oficial](https://developer.android.com/reference/kotlin/androidx/compose/material3/package-summary#FilterChip)
- **WordPress Post Types**: `trabajo` (custom) y `post` (nativo)
- **Endpoint**: `POST /agrochamba/v1/jobs`

---

**Última actualización:** Diciembre 2025  
**Estado:** ✅ **IMPLEMENTADO Y FUNCIONAL**

