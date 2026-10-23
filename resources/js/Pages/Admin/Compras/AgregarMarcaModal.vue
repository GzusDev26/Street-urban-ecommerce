<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import SearchCreateInput from '@/Components/Admin/SearchCreateInput.vue';

const props = defineProps({
  categoria: { type: Object, required: true }, // { id, name, marcas: [{ id, name }] }
  marcas: { type: Array, default: () => [] }, // nombres de todas las marcas registradas
  nombreInicial: { type: String, default: '' }, // marca sugerida (ej. al tocar "Nike")
});

const emit = defineEmits(['close']);

const nombre = ref(props.nombreInicial);
const isSaving = ref(false);
const error = ref('');

// Solo se ofrecen las marcas que todavía no están en esta categoría.
const disponibles = computed(() => {
  const yaEstan = new Set(props.categoria.marcas.map((m) => m.name.toLowerCase()));
  return props.marcas.filter((m) => !yaEstan.has(m.toLowerCase()));
});

const guardar = () => {
  if (!nombre.value.trim()) {
    error.value = 'Elige una marca o escribe una nueva.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  router.post(route('admin.categories.brands.attach', props.categoria.id), { name: nombre.value.trim() }, {
    preserveScroll: true,
    onSuccess: () => emit('close'),
    onError: (errors) => {
      error.value = errors.error || errors.name || Object.values(errors)[0] || 'No se pudo agregar la marca.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};
</script>

<template>
  <ModalShell
    title="Agregar marca"
    :subtitle="`Dentro de ${categoria.name}`"
    icon="fa-solid fa-tag"
    submit-text="Agregar marca"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <FormField label="Marca" hint="Elige una marca que ya usas en otra categoría o escribe una nueva.">
      <SearchCreateInput v-model="nombre" :options="disponibles" placeholder="Ej: Nike, Adidas, Gap..." />
    </FormField>
  </ModalShell>
</template>
