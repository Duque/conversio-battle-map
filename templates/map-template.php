<?php
$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
?>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<div id="cbm-map" x-data="battleMap()" x-init="fetchData()">
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
function battleMap() {
    return {
        mapData: null,
        async fetchData() {
            const token = '<?php echo esc_js( $token ); ?>';
            if (!token) {
                return;
            }
            try {
                const res = await fetch('/wp-json/battle-map/v1/user?token=' + token);
                if (res.ok) {
                    this.mapData = await res.json();
                }
            } catch (e) {
                console.error(e);
            }
        }
    }
}
</script>
