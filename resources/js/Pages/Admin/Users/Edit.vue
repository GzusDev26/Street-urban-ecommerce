<template>
  <Head title="Editar Usuario" />
  <AdminLayout>
    <template #breadcrumb>Usuarios / Editar</template>
    <template #header>Editar Usuario</template>

    <div class="max-w-xl">
      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <span v-if="esYo" class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full mb-5 bg-orange-100 text-[#ff8c42]">
          Esta es tu propia cuenta
        </span>

        <form @submit.prevent="handleSubmit" class="space-y-5">
          <div>
            <label for="edit-1" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
            <input id="edit-1"
              v-model="formData.name"
              type="text"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
              required
            />
            <p v-if="validationErrors.name" class="text-red-500 text-xs mt-1">{{ validationErrors.name[0] }}</p>
          </div>

          <div>
            <label for="edit-2" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input id="edit-2"
              v-model="formData.email"
              type="email"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
              required
            />
            <p v-if="validationErrors.email" class="text-red-500 text-xs mt-1">{{ validationErrors.email[0] }}</p>
          </div>

          <div>
            <span class="block text-sm font-medium text-gray-700 mb-2">Rol</span>
            <SelectDropdown v-model="formData.role" :options="opcionesRol" />
            <p v-if="esYo && usuario.role === 'admin'" class="text-xs text-gray-400 mt-2">
              Si te quitas el rol de admin y eres el único, el sistema no te va a dejar guardar.
            </p>
            <p v-if="validationErrors.role" class="text-red-500 text-xs mt-1">{{ validationErrors.role[0] }}</p>
          </div>

          <div>
            <label for="edit-3" class="block text-sm font-medium text-gray-700 mb-2">Nueva contraseña (opcional)</label>
            <input id="edit-3"
              v-model="formData.password"
              type="password"
              placeholder="Déjalo vacío para no cambiarla"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
            />
            <p v-if="validationErrors.password" class="text-red-500 text-xs mt-1">{{ validationErrors.password[0] }}</p>
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="submit"
              :disabled="loading"
              class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:hover:translate-y-0 text-white font-bold text-sm py-2.5 px-6 rounded-xl transition-all duration-200 shadow-sm"
            >
              <i class="fa-solid fa-save"></i> {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
            <Link
              :href="route('admin.users.index')"
              class="text-sm font-semibold text-gray-500 hover:text-gray-700 px-5 py-2.5 rounded-xl hover:bg-gray-100 transition-colors"
            >
              Cancelar
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  usuario: Object,
  esYo: Boolean,
});

const opcionesRol = [
  { value: 'vendedor', label: 'Vendedor' },
  { value: 'admin', label: 'Administrador' },
];

const loading = ref(false);
const validationErrors = ref({});

const formData = ref({
  name: props.usuario.name,
  email: props.usuario.email,
  role: props.usuario.role,
  password: '',
});

const handleSubmit = () => {
  loading.value = true;
  validationErrors.value = {};

  router.patch(route('admin.users.update', props.usuario.id), formData.value, {
    onError: (errors) => {
      validationErrors.value = errors;
    },
    onFinish: () => {
      loading.value = false;
    },
  });
};
</script>
