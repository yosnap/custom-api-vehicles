# Documentación Oficial: Custom API Vehicles for Motoraldia

**Versión:** 2.2.3
**Namespace:** `api-motor/v1`
**Tipo de contenido:** `singlecar`

## Introducción

Custom API Vehicles for Motoraldia es un plugin de WordPress que proporciona una API REST personalizada para la gestión completa de vehículos. Esta API permite crear, leer, actualizar y eliminar vehículos de diferentes tipos (coches, motos, autocaravanas y vehículos comerciales), así como acceder a taxonomías y glosarios relacionados.

## Novedad v2.2.3: Mapeos de Glosarios Automáticos

### 🆕 Mapeos por Defecto

A partir de la versión 2.2.3, el plugin incluye mapeos automáticos para más de 20 campos de glosario, eliminando la necesidad de configuración manual. Los campos ahora muestran correctamente:

- **Endpoints regulares** (`/vehicles`): Values/slugs (ej: "escuter")
- **Endpoints con labels** (`/vehicles-labels`): Labels traducidos (ej: "Escúter")

### 📋 Campos con Mapeo Automático

| Campo                       | Glosario       | ID  |
| --------------------------- | -------------- | --- |
| `tipus-de-moto`             | Tipus Moto     | 42  |
| `carrosseria-cotxe`         | Carrosseria    | 41  |
| `color-vehicle`             | Color Exterior | 51  |
| `extres-cotxe`              | Extres Coche   | 54  |
| `traccio`                   | Tracció        | 59  |
| Y 15+ campos adicionales... |

### ✅ Corrección de Bugs

- **Campo `tipus-de-moto`**: Ahora correctamente categorizado como campo de glosario
- **Procesamiento consistente**: Eliminada lógica hardcodeada conflictiva
- **Sistema unificado**: Todos los campos usan el mismo sistema de procesamiento

## Requisitos

- WordPress 5.0 o superior
- Plugin JetEngine activado
- Permisos adecuados para cada tipo de operación

## Configuración y Cache

### Página de Administración

A partir de la versión 2.2.2, el plugin incluye una página de configuración en el área de administración de WordPress:

**Ubicación:** WP Admin → API Motoraldia

**Funcionalidades disponibles:**

- **Control de Cache:** Activar/desactivar sistema de cache
- **Duración de Cache:** Configurar tiempo de vida (5 minutos a 24 horas)
- **Limpieza de Cache:** Botón para limpiar todos los transients
- **Caducidad de Anuncios:** Activar/desactivar caducidad automática
- **Días de Caducidad:** Configurar días por defecto para expiración

### Recomendaciones de Cache

