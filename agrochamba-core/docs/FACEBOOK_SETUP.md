# Guía de Configuración de Facebook para AgroChamba

## Paso 0: Crear una Nueva App de Facebook desde Cero

### 1. Eliminar Apps Existentes (Opcional)
- Ve a: https://developers.facebook.com/apps/
- Selecciona cada app que quieras eliminar
- Ve a "Configuración" → "Avanzado" → "Eliminar app"
- Confirma la eliminación

### 2. Crear Nueva App de Facebook

1. **Ir a Facebook Developers:**
   - Ve a: https://developers.facebook.com/apps/
   - Haz clic en "Crear app" o "Create App"

2. **Seleccionar Tipo de App:**
   - Selecciona "Otro" o "Business"
   - Haz clic en "Siguiente"

3. **Configurar Información Básica:**
   - **Nombre de la app:** "AgroChamba" (o el nombre que prefieras)
   - **Email de contacto:** Tu email
   - **Propósito de la app:** Selecciona "Gestionar integraciones de negocio"
   - Haz clic en "Crear app"

4. **Configurar Productos:**
   - En el dashboard de la app, busca "Facebook Login" o "Graph API"
   - Haz clic en "Configurar" o "Set Up"
   - **NO es necesario configurar Facebook Login completamente**, solo necesitas los permisos

5. **Obtener App ID y App Secret:**
   - Ve a "Configuración" → "Básico"
   - Copia el **App ID**
   - Copia el **App Secret** (haz clic en "Mostrar" si está oculto)
   - **Guarda estos valores**, los necesitarás después

6. **Configurar Permisos de Página:**
   - Ve a "Permisos y características" → "Permisos"
   - Agrega estos permisos:
     - `pages_show_list`
     - `pages_read_engagement`
     - `pages_manage_posts`
   - Haz clic en "Solicitar" para cada permiso

7. **Configurar Roles de App:**
   - Ve a "Roles" → "Roles"
   - Agrega tu cuenta de Facebook como "Administrador"
   - Esto te dará acceso completo a la app

### 3. Crear o Seleccionar una Página de Facebook

1. **Si NO tienes una página:**
   - Ve a: https://www.facebook.com/pages/create
   - Selecciona "Negocio o marca" o "Comunidad"
   - Completa la información básica
   - Asegúrate de ser administrador de la página

2. **Si YA tienes una página:**
   - Ve a tu página de Facebook
   - Verifica que seas administrador
   - Ve a "Configuración" → "Información de la página"
   - Copia el **ID de página** (número largo)

## Paso 1: Obtener Page Access Token de Larga Duración

### ⚠️ IMPORTANTE: Si `/me/accounts` devuelve `{"data": []}`

Esto significa que:
1. No tienes páginas creadas en Facebook, O
2. El token no tiene los permisos correctos, O
3. Necesitas crear una página primero

### Solución: Crear una Página de Facebook (si no tienes)

1. Ve a: https://www.facebook.com/pages/create
2. Selecciona el tipo de página (Negocio o Marca, Comunidad, etc.)
3. Completa la información básica
4. Una vez creada, vuelve al Graph API Explorer

### Opción A: Usando Graph API Explorer (Recomendado)

1. **Obtener User Access Token con permisos de páginas:**
   - Ve a: https://developers.facebook.com/tools/explorer/
   - En el dropdown "App de Meta", selecciona tu **nueva app** (ej: "AgroChamba")
   - Haz clic en "Generate Access Token" (botón azul)
   - En el modal que aparece:
     - Verifica que tu app esté seleccionada
     - Selecciona "Usuario o página" → "Token del usuario"
     - En "Permisos", asegúrate de tener estos permisos marcados:
       - `pages_show_list` ✅
       - `pages_read_engagement` ✅
       - `pages_manage_posts` ✅
     - Si falta algún permiso, haz clic en "Agregar un permiso" y agrégalo
   - Haz clic en "Generate Access Token"
   - Autoriza la app cuando te lo pida
   - Copia el token generado

2. **Obtener Page Access Token:**
   - En Graph API Explorer, cambia el endpoint a: `/me/accounts`
   - Haz clic en "Enviar"
   - Verás una lista de tus páginas con sus tokens
   - Copia el `access_token` de tu página (este es el Page Access Token)
   - Copia también el `id` (este es el Page ID)
   
   **✅ Ya tienes estos valores:**
   - Page Access Token: `EAALxPAgcilgBQGj2Y5vZCOVOo9WIZAas1A7HJanOi7bupIoXvyz5jYowlBQMKpZCKgCkSLRqli7aqOoxm08Q5x4zyfRdLYIfT9EbcpZAqYMzB3WAHpZAUb9o1WLrPeZBCF3YJZBY9e7zhxjAvARQ6f9V7tHInWi36GhVSo8mYxdKZBVZACqFjxBIcqxgJWTf0EtRNLXOmXFieGnV8uWfcC7JC0UpQKcbkrvzoLjXMZADGGAloZD`
   - Page ID: `270485056153421`

