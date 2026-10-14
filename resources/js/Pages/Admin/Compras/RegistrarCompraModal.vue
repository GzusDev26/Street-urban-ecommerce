<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import SelectDropdown from '@/Components/Admin/SelectDropdown.vue';
import SearchCreateInput from '@/Components/Admin/SearchCreateInput.vue';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';

const props = defineProps({
  categoriasExistentes: {
    type: Array,
    default: () => [],
  },
  marcasExistentes: {
    type: Array,
    default: () => [],
  },
  prefill: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

// OJO: no usar Date().toISOString() para "hoy" — toISOString() siempre convierte a UTC,
// así que de 7pm a 12am hora Perú (UTC-5) ya cuenta como "mañana" en UTC y la compra se
// guarda con la fecha equivocada. Se arma la fecha con los componentes locales del navegador.
const hoyLocal = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const compraForm = ref({
  fecha: hoyLocal(),
  tienda: '',
  factura: '',
  notas: '',
});

const prendaForm = ref({
  productId: null,
  nombre: '',
  categoria: '',
  marca: '',
  talla: '',
  color: null,
  colorNombre: '',
  cantidad: 1,
  precioCompra: '',
  precioVenta: '',
});

// Colores ya registrados para el modelo seleccionado (para no crear "Negro" duplicado
// cuando ya existe "Negro con blanco", por ejemplo).
const coloresProducto = ref([]);
const cargandoColoresProducto = ref(false);
const mostrarNuevoColor = ref(false);

const cargarColoresProducto = async () => {
  if (!prendaForm.value.productId) {
    coloresProducto.value = [];
    return;
  }
  cargandoColoresProducto.value = true;
  try {
    const res = await fetch(`/admin/catalogo/${prendaForm.value.productId}/colores`);
    coloresProducto.value = await res.json();
  } catch (e) {
    console.error('No se pudieron cargar los colores del producto', e);
    coloresProducto.value = [];
  } finally {
    cargandoColoresProducto.value = false;
  }
};

const isSaving = ref(false);
const prendas = ref([]);

const searchQueryInicial = props.prefill?.nombre || '';

onMounted(() => {
  if (props.prefill) {
    prendaForm.value.productId = props.prefill.productId;
    prendaForm.value.nombre = props.prefill.nombre;
    prendaForm.value.marca = props.prefill.marca;
    prendaForm.value.categoria = props.prefill.categoria;
    prendaForm.value.talla = props.prefill.talla;
    prendaForm.value.color = props.prefill.color;
    prendaForm.value.colorNombre = props.prefill.colorNombre || '';
    prendaForm.value.precioCompra = props.prefill.precioCompra || '';
    prendaForm.value.precioVenta = props.prefill.precioVenta || '';
    // Sugerencia del último proveedor de esta variante — el usuario la puede cambiar
    // si esta vez la está comprando en otro lado.
    compraForm.value.tienda = props.prefill.tienda || '';
    cargarColoresProducto();
  }
});

const capitalizarPrimeraLetra = (str) => (str.length > 0 ? str.charAt(0).toUpperCase() + str.slice(1) : str);

const tiendaCapitalizada = computed({
  get: () => compraForm.value.tienda,
  set: (val) => { compraForm.value.tienda = capitalizarPrimeraLetra(val); },
});

const categoriaCapitalizada = computed({
  get: () => prendaForm.value.categoria,
  set: (val) => { prendaForm.value.categoria = capitalizarPrimeraLetra(val); },
});

const marcaCapitalizada = computed({
  get: () => prendaForm.value.marca,
  set: (val) => { prendaForm.value.marca = capitalizarPrimeraLetra(val); },
});

const searchQuery = ref(searchQueryInicial);
const searchResults = ref([]);
const showSuggestions = ref(false);
const isSearching = ref(false);
let searchTimer = null;

const ocultarSugerenciasConRetraso = () => {
  setTimeout(() => {
    showSuggestions.value = false;
  }, 150);
};

const ejecutarBusqueda = () => {
  clearTimeout(searchTimer);

  const q = searchQuery.value.trim();
  const cat = prendaForm.value.categoria.trim();
  const marca = prendaForm.value.marca.trim();

  // Sin texto y sin categoría/marca elegidas todavía: nada que sugerir.
  if (q.length < 2 && !cat && !marca) {
    searchResults.value = [];
    return;
  }

  searchTimer = setTimeout(async () => {
    isSearching.value = true;
    try {
      const params = new URLSearchParams();
      if (q) params.set('q', q);
      if (cat) params.set('categoria', cat);
      if (marca) params.set('marca', marca);

      const res = await fetch(`/admin/compras/buscar-productos?${params.toString()}`);
      searchResults.value = await res.json();
    } catch (e) {
      console.error('Falló la búsqueda de productos', e);
      searchResults.value = [];
    } finally {
      isSearching.value = false;
    }
  }, 300);
};

const buscarProductos = () => {
  showSuggestions.value = true;
  ejecutarBusqueda();
};

// Al elegir categoría y/o marca, refresca en silencio los modelos sugeridos
// (sin abrir el dropdown hasta que el usuario toque el campo Modelo).
watch(() => [prendaForm.value.categoria, prendaForm.value.marca], () => {
  if (!prendaForm.value.productId) {
    ejecutarBusqueda();
  }
});

const seleccionarProducto = (producto) => {
  prendaForm.value.productId = producto.id;
  prendaForm.value.nombre = producto.nombre;
  prendaForm.value.marca = producto.marca || '';
  prendaForm.value.categoria = producto.categoria || '';
  searchQuery.value = producto.nombre;
  showSuggestions.value = false;
  mostrarNuevoColor.value = false;
  cargarColoresProducto();
};

const limpiarSeleccion = () => {
  prendaForm.value.productId = null;
  prendaForm.value.nombre = '';
  prendaForm.value.marca = '';
  prendaForm.value.categoria = '';
  searchQuery.value = '';
  searchResults.value = [];
  coloresProducto.value = [];
  mostrarNuevoColor.value = false;
};

const onSearchInput = () => {
  searchQuery.value = capitalizarPrimeraLetra(searchQuery.value);
  prendaForm.value.productId = null;
  prendaForm.value.nombre = searchQuery.value;
  buscarProductos();
};

const categorias = computed(() => [...props.categoriasExistentes].sort());

// La marca es independiente de la categoría (una marca puede vender en varias categorías),
// así que siempre se muestran todas las marcas registradas.
const marcas = computed(() => [...props.marcasExistentes].sort());

const tallas = ['Único', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', '38', '40'];
const opcionesTalla = tallas.map(t => ({ value: t, label: t }));

// Paleta de ayuda solo para cuando se está creando un color realmente nuevo (no existe
// todavía para este modelo). No se usa para nombrar colores existentes.
const paletaColores = [
  { nombre: 'Sin color', hex: null },
  { nombre: 'Rojo', hex: '#EF4444' },
  { nombre: 'Azul', hex: '#3B82F6' },
  { nombre: 'Negro', hex: '#1F2937' },
  { nombre: 'Blanco', hex: '#F3F4F6' },
  { nombre: 'Verde', hex: '#10B981' },
  { nombre: 'Naranja', hex: '#ff8c42' },
  { nombre: 'Gris', hex: '#9CA3AF' },
  { nombre: 'Amarillo', hex: '#FBBF24' },
  { nombre: 'Púrpura', hex: '#A855F7' },
  { nombre: 'Rosa', hex: '#EC4899' },
];

const seleccionarColorExistente = (c) => {
  prendaForm.value.colorNombre = c.nombre;
  prendaForm.value.color = c.hex;
};

const seleccionarColorPaleta = (p) => {
  prendaForm.value.color = p.hex;
  prendaForm.value.colorNombre = p.nombre;
};

const agregarPrenda = () => {
  const faltanCampos = !prendaForm.value.nombre
    || !prendaForm.value.categoria
    || !prendaForm.value.marca
    || !prendaForm.value.talla
    || prendaForm.value.cantidad === '' || prendaForm.value.cantidad == null || prendaForm.value.cantidad < 1
    || prendaForm.value.precioCompra === '' || prendaForm.value.precioCompra == null || prendaForm.value.precioCompra < 0
    || prendaForm.value.precioVenta === '' || prendaForm.value.precioVenta == null || prendaForm.value.precioVenta < 0;

  if (faltanCampos) {
    alert('Por favor completa todos los campos');
    return;
  }

  prendas.value.push({
    id: Date.now(),
    ...prendaForm.value,
    colorNombre: prendaForm.value.colorNombre.trim() || 'Sin color',
    subtotal: prendaForm.value.cantidad * prendaForm.value.precioCompra,
  });

  // Mantiene el producto (modelo/categoría/marca/precios) para agregar otra talla/color rápido.
  // Solo resetea lo que suele cambiar entre variantes.
  prendaForm.value.talla = '';
  prendaForm.value.color = null;
  prendaForm.value.colorNombre = '';
  prendaForm.value.cantidad = 1;
  mostrarNuevoColor.value = false;
};

const limpiarFormularioPrenda = () => {
  prendaForm.value = {
    productId: null,
    nombre: '',
    categoria: '',
    marca: '',
    talla: '',
    color: null,
    colorNombre: '',
    cantidad: 1,
    precioCompra: '',
    precioVenta: '',
  };
  searchQuery.value = '';
  searchResults.value = [];
  coloresProducto.value = [];
  mostrarNuevoColor.value = false;
};

const eliminarPrenda = (id) => {
  prendas.value = prendas.value.filter(p => p.id !== id);
};

const editarPrenda = (id) => {
  const prenda = prendas.value.find(p => p.id === id);
  if (prenda) {
    prendaForm.value = { ...prenda };
    searchQuery.value = prenda.nombre;
    eliminarPrenda(id);
    cargarColoresProducto();
  }
};

const totalCompra = () => prendas.value.reduce((sum, p) => sum + (p.cantidad * p.precioCompra), 0);
const totalPrendas = () => prendas.value.reduce((sum, p) => sum + p.cantidad, 0);

const guardarCompra = () => {
  if (prendas.value.length === 0) {
    alert('Agrega al menos una prenda');
    return;
  }

  isSaving.value = true;

  router.post(route('admin.purchases.store'), {
    fecha: compraForm.value.fecha,
    tienda: compraForm.value.tienda,
    factura: compraForm.value.factura,
    notas: compraForm.value.notas,
    prendas: prendas.value,
  }, {
    onSuccess: () => {
      emit('close');
    },
    onError: (errors) => {
      const primerError = Object.values(errors)[0];
      alert(primerError || 'Ocurrió un error al guardar la compra. Revisa los datos e intenta de nuevo.');
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};
</script>

<template>
  <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="emit('close')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[92vh] overflow-y-auto">
      <!-- Modal header -->
      <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between z-10">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Registrar Nueva Compra</h2>
          <p class="text-xs text-gray-400">Ingresa los detalles de tu compra mayorista</p>
        </div>
        <button @click="emit('close')" class="p-2 hover:bg-gray-100 rounded-lg transition">
          <i class="fa-solid fa-xmark text-gray-500"></i>
        </button>
      </div>

      <div class="p-6 space-y-6">
        <!-- DETALLES DE LA COMPRA -->
        <div class="bg-gray-50 rounded-2xl border border-gray-100 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-6">Detalles de la Compra</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
              <label for="registrar-compra-modal-1" class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
              <input id="registrar-compra-modal-1" v-model="compraForm.fecha" type="date" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
            <div>
              <label for="registrar-compra-modal-2" class="block text-sm font-medium text-gray-700 mb-2">Tienda/Proveedor</label>
              <input id="registrar-compra-modal-2" v-model="tiendaCapitalizada" type="text" placeholder="Ej: Centro Comercial Jockey" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
            <div>
              <label for="registrar-compra-modal-3" class="block text-sm font-medium text-gray-700 mb-2">Factura # (opcional)</label>
              <input id="registrar-compra-modal-3" v-model="compraForm.factura" type="text" placeholder="F-001234" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
            <div>
              <label for="registrar-compra-modal-4" class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
              <input id="registrar-compra-modal-4" v-model="compraForm.notas" type="text" placeholder="Cualquier detalle importante..." class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
          </div>
        </div>

        <!-- AGREGAR PRENDA -->
        <div class="bg-gray-50 rounded-2xl border border-gray-100 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-6">Agregar Prenda</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div>
              <label for="compra-categoria" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
              <SearchCreateInput id="compra-categoria"
                v-model="categoriaCapitalizada"
                :options="categorias"
                :disabled="!!prendaForm.productId"
                placeholder="Elige o escribe una nueva..."
              />
            </div>
            <div>
              <label for="compra-marca" class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
              <SearchCreateInput id="compra-marca"
                v-model="marcaCapitalizada"
                :options="marcas"
                :disabled="!!prendaForm.productId"
                placeholder="Nike, Adidas, Gap..."
              />
            </div>

            <div class="relative lg:col-span-2">
              <label for="compra-modelo" class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>

              <div v-if="prendaForm.productId" class="w-full px-4 py-2 border border-green-300 bg-green-50 rounded-lg flex items-center justify-between">
                <div class="min-w-0">
                  <p class="font-medium text-gray-900 truncate">{{ prendaForm.nombre }}</p>
                  <p class="text-xs text-green-600 flex items-center gap-1">
                    <i class="fa-solid fa-circle-check"></i> Modelo existente, se sumará el stock
                  </p>
                </div>
                <button type="button" @click="limpiarSeleccion" class="text-gray-400 hover:text-gray-600 flex-shrink-0 ml-2">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>

              <template v-else>
                <input id="compra-modelo"
                  v-model="searchQuery"
                  @input="onSearchInput"
                  @focus="showSuggestions = true"
                  @blur="ocultarSugerenciasConRetraso"
                  type="text"
                  placeholder="Ej: Stan Smith, Lancaster, Dri-Fit... (buscar o escribir nuevo)"
                  class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white"
                >
                <div
                  v-if="showSuggestions && (searchQuery.trim().length >= 2 || prendaForm.categoria || prendaForm.marca)"
                  class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto"
                >
                  <div v-if="isSearching" class="px-4 py-3 text-sm text-gray-400">Buscando...</div>
                  <template v-else>
                    <button
                      v-for="p in searchResults"
                      :key="p.id"
                      type="button"
                      @mousedown.prevent="seleccionarProducto(p)"
                      class="w-full text-left px-4 py-2.5 hover:bg-gray-50 border-b border-gray-50 last:border-0"
                    >
                      <p class="text-sm font-medium text-gray-900">{{ p.nombre }}</p>
                      <p class="text-xs text-gray-400">{{ p.marca }} · {{ p.categoria || 'Sin categoría' }} · Stock: {{ p.stock }}</p>
                    </button>
                    <div v-if="searchResults.length === 0 && searchQuery.trim().length >= 2" class="px-4 py-3 text-xs text-gray-400">
                      No existe, se creará el modelo <strong>"{{ searchQuery }}"</strong> nuevo
                    </div>
                    <div v-else-if="searchResults.length === 0" class="px-4 py-3 text-xs text-gray-400">
                      Aún no hay modelos registrados para esta combinación. Escribe el nombre para crear uno nuevo.
                    </div>
                  </template>
                </div>
              </template>
            </div>

            <div>
              <span class="block text-sm font-medium text-gray-700 mb-2">Talla</span>
              <SelectDropdown v-model="prendaForm.talla" :options="opcionesTalla" placeholder="Selecciona..." />
            </div>
          </div>

          <div class="mb-6">
            <span class="block text-sm font-medium text-gray-700 mb-2">Color</span>

            <!-- Colores ya registrados para este modelo: reusarlos evita crear duplicados
                 como "Negro" cuando ya existe "Negro con blanco". -->
            <div v-if="coloresProducto.length > 0 && !mostrarNuevoColor">
              <div class="flex gap-2 flex-wrap items-center">
                <button v-for="c in coloresProducto" :key="c.id"
                  @click="seleccionarColorExistente(c)"
                  type="button"
                  :class="['flex items-center gap-2 pl-1.5 pr-3 py-1.5 rounded-full border-2 transition-all',
                           prendaForm.colorNombre === c.nombre ? 'border-gray-900 ring-2 ring-offset-1 ring-[#ff8c42]' : 'border-gray-200 hover:border-gray-400']">
                  <span class="w-6 h-6 rounded-full border border-gray-200 overflow-hidden flex-shrink-0 inline-block">
                    <img v-if="c.imagenUrl" :src="c.imagenUrl" :alt="c.nombre" class="w-full h-full object-cover">
                    <ColorSwatch v-else :hex="c.hex" class="w-full h-full" />
                  </span>
                  <span class="text-sm font-medium text-gray-700">{{ c.nombre }}</span>
                </button>
                <button type="button" @click="mostrarNuevoColor = true" class="text-sm font-semibold text-[#ff8c42] hover:underline px-2 py-1.5">
                  + Otro color
                </button>
              </div>
            </div>

            <div v-else>
              <button v-if="coloresProducto.length > 0" type="button" @click="mostrarNuevoColor = false" class="text-xs text-gray-400 hover:text-gray-600 mb-2 flex items-center gap-1">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Ver colores ya registrados
              </button>
              <input aria-label="Nombre del color"
                v-model="prendaForm.colorNombre"
                type="text"
                placeholder="Nombre del color, ej: Negro con blanco"
                class="w-full mb-2 px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white"
              >
              <div class="flex gap-2 flex-wrap">
                <button v-for="color in paletaColores" :key="color.nombre"
                  @click="seleccionarColorPaleta(color)"
                  type="button"
                  :class="['w-9 h-9 rounded-full border-2 transition-all flex items-center justify-center',
                           prendaForm.color === color.hex ? 'border-gray-900 ring-2 ring-offset-2 ring-[#ff8c42]' : 'border-gray-200 hover:border-gray-400',
                           color.hex === null ? 'bg-white' : '']"
                  :style="color.hex ? { backgroundColor: color.hex } : {}"
                  :title="color.nombre">
                  <i v-if="color.hex === null" class="fa-solid fa-ban text-gray-300 text-sm"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div>
              <label for="registrar-compra-modal-5" class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
              <input id="registrar-compra-modal-5" v-model.number="prendaForm.cantidad" type="number" min="1" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
            <div>
              <label for="registrar-compra-modal-6" class="block text-sm font-medium text-gray-700 mb-2">Precio Compra</label>
              <input id="registrar-compra-modal-6" v-model.number="prendaForm.precioCompra" type="number" step="0.01" min="0" placeholder="S/ 0.00" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
            <div>
              <label for="registrar-compra-modal-7" class="block text-sm font-medium text-gray-700 mb-2">Precio Venta</label>
              <input id="registrar-compra-modal-7" v-model.number="prendaForm.precioVenta" type="number" step="0.01" min="0" placeholder="S/ 0.00" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
            <div>
              <span class="block text-sm font-medium text-gray-700 mb-2">Margen</span>
              <div
                :class="[
                  'px-4 py-2 rounded-lg flex items-center justify-center text-sm font-bold border',
                  !prendaForm.precioCompra || !prendaForm.precioVenta ? 'bg-white border-gray-200 text-gray-400' :
                  (prendaForm.precioVenta - prendaForm.precioCompra) > 0 ? 'bg-green-50 border-green-200 text-green-600' : 'bg-red-50 border-red-200 text-red-500',
                ]"
              >
                {{ prendaForm.precioCompra ? Math.round(((prendaForm.precioVenta - prendaForm.precioCompra) / prendaForm.precioCompra * 100) || 0) + '%' : '—' }}
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3">
            <button
              @click="limpiarFormularioPrenda"
              type="button"
              class="flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-gray-700 px-4 py-2.5 rounded-xl hover:bg-gray-100 transition-colors"
            >
              <i class="fa-solid fa-broom text-xs"></i> Limpiar
            </button>
            <button
              @click="agregarPrenda"
              type="button"
              class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 text-white font-bold text-sm py-2.5 px-6 rounded-xl transition-all duration-200 shadow-sm"
            >
              <i class="fa-solid fa-plus"></i> Agregar Prenda
            </button>
          </div>
        </div>

        <!-- PRENDAS AGREGADAS -->
        <div v-if="prendas.length > 0" class="bg-gray-50 rounded-2xl border border-gray-100 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-6">Prendas Agregadas ({{ totalPrendas() }})</h3>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200">
                <tr>
                  <th class="text-left py-3 px-4 font-semibold text-gray-500">Categoría</th>
                  <th class="text-left py-3 px-4 font-semibold text-gray-500">Marca</th>
                  <th class="text-left py-3 px-4 font-semibold text-gray-500">Modelo</th>
                  <th class="text-left py-3 px-4 font-semibold text-gray-500">Talla</th>
                  <th class="text-left py-3 px-4 font-semibold text-gray-500">Color</th>
                  <th class="text-center py-3 px-4 font-semibold text-gray-500">Cant</th>
                  <th class="text-right py-3 px-4 font-semibold text-gray-500">P.Compra</th>
                  <th class="text-right py-3 px-4 font-semibold text-gray-500">P.Venta</th>
                  <th class="text-right py-3 px-4 font-semibold text-gray-500">Subtotal</th>
                  <th class="text-center py-3 px-4 font-semibold text-gray-500">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="prenda in prendas" :key="prenda.id" class="border-b border-gray-100 hover:bg-white transition-colors">
                  <td class="py-3 px-4 text-gray-900">{{ prenda.categoria }}</td>
                  <td class="py-3 px-4 text-gray-900 font-medium">{{ prenda.marca }}</td>
                  <td class="py-3 px-4 text-gray-900 font-medium">{{ prenda.nombre }}</td>
                  <td class="py-3 px-4 text-gray-600">{{ prenda.talla }}</td>
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-2">
                      <ColorSwatch :hex="prenda.color" class="w-6 h-6 rounded-full border border-gray-200" />
                      <span class="text-gray-600">{{ prenda.colorNombre }}</span>
                    </div>
                  </td>
                  <td class="py-3 px-4 text-center text-gray-900 font-semibold">{{ prenda.cantidad }}</td>
                  <td class="py-3 px-4 text-right text-gray-900">S/ {{ parseFloat(prenda.precioCompra).toFixed(2) }}</td>
                  <td class="py-3 px-4 text-right text-gray-900">S/ {{ parseFloat(prenda.precioVenta).toFixed(2) }}</td>
                  <td class="py-3 px-4 text-right text-gray-900 font-bold">S/ {{ prenda.subtotal.toFixed(2) }}</td>
                  <td class="py-3 px-4 text-center space-x-2 flex justify-center">
                    <button @click="editarPrenda(prenda.id)" type="button" class="text-blue-600 hover:text-blue-800" title="Editar">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    <button @click="eliminarPrenda(prenda.id)" type="button" class="text-red-600 hover:text-red-800" title="Eliminar">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-6 p-4 bg-gradient-to-r from-orange-50 to-amber-50 rounded-lg border border-orange-200">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-gray-600">Total Prendas</p>
                <p class="text-2xl font-bold text-gray-900">{{ totalPrendas() }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Total Compra</p>
                <p class="text-2xl font-bold text-[#ff8c42]">S/ {{ totalCompra().toFixed(2) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Utilidad Aprox.</p>
                <p class="text-2xl font-bold text-green-600">
                  S/ {{ (prendas.reduce((sum, p) => sum + (p.cantidad * (p.precioVenta - p.precioCompra)), 0)).toFixed(2) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer actions -->
      <div class="sticky bottom-0 bg-white border-t border-gray-100 px-6 py-4 flex justify-end gap-3">
        <button
          @click="emit('close')"
          :disabled="isSaving"
          class="text-sm font-semibold text-gray-500 hover:text-gray-700 disabled:opacity-50 px-5 py-3 rounded-xl hover:bg-gray-100 transition-colors"
        >
          Cancelar
        </button>
        <button
          @click="guardarCompra"
          :disabled="isSaving || prendas.length === 0"
          class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:hover:translate-y-0 disabled:cursor-not-allowed text-white font-bold text-sm py-3 px-8 rounded-xl transition-all duration-200 shadow-sm"
        >
          <i class="fa-solid fa-save"></i> {{ isSaving ? 'Guardando...' : 'Guardar Compra' }}
        </button>
      </div>
    </div>
  </div>
</template>
