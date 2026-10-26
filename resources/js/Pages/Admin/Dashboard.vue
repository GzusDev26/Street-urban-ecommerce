<template>
  <Head title="Dashboard" />
  <AdminLayout>
    <template #breadcrumb>Dashboard</template>
    <template #header>Dashboard</template>

    <div class="space-y-5 sm:space-y-6">
      <!-- Metric Cards (2×2 en el celular) -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-5">
        <div
          v-for="metric in metrics"
          :key="metric.label"
          class="relative bg-surface rounded-2xl border border-gray-100 p-4 pl-5 sm:p-6 sm:pl-7 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm hover:shadow-md transition-shadow overflow-hidden"
        >
          <span :class="['absolute left-0 top-0 bottom-0 w-1.5', metric.accent]"></span>
          <div>
            <p class="text-xs sm:text-sm text-gray-500 font-medium">{{ metric.label }}</p>
            <p class="text-xl min-[400px]:text-2xl sm:text-3xl font-black text-gray-900 mt-0.5 sm:mt-1 tabular-nums whitespace-nowrap">{{ metric.value }}</p>
            <p
              :class="[
                'text-[11px] sm:text-xs font-semibold mt-1.5 sm:mt-2',
                metric.change.type === 'up' ? 'text-green-500' : metric.change.type === 'down' ? 'text-red-500' : 'text-gray-300 italic font-medium',
              ]"
            >
              <i
                v-if="metric.change.type !== 'neutral'"
                :class="['fa-solid text-[10px] mr-1', metric.change.type === 'up' ? 'fa-arrow-up' : 'fa-arrow-down']"
              ></i>{{ metric.change.text }}
            </p>
          </div>
          <div :class="['order-first sm:order-last w-10 h-10 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center text-base sm:text-xl flex-shrink-0', metric.iconBg, metric.iconColor]">
            <i :class="['fa-solid', metric.icon]"></i>
          </div>
        </div>
      </div>

      <!-- Chart + Sidebar widgets -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <!-- Weekly sales chart -->
        <div class="xl:col-span-2 bg-surface rounded-2xl border border-gray-100 p-4 sm:p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col">
          <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2 mb-5 sm:mb-6">
            <div>
              <h3 class="text-lg font-bold text-gray-900">Ventas Semanales</h3>
              <p class="text-xs text-gray-400">Últimos 7 días</p>
            </div>
            <div class="sm:text-right">
              <p class="text-xl sm:text-2xl font-black text-gray-900 tabular-nums">S/ {{ totalSemanaMonto.toFixed(2) }}</p>
              <p class="text-xs text-gray-400">
                {{ totalSemanaVentas }} venta{{ totalSemanaVentas === 1 ? '' : 's' }} esta semana
                <span v-if="totalSemanaCanceladas > 0" class="text-red-400"> · {{ totalSemanaCanceladas }} canceladas</span>
              </p>
            </div>
          </div>

          <div class="flex-1 flex flex-col min-h-[220px]">
            <div class="relative flex-1 border-b border-gray-100">
              <!-- Líneas de referencia -->
              <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                <div v-for="n in 4" :key="n" class="border-t border-gray-50 w-full"></div>
              </div>

              <div class="relative h-full flex items-end justify-between gap-1.5 sm:gap-3">
                <div v-for="day in weeklySales" :key="day.label" class="flex-1 flex items-end justify-center gap-1.5 h-full">
                  <div class="w-1/2 h-full flex flex-col items-center justify-end">
                    <span v-if="day.ventas > 0" class="text-[10px] font-bold text-gray-600 mb-1">{{ day.ventas }}</span>
                    <div class="w-full rounded-t-md bg-[#ff8c42] transition-all" :style="{ height: pctBarra(day.ventas) + '%' }"></div>
                  </div>
                  <div class="w-1/2 h-full flex flex-col items-center justify-end">
                    <span v-if="day.canceladas > 0" class="text-[10px] font-bold text-gray-400 mb-1">{{ day.canceladas }}</span>
                    <div class="w-full rounded-t-md bg-gray-200 transition-all" :style="{ height: pctBarra(day.canceladas) + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-start justify-between gap-1.5 sm:gap-3 pt-2">
              <div v-for="day in weeklySales" :key="day.label" class="flex-1 text-center">
                <p class="text-[11px] text-gray-500">{{ day.label }}</p>
                <p :class="['text-[10px] sm:text-[11px] font-bold mt-0.5 tabular-nums', day.monto > 0 ? 'text-gray-700' : 'text-gray-300']">
                  <span class="sm:hidden">{{ montoCorto(day.monto) }}</span>
                  <span class="hidden sm:inline">S/ {{ day.monto.toFixed(2) }}</span>
                </p>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-x-6 gap-y-1 mt-4 pt-4 border-t border-gray-50 text-xs text-gray-500">
            <span class="sm:hidden w-full text-[11px] text-gray-400">Montos en soles (K = miles)</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#ff8c42]"></span>Ventas</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gray-200"></span>Canceladas</span>
          </div>
        </div>

        <!-- Quick actions + Top products -->
        <div class="space-y-5">
          <div class="bg-surface rounded-2xl border border-gray-100 p-4 sm:p-6 shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-sm font-bold text-gray-900 mb-3 sm:mb-4">Acciones Rápidas</h3>
            <div class="space-y-2.5">
              <Link
                :href="route('admin.sales.create')"
                class="w-full bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 text-white text-sm font-bold h-12 sm:h-auto sm:py-3 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-sm"
              >
                <i class="fa-solid fa-plus text-xs"></i> Nueva Venta
              </Link>
              <div class="grid grid-cols-3 gap-2">
                <Link
                  :href="route('admin.purchases.index')"
                  class="flex flex-col items-center justify-center gap-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 min-h-[4.5rem] py-3 rounded-xl transition-colors"
                >
                  <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-cubes"></i>
                  </span>
                  <span class="text-[11px] font-semibold">Inventario</span>
                </Link>
                <Link
                  :href="route('admin.sales.index')"
                  class="flex flex-col items-center justify-center gap-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 min-h-[4.5rem] py-3 rounded-xl transition-colors"
                >
                  <span class="w-8 h-8 rounded-lg bg-orange-50 text-[#ff8c42] flex items-center justify-center text-sm">
                    <i class="fa-solid fa-receipt"></i>
                  </span>
                  <span class="text-[11px] font-semibold">Ventas</span>
                </Link>
                <Link
                  :href="route('admin.customers.index')"
                  class="flex flex-col items-center justify-center gap-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 min-h-[4.5rem] py-3 rounded-xl transition-colors"
                >
                  <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-500 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-people-group"></i>
                  </span>
                  <span class="text-[11px] font-semibold">Clientes</span>
                </Link>
              </div>
            </div>
          </div>

          <div class="bg-surface rounded-2xl border border-gray-100 p-4 sm:p-6 shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-sm font-bold text-gray-900 mb-3 sm:mb-4">Productos Top</h3>
            <div v-if="topProductos.length > 0" class="space-y-4">
              <div v-for="product in topProductos" :key="product.name">
                <div class="flex items-center justify-between text-xs mb-1.5">
                  <span class="font-semibold text-gray-700 truncate">{{ product.name }}</span>
                  <span class="text-gray-400 flex-shrink-0 ml-2">{{ product.unidades }} {{ product.unidades === 1 ? 'unidad' : 'unidades' }}</span>
                </div>
                <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                  <div :class="['h-full rounded-full', product.color]" :style="{ width: product.percent + '%' }"></div>
                </div>
              </div>
            </div>
            <p v-else class="text-xs text-gray-400">Aún no hay ventas registradas.</p>
          </div>
        </div>
      </div>

      <!-- Recent sales -->
      <div class="bg-surface rounded-2xl border border-gray-100 p-4 sm:p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-3 sm:mb-4">
          <h3 class="text-lg font-bold text-gray-900">Ventas Recientes</h3>
          <Link :href="route('admin.sales.index')" class="text-sm sm:text-xs font-semibold text-[#e67e2d] hover:text-[#ff7a24] h-11 sm:h-auto flex items-center">
            Ver todas <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
          </Link>
        </div>

        <!-- Celular: una tarjeta por venta (la tabla de 7 columnas no cabe) -->
        <ul v-if="ventasRecientes.length > 0" class="md:hidden divide-y divide-gray-100 -mx-1">
          <li v-for="venta in ventasRecientes" :key="'m' + venta.id">
            <Link :href="`${route('admin.sales.index')}?venta=${venta.id}`" class="flex items-center gap-3 px-1 py-3.5 min-h-[4rem] active:bg-gray-50">
              <span
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-[11px] font-bold flex-shrink-0',
                  venta.cancelada ? 'bg-gray-100 text-gray-400' : venta.customer === 'Sin cliente registrado' ? 'bg-gray-100 text-gray-500' : 'bg-orange-50 text-[#e67e2d]',
                ]"
              >#{{ venta.id }}</span>
              <span class="min-w-0 flex-1">
                <span :class="['block text-sm font-semibold truncate', venta.cancelada ? 'text-gray-400 line-through' : 'text-gray-900']">{{ venta.customer }}</span>
                <span class="flex items-center gap-1.5 text-xs text-gray-500 truncate">
                  <ColorSwatch :hex="venta.colorHex" class="w-2.5 h-2.5 rounded-full border border-gray-200 flex-shrink-0" />
                  <span class="truncate">{{ venta.product }}</span>
                </span>
                <span class="block text-[11px] text-gray-400 mt-0.5">{{ venta.fecha }}</span>
              </span>
              <span class="text-right flex-shrink-0">
                <span :class="['block text-sm font-black tabular-nums', venta.cancelada ? 'text-gray-300 line-through' : 'text-gray-900']">S/ {{ venta.amount.toFixed(2) }}</span>
                <span :class="['inline-flex items-center gap-1 mt-1 text-[10px] font-semibold px-2 py-0.5 rounded-full', venta.cancelada ? 'bg-red-50 text-red-500' : 'bg-green-50 text-green-600']">
                  <i :class="['fa-solid text-[8px]', venta.cancelada ? 'fa-ban' : 'fa-circle-check']" aria-hidden="true"></i>{{ venta.cancelada ? 'Cancelada' : 'Completada' }}
                </span>
              </span>
            </Link>
          </li>
        </ul>

        <div v-if="ventasRecientes.length > 0" class="hidden md:block overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-gray-200">
              <tr>
                <th class="text-center py-3 px-4 font-semibold text-gray-500">N°</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-500">Cliente</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-500">Producto</th>
                <th class="text-center py-3 px-4 font-semibold text-gray-500">Fecha y Hora</th>
                <th class="text-right py-3 px-4 font-semibold text-gray-500">Monto</th>
                <th class="text-center py-3 px-4 font-semibold text-gray-500">Estado</th>
                <th class="w-8"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="venta in ventasRecientes"
                :key="venta.id"
                @click="router.visit(`${route('admin.sales.index')}?venta=${venta.id}`)"
                class="border-b border-gray-50 hover:bg-gray-50 transition-colors cursor-pointer"
              >
                <td class="py-3 px-4">
                  <div class="flex justify-center">
                    <div
                      :class="[
                        'w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0',
                        venta.cancelada ? 'bg-gray-100 text-gray-300' :
                        venta.customer === 'Sin cliente registrado' ? 'bg-gray-100 text-gray-400' : 'bg-orange-50 text-[#ff8c42]',
                      ]"
                    >
                      #{{ venta.id }}
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span :class="['font-medium truncate', venta.cancelada ? 'text-gray-400 line-through' : 'text-gray-800']">
                    {{ venta.customer }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2 text-gray-600">
                    <ColorSwatch :hex="venta.colorHex" class="w-2.5 h-2.5 rounded-full border border-gray-200 flex-shrink-0" />
                    {{ venta.product }}
                  </div>
                </td>
                <td class="py-3 px-4 text-center text-gray-400 whitespace-nowrap">{{ venta.fecha }}</td>
                <td class="py-3 px-4 text-right">
                  <span :class="['font-bold', venta.cancelada ? 'text-gray-300 line-through' : 'text-gray-900']">
                    S/ {{ venta.amount.toFixed(2) }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex justify-center">
                    <span
                      :class="[
                        'text-[11px] font-semibold px-2.5 py-1 rounded-full flex items-center gap-1 whitespace-nowrap',
                        venta.cancelada ? 'bg-red-50 text-red-400' : 'bg-green-50 text-green-600',
                      ]"
                    >
                      <i :class="['fa-solid text-[9px]', venta.cancelada ? 'fa-ban' : 'fa-circle-check']"></i>
                      {{ venta.cancelada ? 'Cancelada' : 'Completada' }}
                    </span>
                  </div>
                </td>
                <td class="pr-2 text-gray-300">
                  <i class="fa-solid fa-chevron-right text-xs"></i>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-sm text-gray-400 py-4 text-center">Aún no hay ventas registradas.</p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  stats: Object,
  weeklySales: Array,
  topProductos: Array,
  ventasRecientes: Array,
});