3. **Convertir a Token de Larga Duración (Recomendado):**
   
   **Primero: Obtener App ID y App Secret**
   
   1. Ve a: https://developers.facebook.com/apps/
   2. Selecciona tu app "AgroChamba" (o el nombre que le pusiste)
   3. En el menú lateral, ve a **"Configuración" → "Básico"**
   4. Copia estos valores:
      - **App ID**: Número largo (ej: `1234567890123456`)
      - **App Secret**: Haz clic en "Mostrar" y copia el secreto
   
   **Opción A: Usando Graph API Explorer (Método Visual)**
   
   1. **Obtener App ID y App Secret primero:**
      - Ve a: https://developers.facebook.com/apps/
      - Selecciona tu app "Wordpres Agrochamba"
      - Ve a **"Configuración" → "Básico"**
      - Copia el **App ID** (número largo)
      - Haz clic en "Mostrar" junto a App Secret y cópialo
   
   2. **En Graph API Explorer:**
      - Cambia el endpoint a esta URL completa (reemplaza `TU_APP_ID` y `TU_APP_SECRET` con tus valores reales):
        ```
        /oauth/access_token?grant_type=fb_exchange_token&client_828190089841240&client_secret=6b99f26fc5288d1ce2132ff5d90052df&fb_exchange_token=EAALxPAgcilgBQGj2Y5vZCOVOo9WIZAas1A7HJanOi7bupIoXvyz5jYowlBQMKpZCKgCkSLRqli7aqOoxm08Q5x4zyfRdLYIfT9EbcpZAqYMzB3WAHpZAUb9o1WLrPeZBCF3YJZBY9e7zhxjAvARQ6f9V7tHInWi36GhVSo8mYxdKZBVZACqFjxBIcqxgJWTf0EtRNLXOmXFieGnV8uWfcC7JC0UpQKcbkrvzoLjXMZADGGAloZD
        ```
      
      **📋 Template listo para copiar y pegar:**
      ```
      /oauth/access_token?grant_type=fb_exchange_token&client_id=828190089841240&client_secret=6b99f26fc5288d1ce2132ff5d90052df&fb_exchange_token=EAALxPAgcilgBQGj2Y5vZCOVOo9WIZAas1A7HJanOi7bupIoXvyz5jYowlBQMKpZCKgCkSLRqli7aqOoxm08Q5x4zyfRdLYIfT9EbcpZAqYMzB3WAHpZAUb9o1WLrPeZBCF3YJZBY9e7zhxjAvARQ6f9V7tHInWi36GhVSo8mYxdKZBVZACqFjxBIcqxgJWTf0EtRNLXOmXFieGnV8uWfcC7JC0UpQKcbkrvzoLjXMZADGGAloZD
      ```
      
      **Ejemplo de cómo se vería con valores reales (reemplaza con los tuyos):**
      ```
      /oauth/access_token?grant_type=fb_exchange_token&client_id=1234567890123456&client_secret=abcdef1234567890abcdef1234567890&fb_exchange_token=EAALxPAgcilgBQGj2Y5vZCOVOo9WIZAas1A7HJanOi7bupIoXvyz5jYowlBQMKpZCKgCkSLRqli7aqOoxm08Q5x4zyfRdLYIfT9EbcpZAqYMzB3WAHpZAUb9o1WLrPeZBCF3YJZBY9e7zhxjAvARQ6f9V7tHInWi36GhVSo8mYxdKZBVZACqFjxBIcqxgJWTf0EtRNLXOmXFieGnV8uWfcC7JC0UpQKcbkrvzoLjXMZADGGAloZD
      ```
      
   3. Haz clic en "Enviar"
   4. Copia el nuevo `access_token` del resultado (este dura aproximadamente 60 días)
   
   **Opción B: URL completa para copiar y pegar**
   
   Reemplaza `TU_APP_ID` y `TU_APP_SECRET` con tus valores reales:
   ```
   /oauth/access_token?grant_type=fb_exchange_token&client_id=TU_APP_ID&client_secret=TU_APP_SECRET&fb_exchange_token=EAALxPAgcilgBQGj2Y5vZCOVOo9WIZAas1A7HJanOi7bupIoXvyz5jYowlBQMKpZCKgCkSLRqli7aqOoxm08Q5x4zyfRdLYIfT9EbcpZAqYMzB3WAHpZAUb9o1WLrPeZBCF3YJZBY9e7zhxjAvARQ6f9V7tHInWi36GhVSo8mYxdKZBVZACqFjxBIcqxgJWTf0EtRNLXOmXFieGnV8uWfcC7JC0UpQKcbkrvzoLjXMZADGGAloZD
   ```
   
   **⚠️ Nota:** Si no tienes el App Secret o prefieres usar el token corto primero, puedes usar el token actual. Solo durará unas horas, pero puedes probar la integración.

### Opción B: Usando Script PHP (Más fácil)

Crea un archivo temporal `get-facebook-token.php` en la raíz de WordPress:

