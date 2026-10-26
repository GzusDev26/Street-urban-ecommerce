<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({ type: String, default: '' });
</script>

<template>
  <textarea
    v-model="model"
    v-bind="$attrs"
    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 resize-none transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100"
  ></textarea>
</template>
