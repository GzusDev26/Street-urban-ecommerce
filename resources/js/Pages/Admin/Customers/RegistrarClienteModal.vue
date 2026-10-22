<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import DocumentField from '@/Components/Admin/DocumentField.vue';

const emit = defineEmits(['close']);

const form = ref({
  document_type: 'DNI',
  document_number: '',
  name: '',
  email: '',
  phone: '',
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

  router.post(route('admin.customers.store'), form.value, {
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
    title="Nuevo cliente"
    subtitle="Contacto e historial, sin acceso a la web todavía"
    icon="fa-solid fa-user-plus"
    submit-text="Registrar cliente"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <DocumentField
      v-model:type="form.document_type"
      v-model:number="form.document_number"
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

    <p class="text-xs text-gray-400 leading-relaxed">
      Si más adelante quiere comprar online, puede activar su cuenta él mismo desde la tienda usando este correo.
    </p>
  </ModalShell>
</template>
