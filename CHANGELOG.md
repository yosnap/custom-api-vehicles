# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

<<<<<<< HEAD
## [2.2.1] - 2024-07-04
- Los conteos de facetas (facets) en el endpoint /vehicles ahora siempre son globales, reflejando el total de resultados filtrados, independientemente de la paginación.

## [2.2.1.1] - 2024-07-04
- Los facets de modelos (`models-cotxe`, `models-moto`) solo se calculan y devuelven si hay una marca seleccionada (`marques-cotxe` o `marques-moto`).
=======
## [Unreleased]
- Los conteos de facetas (facets) en el endpoint /vehicles ahora siempre son globales, reflejando el total de resultados filtrados, independientemente de la paginación.
>>>>>>> ce4e7f4 (docs: los conteos de facetas en /vehicles ahora siempre son globales (independientes de la paginación))
