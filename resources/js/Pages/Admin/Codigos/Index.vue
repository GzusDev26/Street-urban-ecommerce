<template>
  <Head title="Códigos y etiquetas" />
  <AdminLayout>
    <template #breadcrumb>Compras</template>
    <template #header>Códigos y etiquetas</template>

    <div class="space-y-6 pb-32">
      <div v-if="page.props.flash?.success" role="status" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ page.props.flash.success }}
      </div>

      <!-- Resumen -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="r in resumen" :key="r.label" class="bg-surface rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
          <span :class="['w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0', r.tono]" aria-hidden="true"><i :class="r.icon"></i></span>
          <div class="min-w-0">
            <p class="text-2xl font-black text-gray-900 leading-none tabular-nums">{{ r.valor }}</p>
            <p class="text-xs font-medium text-gray-500 mt-1 truncate">{{ r.label }}</p>
          </div>
        </div>
      </div>

      <!-- Cómo funciona (en el celular se pliega para no empujar la lista hacia abajo) -->
      <button type="button" class="md:hidden -mb-3 self-start flex items-center gap-2 h-11 text-sm font-semibold text-[#e67e2d]" :aria-expanded="verPasos" @click="verPasos = !verPasos">
        <i :class="['fa-solid text-xs', verPasos ? 'fa-chevron-up' : 'fa-circle-question']" aria-hidden="true"></i> {{ verPasos ? 'Ocultar' : '¿Cómo funciona?' }}
      </button>
      <ol :class="[verPasos ? 'grid' : 'hidden', 'md:grid grid-cols-1 md:grid-cols-3 gap-3']">
        <li v-for="(p, i) in PASOS" :key="p.titulo" class="flex items-start gap-3 rounded-2xl bg-gray-50 border border-gray-100 p-4">
          <span class="w-7 h-7 rounded-full bg-[#ff8c42] text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5" aria-hidden="true">{{ i + 1 }}</span>
          <div>
            <p class="text-sm font-bold text-gray-900">{{ p.titulo }}</p>
            <p class="text-sm text-gray-500 leading-snug mt-0.5">{{ p.texto }}</p>
          </div>
        </li>
      </ol>

      <!-- Filtros -->
      <div class="flex flex-col lg:flex-row lg:items-center gap-3">
        <div class="flex-1 min-w-0">
          <SearchInput v-model="busqueda" placeholder="Buscar por modelo, marca, color, talla o código..." />
        </div>
        <div class="overflow-x-auto -mx-1 px-1"><TabFilter v-model="filtro" :tabs="pestanas" /></div>
      </div>

      <!-- Acciones masivas -->
      <div class="space-y-2.5">
        <p class="text-sm font-semibold text-gray-900">{{ paginacion.total }} {{ paginacion.total === 1 ? 'prenda' : 'prendas' }} <span class="font-normal text-gray-500">· esta página: {{ grupos.length }} {{ grupos.length === 1 ? 'modelo' : 'modelos' }}</span></p>
        <div class="flex gap-2 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 pb-1 sm:flex-wrap sm:overflow-visible" role="group" aria-label="Elegir cuántas etiquetas imprimir">
          <button type="button" class="h-11 sm:h-9 px-3.5 sm:px-3 flex-shrink-0 rounded-xl sm:rounded-lg text-sm sm:text-xs font-semibold whitespace-nowrap text-[#e67e2d] bg-orange-50 hover:bg-orange-100 transition-colors" @click="poner(1)">1 a cada una con stock (esta página)</button>
          <button type="button" class="h-11 sm:h-9 px-3.5 sm:px-3 flex-shrink-0 rounded-xl sm:rounded-lg text-sm sm:text-xs font-semibold whitespace-nowrap text-[#e67e2d] bg-orange-50 hover:bg-orange-100 transition-colors disabled:opacity-60" :disabled="cargandoStock" @click="ponerSegunStock"><i v-if="cargandoStock" class="fa-solid fa-spinner fa-spin mr-1" aria-hidden="true"></i>Una por unidad en stock (todo el filtro)</button>
          <button type="button" class="h-11 sm:h-9 px-3.5 sm:px-3 flex-shrink-0 rounded-xl sm:rounded-lg text-sm sm:text-xs font-semibold whitespace-nowrap text-gray-600 bg-gray-100 sm:bg-transparent hover:bg-gray-200 transition-colors" @click="quitarTodas">Quitar todas</button>
        </div>
      </div>

      <!-- Modelos -->
      <div v-if="grupos.length" class="grid grid-cols-1 min-[1500px]:grid-cols-2 gap-4 items-start">
        <section v-for="g in grupos" :key="g.clave" class="bg-surface rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <header class="flex items-center gap-3 px-4 sm:px-5 py-3.5 sm:py-4 bg-gray-50/70 border-b border-gray-100">
            <span class="w-10 h-10 rounded-xl bg-orange-50 text-[#ff8c42] flex items-center justify-center flex-shrink-0" aria-hidden="true"><i class="fa-solid fa-shirt"></i></span>
            <div class="min-w-0 flex-1">
              <h3 class="font-bold text-gray-900 leading-tight break-words sm:truncate">{{ g.producto }}</h3>
              <p class="text-xs text-gray-500 leading-snug sm:truncate">{{ g.marca }} · {{ g.categoria }} · {{ g.items.length }} {{ g.items.length === 1 ? 'variante' : 'variantes' }} · {{ g.stock }} en stock</p>
            </div>
            <button type="button" class="h-10 sm:h-9 px-3 rounded-lg text-xs font-semibold text-[#e67e2d] bg-orange-50 sm:bg-transparent sm:text-gray-600 hover:text-[#e67e2d] hover:bg-orange-50 transition-colors whitespace-nowrap flex-shrink-0" @click="etiquetarModelo(g)">Etiquetar<span class="hidden sm:inline"> modelo</span></button>
          </header>

          <ul class="divide-y divide-gray-100">
            <li
              v-for="v in g.items"
              :key="v.id"
              class="px-4 sm:px-5 py-3 grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1.5 sm:gap-x-4 sm:grid-cols-[minmax(0,1.2fr)_3.5rem_5.5rem_minmax(0,1.6fr)_auto]"
            >
              <!-- Color y talla -->
              <div class="col-start-1 row-start-1 flex items-center gap-2.5 min-w-0 sm:contents">
                <div class="flex items-center gap-2.5 min-w-0">
                  <ColorSwatch :hex="v.colorHex" class="w-5 h-5 rounded-full border border-gray-200 flex-shrink-0" />
                  <span class="text-sm font-semibold text-gray-900 truncate">{{ v.color }}</span>
                </div>
                <span class="inline-flex items-center justify-center min-w-[2.75rem] h-7 px-2 rounded-lg bg-gray-100 text-xs font-bold text-gray-700 flex-shrink-0 sm:justify-self-start">{{ v.talla }}</span>
              </div>

              <span :class="['hidden sm:block text-xs font-semibold tabular-nums', v.stock > 0 ? 'text-gray-600' : 'text-gray-400']">{{ v.stock > 0 ? v.stock + ' en stock' : 'Sin stock' }}</span>

              <!-- Código, origen, stock (solo móvil) y cambiar -->
              <div class="col-span-2 row-start-2 flex items-center gap-2 min-w-0 sm:col-span-1 sm:row-start-auto">
                <code class="px-2.5 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-xs font-bold text-gray-800 tracking-wider truncate">{{ v.codigo }}</code>
                <span :class="['text-[11px] font-semibold rounded-full px-2 py-0.5 whitespace-nowrap', v.propio ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700']">
                  {{ v.propio ? 'Propio' : 'De fábrica' }}
                </span>
                <span :class="['sm:hidden flex-1 min-w-0 truncate text-xs font-semibold tabular-nums', v.stock > 0 ? 'text-gray-500' : 'text-gray-400']">{{ v.stock > 0 ? v.stock + ' en stock' : 'Sin stock' }}</span>
                <button
                  type="button"
                  class="w-11 h-11 -my-1.5 sm:my-0 sm:w-9 sm:h-9 rounded-lg text-gray-400 hover:text-[#e67e2d] hover:bg-orange-50 flex items-center justify-center flex-shrink-0 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
                  :aria-label="`Cambiar código de ${v.producto} ${v.color} talla ${v.talla}`"
                  title="Cambiar código"
                  @click="editando = v"
                >
                  <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i>
                </button>
              </div>

              <!-- Cuántas etiquetas -->
              <div class="col-start-2 row-start-1 sm:col-auto sm:row-auto inline-flex items-center rounded-xl border border-gray-200 bg-surface overflow-hidden" role="group" :aria-label="`Etiquetas de ${v.producto} ${v.color} talla ${v.talla}`">
                <button type="button" class="w-11 h-11 sm:w-10 sm:h-10 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed focus:outline-none focus-visible:bg-gray-100" aria-label="Una etiqueta menos" :disabled="!(copias[v.id] > 0)" @click="copias[v.id] = limitar((copias[v.id] || 0) - 1)">
                  <i class="fa-solid fa-minus text-[10px]" aria-hidden="true"></i>
                </button>
                <input
                  :value="copias[v.id] ?? 0"
                  type="number"
                  min="0"
                  max="99"
                  inputmode="numeric"
                  class="w-12 h-11 sm:h-10 border-0 border-x border-gray-200 bg-transparent text-center text-base sm:text-sm font-bold focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                  :class="copias[v.id] > 0 ? 'text-[#e67e2d]' : 'text-gray-400'"
                  :disabled="v.stock <= 0"
                  @input="copias[v.id] = limitar($event.target.value, v)"
                >
                <button type="button" class="w-11 h-11 sm:w-10 sm:h-10 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed focus:outline-none focus-visible:bg-gray-100" aria-label="Una etiqueta más" :disabled="(copias[v.id] || 0) >= tope(v)" :title="v.stock > 0 ? undefined : 'Sin stock: no hay prenda para etiquetar'" @click="copias[v.id] = limitar((copias[v.id] || 0) + 1, v)">
                  <i class="fa-solid fa-plus text-[10px]" aria-hidden="true"></i>
                </button>
              </div>
            </li>
          </ul>
        </section>
      </div>

      <Pagination v-if="grupos.length" v-model="paginaActual" :total-items="paginacion.total" :per-page="paginacion.porPagina" />

      <EmptyState v-if="!grupos.length" icon="fa-solid fa-barcode" title="No hay prendas que coincidan" text="Prueba con otro modelo, color, talla o código, o cambia el filtro." />
    </div>

    <!-- Barra de impresión: una sola línea también en el celular -->
    <div class="fixed bottom-[max(0.75rem,env(safe-area-inset-bottom))] inset-x-3 sm:inset-x-4 lg:left-auto lg:right-8 z-40 lg:w-[min(640px,calc(100vw-22rem))]" role="region" aria-label="Imprimir etiquetas">
      <div class="flex items-center gap-2 sm:gap-3 rounded-2xl bg-fg text-canvas pl-4 pr-2 py-2 shadow-2xl shadow-black/30">
        <p class="flex-1 min-w-0 text-sm font-medium whitespace-nowrap">
          <i class="fa-solid fa-tags text-[#ff8c42] mr-1.5" aria-hidden="true"></i><b>{{ totalEtiquetas }}</b><span class="hidden min-[420px]:inline"> {{ totalEtiquetas === 1 ? 'etiqueta' : 'etiquetas' }}</span><span class="sr-only min-[420px]:hidden"> etiquetas</span>
        </p>
        <label class="flex-shrink-0">
          <span class="sr-only">Tamaño de la etiqueta</span>
          <select v-model="tamanoId" class="h-11 rounded-xl border-0 bg-canvas/10 text-canvas text-sm font-semibold pl-3 pr-8 focus:ring-2 focus:ring-[#ff8c42]">
            <option v-for="t in TAMANOS" :key="t.id" :value="t.id" class="text-gray-900">{{ t.label }}</option>
          </select>
        </label>
        <button
          type="button"
          :aria-pressed="conPrecio"
          :title="conPrecio ? 'La etiqueta lleva el precio' : 'La etiqueta no lleva el precio'"
          :class="['h-11 min-w-[2.75rem] px-3 flex-shrink-0 rounded-xl text-sm font-bold flex items-center justify-center gap-1.5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]', conPrecio ? 'bg-[#ff8c42]/20 text-[#ff8c42]' : 'bg-canvas/10 text-canvas/60']"
          @click="conPrecio = !conPrecio"
        >
          S/<span class="hidden sm:inline text-xs font-semibold"> Precio</span><span class="sr-only">: {{ conPrecio ? 'incluido' : 'sin precio' }}</span>
        </button>
        <button
          type="button"
          :disabled="totalEtiquetas === 0"
          class="h-11 px-4 flex-shrink-0 rounded-xl bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white text-sm font-bold flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-canvas"
          @click="imprimir"
        >
          <i class="fa-solid fa-print text-xs" aria-hidden="true"></i> Imprimir
        </button>
      </div>
    </div>

    <!-- Cambiar código -->
    <ModalShell
      v-if="editando"
      title="Cambiar código"
      :subtitle="`${editando.producto} · ${editando.color} · Talla ${editando.talla}`"
      icon="fa-solid fa-barcode"
      submit-text="Guardar código"
      :saving="guardando"
      :error="errorCodigo"
      @close="cerrarEdicion"
      @submit="guardarCodigo"
    >
      <FormField label="Código de barras" hint="Escanea con la pistola o la cámara, o escríbelo. Déjalo vacío para volver al código propio.">
        <div class="flex gap-2">
          <div ref="campoCodigo" class="flex-1 min-w-0">
            <FormInput v-model="codigoNuevo" type="text" icon="fa-solid fa-barcode" maxlength="64" autocomplete="off" placeholder="7501234567890" />
          </div>
          <button type="button" class="h-11 px-4 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:border-[#ff8c42] hover:text-[#e67e2d] flex items-center gap-2 flex-shrink-0" @click="camaraAbierta = true">
            <i class="fa-solid fa-camera" aria-hidden="true"></i> Cámara
          </button>
        </div>
      </FormField>
    </ModalShell>

    <CamaraScanner v-if="camaraAbierta" @codigo="(c) => (codigoNuevo = c)" @close="camaraAbierta = false" />

    <!-- Hoja de impresión: solo se ve al imprimir -->
    <component :is="'style'">{{ estiloImpresion }}</component>
    <div id="hoja-etiquetas" aria-hidden="true">
      <div v-for="(e, i) in etiquetasAImprimir" :key="i" class="etiqueta">
        <p class="et-nombre">{{ e.producto }}</p>
        <p class="et-detalle">{{ e.marca }} · {{ e.color }} · T. {{ e.talla }}</p>
        <BarcodeSvg :valor="e.codigo" :alto="tamano.barras" :ancho="1.4" class="et-barras" />
        <p class="et-codigo">{{ e.codigo }}<span v-if="conPrecio" class="et-precio"> · S/ {{ e.precio.toFixed(2) }}</span></p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, defineAsyncComponent, nextTick, reactive, ref, watch } from 'vue';
