<template>
  <div class="cal-wrapper">

    <!-- Header -->
    <div class="cal-header">
      <div>
        <h2 class="cal-title">Planning opérationnel</h2>
        <p class="cal-sub">{{ viewMode === 'week' ? formatDateRange : formatMonthTitle }}</p>
      </div>
      <div class="cal-controls">
        <!-- Toggle vue -->
        <div class="view-toggle">
          <button :class="['toggle-btn', { active: viewMode === 'week' }]" @click="viewMode = 'week'">Semaine</button>
          <button :class="['toggle-btn', { active: viewMode === 'month' }]" @click="viewMode = 'month'">Mois</button>
        </div>
        <!-- Navigation -->
        <button @click="prev" class="nav-btn">
          <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="goToToday" class="today-btn">Aujourd'hui</button>
        <button @click="next" class="nav-btn">
          <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
      </div>
    </div>

    <!-- ══ VUE SEMAINE ══════════════════════════════════════════ -->
    <div v-if="viewMode === 'week'" class="border border-gray-200 rounded-lg overflow-hidden">
      <div class="grid grid-cols-8 bg-gray-50 border-b border-gray-200">
        <div class="p-4"></div>
        <div v-for="day in weekDays" :key="day.date" class="p-4 text-center" :class="{ 'bg-blue-50': day.isToday }">
          <div class="text-sm text-gray-500 font-medium">{{ day.dayName }}</div>
          <div class="text-2xl font-bold mt-1" :class="day.isToday ? 'text-blue-600' : 'text-gray-900'">{{ day.dayNumber }}</div>
        </div>
      </div>

      <div class="grid grid-cols-8" ref="calendarGrid">
        <div class="col-span-1">
          <div v-for="hour in timeSlots" :key="hour" class="time-slot-label">{{ hour }}</div>
        </div>
        <div
          v-for="(day, dayIndex) in weekDays" :key="day.date"
          class="relative border-l border-gray-200 day-column"
          :class="{ 'bg-blue-50/30': day.isToday, 'drag-over': dragOverIndex === dayIndex }"
          @dragover.prevent="onDragOver($event, dayIndex)"
          @dragleave="onDragLeave($event)"
          @drop.prevent="onDrop($event, day)"
        >
          <div v-for="hour in timeSlots" :key="`${day.date}-${hour}`" class="time-slot-cell"></div>
          <div
            v-for="event in getEventsForDay(day.date)" :key="event.id"
            draggable="true"
            class="cal-event"
            :class="[getEventColor(event.priority), { 'is-dragging': draggingEvent?.id === event.id }]"
            :style="getEventPosition(event)"
            @dragstart="onDragStart($event, event)"
            @dragend="onDragEnd"
            @click.stop="$emit('open-event', event)"
          >
            <div class="event-title">{{ event.title }}</div>
            <div class="event-time">{{ event.startTime }} – {{ event.endTime }}</div>
            <div class="event-drag-hint">⠿ déplacer</div>
          </div>
          <div v-if="dragOverIndex === dayIndex && ghostStyle" class="cal-ghost" :style="ghostStyle">
            <div class="ghost-time">{{ ghostTimeLabel }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ VUE MOIS ════════════════════════════════════════════ -->
    <div v-else class="month-grid-wrap">
      <!-- En-têtes jours -->
      <div class="month-dow-row">
        <div v-for="d in dayNames" :key="d" class="month-dow">{{ d }}</div>
      </div>
      <!-- Grille des jours -->
      <div class="month-grid">
        <div
          v-for="cell in monthCells" :key="cell.date"
          class="month-cell"
          :class="{
            'cell-other-month': !cell.isCurrentMonth,
            'cell-today':        cell.isToday,
            'cell-drag-over':    monthDragOver === cell.date,
          }"
          @dragover.prevent="monthDragOver = cell.date"
          @dragleave.self="monthDragOver = null"
          @drop.prevent="onMonthDrop(cell.date)"
        >
          <div class="cell-number">{{ cell.dayNumber }}</div>
          <div class="cell-events">
            <div
              v-for="ev in cell.events.slice(0, 3)" :key="ev.id"
              draggable="true"
              class="month-pill"
              :class="['pill-' + ev.priority, { 'pill-dragging': monthDraggingEvent?.id === ev.id }]"
              @dragstart="onMonthDragStart($event, ev)"
              @dragend="onMonthDragEnd"
              @click.stop="$emit('open-event', ev)"
              :title="ev.title"
            >
              {{ ev.startTime }} {{ ev.title }}
            </div>
            <div v-if="cell.events.length > 3" class="month-more" @click="$emit('open-event', cell.events[3])">
              +{{ cell.events.length - 3 }} autre{{ cell.events.length - 3 > 1 ? 's' : '' }}
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  events:      { type: Array,  default: () => [] },
  initialDate: { type: String, default: () => new Date().toISOString().split('T')[0] },
})

