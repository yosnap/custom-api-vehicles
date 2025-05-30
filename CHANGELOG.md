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
