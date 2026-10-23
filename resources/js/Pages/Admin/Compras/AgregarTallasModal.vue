<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import { grupoDeTallas } from '@/Components/Admin/tallas';

const props = defineProps({
  modelo: { type: Object, required: true }, // { id, nombre, colores: [...], tallas: [...] ya registradas }
  sizeType: { type: String, default: 'ropa' },
});

const emit = defineEmits(['close']);

const grupo = computed(() => grupoDeTallas(props.sizeType));
const yaRegistradas = computed(() => new Set(props.modelo.tallas));
const elegidas = ref([]);
const otra = ref('');
const extras = ref([]);
const isSaving = ref(false);
const error = ref('');

const tallasMostradas = computed(() => [...grupo.value.tallas, ...extras.value.filter((t) => !grupo.value.tallas.includes(t))]);
const sinColores = computed(() => props.modelo.colores.length === 0);

const alternar = (t) => {
  if (yaRegistradas.value.has(t)) return;
  elegidas.value = elegidas.value.includes(t) ? elegidas.value.filter((x) => x !== t) : [...elegidas.value, t];
};

const agregarOtra = () => {
  const t = otra.value.trim();
  if (!t) return;

  if (!tallasMostradas.value.includes(t)) extras.value.push(t);
  if (!yaRegistradas.value.has(t) && !elegidas.value.includes(t)) elegidas.value.push(t);
  otra.value = '';
};

const guardar = () => {
  if (elegidas.value.length === 0) {
    error.value = 'Elige al menos una talla.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  router.post(route('admin.catalogo.tallas.store', props.modelo.id), { tallas: elegidas.value }, {
    preserveScroll: true,
    onSuccess: () => emit('close'),
    onError: (errors) => {
      error.value = errors.tallas || Object.values(errors)[0] || 'No se pudieron agregar las tallas.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};
</script>

<template>
  <ModalShell
    title="Agregar tallas"
    :subtitle="modelo.nombre"
    icon="fa-solid fa-ruler-combined"
    :submit-text="elegidas.length ? `Agregar ${elegidas.length} talla${elegidas.length === 1 ? '' : 's'}` : 'Agregar tallas'"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <p v-if="sinColores" class="flex items-start gap-2.5 text-sm text-amber-700 bg-amber-50 rounded-xl px-4 py-3">
      <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
      <span>Este modelo aún no tiene colores. Agrégale al menos uno y luego vuelve a agregar tallas.</span>
    </p>

    <FormField :label="`Tallas de ${grupo.nombre.toLowerCase()}`" group hint="Se crean para todos los colores del modelo, con 0 de stock. Las que ya existen aparecen marcadas.">
      <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
        <button
          v-for="t in tallasMostradas"
          :key="t"
          type="button"
          @click="alternar(t)"
          :disabled="yaRegistradas.has(t) || sinColores"
          :aria-pressed="elegidas.includes(t)"
          :class="[
            'rounded-xl border-2 py-2 text-sm font-bold transition-all',
            yaRegistradas.has(t) ? 'border-gray-100 bg-gray-100 text-gray-400 cursor-not-allowed'
              : elegidas.includes(t) ? 'border-[#ff8c42] bg-orange-50 text-[#e67e2d]'
              : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 disabled:opacity-50',
          ]"
        >{{ t }}</button>
      </div>

      <div class="flex items-center gap-2 mt-3">
        <input
          v-model="otra"
          @keydown.enter.prevent="agregarOtra"
          type="text"
          maxlength="20"
          placeholder="Otra talla"
          aria-label="Agregar otra talla"
          :disabled="sinColores"
          class="w-36 px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white disabled:opacity-50"
        >
        <button type="button" @click="agregarOtra" :disabled="sinColores" class="text-sm font-semibold text-[#ff8c42] hover:underline disabled:opacity-50">+ Agregar</button>
      </div>
    </FormField>
  </ModalShell>
</template>
