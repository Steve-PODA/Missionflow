<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <div>
          <h2 class="modal-title">Déployer une opération</h2>
          <p class="modal-sub">Renseignez les paramètres de la mission</p>
        </div>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <!-- Nom de l'opération -->
      <div class="form-group">
        <label>Nom de l'opération <span class="required">*</span></label>
        <input
          type="text"
          v-model="form.title"
          placeholder="Ex : Opération Tonnerre"
          class="form-input"
          :class="{ 'input-error': errors.title }"
        />
        <span v-if="errors.title" class="error-msg">{{ errors.title[0] }}</span>
      </div>

      <!-- Briefing -->
      <div class="form-group">
        <label>Briefing opérationnel</label>
        <textarea
          v-model="form.briefing"
          placeholder="Décrivez les objectifs, contraintes et informations clés de l'opération..."
          class="form-input form-textarea"
          :class="{ 'input-error': errors.briefing }"
          rows="3"
        ></textarea>
        <span v-if="errors.briefing" class="error-msg">{{ errors.briefing[0] }}</span>
      </div>

      <!-- Commanditaire -->
      <div class="form-group">
        <label>Commanditaire / Unité <span class="required">*</span></label>
        <input
          type="text"
          v-model="form.company"
          placeholder="Ex : État-Major, DGSE, Unité Delta..."
          class="form-input"
          :class="{ 'input-error': errors.company }"
        />
        <span v-if="errors.company" class="error-msg">{{ errors.company[0] }}</span>
      </div>

      <!-- Date + Heure H -->
      <div class="form-row">
        <div class="form-group">
          <label>Date d'opération <span class="required">*</span></label>
          <input
            type="date"
            v-model="form.date"
            class="form-input"
            :class="{ 'input-error': errors.date || dateError }"
            :min="today"
          />
          <span v-if="dateError" class="error-msg">La date ne peut pas être dans le passé.</span>
          <span v-else-if="errors.date" class="error-msg">{{ errors.date[0] }}</span>
        </div>
        <div class="form-group">
          <label>Heure H <span class="required">*</span></label>
          <input
            type="time"
            v-model="form.startTime"
            class="form-input"
            :class="{ 'input-error': errors.startTime }"
          />
          <span v-if="errors.startTime" class="error-msg">{{ errors.startTime[0] }}</span>
        </div>
      </div>

      <!-- Durée -->
      <div class="form-group">
        <label>Durée estimée</label>
        <select v-model="form.duration" class="form-input">
          <option value="0.5">30 minutes</option>
          <option value="1">1 heure</option>
          <option value="2">2 heures</option>
          <option value="4">4 heures</option>
          <option value="8">Journée complète</option>
        </select>
      </div>

      <!-- Niveau de priorité -->
      <div class="form-group">
        <label>Niveau de priorité <span class="required">*</span></label>
        <div class="priority-grid">
          <button
            v-for="p in priorities"
            :key="p.value"
            type="button"
            class="priority-btn"
            :class="[p.value, { selected: form.priority === p.value }]"
            @click="form.priority = p.value"
          >
            <span class="priority-dot"></span>
            {{ p.label }}
          </button>
        </div>
      </div>

      <!-- Personnel affecté -->
      <div class="form-group">
        <label>Personnel affecté ({{ form.selectedTeam.length }}) <span class="required">*</span></label>
        <span v-if="errors.selectedTeam" class="error-msg" style="margin-bottom:6px;display:block;">{{ errors.selectedTeam[0] }}</span>

        <!-- Avertissement si un agent indisponible est sélectionné -->
        <div v-if="hasUnavailableSelected" class="warning-banner">
          ⚠️ Un ou plusieurs agents sélectionnés ne sont pas disponibles.
        </div>

        <div class="team-select">
          <div
            v-for="member in sortedTeamMembers"
            :key="member.id"
            class="team-member"
            :class="{ selected: isSelected(member.id), 'member-unavailable': isUnavailable(member) }"
            @click="toggleMember(member.id)"
          >
            <div class="member-avatar" :style="{ background: getUserColor(member.name) }">
              {{ getInitials(member.name) }}
            </div>
            <div class="member-info">
              <span class="member-name">{{ member.name }}</span>
              <span class="member-role">{{ member.role }}</span>
              <span v-if="member.active_mission" class="member-op">⚔️ {{ member.active_mission.title }}</span>
            </div>
            <span class="avail-badge" :class="'avail-' + (member.computed_status || 'available')">
              {{ availLabel(member.computed_status) }}
            </span>
            <div class="check-icon" v-if="isSelected(member.id)">✓</div>
          </div>
        </div>
      </div>

      <!-- Zone d'opération -->
      <div class="form-group">
        <label>Zone d'opération <span class="required">*</span></label>
        <input
          type="text"
          v-model="form.location"
          placeholder="Ex : Secteur Nord, Bâtiment 7, Coordonnées GPS..."
          class="form-input"
          :class="{ 'input-error': errors.location }"
        />
        <span v-if="errors.location" class="error-msg">{{ errors.location[0] }}</span>
      </div>

      <!-- CHEF DE MISSION (si plusieurs agents sélectionnés) -->
      <div v-if="form.selectedTeam.length > 1" class="form-group">
        <label>Chef de mission <span class="required">*</span></label>
        <p class="field-hint">Ses coordonnées seront utilisées comme contact principal de l'opération.</p>
        <div class="leader-select">
          <div
            v-for="member in selectedMembers"
            :key="member.id"
            class="leader-option"
            :class="{ active: leaderId === member.id }"
            @click="selectLeader(member.id)"
          >
            <div class="member-avatar" :style="{ background: getUserColor(member.name) }">{{ getInitials(member.name) }}</div>
            <div class="leader-info">
              <span class="member-name">{{ member.name }}</span>
              <span class="member-role">{{ member.role || '—' }}</span>
            </div>
            <div class="radio-circle" :class="{ checked: leaderId === member.id }">
              <div v-if="leaderId === member.id" class="radio-dot"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Officier de liaison + Ligne sécurisée -->
      <div class="form-row">
        <div class="form-group">
          <label>Officier de liaison <span class="required">*</span></label>
          <div class="input-with-badge">
            <input
              type="text"
              v-model="form.clientName"
              @input="autoFilled = false"
              placeholder="Grade et nom"
              class="form-input"
              :class="{ 'input-error': errors.clientName }"
            />
            <span v-if="autoFilled" class="autofill-badge">Auto</span>
          </div>
          <span v-if="errors.clientName" class="error-msg">{{ errors.clientName[0] }}</span>
        </div>
        <div class="form-group">
          <label>Ligne sécurisée <span class="required">*</span></label>
          <div class="input-with-badge">
            <input
              type="tel"
              v-model="form.clientPhone"
              @input="autoFilled = false"
              placeholder="Numéro de contact"
              class="form-input"
              :class="{ 'input-error': errors.clientPhone }"
            />
            <span v-if="autoFilled" class="autofill-badge">Auto</span>
          </div>
          <span v-if="errors.clientPhone" class="error-msg">{{ errors.clientPhone[0] }}</span>
        </div>
      </div>

      <!-- Contact chiffré (email) -->
      <div class="form-group">
        <label>Contact chiffré (email)</label>
        <div class="input-with-badge">
          <input
            type="email"
            v-model="form.clientEmail"
            @input="autoFilled = false"
            placeholder="adresse@domaine.mil"
            class="form-input"
            :class="{ 'input-error': errors.clientEmail }"
          />
          <span v-if="autoFilled" class="autofill-badge">Auto</span>
        </div>
        <span v-if="errors.clientEmail" class="error-msg">{{ errors.clientEmail[0] }}</span>
      </div>

      <div class="modal-actions">
        <button @click="$emit('close')" class="btn-cancel" :disabled="isSaving">Annuler</button>
        <button @click="createMission" class="btn-submit" :disabled="isSaving || !isValid">
          {{ isSaving ? 'Déploiement...' : 'Confirmer le déploiement' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'
import { inject } from 'vue'

export default {
  name: 'MissionCreator',

  props: {
    teamMembers: { type: Array, default: () => [] }
  },

  setup() {
    const showNotification = inject('showNotification', () => {})
    return { showNotification }
  },

  data() {
    return {
      isSaving:  false,
      errors:    {},
      leaderId:  null,
      autoFilled: false,
      today: new Date().toLocaleDateString('sv-SE'),
      form: {
        title:        '',
        briefing:     '',
        company:      '',
        date:         '',
        startTime:    '',
        duration:     '2',
        priority:     'medium',
        location:     '',
        selectedTeam: [],
        clientName:   '',
        clientEmail:  '',
        clientPhone:  '',
      },
      priorities: [
        { value: 'low',    label: 'Niveau 4 – Faible'  },
        { value: 'medium', label: 'Niveau 3 – Standard' },
        { value: 'high',   label: 'Niveau 2 – Haute'   },
        { value: 'urgent', label: 'CRITIQUE'            },
      ],
    }
  },

  watch: {
    'form.selectedTeam'(newVal) {
      if (newVal.length === 1) {
        this.leaderId = newVal[0]
        this.fillFromLeader(newVal[0])
      } else if (newVal.length === 0) {
        this.leaderId   = null
        this.autoFilled = false
      } else {
        // plusieurs membres : si le leader actuel n'est plus dans la sélection, le retirer
        if (this.leaderId && !newVal.includes(this.leaderId)) {
          this.leaderId   = null
          this.autoFilled = false
        }
      }
    },
  },

  computed: {
    sortedTeamMembers() {
      const order = { available: 0, deployed: 1, on_leave: 2, unavailable: 3 }
      return [...this.teamMembers].sort((a, b) =>
        (order[a.computed_status] ?? 0) - (order[b.computed_status] ?? 0)
      )
    },
    hasUnavailableSelected() {
      return this.form.selectedTeam.some(id => {
        const m = this.teamMembers.find(t => t.id === id)
        return m && (m.computed_status === 'on_leave' || m.computed_status === 'unavailable')
      })
    },
    dateError() {
      return this.form.date && this.form.date < this.today
    },
    selectedMembers() {
      return this.form.selectedTeam
        .map(id => this.teamMembers.find(m => m.id === id))
        .filter(Boolean)
    },
    isValid() {
      const leaderOk = this.form.selectedTeam.length < 2 || this.leaderId !== null
      return (
        !this.dateError &&
        this.form.title.trim() &&
        this.form.company.trim() &&
        this.form.date &&
        this.form.startTime &&
        this.form.location.trim() &&
        this.form.selectedTeam.length > 0 &&
        leaderOk &&
        this.form.clientName.trim() &&
        this.form.clientPhone.trim()
      )
    }
  },

  methods: {
    getInitials(name) {
      if (!name) return '??'
      return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
    },

    getUserColor(name) {
      let hash = 0
      for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash)
      }
      return `hsl(${Math.abs(hash) % 360}, 55%, 40%)`
    },

    isSelected(id) { return this.form.selectedTeam.includes(id) },
    isUnavailable(member) {
      return member.computed_status === 'on_leave' || member.computed_status === 'unavailable'
    },
    availLabel(status) {
      return { available: 'Libre', deployed: 'Déployé', on_leave: 'En congé', unavailable: 'Indispo' }[status] ?? 'Libre'
    },

    toggleMember(id) {
      const i = this.form.selectedTeam.indexOf(id)
      if (i > -1) this.form.selectedTeam.splice(i, 1)
      else        this.form.selectedTeam.push(id)
    },
    selectLeader(id) {
      this.leaderId = id
      this.fillFromLeader(id)
    },
    fillFromLeader(id) {
      const member = this.teamMembers.find(m => m.id === id)
      if (!member) return
      this.form.clientName  = member.name        || ''
      this.form.clientPhone = member.phone_number || ''
      this.form.clientEmail = member.email        || ''
      this.autoFilled       = true
    },

    createMission() {
      if (!this.isValid || this.isSaving) return
      this.isSaving = true
      this.errors   = {}

      router.post(route('missions.store'), this.form, {
        onSuccess: () => {
          this.isSaving = false
          this.$emit('close')
        },
        onError: (errs) => {
          this.isSaving = false
          this.errors   = errs
          
          // Afficher le premier message d'erreur comme une notification
          const errorMessages = Object.values(errs).flat()
          if (errorMessages.length > 0) {
            this.showNotification(errorMessages[0], 'error')
          }
        },
      })
    },
  },
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.25);
  width: 90%;
  max-width: 580px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 28px;
  animation: slideIn 0.25s ease-out;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(-16px); }
  to   { opacity: 1; transform: translateY(0);     }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: #1a1f2e;
  margin: 0 0 4px;
}

