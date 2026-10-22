<script setup>
defineProps({
  label: { type: String, required: true },
  optional: { type: Boolean, default: false },
  hint: { type: String, default: '' },
});
</script>

<template>
  <div>
    <div class="flex items-baseline justify-between mb-1.5">
      <label class="text-sm font-semibold text-gray-700">{{ label }}</label>
      <span v-if="optional" class="text-xs text-gray-400">Opcional</span>
    </div>
    <slot />
    <p v-if="hint" class="text-xs text-gray-400 mt-1.5">{{ hint }}</p>
  </div>
</template>
