<template>
  <AppLayout title="Déployer une opération">
    <div class="page-container">
      <div class="form-card">
        <div class="form-header">
          <div>
            <h1 class="form-title">Déployer une opération</h1>
            <p class="form-sub">Renseignez les paramètres de la mission stratégique</p>
          </div>
          <Link :href="route('missions.index')" class="back-link">
            &larr; Retour à la liste
          </Link>
        </div>

        <form @submit.prevent="createMission">
          <!-- Nom de l'opération -->
          <div class="form-group">
            <label>Nom de l'opération <span class="required">*</span></label>
            <input
              type="text"
              v-model="form.title"
              placeholder="Ex : Opération Tonnerre"
              class="form-input"
              :class="{ 'input-error': errors.title }"
            />
            <span v-if="errors.title" class="error-msg">{{ errors.title[0] }}</span>
          </div>

          <!-- Briefing -->
          <div class="form-group">
            <label>Briefing opérationnel</label>
            <textarea
              v-model="form.briefing"
              placeholder="Décrivez les objectifs, contraintes et informations clés de l'opération..."
              class="form-input form-textarea"
              :class="{ 'input-error': errors.briefing }"
              rows="4"
            ></textarea>
            <span v-if="errors.briefing" class="error-msg">{{ errors.briefing[0] }}</span>
          </div>

          <!-- Commanditaire -->
          <div class="form-group">
            <label>Commanditaire / Unité <span class="required">*</span></label>
            <input
              type="text"
              v-model="form.company"
              placeholder="Ex : État-Major, DGSE, Unité Delta..."
              class="form-input"
              :class="{ 'input-error': errors.company }"
            />
            <span v-if="errors.company" class="error-msg">{{ errors.company[0] }}</span>
          </div>

          <!-- Date + Heure H -->
          <div class="form-row">
            <div class="form-group">
              <label>Date d'opération <span class="required">*</span></label>
              <input
                type="date"
                v-model="form.date"
                class="form-input"
                :class="{ 'input-error': errors.date || dateError }"
                :min="today"
              />
              <span v-if="dateError" class="error-msg">La date ne peut pas être dans le passé.</span>
              <span v-else-if="errors.date" class="error-msg">{{ errors.date[0] }}</span>
            </div>
            <div class="form-group">
              <label>Heure H <span class="required">*</span></label>
              <input
                type="time"
                v-model="form.startTime"
                class="form-input"
                :class="{ 'input-error': errors.startTime }"
              />
              <span v-if="errors.startTime" class="error-msg">{{ errors.startTime[0] }}</span>
            </div>
          </div>

          <div class="form-row">
            <!-- Durée -->
            <div class="form-group">
              <label>Durée estimée</label>
              <select v-model="form.duration" class="form-input">
                <option value="0.5">30 minutes</option>
                <option value="1">1 heure</option>
                <option value="2">2 heures</option>
                <option value="4">4 heures</option>
                <option value="8">Journée complète</option>
              </select>
            </div>
            <!-- Zone d'opération -->
            <div class="form-group">
              <label>Zone d'opération <span class="required">*</span></label>
              <input
                type="text"
                v-model="form.location"
                placeholder="Ex : Secteur Nord, Coordonnées GPS..."
                class="form-input"
                :class="{ 'input-error': errors.location }"
              />
              <span v-if="errors.location" class="error-msg">{{ errors.location[0] }}</span>
            </div>
          </div>

          <!-- Niveau de priorité -->
          <div class="form-group">
            <label>Niveau de priorité <span class="required">*</span></label>
            <div class="priority-grid">
              <button
                v-for="p in priorities"
                :key="p.value"
                type="button"
                class="priority-btn"
                :class="[p.value, { selected: form.priority === p.value }]"
                @click="form.priority = p.value"
              >
                <span class="priority-dot"></span>
                {{ p.label }}
              </button>
            </div>
          </div>

          <!-- SELECTION EQUIPE (NOUVEAU) -->
          <div class="form-row" style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 24px;">
            <div class="form-group">
              <label>Peloton</label>
              <select v-model="selectedPelotonId" class="form-input">
                <option value="">-- Choisir le peloton --</option>
                <option v-for="p in pelotons" :key="p.id" :value="p.id">{{ p.nom }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Sous-groupe</label>
              <select v-model="selectedGroupeId" class="form-input" :disabled="!selectedPelotonId">
                <option value="">-- Choisir le sous-groupe --</option>
                <option v-for="g in availableGroupes" :key="g.id" :value="g.id">{{ g.nom }}</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Équipe d'intervention de base <span class="required">*</span></label>
            <select v-model="form.equipeSourceId" class="form-input" :disabled="!selectedGroupeId" @change="onEquipeSelected" :class="{ 'input-error': errors.equipeSourceId }">
              <option value="">-- Choisir l'équipe --</option>
              <option v-for="e in availableEquipes" :key="e.id" :value="e.id">{{ e.nom }}</option>
            </select>
            <span v-if="errors.equipeSourceId" class="error-msg">{{ errors.equipeSourceId[0] }}</span>
          </div>

          <!-- Personnel affecté -->
          <div class="form-group" v-if="form.equipeSourceId">
            <label>Personnalisation de l'équipe ({{ form.selectedTeam.length }}) <span class="required">*</span></label>
            <p class="form-sub" style="margin-bottom: 12px; font-size: 13px;">Sélectionnez les membres à déployer et désignez le Chef de Mission pour cette opération.</p>
            
            <span v-if="errors.selectedTeam" class="error-msg" style="margin-bottom:8px;display:block;">{{ errors.selectedTeam[0] }}</span>
            <span v-if="errors.chefMissionId" class="error-msg" style="margin-bottom:8px;display:block;">Veuillez désigner un Chef de mission.</span>

            <div v-if="hasUnavailableSelected" class="warning-banner">
              ⚠️ Un ou plusieurs agents sélectionnés ne sont pas disponibles (congé ou indisponible).
            </div>

            <!-- Ajout d'une recherche pour trouver d'autres membres facilement -->
            <input type="text" v-model="searchQuery" placeholder="Rechercher des renforts (nom, rôle)..." class="form-input search-input" />

            <div class="team-select">
              <div
                v-for="member in filteredTeamMembers"
                :key="member.id"
                class="team-member"
                :class="{ selected: isSelected(member.id), 'member-unavailable': isUnavailable(member) }"
              >
                <!-- Infos du membre -->
                <div class="member-clickable" @click="toggleMember(member.id)">
                  <div class="member-avatar" :style="{ background: getUserColor(member.name) }">
                    {{ getInitials(member.name) }}
                  </div>
                  <div class="member-info">
                    <span class="member-name">{{ member.name }}</span>
                    <span class="member-role">{{ member.role }}</span>
                    <span v-if="member.active_mission" class="member-op">⚔️ {{ member.active_mission.title }}</span>
                  </div>
                  <span class="avail-badge" :class="'avail-' + (member.computed_status || 'available')">
                    {{ availLabel(member.computed_status) }}
                  </span>
                  <div class="check-icon" v-if="isSelected(member.id)">✓</div>
                </div>

                <!-- Bouton radio pour Chef de mission -->
                <label v-if="isSelected(member.id)" class="chef-radio-wrapper" @click.stop>
                  <input type="radio" :value="member.id" v-model="form.chefMissionId" name="chefMission" />
                  <span class="chef-label">Chef</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Officier de liaison + Ligne sécurisée -->
          <div class="form-row" style="margin-top: 32px;">
            <div class="form-group">
              <label>Officier de liaison <span class="required">*</span></label>
              <input
                type="text"
                v-model="form.clientName"
                placeholder="Grade et nom"
                class="form-input"
                :class="{ 'input-error': errors.clientName }"
              />
              <span v-if="errors.clientName" class="error-msg">{{ errors.clientName[0] }}</span>
            </div>
            <div class="form-group">
              <label>Ligne sécurisée <span class="required">*</span></label>
              <input
                type="tel"
                v-model="form.clientPhone"
                placeholder="Numéro de contact"
                class="form-input"
                :class="{ 'input-error': errors.clientPhone }"
              />
              <span v-if="errors.clientPhone" class="error-msg">{{ errors.clientPhone[0] }}</span>
            </div>
          </div>

          <!-- Contact chiffré -->
          <div class="form-group">
            <label>Contact chiffré (email)</label>
            <input
              type="email"
              v-model="form.clientEmail"
              placeholder="adresse@domaine.mil"
              class="form-input"
              :class="{ 'input-error': errors.clientEmail }"
            />
            <span v-if="errors.clientEmail" class="error-msg">{{ errors.clientEmail[0] }}</span>
          </div>

          <div class="form-actions">
            <Link :href="route('missions.index')" class="btn-cancel">Annuler</Link>
            <button type="submit" class="btn-submit" :disabled="isSaving || !isValid">
              {{ isSaving ? 'Déploiement en cours...' : 'Confirmer le déploiement' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

export default {
  name: 'MissionCreatorPage',
  components: { AppLayout, Link },

  props: {
    team: { type: Array, default: () => [] },
    pelotons: { type: Array, default: () => [] }
  },

  data() {
    return {
      isSaving: false,
      errors: {},
      today: new Date().toLocaleDateString('sv-SE'),
      searchQuery: '',
      selectedPelotonId: '',
      selectedGroupeId: '',
      
      form: {
        title: '',
        briefing: '',
        company: '',
        date: '',
        startTime: '',
        duration: '2',
        priority: 'medium',
        location: '',
        equipeSourceId: '',
        chefMissionId: '',
        selectedTeam: [],
        clientName: '',
        clientEmail: '',
        clientPhone: '',
      },
      priorities: [
        { value: 'low',    label: 'Niveau 4 – Faible'  },
        { value: 'medium', label: 'Niveau 3 – Standard' },
        { value: 'high',   label: 'Niveau 2 – Haute'   },
        { value: 'urgent', label: 'CRITIQUE'            },
      ],
    }
  },

  watch: {
    selectedPelotonId() {
      this.selectedGroupeId = '';
      this.form.equipeSourceId = '';
    },
    selectedGroupeId() {
      this.form.equipeSourceId = '';
    }
  },

  computed: {
    availableGroupes() {
      if (!this.selectedPelotonId) return [];
      const peloton = this.pelotons.find(p => p.id === this.selectedPelotonId);
      return peloton ? peloton.groupes : [];
    },
    availableEquipes() {
      if (!this.selectedGroupeId) return [];
      const groupe = this.availableGroupes.find(g => g.id === this.selectedGroupeId);
      return groupe ? groupe.equipes : [];
    },
    filteredTeamMembers() {
      const q = this.searchQuery.toLowerCase();
      let members = [...this.team];

      if (q) {
        members = members.filter(m => 
          m.name.toLowerCase().includes(q) || 
          m.role.toLowerCase().includes(q)
        );
      }

      // Trier : ceux de l'équipe source en premier, puis par disponibilité
      return members.sort((a, b) => {
        // Equipe source en priorité
        if (this.form.equipeSourceId) {
          const aInEquipe = a.equipe_id === this.form.equipeSourceId;
          const bInEquipe = b.equipe_id === this.form.equipeSourceId;
          if (aInEquipe && !bInEquipe) return -1;
          if (!aInEquipe && bInEquipe) return 1;
        }

        const order = { available: 0, deployed: 1, on_leave: 2, unavailable: 3 }
        return (order[a.computed_status] ?? 0) - (order[b.computed_status] ?? 0)
      })
    },
    hasUnavailableSelected() {
      return this.form.selectedTeam.some(id => {
        const m = this.team.find(t => t.id === id)
        return m && (m.computed_status === 'on_leave' || m.computed_status === 'unavailable')
      })
    },
    dateError() {
      return this.form.date && this.form.date < this.today
    },
    isValid() {
      return (
        !this.dateError &&
        this.form.title.trim() &&
        this.form.company.trim() &&
        this.form.date &&
        this.form.startTime &&
        this.form.location.trim() &&
        this.form.equipeSourceId &&
        this.form.selectedTeam.length > 0 &&
        this.form.chefMissionId &&
        this.form.clientName.trim() &&
        this.form.clientPhone.trim()
      )
    }
  },

  methods: {
    onEquipeSelected() {
      if (!this.form.equipeSourceId) {
        this.form.selectedTeam = [];
        this.form.chefMissionId = '';
        return;
      }

      // Auto-populate with members of the selected team
      const teamMembers = this.team.filter(m => m.equipe_id === this.form.equipeSourceId);
      this.form.selectedTeam = teamMembers.map(m => m.id);
      
      // Auto-assign chef d'équipe as chef de mission
      const defaultChef = teamMembers.find(m => m.role === 'chef_equipe');
      if (defaultChef) {
        this.form.chefMissionId = defaultChef.id;
      } else if (teamMembers.length > 0) {
        this.form.chefMissionId = teamMembers[0].id;
      } else {
        this.form.chefMissionId = '';
      }
    },

    getInitials(name) {
      if (!name) return '??'
      return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
    },

    getUserColor(name) {
      let hash = 0
      for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash)
      }
      return `hsl(${Math.abs(hash) % 360}, 55%, 40%)`
    },

    isSelected(id) { return this.form.selectedTeam.includes(id) },
    isUnavailable(member) {
      return member.computed_status === 'on_leave' || member.computed_status === 'unavailable'
    },
    availLabel(status) {
      return { available: 'Libre', deployed: 'Déployé', on_leave: 'En congé', unavailable: 'Indispo' }[status] ?? 'Libre'
    },

    toggleMember(id) {
      const i = this.form.selectedTeam.indexOf(id)
      if (i > -1) {
        this.form.selectedTeam.splice(i, 1)
        if (this.form.chefMissionId === id) this.form.chefMissionId = '';
      }
      else {
        this.form.selectedTeam.push(id)
        if (!this.form.chefMissionId) this.form.chefMissionId = id;
      }
    },

    createMission() {
      if (!this.isValid || this.isSaving) return
      this.isSaving = true
      this.errors   = {}

      router.post(route('missions.store'), this.form, {
        onSuccess: () => {
          this.isSaving = false
        },
        onError: (errs) => {
          this.isSaving = false
          this.errors   = errs
        },
      })
    },
  },
}
</script>

<style scoped>
.page-container {
  padding: 40px 20px;
  display: flex;
  justify-content: center;
  background: #f8fafc;
  min-height: calc(100vh - 64px);
}

.form-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05), 0 20px 48px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 800px;
  padding: 40px;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 24px;
}

