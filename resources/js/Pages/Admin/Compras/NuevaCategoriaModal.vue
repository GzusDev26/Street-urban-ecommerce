<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import { GRUPOS_TALLA } from '@/Components/Admin/tallas';

const props = defineProps({
  categoria: { type: Object, default: null }, // { id, name, sizeType } para editar; null para crear
  inicial: { type: Object, default: null }, // { name, sizeType } para crear con valores sugeridos
});

const emit = defineEmits(['close']);

const form = ref({
  name: props.categoria?.name ?? props.inicial?.name ?? '',
  size_type: props.categoria?.sizeType ?? props.inicial?.sizeType ?? 'ropa',
});

const isSaving = ref(false);
const error = ref('');

const guardar = () => {
  if (!form.value.name.trim()) {
    error.value = 'Escribe el nombre de la categoría.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  const opciones = {
    preserveScroll: true,
    onSuccess: () => emit('close'),
    onError: (errors) => {
      error.value = errors.error || errors.name || Object.values(errors)[0] || 'No se pudo guardar la categoría.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  };

  if (props.categoria) {
    router.patch(route('admin.categories.update', props.categoria.id), form.value, opciones);
  } else {
    router.post(route('admin.categories.store'), form.value, opciones);
  }
};
</script>

<template>
  <ModalShell
    :title="categoria ? 'Editar categoría' : 'Nueva categoría'"
    subtitle="Por ejemplo: Polos, Shorts, Pantalones, Buzos"
    icon="fa-solid fa-layer-group"
    :submit-text="categoria ? 'Guardar cambios' : 'Crear categoría'"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <FormField label="Nombre">
      <FormInput v-model="form.name" type="text" placeholder="Ej: Polos" maxlength="255" />
    </FormField>

    <FormField label="¿Qué tallas usa?" group hint="Elige según lo que vende esta categoría. Define las tallas que se ofrecen al comprar.">
      <div class="grid grid-cols-3 gap-2" role="group" aria-label="Tipo de tallas">
        <button
          v-for="g in GRUPOS_TALLA"
          :key="g.id"
          type="button"
          @click="form.size_type = g.id"
          :aria-pressed="form.size_type === g.id"
          :class="[
            'flex flex-col items-center gap-1 rounded-xl border-2 px-2 py-3.5 text-center transition-all',
            form.size_type === g.id ? 'border-[#ff8c42] bg-orange-50' : 'border-gray-200 bg-surface hover:border-gray-300',
          ]"
        >
          <i :class="[g.icono, form.size_type === g.id ? 'text-[#ff8c42]' : 'text-gray-400']"></i>
          <span class="text-sm font-bold text-gray-900">{{ g.nombre }}</span>
          <span class="text-[11px] font-semibold text-gray-600">{{ g.detalle }}</span>
          <span class="text-[11px] text-gray-400 leading-tight">Ej: {{ g.ejemplo }}</span>
        </button>
      </div>
    </FormField>
  </ModalShell>
</template>
