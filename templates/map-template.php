<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Battle Map Conversio</title>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script>
    window.cbmBaseUrl = "<?php echo esc_url( CBM_PLUGIN_URL ); ?>";
  </script>
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

  <div class="relative w-screen h-screen overflow-y-auto bg-white text-black font-sans">
    <header class="sticky top-0 z-50 bg-black/80 backdrop-blur px-4 py-3 flex justify-between items-center">
      <div class="flex items-center gap-4">
        <h1 class="text-xl font-bold">Battle Map Conversio</h1>
        <nav class="flex gap-2 text-sm">
          <template x-for="territory in mapData.userMap.territories.filter(t => t.unlocked)">
            <a :href="`#territory-${territory.slug}`" class="hover:underline text-white/80">
              <span x-text="territory.title.split(' ')[0]"></span>
            </a>
          </template>
        </nav>
      </div>
      <button class="text-sm border px-3 py-1 rounded border-white/30 hover:bg-white/10" @click="showAchievementsPanel = true">Logros</button>
    </header>

    <div class="sticky top-[56px] z-40 bg-black/60 px-4 py-2 text-sm backdrop-blur">
      <p>Puntuación acumulada</p>
      <h2 class="text-lg font-semibold" x-text="`${totalPoints} / 1000 puntos`"></h2>
    </div>

    <template x-for="territory in mapData.userMap.territories.filter(t => t.unlocked)" :key="territory.slug">
      <section
        :id="`territory-${territory.slug}`"
        class="w-full min-h-screen px-4 py-16 flex flex-col gap-6 relative snap-start bg-cover bg-center bg-no-repeat"
        :style="`background-image: url(${territory.backgroundImage || cbmBaseUrl + '/assets/backgrounds/' + territory.slug + '.png'}), linear-gradient(to bottom, #f1f5f9, #e2e8f0);`"
      >
        <div class="bg-black/50 p-4 rounded max-w-xl">
          <h2 class="text-2xl font-bold" x-text="territory.title"></h2>
          <p class="text-sm mt-1" x-text="territory.description"></p>
        </div>

        <svg class="map-canvas w-full h-[500px] relative" xmlns="http://www.w3.org/2000/svg">
          <template x-for="path in mapData.visualMap?.paths?.filter(p => p.fromSlug && p.toSlug)" :key="path.fromSlug + '-' + path.toSlug">
            <path
              :d="buildPathD(path)"
              fill="none"
              stroke="#94a3b8"
              stroke-width="2"
              :class="path.style"
            />
          </template>
          <template x-for="(section, index) in territory.sections" :key="section.slug">
            <g :transform="getNodeTransform(section.slug, index)" @click="openPopup(section)" style="cursor: pointer;">
              <circle r="30" fill="white" stroke="#1e3a8a" stroke-width="3"
                      :class="{ 'opacity-50': !section.unlocked, 'fill-green-500': section.completed }" />
              <image :href="`${cbmBaseUrl}/assets/icons/${section.slug}.png`"
                     x="-16" y="-16" width="32" height="32"
                     @error="$el.style.display='none'" />
              <text x="0" y="45" text-anchor="middle" font-size="12" fill="#fff" x-text="section.title"></text>
            </g>
          </template>
        </svg>
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

  <div x-show="popupVisible"
       x-transition
       @click.outside="closePopup"
       class="fixed top-[80px] right-4 w-[320px] max-w-full bg-white/90 text-black rounded-lg shadow-lg p-5 z-50 space-y-3"
       style="backdrop-filter: blur(8px);">

    <div class="flex justify-between items-start">
      <div class="flex items-start gap-3">
        <img
          class="w-10 h-10 mb-2"
          :src="`${cbmBaseUrl}/assets/icons/${activeSection?.slug}.png`"
          :alt="activeSection?.title"
          @error="$el.style.display='none'"
        />
        <h2 class="text-xl font-bold" x-text="activeSection?.title"></h2>
      </div>
      <button @click="closePopup" class="text-gray-600 hover:text-black text-xl font-bold">×</button>
    </div>

    <div class="bg-gray-100 text-gray-800 rounded px-3 py-2 text-sm space-y-1">
      <p><strong>Fricción:</strong> <span x-text="activeSection?.friction"></span></p>
      <p><strong>Impacto:</strong> <span x-text="activeSection?.impact"></span></p>
    </div>

    <template x-if="activeSection?.details">
      <div class="mt-3">
        <p class="text-sm font-semibold mb-1 text-gray-800">Síntomas detectados:</p>
        <p class="text-sm italic text-gray-600" x-text="activeSection.details"></p>
      </div>
    </template>

    <p class="text-sm font-medium text-blue-600 italic"
       x-text="activeSection?.recommendation"></p>

    <template x-if="activeSection?.recommendationsList?.length">
      <div class="mt-4">
        <p class="text-sm font-semibold mb-1">Recomendaciones clave:</p>
        <ul class="space-y-2 text-sm text-gray-700 list-none pl-0">
          <template x-for="(rec, index) in activeSection.recommendationsList">
            <li class="flex items-start gap-2 bg-white/80 p-2 rounded shadow">
              <!-- Prioridad -->
              <span
                class="block w-2 h-2 mt-[6px] rounded-full"
                :class="{
                  'bg-red-500': (rec.priority || 'medium') === 'high',
                  'bg-yellow-500': (rec.priority || 'medium') === 'medium',
                  'bg-green-500': (rec.priority || 'medium') === 'low'
                }"
              ></span>

              <!-- Contenido -->
              <div class="flex-1 space-y-1">
                <p x-text="rec.title" class="leading-tight font-medium text-gray-800"></p>
                <!-- Tipo -->
                <template x-if="rec.type">
                  <span x-text="rec.type"
                        class="text-xs font-semibold px-2 py-[1px] rounded bg-slate-200 text-slate-800"></span>
                </template>
              </div>
            </li>
          </template>
        </ul>
      </div>
    </template>

    <button x-show="activeSection?.unlocked && !activeSection?.completed"
            @click="markAsCompleted(activeSection.slug)"
            class="mt-4 w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">
      Marcar como completada
    </button>
  </div>

  <div x-show="error" style="color: red; margin-bottom: 1rem;" x-text="error"></div>

  </div> <!-- fin del contenedor principal del mapa -->

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
        cbmBaseUrl: '<?php echo esc_url( CBM_PLUGIN_URL ); ?>',
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
        popupVisible: false,
        activeSection: null,
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
          this.activeSection = section;
          this.popupVisible = true;
        },
        closePopup() {
          this.popupVisible = false;
          this.activeSection = null;
        },
        markAsCompleted(slug) {
          if (!this.mapData.userMap || !this.mapData.userMap.userId) {
            return;
          }
          fetch(`/wp-json/battle-map/v1/user/${this.mapData.userMap.userId}/section/${slug}/complete`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            }
          })
            .then(res => res.json())
            .then(data => {
              // actualizar estado local con la respuesta
              if (data && data.userMap) {
                this.mapData.userMap = data.userMap;
                if (data.progressRecord) {
                  this.mapData.progressRecord = data.progressRecord;
                }
                if (Array.isArray(data.newAchievements)) {
                  data.newAchievements.forEach(a => this.showAchievement?.(a.title || a.id));
                }
                if (data.narrativeMessages && data.narrativeMessages.length) {
                  alert(data.narrativeMessages[0].text || data.narrativeMessages[0].message || '¡Buen trabajo!');
                }
              }
              this.calculatePoints();
              this.popupVisible = false;
            })
            .catch(err => console.error('Error al completar sección:', err));
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
          this.popupVisible = false;

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
