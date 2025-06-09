# Conversio Battle Map

Plugin de WordPress que muestra un mapa gamificado del método Conversio para cada usuario. Su propósito no es simplemente visualizar el avance, sino **entregar los resultados de diagnóstico estratégico de su ecommerce en formato juego**, como parte de un entregable profesional.

Cada sección representa un área analizada y cada territorio corresponde a uno de los productos contratados: Clarity Call™, Battle Map™ y Scanner Conversio™.

---

- **Autor:** Raúl Duque
- **Versión:** 1.0.0
- **Última actualización:** junio de 2025
- **Requiere:** WordPress 6.0+
- **Dependencias externas:** Alpine.js (v3.x)

## 🧠 Propósito y visión general

Conversio Battle Map es una herramienta de **entrega de diagnósticos en formato gamificado**. Cada usuario accede a su mapa personalizado mediante un enlace único, y visualiza los hallazgos estratégicos obtenidos a través del método Conversio.

Cada territorio representa uno de los servicios contratados (Clarity Call™, Battle Map™, Scanner Conversio™) y cada sección muestra fricciones detectadas, impacto estimado y recomendaciones prácticas.

Al hacer clic en cada nodo, se despliega un informe expandido que detalla lo observado en esa parte de su ecommerce. El objetivo es que el usuario reciba su informe en un formato más motivador, visual e interactivo que un PDF tradicional.

> Este mapa no es un juego en el sentido tradicional: es un informe estratégico disfrazado de experiencia gamificada.

## 🧰 Tecnologías empleadas

- **PHP** – Motor del plugin y lógica de negocio.
- **API REST de WordPress** – Para exponer los datos del usuario y su mapa.
- **Alpine.js** – Para la interactividad ligera en el frontend.
- **SVG** – Para representar visualmente los nodos y rutas del mapa.
- **Tailwind CSS** (opcional) – Para el diseño responsivo y utilidades visuales.
- **Archivos JSON** – Usados como plantillas y catálogos de datos (mapas, logros, ofertas, etc.).
- **PDF Generator (simulado)** – Para generar un resumen descargable del mapa.

## 🧭 Flujo completo del usuario

1. El usuario accede mediante una URL personalizada con token único, por ejemplo: `/battle-map/map/?token=abc123`.
2. El sistema carga su `UserMap` desde la base de datos, asociado a ese token. Este mapa incluye solo los territorios que ha desbloqueado según lo contratado.
3. El usuario puede visualizar los territorios habilitados:
   - Si contrató Clarity Call™, solo verá ese territorio.
   - Si contrató Battle Map™, verá Clarity + Battle Map.
   - Si contrató Scanner Conversio™, verá los tres.
4. Dentro de cada territorio se muestran las secciones auditadas (por ejemplo: home, producto, ficha, checkout…).
5. Al hacer clic en una sección, se abre un **popup lateral** que muestra:
   - El nivel de fricción detectado.
   - El impacto estimado en ventas o experiencia.
   - El diagnóstico completo.
   - Una recomendación principal.
   - Un listado de subtareas o acciones sugeridas.
6. El usuario puede marcar secciones como completadas (opcional, a modo organizativo). Esto actualiza su puntuación simbólica y puede desbloquear logros.
7. Al completar todas las secciones de un territorio, se muestra:
   - Un mensaje motivacional personalizado.
   - Un botón o enlace CTA para escalar al siguiente territorio (si no lo ha adquirido).
8. En cualquier momento, el usuario puede exportar su mapa como PDF.

## 🧠 Lógica de negocio

### 🗺 Territorios y secciones

Cada territorio representa un servicio de la metodología Conversio (Clarity Call™, Battle Map™, Scanner Conversio™).

Las secciones (`MapSection`) son áreas específicas evaluadas durante la auditoría del ecommerce (por ejemplo: home, producto, carrito, checkout, etc.).

No existe progresión lineal ni personaje: el usuario explora libremente los territorios que tenga desbloqueados.

El contenido de cada sección es personalizado y muestra datos reales obtenidos del análisis del negocio.

### ✅ Completar una sección

Marcar una sección como completada es opcional. Está pensado como ayuda visual para que el usuario organice sus avances o tareas pendientes. Al hacerlo:

- Se actualiza el estado de la sección (`completed = true`).
- Se recalculan los puntos simbólicos del mapa.
- Se evalúa si se cumplen condiciones para desbloquear logros.
- Puede dispararse un mensaje narrativo automático (según catálogos).

#### 🧪 Interacción desde el InfoBox

Cuando el usuario pulsa el botón "Marcar como completada" en el InfoBox:

- Se envía un `POST` al endpoint correspondiente.
- Se actualiza el estado visual (`completed: true`) en frontend.
- Se cierra el panel lateral automáticamente.
- (Opcional) Puede mostrarse un mensaje motivacional si existe en la `narrativeQueue`.