import axios from 'axios';
import Pagination from '@/Components/Admin/Pagination.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BarcodeSvg from '@/Components/Admin/BarcodeSvg.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import TabFilter from '@/Components/Admin/TabFilter.vue';

const CamaraScanner = defineAsyncComponent(() => import('@/Components/Admin/CamaraScanner.vue')); // carga la librería de la cámara solo al abrirla

const props = defineProps({
  variantes: { type: Array, default: () => [] }, // solo las de la página actual
  paginacion: { type: Object, required: true }, // { pagina, total, porPagina }
  filtros: { type: Object, required: true }, // { buscar, filtro }
  conteos: { type: Object, required: true }, // { todas, propio, fabrica }
});

const page = usePage();

// --- Lista y filtros: se resuelven en el servidor, cada cambio pide solo esa página ----------------
const pestanas = computed(() => [
  { value: 'todas', label: 'Todas', count: props.conteos.todas },
  { value: 'propio', label: 'Código propio', count: props.conteos.propio },
  { value: 'fabrica', label: 'De fábrica', count: props.conteos.fabrica },
]);
const busqueda = ref(props.filtros.buscar);
const filtro = ref(props.filtros.filtro);
const verPasos = ref(false); // los 3 pasos se pliegan en el celular

const ir = (extra = {}) => {
  router.get(route('admin.codigos.index'), {
    buscar: busqueda.value.trim() || undefined,
    filtro: filtro.value !== 'todas' ? filtro.value : undefined,
    ...extra,
  }, { preserveState: true, preserveScroll: true, replace: true });
};

