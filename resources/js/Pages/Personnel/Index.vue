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

          <!-- MOTIF + DURÉE INDISPONIBILITÉ -->
          <div v-if="agent.computed_status === 'unavailable' && (agent.unavailability_reason || agent.unavailability_start_date)" class="unavailability-reason">
            <span class="reason-icon">🚫</span>
            <div class="reason-body">
              <span v-if="agent.unavailability_reason" class="reason-text">{{ agent.unavailability_reason }}</span>
              <span v-if="agent.unavailability_start_date && agent.unavailability_duration" class="reason-until">
                Jusqu'au <strong>{{ unavailabilityEndDate(agent) }}</strong>
              </span>
            </div>
          </div>

          <!-- CONGÉ EN COURS -->
          <div v-if="agent.computed_status === 'on_leave' && agent.leave_start_date" class="leave-info">
            <span class="leave-icon">🏖️</span>
            <span class="leave-text">Retour prévu le <strong>{{ leaveEndDate(agent) }}</strong></span>
          </div>

          <!-- CONGÉ EXPIRÉ — confirmation requise -->
          <div v-if="agent.computed_status === 'leave_expired'" class="leave-expired-banner">
            <div class="expired-header">
              <span class="expired-icon">⚠️</span>
              <span class="expired-text">Congé échu le <strong>{{ leaveEndDate(agent) }}</strong></span>
            </div>
            <button
              class="confirm-return-btn"
              @click="confirmReturn(agent)"
              :disabled="saving === agent.id"
            >
              {{ saving === agent.id ? 'Enregistrement…' : '✓ Confirmer le retour en service' }}
            </button>
          </div>

          <!-- TOGGLE DISPONIBILITÉ (seulement si pas déployé ni en attente de confirmation) -->
          <div class="availability-control" v-if="agent.computed_status !== 'deployed' && agent.computed_status !== 'leave_expired'">
            <span class="control-label">Disponibilité :</span>
            <div class="control-btns">
              <button
                v-for="opt in availabilityOptions"
                :key="opt.value"
                class="ctrl-btn"
                :class="['ctrl-' + opt.value, { active: agent.availability === opt.value }]"
                @click="handleAvailabilityClick(agent, opt.value)"
                :disabled="saving === agent.id"
              >
                {{ opt.label }}
              </button>
            </div>

            <!-- SAISIE DURÉE CONGÉ -->
            <div v-if="leaveDraft.agentId === agent.id" class="leave-form">
              <span class="leave-form-label">Durée du congé :</span>
              <div class="leave-form-row">
                <input
                  v-model.number="leaveDraft.duration"
                  type="number"
                  min="1"
                  max="365"
                  class="leave-input"
                  placeholder="Ex: 14"
                />
                <select v-model="leaveDraft.unit" class="leave-select">
                  <option value="days">Jours</option>
                  <option value="months">Mois</option>
                </select>
                <button class="leave-confirm" @click="confirmLeave(agent)" :disabled="!leaveDraft.duration">
                  Confirmer
                </button>
                <button class="leave-cancel" @click="leaveDraft.agentId = null">✕</button>
              </div>
            </div>

            <!-- SAISIE MOTIF + DURÉE INDISPONIBILITÉ -->
            <div v-if="unavailabilityDraft.agentId === agent.id" class="leave-form">
              <span class="leave-form-label">Indisponibilité :</span>
              <div class="reason-form-row">
                <input
                  v-model="unavailabilityDraft.reason"
                  type="text"
                  maxlength="255"
                  class="reason-input"
                  placeholder="Motif (ex: formation, santé…)"
                />
              </div>
              <div class="leave-form-row" style="margin-top: 6px;">
                <input
                  v-model.number="unavailabilityDraft.duration"
                  type="number"
                  min="1"
                  max="365"
                  class="leave-input"
                  placeholder="Durée"
                />
                <select v-model="unavailabilityDraft.unit" class="leave-select">
                  <option value="days">Jours</option>
                  <option value="months">Mois</option>
                </select>
                <button class="leave-confirm" @click="confirmUnavailability(agent)" :disabled="!unavailabilityDraft.duration">
                  Confirmer
                </button>
                <button class="leave-cancel" @click="unavailabilityDraft.agentId = null">✕</button>
              </div>
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
      leaveDraft: { agentId: null, duration: '', unit: 'days' },
      unavailabilityDraft: { agentId: null, reason: '', duration: '', unit: 'days' },
    }
  },

  computed: {
    sortedPersonnel() {
      const order = { available: 0, deployed: 1, leave_expired: 2, on_leave: 3, unavailable: 4 }
      return [...this.personnel].sort((a, b) =>
        (order[a.computed_status] ?? 9) - (order[b.computed_status] ?? 9)
      )
    },
    filters() {
      return [
        { value: 'all',          label: 'Tous',              count: this.personnel.length },
        { value: 'available',    label: 'Disponibles',       count: this.counts.available },
        { value: 'deployed',     label: 'En mission',        count: this.counts.deployed },
        { value: 'unavailable',  label: 'Indisponibles',     count: this.counts.unavailable },
        { value: 'leave_expired',label: 'Retour à confirmer',count: this.counts.leave_expired },
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
        available:     this.personnel.filter(p => p.computed_status === 'available').length,
        deployed:      this.personnel.filter(p => p.computed_status === 'deployed').length,
        unavailable:   this.personnel.filter(p =>
          p.computed_status === 'on_leave' || p.computed_status === 'unavailable'
        ).length,
        leave_expired: this.personnel.filter(p => p.computed_status === 'leave_expired').length,
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
      return {
        available:    'Disponible',
        deployed:     'Déployé',
        on_leave:     'En congé',
        unavailable:  'Indisponible',
        leave_expired:'Retour à confirmer',
      }[status] ?? status
    },
    handleAvailabilityClick(agent, value) {
      const noActiveDraft = this.leaveDraft.agentId !== agent.id && this.unavailabilityDraft.agentId !== agent.id
      if (agent.availability === value && noActiveDraft) return

      if (value === 'on_leave') {
        this.leaveDraft = { agentId: agent.id, duration: '', unit: 'days' }
        this.unavailabilityDraft.agentId = null
        return
      }
      if (value === 'unavailable') {
        this.unavailabilityDraft = { agentId: agent.id, reason: '', duration: '', unit: 'days' }
        this.leaveDraft.agentId = null
        return
      }

      this.leaveDraft.agentId = null
      this.unavailabilityDraft.agentId = null
      this.saving = agent.id
      router.patch(route('personnel.availability', agent.id), { availability: value }, {
        preserveScroll: true,
        onFinish: () => { this.saving = null },
      })
    },
    confirmUnavailability(agent) {
      if (!this.unavailabilityDraft.duration) return
      this.saving = agent.id
      const draft = { ...this.unavailabilityDraft }
      this.unavailabilityDraft.agentId = null
      router.patch(route('personnel.availability', agent.id), {
        availability:             'unavailable',
        unavailability_reason:    draft.reason || null,
        unavailability_duration:  draft.duration,
        unavailability_unit:      draft.unit,
      }, {
        preserveScroll: true,
        onFinish: () => { this.saving = null },
      })
    },
    unavailabilityEndDate(agent) {
      if (!agent.unavailability_start_date || !agent.unavailability_duration || !agent.unavailability_unit) return null
      const d = new Date(agent.unavailability_start_date)
      if (agent.unavailability_unit === 'months') {
        d.setMonth(d.getMonth() + agent.unavailability_duration)
      } else {
        d.setDate(d.getDate() + agent.unavailability_duration)
      }
      return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
    },
    confirmLeave(agent) {
      if (!this.leaveDraft.duration) return
      this.saving = agent.id
      this.leaveDraft.agentId = null
      router.patch(route('personnel.availability', agent.id), {
        availability:   'on_leave',
        leave_duration: this.leaveDraft.duration,
        leave_unit:     this.leaveDraft.unit,
      }, {
        preserveScroll: true,
        onFinish: () => { this.saving = null },
      })
    },
    confirmReturn(agent) {
      this.saving = agent.id
      router.patch(route('personnel.confirmReturn', agent.id), {}, {
        preserveScroll: true,
        onFinish: () => { this.saving = null },
      })
    },
    leaveEndDate(agent) {
      if (!agent.leave_start_date || !agent.leave_duration || !agent.leave_unit) return null
      const start = new Date(agent.leave_start_date)
      if (agent.leave_unit === 'months') {
        start.setMonth(start.getMonth() + agent.leave_duration)
      } else {
        start.setDate(start.getDate() + agent.leave_duration)
      }
      return start.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
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

.card-available     { border-color: #d1fae5; }
.card-deployed      { border-color: #fed7aa; }
.card-on_leave      { border-color: #e5e7eb; }
.card-unavailable   { border-color: #fee2e2; }
.card-leave_expired { border-color: #fde68a; }

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
.badge-available     { background: #d1fae5; color: #059669; }
.badge-deployed      { background: #fed7aa; color: #d97706; }
.badge-on_leave      { background: #f3f4f6; color: #6b7280; }
.badge-unavailable   { background: #fee2e2; color: #dc2626; }
.badge-leave_expired { background: #fef3c7; color: #b45309; }

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

/* CONGÉ EXPIRÉ */
.leave-expired-banner {
  background: #fffbeb;
  border: 1.5px solid #fde68a;
  border-radius: 10px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.expired-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #92400e;
}
.expired-icon { font-size: 15px; }
.confirm-return-btn {
  width: 100%;
  padding: 9px 12px;
  background: #16a34a;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  transition: background 0.15s;
  letter-spacing: 0.2px;
}
.confirm-return-btn:hover:not(:disabled) { background: #15803d; }
.confirm-return-btn:disabled { opacity: 0.6; cursor: wait; }

/* MOTIF INDISPONIBILITÉ */
.unavailability-reason {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background: #fff1f2;
  border: 1px solid #fecdd3;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13px;
  color: #be123c;
}
.reason-icon { font-size: 14px; flex-shrink: 0; margin-top: 2px; }
.reason-body { display: flex; flex-direction: column; gap: 2px; }
.reason-text { line-height: 1.4; word-break: break-word; }
.reason-until { font-size: 12px; opacity: 0.85; }

.reason-form-row {
  display: flex;
  gap: 6px;
  align-items: center;
  flex-wrap: wrap;
}
.reason-input {
  flex: 1;
  min-width: 0;
  padding: 6px 8px;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
  font-family: inherit;
  color: #1a1f2e;
  outline: none;
  transition: border-color 0.15s;
}
.reason-input:focus { border-color: #4f6fee; }

/* CONGÉ EN COURS */
.leave-info {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13px;
  color: #15803d;
}
.leave-icon { font-size: 15px; }
.leave-text { line-height: 1.3; }

/* FORMULAIRE DURÉE CONGÉ */
.leave-form {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px dashed #e5e7eb;
}
.leave-form-label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}
.leave-form-row {
  display: flex;
  gap: 6px;
  align-items: center;
  flex-wrap: wrap;
}
.leave-input {
  width: 72px;
  padding: 6px 8px;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
  font-family: inherit;
  color: #1a1f2e;
  outline: none;
  transition: border-color 0.15s;
}
.leave-input:focus { border-color: #4f6fee; }
.leave-select {
  padding: 6px 8px;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
  font-family: inherit;
  color: #1a1f2e;
  background: white;
  outline: none;
  cursor: pointer;
  transition: border-color 0.15s;
}
.leave-select:focus { border-color: #4f6fee; }
.leave-confirm {
  padding: 6px 12px;
  background: #4f6fee;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: background 0.15s;
}
.leave-confirm:hover:not(:disabled) { background: #3a56d4; }
.leave-confirm:disabled { opacity: 0.5; cursor: not-allowed; }
.leave-cancel {
  padding: 6px 8px;
  background: none;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  font-size: 12px;
  color: #9ca3af;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s;
}
.leave-cancel:hover { border-color: #dc2626; color: #dc2626; }

@media (max-width: 640px) {
  .page-wrapper { padding: 16px; }
  .personnel-grid { grid-template-columns: 1fr; }
}
</style>