### 🔓 Desbloqueo de territorios

El desbloqueo de territorios no ocurre de forma automática. Solo se activa cuando el usuario contrata el siguiente producto de la metodología Conversio. Este desbloqueo puede hacerse manualmente (desde el backend) o automáticamente al completarse el pago, mediante el endpoint REST `unlockTerritory()`.

### 🧮 Puntuación simbólica

Cada sección aporta puntos según su impacto (valor numérico) y el nivel de fricción detectado.

**Fórmula de ponderación:**

```javascript
getSectionWeight(impact, friction) {
  const multipliers = {
    None: 0,
    Low: 1,
    Medium: 1.2,
    High: 1.5,
    Critical: 2
  };
  return impact * multipliers[friction];
}
```

El total simbólico del mapa es de 1000 puntos. Este valor se distribuye entre secciones completadas y sirve para:

- Mostrar progreso general.
- Activar logros.
- Generar motivación emocional.

### 📣 Mensajes narrativos

Los `NarrativeMessage` se almacenan en `catalogs.json`. Cada uno se dispara automáticamente tras un evento definido (`section.complete`, `territory.complete`, `map.enter`, `milestone.reached`, etc.). Sus propiedades son:

- `trigger`: evento que lanza el mensaje.
- `targetSlug` (opcional): si se refiere a una sección concreta.
- `message`: texto del mensaje.
- `style`: narrator, success, info, etc.
- `delay`: retardo en milisegundos antes de mostrarse.
- `autoClose`: si debe cerrarse automáticamente.

### 🏆 Logros desbloqueables

Los logros (`Achievement`) se activan cuando se cumplen ciertas condiciones. Se guardan como parte del estado del usuario (`achievements[]`). Ejemplos comunes:

- `first-step`: primera sección marcada como completada.
- `clarity-complete`: todas las secciones del primer territorio.
- `half-map`: más del 50 % del total del mapa.
- `full-map`: mapa completo terminado.

Los logros pueden mostrarse visualmente como medallas o insignias.

#### 🎉 Visualización de logros

Cuando el usuario completa una sección, se verifica automáticamente si se han desbloqueado logros nuevos. El proceso completo incluye:

- Comparación del estado de achievements antes y después de la acción.
- Identificación de nuevos logros donde `unlocked = true` y no estaban previamente desbloqueados.
- Visualización inmediata de los logros en pantalla como modales, alertas o mensajes flotantes.
- Estilo visual según tipo de logro (success, info, narrator…).
- Posibilidad de cierre manual o cierre automático con retardo (autoClose).
- Fuente de datos opcional desde el catálogo de logros en `catalogs.json`.

Estos logros se almacenan en `userMap.achievements[]` junto con la fecha (`unlockedAt`) y se pueden reutilizar para gamificación, exportaciones o futuras funcionalidades.

### 💡 Popup lateral (InfoBox)

Es el componente clave de visualización dentro del mapa. Implementado como un panel flotante en el lateral derecho que visualiza dinámicamente la información de cada sección. Al hacer clic en un nodo del mapa, se despliega con la siguiente información:

- Icono representativo de la sección.
- Título, nivel de fricción e impacto.
- Diagnóstico completo (`details`).
- Recomendación principal (`recommendation`).
- Lista de subtareas (`recommendationsList`).
- Estado actual: completada, desbloqueada o bloqueada.
- Botón para marcar como completada (solo si la sección está desbloqueada y no ha sido completada).

El popup se cierra automáticamente al hacer clic fuera y cuenta con una transición suave activada.

## 🧩 Modelos de datos clave

Los siguientes modelos representan la estructura de datos usada por el mapa, tanto en frontend como en backend. Todos están definidos en estructuras JSON y manipulados desde PHP y Alpine.js.

### UserMap

- `userId`: string – Identificador del usuario.
- `access_token`: string – Token único de acceso.
- `currentTerritorySlug`: string (opcional) – Último territorio visualizado.
- `currentSectionSlug`: string (opcional) – Última sección visualizada.
- `createdAt`: string – Fecha ISO de creación del mapa.
- `updatedAt`: string – Fecha ISO de la última modificación.
- `territories`: array de `Territory` – Lista de territorios disponibles.
- `progressRecord`: objeto `ProgressRecord` – Puntuación y avance global.
- `achievements`: array de `Achievement` – Logros desbloqueados.
- `narrativeQueue`: array de `NarrativeMessage` (opcional) – Mensajes en cola.
- `offersShown`: array de string (opcional) – CTAs ya mostrados.

### Territory

