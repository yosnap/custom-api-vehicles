# Documentación Oficial: Custom API Vehicles for Motoraldia

**Versión:** 2.0  
**Namespace:** `api-motor/v1`  
**Tipo de contenido:** `singlecar`

## Introducción

Custom API Vehicles for Motoraldia es un plugin de WordPress que proporciona una API REST personalizada para la gestión completa de vehículos. Esta API permite crear, leer, actualizar y eliminar vehículos de diferentes tipos (coches, motos, autocaravanas y vehículos comerciales), así como acceder a taxonomías y glosarios relacionados.

## Requisitos

- WordPress 5.0 o superior
- Plugin JetEngine activado
- Permisos adecuados para cada tipo de operación

## Autenticación

La API utiliza la autenticación estándar de WordPress. Para operaciones de lectura pública, no se requiere autenticación. Para operaciones de escritura (crear, actualizar, eliminar), se requiere autenticación mediante uno de los siguientes métodos:

- Cookie de WordPress (para aplicaciones web)
- Autenticación básica (para aplicaciones de terceros)
- OAuth (si está configurado)

## Gestión de Errores

La API devuelve códigos de estado HTTP estándar:

- `200`: Operación exitosa
- `201`: Recurso creado correctamente
- `400`: Solicitud incorrecta (datos inválidos)
- `401`: No autorizado
- `403`: Prohibido (sin permisos suficientes)
- `404`: Recurso no encontrado
- `500`: Error interno del servidor

Los errores incluyen un objeto JSON con detalles adicionales:

```json
{
  "status": "error",
  "message": "Descripción del error",
  "code": "código_error"
}
```

## Endpoints Disponibles

### Vehículos

#### GET /wp-json/api-motor/v1/vehicles/tipus-vehicle

Obtiene los tipos de vehículos disponibles.

**Respuesta:**
```json
{
  "status": "success",
  "total": 4,
  "data": [
    {"name": "AUTOCARAVANA", "value": "autocaravana-camper"},
    {"name": "COTXE", "value": "cotxe"},
    {"name": "MOTO", "value": "moto-quad-atv"},
    {"name": "VEHICLE COMERCIAL", "value": "vehicle-comercial"}
  ]
}
```

#### GET /wp-json/api-motor/v1/vehicles

Obtiene una lista de vehículos.

**Parámetros:**

| Parámetro | Tipo | Descripción | Requerido | Valor por defecto |
|-----------|------|-------------|-----------|-------------------|
| page | integer | Número de página | No | 1 |
| per_page | integer | Elementos por página | No | 10 |
| orderby | string | Campo para ordenar | No | date |
| order | string | Dirección de ordenación (ASC, DESC) | No | DESC |
| brand | string | ID de la marca para filtrar | No | - |
| user_id | integer | Filtrar por ID de usuario | No | - |
| post_id | integer | Filtrar por ID específico | No | - |
| post_name | string | Filtrar por slug | No | - |
| anunci-actiu | boolean | Filtrar por estado de activación (true: solo anuncios activos, false: solo anuncios inactivos, omitir: todos) | No | Todos |

**Respuesta:**

```json
[
  {
    "id": 123,
    "data-creacio": "2025-04-01 10:00:00",
    "status": "publish",
    "slug": "ejemplo-vehiculo",
    "titol-anunci": "Título del anuncio",
    "descripcio-anunci": "Descripción del anuncio",
    "tipus-vehicle": "cotxe",
    "marca": "Marca del vehículo",
    "model": "Modelo del vehículo",
    "preu": "25000",
    "quilometres": "50000",
    "any": "2020",
    "combustible": "gasolina",
    "potencia": "150",
    "canvi": "manual",
    "color-vehicle": "Blanco",
    "extres-cotxe": ["Extra 1", "Extra 2"]
  }
]
```

**Headers de respuesta:**
- `X-WP-Total`: Total de vehículos encontrados
- `X-WP-TotalPages`: Total de páginas disponibles

#### POST /wp-json/api-motor/v1/vehicles

Crea un nuevo vehículo.

**Notas importantes:**

- El título del vehículo se genera automáticamente usando el formato: `{Marca} {MODELO} {versión}`
- Se requiere autenticación (usuario con capacidad `edit_posts`)
- Soporta carga de archivos para imágenes

**Campos requeridos:**

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| tipus-vehicle | string | Tipo de vehículo (cotxe, moto-quad-atv, autocaravana-camper, vehicle-comercial) | Sí |
| estat-vehicle | string | Estado del vehículo | Sí |
| marques-cotxe | string | Marca del vehículo (para coches, autocaravanas y vehículos comerciales) | Condicional |
| models-cotxe | string | Modelo del vehículo (para coches, autocaravanas y vehículos comerciales) | Condicional |
| marques-de-moto | string | Marca de la moto (solo para motos) | Condicional |
| preu | string | Precio del vehículo | Sí |
| quilometres | string | Kilometraje | Sí |
| any | string | Año de fabricación | Sí |
| combustible | string | Tipo de combustible | Sí |
| potencia | string | Potencia en CV | Sí |

