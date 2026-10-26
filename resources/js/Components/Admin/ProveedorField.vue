<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';

// Proveedor de una compra: opcional ("Sin proveedor" por defecto). Se elige uno ya registrado escribiendo su
// nombre o RUC; con un RUC (11) o DNI (8) nuevo se consulta SUNAT/RENIEC para traer el nombre; o se escribe un
// nombre a mano. El proveedor nuevo se crea recién al guardar la compra.
// Valor: { id, nombre, tipo, numero, nuevo } o null.
const modelo = defineModel({ type: Object, default: null });

// Lo escrito en el buscador; el padre lo lee para avisar si se intenta guardar sin elegir.
const texto = defineModel('texto', { type: String, default: '' });
const abierto = ref(false);
const registrados = ref([]);
const consulta = ref(null); // { cargando, error, existe, nombre, tipo, numero }
const resaltado = ref(-1);
let timer = null;
let pedido = 0;

const soloDigitos = computed(() => /^\d+$/.test(texto.value.trim()));
const esDocumento = computed(() => soloDigitos.value && [8, 11].includes(texto.value.trim().length));

const limpiar = () => {
  registrados.value = [];
  consulta.value = null;
  resaltado.value = -1;
};

const buscar = async () => {
  const q = texto.value.trim();
  const mio = ++pedido;
  limpiar();

  if (q.length < 2) return;

  try {
    const { data } = await axios.get(route('admin.purchases.search-suppliers'), { params: { q } });
    if (mio === pedido) registrados.value = data;
  } catch {
    // sin sugerencias: igual se puede escribir el nombre a mano
  }

  if (!esDocumento.value || mio !== pedido) return;

  consulta.value = { cargando: true };
  try {
    const { data } = await axios.get(route('admin.purchases.supplier-document'), { params: { numero: q } });
    if (mio === pedido) consulta.value = data.existe ? null : { ...data };
  } catch (e) {
    if (mio === pedido) consulta.value = { error: e.response?.data?.message || 'No se pudo consultar el documento.' };
  }
};

watch(texto, () => {
  abierto.value = true;
  clearTimeout(timer);
  timer = setTimeout(buscar, 300);
});
onBeforeUnmount(() => clearTimeout(timer));

const elegir = (p) => {
  modelo.value = { id: p.id, nombre: p.name, tipo: p.document_type, numero: p.document_number, nuevo: false };
  texto.value = '';
  abierto.value = false;
  limpiar();
};

const elegirDocumento = () => {
  const c = consulta.value;
  modelo.value = { id: null, nombre: c.nombre, tipo: c.tipo, numero: c.numero, nuevo: true };
  texto.value = '';
  abierto.value = false;
  limpiar();
};

const elegirNombre = () => {
  modelo.value = { id: null, nombre: texto.value.trim(), tipo: null, numero: null, nuevo: true };
  texto.value = '';
  abierto.value = false;
  limpiar();
};

const quitar = () => { modelo.value = null; };

const puedeCrearNombre = computed(() => !soloDigitos.value && texto.value.trim().length >= 2
  && !registrados.value.some((p) => p.name.toLowerCase() === texto.value.trim().toLowerCase()));

// Opciones del menú, en el orden en que se navega con el teclado.
const opciones = computed(() => [
  ...registrados.value.map((p) => ({ tipo: 'registrado', p })),
  ...(consulta.value?.nombre ? [{ tipo: 'documento' }] : []),
  ...(puedeCrearNombre.value ? [{ tipo: 'nombre' }] : []),
]);

const activar = (o) => {
  if (o.tipo === 'registrado') elegir(o.p);
  else if (o.tipo === 'documento') elegirDocumento();
  else elegirNombre();
};

const alTeclear = (e) => {
  if (e.key === 'ArrowDown') { e.preventDefault(); resaltado.value = Math.min(resaltado.value + 1, opciones.value.length - 1); }
  else if (e.key === 'ArrowUp') { e.preventDefault(); resaltado.value = Math.max(resaltado.value - 1, 0); }
  else if (e.key === 'Enter') {
    e.preventDefault();
    const o = opciones.value[resaltado.value] ?? (opciones.value.length === 1 ? opciones.value[0] : null);
    if (o) activar(o);
  } else if (e.key === 'Escape') abierto.value = false;
};

const fuente = (tipo) => (tipo === 'RUC' ? 'SUNAT' : 'RENIEC');
</script>

