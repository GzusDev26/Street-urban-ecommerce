<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SearchCreateInput from '@/Components/Admin/SearchCreateInput.vue';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import FormTextarea from '@/Components/Admin/FormTextarea.vue';

const props = defineProps({
  categoriasExistentes: { type: Array, default: () => [] },
  marcasExistentes: { type: Array, default: () => [] },
  editando: { type: Object, required: true }, // { id, nombre, marca, categoria, descripcion }
});

const emit = defineEmits(['close']);

const form = ref({
  nombre: props.editando?.nombre || '',
  marca: props.editando?.marca || '',
  marcaDescripcion: '',
  categoria: props.editando?.categoria || '',
  descripcion: props.editando?.descripcion || '',
});

const isSaving = ref(false);
const error = ref('');

const opcionesCategoria = computed(() => props.categoriasExistentes.map((c) => ({ value: c, label: c })));

const marcaEsNueva = computed(() => {
  const q = form.value.marca.trim();
  return q.length > 0 && !props.marcasExistentes.some(m => m.toLowerCase() === q.toLowerCase());
});

const guardar = () => {
  if (!form.value.nombre || !form.value.marca || !form.value.categoria) {
    error.value = 'Completa categoría, marca y modelo.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  router.patch(route('admin.catalogo.update', props.editando.id), form.value, {
    onSuccess: () => emit('close'),
    onError: (errors) => {
      error.value = Object.values(errors)[0] || 'Ocurrió un error al guardar.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};
</script>

<template>
  <ModalShell
    title="Editar modelo"
    subtitle="Categoría, marca y modelo: la identidad del producto"
    submit-text="Guardar cambios"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <FormField label="Categoría" hint="Se elige de la lista. Las categorías nuevas se crean en Categorías y Marcas.">
      <SelectDropdown v-model="form.categoria" :options="opcionesCategoria" placeholder="Elige una categoría" />
    </FormField>

    <FormField label="Marca">
      <SearchCreateInput v-model="form.marca" :options="marcasExistentes" placeholder="Ej: Adidas" />
      <FormTextarea
        v-if="marcaEsNueva"
        v-model="form.marcaDescripcion"
        aria-label="Descripción de la marca"
        placeholder="Descripción de la marca (opcional)"
        rows="2"
        class="mt-2"
      />
    </FormField>

    <FormField label="Modelo">
      <FormInput v-model="form.nombre" type="text" placeholder="Ej: All Star" />
      <FormTextarea
        v-model="form.descripcion"
        aria-label="Descripción del modelo"
        placeholder="Descripción del modelo (opcional)"
        rows="2"
        class="mt-2"
      />
    </FormField>
  </ModalShell>
</template>