defineEmits(['open-event'])

// ─── MODE ─────────────────────────────────────────────────────
const viewMode   = ref('week')
const currentDate = ref(new Date(props.initialDate))

const HOUR_HEIGHT    = 56
const PPM            = HOUR_HEIGHT / 60
const CALENDAR_START = 8
const CALENDAR_END   = 18
const SNAP_MINUTES   = 30

const timeSlots = ['8h00','9h00','10h00','11h00','12h00','13h00','14h00','15h00','16h00','17h00','18h00']
const dayNames  = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim']

// ─── NAVIGATION ───────────────────────────────────────────────
function prev() {
  if (viewMode.value === 'week') {
    currentDate.value = offsetDays(currentDate.value, -7)
  } else {
    const d = new Date(currentDate.value)
    d.setDate(1)
    d.setMonth(d.getMonth() - 1)
    currentDate.value = d
  }
}
function next() {
  if (viewMode.value === 'week') {
    currentDate.value = offsetDays(currentDate.value, +7)
  } else {
    const d = new Date(currentDate.value)
    d.setDate(1)
    d.setMonth(d.getMonth() + 1)
    currentDate.value = d
  }
}
function goToToday() { currentDate.value = new Date() }
function offsetDays(date, days) {
  const d = new Date(date)
  d.setDate(d.getDate() + days)
  return d
}

// ─── VUE SEMAINE ──────────────────────────────────────────────
const weekDays = computed(() => {
  const days  = []
  const start = getStartOfWeek(currentDate.value)
  for (let i = 0; i < 7; i++) {
    const d = new Date(start)
    d.setDate(d.getDate() + i)
    days.push({
      date:      d.toISOString().split('T')[0],
      dayName:   dayNames[i],
      dayNumber: d.getDate(),
      isToday:   d.toDateString() === new Date().toDateString(),
    })
  }
  return days
})

const formatDateRange = computed(() => {
  const s = new Date(weekDays.value[0].date)
  const e = new Date(weekDays.value[6].date)
  return `${s.getDate()}–${e.getDate()} ${e.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}`
})

function getStartOfWeek(date) {
  const d   = new Date(date)
  const day = d.getDay()
  d.setDate(d.getDate() - day + (day === 0 ? -6 : 1))
  return d
}

// ─── VUE MOIS ─────────────────────────────────────────────────
const monthNames = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']

const formatMonthTitle = computed(() => {
  return `${monthNames[currentDate.value.getMonth()]} ${currentDate.value.getFullYear()}`
})

const monthCells = computed(() => {
  const year  = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  const today = new Date().toDateString()

  const firstDay = new Date(year, month, 1)
  const lastDay  = new Date(year, month + 1, 0)

  // Décalage lundi=0
  let startOffset = firstDay.getDay() - 1
  if (startOffset < 0) startOffset = 6

  const cells = []
  // Jours du mois précédent
  for (let i = startOffset - 1; i >= 0; i--) {
    const d = new Date(year, month, -i)
    cells.push(makeCell(d, false, today))
  }
  // Jours du mois courant
  for (let i = 1; i <= lastDay.getDate(); i++) {
    const d = new Date(year, month, i)
    cells.push(makeCell(d, true, today))
  }
  // Compléter jusqu'à multiple de 7
  while (cells.length % 7 !== 0) {
    const d = new Date(year, month + 1, cells.length - lastDay.getDate() - startOffset + 1)
    cells.push(makeCell(d, false, today))
  }
  return cells
})

function makeCell(d, isCurrentMonth, today) {
  const dateStr = d.toISOString().split('T')[0]
  return {
    date:           dateStr,
    dayNumber:      d.getDate(),
    isCurrentMonth,
    isToday:        d.toDateString() === today,
    events:         props.events.filter(e => e.date === dateStr)
                      .sort((a, b) => (a.startTime || '').localeCompare(b.startTime || '')),
  }
}

// ─── ÉVÉNEMENTS SEMAINE ────────────────────────────────────────
function getEventsForDay(date) {
  return props.events.filter(e => e.date === date)
}
function getEventColor(priority) {
  return { urgent: 'event-urgent', high: 'event-high', medium: 'event-medium', low: 'event-low' }[priority] ?? 'event-default'
}
function getEventPosition(event) {
  const [sh, sm] = (event.startTime || '08:00').split(':').map(Number)
  const [eh, em] = (event.endTime   || '10:00').split(':').map(Number)
  const top    = (sh - CALENDAR_START) * 60 + sm
  const height = (eh - sh) * 60 + (em - sm)
  return {
    top:    `${Math.max(0, top    * PPM)}px`,
    height: `${Math.max(20, height * PPM)}px`,
  }
}

