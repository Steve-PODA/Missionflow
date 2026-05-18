<template>
  <AppLayout>
    <div class="page-wrapper">

      <!-- EN-TÊTE -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Gestion des Chevaux</h1>
          <p class="page-sub">
            <span class="dot dot-green"></span>{{ counts.actif }} actif{{ counts.actif > 1 ? 's' : '' }}
            &nbsp;·&nbsp;
            <span class="dot dot-red"></span>{{ counts.malade }} malade{{ counts.malade > 1 ? 's' : '' }}
            &nbsp;·&nbsp;
            <span class="dot dot-gray"></span>{{ counts.autre }} autre{{ counts.autre > 1 ? 's' : '' }}
          </p>
        </div>
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
          <div class="search-box">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input v-model="search" type="text" placeholder="N° incorporation ou cavalier…" class="search-input" />
            <button v-if="search" class="search-clear" @click="search = ''">✕</button>
          </div>
          <button v-if="$page.props.auth.can.manage_chevaux" class="btn-primary" @click="openCreate">+ Nouveau cheval</button>
        </div>
      </div>

      <!-- FILTRES -->
      <div class="filter-tabs">
        <button v-for="f in filters" :key="f.value" class="filter-tab" :class="{ active: activeFilter === f.value }" @click="activeFilter = f.value">
          {{ f.label }}<span class="filter-count">{{ f.count }}</span>
        </button>
      </div>

      <!-- GRILLE -->
      <div class="chevaux-grid">
        <div
          v-for="cheval in filteredChevaux"
          :key="cheval.id"
          class="cheval-card"
          :class="['card-' + cheval.statut, { 'card-clickable': $page.props.auth.can.manage_chevaux }]"
          @click="$page.props.auth.can.manage_chevaux && openEdit(cheval)"
        >

          <!-- BADGE STATUT -->
          <div class="card-top">
            <div class="cheval-icon">🐴</div>
            <span class="status-badge" :class="'badge-' + cheval.statut">{{ statutLabel(cheval.statut) }}</span>
          </div>

          <!-- N° INCORPORATION -->
          <div class="cheval-num">
            <span class="num-label">N° Incorporation</span>
            <span class="num-value">{{ cheval.numero_incorporation }}</span>
          </div>

          <!-- CAVALIER -->
          <div class="cavalier-block">
            <span class="cav-label">Cavalier</span>
            <div v-if="cheval.cavalier" class="cav-info">
              <span class="cav-name">{{ cheval.cavalier.name }}</span>
              <span v-if="cheval.cavalier.numero_incorporation" class="cav-num">N° {{ cheval.cavalier.numero_incorporation }}</span>
            </div>
            <span v-else class="cav-none">Non assigné</span>
          </div>

          <!-- DISPONIBILITÉ -->
          <div class="dispo-block">
            <span class="dispo-label">Disponibilité</span>
            <div class="dispo-btns" v-if="$page.props.auth.can.manage_chevaux">
              <button
                class="dispo-btn"
                :class="['btn-disponible', { active: cheval.disponibilite === 'disponible' }]"
                @click.stop="setDisponibilite(cheval, 'disponible')"
                :disabled="saving === cheval.id"
              >Disponible</button>
              <button
                class="dispo-btn"
                :class="['btn-indisponible', { active: cheval.disponibilite === 'indisponible' }]"
                @click.stop="openIndispo(cheval)"
                :disabled="saving === cheval.id"
              >Indisponible</button>
            </div>
            <span v-else class="dispo-readonly">{{ cheval.disponibilite === 'disponible' ? 'Disponible' : 'Indisponible' }}</span>
          </div>

          <!-- MOTIF INDISPO -->
          <div v-if="cheval.disponibilite === 'indisponible'" class="indispo-info">
            <span v-if="cheval.indisponibilite_motif" class="indispo-motif">🚫 {{ cheval.indisponibilite_motif }}</span>
            <span v-if="indispoEndDate(cheval)" class="indispo-date">📅 Retour prévu le <strong>{{ indispoEndDate(cheval) }}</strong></span>
          </div>

          <!-- FORMULAIRE INDISPO -->
          <div v-if="indispoDraft.chevalId === cheval.id" class="indispo-form" @click.stop>
            <input v-model="indispoDraft.motif" type="text" maxlength="255" class="form-input-sm" placeholder="Motif (ex: blessure, maladie…)" />
            <div class="indispo-form-row">
              <input v-model.number="indispoDraft.duree" type="number" min="1" max="365" class="num-input" placeholder="Durée" />
              <select v-model="indispoDraft.unite" class="sel-input">
                <option value="days">Jours</option>
                <option value="months">Mois</option>
              </select>
              <button class="btn-confirm" @click.stop="confirmIndispo(cheval)" :disabled="!indispoDraft.duree">Confirmer</button>
              <button class="btn-cancel-sm" @click.stop="indispoDraft.chevalId = null">✕</button>
            </div>
          </div>


        </div>

        <div v-if="filteredChevaux.length === 0" class="empty-state">
          <span>Aucun cheval trouvé.</span>
        </div>
      </div>

    </div>

    <!-- MODAL CRÉER / MODIFIER -->
    <div v-if="modal.open" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <h2 class="modal-title">{{ modal.mode === 'create' ? 'Nouveau cheval' : 'Modifier le cheval' }}</h2>

        <div class="form-group">
          <label class="form-label">N° Incorporation *</label>
          <input v-model="form.numero_incorporation" type="text" class="form-input" placeholder="Ex: CH-001" />
          <span v-if="errors.numero_incorporation" class="form-error">{{ errors.numero_incorporation }}</span>
        </div>

        <div class="form-group">
          <label class="form-label">Cavalier</label>
          <select v-model="form.cavalier_id" class="form-input">
            <option :value="null">— Non assigné —</option>
            <option v-for="c in cavaliers" :key="c.id" :value="c.id">
              {{ c.name }}{{ c.numero_incorporation ? ' · N°' + c.numero_incorporation : '' }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Statut *</label>
          <select v-model="form.statut" class="form-input">
            <option value="actif">Actif</option>
            <option value="malade">Malade</option>
            <option value="autre">Autre</option>
          </select>
        </div>

        <div class="modal-footer">
          <button
            v-if="modal.mode === 'edit'"
            class="btn-delete-modal"
            @click="confirmDelete(modal.cheval)"
            :disabled="saving === 'modal'"
            title="Supprimer ce cheval"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          </button>
          <div style="display:flex; gap:10px; margin-left:auto;">
            <button class="btn-secondary" @click="closeModal">Annuler</button>
            <button class="btn-primary" @click="submitForm" :disabled="saving === 'modal'">
              {{ saving === 'modal' ? 'Enregistrement…' : (modal.mode === 'create' ? 'Créer' : 'Enregistrer') }}
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
  name: 'ChevauxIndex',
  components: { AppLayout },

  props: {
    chevaux:   { type: Array, default: () => [] },
    cavaliers: { type: Array, default: () => [] },
  },

  data() {
    return {
      search: '',
      activeFilter: 'all',
      saving: null,
      errors: {},
      modal: { open: false, mode: 'create', cheval: null },
      form: { numero_incorporation: '', cavalier_id: null, statut: 'actif' },
      indispoDraft: { chevalId: null, motif: '', duree: '', unite: 'days' },
    }
  },

  computed: {
    filters() {
      return [
        { value: 'all',      label: 'Tous',      count: this.chevaux.length },
        { value: 'actif',    label: 'Actifs',    count: this.counts.actif },
        { value: 'malade',   label: 'Malades',   count: this.counts.malade },
        { value: 'autre',    label: 'Autres',    count: this.counts.autre },
        { value: 'indispo',  label: 'Indisponibles', count: this.counts.indispo },
      ]
    },
    counts() {
      return {
        actif:   this.chevaux.filter(c => c.statut === 'actif').length,
        malade:  this.chevaux.filter(c => c.statut === 'malade').length,
        autre:   this.chevaux.filter(c => c.statut === 'autre').length,
        indispo: this.chevaux.filter(c => c.disponibilite === 'indisponible').length,
      }
    },
    filteredChevaux() {
      let list = [...this.chevaux]

      if (this.activeFilter === 'indispo') {
        list = list.filter(c => c.disponibilite === 'indisponible')
      } else if (this.activeFilter !== 'all') {
        list = list.filter(c => c.statut === this.activeFilter)
      }

      if (this.search.trim()) {
        const q = this.search.toLowerCase()
        list = list.filter(c =>
          c.numero_incorporation.toLowerCase().includes(q) ||
          c.cavalier?.name?.toLowerCase().includes(q)
        )
      }

      return list
    },
  },

  methods: {
    statutLabel(statut) {
      return { actif: 'Actif', malade: 'Malade', autre: 'Autre' }[statut] ?? statut
    },

    indispoEndDate(cheval) {
      if (!cheval.indisponibilite_debut || !cheval.indisponibilite_duree) return null
      const d = new Date(cheval.indisponibilite_debut)
      if (cheval.indisponibilite_unite === 'months') {
        d.setMonth(d.getMonth() + cheval.indisponibilite_duree)
      } else {
        d.setDate(d.getDate() + cheval.indisponibilite_duree)
      }
      return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
    },

    openCreate() {
      this.form = { numero_incorporation: '', cavalier_id: null, statut: 'actif' }
      this.errors = {}
      this.modal = { open: true, mode: 'create', cheval: null }
    },

    openEdit(cheval) {
      this.form = {
        numero_incorporation: cheval.numero_incorporation,
        cavalier_id:          cheval.cavalier?.id ?? null,
        statut:               cheval.statut,
      }
      this.errors = {}
      this.modal = { open: true, mode: 'edit', cheval }
    },

    closeModal() {
      this.modal.open = false
    },

    submitForm() {
      this.errors = {}
      if (!this.form.numero_incorporation.trim()) {
        this.errors.numero_incorporation = 'Le numéro d\'incorporation est requis.'
        return
      }

      this.saving = 'modal'

      if (this.modal.mode === 'create') {
        router.post(route('chevaux.store'), this.form, {
          preserveScroll: true,
          onSuccess: () => { this.closeModal() },
          onError: (e) => { this.errors = e },
          onFinish: () => { this.saving = null },
        })
      } else {
        router.put(route('chevaux.update', this.modal.cheval.id), this.form, {
          preserveScroll: true,
          onSuccess: () => { this.closeModal() },
          onError: (e) => { this.errors = e },
          onFinish: () => { this.saving = null },
        })
      }
    },

    setDisponibilite(cheval, valeur) {
      this.indispoDraft.chevalId = null
      this.saving = cheval.id
      router.put(route('chevaux.update', cheval.id), { disponibilite: valeur }, {
        preserveScroll: true,
        onFinish: () => { this.saving = null },
      })
    },

    openIndispo(cheval) {
      this.indispoDraft = { chevalId: cheval.id, motif: '', duree: '', unite: 'days' }
    },

    confirmIndispo(cheval) {
      if (!this.indispoDraft.duree) return
      this.saving = cheval.id
      const draft = { ...this.indispoDraft }
      this.indispoDraft.chevalId = null
      router.put(route('chevaux.update', cheval.id), {
        disponibilite:          'indisponible',
        indisponibilite_motif:  draft.motif || null,
        indisponibilite_duree:  draft.duree,
        indisponibilite_unite:  draft.unite,
      }, {
        preserveScroll: true,
        onFinish: () => { this.saving = null },
      })
    },

    confirmDelete(cheval) {
      if (!confirm(`Supprimer le cheval N° ${cheval.numero_incorporation} ?`)) return
      this.closeModal()
      this.saving = cheval.id
      router.delete(route('chevaux.destroy', cheval.id), {
        preserveScroll: true,
        onFinish: () => { this.saving = null },
      })
    },
  },
}
</script>