- `slug`: string – Identificador único del territorio.
- `title`: string – Título visible.
- `description`: string (opcional) – Explicación breve.
- `unlocked`: boolean – Si está disponible.
- `completed`: boolean – Si todas sus secciones están completadas.
- `order`: número – Posición visual.
- `visualTheme`: string (opcional) – Tema visual aplicado.
- `backgroundImage`: string (opcional) – Imagen de fondo del territorio.
- `sections`: array de `MapSection` – Secciones que lo componen.

### MapSection

- `slug`: string – Identificador único de la sección.
- `title`: string – Nombre de la sección.
- `friction`: string – Nivel de fricción: None, Low, Medium, High, Critical.
- `impact`: número – Valor del impacto estimado.
- `recommendation`: string – Acción prioritaria recomendada.
- `details`: string (opcional) – Diagnóstico ampliado.
- `completed`: boolean – Si el usuario la ha marcado como completada.
- `unlocked`: boolean – Si está accesible para ver su contenido.
- `next`: array de string (opcional) – Slugs de secciones siguientes.
- `icon`: string (opcional) – Ruta del icono.
- `recommendationsList`: array de `Recommendation` (opcional) – Lista de subtareas.

### Recommendation

- `id`: string – Identificador único.
- `title`: string – Nombre visible de la recomendación.
- `type`: string – Categoría (UX, Copy, Técnico, Trust, Email, Otro).
- `priority`: string – Grado de urgencia (Alta, Media, Baja).
- `description`: string – Explicación detallada.

### Achievement

- `id`: string – Identificador único del logro.
- `name`: string – Nombre del logro.
- `unlocked`: boolean – Si ha sido conseguido.
- `unlockedAt`: string (opcional) – Fecha ISO de desbloqueo.

### NarrativeMessage

- `id`: string – ID del mensaje.
- `trigger`: string – Evento que lo dispara.
- `message`: string – Texto visible.
- `targetSlug`: string (opcional) – Sección o territorio relacionado.
- `style`: string – Estilo visual: info, success, narrator.
- `delay`: número (opcional) – Milisegundos de espera.
- `autoClose`: boolean (opcional) – Si se cierra automáticamente.

### ProgressRecord

- `totalPoints`: número – Puntos acumulados.
- `completedSections`: array de string – Slugs completados.
- `completedTerritories`: array de string – Territorios completados.
- `scoreByTerritory`: objeto – Puntos por territorio `{ slug: score }`.

### MapVisualNode

- `sectionSlug`: string – Slug de la sección.
- `x`: número – Posición horizontal.
- `y`: número – Posición vertical.
- `icon`: string – Ruta del icono.

### MapVisualPath

- `fromSlug`: string – Nodo de inicio.
- `toSlug`: string – Nodo de destino.
- `pathType`: string – Estilo (line, curve, dotted).
- `style`: string (opcional) – Clase CSS o inline style.

🧭 Visualización de rutas

Las rutas entre nodos se renderizan dentro de `svg.map-canvas` como elementos `<path>`.

- Cada ruta usa `buildPathD(path)` para definir su forma.
- El tipo `line` dibuja una línea recta. El tipo `curve` dibuja una curva de Bézier.
- Se pueden aplicar estilos adicionales desde `path.style` (ej. línea punteada, grosor, color).
- Las rutas se dibujan antes que los nodos para que estos queden encima visualmente

### ProductOffer

- `id`: string – Identificador de la oferta.
- `title`: string – Título visible.
- `description`: string – Texto explicativo.
- `territoryRequired`: string – Territorio relacionado.
- `ctaText`: string – Texto del botón.
- `ctaUrl`: string – Enlace de destino.

### VisualTheme

- `id`: string – Identificador del tema.
- `primaryColor`: string – Color principal.
- `backgroundColor`: string – Color de fondo.
- `fontFamily`: string – Fuente.
- `iconSet`: string (opcional) – Set de iconos aplicados.

### MediaAsset

- `id`: string – ID del recurso.
- `type`: string – `image` o `video`.
- `url`: string – Ruta pública del recurso.
- `label`: string (opcional) – Descripción para accesibilidad o tooltips.

## 🧱 Estructura visual y plantilla

La vista principal del mapa se carga desde el archivo `templates/map-template.php`. Esta plantilla usa Alpine.js para cargar dinámicamente los datos del mapa y representar cada territorio y sección dentro de un contenedor con scroll vertical.

### Estructura general del DOM

- `div.w-screen.h-screen.overflow-y-auto.bg-white.relative.z-0`: contenedor principal del mapa.
- `x-data="demoMap()"`: inicialización del estado desde Alpine.js.
- `template x-for="territory in userMap.territories"`: renderiza todos los territorios desbloqueados.
- `div.territory-title`: muestra el nombre del territorio y su progreso.
- `svg.map-canvas`: lienzo SVG donde se posicionan nodos y rutas.

