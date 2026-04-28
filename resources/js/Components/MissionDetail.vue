<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>

      <!-- HEADER -->
      <div class="modal-header">
        <div class="header-badges">
          <span class="status-badge" :class="statusClass(mission.status)">{{ statusLabel(mission.status) }}</span>
          <span class="priority-badge" :class="'prio-' + mission.priority">{{ priorityLabel(mission.priority) }}</span>
        </div>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <h2 class="mission-title">{{ mission.title }}</h2>
      <p class="mission-company">{{ mission.company }}</p>

      <!-- INFOS CLÉS -->
      <div class="info-grid">
        <div class="info-item">
          <span class="info-icon">📅</span>
          <div>
            <div class="info-label">Date</div>
            <div class="info-value">{{ formatDate(mission.date) }}</div>
          </div>
        </div>
        <div class="info-item">
          <span class="info-icon">🕘</span>
          <div>
            <div class="info-label">Heure H</div>
            <div class="info-value">{{ formatTime(mission.start_time) }}</div>
          </div>
        </div>
        <div class="info-item">
          <span class="info-icon">⏱️</span>
          <div>
            <div class="info-label">Durée</div>
            <div class="info-value">{{ formatDuration(mission.duration) }}</div>
          </div>
        </div>
        <div class="info-item">
          <span class="info-icon">📍</span>
          <div>
            <div class="info-label">Zone d'opération</div>
            <div class="info-value">{{ mission.location }}</div>
          </div>
        </div>
      </div>

      <!-- BRIEFING -->
      <div v-if="mission.briefing" class="section">
        <h3 class="section-title">Briefing opérationnel</h3>
        <div class="briefing-box">{{ mission.briefing }}</div>
      </div>

      <!-- ÉQUIPE -->
      <div class="section">
        <h3 class="section-title">Équipe affectée ({{ mission.users?.length ?? 0 }})</h3>
        <div v-if="mission.users?.length" class="team-list">
          <div v-for="user in mission.users" :key="user.id" class="team-member">
            <div class="member-avatar" :style="{ background: getColor(user.name) }">
              {{ getInitials(user.name) }}
            </div>
            <div class="member-info">
              <span class="member-name">{{ user.name }}</span>
              <span class="member-role">{{ user.role ?? '' }}</span>
            </div>
          </div>
        </div>
        <p v-else class="empty-note">Aucun agent affecté.</p>
      </div>

      <!-- CONTACT -->
      <div class="section">
        <h3 class="section-title">Contact</h3>
        <div class="contact-grid">
          <div class="contact-item">
            <span class="contact-label">Officier de liaison</span>
            <span class="contact-value">{{ mission.client_name }}</span>
          </div>
          <div class="contact-item">
            <span class="contact-label">Ligne sécurisée</span>
            <span class="contact-value">{{ mission.client_phone }}</span>
          </div>
          <div v-if="mission.client_email" class="contact-item">
            <span class="contact-label">Contact chiffré</span>
            <span class="contact-value">{{ mission.client_email }}</span>
          </div>
        </div>
      </div>

      <!-- ACTIONS -->
      <div class="modal-actions">
        <button @click="$emit('close')" class="btn-secondary">Fermer</button>
        <div class="actions-right">
          <template v-if="$page.props.auth.can.edit_missions && confirmDelete">
            <span class="confirm-label">Confirmer la suppression ?</span>
            <button class="btn-danger" @click="deleteMission">Oui, supprimer</button>
            <button class="btn-secondary" @click="confirmDelete = false">Annuler</button>
          </template>
          <template v-else>
            <button
              v-if="canCancel(mission.status)"
              class="btn-danger"
              @click="changeStatus('cancelled')"
            >✕ Abandonner</button>
            <button
              v-if="nextStatus"
              class="btn-progress"
              :class="'btn-' + nextStatus.value"
              @click="changeStatus(nextStatus.value)"
            >{{ nextStatus.label }}</button>
            <button v-if="$page.props.auth.can.edit_missions" class="btn-edit" @click="requestEdit">✏️ Modifier</button>
            <button v-if="$page.props.auth.can.edit_missions" class="btn-delete" @click="confirmDelete = true">🗑️</button>
          </template>
        </div>
      </div>

      <div v-if="editLocked" class="edit-locked-msg">
        🔒 Cette opération est terminée et ne peut plus être modifiée.
      </div>

    </div>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'

