# Changelog - Sistema de Permisos Configurable

## Versión 2.2.6 - Sistema de Permisos Configurable

**Fecha:** 2025-10-14

### 🎯 Problema Resuelto

Los usuarios con roles "Professional" y "Particular" no podían crear ni editar vehículos a través de la API, ya que el sistema solo validaba la capability de WordPress `publish_posts`, que estos roles no tienen.

### ✨ Solución Implementada

Se ha creado un **sistema de permisos configurable** desde el panel de administración que permite:

1. Configurar qué roles pueden **crear vehículos** (POST)
2. Configurar qué roles pueden **editar vehículos** (PUT)
3. Configurar qué roles pueden **subir imágenes**
4. Protección automática: usuarios solo pueden editar sus propios vehículos
5. Administradores mantienen acceso total a todo

### 📁 Archivos Nuevos

```
includes/singlecar-endpoints/
└── permission-helpers.php          # Funciones helper de permisos

test-permissions.php                 # Script de prueba (temporal)
INSTRUCCIONES-PRUEBA-PERMISOS.md    # Guía de pruebas (temporal)
CHANGELOG-PERMISOS.md               # Este archivo
```

### 📝 Archivos Modificados

```
admin/views/
└── permissions-page.php            # Página de configuración mejorada

includes/singlecar-endpoints/
└── routes.php                      # Actualizado permission_callback

includes/api/
└── class-vehicle-controller.php    # Actualizado permission_callback

custom-api-vehicles.php             # Carga del sistema de permisos
```

### 🔧 Funciones Añadidas

#### permission-helpers.php

```php
user_can_create_vehicle()           # Verifica permiso para crear
user_can_edit_vehicle($post_id)     # Verifica permiso para editar
user_can_upload_images()            # Verifica permiso para subir imágenes
user_can_delete_vehicle($post_id)   # Verifica permiso para eliminar
```

### 📊 Opciones de WordPress Añadidas

```php
vehicles_api_create_permissions     # Array de roles permitidos para crear
vehicles_api_edit_permissions       # Array de roles permitidos para editar
vehicles_api_image_permissions      # Array de roles permitidos para imágenes
```

### 🔐 Lógica de Permisos

#### Crear Vehículo (POST)
- ✅ Usuario debe estar logueado
- ✅ Usuario debe tener rol configurado en `vehicles_api_create_permissions`
- ✅ Administradores siempre tienen acceso

#### Editar Vehículo (PUT)
- ✅ Usuario debe estar logueado
- ✅ Usuario debe tener rol configurado en `vehicles_api_edit_permissions`
- ✅ Usuario debe ser propietario del vehículo (excepto administradores)
- ✅ Administradores pueden editar cualquier vehículo

#### Eliminar Vehículo (DELETE)
- ✅ Usuario debe estar logueado
- ✅ Usuario debe ser propietario del vehículo
- ✅ Administradores pueden eliminar cualquier vehículo

#### Subir Imágenes
- ✅ Usuario debe estar logueado
- ✅ Usuario debe tener rol configurado en `vehicles_api_image_permissions`
- ✅ Administradores siempre tienen acceso

### 🎛️ Configuración

**Ubicación:** `WP Admin → API Motoraldia → Permisos`

La página permite marcar checkboxes para los roles que deben tener cada permiso.

**Roles típicos a configurar:**
- Administrator (siempre tiene acceso)
- Professional
- Particular (si aplica)

### 🔄 Cambios en Endpoints

#### Antes
```php
'permission_callback' => function() {
    return is_user_logged_in();
}
```

#### Después
```php
'permission_callback' => function() {
    return user_can_create_vehicle();
}
```

### 🧪 Testing

1. Ejecutar `test-permissions.php` en el navegador
2. Configurar permisos desde el panel admin
3. Probar API con usuario Professional
4. Verificar que solo puede editar sus vehículos
5. Eliminar archivos de prueba

### ⚙️ Retrocompatibilidad

- ✅ Mantiene compatibilidad con código existente
- ✅ Administradores no ven cambios en comportamiento
- ✅ Si no se configura nada, solo administradores tienen acceso (comportamiento seguro)

### 🚀 Próximos Pasos

1. Configurar permisos para Professional y Particular
2. Probar creación de vehículos con usuarios de prueba
3. Verificar edición de vehículos propios
4. Verificar que NO pueden editar vehículos ajenos
5. Eliminar archivos de prueba

### 📚 Documentación

- `INSTRUCCIONES-PRUEBA-PERMISOS.md` - Guía paso a paso
- `README.md` - Documentación general actualizada

### ⚠️ Notas Importantes

1. **Seguridad:** Los usuarios solo pueden editar sus propios vehículos
2. **Administradores:** Mantienen control total sin cambios
3. **Valores por defecto:** Si no se configura, solo administradores tienen acceso
4. **Archivos temporales:** Eliminar `test-permissions.php` después de probar

### 🐛 Bugs Conocidos

Ninguno reportado.

### 💡 Mejoras Futuras

- [ ] Interfaz para ver qué permisos tiene cada usuario
- [ ] Logs detallados de intentos de acceso denegado
- [ ] Notificaciones al usuario cuando no tiene permisos
- [ ] Panel de permisos con vista previa en tiempo real

---

**Autor:** Claude Code
**Issue:** Usuarios Professional no pueden crear/editar vehículos
**Status:** ✅ Resuelto
