<template>
  <AppLayout>
    <div class="dashboard">

      <!-- PAGE HEADER -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Centre Opérationnel</h1>
          <p class="page-sub">Agent {{ $page.props.auth.user.name }} — {{ $page.props.auth.user.role || 'Personnel' }}</p>
        </div>
        <button v-if="$page.props.auth.can.create_missions" class="btn-primary" @click="creatorOpen = true">
          + Déployer une opération
        </button>
      </div>

      <div v-if="!$page.props.auth.can.create_missions" class="technicien-banner">
        🪖 Vue personnelle — vous ne voyez que les opérations auxquelles vous êtes affecté.
      </div>

      <!-- ALERTES MISSIONS EN RETARD -->
      <div v-if="overdue.length" class="overdue-panel">
        <div class="overdue-header">
          <span class="overdue-icon">⚠️</span>
          <span class="overdue-title">{{ overdue.length }} opération{{ overdue.length > 1 ? 's' : '' }} en retard</span>
          <button class="overdue-toggle" @click="overdueOpen = !overdueOpen">
            {{ overdueOpen ? '▲ Réduire' : '▼ Voir' }}
          </button>
        </div>
        <div v-if="overdueOpen" class="overdue-list">
          <div class="overdue-item" v-for="m in overdue" :key="m.id">
            <div class="overdue-info">
              <span class="overdue-name">{{ m.title }}</span>
              <span class="overdue-meta">{{ m.company }} · Prévu le {{ formatDate(m.date) }}</span>
            </div>
            <div class="overdue-agents">{{ m.users?.map(u => u.name).join(', ') || 'Non assigné' }}</div>
            <div class="overdue-actions">
              <button class="btn-deploy" @click="deployMission(m.id)">▶ Déployer</button>
              <button class="btn-abandon" @click="abandonMission(m.id)">✕ Abandonner</button>
            </div>
          </div>
        </div>
      </div>


      <!-- STATS -->
      <section class="stats">
        <div class="stat-card" v-for="s in statsCards" :key="s.label">
          <div class="stat-header">
            <span>{{ s.label }}</span>
            <div class="stat-icon">{{ s.icon }}</div>
          </div>
          <div class="stat-value">{{ s.value }}</div>
        </div>
      </section>

      <!-- MAIN GRID -->
      <section class="main-grid">
        <!-- PROCHAINE MISSION -->
        <div
          class="mission-next"
          :class="{ 'is-clickable': !!nextMission }"
          @click="openNextMissionDetail"
        >
          <div class="mission-header">
            <span class="section-title">🎯 Prochaine opération</span>
            <div class="meta-badges">
              <span v-if="currentNextMission?.priorityLabel" class="priority-badge" :class="currentNextMission.priorityClass">
                {{ currentNextMission.priorityLabel }}
              </span>
              <span v-if="timeTilNext" class="badge">{{ timeTilNext }}</span>
            </div>
          </div>

          <template v-if="currentNextMission">
            <h2>{{ currentNextMission.title }}</h2>
            <p class="company">{{ currentNextMission.company }}</p>
            <div class="mission-info">
              <div>📅<strong>{{ currentNextMission.date }}</strong></div>
              <div>🕘 <strong>{{ currentNextMission.start }} – {{ currentNextMission.end }}</strong></div>
              <div>📍 <strong>{{ currentNextMission.location }}</strong></div>
              <div>🪖 <strong>{{ currentNextMission.assignedTo }}</strong></div>
            </div>
          </template>
          <p v-else class="company" style="margin-top:16px;">Aucune opération planifiée.</p>

          <button class="btn-details" @click.stop="goToMissions">Voir toutes les opérations →</button>
        </div>

        <!-- ÉQUIPE -->
        <div class="team-panel">
          <h3>🪖 Unité</h3>
          <div class="team-list">
            <div class="team-card" v-for="m in team" :key="m.id">
              <div class="avatar" :style="{ background: getAvatarColor(m.name) }">{{ getInitials(m.name) }}</div>
              <div class="team-info">
                <strong>{{ m.name }}</strong>
                <small>{{ m.role }}</small>
                <span class="status" :class="statusClass(m.computed_status)">
                  {{ statusLabel(m.computed_status) }}
                </span>
                <span v-if="m.active_mission" class="active-op">⚔️ {{ m.active_mission.title }}</span>
              </div>
              <div class="missions-count">
                {{ m.missions_count || 0 }}
                <small>missions</small>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CALENDRIER -->
      <div class="calendar-section">
        <WeeklyCalendar :events="missions" @open-event="detailMission = $event" />
      </div>

      <!-- LISTE MISSIONS -->
      <div class="list-section">
        <MissionList :missions="missions" :all-team-members="team" @detail="detailMission = $event" @edit="openEditor" />
        <MissionDetail v-if="detailMission" :mission="detailMission" @close="detailMission = null" @edit="openEditor" />
      </div>

      <MissionCreator v-if="creatorOpen" :team-members="team" @close="creatorOpen = false" />


    </div>
  </AppLayout>
