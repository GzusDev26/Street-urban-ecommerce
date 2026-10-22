<script setup>
defineProps({
  tone: { type: String, default: 'gray' }, // 'gray' | 'green' | 'orange' | 'blue' | 'red'
});

const TONOS = {
  gray: ['bg-gray-100 text-gray-500', 'bg-gray-400'],
  green: ['bg-green-50 text-green-700', 'bg-green-500'],
  orange: ['bg-orange-50 text-[#e67e2d]', 'bg-[#ff8c42]'],
  blue: ['bg-blue-50 text-blue-700', 'bg-blue-500'],
  red: ['bg-red-50 text-red-700', 'bg-red-500'],
};
</script>

<template>
  <span :class="['inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap', TONOS[tone][0]]">
    <span :class="['w-1.5 h-1.5 rounded-full', TONOS[tone][1]]"></span>
    <slot />
  </span>
</template>
