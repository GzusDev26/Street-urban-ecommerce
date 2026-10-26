<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  // Cliente que se está editando: se ignora al avisar de documento duplicado.
  excludeId: { type: Number, default: null },
});

const emit = defineEmits(['found']);

const type = defineModel('type', { type: String, default: 'DNI' });
const number = defineModel('number', { type: String, default: '' });

const buscando = ref(false);
const error = ref('');
const encontrado = ref(false);

const longitud = computed(() => (type.value === 'DNI' ? 8 : 11));
const fuente = computed(() => (type.value === 'DNI' ? 'RENIEC' : 'SUNAT'));

// Autocompleta el nombre desde RENIEC (DNI) o SUNAT (RUC) vía el backend, que guarda el token.
const buscar = async () => {
  if (number.value.length !== longitud.value) {
    error.value = `El ${type.value} debe tener ${longitud.value} dígitos.`;
    return;
  }

  buscando.value = true;
  error.value = '';
  encontrado.value = false;

  try {
    const { data } = await axios.get(route('admin.customers.consultar-documento'), {
      params: { tipo: type.value, numero: number.value, excluir: props.excludeId },
    });
    encontrado.value = true;
    emit('found', data);
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo consultar el documento.';
  } finally {
    buscando.value = false;
  }
};

watch(number, (valor) => {
  const limpio = valor.replace(/\D/g, '').slice(0, longitud.value);
  if (limpio !== valor) {
    number.value = limpio;
    return;
  }
  encontrado.value = false;
  error.value = '';
  if (limpio.length === longitud.value) buscar();
});

// Al cambiar el tipo se limpia el número (solo por acción del usuario, no al cargar).
watch(type, () => {
  number.value = '';
});
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-1.5">
      <label for="campo-documento" class="text-sm font-semibold text-gray-700">Documento <span class="font-normal text-gray-400">(opcional)</span></label>

      <div class="inline-flex p-0.5 bg-gray-100 rounded-lg" role="group" aria-label="Tipo de documento">
        <button
          v-for="op in ['DNI', 'RUC']"
          :key="op"
          type="button"
          @click="type = op"
          :aria-pressed="type === op"
          :class="[
            'px-3 py-1 text-xs font-bold rounded-md transition-all',
            type === op ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700',
          ]"
        >
          {{ op }}
        </button>
      </div>
    </div>

    <div class="relative">
      <input
        id="campo-documento"
        v-model="number"
        type="text"
        inputmode="numeric"
        autocomplete="off"
        :placeholder="type === 'DNI' ? '8 dígitos' : '11 dígitos'"
        :class="[
          'w-full h-11 pl-4 pr-12 rounded-xl border bg-surface text-sm text-gray-900 tracking-wider placeholder:tracking-normal placeholder:text-gray-400 transition',
          'focus:outline-none focus:ring-4',
          error ? 'border-red-300 focus:border-red-400 focus:ring-red-100' : 'border-gray-200 hover:border-gray-300 focus:border-[#ff8c42] focus:ring-orange-100',
        ]"
      >
      <button
        type="button"
        @click="buscar"
        :disabled="buscando"
        :aria-label="`Buscar en ${fuente}`"
        :title="`Buscar en ${fuente}`"
        class="absolute right-1.5 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-orange-50 text-[#ff8c42] hover:bg-orange-100 disabled:opacity-60 flex items-center justify-center transition-colors"
      >
        <i :class="buscando ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-magnifying-glass'" class="text-xs"></i>
      </button>
    </div>

    <p v-if="error" class="flex items-center gap-1.5 text-xs text-red-600 mt-1.5">
      <i class="fa-solid fa-circle-exclamation"></i> {{ error }}
    </p>
    <p v-else-if="encontrado" class="flex items-center gap-1.5 text-xs text-green-600 mt-1.5">
      <i class="fa-solid fa-circle-check"></i> Datos obtenidos de {{ fuente }}
    </p>
    <p v-else class="text-xs text-gray-400 mt-1.5">Al completar los dígitos se buscan los datos en {{ fuente }}.</p>
  </div>
</template>