</template>

<script>
import AppLayout      from '@/Layouts/AppLayout.vue'
import WeeklyCalendar from '@/Components/WeeklyCalendar.vue'
import MissionList    from '@/Components/MissionList.vue'
import MissionDetail  from '@/Components/MissionDetail.vue'
import MissionCreator from '@/Components/MissionCreator.vue'
import { router } from '@inertiajs/vue3'

export default {
  name: 'Home',

  components: { AppLayout, WeeklyCalendar, MissionList, MissionDetail, MissionCreator },

  props: {
    missions:    { type: Array,  default: () => [] },
    team:        { type: Array,  default: () => [] },
    stats:       { type: Object, default: () => ({ total: 0, in_progress: 0, completed: 0, successRate: '0%' }) },
    nextMission: { type: Object, default: null },
    overdue:     { type: Array,  default: () => [] },
  },

  data() {
    return {
      detailMission:  null,
      creatorOpen:    false,
      overdueOpen:    true,
      now:            new Date(),
      countdownTimer: null,
    }
  },

  mounted() {
    this.countdownTimer = setInterval(() => {
      this.now = new Date()
    }, 1000)
  },

  beforeUnmount() {
    if (this.countdownTimer) {
      clearInterval(this.countdownTimer)
      this.countdownTimer = null
    }
  },

  computed: {
    statsCards() {
      return [
        { label: 'Opérations totales',    value: this.stats.total,       icon: '🎯' },
        { label: 'En opération',          value: this.stats.in_progress, icon: '⚔️' },
        { label: 'Accomplies',            value: this.stats.completed,   icon: '✅' },
        { label: 'Taux d\'accomplissement', value: this.stats.successRate, icon: '📊' },
      ]
    },

    currentNextMission() {
      if (!this.nextMission) return null
      const startTime  = (this.nextMission.start_time || '').substring(0, 5)
      const duration   = this.nextMission.duration || 0
      const [h, m]     = startTime.split(':').map(Number)
      const endMinutes = h * 60 + m + duration * 60
      const endTime    = String(Math.floor(endMinutes / 60)).padStart(2, '0') + ':' + String(endMinutes % 60).padStart(2, '0')
      const priorityMeta = this.missionPriorityMeta(this.nextMission.priority)
      return {
        id:         this.nextMission.id,
        title:      this.nextMission.title,
        company:    this.nextMission.company,
        date:       this.formatDate(this.nextMission.date),
        start:      startTime,
        end:        endTime,
        location:   this.nextMission.location,
        assignedTo: this.nextMission.users?.map(u => u.name).join(', ') || 'Non assigné',
        priorityLabel: priorityMeta.label,
        priorityClass: priorityMeta.className,
      }
    },

    timeTilNext() {
      if (!this.nextMission) return null
      const dt   = new Date(`${this.nextMission.date}T${this.nextMission.start_time}`)
      const diff = dt - this.now
      if (diff < 0) return 'En cours'
      const totalSeconds = Math.floor(diff / 1000)
      const h = Math.floor(totalSeconds / 3600)
      const m = Math.floor((totalSeconds % 3600) / 60)
      const s = totalSeconds % 60
      if (h > 0) return `Dans ${h}h ${String(m).padStart(2, '0')}min`
      if (m > 0) return `Dans ${m}min ${String(s).padStart(2, '0')}s`
      if (s > 0) return `Dans ${s}s`
      return 'Maintenant'
    },
  },

  methods: {
    missionPriorityMeta(priority) {
      const normalized = String(priority || '').toLowerCase()
      if (normalized === 'urgent') return { label: 'Urgente', className: 'priority-urgent' }
      if (normalized === 'high') return { label: 'Haute', className: 'priority-high' }
      if (normalized === 'medium') return { label: 'Moyenne', className: 'priority-medium' }
      if (normalized === 'low') return { label: 'Basse', className: 'priority-low' }
      return { label: 'Standard', className: 'priority-medium' }
    },
    openNextMissionDetail() {
      if (!this.nextMission) return
      this.detailMission = this.nextMission
      this.editingMission = null
    },
    goToMissions() { router.visit(route('missions.index')) },
    formatDate(date) {
      if (!date) return ''
      return new Date(date + 'T00:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })
    },
    deployMission(id) {
      router.patch(route('missions.updateStatus', id), { status: 'in_progress' }, { preserveScroll: true })
    },
    abandonMission(id) {
      router.patch(route('missions.updateStatus', id), { status: 'cancelled' }, { preserveScroll: true })
    },
    openEditor(mission) {
      router.visit(route('missions.edit', mission.id))
    },
    getInitials(name) {
      if (!name) return '??'
      return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
    },
    getAvatarColor(name) {
      let hash = 0
      for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
      return `hsl(${Math.abs(hash) % 360}, 50%, 38%)`
    },
    statusLabel(status) {
      return { available: 'Disponible', deployed: 'Déployé', on_leave: 'En congé', unavailable: 'Indisponible' }[status] ?? 'Disponible'
    },
    statusClass(status) {
      return { available: 'free', deployed: 'busy', on_leave: 'leave', unavailable: 'off' }[status] ?? 'free'
    },
  },
}
</script>

<style scoped>
.dashboard {
  padding: 32px;
  max-width: 1400px;
}

/* ALERTES EN RETARD */
.overdue-panel {
  background: #fff7ed;
  border: 1.5px solid #fed7aa;
  border-radius: 12px;
  margin-bottom: 24px;
  overflow: hidden;
}
.overdue-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
}
.overdue-icon  { font-size: 16px; }
.overdue-title { font-size: 14px; font-weight: 700; color: #c2410c; flex: 1; }
.overdue-toggle {
  background: none; border: none; cursor: pointer;
  font-size: 12px; font-weight: 600; color: #ea580c;
  padding: 4px 8px; border-radius: 6px; transition: background 0.15s;
}
.overdue-toggle:hover { background: #fed7aa; }
.overdue-list { border-top: 1px solid #fed7aa; }
.overdue-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 16px;
  border-bottom: 1px solid #ffedd5;
  flex-wrap: wrap;
}
.overdue-item:last-child { border-bottom: none; }
.overdue-info   { flex: 1; min-width: 180px; }
.overdue-name   { font-size: 14px; font-weight: 600; color: #1a1f2e; display: block; }
.overdue-meta   { font-size: 12px; color: #9a3412; margin-top: 2px; display: block; }
.overdue-agents { font-size: 12px; color: #6b7280; flex: 1; min-width: 140px; }
.overdue-actions { display: flex; gap: 8px; }
.btn-deploy, .btn-abandon {
  padding: 6px 12px; border: none; border-radius: 6px;
  font-size: 12px; font-weight: 600; cursor: pointer;
  transition: opacity 0.15s; font-family: inherit;
}
.btn-deploy  { background: #eef2ff; color: #4f6fee; }
.btn-abandon { background: #fee2e2; color: #dc2626; }
.btn-deploy:hover, .btn-abandon:hover { opacity: 0.8; }

/* BANNIÈRE TECHNICIEN */
.technicien-banner {
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  border-radius: 10px;
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 500;
  color: #4338ca;
  margin-bottom: 20px;
}

/* PAGE HEADER */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}
.page-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a1f2e;
  margin: 0 0 4px;
}
.page-sub {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}
.btn-primary {
  background: #4f6fee;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}
.btn-primary:hover { background: #3d5cdb; }

/* STATS */
.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 28px;
}
.stat-card {
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
}
.stat-icon { font-size: 20px; }
.stat-value {
  font-size: 30px;
  font-weight: 700;
  color: #1a1f2e;
}

/* MAIN GRID */
.main-grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 24px;
  margin-bottom: 28px;
}

/* NEXT MISSION */
.mission-next {
  background: white;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 2px 20px rgba(79,111,238,.12);
}
.mission-next.is-clickable {
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.mission-next.is-clickable:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(79,111,238,.14);
}
.mission-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.meta-badges {
  display: flex;
  align-items: center;
  gap: 8px;
}
.section-title { font-size: 13px; font-weight: 600; color: #6b7280; }
.badge {
  background: #eef2ff;
  color: #4f6fee;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.priority-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
}
.priority-urgent { background: #fee2e2; color: #b91c1c; }
.priority-high { background: #ffedd5; color: #c2410c; }
.priority-medium { background: #fef3c7; color: #b45309; }
.priority-low { background: #dcfce7; color: #166534; }
.mission-next h2 { font-size: 20px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.company { font-size: 13px; color: #6b7280; margin: 0 0 16px; }
.mission-info { display: flex; flex-direction: column; gap: 8px; font-size: 14px; color: #374151; }
.btn-details {
  width: 100%;
  margin-top: 20px;
  padding: 12px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(90deg, #4f6fee, #6b8cff);
  color: white;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: opacity 0.15s;
}
.btn-details:hover { opacity: 0.9; }

/* TEAM */
.team-panel {
  background: white;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
.team-panel h3 { font-size: 14px; font-weight: 600; color: #374151; margin: 0 0 16px; }
.team-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 260px;
  overflow-y: auto;
}
.team-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  border-radius: 12px;
  background: #f9fafb;
}
.avatar {
  width: 40px; height: 40px;
  background: #4f6fee;
  color: white;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; flex-shrink: 0;
}
.team-info { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.team-info strong { font-size: 13px; color: #1a1f2e; }
.team-info small  { font-size: 11px; color: #9ca3af; }
.status {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  width: fit-content;
}
.busy  { background: #fff4e5; color: #f59e0b; }
.free  { background: #e8f8ef; color: #22c55e; }
.leave { background: #f3f4f6; color: #6b7280; }
.off   { background: #fee2e2; color: #dc2626; }
.active-op {
  display: block;
  font-size: 11px;
  color: #d97706;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.missions-count { font-size: 18px; font-weight: 700; color: #1a1f2e; text-align: right; }
.missions-count small { display: block; font-size: 10px; color: #9ca3af; font-weight: 400; }

/* SECTIONS */
.calendar-section { margin-bottom: 28px; }
.list-section     { margin-bottom: 32px; }

/* RESPONSIVE */
@media (max-width: 1024px) {
  .stats { grid-template-columns: repeat(2, 1fr); }
  .main-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .dashboard { padding: 16px; }
  .stats { grid-template-columns: 1fr 1fr; }
  .page-header { flex-direction: column; gap: 16px; align-items: stretch; }
}
</style>
