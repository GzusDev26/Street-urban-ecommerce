<script setup>
defineProps({
  tabs: { type: Array, required: true }, // [{ value, label, count }]
});

const model = defineModel({ type: String, default: '' });
</script>

<template>
  <div class="inline-flex p-1 bg-gray-100 rounded-xl" role="tablist">
    <button
      v-for="tab in tabs"
      :key="tab.value"
      type="button"
      role="tab"
      :aria-selected="model === tab.value"
      @click="model = tab.value"
      :class="[
        'flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-semibold transition-all',
        model === tab.value ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700',
      ]"
    >
      {{ tab.label }}
      <span
        :class="[
          'text-xs font-bold px-1.5 py-0.5 rounded-md min-w-[1.5rem] text-center',
          model === tab.value ? 'bg-orange-100 text-[#ff8c42]' : 'bg-gray-200/70 text-gray-500',
        ]"
      >{{ tab.count }}</span>
    </button>
  </div>
</template>
