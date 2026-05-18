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
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
          <div class="search-box">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input v-model="search" type="text" placeholder="Rechercher un agent..." class="search-input" />
            <button v-if="search" class="search-clear" @click="search = ''">✕</button>
          </div>
          <button v-if="$page.props.auth.can.manage_personnel" class="btn-add-personnel" @click="openCreate">+ Nouveau membre</button>
        </div>
      </div>

      <!-- FILTRES ET TRI -->
      <div class="filters-row" style="margin-bottom: 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; background: white; padding: 16px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06);">
        <div style="display: flex; gap: 8px; flex: 1; flex-wrap: wrap; align-items: center;">
          <select v-model="filter.peloton_id" class="form-input" style="padding: 6px 12px; font-size: 13px; max-width: 180px; width: auto; border: 1.5px solid #e5e7eb; border-radius: 8px; font-family: inherit; color: #1a1f2e; background: white;" @change="filter.groupe_id = null; filter.equipe_id = null">
            <option :value="null">Tous les pelotons</option>
            <option v-for="p in pelotons" :key="p.id" :value="p.id">{{ p.nom }}</option>
          </select>
          <select v-model="filter.groupe_id" class="form-input" style="padding: 6px 12px; font-size: 13px; max-width: 180px; width: auto; border: 1.5px solid #e5e7eb; border-radius: 8px; font-family: inherit; color: #1a1f2e; background: white;" :disabled="!filter.peloton_id" @change="filter.equipe_id = null">
            <option :value="null">Tous les groupes</option>
            <option v-for="g in availableFilterGroupes" :key="g.id" :value="g.id">{{ g.nom }}</option>
          </select>
          <select v-model="filter.equipe_id" class="form-input" style="padding: 6px 12px; font-size: 13px; max-width: 180px; width: auto; border: 1.5px solid #e5e7eb; border-radius: 8px; font-family: inherit; color: #1a1f2e; background: white;" :disabled="!filter.groupe_id">
            <option :value="null">Toutes les équipes</option>
            <option v-for="e in availableFilterEquipes" :key="e.id" :value="e.id">{{ e.nom }}</option>
          </select>
          <button v-if="filter.peloton_id || filter.groupe_id || filter.equipe_id" @click="resetFilters" class="btn-cancel" style="padding: 6px 12px; font-size: 12px; border-radius: 8px; margin: 0; background: #f3f4f6; border: none; cursor: pointer; color: #6b7280; font-weight: 600; font-family: inherit;">✕ Réinitialiser</button>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 13px; font-weight: 600; color: #64748b; white-space: nowrap;">Trier par :</span>
          <select v-model="sortBy" class="form-input" style="padding: 6px 12px; font-size: 13px; width: 180px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-family: inherit; color: #1a1f2e; background: white;">
            <option value="status">Statut opérationnel</option>
            <option value="name">Nom (A-Z)</option>
            <option value="affectation">Structure (Peloton > Groupe)</option>
            <option value="role">Grade / Rôle</option>
            <option value="missions">Missions effectuées</option>
          </select>
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
          :class="['card-' + agent.computed_status, { 'card-clickable': $page.props.auth.can.manage_personnel }]"
          @click="$page.props.auth.can.manage_personnel && openEdit(agent)"
        >
          <!-- AVATAR + STATUT -->
          <div class="agent-top">
            <div class="agent-avatar" :style="agent.avatar ? {} : { background: getColor(agent.name) }">
              <img v-if="agent.avatar" :src="'/storage/' + agent.avatar" :alt="agent.name" class="avatar-img" />
              <template v-else>{{ getInitials(agent.name) }}</template>
            </div>
            <span class="status-badge" :class="'badge-' + agent.computed_status">
              {{ statusLabel(agent.computed_status) }}
            </span>
          </div>

          <!-- INFOS -->
          <div class="agent-info">
            <h3 class="agent-name">{{ agent.name }}</h3>
            <p class="agent-rank">
              <span v-if="agent.grade" class="agent-grade">{{ agent.grade }}</span>
              <span v-if="agent.grade && agent.fonction"> · </span>
              <span v-if="agent.fonction">{{ agent.fonction }}</span>
              <span v-if="agent.numero_incorporation" class="agent-incorp"> · N° {{ agent.numero_incorporation }}</span>
            </p>
            <div class="agent-unit" v-if="agent.peloton || agent.groupe || agent.equipe">
              <span class="unit-badge peloton" v-if="agent.peloton">{{ agent.peloton.nom }}</span>
              <span class="unit-badge groupe" v-if="agent.groupe">{{ agent.groupe.nom }}</span>
              <span class="unit-badge equipe" v-if="agent.equipe">{{ agent.equipe.nom }}</span>
            </div>
            <div class="agent-unit" v-else>
              <span class="unit-badge none">Non assigné</span>
            </div>
          </div>

          <!-- MISSION ACTIVE -->
          <div v-if="agent.active_mission" class="active-mission">
            <span class="mission-label">⚔️ En opération</span>
            <span class="mission-title">{{ agent.active_mission.title }}</span>
          </div>
          <div v-else class="no-mission">
            <span>Aucune opération en cours</span>
          </div>

          <!-- STATS + BADGE COMPTE -->
          <div class="agent-stats">
            <div class="stat">
              <span class="stat-value">{{ agent.missions_count }}</span>
              <span class="stat-label">missions</span>
            </div>
            <span v-if="agent.has_account" class="account-badge">🔑 Compte app</span>
          </div>

          <!-- MOTIF INDISPONIBILITÉ -->
          <div v-if="agent.computed_status === 'unavailable' && agent.unavailability_reason" class="unavailability-reason">
            <span class="reason-icon">🚫</span>
            <div class="reason-body">
              <span class="reason-text">{{ agent.unavailability_reason }}</span>
            </div>
          </div>

          <!-- DATE DE RETOUR INDISPONIBILITÉ -->
          <div v-if="agent.computed_status === 'unavailable' && unavailabilityEndDate(agent)" class="leave-info">
            <span class="leave-icon">📅</span>
            <span class="leave-text">Retour prévu le <strong>{{ unavailabilityEndDate(agent) }}</strong></span>
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
              @click.stop="confirmReturn(agent)"
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
                @click.stop="handleAvailabilityClick(agent, opt.value)"
                :disabled="saving === agent.id"
              >
                {{ opt.label }}
              </button>
            </div>

            <!-- SAISIE DURÉE CONGÉ -->
            <div v-if="leaveDraft.agentId === agent.id" class="leave-form" @click.stop>
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
                <button class="leave-confirm" @click.stop="confirmLeave(agent)" :disabled="!leaveDraft.duration">
                  Confirmer
                </button>
                <button class="leave-cancel" @click.stop="leaveDraft.agentId = null">✕</button>
              </div>
            </div>

            <!-- SAISIE MOTIF + DURÉE INDISPONIBILITÉ -->
            <div v-if="unavailabilityDraft.agentId === agent.id" class="leave-form" @click.stop>
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
                <button class="leave-confirm" @click.stop="confirmUnavailability(agent)" :disabled="!unavailabilityDraft.duration">
                  Confirmer
                </button>
                <button class="leave-cancel" @click.stop="unavailabilityDraft.agentId = null">✕</button>
              </div>
            </div>
          </div>
          <div v-else class="availability-control">
            <span class="deployed-note">Retour auto à la fin de l'opération</span>
          </div>


        </div>
      </div>

    </div>

    <!-- MODAL CRÉER / MODIFIER -->
    <div v-if="modal.open" class="modal-overlay" @click.self="closeModal">
      <div class="modal-box">
        <h2 class="modal-title">{{ modal.mode === 'create' ? 'Nouveau membre du personnel' : 'Modifier le membre' }}</h2>

        <div class="form-group">
          <label class="form-label">Nom complet *</label>
          <input v-model="form.name" type="text" class="form-input" placeholder="Prénom Nom" />
          <span v-if="errors.name" class="form-error">{{ errors.name }}</span>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">N° Incorporation</label>
            <input v-model="form.numero_incorporation" type="text" class="form-input" placeholder="Ex: AG-042" />
            <span v-if="errors.numero_incorporation" class="form-error">{{ errors.numero_incorporation }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Grade</label>
            <input v-model="form.grade" type="text" class="form-input" placeholder="Ex: Adjudant-chef" />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Fonction</label>
          <input v-model="form.fonction" type="text" class="form-input" placeholder="Ex: Chef de groupe" />
        </div>

        <div class="form-group">
          <label class="form-label">Téléphone WhatsApp</label>
          <input v-model="form.phone_number" type="tel" class="form-input" placeholder="+226 XX XX XX XX" />
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Peloton</label>
            <select v-model="form.peloton_id" class="form-input" @change="form.groupe_id = null; form.equipe_id = null">
              <option :value="null">-- Aucun --</option>
              <option v-for="p in pelotons" :key="p.id" :value="p.id">{{ p.nom }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Groupe</label>
            <select v-model="form.groupe_id" class="form-input" :disabled="!form.peloton_id" @change="form.equipe_id = null">
              <option :value="null">-- Aucun --</option>
              <option v-for="g in availableFormGroupes" :key="g.id" :value="g.id">{{ g.nom }}</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Équipe</label>
          <select v-model="form.equipe_id" class="form-input" :disabled="!form.groupe_id">
            <option :value="null">-- Aucune --</option>
            <option v-for="e in availableFormEquipes" :key="e.id" :value="e.id">{{ e.nom }}</option>
          </select>
        </div>

        <div class="modal-footer">
          <button
            v-if="modal.mode === 'edit'"
            class="btn-delete-modal"
            @click="confirmDelete(modal.agent)"
            :disabled="saving === 'modal'"
            title="Supprimer ce membre"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          </button>
          <div style="display:flex; gap:10px; margin-left:auto;">
            <button class="btn-secondary" @click="closeModal">Annuler</button>
            <button class="btn-primary-modal" @click="submitForm" :disabled="saving === 'modal'">
              {{ saving === 'modal' ? 'Enregistrement…' : (modal.mode === 'create' ? 'Créer' : 'Enregistrer') }}
            </button>
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
    pelotons:  { type: Array, default: () => [] },
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
      filter: {
        peloton_id: null,
        groupe_id:  null,
        equipe_id:  null,
      },
      sortBy: 'status',
      modal: { open: false, mode: 'create', agent: null },
      form: { name: '', numero_incorporation: '', grade: '', fonction: '', phone_number: '', peloton_id: null, groupe_id: null, equipe_id: null },
      errors: {},
    }
  },

  computed: {
    sortedPersonnel() {
      const result = [...this.personnel]
      result.sort((a, b) => {
        if (this.sortBy === 'status') {
          const order = { available: 0, deployed: 1, leave_expired: 2, on_leave: 3, unavailable: 4 }
          return (order[a.computed_status] ?? 9) - (order[b.computed_status] ?? 9)
        }
        if (this.sortBy === 'name') {
          return a.name.localeCompare(b.name)
        }
        if (this.sortBy === 'role') {
          return (a.grade || '').localeCompare(b.grade || '')
        }
        if (this.sortBy === 'missions') {
          return b.missions_count - a.missions_count
        }
        if (this.sortBy === 'affectation') {
          const pelA = a.peloton?.nom ?? ''
          const pelB = b.peloton?.nom ?? ''
          if (pelA !== pelB) return pelA.localeCompare(pelB)

          const grpA = a.groupe?.nom ?? ''
          const grpB = b.groupe?.nom ?? ''
          if (grpA !== grpB) return grpA.localeCompare(grpB)

          const eqA = a.equipe?.nom ?? ''
          const eqB = b.equipe?.nom ?? ''
          return eqA.localeCompare(eqB)
        }
        return 0
      })
      return result
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
        list = list.filter(p => p.name.toLowerCase().includes(q) || p.grade?.toLowerCase().includes(q) || p.fonction?.toLowerCase().includes(q) || p.numero_incorporation?.toLowerCase().includes(q))
      }

      if (this.filter.peloton_id && this.filter.peloton_id !== 'null') {
        list = list.filter(p => {
          const id = p.peloton_id ?? p.peloton?.id
          return id && String(id) === String(this.filter.peloton_id)
        })
      }
      if (this.filter.groupe_id && this.filter.groupe_id !== 'null') {
        list = list.filter(p => {
          const id = p.groupe_id ?? p.groupe?.id
          return id && String(id) === String(this.filter.groupe_id)
        })
      }
      if (this.filter.equipe_id && this.filter.equipe_id !== 'null') {
        list = list.filter(p => {
          const id = p.equipe_id ?? p.equipe?.id
          return id && String(id) === String(this.filter.equipe_id)
        })
      }

      return list
    },
    availableFilterGroupes() {
      if (!this.filter.peloton_id || this.filter.peloton_id === 'null') return []
      const peloton = this.pelotons.find(p => String(p.id) === String(this.filter.peloton_id))
      return peloton ? peloton.groupes : []
    },
    availableFilterEquipes() {
      if (!this.filter.groupe_id || this.filter.groupe_id === 'null') return []
      const groupes = this.availableFilterGroupes
      const groupe = groupes.find(g => String(g.id) === String(this.filter.groupe_id))
      return groupe ? groupe.equipes : []
    },
    availableFormGroupes() {
      if (!this.form.peloton_id) return []
      const peloton = this.pelotons.find(p => String(p.id) === String(this.form.peloton_id))
      return peloton ? peloton.groupes : []
    },
    availableFormEquipes() {
      if (!this.form.groupe_id) return []
      const groupe = this.availableFormGroupes.find(g => String(g.id) === String(this.form.groupe_id))
      return groupe ? groupe.equipes : []
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
    openCreate() {
      this.form = { name: '', numero_incorporation: '', grade: '', fonction: '', phone_number: '', peloton_id: null, groupe_id: null, equipe_id: null }
      this.errors = {}
      this.modal = { open: true, mode: 'create', agent: null }
    },
    openEdit(agent) {
      this.form = {
        name:                 agent.name,
        numero_incorporation: agent.numero_incorporation ?? '',
        grade:                agent.grade ?? '',
        fonction:             agent.fonction ?? '',
        phone_number:         agent.phone_number ?? '',
        peloton_id:           agent.peloton_id ?? null,
        groupe_id:            agent.groupe_id ?? null,
        equipe_id:            agent.equipe_id ?? null,
      }
      this.errors = {}
      this.modal = { open: true, mode: 'edit', agent }
    },
    closeModal() {
      this.modal.open = false
    },
    submitForm() {
      this.errors = {}
      if (!this.form.name.trim()) { this.errors.name = 'Le nom est requis.'; return }
      this.saving = 'modal'
      if (this.modal.mode === 'create') {
        router.post(route('personnel.store'), this.form, {
          preserveScroll: true,
          onSuccess: () => this.closeModal(),
          onError: (e) => { this.errors = e },
          onFinish: () => { this.saving = null },
        })
      } else {
        router.put(route('personnel.update', this.modal.agent.id), this.form, {
          preserveScroll: true,
          onSuccess: () => this.closeModal(),
          onError: (e) => { this.errors = e },
          onFinish: () => { this.saving = null },
        })
      }
    },
    confirmDelete(agent) {
      if (!confirm(`Supprimer ${agent.name} du personnel ?`)) return
      this.closeModal()
      this.saving = agent.id
      router.delete(route('personnel.destroy', agent.id), {
        preserveScroll: true,
        onFinish: () => { this.saving = null },
      })
    },
    resetFilters() {
      this.filter.peloton_id = null
      this.filter.groupe_id = null
      this.filter.equipe_id = null
    },
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
  align-items: start;
}

