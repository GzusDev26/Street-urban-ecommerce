<template>
  <Head title="Reportes de Compras" />
  <AdminLayout>
    <template #breadcrumb>Compras / Reportes</template>
    <template #header>Reportes de Compras</template>

    <div class="viz space-y-6">
      <nav class="flex gap-2 border-b border-gray-200" aria-label="Secciones de compras">
        <Link :href="route('admin.purchases.index')" class="px-4 py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-700 border-b-2 border-transparent">Inventario</Link>
        <Link :href="route('admin.purchases.historial')" class="px-4 py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-700 border-b-2 border-transparent">Historial de Compras</Link>
        <Link :href="route('admin.purchases.reportes')" aria-current="page" class="px-4 py-2.5 text-sm font-semibold text-[#e67e2d] border-b-2 border-[#ff8c42]">Reportes</Link>
      </nav>

      <ReporteToolbar :filtros="filtros" :listas="listas" ruta-reporte="admin.purchases.reportes" ruta-excel="admin.purchases.reportes.exportar" />

      <div v-if="stats.numeroCompras === 0" class="bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 p-16 text-center">
        <i class="fa-solid fa-chart-pie text-5xl text-gray-300 mb-4" aria-hidden="true"></i>
        <p class="text-gray-600 font-semibold">No hay compras que coincidan con estos filtros.</p>
        <p class="text-sm text-gray-500 mt-1">Prueba con otro rango de fechas, o quita los filtros.</p>
      </div>

      <template v-else>
        <ReporteResumen etiqueta="Total invertido" :valor="dinero(stats.totalInvertido)" :periodo="textoPeriodo(filtros)" :cambio="cambio" :minis="minis">
          <div class="flex items-baseline justify-between gap-3 mb-3">
            <h3 class="font-bold text-gray-900">Inversión {{ serie.granularidad === 'mes' ? 'por mes' : 'por día' }}</h3>
          </div>
          <ReporteTendencia
            nombre="Inversión"
            :puntos="serie.puntos"
            :granularidad="serie.granularidad"
            :series="[{ key: 'total', label: 'Inversión', color: 'var(--viz-2)', area: true }]"
            :extra="(p) => `${p.compras} ${p.compras === 1 ? 'compra' : 'compras'}`"
          />
        </ReporteResumen>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <ReporteInsight icono="fa-solid fa-calendar-day" :titulo="serie.granularidad === 'mes' ? 'Mes de mayor inversión' : 'Día de mayor inversión'" :principal="mejorPunto.etiqueta" :detalle="mejorPunto.detalle" />
          <ReporteInsight icono="fa-solid fa-trophy" tono="bg-amber-50 text-amber-600" titulo="Producto más comprado" :principal="topProductos[0].label" :detalle="`${entero(topProductos[0].unidades)} unidades · ${dinero(topProductos[0].total)}`" />
          <ReporteInsight icono="fa-solid fa-truck-fast" tono="bg-blue-50 text-blue-500" titulo="Proveedor principal" :principal="porProveedor[0].label" :detalle="`${dinero(porProveedor[0].total)} · ${porcentaje(porProveedor[0].total, stats.totalInvertido)} de lo invertido`" />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">
          <ReporteParticipacion titulo="Inversión por categoría" icono="fa-solid fa-tags" :filas="filas(porCategoria)" :total="stats.totalInvertido" />
          <ReporteLideres titulo="Proveedores" icono="fa-solid fa-truck-fast" :filas="filas(porProveedor)" :total="stats.totalInvertido" />
          <ReporteEnfasis titulo="Inversión por marca" icono="fa-solid fa-copyright" :filas="filas(porMarca)" :total="stats.totalInvertido" />
        </div>

        <section class="bg-surface rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <header class="flex flex-wrap items-center gap-x-3 gap-y-1 px-6 pt-5 pb-4">
            <span class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center" aria-hidden="true"><i class="fa-solid fa-trophy text-sm"></i></span>
            <h3 class="font-bold text-gray-900 text-lg">Productos más comprados</h3>
            <p class="text-sm text-gray-500 sm:ml-auto">Se muestran {{ topProductos.length }} de {{ totalProductos }}<template v-if="totalProductos > topProductos.length"> · el Excel trae todos</template></p>
          </header>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                  <th scope="col" class="text-center py-3 px-4 font-semibold w-12">#</th>
                  <th scope="col" class="text-left py-3 px-4 font-semibold">Producto</th>
                  <th scope="col" class="text-left py-3 px-4 font-semibold">Categoría</th>
                  <th scope="col" class="text-center py-3 px-4 font-semibold">Compras</th>
                  <th scope="col" class="text-center py-3 px-4 font-semibold">Unidades</th>
                  <th scope="col" class="text-right py-3 px-4 font-semibold">Costo prom.</th>
                  <th scope="col" class="text-left py-3 px-4 font-semibold min-w-[240px]">Total invertido</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(p, i) in topProductos" :key="p.label + p.marca" class="hover:bg-gray-50 transition-colors">
                  <td class="py-3 px-4 text-center"><span :class="['w-6 h-6 rounded-full inline-flex items-center justify-center text-[11px] font-bold', medalla(i)]">{{ i + 1 }}</span></td>
                  <td class="py-3 px-4"><p class="font-semibold text-gray-900">{{ p.label }}</p><p class="text-xs text-gray-500">{{ p.marca }}</p></td>
                  <td class="py-3 px-4 text-gray-600">{{ p.categoria }}</td>
                  <td class="py-3 px-4 text-center text-gray-600 tabular-nums">{{ p.numeroCompras }}</td>
                  <td class="py-3 px-4 text-center font-bold text-gray-900 tabular-nums">{{ entero(p.unidades) }}</td>
                  <td class="py-3 px-4 text-right text-gray-600 tabular-nums">{{ dinero(p.costoPromedio) }}</td>
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-3">
                      <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden" role="presentation"><div class="h-full rounded-full" :style="{ width: ancho(p.total) + '%', background: 'var(--viz-2)' }"></div></div>
                      <span class="w-28 text-right font-bold text-gray-900 tabular-nums">{{ dinero(p.total) }}</span>
                      <span class="w-12 text-right text-xs text-gray-500 tabular-nums">{{ porcentaje(p.total, stats.totalInvertido, 1) }}</span>
                    </div>
                  </td>
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
  proveedores: Array,
  categorias: Array,
  stats: Object,
  serie: Object,
  anterior: Object,
  porCategoria: Array,
  porProveedor: Array,
  porMarca: Array,
  topProductos: Array,
  totalProductos: Number,
});

