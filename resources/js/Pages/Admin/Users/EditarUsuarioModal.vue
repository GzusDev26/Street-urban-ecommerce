<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';

const props = defineProps({
  usuario: Object,
});

const emit = defineEmits(['close']);

const form = ref({
  name: props.usuario.name,
  email: props.usuario.email,
  role: props.usuario.role,
  password: '',
});

const opcionesRol = [
  { value: 'vendedor', label: 'Vendedor' },
  { value: 'admin', label: 'Administrador' },
];

const isSaving = ref(false);
const error = ref('');

const guardar = () => {
  if (!form.value.name.trim() || !form.value.email.trim()) {
    error.value = 'Nombre y email son obligatorios.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  router.patch(route('admin.users.update', props.usuario.id), form.value, {
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
    title="Editar usuario"
    subtitle="Datos de acceso al panel"
    icon="fa-solid fa-user-pen"
    submit-text="Guardar cambios"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <div v-if="usuario.esYo" class="flex items-center gap-2 text-xs font-semibold text-[#ff8c42] bg-orange-50 rounded-xl px-3.5 py-2.5">
      <i class="fa-solid fa-circle-info"></i> Esta es tu propia cuenta
    </div>

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
    <FormField
      label="Rol"
      hint="Vendedor: registra ventas y compras. Administrador: además cancela ventas/compras y gestiona usuarios."
    >
      <SelectDropdown v-model="form.role" :options="opcionesRol" />
    </FormField>
    <FormField label="Nueva contraseña" optional>
      <FormInput
        v-model="form.password"
        type="password"
        placeholder="Déjala vacía para no cambiarla"
        icon="fa-solid fa-lock"
        autocomplete="new-password"
      />
    </FormField>
  </ModalShell>
</template>
