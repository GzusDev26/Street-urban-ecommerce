<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import FormTextarea from '@/Components/Admin/FormTextarea.vue';

const props = defineProps({
  categoria: { type: Object, required: true }, // { id, name }
  marca: { type: Object, required: true }, // { id, nombre }
});

const emit = defineEmits(['close']);

const form = ref({ nombre: '', descripcion: '' });
const isSaving = ref(false);
const error = ref('');

const guardar = () => {
  if (!form.value.nombre.trim()) {
    error.value = 'Escribe el nombre del modelo.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  router.post(route('admin.catalogo.store'), {
    categoria_id: props.categoria.id,
    marca_id: props.marca.id,
    nombre: form.value.nombre.trim(),
    descripcion: form.value.descripcion.trim() || null,
  }, {
    preserveScroll: true,
    onSuccess: () => emit('close'),
    onError: (errors) => {
      error.value = errors.nombre || Object.values(errors)[0] || 'No se pudo crear el modelo.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};
</script>

<template>
  <ModalShell
    title="Nuevo modelo"
    :subtitle="`${categoria.name} › ${marca.nombre}`"
    icon="fa-solid fa-shirt"
    submit-text="Crear modelo"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <FormField label="Modelo">
      <FormInput v-model="form.nombre" type="text" placeholder="Ej: All Star, Dri-Fit, Stan Smith" maxlength="255" />
    </FormField>

    <FormField label="Descripción" optional>
      <FormTextarea v-model="form.descripcion" rows="2" aria-label="Descripción del modelo" placeholder="Detalles del modelo (opcional)" />
    </FormField>

    <p class="flex items-start gap-2.5 text-xs text-gray-500 bg-gray-50 rounded-xl px-4 py-3">
      <i class="fa-solid fa-circle-info text-gray-400 mt-0.5"></i>
      <span>Después agrégale sus <strong class="text-gray-700">colores</strong> y <strong class="text-gray-700">tallas</strong>. El stock y los precios entran al registrar una compra.</span>
    </p>
  </ModalShell>
</template>
