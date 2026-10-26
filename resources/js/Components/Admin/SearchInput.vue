<script setup>
defineProps({
  placeholder: { type: String, default: 'Buscar...' },
});

const model = defineModel({ type: String, default: '' });
</script>

<template>
  <div class="relative">
    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
    <input
      v-model="model"
      type="text"
      :placeholder="placeholder"
      :aria-label="placeholder"
      class="w-full h-10 pl-10 pr-9 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100"
    >
    <button
      v-if="model"
      type="button"
      @click="model = ''"
      aria-label="Limpiar búsqueda"
      class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center transition-colors"
    >
      <i class="fa-solid fa-xmark text-xs"></i>
    </button>
  </div>
</template>
