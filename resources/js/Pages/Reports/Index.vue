<template>
  <AppLayout>
    <div class="page-wrapper">

      <!-- EN-TÊTE -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Rapports</h1>
          <p class="page-sub">Analyse globale des opérations</p>
        </div>
        <a :href="route('reports.export')" class="btn-export">⬇ Exporter CSV</a>
      </div>

      <!-- KPIs -->
      <section class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">Opérations totales</div>
          <div class="kpi-value">{{ kpis.total }}</div>
        </div>
        <div class="kpi-card kpi-green">
          <div class="kpi-label">Accomplies</div>
          <div class="kpi-value">{{ kpis.completed }}</div>
        </div>
        <div class="kpi-card kpi-red">
          <div class="kpi-label">Abandonnées</div>
          <div class="kpi-value">{{ kpis.cancelled }}</div>
        </div>
        <div class="kpi-card kpi-blue">
          <div class="kpi-label">Taux d'accomplissement</div>
          <div class="kpi-value">{{ kpis.successRate }}%</div>
          <div class="kpi-bar-track">
            <div class="kpi-bar-fill" :style="{ width: kpis.successRate + '%' }"></div>
          </div>
        </div>
      </section>

      <!-- GRAPHIQUE MENSUEL -->
      <section class="card">
        <h2 class="card-title">Opérations par mois <span class="card-sub">6 derniers mois</span></h2>
        <div class="bar-chart">
          <div class="bar-col" v-for="m in months" :key="m.label">
            <div class="bar-stack-wrap">
              <div class="bar-tooltip">
                <div>Total : <strong>{{ m.total }}</strong></div>
                <div class="tt-completed">Accomplies : {{ m.completed }}</div>
                <div class="tt-cancelled">Abandonnées : {{ m.cancelled }}</div>
                <div class="tt-pending">En attente : {{ m.pending }}</div>
                <div class="tt-active">En cours : {{ m.in_progress }}</div>
              </div>
              <div class="bar-stack" :style="{ height: barHeight(m.total) + 'px' }">
                <div class="bar-seg seg-completed"  :style="{ flex: m.completed   }"></div>
                <div class="bar-seg seg-active"     :style="{ flex: m.in_progress }"></div>
                <div class="bar-seg seg-pending"    :style="{ flex: m.pending     }"></div>
                <div class="bar-seg seg-cancelled"  :style="{ flex: m.cancelled   }"></div>
              </div>
            </div>
            <div class="bar-label">{{ m.label }}</div>
            <div class="bar-total">{{ m.total }}</div>
          </div>
        </div>
        <div class="bar-legend">
          <span class="legend-item"><span class="dot dot-completed"></span>Accomplies</span>
          <span class="legend-item"><span class="dot dot-active"></span>En cours</span>
          <span class="legend-item"><span class="dot dot-pending"></span>En attente</span>
          <span class="legend-item"><span class="dot dot-cancelled"></span>Abandonnées</span>
        </div>
      </section>

      <!-- GRILLE BASSE -->
      <div class="bottom-grid">

        <!-- STATS PAR AGENT -->
        <section class="card card-wide">
          <h2 class="card-title">Performance par agent</h2>
          <div v-if="agents.length" class="agents-table-wrap">
            <table class="agents-table">
              <thead>
                <tr>
                  <th>Agent</th>
                  <th class="tc">Total</th>
                  <th class="tc">Accomplies</th>
                  <th class="tc">Abandonnées</th>
                  <th>Taux de réussite</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in agents" :key="a.id">
                  <td>
                    <div class="agent-cell">
                      <div class="agent-avatar" :style="{ background: getColor(a.name) }">{{ getInitials(a.name) }}</div>
                      <div>
                        <div class="agent-name">{{ a.name }}</div>
                        <div class="agent-role">{{ a.role || '—' }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="tc bold">{{ a.total }}</td>
                  <td class="tc text-green">{{ a.completed }}</td>
                  <td class="tc text-red">{{ a.cancelled }}</td>
                  <td>
                    <div class="rate-row">
                      <div class="rate-track">
                        <div class="rate-fill" :style="{ width: a.successRate + '%', background: rateColor(a.successRate) }"></div>
                      </div>
                      <span class="rate-label">{{ a.successRate }}%</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="empty">Aucune donnée disponible.</p>
        </section>

        <!-- DISTRIBUTIONS -->
        <div class="dist-col">

          <!-- Par statut -->
          <section class="card">
            <h2 class="card-title">Par statut</h2>
            <div class="dist-list">
              <div class="dist-item" v-for="s in statuses" :key="s.key">
                <div class="dist-header">
                  <span class="dist-label">{{ s.label }}</span>
                  <span class="dist-count">{{ s.count }}</span>
                </div>
                <div class="dist-track">
                  <div class="dist-fill" :class="'fill-status-' + s.key" :style="{ width: distPct(s.count, statusTotal) + '%' }"></div>
                </div>
              </div>
            </div>
          </section>

          <!-- Par priorité -->
          <section class="card">
            <h2 class="card-title">Par priorité</h2>
            <div class="dist-list">
              <div class="dist-item" v-for="p in priorities" :key="p.key">
                <div class="dist-header">
                  <span class="dist-label">{{ p.label }}</span>
                  <span class="dist-count">{{ p.count }}</span>
                </div>
                <div class="dist-track">
                  <div class="dist-fill" :class="'fill-prio-' + p.key" :style="{ width: distPct(p.count, kpis.total) + '%' }"></div>
                </div>
              </div>
            </div>
          </section>

        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'ReportsIndex',
  components: { AppLayout },

  props: {
    kpis:       { type: Object, default: () => ({}) },
    months:     { type: Array,  default: () => [] },
    agents:     { type: Array,  default: () => [] },
    priorities: { type: Array,  default: () => [] },
    statuses:   { type: Array,  default: () => [] },
  },

  computed: {
    maxMonthTotal() {
      return Math.max(...this.months.map(m => m.total), 1)
    },
    statusTotal() {
      return this.statuses.reduce((sum, s) => sum + s.count, 0) || 1
    },
  },

  methods: {
    barHeight(total) {
      return Math.max(4, Math.round((total / this.maxMonthTotal) * 140))
    },
    distPct(count, total) {
      return total > 0 ? Math.round((count / total) * 100) : 0
    },
    rateColor(rate) {
      if (rate >= 75) return '#22c55e'
      if (rate >= 40) return '#f59e0b'
      return '#ef4444'
    },
    getInitials(name) {
      return name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() ?? '??'
    },
    getColor(name) {
      let hash = 0
      for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
      return `hsl(${Math.abs(hash) % 360}, 50%, 38%)`
    },
  },
}
</script>

