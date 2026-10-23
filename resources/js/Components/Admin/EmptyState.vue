<script setup>
defineProps({
  icon: { type: String, default: 'fa-solid fa-inbox' },
  title: { type: String, required: true },
  text: { type: String, default: '' },
});
</script>

<template>
  <div class="px-6 pb-6">
    <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/60 py-14 px-6 text-center">
      <div class="w-14 h-14 rounded-2xl bg-white shadow-sm text-gray-300 flex items-center justify-center mx-auto mb-4">
        <i :class="icon" class="text-2xl"></i>
      </div>
      <p class="font-semibold text-gray-700">{{ title }}</p>
      <p v-if="text" class="text-sm text-gray-500 mt-1">{{ text }}</p>
      <div v-if="$slots.default" class="mt-5 flex justify-center"><slot /></div>
    </div>
  </div>
</template>
