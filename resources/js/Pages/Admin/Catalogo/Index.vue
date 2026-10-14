<template>
  <Head title="Catálogo" />
  <AdminLayout>
    <template #breadcrumb>Catálogo</template>
    <template #header>Catálogo de Productos</template>

    <div class="space-y-6">
      <!-- FLASH SUCCESS -->
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>
      <div v-if="page.props.errors?.error" class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation"></i> {{ page.props.errors.error }}
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold text-gray-900">Modelos Registrados</h3>
            <p class="text-xs text-gray-400">Categoría, marca y modelo de cada producto — la identidad base del catálogo</p>
          </div>
          <button
            @click="abrirCrear"
            class="bg-[#ff8c42] hover:bg-[#ff7a24] text-white text-sm font-bold py-2.5 px-5 rounded-xl transition flex items-center gap-2"
          >
            <i class="fa-solid fa-plus text-xs"></i> Registrar Modelo
          </button>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-center gap-3 mb-5">
          <SearchInput v-model="filtroTexto" placeholder="Buscar por modelo o marca..." class="w-full sm:w-64" />
          <SelectDropdown v-model="filtroCategoria" :options="opcionesCategoria" class="min-w-[190px]" />
          <SelectDropdown v-model="filtroMarca" :options="opcionesMarca" class="min-w-[170px]" />
          <button
            v-if="filtroTexto || filtroCategoria || filtroMarca"
            @click="filtroTexto = ''; filtroCategoria = ''; filtroMarca = ''"
            class="text-sm font-semibold text-gray-400 hover:text-gray-600 px-3 py-2"
          >
            <i class="fa-solid fa-xmark text-xs"></i> Limpiar
          </button>
        </div>

        <div v-if="productosFiltrados.length > 0" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-gray-200">
              <tr>
                <th class="text-left py-3 px-4 font-semibold text-gray-500">Categoría</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-500">Marca</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-500">Modelo</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-500">Tallas</th>
                <th class="text-center py-3 px-4 font-semibold text-gray-500">Variantes</th>
                <th class="text-center py-3 px-4 font-semibold text-gray-500">Stock Total</th>
                <th class="text-right py-3 px-4 font-semibold text-gray-500">P.Venta</th>
                <th class="text-center py-3 px-4 font-semibold text-gray-500">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="p in productosPaginados" :key="p.id">
                <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                  <td class="py-3 px-4 text-gray-600">{{ p.categoria }}</td>
                  <td class="py-3 px-4 text-gray-600">{{ p.marca }}</td>
                  <td class="py-3 px-4 text-gray-900 font-medium">{{ p.nombre }}</td>
                  <td class="py-3 px-4">
                    <div v-if="p.tallas.length > 0" class="flex flex-wrap gap-1">
                      <span
                        v-for="talla in p.tallas"
                        :key="talla"
                        class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500"
                      >
                        {{ talla }}
                      </span>
                    </div>
                    <span v-else class="text-xs text-gray-300">—</span>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <button
                      @click="toggleVariantes(p.id)"
                      class="inline-flex items-center gap-1.5 text-gray-600 hover:text-[#ff8c42] transition-colors"
                      title="Ver colores registrados"
                    >
                      {{ p.variantes }} color{{ p.variantes === 1 ? '' : 'es' }}
                      <i :class="['fa-solid text-xs', expandedProductId === p.id ? 'fa-chevron-up' : 'fa-layer-group']"></i>
                    </button>
                  </td>
                  <td class="py-3 px-4 text-center">
                    <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', claseStock(p.stock)]">
                      {{ p.stock === 0 ? 'Sin stock' : p.stock }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-right text-gray-900">
                    S/ {{ p.precioVenta.toFixed(2) }}<span v-if="p.precioVentaMax"> - {{ p.precioVentaMax.toFixed(2) }}</span>
                  </td>
                  <td class="py-3 px-4">
                    <div class="flex items-center justify-center gap-2">
                      <button
                        @click="abrirColores(p)"
                        title="Colores"
                        class="w-8 h-8 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-500 hover:text-purple-600 flex items-center justify-center transition-colors"
                      >
                        <i class="fa-solid fa-palette text-xs"></i>
                      </button>
                      <button
                        @click="abrirEditar(p)"
                        title="Editar"
                        class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-500 hover:text-blue-600 flex items-center justify-center transition-colors"
                      >
                        <i class="fa-solid fa-pen text-xs"></i>
                      </button>
                      <button
                        v-if="p.activo"
                        @click="darDeBaja(p)"
                        title="Dar de baja"
                        class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-600 flex items-center justify-center transition-colors"
                      >
                        <i class="fa-solid fa-box-archive text-xs"></i>
                      </button>
                      <button
                        v-else
                        @click="restaurar(p)"
                        title="Restaurar producto"
                        class="w-8 h-8 rounded-lg bg-green-50 hover:bg-green-100 text-green-600 flex items-center justify-center transition-colors"
                      >
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="expandedProductId === p.id" class="bg-gray-50">
                  <td colspan="8" class="px-6 py-4">
                    <div v-if="loadingVariantes" class="text-xs text-gray-400 py-2">Cargando variantes...</div>
                    <div v-else-if="(variantesCache[p.id] || []).length === 0" class="text-xs text-gray-400 py-2">
                      Este modelo aún no tiene colores registrados — agrégalos desde el ícono de paleta.
                    </div>
                    <div v-else class="space-y-2">
                      <div v-for="c in variantesCache[p.id]" :key="c.id" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <button
                          @click="expandedColorId = expandedColorId === c.id ? null : c.id"
                          class="w-full flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors"
                        >
                          <div class="flex items-center gap-2 flex-wrap min-w-0">
                            <ColorSwatch :hex="c.hex" class="w-3.5 h-3.5 rounded-full border border-gray-200 flex-shrink-0" />
                            <span class="text-sm font-medium text-gray-800 flex-shrink-0">{{ c.nombre }}</span>
                            <div v-if="c.tallas.length > 0" class="flex flex-wrap gap-1">
                              <span
                                v-for="t in c.tallas"
                                :key="t.id"
                                class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500"
                              >
                                {{ t.talla }}
                              </span>
                            </div>
                            <span v-else class="text-xs text-gray-400">Sin tallas</span>
                          </div>
                          <div class="flex items-center gap-3 flex-shrink-0">
                            <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', claseStock(c.stockTotal)]">
                              {{ c.stockTotal === 0 ? 'Sin stock' : c.stockTotal }}
                            </span>
                            <i :class="['fa-solid text-xs text-gray-400', expandedColorId === c.id ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                          </div>
                        </button>

                        <table v-if="expandedColorId === c.id" class="w-full text-xs border-t border-gray-100">
                          <thead>
                            <tr class="text-gray-400">
                              <th class="text-left py-1.5 px-4 font-semibold">Talla</th>
                              <th class="text-center py-1.5 px-4 font-semibold">Stock</th>
                              <th class="text-right py-1.5 px-4 font-semibold">Costo Ref.</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-if="c.tallas.length === 0">
                              <td colspan="3" class="py-2 px-4 text-gray-400">Sin tallas registradas — se crean al registrar una compra.</td>
                            </tr>
                            <tr v-for="t in c.tallas" :key="t.id" class="border-t border-gray-50">
                              <td class="py-2 px-4 text-gray-900 font-medium">{{ t.talla }}</td>
                              <td class="py-2 px-4 text-center">
                                <span :class="['text-xs font-bold px-2 py-0.5 rounded-full', claseStock(t.stock)]">
                                  {{ t.stock === 0 ? 'Sin stock' : t.stock }}
                                </span>
                              </td>
                              <td class="py-2 px-4 text-right text-gray-900">S/ {{ t.costo.toFixed(2) }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>

          <Pagination v-model="paginaActual" :total-items="productosFiltrados.length" :per-page="perPage" />
        </div>

        <div v-else-if="productos.length === 0" class="bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
          <i class="fa-solid fa-book text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">Aún no hay modelos registrados en el catálogo.</p>
        </div>

        <div v-else class="bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
          <i class="fa-solid fa-filter-circle-xmark text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">Ningún modelo coincide con estos filtros.</p>
        </div>
      </div>
    </div>

    <RegistrarModeloModal
      v-if="showModal"
      :categorias-existentes="categorias"
      :marcas-existentes="marcas"
      :editando="editando"
      @close="cerrarModal"
    />

    <GestionarColoresModal
      v-if="productoColores"
      :producto="productoColores"
      @close="cerrarColores"
    />
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RegistrarModeloModal from './RegistrarModeloModal.vue';
import GestionarColoresModal from './GestionarColoresModal.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import { ref, computed, watch } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
  productos: Array,
  categorias: Array,
  marcas: Array,
});

