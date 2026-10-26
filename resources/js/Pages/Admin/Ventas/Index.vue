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
        <div v-for="stat in tarjetas" :key="stat.label" class="bg-surface rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
          <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', stat.fondo]">
            <i :class="['fa-solid text-lg', stat.icono, stat.color]"></i>
          </div>
          <div class="min-w-0">
            <p class="text-sm text-gray-400 font-medium">{{ stat.label }}</p>
            <p :class="['text-2xl font-black mt-0.5 truncate', stat.destacado ? 'text-[#ff8c42]' : 'text-gray-900']">{{ stat.valor }}</p>
          </div>
        </div>
      </div>

      <!-- List -->
      <ListCard title="Ventas registradas" subtitle="Historial de ventas y prendas despachadas">
        <template #action>
          <PrimaryButton :href="route('admin.sales.create')">Registrar venta</PrimaryButton>
        </template>

        <template #toolbar>
          <SearchInput v-model="filtroTexto" placeholder="Buscar por producto, cliente o N° de venta" class="w-full sm:w-96" />
          <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar por estado">
            <button
              v-for="f in filtrosEstado"
              :key="f.value"
              @click="filtroEstado = f.value"
              :aria-pressed="filtroEstado === f.value"
              :class="[
                'text-xs font-semibold px-3.5 py-2 rounded-full border transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]/50',
                filtroEstado === f.value
                  ? 'bg-[#ff8c42] border-[#ff8c42] text-white shadow-sm'
                  : 'bg-surface border-gray-200 text-gray-500 hover:border-[#ff8c42]/50 hover:text-[#ff8c42]',
              ]"
            >
              {{ f.label }}
              <span :class="['ml-1 tabular-nums', filtroEstado === f.value ? 'text-white/80' : 'text-gray-300']">{{ f.cantidad }}</span>
            </button>
          </div>
        </template>

        <div v-if="ventasFiltradas.length > 0" class="px-6 pb-6">
          <section v-for="grupo in gruposPaginados" :key="grupo.fecha" class="mb-6 last:mb-0">
            <!-- Cabecera del día -->
            <div class="flex items-center gap-3 mb-3">
              <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ grupo.etiqueta }}</h4>
              <span class="flex-1 h-px bg-gray-100"></span>
              <p class="text-xs text-gray-400 whitespace-nowrap">
                {{ grupo.cantidadVentas }} venta{{ grupo.cantidadVentas === 1 ? '' : 's' }}
                <span class="mx-1 text-gray-200">|</span>
                <span class="font-bold text-gray-700">S/ {{ grupo.total.toFixed(2) }}</span>
              </p>
            </div>

            <div class="space-y-3">
              <article
                v-for="venta in grupo.ventas"
                :key="venta.id"
                :id="`venta-${venta.id}`"
                :class="[
                  'rounded-2xl border bg-surface overflow-hidden transition-all duration-200 border-l-4',
                  acentoEstado(venta),
                  venta.id === ventaDestacada ? 'ring-2 ring-[#ff8c42]/30 border-[#ff8c42]' : 'border-gray-100',
                  expandedId === venta.id ? 'shadow-md' : 'hover:shadow-sm',
                ]"
              >
                <button
                  @click="toggleExpand(venta.id)"
                  :aria-expanded="expandedId === venta.id"
                  :aria-controls="`detalle-${venta.id}`"
                  class="w-full flex items-center justify-between gap-4 px-5 py-4 hover:bg-orange-50/40 transition-colors text-left focus-visible:outline-none focus-visible:bg-orange-50/60"
                >
                  <div class="flex items-center gap-4 min-w-0">
                    <div
                      :class="[
                        'w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold',
                        venta.cliente ? 'bg-orange-50 text-[#ff8c42]' : 'bg-gray-100 text-gray-300',
                      ]"
                      aria-hidden="true"
                    >
                      <template v-if="venta.cliente">{{ iniciales(venta.cliente) }}</template>
                      <i v-else class="fa-solid fa-user text-sm"></i>
                    </div>

                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <p :class="['text-sm font-bold text-gray-900 truncate max-w-full', venta.cancelada ? 'line-through text-gray-500' : '']">{{ tituloVenta(venta) }}</p>
                        <span class="font-mono text-[11px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded flex-shrink-0">{{ venta.numero }}</span>
                        <span v-if="venta.cancelada" class="text-[11px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0 bg-red-50 text-red-500">
                          <i class="fa-solid fa-ban text-[9px] mr-1"></i>Cancelada
                        </span>
                        <span v-else-if="venta.prendasDevueltas > 0" class="text-[11px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0 bg-amber-50 text-amber-600">
                          <i class="fa-solid fa-rotate-left text-[9px] mr-1"></i>{{ venta.prendasDevueltas }} devuelta{{ venta.prendasDevueltas === 1 ? '' : 's' }}
                        </span>
                      </div>
                      <p class="text-xs text-gray-500 mt-1 truncate">
                        <span class="font-medium text-gray-700">{{ venta.cliente || 'Sin cliente registrado' }}</span>
                        <span class="text-gray-300 mx-1.5">&bull;</span>{{ venta.totalPrendas }} prenda{{ venta.totalPrendas === 1 ? '' : 's' }}
                        <span v-if="venta.registradoPor" class="hidden sm:inline"><span class="text-gray-300 mx-1.5">&bull;</span>Por {{ venta.registradoPor }}</span>
                        <span v-if="venta.cancelada && venta.canceladaPor" class="hidden sm:inline"><span class="text-gray-300 mx-1.5">&bull;</span>Cancelada por {{ venta.canceladaPor }}</span>
                      </p>
                    </div>
                  </div>

                  <div class="flex items-center gap-4 flex-shrink-0">
                    <div class="text-right">
                      <p :class="['text-lg font-black tabular-nums', venta.cancelada ? 'line-through text-gray-400' : 'text-gray-900']">S/ {{ venta.totalNeto.toFixed(2) }}</p>
                      <p v-if="!venta.cancelada && venta.totalNeto !== venta.total" class="text-[11px] text-gray-400 line-through tabular-nums">S/ {{ venta.total.toFixed(2) }}</p>
                    </div>
                    <span :class="['w-8 h-8 rounded-full flex items-center justify-center transition-colors', expandedId === venta.id ? 'bg-orange-50 text-[#ff8c42]' : 'bg-gray-50 text-gray-400']">
                      <i :class="['fa-solid fa-chevron-down text-xs transition-transform duration-200', expandedId === venta.id ? 'rotate-180' : '']"></i>
                    </span>
                  </div>
                </button>

                <!-- Detalle -->
                <div v-if="expandedId === venta.id" :id="`detalle-${venta.id}`" class="border-t border-gray-100 bg-gray-50/70 px-5 py-5">
                  <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <p v-if="venta.notas" class="text-xs text-gray-500 italic flex items-center gap-1.5">
                      <i class="fa-regular fa-note-sticky text-gray-300"></i>{{ venta.notas }}
                    </p>
                    <span v-else></span>
                    <div class="flex items-center gap-2">
                      <button
                        v-if="!venta.cancelada && esAdmin"
                        @click="cancelarVenta(venta)"
                        class="text-xs font-semibold text-red-500 hover:text-white hover:bg-red-500 bg-surface border border-red-200 px-3.5 py-2 rounded-lg flex items-center gap-1.5 flex-shrink-0 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-300"
                      >
                        <i class="fa-solid fa-ban text-[11px]"></i> Cancelar venta
                      </button>
                      <button
                        type="button"
                        @click="boletaAbierta = venta.id"
                        class="text-xs font-semibold text-canvas bg-fg hover:bg-[#ff8c42] px-3.5 py-2 rounded-lg flex items-center gap-1.5 flex-shrink-0 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]/50"
                      >
                        <i class="fa-solid fa-print text-[11px]"></i> Imprimir boleta
                      </button>
                    </div>
                  </div>

                  <div class="bg-surface rounded-xl border border-gray-100 overflow-x-auto">
                    <table class="w-full text-xs min-w-[640px]">
                      <thead>
                        <tr class="text-gray-400 bg-gray-50/80 uppercase tracking-wide text-[10px]">
                          <th class="text-left py-2.5 pl-4 pr-4 font-semibold">Producto</th>
                          <th class="text-left py-2.5 pr-4 font-semibold">Talla</th>
                          <th class="text-left py-2.5 pr-4 font-semibold">Color</th>
                          <th class="text-center py-2.5 pr-4 font-semibold">Cant.</th>
                          <th class="text-right py-2.5 pr-4 font-semibold">P. Unit.</th>
                          <th class="text-right py-2.5 pr-4 font-semibold">Subtotal</th>
                          <th v-if="!venta.cancelada && esAdmin" class="text-center py-2.5 pr-4 font-semibold">Devolución</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, idx) in venta.items" :key="idx" class="border-t border-gray-100">
                          <td class="py-3 pl-4 pr-4">
                            <p class="text-gray-900 font-semibold text-[13px]">{{ item.producto }}</p>
                            <p class="text-gray-400 mt-0.5">{{ item.marca }}</p>
                          </td>
                          <td class="py-3 pr-4">
                            <span class="font-semibold text-gray-700 bg-gray-100 rounded-md px-2 py-0.5">{{ item.talla }}</span>
                          </td>
                          <td class="py-3 pr-4">
                            <div class="flex items-center gap-1.5">
                              <ColorSwatch :hex="item.colorHex" class="w-3.5 h-3.5 rounded-full border border-gray-200" />
                              <span class="text-gray-600">{{ item.color }}</span>
                            </div>
                          </td>
                          <td class="py-3 pr-4 text-center text-gray-900 font-semibold tabular-nums">
                            {{ item.cantidad }}
                            <span v-if="item.cantidadDevuelta > 0" class="block text-[10px] text-red-400 font-semibold">-{{ item.cantidadDevuelta }} dev.</span>
                          </td>
                          <td class="py-3 pr-4 text-right text-gray-600 tabular-nums">S/ {{ item.precioUnitario.toFixed(2) }}</td>
                          <td class="py-3 pr-4 text-right text-gray-900 font-bold tabular-nums">
                            S/ {{ item.subtotalNeto.toFixed(2) }}
                            <span v-if="item.subtotalNeto !== item.subtotal" class="block text-[10px] text-gray-400 font-normal line-through">S/ {{ item.subtotal.toFixed(2) }}</span>
                          </td>
                          <td v-if="!venta.cancelada && esAdmin" class="py-3 pr-4 text-center">
                            <button
                              v-if="item.pendienteDevolucion > 0"
                              @click="devolverItem(item)"
                              class="text-[11px] font-semibold text-gray-500 hover:text-white hover:bg-red-500 bg-surface border border-gray-200 px-2.5 py-1 rounded-lg transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-300"
                            >
                              <i class="fa-solid fa-rotate-left text-[10px] mr-1"></i>Devolver
                            </button>
                            <span v-else class="text-[11px] text-gray-300">Devuelto</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Resumen -->
                  <div class="flex justify-end mt-4">
                    <dl class="w-full sm:w-64 space-y-1.5 text-xs">
                      <div v-if="venta.totalNeto !== venta.total" class="flex justify-between text-gray-400">
                        <dt>Total facturado</dt>
                        <dd class="tabular-nums">S/ {{ venta.total.toFixed(2) }}</dd>
                      </div>
                      <div v-if="venta.totalNeto !== venta.total" class="flex justify-between text-red-400">
                        <dt>Devoluciones</dt>
                        <dd class="tabular-nums">- S/ {{ (venta.total - venta.totalNeto).toFixed(2) }}</dd>
                      </div>
                      <div class="flex justify-between items-baseline pt-2 border-t border-gray-200">
                        <dt class="font-semibold text-gray-600">Total neto</dt>
                        <dd :class="['text-base font-black tabular-nums', venta.cancelada ? 'text-gray-400 line-through' : 'text-gray-900']">S/ {{ venta.totalNeto.toFixed(2) }}</dd>
                      </div>
                    </dl>
                  </div>
                </div>
              </article>
            </div>
          </section>

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
          text="Prueba con otra búsqueda o cambia el filtro."
        >
          <button @click="limpiarFiltros" class="text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 px-4 py-2 rounded-xl transition">Limpiar filtros</button>
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

    <BoletaModal v-if="boletaAbierta" :venta-id="boletaAbierta" @close="boletaAbierta = null" />

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
import BoletaModal from './BoletaModal.vue';
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