/* CARTE AGENT */
.agent-card {
  background: white;
  border-radius: 16px;
  padding: 22px;
  position: relative;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 2px solid transparent;
  display: flex;
  flex-direction: column;
  gap: 14px;
  transition: box-shadow 0.2s;
  min-height: 355px;
  max-height: 600px;
  overflow-y: auto;
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
  overflow: hidden;
}
.avatar-img {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 50%;
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
.agent-name   { font-size: 16px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.agent-rank   { font-size: 12px; color: #6b7280; margin: 0; }
.agent-grade  { font-weight: 700; color: #374151; }
.agent-incorp { font-weight: 700; color: #4f6fee; font-family: monospace; }

.account-badge { font-size: 10px; font-weight: 600; color: #6b7280; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 20px; padding: 2px 7px; white-space: nowrap; }
.card-clickable { cursor: pointer; }
.card-clickable:hover { box-shadow: 0 6px 24px rgba(0,0,0,.1); }
.btn-add-personnel { padding:9px 18px; background:#4f6fee; color:white; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; white-space:nowrap; }
.btn-add-personnel:hover { background:#3a56d4; }

/* MODAL */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:100; padding:20px; }
.modal-box     { background:white; border-radius:16px; padding:32px; width:100%; max-width:520px; box-shadow:0 20px 60px rgba(0,0,0,.2); max-height:90vh; overflow-y:auto; }
.modal-title   { font-size:18px; font-weight:700; color:#1a1f2e; margin:0 0 24px; }
.form-group    { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
.form-label    { font-size:13px; font-weight:600; color:#374151; }
.form-input    { padding:9px 12px; border:1.5px solid #e5e7eb; border-radius:8px; font-size:14px; font-family:inherit; color:#1a1f2e; outline:none; background:white; transition:border-color .15s; }
.form-input:focus { border-color:#4f6fee; }
.form-error    { font-size:12px; color:#dc2626; }
.form-row-2    { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.modal-footer  { display:flex; gap:10px; justify-content:flex-end; margin-top:24px; }
.btn-secondary      { padding:9px 18px; background:#f3f4f6; color:#374151; border:none; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; }
.btn-secondary:hover { background:#e5e7eb; }
.btn-primary-modal  { padding:9px 18px; background:#4f6fee; color:white; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; }
.btn-primary-modal:disabled { opacity:.6; cursor:wait; }
.btn-delete-modal   { padding:9px 12px; background:#fff1f2; color:#dc2626; border:1.5px solid #fecdd3; border-radius:10px; cursor:pointer; display:flex; align-items:center; transition:all .15s; }
.btn-delete-modal:hover:not(:disabled) { background:#fee2e2; border-color:#fca5a5; }
.btn-delete-modal:disabled { opacity:.5; cursor:wait; }
.modal-footer { display:flex; align-items:center; margin-top:24px; }

/* UNIT BADGES */
.agent-unit {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 8px;
}
.unit-badge {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
  background: #f3f4f6;
  color: #4b5563;
  border: 1px solid #e5e7eb;
}
.unit-badge.peloton { background: #e0e7ff; color: #3730a3; border-color: #c7d2fe; }
.unit-badge.groupe { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.unit-badge.equipe { background: #fce7f3; color: #9d174d; border-color: #fbcfe8; }
.unit-badge.none { background: #f3f4f6; color: #9ca3af; border-style: dashed; }

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
