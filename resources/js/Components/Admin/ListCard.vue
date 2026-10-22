<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
});
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-4 p-6 pb-5">
      <div>
        <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
        <p v-if="subtitle" class="text-sm text-gray-500 mt-0.5">{{ subtitle }}</p>
      </div>
      <slot name="action" />
    </div>

    <div v-if="$slots.toolbar" class="flex flex-wrap items-center justify-between gap-3 px-6 pb-5">
      <slot name="toolbar" />
    </div>

    <slot />
  </div>
</template>
