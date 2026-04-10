<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <div>
          <h2 class="modal-title">Modifier l'opération</h2>
          <p class="modal-sub">{{ mission.title }}</p>
        </div>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <!-- Nom de l'opération -->
      <div class="form-group">
        <label>Nom de l'opération <span class="required">*</span></label>
        <input
          type="text"
          v-model="form.title"
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
          class="form-input form-textarea"
          rows="3"
        ></textarea>
      </div>

      <!-- Commanditaire -->
      <div class="form-group">
        <label>Commanditaire / Unité <span class="required">*</span></label>
        <input
          type="text"
          v-model="form.company"
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
            :class="{ 'input-error': errors.date }"
          />
          <span v-if="errors.date" class="error-msg">{{ errors.date[0] }}</span>
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
              <span v-if="member.active_mission && !isSelected(member.id)" class="member-op">⚔️ {{ member.active_mission.title }}</span>
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
          class="form-input"
          :class="{ 'input-error': errors.location }"
        />
        <span v-if="errors.location" class="error-msg">{{ errors.location[0] }}</span>
      </div>

      <!-- Officier de liaison + Ligne sécurisée -->
      <div class="form-row">
        <div class="form-group">
          <label>Officier de liaison <span class="required">*</span></label>
          <input
            type="text"
            v-model="form.clientName"
            class="form-input"
            :class="{ 'input-error': errors.clientName }"
          />
          <span v-if="errors.clientName" class="error-msg">{{ errors.clientName[0] }}</span>
        </div>
        <div class="form-group">
          <label>Ligne sécurisée <span class="required">*</span></label>
          <input
            type="tel"
            v-model="form.clientPhone"
            class="form-input"
            :class="{ 'input-error': errors.clientPhone }"
          />
          <span v-if="errors.clientPhone" class="error-msg">{{ errors.clientPhone[0] }}</span>
        </div>
      </div>

      <!-- Contact chiffré -->
      <div class="form-group">
        <label>Contact chiffré (email)</label>
        <input
          type="email"
          v-model="form.clientEmail"
          class="form-input"
          :class="{ 'input-error': errors.clientEmail }"
        />
        <span v-if="errors.clientEmail" class="error-msg">{{ errors.clientEmail[0] }}</span>
      </div>

      <div class="modal-actions">
        <button @click="$emit('close')" class="btn-cancel" :disabled="isSaving">Annuler</button>
        <button @click="saveMission" class="btn-submit" :disabled="isSaving || !isValid">
          {{ isSaving ? 'Enregistrement...' : 'Enregistrer les modifications' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'

export default {
  name: 'MissionEditor',

  props: {
    mission:     { type: Object, required: true },
    teamMembers: { type: Array,  default: () => [] },
  },

  emits: ['close'],

  data() {
    return {
      isSaving: false,
      errors: {},
      form: {
        title:        this.mission.title        ?? '',
        briefing:     this.mission.briefing     ?? '',
        company:      this.mission.company      ?? '',
        date:         this.mission.date         ?? '',
        startTime:    (this.mission.start_time  ?? '').substring(0, 5),
        duration:     String(this.mission.duration ?? '2'),
        priority:     this.mission.priority     ?? 'medium',
        location:     this.mission.location     ?? '',
        selectedTeam: (this.mission.users       ?? []).map(u => u.id),
        clientName:   this.mission.client_name  ?? '',
        clientEmail:  this.mission.client_email ?? '',
        clientPhone:  this.mission.client_phone ?? '',
      },
      priorities: [
        { value: 'low',    label: 'Niveau 4 – Faible'  },
        { value: 'medium', label: 'Niveau 3 – Standard' },
        { value: 'high',   label: 'Niveau 2 – Haute'   },
        { value: 'urgent', label: 'CRITIQUE'            },
      ],
    }
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
    isValid() {
      return (
        this.form.title.trim() &&
        this.form.company.trim() &&
        this.form.date &&
        this.form.startTime &&
        this.form.location.trim() &&
        this.form.selectedTeam.length > 0 &&
        this.form.clientName.trim() &&
        this.form.clientPhone.trim()
      )
    },
  },

  methods: {
    getInitials(name) {
      if (!name) return '??'
      return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
    },
    getUserColor(name) {
      let hash = 0
      for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
      return `hsl(${Math.abs(hash) % 360}, 55%, 40%)`
    },
    isSelected(id)      { return this.form.selectedTeam.includes(id) },
    isUnavailable(m)    { return m.computed_status === 'on_leave' || m.computed_status === 'unavailable' },
    availLabel(status)  {
      return { available: 'Libre', deployed: 'Déployé', on_leave: 'En congé', unavailable: 'Indispo' }[status] ?? 'Libre'
    },
    toggleMember(id) {
      const i = this.form.selectedTeam.indexOf(id)
      if (i > -1) this.form.selectedTeam.splice(i, 1)
      else        this.form.selectedTeam.push(id)
    },
    saveMission() {
      if (!this.isValid || this.isSaving) return
      this.isSaving = true
      this.errors   = {}

      router.put(route('missions.update', this.mission.id), this.form, {
        onSuccess: () => {
          this.isSaving = false
          this.$emit('close')
        },
        onError: (errs) => {
          this.isSaving = false
          this.errors   = errs
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
.modal-title { font-size: 20px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.modal-sub   { font-size: 13px; color: #6b7280; margin: 0; }
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
.form-textarea { resize: vertical; min-height: 80px; }
.input-error   { border-color: #ef4444 !important; }
.error-msg     { font-size: 12px; color: #ef4444; margin-top: 4px; display: block; }
.form-row      { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.priority-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
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
.priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.priority-btn.low    { --c: #10b981; }
.priority-btn.medium { --c: #f59e0b; }
.priority-btn.high   { --c: #ef4444; }
.priority-btn.urgent { --c: #dc2626; }
.priority-btn .priority-dot     { background: var(--c); }
.priority-btn:hover              { border-color: var(--c); color: var(--c); }
.priority-btn.selected           { border-color: var(--c); background: var(--c); color: white; }
.priority-btn.selected .priority-dot { background: white; }

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
.member-op { font-size: 11px; color: #d97706; display: block; }
.avail-badge {
  font-size: 10px; font-weight: 700;
  padding: 2px 7px; border-radius: 20px;
  white-space: nowrap; flex-shrink: 0;
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
  width: 38px; height: 38px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 13px; color: white; flex-shrink: 0;
}
.member-info { flex: 1; display: flex; flex-direction: column; }
.member-name { font-size: 14px; font-weight: 600; color: #1a1f2e; }
.member-role { font-size: 12px; color: #6b7280; }
.check-icon {
  width: 22px; height: 22px; background: #4f6fee;
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  color: white; font-size: 12px; font-weight: 700;
}

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
