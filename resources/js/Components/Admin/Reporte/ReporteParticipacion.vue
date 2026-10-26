<script setup>
import { computed, ref } from 'vue';
import { dinero, porcentaje } from './format';

// Parte del todo en una sola barra segmentada (2 px de superficie entre segmentos). Los 4 primeros usan la paleta
// en orden fijo; el resto se pliega en "Otras". El valor se lee en la leyenda, no solo en el color.
const props = defineProps({
  titulo: { type: String, required: true },
  icono: { type: String, required: true },
  filas: { type: Array, required: true }, // [{ label, total, detalle }]
  total: { type: Number, required: true },
});

const COLORES = ['var(--viz-1)', 'var(--viz-2)', 'var(--viz-3)', 'var(--viz-4)'];

const segmentos = computed(() => {
  const principales = props.filas.slice(0, 4).map((f, i) => ({ ...f, color: COLORES[i] }));
  const resto = props.filas.slice(4);
  if (resto.length) {
    principales.push({ label: `Otras (${resto.length})`, total: resto.reduce((a, f) => a + f.total, 0), detalle: resto.map((f) => f.label).join(', '), color: 'var(--viz-otro)' });
  }
  return principales;
});

const activo = ref(null);
const lectura = computed(() => (activo.value == null ? null : segmentos.value[activo.value]));
// Texto dentro del relleno: blanco, salvo sobre el amarillo.
const tinta = (c) => (c === 'var(--viz-4)' ? '#0b0b0b' : '#ffffff');
</script>

<template>
  <section class="viz bg-surface rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
    <header class="flex items-center gap-2.5 mb-5">
      <span class="w-9 h-9 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center" aria-hidden="true"><i :class="icono" class="text-sm"></i></span>
      <h3 class="font-bold text-gray-900">{{ titulo }}</h3>
    </header>

    <template v-if="filas.length">
      <p class="h-5 text-sm text-gray-600 truncate" aria-live="polite">
        <template v-if="lectura"><b class="text-gray-900">{{ lectura.label }}</b> · {{ dinero(lectura.total) }} · {{ porcentaje(lectura.total, total, 1) }}</template>
        <template v-else>Pasa el cursor por la barra para ver el detalle</template>
      </p>

      <div class="mt-2 flex gap-[2px] h-11" role="list" @pointerleave="activo = null">
        <div
          v-for="(s, i) in segmentos"
          :key="s.label"
          role="listitem"
          tabindex="0"
          :aria-label="`${s.label}: ${dinero(s.total)}, ${porcentaje(s.total, total, 1)}`"
          class="relative flex items-center justify-center first:rounded-l-xl last:rounded-r-xl text-xs font-bold overflow-hidden transition-opacity duration-150 outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-900"
          :style="{ width: (total > 0 ? Math.max(2, (s.total / total) * 100) : 0) + '%', background: s.color, color: tinta(s.color), opacity: activo == null || activo === i ? 1 : 0.45 }"
          @pointerenter="activo = i"
          @focus="activo = i"
          @blur="activo = null"
        >
          <span v-if="s.total / total >= 0.14">{{ porcentaje(s.total, total) }}</span>
        </div>
      </div>

      <ul class="mt-5 space-y-1">
        <li
          v-for="(s, i) in segmentos"
          :key="s.label"
          class="flex items-center gap-3 rounded-lg px-2 py-2 transition-colors"
          :class="activo === i ? 'bg-gray-50' : ''"
          @pointerenter="activo = i"
          @pointerleave="activo = null"
        >
          <span class="w-3 h-3 rounded-sm flex-shrink-0" :style="{ background: s.color }" aria-hidden="true"></span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-gray-800 truncate">{{ s.label }}</span>
            <span v-if="s.detalle" class="block text-xs text-gray-500 truncate">{{ s.detalle }}</span>
          </span>
          <span class="text-right tabular-nums">
            <b class="block text-sm text-gray-900">{{ dinero(s.total) }}</b>
            <span class="text-xs text-gray-500">{{ porcentaje(s.total, total, 1) }}</span>
          </span>
        </li>
      </ul>
    </template>
    <p v-else class="text-sm text-gray-500">Sin datos en este período.</p>
  </section>
</template>
