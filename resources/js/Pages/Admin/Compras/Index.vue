<template>
  <Head title="Compras" />
  <AdminLayout>
    <template #breadcrumb>Compras</template>
    <template #header>Compras</template>

    <div class="space-y-5 sm:space-y-6">
      <!-- Tabs (en el celular se deslizan hacia el lado si no caben) -->
      <div class="flex gap-2 border-b border-gray-200 overflow-x-auto whitespace-nowrap -mx-4 px-4 sm:mx-0 sm:px-0">
        <Link
          :href="route('admin.purchases.index')"
          class="px-4 py-2.5 text-sm font-semibold text-[#ff8c42] border-b-2 border-[#ff8c42]"
        >
          Inventario
        </Link>
        <Link
          :href="route('admin.purchases.historial')"
          class="px-4 py-2.5 text-sm font-semibold text-gray-400 hover:text-gray-600 border-b-2 border-transparent"
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

      <!-- FLASH SUCCESS -->
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-5">
        <div class="bg-surface rounded-2xl border border-gray-100 px-3.5 py-3 sm:p-6 shadow-sm">
          <p class="text-xs sm:text-sm text-gray-500 font-medium">Productos</p>
          <p class="text-xl sm:text-3xl font-black text-gray-900 mt-1 tabular-nums">{{ stats.totalProductos }}</p>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 px-3.5 py-3 sm:p-6 shadow-sm">
          <p class="text-xs sm:text-sm text-gray-500 font-medium">Variantes</p>
          <p class="text-xl sm:text-3xl font-black text-gray-900 mt-1 tabular-nums">{{ stats.totalVariantes }}</p>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 px-3.5 py-3 sm:p-6 shadow-sm">
          <p class="text-xs sm:text-sm text-gray-500 font-medium">Sin stock</p>
          <p class="text-xl sm:text-3xl font-black text-red-500 mt-1 tabular-nums">{{ stats.sinStock }}</p>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 px-3.5 py-3 sm:p-6 shadow-sm">
          <p class="text-xs sm:text-sm text-gray-500 font-medium">Valor del inventario</p>
          <p class="text-lg min-[380px]:text-xl sm:text-3xl font-black text-[#ff8c42] mt-1 tabular-nums whitespace-nowrap">S/ {{ dinero(stats.valorInventario) }}</p>
        </div>
      </div>

      <!-- Explorar: categoría → marca → modelo → colores × tallas -->
      <ListCard v-if="vista === 'arbol'" title="Inventario" subtitle="Recorre tus categorías, marcas y modelos; toca una talla para comprar más">
        <template #action>
          <div class="inline-flex p-1 bg-gray-100 rounded-xl" role="tablist" aria-label="Vista del inventario">
            <button
              v-for="op in VISTAS"
              :key="op.id"
              type="button"
              role="tab"
              :aria-selected="vista === op.id"
              @click="cambiarVista(op.id)"
              :class="['flex items-center gap-2 px-3.5 h-10 sm:h-auto sm:py-1.5 rounded-lg text-sm font-semibold transition-all', vista === op.id ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
            >
              <i :class="op.icono" class="text-xs"></i> {{ op.nombre }}
            </button>
          </div>
        </template>

        <InventarioExplorador
          :modelos="modelos"
          :categorias-info="categoriasInfo"
          :marcas="marcas"
          @comprar="comprarDesdeInventario"
        />
      </ListCard>

      <!-- Lista plana de variantes (con sus lotes) -->
      <ListCard v-else title="Inventario comprado" subtitle="Productos y variantes registrados por compras">
        <template #action>
          <div class="inline-flex p-1 bg-gray-100 rounded-xl" role="tablist" aria-label="Vista del inventario">
            <button
              v-for="op in VISTAS"
              :key="op.id"
              type="button"
              role="tab"
              :aria-selected="vista === op.id"
              @click="cambiarVista(op.id)"
              :class="['flex items-center gap-2 px-3.5 h-10 sm:h-auto sm:py-1.5 rounded-lg text-sm font-semibold transition-all', vista === op.id ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
            >
              <i :class="op.icono" class="text-xs"></i> {{ op.nombre }}
            </button>
          </div>
        </template>

        <template v-if="stats.totalVariantes > 0" #toolbar>
          <div class="w-full space-y-2.5 sm:space-y-0 sm:flex sm:flex-wrap sm:items-center sm:gap-3">
            <!-- Búsqueda (y, en el celular, «Registrar compra» como botón compacto al lado) -->
            <div class="flex gap-2 sm:contents">
              <SearchInput v-model="filtroTexto" placeholder="Modelo o marca" class="flex-1 min-w-0 sm:flex-none sm:w-72" />
              <button
                type="button"
                class="sm:hidden w-11 h-11 rounded-xl bg-[#ff8c42] hover:bg-[#f27d30] active:scale-95 text-white flex items-center justify-center flex-shrink-0 shadow-sm shadow-orange-200"
                aria-label="Registrar nueva compra"
                @click="showModal = true"
              ><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
            </div>

            <!-- Celular: panel con dos filas separadas por una línea; la etiqueta queda fija y solo las fichas se deslizan -->
            <div class="sm:hidden rounded-2xl border border-gray-100 bg-gray-50 divide-y divide-gray-100">
              <div class="flex items-center" role="group" aria-label="Filtrar por stock">
                <span class="w-[5.5rem] pl-3.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 flex-shrink-0">Stock</span>
                <div class="flex-1 min-w-0 flex gap-2 overflow-x-auto py-2 pr-3">
                  <button
                    v-for="o in opcionesStock"
                    :key="'s' + o.value"
                    type="button"
                    :aria-pressed="filtroStock === o.value"
                    :class="['h-10 px-4 flex-shrink-0 whitespace-nowrap rounded-full text-sm font-semibold border transition-colors', filtroStock === o.value ? 'bg-[#ff8c42] border-[#ff8c42] text-white shadow-sm' : 'bg-surface border-gray-200 text-gray-600']"
                    @click="filtroStock = o.value"
                  >{{ o.corto }}</button>
                </div>
              </div>
              <div class="flex items-center" role="group" aria-label="Filtrar por categoría">
                <span class="w-[5.5rem] pl-3.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 flex-shrink-0">Categoría</span>
                <div class="flex-1 min-w-0 flex gap-2 overflow-x-auto py-2 pr-3">
                  <button
                    v-for="o in opcionesCategoria"
                    :key="'c' + o.value"
                    type="button"
                    :aria-pressed="filtroCategoria === o.value"
                    :class="['h-10 px-4 flex-shrink-0 whitespace-nowrap rounded-full text-sm font-semibold border transition-colors', filtroCategoria === o.value ? 'bg-fg border-fg text-canvas' : 'bg-surface border-gray-200 text-gray-600']"
                    @click="filtroCategoria = o.value"
                  >{{ o.value === '' ? 'Todas' : o.label }}</button>
                </div>
              </div>
            </div>

            <!-- Escritorio: listas desplegables -->
            <SelectDropdown v-model="filtroCategoria" :options="opcionesCategoria" size="sm" class="hidden sm:block sm:min-w-[190px]" />
            <SelectDropdown v-model="filtroStock" :options="opcionesStock" size="sm" class="hidden sm:block sm:min-w-[170px]" />
            <button
              v-if="hayFiltrosActivos"
              @click="limpiarFiltros"
              class="text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-100 h-11 sm:h-auto px-3 sm:py-2 rounded-xl flex items-center gap-1.5 transition-colors"
            >
              <i class="fa-solid fa-xmark text-xs"></i> Limpiar filtros
            </button>
            <PrimaryButton class="hidden sm:inline-flex lg:ml-auto" @click="showModal = true">Registrar nueva compra</PrimaryButton>
          </div>
        </template>

        <!-- Celular: una tarjeta por variante (la tabla de 5 columnas no cabe) -->
        <ul v-if="variantesPaginadas.length > 0" class="md:hidden px-4 pb-2 space-y-2.5">
          <li v-for="v in variantesPaginadas" :key="'m' + v.id" class="rounded-2xl border border-gray-100 bg-surface overflow-hidden">
            <div class="flex">
              <!-- Franja de estado del stock -->
              <span :class="['w-1.5 flex-shrink-0 my-3 ml-2.5 rounded-full', v.stock === 0 ? 'bg-red-400' : v.stock < umbralStockBajo ? 'bg-yellow-400' : 'bg-green-500']" aria-hidden="true"></span>
              <div class="flex-1 min-w-0 py-3.5 pr-3.5 pl-3">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="font-semibold text-gray-900 leading-tight truncate">{{ v.producto }}</p>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ v.marca }} · {{ v.categoria }}</p>
                    <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-gray-600">
                      <span class="inline-flex items-center gap-1.5"><ColorSwatch :hex="v.colorHex" class="w-3.5 h-3.5 rounded-full border border-gray-200" />{{ v.color }}</span>
                      <span class="font-semibold bg-gray-100 rounded-md px-2 py-0.5">Talla {{ v.talla }}</span>
                    </div>
                  </div>
                  <div class="text-right flex-shrink-0">
                    <p :class="['text-2xl font-black leading-none tabular-nums', v.stock === 0 ? 'text-red-500' : v.stock < umbralStockBajo ? 'text-yellow-600' : 'text-gray-900']">{{ v.stock }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ v.stock === 0 ? 'sin stock' : v.stock === 1 ? 'unidad' : 'unidades' }}</p>
                  </div>
                </div>

                <div class="mt-2 flex items-center justify-between text-xs text-gray-500 tabular-nums">
                  <span>Costo S/ {{ dinero(v.precioCompra) }}</span>
                  <span class="font-bold text-gray-900 text-sm">Venta S/ {{ dinero(v.precioVenta) }}</span>
                </div>

                <div class="mt-3 flex items-center gap-2">
                  <button
                    type="button"
                    class="flex-1 h-11 rounded-xl bg-orange-50 hover:bg-[#ff8c42] text-[#e67e2d] hover:text-white text-sm font-bold flex items-center justify-center gap-2 transition-colors"
                    @click="reabastecer(v)"
                  ><i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> Reabastecer</button>
                  <button
                    type="button"
                    class="h-11 px-3.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-semibold flex items-center gap-2 flex-shrink-0"
                    :aria-expanded="expandedVariantId === v.id"
                    @click="toggleLotes(v.id)"
                  ><i :class="expandedVariantId === v.id ? 'fa-solid fa-chevron-up' : 'fa-solid fa-layer-group'" class="text-xs" aria-hidden="true"></i> Lotes</button>
                </div>

            <div v-if="expandedVariantId === v.id" class="mt-3 rounded-xl bg-gray-50 p-3">
              <p v-if="loadingLotes" class="text-xs text-gray-500">Cargando lotes…</p>
              <p v-else-if="(lotesCache[v.id] || []).length === 0" class="text-xs text-gray-500">Sin lotes registrados.</p>
              <ul v-else class="divide-y divide-gray-200">
                <li v-for="lote in lotesCache[v.id]" :key="lote.id" class="py-2 first:pt-0 last:pb-0 text-xs">
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-mono text-[11px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ lote.numeroCompra }}</span>
                    <span class="text-gray-900 font-semibold tabular-nums">S/ {{ dinero(lote.costoUnitario) }} c/u</span>
                  </div>
                  <p class="mt-1 text-gray-500">{{ lote.fecha }} · {{ lote.proveedor || 'Sin proveedor' }}</p>
                  <p class="text-gray-500">Comprado {{ lote.cantidad }} · <span :class="lote.cantidadRestante === 0 ? 'text-red-400 font-semibold' : 'text-gray-900 font-semibold'">quedan {{ lote.cantidadRestante }}</span></p>
                </li>
              </ul>
            </div>
              </div>
            </div>
          </li>
        </ul>
        <div v-if="variantesPaginadas.length > 0" class="md:hidden px-4 pb-4"><Pagination v-model="paginaActual" :total-items="totalLista" :per-page="porPagina" /></div>

        <DataTable v-if="variantesPaginadas.length > 0" class="hidden md:block">
          <template #head>
            <tr>
              <th class="text-left">Producto</th>
              <th class="text-left">Talla</th>
              <th class="text-left hidden md:table-cell">Color</th>
              <th class="text-left">Stock</th>
              <th class="text-right hidden lg:table-cell">Último costo</th>
              <th class="text-right">P. venta</th>
              <th class="text-right">Lotes</th>
            </tr>
          </template>

          <template v-for="(v, i) in variantesPaginadas" :key="v.id">
            <tr :class="mismoModelo(i) ? '[&>td]:!pt-2 [&>td]:!pb-2 !border-t-0' : ''">
              <td>
                <!-- Las variantes seguidas del mismo modelo no repiten el nombre: se agrupan bajo una línea -->
                <div v-if="mismoModelo(i)" class="flex items-center gap-2 pl-1 text-gray-300" aria-hidden="true"><i class="fa-solid fa-turn-up fa-rotate-90 text-[10px]"></i></div>
                <template v-else>
                  <p class="font-semibold text-gray-900 truncate max-w-[18rem]">{{ v.producto }}</p>
                  <p class="text-xs text-gray-500 mt-0.5 truncate max-w-[18rem]">{{ v.marca }} <span class="text-gray-300">·</span> {{ v.categoria }}</p>
                </template>
                <span v-if="mismoModelo(i)" class="sr-only">{{ v.producto }}</span>
              </td>

              <td>
                <span class="inline-flex items-center justify-center min-w-[2.5rem] h-7 px-2 rounded-lg text-xs font-bold bg-gray-100 text-gray-700">{{ v.talla }}</span>
              </td>

              <td class="hidden md:table-cell">
                <div class="flex items-center gap-2">
                  <ColorSwatch :hex="v.colorHex" class="w-5 h-5 rounded-full border border-gray-200" />
                  <span class="text-gray-700">{{ v.color }}</span>
                </div>
              </td>

              <td>
                <div class="flex items-center gap-2">
                  <span
                    :class="[
                      'text-xs font-bold px-2.5 py-1 rounded-full whitespace-nowrap',
                      v.stock === 0 ? 'bg-red-100 text-red-500' : v.stock < umbralStockBajo ? 'bg-yellow-100 text-yellow-600' : 'bg-green-100 text-green-600',
                    ]"
                  >
                    <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle" :class="v.stock === 0 ? 'bg-red-400' : v.stock < umbralStockBajo ? 'bg-yellow-500' : 'bg-green-500'" aria-hidden="true"></span>{{ v.stock === 0 ? 'Sin stock' : `${v.stock} ${v.stock === 1 ? 'unidad' : 'unidades'}` }}
                  </span>
                  <button
                    @click="reabastecer(v)"
                    title="Reabastecer stock"
                    aria-label="Reabastecer stock"
                    class="w-8 h-8 rounded-full bg-orange-50 hover:bg-[#ff8c42] text-[#ff8c42] hover:text-white flex items-center justify-center transition-[transform,background-color,color] duration-200 hover:scale-110 active:scale-95"
                  >
                    <i class="fa-solid fa-plus text-xs"></i>
                  </button>
                </div>
              </td>

              <td class="text-right text-gray-600 tabular-nums whitespace-nowrap hidden lg:table-cell">S/ {{ dinero(v.precioCompra) }}</td>
              <td class="text-right text-gray-900 font-semibold tabular-nums whitespace-nowrap">S/ {{ dinero(v.precioVenta) }}</td>

              <td>
                <div class="flex justify-end">
                  <ActionButton
                    :icon="expandedVariantId === v.id ? 'fa-solid fa-chevron-up' : 'fa-solid fa-layer-group'"
                    label="Ver lotes de compra"
                    tone="gray"
                    :aria-expanded="expandedVariantId === v.id"
                    @click="toggleLotes(v.id)"
                  />
                </div>
              </td>
            </tr>

            <tr v-if="expandedVariantId === v.id" class="!bg-gray-50/70">
              <td colspan="7" class="!px-6 !py-4">
                <div v-if="loadingLotes" class="text-xs text-gray-400 py-2">Cargando lotes...</div>
                <div v-else-if="(lotesCache[v.id] || []).length === 0" class="text-xs text-gray-400 py-2">
                  Sin lotes registrados.
                </div>
                <table v-else class="w-full text-xs">
                  <thead>
                    <tr class="text-gray-400">
                      <th class="text-left py-1.5 pr-4 font-semibold">N° Compra</th>
                      <th class="text-left py-1.5 pr-4 font-semibold">Fecha</th>
                      <th class="text-left py-1.5 pr-4 font-semibold">Proveedor</th>
                      <th class="text-center py-1.5 pr-4 font-semibold">Comprado</th>
                      <th class="text-center py-1.5 pr-4 font-semibold">Restante</th>
                      <th class="text-right py-1.5 font-semibold">Costo Unit.</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="lote in lotesCache[v.id]" :key="lote.id" class="border-t border-gray-100">
                      <td class="py-2 pr-4">
                        <span class="font-mono text-[11px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ lote.numeroCompra }}</span>
                      </td>
                      <td class="py-2 pr-4 text-gray-600">{{ lote.fecha }}</td>
                      <td class="py-2 pr-4 text-gray-600">{{ lote.proveedor || '—' }}</td>
                      <td class="py-2 pr-4 text-center text-gray-900">{{ lote.cantidad }}</td>
                      <td class="py-2 pr-4 text-center font-semibold" :class="lote.cantidadRestante === 0 ? 'text-red-400' : 'text-gray-900'">
                        {{ lote.cantidadRestante }}
                      </td>
                      <td class="py-2 text-right text-gray-900">S/ {{ dinero(lote.costoUnitario) }}</td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </template>

          <template #footer>
            <Pagination v-model="paginaActual" :total-items="totalLista" :per-page="porPagina" />
          </template>
        </DataTable>

        <EmptyState
          v-else-if="stats.totalVariantes === 0"
          icon="fa-solid fa-box"
          title="Aún no hay productos registrados por compra"
          text="Registra tu primera compra para empezar a llenar el inventario."
        >
          <PrimaryButton @click="showModal = true">Registrar nueva compra</PrimaryButton>
        </EmptyState>

        <p v-else-if="!lista" class="px-6 pb-8 text-sm text-gray-500" role="status"><i class="fa-solid fa-spinner fa-spin mr-2" aria-hidden="true"></i>Cargando la lista…</p>

        <EmptyState
          v-else
          icon="fa-solid fa-filter-circle-xmark"
          title="Ningún producto coincide"
          text="Prueba con otra búsqueda o cambia los filtros."
        >
          <button @click="limpiarFiltros" class="text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 px-4 py-2 rounded-xl transition">Limpiar filtros</button>
        </EmptyState>
      </ListCard>
    </div>

    <RegistrarCompraModal v-if="showModal" :categorias-existentes="categorias" :categorias-info="categoriasInfo" :marcas-info="marcasInfo" :marcas-existentes="marcas" :prefill="prefillData" @close="cerrarModal" />
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RegistrarCompraModal from './RegistrarCompraModal.vue';
import InventarioExplorador from './InventarioExplorador.vue';
import { ref, computed, watch, onMounted } from 'vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import ListCard from '@/Components/Admin/ListCard.vue';
import PrimaryButton from '@/Components/Admin/PrimaryButton.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';

