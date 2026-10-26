<script setup>
import { computed, ref, watch } from 'vue';
import { confirmar } from '@/alerts';
import { Link, router, usePage } from '@inertiajs/vue3';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import EmptyHero from '@/Components/Admin/EmptyHero.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { ORDEN_TALLAS, grupoDeTallas } from '@/Components/Admin/tallas';
import EditarModeloModal from './EditarModeloModal.vue';
import GestionarColoresModal from './GestionarColoresModal.vue';
import ColorPhoto from '@/Components/Admin/ColorPhoto.vue';
import NuevaCategoriaModal from './NuevaCategoriaModal.vue';
import AgregarMarcaModal from './AgregarMarcaModal.vue';
import NuevoModeloModal from './NuevoModeloModal.vue';
import AgregarTallasModal from './AgregarTallasModal.vue';

const props = defineProps({
  modelos: { type: Array, required: true },
  categoriasInfo: { type: Array, required: true }, // [{ id, name, sizeType, marcas: [{ id, name }] }]
  marcas: { type: Array, default: () => [] },
});

// "Registrar compra" desde cualquier nivel: el formulario se abre con lo que ya se sabe
// (categoría, marca, modelo, color y talla) para no volver a elegirlo.
const emit = defineEmits(['comprar']);

const page = usePage();
const esAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const umbral = computed(() => page.props.business?.low_stock_threshold ?? 5);

// ---------- Navegación ----------
const categoriaId = ref(null);
const marcaId = ref(null);
const modeloId = ref(null);
const busqueda = ref('');
const verBajas = ref(false);

const buscando = computed(() => busqueda.value.trim().length >= 2);

const irInicio = () => { categoriaId.value = null; marcaId.value = null; modeloId.value = null; };
const irCategoria = (id) => { categoriaId.value = id; marcaId.value = null; modeloId.value = null; };
const irMarca = (id) => { marcaId.value = id; modeloId.value = null; };
const irModelo = (m) => { categoriaId.value = m.categoriaId; marcaId.value = m.marcaId; modeloId.value = m.id; busqueda.value = ''; };

// ---------- Datos derivados ----------
const modelosActivos = computed(() => props.modelos.filter((m) => verBajas.value || m.activo));

const sumar = (lista) => ({
  modelos: lista.length,
  unidades: lista.reduce((s, m) => s + m.stock, 0),
  sinStock: lista.filter((m) => m.activo && m.stock === 0).length,
});

const categorias = computed(() =>
  props.categoriasInfo.map((c) => {
    const delaCategoria = modelosActivos.value.filter((m) => m.categoriaId === c.id);
    const marcasIds = new Set([...c.marcas.map((b) => b.id), ...delaCategoria.map((m) => m.marcaId)]);

    return { ...c, ...sumar(delaCategoria), marcas: marcasIds.size, vacia: !props.modelos.some((m) => m.categoriaId === c.id) };
  }),
);

const categoriaActual = computed(() => props.categoriasInfo.find((c) => c.id === categoriaId.value) ?? null);

const marcasDeCategoria = computed(() => {
  const grupos = new Map();

  // Las marcas agregadas a la categoría aparecen aunque todavía no tengan modelos.
  categoriaActual.value?.marcas.forEach((b) => grupos.set(b.id, { id: b.id, nombre: b.name, lista: [] }));

  modelosActivos.value.filter((m) => m.categoriaId === categoriaId.value).forEach((m) => {
    if (!grupos.has(m.marcaId)) grupos.set(m.marcaId, { id: m.marcaId, nombre: m.marca, lista: [] });
    grupos.get(m.marcaId).lista.push(m);
  });

  return [...grupos.values()]
    .map((g) => ({
      id: g.id,
      nombre: g.nombre,
      ...sumar(g.lista),
      sinModelos: !props.modelos.some((m) => m.categoriaId === categoriaId.value && m.marcaId === g.id),
    }))
    .sort((a, b) => a.nombre.localeCompare(b.nombre));
});

const marcaActual = computed(() => marcasDeCategoria.value.find((m) => m.id === marcaId.value) ?? null);

const modelosDeMarca = computed(() =>
  modelosActivos.value.filter((m) => m.categoriaId === categoriaId.value && m.marcaId === marcaId.value),
);

const modeloActual = computed(() => props.modelos.find((m) => m.id === modeloId.value) ?? null);

const resultadosBusqueda = computed(() => {
  const q = busqueda.value.trim().toLowerCase();
  return modelosActivos.value.filter((m) => `${m.nombre} ${m.marca} ${m.categoria}`.toLowerCase().includes(q));
});

