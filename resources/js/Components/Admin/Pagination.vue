<script setup>
import { computed } from 'vue';

const props = defineProps({
  totalItems: { type: Number, required: true },
  perPage: { type: Number, default: 15 },
});

const page = defineModel({ type: Number, default: 1 });

const totalPages = computed(() => Math.max(1, Math.ceil(props.totalItems / props.perPage)));

const rangoInicio = computed(() => props.totalItems === 0 ? 0 : (page.value - 1) * props.perPage + 1);
const rangoFin = computed(() => Math.min(page.value * props.perPage, props.totalItems));

const paginas = computed(() => {
  const total = totalPages.value;
  const actual = page.value;
  const delta = 1;
  const rango = [];

  for (let i = Math.max(1, actual - delta); i <= Math.min(total, actual + delta); i++) {
    rango.push(i);
  }
  if (rango[0] > 1) {
    if (rango[0] > 2) rango.unshift('...');
    rango.unshift(1);
  }
  if (rango[rango.length - 1] < total) {
    if (rango[rango.length - 1] < total - 1) rango.push('...');
    rango.push(total);
  }
  return rango;
});

const irA = (p) => {
  if (typeof p !== 'number' || p === page.value) return;
  page.value = p;
};
</script>

<template>
  <div v-if="totalPages > 1" class="flex flex-wrap items-center justify-between gap-3 pt-4 mt-2 border-t border-gray-100">
    <p class="text-xs text-gray-400">
      Mostrando {{ rangoInicio }}–{{ rangoFin }} de {{ totalItems }}
    </p>
    <div class="flex items-center gap-1">
      <button
        @click="irA(page - 1)"
        :disabled="page === 1"
        class="w-10 h-10 sm:w-8 sm:h-8 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent flex items-center justify-center transition-colors"
        aria-label="Página anterior"
      >
        <i class="fa-solid fa-chevron-left text-xs"></i>
      </button>

      <template v-for="(p, idx) in paginas" :key="idx">
        <span v-if="p === '...'" class="w-6 sm:w-8 h-10 sm:h-8 flex items-center justify-center text-gray-300 text-xs">…</span>
        <button
          v-else
          @click="irA(p)"
          :class="[
            'w-10 h-10 sm:w-8 sm:h-8 rounded-lg text-sm font-semibold flex items-center justify-center transition-colors',
            p === page ? 'bg-[#ff8c42] text-white' : 'text-gray-500 hover:bg-gray-100',
          ]"
        >
          {{ p }}
        </button>
      </template>

      <button
        @click="irA(page + 1)"
        :disabled="page === totalPages"
        class="w-10 h-10 sm:w-8 sm:h-8 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent flex items-center justify-center transition-colors"
        aria-label="Página siguiente"
      >
        <i class="fa-solid fa-chevron-right text-xs"></i>
      </button>
    </div>
  </div>
</template>
