<template>
  <AppLayout>
    <div class="page-wrapper">

      <!-- EN-TÊTE -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Administration</h1>
          <p class="page-sub">{{ filteredUsers.length }} / {{ users.length }} agent{{ users.length > 1 ? 's' : '' }}</p>
        </div>
        <div class="header-right">
          <div class="search-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input v-model="search" type="text" placeholder="Rechercher un agent..." class="search-input" />
            <button v-if="search" class="search-clear" @click="search = ''">✕</button>
          </div>
          <button class="btn-primary" @click="openCreate">+ Ajouter un agent</button>
        </div>
      </div>

      <!-- TABLEAU -->
      <div class="table-wrapper">
        <table class="users-table">
          <thead>
            <tr>
              <th>Agent</th>
              <th>Téléphone</th>
              <th>Grade / Rôle</th>
              <th>Accès</th>
              <th>Disponibilité</th>
              <th>Missions</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id" class="user-row" :class="{ 'row-blocked': user.is_blocked }">
              <td>
                <div class="user-cell">
                  <div class="avatar" :style="{ background: getColor(user.name) }">{{ getInitials(user.name) }}</div>
                  <div>
                    <span class="user-name">{{ user.name }}</span>
                    <span v-if="user.is_blocked" class="blocked-tag">Suspendu</span>
                  </div>
                </div>
              </td>
              <td class="cell-phone">{{ user.phone_number || '—' }}</td>
              <td>{{ user.role || '—' }}</td>
              <td>
                <span class="role-badge" :class="'role-' + user.spatie_role">{{ roleLabel(user.spatie_role) }}</span>
              </td>
              <td>
                <span class="avail-badge" :class="'avail-' + user.availability">{{ availLabel(user.availability) }}</span>
              </td>
              <td class="cell-center">{{ user.missions_count }}</td>
              <td>
                <div class="row-actions">
                  <!-- Modifier -->
                  <button class="action-btn edit" @click="openEdit(user)" title="Modifier">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>
                  <!-- Bloquer / Débloquer -->
                  <button
                    class="action-btn"
                    :class="user.is_blocked ? 'unblock' : 'block'"
                    @click="confirmBlock(user)"
                    :disabled="user.id === currentUserId"
                    :title="user.is_blocked ? 'Débloquer' : 'Suspendre'"
                  >
                    <svg v-if="!user.is_blocked" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                      <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                    </svg>
                  </button>
                  <!-- Supprimer -->
                  <button
                    class="action-btn delete"
                    @click="confirmDelete(user)"
                    :disabled="user.id === currentUserId"
                    title="Supprimer"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                      <path d="M10 11v6"/>
                      <path d="M14 11v6"/>
                      <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- MODAL CRÉATION / ÉDITION -->
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <div>
              <h2 class="modal-title">{{ editingUser ? 'Modifier l\'agent' : 'Ajouter un agent' }}</h2>
              <p class="modal-sub">{{ editingUser ? editingUser.name : 'Nouveau membre de l\'équipe' }}</p>
            </div>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <div class="form-group">
            <label>Nom complet <span class="required">*</span></label>
            <input v-model="form.name" type="text" class="form-input" :class="{ 'input-error': errors.name }" placeholder="Prénom Nom" />
            <span v-if="errors.name" class="error-msg">{{ errors.name[0] }}</span>
          </div>

          <div class="form-group">
            <label>Adresse email <span class="required">*</span></label>
            <input v-model="form.email" type="email" class="form-input" :class="{ 'input-error': errors.email }" placeholder="agent@missionflow.fr" />
            <span v-if="errors.email" class="error-msg">{{ errors.email[0] }}</span>
          </div>

          <div class="form-group">
            <label>Numéro WhatsApp</label>
            <input v-model="form.phone_number" type="tel" class="form-input" :class="{ 'input-error': errors.phone_number }" placeholder="+241 XX XX XX XX" />
            <span class="field-hint">Avec indicatif pays (ex: +24177123456) — utilisé pour les alertes WhatsApp</span>
            <span v-if="errors.phone_number" class="error-msg">{{ errors.phone_number[0] }}</span>
          </div>

          <div class="form-group">
            <label>{{ editingUser ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe' }} <span v-if="!editingUser" class="required">*</span></label>
            <input v-model="form.password" type="password" class="form-input" :class="{ 'input-error': errors.password }" placeholder="8 caractères minimum" />
            <span v-if="errors.password" class="error-msg">{{ errors.password[0] }}</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Grade / Intitulé de poste</label>
              <input v-model="form.role" type="text" class="form-input" placeholder="Ex : Technicien Senior, Chef d'équipe..." />
            </div>
            <div class="form-group">
              <label>Disponibilité <span class="required">*</span></label>
              <select v-model="form.availability" class="form-input">
                <option value="available">Disponible</option>
                <option value="on_leave">En congé</option>
                <option value="unavailable">Indisponible</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Niveau d'accès <span class="required">*</span></label>
            <div class="access-grid">
              <button
                v-for="r in roles"
                :key="r"
                type="button"
                class="access-btn"
                :class="{ active: form.spatie_role === r }"
                @click="form.spatie_role = r"
              >
                <span class="access-icon">{{ roleIcon(r) }}</span>
                <div>
                  <div class="access-name">{{ roleLabel(r) }}</div>
                  <div class="access-desc">{{ roleDesc(r) }}</div>
                </div>
              </button>
            </div>
            <span v-if="errors.spatie_role" class="error-msg">{{ errors.spatie_role[0] }}</span>
          </div>

          <div class="modal-actions">
            <button @click="closeModal" class="btn-cancel" :disabled="isSaving">Annuler</button>
            <button @click="saveUser" class="btn-submit" :disabled="isSaving || !isValid">
              {{ isSaving ? 'Enregistrement...' : (editingUser ? 'Enregistrer' : 'Créer l\'agent') }}
            </button>
          </div>
        </div>
      </div>

      <!-- MODAL CONFIRMATION BLOCAGE -->
      <div v-if="blockingUser" class="modal-overlay" @click="blockingUser = null">
        <div class="modal-content modal-confirm" @click.stop>
          <div class="confirm-icon">{{ blockingUser.is_blocked ? '🔓' : '🔒' }}</div>
          <h3 class="confirm-title">{{ blockingUser.is_blocked ? 'Débloquer cet agent ?' : 'Suspendre cet agent ?' }}</h3>
          <p class="confirm-body">
            <strong>{{ blockingUser.name }}</strong>
            <template v-if="blockingUser.is_blocked">
              pourra de nouveau se connecter à l'application.
            </template>
            <template v-else>
              ne pourra plus se connecter à l'application. Vous pourrez le débloquer à tout moment.
            </template>
          </p>
          <div class="modal-actions">
            <button @click="blockingUser = null" class="btn-cancel">Annuler</button>
            <button @click="doToggleBlock" :class="blockingUser.is_blocked ? 'btn-submit' : 'btn-warn'" :disabled="isSaving">
              {{ isSaving ? 'En cours...' : (blockingUser.is_blocked ? 'Débloquer' : 'Suspendre') }}
            </button>
          </div>
        </div>
      </div>

      <!-- MODAL CONFIRMATION SUPPRESSION -->
      <div v-if="deletingUser" class="modal-overlay" @click="deletingUser = null">
        <div class="modal-content modal-confirm" @click.stop>
          <div class="confirm-icon">⚠️</div>
          <h3 class="confirm-title">Supprimer cet agent ?</h3>
          <p class="confirm-body">
            <strong>{{ deletingUser.name }}</strong> sera définitivement supprimé.
            Cette action est irréversible.
          </p>
          <div class="modal-actions">
            <button @click="deletingUser = null" class="btn-cancel">Annuler</button>
            <button @click="deleteUser" class="btn-danger" :disabled="isSaving">
              {{ isSaving ? 'Suppression...' : 'Supprimer' }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script>
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
  name: 'UsersIndex',
  components: { AppLayout },

  props: {
    users: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
  },

  data() {
    return {
      showModal:    false,
      editingUser:  null,
      deletingUser: null,
      blockingUser: null,
      isSaving:     false,
      errors:       {},
      search:       '',
      form: {
        name:         '',
        email:        '',
        phone_number: '',
        password:     '',
        role:         '',
        spatie_role:  'agent',
        availability: 'available',
      },
    }
  },

  computed: {
    currentUserId() {
      return usePage().props.auth.user.id
    },
    filteredUsers() {
      const q = this.search.trim().toLowerCase()
      if (!q) return this.users
      return this.users.filter(u =>
        u.name.toLowerCase().includes(q) ||
        u.email.toLowerCase().includes(q) ||
        (u.phone_number || '').includes(q) ||
        (u.role || '').toLowerCase().includes(q)
      )
    },
    isValid() {
      return (
        this.form.name.trim() &&
        this.form.email.trim() &&
        this.form.spatie_role &&
        (this.editingUser || this.form.password.trim())
      )
    },
  },

  methods: {
    openCreate() {
      this.editingUser = null
      this.errors      = {}
      this.form        = { name: '', email: '', phone_number: '', password: '', role: '', spatie_role: 'agent', availability: 'available' }
      this.showModal   = true
    },
    openEdit(user) {
      this.editingUser = user
      this.errors      = {}
      this.form        = {
        name:         user.name,
        email:        user.email,
        phone_number: user.phone_number ?? '',
        password:     '',
        role:         user.role ?? '',
        spatie_role:  user.spatie_role,
        availability: user.availability ?? 'available',
      }
      this.showModal = true
    },
    closeModal() {
      this.showModal   = false
      this.editingUser = null
      this.errors      = {}
    },
    confirmDelete(user) {
      this.deletingUser = user
    },
    confirmBlock(user) {
      this.blockingUser = user
    },
    saveUser() {
      if (!this.isValid || this.isSaving) return
      this.isSaving = true
      this.errors   = {}

      const options = {
        onSuccess: () => { this.isSaving = false; this.closeModal() },
        onError:   (errs) => { this.isSaving = false; this.errors = errs },
      }

      if (this.editingUser) {
        router.put(route('users.update', this.editingUser.id), this.form, options)
      } else {
        router.post(route('users.store'), this.form, options)
      }
    },
    doToggleBlock() {
      if (!this.blockingUser || this.isSaving) return
      this.isSaving = true
      router.patch(route('users.toggleBlock', this.blockingUser.id), {}, {
        onFinish: () => { this.isSaving = false; this.blockingUser = null },
      })
    },
    deleteUser() {
      if (!this.deletingUser || this.isSaving) return
      this.isSaving = true
      router.delete(route('users.destroy', this.deletingUser.id), {
        onFinish: () => { this.isSaving = false; this.deletingUser = null },
      })
    },
    getInitials(name) {
      return name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() ?? '??'
    },
    getColor(name) {
      let hash = 0
      for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
      return `hsl(${Math.abs(hash) % 360}, 50%, 38%)`
    },
    roleLabel(r)  { return { admin: 'Administrateur', manager: 'Manager', agent: 'Technicien' }[r] ?? r },
    roleIcon(r)   { return { admin: '🛡️', manager: '⚙️', agent: '🔧' }[r] ?? '👤' },
    roleDesc(r)   {
      return {
        admin:      'Accès total — gestion des utilisateurs incluse',
        manager:    'Gestion des missions et du personnel',
        agent: 'Consultation et mise à jour de statut uniquement',
      }[r] ?? ''
    },
    availLabel(a) { return { available: 'Disponible', on_leave: 'En congé', unavailable: 'Indisponible' }[a] ?? a },
  },
}
</script>

<style scoped>
.page-wrapper  { padding: 32px; max-width: 1400px; }
.page-header   { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.page-title    { font-size: 24px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.page-sub      { font-size: 14px; color: #6b7280; margin: 0; }
.header-right  { display: flex; align-items: center; gap: 12px; }
.search-box    { position: relative; display: flex; align-items: center; }
.search-icon   { position: absolute; left: 10px; color: #9ca3af; pointer-events: none; flex-shrink: 0; }
.search-input  {
  padding: 9px 32px 9px 34px; border: 1.5px solid #e5e7eb; border-radius: 10px;
  font-size: 13px; color: #1a1f2e; font-family: inherit; width: 220px;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.search-input:focus { outline: none; border-color: #4f6fee; box-shadow: 0 0 0 3px rgba(79,111,238,0.1); }
.search-input::placeholder { color: #9ca3af; }
.search-clear  {
  position: absolute; right: 8px; background: none; border: none; cursor: pointer;
  color: #9ca3af; font-size: 11px; padding: 2px 4px; border-radius: 4px;
}
.search-clear:hover { color: #374151; background: #f3f4f6; }
.btn-primary   { background: #4f6fee; color: white; border: none; padding: 10px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.15s; font-family: inherit; white-space: nowrap; }
.btn-primary:hover { background: #3d5cdb; }

/* TABLE */
.table-wrapper {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  overflow: hidden;
}
.users-table {
  width: 100%;
  border-collapse: collapse;
}
.users-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}
.users-table th {
  padding: 10px 12px;
  font-size: 10px;
  font-weight: 700;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-align: left;
  white-space: nowrap;
}
.users-table td {
  padding: 11px 12px;
  font-size: 13px;
  color: #374151;
  border-bottom: 1px solid #f3f4f6;
  white-space: nowrap;
}
.user-row:last-child td { border-bottom: none; }
.user-row:hover td { background: #fafafa; }
.row-blocked td { background: #fef9f9 !important; opacity: 0.75; }
.row-blocked:hover td { background: #fef2f2 !important; opacity: 1; }
.cell-email  { color: #6b7280; font-size: 12px; max-width: 160px; overflow: hidden; text-overflow: ellipsis; }
.cell-phone  { color: #6b7280; font-size: 12px; font-family: monospace; }
.cell-center { text-align: center; font-weight: 700; color: #1a1f2e; }
.field-hint  { font-size: 11px; color: #9ca3af; margin-top: 4px; display: block; white-space: normal; }

.user-cell { display: flex; align-items: center; gap: 8px; }
.avatar {
  width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.user-name { font-weight: 600; color: #1a1f2e; display: block; font-size: 13px; }
.blocked-tag {
  display: inline-block; margin-top: 2px;
  font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
  background: #fee2e2; color: #dc2626; padding: 1px 5px; border-radius: 10px;
}

.role-badge {
  padding: 3px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
}
.role-admin      { background: #fef3c7; color: #d97706; }
.role-manager    { background: #eef2ff; color: #4f6fee; }
.role-agent { background: #f3f4f6; color: #6b7280; }

.avail-badge {
  padding: 3px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 700;
}
.avail-available   { background: #d1fae5; color: #059669; }
.avail-on_leave    { background: #f3f4f6; color: #6b7280; }
.avail-unavailable { background: #fee2e2; color: #dc2626; }

/* ACTIONS */
.row-actions { display: flex; gap: 3px; align-items: center; flex-wrap: nowrap; }
.action-btn {
  background: #f3f4f6;
  border: 1px solid transparent;
  cursor: pointer;
  padding: 5px;
  border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
  color: #6b7280;
  flex-shrink: 0;
}
.action-btn:hover { background: #e5e7eb; color: #374151; }
.action-btn.edit:hover { background: #eef2ff; color: #4f6fee; border-color: #c7d2fe; }
.action-btn.block:hover { background: #fff7ed; color: #ea580c; border-color: #fed7aa; }
.action-btn.unblock { background: #fee2e2; color: #dc2626; }
.action-btn.unblock:hover { background: #fecaca; color: #b91c1c; border-color: #fca5a5; }
.action-btn.delete:hover { background: #fef2f2; color: #ef4444; border-color: #fecaca; }
.action-btn:disabled { opacity: 0.3; cursor: not-allowed; }

/* MODAL */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.6);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 20px;
}
.modal-content {
  background: white; border-radius: 16px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.25);
  width: 90%; max-width: 560px; max-height: 90vh;
  overflow-y: auto; padding: 28px;
  animation: slideIn 0.25s ease-out;
}
.modal-confirm { max-width: 420px; text-align: center; }
@keyframes slideIn {
  from { opacity: 0; transform: translateY(-16px); }
  to   { opacity: 1; transform: translateY(0); }
}
.modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.modal-title  { font-size: 20px; font-weight: 700; color: #1a1f2e; margin: 0 0 4px; }
.modal-sub    { font-size: 13px; color: #6b7280; margin: 0; }
.close-btn {
  background: #f3f4f6; border: none; border-radius: 8px;
  width: 32px; height: 32px; cursor: pointer; color: #6b7280;
  font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.close-btn:hover { background: #e5e7eb; }

.confirm-icon  { font-size: 40px; margin-bottom: 12px; }
.confirm-title { font-size: 18px; font-weight: 700; color: #1a1f2e; margin: 0 0 8px; }
.confirm-body  { font-size: 14px; color: #6b7280; margin: 0 0 24px; line-height: 1.5; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
.required { color: #ef4444; }
.form-input {
  width: 100%; border: 1.5px solid #e5e7eb; border-radius: 8px;
  padding: 10px 12px; font-size: 14px; color: #1a1f2e;
  font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box;
}
.form-input:focus { outline: none; border-color: #4f6fee; box-shadow: 0 0 0 3px rgba(79,111,238,0.1); }
.input-error { border-color: #ef4444 !important; }
.error-msg { font-size: 12px; color: #ef4444; margin-top: 4px; display: block; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

/* ACCÈS */
.access-grid { display: flex; flex-direction: column; gap: 8px; }
.access-btn {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px;
  background: white; cursor: pointer; text-align: left;
  transition: border-color 0.15s, background 0.15s; font-family: inherit;
}
.access-btn:hover { border-color: #4f6fee; }
.access-btn.active { border-color: #4f6fee; background: #eef2ff; }
.access-icon { font-size: 20px; flex-shrink: 0; }
.access-name { font-size: 14px; font-weight: 600; color: #1a1f2e; }
.access-desc { font-size: 12px; color: #9ca3af; margin-top: 1px; }

/* BOUTONS MODAL */
.modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f3f4f6; }
.btn-cancel, .btn-submit, .btn-danger, .btn-warn {
  padding: 10px 20px; border-radius: 8px; font-size: 14px;
  font-weight: 600; cursor: pointer; border: none; font-family: inherit; transition: all 0.15s;
}
.btn-cancel { background: #f3f4f6; color: #6b7280; }
.btn-cancel:hover:not(:disabled) { background: #e5e7eb; }
.btn-submit { background: #4f6fee; color: white; }
.btn-submit:hover:not(:disabled) { background: #3d5cdb; }
.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover:not(:disabled) { background: #dc2626; }
.btn-warn { background: #f97316; color: white; }
.btn-warn:hover:not(:disabled) { background: #ea580c; }
.btn-cancel:disabled, .btn-submit:disabled, .btn-danger:disabled, .btn-warn:disabled { opacity: 0.5; cursor: not-allowed; }

.modal-content::-webkit-scrollbar { width: 5px; }
.modal-content::-webkit-scrollbar-track { background: transparent; }
.modal-content::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

/* scrollbar discrète */
.table-wrapper::-webkit-scrollbar { height: 4px; }
.table-wrapper::-webkit-scrollbar-track { background: transparent; }
.table-wrapper::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
.table-wrapper::-webkit-scrollbar-thumb:hover { background: #d1d5db; }

@media (max-width: 768px) {
  .page-wrapper { padding: 16px; }
  .header-right { flex-direction: column; align-items: stretch; gap: 8px; }
  .search-input { width: 100%; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
