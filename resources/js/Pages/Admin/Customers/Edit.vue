<template>
  <Head title="Editar Cliente" />
  <AdminLayout>
    <template #breadcrumb>Clientes / Editar</template>
    <template #header>Editar Cliente</template>

    <div class="max-w-xl space-y-6">
      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <span
          :class="[
            'inline-block text-xs font-semibold px-2.5 py-1 rounded-full mb-5',
            customer.email_verified_at ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400',
          ]"
        >
          {{ customer.email_verified_at ? 'Cuenta activa' : 'Solo registro (sin acceso a la web)' }}
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
            <label for="edit-2" class="block text-sm font-medium text-gray-700 mb-2">Email (opcional)</label>
            <input id="edit-2"
              v-model="formData.email"
              type="email"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
            />
            <p v-if="validationErrors.email" class="text-red-500 text-xs mt-1">{{ validationErrors.email[0] }}</p>
          </div>

          <div>
            <label for="edit-3" class="block text-sm font-medium text-gray-700 mb-2">Teléfono (opcional)</label>
            <input id="edit-3"
              v-model="formData.phone"
              type="text"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
            />
            <p v-if="validationErrors.phone" class="text-red-500 text-xs mt-1">{{ validationErrors.phone[0] }}</p>
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
              :href="route('admin.customers.index')"
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
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  customer: Object,
});

const loading = ref(false);
const validationErrors = ref({});

const formData = ref({
  name: props.customer.name,
  email: props.customer.email,
  phone: props.customer.phone,
});

const handleSubmit = () => {
  loading.value = true;
  validationErrors.value = {};

  router.patch(route('admin.customers.update', props.customer.id), formData.value, {
    onError: (errors) => {
      validationErrors.value = errors;
    },
    onFinish: () => {
      loading.value = false;
    },
  });
};
</script>