const page = usePage();
const umbralStockBajo = computed(() => page.props.business?.low_stock_threshold ?? 5);

const claseStock = (stock) => {
  if (stock === 0) return 'bg-red-100 text-red-500';
  if (stock < umbralStockBajo.value) return 'bg-yellow-100 text-yellow-600';
  return 'bg-green-100 text-green-600';
};

const filtroTexto = ref('');
const filtroCategoria = ref('');
const filtroMarca = ref('');

const opcionesCategoria = computed(() => [
  { value: '', label: 'Todas las categorías' },
  ...props.categorias.map(c => ({ value: c, label: c })),
]);

const opcionesMarca = computed(() => [
  { value: '', label: 'Todas las marcas' },
  ...props.marcas.map(m => ({ value: m, label: m })),
]);

const productosFiltrados = computed(() => {
  const texto = filtroTexto.value.trim().toLowerCase();

  return props.productos.filter((p) => {
    if (filtroCategoria.value && p.categoria !== filtroCategoria.value) return false;
    if (filtroMarca.value && p.marca !== filtroMarca.value) return false;
    if (texto && !p.nombre.toLowerCase().includes(texto) && !p.marca.toLowerCase().includes(texto)) return false;
    return true;
  });
});

const perPage = 15;
const paginaActual = ref(1);

