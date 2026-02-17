# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4.0] - 2026-02-17

### Añadido
- **Glosarios dinámicos** - 8 funciones de opciones convertidas de arrays hardcoded a consultas dinámicas de JetEngine con fallback a valores por defecto
- **Validación de glosarios en PUT** - El endpoint PUT ahora valida campos de glosario antes de guardar, rechazando valores inválidos con mensaje de error claro y opciones disponibles
- **Reverse lookup en GET** - Los campos de glosario ahora soportan búsqueda inversa (label→key) para resolver valores correctamente

### Corregido
- **IDs de glosarios en validación** - Corregidos IDs inconsistentes en `$direct_mappings` de `validation.php` para alinearlos con la fuente de verdad (`class-glossary-mappings.php`)
- **Campo traccio vacío en GET** - Solucionado problema donde el campo `traccio` devolvía vacío al no encontrar coincidencia en el glosario, añadiendo fallback al valor original
- **Duplicación de mapeos de glosarios** - Eliminado mapa duplicado de IDs en `class-vehicle-field-handler.php`, delegando a `Vehicle_Glossary_Mappings` como fuente única

### Técnico
- Funciones convertidas a dinámicas: `get_cables_recarrega_options()`, `get_connectors_options()`, `get_emissions_vehicle_options()`, `get_traccio_options()`, `get_roda_recanvi_options()`, `get_color_vehicle_options()`, `get_tipus_tapisseria_options()`, `get_color_tapisseria_options()`
- `get_field_label()` en `field-processors.php` ahora incluye reverse lookup y fallback
- `validate_glossary_fields()` integrada en el flujo PUT antes de `process_and_save_meta_fields()`
- `Vehicle_Glossary_Mappings` es ahora la fuente única de verdad para IDs de glosarios

## [2.3.0] - 2025-12-01

### Añadido
- **Endpoint de gestión de imágenes** - Nuevo endpoint `DELETE /vehicles/{id}/images` para eliminar imágenes específicas por ID de WordPress
- **Endpoint para añadir imágenes** - Nuevo endpoint `POST /vehicles/{id}/images` para añadir imágenes a la galería sin eliminar las existentes
- **IDs de WordPress en respuestas** - Las respuestas de POST y PUT ahora incluyen los IDs de WordPress de las imágenes:
  - `imatge-destacada-wp-id`: ID de la imagen destacada
  - `galeria-vehicle-wp-ids`: Array de IDs de las imágenes de la galería
- **Funciones de eliminación selectiva** - Nueva función `delete_vehicle_images_by_id()` para eliminar imágenes específicas
- **Funciones de adición incremental** - Nueva función `add_images_to_gallery()` para añadir imágenes sin afectar las existentes

### Técnico
- Los endpoints de imágenes usan los mismos permisos que la edición de vehículos (`user_can_edit_vehicle`)
- El endpoint DELETE acepta:
  - `delete-featured-image`: boolean para eliminar la imagen destacada
  - `delete-gallery-ids`: array de IDs de WordPress a eliminar de la galería
- El endpoint POST acepta:
  - `gallery-urls`: array de URLs de imágenes a añadir
- Las imágenes eliminadas se borran completamente del media library de WordPress

### Propósito
- Permite a clientes externos (como Kars) gestionar imágenes de forma granular
- Evita la duplicación de imágenes al actualizar vehículos sincronizados
- Facilita la sincronización inteligente donde solo se envían los cambios de imágenes

## [2.2.6] - 2025-10-14

### Añadido
- **Sistema de permisos configurable** - Panel de administración para gestionar permisos de la API por rol de usuario
- **Página de configuración de permisos** - Interfaz en `WP Admin → API Motoraldia → Permisos` para configurar roles permitidos
- **Funciones helper de permisos** - Nuevas funciones para validar permisos: `user_can_create_vehicle()`, `user_can_edit_vehicle()`, `user_can_upload_images()`, `user_can_delete_vehicle()`
- **Soporte para roles Professional y Particular** - Ahora pueden crear, editar y gestionar vehículos a través de la API
- **Protección de propiedad** - Los usuarios solo pueden editar/eliminar sus propios vehículos (excepto administradores)
- **Tres niveles de permisos configurables**:
  - Crear vehículos (POST)
  - Editar vehículos (PUT)
  - Subir imágenes (imagen destacada y galería)

