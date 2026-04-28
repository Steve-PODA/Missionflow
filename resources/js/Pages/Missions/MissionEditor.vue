<template>
  <AppLayout :title="'Modifier : ' + mission.title">
    <div class="page-container">
      <div class="form-card">
        <div class="form-header">
          <div>
            <h1 class="form-title">Modifier l'opération</h1>
            <p class="form-sub">{{ mission.title }} — ID: {{ mission.id }}</p>
          </div>
          <Link :href="route('missions.index')" class="back-link">
            &larr; Retour à la liste
          </Link>
        </div>

        <form @submit.prevent="saveMission">
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
              rows="4"
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

          <div class="form-row">
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
            <span v-if="errors.selectedTeam" class="error-msg" style="margin-bottom:8px;display:block;">{{ errors.selectedTeam[0] }}</span>

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
                  class="form-input"
                  :class="{ 'input-error': errors.clientPhone }"
                />
                <span v-if="autoFilled" class="autofill-badge">Auto</span>
              </div>
              <span v-if="errors.clientPhone" class="error-msg">{{ errors.clientPhone[0] }}</span>
            </div>
          </div>

          <!-- Contact chiffré -->
          <div class="form-group">
            <label>Contact chiffré (email)</label>
            <div class="input-with-badge">
              <input
                type="email"
                v-model="form.clientEmail"
                @input="autoFilled = false"
                class="form-input"
                :class="{ 'input-error': errors.clientEmail }"
              />
              <span v-if="autoFilled" class="autofill-badge">Auto</span>
            </div>
            <span v-if="errors.clientEmail" class="error-msg">{{ errors.clientEmail[0] }}</span>
          </div>

          <div class="form-actions">
            <Link :href="route('missions.index')" class="btn-cancel">Annuler</Link>
            <button type="submit" class="btn-submit" :disabled="isSaving || !isValid">
              {{ isSaving ? 'Enregistrement...' : 'Enregistrer les modifications' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { inject } from 'vue'

export default {
  name: 'MissionEditorPage',
  components: { AppLayout, Link },

  props: {
    mission: { type: Object, required: true },
    team:    { type: Array,  default: () => [] },
  },

  setup() {
    const showNotification = inject('showNotification', () => {})
    return { showNotification }
  },

  data() {
    const selectedTeam = (this.mission.users ?? []).map(u => u.id)
    const today = new Date().toLocaleDateString('sv-SE')
    return {
      isSaving:     false,
      errors:       {},
      leaderId:     selectedTeam.length === 1 ? selectedTeam[0] : null,
      autoFilled:   false,
      today,
      originalDate: this.mission.date ?? '',
      form: {
        title:        this.mission.title        ?? '',
        briefing:     this.mission.briefing     ?? '',
        company:      this.mission.company      ?? '',
        date:         this.mission.date         ?? '',
        startTime:    (this.mission.start_time  ?? '').substring(0, 5),
        duration:     String(this.mission.duration ?? '2'),
        priority:     this.mission.priority     ?? 'medium',
        location:     this.mission.location     ?? '',
        selectedTeam,
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

  watch: {
    'form.selectedTeam'(newVal) {
      if (newVal.length === 1) {
        this.leaderId = newVal[0]
        this.fillFromLeader(newVal[0])
      } else if (newVal.length === 0) {
        this.leaderId   = null
        this.autoFilled = false
      } else {
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
      return [...this.team].sort((a, b) =>
        (order[a.computed_status] ?? 0) - (order[b.computed_status] ?? 0)
      )
    },
    hasUnavailableSelected() {
      return this.form.selectedTeam.some(id => {
        const m = this.team.find(t => t.id === id)
        return m && (m.computed_status === 'on_leave' || m.computed_status === 'unavailable')
      })
    },
    selectedMembers() {
      return this.form.selectedTeam
        .map(id => this.team.find(m => m.id === id))
        .filter(Boolean)
    },
    dateError() {
      return this.form.date && this.form.date !== this.originalDate && this.form.date < this.today
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
    selectLeader(id) {
      this.leaderId = id
      this.fillFromLeader(id)
    },
    fillFromLeader(id) {
      const member = this.team.find(m => m.id === id)
      if (!member) return
      this.form.clientName  = member.name         || ''
      this.form.clientPhone = member.phone_number  || ''
      this.form.clientEmail = member.email         || ''
      this.autoFilled       = true
    },
    saveMission() {
      if (!this.isValid || this.isSaving) return
      this.isSaving = true
      this.errors   = {}

      router.put(route('missions.update', this.mission.id), this.form, {
        onSuccess: () => {
          this.isSaving = false
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
/* Page Layout - Same as MissionCreator for consistency */
.page-container {
  padding: 40px 20px;
  display: flex;
  justify-content: center;
  background: #f8fafc;
  min-height: calc(100vh - 64px);
}

.form-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05), 0 20px 48px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 800px;
  padding: 40px;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 24px;
}

.form-title {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 8px;
}

.form-sub {
  font-size: 15px;
  color: #64748b;
  margin: 0;
}

.back-link {
  font-size: 14px;
  color: #64748b;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s;
}
.back-link:hover { color: #4f6fee; }

/* REUSED STYLES FROM COMPONENT */
.form-group { margin-bottom: 24px; }
.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 8px;
}
.required { color: #ef4444; }
.form-input {
  width: 100%;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px 16px;
  font-size: 15px;
  color: #1e293b;
  font-family: inherit;
  transition: all 0.2s;
  box-sizing: border-box;
}
.form-input:focus {
  outline: none;
  border-color: #4f6fee;
  box-shadow: 0 0 0 4px rgba(79, 111, 238, 0.1);
  background: white;
}
.form-textarea { resize: vertical; min-height: 120px; }
.input-error   { border-color: #ef4444 !important; }
.error-msg     { font-size: 13px; color: #ef4444; margin-top: 6px; display: block; }
.form-row      { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

.priority-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.priority-btn {
  border: 2px solid #f1f5f9;
  background: #f8fafc;
  border-radius: 10px;
  padding: 14px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  font-family: inherit;
}
.priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.priority-btn.low    { --c: #10b981; }
.priority-btn.medium { --c: #f59e0b; }
.priority-btn.high   { --c: #ef4444; }
.priority-btn.urgent { --c: #dc2626; }
.priority-btn .priority-dot     { background: var(--c); }
.priority-btn:hover              { border-color: var(--c); background: white; color: var(--c); }
.priority-btn.selected           { border-color: var(--c); background: var(--c); color: white; }
.priority-btn.selected .priority-dot { background: white; }

.team-select {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  max-height: 350px;
  overflow-y: auto;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  background: #f8fafc;
}
.team-member {
  display: flex; align-items: center; gap: 12px;
  padding: 12px; background: white; border-radius: 10px;
  cursor: pointer; border: 2px solid transparent; transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.team-member:hover              { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
.team-member.selected           { background: rgba(79, 111, 238, 0.05); border-color: #4f6fee; }
.team-member.member-unavailable { opacity: 0.6; }
.member-op { font-size: 11px; color: #b45309; display: block; }

.avail-badge {
  font-size: 10px; font-weight: 700; padding: 2px 8px;
  border-radius: 20px; white-space: nowrap; flex-shrink: 0;
}
.avail-available   { background: #dcfce7; color: #15803d; }
.avail-deployed    { background: #fef3c7; color: #b45309; }
.avail-on_leave    { background: #f1f5f9; color: #64748b; }
.avail-unavailable { background: #fee2e2; color: #b91c1c; }

.warning-banner {
  background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px;
  padding: 12px 16px; font-size: 14px; color: #92400e; margin-bottom: 12px;
}
.member-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px; color: white; flex-shrink: 0;
}
.member-info { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.member-name { font-size: 14px; font-weight: 600; color: #1e293b; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; }
.member-role { font-size: 12px; color: #64748b; }
.check-icon {
  width: 20px; height: 20px; background: #4f6fee; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 11px; font-weight: 700;
}

/* CHEF DE MISSION */
.field-hint { font-size: 12px; color: #9ca3af; margin: -4px 0 10px; display: block; }
.leader-select { display: flex; flex-direction: column; gap: 8px; }
.leader-option {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; border-radius: 10px;
  border: 2px solid #e2e8f0; cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.leader-option:hover  { border-color: #4f6fee; background: #f8f9ff; }
.leader-option.active { border-color: #4f6fee; background: rgba(79,111,238,0.06); }
.leader-info { flex: 1; display: flex; flex-direction: column; }
.radio-circle {
  width: 18px; height: 18px; border-radius: 50%;
  border: 2px solid #d1d5db;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: border-color 0.15s;
}
.leader-option.active .radio-circle { border-color: #4f6fee; }
.radio-dot { width: 8px; height: 8px; border-radius: 50%; background: #4f6fee; }

/* AUTOFILL BADGE */
.input-with-badge { position: relative; }
.input-with-badge .form-input { padding-right: 56px; }
.autofill-badge {
  position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
  font-size: 10px; font-weight: 700;
  background: #eef2ff; color: #4f6fee;
  padding: 2px 8px; border-radius: 10px; pointer-events: none;
}

.form-actions {
  display: flex; justify-content: flex-end; gap: 16px;
  margin-top: 40px; padding-top: 32px; border-top: 1px solid #f1f5f9;
}
.btn-cancel, .btn-submit {
  padding: 12px 28px; border-radius: 10px; font-size: 15px; font-weight: 600;
  cursor: pointer; border: none; font-family: inherit; transition: all 0.2s;
  text-decoration: none; display: inline-flex; align-items: center; justify-content: center;
}
.btn-cancel { background: #f1f5f9; color: #64748b; }
.btn-cancel:hover { background: #e2e8f0; color: #1e293b; }
.btn-submit { background: #4f6fee; color: white; }
.btn-submit:hover:not(:disabled) { background: #3d5cdb; box-shadow: 0 10px 15px -3px rgba(79, 111, 238, 0.4); transform: translateY(-1px); }
.btn-cancel:disabled, .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

@media (max-width: 768px) {
  .page-container { padding: 20px 10px; }
  .form-card { padding: 24px; }
  .form-row { grid-template-columns: 1fr; gap: 0; }
  .priority-grid { grid-template-columns: 1fr 1fr; }
  .team-select { grid-template-columns: 1fr; }
}
</style>
