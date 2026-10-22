<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: { type: String, default: '' },
  id: { type: Number, default: 0 },
  icon: { type: String, default: '' }, // si se indica, se muestra el ícono en vez de las iniciales
  src: { type: String, default: null },
  tone: { type: String, default: '' }, // fuerza un color en vez del derivado del id
});

// Color pastel fijo por registro (no cambia entre cargas).
const COLORES = [
  'bg-orange-100 text-orange-600',
  'bg-blue-100 text-blue-600',
  'bg-purple-100 text-purple-600',
  'bg-green-100 text-green-600',
  'bg-pink-100 text-pink-600',
  'bg-amber-100 text-amber-700',
];

const color = computed(() => props.tone || COLORES[props.id % COLORES.length]);

const iniciales = computed(() =>
  props.name.split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase()
);
</script>

<template>
  <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 overflow-hidden', color]">
    <img v-if="src" :src="src" :alt="name" class="w-full h-full object-cover" />
    <i v-else-if="icon" :class="icon" class="text-sm"></i>
    <template v-else>{{ iniciales }}</template>
  </div>
</template>
