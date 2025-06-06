# Battle Map Conversio

Este repositorio contiene la implementaci\xC3\xB3n inicial del plugin **Battle Map Conversio** para WordPress. El objetivo es ofrecer una experiencia gamificada que muestre el avance de un usuario a trav\xC3\xA9s del m\xC3\xA9todo Conversio (Clarity Call, Battle Map y Scanner Conversio).

## Estructura del plugin

```
conversio-battle-map/
└── conversio-battle-map/
    ├── conversio-battle-map.php         # Cargador principal del plugin
    ├── includes/
    │   ├── register-cpt.php             # Registro del Custom Post Type
    │   ├── class-user-map.php           # Utilidades para generar mapas
    │   ├── class-rest-endpoints.php     # Endpoints REST básicos
    │   └── token-auth.php               # Validación simple del token
    ├── templates/
    │   └── map-template.php             # Marcado HTML del mapa
    ├── assets/
    │   ├── style.css
    │   └── alpine.min.js
    ├── data/
    │   ├── catalogs.json                # Catálogos de ejemplo
    │   └── dummy-user-map.json          # Mapa de ejemplo
    └── admin/
        └── settings-page.php            # Esbozo de página de ajustes
```

## Endpoints disponibles

El plugin expone un primer endpoint para recuperar el mapa asociado a un token:

```
GET /wp-json/battle-map/v1/user?token=YOUR_TOKEN
```

Devuelve los datos guardados en el CPT `battle_map` en las claves `user_map`,
`progress_record` y `achievements`.

Además, se añade un segundo endpoint para marcar secciones completadas:

```
POST /wp-json/battle-map/v1/user/<userId>/section/<slug>/complete
```

Actualiza la sección indicada dentro del mapa del usuario y devuelve el
`user_map` actualizado junto con el objeto `progressRecord` que incluye los
puntos totales y la fecha de actualización.

Se incluye también un endpoint para obtener un resumen de progreso:

```
GET /wp-json/battle-map/v1/user/<userId>/summary
```

Devuelve información básica de avance como puntos totales y secciones completadas.

Y un endpoint para cargar los catálogos base utilizados por el mapa:

```
GET /wp-json/battle-map/v1/catalogs
```

Lee el archivo `data/catalogs.json` del plugin y lo retorna como respuesta.

Por último, se ha añadido un endpoint para descargar un PDF con el estado del mapa:

```
POST /wp-json/battle-map/v1/user/<userId>/export/pdf
```

Genera un documento PDF (simulado) con la información principal del mapa y lo devuelve como descarga.

## Uso

1. Clona el repositorio en tu instalaci\xC3\xB3n de WordPress dentro de `wp-content/plugins`.
2. Activa el plugin *Battle Map Conversio* desde el panel de administraci\xC3\xB3n.
3. Accede al endpoint REST `wp-json/battle-map/v1/user?token=demo` para obtener un JSON de ejemplo.

Esta versi\xC3\xB3n es solo un punto de partida para seguir desarrollando la l\xC3\xB3gica de territorios, secciones, puntos y logros descrita en el dise\xC3\xB1o del producto.
