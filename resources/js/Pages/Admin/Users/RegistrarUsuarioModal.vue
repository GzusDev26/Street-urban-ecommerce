<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';

const emit = defineEmits(['close']);

const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'vendedor',
});

const opcionesRol = [
  { value: 'vendedor', label: 'Vendedor' },
  { value: 'admin', label: 'Administrador' },
];

const isSaving = ref(false);
const error = ref('');

const guardar = () => {
  if (!form.value.name.trim() || !form.value.email.trim() || !form.value.password.trim()) {
    error.value = 'Nombre, email y contraseña son obligatorios.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  router.post(route('admin.users.store'), form.value, {
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
    title="Nuevo usuario"
    subtitle="Da acceso al panel a alguien de tu equipo"
    icon="fa-solid fa-user-shield"
    submit-text="Registrar usuario"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <FormField label="Nombre">
      <FormInput
        v-model="form.name"
        type="text"
        placeholder="Nombre completo"
        icon="fa-regular fa-user"
      />
    </FormField>
    <FormField label="Email">
      <FormInput
        v-model="form.email"
        type="email"
        placeholder="correo@streeturban.com"
        icon="fa-regular fa-envelope"
      />
    </FormField>
    <FormField label="Contraseña">
      <FormInput
        v-model="form.password"
        type="password"
        placeholder="Mínimo 6 caracteres"
        icon="fa-solid fa-lock"
        autocomplete="new-password"
      />
    </FormField>
    <FormField
      label="Rol"
      hint="Vendedor: registra ventas y compras. Administrador: además cancela ventas/compras y gestiona usuarios."
    >
      <SelectDropdown v-model="form.role" :options="opcionesRol" />
    </FormField>
  </ModalShell>
</template>
