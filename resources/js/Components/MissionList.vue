<template>
  <div class="missions-page">
    <div class="filters-bar">
      <div class="search-wrapper">
        <span class="search-icon">&#128269;</span>
        <input v-model="search" class="search-input" placeholder="Rechercher une mission..." />
      </div>
      <div class="selects">
        <div class="select-wrapper">
          <select v-model="filterStatus">
            <option value="">Tous les statuts</option>
            <option value="pending">En attente de déploiement</option>
            <option value="in_progress">En opération</option>
            <option value="completed">Accomplie</option>
            <option value="cancelled">Abandonnée</option>
          </select>
          <span class="chevron">v</span>
        </div>
        <div class="select-wrapper">
          <select v-model="filterMember">
            <option value="">Tous les membres</option>
            <option v-for="m in allTeamMembers" :key="m.id" :value="m.name">{{ m.name }}</option>
          </select>
          <span class="chevron">v</span>
        </div>
      </div>
    </div>

    <div class="missions-grid">
      <div
        v-for="mission in filteredMissions"
        :key="mission.id"
        class="mission-card"
        :class="{ 'is-active': mission.status === 'in_progress' }"
        @click="$emit('detail', mission)"
      >
        <div class="card-header">
          <h3 :class="{ 'active-title': mission.status === 'in_progress' }">{{ mission.title }}</h3>
          <div class="card-header-right">
            <span class="status-badge" :class="statusClass(mission.status)">{{ statusLabel(mission.status) }}</span>
            <button v-if="$page.props.auth.can.edit_missions" class="edit-btn" @click.stop="$emit('edit', mission)" title="Modifier">✏️</button>
          </div>
        </div>
        <p class="company">{{ mission.company }}</p>
        <div class="card-details">
          <div class="detail-row"><span class="detail-icon">&#128336;</span><span>{{ formatTime(mission.start_time) }} · {{ formatDuration(mission.duration) }}</span></div>
          <div class="detail-row"><span class="detail-icon">&#128205;</span><span>{{ mission.location }}</span></div>
          <div class="detail-row"><span class="detail-icon">🪖</span><span>{{ mission.users?.map(u => u.name).join(', ') || 'Aucun agent affecté' }}</span></div>
        </div>
        <div class="card-actions" v-if="nextStatus(mission.status) || canCancel(mission.status)">
          <button
            v-if="nextStatus(mission.status)"
            class="action-btn"
            :class="'action-' + nextStatus(mission.status).value"
            @click.stop="changeStatus(mission.id, nextStatus(mission.status).value)"
          >
            {{ nextStatus(mission.status).label }}
          </button>
          <button
            v-if="canCancel(mission.status)"
            class="action-btn action-cancel"
            @click.stop="changeStatus(mission.id, 'cancelled')"
          >
            ✕ Abandonner
          </button>
        </div>
      </div>
    </div>

    <div v-if="filteredMissions.length === 0" class="empty">Aucune opération enregistrée.</div>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'

export default {
  name: 'MissionList',

  props: {
    missions: {
      type: Array,
      default: () => []
    },
    allTeamMembers: {
      type: Array,
      default: () => []
    }
  },

  data() {
    return {
      search: '',
      filterStatus: '',
      filterMember: ''
    }
  },

  computed: {
    allMembers() {
      if (!this.missions) return [];
      
      const names = [];
      this.missions.forEach(mission => {
        if (mission.users) {
          mission.users.forEach(user => {
            if (user.name) names.push(user.name);
          });
        }
      });
      
      // On retire les doublons pour avoir une liste propre
      return [...new Set(names)].sort();
    },
    filteredMissions() {
      const data = this.missions || [];
      
      return data.filter(m => {
        // 1. Filtre par texte (Titre ou Entreprise)
        const searchTerm = this.search.toLowerCase();
        const matchSearch = !this.search ||
          (m.title && m.title.toLowerCase().includes(searchTerm)) ||
          (m.company && m.company.toLowerCase().includes(searchTerm));
        
        // 2. Filtre par statut
        const matchStatus = !this.filterStatus || m.status === this.filterStatus;
        
        // 3. Filtre par membre (On cherche si le membre sélectionné est dans le tableau users)
        const matchMember = !this.filterMember || 
          (m.users && m.users.some(u => u.name === this.filterMember));
        
        return matchSearch && matchStatus && matchMember;
      });
    }
  },

  methods: {
    formatTime(time) {
      return time ? time.substring(0, 5) : ''
    },
    formatDuration(d) {
      if (!d) return ''
      const h = Math.floor(d)
      const m = Math.round((d - h) * 60)
      if (h === 0) return `${m}min`
      return m > 0 ? `${h}h${m}` : `${h}h`
    },
    nextStatus(current) {
      const map = {
        pending:     { value: 'in_progress', label: '▶ Déployer' },
        in_progress: { value: 'completed',   label: '✓ Mission accomplie' },
      }
      return map[current] || null
    },
    canCancel(status) {
      return status === 'pending' || status === 'in_progress'
    },
    changeStatus(id, status) {
      router.patch(route('missions.updateStatus', id), { status }, {
        preserveScroll: true,
      })
    },
    statusLabel(status) {
      const map = {
        'pending':     'En attente',
        'in_progress': 'En opération',
        'completed':   'Accomplie',
        'cancelled':   'Abandonnée',
      }
      return map[status] || status
    },
    statusClass(status) {
      const map = {
        'pending':     'badge-planned',
        'in_progress': 'badge-active',
        'completed':   'badge-done',
        'cancelled':   'badge-cancelled',
      }
      return map[status] || ''
    }
  }
}
</script>