<style scoped>
.page-wrapper { padding: 32px; max-width: 1400px; }
.page-header  { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: flex-start; }
.btn-export {
  background: white;
  border: 1.5px solid #e5e7eb;
  color: #374151;
  padding: 10px 18px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.15s;
  white-space: nowrap;
}
.btn-export:hover { border-color: #4f6fee; color: #4f6fee; }
.page-title   { font-size: 24px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.page-sub     { font-size: 14px; color: #6b7280; margin: 0; }

/* ── KPIs ─────────────────────────────────────────────── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}
.kpi-card {
  background: white;
  border-radius: 16px;
  padding: 22px 24px;
  box-shadow: 0 2px 12px rgba(0,0,0,.05);
  border-left: 4px solid #e5e7eb;
}
.kpi-card.kpi-green { border-left-color: #22c55e; }
.kpi-card.kpi-red   { border-left-color: #ef4444; }
.kpi-card.kpi-blue  { border-left-color: #4f6fee; }
.kpi-label { font-size: 12px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.kpi-value { font-size: 32px; font-weight: 800; color: #1a1f2e; line-height: 1; margin-bottom: 10px; }
.kpi-bar-track { height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; }
.kpi-bar-fill  { height: 100%; background: #4f6fee; border-radius: 3px; transition: width 0.6s ease; }

/* ── CARTE GÉNÉRIQUE ──────────────────────────────────── */
.card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0,0,0,.05);
  margin-bottom: 24px;
}
.card-title {
  font-size: 15px;
  font-weight: 700;
  color: #1a1f2e;
  margin: 0 0 20px;
}
.card-sub {
  font-size: 12px;
  font-weight: 400;
  color: #9ca3af;
  margin-left: 8px;
}

/* ── GRAPHIQUE EN BARRES ──────────────────────────────── */
.bar-chart {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  height: 180px;
  padding-bottom: 4px;
}
.bar-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  position: relative;
}
.bar-stack-wrap {
  position: relative;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: flex-end;
}
.bar-stack-wrap:hover .bar-tooltip { opacity: 1; pointer-events: auto; }
.bar-tooltip {
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  background: #1a1f2e;
  color: white;
  font-size: 12px;
  padding: 8px 12px;
  border-radius: 8px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.15s;
  z-index: 10;
  line-height: 1.6;
}
.bar-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #1a1f2e;
}
.tt-completed { color: #4ade80; }
.tt-cancelled { color: #f87171; }
.tt-pending   { color: #93c5fd; }
.tt-active    { color: #fcd34d; }

.bar-stack {
  width: 70%;
  min-height: 4px;
  border-radius: 6px 6px 0 0;
  overflow: hidden;
  display: flex;
  flex-direction: column-reverse;
  transition: height 0.4s ease;
}
.bar-seg { min-height: 0; transition: flex 0.4s ease; }
.seg-completed { background: #22c55e; }
.seg-active    { background: #f59e0b; }
.seg-pending   { background: #4f6fee; }
.seg-cancelled { background: #ef4444; }

.bar-label { font-size: 11px; color: #9ca3af; text-align: center; white-space: nowrap; }
.bar-total { font-size: 12px; font-weight: 700; color: #374151; }

.bar-legend {
  display: flex;
  gap: 20px;
  margin-top: 16px;
  flex-wrap: wrap;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #6b7280;
}
.dot {
  width: 10px; height: 10px; border-radius: 50%;
}
.dot-completed { background: #22c55e; }
.dot-active    { background: #f59e0b; }
.dot-pending   { background: #4f6fee; }
.dot-cancelled { background: #ef4444; }

/* ── GRILLE BASSE ─────────────────────────────────────── */
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 24px;
  align-items: start;
}
.card-wide { margin-bottom: 0; }
.dist-col  { display: flex; flex-direction: column; gap: 0; }
.dist-col .card { margin-bottom: 24px; }
.dist-col .card:last-child { margin-bottom: 0; }

/* ── TABLE AGENTS ─────────────────────────────────────── */
.agents-table-wrap { overflow-x: auto; }
.agents-table {
  width: 100%;
  border-collapse: collapse;
}
.agents-table th {
  padding: 8px 12px;
  font-size: 11px;
  font-weight: 700;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}
.agents-table td {
  padding: 12px;
  font-size: 13px;
  color: #374151;
  border-bottom: 1px solid #f9fafb;
}
.agents-table tbody tr:last-child td { border-bottom: none; }
.agents-table tbody tr:hover td { background: #fafafa; }
.tc        { text-align: center; }
.bold      { font-weight: 700; color: #1a1f2e; }
.text-green { color: #16a34a; font-weight: 600; }
.text-red   { color: #dc2626; font-weight: 600; }

.agent-cell   { display: flex; align-items: center; gap: 10px; }
.agent-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 11px; font-weight: 700; flex-shrink: 0; }
.agent-name   { font-size: 13px; font-weight: 600; color: #1a1f2e; }
.agent-role   { font-size: 11px; color: #9ca3af; }

.rate-row   { display: flex; align-items: center; gap: 8px; }
.rate-track { flex: 1; height: 6px; background: #f3f4f6; border-radius: 3px; overflow: hidden; }
.rate-fill  { height: 100%; border-radius: 3px; transition: width 0.5s ease; }
.rate-label { font-size: 12px; font-weight: 700; color: #374151; min-width: 34px; text-align: right; }

/* ── DISTRIBUTIONS ────────────────────────────────────── */
.dist-list { display: flex; flex-direction: column; gap: 12px; }
.dist-item {}
.dist-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
.dist-label  { font-size: 13px; font-weight: 500; color: #374151; }
.dist-count  { font-size: 13px; font-weight: 700; color: #1a1f2e; }
.dist-track  { height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
.dist-fill   { height: 100%; border-radius: 4px; transition: width 0.5s ease; }

.fill-status-pending     { background: #4f6fee; }
.fill-status-in_progress { background: #f59e0b; }
.fill-status-completed   { background: #22c55e; }
.fill-status-cancelled   { background: #ef4444; }

.fill-prio-urgent { background: #dc2626; }
.fill-prio-high   { background: #ea580c; }
.fill-prio-medium { background: #f59e0b; }
.fill-prio-low    { background: #22c55e; }

.empty { font-size: 13px; color: #9ca3af; font-style: italic; }

/* ── RESPONSIVE ───────────────────────────────────────── */
@media (max-width: 1200px) {
  .bottom-grid { grid-template-columns: 1fr; }
  .dist-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
  .dist-col .card { margin-bottom: 0; }
}
@media (max-width: 900px) {
  .kpi-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
  .page-wrapper { padding: 16px; }
  .kpi-grid  { grid-template-columns: 1fr 1fr; }
  .dist-col  { grid-template-columns: 1fr; }
  .bar-chart { gap: 6px; }
}
</style>