### Cambiado
- **Permission callbacks** - Actualizados en todos los endpoints para usar el nuevo sistema de permisos
- **Validación de permisos** - Ahora basada en configuración de roles en lugar de capabilities fijas de WordPress
- **Valores por defecto** - Si no se configura ningún rol, solo administradores tienen acceso (comportamiento seguro)

### Archivos Nuevos
- `includes/singlecar-endpoints/permission-helpers.php` - Funciones de validación de permisos
- `CHANGELOG-PERMISOS.md` - Documentación detallada de cambios de permisos
- `INSTRUCCIONES-PRUEBA-PERMISOS.md` - Guía paso a paso para probar el sistema
- `test-permissions.php` - Script de prueba (temporal, eliminar después de probar)

### Archivos Modificados
- `admin/views/permissions-page.php` - Nueva interfaz de configuración de permisos
- `includes/singlecar-endpoints/routes.php` - Actualizado permission_callback
- `includes/api/class-vehicle-controller.php` - Actualizado permission_callback
- `custom-api-vehicles.php` - Carga del sistema de permisos

### Técnico
- Opciones de WordPress: `vehicles_api_create_permissions`, `vehicles_api_edit_permissions`, `vehicles_api_image_permissions`
- Retrocompatibilidad completa con código existente
- Administradores mantienen acceso total sin cambios
- Sistema de seguridad: validación de propiedad del vehículo en edición/eliminación

### Corregido
- **Error 403 para usuarios Professional** - Ahora pueden crear y editar vehículos a través de la API
- **Error 403 para usuarios Particular** - Ahora pueden gestionar sus vehículos (si se configura)
- **Permisos no configurables** - El sistema anterior solo validaba capabilities de WordPress, ahora es completamente configurable

## [2.2.5] - 2025-09-22

### Corregido
- **Campo carrosseria-cotxe** - Corregido nombre del campo de "carroseria-cotxe" a "carrosseria-cotxe" en todo el sistema
- **Mapeo de glosarios** - Añadido mapeo específico para "carrosseria-cotxe" con ID 41 (mismo que segment)
- **Duplicados en configuración** - Eliminada línea duplicada en el array carroceria_fields

### Técnico
- Normalización de nombres de campos para carrocería de coches
- Consistencia en recepción y envío de datos del campo carrosseria-cotxe
- Mapeo unificado entre segment y carrosseria-cotxe

## [2.2.4] - 2025-09-22

### Corregido
- **Procesamiento de campos booleanos** - Los campos booleanos ahora respetan los valores del usuario en lugar de ser forzados a valores por defecto
- **Campo anunci-destacat** - Corregido para guardar como `is-vip` con valores 0/1 y por defecto `false`
- **Mapeo de campos** - Implementado mapeo bidireccional para compatibilidad de nombres de campos
- **Procesamiento de segment/carroseria-cotxe** - Campo de glosario ahora se guarda correctamente con ambos nombres
- **Campos numéricos** - `portes-cotxe`, `temps-recarrega-total`, `temps-recarrega-fins-80` ahora se guardan solo si están presentes
- **Arrays de glosario** - `cables-recarrega` y `connectors` ahora se procesan correctamente como arrays
- **Validación de edición** - Parámetro `$is_update` agregado para distinguir entre creación y edición
- **Exclusión de campos procesados** - Campos con procesamiento manual ahora se excluyen correctamente del bucle principal

### Técnico
- Mapeo de campos aplicado antes del procesamiento de glosarios
- Procesamiento específico para campos de array de glosario
- Manejo dual para `segment` y `carroseria-cotxe`
- Validación condicional de campos obligatorios solo para campos vacíos en edición

## [2.2.2.2] - 2025-07-14

### Añadido
- **Nuevo endpoint `/vehicles-all`** - Endpoint que devuelve TODOS los vehículos sin filtros por defecto
- **Acceso completo a datos** - Incluye vehículos vendidos, no vendidos, activos e inactivos
- **Compatibilidad total** - Mantiene la misma estructura de respuesta que `/vehicles`
- **Post status completo** - Incluye cualquier estado de post (publish, draft, etc.)
- **Filtros opcionales** - Permite aplicar filtros solo si se pasan explícitamente como parámetros

### Comparativa de endpoints
- `/vehicles`: 23 vehículos (con filtros por defecto - excluye vendidos)
- `/vehicles-all`: 47 vehículos (sin filtros - incluye todos)

### Técnico
- Creada función `get_all_singlecar()` en `get-handlers.php`
- Registrada ruta `/vehicles-all` en `routes.php`
- Documentación actualizada en README.md con comparativa de endpoints

