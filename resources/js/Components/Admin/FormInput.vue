<script setup>
import { useFieldId } from './useFieldId';

defineOptions({ inheritAttrs: false });

defineProps({
  icon: { type: String, default: '' },
  prefix: { type: String, default: '' }, // texto fijo a la izquierda, ej. la moneda
});

const fieldId = useFieldId();
const model = defineModel({ type: [String, Number], default: '' });
</script>

<template>
  <div class="relative">
    <i v-if="icon" :class="[icon, 'absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none']"></i>
    <span v-if="prefix" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none">{{ prefix }}</span>
    <input
      v-model="model"
      :id="fieldId"
      v-bind="$attrs"
      :class="[
        'w-full h-11 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition',
        'hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100',
        icon ? 'pl-10 pr-4' : prefix ? 'pl-9 pr-4' : 'px-4',
      ]"
    >
  </div>
</template>