const boletaAbierta = ref(null);
const itemDevolver = ref(null);

const devolverItem = (item) => {
  itemDevolver.value = item;
};

const tituloVenta = (venta) => {
  const nombresUnicos = [...new Set(venta.items.map(i => i.producto))];
  if (nombresUnicos.length === 1) return nombresUnicos[0];
  return `${nombresUnicos[0]} + ${nombresUnicos.length - 1} más`;
};

const filtroEstado = ref('todas');

const esVigente = (v) => !v.cancelada;
const tieneDevolucion = (v) => !v.cancelada && v.prendasDevueltas > 0;

const filtrosEstado = computed(() => [
  { value: 'todas', label: 'Todas', cantidad: props.ventas.length },
  { value: 'vigentes', label: 'Vigentes', cantidad: props.ventas.filter(esVigente).length },
  { value: 'devoluciones', label: 'Con devoluciones', cantidad: props.ventas.filter(tieneDevolucion).length },
  { value: 'canceladas', label: 'Canceladas', cantidad: props.ventas.filter((v) => v.cancelada).length },
]);

const ventasFiltradas = computed(() => {
  const texto = filtroTexto.value.trim().toLowerCase();

  return props.ventas.filter((v) => {
    if (filtroEstado.value === 'vigentes' && !esVigente(v)) return false;
    if (filtroEstado.value === 'devoluciones' && !tieneDevolucion(v)) return false;
    if (filtroEstado.value === 'canceladas' && !v.cancelada) return false;
    if (!texto) return true;

    return (v.cliente || '').toLowerCase().includes(texto) ||
      v.numero.toLowerCase().includes(texto) ||
      v.items.some(i => i.producto.toLowerCase().includes(texto));
  });
});