// Si el modelo/marca/categoría desaparece (se da de baja, se cambia de categoría), se sube un nivel.
watch(() => props.modelos, () => {
  if (modeloId.value && !modeloActual.value) modeloId.value = null;
  if (marcaId.value && !marcaActual.value && !modeloActual.value) marcaId.value = null;
});

// ---------- Cuadrícula colores × tallas ----------
const tallasDelModelo = computed(() => {
  if (!modeloActual.value) return [];
  const set = new Set(modeloActual.value.colores.flatMap((c) => c.variantes.map((v) => v.talla)));

  return [...set].sort((a, b) => {
    const ia = ORDEN_TALLAS.indexOf(a);
    const ib = ORDEN_TALLAS.indexOf(b);
    return (ia === -1 ? 999 : ia) - (ib === -1 ? 999 : ib) || String(a).localeCompare(String(b));
  });
});

const varianteDe = (color, talla) => color.variantes.find((v) => v.talla === talla) ?? null;
const stockDeColor = (color) => color.variantes.reduce((s, v) => s + v.stock, 0);
const stockDeTalla = (talla) => modeloActual.value.colores.reduce((s, c) => s + (varianteDe(c, talla)?.stock ?? 0), 0);

const claseStock = (n) => {
  if (n === 0) return 'bg-red-50 text-red-500';
  if (n < umbral.value) return 'bg-yellow-50 text-yellow-600';
  return 'bg-green-50 text-green-600';
};

const rangoPrecio = (m) => {
  if (m.precioMin === null || m.precioMin <= 0) return null;
  const f = (n) => n.toFixed(2);
  return m.precioMax !== m.precioMin ? `S/ ${f(m.precioMin)} – ${f(m.precioMax)}` : `S/ ${f(m.precioMin)}`;
};


// ---------- Comprar (precarga el formulario) ----------
const baseModelo = (m) => ({ productId: m.id, nombre: m.nombre, marca: m.marca, categoria: m.categoria });

const comprarEnCategoria = () => emit('comprar', categoriaActual.value ? { categoria: categoriaActual.value.name } : null);
const comprarEnMarca = () => emit('comprar', { categoria: categoriaActual.value.name, marca: marcaActual.value.nombre });
const comprarModelo = (m) => emit('comprar', baseModelo(m));

const comprarCelda = (m, color, talla) => {
  const v = varianteDe(color, talla);
  const referencia = v ?? color.variantes[color.variantes.length - 1] ?? null;

  emit('comprar', {
    ...baseModelo(m),
    talla,
    color: color.hex,
    colorNombre: color.nombre,
    precioCompra: referencia?.costo ?? '',
    precioVenta: referencia?.precio ?? '',
  });
};

const textoComprar = computed(() => {
  if (modeloActual.value) return 'Comprar este modelo';
  if (marcaActual.value) return `Comprar ${marcaActual.value.nombre}`;
  if (categoriaActual.value) return `Comprar en ${categoriaActual.value.name}`;
  return 'Registrar compra';
});

const accionComprar = () => {
  if (modeloActual.value) return comprarModelo(modeloActual.value);
  if (marcaActual.value) return comprarEnMarca();
  return comprarEnCategoria();
};

// ---------- Editar modelo / colores / baja ----------
const editando = ref(null);
const modeloColores = ref(null);

const abrirEditar = (m) => {
  editando.value = { id: m.id, nombre: m.nombre, marca: m.marca, categoria: m.categoria, descripcion: m.descripcion };
};
const abrirColores = (m) => { modeloColores.value = { id: m.id, nombre: m.nombre, marca: m.marca }; };
const coloresSinFoto = (m) => m.colores.filter((c) => !c.imagenUrl).length;

const darDeBaja = async (m) => {
  const ok = await confirmar(`"${m.nombre}" (${m.marca}) dejará de mostrarse. Se conserva su historial y puedes restaurarlo después.`, {
    titulo: '¿Dar de baja este modelo?',
    confirmarTexto: 'Sí, dar de baja',
    peligro: true,
  });
  if (!ok) return;
  router.patch(route('admin.catalogo.deactivate', m.id), {}, { preserveScroll: true });
};
const restaurar = (m) => router.patch(route('admin.catalogo.restore', m.id), {}, { preserveScroll: true });

const categoriasNombres = computed(() => props.categoriasInfo.map((c) => c.name));

// ---------- Crear dentro del árbol (solo administradores) ----------
const modalCategoria = ref(null); // { categoria: null | {...} }
const modalMarca = ref(false);
const modalModelo = ref(false);
const modalTallas = ref(false);
const categoriaPorEliminar = ref(null);
const marcaPorQuitar = ref(null);
const procesando = ref(false);