const props = defineProps({
  lista: Object, // solo existe cuando se abre la vista «Lista»: { data, paginacion }
  modelos: Array,
  stats: Object,
  categorias: Array,
  categoriasInfo: Array,
  marcas: Array,
  marcasInfo: Array,
});

const page = usePage();
const umbralStockBajo = computed(() => page.props.business?.low_stock_threshold ?? 5);
const showModal = ref(false);

// Vista del inventario: "Explorar" (árbol por categoría) o "Lista" (variantes con sus lotes).
const VISTAS = [
  { id: 'arbol', nombre: 'Explorar', icono: 'fa-solid fa-sitemap' },
  { id: 'lista', nombre: 'Lista', icono: 'fa-solid fa-list' },
];
const CLAVE_VISTA = 'inventario.vista';

const leerVista = () => {
  try {
    return localStorage.getItem(CLAVE_VISTA) === 'lista' ? 'lista' : 'arbol';
  } catch {
    return 'arbol';
  }
};

const vista = ref(leerVista());

const cambiarVista = (id) => {
  vista.value = id;
  if (id === 'lista') cargarLista();
  try {
    localStorage.setItem(CLAVE_VISTA, id);
  } catch {
    // Sin almacenamiento disponible: la vista simplemente no se recuerda.
  }
};

