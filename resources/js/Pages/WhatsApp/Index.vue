<template>
  <AppLayout>
    <div class="page-wrapper">

      <!-- EN-TÊTE -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Alertes WhatsApp</h1>
          <p class="page-sub">Relances automatiques & historique des envois</p>
        </div>
        <div class="header-actions">
          <button class="btn-secondary" @click="confirmTrigger('remind')">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Rappels J-1
          </button>
          <button class="btn-primary" @click="confirmTrigger('day')">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Alertes Jour J
          </button>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-val">{{ stats.total }}</div>
          <div class="stat-label">Total envoyés</div>
        </div>
        <div class="stat-card stat-ok">
          <div class="stat-val">{{ stats.sent }}</div>
          <div class="stat-label">Réussis</div>
        </div>
        <div class="stat-card stat-err">
          <div class="stat-val">{{ stats.failed }}</div>
          <div class="stat-label">Échecs</div>
        </div>
        <div class="stat-card stat-rate">
          <div class="stat-val">{{ successRate }}%</div>
          <div class="stat-label">Taux de succès</div>
        </div>
      </div>

      <!-- TABLEAU LOGS -->
      <div class="table-wrapper">
        <div class="table-head-row">
          <h2 class="table-title">Historique des envois</h2>
          <span class="badge-count">{{ logs.length }} entrées</span>
        </div>
        <table class="logs-table" v-if="logs.length">
          <thead>
            <tr>
              <th>Date</th>
              <th>Agent</th>
              <th>Mission</th>
              <th>Téléphone</th>
              <th>Template</th>
              <th>Déclencheur</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="cell-date">{{ log.sent_at }}</td>
              <td class="cell-name">{{ log.user_name }}</td>
              <td class="cell-mission">{{ log.mission_title }}</td>
              <td class="cell-phone">{{ log.phone_number }}</td>
              <td><span class="template-badge">{{ templateLabel(log.template) }}</span></td>
              <td><span class="trigger-badge" :class="'trig-' + log.trigger">{{ triggerLabel(log.trigger) }}</span></td>
              <td>
                <span class="status-badge" :class="'st-' + log.status">
                  <svg v-if="log.status === 'sent'" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  {{ log.status === 'sent' ? 'Envoyé' : 'Échec' }}
                </span>
                <span v-if="log.error" class="error-hint" :title="log.error">⚠</span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty-state">
          <p>Aucun envoi enregistré pour le moment.</p>
          <p class="empty-sub">Les alertes apparaîtront ici après le premier envoi automatique ou manuel.</p>
        </div>
      </div>

      <!-- MODAL CONFIRMATION -->
      <div v-if="confirming" class="modal-overlay" @click="confirming = null">
        <div class="modal-content" @click.stop>
          <div class="confirm-icon">📱</div>
          <h3 class="confirm-title">Déclencher les {{ confirming === 'remind' ? 'rappels J-1' : 'alertes Jour J' }} ?</h3>
          <p class="confirm-body">
            <template v-if="confirming === 'remind'">
              Un message WhatsApp sera envoyé à tous les agents ayant une mission <strong>demain</strong>.
            </template>
            <template v-else>
              Un message WhatsApp sera envoyé à tous les agents ayant une mission <strong>aujourd'hui</strong>.
            </template>
          </p>
          <div class="modal-actions">
            <button @click="confirming = null" class="btn-cancel">Annuler</button>
            <button @click="doTrigger" class="btn-submit" :disabled="sending">
              {{ sending ? 'Envoi en cours...' : 'Confirmer' }}
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
  name: 'WhatsAppIndex',
  components: { AppLayout },

  props: {
    logs:  { type: Array,  default: () => [] },
    stats: { type: Object, default: () => ({ total: 0, sent: 0, failed: 0 }) },
  },

  data() {
    return { confirming: null, sending: false }
  },

  computed: {
    successRate() {
      if (!this.stats.total) return 0
      return Math.round((this.stats.sent / this.stats.total) * 100)
    },
  },

  methods: {
    confirmTrigger(type) { this.confirming = type },
    doTrigger() {
      if (!this.confirming || this.sending) return
      this.sending = true
      const routeName = this.confirming === 'remind' ? 'whatsapp.reminders' : 'whatsapp.day-alerts'
      router.post(route(routeName), {}, {
        onFinish: () => { this.sending = false; this.confirming = null },
      })
    },
    templateLabel(t) {
      return { mission_reminder: 'Rappel J-1', mission_day_alert: 'Jour J' }[t] ?? t
    },
    triggerLabel(t) {
      return { scheduled: 'Auto', manual: 'Manuel' }[t] ?? t
    },
  },
}
</script>

