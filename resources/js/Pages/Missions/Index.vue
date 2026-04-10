<template>
  <AppLayout>
    <div class="page-wrapper">

      <div class="page-header">
        <div>
          <h1 class="page-title">Opérations</h1>
          <p class="page-sub">{{ missions.length }} opération{{ missions.length > 1 ? 's' : '' }} enregistrée{{ missions.length > 1 ? 's' : '' }}</p>
        </div>
        <button v-if="$page.props.auth.can.create_missions" class="btn-primary" @click="isCreating = true">+ Déployer une opération</button>
      </div>

      <div v-if="!$page.props.auth.can.create_missions" class="technicien-banner">
        🪖 Vue personnelle — vous ne voyez que les opérations auxquelles vous êtes affecté.
      </div>

      <MissionCreator v-if="isCreating" :team-members="team" @close="isCreating = false" />
      <MissionDetail  v-if="detailMission && !editingMission" :mission="detailMission" @close="detailMission = null" @edit="openEditor" />
      <MissionEditor  v-if="editingMission" :mission="editingMission" :team-members="team" @close="editingMission = null" />
      <MissionList :missions="missions" :all-team-members="team" @detail="detailMission = $event" @edit="openEditor" />

    </div>
  </AppLayout>
</template>

<script>
import AppLayout      from '@/Layouts/AppLayout.vue'
import MissionList    from '@/Components/MissionList.vue'
import MissionCreator from '@/Components/MissionCreator.vue'
import MissionEditor  from '@/Components/MissionEditor.vue'
import MissionDetail  from '@/Components/MissionDetail.vue'

export default {
  name: 'MissionsIndex',
  components: { AppLayout, MissionList, MissionCreator, MissionEditor, MissionDetail },
  props: {
    missions: { type: Array, default: () => [] },
    team:     { type: Array, default: () => [] },
  },
  data() {
    return {
      isCreating:     false,
      detailMission:  null,
      editingMission: null,
    }
  },
  methods: {
    openEditor(mission) {
      this.detailMission  = null
      this.editingMission = mission
    },
  },
}
</script>

<style scoped>
.page-wrapper {
  padding: 32px;
  max-width: 1400px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 28px;
}
.page-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a1f2e;
  margin: 0 0 4px;
}
.page-sub { font-size: 14px; color: #6b7280; margin: 0; }
.btn-primary {
  background: #4f6fee;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}
.btn-primary:hover { background: #3d5cdb; }
.technicien-banner {
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  border-radius: 10px;
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 500;
  color: #4338ca;
  margin-bottom: 20px;
}
@media (max-width: 640px) {
  .page-wrapper { padding: 16px; }
  .page-header { flex-direction: column; gap: 16px; }
}
</style>