let temporizador = null;
watch(busqueda, () => {
  clearTimeout(temporizador);
  temporizador = setTimeout(() => ir(), 300);
});
watch(filtro, () => ir());

const paginaActual = computed({
  get: () => props.paginacion.pagina,
  set: (p) => ir({ pagina: p }),
});

const visibles = computed(() => props.variantes);

// Resumen de arriba, pasos y agrupación por modelo.
const PASOS = [
  { titulo: 'Asigna el código', texto: 'Si la prenda trae el de fábrica, escanéalo con «Cambiar código». Si no, ya tiene uno propio (empieza con SU).' },
  { titulo: 'Imprime la etiqueta', texto: 'Elige cuántas de cada prenda, el tamaño y pégala en la prenda.' },
  { titulo: 'Escanea al vender', texto: 'En Registrar venta, con la pistola o la cámara, la prenda se agrega sola al ticket.' },
];

const resumen = computed(() => [
  { label: 'Prendas', valor: props.conteos.todas, icon: 'fa-solid fa-shirt', tono: 'bg-orange-50 text-[#ff8c42]' },
  { label: 'Con código propio', valor: props.conteos.propio, icon: 'fa-solid fa-hashtag', tono: 'bg-amber-50 text-amber-600' },
  { label: 'Con código de fábrica', valor: props.conteos.fabrica, icon: 'fa-solid fa-barcode', tono: 'bg-green-50 text-green-600' },
  { label: 'Etiquetas por imprimir', valor: totalEtiquetas.value, icon: 'fa-solid fa-tags', tono: 'bg-blue-50 text-blue-500' },
]);

