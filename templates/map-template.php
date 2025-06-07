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
<body x-data="demoMap()" x-init="init()" style="position: relative;">

  <h2>Battle Map Conversio</h2>

  <div style="position:absolute; top:20px; right:20px; background:#ffffff; padding:0.5rem 1rem; border-radius:4px; box-shadow:0 2px 4px rgba(0,0,0,0.1);">
    Progreso: <span x-text="`${mapData.totalPoints} / 1000 puntos`"></span>
  </div>

  <button @click="showAchievementsPanel = true" style="position:absolute; top:20px; left:20px; background:#1e293b; color:#fff; padding:0.25rem 0.5rem; border-radius:4px;">Logros</button>

  <div x-show="mapData.userMap && mapData.userMap.currentTerritorySlug" style="margin-bottom:1rem;">
    <h3 x-text="getCurrentTerritoryTitle()"></h3>
    <div style="height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden;">
      <div style="height:100%; background:#4ade80;" :style="`width:${getTerritoryProgress().percent}%`"></div>
    </div>
    <div style="font-size:12px; margin-top:4px;" x-text="`${getTerritoryProgress().completed} de ${getTerritoryProgress().total} secciones completadas`"></div>
  </div>

  <template x-if="mapData && mapData.userMap && mapData.userMap.territories.length > 0">
    <svg viewBox="0 0 800 200" width="100%" height="200" style="background: #f1f5f9;">
      <template x-if="mapData.visualMap && Array.isArray(mapData.visualMap.paths)">
        <g>
          <template x-for="(path, i) in mapData.visualMap.paths" :key="i">
            <path
              :d="buildPathD(path)"
              stroke="#94a3b8"
              stroke-width="2"
              fill="none"
              :stroke-dasharray="path.dashed ? '4 4' : ''"
            ></path>
          </template>
        </g>
      </template>
      <g
        x-for="(section, index) in mapData.userMap.territories[0].sections"
        :key="section.slug"
        :transform="getNodeTransform(section.slug, index)"
        @click="showPopup(section.slug, $event)"
      >
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
    </svg>
  </template>

  <template x-if="popupBox.visible">
    <div
      class="popup-box"
      :style="`position:absolute; left:${popupBox.position.x}px; top:${popupBox.position.y}px; transform:translate(-50%, 10px); background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.3); padding:1rem; border-radius:8px; width:220px;`">
      <h3 x-text="popupBox.title" style="margin-top:0; font-size:16px;"></h3>
      <p>
        Estado:
        <span x-text="(() => { const sec = popupBox.targetSlug ? mapData.userMap.territories.flatMap(t => t.sections).find(s => s.slug === popupBox.targetSlug) : null; return sec && sec.completed ? 'completada' : (sec && sec.unlocked ? 'desbloqueada' : 'bloqueada'); })()"></span>
      </p>
      <p x-show="popupBox.friction">Fricción: <span x-text="popupBox.friction"></span></p>
      <p x-show="popupBox.recommendation">Recomendación: <span x-text="popupBox.recommendation"></span></p>
      <p x-show="popupBox.details" x-text="popupBox.details"></p>
      <button @click="popupBox.visible = false" style="margin-top:0.5rem;">Cerrar</button>
    </div>
  </template>

  <template x-if="territoryCompletedBox.visible">
    <div style="position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:#ffffff; box-shadow:0 4px 12px rgba(0,0,0,0.3); padding:1rem 1.5rem; border-radius:8px; max-width:420px; text-align:center; z-index:1000;">
      <h3 x-text="territoryCompletedBox.title" style="margin-top:0;"></h3>
      <p x-text="territoryCompletedBox.message" style="margin-bottom:1rem;"></p>
      <a :href="territoryCompletedBox.ctaLink" style="display:inline-block; padding:0.5rem 1rem; background:#2563eb; color:#fff; border-radius:4px; text-decoration:none;" x-text="territoryCompletedBox.ctaLabel"></a>
      <button @click="territoryCompletedBox.visible = false" style="display:block; margin-top:0.5rem;">Cerrar</button>
    </div>
  </template>

  <template x-if="achievementNotification.visible">
    <div x-transition.opacity style="position:fixed; top:70px; left:50%; transform:translateX(-50%); background:#ffffff; padding:0.75rem 1rem; box-shadow:0 2px 8px rgba(0,0,0,0.3); border-radius:6px; z-index:1000;">
      <span x-text="achievementNotification.message"></span>
    </div>
  </template>

  <template x-if="showAchievementsPanel">
    <div style="position:fixed; top:100px; left:50%; transform:translateX(-50%); background:#fff; box-shadow:0 4px 12px rgba(0,0,0,0.3); padding:1rem; border-radius:8px; max-width:300px; z-index:1000;">
      <h3>Logros</h3>
      <ul style="list-style:none; padding-left:0;">
        <template x-for="ach in mapData.achievements" :key="ach.id">
          <li style="margin-bottom:0.5rem;">
            <span x-text="ach.title || ach.id"></span>
            <span x-text="ach.unlocked ? '✅' : '🔒'" style="margin-left:0.25rem;"></span>
          </li>
        </template>
      </ul>
      <button @click="showAchievementsPanel = false" style="margin-top:0.5rem;">Cerrar</button>
    </div>
  </template>

  <div x-show="error" style="color: red; margin-bottom: 1rem;" x-text="error"></div>

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
          userMap: {},
          progressRecord: {},
          achievements: [],
          totalPoints: 0,
          mapSettings: {
            enableSoundFx: false
          }
        },
        popupBox: {
          title: '',
          friction: '',
          recommendation: '',
          details: '',
          visible: false,
          targetSlug: '',
          position: { x: 0, y: 0 }
        },
        territoryCompletedBox: {
          visible: false,
          title: '',
          message: '',
          ctaLabel: '',
          ctaLink: ''
        },
        achievementNotification: {
          visible: false,
          message: ''
        },
        showAchievementsPanel: false,
        init() {
          const token = new URLSearchParams(window.location.search).get('token');
          if (!token) {
            this.error = 'Token no encontrado.';
            return;
          }
          this.loading = true;
          fetch(`/wp-json/conversio-battle-map/v1/map/token/${token}`)
            .then(r => r.json())
            .then(response => {
              if (response.success === true) {
                this.mapData.userMap = response.data.userMap;
                this.mapData.progressRecord = response.data.progressRecord;
                this.mapData.achievements = response.data.achievements;
                this.error = false;
                this.calculatePoints();
                this.checkTerritoryCompletion();
              } else {
                this.error = response.data && response.data.message ? response.data.message : 'Error al cargar el mapa.';
              }
            })
            .catch(() => {
              this.error = 'Error al cargar el mapa.';
            })
            .finally(() => {
              this.loading = false;
            });
        },
        showPopup(slug, evt) {
          if (!this.mapData.userMap || !Array.isArray(this.mapData.userMap.territories)) {
            return;
          }
          let sectionInfo = null;
          for (const territory of this.mapData.userMap.territories) {
            if (!Array.isArray(territory.sections)) continue;
            for (const sec of territory.sections) {
              if (sec.slug === slug) {
                sectionInfo = sec;
                break;
              }
            }
            if (sectionInfo) break;
          }
          if (!sectionInfo) {
            return;
          }
          this.popupBox.title = sectionInfo.title || slug;
          this.popupBox.friction = sectionInfo.friction || '';
          this.popupBox.recommendation = sectionInfo.recommendation || '';
          this.popupBox.details = sectionInfo.details || '';
          this.popupBox.targetSlug = slug;
          const rect = evt.currentTarget.getBoundingClientRect();
          this.popupBox.position.x = rect.left + rect.width / 2 + window.scrollX;
          this.popupBox.position.y = rect.top + rect.height + window.scrollY;
          this.popupBox.visible = true;
        },
        getCurrentTerritory() {
          if (!this.mapData.userMap || !Array.isArray(this.mapData.userMap.territories)) {
            return null;
          }
          return this.mapData.userMap.territories.find(t => t.slug === this.mapData.userMap.currentTerritorySlug) || null;
        },
        getCurrentTerritoryTitle() {
          const terr = this.getCurrentTerritory();
          return terr ? (terr.title || terr.slug) : '';
        },
        getTerritoryProgress() {
          const terr = this.getCurrentTerritory();
          if (!terr || !Array.isArray(terr.sections)) {
            return { completed: 0, total: 0, percent: 0 };
          }
          const total = terr.sections.length;
          const completed = terr.sections.filter(s => s.completed).length;
          const percent = total ? Math.round((completed / total) * 100) : 0;
          return { completed, total, percent };
        },
        checkTerritoryCompletion() {
          const terr = this.getCurrentTerritory();
          if (!terr || !Array.isArray(terr.sections)) {
            return;
          }
          const allDone = terr.sections.every(s => s.completed);
          if (allDone) {
            this.territoryCompletedBox.title = terr.title || terr.slug;
            this.territoryCompletedBox.message =
              '¡Felicidades! Has completado este territorio. Sigue avanzando al siguiente reto!';
            this.territoryCompletedBox.ctaLabel = 'Descubre el siguiente territorio';
            this.territoryCompletedBox.ctaLink = '#';
            this.territoryCompletedBox.visible = true;
          }
          this.calculatePoints();
        },
        calculatePoints() {
          if (!this.mapData.userMap || !Array.isArray(this.mapData.userMap.territories)) {
            this.mapData.totalPoints = 0;
            return;
          }
          const multipliers = { None: 0, Low: 1, Medium: 1.2, High: 1.5, Critical: 2 };
          let completedWeight = 0;
          let totalWeight = 0;
          for (const terr of this.mapData.userMap.territories) {
            if (!terr.unlocked || !Array.isArray(terr.sections)) continue;
            for (const sec of terr.sections) {
              const friction = sec.friction || 'None';
              const impact = parseFloat(sec.impact) || 0;
              const weight = impact * (multipliers[friction] ?? 0);
              if (sec.completed) completedWeight += weight;
              totalWeight += weight;
            }
          }
          if (totalWeight > 0) {
            this.mapData.totalPoints = Math.floor((completedWeight / totalWeight) * 1000);
          } else {
            this.mapData.totalPoints = 0;
          }
          this.unlockAchievements();
        },
        unlockAchievements() {
          const catalog = [
            {
              id: 'first-step',
              title: 'Primer paso \uD83C\uDF31',
              check: () => {
                if (!this.mapData.userMap || !Array.isArray(this.mapData.userMap.territories)) return false;
                for (const terr of this.mapData.userMap.territories) {
                  if (!Array.isArray(terr.sections)) continue;
                  if (terr.sections.some(s => s.completed)) return true;
                }
                return false;
              }
            },
            {
              id: 'clarity-complete',
              title: 'Clarity Call completo',
              check: () => {
                if (!this.mapData.userMap || !Array.isArray(this.mapData.userMap.territories)) return false;
                const terr = this.mapData.userMap.territories.find(t => t.slug === 'clarity-call');
                return terr && Array.isArray(terr.sections) && terr.sections.every(s => s.completed);
              }
            },
            {
              id: 'half-map',
              title: 'Mitad del mapa',
              check: () => this.mapData.totalPoints >= 500
            },
            {
              id: 'full-map',
              title: 'Mapa completo \uD83C\uDFC6',
              check: () => this.mapData.totalPoints === 1000
            }
          ];

          catalog.forEach(ach => {
            const existing = this.mapData.achievements.find(a => a.id === ach.id);
            const unlocked = existing && existing.unlocked;
            if (!unlocked && ach.check()) {
              const now = new Date().toISOString();
              if (existing) {
                existing.unlocked = true;
                existing.unlockedAt = now;
                existing.title = ach.title;
              } else {
                this.mapData.achievements.push({ id: ach.id, title: ach.title, unlocked: true, unlockedAt: now });
              }
              this.showAchievement(ach.title);
            }
          });
        },
        showAchievement(title) {
          this.achievementNotification.message = `\u00A1Logro desbloqueado! ${title}`;
          this.achievementNotification.visible = true;
          if (this.mapData.mapSettings && this.mapData.mapSettings.enableSoundFx) {
            try {
              const audio = new Audio('/wp-content/plugins/conversio-battle-map/assets/success.mp3');
              audio.play();
            } catch (e) {}
            if (navigator.vibrate) navigator.vibrate(200);
          }
          setTimeout(() => { this.achievementNotification.visible = false; }, 3000);
        },
        getNodePosition(slug) {
          if (this.mapData.visualMap && Array.isArray(this.mapData.visualMap.nodes)) {
            const node = this.mapData.visualMap.nodes.find(n => n.slug === slug);
            if (node) return { x: parseFloat(node.x), y: parseFloat(node.y) };
          }
          if (this.mapData.userMap && this.mapData.userMap.territories && this.mapData.userMap.territories[0]) {
            const idx = this.mapData.userMap.territories[0].sections.findIndex(s => s.slug === slug);
            if (idx >= 0) {
              return { x: 100 + idx * 200, y: 100 };
            }
          }
          return null;
        },
        getNodeTransform(slug, index) {
          const pos = this.getNodePosition(slug);
          if (pos) {
            return `translate(${pos.x}, ${pos.y})`;
          }
          return `translate(${100 + index * 200}, 100)`;
        },
        buildPathD(path) {
          const from = this.getNodePosition(path.fromSlug);
          const to = this.getNodePosition(path.toSlug);
          if (!from || !to) return '';
          if (path.pathType === 'curve') {
            const cx = (from.x + to.x) / 2;
            const cy = Math.min(from.y, to.y) - 40;
            return `M ${from.x} ${from.y} C ${cx} ${cy}, ${cx} ${cy}, ${to.x} ${to.y}`;
          }
          return `M ${from.x} ${from.y} L ${to.x} ${to.y}`;
        }
      }
    }
  </script>

</body>
</html>
