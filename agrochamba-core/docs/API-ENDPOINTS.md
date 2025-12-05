
**Response 200:**
```json
{
  "success": true,
  "message": "Foto de perfil eliminada correctamente."
}
```

---

## Endpoints de Favoritos

### 17. Agregar a Favoritos
**POST** `/me/favorites/{job_id}`
🔒 Requiere autenticación

**Response 200:**
```json
{
  "success": true,
  "message": "Trabajo agregado a favoritos."
}
```

---

### 18. Eliminar de Favoritos
**DELETE** `/me/favorites/{job_id}`
🔒 Requiere autenticación

**Response 200:**
```json
{
  "success": true,
  "message": "Trabajo eliminado de favoritos."
}
```

---

### 19. Obtener Favoritos
**GET** `/me/favorites`
🔒 Requiere autenticación

**Query Parameters:**
- `page`: número de página
- `per_page`: trabajos por página

**Response 200:**
```json
{
  "favorites": [
    {
      "id": 321,
      "title": "Empacadores de Uva",
      "date": "2025-02-01",
      "link": "https://ejemplo.com/trabajo/321"
    }
  ],
  "pagination": {
    "total": 5,
    "total_pages": 1,
    "current_page": 1,
    "per_page": 10
  }
}
```

---

## Códigos de Error

### Errores Comunes

**401 Unauthorized**
```json
{
  "code": "rest_forbidden",
  "message": "Debes iniciar sesión.",
  "data": { "status": 401 }
}
```

**403 Forbidden**
```json
{
  "code": "rest_forbidden",
  "message": "No tienes permiso para realizar esta acción.",
  "data": { "status": 403 }
}
```

**404 Not Found**
```json
{
  "code": "rest_not_found",
  "message": "Recurso no encontrado.",
  "data": { "status": 404 }
}
```

**429 Too Many Requests**
```json
{
  "code": "rate_limit_exceeded",
  "message": "Has excedido el límite de peticiones por minuto.",
  "data": { "status": 429 }
}
```

---

## Rate Limiting

**Límites por defecto:**
- 60 peticiones por minuto
- 1000 peticiones por hora
- 10000 peticiones por día

Los límites son por usuario autenticado o por IP+UserAgent para usuarios anónimos.

---

## CORS

**Orígenes permitidos por defecto:**
- https://agrochamba.com
- http://localhost
- http://localhost:8080
- http://localhost:8100
- capacitor://localhost
- ionic://localhost
- Apps móviles (sin origen)

**Headers permitidos:**
- Content-Type
- Authorization
- X-WP-Nonce
- X-Requested-With

---

## Notas de Seguridad

1. **JWT Token**: Los tokens deben renovarse periódicamente
2. **HTTPS**: Se recomienda usar HTTPS en producción
3. **Tamaño de archivos**: Límite de 5MB para imágenes (configurable)
4. **Moderación**: Los trabajos creados por empresas requieren aprobación admin
5. **Validación**: Todos los inputs son sanitizados y validados

---

## Versiones

- **v1.0.0**: Versión inicial
- Base URL: `/wp-json/agrochamba/v1/`

### 16.1 Obtener Perfil de Empresa (propio)
**GET** `/me/company-profile`
🔒 Requiere autenticación (rol employer o admin)

**Response 200:**
```json
{
  "user_id": 123,
  "company_name": "Empresa Agrícola S.A.C.",
  "description": "Empresa dedicada a...",
  "email": "contacto@empresa.com",
  "company_website": "https://empresa.com",
  "company_phone": "+51999999999",
  "company_address": "Av. Principal 123"
}
```

### 16.2 Actualizar Perfil de Empresa (propio)
**PUT** `/me/company-profile`
🔒 Requiere autenticación (rol employer o admin)

**Body (ejemplos, todos opcionales):**
```json
{
  "description": "Descripción actualizada...",
  "phone": "+51888888888",
  "website": "https://nueva-web.com"
}
```

### 16.3 Perfil de Empresa por ID
**GET** `/companies/{user_id}/profile`

### 16.4 Perfil de Empresa por Nombre
**GET** `/companies/profile?name=NombreEmpresa`
