<template>
  <Head title="Historial de Ventas" />
  <AdminLayout>
    <template #breadcrumb>Ventas / Historial</template>
    <template #header>Historial de Ventas</template>

    <div class="space-y-6">
      <!-- Tabs -->
      <div class="flex gap-2 border-b border-gray-200">
        <Link
          :href="route('admin.sales.create')"
          class="px-4 py-2.5 text-sm font-semibold text-gray-400 hover:text-gray-600 border-b-2 border-transparent"
        >
          Registrar Venta
        </Link>
        <Link
          :href="route('admin.sales.index')"
          class="px-4 py-2.5 text-sm font-semibold text-[#ff8c42] border-b-2 border-[#ff8c42]"
        >
          Historial de Ventas
        </Link>
        <Link
          :href="route('admin.sales.reportes')"
          class="px-4 py-2.5 text-sm font-semibold text-gray-400 hover:text-gray-600 border-b-2 border-transparent"
        >
          Reportes de Ventas
        </Link>
      </div>

      <!-- FLASH SUCCESS -->
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>
      <div v-if="page.props.errors?.error" class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation"></i> {{ page.props.errors.error }}
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Total de Ventas</p>
          <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.totalVentas }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Ingresos Totales</p>
          <p class="text-3xl font-black text-[#ff8c42] mt-1">S/ {{ stats.totalIngresos.toFixed(2) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Prendas Vendidas</p>
          <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.prendasVendidas }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Promedio por Venta</p>
          <p class="text-3xl font-black text-gray-900 mt-1">S/ {{ stats.promedioVenta.toFixed(2) }}</p>
        </div>
      </div>

      <!-- List -->
      <ListCard title="Ventas registradas" subtitle="Historial de ventas y prendas despachadas">
        <template #action>
          <PrimaryButton :href="route('admin.sales.create')">Registrar venta</PrimaryButton>
        </template>

        <template #toolbar>
          <SearchInput v-model="filtroTexto" placeholder="Buscar por producto, cliente o N° de venta" class="w-full sm:w-96" />
        </template>

        <div v-if="ventasFiltradas.length > 0" class="space-y-3 px-6 pb-6">
          <div
            v-for="venta in ventasPaginadas"
            :key="venta.id"
            :id="`venta-${venta.id}`"
            :class="[
              'border rounded-xl overflow-hidden transition-colors',
              venta.id === ventaDestacada ? 'border-[#ff8c42] ring-2 ring-[#ff8c42]/30' : 'border-gray-100',
              venta.cancelada ? 'opacity-60' : '',
            ]"
          >
            <button
              @click="toggleExpand(venta.id)"
              class="w-full flex items-center justify-between px-5 py-4 hover:bg-orange-50/40 transition-colors text-left"
            >
              <div class="flex items-center gap-4 min-w-0">
                <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded flex-shrink-0">{{ venta.numero }}</span>
                <div class="min-w-0">
                  <div class="flex items-center gap-2 min-w-0">
                    <p :class="['text-sm font-semibold text-gray-900 truncate', venta.cancelada ? 'line-through' : '']">{{ tituloVenta(venta) }}</p>
                    <span
                      :class="[
                        'text-[11px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0',
                        venta.cliente ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-400',
                      ]"
                    >
                      <i class="fa-solid fa-user text-[9px] mr-1"></i>{{ venta.cliente || 'Sin cliente registrado' }}
                    </span>
                    <span v-if="venta.cancelada" class="text-[11px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0 bg-red-50 text-red-500">
                      <i class="fa-solid fa-ban text-[9px] mr-1"></i>Cancelada
                    </span>
                  </div>
                  <p class="text-xs text-gray-400 mt-0.5">
                    {{ venta.fecha }} · {{ venta.totalPrendas }} prenda{{ venta.totalPrendas === 1 ? '' : 's' }}
                    <span v-if="venta.prendasDevueltas > 0"> · {{ venta.prendasDevueltas }} devuelta{{ venta.prendasDevueltas === 1 ? '' : 's' }}</span>
                    <span v-if="venta.registradoPor"> · Registrado por {{ venta.registradoPor }}</span>
                    <span v-if="venta.cancelada && venta.canceladaPor"> · Cancelada por {{ venta.canceladaPor }}</span>
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-4 flex-shrink-0">
                <div class="text-right">
                  <span :class="['text-lg font-bold text-gray-900', venta.cancelada ? 'line-through' : '']">S/ {{ venta.totalNeto.toFixed(2) }}</span>
                  <p v-if="!venta.cancelada && venta.totalNeto !== venta.total" class="text-[11px] text-gray-400 line-through">S/ {{ venta.total.toFixed(2) }}</p>
                </div>
                <i :class="['fa-solid', expandedId === venta.id ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
              </div>
            </button>

            <div v-if="expandedId === venta.id" class="border-t border-gray-100 bg-gray-50 px-5 py-4">
              <div class="flex items-center justify-between mb-3">
                <p v-if="venta.notas" class="text-xs text-gray-500 italic">"{{ venta.notas }}"</p>
                <span v-else></span>
                <div class="flex items-center gap-2">
                  <button
                    v-if="!venta.cancelada && esAdmin"
                    @click="cancelarVenta(venta)"
                    class="text-xs font-semibold text-red-500 hover:text-white hover:bg-red-500 bg-white border border-red-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 flex-shrink-0 transition-colors"
                  >
                    <i class="fa-solid fa-ban text-[11px]"></i> Cancelar Venta
                  </button>
                  <a
                    :href="route('admin.sales.boleta', venta.id)"
                    target="_blank"
                    class="text-xs font-semibold text-gray-600 hover:text-[#ff8c42] bg-white border border-gray-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 flex-shrink-0"
                  >
                    <i class="fa-solid fa-print text-[11px]"></i> Imprimir Boleta
                  </a>
                </div>
              </div>

              <table class="w-full text-xs">
                <thead>
                  <tr class="text-gray-400">
                    <th class="text-left py-1.5 pr-4 font-semibold">Producto</th>
                    <th class="text-left py-1.5 pr-4 font-semibold">Marca</th>
                    <th class="text-left py-1.5 pr-4 font-semibold">Talla</th>
                    <th class="text-left py-1.5 pr-4 font-semibold">Color</th>
                    <th class="text-center py-1.5 pr-4 font-semibold">Cant.</th>
                    <th class="text-right py-1.5 pr-4 font-semibold">P. Unit.</th>
                    <th class="text-right py-1.5 pr-4 font-semibold">Subtotal</th>
                    <th v-if="!venta.cancelada && esAdmin" class="text-center py-1.5 font-semibold">Devolución</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in venta.items" :key="idx" class="border-t border-gray-100">
                    <td class="py-2 pr-4 text-gray-900 font-medium">{{ item.producto }}</td>
                    <td class="py-2 pr-4 text-gray-600">{{ item.marca }}</td>
                    <td class="py-2 pr-4 text-gray-600">{{ item.talla }}</td>
                    <td class="py-2 pr-4">
                      <div class="flex items-center gap-1.5">
                        <ColorSwatch :hex="item.colorHex" class="w-3.5 h-3.5 rounded-full border border-gray-200" />
                        <span class="text-gray-600">{{ item.color }}</span>
                      </div>
                    </td>
                    <td class="py-2 pr-4 text-center text-gray-900">
                      {{ item.cantidad }}
                      <span v-if="item.cantidadDevuelta > 0" class="block text-[10px] text-red-400 font-semibold">-{{ item.cantidadDevuelta }} dev.</span>
                    </td>
                    <td class="py-2 pr-4 text-right text-gray-900">S/ {{ item.precioUnitario.toFixed(2) }}</td>
                    <td class="py-2 pr-4 text-right text-gray-900 font-semibold">
                      S/ {{ item.subtotalNeto.toFixed(2) }}
                      <span v-if="item.subtotalNeto !== item.subtotal" class="block text-[10px] text-gray-400 font-normal line-through">S/ {{ item.subtotal.toFixed(2) }}</span>
                    </td>
                    <td v-if="!venta.cancelada && esAdmin" class="py-2 text-center">
                      <button
                        v-if="item.pendienteDevolucion > 0"
                        @click="devolverItem(item)"
                        class="text-[11px] font-semibold text-gray-500 hover:text-white hover:bg-red-500 bg-white border border-gray-200 px-2.5 py-1 rounded-lg transition-colors"
                      >
                        <i class="fa-solid fa-rotate-left text-[10px] mr-1"></i>Devolver
                      </button>
                      <span v-else class="text-[11px] text-gray-300">Devuelto</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <Pagination v-model="paginaActual" :total-items="ventasFiltradas.length" :per-page="perPage" />
        </div>

        <EmptyState
          v-else-if="ventas.length === 0"
          icon="fa-solid fa-cash-register"
          title="Aún no hay ventas registradas"
          text="Cuando registres la primera venta aparecerá aquí."
        >
          <PrimaryButton :href="route('admin.sales.create')">Registrar venta</PrimaryButton>
        </EmptyState>

        <EmptyState
          v-else
          icon="fa-solid fa-filter-circle-xmark"
          title="Ninguna venta coincide"
          text="Prueba con otra búsqueda."
        >
          <button @click="filtroTexto = ''" class="text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 px-4 py-2 rounded-xl transition">Limpiar búsqueda</button>
        </EmptyState>
      </ListCard>
    </div>

    <ConfirmModal
      v-if="ventaPendienteCancelar"
      title="Cancelar Venta"
      :message="`¿Cancelar la venta ${ventaPendienteCancelar.numero}? El stock vendido se devolverá al inventario.`"
      confirm-text="Sí, Cancelar"
      cancel-text="Volver"
      variant="danger"
      :loading="cancelando"
      @confirm="confirmarCancelacion"
      @close="ventaPendienteCancelar = null"
    />

    <DevolucionModal v-if="itemDevolver" :item="itemDevolver" @close="itemDevolver = null" />
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ListCard from '@/Components/Admin/ListCard.vue';
import PrimaryButton from '@/Components/Admin/PrimaryButton.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import DevolucionModal from './DevolucionModal.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, nextTick } from 'vue';

