# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.3.0] - 2025-06-05

### 🆕 Añadido

- Sistema de validación de dependencias mejorado con clase `Vehicle_Plugin_Dependencies`
- Endpoint de diagnóstico completamente renovado con información detallada del sistema
- Clase `DiagnosticHelpers` para pruebas de conectividad y análisis de la API
- **Debug handler completamente reescrito con métodos especializados:**
  - `log_api_error()` para errores específicos de API
  - `log_validation_error()` para fallos de validación
  - `log_success()` para operaciones exitosas
  - `log_security_event()` para eventos de seguridad
  - `log_performance()` para medición de tiempos de ejecución
  - `get_debug_stats()` para estadísticas de debug
  - `cleanup_old_logs()` para limpieza automática
- Endpoints administrativos para gestión de logs (`/debug/stats`, `/debug/cleanup`, `/debug/recent-logs`)
- Limpieza automática de logs programada semanalmente
- Sistema mejorado de carga de dependencias con verificación de archivos
- Endpoint para opciones de glosario (`get-glossary-options-endpoint.php`)
- Mejoras en la interfaz de administración con páginas específicas para logs y permisos

### 🔧 Cambiado

- **IMPORTANTE**: Restaurada la seguridad de la API REST con validación inteligente de permisos
- Sistema de debug completamente reescrito con niveles de logging (error, warning, info, debug)
- Refactorización del método `fix_rest_api_permissions()` con mayor granularidad
- Mejores mensajes de error con información contextual para administradores
- Estructura de archivos reorganizada para mejor mantenimiento
- Documentación del código mejorada con comentarios detallados

### 🐛 Corregido

- Eliminadas referencias a archivos CSS/JS inexistentes que causaban errores 404
- Implementación correcta de autenticación para métodos POST, PUT, DELETE y PATCH
- Mantenimiento de acceso público solo para métodos GET en endpoints específicos
- Validación de dependencias con avisos informativos en lugar de errores genéricos
- Optimizada la carga de dependencias evitando includes duplicados

### 🛡️ Seguridad

- Restauración completa del sistema de permisos de la API REST
- Verificación de autenticación obligatoria para operaciones que modifican datos
- Logging de intentos de acceso no autorizados con IP del cliente
- Control granular de permisos por namespace y método HTTP
- Validación mejorada de rutas protegidas

### 📝 Documentación

- README.md actualizado con estructura de archivos completa
- CHANGELOG.md detallado con categorización de cambios
- Documentación de nuevos endpoints y funcionalidades
- Guías de migración desde versiones anteriores
- Comentarios de código mejorados y más descriptivos

### 🔧 Técnico

- Nueva estructura de archivos: `class-dependencies.php`, `class-diagnostic-helpers.php`, `enhanced-diagnostic-endpoint.php`
- Compatibilidad mejorada con entornos de desarrollo y producción
- Sistema de constantes para habilitar debug específico del plugin (`VEHICLE_API_DEBUG`)
- Optimización de carga de dependencias con verificación de archivos
- Estructura modular mejorada para mantenimiento y escalabilidad

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
