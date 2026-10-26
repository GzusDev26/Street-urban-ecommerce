<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount, watch } from 'vue';
import { avisar } from '@/alerts';
import { bloquearScroll } from '@/useBloqueoScroll';
import { router, usePage } from '@inertiajs/vue3';
import SearchCreateInput from '@/Components/Admin/SearchCreateInput.vue';
import SearchSelect from '@/Components/Admin/SearchSelect.vue';
import { GRUPOS_TALLA } from '@/Components/Admin/tallas';
import { PALETA } from '@/Components/Admin/colores';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import ProveedorField from '@/Components/Admin/ProveedorField.vue';

const props = defineProps({
  categoriasExistentes: {
    type: Array,
    default: () => [],
  },
  // [{ id, name, sizeType }]: cada categoría define qué tallas se ofrecen (ropa, pantalón, calzado).
  categoriasInfo: {
    type: Array,
    default: () => [],
  },
  marcasExistentes: {
    type: Array,
    default: () => [],
  },
  // [{ id, name }]: todas las marcas del negocio. Una marca no depende de la categoría (hay "Nike" en
  // polos y "Nike" en zapatillas), así que se puede elegir en cualquiera.
  marcasInfo: {
    type: Array,
    default: () => [],
  },
  prefill: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

// El fondo oscuro solo cierra el formulario si el clic empezó ahí Y todavía no hay nada escrito: una compra
// a medias no se pierde por un clic fuera de lugar (se cierra con la X o con "Cancelar").
const pulsoEnElFondo = ref(false);
const cerrarPorFondo = () => {
  if (pulsoEnElFondo.value && !hayDatos.value) emit('close');
};

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
  color: null,
  colorNombre: '',
  precioCompra: '',
  precioVenta: '',
});

// Cuadrícula de tallas: cantidad comprada de cada talla ({ '38': 2, '39': 3 }). Las tallas sin
// cantidad no se compran. Al agregar, cada talla con cantidad se convierte en una línea de la compra.
const grilla = ref({});
const tallasExtra = ref([]); // tallas fuera de la lista habitual (ej. "42" o una talla ya registrada)
const tallaNueva = ref('');

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

// La página de atrás no debe moverse mientras el formulario está abierto.
// Celular: el resumen (fecha, proveedor, prendas agregadas) está al final del formulario; la barra de abajo lleva hasta él.
const irAlResumen = () => document.getElementById('resumen-compra')?.scrollIntoView({ behavior: 'smooth', block: 'start' });

let liberarScroll = null;
onMounted(() => { liberarScroll = bloquearScroll(); });
onBeforeUnmount(() => liberarScroll?.());

onMounted(() => {
  if (props.prefill) {
    prendaForm.value.productId = props.prefill.productId ?? null;
    prendaForm.value.nombre = props.prefill.nombre ?? '';
    prendaForm.value.marca = props.prefill.marca ?? '';
    prendaForm.value.categoria = props.prefill.categoria ?? '';
    // Reabastecer una variante concreta: llega con su talla marcada y 1 unidad.
    if (props.prefill.talla) {
      grupoTalla.value = GRUPOS_TALLA.find((g) => g.tallas.includes(props.prefill.talla))?.id ?? 'ropa';
      grupoElegidoAMano = true;
      if (!tallas.includes(props.prefill.talla)) tallasExtra.value.push(props.prefill.talla);
      grilla.value = { [props.prefill.talla]: 1 };
    }
    prendaForm.value.color = props.prefill.color ?? null;
    prendaForm.value.colorNombre = props.prefill.colorNombre || '';
    prendaForm.value.precioCompra = props.prefill.precioCompra || '';
    prendaForm.value.precioVenta = props.prefill.precioVenta || '';
    // Sugerencia del último proveedor de esta variante — el usuario la puede cambiar
    // si esta vez la está comprando en otro lado.
    if (props.prefill.tienda) proveedor.value = { id: null, nombre: props.prefill.tienda, tipo: null, numero: null, nuevo: false };
    if (props.prefill.productId) cargarColoresProducto();
  }
});

const capitalizarPrimeraLetra = (str) => (str.length > 0 ? str.charAt(0).toUpperCase() + str.slice(1) : str);

// Proveedor elegido (opcional): { id, nombre, tipo, numero, nuevo } o null = sin proveedor.
const proveedor = ref(null);
const textoProveedor = ref('');

// Las categorías se eligen de la lista (no se crean escribiendo, para no acabar con "Polo" y "Polos").
// Un administrador puede crear una a propósito con "Nueva categoría", eligiendo su tipo de tallas.
const page = usePage();
const esAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const categoriasLocales = ref(props.categoriasInfo.map((c) => ({ ...c, marcas: [...(c.marcas ?? [])] })));
const opcionesCategoria = computed(() => categoriasLocales.value.map((c) => ({ value: c.name, label: c.name })));

const creandoCategoria = ref(false);
const categoriaNueva = ref({ name: '', sizeType: 'ropa' });
const errorCategoria = ref('');
const guardandoCategoria = ref(false);

// Mueve el cursor al siguiente campo del formulario cuando algo recién creado deja listo el paso anterior.
const irAlSiguiente = async (selector) => {
  await nextTick();
  document.querySelector(selector)?.focus();
};

// Solo uno de los paneles de creación ("Nueva categoría / marca / modelo") está abierto a la vez.
const cerrarPaneles = () => {
  creandoCategoria.value = false;
  creandoMarca.value = false;
  creandoModelo.value = false;
};

const abrirNuevaCategoria = (nombre = '') => {
  cerrarPaneles();
  categoriaNueva.value = { name: nombre, sizeType: 'ropa' };
  errorCategoria.value = '';
  creandoCategoria.value = true;
};

// Llamada JSON al servidor sin salir del formulario (crear categoría, marca o modelo al vuelo).
const enviarJson = async (url, cuerpo) => {
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    },
    body: JSON.stringify(cuerpo),
  });

  return { ok: res.ok, data: await res.json().catch(() => ({})) };
};

const mensajeError = (data, porDefecto) => data.message && !/^The .* field/i.test(data.message)
  ? data.message
  : (Object.values(data.errors ?? {})[0]?.[0] || porDefecto);

const crearCategoria = async () => {
  const name = categoriaNueva.value.name.trim();
  if (!name) {
    errorCategoria.value = 'Escribe el nombre de la categoría.';
    return;
  }

  guardandoCategoria.value = true;
  errorCategoria.value = '';

  try {
    const { ok, data } = await enviarJson(route('admin.categories.quick-store'), { name, size_type: categoriaNueva.value.sizeType });

    if (!ok) {
      errorCategoria.value = mensajeError(data, 'No se pudo crear la categoría.');
      return;
    }

    categoriasLocales.value.push({ ...data, marcas: [] });
    prendaForm.value.categoria = data.name;
    creandoCategoria.value = false;
    irAlSiguiente('#compra-marca'); // categoría lista: se sigue con la marca

    // La categoría ya existe en la base: se refrescan los datos de la pantalla de atrás para que
    // también aparezca en el inventario (sin cerrar este formulario ni perder lo escrito).
    router.reload({ only: ['categoriasInfo', 'categorias'], preserveScroll: true });
  } catch (e) {
    console.error('No se pudo crear la categoría', e);
    errorCategoria.value = 'No se pudo crear la categoría. Intenta de nuevo.';
  } finally {
    guardandoCategoria.value = false;
  }
};