🔁 Render dinámico en SVG

  Cada sección del territorio se representa como un nodo SVG dentro de `svg.map-canvas`, con su posición (`x`, `y`) y estilo visual según su estado (`completed`, `locked`). Se usa `<circle>` o `<image>` para representar los nodos, junto con iconos personalizados desde `/assets/icons/{slug}.png`.

  Cada territorio aplica su `backgroundImage` como fondo visual. Esto permite convertir la experiencia en una navegación tipo mapa, no una lista textual.
- `template x-for="section in territory.sections"`: renderiza los nodos visuales (por ahora círculos con icono).
- `img.section-icon`: icono de cada sección (ruta `/assets/icons/{slug}.png`).
- `div.debug-box`: caja flotante en esquina inferior derecha para visualizar el estado (solo en modo desarrollo).

🧭 Visualización de rutas

Las rutas entre nodos se dibujan en `svg.map-canvas` mediante elementos `<path>`.
- Cada trazado se genera con `buildPathD(path)`.
- Los estilos extra provienen de `path.style` y permiten líneas punteadas o distintos colores.
- Se renderizan antes de los nodos para que estos se muestren por encima.

### Interacciones clave

- `@click="openPopup(section)"`: muestra el panel lateral con la información de la sección.
- `:class="{ 'completed': section.completed, 'locked': !section.unlocked }"`: controla el estilo visual del nodo según su estado.
- `x-show="popupVisible"`: muestra u oculta el InfoBox lateral.
- `@click.outside="closePopup()"`: cierra el InfoBox al hacer clic fuera de él.

### InfoBox (panel lateral)

Se implementa como un panel flotante en el lateral derecho del mapa y contiene:

- Título y estado de la sección.
- Fricción e impacto.
- Diagnóstico ampliado y recomendación principal.
- Lista de subtareas.
- Botón «Marcar como completada» (visible solo si la sección está desbloqueada y no completada).

Se cierra automáticamente al hacer clic fuera y cuenta con una transición suave; también puede cerrarse manualmente.

### Estilos visuales

- El mapa ocupa toda la pantalla (`100vw` × `100vh`) y permite scroll vertical.
- Cada territorio puede tener su propia imagen de fondo (`backgroundImage`).
- Las secciones se posicionan con coordenadas absolutas (`x`, `y`) dentro del SVG.
- El panel de debug (`div.debug-box`) tiene posición fija (`fixed`) y alto `z-index`.

## 🔌 Endpoints REST

El plugin expone varios endpoints REST bajo los namespaces `battle-map/v1` y `conversio-battle-map/v1`. Se utilizan tanto para cargar el mapa del usuario como para actualizar su progreso, consultar catálogos o exportar información.

### Endpoints principales

#### Obtener mapa por token

- **Método:** `GET`
- **Ruta:** `/wp-json/battle-map/v1/user?token=abc123`
- **Descripción:** Devuelve el mapa completo asociado al token recibido. Incluye territorios, secciones, logros y progreso.
- **Parámetros:**
  - `token` (query param) – Token de acceso personalizado.

#### Marcar sección como completada

- **Método:** `POST`
- **Ruta:** `/wp-json/battle-map/v1/user/{userId}/section/{slug}/complete`
- **Descripción:** Marca la sección como completada, recalcula los puntos y activa logros si corresponde.

#### Obtener resumen de progreso

- **Método:** `GET`
- **Ruta:** `/wp-json/battle-map/v1/user/{userId}/summary`
- **Descripción:** Devuelve los puntos totales, secciones y territorios completados.

#### Cargar catálogos del sistema

- **Método:** `GET`
- **Ruta:** `/wp-json/battle-map/v1/catalogs`
- **Descripción:** Devuelve el contenido de `catalogs.json`, incluyendo logros, mensajes, ofertas y configuraciones visuales.

#### Exportar el mapa a PDF

- **Método:** `POST`
- **Ruta:** `/wp-json/battle-map/v1/user/{userId}/export/pdf`
- **Descripción:** Devuelve un archivo PDF con el estado actual del mapa del usuario. Actualmente es un prototipo básico.

### Endpoints equivalentes con prefijo alternativo

Estos endpoints tienen el mismo comportamiento que los anteriores pero usan el namespace `conversio-battle-map/v1`.

#### Obtener mapa por token

`GET /wp-json/conversio-battle-map/v1/map/token/{token}`

#### Completar sección

`POST /wp-json/conversio-battle-map/v1/map/{userId}/section/{slug}/complete`

#### Desbloquear territorio manualmente

`POST /wp-json/conversio-battle-map/v1/map/{userId}/territory/{slug}/unlock`

Body esperado:

```json
{ "productId": "scanner", "paymentVerified": true }
```

Solo si se ha verificado el acceso o la compra correspondiente.
