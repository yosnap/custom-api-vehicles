# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.3] - 2025-07-27

### 🔄 BREAKING CHANGE: API devuelve Values en lugar de Labels

#### 🆕 Añadido

- Configuración global para devolver valores/slugs en lugar de etiquetas traducidas
- Consistencia total en la respuesta de la API para todos los campos
- Mejora en la procesabilidad de datos para frontends

#### 🔧 Cambiado

- **CRÍTICO**: Todos los campos de taxonomía ahora devuelven slugs (`cotxe` en lugar de `Coche`)
- **CRÍTICO**: Todos los campos de glosario ahora devuelven values (`aire-acondicionat` en lugar de `Aire acondicionado`)
- **CRÍTICO**: Arrays de campos devuelven arrays de values en lugar de labels
- Función `should_get_field_label()` modificada para devolver siempre `false`
- Campos de taxonomía modificados para usar `->slug` en lugar de `->name`

#### 💡 Mantenido (Flexibilidad)

- **POST/PUT endpoints**: Continúan aceptando tanto values como labels para facilitar la migración
- **Validación inteligente**: Reconoce automáticamente si el valor enviado es value o label
- **Compatibilidad**: Los sistemas existentes pueden seguir enviando labels sin romper

#### 📝 Documentación

- README.md actualizado con ejemplos de respuesta que reflejan los nuevos values
- Documentación de migración añadida para desarrolladores frontend
- Ejemplos comparativos entre versiones anteriores y actual

#### 🔧 Técnico

- Modificado `includes/singlecar-endpoints/field-processors.php`
- Modificado `includes/singlecar-endpoints/get-handlers.php`
- Cambios aplicados tanto a listados como a vehículos individuales
- Mantenida compatibilidad con estructura de datos existente

#### ⚠️ Migración Requerida

Los frontends que consumen esta API deberán:

1. Actualizar el procesamiento de respuestas para manejar values en lugar de labels
2. Implementar traducción local de values a labels visibles
3. Actualizar filtros y búsquedas para usar values en lugar de labels
4. Verificar que el manejo de arrays de extras funcione correctamente

## [2.3.0] - 2025-06-05log

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