<style scoped>
.page-wrapper { padding: 32px; max-width: 1400px; }

.page-header { margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.page-title  { font-size: 24px; font-weight: 700; color: #1a1f2e; margin: 0 0 8px; }
.page-sub    { font-size: 14px; color: #6b7280; display: flex; align-items: center; gap: 4px; }

.dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 2px; }
.dot-green { background: #22c55e; }
.dot-red   { background: #ef4444; }
.dot-gray  { background: #9ca3af; }

.search-box {
  display: flex; align-items: center; gap: 8px;
  background: white; border: 1.5px solid #e5e7eb; border-radius: 10px;
  padding: 0 12px; height: 42px; min-width: 260px;
  transition: border-color 0.15s;
}
.search-box:focus-within { border-color: #4f6fee; }
.search-icon  { width: 16px; height: 16px; color: #9ca3af; flex-shrink: 0; }
.search-input { flex: 1; border: none; outline: none; font-size: 14px; color: #1a1f2e; background: transparent; font-family: inherit; }
.search-input::placeholder { color: #9ca3af; }
.search-clear { background: none; border: none; cursor: pointer; color: #9ca3af; font-size: 12px; padding: 2px; }
.search-clear:hover { color: #374151; }

.btn-primary {
  padding: 9px 18px; background: #4f6fee; color: white; border: none;
  border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;
  font-family: inherit; transition: background 0.15s; white-space: nowrap;
}
.btn-primary:hover { background: #3a56d4; }

/* FILTRES */
.filter-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
.filter-tab {
  display: flex; align-items: center; gap: 8px; padding: 8px 16px;
  border: 1.5px solid #e5e7eb; border-radius: 10px; background: white;
  font-size: 13px; font-weight: 600; color: #6b7280; cursor: pointer;
  transition: all 0.15s; font-family: inherit;
}
.filter-tab:hover { border-color: #4f6fee; color: #4f6fee; }
.filter-tab.active { background: #4f6fee; border-color: #4f6fee; color: white; }
.filter-count { background: rgba(0,0,0,0.1); border-radius: 20px; padding: 1px 7px; font-size: 11px; font-weight: 700; }
.filter-tab.active .filter-count { background: rgba(255,255,255,0.25); }

/* GRILLE */
.chevaux-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
  align-items: start;
}

/* CARTE */
.cheval-card {
  background: white; border-radius: 16px; padding: 22px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 2px solid transparent;
  display: flex; flex-direction: column; gap: 14px;
  transition: box-shadow 0.2s;
  min-height: 300px;
  position: relative;
}
.cheval-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.1); }
.card-clickable { cursor: pointer; }

.card-actif   { border-color: #d1fae5; }
.card-malade  { border-color: #fee2e2; }
.card-autre   { border-color: #e5e7eb; }

.card-top { display: flex; justify-content: space-between; align-items: center; }
.cheval-icon { font-size: 32px; }

.status-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
.badge-actif  { background: #d1fae5; color: #059669; }
.badge-malade { background: #fee2e2; color: #dc2626; }
.badge-autre  { background: #f3f4f6; color: #6b7280; }

.cheval-num { display: flex; flex-direction: column; gap: 2px; }
.num-label  { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
.num-value  { font-size: 20px; font-weight: 800; color: #1a1f2e; letter-spacing: 1px; }

.cavalier-block { display: flex; flex-direction: column; gap: 4px; background: #f9fafb; border-radius: 8px; padding: 10px 12px; }
.cav-label { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
.cav-info  { display: flex; flex-direction: column; gap: 2px; }
.cav-name  { font-size: 14px; font-weight: 700; color: #1a1f2e; }
.cav-num   { font-size: 11px; color: #6b7280; }
.cav-none  { font-size: 13px; color: #9ca3af; font-style: italic; }

.dispo-block  { display: flex; flex-direction: column; gap: 6px; border-top: 1px solid #f3f4f6; padding-top: 12px; }
.dispo-label  { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
.dispo-btns   { display: flex; gap: 6px; }
.dispo-btn    { flex: 1; padding: 6px 4px; border: 1.5px solid #e5e7eb; border-radius: 6px; background: white; font-size: 11px; font-weight: 600; cursor: pointer; color: #6b7280; transition: all 0.15s; font-family: inherit; }
.dispo-btn:disabled { opacity: 0.5; cursor: wait; }
.btn-disponible.active   { background: #d1fae5; border-color: #059669; color: #059669; }
.btn-indisponible.active { background: #fee2e2; border-color: #dc2626; color: #dc2626; }
.dispo-readonly { font-size: 13px; color: #6b7280; }

.indispo-info  { display: flex; flex-direction: column; gap: 4px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 8px 12px; font-size: 13px; color: #be123c; }
.indispo-motif { font-weight: 600; }
.indispo-date  { font-size: 12px; opacity: 0.85; }

.indispo-form     { display: flex; flex-direction: column; gap: 6px; padding-top: 6px; }
.indispo-form-row { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.form-input-sm    { width: 100%; padding: 6px 10px; border: 1.5px solid #e5e7eb; border-radius: 6px; font-size: 13px; font-family: inherit; color: #1a1f2e; outline: none; }
.form-input-sm:focus { border-color: #4f6fee; }
.num-input  { width: 72px; padding: 6px 8px; border: 1.5px solid #e5e7eb; border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; }
.num-input:focus { border-color: #4f6fee; }
.sel-input  { padding: 6px 8px; border: 1.5px solid #e5e7eb; border-radius: 6px; font-size: 13px; font-family: inherit; background: white; outline: none; cursor: pointer; }
.btn-confirm { padding: 6px 12px; background: #4f6fee; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; }
.btn-confirm:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-cancel-sm { padding: 6px 8px; background: none; border: 1.5px solid #e5e7eb; border-radius: 6px; font-size: 12px; color: #9ca3af; cursor: pointer; font-family: inherit; }
.btn-cancel-sm:hover { border-color: #dc2626; color: #dc2626; }

.card-actions { display: flex; gap: 8px; border-top: 1px solid #f3f4f6; padding-top: 12px; margin-top: auto; }

.empty-state { grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #9ca3af; font-size: 14px; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal { background: white; border-radius: 16px; padding: 32px; width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.modal-title { font-size: 18px; font-weight: 700; color: #1a1f2e; margin: 0 0 24px; }

.form-group  { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
.form-label  { font-size: 13px; font-weight: 600; color: #374151; }
.form-input  { padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 14px; font-family: inherit; color: #1a1f2e; outline: none; transition: border-color 0.15s; background: white; }
.form-input:focus { border-color: #4f6fee; }
.form-error  { font-size: 12px; color: #dc2626; }

.modal-footer { display: flex; align-items: center; margin-top: 24px; }
.btn-secondary { padding: 9px 18px; background: #f3f4f6; color: #374151; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-secondary:hover { background: #e5e7eb; }
.btn-delete-modal { padding: 9px 12px; background: #fff1f2; color: #dc2626; border: 1.5px solid #fecdd3; border-radius: 10px; cursor: pointer; display: flex; align-items: center; transition: all .15s; }
.btn-delete-modal:hover:not(:disabled) { background: #fee2e2; border-color: #fca5a5; }
.btn-delete-modal:disabled { opacity: .5; cursor: wait; }

@media (max-width: 640px) {
  .page-wrapper { padding: 16px; }
  .chevaux-grid { grid-template-columns: 1fr; }
}
</style>
