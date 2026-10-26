<script setup>
defineProps({
  icon: { type: String, required: true },
  label: { type: String, required: true },
  tone: { type: String, default: 'gray' }, // 'gray' | 'purple' | 'blue' | 'red' | 'green'
});

const TONOS = {
  gray: 'hover:text-gray-700 hover:bg-gray-100',
  purple: 'hover:text-purple-600 hover:bg-purple-50',
  blue: 'hover:text-blue-600 hover:bg-blue-50',
  red: 'hover:text-red-600 hover:bg-red-50',
  green: 'hover:text-green-600 hover:bg-green-50',
};
</script>

<template>
  <button
    type="button"
    :title="label"
    :aria-label="label"
    :class="['w-11 h-11 sm:w-9 sm:h-9 rounded-lg text-gray-400 flex items-center justify-center transition-colors', TONOS[tone]]"
  >
    <i :class="icon" class="text-sm"></i>
  </button>
</template>