**Campos específicos por tipo de vehículo:**

- **Coches (cotxe)**: extres-cotxe, tipus-tapisseria, color-tapisseria, portes-cotxe
- **Motos (moto-quad-atv)**: extres-moto, tipus-de-moto
- **Autocaravanas (autocaravana-camper)**: extres-autocaravana, carrosseria-caravana, extres-habitacle
- **Vehículos Comerciales (vehicle-comercial)**: carroseria-vehicle-comercial

**Campos de imágenes:**

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| featured_image | file/URL | Imagen destacada del vehículo | No |
| gallery | array | Array de imágenes para la galería | No |

**Respuesta:**

```json
{
  "id": 123,
  "data-creacio": "2025-04-01 10:00:00",
  "status": "publish",
  "slug": "ejemplo-vehiculo",
  "titol-anunci": "Título del anuncio",
  "descripcio-anunci": "Descripción del anuncio",
  "tipus-vehicle": "cotxe",
  "marca": "Marca del vehículo",
  "model": "Modelo del vehículo"
}
```

#### GET /wp-json/api-motor/v1/vehicles/{id}

Obtiene detalles de un vehículo específico.

**Parámetros:**
- `id`: ID del vehículo (requerido)

**Respuesta:**
Misma estructura que un elemento individual de la lista de vehículos, pero con todos los campos disponibles.

#### PUT /wp-json/api-motor/v1/vehicles/{id}

Actualiza un vehículo existente.

**Parámetros:**
- `id`: ID del vehículo a actualizar (requerido)
- Campos a actualizar (similar a la creación, pero opcionales)

**Respuesta:**
Objeto completo del vehículo actualizado.

#### DELETE /wp-json/api-motor/v1/vehicles/{id}

Elimina (mueve a papelera) un vehículo.

**Parámetros:**
- `id`: ID del vehículo a eliminar (requerido)

**Respuesta:**
```json
{
  "status": "success",
  "message": "Vehículo eliminado correctamente"
}
```

### Taxonomías y Glosarios

#### Vehículos

##### GET /wp-json/api-motor/v1/tipus-vehicle

Obtiene los tipos de vehículos disponibles.

**Respuesta:**
```json
{
  "status": "success",
  "total": 4,
  "data": [
    {"name": "AUTOCARAVANA", "value": "autocaravana-camper"},
    {"name": "COTXE", "value": "cotxe"},
    {"name": "MOTO", "value": "moto-quad-atv"},
    {"name": "VEHICLE COMERCIAL", "value": "vehicle-comercial"}
  ]
}
```

##### GET /wp-json/api-motor/v1/estat-vehicle

Obtiene los estados posibles de un vehículo.

**Respuesta:**
```json
{
  "status": "success",
  "total": 3,
  "data": [
    {"name": "Nou", "value": "nou"},
    {"name": "Seminou", "value": "seminou"},
    {"name": "Ocasió", "value": "ocasio"}
  ]
}
```

##### GET /wp-json/api-motor/v1/tipus-combustible

Obtiene los tipos de combustible disponibles.

**Respuesta:**
```json
{
  "status": "success",
  "total": 6,
  "data": [
    {"name": "Gasolina", "value": "gasolina"},
    {"name": "Dièsel", "value": "diesel"},
    {"name": "Híbrid", "value": "hibrid"},
    {"name": "Elèctric", "value": "electric"},
    {"name": "GLP", "value": "glp"},
    {"name": "Altres", "value": "altres"}
  ]
}
```

##### GET /wp-json/api-motor/v1/tipus-canvi-cotxe

Obtiene los tipos de cambio disponibles.

**Respuesta:**
```json
{
  "status": "success",
  "total": 2,
  "data": [
    {"name": "Manual", "value": "manual"},
    {"name": "Automàtic", "value": "automatic"}
  ]
}
```

##### GET /wp-json/api-motor/v1/tipus-propulsor

Obtiene los tipos de propulsor disponibles.

#### Marcas y Modelos

##### GET /wp-json/api-motor/v1/marques-cotxe

Lista todas las marcas de coches.

**Respuesta:**
```json
{
  "status": "success",
  "total": 50,
  "data": [
    {"value": "audi", "label": "Audi"},
    {"value": "bmw", "label": "BMW"},
    // ... más marcas
  ]
}
```

##### GET /wp-json/api-motor/v1/marques-cotxe?marca={slug}

Obtiene modelos de una marca específica de coche.

**Parámetros:**
- `marca`: Slug de la marca (requerido)

