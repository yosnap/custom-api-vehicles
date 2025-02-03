# Custom API Vehicles para Motoraldia

## Endpoints Disponibles

### Vehículos

#### GET /wp-json/api-motor/v1/vehicles
Obtiene una lista de vehículos.

**Parámetros:**
- `page`: Número de página (opcional)
- `per_page`: Items por página (opcional)
- `user_id`: Filtrar por ID de usuario (solo admin)
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