# Custom API Vehicles for Motoraldia

Plugin WordPress para gestionar vehículos a través de una API REST personalizada.

**Versión actual:** 2.0  
**Namespace:** `api-motor/v1`  
**Tipo de contenido:** `singlecar`

## Endpoints Disponibles

### Vehículos

#### GET /wp-json/api-motor/v1/vehicles/tipus-vehicle

Obtiene los tipos de vehículos disponibles.

**Respuesta:**
```json
[
  {"id": 1, "name": "Coche"},
  {"id": 2, "name": "Moto"},
  {"id": 3, "name": "Furgoneta"},
  {"id": 4, "name": "Autocaravana"},
  {"id": 5, "name": "Camión"}
]
```

#### GET /wp-json/api-motor/v1/vehicles

Obtiene una lista de vehículos.

**Parámetros:**

- `page`: Número de página (opcional, por defecto: 1)
- `per_page`: Items por página (opcional, por defecto: 10)
- `brand`: ID de la marca para filtrar (opcional)
- `user_id`: Filtrar por ID de usuario (admins pueden ver todos, usuarios solo pueden ver los suyos)
- `post_id`: Filtrar por ID específico
- `post_name`: Filtrar por slug
- `anunci-actiu`: Filtrar por estado de activación (true: solo anuncios activos, false: solo anuncios inactivos, omitir: todos)

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
- No es necesario enviar el campo `titol-anunci`

**Campos requeridos:**

- `title`: Título del anuncio
- `tipus-vehicle`: Tipo de vehículo (cotxe, moto, autocaravana, vehicle-comercial)
- `marca`: Marca del vehículo
- `model`: Modelo del vehículo
- `preu`: Precio del vehículo
- `quilometres`: Kilometraje
- `any`: Año de fabricación
- `combustible`: Tipo de combustible
- `potencia`: Potencia en CV

**Campos específicos por tipo de vehículo:**

- **Coches (cotxe)**: extres-cotxe, tipus-tapisseria, color-tapisseria
- **Motos (moto)**: extres-moto, tipus-de-moto
- **Autocaravanas (autocaravana)**: extres-autocaravana, carrosseria-caravana, extres-habitacle
- **Vehículos Comerciales (vehicle-comercial)**: carroseria-vehicle-comercial

**Campos de imágenes:**

- `featured_image`: Acepta URL o ID de media
- `gallery`: Array de URLs para la galería de imágenes

**Respuesta:**