.modal-sub {
  font-size: 13px;
  color: #6b7280;
  margin: 0;
}

.close-btn {
  background: #f3f4f6;
  border: none;
  border-radius: 8px;
  width: 32px;
  height: 32px;
  cursor: pointer;
  color: #6b7280;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.close-btn:hover { background: #e5e7eb; }

/* FORM */
.form-group { margin-bottom: 16px; }

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

.required { color: #ef4444; }

.form-input {
  width: 100%;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 14px;
  color: #1a1f2e;
  font-family: inherit;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #4f6fee;
  box-shadow: 0 0 0 3px rgba(79, 111, 238, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.input-error { border-color: #ef4444 !important; }

.error-msg {
  font-size: 12px;
  color: #ef4444;
  margin-top: 4px;
  display: block;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

/* PRIORITY */
.priority-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.priority-btn {
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s, color 0.15s;
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: inherit;
}

.priority-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.priority-btn.low    { --c: #10b981; }
.priority-btn.medium { --c: #f59e0b; }
.priority-btn.high   { --c: #ef4444; }
.priority-btn.urgent { --c: #dc2626; }

.priority-btn .priority-dot     { background: var(--c); }
.priority-btn:hover              { border-color: var(--c); color: var(--c); }
.priority-btn.selected           { border-color: var(--c); background: var(--c); color: white; }
.priority-btn.selected .priority-dot { background: white; }

/* TEAM */
.team-select {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 200px;
  overflow-y: auto;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px;
}

.team-member {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.15s;
}

.team-member:hover              { background: #f9fafb; }
.team-member.selected           { background: rgba(79, 111, 238, 0.08); border-color: #4f6fee; }
.team-member.member-unavailable { opacity: 0.65; }

.member-op {
  font-size: 11px;
  color: #d97706;
  display: block;
}

.avail-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 20px;
  white-space: nowrap;
  flex-shrink: 0;
}
.avail-available   { background: #d1fae5; color: #059669; }
.avail-deployed    { background: #fed7aa; color: #d97706; }
.avail-on_leave    { background: #f3f4f6; color: #6b7280; }
.avail-unavailable { background: #fee2e2; color: #dc2626; }

.warning-banner {
  background: #fffbeb;
  border: 1px solid #fcd34d;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13px;
  color: #92400e;
  margin-bottom: 8px;
}

.member-avatar {
  width: 38px; height: 38px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 13px; color: white; flex-shrink: 0;
}

.member-info { flex: 1; display: flex; flex-direction: column; }
.member-name { font-size: 14px; font-weight: 600; color: #1a1f2e; }
.member-role { font-size: 12px; color: #6b7280; }

.check-icon {
  width: 22px; height: 22px;
  background: #4f6fee;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 12px; font-weight: 700;
}

/* CHEF DE MISSION */
.field-hint { font-size: 11px; color: #9ca3af; margin-bottom: 8px; display: block; }
.leader-select { display: flex; flex-direction: column; gap: 6px; }
.leader-option {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; border-radius: 10px;
  border: 2px solid #e5e7eb; cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.leader-option:hover { border-color: #4f6fee; background: #f8f9ff; }
.leader-option.active { border-color: #4f6fee; background: rgba(79,111,238,0.07); }
.leader-info { flex: 1; display: flex; flex-direction: column; }
.radio-circle {
  width: 18px; height: 18px; border-radius: 50%;
  border: 2px solid #d1d5db; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: border-color 0.15s;
}
.leader-option.active .radio-circle { border-color: #4f6fee; }
.radio-dot { width: 8px; height: 8px; border-radius: 50%; background: #4f6fee; }

/* AUTOFILL BADGE */
.input-with-badge { position: relative; }
.input-with-badge .form-input { padding-right: 52px; }
.autofill-badge {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  font-size: 10px; font-weight: 700; background: #eef2ff; color: #4f6fee;
  padding: 2px 7px; border-radius: 10px; pointer-events: none;
}

/* ACTIONS */
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #f3f4f6;
}

.btn-cancel, .btn-submit {
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  font-family: inherit;
  transition: all 0.15s;
}

.btn-cancel { background: #f3f4f6; color: #6b7280; }
.btn-cancel:hover:not(:disabled) { background: #e5e7eb; }

.btn-submit { background: #4f6fee; color: white; }
.btn-submit:hover:not(:disabled) { background: #3d5cdb; box-shadow: 0 4px 12px rgba(79, 111, 238, 0.3); }

.btn-cancel:disabled, .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }

/* SCROLLBAR */
.modal-content::-webkit-scrollbar,
.team-select::-webkit-scrollbar { width: 5px; }
.modal-content::-webkit-scrollbar-track,
.team-select::-webkit-scrollbar-track { background: transparent; }
.modal-content::-webkit-scrollbar-thumb,
.team-select::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

@media (max-width: 640px) {
  .modal-content { padding: 20px; }
  .form-row { grid-template-columns: 1fr; }
  .priority-grid { grid-template-columns: 1fr 1fr; }
}
</style>
