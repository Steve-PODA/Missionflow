<template>
  <AppLayout>
    <div class="page-wrapper">

      <!-- EN-TÊTE -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Administration</h1>
          <p class="page-sub">{{ users.length }} agent{{ users.length > 1 ? 's' : '' }} enregistré{{ users.length > 1 ? 's' : '' }}</p>
        </div>
        <button class="btn-primary" @click="openCreate">+ Ajouter un agent</button>
      </div>

      <!-- TABLEAU -->
      <div class="table-wrapper">
        <table class="users-table">
          <thead>
            <tr>
              <th>Agent</th>
              <th>Email</th>
              <th>Téléphone</th>
              <th>Grade / Rôle</th>
              <th>Accès</th>
              <th>Disponibilité</th>
              <th>Missions</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id" class="user-row">
              <td>
                <div class="user-cell">
                  <div class="avatar" :style="{ background: getColor(user.name) }">{{ getInitials(user.name) }}</div>
                  <span class="user-name">{{ user.name }}</span>
                </div>
              </td>
              <td class="cell-email">{{ user.email }}</td>
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
                  <button class="action-btn edit" @click="openEdit(user)" title="Modifier">✏️</button>
                  <button
                    class="action-btn delete"
                    @click="confirmDelete(user)"
                    :disabled="user.id === currentUserId"
                    title="Supprimer"
                  >🗑️</button>
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
      isSaving:     false,
      errors:       {},
      form: {
        name:         '',
        email:        '',
        phone_number: '',
        password:     '',
        role:         '',
        spatie_role:  'technicien',
        availability: 'available',
      },
    }
  },

  computed: {
    currentUserId() {
      return usePage().props.auth.user.id
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
      this.form        = { name: '', email: '', phone_number: '', password: '', role: '', spatie_role: 'technicien', availability: 'available' }
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
    roleLabel(r)  { return { admin: 'Administrateur', manager: 'Manager', technicien: 'Technicien' }[r] ?? r },
    roleIcon(r)   { return { admin: '🛡️', manager: '⚙️', technicien: '🔧' }[r] ?? '👤' },
    roleDesc(r)   {
      return {
        admin:      'Accès total — gestion des utilisateurs incluse',
        manager:    'Gestion des missions et du personnel',
        technicien: 'Consultation et mise à jour de statut uniquement',
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
.btn-primary   { background: #4f6fee; color: white; border: none; padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.15s; font-family: inherit; }
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
  padding: 12px 16px;
  font-size: 11px;
  font-weight: 700;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-align: left;
}
.users-table td {
  padding: 14px 16px;
  font-size: 14px;
  color: #374151;
  border-bottom: 1px solid #f3f4f6;
}
.user-row:last-child td { border-bottom: none; }
.user-row:hover td { background: #fafafa; }
.cell-email  { color: #6b7280; font-size: 13px; }
.cell-phone  { color: #6b7280; font-size: 13px; font-family: monospace; }
.cell-center { text-align: center; font-weight: 700; color: #1a1f2e; }
.field-hint  { font-size: 11px; color: #9ca3af; margin-top: 4px; display: block; }

.user-cell { display: flex; align-items: center; gap: 10px; }
.avatar {
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 13px; font-weight: 700; flex-shrink: 0;
}
.user-name { font-weight: 600; color: #1a1f2e; }

.role-badge {
  padding: 3px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
}
.role-admin      { background: #fef3c7; color: #d97706; }
.role-manager    { background: #eef2ff; color: #4f6fee; }
.role-technicien { background: #f3f4f6; color: #6b7280; }

.avail-badge {
  padding: 3px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 700;
}
.avail-available   { background: #d1fae5; color: #059669; }
.avail-on_leave    { background: #f3f4f6; color: #6b7280; }
.avail-unavailable { background: #fee2e2; color: #dc2626; }

.row-actions { display: flex; gap: 4px; }
.action-btn {
  background: none; border: none; cursor: pointer; font-size: 15px;
  padding: 4px 6px; border-radius: 6px; transition: background 0.15s;
  opacity: 0; transition: opacity 0.15s, background 0.15s;
}
.user-row:hover .action-btn { opacity: 1; }
.action-btn:hover { background: #f3f4f6; }
.action-btn:disabled { opacity: 0.25 !important; cursor: not-allowed; }

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

/* ACTIONS */
.modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f3f4f6; }
.btn-cancel, .btn-submit, .btn-danger {
  padding: 10px 20px; border-radius: 8px; font-size: 14px;
  font-weight: 600; cursor: pointer; border: none; font-family: inherit; transition: all 0.15s;
}
.btn-cancel { background: #f3f4f6; color: #6b7280; }
.btn-cancel:hover:not(:disabled) { background: #e5e7eb; }
.btn-submit { background: #4f6fee; color: white; }
.btn-submit:hover:not(:disabled) { background: #3d5cdb; }
.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover:not(:disabled) { background: #dc2626; }
.btn-cancel:disabled, .btn-submit:disabled, .btn-danger:disabled { opacity: 0.5; cursor: not-allowed; }

.modal-content::-webkit-scrollbar { width: 5px; }
.modal-content::-webkit-scrollbar-track { background: transparent; }
.modal-content::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

@media (max-width: 768px) {
  .page-wrapper { padding: 16px; }
  .table-wrapper { overflow-x: auto; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