const listas = computed(() => [
  { clave: 'proveedor', etiqueta: 'Proveedor', todas: 'Todos los proveedores', opciones: props.proveedores },
  { clave: 'categoria', etiqueta: 'Categoría', todas: 'Todas las categorías', opciones: props.categorias },
]);

// Contra el período anterior de la misma duración: invertir más no es "bueno" ni "malo", así que el cambio va neutral.
const cambio = computed(() => props.anterior && {
  pct: variacion(props.stats.totalInvertido, props.anterior.medidas.total),
  rango: rangoCorto(props.anterior.desde, props.anterior.hasta),
  sustantivo: 'compras',
  neutro: true,
});

const minis = computed(() => [
  { etiqueta: 'Compras', valor: entero(props.stats.numeroCompras), icono: 'fa-solid fa-cart-shopping', tono: 'text-blue-500' },
  { etiqueta: 'Unidades', valor: entero(props.stats.totalUnidades), icono: 'fa-solid fa-boxes-stacked', tono: 'text-green-600' },
  { etiqueta: 'Promedio por compra', valor: dinero(props.stats.promedioPorCompra), icono: 'fa-solid fa-scale-balanced', tono: 'text-purple-500' },
  { etiqueta: 'Costo por unidad', valor: dinero(props.stats.totalUnidades > 0 ? props.stats.totalInvertido / props.stats.totalUnidades : 0), icono: 'fa-solid fa-tag', tono: 'text-amber-600' },
]);

const mejorPunto = computed(() => {
  const m = props.serie.puntos.reduce((a, p) => (p.total > (a?.total ?? -1) ? p : a), null);
  if (!m) return { etiqueta: '—', detalle: '' };
  const [y, mo, d] = m.clave.split('-').map(Number);
  const f = new Date(y, mo - 1, d || 1);
  const etiqueta = props.serie.granularidad === 'mes'
    ? f.toLocaleDateString('es-PE', { month: 'long', year: 'numeric' })
    : f.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long' });

  return { etiqueta: etiqueta.charAt(0).toUpperCase() + etiqueta.slice(1), detalle: `${dinero(m.total)} · ${m.compras} ${m.compras === 1 ? 'compra' : 'compras'}` };
});

const filas = (lista) => lista.map((r) => ({ label: r.label, total: r.total, detalle: `${r.compras} compra${r.compras === 1 ? '' : 's'} · ${entero(r.unidades)} unid.` }));

const maximo = computed(() => Math.max(0, ...props.topProductos.map((p) => p.total)));
const ancho = (v) => (maximo.value > 0 ? Math.max(2, (v / maximo.value) * 100) : 0);
const medalla = (i) => ['bg-amber-100 text-amber-700', 'bg-gray-200 text-gray-600', 'bg-orange-100 text-orange-700'][i] ?? 'bg-gray-100 text-gray-500';
</script>