const grupos = computed(() => {
  const mapa = new Map();
  for (const v of visibles.value) {
    const clave = `${v.producto}|${v.marca}`;
    if (!mapa.has(clave)) mapa.set(clave, { clave, producto: v.producto, marca: v.marca, categoria: v.categoria, items: [], stock: 0 });
    const g = mapa.get(clave);
    g.items.push(v);
    g.stock += v.stock;
  }
  return [...mapa.values()];
});

// Solo las prendas con stock: una etiqueta se pega en una prenda real. Las agotadas se etiquetan al volver a comprarlas
// (y si hiciera falta antes, se puede subir su contador a mano).
const etiquetarModelo = (g) => g.items.filter((v) => v.stock > 0).forEach((v) => { copias[v.id] = Math.max(1, copias[v.id] || 0); });

// --- Cuántas etiquetas imprimir de cada prenda ----------------------------------------------------
// La selección sobrevive al cambiar de página: se guardan los datos de cada prenda elegida.
const copias = reactive({});
const datos = {};
// Sin stock no hay prenda a la que pegarle la etiqueta: el tope de esas prendas es 0.
const tope = (v) => (v.stock > 0 ? 99 : 0);
const limitar = (valor, v = null) => Math.min(v ? tope(v) : 99, Math.max(0, parseInt(valor, 10) || 0));

