<template>
  <Head title="Reportes de Ventas" />
  <AdminLayout>
    <template #breadcrumb>Ventas / Reportes</template>
    <template #header>Reportes de Ventas</template>

    <div class="viz space-y-5 sm:space-y-6">
      <nav class="flex gap-2 border-b border-gray-200 overflow-x-auto whitespace-nowrap -mx-4 px-4 sm:mx-0 sm:px-0" aria-label="Secciones de ventas">
        <Link :href="route('admin.sales.create')" class="px-4 py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-700 border-b-2 border-transparent">Registrar Venta</Link>
        <Link :href="route('admin.sales.index')" class="px-4 py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-700 border-b-2 border-transparent">Historial de Ventas</Link>
        <Link :href="route('admin.sales.reportes')" aria-current="page" class="px-4 py-2.5 text-sm font-semibold text-[#e67e2d] border-b-2 border-[#ff8c42]">Reportes de Ventas</Link>
      </nav>

      <ReporteToolbar :filtros="filtros" :listas="listas" ruta-reporte="admin.sales.reportes" ruta-excel="admin.sales.reportes.exportar" />

      <div v-if="stats.numeroVentas === 0" class="bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 p-16 text-center">
        <i class="fa-solid fa-chart-pie text-5xl text-gray-300 mb-4" aria-hidden="true"></i>
        <p class="text-gray-600 font-semibold">No hay ventas que coincidan con estos filtros.</p>
        <p class="text-sm text-gray-500 mt-1">Prueba con otro rango de fechas, o quita los filtros.</p>
      </div>

      <template v-else>
        <ReporteResumen etiqueta="Total ingresos" :valor="dinero(stats.totalIngresos)" :periodo="textoPeriodo(filtros)" :cambio="cambio" :minis="minis">
          <h3 class="font-bold text-gray-900 mb-3">Ingresos y utilidad {{ serie.granularidad === 'mes' ? 'por mes' : 'por día' }}</h3>
          <ReporteTendencia
            nombre="Ventas"
            :puntos="serie.puntos"
            :granularidad="serie.granularidad"
            :series="[
              { key: 'ingresos', label: 'Ingresos', color: 'var(--viz-2)', area: true },
              { key: 'utilidad', label: 'Utilidad', color: 'var(--viz-1)', area: false },
            ]"
            :extra="(p) => `${p.ventas} ${p.ventas === 1 ? 'venta' : 'ventas'}`"
          />
        </ReporteResumen>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <ReporteInsight icono="fa-solid fa-calendar-day" :titulo="serie.granularidad === 'mes' ? 'Mejor mes' : 'Mejor día'" :principal="mejorPunto.etiqueta" :detalle="mejorPunto.detalle" />
          <ReporteInsight icono="fa-solid fa-trophy" tono="bg-amber-50 text-amber-600" titulo="Producto estrella" :principal="topProductos[0].label" :detalle="`${entero(topProductos[0].unidades)} unidades · ${dinero(topProductos[0].total)}`" />
          <ReporteInsight icono="fa-solid fa-user-check" tono="bg-blue-50 text-blue-500" titulo="Mejor cliente" :principal="porCliente[0].label" :detalle="`${dinero(porCliente[0].total)} · ${porcentaje(porCliente[0].total, stats.totalIngresos)} de los ingresos`" />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">
          <ReporteParticipacion titulo="Ingresos por categoría" icono="fa-solid fa-tags" :filas="filas(porCategoria)" :total="stats.totalIngresos" />
          <ReporteLideres titulo="Clientes" icono="fa-solid fa-user-group" :filas="filas(porCliente)" :total="stats.totalIngresos" />
          <ReporteEnfasis titulo="Ingresos por marca" icono="fa-solid fa-copyright" :filas="filas(porMarca)" :total="stats.totalIngresos" />
        </div>

        <section class="bg-surface rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <header class="flex flex-wrap items-center gap-x-3 gap-y-1 px-4 sm:px-6 pt-5 pb-4">
            <span class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center" aria-hidden="true"><i class="fa-solid fa-trophy text-sm"></i></span>
            <h3 class="font-bold text-gray-900 text-lg">Productos más vendidos</h3>
            <p class="text-sm text-gray-500 sm:ml-auto">Se muestran {{ topProductos.length }} de {{ totalProductos }}<template v-if="totalProductos > topProductos.length"> · el Excel trae todos</template></p>
          </header>

          <!-- Celular: una tarjeta por producto -->
          <ol class="sm:hidden divide-y divide-gray-100 border-t border-gray-100">
            <li v-for="(p, i) in topProductos" :key="'m' + p.label + p.marca" class="px-4 py-3.5">
              <div class="flex items-start gap-3">
                <span :class="['w-6 h-6 mt-0.5 rounded-full inline-flex items-center justify-center text-[11px] font-bold flex-shrink-0', medalla(i)]">{{ i + 1 }}</span>
                <div class="min-w-0 flex-1">
                  <p class="font-semibold text-gray-900 leading-tight">{{ p.label }}</p>
                  <p class="text-xs text-gray-500">{{ p.marca }} · {{ p.categoria }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                  <p class="font-black text-gray-900 tabular-nums">{{ dinero(p.total) }}</p>
                  <p :class="['text-xs font-bold tabular-nums', p.utilidad >= 0 ? 'text-green-600' : 'text-red-500']">{{ p.utilidad >= 0 ? '+' : '' }}{{ dinero(p.utilidad) }}</p>
                </div>
              </div>
              <div class="mt-2.5 h-1.5 bg-gray-100 rounded-full overflow-hidden" role="presentation"><div class="h-full rounded-full" :style="{ width: ancho(p.total) + '%', background: 'var(--viz-2)' }"></div></div>
              <p class="mt-1.5 text-xs text-gray-500 tabular-nums">{{ entero(p.unidades) }} unid. · {{ p.numeroVentas }} venta{{ p.numeroVentas === 1 ? '' : 's' }} · {{ dinero(p.precioPromedio) }} c/u · {{ porcentaje(p.total, stats.totalIngresos, 1) }} de los ingresos</p>
            </li>
          </ol>

          <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                  <th scope="col" class="text-center py-3 px-4 font-semibold w-12">#</th>
                  <th scope="col" class="text-left py-3 px-4 font-semibold">Producto</th>
                  <th scope="col" class="text-left py-3 px-4 font-semibold">Categoría</th>
                  <th scope="col" class="text-center py-3 px-4 font-semibold">Ventas</th>
                  <th scope="col" class="text-center py-3 px-4 font-semibold">Unidades</th>
                  <th scope="col" class="text-right py-3 px-4 font-semibold">Precio prom.</th>
                  <th scope="col" class="text-left py-3 px-4 font-semibold min-w-[240px]">Ingresos</th>
                  <th scope="col" class="text-right py-3 px-4 font-semibold">Utilidad</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(p, i) in topProductos" :key="p.label + p.marca" class="hover:bg-gray-50 transition-colors">
                  <td class="py-3 px-4 text-center"><span :class="['w-6 h-6 rounded-full inline-flex items-center justify-center text-[11px] font-bold', medalla(i)]">{{ i + 1 }}</span></td>
                  <td class="py-3 px-4"><p class="font-semibold text-gray-900">{{ p.label }}</p><p class="text-xs text-gray-500">{{ p.marca }}</p></td>
                  <td class="py-3 px-4 text-gray-600">{{ p.categoria }}</td>
                  <td class="py-3 px-4 text-center text-gray-600 tabular-nums">{{ p.numeroVentas }}</td>
                  <td class="py-3 px-4 text-center font-bold text-gray-900 tabular-nums">{{ entero(p.unidades) }}</td>
                  <td class="py-3 px-4 text-right text-gray-600 tabular-nums">{{ dinero(p.precioPromedio) }}</td>
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-3">
                      <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden" role="presentation"><div class="h-full rounded-full" :style="{ width: ancho(p.total) + '%', background: 'var(--viz-2)' }"></div></div>
                      <span class="w-28 text-right font-bold text-gray-900 tabular-nums">{{ dinero(p.total) }}</span>
                      <span class="w-12 text-right text-xs text-gray-500 tabular-nums">{{ porcentaje(p.total, stats.totalIngresos, 1) }}</span>
                    </div>
                  </td>
                  <td :class="['py-3 px-4 text-right font-bold tabular-nums', p.utilidad >= 0 ? 'text-green-600' : 'text-red-500']">{{ dinero(p.utilidad) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ReporteToolbar from '@/Components/Admin/Reporte/ReporteToolbar.vue';
import ReporteResumen from '@/Components/Admin/Reporte/ReporteResumen.vue';
import ReporteTendencia from '@/Components/Admin/Reporte/ReporteTendencia.vue';
import ReporteInsight from '@/Components/Admin/Reporte/ReporteInsight.vue';
import ReporteParticipacion from '@/Components/Admin/Reporte/ReporteParticipacion.vue';
import ReporteLideres from '@/Components/Admin/Reporte/ReporteLideres.vue';
import ReporteEnfasis from '@/Components/Admin/Reporte/ReporteEnfasis.vue';
import { dinero, entero, porcentaje, textoPeriodo, rangoCorto, variacion } from '@/Components/Admin/Reporte/format';

const props = defineProps({
  filtros: Object,
  clientes: Array,
  categorias: Array,
  stats: Object,
  serie: Object,
  anterior: Object,
  porCategoria: Array,
  porCliente: Array,
  porMarca: Array,
  topProductos: Array,
  totalProductos: Number,
});

const listas = computed(() => [
  { clave: 'cliente', etiqueta: 'Cliente', todas: 'Todos los clientes', opciones: props.clientes },
  { clave: 'categoria', etiqueta: 'Categoría', todas: 'Todas las categorías', opciones: props.categorias },
]);

// Contra el período anterior de la misma duración: vender más es bueno.
const cambio = computed(() => props.anterior && {
  pct: variacion(props.stats.totalIngresos, props.anterior.medidas.ingresos),
  rango: rangoCorto(props.anterior.desde, props.anterior.hasta),
  sustantivo: 'ventas',
  subirEsBueno: true,
});

const minis = computed(() => [
  { etiqueta: 'Utilidad', valor: dinero(props.stats.utilidadTotal), nota: `Margen del ${porcentaje(props.stats.utilidadTotal, props.stats.totalIngresos, 1)}`, icono: 'fa-solid fa-arrow-trend-up', tono: 'text-green-600' },
  { etiqueta: 'Ventas', valor: entero(props.stats.numeroVentas), nota: 'Sin canceladas', icono: 'fa-solid fa-receipt', tono: 'text-blue-500' },
  { etiqueta: 'Prendas vendidas', valor: entero(props.stats.totalUnidades), nota: 'Netas de devoluciones', icono: 'fa-solid fa-shirt', tono: 'text-amber-600' },
  { etiqueta: 'Promedio por venta', valor: dinero(props.stats.promedioPorVenta), icono: 'fa-solid fa-scale-balanced', tono: 'text-purple-500' },
]);

const mejorPunto = computed(() => {
  const m = props.serie.puntos.reduce((a, p) => (p.ingresos > (a?.ingresos ?? -1) ? p : a), null);
  if (!m) return { etiqueta: '—', detalle: '' };
  const [y, mo, d] = m.clave.split('-').map(Number);
  const f = new Date(y, mo - 1, d || 1);
  const etiqueta = props.serie.granularidad === 'mes'
    ? f.toLocaleDateString('es-PE', { month: 'long', year: 'numeric' })
    : f.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long' });

  return { etiqueta: etiqueta.charAt(0).toUpperCase() + etiqueta.slice(1), detalle: `${dinero(m.ingresos)} · ${m.ventas} ${m.ventas === 1 ? 'venta' : 'ventas'}` };
});

const filas = (lista) => lista.map((r) => ({ label: r.label, total: r.total, detalle: `${r.ventas} venta${r.ventas === 1 ? '' : 's'} · ${entero(r.unidades)} unid. · utilidad ${dinero(r.utilidad)}` }));

const maximo = computed(() => Math.max(0, ...props.topProductos.map((p) => p.total)));
const ancho = (v) => (maximo.value > 0 ? Math.max(2, (v / maximo.value) * 100) : 0);
const medalla = (i) => ['bg-amber-100 text-amber-700', 'bg-gray-200 text-gray-600', 'bg-orange-100 text-orange-700'][i] ?? 'bg-gray-100 text-gray-500';
</script>
