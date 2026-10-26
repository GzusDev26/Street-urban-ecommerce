<template>
  <Head title="Compras" />
  <AdminLayout>
    <template #breadcrumb>Compras</template>
    <template #header>Compras</template>

    <div class="space-y-6">
      <!-- Tabs -->
      <div class="flex gap-2 border-b border-gray-200">
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
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Total Productos</p>
          <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.totalProductos }}</p>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Variantes Registradas</p>
          <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.totalVariantes }}</p>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Sin Stock</p>
          <p class="text-3xl font-black text-red-500 mt-1">{{ stats.sinStock }}</p>
        </div>
        <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
          <p class="text-sm text-gray-400 font-medium">Valor de Inventario</p>
          <p class="text-3xl font-black text-[#ff8c42] mt-1">S/ {{ stats.valorInventario.toFixed(2) }}</p>
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
              :class="['flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-semibold transition-all', vista === op.id ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
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
              :class="['flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-semibold transition-all', vista === op.id ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
            >
              <i :class="op.icono" class="text-xs"></i> {{ op.nombre }}
            </button>
          </div>
        </template>

        <template v-if="variants.length > 0" #toolbar>
          <div class="flex flex-wrap items-center gap-3 w-full">
            <SearchInput v-model="filtroTexto" placeholder="Buscar por modelo o marca" class="w-full sm:w-72" />
            <SelectDropdown v-model="filtroCategoria" :options="opcionesCategoria" size="sm" class="min-w-[190px]" />
            <SelectDropdown v-model="filtroStock" :options="opcionesStock" size="sm" class="min-w-[170px]" />
            <button
              v-if="hayFiltrosActivos"
              @click="limpiarFiltros"
              class="text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-xl flex items-center gap-1.5 transition-colors"
            >
              <i class="fa-solid fa-xmark text-xs"></i> Limpiar
            </button>
            <PrimaryButton class="lg:ml-auto" @click="showModal = true">Registrar nueva compra</PrimaryButton>
          </div>
        </template>

        <DataTable v-if="variantesFiltradas.length > 0">
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

          <template v-for="v in variantesPaginadas" :key="v.id">
            <tr>
              <td>
                <p class="font-semibold text-gray-900 truncate max-w-[18rem]">{{ v.producto }}</p>
                <p class="text-xs text-gray-500 mt-0.5 truncate max-w-[18rem]">{{ v.marca }} <span class="text-gray-300">·</span> {{ v.categoria }}</p>
              </td>

              <td>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-gray-100 text-gray-600">{{ v.talla }}</span>
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
                    {{ v.stock === 0 ? 'Sin stock' : v.stock }}
                  </span>
                  <button
                    @click="reabastecer(v)"
                    title="Reabastecer stock"
                    aria-label="Reabastecer stock"
                    class="w-6 h-6 rounded-full bg-orange-50 hover:bg-[#ff8c42] text-[#ff8c42] hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95"
                  >
                    <i class="fa-solid fa-plus text-[10px]"></i>
                  </button>
                </div>
              </td>

              <td class="text-right text-gray-600 tabular-nums whitespace-nowrap hidden lg:table-cell">S/ {{ v.precioCompra.toFixed(2) }}</td>
              <td class="text-right text-gray-900 font-semibold tabular-nums whitespace-nowrap">S/ {{ v.precioVenta.toFixed(2) }}</td>

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
                      <td class="py-2 text-right text-gray-900">S/ {{ lote.costoUnitario.toFixed(2) }}</td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </template>

          <template #footer>
            <Pagination v-model="paginaActual" :total-items="variantesFiltradas.length" :per-page="perPage" />
          </template>
        </DataTable>

        <EmptyState
          v-else-if="variants.length === 0"
          icon="fa-solid fa-box"
          title="Aún no hay productos registrados por compra"
          text="Registra tu primera compra para empezar a llenar el inventario."
        >
          <PrimaryButton @click="showModal = true">Registrar nueva compra</PrimaryButton>
        </EmptyState>

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
import { ref, computed, watch } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
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
  variants: Array,
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

const filtroTexto = ref('');
const filtroCategoria = ref('');
const filtroStock = ref('');

const opcionesStock = computed(() => [
  { value: '', label: 'Todo el stock' },
  { value: 'sin', label: 'Sin stock' },
  { value: 'bajo', label: `Stock bajo (< ${umbralStockBajo.value})` },
  { value: 'con', label: 'Con stock' },
]);

const opcionesCategoria = computed(() => {
  const presentes = [...new Set(props.variants.map(v => v.categoria))].sort();
  return [{ value: '', label: 'Todas las categorías' }, ...presentes.map(c => ({ value: c, label: c }))];
});

const hayFiltrosActivos = computed(() => !!(filtroTexto.value || filtroCategoria.value || filtroStock.value));

const limpiarFiltros = () => {
  filtroTexto.value = '';
  filtroCategoria.value = '';
  filtroStock.value = '';
};

const variantesFiltradas = computed(() => {
  const texto = filtroTexto.value.trim().toLowerCase();

  return props.variants.filter((v) => {
    if (filtroCategoria.value && v.categoria !== filtroCategoria.value) return false;

    if (filtroStock.value === 'sin' && v.stock !== 0) return false;
    if (filtroStock.value === 'bajo' && (v.stock === 0 || v.stock >= umbralStockBajo.value)) return false;
    if (filtroStock.value === 'con' && v.stock === 0) return false;

    if (texto) {
      const coincide = v.producto.toLowerCase().includes(texto) || v.marca.toLowerCase().includes(texto);
      if (!coincide) return false;
    }

    return true;
  });
});

const perPage = 15;
const paginaActual = ref(1);

watch([filtroTexto, filtroCategoria, filtroStock], () => {
  paginaActual.value = 1;
});

watch(() => variantesFiltradas.value.length, (total) => {
  const totalPaginas = Math.max(1, Math.ceil(total / perPage));
  if (paginaActual.value > totalPaginas) paginaActual.value = totalPaginas;
});

const variantesPaginadas = computed(() => {
  const inicio = (paginaActual.value - 1) * perPage;
  return variantesFiltradas.value.slice(inicio, inicio + perPage);
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