watch(() => props.variantes, (lista) => lista.forEach((v) => { datos[v.id] = v; }), { immediate: true });

const poner = (n) => visibles.value.filter((v) => n === 0 || v.stock > 0).forEach((v) => { copias[v.id] = n; });
const quitarTodas = () => Object.keys(copias).forEach((id) => { copias[id] = 0; });

// «Una por unidad en stock» abarca todas las prendas del filtro, no solo la página que se ve.
const cargandoStock = ref(false);
const ponerSegunStock = async () => {
  cargandoStock.value = true;
  try {
    const { data } = await axios.get(route('admin.codigos.etiquetas'), { params: { buscar: busqueda.value.trim() || undefined, filtro: filtro.value !== 'todas' ? filtro.value : undefined } });
    data.forEach((v) => { datos[v.id] = v; copias[v.id] = limitar(v.stock); });
  } finally {
    cargandoStock.value = false;
  }
};

const etiquetasAImprimir = computed(() =>
  Object.entries(copias).flatMap(([id, n]) => Array.from({ length: n || 0 }, () => datos[id]).filter(Boolean)),
);
const totalEtiquetas = computed(() => etiquetasAImprimir.value.length);

// --- Impresión --------------------------------------------------------------------------------------
// El tamaño se aplica con @page; la impresora térmica lo toma del controlador al elegirla en el diálogo de impresión.
const TAMANOS = [
  { id: '50x30', label: '50 × 30 mm', w: 50, h: 30, barras: 34 },
  { id: '40x25', label: '40 × 25 mm', w: 40, h: 25, barras: 26 },
  { id: '60x40', label: '60 × 40 mm', w: 60, h: 40, barras: 46 },
];
const tamanoId = ref('50x30');
const conPrecio = ref(true);
const tamano = computed(() => TAMANOS.find((t) => t.id === tamanoId.value));

