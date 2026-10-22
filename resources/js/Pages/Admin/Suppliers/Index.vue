<template>
  <Head title="Proveedores" />
  <AdminLayout>
    <template #breadcrumb>Proveedores</template>
    <template #header>Proveedores</template>

    <div class="space-y-6">
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>

      <ListCard title="Proveedores registrados" subtitle="Tiendas y proveedores a los que les compras mercadería">
        <template #action>
          <PrimaryButton @click="showModal = true">Nuevo proveedor</PrimaryButton>
        </template>

        <template #toolbar>
          <span class="text-sm text-gray-500">
            <strong class="text-gray-900">{{ proveedoresFiltrados.length }}</strong>
            {{ proveedoresFiltrados.length === 1 ? 'proveedor' : 'proveedores' }}
          </span>
          <SearchInput v-model="filtroTexto" placeholder="Buscar por nombre, email o teléfono" class="w-full sm:w-80" />
        </template>

        <DataTable v-if="proveedoresFiltrados.length > 0">
          <template #head>
            <tr>
              <th class="text-left">Proveedor</th>
              <th class="text-left hidden lg:table-cell">Contacto</th>
              <th class="text-left hidden md:table-cell">Registro</th>
              <th class="text-right">Acciones</th>
            </tr>
          </template>

          <tr v-for="proveedor in proveedoresPaginados" :key="proveedor.id">
            <td>
              <div class="flex items-center gap-3.5 min-w-0">
                <InitialsAvatar :name="proveedor.name" :id="proveedor.id" icon="fa-solid fa-store" />
                <div class="min-w-0">
                  <p class="font-semibold text-gray-900 truncate max-w-[16rem]">{{ proveedor.name }}</p>
                  <p v-if="proveedor.address" class="flex items-center gap-1.5 text-xs text-gray-500 mt-0.5 truncate max-w-[16rem]">
                    <i class="fa-solid fa-location-dot text-gray-400"></i>{{ proveedor.address }}
                  </p>
                  <p v-else class="text-xs text-gray-400 mt-0.5">Sin dirección</p>
                  <p v-if="proveedor.email || proveedor.phone" class="lg:hidden text-xs text-gray-500 mt-1 truncate">
                    {{ [proveedor.email, proveedor.phone].filter(Boolean).join(' · ') }}
                  </p>
                </div>
              </div>
            </td>

            <td class="hidden lg:table-cell">
              <div v-if="proveedor.email || proveedor.phone" class="space-y-1">
                <p v-if="proveedor.email" class="flex items-center gap-2 text-gray-700">
                  <i class="fa-regular fa-envelope text-gray-400 text-xs w-3.5"></i>{{ proveedor.email }}
                </p>
                <p v-if="proveedor.phone" class="flex items-center gap-2 text-gray-700">
                  <i class="fa-solid fa-phone text-gray-400 text-xs w-3.5"></i>{{ proveedor.phone }}
                </p>
              </div>
              <span v-else class="text-gray-300">—</span>
            </td>

            <td class="text-gray-500 tabular-nums whitespace-nowrap hidden md:table-cell">{{ proveedor.registrado }}</td>

            <td>
              <div class="flex items-center justify-end gap-1">
                <ActionButton icon="fa-solid fa-receipt" label="Historial de compras" tone="purple" @click="verHistorial(proveedor)" />
                <ActionButton icon="fa-solid fa-pen" label="Editar" tone="blue" @click="editarProveedor(proveedor)" />
                <ActionButton icon="fa-solid fa-trash" label="Eliminar" tone="red" @click="eliminar(proveedor)" />
              </div>
            </td>
          </tr>

          <template #footer>
            <Pagination v-model="paginaActual" :total-items="proveedoresFiltrados.length" :per-page="perPage" />
          </template>
        </DataTable>

        <EmptyState
          v-else-if="suppliers.length === 0"
          icon="fa-solid fa-truck-field"
          title="Aún no hay proveedores registrados"
          text="Registra la tienda o proveedor al que le compras mercadería."
        >
          <PrimaryButton @click="showModal = true">Nuevo proveedor</PrimaryButton>
        </EmptyState>

        <EmptyState
          v-else
          icon="fa-solid fa-filter-circle-xmark"
          title="Ningún proveedor coincide"
          text="Prueba con otra búsqueda."
        >
          <button @click="filtroTexto = ''" class="text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 px-4 py-2 rounded-xl transition">Limpiar búsqueda</button>
        </EmptyState>
      </ListCard>
    </div>

    <ConfirmModal
      v-if="proveedorPendienteEliminar"
      title="Eliminar Proveedor"
      :message="`¿Eliminar a ${proveedorPendienteEliminar.name}? Esta acción no se puede deshacer.`"
      confirm-text="Sí, Eliminar"
      cancel-text="Cancelar"
      variant="danger"
      :loading="eliminando"
      @confirm="confirmarEliminacion"
      @close="proveedorPendienteEliminar = null"
    />

    <RegistrarProveedorModal v-if="showModal" @close="showModal = false" />
    <EditarProveedorModal v-if="proveedorEditando" :proveedor="proveedorEditando" @close="proveedorEditando = null" />

    <HistorialProveedorModal
      v-if="proveedorHistorial"
      :proveedor="proveedorHistorial"
      @close="proveedorHistorial = null"
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
import InitialsAvatar from '@/Components/Admin/InitialsAvatar.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import RegistrarProveedorModal from './RegistrarProveedorModal.vue';
import EditarProveedorModal from './EditarProveedorModal.vue';
import HistorialProveedorModal from './HistorialProveedorModal.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
  suppliers: Array,
});

const page = usePage();
const filtroTexto = ref('');
const showModal = ref(false);
const proveedorEditando = ref(null);
const editarProveedor = (proveedor) => {
  proveedorEditando.value = proveedor;
};
const proveedorHistorial = ref(null);

const verHistorial = (proveedor) => {
  proveedorHistorial.value = proveedor;
};

const proveedoresFiltrados = computed(() => {
  const texto = filtroTexto.value.trim().toLowerCase();
  if (!texto) return props.suppliers;

  return props.suppliers.filter((s) =>
    s.name.toLowerCase().includes(texto) ||
    (s.email || '').toLowerCase().includes(texto) ||
    (s.phone || '').toLowerCase().includes(texto)
  );
});

const perPage = 15;
const paginaActual = ref(1);

watch(filtroTexto, () => {
  paginaActual.value = 1;
});

watch(() => proveedoresFiltrados.value.length, (total) => {
  const totalPaginas = Math.max(1, Math.ceil(total / perPage));
  if (paginaActual.value > totalPaginas) paginaActual.value = totalPaginas;
});

const proveedoresPaginados = computed(() => {
  const inicio = (paginaActual.value - 1) * perPage;
  return proveedoresFiltrados.value.slice(inicio, inicio + perPage);
});

const proveedorPendienteEliminar = ref(null);
const eliminando = ref(false);

const eliminar = (proveedor) => {
  proveedorPendienteEliminar.value = proveedor;
};

const confirmarEliminacion = () => {
  eliminando.value = true;

  router.delete(route('admin.suppliers.destroy', proveedorPendienteEliminar.value.id), {
    onFinish: () => {
      eliminando.value = false;
      proveedorPendienteEliminar.value = null;
    },
  });
};
</script>