// Sugerencias para empezar rápido en un nivel vacío (al tocarlas se abre el formulario ya rellenado).
const SUGERENCIAS_CATEGORIA = [
  { name: 'Polos', sizeType: 'ropa' },
  { name: 'Camisas', sizeType: 'ropa' },
  { name: 'Buzos', sizeType: 'ropa' },
  { name: 'Casacas', sizeType: 'ropa' },
  { name: 'Pantalones', sizeType: 'pantalon' },
  { name: 'Shorts', sizeType: 'pantalon' },
  { name: 'Zapatillas', sizeType: 'calzado' },
];
const SUGERENCIAS_MARCA = ['Nike', 'Adidas', 'Puma', 'Gap', 'Reebok', 'Levi\'s', 'Converse'];

const marcaInicial = ref('');
const categoriaInicial = ref(null);

const sugerirCategoria = (sugerencia) => { categoriaInicial.value = sugerencia; modalCategoria.value = { categoria: null }; };
const sugerirMarca = (nombre) => { marcaInicial.value = nombre; modalMarca.value = true; };

const sugerenciasMarcaLibres = computed(() => {
  const yaEstan = new Set([...(categoriaActual.value?.marcas ?? []).map((m) => m.name.toLowerCase()), ...props.marcas.map((m) => m.toLowerCase())]);
  // Se ofrecen primero las marcas que ya usas en otras categorías; luego las sugeridas que aún no existen.
  const existentes = props.marcas.filter((m) => !(categoriaActual.value?.marcas ?? []).some((b) => b.name === m)).slice(0, 7);
  const nuevas = SUGERENCIAS_MARCA.filter((m) => !yaEstan.has(m.toLowerCase()));
  return [...new Set([...existentes, ...nuevas])].slice(0, 7);
});

const eliminarCategoria = () => {
  procesando.value = true;
  router.delete(route('admin.categories.destroy', categoriaPorEliminar.value.id), {
    preserveScroll: true,
    onFinish: () => { procesando.value = false; categoriaPorEliminar.value = null; },
  });
};

const quitarMarca = () => {
  procesando.value = true;
  router.delete(route('admin.categories.brands.detach', [categoriaActual.value.id, marcaPorQuitar.value.id]), {
    preserveScroll: true,
    onFinish: () => { procesando.value = false; marcaPorQuitar.value = null; },
  });
};

const modeloParaTallas = computed(() => modeloActual.value && {
  id: modeloActual.value.id,
  nombre: modeloActual.value.nombre,
  colores: modeloActual.value.colores,
  tallas: tallasDelModelo.value,
});

// Tarjeta punteada "+ Crear ..." que cierra cada fila de tarjetas.
const claseTarjetaNueva = 'flex flex-col items-center justify-center gap-2 min-h-[10rem] rounded-2xl border-2 border-dashed border-orange-200 hover:border-[#ff8c42] hover:bg-orange-50/50 text-[#ff8c42] font-semibold text-sm transition-colors';
</script>