.form-title { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0 0 8px; }
.form-sub { font-size: 15px; color: #64748b; margin: 0; }

.back-link { font-size: 14px; color: #64748b; text-decoration: none; font-weight: 500; transition: color 0.15s; }
.back-link:hover { color: #4f6fee; }

.form-group { margin-bottom: 24px; }
.form-group label { display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 8px; }
.required { color: #ef4444; }

.form-input {
  width: 100%; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 16px;
  font-size: 15px; color: #1e293b; font-family: inherit; transition: all 0.2s; box-sizing: border-box;
}
.form-input:focus { outline: none; border-color: #4f6fee; box-shadow: 0 0 0 4px rgba(79, 111, 238, 0.1); background: white; }
.form-input:disabled { background: #f1f5f9; cursor: not-allowed; }

.search-input { margin-bottom: 16px; background: #f8fafc; border-style: dashed; }

.form-textarea { resize: vertical; min-height: 120px; }
.input-error { border-color: #ef4444 !important; }
.error-msg { font-size: 13px; color: #ef4444; margin-top: 6px; display: block; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* PRIORITY */
.priority-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.priority-btn {
  border: 2px solid #f1f5f9; background: #f8fafc; border-radius: 10px; padding: 14px 12px;
  font-size: 13px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center; gap: 8px; font-family: inherit;
}
.priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.priority-btn.low { --c: #10b981; } .priority-btn.medium { --c: #f59e0b; }
.priority-btn.high { --c: #ef4444; } .priority-btn.urgent { --c: #dc2626; }
.priority-btn .priority-dot { background: var(--c); }
.priority-btn:hover { border-color: var(--c); background: white; color: var(--c); }
.priority-btn.selected { border-color: var(--c); background: var(--c); color: white; }
.priority-btn.selected .priority-dot { background: white; }

/* TEAM */
.team-select {
  display: flex; flex-direction: column; gap: 10px; max-height: 400px; overflow-y: auto;
  border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #f8fafc;
}

.team-member {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px;
  background: white; border-radius: 10px; cursor: default; border: 2px solid transparent;
  transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.team-member:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
.team-member.selected { background: rgba(79, 111, 238, 0.05); border-color: #4f6fee; }
.team-member.member-unavailable { opacity: 0.5; filter: grayscale(50%); background: #f1f5f9; }

.member-clickable { display: flex; align-items: center; gap: 12px; flex: 1; cursor: pointer; }

.member-op { font-size: 11px; color: #b45309; display: block; }
.avail-badge { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
.avail-available { background: #dcfce7; color: #15803d; }
.avail-deployed { background: #fef3c7; color: #b45309; }
.avail-on_leave { background: #e2e8f0; color: #475569; }
.avail-unavailable { background: #fee2e2; color: #b91c1c; text-decoration: line-through; }

.warning-banner { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; padding: 12px 16px; font-size: 14px; color: #92400e; margin-bottom: 12px; }

.member-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: white; flex-shrink: 0; }
.member-info { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.member-name { font-size: 14px; font-weight: 600; color: #1e293b; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; }
.member-role { font-size: 12px; color: #64748b; }

.check-icon { width: 20px; height: 20px; background: #4f6fee; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 11px; font-weight: 700; }

.chef-radio-wrapper {
  display: flex; align-items: center; gap: 6px; background: white; padding: 6px 12px;
  border-radius: 20px; border: 1px solid #cbd5e1; cursor: pointer; transition: all 0.2s;
}
.chef-radio-wrapper:hover { border-color: #4f6fee; background: #f8fafc; }
.chef-radio-wrapper input { cursor: pointer; accent-color: #4f6fee; }
.chef-label { font-size: 12px; font-weight: 600; color: #475569; }

/* ACTIONS */
.form-actions { display: flex; justify-content: flex-end; gap: 16px; margin-top: 40px; padding-top: 32px; border-top: 1px solid #f1f5f9; }
.btn-cancel, .btn-submit { padding: 12px 28px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; font-family: inherit; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
.btn-cancel { background: #f1f5f9; color: #64748b; }
.btn-cancel:hover { background: #e2e8f0; color: #1e293b; }
.btn-submit { background: #4f6fee; color: white; }
.btn-submit:hover:not(:disabled) { background: #3d5cdb; box-shadow: 0 10px 15px -3px rgba(79, 111, 238, 0.4); transform: translateY(-1px); }
.btn-cancel:disabled, .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

/* SCROLLBAR */
.team-select::-webkit-scrollbar { width: 6px; }
.team-select::-webkit-scrollbar-track { background: transparent; }
.team-select::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

@media (max-width: 768px) {
  .page-container { padding: 20px 10px; }
  .form-card { padding: 24px; }
  .form-row { grid-template-columns: 1fr; gap: 0; }
  .priority-grid { grid-template-columns: 1fr 1fr; }
}
</style>
