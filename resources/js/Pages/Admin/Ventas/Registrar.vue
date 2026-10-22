<template>
  <Head title="Registrar Venta" />
  <AdminLayout>
    <template #breadcrumb>Ventas / Registrar</template>
    <template #header>Registrar Nueva Venta</template>

    <div class="space-y-6">
      <!-- Tabs -->
      <div class="flex gap-2 border-b border-gray-200">
        <Link
          :href="route('admin.sales.create')"
          class="px-4 py-2.5 text-sm font-semibold text-[#ff8c42] border-b-2 border-[#ff8c42]"
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
          class="px-4 py-2.5 text-sm font-semibold text-gray-400 hover:text-gray-600 border-b-2 border-transparent"
        >
          Reportes de Ventas
        </Link>
      </div>

      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center justify-between gap-3 flex-wrap">
        <span class="flex items-center gap-2">
          <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
        </span>
        <div v-if="page.props.flash?.ventaId" class="flex items-center gap-2">
          <Link
            :href="`${route('admin.sales.index')}?venta=${page.props.flash.ventaId}`"
            class="text-xs font-semibold text-green-700 hover:text-green-800 bg-white border border-green-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5"
          >
            <i class="fa-solid fa-receipt text-[11px]"></i> Ver en Historial
          </Link>
          <a
            :href="route('admin.sales.boleta', page.props.flash.ventaId)"
            target="_blank"
            class="text-xs font-semibold text-white bg-green-600 hover:bg-green-700 px-3 py-1.5 rounded-lg flex items-center gap-1.5"
          >
            <i class="fa-solid fa-print text-[11px]"></i> Imprimir Boleta
          </a>
        </div>
      </div>

      <p class="text-sm text-gray-400">Solo puedes vender prendas que ya tengan stock registrado</p>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- IZQUIERDA: búsqueda y detalles -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Detalles de la Venta</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label for="registrar-1" class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                <input id="registrar-1" v-model="ventaForm.fecha" type="date" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]">
              </div>
              <div class="relative">
                <label for="venta-cliente" class="block text-sm font-medium text-gray-700 mb-2">Cliente (opcional)</label>

                <div v-if="clienteSeleccionado" class="w-full px-4 py-2 border border-green-300 bg-green-50 rounded-lg flex items-center justify-between">
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ clienteSeleccionado.name }}</p>
                    <p class="text-xs text-green-600 truncate">{{ clienteSeleccionado.email }}</p>
                  </div>
                  <button type="button" @click="limpiarCliente" class="text-gray-400 hover:text-gray-600 flex-shrink-0 ml-2">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>

                <template v-else>
                  <input id="venta-cliente"
                    v-model="ventaForm.cliente"
                    @input="onClienteInput"
                    @focus="showClienteSuggestions = true"
                    @blur="ocultarSugerenciasClienteConRetraso"
                    type="text"
                    placeholder="Nombre (o busca uno registrado)"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
                  >
                  <div
                    v-if="showClienteSuggestions && clienteResultados.length > 0"
                    class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                  >
                    <button
                      v-for="c in clienteResultados"
                      :key="c.id"
                      type="button"
                      @mousedown.prevent="seleccionarCliente(c)"
                      class="w-full text-left px-4 py-2 hover:bg-gray-50 border-b border-gray-50 last:border-0"
                    >
                      <p class="text-sm font-medium text-gray-900 truncate">{{ c.name }}</p>
                      <p class="text-xs text-gray-400 truncate">{{ c.email }}</p>
                    </button>
                  </div>
                </template>
              </div>
              <div>
                <label for="registrar-2" class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
                <input id="registrar-2" v-model="ventaForm.notas" type="text" placeholder="Cualquier detalle importante..." class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]">
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Buscar Prenda</h3>

            <div class="flex flex-wrap items-center gap-3 mb-5">
              <SelectDropdown v-model="filtroCategoria" :options="opcionesCategoria" class="min-w-[190px]" />
              <SearchInput
                v-model="searchQuery"
                placeholder="Buscar por modelo o marca..."
                class="flex-1 min-w-[220px]"
              />
            </div>

            <div v-if="isSearching" class="text-sm text-gray-400 py-6 text-center">Buscando...</div>

            <div v-else-if="searchResults.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <button
                v-for="v in searchResults"
                :key="v.variantId"
                type="button"
                @click="seleccionarVariante(v)"
                :class="[
                  'text-left px-4 py-3 rounded-xl border-2 transition-colors flex items-center gap-3',
                  variantSeleccionada?.variantId === v.variantId ? 'border-[#ff8c42] bg-orange-50' : 'border-gray-100 hover:border-gray-300',
                ]"
              >
                <ColorSwatch :hex="v.colorHex" class="w-6 h-6 rounded-full border border-gray-200 flex-shrink-0" />
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-semibold text-gray-900 truncate">{{ v.producto }}</p>
                  <p class="text-xs text-gray-400">{{ v.marca }} · Talla {{ v.talla }} · {{ v.color }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                  <p class="text-sm font-bold text-gray-900">S/ {{ v.precioVenta.toFixed(2) }}</p>
                  <p class="text-[11px] text-gray-400">Stock: {{ v.stock }}</p>
                </div>
              </button>
            </div>

            <div v-else-if="busquedaRealizada" class="text-sm text-gray-400 py-6 text-center">
              No hay prendas con stock disponible para esta búsqueda.
            </div>

            <div v-else class="text-sm text-gray-400 py-6 text-center">
              Elige una categoría o escribe un modelo/marca para ver el stock disponible.
            </div>

            <!-- Cantidad y precio de la prenda seleccionada -->
            <div v-if="variantSeleccionada" class="mt-5 pt-5 border-t border-gray-100">
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                  <ColorSwatch :hex="variantSeleccionada.colorHex" class="w-5 h-5 rounded-full border border-gray-200" />
                  <p class="text-sm font-semibold text-gray-900">{{ variantSeleccionada.producto }} · {{ variantSeleccionada.talla }} · {{ variantSeleccionada.color }}</p>
                </div>
                <span class="text-xs text-gray-400">Disponible: {{ stockDisponibleSeleccion }}</span>
              </div>

              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label for="registrar-3" class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                  <input id="registrar-3" v-model.number="cantidad" type="number" min="1" :max="stockDisponibleSeleccion || 1" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]">
                </div>
                <div>
                  <label for="registrar-4" class="block text-sm font-medium text-gray-700 mb-2">Precio de Venta</label>
                  <input id="registrar-4" v-model.number="precioVenta" type="number" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]">
                </div>
              </div>

              <button
                @click="agregarPrenda"
                type="button"
                class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg text-white font-bold text-sm py-2.5 px-6 rounded-xl transition-all duration-200 shadow-sm"
              >
                <i class="fa-solid fa-cart-plus"></i> Agregar al Ticket
              </button>
            </div>
          </div>
        </div>

        <!-- DERECHA: ticket -->
        <div class="lg:sticky lg:top-6">
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
              <i class="fa-solid fa-receipt text-[#ff8c42]"></i>
              <h3 class="text-lg font-bold text-gray-900">Ticket</h3>
            </div>

            <div v-if="prendas.length === 0" class="px-6 py-10 text-center text-sm text-gray-400">
              Agrega prendas para armar la venta.
            </div>

            <div v-else class="divide-y divide-gray-50 max-h-[420px] overflow-y-auto">
              <div v-for="prenda in prendas" :key="prenda.id" class="px-6 py-3 flex items-center gap-3">
                <ColorSwatch :hex="prenda.colorHex" class="w-5 h-5 rounded-full border border-gray-200 flex-shrink-0" />
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ prenda.producto }}</p>
                  <p class="text-xs text-gray-400">{{ prenda.talla }} · {{ prenda.color }} · {{ prenda.cantidad }} x S/ {{ parseFloat(prenda.precioVenta).toFixed(2) }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                  <p class="text-sm font-bold text-gray-900">S/ {{ prenda.subtotal.toFixed(2) }}</p>
                  <button @click="eliminarPrenda(prenda.id)" type="button" class="text-xs text-red-500 hover:text-red-700">
                    Quitar
                  </button>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 space-y-3">
              <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Prendas</span>
                <span class="font-semibold text-gray-900">{{ totalPrendas() }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Total</span>
                <span class="text-2xl font-black text-[#ff8c42]">S/ {{ totalVenta().toFixed(2) }}</span>
              </div>

              <button
                @click="guardarVenta"
                :disabled="isSaving || prendas.length === 0"
                class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm py-3 px-6 rounded-xl transition-all duration-200 shadow-sm"
              >
                <i class="fa-solid fa-save"></i> {{ isSaving ? 'Guardando...' : 'Guardar Venta' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';

const props = defineProps({
  categorias: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();

// OJO: no usar Date().toISOString() para "hoy" — toISOString() siempre convierte a UTC,
// así que de 7pm a 12am hora Perú (UTC-5) ya cuenta como "mañana" en UTC y la venta se
// guarda con la fecha equivocada. Se arma la fecha con los componentes locales del navegador.
const hoyLocal = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const ventaForm = ref({
  fecha: hoyLocal(),
  cliente: '',
  notas: '',
});

const clienteSeleccionado = ref(null);
const clienteResultados = ref([]);
const showClienteSuggestions = ref(false);
let clienteSearchTimer = null;

const buscarClientes = () => {
  clearTimeout(clienteSearchTimer);
  const q = ventaForm.value.cliente.trim();

  if (q.length < 2) {
    clienteResultados.value = [];
    return;
  }

  clienteSearchTimer = setTimeout(async () => {
    try {
      const res = await fetch(`/admin/ventas/buscar-clientes?q=${encodeURIComponent(q)}`);
      clienteResultados.value = await res.json();
    } catch (e) {
      console.error('Falló la búsqueda de clientes', e);
      clienteResultados.value = [];
    }
  }, 300);
};

const onClienteInput = () => {
  clienteSeleccionado.value = null;
  showClienteSuggestions.value = true;
  buscarClientes();
};

const seleccionarCliente = (c) => {
  clienteSeleccionado.value = c;
  ventaForm.value.cliente = c.name;
  showClienteSuggestions.value = false;
};

const limpiarCliente = () => {
  clienteSeleccionado.value = null;
  ventaForm.value.cliente = '';
  clienteResultados.value = [];
};

const ocultarSugerenciasClienteConRetraso = () => {
  setTimeout(() => { showClienteSuggestions.value = false; }, 150);
};

const filtroCategoria = ref('');

const opcionesCategoria = computed(() => [
  { value: '', label: 'Todas las categorías' },
  ...props.categorias.map(c => ({ value: c, label: c })),
]);

const variantSeleccionada = ref(null);
const cantidad = ref(1);
const precioVenta = ref('');

const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const busquedaRealizada = ref(false);
let searchTimer = null;

const isSaving = ref(false);
const prendas = ref([]);

const ejecutarBusqueda = () => {
  clearTimeout(searchTimer);

  const q = searchQuery.value.trim();
  const cat = filtroCategoria.value;

  if (q.length < 2 && !cat) {
    searchResults.value = [];
    busquedaRealizada.value = false;
    return;
  }

  searchTimer = setTimeout(async () => {
    isSearching.value = true;
    try {
      const params = new URLSearchParams();
      if (q) params.set('q', q);
      if (cat) params.set('categoria', cat);

      const res = await fetch(`/admin/ventas/buscar-variantes?${params.toString()}`);
      searchResults.value = await res.json();
    } catch (e) {
      console.error('Falló la búsqueda de variantes', e);
      searchResults.value = [];
    } finally {
      isSearching.value = false;
      busquedaRealizada.value = true;
    }
  }, 300);
};

watch(filtroCategoria, ejecutarBusqueda);
watch(searchQuery, () => {
  variantSeleccionada.value = null;
  ejecutarBusqueda();
});

// Cuánto de esta variante ya se agregó al ticket, para no exceder el stock real.
const cantidadYaAgregada = (variantId) =>
  prendas.value.filter(p => p.variantId === variantId).reduce((sum, p) => sum + p.cantidad, 0);

const stockDisponibleSeleccion = computed(() => {
  if (!variantSeleccionada.value) return 0;
  return variantSeleccionada.value.stock - cantidadYaAgregada(variantSeleccionada.value.variantId);
});

const seleccionarVariante = (v) => {
  variantSeleccionada.value = v;
  precioVenta.value = v.precioVenta;
  cantidad.value = 1;
};

const agregarPrenda = () => {
  if (!variantSeleccionada.value) return;

  if (!cantidad.value || cantidad.value < 1 || precioVenta.value === '' || precioVenta.value == null || precioVenta.value < 0) {
    alert('Completa la cantidad y el precio de venta.');
    return;
  }

  if (cantidad.value > stockDisponibleSeleccion.value) {
    alert(`Solo quedan ${stockDisponibleSeleccion.value} unidades disponibles de esta prenda.`);
    return;
  }

  prendas.value.push({
    id: Date.now(),
    variantId: variantSeleccionada.value.variantId,
    producto: variantSeleccionada.value.producto,
    marca: variantSeleccionada.value.marca,
    talla: variantSeleccionada.value.talla,
    color: variantSeleccionada.value.color,
    colorHex: variantSeleccionada.value.colorHex,
    cantidad: cantidad.value,
    precioVenta: precioVenta.value,
    subtotal: cantidad.value * precioVenta.value,
  });

  variantSeleccionada.value = null;
  cantidad.value = 1;
  precioVenta.value = '';
};

const eliminarPrenda = (id) => {
  prendas.value = prendas.value.filter(p => p.id !== id);
};

const totalVenta = () => prendas.value.reduce((sum, p) => sum + (p.cantidad * p.precioVenta), 0);
const totalPrendas = () => prendas.value.reduce((sum, p) => sum + p.cantidad, 0);

const guardarVenta = () => {
  if (prendas.value.length === 0) return;

  isSaving.value = true;

  router.post(route('admin.sales.store'), {
    fecha: ventaForm.value.fecha,
    cliente: ventaForm.value.cliente,
    clienteId: clienteSeleccionado.value?.id ?? null,
    notas: ventaForm.value.notas,
    prendas: prendas.value.map(p => ({
      variantId: p.variantId,
      cantidad: p.cantidad,
      precioVenta: p.precioVenta,
    })),
  }, {
    onSuccess: () => {
      // Deja la fecha tal cual (para seguir registrando ventas del mismo día) y limpia
      // el resto, listo para la siguiente venta sin salir de esta página.
      ventaForm.value.cliente = '';
      ventaForm.value.notas = '';
      clienteSeleccionado.value = null;
      clienteResultados.value = [];
      prendas.value = [];
      variantSeleccionada.value = null;
      searchQuery.value = '';
      searchResults.value = [];
      busquedaRealizada.value = false;
    },
    onError: (errors) => {
      const primerError = Object.values(errors)[0];
      alert(primerError || 'Ocurrió un error al guardar la venta. Revisa los datos e intenta de nuevo.');
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};
</script>
