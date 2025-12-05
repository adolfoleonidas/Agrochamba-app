# Guía de Configuración de n8n para AgroChamba

## ¿Qué es n8n?

n8n es una herramienta de automatización de flujos de trabajo (workflow automation) que permite conectar diferentes servicios y APIs sin necesidad de escribir código complejo.

## Ventajas de usar n8n

- ✅ **Gestión centralizada**: Todos tus flujos de automatización en un solo lugar
- ✅ **Manejo robusto de errores**: Reintentos automáticos y notificaciones
- ✅ **Logging y monitoreo**: Visualiza fácilmente qué está pasando
- ✅ **Escalabilidad**: Fácil agregar más plataformas (Instagram, LinkedIn, Twitter, etc.)
- ✅ **Sin código PHP complejo**: WordPress solo envía un webhook simple

## Instalación de n8n

### Opción 1: n8n Cloud (Recomendado para empezar)

1. Ve a: https://n8n.io/
2. Crea una cuenta gratuita
3. Tu instancia estará lista en minutos

### Opción 2: n8n Self-hosted

```bash
# Con Docker
docker run -it --rm \
  --name n8n \
  -p 5678:5678 \
  -v ~/.n8n:/home/node/.n8n \
  n8nio/n8n

# O con npm
npm install n8n -g
n8n start
```

## Configuración del Workflow en n8n

### Paso 1: Crear un nuevo workflow

1. En n8n, haz clic en "Workflows" → "New Workflow"
2. Nombra el workflow: "AgroChamba - Publicar en Facebook"

### Paso 2: Agregar nodo Webhook

1. Arrastra el nodo **"Webhook"** al canvas
2. Configura el webhook:
   - **HTTP Method**: POST
   - **Path**: `/agrochamba-facebook` (o el que prefieras)
   - **Response Mode**: "Respond When Last Node Finishes"
   - Haz clic en "Listen for Test Event"
   - **Copia la URL del webhook** (ejemplo: `https://tu-n8n.com/webhook/agrochamba-facebook`)
   - Esta URL es la que debes poner en WordPress

### Paso 3: Agregar nodo Function (Opcional - para procesar datos)

1. Arrastra el nodo **"Function"** después del Webhook
2. Configura el código para procesar los datos:

```javascript
// Obtener datos del webhook
const postId = $input.item.json.post_id;
const title = $input.item.json.title;
const message = $input.item.json.message;
const link = $input.item.json.link;
const imageUrl = $input.item.json.image_url;

// Preparar datos para Facebook
return {
  json: {
    post_id: postId,
    title: title,
    message: message,
    link: link,
    image_url: imageUrl
  }
};
```

### Paso 4: Agregar nodo HTTP Request (Facebook Graph API)

1. Arrastra el nodo **"HTTP Request"** después del Function
2. Configura la petición:
   - **Method**: POST
   - **URL**: `https://graph.facebook.com/v18.0/{{ $json.page_id }}/feed`
   - **Authentication**: Query Auth
   - **Query Parameters**:
     - `access_token`: `{{ $json.page_access_token }}`
   - **Body Parameters**:
     - `message`: `{{ $json.message }}`
     - `link`: `{{ $json.link }}`
     - `picture`: `{{ $json.image_url }}` (si existe)

### Paso 5: Configurar Credenciales de Facebook

1. En el nodo HTTP Request, haz clic en "Add Credential"
2. Selecciona "Query Auth"
3. Configura:
   - **Page Access Token**: Tu token de larga duración de Facebook
   - **Page ID**: El ID de tu página de Facebook

**O mejor aún**, usa el nodo **"Facebook Trigger"** o **"Facebook API"** si está disponible en tu versión de n8n.

### Paso 6: Agregar nodo para manejar respuesta

1. Arrastra el nodo **"Function"** después del HTTP Request
2. Configura para procesar la respuesta:

```javascript
const response = $input.item.json;

if (response.id) {
  // Éxito - devolver el ID del post de Facebook
  return {
    json: {
      success: true,
      facebook_post_id: response.id,
      message: 'Publicado correctamente en Facebook'
    }
  };
} else {
  // Error
  return {
    json: {
      success: false,
      error: response.error || 'Error desconocido'
    }
  };
}
```

### Paso 7: Configurar el nodo Webhook para responder

1. Vuelve al nodo Webhook
2. Asegúrate de que "Response Mode" esté en "Respond When Last Node Finishes"
3. El último nodo del flujo será la respuesta que WordPress recibirá

### Paso 8: Activar el workflow

1. Haz clic en el botón **"Active"** en la esquina superior derecha
2. El workflow ahora está escuchando webhooks

## Configuración en WordPress

1. Ve a WordPress Admin → Configuración → Facebook Integration
2. Marca "Habilitar publicación en Facebook"
3. Marca "Usar n8n para automatización"
4. Pega la URL del webhook de n8n en "URL del Webhook de n8n"
5. Guarda los cambios

## Estructura del Payload que WordPress envía

WordPress enviará un JSON con esta estructura:

```json
{
  "post_id": 123,
  "title": "Título del trabajo",
  "message": "Mensaje formateado para Facebook",
  "link": "https://tusitio.com/trabajo/123",
  "image_url": "https://tusitio.com/wp-content/uploads/imagen.jpg",
  "timestamp": "2024-01-01 12:00:00",
  "site_url": "https://tusitio.com"
}
```

## Estructura de la Respuesta esperada

n8n debe responder con:

```json
{
  "success": true,
  "facebook_post_id": "123456789_987654321",
  "message": "Publicado correctamente"
}
```

O en caso de error:

```json
{
  "success": false,
  "error": "Mensaje de error"
}
```

## Ejemplo de Workflow Completo

```
[Webhook] → [Function (procesar)] → [HTTP Request (Facebook)] → [Function (respuesta)] → [Webhook Response]
```

## Agregar más funcionalidades

Con n8n puedes fácilmente agregar:

- **Publicación en múltiples plataformas**: Instagram, LinkedIn, Twitter
- **Notificaciones**: Email, Slack, Telegram cuando se publique
- **Programación**: Publicar en horarios específicos
- **Filtros**: Solo publicar ciertos tipos de trabajos
- **Análisis**: Guardar métricas en una base de datos

## Troubleshooting

### El webhook no se recibe

1. Verifica que el workflow esté activo en n8n
2. Verifica la URL del webhook en WordPress
3. Revisa los logs de n8n en la pestaña "Executions"

### Error al publicar en Facebook

1. Verifica que el Page Access Token sea válido
2. Verifica que el Page ID sea correcto
3. Revisa los permisos de la app de Facebook
4. Revisa los logs en n8n para ver el error exacto

### WordPress no recibe respuesta

1. Asegúrate de que el último nodo del workflow devuelva una respuesta
2. Verifica que el nodo Webhook esté configurado para responder

## Recursos adicionales

- Documentación de n8n: https://docs.n8n.io/
- Facebook Graph API: https://developers.facebook.com/docs/graph-api
- Comunidad n8n: https://community.n8n.io/

