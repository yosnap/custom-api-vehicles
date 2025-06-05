# Guía de Verificación Rápida - Custom API Vehicles v2.3.0

## ✅ Lista de Verificación Post-Instalación

### 1. Verificar Archivos Nuevos
Confirma que estos archivos existen:
- `includes/class-dependencies.php`
- `includes/class-diagnostic-helpers.php` 
- `includes/enhanced-diagnostic-endpoint.php`
- `config-example.php`
- `installation-check.php`

### 2. Probar Seguridad de la API

**❌ Debe FALLAR (401 Unauthorized):**
```bash
curl -X POST "http://tudominio.com/wp-json/api-motor/v1/vehicles" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Vehicle"}'
```

**✅ Debe FUNCIONAR (200 OK):**
```bash
curl -X GET "http://tudominio.com/wp-json/api-motor/v1/vehicles?per_page=1"
```

### 3. Probar Endpoint de Diagnóstico

**Como administrador autenticado:**
```
http://tudominio.com/wp-json/api-motor/v1/diagnostic
```

**Debe devolver información completa del sistema**

### 4. Verificar Dependencias

Si JetEngine no está instalado, debe aparecer un aviso en el admin de WordPress:
"Custom API Vehicles: JetEngine es requerido..."

### 5. Probar Debug Logging

**Activar debug temporal en wp-config.php:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('VEHICLE_API_DEBUG', true);
```

**Probar el debug handler (solo en modo debug):**
```
http://tudominio.com/wp-json/api-motor/v1/debug/test
```

**Verificar estadísticas de debug:**
```
http://tudominio.com/wp-json/api-motor/v1/debug/stats
```

**Verificar que los logs funcionan:**
- Los errores deben aparecer en el log del plugin
- En modo debug, también se registran mensajes informativos

## 🚨 Solución de Problemas

### Problema: "Class not found"
**Solución:** Verificar que todos los archivos se copiaron correctamente

### Problema: API sigue permitiendo todo sin autenticación
**Solución:** Verificar que el código de `fix_rest_api_permissions` se actualizó

### Problema: No aparecen logs de debug
**Solución:** 
1. Verificar que `WP_DEBUG` y `WP_DEBUG_LOG` están habilitados
2. Verificar permisos de escritura en `/wp-content/debug.log`
3. Activar `VEHICLE_API_DEBUG` si necesitas debug extendido

### Problema: Endpoint de diagnóstico devuelve 403
**Solución:** Verificar que el usuario tiene permisos de administrador

## 📞 Contacto para Soporte

Si encuentras algún problema:
1. Verificar el endpoint `/installation-check` (solo en modo debug)
2. Revisar los logs de error de WordPress
3. Comprobar la respuesta del endpoint `/diagnostic`

## ⚡ Comandos de Verificación Rápida

```bash
# Verificar que la API responde
curl -I "http://tudominio.com/wp-json/api-motor/v1/vehicles"

# Verificar que requiere auth para POST
curl -X POST "http://tudominio.com/wp-json/api-motor/v1/vehicles"

# Verificar endpoint de diagnóstico (requiere auth)
curl "http://tudominio.com/wp-json/api-motor/v1/diagnostic"
```

**Resultado esperado:**
- GET: 200 OK
- POST sin auth: 401 Unauthorized  
- Diagnostic sin auth: 403 Forbidden