<template>
  <div>
    <!-- Barra: migas + búsqueda + acciones -->
    <div class="flex flex-wrap items-center justify-between gap-3 px-6 pb-4">
      <nav aria-label="Ubicación en el inventario" class="flex flex-wrap items-center gap-1.5 text-sm min-w-0">
        <button
          type="button"
          @click="irInicio"
          :class="['font-semibold rounded-lg px-2 py-1 transition-colors', !categoriaActual ? 'text-gray-900' : 'text-gray-500 hover:text-[#ff8c42] hover:bg-orange-50']"
        >
          <i class="fa-solid fa-boxes-stacked text-xs mr-1.5"></i>Inventario
        </button>

        <template v-if="categoriaActual">
          <i class="fa-solid fa-chevron-right text-[9px] text-gray-300"></i>
          <button type="button" @click="irCategoria(categoriaActual.id)" :class="['font-semibold rounded-lg px-2 py-1 transition-colors', !marcaActual ? 'text-gray-900' : 'text-gray-500 hover:text-[#ff8c42] hover:bg-orange-50']">
            {{ categoriaActual.name }}
          </button>
        </template>
        <template v-if="marcaActual">
          <i class="fa-solid fa-chevron-right text-[9px] text-gray-300"></i>
          <button type="button" @click="irMarca(marcaActual.id)" :class="['font-semibold rounded-lg px-2 py-1 transition-colors', !modeloActual ? 'text-gray-900' : 'text-gray-500 hover:text-[#ff8c42] hover:bg-orange-50']">
            {{ marcaActual.nombre }}
          </button>
        </template>
        <template v-if="modeloActual">
          <i class="fa-solid fa-chevron-right text-[9px] text-gray-300"></i>
          <span class="font-semibold text-gray-900 px-2 py-1 truncate max-w-[14rem]">{{ modeloActual.nombre }}</span>
        </template>
      </nav>

      <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
        <SearchInput v-model="busqueda" placeholder="Buscar modelo o marca" class="w-full sm:w-64" />
        <button
          type="button"
          @click="accionComprar"
          class="bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white text-sm font-bold py-2.5 px-5 rounded-xl shadow-sm shadow-orange-200 transition-all flex items-center gap-2 whitespace-nowrap"
        >
          <i class="fa-solid fa-plus text-xs"></i> {{ textoComprar }}
        </button>
      </div>
    </div>

    <div class="px-6 pb-6">
      <!-- ============ BÚSQUEDA (lista plana) ============ -->
      <template v-if="buscando">
        <p class="text-xs text-gray-500 mb-3">{{ resultadosBusqueda.length }} resultado{{ resultadosBusqueda.length === 1 ? '' : 's' }}</p>

        <div v-if="resultadosBusqueda.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
          <button
            v-for="m in resultadosBusqueda"
            :key="m.id"
            type="button"
            @click="irModelo(m)"
            class="text-left bg-surface border border-gray-100 hover:border-[#ff8c42] hover:shadow-sm rounded-2xl p-4 transition-all"
          >
            <p class="text-[11px] text-gray-400 mb-0.5">{{ m.categoria }} › {{ m.marca }}</p>
            <p class="font-semibold text-gray-900 truncate">{{ m.nombre }}</p>
            <span :class="['inline-block mt-2 text-xs font-bold px-2.5 py-1 rounded-full', claseStock(m.stock)]">{{ m.stock === 0 ? 'Sin stock' : `${m.stock} unid.` }}</span>
          </button>
        </div>
        <EmptyState v-else icon="fa-solid fa-magnifying-glass" title="Sin resultados" text="Prueba con otro nombre o marca." />
      </template>

      <!-- ============ NIVEL 0: categorías ============ -->
      <template v-else-if="!categoriaActual">
        <div v-if="categorias.length" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
          <div
            v-for="c in categorias"
            :key="c.id"
            class="group relative bg-surface border border-gray-100 hover:border-[#ff8c42] hover:shadow-md rounded-2xl transition-all"
          >
            <button type="button" @click="irCategoria(c.id)" class="block w-full text-left p-5">
              <div class="flex items-start justify-between mb-4">
                <span class="w-11 h-11 rounded-xl bg-orange-50 text-[#ff8c42] flex items-center justify-center group-hover:bg-[#ff8c42] group-hover:text-white transition-colors">
                  <i :class="grupoDeTallas(c.sizeType).icono"></i>
                </span>
                <span v-if="c.sinStock > 0" class="text-[11px] font-bold text-red-500 bg-red-50 rounded-full px-2 py-0.5">{{ c.sinStock }} sin stock</span>
              </div>
              <p class="font-bold text-gray-900 text-lg leading-tight">{{ c.name }}</p>
              <p class="text-xs text-gray-500 mt-1">
                {{ c.marcas }} marca{{ c.marcas === 1 ? '' : 's' }} · {{ c.modelos }} modelo{{ c.modelos === 1 ? '' : 's' }}
              </p>
              <p class="text-sm font-semibold text-gray-700 mt-3">{{ c.unidades }} <span class="font-normal text-gray-400">unidades</span></p>
            </button>

            <div v-if="esAdmin" class="absolute top-3 right-3 flex gap-0.5 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
              <ActionButton icon="fa-solid fa-pen" label="Editar categoría" tone="blue" @click="modalCategoria = { categoria: c }" />
              <ActionButton v-if="c.vacia" icon="fa-solid fa-trash" label="Eliminar categoría" tone="red" @click="categoriaPorEliminar = c" />
            </div>
          </div>

          <button v-if="esAdmin" type="button" @click="modalCategoria = { categoria: null }" :class="claseTarjetaNueva">
            <span class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center"><i class="fa-solid fa-plus"></i></span>
            Nueva categoría
          </button>
        </div>

        <EmptyHero
          v-else
          icon="fa-solid fa-layer-group"
          title="Empieza creando tus categorías"
          :text="esAdmin ? 'Las categorías ordenan tu inventario: dentro de cada una irán las marcas, los modelos, y sus colores y tallas.' : 'Un administrador debe crear las categorías primero.'"
        >
          <template v-if="esAdmin">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-2.5">Toca una para empezar</p>
            <div class="flex flex-wrap justify-center gap-2 max-w-xl mx-auto">
              <button
                v-for="sug in SUGERENCIAS_CATEGORIA"
                :key="sug.name"
                type="button"
                @click="sugerirCategoria(sug)"
                class="inline-flex items-center gap-1.5 bg-surface border border-orange-200 text-[#e67e2d] hover:border-[#ff8c42] hover:bg-orange-50 rounded-full px-3.5 py-1.5 text-sm font-semibold shadow-sm transition-colors"
              >
                <i class="fa-solid fa-plus text-[10px]"></i> {{ sug.name }}
              </button>
            </div>
            <button type="button" @click="categoriaInicial = null; modalCategoria = { categoria: null }" class="mt-6 inline-flex items-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white text-sm font-bold py-3 px-6 rounded-xl shadow-md shadow-orange-200 transition-all">
              <i class="fa-solid fa-plus text-xs"></i> Crear con otro nombre
            </button>
          </template>
        </EmptyHero>

        <div class="flex flex-wrap items-center justify-between gap-3 mt-5 text-xs text-gray-400">
          <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input v-model="verBajas" type="checkbox" class="rounded border-gray-300 text-[#ff8c42] focus:ring-[#ff8c42]">
            Incluir modelos dados de baja
          </label>
          <Link v-if="esAdmin" :href="route('admin.taxonomies.index')" class="font-semibold text-gray-500 hover:text-[#ff8c42]">
            <i class="fa-solid fa-gear mr-1"></i> Gestionar categorías y marcas
          </Link>
        </div>
      </template>

      <!-- ============ NIVEL 1: marcas de la categoría ============ -->
      <template v-else-if="!marcaActual">
        <div v-if="marcasDeCategoria.length" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
          <div
            v-for="m in marcasDeCategoria"
            :key="m.id"
            class="group relative bg-surface border border-gray-100 hover:border-[#ff8c42] hover:shadow-md rounded-2xl transition-all"
          >
            <button type="button" @click="irMarca(m.id)" class="block w-full text-left p-5">
              <div class="flex items-start justify-between mb-3">
                <span class="w-10 h-10 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-black">{{ m.nombre.charAt(0).toUpperCase() }}</span>
                <span v-if="m.sinStock > 0" class="text-[11px] font-bold text-red-500 bg-red-50 rounded-full px-2 py-0.5">{{ m.sinStock }} sin stock</span>
              </div>
              <p class="font-bold text-gray-900 leading-tight">{{ m.nombre }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ m.modelos }} modelo{{ m.modelos === 1 ? '' : 's' }}</p>
              <p class="text-sm font-semibold text-gray-700 mt-3">{{ m.unidades }} <span class="font-normal text-gray-400">unidades</span></p>
            </button>

            <div v-if="esAdmin && m.sinModelos" class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
              <ActionButton icon="fa-solid fa-xmark" label="Quitar de esta categoría" tone="red" @click="marcaPorQuitar = m" />
            </div>
          </div>

          <button v-if="esAdmin" type="button" @click="modalMarca = true" :class="claseTarjetaNueva">
            <span class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center"><i class="fa-solid fa-plus"></i></span>
            Agregar marca
          </button>
        </div>

        <EmptyHero
          v-else
          icon="fa-solid fa-tags"
          :title="`Agrega la primera marca de ${categoriaActual.name}`"
          :text="esAdmin ? 'Las marcas que vendes dentro de esta categoría. Luego les crearás sus modelos.' : 'Un administrador debe agregar las marcas.'"
        >
          <template v-if="esAdmin">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-2.5">Toca una para empezar</p>
            <div class="flex flex-wrap justify-center gap-2 max-w-xl mx-auto">
              <button
                v-for="nombre in sugerenciasMarcaLibres"
                :key="nombre"
                type="button"
                @click="sugerirMarca(nombre)"
                class="inline-flex items-center gap-1.5 bg-surface border border-orange-200 text-[#e67e2d] hover:border-[#ff8c42] hover:bg-orange-50 rounded-full px-3.5 py-1.5 text-sm font-semibold shadow-sm transition-colors"
              >
                <i class="fa-solid fa-plus text-[10px]"></i> {{ nombre }}
              </button>
            </div>
            <button type="button" @click="marcaInicial = ''; modalMarca = true" class="mt-6 inline-flex items-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white text-sm font-bold py-3 px-6 rounded-xl shadow-md shadow-orange-200 transition-all">
              <i class="fa-solid fa-plus text-xs"></i> Escribir otra marca
            </button>
          </template>
        </EmptyHero>
      </template>

      <!-- ============ NIVEL 2: modelos de la marca ============ -->
      <template v-else-if="!modeloActual">
        <div v-if="modelosDeMarca.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="m in modelosDeMarca"
            :key="m.id"
            :class="['bg-surface border border-gray-100 hover:border-gray-200 rounded-2xl p-5 transition-all', !m.activo ? 'opacity-60' : '']"
          >
            <button type="button" @click="irModelo(m)" class="block w-full text-left">
              <div class="flex items-start justify-between gap-3">
                <p class="font-bold text-gray-900 leading-tight truncate">
                  {{ m.nombre }}
                  <span v-if="!m.activo" class="ml-1 text-[10px] font-bold text-gray-500 bg-gray-100 rounded px-1.5 py-0.5 align-middle">De baja</span>
                </p>
                <span :class="['text-xs font-bold px-2.5 py-1 rounded-full whitespace-nowrap flex-shrink-0', claseStock(m.stock)]">{{ m.stock === 0 ? 'Sin stock' : `${m.stock} unid.` }}</span>
              </div>

              <div class="flex items-center gap-1.5 mt-3 min-h-[1.5rem]">
                <template v-if="m.colores.length">
                  <span v-for="c in m.colores.slice(0, 6)" :key="c.id" :title="c.nombre" class="w-5 h-5 rounded-full border border-gray-200 overflow-hidden inline-block">
                    <img v-if="c.imagenUrl" :src="c.imagenUrl" :alt="c.nombre" class="w-full h-full object-cover">
                    <ColorSwatch v-else :hex="c.hex" class="w-full h-full" />
                  </span>
                  <span v-if="m.colores.length > 6" class="text-xs text-gray-400">+{{ m.colores.length - 6 }}</span>
                  <span
                    v-if="esAdmin && coloresSinFoto(m) > 0"
                    class="ml-auto inline-flex items-center gap-1 text-[11px] font-bold text-[#e67e2d] bg-orange-50 rounded-full px-2 py-0.5"
                  >
                    <i class="fa-solid fa-camera text-[9px]" aria-hidden="true"></i>
                    {{ coloresSinFoto(m) }} sin foto
                  </span>
                </template>
                <span v-else class="text-xs text-gray-400">Sin colores todavía</span>
              </div>

              <p class="text-sm text-gray-600 mt-3 font-medium">{{ rangoPrecio(m) ?? 'Sin precio' }}</p>
            </button>

            <div class="flex items-center justify-between gap-2 mt-4 pt-3 border-t border-gray-50">
              <button type="button" @click="comprarModelo(m)" class="text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 px-3 py-1.5 rounded-lg transition-colors">
                <i class="fa-solid fa-plus text-xs mr-1"></i>Comprar
              </button>
              <div v-if="esAdmin" class="flex items-center gap-0.5">
                <ActionButton icon="fa-solid fa-palette" label="Colores" tone="purple" @click="abrirColores(m)" />
                <ActionButton icon="fa-solid fa-pen" label="Editar" tone="blue" @click="abrirEditar(m)" />
                <ActionButton v-if="m.activo" icon="fa-solid fa-box-archive" label="Dar de baja" tone="red" @click="darDeBaja(m)" />
                <ActionButton v-else icon="fa-solid fa-rotate-left" label="Restaurar" tone="gray" @click="restaurar(m)" />
              </div>
            </div>
          </div>

          <button v-if="esAdmin" type="button" @click="modalModelo = true" :class="claseTarjetaNueva">
            <span class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center"><i class="fa-solid fa-plus"></i></span>
            Nuevo modelo
          </button>
        </div>
        <EmptyHero
          v-else
          icon="fa-solid fa-shirt"
          :title="`Crea el primer modelo de ${marcaActual.nombre}`"
          :text="esAdmin ? 'Un modelo es cada prenda concreta de la marca (por ejemplo: All Star, Dri-Fit, Stan Smith). Después le agregas colores y tallas.' : 'Un administrador debe crear los modelos.'"
        >
          <button v-if="esAdmin" type="button" @click="modalModelo = true" class="inline-flex items-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white text-sm font-bold py-3 px-6 rounded-xl shadow-md shadow-orange-200 transition-all">
            <i class="fa-solid fa-plus text-xs"></i> Crear el primer modelo
          </button>
        </EmptyHero>
      </template>

      <!-- ============ NIVEL 3: detalle del modelo (colores × tallas) ============ -->
      <template v-else>
        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
          <div>
            <h4 class="text-xl font-bold text-gray-900">{{ modeloActual.nombre }}</h4>
            <p class="text-sm text-gray-500 mt-0.5">{{ modeloActual.marca }} · {{ modeloActual.categoria }}</p>
            <p v-if="modeloActual.descripcion" class="text-sm text-gray-400 mt-1 max-w-xl">{{ modeloActual.descripcion }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span :class="['text-sm font-bold px-3 py-1.5 rounded-full', claseStock(modeloActual.stock)]">{{ modeloActual.stock === 0 ? 'Sin stock' : `${modeloActual.stock} unidades` }}</span>
            <div v-if="esAdmin" class="flex items-center gap-2">
              <button type="button" @click="abrirColores(modeloActual)" class="text-sm font-semibold text-purple-600 hover:bg-purple-50 border border-purple-100 px-3 py-1.5 rounded-lg transition-colors">
                <i class="fa-solid fa-palette text-xs mr-1.5"></i>Colores
              </button>
              <button type="button" @click="modalTallas = true" class="text-sm font-semibold text-[#e67e2d] hover:bg-orange-50 border border-orange-100 px-3 py-1.5 rounded-lg transition-colors">
                <i class="fa-solid fa-ruler-combined text-xs mr-1.5"></i>Tallas
              </button>
              <ActionButton icon="fa-solid fa-pen" label="Editar" tone="blue" @click="abrirEditar(modeloActual)" />
              <ActionButton v-if="modeloActual.activo" icon="fa-solid fa-box-archive" label="Dar de baja" tone="red" @click="darDeBaja(modeloActual)" />
              <ActionButton v-else icon="fa-solid fa-rotate-left" label="Restaurar" tone="gray" @click="restaurar(modeloActual)" />
            </div>
          </div>
        </div>

        <div
          v-if="esAdmin && tallasDelModelo.length && coloresSinFoto(modeloActual) > 0"
          class="flex items-center gap-3 mb-4 rounded-xl border border-orange-100 bg-orange-50/70 px-4 py-3 text-sm text-gray-700"
        >
          <span class="w-8 h-8 rounded-lg bg-surface text-[#ff8c42] flex items-center justify-center flex-shrink-0 shadow-sm">
            <i class="fa-solid fa-camera" aria-hidden="true"></i>
          </span>
          <p>
            <strong class="font-bold text-gray-900">{{ coloresSinFoto(modeloActual) === 1 ? '1 color no tiene foto' : `${coloresSinFoto(modeloActual)} colores no tienen foto` }}.</strong>
            Toca el recuadro <span class="font-semibold text-[#e67e2d]">Subir</span> de cada color, o arrastra la imagen encima. Así se verá en la tienda.
          </p>
        </div>

        <div v-if="modeloActual.colores.length && tallasDelModelo.length" class="overflow-x-auto rounded-xl border border-gray-100">
          <table class="w-full text-sm">
            <thead class="bg-gray-50/80 text-xs uppercase tracking-wider text-gray-500">
              <tr>
                <th class="text-left font-semibold py-3 px-5">Color</th>
                <th v-for="t in tallasDelModelo" :key="t" class="text-center font-semibold py-3 px-2 min-w-[3.5rem]">{{ t }}</th>
                <th class="text-right font-semibold py-3 px-5">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="c in modeloActual.colores" :key="c.id">
                <td class="py-3 px-5">
                  <div class="flex items-center gap-3">
                    <ColorPhoto :color="c" :editable="esAdmin" />
                    <div class="min-w-0">
                      <p class="font-semibold text-gray-900 truncate">{{ c.nombre }}</p>
                      <p v-if="esAdmin && !c.imagenUrl" class="text-[11px] font-semibold text-[#e67e2d]">Sin foto</p>
                    </div>
                  </div>
                </td>

                <td v-for="t in tallasDelModelo" :key="t" class="py-3 px-2 text-center">
                  <button
                    v-if="varianteDe(c, t)"
                    type="button"
                    @click="comprarCelda(modeloActual, c, t)"
                    :title="`Comprar más: ${c.nombre} talla ${t}`"
                    :class="['min-w-[2.5rem] text-xs font-bold px-2 py-1.5 rounded-lg hover:ring-2 hover:ring-[#ff8c42] transition-all', claseStock(varianteDe(c, t).stock)]"
                  >{{ varianteDe(c, t).stock }}</button>
                  <button
                    v-else
                    type="button"
                    @click="comprarCelda(modeloActual, c, t)"
                    :title="`Agregar talla ${t} a ${c.nombre}`"
                    class="min-w-[2.5rem] text-xs text-gray-300 hover:text-[#ff8c42] hover:bg-orange-50 px-2 py-1.5 rounded-lg transition-colors"
                  >—</button>
                </td>

                <td class="py-3 px-5 text-right font-bold text-gray-900 tabular-nums">{{ stockDeColor(c) }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50/60 border-t border-gray-100">
              <tr class="text-xs font-bold text-gray-600">
                <td class="py-2.5 px-5">Total</td>
                <td v-for="t in tallasDelModelo" :key="t" class="py-2.5 px-2 text-center tabular-nums">{{ stockDeTalla(t) }}</td>
                <td class="py-2.5 px-5 text-right tabular-nums text-gray-900">{{ modeloActual.stock }}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <EmptyState
          v-else
          icon="fa-solid fa-layer-group"
          :title="modeloActual.colores.length ? 'Aún no hay tallas' : 'Este modelo aún no tiene colores'"
          :text="esAdmin ? (modeloActual.colores.length ? 'Agrega las tallas que manejas, o registra una compra y se crean solas.' : 'Agrega sus colores y luego sus tallas; el stock entra al registrar una compra.') : 'Un administrador debe agregar los colores y tallas.'"
        >
          <div class="flex flex-wrap justify-center gap-2">
            <button v-if="esAdmin && !modeloActual.colores.length" type="button" @click="abrirColores(modeloActual)" class="inline-flex items-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] text-white text-sm font-bold py-2.5 px-5 rounded-xl transition">
              <i class="fa-solid fa-palette text-xs"></i> Agregar colores
            </button>
            <button v-if="esAdmin && modeloActual.colores.length" type="button" @click="modalTallas = true" class="inline-flex items-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] text-white text-sm font-bold py-2.5 px-5 rounded-xl transition">
              <i class="fa-solid fa-ruler-combined text-xs"></i> Agregar tallas
            </button>
            <button type="button" @click="comprarModelo(modeloActual)" class="inline-flex items-center gap-2 border border-orange-200 text-[#ff8c42] hover:bg-orange-50 text-sm font-bold py-2.5 px-5 rounded-xl transition">
              <i class="fa-solid fa-plus text-xs"></i> Comprar este modelo
            </button>
          </div>
        </EmptyState>

        <p v-if="modeloActual.colores.length && tallasDelModelo.length" class="text-xs text-gray-400 mt-3">
          Toca un número para comprar más de esa talla, o un guion para agregar una talla nueva a ese color.
        </p>
      </template>
    </div>

    <EditarModeloModal
      v-if="editando"
      :categorias-existentes="categoriasNombres"
      :marcas-existentes="marcas"
      :editando="editando"
      @close="editando = null"
    />

    <GestionarColoresModal
      v-if="modeloColores"
      :producto="modeloColores"
      @close="modeloColores = null"
    />

    <NuevaCategoriaModal v-if="modalCategoria" :categoria="modalCategoria.categoria" :inicial="categoriaInicial" @close="modalCategoria = null; categoriaInicial = null" />
    <AgregarMarcaModal v-if="modalMarca && categoriaActual" :categoria="categoriaActual" :marcas="marcas" :nombre-inicial="marcaInicial" @close="modalMarca = false; marcaInicial = ''" />
    <NuevoModeloModal v-if="modalModelo && marcaActual" :categoria="categoriaActual" :marca="marcaActual" @close="modalModelo = false" />
    <AgregarTallasModal v-if="modalTallas && modeloParaTallas" :modelo="modeloParaTallas" :size-type="categoriaActual?.sizeType" @close="modalTallas = false" />

    <ConfirmModal
      v-if="categoriaPorEliminar"
      title="Eliminar categoría"
      :message="`¿Eliminar la categoría «${categoriaPorEliminar.name}»? Está vacía, así que no se pierde ningún modelo.`"
      confirm-text="Sí, eliminar"
      variant="danger"
      :loading="procesando"
      @confirm="eliminarCategoria"
      @close="categoriaPorEliminar = null"
    />
    <ConfirmModal
      v-if="marcaPorQuitar"
      title="Quitar marca"
      :message="`¿Quitar «${marcaPorQuitar.nombre}» de ${categoriaActual?.name}? Está sin modelos aquí. Si tampoco está en otra categoría, la marca se elimina del todo.`"
      confirm-text="Sí, quitar"
      variant="danger"
      :loading="procesando"
      @confirm="quitarMarca"
      @close="marcaPorQuitar = null"
    />
  </div>
</template>