const prefillData = ref(null);

// "Comprar" desde el árbol: abre el formulario con lo que ya se sabe (categoría, marca, modelo...).
const comprarDesdeInventario = (prefill) => {
  prefillData.value = prefill;
  showModal.value = true;
};

const reabastecer = (variant) => {
  prefillData.value = {
    productId: variant.productId,
    nombre: variant.producto,
    marca: variant.marca,
    categoria: variant.categoria,
    talla: variant.talla,
    color: variant.colorHex,
    colorNombre: variant.color,
    precioCompra: variant.precioCompra,
    precioVenta: variant.precioVenta,
    tienda: variant.ultimoProveedor,
  };
  showModal.value = true;
};

const cerrarModal = () => {
  showModal.value = false;
  prefillData.value = null;
};

const mismoModelo = (i) => {
  const a = variantesPaginadas.value[i];
  const b = variantesPaginadas.value[i - 1];
  return !!b && a.productId === b.productId;
};

const dinero = (n) => Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const filtroTexto = ref('');
const filtroCategoria = ref('');
const filtroStock = ref('');

const opcionesStock = computed(() => [
  { value: '', label: 'Todo el stock', corto: 'Todo' },
  { value: 'sin', label: 'Sin stock', corto: 'Sin stock' },
  { value: 'bajo', label: `Stock bajo (< ${umbralStockBajo.value})`, corto: 'Stock bajo' },
  { value: 'con', label: 'Con stock', corto: 'Con stock' },
]);

