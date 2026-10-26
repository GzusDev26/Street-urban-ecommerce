<script setup>
import { computed } from 'vue';

// Portada del reporte: la cifra principal (una sola por vista), cómo va contra el período anterior y las demás
// cifras en pequeño. El gráfico entra por el slot.
const props = defineProps({
  etiqueta: { type: String, required: true },
  valor: { type: String, required: true },
  periodo: { type: String, required: true },
  cambio: { type: Object, default: null }, // { pct: number|null, rango: '13 oct. – 19 oct.', sustantivo: 'compras', subirEsBueno: true, neutro: false }
  minis: { type: Array, required: true }, // [{ etiqueta, valor, nota, icono, tono }]
});

const dir = computed(() => {
  const p = props.cambio?.pct;
  if (p == null || Math.abs(p) < 0.05) return 'igual';
  return p > 0 ? 'sube' : 'baja';
});
const bueno = computed(() => dir.value === 'igual' || (dir.value === 'sube') === (props.cambio?.subirEsBueno ?? true));
// Cuando subir o bajar no es ni bueno ni malo (p. ej. cuánto se invirtió), el cambio va en azul neutro.
const clasesCambio = computed(() => {
  if (dir.value === 'igual') return 'bg-gray-100 text-gray-600';
  if (props.cambio?.neutro) return 'bg-blue-50 text-blue-600';
  return bueno.value ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600';
});
</script>

<template>
  <section class="bg-surface rounded-3xl border border-gray-100 shadow-sm overflow-hidden grid grid-cols-1 xl:grid-cols-[380px_minmax(0,1fr)]">
    <div class="p-5 sm:p-7 flex flex-col xl:border-r border-b xl:border-b-0 border-gray-100 bg-gradient-to-br from-orange-50/70 to-transparent">
      <p class="text-sm font-semibold text-[#e67e2d]">{{ periodo }}</p>
      <p class="mt-4 sm:mt-5 text-sm font-medium text-gray-500">{{ etiqueta }}</p>
      <p class="mt-1 text-[2.5rem] sm:text-[3.25rem] leading-none font-black text-gray-900 tracking-tight">{{ valor }}</p>

      <p v-if="cambio" class="mt-3 text-sm">
        <span v-if="cambio.pct == null" class="text-gray-500">En el período anterior ({{ cambio.rango }}) no hubo {{ cambio.sustantivo }}, así que no hay con qué comparar.</span>
        <template v-else>
          <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-bold', clasesCambio]">
            <i :class="['fa-solid text-xs', dir === 'sube' ? 'fa-arrow-up' : dir === 'baja' ? 'fa-arrow-down' : 'fa-equals']" aria-hidden="true"></i>
            {{ dir === 'igual' ? 'Igual' : `${cambio.pct > 0 ? '+' : ''}${cambio.pct.toFixed(1)}%` }}
          </span>
          <span class="ml-2 text-gray-500">vs {{ cambio.rango }}</span>
        </template>
      </p>
      <p v-else class="mt-3 text-sm text-gray-500">Elige un rango de fechas para compararlo con el período anterior.</p>

      <dl class="mt-auto pt-6 sm:pt-7 grid grid-cols-2 gap-x-4 gap-y-4 sm:gap-y-5">
        <div v-for="m in minis" :key="m.etiqueta">
          <dt class="flex items-center gap-2 text-xs font-medium text-gray-500"><i :class="[m.icono, m.tono]" class="text-[11px]" aria-hidden="true"></i>{{ m.etiqueta }}</dt>
          <dd class="mt-0.5 text-xl font-bold text-gray-900 tabular-nums">{{ m.valor }}</dd>
          <dd v-if="m.nota" class="text-xs text-gray-500">{{ m.nota }}</dd>
        </div>
      </dl>
    </div>

    <div class="p-4 sm:p-6 min-w-0"><slot /></div>
  </section>
</template>