**Respuesta:**
```json
{
  "status": "success",
  "total": 15,
  "data": [
    {"value": "a3", "label": "A3"},
    {"value": "a4", "label": "A4"},
    // ... más modelos
  ]
}
```

##### GET /wp-json/api-motor/v1/marques-moto

Lista todas las marcas de motos.

**Respuesta:**
Similar a la respuesta de marcas de coches.

##### GET /wp-json/api-motor/v1/marques-moto?marca={slug}

Obtiene modelos de una marca específica de moto.

**Parámetros:**
- `marca`: Slug de la marca (requerido)

**Respuesta:**
Similar a la respuesta de modelos de coches.

#### Carrocerías

##### GET /wp-json/api-motor/v1/carrosseria-cotxe

Obtiene los tipos de carrocería para coches.

##### GET /wp-json/api-motor/v1/carrosseria-moto

Obtiene los tipos de carrocería para motos.

##### GET /wp-json/api-motor/v1/carrosseria-caravana

Obtiene los tipos de carrocería para caravanas.

##### GET /wp-json/api-motor/v1/carrosseria-comercial

Obtiene los tipos de carrocería para vehículos comerciales.

#### Extras

##### GET /wp-json/api-motor/v1/extres-cotxe

Obtiene los extras disponibles para coches.

##### GET /wp-json/api-motor/v1/extres-moto

Obtiene los extras disponibles para motos.

##### GET /wp-json/api-motor/v1/extres-caravana

Obtiene los extras disponibles para caravanas.

##### GET /wp-json/api-motor/v1/extres-habitacle

Obtiene los extras de habitáculo disponibles.

#### Colores y Tapicería

##### GET /wp-json/api-motor/v1/colors-exterior

Obtiene los colores exteriores disponibles.

##### GET /wp-json/api-motor/v1/tapiceria

Obtiene los tipos de tapicería disponibles.

##### GET /wp-json/api-motor/v1/colors-tapiceria

Obtiene los colores de tapicería disponibles.

### Endpoints de Administración

#### GET /wp-json/api-motor/v1/glosarios

Lista todos los glosarios disponibles.

**Permisos requeridos:** Administrador

#### GET /wp-json/api-motor/v1/debug-fields

Proporciona información detallada sobre los campos disponibles.

**Permisos requeridos:** Administrador

#### GET /wp-json/api-motor/v1/authors

Lista de autores (para administradores) o información del autor actual (para usuarios autenticados).

#### GET /wp-json/api-motor/v1/diagnostic

Proporciona información de diagnóstico sobre el sistema.

**Permisos requeridos:** Administrador

**Respuesta:**
```json
{
  "jet_engine_active": true,
  "taxonomies": ["array_de_taxonomias"],
  "post_types": ["array_de_tipos_de_post"],
  "meta_boxes": {},
  "glossaries": {},
  "php_version": "8.0.0",
  "wp_version": "6.0"
}
```

### Endpoints de Vendedores

#### GET /wp-json/api-motor/v1/sellers

Obtiene información de vendedores profesionales.

## Manejo de Imágenes

La API permite subir imágenes para los vehículos de dos formas:

1. **URLs externas**: Enviando URLs en los campos `featured_image` y `gallery`
2. **Carga directa**: Enviando archivos mediante formularios multipart

### Ejemplo de carga de imágenes:

```javascript
// Usando FormData en JavaScript
const formData = new FormData();
formData.append('tipus-vehicle', 'cotxe');
formData.append('preu', '25000');
// ... otros campos

// Imagen destacada
formData.append('featured_image', fileInputElement.files[0]);

// Galería (múltiples imágenes)
for (let i = 0; i < galleryInputElement.files.length; i++) {
  formData.append('gallery[]', galleryInputElement.files[i]);
}

fetch('/wp-json/api-motor/v1/vehicles', {
  method: 'POST',
  body: formData,
  credentials: 'include' // Para incluir cookies de autenticación
})
```

## Sistema de Logging

El plugin incluye un sistema de registro personalizado que captura todas las operaciones realizadas en los vehículos. Este sistema almacena la información en una tabla dedicada en la base de datos de WordPress.

### Estructura de la tabla de logs:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único del registro |
| user_id | bigint(20) | ID del usuario que realizó la acción |
| vehicle_id | bigint(20) | ID del vehículo afectado |
| action | varchar(50) | Tipo de acción (create, update, delete) |
| details | text | Detalles adicionales en formato JSON |
| created_at | datetime | Fecha y hora de la acción |

Los mensajes de debug se gestionan a través de la clase `Vehicle_Debug_Handler`, que proporciona un control centralizado sobre los mensajes de depuración.

## Consideraciones de Rendimiento