- **Desarrollo:** Desactivar cache para ver cambios inmediatamente
- **Producción:** Activar cache con duración de 1-6 horas para mejor rendimiento
- **Debug:** Usar botón "Limpiar Cache Ahora" cuando se realizan cambios

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
    { "name": "AUTOCARAVANA", "value": "autocaravana-camper" },
    { "name": "COTXE", "value": "cotxe" },
    { "name": "MOTO", "value": "moto-quad-atv" },
    { "name": "VEHICLE COMERCIAL", "value": "vehicle-comercial" }
  ]
}
```

#### GET /wp-json/api-motor/v1/vehicles

Obtiene una lista de vehículos.

**Parámetros:**

| Parámetro    | Tipo    | Descripción                                                                                                                             | Requerido | Valor por defecto |
| ------------ | ------- | --------------------------------------------------------------------------------------------------------------------------------------- | --------- | ----------------- |
| page         | integer | Número de página                                                                                                                        | No        | 1                 |
| per_page     | integer | Elementos por página                                                                                                                    | No        | 10                |
| orderby      | string  | Campo para ordenar                                                                                                                      | No        | date              |
| order        | string  | Dirección de ordenación (ASC, DESC)                                                                                                     | No        | DESC              |
| brand        | string  | ID de la marca para filtrar                                                                                                             | No        | -                 |
| user_id      | integer | Filtrar por ID de usuario                                                                                                               | No        | -                 |
| post_id      | integer | Filtrar por ID específico                                                                                                               | No        | -                 |
| post_name    | string  | Filtrar por slug                                                                                                                        | No        | -                 |
| anunci-actiu | boolean | Filtrar por estado de activación (true: solo anuncios activos, false: solo anuncios inactivos, omitir: todos) - **CORREGIDO en v2.2.2** | No        | Todos             |

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

| Campo         | Tipo   | Descripción                                                                     | Requerido   |
| ------------- | ------ | ------------------------------------------------------------------------------- | ----------- |
| tipus-vehicle | string | Tipo de vehículo (cotxe, moto-quad-atv, autocaravana-camper, vehicle-comercial) | Sí          |
| estat-vehicle | string | Estado del vehículo                                                             | Sí          |
| marques-cotxe | string | Marca del vehículo (para coches, autocaravanas y vehículos comerciales)         | Condicional |
| models-cotxe  | string | Modelo del vehículo (para coches, autocaravanas y vehículos comerciales)        | Condicional |
| marques-moto  | string | Marca de la moto (solo para motos)                                              | Condicional |
| preu          | string | Precio del vehículo                                                             | Sí          |
| quilometres   | string | Kilometraje                                                                     | Sí          |
| any           | string | Año de fabricación                                                              | Sí          |
| combustible   | string | Tipo de combustible                                                             | Sí          |
| potencia      | string | Potencia en CV                                                                  | Sí          |

**Campos específicos por tipo de vehículo:**

- **Coches (cotxe)**: extres-cotxe, tipus-tapisseria, color-tapisseria, portes-cotxe
- **Motos (moto-quad-atv)**: extres-moto, tipus-de-moto
- **Autocaravanas (autocaravana-camper)**: extres-autocaravana, carrosseria-caravana, extres-habitacle
- **Vehículos Comerciales (vehicle-comercial)**: carroseria-vehicle-comercial

**Campos de imágenes:**

| Campo          | Tipo     | Descripción                       | Requerido |
| -------------- | -------- | --------------------------------- | --------- |
| featured_image | file/URL | Imagen destacada del vehículo     | No        |
| gallery        | array    | Array de imágenes para la galería | No        |

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
    { "name": "AUTOCARAVANA", "value": "autocaravana-camper" },
    { "name": "COTXE", "value": "cotxe" },
    { "name": "MOTO", "value": "moto-quad-atv" },
    { "name": "VEHICLE COMERCIAL", "value": "vehicle-comercial" }
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
    { "name": "Nou", "value": "nou" },
    { "name": "Seminou", "value": "seminou" },
    { "name": "Ocasió", "value": "ocasio" }
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
    { "name": "Gasolina", "value": "gasolina" },
    { "name": "Dièsel", "value": "diesel" },
    { "name": "Híbrid", "value": "hibrid" },
    { "name": "Elèctric", "value": "electric" },
    { "name": "GLP", "value": "glp" },
    { "name": "Altres", "value": "altres" }
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
    { "name": "Manual", "value": "manual" },
    { "name": "Automàtic", "value": "automatic" }
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
    { "value": "audi", "label": "Audi" },
    { "value": "bmw", "label": "BMW" }
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
    { "value": "a3", "label": "A3" },
    { "value": "a4", "label": "A4" }
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
formData.append("tipus-vehicle", "cotxe");
formData.append("preu", "25000");
// ... otros campos

// Imagen destacada
formData.append("featured_image", fileInputElement.files[0]);

// Galería (múltiples imágenes)
for (let i = 0; i < galleryInputElement.files.length; i++) {
  formData.append("gallery[]", galleryInputElement.files[i]);
}

fetch("/wp-json/api-motor/v1/vehicles", {
  method: "POST",
  body: formData,
  credentials: "include", // Para incluir cookies de autenticación
});
```

## Sistema de Logging

El plugin incluye un sistema de registro personalizado que captura todas las operaciones realizadas en los vehículos. Este sistema almacena la información en una tabla dedicada en la base de datos de WordPress.

### Estructura de la tabla de logs:

| Campo      | Tipo        | Descripción                             |
| ---------- | ----------- | --------------------------------------- |
| id         | bigint(20)  | ID único del registro                   |
| user_id    | bigint(20)  | ID del usuario que realizó la acción    |
| vehicle_id | bigint(20)  | ID del vehículo afectado                |
| action     | varchar(50) | Tipo de acción (create, update, delete) |
| details    | text        | Detalles adicionales en formato JSON    |
| created_at | datetime    | Fecha y hora de la acción               |

Los mensajes de debug se gestionan a través de la clase `Vehicle_Debug_Handler`, que proporciona un control centralizado sobre los mensajes de depuración.

## Consideraciones de Rendimiento

- La API implementa paginación para evitar sobrecarga en solicitudes con muchos resultados
- Se recomienda limitar el número de elementos por página (parámetro `per_page`) a un máximo de 50
- Para operaciones de creación y actualización con imágenes, se recomienda optimizar las imágenes antes de enviarlas

## Ejemplos de Uso

### Obtener lista de vehículos:

```javascript
fetch("/wp-json/api-motor/v1/vehicles?page=1&per_page=10")
  .then((response) => response.json())
  .then((data) => console.log(data));
```

### Crear un nuevo vehículo:

