<template>
  <AppLayout>
    <div class="page-wrapper">

      <!-- EN-TÊTE -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Journal d'activité</h1>
          <p class="page-sub">{{ activities.length }} action{{ activities.length > 1 ? 's' : '' }} enregistrée{{ activities.length > 1 ? 's' : '' }}</p>
        </div>
      </div>

      <!-- FILTRES -->
      <div class="filters-bar">
        <div class="filter-group">
          <label class="filter-label">Catégorie</label>
          <div class="filter-tabs">
            <button
              v-for="tab in logTabs" :key="tab.value"
              class="filter-tab"
              :class="{ active: activeLog === tab.value }"
              @click="setFilter('log_name', tab.value)"
            >
              <span>{{ tab.icon }}</span> {{ tab.label }}
            </button>
          </div>
        </div>
        <div class="filter-group">
          <label class="filter-label">Agent</label>
          <select class="filter-select" :value="filters.causer_id || ''" @change="setFilter('causer_id', $event.target.value || null)">
            <option value="">Tous les agents</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>
        </div>
      </div>

      <!-- TIMELINE -->
      <div class="timeline" v-if="activities.length">
        <div v-for="(group, date) in groupedActivities" :key="date" class="timeline-group">
          <div class="timeline-date">{{ formatGroupDate(date) }}</div>
          <div class="timeline-items">
            <div v-for="a in group" :key="a.id" class="timeline-item">
              <!-- Icône catégorie -->
              <div class="item-icon" :class="'icon-' + a.log_name">
                {{ logIcon(a.log_name) }}
              </div>
              <!-- Contenu -->
              <div class="item-body">
                <div class="item-description">{{ a.description }}</div>
                <div class="item-meta">
                  <span v-if="a.causer" class="item-causer">
                    <span class="causer-avatar" :style="{ background: getColor(a.causer.name) }">{{ getInitials(a.causer.name) }}</span>
                    {{ a.causer.name }}
                    <span v-if="a.causer.role" class="causer-role">· {{ a.causer.role }}</span>
                  </span>
                  <span v-else class="item-causer text-gray">Système</span>
                  <span class="item-time">{{ formatTime(a.created_at) }}</span>
                </div>
                <!-- Propriétés (avant/après) -->
                <div v-if="hasChanges(a)" class="item-changes">
                  <template v-if="a.properties.old && a.properties.new">
                    <span class="change-old">{{ a.properties.old }}</span>
                    <span class="change-arrow">→</span>
                    <span class="change-new">{{ a.properties.new }}</span>
                  </template>
                  <template v-else-if="a.properties.attributes">
                    <span v-for="(val, key) in filteredAttributes(a.properties.attributes)" :key="key" class="change-attr">
                      <span class="attr-key">{{ key }}</span> : {{ val }}
                    </span>
                  </template>
                </div>
              </div>
              <!-- Badge catégorie -->
              <span class="item-badge" :class="'badge-' + a.log_name">{{ logLabel(a.log_name) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty">Aucune activité enregistrée.</div>

    </div>
  </AppLayout>
</template>

<script>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'ActivityIndex',
  components: { AppLayout },

  props: {
    activities: { type: Array,  default: () => [] },
    users:      { type: Array,  default: () => [] },
    filters:    { type: Object, default: () => ({}) },
  },

  data() {
    return {
      logTabs: [
        { value: null,       label: 'Tout',       icon: '📋' },
        { value: 'mission',  label: 'Opérations', icon: '🎯' },
        { value: 'user',     label: 'Comptes',    icon: '👤' },
        { value: 'personnel',label: 'Personnel',  icon: '🪖' },
      ],
    }
  },

  computed: {
    activeLog() {
      return this.filters.log_name || null
    },
    groupedActivities() {
      const groups = {}
      for (const a of this.activities) {
        const date = a.created_at.split(' ')[0]
        if (!groups[date]) groups[date] = []
        groups[date].push(a)
      }
      return groups
    },
  },

  methods: {
    setFilter(key, value) {
      const params = { ...this.filters, [key]: value || undefined }
      router.get(route('activity.index'), params, { preserveScroll: true, replace: true })
    },
    formatGroupDate(dateStr) {
      const date  = new Date(dateStr + 'T00:00:00')
      const today = new Date()
      const yesterday = new Date()
      yesterday.setDate(today.getDate() - 1)
      if (date.toDateString() === today.toDateString())     return 'Aujourd\'hui'
      if (date.toDateString() === yesterday.toDateString()) return 'Hier'
      return date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
    },
    formatTime(datetime) {
      return new Date(datetime).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    },
    logIcon(name) {
      return { mission: '🎯', user: '👤', personnel: '🪖' }[name] ?? '📋'
    },
    logLabel(name) {
      return { mission: 'Opération', user: 'Compte', personnel: 'Personnel' }[name] ?? name
    },
    hasChanges(a) {
      return (a.properties?.old !== undefined && a.properties?.new !== undefined)
          || (a.properties?.attributes && Object.keys(a.properties.attributes).length > 0)
    },
    filteredAttributes(attrs) {
      const skip = ['id', 'created_at', 'updated_at', 'password', 'remember_token']
      return Object.fromEntries(Object.entries(attrs).filter(([k]) => !skip.includes(k)))
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
.page-wrapper { padding: 32px; max-width: 960px; }
.page-header  { margin-bottom: 24px; }
.page-title   { font-size: 24px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.page-sub     { font-size: 14px; color: #6b7280; margin: 0; }

/* FILTRES */
.filters-bar  { display: flex; gap: 24px; align-items: flex-end; margin-bottom: 28px; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 6px; }
.filter-label { font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
.filter-tabs  { display: flex; gap: 6px; flex-wrap: wrap; }
.filter-tab {
  display: flex; align-items: center; gap: 6px;
  padding: 7px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  background: white; font-size: 13px; font-weight: 600; color: #6b7280;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.filter-tab:hover  { border-color: #4f6fee; color: #4f6fee; }
.filter-tab.active { background: #4f6fee; border-color: #4f6fee; color: white; }
.filter-select {
  padding: 8px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  background: white; font-size: 13px; color: #374151;
  font-family: inherit; outline: none; cursor: pointer;
  transition: border-color 0.15s; min-width: 200px;
}
.filter-select:focus { border-color: #4f6fee; }

/* TIMELINE */
.timeline { display: flex; flex-direction: column; gap: 28px; }

.timeline-date {
  font-size: 12px; font-weight: 700; color: #9ca3af;
  text-transform: capitalize; letter-spacing: 0.5px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f3f4f6;
  margin-bottom: 4px;
}

.timeline-items { display: flex; flex-direction: column; gap: 2px; }

.timeline-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  border-radius: 12px;
  transition: background 0.1s;
}
.timeline-item:hover { background: #f9fafb; }

.item-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; flex-shrink: 0;
}
.icon-mission   { background: #eef2ff; }
.icon-user      { background: #fef3c7; }
.icon-personnel { background: #d1fae5; }

.item-body { flex: 1; min-width: 0; }
.item-description { font-size: 14px; font-weight: 600; color: #1a1f2e; margin-bottom: 4px; }

.item-meta {
  display: flex; align-items: center; gap: 12px;
  font-size: 12px; color: #6b7280; flex-wrap: wrap;
}
.item-causer { display: flex; align-items: center; gap: 6px; }
.causer-avatar {
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 9px; font-weight: 700;
}
.causer-role { color: #9ca3af; }
.item-time   { color: #9ca3af; margin-left: auto; white-space: nowrap; }
.text-gray   { color: #9ca3af; }

.item-changes {
  display: flex; align-items: center; gap: 6px;
  margin-top: 6px; flex-wrap: wrap;
}
.change-old  { background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.change-new  { background: #d1fae5; color: #059669; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.change-arrow { color: #9ca3af; font-size: 12px; }
.change-attr {
  background: #f3f4f6; color: #374151;
  padding: 2px 8px; border-radius: 4px; font-size: 12px;
}
.attr-key { font-weight: 700; }

.item-badge {
  font-size: 10px; font-weight: 700; padding: 3px 9px;
  border-radius: 20px; text-transform: uppercase;
  letter-spacing: 0.4px; flex-shrink: 0; align-self: flex-start; margin-top: 2px;
}
.badge-mission   { background: #eef2ff; color: #4f6fee; }
.badge-user      { background: #fef3c7; color: #d97706; }
.badge-personnel { background: #d1fae5; color: #059669; }

.empty { font-size: 14px; color: #9ca3af; text-align: center; padding: 60px; }

@media (max-width: 640px) {
  .page-wrapper { padding: 16px; }
  .item-time { margin-left: 0; }
}
</style>
