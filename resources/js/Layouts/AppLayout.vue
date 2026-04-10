<template>
  <div class="app-shell">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <div class="logo-icon">📅</div>
        <div class="logo-text">
          <span class="logo-name">MissionFlow</span>
          <span class="logo-sub">Commandement & Opérations</span>
        </div>
      </div>

      <nav class="sidebar-nav">
        <Link :href="route('home')" class="nav-item" :class="{ active: route().current('home') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span>Centre Opérationnel</span>
        </Link>
        <Link :href="route('missions.index')" class="nav-item" :class="{ active: route().current('missions.*') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span>Opérations</span>
        </Link>
        <Link :href="route('personnel.index')" class="nav-item" :class="{ active: route().current('personnel.*') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>Personnel</span>
        </Link>
        <Link :href="route('profile.edit')" class="nav-item" :class="{ active: route().current('profile.*') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Dossier Personnel</span>
        </Link>
        <Link v-if="$page.props.auth.can.edit_missions" :href="route('reports.index')" class="nav-item" :class="{ active: route().current('reports.*') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          <span>Rapports</span>
        </Link>
        <Link v-if="$page.props.auth.can.manage_users" :href="route('activity.index')" class="nav-item" :class="{ active: route().current('activity.*') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          <span>Journal</span>
        </Link>
        <Link v-if="$page.props.auth.can.manage_users" :href="route('users.index')" class="nav-item" :class="{ active: route().current('users.*') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/><path d="M18 14l2 2 4-4"/></svg>
          <span>Administration</span>
        </Link>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">{{ initials }}</div>
          <div class="user-details">
            <span class="user-name">{{ $page.props.auth.user.name }}</span>
            <span class="user-role">{{ $page.props.auth.user.role || 'Utilisateur' }}</span>
          </div>
        </div>
        <Link :href="route('logout')" method="post" as="button" class="logout-btn">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </Link>
      </div>
    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

      <!-- TOP BAR -->
      <header class="topbar">
        <div class="topbar-left">
          <div class="topbar-page">{{ pageTitle }}</div>
          <div class="topbar-date">{{ currentDate }}</div>
        </div>
        <div class="topbar-right">
          <GlobalSearch />
          <div class="topbar-user">
            <div class="topbar-avatar">{{ initials }}</div>
            <div class="topbar-user-info">
              <span class="topbar-user-name">{{ $page.props.auth.user.name }}</span>
              <span class="topbar-user-role">{{ $page.props.auth.user.role || 'Utilisateur' }}</span>
            </div>
          </div>
        </div>
      </header>

      <div class="page-content">
        <slot />
      </div>
    </main>

    <!-- TOAST -->
    <Transition name="toast">
      <div v-if="toast" class="toast" :class="'toast-' + toastType">
        <span class="toast-icon">{{ toastType === 'success' ? '✓' : '✕' }}</span>
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import GlobalSearch from '@/Components/GlobalSearch.vue'

const page = usePage()

const toast    = ref(null)
const toastType = ref('success')
let toastTimer = null

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success || flash?.error) {
      clearTimeout(toastTimer)
      toast.value    = flash.success || flash.error
      toastType.value = flash.success ? 'success' : 'error'
      toastTimer = setTimeout(() => { toast.value = null }, 3500)
    }
  },
  { deep: true }
)

const initials = computed(() => {
  const name = page.props.auth?.user?.name || ''
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
})

const pageTitle = computed(() => {
  const titles = {
    'home':             'Centre Opérationnel',
    'missions.index':   'Opérations',
    'personnel.index':  'Personnel',
    'profile.edit':     'Dossier Personnel',
    'reports.index':    'Rapports',
    'activity.index':   'Journal d\'activité',
    'users.index':      'Administration',
  }
  for (const [routeName, title] of Object.entries(titles)) {
    if (route().current(routeName)) return title
  }
  return 'MissionFlow'
})

const currentDate = computed(() => {
  return new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
})
</script>

<style scoped>
.app-shell {
  display: flex;
  min-height: 100vh;
  background: #f6f8fc;
}

/* ─── SIDEBAR ─────────────────────────────── */
.sidebar {
  width: 240px;
  min-width: 240px;
  background: #1e1f2e;
  display: flex;
  flex-direction: column;
  padding: 24px 16px;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 36px;
  padding: 0 8px;
}

.logo-icon {
  width: 40px;
  height: 40px;
  background: #4f6fee;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.logo-name {
  display: block;
  font-size: 15px;
  font-weight: 700;
  color: #fff;
}

.logo-sub {
  display: block;
  font-size: 11px;
  color: #6b7280;
}

/* ─── NAV ─────────────────────────────────── */
.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 12px;
  border-radius: 10px;
  color: #9ca3af;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.15s;
}

.nav-item svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #e5e7eb;
}

.nav-item.active {
  background: #4f6fee;
  color: #fff;
}

/* ─── FOOTER ──────────────────────────────── */
.sidebar-footer {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 8px;
  border-top: 1px solid rgba(255, 255, 255, 0.07);
  margin-top: 16px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
}

.user-avatar {
  width: 36px;
  height: 36px;
  min-width: 36px;
  background: #4f6fee;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 13px;
  font-weight: 700;
}

.user-details {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.user-name {
  font-size: 13px;
  font-weight: 600;
  color: #e5e7eb;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  font-size: 11px;
  color: #6b7280;
}

.logout-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  padding: 6px;
  border-radius: 8px;
  display: flex;
  transition: color 0.15s, background 0.15s;
}

.logout-btn:hover {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

.logout-btn svg {
  width: 18px;
  height: 18px;
}

/* ─── MAIN ────────────────────────────────── */
.main-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
}

.page-content {
  flex: 1;
  overflow-y: auto;
}

/* ─── TOP BAR ─────────────────────────────── */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  height: 64px;
  min-height: 64px;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
  z-index: 100;
}

.topbar-left {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.topbar-page {
  font-size: 16px;
  font-weight: 700;
  color: #1a1f2e;
  line-height: 1;
}

.topbar-date {
  font-size: 12px;
  color: #9ca3af;
  text-transform: capitalize;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.topbar-user {
  display: flex;
  align-items: center;
  gap: 10px;
}

.topbar-avatar {
  width: 36px;
  height: 36px;
  background: #4f6fee;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}

.topbar-user-info {
  display: flex;
  flex-direction: column;
}

.topbar-user-name {
  font-size: 13px;
  font-weight: 600;
  color: #1a1f2e;
}

.topbar-user-role {
  font-size: 11px;
  color: #9ca3af;
}

/* ─── TOAST ───────────────────────────────── */
.toast {
  position: fixed;
  bottom: 28px;
  right: 28px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  max-width: 360px;
}

.toast-success {
  background: #1a1f2e;
  color: #fff;
  border-left: 4px solid #22c55e;
}

.toast-error {
  background: #1a1f2e;
  color: #fff;
  border-left: 4px solid #ef4444;
}

.toast-icon {
  font-size: 16px;
  font-weight: 700;
}

.toast-success .toast-icon { color: #22c55e; }
.toast-error  .toast-icon  { color: #ef4444; }

.toast-enter-active, .toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

/* ─── RESPONSIVE ──────────────────────────── */
@media (max-width: 768px) {
  .sidebar {
    width: 60px;
    min-width: 60px;
    padding: 16px 8px;
  }
  .logo-text, .nav-item span, .user-details, .logo-sub { display: none; }
  .sidebar-logo { justify-content: center; margin-bottom: 24px; padding: 0; }
  .nav-item { justify-content: center; padding: 12px; }
  .user-info { justify-content: center; }
}
</style>