const props = defineProps({
  ventas: Array,
  stats: Object,
});

const page = usePage();
const esAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const filtroTexto = ref('');
const expandedId = ref(null);
const ventaDestacada = ref(null);

const ventaPendienteCancelar = ref(null);
const cancelando = ref(false);

const cancelarVenta = (venta) => {
  ventaPendienteCancelar.value = venta;
};

const confirmarCancelacion = () => {
  cancelando.value = true;

  router.patch(route('admin.sales.cancel', ventaPendienteCancelar.value.id), {}, {
    preserveScroll: true,
    onFinish: () => {
      cancelando.value = false;
      ventaPendienteCancelar.value = null;
    },
  });
};

const toggleExpand = (id) => {
  expandedId.value = expandedId.value === id ? null : id;
};

const itemDevolver = ref(null);

const devolverItem = (item) => {
  itemDevolver.value = item;
};

const tituloVenta = (venta) => {
  const nombresUnicos = [...new Set(venta.items.map(i => i.producto))];
  if (nombresUnicos.length === 1) return nombresUnicos[0];
  return `${nombresUnicos[0]} + ${nombresUnicos.length - 1} más`;
};

const ventasFiltradas = computed(() => {
  const texto = filtroTexto.value.trim().toLowerCase();
  if (!texto) return props.ventas;

  return props.ventas.filter((v) =>
    (v.cliente || '').toLowerCase().includes(texto) ||
    v.numero.toLowerCase().includes(texto) ||
    v.items.some(i => i.producto.toLowerCase().includes(texto))
  );
});

const perPage = 15;
const paginaActual = ref(1);

watch(filtroTexto, () => {
  paginaActual.value = 1;
});

watch(() => ventasFiltradas.value.length, (total) => {
  const totalPaginas = Math.max(1, Math.ceil(total / perPage));
  if (paginaActual.value > totalPaginas) paginaActual.value = totalPaginas;
});

const ventasPaginadas = computed(() => {
  const inicio = (paginaActual.value - 1) * perPage;
  return ventasFiltradas.value.slice(inicio, inicio + perPage);
});

// Llega desde "Ver en Historial" (?venta=123) al registrar una venta: la busca, se
// asegura de estar en la página correcta, la expande y hace scroll hasta ella.
onMounted(() => {
  const ventaId = Number(new URLSearchParams(window.location.search).get('venta'));
  if (!ventaId) return;

  const indice = props.ventas.findIndex(v => v.id === ventaId);
  if (indice === -1) return;

  paginaActual.value = Math.floor(indice / perPage) + 1;
  expandedId.value = ventaId;
  ventaDestacada.value = ventaId;

  nextTick(() => {
    document.getElementById(`venta-${ventaId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
});
</script>