// ─── DRAG & DROP MOIS ─────────────────────────────────────────
const monthDraggingEvent = ref(null)
const monthDragOver      = ref(null)

function onMonthDragStart(e, event) {
  monthDraggingEvent.value = event
  const ghost = document.createElement('div')
  ghost.style.opacity = '0'
  document.body.appendChild(ghost)
  e.dataTransfer.setDragImage(ghost, 0, 0)
  setTimeout(() => document.body.removeChild(ghost), 0)
}
function onMonthDragEnd() {
  monthDraggingEvent.value = null
  monthDragOver.value      = null
}
function onMonthDrop(newDate) {
  if (!monthDraggingEvent.value || monthDraggingEvent.value.date === newDate) {
    onMonthDragEnd()
    return
  }
  const missionId = monthDraggingEvent.value.id
  const startTime = monthDraggingEvent.value.startTime
  onMonthDragEnd()
  router.patch(route('missions.reschedule', missionId), {
    date:       newDate,
    start_time: startTime,
  }, { preserveScroll: true })
}

// ─── DRAG & DROP SEMAINE ───────────────────────────────────────
const calendarGrid  = ref(null)
const draggingEvent = ref(null)
const dragOffsetY   = ref(0)
const dragOverIndex = ref(null)
const ghostMinutes  = ref(null)

const ghostStyle = computed(() => {
  if (ghostMinutes.value === null || !draggingEvent.value) return null
  return {
    top:    `${ghostMinutes.value * PPM}px`,
    height: `${Math.max(20, draggingEvent.value.duration * 60 * PPM)}px`,
  }
})
const ghostTimeLabel = computed(() => {
  if (ghostMinutes.value === null) return ''
  const abs = CALENDAR_START * 60 + ghostMinutes.value
  return `${String(Math.floor(abs / 60)).padStart(2,'0')}:${String(abs % 60).padStart(2,'0')}`
})

function onDragStart(e, event) {
  draggingEvent.value = event
  const rect = e.currentTarget.getBoundingClientRect()
  dragOffsetY.value = e.clientY - rect.top
  const ghost = document.createElement('div')
  ghost.style.opacity = '0'
  document.body.appendChild(ghost)
  e.dataTransfer.setDragImage(ghost, 0, 0)
  setTimeout(() => document.body.removeChild(ghost), 0)
}
function onDragOver(e, dayIndex) {
  dragOverIndex.value = dayIndex
  if (!calendarGrid.value) return
  const gridRect   = calendarGrid.value.getBoundingClientRect()
  const relY       = e.clientY - gridRect.top - dragOffsetY.value
  const snapped    = Math.round((relY / PPM) / SNAP_MINUTES) * SNAP_MINUTES
  const maxMinutes = (CALENDAR_END - CALENDAR_START) * 60
  ghostMinutes.value = Math.max(0, Math.min(snapped, maxMinutes - (draggingEvent.value?.duration * 60 || 30)))
}
function onDragLeave(e) {
  if (!e.currentTarget.contains(e.relatedTarget)) {
    dragOverIndex.value = null
    ghostMinutes.value  = null
  }
}
function onDragEnd() {
  draggingEvent.value = null
  dragOffsetY.value   = 0
  dragOverIndex.value = null
  ghostMinutes.value  = null
}
function onDrop(e, day) {
  if (!draggingEvent.value || ghostMinutes.value === null) return
  const absMinutes = CALENDAR_START * 60 + ghostMinutes.value
  const newTime    = `${String(Math.floor(absMinutes / 60)).padStart(2,'0')}:${String(absMinutes % 60).padStart(2,'0')}`
  const missionId  = draggingEvent.value.id
  onDragEnd()
  router.patch(route('missions.reschedule', missionId), { date: day.date, start_time: newTime }, { preserveScroll: true })
}
</script>

<style scoped>
.cal-wrapper { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 24px; }

