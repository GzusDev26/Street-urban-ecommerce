<template>
  <Head title="Reportes de Ventas" />
  <AdminLayout>
    <template #breadcrumb>Ventas / Reportes</template>
    <template #header>Reportes de Ventas</template>

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
          class="px-4 py-2.5 text-sm font-semibold text-gray-400 hover:text-gray-600 border-b-2 border-transparent"
        >
          Historial de Ventas
        </Link>
        <Link
          :href="route('admin.sales.reportes')"
          class="px-4 py-2.5 text-sm font-semibold text-[#ff8c42] border-b-2 border-[#ff8c42]"
        >
          Reportes de Ventas
        </Link>
      </div>

      <!-- Filtros -->
      <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <i class="fa-solid fa-sliders text-gray-400 text-sm"></i>
          <h3 class="text-sm font-bold text-gray-900">Filtrar reporte</h3>
        </div>
        <div class="flex flex-wrap items-end gap-3">
          <div>
            <label for="reportes-1" class="block text-xs font-medium text-gray-500 mb-1.5">Desde</label>
            <input id="reportes-1"
              v-model="filtros.desde"
              type="date"
              class="border border-gray-200 rounded-lg text-sm px-3 py-2 text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
            >
          </div>
          <div>
            <label for="reportes-2" class="block text-xs font-medium text-gray-500 mb-1.5">Hasta</label>
            <input id="reportes-2"
              v-model="filtros.hasta"
              type="date"
              class="border border-gray-200 rounded-lg text-sm px-3 py-2 text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
            >
          </div>
          <div class="min-w-[190px]">
            <span class="block text-xs font-medium text-gray-500 mb-1.5">Cliente</span>
            <SelectDropdown v-model="filtros.cliente" :options="opcionesCliente" />
          </div>
          <div class="min-w-[190px]">
            <span class="block text-xs font-medium text-gray-500 mb-1.5">Categoría</span>
            <SelectDropdown v-model="filtros.categoria" :options="opcionesCategoria" />
          </div>

          <button
            @click="aplicarFiltros"
            class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 text-white text-sm font-bold py-2.5 px-6 rounded-xl transition-all duration-200 shadow-sm"
          >
            <i class="fa-solid fa-magnifying-glass text-xs"></i> Aplicar
          </button>
          <button
            v-if="hayFiltrosActivos"
            @click="limpiarFiltros"
            class="flex items-center gap-1.5 text-sm font-semibold text-gray-400 hover:text-gray-600 px-3 py-2.5"
          >
            <i class="fa-solid fa-xmark text-xs"></i> Limpiar
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5">
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-400 font-medium">Total Ingresos</p>
            <p class="text-3xl font-black text-[#ff8c42] mt-1">S/ {{ stats.totalIngresos.toFixed(2) }}</p>
          </div>
          <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-orange-50 text-[#ff8c42]">
            <i class="fa-solid fa-sack-dollar"></i>
          </div>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-400 font-medium">N° de Ventas</p>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.numeroVentas }}</p>
          </div>
          <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-blue-50 text-blue-500">
            <i class="fa-solid fa-cash-register"></i>
          </div>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-400 font-medium">Unidades Vendidas</p>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.totalUnidades }}</p>
          </div>
          <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-green-50 text-green-500">
            <i class="fa-solid fa-boxes-stacked"></i>
          </div>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-400 font-medium">Promedio por Venta</p>
            <p class="text-3xl font-black text-gray-900 mt-1">S/ {{ stats.promedioPorVenta.toFixed(2) }}</p>
          </div>
          <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-purple-50 text-purple-500">
            <i class="fa-solid fa-scale-balanced"></i>
          </div>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-400 font-medium">Utilidad</p>
            <p class="text-3xl font-black text-green-600 mt-1">S/ {{ stats.utilidadTotal.toFixed(2) }}</p>
          </div>
          <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-green-50 text-green-600">
            <i class="fa-solid fa-chart-line"></i>
          </div>
        </div>
      </div>

      <div v-if="stats.numeroVentas === 0" class="bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 p-16 text-center">
        <i class="fa-solid fa-chart-pie text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 font-medium">No hay ventas que coincidan con estos filtros.</p>
        <button v-if="hayFiltrosActivos" @click="limpiarFiltros" class="text-sm font-semibold text-[#ff8c42] hover:text-[#ff7a24] mt-3">
          Limpiar filtros
        </button>
      </div>

      <template v-else>
        <!-- Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
          <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-5">
              <i class="fa-solid fa-tags text-gray-400 text-sm"></i>
              <h3 class="text-sm font-bold text-gray-900">Ingresos por Categoría</h3>
            </div>
            <div class="space-y-4">
              <div v-for="(row, idx) in porCategoria" :key="row.label" class="flex items-center gap-3">
                <span :class="['w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0', rankColor(idx)]">{{ idx + 1 }}</span>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-semibold text-gray-700 truncate">{{ row.label }}</span>
                    <span class="text-gray-400 flex-shrink-0 ml-2">S/ {{ row.total.toFixed(2) }} <span class="text-gray-300">({{ pct(row.total).toFixed(0) }}%)</span></span>
                  </div>
                  <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#ff8c42] rounded-full transition-all" :style="{ width: barPct(row.total, porCategoria) + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-5">
              <i class="fa-solid fa-people-group text-gray-400 text-sm"></i>
              <h3 class="text-sm font-bold text-gray-900">Ingresos por Cliente</h3>
            </div>
            <div class="space-y-4">
              <div v-for="(row, idx) in porCliente" :key="row.label" class="flex items-center gap-3">
                <span :class="['w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0', rankColor(idx)]">{{ idx + 1 }}</span>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-semibold text-gray-700 truncate">{{ row.label }}</span>
                    <span class="text-gray-400 flex-shrink-0 ml-2">S/ {{ row.total.toFixed(2) }} <span class="text-gray-300">({{ pct(row.total).toFixed(0) }}%)</span></span>
                  </div>
                  <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-400 rounded-full transition-all" :style="{ width: barPct(row.total, porCliente) + '%' }"></div>
                  </div>
                  <p class="text-[11px] text-gray-400 mt-1">{{ row.ventas }} venta{{ row.ventas === 1 ? '' : 's' }} · {{ row.unidades }} unid.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-5">
              <i class="fa-solid fa-copyright text-gray-400 text-sm"></i>
              <h3 class="text-sm font-bold text-gray-900">Ingresos por Marca</h3>
            </div>
            <div class="space-y-4">
              <div v-for="(row, idx) in porMarca" :key="row.label" class="flex items-center gap-3">
                <span :class="['w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0', rankColor(idx)]">{{ idx + 1 }}</span>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-semibold text-gray-700 truncate">{{ row.label }}</span>
                    <span class="text-gray-400 flex-shrink-0 ml-2">S/ {{ row.total.toFixed(2) }} <span class="text-gray-300">({{ pct(row.total).toFixed(0) }}%)</span></span>
                  </div>
                  <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-green-400 rounded-full transition-all" :style="{ width: barPct(row.total, porMarca) + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top productos -->
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
          <div class="flex items-center gap-2 mb-5">
            <i class="fa-solid fa-trophy text-gray-400 text-sm"></i>
            <h3 class="text-lg font-bold text-gray-900">Productos Más Vendidos</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200">
                <tr>
                  <th class="text-center py-2 px-3 font-semibold text-gray-500">#</th>
                  <th class="text-left py-2 px-3 font-semibold text-gray-500">Producto</th>
                  <th class="text-left py-2 px-3 font-semibold text-gray-500">Marca</th>
                  <th class="text-left py-2 px-3 font-semibold text-gray-500">Categoría</th>
                  <th class="text-center py-2 px-3 font-semibold text-gray-500">N° Ventas</th>
                  <th class="text-center py-2 px-3 font-semibold text-gray-500">Unidades</th>
                  <th class="text-right py-2 px-3 font-semibold text-gray-500">Precio Prom.</th>
                  <th class="text-right py-2 px-3 font-semibold text-gray-500">Total Vendido</th>
                  <th class="text-right py-2 px-3 font-semibold text-gray-500">Utilidad</th>
                  <th class="text-right py-2 px-3 font-semibold text-gray-500">% del Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in topProductos" :key="row.label" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                  <td class="py-2.5 px-3 text-center">
                    <span :class="['w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px] font-bold', rankColor(idx)]">{{ idx + 1 }}</span>
                  </td>
                  <td class="py-2.5 px-3 text-gray-900 font-medium">{{ row.label }}</td>
                  <td class="py-2.5 px-3 text-gray-600">{{ row.marca }}</td>
                  <td class="py-2.5 px-3 text-gray-600">{{ row.categoria }}</td>
                  <td class="py-2.5 px-3 text-center text-gray-600">{{ row.numeroVentas }}</td>
                  <td class="py-2.5 px-3 text-center text-gray-900 font-semibold">{{ row.unidades }}</td>
                  <td class="py-2.5 px-3 text-right text-gray-600">S/ {{ (row.precioPromedio || 0).toFixed(2) }}</td>
                  <td class="py-2.5 px-3 text-right text-gray-900 font-bold">S/ {{ row.total.toFixed(2) }}</td>
                  <td class="py-2.5 px-3 text-right text-green-600 font-semibold">S/ {{ row.utilidad.toFixed(2) }}</td>
                  <td class="py-2.5 px-3 text-right text-gray-400">{{ pct(row.total).toFixed(1) }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';

const props = defineProps({
  filtros: Object,
  clientes: Array,
  categorias: Array,
  stats: Object,
  porCategoria: Array,
  porCliente: Array,
  porMarca: Array,
  topProductos: Array,
});

const filtros = reactive({
  desde: props.filtros.desde || '',
  hasta: props.filtros.hasta || '',
  cliente: props.filtros.cliente || '',
  categoria: props.filtros.categoria || '',
});

const opcionesCliente = computed(() => [
  { value: '', label: 'Todos los clientes' },
  ...props.clientes.map(c => ({ value: c, label: c })),
]);

const opcionesCategoria = computed(() => [
  { value: '', label: 'Todas las categorías' },
  ...props.categorias.map(c => ({ value: c, label: c })),
]);

const hayFiltrosActivos = computed(() => !!(filtros.desde || filtros.hasta || filtros.cliente || filtros.categoria));

const aplicarFiltros = () => {
  router.get(route('admin.sales.reportes'), { ...filtros }, { preserveState: true, preserveScroll: true, replace: true });
};

const limpiarFiltros = () => {
  filtros.desde = '';
  filtros.hasta = '';
  filtros.cliente = '';
  filtros.categoria = '';
  aplicarFiltros();
};

const barPct = (valor, lista) => {
  const max = Math.max(...lista.map(r => r.total));
  return max > 0 ? (valor / max * 100) : 0;
};

const pct = (valor) => props.stats.totalIngresos > 0 ? (valor / props.stats.totalIngresos * 100) : 0;

const rankColor = (idx) => {
  if (idx === 0) return 'bg-amber-100 text-amber-700';
  if (idx === 1) return 'bg-gray-200 text-gray-600';
  if (idx === 2) return 'bg-orange-100 text-orange-700';
  return 'bg-gray-100 text-gray-400';
};
</script>