```javascript
const vehicleData = {
  "tipus-vehicle": "cotxe",
  "estat-vehicle": "nou",
  "marques-cotxe": "audi",
  "models-cotxe": "a3",
  preu: "35000",
  quilometres: "0",
  any: "2025",
  combustible: "electric",
  potencia: "204",
  canvi: "automatic",
  "color-vehicle": "Blanc",
  "extres-cotxe": ["climatitzacio", "navegador", "bluetooth"],
};

fetch("/wp-json/api-motor/v1/vehicles", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify(vehicleData),
  credentials: "include",
})
  .then((response) => response.json())
  .then((data) => console.log(data));
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
    "galeria-professionals": ["https://.../img1.jpg", "https://.../img2.jpg"],
    "total_vehicles": 10,
    "active_vehicles": 8
  }
}
```

### Filtro por vendidos (`venut`)

- Si **no pasas** el parámetro `venut`, solo se mostrarán los vehículos no vendidos o que no tienen el campo (disponibles).
- Si pasas `venut=false`, solo se mostrarán los vehículos que tienen el campo `venut` explícitamente en "false".
- Si pasas `venut=true`, solo se mostrarán los vehículos vendidos.

## Soporte y Contacto

Para soporte técnico o consultas sobre la API, contacte con el equipo de desarrollo en [soporte@motoraldia.com](mailto:soporte@motoraldia.com).

---

Esta documentación está sujeta a actualizaciones. Última actualización: Mayo 2025.

## Parámetros de consulta disponibles para /wp-json/api-motor/v1/vehicles

| Parámetro          | Tipo    | Descripción                                             | Ejemplo                    |
| ------------------ | ------- | ------------------------------------------------------- | -------------------------- |
| page               | integer | Número de página (por defecto: 1)                       | page=2                     |
| per_page           | integer | Ítems por página (por defecto: 10)                      | per_page=20                |
| orderby            | string  | Campo por el que ordenar (featured, price, date, title) | orderby=price              |
| order              | string  | Dirección de ordenación (ASC, DESC)                     | order=DESC                 |
| search             | string  | Búsqueda por texto libre                                | search=audi                |
| tipus-vehicle      | string  | Tipo de vehículo (cotxe, moto, etc.)                    | tipus-vehicle=cotxe        |
| estat-vehicle      | string  | Estado del vehículo (nou, seminou, etc.)                | estat-vehicle=nou          |
| marques-cotxe      | string  | Marca del coche (slug)                                  | marques-cotxe=audi         |
| models-cotxe       | string  | Modelo del coche (slug)                                 | models-cotxe=a4            |
| marques-moto       | string  | Marca de la moto (slug)                                 | marques-moto=honda         |
| models-moto        | string  | Modelo de la moto (slug)                                | models-moto=cbr            |
| tipus-combustible  | string  | Tipo de combustible                                     | tipus-combustible=benzina  |
| tipus-canvi        | string  | Tipo de cambio                                          | tipus-canvi=automatic      |
| tipus-propulsor    | string  | Tipo de propulsor                                       | tipus-propulsor=electric   |
| preu_min           | number  | Precio mínimo                                           | preu_min=10000             |
| preu_max           | number  | Precio máximo                                           | preu_max=30000             |
| km_min             | number  | Kilometraje mínimo                                      | km_min=0                   |
| km_max             | number  | Kilometraje máximo                                      | km_max=50000               |
| any_min            | number  | Año mínimo                                              | any_min=2018               |
| any_max            | number  | Año máximo                                              | any_max=2023               |
| potencia_cv_min    | number  | Potencia mínima (CV)                                    | potencia_cv_min=100        |
| potencia_cv_max    | number  | Potencia máxima (CV)                                    | potencia_cv_max=200        |
| anunci-actiu       | boolean | Solo anuncios activos (true/false)                      | anunci-actiu=true          |
| anunci-destacat    | boolean | Solo destacados (true/false o 1/0)                      | anunci-destacat=1          |
| venut              | boolean | Solo vendidos (true/false)                              | venut=false                |
| llibre-manteniment | boolean | Con libro de mantenimiento (true/false)                 | llibre-manteniment=true    |
| revisions-oficials | boolean | Con revisiones oficiales (true/false)                   | revisions-oficials=true    |
| impostos-deduibles | boolean | Impuestos deducibles (true/false)                       | impostos-deduibles=true    |
| vehicle-a-canvi    | boolean | Vehículo a cambio (true/false)                          | vehicle-a-canvi=true       |
| garantia           | boolean | Con garantía (true/false)                               | garantia=true              |
| vehicle-accidentat | boolean | Accidentado (true/false)                                | vehicle-accidentat=false   |
| aire-acondicionat  | boolean | Aire acondicionado (true/false)                         | aire-acondicionat=true     |
| climatitzacio      | boolean | Climatización (true/false)                              | climatitzacio=true         |
| vehicle-fumador    | boolean | Vehículo de fumador (true/false)                        | vehicle-fumador=false      |
| venedor            | string  | Tipo de vendedor                                        | venedor=professional       |
| traccio            | string  | Tracción                                                | traccio=davant             |
| roda-recanvi       | string  | Rueda de recambio                                       | roda-recanvi=kit           |
| segment            | string  | Segmento                                                | segment=compacte           |
| color-vehicle      | string  | Color del vehículo                                      | color-vehicle=blanco       |
| tipus-tapisseria   | string  | Tipo de tapicería                                       | tipus-tapisseria=cuir      |
| color-tapisseria   | string  | Color de tapicería                                      | color-tapisseria=negre     |
| emissions-vehicle  | string  | Emisiones del vehículo                                  | emissions-vehicle=euro6    |
| extres-cotxe       | string  | Extras del coche                                        | extres-cotxe=abs           |
| cables-recarrega   | string  | Cables de recarga                                       | cables-recarrega=mennekes  |
| connectors         | string  | Conectores                                              | connectors=tipo2           |
| user_id            | integer | Filtrar por ID de usuario (requiere permisos)           | user_id=45                 |
| post_id            | integer | Filtrar por ID específico                               | post_id=123                |
| post_name          | string  | Filtrar por slug                                        | post_name=ejemplo-vehiculo |

