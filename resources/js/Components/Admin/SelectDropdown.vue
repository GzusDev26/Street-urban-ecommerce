<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  options: { type: Array, required: true }, // [{ value, label }]
  placeholder: { type: String, default: 'Selecciona...' },
  size: { type: String, default: 'md' }, // 'md' para formularios, 'sm' para barras de filtros
});

const model = defineModel({ type: String, default: '' });

const isOpen = ref(false);

const currentLabel = computed(() => {
  const found = props.options.find(o => o.value === model.value);
  return found ? found.label : props.placeholder;
});

const seleccionar = (value) => {
  model.value = value;
  isOpen.value = false;
};

const cerrarConRetraso = () => {
  setTimeout(() => {
    isOpen.value = false;
  }, 150);
};
</script>

<template>
  <div class="relative">
    <button
      type="button"
      @click="isOpen = !isOpen"
      @blur="cerrarConRetraso"
      @keydown.esc.stop="isOpen = false"
      aria-haspopup="listbox"
      :aria-expanded="isOpen"
      :class="[
        size === 'sm' ? 'h-10' : 'h-11',
        'w-full flex items-center justify-between gap-2 bg-white border rounded-xl text-sm px-4 text-left transition',
        'focus:outline-none focus:ring-4 focus:ring-orange-100',
        isOpen ? 'border-[#ff8c42]' : 'border-gray-200 hover:border-gray-300 focus:border-[#ff8c42]',
        model ? 'text-gray-900' : 'text-gray-400',
      ]"
    >
      <span class="truncate">{{ currentLabel }}</span>
      <i :class="['fa-solid fa-chevron-down text-gray-400 text-xs flex-shrink-0 transition-transform duration-150', isOpen ? 'rotate-180' : '']"></i>
    </button>

    <div
      v-if="isOpen"
      role="listbox"
      class="absolute z-20 mt-1.5 w-full bg-white border border-gray-100 rounded-xl shadow-xl ring-1 ring-black/5 p-1 max-h-56 overflow-y-auto"
    >
      <button
        v-for="op in options"
        :key="op.value"
        type="button"
        role="option"
        :aria-selected="model === op.value"
        @mousedown.prevent="seleccionar(op.value)"
        :class="[
          'w-full flex items-center justify-between gap-2 text-left px-3 py-2 rounded-lg text-sm transition-colors',
          model === op.value ? 'bg-orange-50 text-[#ff8c42] font-semibold' : 'text-gray-700 hover:bg-gray-50',
        ]"
      >
        <span class="truncate">{{ op.label }}</span>
        <i v-if="model === op.value" class="fa-solid fa-check text-xs flex-shrink-0"></i>
      </button>
    </div>
  </div>
</template>