```json
{
  "id": 123,
  "data-creacio": "2025-04-01 10:00:00",
  "status": "publish",
  "slug": "ejemplo-vehiculo",
  "titol-anunci": "Título del anuncio",
  "descripcio-anunci": "Descripción del anuncio",
  // ... todos los campos del vehículo
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

### Debug

#### GET /wp-json/api-motor/v1/debug-fields

Endpoint de debug para ver campos disponibles.

### Sellers

#### GET /wp-json/api-motor/v1/sellers

Obtiene información de vendedores profesionales.

**Parámetros:**

- `user_id`: ID del usuario (opcional para admin, ignorado para usuarios normales)

**Comportamiento:**

1. Como administrador:
   - Sin `user_id`: Devuelve lista de todos los usuarios no administradores
   - Con `user_id`: Devuelve detalles completos del usuario específico

2. Como usuario normal:
   - Siempre devuelve los detalles completos del usuario autenticado
   - El parámetro `user_id` es ignorado

## Campos por Tipo de Vehículo

### Campos Comunes (Todos los vehículos)

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `title` | string | Título del anuncio | Sí |
| `content` | string | Descripción del anuncio | No |
| `tipus-vehicle` | string | Tipo de vehículo | Sí |
| `marca` | string | Marca del vehículo | Sí |
| `model` | string | Modelo del vehículo | Sí |
| `preu` | string | Precio del vehículo | Sí |
| `quilometres` | string | Kilometraje | Sí |
| `any` | string | Año de fabricación | Sí |
| `combustible` | string | Tipo de combustible | Sí |
| `potencia` | string | Potencia en CV | Sí |
| `color-vehicle` | string | Color del vehículo | No |

### Campos Específicos para Coches (`tipus-vehicle` = "cotxe")

| Campo | Tipo | Descripción | Valores válidos |
|-------|------|-------------|----------------|
| `extres-cotxe` | array | Extras del coche | Valores del glosario ID 54 |
| `tipus-tapisseria` | string | Tipo de tapicería | Valores del glosario ID 52 |
| `color-tapisseria` | string | Color de tapicería | Valores del glosario ID 53 |
| `portes-cotxe` | string | Número de puertas | Por defecto: "5" |
| `canvi` | string | Tipo de cambio | "manual", "automatic" |

### Campos Específicos para Motos (`tipus-vehicle` = "moto")

| Campo | Tipo | Descripción | Valores válidos |
|-------|------|-------------|----------------|
| `extres-moto` | array | Extras de la moto | Valores del glosario ID 55 |
| `tipus-de-moto` | string | Tipo de moto | Valores del glosario ID 42 |
| `tipus-canvi-moto` | string | Tipo de cambio | Valores del glosario ID 62 |

### Campos Específicos para Autocaravanas (`tipus-vehicle` = "autocaravana")

| Campo | Tipo | Descripción | Valores válidos |
|-------|------|-------------|----------------|
| `extres-autocaravana` | array | Extras de la autocaravana | Valores del glosario ID 56 |
| `carrosseria-caravana` | string | Tipo de carrocería | Valores del glosario ID 43 ("c-perfilada", "c-capuchina", "c-integral", "c-camper") |
| `extres-habitacle` | array | Extras del habitáculo | Valores del glosario ID 57 |

### Campos Específicos para Vehículos Comerciales (`tipus-vehicle` = "vehicle-comercial")

| Campo | Tipo | Descripción | Valores válidos |
|-------|------|-------------|----------------|
| `carroseria-vehicle-comercial` | string | Tipo de carrocería | Valores del glosario ID 44 ("c-furgon-industrial", "c-furgo-industrial") |
| `extres-cotxe` | array | Extras del vehículo | Valores del glosario ID 54 |

## Glosarios Disponibles

La API utiliza glosarios para validar ciertos campos. Cada glosario tiene un ID único y contiene valores válidos para campos específicos.

| ID Glosario | Campo Asociado | Descripción |
|-------------|----------------|-------------|
| 41 | `segment` | Segmento del vehículo |
| 42 | `tipus-de-moto` | Tipos de motos |
| 43 | `carrosseria-caravana` | Tipos de carrocería para autocaravanas |
| 44 | `carroseria-vehicle-comercial` | Tipos de carrocería para vehículos comerciales |
| 49 | `connectors` | Tipos de conectores |
| 50 | `cables-recarrega` | Cables de recarga |
| 51 | `color-vehicle` | Colores de vehículos |
| 52 | `tipus-tapisseria` | Tipos de tapicería |
| 53 | `color-tapisseria` | Colores de tapicería |
| 54 | `extres-cotxe` | Extras para coches |
| 55 | `extres-moto` | Extras para motos |
| 56 | `extres-autocaravana` | Extras para autocaravanas |
| 57 | `extres-habitacle` | Extras para habitáculos |
| 58 | `emissions-vehicle` | Emisiones del vehículo |
| 59 | `traccio` | Tipos de tracción |
| 60 | `roda-recanvi` | Rueda de recambio |
| 62 | `tipus-canvi-moto` | Tipos de cambio para motos |
| 63 | `tipus-canvi-electric` | Tipos de cambio para vehículos eléctricos |

## Validación de Campos

La API implementa validación estricta para los campos, especialmente para aquellos asociados a glosarios. Si se intenta guardar un valor no válido para un campo de glosario, la API devolverá un error con los valores válidos disponibles.

### Ejemplo de Error de Validación

```json
{
  "code": "invalid_glossary_values",
  "message": "Valores de glosario inválidos: Campo carroseria-vehicle-comercial: valores inválidos (valor-invalido). Valores válidos: c-furgon-industrial, c-furgo-industrial",
  "data": {
    "status": 400
  }
}
```

## Campos con Valores por Defecto

Los siguientes campos se establecen automáticamente con valores por defecto si no se proporcionan:

| Campo | Valor por defecto |
|-------|------------------|
| `frenada-regenerativa` | "no" |
| `one-pedal` | "no" |
| `aire-acondicionat` | "no" |
| `portes-cotxe` | "5" |
| `climatitzacio` | "no" |
| `vehicle-fumador` | "no" |
| `vehicle-accidentat` | "no" |
| `llibre-manteniment` | "no" |
| `revisions-oficials` | "no" |
| `impostos-deduibles` | "no" |
| `vehicle-a-canvi` | "no" |

## Ejemplos de Uso

### Crear un Coche

```http
POST /wp-json/api-motor/v1/vehicles
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
  "title": "Coche de ejemplo",
  "content": "Descripción detallada del coche",
  "tipus-vehicle": "cotxe",
  "marca": "Toyota",
  "model": "Corolla",
  "preu": "25000",
  "quilometres": "50000",
  "any": "2020",
  "combustible": "gasolina",
  "potencia": "150",
  "canvi": "manual",
  "color-vehicle": "Blanco",
  "extres-cotxe": ["Bluetooth", "Climatizador"]
}
```

### Crear un Vehículo Comercial

```http
POST /wp-json/api-motor/v1/vehicles
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
  "title": "Furgoneta comercial",
  "content": "Descripción detallada de la furgoneta",
  "tipus-vehicle": "vehicle-comercial",
  "marca": "Mercedes",
  "model": "Sprinter",
  "preu": "35000",
  "quilometres": "80000",
  "any": "2019",
  "combustible": "diesel",
  "potencia": "170",
  "carroseria-vehicle-comercial": "c-furgo-industrial",
  "extres-cotxe": ["Bluetooth", "Aire acondicionado"]
}
```

## Endpoint: /vehicles

Este endpoint permite obtener un listado de vehículos con soporte para múltiples filtros, ordenamiento y paginación.

### Parámetros de filtrado

#### Paginación
- `page`: Número de página (default: 1)
- `per_page`: Resultados por página (default: 10)

#### Taxonomías
- `tipus-vehicle`: Tipo de vehículo
- `tipus-combustible`: Tipo de combustible
- `tipus-canvi`: Tipo de cambio
- `tipus-propulsor`: Tipo de propulsor
- `estat-vehicle`: Estado del vehículo
- `marques-cotxe`: Marca del coche
- `marques-de-moto`: Marca de la moto
- `models-cotxe`: Modelo del coche (funciona en conjunto con marques-cotxe o independientemente)
- `models-moto`: Modelo de la moto (funciona en conjunto con marques-de-moto o independientemente)

#### Rangos numéricos
- Precio:
  - `preu_min`: Precio mínimo
  - `preu_max`: Precio máximo
- Kilómetros:
  - `km_min`: Kilómetros mínimos
  - `km_max`: Kilómetros máximos
- Año:
  - `any_min`: Año mínimo
  - `any_max`: Año máximo
- Potencia:
  - `potencia_cv_min`: Potencia mínima en CV
  - `potencia_cv_max`: Potencia máxima en CV

#### Filtros booleanos
- `anunci-destacat`: Anuncio destacado (utiliza el meta field 'is-vip')
- `venut`: Vehículo vendido
- `llibre-manteniment`: Libro de mantenimiento
- `revisions-oficials`: Revisiones oficiales
- `impostos-deduibles`: Impuestos deducibles
- `vehicle-a-canvi`: Acepta vehículo a cambio
- `garantia`: Con garantía
- `vehicle-accidentat`: Vehículo accidentado
- `aire-acondicionat`: Aire acondicionado
- `climatitzacio`: Climatización
- `vehicle-fumador`: Vehículo de fumador

#### Filtros de glosario
- `venedor`: Vendedor
- `traccio`: Tracción
- `roda-recanvi`: Rueda de recambio
- `segment`: Segmento
- `color-vehicle`: Color del vehículo
- `tipus-tapisseria`: Tipo de tapicería
- `color-tapisseria`: Color de tapicería
- `emissions-vehicle`: Emisiones del vehículo
- `extres-cotxe`: Extras del coche
- `cables-recarrega`: Cables de recarga
- `connectors`: Conectores

#### Búsqueda y ordenamiento
- `search`: Búsqueda por texto
- `orderby`: Campo por el que ordenar
  - `date`: Fecha
  - `price`: Precio
  - `km`: Kilómetros
  - `year`: Año
- `order`: Dirección del ordenamiento
  - `ASC`: Ascendente
  - `DESC`: Descendente (default)

#### Otros filtros
- `user_id`: ID del usuario (requiere permisos)
- `anunci-actiu`: Estado activo del anuncio

### Ejemplos de uso

1. Filtrar coches por marca y modelo:
```
/wp-json/api-motor/v1/vehicles?marques-cotxe=bmw&models-cotxe=serie-3
```

2. Filtrar por rango de precio y kilómetros:
```
/wp-json/api-motor/v1/vehicles?preu_min=10000&preu_max=20000&km_min=0&km_max=100000
```

3. Búsqueda con múltiples filtros:
```
/wp-json/api-motor/v1/vehicles?tipus-vehicle=cotxe&estat-vehicle=nou&orderby=price&order=ASC&page=1&per_page=20
```

4. Filtrar motos por marca y modelo:
```
/wp-json/api-motor/v1/vehicles?marques-de-moto=honda&models-moto=cbr
```

5. Filtrar anuncios destacados:
```
/wp-json/api-motor/v1/vehicles?anunci-destacat=true&orderby=date&order=DESC
```

### Respuesta

La respuesta incluye:
- `status`: Estado de la respuesta
- `items`: Array de vehículos
- `total`: Total de items encontrados
- `pages`: Número total de páginas
- `page`: Página actual
- `per_page`: Items por página

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

### Filtro por estado activo
Puedes filtrar los vehículos activos o inactivos:

- Solo activos:
  `/wp-json/api-motor/v1/vehicles?anunci-actiu=true`
- Solo inactivos:
  `/wp-json/api-motor/v1/vehicles?anunci-actiu=false`

El filtro es exacto y solo devuelve los ítems cuyo estado real coincide con el solicitado.

### Ordenar por destacados
Para mostrar los vehículos destacados primero, usa:

- `/wp-json/api-motor/v1/vehicles?orderby=featured`

Esto ordena primero los que tienen `is-vip = 'true'` y luego el resto, por fecha descendente.

### Filtro por vendidos (`venut`)
- Si **no pasas** el parámetro `venut`, solo se mostrarán los vehículos no vendidos o que no tienen el campo (disponibles).
- Si pasas `venut=false`, solo se mostrarán los vehículos que tienen el campo `venut` explícitamente en "false".
- Si pasas `venut=true`, solo se mostrarán los vehículos vendidos.

# Endpoints REST avanzados para vehículos

## Filtros por taxonomía

Puedes filtrar vehículos por los siguientes endpoints:

- `/wp-json/api-motor/v1/tipus-combustible/{slug}`
- `/wp-json/api-motor/v1/tipus-propulsor/{slug}`
- `/wp-json/api-motor/v1/tipus-vehicle/{slug}`
- `/wp-json/api-motor/v1/marques-cotxe/{slug}`
- `/wp-json/api-motor/v1/marques-moto/{slug}`
- `/wp-json/api-motor/v1/estat-vehicle/{slug}`

**Parámetros disponibles:**
- `page` (int, por defecto 1)
- `per_page` (int, por defecto 10)
- `orderby` (string, por defecto 'date')
- `order` (string, por defecto 'DESC')

**Ejemplo:**
```
/wp-json/api-motor/v1/marques-cotxe/audi?page=2&per_page=5&orderby=price&order=ASC
```

## Filtros por modelo bajo marca

- `/wp-json/api-motor/v1/marques-cotxe/{marca}/{modelo}`
- `/wp-json/api-motor/v1/marques-moto/{marca}/{modelo}`

**Ejemplo:**
```
/wp-json/api-motor/v1/marques-cotxe/audi/a3
```

La respuesta es igual que el endpoint general de vehículos, incluyendo paginación, total de resultados y todos los campos de cada vehículo.
