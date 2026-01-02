# 📚 Documentación del Plugin Agrochamba Core

## ✅ Documentación que SÍ se sube a Git

Toda la documentación técnica es útil y debe subirse al repositorio:

- ✅ `GUIA-TECNICA-APP-WEB-SUPABASE.md` - Guía técnica completa
- ✅ `PROMPT-DESARROLLADOR-APP-WEB.md` - Prompt para desarrolladores
- ✅ `CODIGO-EJEMPLOS-APP-WEB.md` - Ejemplos de código
- ✅ `CONFIGURACION-SUPABASE.md` - Guía de configuración
- ✅ `RESUMEN-APP-WEB-SUPABASE.md` - Resumen ejecutivo
- ✅ `INDICE-DOCUMENTACION.md` - Índice general
- ✅ `VALORES-PARA-DESARROLLADOR.template.md` - Template sin valores sensibles

## ⚠️ Archivos que NO se suben a Git

- ❌ `VALORES-PARA-DESARROLLADOR.md` - Contiene JWT_SECRET real (en .gitignore)
- ❌ Archivos `.env` o `.env.local` - Contienen secrets
- ❌ `wp-config.php` - Contiene configuración sensible

## 🔒 Seguridad

**IMPORTANTE:** El archivo `VALORES-PARA-DESARROLLADOR.md` contiene el JWT_SECRET real y está en `.gitignore`.

**Para compartir valores con desarrolladores:**
1. Usa `VALORES-PARA-DESARROLLADOR.template.md` como base
2. Crea una copia local con los valores reales
3. Comparte la copia local directamente (no por Git)
4. O usa variables de entorno en lugar de hardcodear valores

## 📝 Uso de la Documentación

- **Para desarrolladores nuevos:** Empieza con `INDICE-DOCUMENTACION.md`
- **Para implementar app web:** Lee `GUIA-TECNICA-APP-WEB-SUPABASE.md`
- **Para configurar Supabase:** Consulta `CONFIGURACION-SUPABASE.md`
- **Para valores de configuración:** Usa `VALORES-PARA-DESARROLLADOR.template.md`

---

**Última actualización:** 2025-01-XX

