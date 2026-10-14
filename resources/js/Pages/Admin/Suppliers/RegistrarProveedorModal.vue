<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

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
  <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="emit('close')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Nuevo Proveedor</h2>
          <p class="text-xs text-gray-400">Tienda o proveedor al que le compras mercadería</p>
        </div>
        <button @click="emit('close')" class="p-2 hover:bg-gray-100 rounded-lg transition">
          <i class="fa-solid fa-xmark text-gray-500"></i>
        </button>
      </div>

      <div class="p-6 space-y-5">
        <div>
          <label for="registrar-proveedor-modal-1" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
          <input id="registrar-proveedor-modal-1"
            v-model="form.name"
            type="text"
            placeholder="Nombre de la tienda/proveedor"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white placeholder:text-gray-300"
          >
        </div>

        <div>
          <label for="registrar-proveedor-modal-2" class="block text-sm font-medium text-gray-700 mb-2">Teléfono (opcional)</label>
          <input id="registrar-proveedor-modal-2"
            v-model="form.phone"
            type="text"
            placeholder="987654321"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white placeholder:text-gray-300"
          >
        </div>

        <div>
          <label for="registrar-proveedor-modal-3" class="block text-sm font-medium text-gray-700 mb-2">Email (opcional)</label>
          <input id="registrar-proveedor-modal-3"
            v-model="form.email"
            type="email"
            placeholder="contacto@proveedor.com"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white placeholder:text-gray-300"
          >
        </div>

        <div>
          <label for="registrar-proveedor-modal-4" class="block text-sm font-medium text-gray-700 mb-2">Dirección (opcional)</label>
          <input id="registrar-proveedor-modal-4"
            v-model="form.address"
            type="text"
            placeholder="Dirección de la tienda"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white placeholder:text-gray-300"
          >
        </div>

        <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 rounded-lg px-4 py-3 text-sm">
          {{ error }}
        </div>
      </div>

      <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
        <button @click="emit('close')" :disabled="isSaving" class="text-sm font-semibold text-gray-500 hover:text-gray-700 disabled:opacity-50 px-5 py-3 rounded-xl hover:bg-gray-100 transition-colors">
          Cancelar
        </button>
        <button
          @click="guardar"
          :disabled="isSaving"
          class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm py-3 px-8 rounded-xl transition-all duration-200 shadow-sm"
        >
          <i class="fa-solid fa-save"></i> {{ isSaving ? 'Guardando...' : 'Registrar Proveedor' }}
        </button>
      </div>
    </div>
  </div>
</template>
