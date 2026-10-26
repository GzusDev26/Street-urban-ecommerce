<script setup>
import { computed, ref, watch } from 'vue';
import { useFieldId } from './useFieldId';

// Campo de elegir escribiendo: se teclea para filtrar los existentes y, si lo escrito no existe y
// `canCreate` está activo, aparece "+ Crear «lo escrito»". Reemplaza al desplegable que no se podía escribir.
const props = defineProps({
  options: { type: Array, required: true }, // [{ value, label, hint? }]
  placeholder: { type: String, default: 'Escribe o elige...' },
  disabled: { type: Boolean, default: false },
  canCreate: { type: Boolean, default: false },
  id: { type: String, default: undefined },
});

const emit = defineEmits(['create']);

const model = defineModel({ type: String, default: '' });
const campoId = useFieldId();
const idFinal = computed(() => props.id ?? campoId);

const texto = ref(model.value);
const abierto = ref(false);
const tecleado = ref(false); // solo se filtra cuando el usuario escribió algo; al abrir se ven todas
const activo = ref(0);

watch(model, (v) => { if (!abierto.value) texto.value = v; });

const normal = (s) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();

const filtradas = computed(() => {
  if (!tecleado.value || !texto.value.trim()) return props.options;
  const q = normal(texto.value);
  return props.options.filter((o) => normal(o.label).includes(q));
});

const puedeCrear = computed(() => {
  const t = texto.value.trim();
  return props.canCreate && tecleado.value && t.length > 0 && !props.options.some((o) => normal(o.label) === normal(t));
});

const total = computed(() => filtradas.value.length + (puedeCrear.value ? 1 : 0));

const abrir = (e) => {
  if (props.disabled) return;
  abierto.value = true;
  tecleado.value = false;
  activo.value = Math.max(0, filtradas.value.findIndex((o) => o.value === model.value));
  e?.target?.select?.();
};

const cerrar = () => {
  abierto.value = false;
  tecleado.value = false;
  texto.value = model.value; // lo escrito que no se eligió se descarta
};

const cerrarConRetraso = () => setTimeout(cerrar, 150);

const elegir = (op) => {
  model.value = op.value;
  texto.value = op.label;
  abierto.value = false;
  tecleado.value = false;
};

const crear = () => {
  const t = texto.value.trim();
  abierto.value = false;
  tecleado.value = false;
  texto.value = model.value;
  emit('create', t);
};

const alEscribir = () => {
  tecleado.value = true;
  abierto.value = true;
  activo.value = 0;
};

const alTeclear = (e) => {
  if (e.key === 'ArrowDown') { e.preventDefault(); abierto.value = true; activo.value = (activo.value + 1) % Math.max(total.value, 1); }
  else if (e.key === 'ArrowUp') { e.preventDefault(); activo.value = (activo.value - 1 + Math.max(total.value, 1)) % Math.max(total.value, 1); }
  else if (e.key === 'Enter') {
    if (!abierto.value) return;
    e.preventDefault();
    if (activo.value < filtradas.value.length) elegir(filtradas.value[activo.value]);
    else if (puedeCrear.value) crear();
  } else if (e.key === 'Escape') { e.stopPropagation(); cerrar(); }
};
</script>

<template>
  <div class="relative">
    <div class="relative">
      <input
        v-model="texto"
        :id="idFinal"
        :disabled="disabled"
        :placeholder="placeholder"
        type="text"
        role="combobox"
        autocomplete="off"
        :aria-expanded="abierto"
        @focus="abrir"
        @click="abrir"
        @input="alEscribir"
        @blur="cerrarConRetraso"
        @keydown="alTeclear"
        :class="[
          'w-full h-11 pl-4 pr-10 rounded-xl border bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition',
          'focus:outline-none focus:ring-4 focus:ring-orange-100 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed',
          abierto ? 'border-[#ff8c42]' : 'border-gray-200 hover:border-gray-300 focus:border-[#ff8c42]',
        ]"
      >
      <i :class="['fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none transition-transform duration-150', abierto ? 'rotate-180' : '']"></i>
    </div>

    <div
      v-if="abierto && !disabled"
      role="listbox"
      class="absolute z-30 mt-1.5 w-full bg-surface border border-gray-100 rounded-xl shadow-xl ring-1 ring-black/5 p-1 max-h-60 overflow-y-auto"
    >
      <button
        v-for="(op, i) in filtradas"
        :key="op.value"
        type="button"
        role="option"
        :aria-selected="model === op.value"
        @mousedown.prevent="elegir(op)"
        @mousemove="activo = i"
        :class="[
          'w-full flex items-center justify-between gap-2 text-left px-3 py-2 rounded-lg text-sm transition-colors',
          model === op.value ? 'text-[#ff8c42] font-semibold' : 'text-gray-700',
          activo === i ? 'bg-orange-50' : '',
        ]"
      >
        <span class="truncate">{{ op.label }}</span>
        <span class="flex items-center gap-2 flex-shrink-0">
          <span v-if="op.hint" class="text-[11px] font-medium text-gray-400">{{ op.hint }}</span>
          <i v-if="model === op.value" class="fa-solid fa-check text-xs"></i>
        </span>
      </button>

      <p v-if="!filtradas.length && !puedeCrear" class="px-3 py-2.5 text-xs text-gray-400">
        {{ options.length ? 'Ninguno coincide con lo que escribes.' : 'Aún no hay ninguno.' }}
      </p>

      <button
        v-if="puedeCrear"
        type="button"
        @mousedown.prevent="crear"
        @mousemove="activo = filtradas.length"
        :class="['w-full flex items-center gap-2 text-left px-3 py-2.5 rounded-lg text-sm font-semibold text-[#ff8c42] transition-colors', filtradas.length ? 'mt-1 border-t border-gray-100' : '', activo === filtradas.length ? 'bg-orange-50' : '']"
      >
        <i class="fa-solid fa-plus text-xs"></i> Crear «{{ texto.trim() }}»
      </button>
    </div>
  </div>
</template>