### Ejemplo de uso combinado

```
/wp-json/api-motor/v1/vehicles?marques-cotxe=audi&preu_min=10000&preu_max=30000&anunci-destacat=1&anunci-actiu=true&page=1&per_page=10
```

## Endpoint: /wp-json/api-motor/v1/blog-posts

Devuelve los posts del blog con los campos principales, taxonomías, tags, metadatos SEO, paginación y facetas (categorías, tags, fechas).

### Parámetros de consulta

| Parámetro | Tipo    | Descripción                            | Ejemplo           |
| --------- | ------- | -------------------------------------- | ----------------- |
| page      | integer | Número de página (por defecto: 1)      | page=2            |
| per_page  | integer | Ítems por página (por defecto: 10)     | per_page=20       |
| orderby   | string  | Campo por el que ordenar (date, title) | orderby=title     |
| order     | string  | Dirección de ordenación (ASC, DESC)    | order=ASC         |
| category  | string  | Slug o ID de categoría (opcional)      | category=noticias |
| tag       | string  | Slug o ID de tag (opcional)            | tag=motor         |
| search    | string  | Búsqueda por texto (opcional)          | search=coches     |

### Estructura de respuesta

```json
{
  "status": "success",
  "items": [
    {
      "id": 123,
      "title": "Título del post",
      "slug": "titulo-del-post",
      "featured_image": "https://...",
      "categories": [{ "id": 1, "name": "Noticias", "slug": "noticias" }],
      "tags": [{ "id": 5, "name": "Motor", "slug": "motor" }],
      "date": "2024-06-01T12:00:00",
      "author": "Nombre del autor",
      "content": "...",
      "excerpt": "...",
      "seo": {
        "meta_title": "...",
        "meta_description": "...",
        "og_image": "...",
        "og_type": "article",
        "twitter_card": "summary_large_image",
        "canonical_url": "...",
        "meta_keywords": "..."
      }
    }
    // ...
  ],
  "total": 100,
  "pages": 10,
  "page": 1,
  "per_page": 10,
  "facets": {
    "categories": { "noticias": 20, "eventos": 15 },
    "tags": { "motor": 30, "coches": 10 },
    "dates": { "2024-06": 5, "2024-05": 8 }
  }
}
```

### Ejemplo de uso

```
/wp-json/api-motor/v1/blog-posts?category=noticias&orderby=title&order=ASC&page=1&per_page=5
```

> **Nota sobre las facetas:**
> Los conteos de las facetas (`facets`) siempre reflejan el total de resultados que cumplen los filtros activos, independientemente de la paginación. Es decir, aunque solo se muestren 10 ítems por página, los conteos de cada filtro corresponden al total global de la búsqueda.
> <<<<<<< HEAD

> **Nota sobre los facets de modelos:**
> Los conteos de modelos (`models-cotxe`, `models-moto`) solo se calculan y devuelven si el filtro de marca correspondiente (`marques-cotxe` o `marques-moto`) está presente en la consulta. Si no hay marca seleccionada, estos facets serán un array vacío.
> =======
>
> > > > > > > ce4e7f4 (docs: los conteos de facetas en /vehicles ahora siempre son globales (independientes de la paginación))
