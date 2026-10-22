<template>
  <Head title="Usuarios" />
  <AdminLayout>
    <template #breadcrumb>Usuarios</template>
    <template #header>Usuarios</template>

    <div class="space-y-6">
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>

      <ListCard title="Usuarios del panel" subtitle="Personas de tu equipo con acceso al panel de administración">
        <template #action>
          <PrimaryButton @click="showModal = true">Nuevo usuario</PrimaryButton>
        </template>

        <template #toolbar>
          <TabFilter v-model="filtroRol" :tabs="tabs" />
          <SearchInput v-model="filtroTexto" placeholder="Buscar por nombre o email" class="w-full sm:w-80" />
        </template>

        <DataTable v-if="usuariosFiltrados.length > 0">
          <template #head>
            <tr>
              <th class="text-left">Usuario</th>
              <th class="text-left">Rol</th>
              <th class="text-right">Acciones</th>
            </tr>
          </template>

          <tr v-for="usuario in usuariosPaginados" :key="usuario.id">
            <td>
              <div class="flex items-center gap-3.5 min-w-0">
                <InitialsAvatar :name="usuario.name" :id="usuario.id" :src="usuario.avatarUrl" tone="bg-[#ff8c42] text-white" />
                <div class="min-w-0">
                  <p class="font-semibold text-gray-900 truncate max-w-[16rem]">
                    {{ usuario.name }}
                    <span v-if="usuario.esYo" class="ml-1 text-[11px] font-semibold text-[#e67e2d] bg-orange-50 rounded px-1.5 py-0.5 align-middle">Tú</span>
                  </p>
                  <p class="flex items-center gap-1.5 text-xs text-gray-500 mt-0.5 truncate max-w-[16rem]">
                    <i class="fa-regular fa-envelope text-gray-400"></i>{{ usuario.email }}
                  </p>
                </div>
              </div>
            </td>

            <td>
              <StatusPill :tone="usuario.role === 'admin' ? 'orange' : 'blue'">
                {{ usuario.role === 'admin' ? 'Administrador' : 'Vendedor' }}
              </StatusPill>
            </td>

            <td>
              <div class="flex items-center justify-end gap-1">
                <ActionButton icon="fa-solid fa-pen" label="Editar" tone="blue" @click="editarUsuario(usuario)" />
                <ActionButton v-if="!usuario.esYo" icon="fa-solid fa-trash" label="Eliminar" tone="red" @click="eliminar(usuario)" />
              </div>
            </td>
          </tr>

          <template #footer>
            <Pagination v-model="paginaActual" :total-items="usuariosFiltrados.length" :per-page="perPage" />
          </template>
        </DataTable>

        <EmptyState
          v-else-if="users.length === 0"
          icon="fa-solid fa-user-shield"
          title="Aún no hay usuarios registrados"
        >
          <PrimaryButton @click="showModal = true">Nuevo usuario</PrimaryButton>
        </EmptyState>

        <EmptyState
          v-else
          icon="fa-solid fa-filter-circle-xmark"
          title="Ningún usuario coincide"
          text="Prueba con otra búsqueda o cambia el filtro."
        >
          <button @click="limpiarFiltros" class="text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 px-4 py-2 rounded-xl transition">Limpiar filtros</button>
        </EmptyState>
      </ListCard>
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
    <EditarUsuarioModal v-if="usuarioEditando" :usuario="usuarioEditando" @close="usuarioEditando = null" />
  </AdminLayout>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ListCard from '@/Components/Admin/ListCard.vue';
import PrimaryButton from '@/Components/Admin/PrimaryButton.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import StatusPill from '@/Components/Admin/StatusPill.vue';
import InitialsAvatar from '@/Components/Admin/InitialsAvatar.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import TabFilter from '@/Components/Admin/TabFilter.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import RegistrarUsuarioModal from './RegistrarUsuarioModal.vue';
import EditarUsuarioModal from './EditarUsuarioModal.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
  users: Array,
});

const page = usePage();
const filtroTexto = ref('');
const showModal = ref(false);
const usuarioEditando = ref(null);
const editarUsuario = (usuario) => {
  usuarioEditando.value = usuario;
};

const filtroRol = ref('todos');

const tabs = computed(() => [
  { value: 'todos', label: 'Todos', count: props.users.length },
  { value: 'admin', label: 'Administradores', count: props.users.filter((u) => u.role === 'admin').length },
  { value: 'vendedor', label: 'Vendedores', count: props.users.filter((u) => u.role === 'vendedor').length },
]);

const usuariosFiltrados = computed(() => {
  const texto = filtroTexto.value.trim().toLowerCase();

  return props.users.filter((u) => {
    if (filtroRol.value !== 'todos' && u.role !== filtroRol.value) return false;
    if (!texto) return true;

    return u.name.toLowerCase().includes(texto) || u.email.toLowerCase().includes(texto);
  });
});

const limpiarFiltros = () => {
  filtroTexto.value = '';
  filtroRol.value = 'todos';
};

const perPage = 15;
const paginaActual = ref(1);

watch([filtroTexto, filtroRol], () => {
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
