<template>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="sidebar-header">
        <img src="/images/drapeau.png" alt="Drapeau Burkina Faso" class="drapeau-img"/>
        <div class="sidebar-logo">
          <div class="logo-icon">📅</div>
          <div class="logo-text">
            <span class="logo-name">MissionFlow</span>
            <span class="logo-sub">Commandement & Opérations</span>
          </div>
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
        <Link v-if="$page.props.auth.can.manage_users" :href="route('whatsapp.index')" class="nav-item" :class="{ active: route().current('whatsapp.*') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <span>WhatsApp</span>
        </Link>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar" @click="toggleAvatarMenu" title="Modifier la photo">
            <img v-if="$page.props.auth.user.avatar" :src="'/storage/' + $page.props.auth.user.avatar" :alt="$page.props.auth.user.name" class="avatar-img" />
            <span v-else>{{ initials }}</span>
            <div class="avatar-overlay">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
          </div>

          <Transition name="avatar-menu">
            <div v-if="showAvatarMenu" class="avatar-menu">
              <button class="avatar-menu-item" @click="triggerAvatarInput">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Changer la photo
              </button>
              <button v-if="$page.props.auth.user.avatar" class="avatar-menu-item avatar-menu-item--danger" @click="deleteAvatar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                Supprimer la photo
              </button>
            </div>
          </Transition>

          <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/webp" class="avatar-hidden-input" @change="uploadAvatar" />
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

    <main class="main-content">
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

      <footer class="footer">
        <span>◆ MissionFlow v2.1 — Gendarmerie Nationale du Burkina Faso</span>
        <div style="display:flex;gap:8px">
          <span class="footer-badge">Réseau sécurisé</span>
          <span class="footer-badge">{{ new Date().getFullYear() }}</span>
        </div>
      </footer>
    </main>

    <Transition name="toast">
      <div v-if="toast" class="toast" :class="'toast-' + toastType">
        <span class="toast-icon">{{ toastType === 'success' ? '✓' : '✕' }}</span>
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, usePage, useForm, router } from '@inertiajs/vue3'
import GlobalSearch from '@/Components/GlobalSearch.vue'

const page           = usePage()
const avatarInput    = ref(null)
const showAvatarMenu = ref(false)
const avatarForm     = useForm({ avatar: null })

function toggleAvatarMenu() { showAvatarMenu.value = !showAvatarMenu.value }

function closeAvatarMenu(e) {
  if (!e.target.closest('.user-avatar') && !e.target.closest('.avatar-menu')) {
    showAvatarMenu.value = false
  }
}

onMounted(() => document.addEventListener('click', closeAvatarMenu))
onUnmounted(() => document.removeEventListener('click', closeAvatarMenu))

function triggerAvatarInput() {
  showAvatarMenu.value = false
  avatarInput.value.click()
}

function uploadAvatar(e) {
  const file = e.target.files[0]
  if (!file) return
  avatarForm.avatar = file
  avatarForm.post(route('profile.avatar'), {
    forceFormData: true,
    onSuccess: () => { avatarInput.value.value = '' },
    onError:   () => { avatarInput.value.value = '' },
  })
}

function deleteAvatar() {
  showAvatarMenu.value = false
  router.delete(route('profile.avatar.delete'))
}

const toast     = ref(null)
const toastType = ref('success')
let toastTimer  = null

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success || flash?.error) {
      clearTimeout(toastTimer)
      toast.value     = flash.success || flash.error
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
    'home':            'Centre Opérationnel',
    'missions.index':  'Opérations',
    'personnel.index': 'Personnel',
    'profile.edit':    'Dossier Personnel',
    'reports.index':   'Rapports',
    'activity.index':  "Journal d'activité",
    'users.index':     'Administration',
  }
  for (const [routeName, title] of Object.entries(titles)) {
    if (route().current(routeName)) return title
  }
  return 'MissionFlow'
})

const currentDate = computed(() => {
  return new Date().toLocaleDateString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
  })
})
</script>

<style scoped>
/* ══════════════════════════════════════════════
   GENDARMERIE NATIONALE BURKINA FASO
   Fond nuit  : #2A1F5C
   Cartes/badges : #2A1F5C (même bleu sombre)
   Textes     : #EDE7F6 (blanc lavande)
   Accent     : #5B4A9E · Bordures : #5B4A9E
══════════════════════════════════════════════ */

.app-shell {
  display: flex;
  min-height: 100vh;
  background-color: #f3f4f6;
}

.sidebar, .main-content { position: relative; z-index: 1; }

.sidebar, .main-content { position: relative; z-index: 1; }

/* ─── SIDEBAR ───────────────────────────────── */
.sidebar {
  width: 240px;
  min-width: 240px;
  background: rgba(20,14,50,0.97);
  display: flex;
  flex-direction: column;
  padding: 24px 16px;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  border-right: 2px solid #5B4A9E;
}

.sidebar-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 8px;
}

.drapeau-img { width: 160px; height: auto; object-fit: contain; margin-bottom: 10px; }

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 28px;
  padding: 0 8px;
}

.logo-icon {
  width: 40px; height: 40px;
  background: #2A1F5C;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; flex-shrink: 0;
  border: 1px solid rgba(91,74,158,0.6);
}

