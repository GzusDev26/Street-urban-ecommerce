<script setup>
defineProps({
  placeholder: { type: String, default: 'Buscar...' },
});

const model = defineModel({ type: String, default: '' });
</script>

<template>
  <div class="relative">
    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
    <input
      :aria-label="placeholder"
      v-model="model"
      type="text"
      :placeholder="placeholder"
      class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
    >
  </div>
</template>
