<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import DocumentField from '@/Components/Admin/DocumentField.vue';

const props = defineProps({
  cliente: Object,
});

const emit = defineEmits(['close']);

const form = ref({
  document_type: props.cliente.document_type || 'DNI',
  document_number: props.cliente.document_number || '',
  name: props.cliente.name,
  email: props.cliente.email ?? '',
  phone: props.cliente.phone ?? '',
});

const alEncontrar = (datos) => {
  form.value.name = datos.nombre;
};

const isSaving = ref(false);
const error = ref('');

const guardar = () => {
  if (!form.value.name.trim()) {
    error.value = 'El nombre es obligatorio.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  router.patch(route('admin.customers.update', props.cliente.id), form.value, {
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
    title="Editar cliente"
    subtitle="Documento, nombre y datos de contacto"
    icon="fa-solid fa-user-pen"
    submit-text="Guardar cambios"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <DocumentField
      v-model:type="form.document_type"
      v-model:number="form.document_number"
      :exclude-id="cliente.id"
      @found="alEncontrar"
    />

    <FormField :label="form.document_type === 'RUC' ? 'Razón social' : 'Nombre'">
      <FormInput
        v-model="form.name"
        type="text"
        :placeholder="form.document_type === 'RUC' ? 'Razón social' : 'Nombre completo'"
        icon="fa-regular fa-user"
      />
    </FormField>
    <FormField label="Email" optional>
      <FormInput
        v-model="form.email"
        type="email"
        placeholder="correo@ejemplo.com"
        icon="fa-regular fa-envelope"
      />
    </FormField>
    <FormField label="Teléfono" optional>
      <FormInput
        v-model="form.phone"
        type="text"
        placeholder="987654321"
        icon="fa-solid fa-phone"
      />
    </FormField>
  </ModalShell>
</template>