export default {
  name: 'MissionDetail',

  props: {
    mission: { type: Object, required: true },
  },

  emits: ['close', 'edit'],

  data() {
    return {
      confirmDelete: false,
      editLocked:    false,
    }
  },

  computed: {
    nextStatus() {
      const map = {
        pending:     { value: 'in_progress', label: '▶ Déployer' },
        in_progress: { value: 'completed',   label: '✓ Mission accomplie' },
      }
      return map[this.mission.status] ?? null
    },
  },

  methods: {
    formatDate(date) {
      if (!date) return ''
      return new Date(date + 'T00:00:00').toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
      })
    },
    formatTime(time) {
      return time ? time.substring(0, 5) : ''
    },
    formatDuration(d) {
      if (!d) return ''
      const h = Math.floor(d)
      const m = Math.round((d - h) * 60)
      if (h === 0) return `${m} min`
      return m > 0 ? `${h}h${m}` : `${h}h`
    },
    statusLabel(status) {
      return { pending: 'En attente', in_progress: 'En opération', completed: 'Accomplie', cancelled: 'Abandonnée' }[status] ?? status
    },
    statusClass(status) {
      return { pending: 'badge-pending', in_progress: 'badge-active', completed: 'badge-done', cancelled: 'badge-cancelled' }[status] ?? ''
    },
    priorityLabel(p) {
      return { low: 'Niveau 4 – Faible', medium: 'Niveau 3 – Standard', high: 'Niveau 2 – Haute', urgent: 'CRITIQUE' }[p] ?? p
    },
    getInitials(name) {
      return name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() ?? '??'
    },
    getColor(name) {
      let hash = 0
      for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
      return `hsl(${Math.abs(hash) % 360}, 50%, 38%)`
    },
    canCancel(status) {
      return status === 'pending' || status === 'in_progress'
    },
    changeStatus(status) {
      router.patch(route('missions.updateStatus', this.mission.id), { status }, {
        preserveScroll: true,
        onSuccess: () => this.$emit('close'),
      })
    },
    requestEdit() {
      if (this.mission.status === 'completed' || this.mission.status === 'cancelled') {
        this.editLocked = true
        setTimeout(() => { this.editLocked = false }, 4000)
        return
      }
      this.$emit('edit', this.mission)
    },
    deleteMission() {
      router.delete(route('missions.destroy', this.mission.id), {
        onSuccess: () => this.$emit('close'),
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
  border-radius: 20px;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.25);
  width: 90%;
  max-width: 560px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 28px;
  animation: slideIn 0.25s ease-out;
}
@keyframes slideIn {
  from { opacity: 0; transform: translateY(-16px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* HEADER */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.header-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.close-btn {
  background: #f3f4f6;
  border: none;
  border-radius: 8px;
  width: 32px; height: 32px;
  cursor: pointer;
  color: #6b7280;
  font-size: 14px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.close-btn:hover { background: #e5e7eb; }

.mission-title   { font-size: 22px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.mission-company { font-size: 14px; color: #6b7280; margin: 0 0 24px; }

/* BADGES */
.status-badge, .priority-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.badge-pending   { background: #eef2ff; color: #4f6fee; }
.badge-active    { background: #fff4e5; color: #f59e0b; }
.badge-done      { background: #e8f8ef; color: #22c55e; }
.badge-cancelled { background: #fef2f2; color: #ef4444; }

.prio-low    { background: #d1fae5; color: #059669; }
.prio-medium { background: #fef3c7; color: #d97706; }
.prio-high   { background: #fee2e2; color: #dc2626; }
.prio-urgent { background: #dc2626; color: white; }

/* INFOS CLÉS */
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 24px;
}
.info-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: #f9fafb;
  border-radius: 10px;
  padding: 12px;
}
.info-icon  { font-size: 18px; flex-shrink: 0; line-height: 1; }
.info-label { font-size: 11px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2px; }
.info-value { font-size: 14px; font-weight: 600; color: #1a1f2e; }

/* SECTIONS */
.section { margin-bottom: 24px; }
.section-title {
  font-size: 12px;
  font-weight: 700;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin: 0 0 10px;
}
.briefing-box {
  background: #f9fafb;
  border-radius: 10px;
  padding: 14px 16px;
  font-size: 14px;
  color: #374151;
  line-height: 1.6;
  white-space: pre-wrap;
}

/* ÉQUIPE */
.team-list { display: flex; flex-direction: column; gap: 8px; }
.team-member {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f9fafb;
  border-radius: 10px;
  padding: 10px 14px;
}
.member-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 13px; font-weight: 700; flex-shrink: 0;
}
.member-info { display: flex; flex-direction: column; }
.member-name { font-size: 14px; font-weight: 600; color: #1a1f2e; }
.member-role { font-size: 12px; color: #9ca3af; }
.empty-note  { font-size: 13px; color: #9ca3af; font-style: italic; }

/* CONTACT */
.contact-grid { display: flex; flex-direction: column; gap: 8px; }
.contact-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f9fafb;
  border-radius: 10px;
  padding: 10px 14px;
}
.contact-label { font-size: 12px; color: #9ca3af; font-weight: 600; }
.contact-value { font-size: 14px; font-weight: 600; color: #1a1f2e; }

/* ACTIONS */
.modal-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-top: 8px;
  padding-top: 20px;
  border-top: 1px solid #f3f4f6;
  flex-wrap: wrap;
}
.actions-right { display: flex; gap: 8px; flex-wrap: wrap; }

.btn-secondary, .btn-danger, .btn-progress, .btn-edit {
  padding: 9px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  font-family: inherit;
  transition: all 0.15s;
}
.btn-secondary { background: #f3f4f6; color: #6b7280; }
.btn-secondary:hover { background: #e5e7eb; }
.btn-danger    { background: #fef2f2; color: #ef4444; }
.btn-danger:hover { background: #fee2e2; }
.btn-edit      { background: #f3f4f6; color: #374151; }
.btn-edit:hover { background: #e5e7eb; }

.btn-in_progress { background: #eef2ff; color: #4f6fee; }
.btn-in_progress:hover { background: #e0e7ff; }
.btn-completed   { background: #e8f8ef; color: #22c55e; }
.btn-completed:hover { background: #d1fae5; }

.btn-delete {
  padding: 9px 12px;
  border-radius: 8px;
  font-size: 13px;
  cursor: pointer;
  border: none;
  font-family: inherit;
  background: #fef2f2;
  color: #ef4444;
  transition: background 0.15s;
}
.btn-delete:hover { background: #fee2e2; }

.confirm-label {
  font-size: 13px;
  font-weight: 600;
  color: #ef4444;
  align-self: center;
}
.edit-locked-msg {
  margin-top: 12px;
  padding: 10px 14px;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #92400e;
  animation: fadeIn 0.2s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: translateY(0); }
}

.modal-content::-webkit-scrollbar { width: 5px; }
.modal-content::-webkit-scrollbar-track { background: transparent; }
.modal-content::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

@media (max-width: 640px) {
  .info-grid { grid-template-columns: 1fr; }
  .modal-content { padding: 20px; }
  .modal-actions { flex-direction: column; align-items: stretch; }
  .actions-right { justify-content: flex-end; }
}
</style>
