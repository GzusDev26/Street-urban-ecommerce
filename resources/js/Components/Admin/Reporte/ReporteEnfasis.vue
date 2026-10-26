<script setup>
import { computed, ref } from 'vue';
import { dinero, porcentaje } from './format';

// Barras de énfasis: la primera, en el color de acento; las demás, en gris. El valor va en la punta de cada barra.
const props = defineProps({
  titulo: { type: String, required: true },
  icono: { type: String, required: true },
  filas: { type: Array, required: true }, // [{ label, total, detalle }]
  total: { type: Number, required: true },
  limite: { type: Number, default: 6 },
});

const abierto = ref(false);
const visibles = computed(() => (abierto.value ? props.filas : props.filas.slice(0, props.limite)));
const maximo = computed(() => Math.max(0, ...props.filas.map((f) => f.total)));
const ancho = (v) => (maximo.value > 0 ? Math.max(1.5, (v / maximo.value) * 100) : 0);
</script>

<template>
  <section class="viz bg-surface rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
    <header class="flex items-center gap-2.5 mb-5">
      <span class="w-9 h-9 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center" aria-hidden="true"><i :class="icono" class="text-sm"></i></span>
      <h3 class="font-bold text-gray-900">{{ titulo }}</h3>
    </header>

    <ul v-if="filas.length" class="space-y-4">
      <li v-for="(f, i) in visibles" :key="f.label">
        <div class="flex items-baseline justify-between gap-3 text-sm">
          <span class="font-semibold text-gray-800 truncate">{{ f.label }}</span>
          <span class="flex-shrink-0 tabular-nums"><b class="text-gray-900">{{ dinero(f.total) }}</b> <span class="text-xs text-gray-500">{{ porcentaje(f.total, total) }}</span></span>
        </div>
        <div class="mt-1.5 h-2.5 w-full" role="presentation">
          <div class="h-full rounded-r-[4px]" :style="{ width: ancho(f.total) + '%', background: i === 0 ? 'var(--viz-2)' : 'var(--viz-otro)' }"></div>
        </div>
        <p v-if="f.detalle" class="mt-1 text-xs text-gray-500">{{ f.detalle }}</p>
      </li>
    </ul>
    <p v-else class="text-sm text-gray-500">Sin datos en este período.</p>

    <button v-if="filas.length > limite" type="button" class="mt-4 text-sm font-semibold text-[#e67e2d] hover:underline" :aria-expanded="abierto" @click="abierto = !abierto">
      {{ abierto ? 'Ver menos' : `Ver las ${filas.length}` }}
    </button>
  </section>
</template>
