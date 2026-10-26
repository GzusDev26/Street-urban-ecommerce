<script setup>
defineProps({
  hex: { type: String, default: null },
});
</script>

<template>
  <div v-if="hex" :style="{ backgroundColor: hex }"></div>
  <div
    v-else
    class="bg-surface"
    style="background-image: linear-gradient(135deg, transparent calc(50% - 1px), #d1d5db calc(50% - 1px), #d1d5db calc(50% + 1px), transparent calc(50% + 1px));"
    title="Sin color registrado"
  ></div>
</template>
