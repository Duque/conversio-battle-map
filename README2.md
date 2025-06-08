# Conversio Battle Map

Plugin de WordPress que muestra un mapa gamificado del método Conversio para cada usuario. Su propósito es guiar paso a paso el avance del funnel de ventas mediante territorios y secciones desbloqueables.

## Tecnologías empleadas

- **PHP** y la API REST de WordPress.
- **Alpine.js** para la lógica de la plantilla del mapa.
- **SVG** y CSS para la representación visual.
- Archivos **JSON** como almacén temporal de datos.

## Flujo completo del usuario

1. El usuario accede a la URL del mapa con un **token** de acceso.
2. El frontend obtiene el mapa desde `/wp-json/conversio-battle-map/v1/map/token/<token>`.
3. El usuario navega por las secciones desbloqueadas y abre el **popup** de cada una.
4. Al marcar una sección como completada se llama a `/wp-json/conversio-battle-map/v1/map/<userId>/section/<slug>/complete`.
5. Se recalculan puntos, se evalúan logros y se devuelven mensajes narrativos.
6. Cuando todas las secciones de un territorio están completas se muestra un mensaje motivacional y se pueden desbloquear nuevos territorios.
7. El usuario puede solicitar un **PDF** de resumen mediante `/wp-json/battle-map/v1/user/<userId>/export/pdf`.

## Lógica de negocio

- **Territorios y secciones**: cada territorio agrupa varias secciones que pueden desbloquearse en cadena mediante el campo `next`.
- **Completar una sección**: marca la propiedad `completed` y desbloquea las siguientes; si todas las secciones de un territorio están completas, este se marca `completed`.
- **Desbloqueo de territorios**: mediante el endpoint `/map/<userId>/territory/<slug>/unlock` se habilitan territorios en función de un producto adquirido.
- **Cálculo de puntuación**: la función `calculatePoints()` pondera el impacto y la fricción de las secciones completadas y actualiza `totalPoints`.
- **Mensajes narrativos**: `handleNarrativeTriggers()` busca mensajes motivacionales en `catalogs.json` según el evento recibido.
- **Logros**: `unlockAchievement()` añade logros al completar hitos como "first-step" o "full-map".
- **Recomendaciones y CTA**: cada sección puede incluir sugerencias que se muestran en el popup junto con enlaces de acción.
- **Popup**: presenta descripción, fricción, impacto y recomendaciones. Permite marcar la sección como completada.

## Estructura de carpetas

```
├── conversio-battle-map.php      # Cargador y registro de ganchos
├── includes/
│   ├── register-cpt.php          # Custom Post Type y metacampos
│   ├── class-user-map.php        # Gestión de mapas de usuario
│   ├── class-rest-endpoints.php  # Endpoints REST
│   ├── helpers-map.php           # Cálculo de puntos y logros
│   └── token-auth.php            # Validación de tokens
├── templates/
│   └── map-template.php          # Plantilla HTML con Alpine.js
├── assets/                       # Estilos, fondos e iconos
├── data/                         # Archivos JSON de ejemplo
└── admin/
    └── settings-page.php         # Borrador de ajustes
```

## Endpoints REST

| Método | Ruta | Parámetros | Descripción |
| ------ | ---- | ---------- | ----------- |
| `GET`  | `/wp-json/battle-map/v1/user?token=<token>` | `token` | Devuelve mapa, progreso y logros asociados al token. |
| `POST` | `/wp-json/battle-map/v1/user/<userId>/section/<slug>/complete` | – | Marca la sección como completada y recalcula puntos. |
| `GET`  | `/wp-json/battle-map/v1/user/<userId>/summary` | – | Resumen de puntos y secciones completadas. |
| `GET`  | `/wp-json/battle-map/v1/catalogs` | – | Carga catálogos de `data/catalogs.json`. |
| `POST` | `/wp-json/battle-map/v1/user/<userId>/export/pdf` | cuerpo JSON con opciones | Devuelve un PDF con información básica. |
| `GET`  | `/wp-json/conversio-battle-map/v1/map/token/<token>` | – | Alternativa para obtener el mapa por token. |
| `POST` | `/wp-json/conversio-battle-map/v1/map/<userId>/section/<slug>/complete` | – | Versión equivalente de completado de sección. |
| `POST` | `/wp-json/conversio-battle-map/v1/map/<userId>/territory/<slug>/unlock` | `productId`, `paymentVerified` | Desbloquea territorios tras una compra válida. |

