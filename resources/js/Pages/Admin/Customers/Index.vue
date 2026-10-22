<template>
  <Head title="Clientes" />
  <AdminLayout>
    <template #breadcrumb>Clientes</template>
    <template #header>Clientes</template>

    <div class="space-y-6">
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>

      <ListCard title="Clientes registrados" subtitle="Cuentas con acceso a la tienda y clientes registrados en el mostrador">
        <template #action>
          <PrimaryButton @click="showModal = true">Nuevo cliente</PrimaryButton>
        </template>

        <template #toolbar>
          <TabFilter v-model="filtroCuenta" :tabs="tabs" />
          <SearchInput v-model="filtroTexto" placeholder="Buscar por nombre, documento, email o teléfono" class="w-full sm:w-80" />
        </template>

        <DataTable v-if="clientesFiltrados.length > 0">
          <template #head>
            <tr>
              <th class="text-left">Cliente</th>
              <th class="text-left hidden lg:table-cell">Contacto</th>
              <th class="text-left">Cuenta</th>
              <th class="text-left hidden md:table-cell">Registro</th>
              <th class="text-right">Acciones</th>
            </tr>
          </template>

          <tr v-for="cliente in clientesPaginados" :key="cliente.id">
            <td>
              <div class="flex items-center gap-3.5 min-w-0">
                <InitialsAvatar
                  :name="cliente.name"
                  :id="cliente.id"
                  :icon="cliente.document_type === 'RUC' ? 'fa-solid fa-building' : ''"
                  :tone="cliente.document_type === 'RUC' ? 'bg-slate-100 text-slate-600' : ''"
                />
                <div class="min-w-0">
                  <p class="font-semibold text-gray-900 truncate max-w-[16rem]">{{ cliente.name }}</p>
                  <p v-if="cliente.document_number" class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-[10px] font-bold tracking-wide text-gray-500 bg-gray-100 rounded px-1.5 py-0.5">{{ cliente.document_type }}</span>
                    <span class="text-xs text-gray-500 tabular-nums">{{ cliente.document_number }}</span>
                  </p>
                  <p v-else class="text-xs text-gray-400 mt-0.5">Sin documento</p>
                  <p v-if="cliente.email || cliente.phone" class="lg:hidden text-xs text-gray-500 mt-1 truncate">
                    {{ [cliente.email, cliente.phone].filter(Boolean).join(' · ') }}
                  </p>
                </div>
              </div>
            </td>

            <td class="hidden lg:table-cell">
              <div v-if="cliente.email || cliente.phone" class="space-y-1">
                <p v-if="cliente.email" class="flex items-center gap-2 text-gray-700">
                  <i class="fa-regular fa-envelope text-gray-400 text-xs w-3.5"></i>{{ cliente.email }}
                </p>
                <p v-if="cliente.phone" class="flex items-center gap-2 text-gray-700">
                  <i class="fa-solid fa-phone text-gray-400 text-xs w-3.5"></i>{{ cliente.phone }}
                </p>
              </div>
              <span v-else class="text-gray-300">—</span>
            </td>

            <td>
              <StatusPill :tone="cliente.activa ? 'green' : 'gray'">{{ cliente.activa ? 'Activa' : 'Solo registro' }}</StatusPill>
            </td>

            <td class="text-gray-500 tabular-nums whitespace-nowrap hidden md:table-cell">{{ cliente.registrado }}</td>

            <td>
              <div class="flex items-center justify-end gap-1">
                <ActionButton icon="fa-solid fa-receipt" label="Historial de compras" tone="purple" @click="verHistorial(cliente)" />
                <ActionButton icon="fa-solid fa-pen" label="Editar" tone="blue" @click="editarCliente(cliente)" />
                <ActionButton icon="fa-solid fa-trash" label="Eliminar" tone="red" @click="eliminar(cliente)" />
              </div>
            </td>
          </tr>

          <template #footer>
            <Pagination v-model="paginaActual" :total-items="clientesFiltrados.length" :per-page="perPage" />
          </template>
        </DataTable>

        <EmptyState
          v-else-if="customers.length === 0"
          icon="fa-solid fa-people-group"
          title="Aún no hay clientes registrados"
          text="Registra el primero para empezar a emitir comprobantes."
        >
          <PrimaryButton @click="showModal = true">Nuevo cliente</PrimaryButton>
        </EmptyState>

        <EmptyState
          v-else
          icon="fa-solid fa-filter-circle-xmark"
          title="Ningún cliente coincide"
          text="Prueba con otra búsqueda o cambia el filtro."
        >
          <button @click="limpiarFiltros" class="text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 px-4 py-2 rounded-xl transition">Limpiar filtros</button>
        </EmptyState>
      </ListCard>
    </div>

    <ConfirmModal
      v-if="clientePendienteEliminar"
      title="Eliminar Cliente"
      :message="`¿Eliminar a ${clientePendienteEliminar.name}? Esta acción no se puede deshacer.`"
      confirm-text="Sí, Eliminar"
      cancel-text="Cancelar"
      variant="danger"
      :loading="eliminando"
      @confirm="confirmarEliminacion"
      @close="clientePendienteEliminar = null"
    />

    <RegistrarClienteModal v-if="showModal" @close="showModal = false" />
    <EditarClienteModal v-if="clienteEditando" :cliente="clienteEditando" @close="clienteEditando = null" />

    <HistorialClienteModal
      v-if="clienteHistorial"
      :cliente="clienteHistorial"
      @close="clienteHistorial = null"
    />
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
import RegistrarClienteModal from './RegistrarClienteModal.vue';
import EditarClienteModal from './EditarClienteModal.vue';
import HistorialClienteModal from './HistorialClienteModal.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
  customers: Array,
});