## [2.2.2.1] - 2025-07-14

### Corregido
- **CRÍTICO: Campo anunci-destacat funcionando correctamente** - Solucionado problema donde campo `anunci-destacat` siempre devolvía 0 para todos los vehículos
- **Procesamiento de valores booleanos mejorado** - El campo `is-vip` ahora se procesa correctamente independientemente del formato ('true', 'false', boolean, etc.)
- **Función process_boolean_value()** - Nueva función para manejar diferentes formatos de valores booleanos ('true', 'yes', 'si', 'on', '1', etc.)
- **Mapeo de campos específicos** - Función `map_field_value()` para procesamiento específico de campos como `is-vip`
- **Campo tipus-canvi visible en listado** - Corregido cambio de tipo de campo de glossary a taxonomy

### Técnico
- Refactorizado procesamiento de campos booleanos en `field-processors.php`
- Añadida función `process_boolean_value()` para manejo robusto de valores booleanos
- Creada función `map_field_value()` para mapeo específico de campos
- Modificado `process_standard_field()` para usar el nuevo sistema de procesamiento
- Actualizado `class-vehicle-fields.php` para marcar `tipus-canvi` como taxonomy

## [2.2.2] - 2025-07-12

### Añadido
- **Página de configuración en admin** - Nueva sección "API Motoraldia" en wp-admin para gestionar cache y caducidad
- **Control de cache configurable** - Activar/desactivar cache desde la interfaz admin sin tocar código
- **Gestión de caducidad de anuncios** - Control completo sobre la caducidad automática de vehículos
- **Botón de limpieza de cache** - Herramienta para limpiar transients desde la interfaz
- **Configuración de duración de cache** - Opciones desde 5 minutos hasta 24 horas
- **Configuración de días de caducidad** - Personalizar días por defecto para expiración de anuncios

### Corregido
- **CRÍTICO: Filtro anunci-actiu funcionando correctamente** - Solucionado problema donde `anunci-actiu=true` no filtraba correctamente
- **Inconsistencia de tipos en anunci-actiu** - Normalizado para devolver siempre strings ('true'/'false') en lugar de boolean mixto
- **Problema de caducidad prematura** - Corregida lógica que marcaba como inactivos vehículos que deberían estar activos
- **Cache interfiriendo con desarrollo** - Ahora desactivado por defecto para permitir ver cambios inmediatamente
- **Endpoints individuales con lógica incorrecta** - Los endpoints `/vehicles/{id}` ahora usan la misma lógica que el listado
- **Problemas de codificación UTF-8** - Caracteres especiales en interfaz admin ahora se muestran correctamente

### Cambiado
- **Sistema de cache más inteligente** - Cache ahora respeta configuración desde admin en lugar de estar hardcodeado
- **Lógica de caducidad mejorada** - Puede desactivarse completamente o configurar días por defecto
- **Gestión unificada de anunci-actiu** - Misma lógica aplicada en todos los endpoints (listado, individual, por slug)
- **Mejor experiencia de desarrollo** - No más necesidad de limpiar transients manualmente

### Técnico
- Refactorizado sistema de cache para usar `get_option()` en lugar de constantes
- Añadida función `process_expiry()` unificada para manejo de caducidad
- Creado método `process_anunci_actiu()` en vehicle controller para consistencia
- Mejorado debug logging para filtro anunci-actiu
- Eliminado archivo config.php a favor de opciones de WordPress

## [2.0] - 2025-05-30

### Añadido
- Documentación completa de la API en el archivo `API-DOCUMENTATION.md`
- Implementación del parámetro `anunci-actiu` para filtrar vehículos por estado de activación

### Cambiado
- Mejora en el sistema de gestión de logs y mensajes de debug
- Reemplazo de todas las llamadas a `error_log` por el sistema personalizado `Vehicle_Debug_Handler`
- Actualización de la documentación en README.md

### Corregido
- Eliminación de mensajes de debug innecesarios que se enviaban al log del sistema
- Corrección de formato en ejemplos JSON en la documentación

## [1.7.7] - 2025-04-15

### Añadido
- Soporte inicial para la API REST de vehículos
- Endpoints básicos para CRUD de vehículos
- Soporte para taxonomías y glosarios

## [2.1] - 2024-06-01

