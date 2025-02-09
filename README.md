# Custom API Vehicles para Motoraldia

## Endpoints Disponibles

### Vehículos

#### GET /wp-json/api-motor/v1/vehicles

Obtiene una lista de vehículos.

**Parámetros:**

- `page`: Número de página (opcional)
- `per_page`: Items por página (opcional)
- `user_id`: Filtrar por ID de usuario (admins pueden ver todos, usuarios solo pueden ver los suyos)
- `post_id`: Filtrar por ID específico
- `post_name`: Filtrar por slug

**Respuesta:**

```json
{
    "vehicles": [...],
    "total_posts": 100,
    "total_pages": 10,
    "current_page": 1
}
```

#### POST /wp-json/api-motor/v1/vehicles

Crea un nuevo vehículo.

**Notas importantes:**

- El título del vehículo se genera automáticamente usando el formato: `{Marca} {MODELO} {versión}`
- No es necesario enviar el campo `titol-anunci`

**Campos requeridos:**

- marques-cotxe
- models-cotxe
- versio
- tipus-vehicle
- tipus-combustible
- tipus-canvi-cotxe
- tipus-propulsor
- estat-vehicle
- preu

**Campos de imágenes:**

- imatge-destacada-id: Acepta URL o ID de media
- galeria-vehicle: Array de URLs para la galería de imágenes

**Respuesta:**

```json
{
  "status": "success",
  "message": "Vehículo creado exitosamente",
  "post_id": 123,
  "titol-anunci": "...",
  "descripcio-anunci": "...",
  "status": "publish"
}
```

#### GET /wp-json/api-motor/v1/vehicles/{id}

Obtiene detalles de un vehículo específico.

**Requiere autenticación y ser propietario**

#### PUT /wp-json/api-motor/v1/vehicles/{id}

Actualiza un vehículo existente.

**Requiere autenticación y ser propietario**

#### DELETE /wp-json/api-motor/v1/vehicles/{id}

Elimina (mueve a papelera) un vehículo.

**Requiere autenticación y ser propietario**

### Taxonomías y Glosarios

#### Vehículos

- `GET /wp-json/api-motor/v1/tipus-vehicle`: Tipos de vehículos
- `GET /wp-json/api-motor/v1/estat-vehicle`: Estados de vehículo
- `GET /wp-json/api-motor/v1/tipus-combustible`: Tipos de combustible
- `GET /wp-json/api-motor/v1/tipus-canvi`: Tipos de cambio
- `GET /wp-json/api-motor/v1/tipus-propulsor`: Tipos de propulsor

#### Marcas y Modelos

- `GET /wp-json/api-motor/v1/marques-cotxe`: Lista todas las marcas de coches
- `GET /wp-json/api-motor/v1/marques-cotxe?marca={slug}`: Obtiene modelos de una marca específica
- `GET /wp-json/api-motor/v1/marques-moto`: Lista todas las marcas de motos
- `GET /wp-json/api-motor/v1/marques-moto?marca={slug}`: Obtiene modelos de una marca específica

#### Carrocerías

- `GET /wp-json/api-motor/v1/carrosseria-cotxe`: Carrocerías de coches
- `GET /wp-json/api-motor/v1/carrosseria-moto`: Carrocerías de motos
- `GET /wp-json/api-motor/v1/carrosseria-caravana`: Carrocerías de caravanas
- `GET /wp-json/api-motor/v1/carrosseria-comercial`: Carrocerías de vehículos comerciales

#### Extras

- `GET /wp-json/api-motor/v1/extres-cotxe`: Extras de coches
- `GET /wp-json/api-motor/v1/extres-moto`: Extras de motos
- `GET /wp-json/api-motor/v1/extres-caravana`: Extras de caravanas
- `GET /wp-json/api-motor/v1/extres-habitacle`: Extras de habitáculo

#### Colores y Tapicería

- `GET /wp-json/api-motor/v1/colors-exterior`: Colores exteriores
- `GET /wp-json/api-motor/v1/tapiceria`: Tipos de tapicería
- `GET /wp-json/api-motor/v1/colors-tapiceria`: Colores de tapicería

#### Administración

- `GET /wp-json/api-motor/v1/glosarios`: Lista todos los glosarios (solo admin)
- `GET /wp-json/api-motor/v1/debug-fields`: Información detallada de campos (solo admin)
- `GET /wp-json/api-motor/v1/authors`: Lista de autores (admin) o información del autor actual (usuario autenticado)

**Ejemplo de respuesta para taxonomías:**

### Debug

#### GET /wp-json/api-motor/v1/debug-fields

Endpoint de debug para ver campos disponibles.

## Autenticación

La API utiliza autenticación mediante tokens JWT o cookies de WordPress.

## Códigos de Estado

- 200: Éxito
- 201: Creado
- 400: Error en la petición
- 403: No autorizado
- 404: No encontrado

## Campos de Vehículos

### Campos Básicos

- titol-anunci: Título del anuncio
- descripcio-anunci: Descripción
- tipus-de-vehicle: Tipo de vehículo
- marques-cotxe: Marca
- models-cotxe: Modelo
- anunci-actiu: Estado activo del anuncio

### Imágenes

- imatge-destacada-id: ID de imagen destacada
- imatge-destacada-url: URL de imagen destacada
- galeria-vehicle-urls: URLs de la galería

### Campos de Control

- dies-caducitat: Días hasta caducidad (solo admin)
- anunci-actiu: Estado activo del anuncio

## Notas Adicionales

- Los campos de imágenes aceptan: IDs, URLs o imágenes en base64
- Algunos campos son específicos para administradores
- Los registros eliminados se mueven a la papelera en lugar de eliminarse permanentemente
