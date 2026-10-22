<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';

const emit = defineEmits(['close']);

const form = ref({
  name: '',
  phone: '',
  email: '',
  address: '',
});

const isSaving = ref(false);
const error = ref('');

const guardar = () => {
  if (!form.value.name.trim()) {
    error.value = 'El nombre es obligatorio.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  router.post(route('admin.suppliers.store'), form.value, {
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
    title="Nuevo proveedor"
    subtitle="Tienda o proveedor al que le compras mercadería"
    icon="fa-solid fa-truck-field"
    submit-text="Registrar proveedor"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <FormField label="Nombre">
      <FormInput
        v-model="form.name"
        type="text"
        placeholder="Nombre de la tienda o proveedor"
        icon="fa-solid fa-store"
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
    <FormField label="Email" optional>
      <FormInput
        v-model="form.email"
        type="email"
        placeholder="contacto@proveedor.com"
        icon="fa-regular fa-envelope"
      />
    </FormField>
    <FormField label="Dirección" optional>
      <FormInput
        v-model="form.address"
        type="text"
        placeholder="Dirección de la tienda"
        icon="fa-solid fa-location-dot"
      />
    </FormField>
  </ModalShell>
</template>
