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
      background-color: #f8fafc;
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
<body x-data="demoMap()" x-init="init()">

  <main class="min-h-screen w-full bg-slate-100 overflow-y-auto p-4">
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">Battle Map Conversio</h1>
      <button @click="showAchievementsPanel = true" class="bg-gray-800 text-white px-2 py-1 rounded">Logros</button>
    </header>

    <div class="bg-white shadow p-4 text-center sticky top-0 z-40">
      <p class="text-sm text-gray-600">Puntuación acumulada</p>
      <h2 class="text-2xl font-bold text-green-700" x-text="`${totalPoints} / 1000 puntos`"></h2>
    </div>

    <template x-for="territory in mapData.userMap.territories.filter(t => t.unlocked)" :key="territory.slug">
      <section
        class="py-10 px-4 mb-8 rounded shadow-md"
        :style="`background-color: ${getTerritoryColor(territory.slug)}`"
      >
        <h2 class="text-xl font-bold text-white" x-text="territory.title"></h2>
        <p class="text-sm text-gray-200 mb-4" x-text="territory.description"></p>

        <template x-for="section in territory.sections" :key="section.slug">
          <div class="relative bg-white rounded p-4 shadow mb-4 cursor-pointer"
               @click="openPopup(section)"
               :class="{ 'opacity-100': section.unlocked, 'opacity-50': !section.unlocked, 'border-l-4 border-green-500': section.completed }">
            <div class="absolute top-2 right-2 text-xl">
              <template x-if="section.completed">
                <span class="text-green-600" title="Completada">✅</span>
              </template>
              <template x-if="!section.completed && section.unlocked">
                <span class="text-blue-600" title="Desbloqueada">🔓</span>
              </template>
              <template x-if="!section.unlocked">
                <span class="text-gray-400" title="Bloqueada">🔒</span>
              </template>
            </div>
            <h3 class="text-lg font-bold" x-text="section.title"></h3>
            <p class="text-sm text-gray-700 mt-1" x-text="`Impacto: ${section.impact}`"></p>
            <p class="text-sm text-gray-500 italic" x-text="`Fricción: ${section.friction}`"></p>
          </div>
        </template>
      </section>
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

    </main>

  <div x-show="popupBox.visible"
       class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
       @click.self="closePopup">
    <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-lg relative">
      <button @click="closePopup"
              class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
        ✕
      </button>

      <h2 class="text-xl font-bold mb-2" x-text="popupBox.section?.title"></h2>
      <p class="text-sm text-gray-600 mb-1"
         x-text="`Fricción: ${popupBox.section?.friction}`"></p>
      <p class="text-sm text-gray-600 mb-1"
         x-text="`Impacto: ${popupBox.section?.impact}`"></p>
      <p class="text-gray-800 mt-4 text-sm" x-text="popupBox.section?.details"></p>
      <p class="text-blue-700 font-medium mt-2 text-sm italic"
         x-text="popupBox.section?.recommendation"></p>

      <template x-if="popupBox.section?.unlocked && !popupBox.section?.completed">
        <button @click="completeSection(popupBox.section.slug)"
                class="mt-4 w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">
          Marcar como completada
        </button>
      </template>
    </div>
  </div>

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
          mapSettings: {
            enableSoundFx: false
          }
        },
        totalPoints: 0,
        popupBox: {
          visible: false,
          section: null
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
        hoverIndex: null,
        get currentTerritory() {
          if (!this.mapData.userMap || !Array.isArray(this.mapData.userMap.territories)) {
            return { sections: [] };
          }
          return this.mapData.userMap.territories.find(t => t.slug === this.mapData.userMap.currentTerritorySlug) || { sections: [] };
        },
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
        openPopup(section) {
          this.popupBox.section = section;
          this.popupBox.visible = true;
        },
        closePopup() {
          this.popupBox.visible = false;
        },
        completeSection(slug) {
          let territory = null;
          let section = null;
          for (const terr of this.mapData.userMap?.territories || []) {
            const sec = terr.sections?.find(s => s.slug === slug);
            if (sec) { territory = terr; section = sec; break; }
          }
          if (!section || !section.unlocked || section.completed) return;

          // 1. Marcar sección como completada
          section.completed = true;

          // 2. Desbloquear secciones siguientes si las hay
          (section.next || []).forEach(nextSlug => {
            const next = territory.sections.find(s => s.slug === nextSlug);
            if (next && !next.unlocked) next.unlocked = true;
          });

          // 3. Verificar si todo el territorio está completado
          if (territory.sections.every(s => s.completed)) {
            territory.completed = true;
          }

          // 4. Recalcular puntos
          this.calculatePoints();

          // 5. Evaluar logros
          this.unlockAchievements?.();

          // 6. Cerrar popup
          this.popupBox.visible = false;

          // Mostrar mensaje motivacional si se completó todo el territorio
          this.checkTerritoryCompletion(territory);

          // 7. Sincronizar con backend
          if (this.mapData.userMap && this.mapData.userMap.userId) {
            fetch(`/wp-json/conversio-battle-map/v1/map/${this.mapData.userMap.userId}/section/${slug}/complete`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                timestamp: new Date().toISOString()
              })
            })
              .then(res => res.json())
              .then(data => {
                if (!data.success) {
                  console.error('Error al sincronizar sección:', data);
                }
              });
          }
        },
        getCurrentTerritory() {
          return this.currentTerritory;
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
        checkTerritoryCompletion(territory = null) {
          const terr = territory || this.getCurrentTerritory();
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
            this.totalPoints = 0;
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
            this.totalPoints = Math.round((completedWeight / totalWeight) * 1000);
          } else {
            this.totalPoints = 0;
          }
          this.mapData.totalPoints = this.totalPoints;
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
              check: () => this.totalPoints >= 500
            },
            {
              id: 'full-map',
              title: 'Mapa completo \uD83C\uDFC6',
              check: () => this.totalPoints === 1000
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
        getTerritoryColor(slug) {
          const map = {
            'clarity-call': '#0ea5e9',
            'battle-map': '#10b981',
            'scanner': '#8b5cf6'
          };
          return map[slug] || '#94a3b8';
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