/* ─── HEADER ──────────────────────────────────────────── */
.cal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}
.cal-title { font-size: 18px; font-weight: 700; color: #1a1f2e; margin: 0 0 2px; }
.cal-sub   { font-size: 13px; color: #9ca3af; margin: 0; text-transform: capitalize; }
.cal-controls { display: flex; align-items: center; gap: 6px; }

.view-toggle {
  display: flex;
  background: #f3f4f6;
  border-radius: 8px;
  padding: 3px;
  margin-right: 6px;
}
.toggle-btn {
  padding: 5px 14px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
  background: transparent;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}
.toggle-btn.active { background: white; color: #1a1f2e; box-shadow: 0 1px 4px rgba(0,0,0,.1); }

.nav-btn {
  background: none;
  border: none;
  padding: 6px;
  border-radius: 8px;
  cursor: pointer;
  color: #6b7280;
  display: flex;
  transition: background 0.15s;
}
.nav-btn:hover { background: #f3f4f6; }
.nav-icon { width: 18px; height: 18px; }

.today-btn {
  padding: 6px 14px;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}
.today-btn:hover { border-color: #4f6fee; color: #4f6fee; }

/* ─── VUE SEMAINE ─────────────────────────────────────── */
.time-slot-label {
  height: 56px; padding: 6px 16px;
  font-size: 13px; color: #9ca3af;
  border-bottom: 1px solid #f3f4f6;
  display: flex; align-items: flex-start;
}
.time-slot-cell { height: 56px; border-bottom: 1px solid #f3f4f6; }
.day-column { cursor: default; }
.drag-over  { background: rgba(79, 111, 238, 0.04) !important; }

.cal-event {
  position: absolute; left: 3px; right: 3px;
  border-radius: 8px; padding: 6px 8px; color: white;
  cursor: grab; transition: opacity 0.15s, box-shadow 0.15s;
  overflow: hidden; z-index: 10; user-select: none;
}
.cal-event:active { cursor: grabbing; }
.cal-event:hover  { box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
.cal-event.is-dragging { opacity: 0.35; box-shadow: none; }
.event-title { font-size: 12px; font-weight: 600; line-height: 1.3; }
.event-time  { font-size: 11px; opacity: 0.85; margin-top: 2px; }
.event-drag-hint { font-size: 10px; opacity: 0; margin-top: 2px; transition: opacity 0.15s; }
.cal-event:hover .event-drag-hint { opacity: 0.7; }

.event-urgent  { background: linear-gradient(135deg, #dc2626, #b91c1c); }
.event-high    { background: linear-gradient(135deg, #ea580c, #c2410c); }
.event-medium  { background: linear-gradient(135deg, #4f6fee, #3d5cdb); }
.event-low     { background: linear-gradient(135deg, #059669, #047857); }
.event-default { background: linear-gradient(135deg, #6b7280, #4b5563); }

.cal-ghost {
  position: absolute; left: 3px; right: 3px;
  border-radius: 8px; background: rgba(79,111,238,0.25);
  border: 2px dashed #4f6fee; z-index: 20; pointer-events: none;
  display: flex; align-items: flex-start; padding: 5px 8px;
}
.ghost-time { font-size: 12px; font-weight: 700; color: #4f6fee; }

/* ─── VUE MOIS ────────────────────────────────────────── */
.month-grid-wrap { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }

.month-dow-row {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}
.month-dow {
  padding: 10px 0;
  text-align: center;
  font-size: 12px;
  font-weight: 700;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.month-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
}
.month-cell {
  min-height: 110px;
  border-right: 1px solid #f3f4f6;
  border-bottom: 1px solid #f3f4f6;
  padding: 8px 6px 6px;
  background: white;
}
.month-cell:nth-child(7n) { border-right: none; }

.cell-other-month { background: #fafafa; }
.cell-other-month .cell-number { color: #d1d5db; }
.cell-today { background: #eef2ff; }
.cell-today .cell-number {
  background: #4f6fee;
  color: white;
  border-radius: 50%;
  width: 24px; height: 24px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700;
}

.cell-number {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
  width: 24px; height: 24px;
  display: flex; align-items: center; justify-content: center;
}

.cell-events { display: flex; flex-direction: column; gap: 2px; }

.month-pill {
  font-size: 11px;
  font-weight: 600;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  cursor: pointer;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  transition: opacity 0.15s;
}
.month-pill:hover { opacity: 0.85; }

.pill-urgent  { background: #dc2626; }
.pill-high    { background: #ea580c; }
.pill-medium  { background: #4f6fee; }
.pill-low     { background: #059669; }

.cell-drag-over {
  background: rgba(79, 111, 238, 0.07) !important;
  outline: 2px dashed #4f6fee;
  outline-offset: -2px;
}
.pill-dragging {
  opacity: 0.35;
}

.month-more {
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  padding: 2px 6px;
  cursor: pointer;
  border-radius: 4px;
}
.month-more:hover { background: #f3f4f6; }
</style>