### Añadido
- Campo `author_id` en la respuesta de vehículos individuales
- Nuevos campos detallados en la respuesta de sellers (teléfonos, dirección, contacto, galería, etc.) al consultar un vendedor específico
- Cálculo real de vehículos totales y activos para cada vendedor
- Ejemplos de payloads actualizados en la documentación
- Parámetro de ordenación `featured` (destacados primero) y resto de opciones de ordenación en el endpoint de vehículos
- Ejemplos de consulta de vehículos por usuario y estado en la documentación

### Cambiado
- La respuesta de la lista de sellers ahora solo incluye los campos principales (id, username, email, name, registered_date, role, total_vehicles, active_vehicles)
- El parámetro de ordenación para destacados primero es ahora `featured` en vez de `vip_first`
- Documentación y ejemplos actualizados en README.md y API-DOCUMENTATION.md

### Corregido
- Ahora los endpoints de sellers devuelven correctamente el total y activos de cada vendedor
- Mejoras menores de formato y consistencia en la documentación

## [2.2] - 2024-06-01

### Añadido
- Filtro exacto por `anunci-actiu` en el endpoint de vehículos: solo devuelve los ítems activos o inactivos según el parámetro.
- Ordenación por destacados con el parámetro `orderby=featured` (primero los que tienen `is-vip='true'`).
- Documentación actualizada con ejemplos de filtrado y ordenación.

## [2.3] - 2024-06-01

### Añadido
- Nueva lógica para el filtro `venut` en el endpoint de vehículos:
  - Si no se pasa el parámetro, solo se muestran los vehículos no vendidos o que no tienen el campo (disponibles).
  - Si se pasa `venut=false`, solo los que tienen el campo explícitamente en 'false'.
  - Si se pasa `venut=true`, solo los vendidos.
- Documentación actualizada para reflejar este comportamiento.

## [2.2.0] - YYYY-MM-DD
### Añadido
- Endpoints REST para filtrar vehículos por:
  - Estado (`estat-vehicle`)
  - Tipo de combustible (`tipus-combustible`)
  - Tipo de propulsor (`tipus-propulsor`)
  - Tipo de vehículo (`tipus-vehicle`)
  - Marca de coche (`marques-cotxe`)
  - Marca de moto (`marques-moto`)
- Endpoints anidados para modelos bajo marca:
  - `/marques-cotxe/{marca}/{modelo}`
  - `/marques-moto/{marca}/{modelo}`
- Todos los endpoints permiten paginación, orden y devuelven la respuesta completa de vehículos.

## [2.2.1.2] - 2025-01-06
### Añadido
- Sistema completo de marcas y modelos por tipo de vehículo con detección automática:
  - **Coches** (por defecto): `marques-cotxe` y `models-cotxe` (taxonomía: `marques-coches`)
  - **Autocaravanas** (cuando `tipus-vehicle` contiene "autocaravana" o "camper"): `marques-autocaravana` y `models-autocaravana` (taxonomía: `marques-coches`)
  - **Vehículos comerciales** (cuando `tipus-vehicle` contiene "comercial"): `marques-comercial` y `models-comercial` (taxonomía: `marques-coches`)
  - **Motos/Quad/ATV** (taxonomía específica): `marques-moto` y `models-moto` (taxonomía: `marques-de-moto`)
- Filtros por parámetros para cada tipo de vehículo:
  - `marques-cotxe` y `models-cotxe` para coches
  - `marques-autocaravana` y `models-autocaravana` para autocaravanas
  - `marques-comercial` y `models-comercial` para vehículos comerciales
  - `marques-moto` y `models-moto` para motos
- Endpoint `/clear-cache` (DELETE) para limpiar transientes del cache
- Facetas inteligentes: los modelos solo se muestran cuando hay una marca seleccionada

### Mejorado
- Asignación automática de campos de marca/modelo según el tipo de vehículo
- Los conteos de facetas son globales (independientes de la paginación)
- Optimización de consultas para marcas y modelos

### Corregido
- Filtrado correcto por marca de moto (`marques-moto` en lugar de `marques-de-moto`)
- Conflictos de merge en el código de facetas

## [2.2.1.1] - 2024-07-04
- Los facets de modelos (`models-cotxe`, `models-moto`) solo se calculan y devuelven si hay una marca seleccionada (`marques-cotxe` o `marques-moto`).

## [2.2.1] - 2024-07-04
- Los conteos de facetas (facets) en el endpoint /vehicles ahora siempre son globales, reflejando el total de resultados filtrados, independientemente de la paginación.