const page = usePage();

const showModal = ref(false);
const clienteEditando = ref(null);
const clienteHistorial = ref(null);

const editarCliente = (cliente) => {
  clienteEditando.value = cliente;
};

const verHistorial = (cliente) => {
  clienteHistorial.value = cliente;
};

const filtroTexto = ref('');
const filtroCuenta = ref('todos');

const tabs = computed(() => {
  const activas = props.customers.filter((c) => c.activa).length;

  return [
    { value: 'todos', label: 'Todos', count: props.customers.length },
    { value: 'activa', label: 'Con cuenta', count: activas },
    { value: 'registro', label: 'Solo registro', count: props.customers.length - activas },
  ];
});

const clientesFiltrados = computed(() => {
  const texto = filtroTexto.value.trim().toLowerCase();

  return props.customers.filter((c) => {
    if (filtroCuenta.value === 'activa' && !c.activa) return false;
    if (filtroCuenta.value === 'registro' && c.activa) return false;
    if (!texto) return true;

    return (
      c.name.toLowerCase().includes(texto) ||
      (c.document_number || '').includes(texto) ||
      (c.email || '').toLowerCase().includes(texto) ||
      (c.phone || '').toLowerCase().includes(texto)
    );
  });
});

const limpiarFiltros = () => {
  filtroTexto.value = '';
  filtroCuenta.value = 'todos';
};

const perPage = 15;
const paginaActual = ref(1);

watch([filtroTexto, filtroCuenta], () => {
  paginaActual.value = 1;
});

watch(() => clientesFiltrados.value.length, (total) => {
  const totalPaginas = Math.max(1, Math.ceil(total / perPage));
  if (paginaActual.value > totalPaginas) paginaActual.value = totalPaginas;
});

const clientesPaginados = computed(() => {
  const inicio = (paginaActual.value - 1) * perPage;
  return clientesFiltrados.value.slice(inicio, inicio + perPage);
});

const clientePendienteEliminar = ref(null);
const eliminando = ref(false);

const eliminar = (cliente) => {
  clientePendienteEliminar.value = cliente;
};

const confirmarEliminacion = () => {
  eliminando.value = true;

  router.delete(route('admin.customers.destroy', clientePendienteEliminar.value.id), {
    onFinish: () => {
      eliminando.value = false;
      clientePendienteEliminar.value = null;
    },
  });
};
</script>