watch([filtroTexto, filtroCategoria, filtroMarca], () => {
  paginaActual.value = 1;
});

watch(() => productosFiltrados.value.length, (total) => {
  const totalPaginas = Math.max(1, Math.ceil(total / perPage));
  if (paginaActual.value > totalPaginas) paginaActual.value = totalPaginas;
});

const productosPaginados = computed(() => {
  const inicio = (paginaActual.value - 1) * perPage;
  return productosFiltrados.value.slice(inicio, inicio + perPage);
});

const showModal = ref(false);
const editando = ref(null);

const abrirCrear = () => {
  editando.value = null;
  showModal.value = true;
};

const abrirEditar = (producto) => {
  editando.value = { id: producto.id, nombre: producto.nombre, marca: producto.marca, categoria: producto.categoria, descripcion: producto.descripcion };
  showModal.value = true;
};

const cerrarModal = () => {
  showModal.value = false;
  editando.value = null;
};

const productoColores = ref(null);

const abrirColores = (producto) => {
  productoColores.value = producto;
};

const cerrarColores = () => {
  productoColores.value = null;
  // Puede haber cambiado el color de alguna variante ya cargada en caché.
  variantesCache.value = {};
  expandedColorId.value = null;
};

const darDeBaja = (producto) => {
  if (!confirm(`¿Dar de baja "${producto.nombre}" (${producto.marca})? Se conservará su historial y podrás restaurarlo después.`)) return;
  router.patch(route('admin.catalogo.deactivate', producto.id));
};

const restaurar = (producto) => {
  router.patch(route('admin.catalogo.restore', producto.id));
};

const expandedProductId = ref(null);
const expandedColorId = ref(null);
const variantesCache = ref({});
const loadingVariantes = ref(false);

const toggleVariantes = async (productId) => {
  expandedColorId.value = null;

  if (expandedProductId.value === productId) {
    expandedProductId.value = null;
    return;
  }

  expandedProductId.value = productId;

  if (variantesCache.value[productId]) {
    return;
  }

  loadingVariantes.value = true;
  try {
    const res = await fetch(`/admin/catalogo/${productId}/variantes`);
    variantesCache.value[productId] = await res.json();
  } catch (e) {
    console.error('No se pudieron cargar las variantes', e);
    variantesCache.value[productId] = [];
  } finally {
    loadingVariantes.value = false;
  }
};
</script>