- La API implementa paginación para evitar sobrecarga en solicitudes con muchos resultados
- Se recomienda limitar el número de elementos por página (parámetro `per_page`) a un máximo de 50
- Para operaciones de creación y actualización con imágenes, se recomienda optimizar las imágenes antes de enviarlas

## Ejemplos de Uso

### Obtener lista de vehículos:

```javascript
fetch('/wp-json/api-motor/v1/vehicles?page=1&per_page=10')
  .then(response => response.json())
  .then(data => console.log(data));
```

### Crear un nuevo vehículo:

```javascript
const vehicleData = {
  "tipus-vehicle": "cotxe",
  "estat-vehicle": "nou",
  "marques-cotxe": "audi",
  "models-cotxe": "a3",
  "preu": "35000",
  "quilometres": "0",
  "any": "2025",
  "combustible": "electric",
  "potencia": "204",
  "canvi": "automatic",
  "color-vehicle": "Blanc",
  "extres-cotxe": ["climatitzacio", "navegador", "bluetooth"]
};

fetch('/wp-json/api-motor/v1/vehicles', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(vehicleData),
  credentials: 'include'
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### Ejemplo de respuesta de un vehículo individual
```json
{
  "id": 123,
  "author_id": 45,
  "data-creacio": "2024-06-01 12:00:00",
  "status": "publish",
  "slug": "ford-focus-2024",
  "titol-anunci": "Ford Focus 2024",
  "descripcio-anunci": "Vehículo en perfecto estado...",
  "anunci-actiu": "true",
  "anunci-destacat": "true",
  "tipus-vehicle": "Cotxe",
  "marques-cotxe": "Ford",
  "models-cotxe": "Focus",
  "imatge-destacada-url": "https://...",
  "galeria-vehicle-urls": ["https://...", "https://..."]
  // ...otros campos personalizados...
}
```

### Ejemplo de respuesta de sellers (lista)
```json
{
  "status": "success",
  "total": 2,
  "data": [
    {
      "id": 45,
      "username": "vendedor1",
      "email": "vendedor1@email.com",
      "name": "Nombre Vendedor",
      "registered_date": "2024-01-01 10:00:00",
      "role": "professional",
      "total_vehicles": 10,
      "active_vehicles": 8
    }
    // ...más vendedores...
  ]
}
```

### Ejemplo de respuesta de sellers (detalle)
```json
{
  "status": "success",
  "data": {
    "id": 45,
    "username": "vendedor1",
    "email": "vendedor1@email.com",
    "name": "Nombre Vendedor",
    "registered_date": "2024-01-01 10:00:00",
    "role": "professional",
    "logo-empresa": "https://...",
    "logo-empresa-home": "https://...",
    "nom-empresa": "Empresa S.L.",
    "telefon-mobile-professional": "600123456",
    "telefon-comercial": "934567890",
    "telefon-whatsapp": "600123456",
    "localitat-professional": "Barcelona",
    "adreca-professional": "Calle Falsa 123",
    "nom-contacte": "Juan",
    "cognoms-contacte": "Pérez",
    "descripcio-empresa": "Concesionario oficial...",
    "pagina-web": "https://empresa.com",
    "galeria-professionals": [
      "https://.../img1.jpg",
      "https://.../img2.jpg"
    ],
    "total_vehicles": 10,
    "active_vehicles": 8
  }
}
```

### Parámetros de ordenación soportados en /vehicles

| Opción UI                | Parámetro `orderby` | Parámetro `order` | Descripción                                 |
|--------------------------|---------------------|-------------------|---------------------------------------------|
| Destacados primero       | featured            | -                 | Destacados primero, luego más recientes     |
| Precio: menor a mayor    | price               | ASC               | Precio ascendente                           |
| Precio: mayor a menor    | price               | DESC              | Precio descendente                          |
| Más recientes            | date                | DESC              | Fecha de publicación descendente            |
| Más antiguos             | date                | ASC               | Fecha de publicación ascendente             |
| Alfabético (A-Z)         | title               | ASC               | Título ascendente                           |
| Alfabético (Z-A)         | title               | DESC              | Título descendente                          |

### Ejemplos de consulta de vehículos por usuario y estado

- Todos los vehículos de un usuario:
  `/wp-json/api-motor/v1/vehicles?user_id=45`
- Solo activos:
  `/wp-json/api-motor/v1/vehicles?user_id=45&anunci-actiu=true`
- Solo inactivos:
  `/wp-json/api-motor/v1/vehicles?user_id=45&anunci-actiu=false`
- Solo vendidos (si existe el campo `venut`):
  `/wp-json/api-motor/v1/vehicles?user_id=45&venut=true`

## Soporte y Contacto

Para soporte técnico o consultas sobre la API, contacte con el equipo de desarrollo en [soporte@motoraldia.com](mailto:soporte@motoraldia.com).

---

Esta documentación está sujeta a actualizaciones. Última actualización: Mayo 2025.
