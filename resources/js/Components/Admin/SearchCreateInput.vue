<script setup>
import { ref, computed } from 'vue';
import { useFieldId } from './useFieldId';

const props = defineProps({
  options: { type: Array, required: true }, // Array<string>
  placeholder: { type: String, default: 'Elige o escribe uno nuevo...' },
  disabled: { type: Boolean, default: false },
  id: { type: String, default: undefined },
});

const campoId = useFieldId();
const idFinal = computed(() => props.id ?? campoId);
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
      :id="idFinal"
      :aria-label="idFinal ? undefined : placeholder"
      v-model="model"
      :disabled="disabled"
      @focus="showDropdown = true"
      @blur="ocultarConRetraso"
      @keydown.esc.stop="showDropdown = false"
      type="text"
      autocomplete="off"
      :placeholder="placeholder"
      class="w-full h-11 px-4 pr-24 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100 disabled:bg-gray-100 disabled:text-gray-500"
    >

    <span
      v-if="esNuevo"
      class="absolute right-2.5 top-1/2 -translate-y-1/2 flex items-center gap-1 text-[11px] font-bold text-[#e67e2d] bg-orange-50 rounded-md px-2 py-1 pointer-events-none"
    >
      <i class="fa-solid fa-circle-plus"></i> Nuevo
    </span>

    <div
      v-if="showDropdown && !disabled"
      role="listbox"
      class="absolute z-20 mt-1.5 w-full bg-white border border-gray-100 rounded-xl shadow-xl ring-1 ring-black/5 p-1 max-h-56 overflow-y-auto"
    >
      <button
        v-for="op in opcionesFiltradas"
        :key="op"
        type="button"
        role="option"
        :aria-selected="model === op"
        @mousedown.prevent="seleccionar(op)"
        :class="[
          'w-full flex items-center justify-between gap-2 text-left px-3 py-2 rounded-lg text-sm transition-colors',
          model === op ? 'bg-orange-50 text-[#ff8c42] font-semibold' : 'text-gray-700 hover:bg-gray-50',
        ]"
      >
        <span class="truncate">{{ op }}</span>
        <i v-if="model === op" class="fa-solid fa-check text-xs flex-shrink-0"></i>
      </button>
      <div v-if="opcionesFiltradas.length === 0" class="px-3 py-2.5 text-xs text-gray-400">
        No hay coincidencias. Se creará como nuevo.
      </div>
    </div>
  </div>
</template>
