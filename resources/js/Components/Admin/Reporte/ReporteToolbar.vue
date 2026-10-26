<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';
import { rangosRapidos } from './format';

// Barra de filtros de un reporte: rangos rápidos, fechas y listas, y el botón de descarga en Excel
// (que descarga exactamente lo filtrado). Los filtros se aplican solos al cambiarlos.
const props = defineProps({
  filtros: { type: Object, required: true }, // { desde, hasta, ...claves de las listas }
  listas: { type: Array, default: () => [] }, // [{ clave, etiqueta, todas, opciones: [texto] }]
  rutaReporte: { type: String, required: true },
  rutaExcel: { type: String, required: true },
});

const form = reactive({ desde: props.filtros.desde || '', hasta: props.filtros.hasta || '' });
props.listas.forEach((l) => { form[l.clave] = props.filtros[l.clave] || ''; });

const rangos = rangosRapidos();
const rangoActivo = computed(() => rangos.find((r) => r.desde === form.desde && r.hasta === form.hasta)?.id ?? null);

const consulta = () => Object.fromEntries(Object.entries(form).filter(([, v]) => v));

const aplicar = () => {
  router.get(route(props.rutaReporte), consulta(), { preserveState: true, preserveScroll: true, replace: true });
};

let timer = null;
watch(form, () => {
  clearTimeout(timer);
  timer = setTimeout(aplicar, 250);
});

const elegirRango = (r) => {
  form.desde = r.desde;
  form.hasta = r.hasta;
};

const hayFiltros = computed(() => Object.values(form).some(Boolean));
const limpiar = () => { Object.keys(form).forEach((k) => { form[k] = ''; }); };

const urlExcel = computed(() => {
  const q = new URLSearchParams(consulta()).toString();
  return route(props.rutaExcel) + (q ? `?${q}` : '');
});

// El navegador no avisa cuándo termina una descarga: se muestra "Preparando…" un momento para confirmar el clic.
const descargando = ref(false);
const alDescargar = () => {
  descargando.value = true;
  setTimeout(() => { descargando.value = false; }, 2500);
};

const opciones = (l) => [{ value: '', label: l.todas }, ...l.opciones.map((o) => ({ value: o, label: o }))];
</script>

<template>
  <section class="flex flex-wrap items-center gap-x-3 gap-y-3" aria-label="Filtros del reporte">
    <div class="flex sm:inline-flex gap-1 p-1 rounded-2xl bg-gray-100 overflow-x-auto sm:overflow-visible w-full sm:w-auto" role="group" aria-label="Rangos rápidos">
      <button
        v-for="r in rangos"
        :key="r.id"
        type="button"
        :aria-pressed="rangoActivo === r.id"
        :class="[
          'h-10 sm:h-9 px-3.5 flex-shrink-0 whitespace-nowrap rounded-xl text-sm font-semibold transition-colors',
          rangoActivo === r.id ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800',
        ]"
        @click="elegirRango(r)"
      >{{ r.label }}</button>
    </div>

    <div class="grid grid-cols-2 gap-2 w-full sm:inline-flex sm:items-center sm:w-auto sm:gap-0 sm:rounded-xl sm:border sm:border-gray-200 sm:bg-surface sm:divide-x sm:divide-gray-200">
      <label class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 sm:pl-3.5 sm:pr-2 text-xs font-semibold text-gray-500 min-w-0">
        Desde
        <input v-model="form.desde" type="date" class="w-full h-11 sm:h-10 min-w-0 rounded-xl border border-gray-200 sm:border-0 bg-surface sm:bg-transparent text-sm text-gray-800 px-3 sm:px-1 focus:ring-0">
      </label>
      <label class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 sm:pl-3.5 sm:pr-2 text-xs font-semibold text-gray-500 min-w-0">
        Hasta
        <input v-model="form.hasta" type="date" class="w-full h-11 sm:h-10 min-w-0 rounded-xl border border-gray-200 sm:border-0 bg-surface sm:bg-transparent text-sm text-gray-800 px-3 sm:px-1 focus:ring-0">
      </label>
    </div>

    <div v-for="l in listas" :key="l.clave" class="w-full sm:w-auto sm:min-w-[200px]">
      <span class="sr-only">{{ l.etiqueta }}</span>
      <SelectDropdown v-model="form[l.clave]" :options="opciones(l)" />
    </div>

    <button v-if="hayFiltros" type="button" class="h-11 sm:h-10 px-2 text-sm font-semibold text-gray-500 hover:text-gray-800 flex items-center gap-1.5" @click="limpiar">
      <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i> Limpiar
    </button>

    <span class="hidden sm:block flex-1"></span>

    <a
      :href="urlExcel"
      download
      class="h-12 sm:h-11 w-full sm:w-auto justify-center px-5 rounded-xl bg-[#1d6f42] hover:bg-[#185c37] active:scale-[0.98] text-white text-sm font-bold flex items-center gap-2.5 shadow-sm transition-all focus:outline-none focus-visible:ring-4 focus-visible:ring-green-200"
      @click="alDescargar"
    >
      <i :class="descargando ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-file-excel'" aria-hidden="true"></i>
      {{ descargando ? 'Preparando…' : 'Descargar Excel' }}
    </a>
  </section>
</template>
