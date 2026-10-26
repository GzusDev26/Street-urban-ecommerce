<script setup>
defineProps({
  icono: { type: String, required: true },
  tono: { type: String, default: 'bg-orange-50 text-[#ff8c42]' },
  titulo: { type: String, required: true },
  principal: { type: String, required: true },
  detalle: { type: String, default: '' },
});
</script>

<template>
  <div class="bg-surface rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
    <span :class="['w-12 h-12 rounded-2xl flex items-center justify-center text-lg flex-shrink-0', tono]" aria-hidden="true"><i :class="icono"></i></span>
    <div class="min-w-0">
      <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ titulo }}</p>
      <p class="text-base font-bold text-gray-900 truncate">{{ principal }}</p>
      <p v-if="detalle" class="text-sm text-gray-500 truncate">{{ detalle }}</p>
    </div>
  </div>
</template>
