<template>
  <Head title="Usuarios" />
  <AdminLayout>
    <template #breadcrumb>Usuarios</template>
    <template #header>Usuarios</template>

    <div class="space-y-6">
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>
      <div v-if="page.props.errors?.error" class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation"></i> {{ page.props.errors.error }}
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold text-gray-900">Usuarios del Staff</h3>
            <p class="text-xs text-gray-400">Quién puede entrar al panel, y qué rol tiene</p>
          </div>
          <button
            @click="showModal = true"
            class="bg-[#ff8c42] hover:bg-[#ff7a24] text-white text-sm font-bold py-2.5 px-5 rounded-xl transition flex items-center gap-2"
          >
            <i class="fa-solid fa-plus text-xs"></i> Nuevo Usuario
          </button>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-5">
          <SearchInput v-model="filtroTexto" placeholder="Buscar por nombre o email..." class="w-full sm:w-72" />
          <button
            v-if="filtroTexto"
            @click="filtroTexto = ''"
            class="text-sm font-semibold text-gray-400 hover:text-gray-600 px-3 py-2"
          >
            <i class="fa-solid fa-xmark text-xs"></i> Limpiar
          </button>
        </div>

        <div v-if="usuariosFiltrados.length > 0" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-gray-200">
              <tr>
                <th class="text-left py-3 px-4 font-semibold text-gray-500">Nombre</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-500">Email</th>
                <th class="text-center py-3 px-4 font-semibold text-gray-500">Rol</th>
                <th class="text-center py-3 px-4 font-semibold text-gray-500">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="usuario in usuariosPaginados" :key="usuario.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-[#ff8c42] flex items-center justify-center text-white font-bold text-xs flex-shrink-0 overflow-hidden">
                      <img v-if="usuario.avatarUrl" :src="usuario.avatarUrl" :alt="usuario.name" class="w-full h-full object-cover" />
                      <template v-else>{{ usuario.name.charAt(0).toUpperCase() }}</template>
                    </div>
                    <span class="text-gray-900 font-medium">
                      {{ usuario.name }}
                      <span v-if="usuario.esYo" class="text-[11px] text-gray-400 font-normal">(tú)</span>
                    </span>
                  </div>
                </td>
                <td class="py-3 px-4 text-gray-600">{{ usuario.email }}</td>
                <td class="py-3 px-4 text-center">
                  <span
                    :class="[
                      'text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap',
                      usuario.role === 'admin' ? 'bg-orange-100 text-[#ff8c42]' : 'bg-blue-100 text-blue-600',
                    ]"
                  >
                    {{ usuario.role === 'admin' ? 'Administrador' : 'Vendedor' }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center justify-center gap-2">
                    <Link
                      :href="route('admin.users.edit', usuario.id)"
                      title="Editar"
                      class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-500 hover:text-blue-600 flex items-center justify-center transition-colors"
                    >
                      <i class="fa-solid fa-pen text-xs"></i>
                    </Link>
                    <button
                      v-if="!usuario.esYo"
                      @click="eliminar(usuario)"
                      title="Eliminar"
                      class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-600 flex items-center justify-center transition-colors"
                    >
                      <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <Pagination v-model="paginaActual" :total-items="usuariosFiltrados.length" :per-page="perPage" />
        </div>

        <div v-else-if="users.length === 0" class="bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
          <i class="fa-solid fa-user-shield text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">Aún no hay usuarios registrados.</p>
        </div>

        <div v-else class="bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
          <i class="fa-solid fa-filter-circle-xmark text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">Ningún usuario coincide con tu búsqueda.</p>
        </div>
      </div>
    </div>

    <ConfirmModal
      v-if="usuarioPendienteEliminar"
      title="Eliminar Usuario"
      :message="`¿Eliminar a ${usuarioPendienteEliminar.name}? Ya no podrá entrar al panel.`"
      confirm-text="Sí, Eliminar"
      cancel-text="Cancelar"
      variant="danger"
      :loading="eliminando"
      @confirm="confirmarEliminacion"
      @close="usuarioPendienteEliminar = null"
    />

    <RegistrarUsuarioModal v-if="showModal" @close="showModal = false" />
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import RegistrarUsuarioModal from './RegistrarUsuarioModal.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
  users: Array,
});

const page = usePage();
const filtroTexto = ref('');
const showModal = ref(false);

const usuariosFiltrados = computed(() => {
  const texto = filtroTexto.value.trim().toLowerCase();
  if (!texto) return props.users;

  return props.users.filter((u) =>
    u.name.toLowerCase().includes(texto) ||
    u.email.toLowerCase().includes(texto)
  );
});

const perPage = 15;
const paginaActual = ref(1);

watch(filtroTexto, () => {
  paginaActual.value = 1;
});

watch(() => usuariosFiltrados.value.length, (total) => {
  const totalPaginas = Math.max(1, Math.ceil(total / perPage));
  if (paginaActual.value > totalPaginas) paginaActual.value = totalPaginas;
});

const usuariosPaginados = computed(() => {
  const inicio = (paginaActual.value - 1) * perPage;
  return usuariosFiltrados.value.slice(inicio, inicio + perPage);
});

const usuarioPendienteEliminar = ref(null);
const eliminando = ref(false);

const eliminar = (usuario) => {
  usuarioPendienteEliminar.value = usuario;
};

const confirmarEliminacion = () => {
  eliminando.value = true;

  router.delete(route('admin.users.destroy', usuarioPendienteEliminar.value.id), {
    onFinish: () => {
      eliminando.value = false;
      usuarioPendienteEliminar.value = null;
    },
  });
};
</script>