<style scoped>
.missions-page {
  background: #f6f8fc;
  padding: 24px;
  min-height: 100vh;
  font-family: 'Segoe UI', sans-serif;
}
.filters-bar {
  display: flex;
  gap: 16px;
  align-items: center;
  margin-bottom: 28px;
}
.search-wrapper {
  flex: 1;
  position: relative;
}
.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #aaa;
}
.search-input {
  width: 100%;
  padding: 13px 16px 13px 42px;
  border: 1.5px solid #e8eaf0;
  border-radius: 12px;
  background: white;
  font-size: 14px;
  color: #333;
  outline: none;
  box-sizing: border-box;
  transition: border-color 0.2s;
}
.search-input:focus {
  border-color: #4f6fee;
}
.selects {
  display: flex;
  gap: 12px;
}
.select-wrapper {
  position: relative;
}
.select-wrapper select {
  appearance: none;
  padding: 13px 36px 13px 16px;
  border: 1.5px solid #e8eaf0;
  border-radius: 12px;
  background: white;
  font-size: 14px;
  color: #333;
  cursor: pointer;
  outline: none;
  min-width: 170px;
}
.chevron {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #999;
  font-size: 12px;
}
.missions-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
.mission-card {
  background: white;
  border-radius: 16px;
  padding: 22px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  border: 1.5px solid transparent;
  transition: box-shadow 0.2s, transform 0.2s;
}
.mission-card:hover {
  box-shadow: 0 6px 24px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}
.mission-card.is-active {
  border-left: 4px solid #4f6fee;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 6px;
}
.card-header-right {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.edit-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  padding: 2px 4px;
  border-radius: 6px;
  opacity: 0;
  transition: opacity 0.15s, background 0.15s;
  line-height: 1;
}
.mission-card:hover .edit-btn { opacity: 1; }
.edit-btn:hover { background: #f3f4f6; }
.card-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
}
.active-title {
  color: #4f6fee !important;
}
.company {
  font-size: 13px;
  font-weight: 600;
  color: #666;
  margin: 0 0 18px;
}
.status-badge {
  flex-shrink: 0;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.badge-planned   { background: #eef2ff; color: #4f6fee; }
.badge-active    { background: #fff4e5; color: #f59e0b; }
.badge-done      { background: #e8f8ef; color: #22c55e; }
.badge-cancelled { background: #fef2f2; color: #ef4444; }
.card-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.detail-row {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: #444;
}
.detail-icon {
  width: 20px;
  text-align: center;
  opacity: 0.6;
}
.card-actions {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid #f3f4f6;
}
.action-btn {
  width: 100%;
  padding: 8px 12px;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s;
}
.action-btn:hover { opacity: 0.85; }
.action-in_progress { background: #eef2ff; color: #4f6fee; }
.action-completed   { background: #e8f8ef; color: #22c55e; }
.action-cancel      { background: #fef2f2; color: #ef4444; }

.empty {
  text-align: center;
  padding: 60px;
  color: #999;
  font-size: 15px;
}
@media (max-width: 1024px) {
  .missions-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .missions-grid { grid-template-columns: 1fr; }
  .filters-bar { flex-direction: column; }
}
</style>