.logo-name { display: block; font-size: 15px; font-weight: 700; color: #EDE7F6; letter-spacing: 0.5px; }
.logo-sub  { display: block; font-size: 11px; color: #EDE7F6; opacity: 0.65; }

/* ─── NAV ───────────────────────────────────── */
.sidebar-nav { display: flex; flex-direction: column; gap: 4px; flex: 1; }

.nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 12px; border-radius: 10px;
  color: #EDE7F6; text-decoration: none;
  font-size: 14px; font-weight: 500;
  transition: all 0.15s; opacity: 0.75;
}

.nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
.nav-item:hover { background: rgba(91,74,158,0.30); opacity: 1; }
.nav-item.active {
  background: #2A1F5C;
  color: #EDE7F6; opacity: 1;
  border: 1px solid rgba(91,74,158,0.6);
}

/* ─── SIDEBAR FOOTER ────────────────────────── */
.sidebar-footer {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 8px;
  border-top: 1px solid rgba(91,74,158,0.4);
  margin-top: 16px;
}

.user-info { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; position: relative; }

.user-avatar {
  width: 36px; height: 36px; min-width: 36px;
  background: #2A1F5C; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #EDE7F6; font-size: 13px; font-weight: 700;
  border: 1px solid rgba(91,74,158,0.6);
  overflow: hidden; position: relative; cursor: pointer;
}

.avatar-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

.avatar-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,0.45); border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.15s;
}
.avatar-overlay svg { width: 14px; height: 14px; color: #fff; }
.user-avatar:hover .avatar-overlay { opacity: 1; }
.avatar-hidden-input { display: none; }

.avatar-menu {
  position: absolute; bottom: 50px; left: 0;
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  padding: 6px; display: flex; flex-direction: column;
  gap: 2px; min-width: 180px; z-index: 200;
}
.avatar-menu-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px; border: none; background: none;
  border-radius: 7px; font-size: 13px; font-weight: 500;
  color: #374151; cursor: pointer; text-align: left;
  transition: background 0.12s; width: 100%;
}
.avatar-menu-item svg { width: 15px; height: 15px; flex-shrink: 0; }
.avatar-menu-item:hover { background: #f3f4f6; }
.avatar-menu-item--danger { color: #ef4444; }
.avatar-menu-item--danger:hover { background: #fef2f2; }
.avatar-menu-enter-active, .avatar-menu-leave-active { transition: opacity 0.15s, transform 0.15s; }
.avatar-menu-enter-from, .avatar-menu-leave-to { opacity: 0; transform: translateY(6px); }

.user-details { display: flex; flex-direction: column; min-width: 0; }
.user-name { font-size: 13px; font-weight: 600; color: #EDE7F6; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role { font-size: 11px; color: #EDE7F6; opacity: 0.65; }

.logout-btn {
  background: none; border: none; cursor: pointer;
  color: #EDE7F6; opacity: 0.6; padding: 6px;
  border-radius: 8px; display: flex; transition: all 0.15s;
}
.logout-btn:hover { color: #ef4444; opacity: 1; background: rgba(239,68,68,0.15); }
.logout-btn svg { width: 18px; height: 18px; }

/* ─── MAIN ──────────────────────────────────── */
.main-content { flex: 1; min-width: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
.page-content { flex: 1; overflow-y: auto; }

/* ─── TOP BAR ───────────────────────────────── */
.topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 32px; height: 64px; min-height: 64px;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0; z-index: 100;
}

.topbar-left { display: flex; flex-direction: column; gap: 2px; }
.topbar-page { font-size: 16px; font-weight: 700; color: #111827; line-height: 1; letter-spacing: 0.3px; }
.topbar-date { font-size: 12px; color: #6b7280; text-transform: capitalize; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.topbar-user  { display: flex; align-items: center; gap: 10px; }

.topbar-avatar {
  width: 36px; height: 36px;
  background: #eef2ff; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #4f6fee; font-size: 13px; font-weight: 700;
  flex-shrink: 0; border: 1px solid #c7d2fe;
}

.topbar-user-info { display: flex; flex-direction: column; }
.topbar-user-name { font-size: 13px; font-weight: 600; color: #111827; }
.topbar-user-role { font-size: 11px; color: #6b7280; }

/* ─── FOOTER ────────────────────────────────── */
.footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 32px;
  background: #fff;
  border-top: 1px solid #e5e7eb;
  flex-shrink: 0;
}

.footer span { font-size: 11px; color: #6b7280; letter-spacing: 0.3px; }

.footer-badge {
  background: #f3f4f6 !important;
  color: #374151 !important;
  font-size: 10px !important;
  padding: 3px 10px;
  border-radius: 20px;
  border: 1px solid #e5e7eb;
  opacity: 1 !important;
}

/* ─── TOAST ─────────────────────────────────── */
.toast {
  position: fixed; bottom: 28px; right: 28px;
  display: flex; align-items: center; gap: 10px;
  padding: 14px 20px; border-radius: 12px;
  font-size: 14px; font-weight: 500;
  box-shadow: 0 8px 30px rgba(42,31,92,0.4);
  z-index: 9999; max-width: 360px;
}

.toast-success { background: #2A1F5C; color: #EDE7F6; border-left: 4px solid #7B6BC4; }
.toast-error   { background: #2A1F5C; color: #EDE7F6; border-left: 4px solid #ef4444; }
.toast-icon    { font-size: 16px; font-weight: 700; }
.toast-success .toast-icon { color: #7B6BC4; }
.toast-error   .toast-icon { color: #ef4444; }

.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px); }

/* ─── RESPONSIVE ────────────────────────────── */
@media (max-width: 768px) {
  .sidebar { width: 60px; min-width: 60px; padding: 16px 8px; }
  .logo-text, .nav-item span, .user-details, .logo-sub { display: none; }
  .sidebar-logo { justify-content: center; margin-bottom: 24px; padding: 0; }
  .nav-item { justify-content: center; padding: 12px; }
  .user-info { justify-content: center; }
  .drapeau-img { width: 36px; }
}
</style>