// ---- Marca: una sola lista para todo el negocio. Las que ya están en la categoría salen primero.
const marcasLocales = ref(props.marcasInfo.map((m) => ({ ...m })));
const categoriaElegida = computed(() => categoriasLocales.value.find((c) => c.name === prendaForm.value.categoria) ?? null);
const marcasDeCategoria = computed(() => categoriaElegida.value?.marcas ?? []);
const marcaElegida = computed(() => marcasLocales.value.find((m) => m.name.toLowerCase() === prendaForm.value.marca.toLowerCase()) ?? null);

const opcionesMarca = computed(() => {
  const enCategoria = new Set(marcasDeCategoria.value.map((m) => m.id));

  return [...marcasLocales.value]
    .sort((a, b) => (enCategoria.has(b.id) - enCategoria.has(a.id)) || a.name.localeCompare(b.name))
    .map((m) => ({ value: m.name, label: m.name, hint: enCategoria.has(m.id) ? `en ${prendaForm.value.categoria}` : '' }));
});

// Para el panel "Nueva marca": las que existen en el negocio pero todavía no están en esta categoría.
const marcasDisponibles = computed(() => {
  const yaEstan = new Set(marcasDeCategoria.value.map((m) => m.name.toLowerCase()));
  return marcasLocales.value.map((m) => m.name).filter((n) => !yaEstan.has(n.toLowerCase()));
});

const creandoMarca = ref(false);
const marcaNueva = ref('');
const errorMarca = ref('');
const guardandoMarca = ref(false);

const abrirNuevaMarca = (nombre = '') => {
  cerrarPaneles();
  marcaNueva.value = nombre;
  errorMarca.value = '';
  creandoMarca.value = true;
};

// Desde la lista se crea directo (nombre ya escrito); desde el panel "+ Nueva marca" se usa lo del panel.
const crearMarcaDirecta = (nombre) => {
  if (!nombre.trim()) return;
  marcaNueva.value = nombre;
  crearMarca(true);
};

const crearMarca = async (directo = false) => {
  const name = marcaNueva.value.trim();
  if (!name) {
    errorMarca.value = 'Elige una marca o escribe una nueva.';
    return;
  }

  guardandoMarca.value = true;
  errorMarca.value = '';

  try {
    const { ok, data } = await enviarJson(route('admin.categories.brands.attach', categoriaElegida.value.id), { name });

    if (!ok) {
      errorMarca.value = mensajeError(data, 'No se pudo agregar la marca.');
      if (directo) creandoMarca.value = true; // el error se muestra en el panel
      return;
    }

    if (!categoriaElegida.value.marcas.some((m) => m.id === data.id)) categoriaElegida.value.marcas.push({ id: data.id, name: data.name });
    if (!marcasLocales.value.some((m) => m.id === data.id)) marcasLocales.value.push({ id: data.id, name: data.name });
    prendaForm.value.marca = data.name;
    creandoMarca.value = false;
    irAlSiguiente('#compra-modelo'); // marca lista: se sigue con el modelo
    router.reload({ only: ['categoriasInfo', 'marcas', 'marcasInfo'], preserveScroll: true });
  } catch (e) {
    console.error('No se pudo agregar la marca', e);
    errorMarca.value = 'No se pudo agregar la marca. Intenta de nuevo.';
    if (directo) creandoMarca.value = true;
  } finally {
    guardandoMarca.value = false;
  }
};

// ---- Modelo: se busca entre los existentes; "Nuevo modelo" lo crea en la categoría y marca elegidas.
const creandoModelo = ref(false);
const modeloNuevo = ref('');
const errorModelo = ref('');
const guardandoModelo = ref(false);

const abrirNuevoModelo = (nombreSugerido = '') => {
  cerrarPaneles();
  modeloNuevo.value = nombreSugerido;
  errorModelo.value = '';
  creandoModelo.value = true;
};

const crearModeloDirecto = (nombre) => {
  if (!nombre.trim()) return;
  cerrarPaneles();
  modeloNuevo.value = nombre;
  crearModelo(true);
};

const crearModelo = async (directo = false) => {
  const nombre = modeloNuevo.value.trim();
  if (!nombre) {
    errorModelo.value = 'Escribe el nombre del modelo.';
    return;
  }

  guardandoModelo.value = true;
  errorModelo.value = '';

  try {
    const { ok, data } = await enviarJson(route('admin.catalogo.store'), {
      categoria_id: categoriaElegida.value.id,
      marca_id: marcaElegida.value.id,
      nombre,
    });

    if (!ok) {
      errorModelo.value = mensajeError(data, 'No se pudo crear el modelo.');
      if (directo) creandoModelo.value = true; // el error se muestra en el panel
      return;
    }

    creandoModelo.value = false;
    seleccionarProducto(data); // queda elegido: ya se pueden cargar sus colores, tallas y precios
    irAlSiguiente('input[aria-label="Nombre del color"]'); // modelo listo: se sigue con el color
    router.reload({ only: ['modelos', 'variants', 'stats'], preserveScroll: true });
  } catch (e) {
    console.error('No se pudo crear el modelo', e);
    errorModelo.value = 'No se pudo crear el modelo. Intenta de nuevo.';
    if (directo) creandoModelo.value = true;
  } finally {
    guardandoModelo.value = false;
  }
};


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
  searchQuery.value = '';
  searchResults.value = [];
  coloresProducto.value = [];
  mostrarNuevoColor.value = false;
};

const onSearchInput = () => {
  searchQuery.value = capitalizarPrimeraLetra(searchQuery.value);
  prendaForm.value.productId = null;
  prendaForm.value.nombre = '';
  buscarProductos();
};


const tallas = [...new Set(GRUPOS_TALLA.flatMap((g) => g.tallas))];

const grupoTalla = ref('ropa');
let grupoElegidoAMano = false;

// Categorías sin tallas ni colores (perfumes...): no se pide el color y la compra va con "Sin color".
const sinColor = computed(() => !!GRUPOS_TALLA.find((g) => g.id === grupoTalla.value)?.sinColor);

const elegirGrupoTalla = (id) => {
  grupoTalla.value = id;
  grupoElegidoAMano = true;
};

// Cada categoría define su tipo de tallas; se muestran esas salvo que ya hayan elegido otro grupo a mano.
watch(() => prendaForm.value.categoria, (categoria) => {
  if (grupoElegidoAMano) return;

  const tipo = categoriasLocales.value.find((c) => c.name === categoria)?.sizeType;
  grupoTalla.value = GRUPOS_TALLA.some((g) => g.id === tipo) ? tipo : 'ropa';
});
// Las del grupo elegido, más cualquiera que ya tenga cantidad o se haya agregado a mano
// (así no desaparecen al cambiar de grupo ni al editar una línea ya agregada).
const tallasMostradas = computed(() => {
  const base = GRUPOS_TALLA.find((g) => g.id === grupoTalla.value).tallas;
  const extra = [...Object.keys(grilla.value), ...tallasExtra.value].filter((t) => !base.includes(t));

  return [...base, ...new Set(extra)];
});