```php
<?php
// get-facebook-token.php
// Ejecutar UNA VEZ y luego ELIMINAR este archivo

$user_access_token = 'TU_USER_ACCESS_TOKEN_AQUI'; // El token que obtuviste del Explorer
$app_id = 'TU_APP_ID';
$app_secret = 'TU_APP_SECRET';

// Paso 1: Obtener páginas del usuario
$pages_url = "https://graph.facebook.com/v24.0/me/accounts?access_token={$user_access_token}";
$pages_response = file_get_contents($pages_url);
$pages_data = json_decode($pages_response, true);

echo "<h2>Páginas disponibles:</h2>";
echo "<pre>";
print_r($pages_data);
echo "</pre>";

if (isset($pages_data['data']) && !empty($pages_data['data'])) {
    $page = $pages_data['data'][0]; // Primera página
    $page_id = $page['id'];
    $page_name = $page['name'];
    $page_access_token = $page['access_token'];
    
    echo "<h2>Información de la página:</h2>";
    echo "<p><strong>Page ID:</strong> {$page_id}</p>";
    echo "<p><strong>Page Name:</strong> {$page_name}</p>";
    echo "<p><strong>Page Access Token:</strong> {$page_access_token}</p>";
    
    // Paso 2: Convertir a token de larga duración
    $exchange_url = "https://graph.facebook.com/v24.0/oauth/access_token?" .
        "grant_type=fb_exchange_token&" .
        "client_id={$app_id}&" .
        "client_secret={$app_secret}&" .
        "fb_exchange_token={$page_access_token}";
    
    $exchange_response = file_get_contents($exchange_url);
    $exchange_data = json_decode($exchange_response, true);
    
    if (isset($exchange_data['access_token'])) {
        echo "<h2>✅ Token de Larga Duración:</h2>";
        echo "<p><strong>Long-lived Page Access Token:</strong></p>";
        echo "<textarea style='width:100%;height:100px;'>{$exchange_data['access_token']}</textarea>";
        echo "<p>Este token dura aproximadamente 60 días.</p>";
    } else {
        echo "<h2>❌ Error al obtener token de larga duración:</h2>";
        echo "<pre>";
        print_r($exchange_data);
        echo "</pre>";
    }
} else {
    echo "<p>No se encontraron páginas. Asegúrate de tener permisos de administrador en una página.</p>";
}
?>
```

## Paso 2: Obtener Page ID

El Page ID ya lo obtuviste en el paso anterior. Es el `id` que aparece en la respuesta de `/me/accounts`.

También puedes obtenerlo desde:
- La configuración de tu página de Facebook
- O usando: `https://graph.facebook.com/v24.0/me/accounts?access_token=TU_TOKEN`

## Paso 3: Configurar en WordPress

1. Ve a: **WordPress Admin → Configuración → Facebook Integration**
2. Activa el checkbox: **"Habilitar publicación en Facebook"**
3. Pega el **Page Access Token de larga duración** en el campo "Page Access Token"
4. Pega el **Page ID** en el campo "Page ID"
5. Haz clic en **"Guardar cambios"**

## Paso 4: Probar la publicación

1. Crea un trabajo desde la app Android
2. Activa el switch "Publicar también en Facebook"
3. Publica el trabajo
4. Verifica que aparezca en tu página de Facebook

## Solución de Problemas

### Error: "Invalid OAuth access token"
- El token expiró o es inválido
- Genera un nuevo token siguiendo los pasos anteriores

### Error: "Requires extended permission: pages_manage_posts"
- Asegúrate de tener el permiso `pages_manage_posts` en el token
- Regenera el token con todos los permisos necesarios

### Error: "Page ID is invalid"
- Verifica que el Page ID sea correcto
- Debe ser solo números, sin espacios ni caracteres especiales

## Notas Importantes

⚠️ **Seguridad:**
- El Page Access Token es sensible, guárdalo de forma segura
- No lo compartas públicamente
- Si se compromete, revócalo desde Facebook Developers

⚠️ **Duración del Token:**
- Los tokens de larga duración duran aproximadamente 60 días
- Puedes configurar un recordatorio para renovarlo antes de que expire
- O implementar renovación automática (requiere App Secret)

ejemplo para el facebook agrochamba-reclutamiento agricola
/oauth/access_token?grant_type=fb_exchange_token&client_id=1559763738695115&client_secret=cbc133f281272ecb5e49b5b03b9ce420&fb_exchange_token=EAAWKmMuUFcsBQbeJvdYepQ1ZAEZAcCFKEeoaU3YtZB2Ve4UnFLvCoIMAOmI3xe9J8lovN6Pa33DM3QEwq76ieKetPoazvHZCq6WI4uiAQXO7MIIdtOnZC26PfRZA3fRhRP6FDzsb8ZCDnFsZBddCmz0eSxHJlXwxsLbiwLIW9H4grlVmhHZCATBKPw494Ray7RkoWrNV5POA6wgeXqFfZCl7Cv4p0giSPR9xurz5zhnVwZD