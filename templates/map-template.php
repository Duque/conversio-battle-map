<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Battle Map Conversio</title>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <style>
    body {
      font-family: system-ui, sans-serif;
      padding: 2rem;
      background-color: #f8fafc;
    }
    h2 {
      font-size: 24px;
      margin-bottom: 1rem;
    }
    svg {
      border: 1px solid #cbd5e1;
      margin-bottom: 2rem;
      background: #ffffff;
    }
    text {
      pointer-events: none;
    }
    .debug {
      background: #fef9c3;
      padding: 1rem;
      font-size: 14px;
      white-space: pre-wrap;
      border-radius: 8px;
      margin-top: 2rem;
    }
  </style>
</head>
<body x-data="demoMap()">

  <h2>Battle Map Conversio</h2>

  <template x-if="mapData && mapData.userMap && mapData.userMap.territories.length > 0">
    <svg viewBox="0 0 800 200" width="100%" height="auto">
      <template x-for="(section, index) in mapData.userMap.territories[0].sections" :key="section.slug">
        <g :transform="`translate(${100 + index * 200}, 100)`">
          <circle
            r="40"
            :fill="section.completed ? '#4ade80' : (section.unlocked ? '#facc15' : '#94a3b8')"
            stroke="#1e293b"
            stroke-width="3"
          ></circle>
          <text
            x="0"
            y="5"
            font-size="14"
            fill="#1e293b"
            text-anchor="middle"
            x-text="section.slug"
          ></text>
        </g>
      </template>
    </svg>
  </template>

  <div class="debug">
    <strong>Debug:</strong><br>
    Estado: <span x-text="loading ? 'Cargando...' : (error ? 'Error' : 'OK')"></span><br>
    Token: <span x-text="new URLSearchParams(window.location.search).get('token')"></span><br>
    Datos cargados:<br>
    <pre x-text="JSON.stringify(mapData, null, 2)"></pre>
  </div>

  <script>
    function demoMap() {
      return {
        loading: false,
        error: false,
        mapData: {
          userMap: {
            currentTerritorySlug: 'clarity-call',
            territories: [
              {
                slug: 'clarity-call',
                title: 'Clarity Call\u2122',
                unlocked: true,
                completed: false,
                order: 1,
                sections: [
                  { slug: 'home', completed: true, unlocked: true },
                  { slug: 'product', completed: false, unlocked: true },
                  { slug: 'cart', completed: false, unlocked: false },
                  { slug: 'checkout', completed: false, unlocked: false }
                ]
              }
            ]
          }
        }
      }
    }
  </script>

</body>
</html>
