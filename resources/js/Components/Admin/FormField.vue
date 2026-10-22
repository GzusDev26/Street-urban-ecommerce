<script setup>
import { provideFieldId } from './useFieldId';

defineProps({
  label: { type: String, required: true },
  optional: { type: Boolean, default: false },
  hint: { type: String, default: '' },
  // Para controles sin un único campo asociado (paletas, grupos): la etiqueta no apunta a un id.
  group: { type: Boolean, default: false },
});

const id = provideFieldId();
</script>

<template>
  <div>
    <div class="flex items-baseline justify-between mb-1.5">
      <span v-if="group" class="text-sm font-semibold text-gray-700">{{ label }}</span>
      <label v-else :for="id" class="text-sm font-semibold text-gray-700">{{ label }}</label>
      <span v-if="optional" class="text-xs text-gray-400">Opcional</span>
    </div>
    <slot />
    <p v-if="hint" class="text-xs text-gray-400 mt-1.5">{{ hint }}</p>
  </div>
</template>
