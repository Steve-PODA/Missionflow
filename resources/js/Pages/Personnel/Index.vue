<template>
  <AppLayout>
    <div class="page-wrapper">

      <!-- EN-TÊTE -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Gestion du Personnel</h1>
          <p class="page-sub">
            <span class="dot dot-green"></span>{{ counts.available }} disponible{{ counts.available > 1 ? 's' : '' }}
            &nbsp;·&nbsp;
            <span class="dot dot-orange"></span>{{ counts.deployed }} déployé{{ counts.deployed > 1 ? 's' : '' }}
            &nbsp;·&nbsp;
            <span class="dot dot-gray"></span>{{ counts.unavailable }} indisponible{{ counts.unavailable > 1 ? 's' : '' }}
          </p>
        </div>
        <div class="search-box">
          <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input
            v-model="search"
            type="text"
            placeholder="Rechercher un agent..."
            class="search-input"
          />
          <button v-if="search" class="search-clear" @click="search = ''">✕</button>
        </div>
      </div>

      <!-- FILTRES -->
      <div class="filter-tabs">
        <button
          v-for="f in filters"
          :key="f.value"
          class="filter-tab"
          :class="{ active: activeFilter === f.value }"
          @click="activeFilter = f.value"
        >
          {{ f.label }}
          <span class="filter-count">{{ f.count }}</span>
        </button>
      </div>

      <!-- GRILLE AGENTS -->
      <div class="personnel-grid">
        <div
          v-for="agent in filteredPersonnel"
          :key="agent.id"
          class="agent-card"
          :class="'card-' + agent.computed_status"
        >
          <!-- AVATAR + STATUT -->
          <div class="agent-top">
            <div class="agent-avatar" :style="{ background: getColor(agent.name) }">
              {{ getInitials(agent.name) }}
            </div>
            <span class="status-badge" :class="'badge-' + agent.computed_status">
              {{ statusLabel(agent.computed_status) }}
            </span>
          </div>

          <!-- INFOS -->
          <div class="agent-info">
            <h3 class="agent-name">{{ agent.name }}</h3>
            <p class="agent-rank">{{ agent.role }}</p>
          </div>

          <!-- MISSION ACTIVE -->
          <div v-if="agent.active_mission" class="active-mission">
            <span class="mission-label">⚔️ En opération</span>
            <span class="mission-title">{{ agent.active_mission.title }}</span>
          </div>
          <div v-else class="no-mission">
            <span>Aucune opération en cours</span>
          </div>

          <!-- STATS -->
          <div class="agent-stats">
            <div class="stat">
              <span class="stat-value">{{ agent.missions_count }}</span>
              <span class="stat-label">missions</span>
            </div>
          </div>

          <!-- TOGGLE DISPONIBILITÉ (seulement si pas déployé) -->
          <div class="availability-control" v-if="agent.computed_status !== 'deployed'">
            <span class="control-label">Disponibilité :</span>
            <div class="control-btns">
              <button
                v-for="opt in availabilityOptions"
                :key="opt.value"
                class="ctrl-btn"
                :class="['ctrl-' + opt.value, { active: agent.availability === opt.value }]"
                @click="updateAvailability(agent, opt.value)"
                :disabled="saving === agent.id"
              >
                {{ opt.label }}
              </button>
            </div>
          </div>
          <div v-else class="availability-control">
            <span class="deployed-note">Retour auto à la fin de l'opération</span>
          </div>

        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'PersonnelIndex',
  components: { AppLayout },

  props: {
    personnel: { type: Array, default: () => [] },
  },

  data() {
    return {
      saving: null,
      search: '',
      activeFilter: 'all',
      availabilityOptions: [
        { value: 'available',   label: 'Disponible'    },
        { value: 'on_leave',    label: 'En congé'      },
        { value: 'unavailable', label: 'Indisponible'  },
      ],
    }
  },

  computed: {
    sortedPersonnel() {
      const order = { available: 0, deployed: 1, on_leave: 2, unavailable: 3 }
      return [...this.personnel].sort((a, b) =>
        (order[a.computed_status] ?? 9) - (order[b.computed_status] ?? 9)
      )
    },
    filters() {
      return [
        { value: 'all',         label: 'Tous',          count: this.personnel.length },
        { value: 'available',   label: 'Disponibles',   count: this.counts.available },
        { value: 'deployed',    label: 'En mission',    count: this.counts.deployed },
        { value: 'unavailable', label: 'Indisponibles', count: this.counts.unavailable },
      ]
    },
    filteredPersonnel() {
      let list = this.sortedPersonnel
      if (this.activeFilter !== 'all') {
        if (this.activeFilter === 'unavailable') {
          list = list.filter(p => p.computed_status === 'on_leave' || p.computed_status === 'unavailable')
        } else {
          list = list.filter(p => p.computed_status === this.activeFilter)
        }
      }
      if (this.search.trim()) {
        const q = this.search.toLowerCase()
        list = list.filter(p => p.name.toLowerCase().includes(q) || p.role?.toLowerCase().includes(q))
      }
      return list
    },
    counts() {
      return {
        available:   this.personnel.filter(p => p.computed_status === 'available').length,
        deployed:    this.personnel.filter(p => p.computed_status === 'deployed').length,
        unavailable: this.personnel.filter(p =>
          p.computed_status === 'on_leave' || p.computed_status === 'unavailable'
        ).length,
      }
    },
  },

  methods: {
    getInitials(name) {
      return name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() ?? '??'
    },
    getColor(name) {
      let hash = 0
      for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
      return `hsl(${Math.abs(hash) % 360}, 50%, 38%)`
    },
    statusLabel(status) {
      return { available: 'Disponible', deployed: 'Déployé', on_leave: 'En congé', unavailable: 'Indisponible' }[status] ?? status
    },
    updateAvailability(agent, value) {
      if (agent.availability === value) return
      this.saving = agent.id
      router.patch(route('personnel.availability', agent.id), { availability: value }, {
        preserveScroll: true,
        onFinish: () => { this.saving = null },
      })
    },
  },
}
</script>