<template>
  <div class="relative">
    <label for="proveedor-campo" class="block text-xs font-semibold text-gray-600 mb-1">Proveedor <span class="font-normal text-gray-400">(opcional)</span></label>

    <!-- Elegido -->
    <div v-if="modelo" class="flex items-center gap-3 h-12 px-3 rounded-xl border border-green-200 bg-green-50">
      <span class="w-8 h-8 rounded-lg bg-surface text-green-600 flex items-center justify-center flex-shrink-0" aria-hidden="true"><i class="fa-solid fa-truck-fast text-sm"></i></span>
      <span class="min-w-0 flex-1">
        <span class="block text-sm font-semibold text-gray-900 truncate">{{ modelo.nombre }}</span>
        <span class="block text-[11px] text-gray-500 truncate">
          <template v-if="modelo.numero">{{ modelo.tipo }} {{ modelo.numero }} · </template>{{ modelo.nuevo ? 'Se registrará al guardar la compra' : 'Proveedor registrado' }}
        </span>
      </span>
      <button type="button" class="w-8 h-8 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-green-100 flex items-center justify-center flex-shrink-0" aria-label="Quitar proveedor" @click="quitar">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
    </div>

    <!-- Búsqueda -->
    <template v-else>
      <div class="relative">
        <i class="fa-solid fa-truck-fast absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none" aria-hidden="true"></i>
        <input
          id="proveedor-campo"
          v-model="texto"
          type="text"
          autocomplete="off"
          role="combobox"
          :aria-expanded="abierto && opciones.length > 0"
          placeholder="Sin proveedor · busca por nombre o RUC"
          class="w-full h-11 pl-10 pr-3 text-sm border border-gray-200 rounded-xl bg-surface placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100"
          @focus="abierto = true"
          @blur="abierto = false"
          @keydown="alTeclear"
        >
      </div>

      <div v-if="abierto && (opciones.length || consulta)" class="absolute z-20 left-0 right-0 mt-1.5 rounded-xl border border-gray-200 bg-surface shadow-xl overflow-hidden" @mousedown.prevent>
        <button
          v-for="(o, i) in opciones.filter((x) => x.tipo === 'registrado')"
          :key="o.p.id"
          type="button"
          :class="['w-full flex items-center gap-3 px-3.5 py-2.5 text-left transition-colors', resaltado === i ? 'bg-orange-50' : 'hover:bg-gray-50']"
          @click="elegir(o.p)"
        >
          <i class="fa-solid fa-truck-fast text-gray-400 text-sm" aria-hidden="true"></i>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-gray-900 truncate">{{ o.p.name }}</span>
            <span v-if="o.p.document_number" class="block text-[11px] text-gray-500">{{ o.p.document_type }} {{ o.p.document_number }}</span>
          </span>
          <span class="text-[11px] font-semibold text-green-600">Registrado</span>
        </button>

        <p v-if="consulta?.cargando" class="px-3.5 py-3 text-sm text-gray-500 flex items-center gap-2"><i class="fa-solid fa-spinner fa-spin text-xs" aria-hidden="true"></i> Consultando documento…</p>
        <p v-else-if="consulta?.error" class="px-3.5 py-3 text-sm text-red-500">{{ consulta.error }}</p>

        <button
          v-if="consulta?.nombre"
          type="button"
          :class="['w-full flex items-center gap-3 px-3.5 py-2.5 text-left border-t border-gray-100 transition-colors', resaltado === registrados.length ? 'bg-orange-50' : 'hover:bg-gray-50']"
          @click="elegirDocumento"
        >
          <i class="fa-solid fa-id-card text-[#ff8c42] text-sm" aria-hidden="true"></i>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-gray-900 truncate">{{ consulta.nombre }}</span>
            <span class="block text-[11px] text-gray-500">{{ consulta.tipo }} {{ consulta.numero }} · Encontrado en {{ fuente(consulta.tipo) }}</span>
          </span>
          <span class="text-[11px] font-semibold text-[#e67e2d]">Usar como nuevo</span>
        </button>

        <button
          v-if="puedeCrearNombre"
          type="button"
          :class="['w-full flex items-center gap-3 px-3.5 py-2.5 text-left border-t border-gray-100 transition-colors', resaltado === opciones.length - 1 ? 'bg-orange-50' : 'hover:bg-gray-50']"
          @click="elegirNombre"
        >
          <i class="fa-solid fa-plus text-[#ff8c42] text-sm" aria-hidden="true"></i>
          <span class="text-sm text-gray-700">Registrar «<b class="text-gray-900">{{ texto.trim() }}</b>» como proveedor nuevo</span>
        </button>
      </div>
    </template>
  </div>
</template>