Ejemplo de respuesta de `/user?token=<token>`:
```json
{
  "id": 123,
  "user_id": "raul123",
  "access_token": "<token>",
  "userMap": { ... },
  "progressRecord": { "totalPoints": 0 },
  "achievements": []
}
```

## Modelos de datos

### UserMap
| Campo | Tipo | Descripción |
| ----- | ---- | ----------- |
| `userId` | string | Identificador del usuario. |
| `currentTerritorySlug` | string | Territorio actual. |
| `currentSectionSlug` | string | Sección actual. |
| `createdAt` / `updatedAt` | string | Fechas ISO de creación y actualización. |
| `territories` | array<Territory> | Lista de territorios del mapa. |

### Territory
| Campo | Tipo | Descripción |
| ----- | ---- | ----------- |
| `slug` | string | Identificador único. |
| `title` | string | Nombre visible. |
| `description` | string | Breve descripción. |
| `unlocked` | bool | Si el territorio está disponible. |
| `completed` | bool | Se marca al finalizar todas sus secciones. |
| `order` | int | Posición en el mapa. |
| `sections` | array<MapSection> | Secciones pertenecientes al territorio. |

### MapSection
| Campo | Tipo | Descripción |
| ----- | ---- | ----------- |
| `slug` | string | Identificador. |
| `title` | string | Nombre de la sección. |
| `friction` | string | Grado de fricción detectado. |
| `impact` | int | Valor de impacto en la puntuación. |
| `recommendation` | string | Texto de mejora principal. |
| `details` | string | Diagnóstico más largo. |
| `completed` | bool | Estado de finalización. |
| `unlocked` | bool | Si está disponible para el usuario. |
| `next` | array<string> | Slugs de secciones siguientes. |

### Achievement
| Campo | Tipo | Descripción |
| ----- | ---- | ----------- |
| `id` | string | Identificador único. |
| `title` | string | Nombre descriptivo. |
| `unlocked` | bool | Se activa cuando se cumple la condición. |
| `unlockedAt` | string | Fecha ISO de desbloqueo. |

### NarrativeMessage
| Campo | Tipo | Descripción |
| ----- | ---- | ----------- |
| `id` | string | Identificador. |
| `trigger` | string | Evento que lo lanza. |
| `targetSlug` | string | Slug asociado (opcional). |
| `message` | string | Texto a mostrar. |

## Catálogos (`catalogs.json`)

El archivo `data/catalogs.json` agrupa distintas colecciones:

- **achievements** – definiciones de logros con sus condiciones.
- **narrativeMessages** – mensajes motivacionales asociados a eventos.
- **offers** – llamadas a la acción según el avance del mapa.
- **visualThemes** – combinaciones de color y estilo para el mapa.
- **nodes** y **paths** – posiciones y conexiones para la representación SVG.

Actualmente el archivo está vacío y sirve como esquema para futuras configuraciones.

## Funciones clave

- `completeSection(slug)` – función de Alpine.js que marca una sección como completada, desbloquea las siguientes y sincroniza con el backend.
- `calculatePoints($userMap)` – PHP: suma ponderada de impacto y fricción para obtener hasta 1000 puntos.
- `generate_user_map($access_token)` – PHP: recupera mapa, progreso y logros a partir del token guardado.
- `unlockTerritory()` – implementado como endpoint `/territory/<slug>/unlock` para habilitar territorios mediante un producto comprado.
- `get_user_summary()` – PHP: calcula datos agregados de avance.
- `export_pdf()` – PHP: genera un PDF básico con el estado del usuario.
- `handleNarrativeTriggers($trigger, $targetSlug)` – PHP: filtra mensajes de `catalogs.json` según el evento recibido.

