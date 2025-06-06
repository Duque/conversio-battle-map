<?php
$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
?>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<div id="cbm-map" x-data="demoMap()">
    <h2>Battle Map Conversio</h2>
    <svg viewBox="0 0 800 200" width="100%" height="auto">
      <template x-if="mapData">
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
      </template>
    </svg>
</div>

<script>
  function demoMap() {
    return {
      loading: false,
      error: false,
      mapData: {
        userMap: {
          currentTerritorySlug: 'clarity-call',
          currentSectionSlug: 'product',
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
            },
            {
              slug: 'battle-map',
              title: 'Battle Map\u2122',
              unlocked: false,
              completed: false,
              order: 2,
              sections: [
                { slug: 'mobile-speed', completed: false, unlocked: false },
                { slug: 'usability', completed: false, unlocked: false },
                { slug: 'trust', completed: false, unlocked: false }
              ]
            }
          ]
        }
      }
    }
  }
</script>
