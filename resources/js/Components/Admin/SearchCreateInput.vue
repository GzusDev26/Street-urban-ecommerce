<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  options: { type: Array, required: true }, // Array<string>
  placeholder: { type: String, default: 'Elige o escribe uno nuevo...' },
  disabled: { type: Boolean, default: false },
  id: { type: String, default: undefined },
});

const model = defineModel({ type: String, default: '' });

const showDropdown = ref(false);

const opcionesFiltradas = computed(() => {
  const q = (model.value || '').trim().toLowerCase();
  if (!q) return props.options;
  return props.options.filter(o => o.toLowerCase().includes(q));
});

const esNuevo = computed(() => {
  const q = (model.value || '').trim();
  return q.length > 0 && !props.options.some(o => o.toLowerCase() === q.toLowerCase());
});

const seleccionar = (valor) => {
  model.value = valor;
  showDropdown.value = false;
};

const ocultarConRetraso = () => {
  setTimeout(() => {
    showDropdown.value = false;
  }, 150);
};
</script>

<template>
  <div class="relative">
    <input
      :id="id"
      :aria-label="id ? undefined : placeholder"
      v-model="model"
      :disabled="disabled"
      @focus="showDropdown = true"
      @blur="ocultarConRetraso"
      type="text"
      :placeholder="placeholder"
      class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white disabled:bg-gray-100 disabled:text-gray-500"
    >

    <div
      v-if="showDropdown && !disabled"
      class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden max-h-56 overflow-y-auto"
    >
      <button
        v-for="(op, idx) in opcionesFiltradas"
        :key="op"
        type="button"
        @mousedown.prevent="seleccionar(op)"
        :class="['w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50', idx > 0 ? 'border-t border-gray-50' : '', model === op ? 'text-[#ff8c42] font-semibold' : 'text-gray-600']"
      >
        {{ op }}
      </button>
      <div v-if="opcionesFiltradas.length === 0" class="px-4 py-3 text-xs text-gray-400">
        No hay coincidencias
      </div>
    </div>

    <p v-if="esNuevo" class="text-xs text-[#ff8c42] mt-1">
      <i class="fa-solid fa-circle-plus"></i> Se creará como nuevo
    </p>
  </div>
</template>