const estiloImpresion = computed(() => `
#hoja-etiquetas { display: none; }
@media print {
  @page { size: ${tamano.value.w}mm ${tamano.value.h}mm; margin: 0; }
  body * { visibility: hidden; }
  #hoja-etiquetas, #hoja-etiquetas * { visibility: visible; }
  #hoja-etiquetas { display: block; position: absolute; left: 0; top: 0; }
  .etiqueta { width: ${tamano.value.w}mm; height: ${tamano.value.h}mm; box-sizing: border-box; padding: 1.5mm 2mm; overflow: hidden;
    page-break-after: always; break-after: page; color: #000; background: #fff; text-align: center; font-family: Arial, sans-serif; }
  .etiqueta:last-child { page-break-after: auto; break-after: auto; }
  .et-nombre { margin: 0; font-size: 8pt; font-weight: 700; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .et-detalle { margin: 0 0 1mm; font-size: 6.5pt; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .et-barras { display: block; margin: 0 auto; max-width: 100%; }
  .et-codigo { margin: 0.5mm 0 0; font-size: 7pt; letter-spacing: 0.4px; }
  .et-precio { font-weight: 700; }
}`);

const imprimir = async () => {
  if (totalEtiquetas.value === 0) return;
  await nextTick(); // asegura que los códigos ya están dibujados
  window.print();
};

// --- Cambiar el código de una prenda ----------------------------------------------------------------
const editando = ref(null);
const codigoNuevo = ref('');
const guardando = ref(false);
const errorCodigo = ref('');
const camaraAbierta = ref(false);
const campoCodigo = ref(null);

watch(editando, async (v) => {
  codigoNuevo.value = v && !v.propio ? v.codigo : '';
  errorCodigo.value = '';
  if (v) {
    await nextTick();
    campoCodigo.value?.querySelector('input')?.focus();
  }
});

const cerrarEdicion = () => { editando.value = null; };

const guardarCodigo = () => {
  guardando.value = true;
  errorCodigo.value = '';

  router.patch(route('admin.codigos.update', editando.value.id), { codigo: codigoNuevo.value }, {
    preserveScroll: true,
    onSuccess: cerrarEdicion,
    onError: (e) => { errorCodigo.value = e.codigo || Object.values(e)[0] || 'No se pudo guardar el código.'; },
    onFinish: () => { guardando.value = false; },
  });
};
</script>
