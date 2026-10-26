<template>
  <Head title="Historial de Compras" />
  <AdminLayout>
    <template #breadcrumb>Compras / Historial</template>
    <template #header>Historial de Compras</template>

    <div class="space-y-6">
      <!-- Tabs -->
      <div class="flex gap-2 border-b border-gray-200">
        <Link
          :href="route('admin.purchases.index')"
          class="px-4 py-2.5 text-sm font-semibold text-gray-400 hover:text-gray-600 border-b-2 border-transparent"
        >
          Inventario
        </Link>
        <Link
          :href="route('admin.purchases.historial')"
          class="px-4 py-2.5 text-sm font-semibold text-[#ff8c42] border-b-2 border-[#ff8c42]"
        >
          Historial de Compras
        </Link>
        <Link
          :href="route('admin.purchases.reportes')"
          class="px-4 py-2.5 text-sm font-semibold text-gray-400 hover:text-gray-600 border-b-2 border-transparent"
        >
          Reportes
        </Link>
      </div>

      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>
      <div v-if="page.props.errors?.error" class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation"></i> {{ page.props.errors.error }}
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Total de Compras Registradas</p>
          <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.totalCompras }}</p>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Total Invertido</p>
          <p class="text-3xl font-black text-[#ff8c42] mt-1">S/ {{ stats.totalGastado.toFixed(2) }}</p>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Promedio por Compra</p>
          <p class="text-3xl font-black text-gray-900 mt-1">S/ {{ stats.promedioCompra.toFixed(2) }}</p>
        </div>
      </div>

      <!-- List -->
      <ListCard title="Compras registradas" subtitle="Historial de compras de mercadería a tus proveedores">
        <template #toolbar>
          <SearchInput v-model="filtroTexto" placeholder="Buscar por producto, proveedor, factura o N° de compra" class="w-full sm:w-96" />
        </template>

        <div v-if="comprasFiltradas.length > 0" class="space-y-3 px-6 pb-6">
          <div
            v-for="compra in comprasPaginadas"
            :key="compra.id"
            :id="`compra-${compra.id}`"
            :class="[
              'border rounded-xl overflow-hidden transition-colors',
              compra.id === compraDestacada ? 'border-[#ff8c42] ring-2 ring-[#ff8c42]/30' : 'border-gray-100',
              compra.cancelada ? 'opacity-60' : '',
            ]"
          >
            <button
              @click="toggleExpand(compra.id)"
              class="w-full flex items-center justify-between px-5 py-4 hover:bg-orange-50/40 transition-colors text-left"
            >
              <div class="flex items-center gap-4 min-w-0">
                <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded flex-shrink-0">{{ compra.numero }}</span>
                <div class="min-w-0">
                  <div class="flex items-center gap-2 min-w-0">
                    <p :class="['text-sm font-semibold text-gray-900 truncate', compra.cancelada ? 'line-through' : '']">{{ tituloCompra(compra) }}</p>
                    <span v-if="compra.cancelada" class="text-[11px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0 bg-red-50 text-red-500">
                      <i class="fa-solid fa-ban text-[9px] mr-1"></i>Cancelada
                    </span>
                  </div>
                  <p class="text-xs text-gray-400 mt-0.5">
                    {{ compra.fecha }} · {{ compra.totalPrendas }} prenda{{ compra.totalPrendas === 1 ? '' : 's' }}
                    <span v-if="compra.proveedor"> · {{ compra.proveedor }}</span>
                    <span v-if="compra.factura"> · Factura {{ compra.factura }}</span>
                    <span v-if="compra.registradoPor"> · Registrado por {{ compra.registradoPor }}</span>
                    <span v-if="compra.cancelada && compra.canceladaPor"> · Cancelada por {{ compra.canceladaPor }}</span>
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-4 flex-shrink-0">
                <span :class="['text-lg font-bold text-gray-900', compra.cancelada ? 'line-through' : '']">S/ {{ compra.total.toFixed(2) }}</span>
                <i :class="['fa-solid', expandedId === compra.id ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
              </div>
            </button>

            <div v-if="expandedId === compra.id" class="border-t border-gray-100 bg-gray-50 px-5 py-4">
              <div class="flex items-center justify-between mb-3">
                <p v-if="compra.notas" class="text-xs text-gray-500 italic">"{{ compra.notas }}"</p>
                <span v-else></span>
                <button
                  v-if="!compra.cancelada && esAdmin"
                  @click="cancelarCompra(compra)"
                  class="text-xs font-semibold text-red-500 hover:text-white hover:bg-red-500 bg-surface border border-red-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 flex-shrink-0 transition-colors"
                >
                  <i class="fa-solid fa-ban text-[11px]"></i> Cancelar Compra
                </button>
              </div>

              <table class="w-full text-xs">
                <thead>
                  <tr class="text-gray-400">
                    <th class="text-left py-1.5 pr-4 font-semibold">Producto</th>
                    <th class="text-left py-1.5 pr-4 font-semibold">Marca</th>
                    <th class="text-left py-1.5 pr-4 font-semibold">Talla</th>
                    <th class="text-left py-1.5 pr-4 font-semibold">Color</th>
                    <th class="text-center py-1.5 pr-4 font-semibold">Cant.</th>
                    <th class="text-right py-1.5 pr-4 font-semibold">Costo Unit.</th>
                    <th class="text-right py-1.5 font-semibold">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in compra.items" :key="idx" class="border-t border-gray-100">
                    <td class="py-2 pr-4 text-gray-900 font-medium">{{ item.producto }}</td>
                    <td class="py-2 pr-4 text-gray-600">{{ item.marca }}</td>
                    <td class="py-2 pr-4 text-gray-600">{{ item.talla }}</td>
                    <td class="py-2 pr-4">
                      <div class="flex items-center gap-1.5">
                        <ColorSwatch :hex="item.colorHex" class="w-3.5 h-3.5 rounded-full border border-gray-200" />
                        <span class="text-gray-600">{{ item.color }}</span>
                      </div>
                    </td>
                    <td class="py-2 pr-4 text-center text-gray-900">{{ item.cantidad }}</td>
                    <td class="py-2 pr-4 text-right text-gray-900">S/ {{ item.costoUnitario.toFixed(2) }}</td>
                    <td class="py-2 text-right text-gray-900 font-semibold">S/ {{ item.subtotal.toFixed(2) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <Pagination v-model="paginaActual" :total-items="comprasFiltradas.length" :per-page="perPage" />
        </div>

        <EmptyState
          v-else-if="compras.length === 0"
          icon="fa-solid fa-receipt"
          title="Aún no hay compras registradas"
          text="Registra tu primera compra desde la pestaña Inventario."
        />

        <EmptyState
          v-else
          icon="fa-solid fa-filter-circle-xmark"
          title="Ninguna compra coincide"
          text="Prueba con otra búsqueda."
        >
          <button @click="filtroTexto = ''" class="text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 px-4 py-2 rounded-xl transition">Limpiar búsqueda</button>
        </EmptyState>
      </ListCard>
    </div>

    <ConfirmModal
      v-if="compraPendienteCancelar"
      title="Cancelar Compra"
      :message="`¿Cancelar la compra ${compraPendienteCancelar.numero}? El stock que agregó se revertirá.`"
      confirm-text="Sí, Cancelar"
      cancel-text="Volver"
      variant="danger"
      :loading="cancelando"
      @confirm="confirmarCancelacion"
      @close="compraPendienteCancelar = null"
    />
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ListCard from '@/Components/Admin/ListCard.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, nextTick } from 'vue';

const props = defineProps({
  compras: Array,
  stats: Object,
});

const page = usePage();
const esAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const compraPendienteCancelar = ref(null);
const cancelando = ref(false);

const cancelarCompra = (compra) => {
  compraPendienteCancelar.value = compra;
};

const confirmarCancelacion = () => {
  cancelando.value = true;

  router.patch(route('admin.purchases.cancel', compraPendienteCancelar.value.id), {}, {
    preserveScroll: true,
    onFinish: () => {
      cancelando.value = false;
      compraPendienteCancelar.value = null;
    },
  });
};

const filtroTexto = ref('');
const expandedId = ref(null);
const compraDestacada = ref(null);

const toggleExpand = (id) => {
  expandedId.value = expandedId.value === id ? null : id;
};

const tituloCompra = (compra) => {
  const nombresUnicos = [...new Set(compra.items.map(i => i.producto))];
  if (nombresUnicos.length === 1) return nombresUnicos[0];
  return `${nombresUnicos[0]} + ${nombresUnicos.length - 1} más`;
};

const comprasFiltradas = computed(() => {
  const texto = filtroTexto.value.trim().toLowerCase();
  if (!texto) return props.compras;

  return props.compras.filter((c) =>
    (c.proveedor || '').toLowerCase().includes(texto) ||
    (c.factura || '').toLowerCase().includes(texto) ||
    c.numero.toLowerCase().includes(texto) ||
    c.items.some(i => i.producto.toLowerCase().includes(texto))
  );
});

const perPage = 15;
const paginaActual = ref(1);

watch(filtroTexto, () => {
  paginaActual.value = 1;
});

watch(() => comprasFiltradas.value.length, (total) => {
  const totalPaginas = Math.max(1, Math.ceil(total / perPage));
  if (paginaActual.value > totalPaginas) paginaActual.value = totalPaginas;
});

const comprasPaginadas = computed(() => {
  const inicio = (paginaActual.value - 1) * perPage;
  return comprasFiltradas.value.slice(inicio, inicio + perPage);
});

// Llega desde "Historial de Compras" de un proveedor (?compra=123): la busca, se
// asegura de estar en la página correcta, la expande y hace scroll hasta ella.
onMounted(() => {
  const compraId = Number(new URLSearchParams(window.location.search).get('compra'));
  if (!compraId) return;

  const indice = props.compras.findIndex(c => c.id === compraId);
  if (indice === -1) return;

  paginaActual.value = Math.floor(indice / perPage) + 1;
  expandedId.value = compraId;
  compraDestacada.value = compraId;

  nextTick(() => {
    document.getElementById(`compra-${compraId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
});
</script>
