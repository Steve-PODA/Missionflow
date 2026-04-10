<template>
  <div class="search-wrapper" ref="wrapper">
    <!-- Input -->
    <div class="search-box" :class="{ focused: open }">
      <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input
        ref="input"
        v-model="query"
        type="text"
        placeholder="Rechercher..."
        class="search-input"
        @focus="open = true"
        @keydown.escape="close"
        @input="onInput"
      />
      <button v-if="query" class="search-clear" @click="clear">✕</button>
    </div>

    <!-- Dropdown -->
    <div v-if="open && (results.missions.length || results.personnel.length || loading || query.length >= 2)" class="search-dropdown">

      <div v-if="loading" class="search-loading">Recherche...</div>

      <template v-else>
        <!-- Missions -->
        <div v-if="results.missions.length">
          <div class="dropdown-section">Opérations</div>
          <div
            v-for="m in results.missions" :key="'m' + m.id"
            class="dropdown-item"
            @click="goToMissions(m)"
          >
            <span class="item-dot" :class="'dot-' + m.priority"></span>
            <div class="item-body">
              <span class="item-title">{{ m.title }}</span>
              <span class="item-sub">{{ m.company }} · {{ formatDate(m.date) }}</span>
            </div>
            <span class="item-badge" :class="statusClass(m.status)">{{ statusLabel(m.status) }}</span>
          </div>
        </div>

        <!-- Personnel -->
        <div v-if="results.personnel.length">
          <div class="dropdown-section">Personnel</div>
          <div
            v-for="u in results.personnel" :key="'u' + u.id"
            class="dropdown-item"
            @click="goToPersonnel"
          >
            <div class="item-avatar" :style="{ background: getColor(u.name) }">{{ getInitials(u.name) }}</div>
            <div class="item-body">
              <span class="item-title">{{ u.name }}</span>
              <span class="item-sub">{{ u.role || u.email }}</span>
            </div>
          </div>
        </div>

        <!-- Aucun résultat -->
        <div v-if="!results.missions.length && !results.personnel.length && query.length >= 2" class="search-empty">
          Aucun résultat pour « {{ query }} »
        </div>
      </template>
    </div>
  </div>

  <!-- Overlay pour fermer -->
  <div v-if="open" class="search-overlay" @click="close"></div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const query   = ref('')
const open    = ref(false)
const loading = ref(false)
const results = reactive({ missions: [], personnel: [] })
const input   = ref(null)
const wrapper = ref(null)

let debounceTimer = null

function onInput() {
  clearTimeout(debounceTimer)
  results.missions  = []
  results.personnel = []

  if (query.value.length < 2) {
    loading.value = false
    return
  }

  loading.value = true
  debounceTimer = setTimeout(async () => {
    try {
      const { data } = await axios.get(route('search'), { params: { q: query.value } })
      results.missions  = data.missions  ?? []
      results.personnel = data.personnel ?? []
    } finally {
      loading.value = false
    }
  }, 300)
}

function close() {
  open.value  = false
  query.value = ''
  results.missions  = []
  results.personnel = []
}

function clear() {
  query.value = ''
  results.missions  = []
  results.personnel = []
  input.value?.focus()
}

function goToMissions(mission) {
  close()
  router.visit(route('missions.index'))
}

function goToPersonnel() {
  close()
  router.visit(route('personnel.index'))
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date + 'T00:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}
function statusLabel(s) {
  return { pending: 'En attente', in_progress: 'En cours', completed: 'Accomplie', cancelled: 'Abandonnée' }[s] ?? s
}
function statusClass(s) {
  return { pending: 'badge-pending', in_progress: 'badge-active', completed: 'badge-done', cancelled: 'badge-cancelled' }[s] ?? ''
}
function getInitials(name) {
  return name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() ?? '??'
}
function getColor(name) {
  let hash = 0
  for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return `hsl(${Math.abs(hash) % 360}, 50%, 38%)`
}

// Raccourci clavier Ctrl+K / Cmd+K
function onKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    open.value = true
    input.value?.focus()
  }
}
onMounted(()  => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
.search-wrapper {
  position: relative;
  z-index: 200;
}
.search-overlay {
  position: fixed;
  inset: 0;
  z-index: 199;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f3f4f6;
  border: 1.5px solid transparent;
  border-radius: 10px;
  padding: 0 12px;
  height: 38px;
  width: 220px;
  transition: all 0.15s;
}
.search-box.focused {
  background: white;
  border-color: #4f6fee;
  width: 280px;
  box-shadow: 0 0 0 3px rgba(79,111,238,0.1);
}
.search-icon  { width: 15px; height: 15px; color: #9ca3af; flex-shrink: 0; }
.search-input {
  flex: 1; border: none; outline: none;
  font-size: 13px; color: #1a1f2e;
  background: transparent; font-family: inherit;
}
.search-input::placeholder { color: #9ca3af; }
.search-clear {
  background: none; border: none; cursor: pointer;
  color: #9ca3af; font-size: 11px; padding: 2px;
  line-height: 1; transition: color 0.15s;
}
.search-clear:hover { color: #374151; }

.search-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  min-width: 340px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 12px 40px rgba(0,0,0,0.15);
  overflow: hidden;
  animation: fadeIn 0.15s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}

.search-loading, .search-empty {
  padding: 16px;
  font-size: 13px;
  color: #9ca3af;
  text-align: center;
}

.dropdown-section {
  padding: 8px 14px 4px;
  font-size: 11px;
  font-weight: 700;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: #f9fafb;
  border-bottom: 1px solid #f3f4f6;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  cursor: pointer;
  border-bottom: 1px solid #f9fafb;
  transition: background 0.1s;
}
.dropdown-item:hover { background: #f9fafb; }
.dropdown-item:last-child { border-bottom: none; }

.item-dot {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.dot-urgent { background: #dc2626; }
.dot-high   { background: #ea580c; }
.dot-medium { background: #4f6fee; }
.dot-low    { background: #22c55e; }

.item-avatar {
  width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 10px; font-weight: 700;
}
.item-body  { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.item-title { font-size: 13px; font-weight: 600; color: #1a1f2e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.item-sub   { font-size: 11px; color: #9ca3af; margin-top: 1px; }

.item-badge {
  font-size: 10px; font-weight: 700;
  padding: 2px 7px; border-radius: 20px; flex-shrink: 0;
}
.badge-pending   { background: #eef2ff; color: #4f6fee; }
.badge-active    { background: #fff4e5; color: #f59e0b; }
.badge-done      { background: #e8f8ef; color: #22c55e; }
.badge-cancelled { background: #fef2f2; color: #ef4444; }
</style>
