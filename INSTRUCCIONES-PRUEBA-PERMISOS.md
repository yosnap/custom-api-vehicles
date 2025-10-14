# Instrucciones para Probar el Sistema de Permisos

## 📋 Archivos Modificados/Creados

### Nuevos Archivos
- `includes/singlecar-endpoints/permission-helpers.php` - Funciones helper de permisos
- `test-permissions.php` - Script de prueba (eliminar después)
- `INSTRUCCIONES-PRUEBA-PERMISOS.md` - Este archivo

### Archivos Modificados
- `admin/views/permissions-page.php` - Página de configuración de permisos mejorada
- `includes/singlecar-endpoints/routes.php` - Actualizado permission_callback
- `includes/api/class-vehicle-controller.php` - Actualizado permission_callback
- `custom-api-vehicles.php` - Carga del archivo de permisos

## 🧪 Pasos para Probar

### Paso 1: Verificar que el Plugin Está Activo

1. Ve a **WP Admin → Plugins**
2. Verifica que "Custom API Vehicles" está activo
3. Si no está activo, actívalo

### Paso 2: Ejecutar Script de Prueba

1. Accede como administrador
2. Abre en el navegador:
   ```
   https://motoraldia.net/wp-content/plugins/custom-api-vehicles/test-permissions.php
   ```
3. Verifica que todas las funciones están disponibles (✅)
4. Revisa la configuración actual de permisos

### Paso 3: Configurar Permisos para Professional

1. Ve a **WP Admin → API Motoraldia → Permisos**
2. En la sección **"Crear Vehículos (POST)"**:
   - ✅ Marca "Administrator"
   - ✅ Marca "Professional"
3. En la sección **"Editar Vehículos (PUT)"**:
   - ✅ Marca "Administrator"
   - ✅ Marca "Professional"
4. En la sección **"Subir Imágenes"**:
   - ✅ Marca "Administrator"
   - ✅ Marca "Professional"
5. Haz clic en **"Guardar Permisos"**

### Paso 4: Configurar Permisos para Particular (si aplica)

Repite el Paso 3 pero marcando también "Particular" en cada sección.

### Paso 5: Verificar Cambios

1. Vuelve a ejecutar el script de prueba (Paso 2)
2. Verifica que ahora aparecen los roles configurados en cada sección
3. La configuración debe mostrar algo como:
   ```
   Crear Vehículos (POST)
   ✅ administrator
   ✅ professional
   ```

### Paso 6: Probar con Usuario Professional

#### Opción A: Crear Usuario de Prueba

1. Ve a **WP Admin → Usuarios → Añadir Nuevo**
2. Crea un usuario con estos datos:
   - Usuario: `professional_test`
   - Email: `professional@test.com`
   - Rol: **Professional**
   - Contraseña: (genera una segura)
3. Guarda el usuario

#### Opción B: Usar Usuario Existente

1. Ve a **WP Admin → Usuarios**
2. Identifica un usuario con rol "Professional"
3. Anota sus credenciales

### Paso 7: Probar API como Professional

#### 7.1. Obtener Token/Credenciales

Si usas Basic Auth, necesitas las credenciales del usuario Professional.

#### 7.2. Probar Endpoint POST (Crear Vehículo)

Usa Postman o curl para probar:

```bash
curl -X POST https://motoraldia.net/wp-json/api-motor/v1/vehicles \
  -u professional_test:PASSWORD_AQUI \
  -F "titol-anunci=Mercedes Clase A TEST" \
  -F "preu=25000" \
  -F "tipus-vehicle=cotxe" \
  -F "marques-cotxe=mercedes-benz" \
  -F "models-cotxe=clase-a" \
  -F "estat-vehicle=seminuevo" \
  -F "imatge-destacada=@/ruta/a/imagen.jpg"
```

**Resultado Esperado:**
- ✅ Status 200 o 201
- ✅ JSON con los datos del vehículo creado
- ❌ Si falla con 403, revisar la configuración de permisos

#### 7.3. Probar Endpoint PUT (Editar Vehículo)