## Relación de objetos y funciones

### PHP

- **CBM_User_Map**
  - `generate_user_map( $access_token )` – busca el CPT por token y devuelve el mapa completo.
  - `get_user_map_by_token( $token )` – variante para obtener el mapa únicamente a partir del token.
  - `get_user_map_by_user_id( $user_id )` – localiza el mapa de un usuario por su ID.
  - `update_user_map_data( $post_id, $map, $progress, $achievements )` – guarda los cambios de un usuario.
  - `build_user_map_data( $post )` – método interno para componer el array con mapas y progreso.

- **CBM_Rest_Endpoints**
  - `register_routes()` – declara todos los endpoints REST.
  - `get_user_map()` – devuelve el mapa asociado a un token.
  - `complete_section()` – marca una sección y actualiza puntuación y logros.
  - `get_user_summary()` – ofrece un resumen con totales de progreso.
  - `get_catalogs()` – lee `data/catalogs.json` y lo sirve vía API.
  - `export_pdf()` – genera un PDF simple utilizando `create_simple_pdf()`.

- **register_battle_map_cpt()** – registra el Custom Post Type `battle_map`.
- **cbm_initialize_battle_map_fields()** – crea los metacampos iniciales cuando se añade un mapa.
- **cbm_activate() / cbm_deactivate()** – gestionan reglas de reescritura al activar o desactivar el plugin.
- **cbm_enqueue_assets()** – carga CSS y Alpine.js en el frontend.
- **cbm_render_map()** – shortcode que muestra la plantilla del mapa.
- **cbm_validate_token()** – validación simple del token recibido.
- **unlockAchievement()** – evalúa y desbloquea logros según condiciones.

### JavaScript (demoMap)

- `init()` – carga el mapa desde la API usando el token de la URL.
- `openPopup(section)` / `closePopup()` – muestran y ocultan la tarjeta emergente.
- `completeSection(slug)` – lógica de frontend para marcar y desbloquear secciones.
- `checkTerritoryCompletion(territory)` – activa un mensaje cuando todo un territorio está completo.
- `calculatePoints()` – cálculo en cliente para mostrar puntuación instantánea.
- `unlockAchievements()` y `showAchievement()` – gestionan la notificación de logros.
- `getNodePosition()` y `buildPathD()` – determinan coordenadas y líneas dentro del SVG.

## Privacidad y RGPD

El plugin almacena los mapas en un Custom Post Type privado y asocia el token de acceso al identificador de usuario. Se recomienda informar al usuario y obtener su consentimiento para el uso de estos datos. Los archivos JSON de ejemplo no contienen información personal real.

## Roadmap

- **Implementado**: registro del CPT, plantilla con Alpine.js, endpoints básicos, cálculo de puntos y logros locales.
- **Pendiente**:
  - Sistema completo de nodos y paths SVG.
  - Mejorar el popup con CTAs y oferta dinámica.
  - Progreso visual detallado y conexiones entre secciones.
  - Panel de administración completo y exportaciones avanzadas.

## Simular el sistema sin backend

Los archivos de `data/` permiten ejecutar el mapa sin llamadas externas. Cargando `data/dummy-user-map.json` en la plantilla se puede simular el avance marcando secciones y desbloqueando logros de forma local.

## Créditos y estilo de desarrollo

El proyecto se inspira en técnicas de **gamificación** y mantiene un enfoque de branding emocional para motivar al usuario. Se anima a seguir buenas prácticas de WordPress y un código claro, comentado y orientado a la expansión futura.