// Siempre devuelve { text, type } (nunca null) para que las 4 tarjetas midan lo mismo,
// tengan o no datos del mes anterior con qué comparar.
const formatCambio = (valor) => {
  if (valor === null || valor === undefined) {
    return { text: 'Sin datos del mes anterior', type: 'neutral' };
  }
  return {
    text: `${valor > 0 ? '+' : ''}${valor}% vs mes anterior`,
    type: valor >= 0 ? 'up' : 'down',
  };
};

const formatNuevos = (cantidad) => ({
  text: `+${cantidad} este mes`,
  type: 'up',
});

const metrics = computed(() => [
  {
    label: 'Ventas del Mes',
    value: `S/ ${props.stats.ventasMes.toFixed(2)}`,
    change: formatCambio(props.stats.cambioVentas),
    icon: 'fa-sack-dollar', iconBg: 'bg-blue-50', iconColor: 'text-blue-500', accent: 'bg-blue-400',
  },
  {
    label: 'Ventas',
    value: props.stats.numVentasMes,
    change: formatCambio(props.stats.cambioNumVentas),
    icon: 'fa-receipt', iconBg: 'bg-orange-50', iconColor: 'text-[#ff8c42]', accent: 'bg-[#ff8c42]',
  },
  {
    label: 'Clientes',
    value: props.stats.totalClientes,
    change: formatNuevos(props.stats.nuevosClientesMes),
    icon: 'fa-people-group', iconBg: 'bg-purple-50', iconColor: 'text-purple-500', accent: 'bg-purple-400',
  },
  {
    label: 'Productos',
    value: props.stats.totalProductos,
    change: formatNuevos(props.stats.nuevosProductosMes),
    icon: 'fa-cubes', iconBg: 'bg-green-50', iconColor: 'text-green-500', accent: 'bg-green-400',
  },
]);

// Montos cortos para el celular, donde 7 columnas no dejan espacio para "S/ 1234.56": 850 → "850", 1259 → "1.3K".
const montoCorto = (m) => {
  if (!m) return '—';
  return m >= 1000 ? `${(m / 1000).toFixed(1)}K` : String(Math.round(m));
};

const maxSales = computed(() => Math.max(1, ...props.weeklySales.map(d => Math.max(d.ventas, d.canceladas))));
const pctBarra = (valor) => (valor / maxSales.value) * 100;

const totalSemanaVentas = computed(() => props.weeklySales.reduce((sum, d) => sum + d.ventas, 0));
const totalSemanaCanceladas = computed(() => props.weeklySales.reduce((sum, d) => sum + d.canceladas, 0));
const totalSemanaMonto = computed(() => props.weeklySales.reduce((sum, d) => sum + d.monto, 0));
</script>