const setCantidad = (talla, valor) => {
  const n = parseInt(valor, 10);
  const nueva = { ...grilla.value };

  if (Number.isFinite(n) && n > 0) {
    nueva[talla] = n;
  } else {
    delete nueva[talla];
  }

  grilla.value = nueva;
};

// Botones "−" y "+" de cada talla (la cantidad nunca baja de 0).
const cambiarCantidad = (talla, delta) => {
  setCantidad(talla, Math.max(0, (grilla.value[talla] || 0) + delta));
};

const agregarTallaNueva = () => {
  const t = tallaNueva.value.trim();
  if (!t) return;

  if (!tallasMostradas.value.includes(t)) tallasExtra.value.push(t);
  if (!grilla.value[t]) grilla.value = { ...grilla.value, [t]: 1 };
  tallaNueva.value = '';
};

const hayDatos = computed(() => prendas.value.length > 0
  || !!(prendaForm.value.categoria || prendaForm.value.marca || prendaForm.value.nombre || prendaForm.value.colorNombre
    || proveedor.value || compraForm.value.factura || compraForm.value.notas || unidadesEnGrilla.value > 0));

// Indicador de progreso: cada paso se marca como completo cuando ya tiene lo necesario.
const paso1Listo = computed(() => !!prendaForm.value.productId);
const paso2Listo = computed(() => !!prendaForm.value.colorNombre.trim());
const paso3Listo = computed(() => unidadesEnGrilla.value > 0);
const paso4Listo = computed(() => prendaForm.value.precioCompra !== '' && prendaForm.value.precioCompra != null
  && prendaForm.value.precioVenta !== '' && prendaForm.value.precioVenta != null);

const margenPorcentaje = computed(() => {
  const { precioCompra, precioVenta } = prendaForm.value;
  if (!precioCompra) return null;
  return Math.round(((precioVenta - precioCompra) / precioCompra * 100) || 0);
});

const utilidadTotal = () => prendas.value.reduce((sum, p) => sum + (p.cantidad * (p.precioVenta - p.precioCompra)), 0);

const unidadesEnGrilla = computed(() => Object.values(grilla.value).reduce((sum, n) => sum + n, 0));

// Paleta de ayuda solo para cuando se está creando un color realmente nuevo (no existe
// todavía para este modelo). No se usa para nombrar colores existentes.
const paletaColores = [{ nombre: 'Sin color', hex: null }, ...PALETA];

const seleccionarColorExistente = (c) => {
  prendaForm.value.colorNombre = c.nombre;
  prendaForm.value.color = c.hex;
};

const seleccionarColorPaleta = (p) => {
  prendaForm.value.color = p.hex;
  prendaForm.value.colorNombre = p.nombre;
};

// `repetir`: deja las tallas y cantidades escritas para cargar el mismo surtido en otro color.
const agregarPrenda = (repetir = false) => {
  const f = prendaForm.value;

  if (!f.productId) {
    avisar('Elige un modelo de la lista, o créalo con "+ Nuevo modelo".', { titulo: 'Falta el modelo' });
    return;
  }

  // Se nombra solo lo que falta, para no pedir datos que ya están completos.
  const precioVacio = (p) => p === '' || p == null || p < 0;
  const faltantes = [
    !f.categoria && 'la categoría',
    !f.marca && 'la marca',
    !f.nombre && 'el modelo',
    precioVacio(f.precioCompra) && 'el precio de compra',
    precioVacio(f.precioVenta) && 'el precio de venta',
  ].filter(Boolean);

  if (faltantes.length) {
    const lista = faltantes.length === 1
      ? faltantes[0]
      : `${faltantes.slice(0, -1).join(', ')} y ${faltantes.at(-1)}`;
    const soloPrecios = faltantes.every((c) => c.startsWith('el precio'));
    let titulo = 'Faltan datos de la prenda';
    if (faltantes.length === 1) titulo = `Falta ${faltantes[0]}`;
    else if (soloPrecios) titulo = 'Faltan los precios';
    avisar(`Completa ${lista} para agregar esta prenda.`, { titulo });
    return;
  }

  const tallasConCantidad = Object.entries(grilla.value).filter(([, n]) => n > 0);
  if (tallasConCantidad.length === 0) {
    avisar('Indica la cantidad de al menos una talla.', { titulo: 'Faltan cantidades' });
    return;
  }

  // Una línea por talla (así lo espera el guardado), todas con el mismo color y precios.
  tallasConCantidad.forEach(([talla, cantidad], i) => {
    prendas.value.push({
      id: Date.now() + i,
      ...f,
      talla,
      cantidad,
      colorNombre: (!sinColor.value && f.colorNombre.trim()) || 'Sin color',
      color: sinColor.value ? null : f.color,
      subtotal: cantidad * f.precioCompra,
    });
  });

  // Mantiene el producto (modelo/categoría/marca/precios) para agregar otro color rápido.
  // Solo resetea lo que suele cambiar entre colores (y las tallas, salvo que se pida repetirlas).
  prendaForm.value.color = null;
  prendaForm.value.colorNombre = '';
  if (repetir !== true) grilla.value = {};
  mostrarNuevoColor.value = false;

  if (repetir === true && !sinColor.value) irAlSiguiente('input[aria-label="Nombre del color"]'); // solo falta el color nuevo
};

const limpiarFormularioPrenda = () => {
  prendaForm.value = {
    productId: null,
    nombre: '',
    categoria: '',
    marca: '',
    color: null,
    colorNombre: '',
    precioCompra: '',
    precioVenta: '',
  };
  grilla.value = {};
  tallasExtra.value = [];
  tallaNueva.value = '';
  grupoTalla.value = 'ropa';
  grupoElegidoAMano = false;
  searchQuery.value = '';
  searchResults.value = [];
  coloresProducto.value = [];
  mostrarNuevoColor.value = false;
};

const eliminarPrenda = (id) => {
  prendas.value = prendas.value.filter(p => p.id !== id);
};

// Las líneas del mismo modelo, color y precios se muestran juntas, con sus tallas en una fila.
const grupos = computed(() => {
  const mapa = new Map();

  prendas.value.forEach((p) => {
    const clave = [p.productId ?? '', p.nombre, p.marca, p.colorNombre, p.precioCompra, p.precioVenta].join('|');
    if (!mapa.has(clave)) mapa.set(clave, { clave, ...p, lineas: [] });
    mapa.get(clave).lineas.push(p);
  });

  return [...mapa.values()].map((g) => ({
    ...g,
    unidades: g.lineas.reduce((sum, l) => sum + l.cantidad, 0),
    subtotalGrupo: g.lineas.reduce((sum, l) => sum + l.subtotal, 0),
  }));
});

const eliminarGrupo = (grupo) => {
  const ids = grupo.lineas.map((l) => l.id);
  prendas.value = prendas.value.filter((p) => !ids.includes(p.id));
};

// Devuelve el grupo al formulario (con su cuadrícula de tallas) para corregirlo.
// Repite un color ya agregado en otro color: mismas tallas, cantidades y precios; solo falta elegir el color.
const repetirGrupo = (grupo) => {
  const { lineas, unidades, subtotalGrupo, clave, talla, cantidad, subtotal, id, ...datos } = grupo;

  prendaForm.value = { ...datos, color: null, colorNombre: '' };
  searchQuery.value = datos.nombre;
  grilla.value = Object.fromEntries(lineas.map((l) => [l.talla, l.cantidad]));
  tallasExtra.value = lineas.map((l) => l.talla).filter((t) => !tallas.includes(t));
  mostrarNuevoColor.value = false;
  cargarColoresProducto();
  irAlSiguiente('input[aria-label="Nombre del color"]');
};