const limpiarFiltros = () => {
  filtroTexto.value = '';
  filtroEstado.value = 'todas';
};

const tarjetas = computed(() => [
  { label: 'Total de Ventas', valor: props.stats.totalVentas, icono: 'fa-receipt', color: 'text-blue-500', fondo: 'bg-blue-50' },
  { label: 'Ingresos Totales', valor: `S/ ${props.stats.totalIngresos.toFixed(2)}`, icono: 'fa-sack-dollar', color: 'text-[#ff8c42]', fondo: 'bg-orange-50', destacado: true },
  { label: 'Prendas Vendidas', valor: props.stats.prendasVendidas, icono: 'fa-shirt', color: 'text-emerald-500', fondo: 'bg-emerald-50' },
  { label: 'Promedio por Venta', valor: `S/ ${props.stats.promedioVenta.toFixed(2)}`, icono: 'fa-chart-simple', color: 'text-violet-500', fondo: 'bg-violet-50' },
]);

const iniciales = (nombre) =>
  nombre.trim().split(/\s+/).slice(0, 2).map((p) => p[0]).join('').toUpperCase();

// Franja de color a la izquierda de cada venta según su estado.
const acentoEstado = (v) => {
  if (v.cancelada) return 'border-l-red-300 opacity-70';
  if (v.prendasDevueltas > 0) return 'border-l-amber-300';
  return 'border-l-emerald-300';
};

