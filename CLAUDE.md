# CLAUDE.md

Este archivo proporciona guía a Claude Code (claude.ai/code) cuando trabaja con código en este repositorio.

## Información del Proyecto

**Plugin WordPress:** Custom API Vehicles para Motoraldía
**Versión:** 2.2.3
**Namespace API:** `api-motor/v1`
**Tipo de contenido:** `singlecar`

## Estructura de Archivos Principales

```
custom-api-vehicles.php          # Plugin principal - carga dependencias y configura filtros REST
├── admin/
│   ├── class-admin-menu.php     # Menú de administración WP
│   ├── class-glossary-mappings.php  # Mapeos de glosarios automáticos
│   └── views/                   # Vistas del panel admin
├── includes/
│   ├── api/
│   │   ├── class-vehicle-controller.php    # Controlador principal de vehículos
│   │   └── get-glossary-options-endpoint.php  # Endpoint opciones glosario
│   ├── singlecar-endpoints/     # Lógica modular de endpoints
│   │   ├── routes.php           # Definición de rutas REST
│   │   ├── get-handlers.php     # Handlers GET (listados, detalles)
│   │   ├── post-handlers.php    # Handlers POST (creación)
│   │   ├── put-handlers.php     # Handlers PUT (actualización)
│   │   ├── delete-handlers.php  # Handlers DELETE
│   │   ├── field-processors.php # Procesamiento de campos y validación
│   │   ├── validation.php       # Validaciones de glosarios y campos
│   │   ├── media-handlers.php   # Manejo de imágenes y galerías
│   │   ├── cache-handlers.php   # Sistema de caché configurable
│   │   ├── meta-handlers.php    # Manejo de metadatos
│   │   └── utils.php            # Utilidades comunes
│   ├── class-vehicle-fields.php # Configuración de campos de vehículos
│   ├── class-glossary-fields.php # Gestión de glosarios (validación)
│   ├── class-api-logger.php     # Sistema de logging personalizado
│   ├── class-debug-handler.php  # Handler de debug
│   ├── taxonomy-endpoints.php   # Endpoints de taxonomías
│   ├── author-endpoint.php      # Endpoint de autores/sellers
│   └── singlecar-endpoint.php   # Funciones legacy de vehículos
```

## Arquitectura del Sistema

### Endpoints Principales
- **`/vehicles`** - Lista con filtros por defecto (excluye vendidos)
- **`/vehicles-all`** - Lista sin filtros (incluye todos)
- **`/vehicles-labels`** - Lista con etiquetas traducidas para frontend
- **`/vehicles/{id}`** - Detalle individual
- **`/vehicles-labels/{id}`** - Detalle con etiquetas traducidas

### Sistema de Campos por Tipo de Vehículo
El plugin implementa campos dinámicos según `tipus-vehicle`:
- **Coches**: `marques-cotxe`, `models-cotxe`, `extres-cotxe`
- **Motos**: `marques-moto`, `models-moto`, `extres-moto`, `tipus-de-moto`
- **Autocaravanas**: `marques-autocaravana`, `carrosseria-caravana`, `extres-autocaravana`
- **Vehículos comerciales**: `marques-comercial`, `carroseria-vehicle-comercial`

### Mapeos de Tipos (Vehicle_Glossary_Mappings)
```php
// Definidos en CLAUDE.local.md
"MOTO-QUAD-ATV" => motos, quadbikes, ATVs
"AUTOCARAVANA-CAMPER" => caravanas
"VEHICLE-COMERCIAL" => vehículos comerciales
```

### Sistema de Glosarios
- **ID-based**: Cada glosario tiene un ID numérico (ej: 42 = tipus-de-moto)
- **Validación automática**: Se validan values/labels contra glosarios
- **Mapeos por defecto**: 20+ campos pre-configurados automáticamente
- **Flexibilidad**: Acepta tanto values (`gasolina`) como labels (`Gasolina`)

## Comandos de Desarrollo Comunes

### WordPress/Plugin
```bash
# No hay composer.json - usa estructura WordPress estándar
# Activar plugin desde wp-admin
# Logs en: wp-content/debug.log (si WP_DEBUG activado)
```

### Testing
```bash
# No hay tests automatizados definidos
# Testing manual vía:
# - Postman/curl contra endpoints REST
# - Panel admin WordPress
# - Logs del plugin en base de datos
```

### Cache y Debug
```bash
# Limpiar cache desde admin o vía endpoint:
curl -X DELETE "/wp-json/api-motor/v1/clear-cache"

# Ver logs:
# - Panel admin: WP Admin → API Motoraldia
# - Base datos: tabla {prefix}_vehicle_api_logs
```

## Convenciones de Código

### Estructura de Respuesta API
```php
// GET endpoints devuelven values por defecto
"combustible": "gasolina"        // NOT "Gasolina"
"extres-cotxe": ["aire-acondicionat", "bluetooth"]  // NOT ["Aire acondicionado", "Bluetooth"]

// Endpoints -labels devuelven labels para frontend
"combustible": "Gasolina"
"extres-cotxe": ["Aire acondicionado", "Bluetooth"]
```

### Nombres de Archivos y Clases
- **Clases PHP**: `class-nombre-archivo.php`
- **Endpoints modulares**: `{accion}-handlers.php`
- **Prefijo clases**: `Vehicle_`, `Glossary_`, sin namespace

### Campos de Base de Datos
- **Custom Fields**: Prefijo según tipo (ej: `extres-cotxe`, `extres-moto`)
- **Metafields especiales**: `is-vip`, `anunci-actiu`, `venut`
- **Taxonomías**: `marques-cotxe`, `marques-de-moto`, `tipus-vehicle`, etc.

## Dependencias Importantes

### WordPress Requeridas
- **JetEngine**: Plugin requerido para custom fields y taxonomías
- **WordPress REST API**: Extendido con namespace personalizado

### Configuración Admin
- **Panel admin**: WP Admin → API Motoraldia
- **Cache configurable**: activar/desactivar desde interfaz
- **Glosarios**: mapeos automáticos + configuración manual disponible

## Flujo de Desarrollo Típico

1. **Modificar lógica endpoints**: `includes/singlecar-endpoints/{tipo}-handlers.php`
2. **Añadir campos**: `includes/class-vehicle-fields.php` + `includes/class-glossary-fields.php`
3. **Nuevas rutas**: `includes/singlecar-endpoints/routes.php`
4. **Validaciones**: `includes/singlecar-endpoints/validation.php`
5. **Testing**: endpoints vía curl/Postman + verificar logs
6. **Documentación**: actualizar README.md + CHANGELOG.md

## Notas de Seguridad

- **Autenticación**: Basic Auth + WordPress users
- **Permisos**: Solo propietarios pueden modificar sus vehículos
- **Validación**: Todos los campos validados contra glosarios
- **Sanitización**: WordPress native + validaciones custom