<style scoped>
.page-wrapper { padding: 32px; max-width: 1300px; }
.page-header  { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
.page-title   { font-size: 24px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.page-sub     { font-size: 14px; color: #6b7280; margin: 0; }
.header-actions { display: flex; gap: 10px; }

.btn-primary, .btn-secondary {
  display: flex; align-items: center; gap: 7px;
  padding: 10px 18px; border: none; border-radius: 10px;
  font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.15s;
}
.btn-primary   { background: #4f6fee; color: white; }
.btn-primary:hover { background: #3d5cdb; }
.btn-secondary { background: #f3f4f6; color: #374151; border: 1.5px solid #e5e7eb; }
.btn-secondary:hover { border-color: #4f6fee; color: #4f6fee; background: #eef2ff; }

/* STATS */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-card {
  background: white; border-radius: 14px; padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,.05); border: 1.5px solid #f3f4f6;
}
.stat-val   { font-size: 32px; font-weight: 800; color: #1a1f2e; line-height: 1; margin-bottom: 6px; }
.stat-label { font-size: 13px; color: #6b7280; font-weight: 500; }
.stat-ok  .stat-val { color: #16a34a; }
.stat-err .stat-val { color: #dc2626; }
.stat-rate .stat-val { color: #4f6fee; }

/* TABLE */
.table-wrapper { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.06); overflow: hidden; }
.table-head-row { display: flex; align-items: center; gap: 12px; padding: 18px 20px 14px; border-bottom: 1px solid #f3f4f6; }
.table-title  { font-size: 15px; font-weight: 700; color: #1a1f2e; margin: 0; }
.badge-count  { font-size: 12px; background: #f3f4f6; color: #6b7280; padding: 2px 10px; border-radius: 20px; font-weight: 600; }

.logs-table { width: 100%; border-collapse: collapse; }
.logs-table th {
  padding: 10px 16px; font-size: 10px; font-weight: 700; color: #9ca3af;
  text-transform: uppercase; letter-spacing: 0.5px; text-align: left;
  background: #f9fafb; border-bottom: 1px solid #e5e7eb;
}
.logs-table td { padding: 11px 16px; font-size: 13px; color: #374151; border-bottom: 1px solid #f3f4f6; }
.logs-table tr:last-child td { border-bottom: none; }
.logs-table tr:hover td { background: #fafafa; }

.cell-date    { color: #6b7280; font-size: 12px; white-space: nowrap; }
.cell-name    { font-weight: 600; color: #1a1f2e; }
.cell-mission { color: #374151; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cell-phone   { font-family: monospace; font-size: 12px; color: #6b7280; }

.template-badge {
  font-size: 11px; font-weight: 600; background: #f3f4f6; color: #374151;
  padding: 2px 8px; border-radius: 20px;
}
.trigger-badge { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.trig-scheduled { background: #eef2ff; color: #4f6fee; }
.trig-manual    { background: #fef3c7; color: #d97706; }

.status-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px;
}
.st-sent   { background: #d1fae5; color: #059669; }
.st-failed { background: #fee2e2; color: #dc2626; }
.error-hint { margin-left: 4px; cursor: help; font-size: 13px; }

.empty-state { padding: 48px 24px; text-align: center; color: #6b7280; }
.empty-state p { margin: 0 0 4px; font-weight: 500; }
.empty-sub { font-size: 13px; color: #9ca3af; }

/* MODAL */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.55);
  display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px;
}
.modal-content {
  background: white; border-radius: 16px; padding: 32px; max-width: 400px;
  width: 90%; text-align: center; box-shadow: 0 24px 64px rgba(0,0,0,0.25);
  animation: slideIn 0.2s ease-out;
}
@keyframes slideIn { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
.confirm-icon  { font-size: 40px; margin-bottom: 12px; }
.confirm-title { font-size: 18px; font-weight: 700; color: #1a1f2e; margin: 0 0 8px; }
.confirm-body  { font-size: 14px; color: #6b7280; margin: 0 0 24px; line-height: 1.6; }
.modal-actions { display: flex; justify-content: center; gap: 12px; }
.btn-cancel, .btn-submit {
  padding: 10px 22px; border-radius: 8px; font-size: 14px; font-weight: 600;
  cursor: pointer; border: none; font-family: inherit; transition: all 0.15s;
}
.btn-cancel { background: #f3f4f6; color: #6b7280; }
.btn-cancel:hover { background: #e5e7eb; }
.btn-submit { background: #4f6fee; color: white; }
.btn-submit:hover:not(:disabled) { background: #3d5cdb; }
.btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }

@media (max-width: 768px) {
  .page-wrapper { padding: 16px; }
  .stats-row { grid-template-columns: 1fr 1fr; }
  .table-wrapper { overflow-x: auto; }
}
</style>
