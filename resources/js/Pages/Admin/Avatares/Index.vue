<template>
  <Head title="Avatares" />
  <AdminLayout>
    <template #breadcrumb>Configuración</template>
    <template #header>Avatares</template>

    <div class="space-y-6">
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>
      <div v-if="page.props.errors?.imagen" class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation"></i> {{ page.props.errors.imagen }}
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-1">
          <i class="fa-solid fa-images text-gray-400 text-sm"></i>
          <h3 class="text-lg font-bold text-gray-900">Galería de Avatares</h3>
        </div>
        <p class="text-xs text-gray-400 mb-6">
          Sube las fotos/íconos disponibles. Cada usuario elige el suyo desde su propio Perfil.
        </p>

        <label
          class="flex items-center justify-center gap-2 border-2 border-dashed border-orange-200 hover:bg-orange-50 rounded-xl py-4 mb-6 cursor-pointer transition-colors text-sm font-semibold text-[#ff8c42]"
        >
          <i class="fa-solid fa-upload text-xs"></i>
          {{ subiendo ? 'Subiendo...' : 'Subir Avatar' }}
          <input type="file" accept="image/*" class="hidden" :disabled="subiendo" @change="subirAvatar" />
        </label>

        <div v-if="avatares.length > 0" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
          <div v-for="avatar in avatares" :key="avatar.id" class="relative group">
            <div class="w-full aspect-square rounded-xl overflow-hidden border border-gray-200 bg-gray-50">
              <img :src="avatar.url" alt="Avatar" class="w-full h-full object-cover" />
            </div>
            <button
              @click="eliminar(avatar)"
              title="Eliminar"
              class="absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <i class="fa-solid fa-xmark text-[10px]"></i>
            </button>
            <span v-if="avatar.enUso > 0" class="absolute bottom-1 right-1 text-[9px] font-bold bg-black/60 text-white px-1.5 py-0.5 rounded-full">
              {{ avatar.enUso }}
            </span>
          </div>
        </div>

        <div v-else class="bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
          <i class="fa-solid fa-image text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">Aún no hay avatares subidos.</p>
        </div>
      </div>
    </div>

    <ConfirmModal
      v-if="avatarPendienteEliminar"
      title="Eliminar Avatar"
      :message="avatarPendienteEliminar.enUso > 0
        ? `Este avatar lo tienen elegido ${avatarPendienteEliminar.enUso} usuario(s) — al eliminarlo, volverán a sus iniciales. ¿Continuar?`
        : '¿Eliminar este avatar?'"
      confirm-text="Sí, Eliminar"
      cancel-text="Cancelar"
      variant="danger"
      :loading="eliminando"
      @confirm="confirmarEliminacion"
      @close="avatarPendienteEliminar = null"
    />
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';

defineProps({
  avatares: Array,
});

const page = usePage();
const subiendo = ref(false);

const subirAvatar = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  subiendo.value = true;

  router.post(route('admin.avatars.store'), { imagen: file }, {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      subiendo.value = false;
      e.target.value = '';
    },
  });
};

const avatarPendienteEliminar = ref(null);
const eliminando = ref(false);

const eliminar = (avatar) => {
  avatarPendienteEliminar.value = avatar;
};

const confirmarEliminacion = () => {
  eliminando.value = true;

  router.delete(route('admin.avatars.destroy', avatarPendienteEliminar.value.id), {
    preserveScroll: true,
    onFinish: () => {
      eliminando.value = false;
      avatarPendienteEliminar.value = null;
    },
  });
};
</script>