const editarGrupo = (grupo) => {
  const { lineas, unidades, subtotalGrupo, clave, talla, cantidad, subtotal, id, ...datos } = grupo;

  prendaForm.value = { ...datos };
  searchQuery.value = datos.nombre;
  grilla.value = Object.fromEntries(lineas.map((l) => [l.talla, l.cantidad]));
  tallasExtra.value = lineas.map((l) => l.talla).filter((t) => !tallas.includes(t));
  grupoTalla.value = GRUPOS_TALLA.find((g) => lineas.every((l) => g.tallas.includes(l.talla)))?.id ?? grupoTalla.value;
  grupoElegidoAMano = true;
  eliminarGrupo(grupo);
  cargarColoresProducto();
};

const totalCompra = () => prendas.value.reduce((sum, p) => sum + (p.cantidad * p.precioCompra), 0);
const totalPrendas = () => prendas.value.reduce((sum, p) => sum + p.cantidad, 0);

const guardarCompra = () => {
  if (prendas.value.length === 0) {
    avisar('Agrega al menos una prenda antes de guardar la compra.', { titulo: 'La compra está vacía' });
    return;
  }

  // Escribió un proveedor pero no lo eligió: se avisa en vez de guardar la compra sin proveedor en silencio.
  if (!proveedor.value && textoProveedor.value.trim()) {
    avisar('Elige el proveedor de la lista (pulsa «Usar como nuevo» o «Registrar…»), o borra lo escrito para guardar la compra sin proveedor.', { titulo: 'Falta elegir el proveedor' });
    return;
  }

  isSaving.value = true;

  router.post(route('admin.purchases.store'), {
    fecha: compraForm.value.fecha,
    tienda: proveedor.value?.nombre ?? '',
    proveedorId: proveedor.value?.id ?? null,
    proveedorDocTipo: proveedor.value?.tipo ?? null,
    proveedorDocNumero: proveedor.value?.numero ?? null,
    factura: compraForm.value.factura,
    notas: compraForm.value.notas,
    prendas: prendas.value,
  }, {
    onSuccess: () => {
      emit('close');
    },
    onError: (errors) => {
      const primerError = Object.values(errors)[0];
      avisar(primerError || 'Ocurrió un error al guardar la compra. Revisa los datos e intenta de nuevo.', { titulo: 'No se pudo guardar la compra', tipo: 'error' });
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};
</script>

<template>
  <div
    class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
    @mousedown="pulsoEnElFondo = $event.target === $event.currentTarget"
    @click.self="cerrarPorFondo"
  >
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Registrar Nueva Compra"
      class="modal-compra bg-gray-50 w-full max-w-6xl rounded-t-3xl sm:rounded-3xl shadow-2xl ring-1 ring-black/5 flex flex-col h-[94dvh] sm:h-auto sm:max-h-[94dvh] overflow-hidden"
    >
      <!-- Encabezado -->
      <header class="flex items-center gap-3 sm:gap-4 px-4 sm:px-6 py-3 sm:py-4 bg-surface border-b border-gray-100 flex-shrink-0">
        <div class="w-11 h-11 rounded-2xl bg-orange-50 text-[#ff8c42] flex items-center justify-center flex-shrink-0">
          <i class="fa-solid fa-cart-flatbed"></i>
        </div>
        <div class="min-w-0 flex-1">
          <h2 class="text-lg font-bold text-gray-900 leading-tight">Registrar Nueva Compra</h2>
          <p class="hidden sm:block text-sm text-gray-500">Elige la prenda, su color y tallas, y revisa el resumen antes de guardar.</p>
        </div>
        <button
          type="button"
          @click="emit('close')"
          aria-label="Cerrar"
          class="w-11 h-11 sm:w-10 sm:h-10 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center transition-colors flex-shrink-0"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </header>

      <div class="flex-1 min-h-0 overflow-y-auto lg:overflow-hidden lg:grid lg:grid-cols-[minmax(0,1fr)_23rem]">
        <!-- ======================= IZQUIERDA: pasos ======================= -->
        <div class="lg:overflow-y-auto p-3.5 sm:p-6 space-y-4 sm:space-y-5">

          <!-- Paso 1: prenda -->
          <section class="bg-surface rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
            <header class="flex items-center gap-3 mb-4">
              <span :class="['w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center transition-colors', paso1Listo ? 'bg-green-500 text-white' : 'bg-[#ff8c42] text-white']">
                <i v-if="paso1Listo" class="fa-solid fa-check text-[11px]"></i><template v-else>1</template>
              </span>
              <h3 class="font-bold text-gray-900">¿Qué prenda compraste?</h3>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <label for="compra-categoria" class="block text-sm font-semibold text-gray-700 mb-1.5">Categoría</label>
                <div v-if="prendaForm.productId" id="compra-categoria" class="h-11 px-4 flex items-center text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-xl">{{ prendaForm.categoria }}</div>
                <template v-else>
                  <SearchSelect id="compra-categoria" v-model="prendaForm.categoria" :options="opcionesCategoria" :can-create="esAdmin" placeholder="Escribe o elige la categoría" @create="abrirNuevaCategoria" />
                  <button v-if="esAdmin && !creandoCategoria" type="button" @click="abrirNuevaCategoria()" class="mt-1.5 inline-block text-xs font-semibold text-[#ff8c42] hover:text-[#e67e2d] hover:underline">+ Nueva categoría</button>
                  <p v-else-if="!esAdmin && opcionesCategoria.length === 0" class="mt-1.5 text-xs text-gray-400">No hay categorías: pídele a un administrador que las cree.</p>
                </template>
              </div>

              <div>
                <label for="compra-marca" class="block text-sm font-semibold text-gray-700 mb-1.5">Marca</label>
                <div v-if="prendaForm.productId" id="compra-marca" class="h-11 px-4 flex items-center text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-xl">{{ prendaForm.marca }}</div>
                <template v-else>
                  <SearchSelect id="compra-marca" v-model="prendaForm.marca" :options="opcionesMarca" :can-create="esAdmin && !!categoriaElegida" placeholder="Escribe o elige la marca" @create="crearMarcaDirecta" />
                  <button v-if="esAdmin && categoriaElegida && !creandoMarca" type="button" @click="abrirNuevaMarca()" class="mt-1.5 inline-block text-xs font-semibold text-[#ff8c42] hover:text-[#e67e2d] hover:underline">+ Nueva marca</button>
                  <p v-else-if="esAdmin && !categoriaElegida" class="mt-1.5 text-xs text-gray-400">Elige la categoría para poder crear una marca nueva.</p>
                </template>
              </div>

              <div class="relative md:col-span-2">
                <label for="compra-modelo" class="block text-sm font-semibold text-gray-700 mb-1.5">Modelo</label>

                <div v-if="prendaForm.productId" class="w-full min-h-11 px-4 py-2 border border-green-200 bg-green-50 rounded-xl flex items-center justify-between gap-3">
                  <div class="min-w-0">
                    <p class="font-semibold text-gray-900 truncate leading-tight">{{ prendaForm.nombre }}</p>
                    <p class="text-xs text-green-700 flex items-center gap-1">
                      <i class="fa-solid fa-circle-check"></i> Modelo existente, se sumará el stock
                    </p>
                  </div>
                  <button type="button" @click="limpiarSeleccion" aria-label="Quitar el modelo elegido" class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 hover:bg-surface flex items-center justify-center flex-shrink-0">
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
                    autocomplete="off"
                    placeholder="Busca el modelo (Ej: Stan Smith, Dri-Fit, All Star)"
                    class="w-full h-11 px-4 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100"
                  >
                  <div
                    v-if="showSuggestions && (searchQuery.trim().length >= 2 || prendaForm.categoria || prendaForm.marca)"
                    class="absolute z-20 mt-1.5 w-full bg-surface border border-gray-100 rounded-xl shadow-xl ring-1 ring-black/5 p-1 max-h-60 overflow-y-auto"
                  >
                    <div v-if="isSearching" class="px-3 py-2.5 text-sm text-gray-400">Buscando...</div>
                    <template v-else>
                      <button
                        v-for="p in searchResults"
                        :key="p.id"
                        type="button"
                        @mousedown.prevent="seleccionarProducto(p)"
                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-orange-50 transition-colors"
                      >
                        <p class="text-sm font-semibold text-gray-900">{{ p.nombre }}</p>
                        <p class="text-xs text-gray-400">{{ p.marca }} · {{ p.categoria || 'Sin categoría' }} · Stock: {{ p.stock }}</p>
                      </button>
                      <div v-if="searchResults.length === 0" class="px-3 py-2.5 text-xs text-gray-500">
                        <template v-if="searchQuery.trim().length >= 2">No hay un modelo llamado <strong>«{{ searchQuery.trim() }}»</strong>.</template>
                        <template v-else>Aún no hay modelos para esta combinación.</template>
                        <button
                          v-if="esAdmin && marcaElegida"
                          type="button"
                          @mousedown.prevent="crearModeloDirecto(searchQuery.trim())"
                          class="block mt-1.5 font-semibold text-[#ff8c42] hover:underline"
                        >+ Crear {{ searchQuery.trim().length >= 2 ? `«${searchQuery.trim()}»` : 'un modelo nuevo' }}</button>
                        <span v-else-if="esAdmin" class="block mt-1.5 text-gray-400">Para crearlo, elige primero la categoría y la marca.</span>
                      </div>
                    </template>
                  </div>
                  <div v-if="esAdmin && !creandoModelo">
                    <button v-if="marcaElegida" type="button" @click="abrirNuevoModelo(searchQuery.trim())" class="mt-1.5 inline-block text-xs font-semibold text-[#ff8c42] hover:text-[#e67e2d] hover:underline">+ Nuevo modelo</button>
                    <p v-else class="mt-1.5 text-xs text-gray-400">Para crear un modelo nuevo, primero elige la categoría y la marca.</p>
                  </div>
                </template>
              </div>
            </div>

            <!-- Crear categoría a propósito (solo administradores) -->
            <div v-if="creandoCategoria" class="mt-5 rounded-2xl border border-orange-200 bg-gradient-to-br from-orange-50 to-surface p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-bold text-gray-900">Nueva categoría</h4>
                <button type="button" @click="creandoCategoria = false" class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 hover:bg-surface" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div class="md:col-span-1 md:self-start">
                  <label for="categoria-nueva-nombre" class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre</label>
                  <input id="categoria-nueva-nombre" v-model="categoriaNueva.name" @keydown.enter.prevent="crearCategoria" type="text" placeholder="Ej: Polos" class="w-full h-11 px-4 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
                </div>
                <div class="md:col-span-2">
                  <span class="block text-xs font-semibold text-gray-600 mb-1.5">¿Qué tallas usa? <span class="font-normal text-gray-400">(elige según lo que vende)</span></span>
                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" role="group" aria-label="Tipo de tallas">
                    <button
                      v-for="g in GRUPOS_TALLA"
                      :key="g.id"
                      type="button"
                      @click="categoriaNueva.sizeType = g.id"
                      :aria-pressed="categoriaNueva.sizeType === g.id"
                      :class="['flex flex-col items-center gap-0.5 rounded-xl border-2 px-2 py-2.5 text-center transition-all', categoriaNueva.sizeType === g.id ? 'border-[#ff8c42] bg-surface shadow-sm' : 'border-gray-200 bg-surface/60 hover:border-gray-300']"
                    >
                      <i :class="[g.icono, categoriaNueva.sizeType === g.id ? 'text-[#ff8c42]' : 'text-gray-400']" class="text-sm"></i>
                      <span class="text-sm font-bold text-gray-900">{{ g.nombre }}</span>
                      <span class="text-[11px] font-semibold text-gray-600">{{ g.detalle }}</span>
                      <span class="text-[11px] text-gray-400 leading-tight">Ej: {{ g.ejemplo }}</span>
                    </button>
                  </div>
                </div>
                <div class="flex justify-end md:col-span-3">
                  <button type="button" @click="crearCategoria" :disabled="guardandoCategoria" class="bg-[#ff8c42] hover:bg-[#f27d30] disabled:opacity-60 text-white text-sm font-bold h-11 px-5 rounded-xl transition-colors">
                    {{ guardandoCategoria ? 'Creando...' : 'Crear categoría' }}
                  </button>
                </div>
              </div>
              <p v-if="errorCategoria" role="alert" class="flex items-center gap-1.5 text-xs text-red-600 mt-2"><i class="fa-solid fa-circle-exclamation"></i> {{ errorCategoria }}</p>
            </div>

            <!-- Agregar marca a la categoría (solo administradores) -->
            <div v-if="creandoMarca" class="mt-5 rounded-2xl border border-orange-200 bg-gradient-to-br from-orange-50 to-surface p-4">
              <div class="flex items-center justify-between mb-1">
                <h4 class="text-sm font-bold text-gray-900">Nueva marca en {{ prendaForm.categoria }}</h4>
                <button type="button" @click="creandoMarca = false" class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 hover:bg-surface" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
              </div>
              <p class="text-xs text-gray-500 mb-3">Elige una marca que ya usas en otra categoría o escribe una nueva. Por ejemplo: Nike, Adidas, Gap.</p>
              <div class="flex flex-wrap items-start gap-3">
                <div class="flex-1 min-w-[14rem]">
                  <SearchCreateInput v-model="marcaNueva" :options="marcasDisponibles" placeholder="Ej: Nike, Adidas, Gap..." />
                </div>
                <button type="button" @click="crearMarca()" :disabled="guardandoMarca" class="bg-[#ff8c42] hover:bg-[#f27d30] disabled:opacity-60 text-white text-sm font-bold h-11 px-5 rounded-xl transition-colors">
                  {{ guardandoMarca ? 'Agregando...' : 'Agregar marca' }}
                </button>
              </div>
              <p v-if="errorMarca" role="alert" class="flex items-center gap-1.5 text-xs text-red-600 mt-2"><i class="fa-solid fa-circle-exclamation"></i> {{ errorMarca }}</p>
            </div>

            <!-- Crear modelo en la categoría y marca elegidas (solo administradores) -->
            <div v-if="creandoModelo" class="mt-5 rounded-2xl border border-orange-200 bg-gradient-to-br from-orange-50 to-surface p-4">
              <div class="flex items-center justify-between mb-1">
                <h4 class="text-sm font-bold text-gray-900">Nuevo modelo de {{ prendaForm.marca }}</h4>
                <button type="button" @click="creandoModelo = false" class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 hover:bg-surface" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
              </div>
              <p class="text-xs text-gray-500 mb-3">Dentro de {{ prendaForm.categoria }} › {{ prendaForm.marca }}. Después eliges su color y sus tallas.</p>
              <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-start gap-2.5 sm:gap-3">
                <input
                  v-model="modeloNuevo"
                  @keydown.enter.prevent="crearModelo()"
                  type="text"
                  maxlength="255"
                  aria-label="Nombre del modelo nuevo"
                  placeholder="Ej: Stan Smith, Dri-Fit, All Star"
                  class="w-full h-11 px-4 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100 flex-1 min-w-[14rem]"
                >
                <button type="button" @click="crearModelo()" :disabled="guardandoModelo" class="w-full sm:w-auto bg-[#ff8c42] hover:bg-[#f27d30] disabled:opacity-60 text-white text-sm font-bold h-12 sm:h-11 px-5 rounded-xl transition-colors">
                  {{ guardandoModelo ? 'Creando...' : 'Crear modelo' }}
                </button>
              </div>
              <p v-if="errorModelo" role="alert" class="flex items-center gap-1.5 text-xs text-red-600 mt-2"><i class="fa-solid fa-circle-exclamation"></i> {{ errorModelo }}</p>
            </div>
          </section>

          <!-- Paso 2: color (no aplica en categorías sin tallas ni color) -->
          <section v-if="!sinColor" class="bg-surface rounded-2xl border border-gray-100 shadow-sm p-5">
            <header class="flex items-center gap-3 mb-4">
              <span :class="['w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center transition-colors', paso2Listo ? 'bg-green-500 text-white' : 'bg-[#ff8c42] text-white']">
                <i v-if="paso2Listo" class="fa-solid fa-check text-[11px]"></i><template v-else>2</template>
              </span>
              <h3 class="font-bold text-gray-900">Color</h3>
              <span v-if="prendaForm.colorNombre" class="ml-auto flex items-center gap-2 text-sm font-semibold text-gray-600">
                <ColorSwatch :hex="prendaForm.color" class="w-4 h-4 rounded-full border border-gray-200" /> {{ prendaForm.colorNombre }}
              </span>
            </header>

            <!-- Colores ya registrados para este modelo: reusarlos evita crear duplicados
                 como "Negro" cuando ya existe "Negro con blanco". -->
            <div v-if="coloresProducto.length > 0 && !mostrarNuevoColor">
              <div class="flex gap-2 flex-wrap items-center">
                <button v-for="c in coloresProducto" :key="c.id"
                  @click="seleccionarColorExistente(c)"
                  type="button"
                  :aria-pressed="prendaForm.colorNombre === c.nombre"
                  :class="['flex items-center gap-2 pl-1.5 pr-3.5 py-1.5 rounded-full border-2 transition-all',
                           prendaForm.colorNombre === c.nombre ? 'border-[#ff8c42] bg-orange-50' : 'border-gray-200 hover:border-gray-300']">
                  <span class="w-7 h-7 rounded-full border border-gray-200 overflow-hidden flex-shrink-0 inline-block">
                    <img v-if="c.imagenUrl" :src="c.imagenUrl" :alt="c.nombre" class="w-full h-full object-cover">
                    <ColorSwatch v-else :hex="c.hex" class="w-full h-full" />
                  </span>
                  <span class="text-sm font-semibold text-gray-700">{{ c.nombre }}</span>
                </button>
                <button type="button" @click="mostrarNuevoColor = true" class="text-sm font-semibold text-[#ff8c42] hover:underline px-2 py-1.5">
                  + Otro color
                </button>
              </div>
            </div>

            <div v-else>
              <button v-if="coloresProducto.length > 0" type="button" @click="mostrarNuevoColor = false" class="text-xs text-gray-500 hover:text-gray-700 mb-2 flex items-center gap-1">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Ver colores ya registrados
              </button>
              <input aria-label="Nombre del color"
                v-model="prendaForm.colorNombre"
                type="text"
                placeholder="Nombre del color, ej: Negro con blanco"
                class="w-full h-11 px-4 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100 mb-3"
              >
              <p class="text-xs font-semibold text-gray-500 mb-2">O elige un color de referencia <span class="font-normal text-gray-400">(completa el nombre)</span></p>
              <div class="flex gap-2 sm:gap-1.5 flex-wrap">
                <button v-for="color in paletaColores" :key="color.nombre"
                  @click="seleccionarColorPaleta(color)"
                  type="button"
                  :aria-label="color.nombre"
                  :aria-pressed="prendaForm.color === color.hex"
                  :class="['w-10 h-10 sm:w-8 sm:h-8 rounded-full border-2 transition-all flex items-center justify-center',
                           prendaForm.color === color.hex ? 'border-gray-900 ring-2 ring-offset-2 ring-[#ff8c42] scale-105' : 'border-gray-200 hover:scale-110',
                           color.hex === null ? 'bg-surface' : '']"
                  :style="color.hex ? { backgroundColor: color.hex } : {}"
                  :title="color.nombre">
                  <i v-if="color.hex === null" class="fa-solid fa-ban text-gray-300 text-xs"></i>
                </button>
              </div>
            </div>
          </section>

          <!-- Paso 3: tallas y cantidades -->
          <section class="bg-surface rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
            <header class="flex flex-wrap items-center gap-3 mb-4">
              <span :class="['w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center transition-colors', paso3Listo ? 'bg-green-500 text-white' : 'bg-[#ff8c42] text-white']">
                <i v-if="paso3Listo" class="fa-solid fa-check text-[11px]"></i><template v-else>3</template>
              </span>
              <h3 class="font-bold text-gray-900">{{ sinColor ? 'Cantidad' : 'Tallas y cantidades' }}</h3>
              <div class="flex sm:inline-flex w-full sm:w-auto p-0.5 bg-gray-100 rounded-lg overflow-x-auto" role="group" aria-label="Tipo de tallas">
                <button
                  v-for="g in GRUPOS_TALLA"
                  :key="g.id"
                  type="button"
                  @click="elegirGrupoTalla(g.id)"
                  :aria-pressed="grupoTalla === g.id"
                  :class="['px-3 h-10 sm:h-auto sm:py-1.5 flex-shrink-0 whitespace-nowrap text-xs font-bold rounded-md transition-all', grupoTalla === g.id ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
                >{{ g.nombre }}</button>
              </div>
              <span class="ml-auto text-xs text-gray-500">
                <template v-if="unidadesEnGrilla > 0"><strong class="text-gray-900">{{ unidadesEnGrilla }}</strong> unidad{{ unidadesEnGrilla === 1 ? '' : 'es' }}</template>
                <template v-else>Escribe la cantidad en cada talla que compraste</template>
              </span>
            </header>

            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5">
              <div
                v-for="t in tallasMostradas"
                :key="t"
                :class="[
                  'rounded-xl border-2 px-2 pt-2 pb-2 transition-colors focus-within:ring-4 focus-within:ring-orange-100',
                  grilla[t] ? 'border-[#ff8c42] bg-orange-50' : 'border-gray-200 bg-surface hover:border-gray-300 focus-within:border-[#ff8c42]',
                ]"
              >
                <p :class="['text-center text-xs font-bold mb-1', grilla[t] ? 'text-[#e67e2d]' : 'text-gray-500']">{{ t }}</p>

                <div class="flex items-center justify-between gap-1">
                  <button
                    type="button"
                    @click="cambiarCantidad(t, -1)"
                    :disabled="!grilla[t]"
                    :aria-label="`Restar 1 a la talla ${t}`"
                    class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 bg-surface border border-gray-200 hover:border-[#ff8c42] hover:text-[#ff8c42] active:scale-95 disabled:opacity-30 disabled:hover:border-gray-200 disabled:hover:text-gray-500 disabled:cursor-not-allowed transition-all"
                  >
                    <i class="fa-solid fa-minus text-xs"></i>
                  </button>

                  <input
                    :value="grilla[t] || ''"
                    @input="setCantidad(t, $event.target.value)"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    placeholder="0"
                    :aria-label="`Cantidad talla ${t}`"
                    class="w-full min-w-0 text-center text-base font-bold bg-transparent border-0 p-0 focus:ring-0 focus:outline-none placeholder:text-gray-300 text-gray-900 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                  >

                  <button
                    type="button"
                    @click="cambiarCantidad(t, 1)"
                    :aria-label="`Sumar 1 a la talla ${t}`"
                    class="w-9 h-9 rounded-lg flex items-center justify-center text-white bg-[#ff8c42] hover:bg-[#f27d30] active:scale-95 shadow-sm transition-all"
                  >
                    <i class="fa-solid fa-plus text-xs"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 mt-3">
              <input
                v-model="tallaNueva"
                @keydown.enter.prevent="agregarTallaNueva"
                type="text"
                maxlength="20"
                placeholder="Otra talla (ej. 42)"
                aria-label="Agregar otra talla"
                class="w-40 h-9 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100 bg-surface"
              >
              <button type="button" @click="agregarTallaNueva" class="text-sm font-semibold text-[#ff8c42] hover:underline">+ Agregar talla</button>
            </div>
          </section>

          <!-- Paso 4: precios y agregar -->
          <section class="bg-surface rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
            <header class="flex items-center gap-3 mb-4">
              <span :class="['w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center transition-colors', paso4Listo ? 'bg-green-500 text-white' : 'bg-[#ff8c42] text-white']">
                <i v-if="paso4Listo" class="fa-solid fa-check text-[11px]"></i><template v-else>4</template>
              </span>
              <h3 class="font-bold text-gray-900">Precios</h3>
              <span class="ml-auto text-xs text-gray-500">Por unidad, para todas las tallas de este color</span>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label for="registrar-compra-modal-6" class="block text-sm font-semibold text-gray-700 mb-1.5">Precio Compra</label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none">S/</span>
                  <input id="registrar-compra-modal-6" v-model.number="prendaForm.precioCompra" type="number" step="0.01" min="0" placeholder="0.00" class="w-full h-11 px-4 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100 pl-10">
                </div>
              </div>
              <div>
                <label for="registrar-compra-modal-7" class="block text-sm font-semibold text-gray-700 mb-1.5">Precio Venta</label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none">S/</span>
                  <input id="registrar-compra-modal-7" v-model.number="prendaForm.precioVenta" type="number" step="0.01" min="0" placeholder="0.00" class="w-full h-11 px-4 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100 pl-10">
                </div>
              </div>
              <div>
                <span class="block text-sm font-semibold text-gray-700 mb-1.5">Margen</span>
                <div
                  :class="[
                    'h-11 rounded-xl flex items-center justify-center gap-1.5 text-sm font-bold border',
                    margenPorcentaje === null || !prendaForm.precioVenta ? 'bg-gray-50 border-gray-200 text-gray-400'
                      : margenPorcentaje > 0 ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-600',
                  ]"
                >
                  <i v-if="margenPorcentaje !== null && prendaForm.precioVenta" :class="['fa-solid text-xs', margenPorcentaje > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down']"></i>
                  {{ margenPorcentaje !== null && prendaForm.precioVenta ? margenPorcentaje + '%' : '—' }}
                </div>
              </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:flex-wrap sm:items-center sm:justify-end gap-2.5 mt-5 pt-4 border-t border-gray-100">
              <button
                @click="limpiarFormularioPrenda"
                type="button"
                class="flex items-center justify-center gap-2 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-100 h-11 px-4 rounded-xl transition-colors"
              >
                <i class="fa-solid fa-broom text-xs"></i> Limpiar
              </button>
              <button
                @click="agregarPrenda(true)"
                :disabled="unidadesEnGrilla === 0"
                type="button"
                title="Agrega estas tallas y deja las mismas cantidades listas para otro color"
                class="flex items-center justify-center gap-2 text-[#e67e2d] border border-orange-200 hover:bg-orange-50 disabled:opacity-40 disabled:cursor-not-allowed font-bold text-sm h-12 sm:h-11 px-5 rounded-xl transition-colors"
              >
                <i class="fa-solid fa-clone text-xs"></i> Agregar y repetir en otro color
              </button>
              <button
                @click="agregarPrenda()"
                type="button"
                class="flex items-center justify-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white font-bold text-base sm:text-sm h-12 sm:h-11 px-6 rounded-xl shadow-sm shadow-orange-200 transition-all"
              >
                <i class="fa-solid fa-plus"></i>
                {{ unidadesEnGrilla > 0 ? `Agregar ${unidadesEnGrilla} prenda${unidadesEnGrilla === 1 ? '' : 's'}` : 'Agregar Prenda' }}
              </button>
            </div>
          </section>
        </div>

        <!-- ======================= DERECHA: resumen ======================= -->
        <aside id="resumen-compra" class="bg-surface border-t lg:border-t-0 lg:border-l border-gray-100 flex flex-col lg:min-h-0 scroll-mt-2" aria-label="Resumen de la compra">
          <div class="flex-1 lg:overflow-y-auto p-5 space-y-5">
            <div class="flex items-center justify-between">
              <h3 class="font-bold text-gray-900">Resumen de la compra</h3>
              <span class="text-xs font-bold text-[#e67e2d] bg-orange-50 rounded-full px-2.5 py-1">{{ totalPrendas() }} prenda{{ totalPrendas() === 1 ? '' : 's' }}</span>
            </div>

            <!-- Datos de la compra -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label for="registrar-compra-modal-1" class="block text-xs font-semibold text-gray-600 mb-1">Fecha</label>
                <input id="registrar-compra-modal-1" v-model="compraForm.fecha" type="date" class="w-full h-11 sm:h-10 px-3 text-sm border border-gray-200 rounded-xl bg-surface focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
              </div>
              <div>
                <label for="registrar-compra-modal-3" class="block text-xs font-semibold text-gray-600 mb-1">Factura # (opcional)</label>
                <input id="registrar-compra-modal-3" v-model="compraForm.factura" type="text" placeholder="F-001234" class="w-full h-11 sm:h-10 px-3 text-sm border border-gray-200 rounded-xl bg-surface placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
              </div>
              <div class="col-span-2">
                <ProveedorField v-model="proveedor" v-model:texto="textoProveedor" />
              </div>
              <div class="col-span-2">
                <label for="registrar-compra-modal-4" class="block text-xs font-semibold text-gray-600 mb-1">Notas (opcional)</label>
                <input id="registrar-compra-modal-4" v-model="compraForm.notas" type="text" placeholder="Cualquier detalle importante..." class="w-full h-11 sm:h-10 px-3 text-sm border border-gray-200 rounded-xl bg-surface placeholder:text-gray-400 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100">
              </div>
            </div>

            <!-- Prendas agregadas -->
            <div>
              <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-2.5">Prendas agregadas</p>

              <div v-if="grupos.length === 0" class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/60 px-4 py-8 text-center">
                <div class="w-11 h-11 mx-auto mb-3 rounded-2xl bg-surface shadow-sm text-gray-300 flex items-center justify-center"><i class="fa-solid fa-shirt"></i></div>
                <p class="text-sm font-semibold text-gray-600">Aún no agregaste prendas</p>
                <p class="text-xs text-gray-400 mt-1">Completa los 4 pasos y pulsa «Agregar».</p>
              </div>

              <ul v-else class="space-y-2.5">
                <li v-for="g in grupos" :key="g.clave" data-grupo class="rounded-2xl border border-gray-100 bg-gray-50/60 p-3.5">
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                      <p class="font-semibold text-gray-900 truncate leading-tight">{{ g.nombre }}</p>
                      <p class="text-xs text-gray-400 truncate">{{ g.marca }} · {{ g.categoria }}</p>
                    </div>
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 bg-surface border border-gray-200 rounded-full pl-1.5 pr-2.5 py-0.5 flex-shrink-0">
                      <ColorSwatch :hex="g.color" class="w-3.5 h-3.5 rounded-full border border-gray-200" /> {{ g.colorNombre }}
                    </span>
                  </div>

                  <div class="flex flex-wrap gap-1 mt-2.5">
                    <span
                      v-for="l in g.lineas"
                      :key="l.id"
                      class="inline-flex items-center gap-1 text-xs font-semibold bg-surface border border-gray-200 rounded-md pl-2 pr-1 py-0.5 text-gray-700"
                    >
                      {{ l.talla }} <span class="text-[#e67e2d]">×{{ l.cantidad }}</span>
                      <button type="button" @click="eliminarPrenda(l.id)" class="w-4 h-4 rounded text-gray-300 hover:text-red-500 hover:bg-red-50 flex items-center justify-center" :aria-label="`Quitar talla ${l.talla}`">
                        <i class="fa-solid fa-xmark text-[9px]"></i>
                      </button>
                    </span>
                  </div>

                  <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-gray-100">
                    <div>
                      <p class="text-sm font-bold text-gray-900 tabular-nums">S/ {{ g.subtotalGrupo.toFixed(2) }}</p>
                      <p class="text-[11px] text-gray-400 tabular-nums">compra S/ {{ parseFloat(g.precioCompra).toFixed(2) }} · venta S/ {{ parseFloat(g.precioVenta).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-center gap-0.5">
                      <button @click="repetirGrupo(g)" type="button" class="w-10 h-10 sm:w-8 sm:h-8 rounded-lg text-gray-400 hover:text-[#ff8c42] hover:bg-orange-50 flex items-center justify-center transition-colors" title="Repetir estas tallas en otro color" aria-label="Repetir estas tallas en otro color">
                        <i class="fa-solid fa-clone text-sm"></i>
                      </button>
                      <button @click="editarGrupo(g)" type="button" class="w-10 h-10 sm:w-8 sm:h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition-colors" title="Editar" aria-label="Editar">
                        <i class="fa-solid fa-pen text-sm"></i>
                      </button>
                      <button @click="eliminarGrupo(g)" type="button" class="w-10 h-10 sm:w-8 sm:h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition-colors" title="Eliminar" aria-label="Eliminar">
                        <i class="fa-solid fa-trash text-sm"></i>
                      </button>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <!-- Totales y guardar: siempre visibles -->
          <footer class="max-lg:hidden border-t border-gray-100 bg-surface p-5 space-y-4 flex-shrink-0">
            <dl class="space-y-1.5 text-sm">
              <div class="flex justify-between"><dt class="text-gray-500">Total prendas</dt><dd class="font-semibold text-gray-900 tabular-nums">{{ totalPrendas() }}</dd></div>
              <div class="flex justify-between"><dt class="text-gray-500">Utilidad aprox.</dt><dd class="font-semibold text-green-600 tabular-nums">S/ {{ utilidadTotal().toFixed(2) }}</dd></div>
              <div class="flex justify-between items-baseline pt-2 border-t border-gray-100"><dt class="font-bold text-gray-900">Total compra</dt><dd class="text-2xl font-black text-[#ff8c42] tabular-nums">S/ {{ totalCompra().toFixed(2) }}</dd></div>
            </dl>

            <div class="flex gap-2.5">
              <button
                @click="emit('close')"
                :disabled="isSaving"
                type="button"
                class="h-12 px-5 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 disabled:opacity-50 rounded-xl transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="guardarCompra"
                :disabled="isSaving || prendas.length === 0"
                type="button"
                class="flex-1 h-12 flex items-center justify-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl shadow-md shadow-orange-200 transition-all"
              >
                <i :class="isSaving ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-floppy-disk'"></i> {{ isSaving ? 'Guardando...' : 'Guardar Compra' }}
              </button>
            </div>
          </footer>
        </aside>
      </div>

      <!-- Celular: total y «Guardar compra» siempre a la vista, sin tener que bajar hasta el resumen -->
      <div class="lg:hidden flex-shrink-0 bg-surface border-t border-gray-100 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] flex items-center gap-3">
        <div class="min-w-0 flex-1 leading-tight">
          <p class="text-xs text-gray-500 truncate">{{ totalPrendas() }} prenda{{ totalPrendas() === 1 ? '' : 's' }} · utilidad aprox. S/ {{ utilidadTotal().toFixed(2) }}</p>
          <p class="text-xl font-black text-[#ff8c42] tabular-nums">S/ {{ totalCompra().toFixed(2) }}</p>
        </div>
        <button
          type="button"
          class="relative w-12 h-12 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center flex-shrink-0"
          aria-label="Ir al resumen de la compra"
          @click="irAlResumen"
        >
          <i class="fa-solid fa-list-check" aria-hidden="true"></i>
          <span v-if="totalPrendas() > 0" class="absolute -top-1.5 -right-1.5 min-w-[1.25rem] h-5 px-1 rounded-full bg-[#ff8c42] text-white text-[11px] font-bold flex items-center justify-center">{{ totalPrendas() }}</span>
        </button>
        <button
          @click="guardarCompra"
          :disabled="isSaving || prendas.length === 0"
          type="button"
          class="h-12 px-5 flex items-center justify-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl shadow-md shadow-orange-200 transition-all flex-shrink-0"
        >
          <i :class="isSaving ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-floppy-disk'" aria-hidden="true"></i> {{ isSaving ? 'Guardando…' : 'Guardar' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-compra {
  animation: compra-in 200ms cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes compra-in {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.985);
  }
}

@media (prefers-reduced-motion: reduce) {
  .modal-compra {
    animation: none;
  }
}
</style>