const opcionesCategoria = computed(() => [
  { value: '', label: 'Todas las categorías' },
  ...props.categorias.map((c) => ({ value: c, label: c })),
]);

const hayFiltrosActivos = computed(() => !!(filtroTexto.value || filtroCategoria.value || filtroStock.value));

const limpiarFiltros = () => {
  filtroTexto.value = '';
  filtroCategoria.value = '';
  filtroStock.value = '';
};

// La lista se pide al servidor ya filtrada y paginada: cada cambio de filtro o de página trae solo esa página.
const variantesPaginadas = computed(() => props.lista?.data ?? []);
const totalLista = computed(() => props.lista?.paginacion.total ?? 0);
const porPagina = computed(() => props.lista?.paginacion.porPagina ?? 15);

const cargarLista = (extra = {}) => {
  router.get(route('admin.purchases.index'), {
    buscar: filtroTexto.value.trim() || undefined,
    categoria: filtroCategoria.value || undefined,
    stock: filtroStock.value || undefined,
    ...extra,
  }, { only: ['lista'], preserveState: true, preserveScroll: true, replace: true });
};

const paginaActual = computed({
  get: () => props.lista?.paginacion.pagina ?? 1,
  set: (p) => cargarLista({ pagina: p }),
});

let temporizador = null;
watch(filtroTexto, () => {
  clearTimeout(temporizador);
  temporizador = setTimeout(() => cargarLista(), 300);
});
watch([filtroCategoria, filtroStock], () => cargarLista());

// Si la última vez se dejó la vista «Lista», se pide al abrir.
onMounted(() => {
  if (vista.value === 'lista') cargarLista();
});

const expandedVariantId = ref(null);
const lotesCache = ref({});
const loadingLotes = ref(false);

const toggleLotes = async (variantId) => {
  if (expandedVariantId.value === variantId) {
    expandedVariantId.value = null;
    return;
  }

  expandedVariantId.value = variantId;

  if (lotesCache.value[variantId]) {
    return;
  }

  loadingLotes.value = true;
  try {
    const res = await fetch(`/admin/compras/variantes/${variantId}/lotes`);
    lotesCache.value[variantId] = await res.json();
  } catch (e) {
    console.error('No se pudieron cargar los lotes', e);
    lotesCache.value[variantId] = [];
  } finally {
    loadingLotes.value = false;
  }
};
</script>