// "d/m/Y" -> Date local
const parseFecha = (texto) => {
  const [d, m, y] = texto.split('/').map(Number);
  return new Date(y, m - 1, d);
};

const etiquetaDia = (texto) => {
  const fecha = parseFecha(texto);
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const dias = Math.round((hoy - fecha) / 86400000);
  const larga = fecha.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long' });
  const capitalizada = larga.charAt(0).toUpperCase() + larga.slice(1);

  if (dias === 0) return `Hoy · ${capitalizada}`;
  if (dias === 1) return `Ayer · ${capitalizada}`;
  return capitalizada;
};

const perPage = 15;
const paginaActual = ref(1);

watch([filtroTexto, filtroEstado], () => {
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

// Agrupa la página actual por día; el total del día se calcula sobre todas las ventas
// filtradas de esa fecha (aunque el día continúe en otra página) y excluye canceladas.
const gruposPaginados = computed(() => {
  const grupos = [];
  for (const venta of ventasPaginadas.value) {
    let grupo = grupos[grupos.length - 1];
    if (!grupo || grupo.fecha !== venta.fecha) {
      const delDia = ventasFiltradas.value.filter((v) => v.fecha === venta.fecha);
      grupo = {
        fecha: venta.fecha,
        etiqueta: etiquetaDia(venta.fecha),
        cantidadVentas: delDia.filter(esVigente).length,
        total: delDia.filter(esVigente).reduce((acc, v) => acc + v.totalNeto, 0),
        ventas: [],
      };
      grupos.push(grupo);
    }
    grupo.ventas.push(venta);
  }
  return grupos;
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