```bash
curl -X PUT https://motoraldia.net/wp-json/api-motor/v1/vehicles/VEHICLE_ID \
  -u professional_test:PASSWORD_AQUI \
  -F "preu=26000" \
  -F "titol-anunci=Mercedes Clase A TEST (Actualizado)"
```

**Resultado Esperado:**
- ✅ Status 200 si el usuario es propietario
- ❌ Status 403 si intenta editar un vehículo de otro usuario

#### 7.4. Probar que NO Puede Editar Vehículos de Otros

Intenta editar un vehículo creado por otro usuario:

```bash
curl -X PUT https://motoraldia.net/wp-json/api-motor/v1/vehicles/ID_DE_OTRO_USUARIO \
  -u professional_test:PASSWORD_AQUI \
  -F "preu=99999"
```

**Resultado Esperado:**
- ❌ Status 403 Forbidden
- Mensaje: No tienes permiso para editar este vehículo

## ✅ Lista de Verificación

Marca cada item cuando lo hayas verificado:

- [ ] Script de prueba ejecutado sin errores
- [ ] Permisos configurados para Professional
- [ ] Permisos configurados para Particular (si aplica)
- [ ] Usuario Professional puede crear vehículos (POST)
- [ ] Usuario Professional puede editar sus vehículos (PUT)
- [ ] Usuario Professional NO puede editar vehículos de otros
- [ ] Usuario Professional puede subir imágenes
- [ ] Administrador puede hacer todo (sin cambios)
- [ ] Script test-permissions.php eliminado (seguridad)

## 🔍 Troubleshooting

### Error: "Funciones no están disponibles"

**Solución:**
1. Verifica que el archivo `permission-helpers.php` existe en `includes/singlecar-endpoints/`
2. Verifica que se está cargando en `custom-api-vehicles.php` línea 89
3. Desactiva y reactiva el plugin

### Error 403 al crear vehículo

**Solución:**
1. Ve a **WP Admin → API Motoraldia → Permisos**
2. Verifica que el rol está marcado en "Crear Vehículos (POST)"
3. Guarda los cambios
4. Limpia la caché si usas algún plugin de caché

### Error 403 al editar vehículo propio

**Solución:**
1. Verifica que el rol está marcado en "Editar Vehículos (PUT)"
2. Verifica que el usuario es el propietario del vehículo
3. Consulta el `author_id` del vehículo y compáralo con el ID del usuario

### Professional puede editar vehículos de otros

**Problema de Seguridad:**
1. Revisa el archivo `permission-helpers.php`
2. La función `user_can_edit_vehicle()` debe verificar:
   - Que el usuario es propietario O
   - Que el usuario es administrador
3. Si no funciona, contacta a soporte

## 🔒 Seguridad

**IMPORTANTE:**
- ❌ Elimina el archivo `test-permissions.php` después de las pruebas
- ✅ Los usuarios solo pueden editar sus propios vehículos
- ✅ Los administradores pueden editar todos los vehículos
- ✅ Configura permisos según tus necesidades

## 📝 Notas Adicionales

### Valores por Defecto

Si no configuras ningún rol, los valores por defecto son:
- Crear vehículos: Solo administradores
- Editar vehículos: Solo administradores
- Subir imágenes: Solo administradores

### Roles Personalizados

Si tienes roles personalizados además de Professional y Particular:
1. Ve a **WP Admin → API Motoraldia → Permisos**
2. Verás TODOS los roles disponibles en tu WordPress
3. Marca los que necesites

### Logs

Si necesitas debug:
1. Ve a **WP Admin → API Motoraldia → Logs**
2. Filtra por usuario o acción
3. Revisa los errores de permisos

## ✅ Finalización

Una vez que hayas verificado todo:

1. ✅ Marca todos los items de la lista de verificación
2. ❌ Elimina el archivo `test-permissions.php`
3. ❌ Puedes eliminar este archivo `INSTRUCCIONES-PRUEBA-PERMISOS.md`
4. 🎉 El sistema de permisos está listo para producción

---

**Fecha de creación:** 2025-10-14
**Versión del plugin:** 2.2.5+