<style scoped>
.page-wrapper { padding: 32px; max-width: 1400px; }

.page-header { margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.page-title  { font-size: 24px; font-weight: 700; color: #1a1f2e; margin: 0 0 8px; }
.page-sub    { font-size: 14px; color: #6b7280; display: flex; align-items: center; gap: 4px; }

.dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 2px; }

/* RECHERCHE */
.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: white;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  padding: 0 12px;
  height: 42px;
  min-width: 260px;
  transition: border-color 0.15s;
}
.search-box:focus-within { border-color: #4f6fee; }
.search-icon { width: 16px; height: 16px; color: #9ca3af; flex-shrink: 0; }
.search-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  color: #1a1f2e;
  background: transparent;
  font-family: inherit;
}
.search-input::placeholder { color: #9ca3af; }
.search-clear {
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  font-size: 12px;
  padding: 2px;
  line-height: 1;
  transition: color 0.15s;
}
.search-clear:hover { color: #374151; }
.dot-green  { background: #22c55e; }
.dot-orange { background: #f59e0b; }
.dot-gray   { background: #9ca3af; }

/* FILTRES */
.filter-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.filter-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  background: white;
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}
.filter-tab:hover { border-color: #4f6fee; color: #4f6fee; }
.filter-tab.active { background: #4f6fee; border-color: #4f6fee; color: white; }

.filter-count {
  background: rgba(0,0,0,0.1);
  border-radius: 20px;
  padding: 1px 7px;
  font-size: 11px;
  font-weight: 700;
}
.filter-tab.active .filter-count { background: rgba(255,255,255,0.25); }

/* GRILLE */
.personnel-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

/* CARTE AGENT */
.agent-card {
  background: white;
  border-radius: 16px;
  padding: 22px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 2px solid transparent;
  display: flex;
  flex-direction: column;
  gap: 14px;
  transition: box-shadow 0.2s;
}
.agent-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.1); }

.card-available   { border-color: #d1fae5; }
.card-deployed    { border-color: #fed7aa; }
.card-on_leave    { border-color: #e5e7eb; }
.card-unavailable { border-color: #fee2e2; }

/* TOP */
.agent-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.agent-avatar {
  width: 52px; height: 52px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 16px; font-weight: 700;
}

.status-badge {
  font-size: 11px; font-weight: 700;
  padding: 4px 10px; border-radius: 20px;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.badge-available   { background: #d1fae5; color: #059669; }
.badge-deployed    { background: #fed7aa; color: #d97706; }
.badge-on_leave    { background: #f3f4f6; color: #6b7280; }
.badge-unavailable { background: #fee2e2; color: #dc2626; }

/* INFOS */
.agent-name { font-size: 16px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.agent-rank { font-size: 12px; color: #6b7280; margin: 0; }

/* MISSION ACTIVE */
.active-mission {
  background: #fff7ed;
  border-radius: 8px;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.mission-label { font-size: 11px; font-weight: 700; color: #d97706; text-transform: uppercase; }
.mission-title { font-size: 13px; font-weight: 600; color: #1a1f2e; }

.no-mission {
  font-size: 12px;
  color: #9ca3af;
  background: #f9fafb;
  border-radius: 8px;
  padding: 8px 12px;
}

/* STATS */
.agent-stats { display: flex; gap: 16px; }
.stat { display: flex; flex-direction: column; }
.stat-value { font-size: 20px; font-weight: 700; color: #1a1f2e; line-height: 1; }
.stat-label { font-size: 11px; color: #9ca3af; }

/* TOGGLE */
.availability-control {
  border-top: 1px solid #f3f4f6;
  padding-top: 12px;
}
.control-label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}
.control-btns { display: flex; gap: 6px; }
.ctrl-btn {
  flex: 1;
  padding: 6px 4px;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  background: white;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.15s;
  font-family: inherit;
}
.ctrl-btn:hover:not(:disabled) { border-color: #4f6fee; color: #4f6fee; }
.ctrl-btn:disabled { opacity: 0.5; cursor: wait; }

.ctrl-available.active   { background: #d1fae5; border-color: #059669; color: #059669; }
.ctrl-on_leave.active    { background: #f3f4f6; border-color: #6b7280; color: #6b7280; }
.ctrl-unavailable.active { background: #fee2e2; border-color: #dc2626; color: #dc2626; }

.deployed-note {
  font-size: 11px;
  color: #9ca3af;
  font-style: italic;
}

@media (max-width: 640px) {
  .page-wrapper { padding: 16px; }
  .personnel-grid { grid-template-columns: 1fr; }
}
</style>
