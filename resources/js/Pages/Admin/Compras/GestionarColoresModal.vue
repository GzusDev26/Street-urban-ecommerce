<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import ColorPhoto from '@/Components/Admin/ColorPhoto.vue';
import { GRUPOS_COLOR, PALETA, esColorClaro } from '@/Components/Admin/colores';

const props = defineProps({
  producto: { type: Object, required: true }, // { id, nombre, marca }
});

const emit = defineEmits(['close']);

const colores = ref([]);
const loading = ref(true);

const paleta = PALETA;

const cargarColores = async () => {
  loading.value = true;
  try {
    const res = await fetch(`/admin/catalogo/${props.producto.id}/colores`);
    colores.value = await res.json();
  } catch (e) {
    console.error('No se pudieron cargar los colores', e);
    colores.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(cargarColores);

const showForm = ref(false);
const editandoColor = ref(null); // color object o null si es nuevo
const formNombre = ref('');
const formHex = ref(null); // null = sin color de referencia elegido todavía
// Último nombre puesto automáticamente al elegir un color de la paleta: solo se reemplaza si el
// usuario no escribió uno propio (ej. una combinación como "Blanco / Negro").
const nombreAutomatico = ref('');
const formImagen = ref(null);
const previewUrl = ref(null);
const isSaving = ref(false);
const error = ref('');
const fileInput = ref(null);

// Modo "varios a la vez": se marcan colores de la paleta y se crean todos con un solo clic.
const modoVarios = ref(false);
const seleccion = ref([]); // hex de los colores de la paleta marcados
const guardandoVarios = ref(false);

const nombresExistentes = computed(() => new Set(colores.value.map((c) => c.nombre.trim().toLowerCase())));
const yaRegistrado = (color) => nombresExistentes.value.has(color.nombre.toLowerCase());

const alternarSeleccion = (color) => {
  if (yaRegistrado(color)) return;

  seleccion.value = seleccion.value.includes(color.hex)
    ? seleccion.value.filter((h) => h !== color.hex)
    : [...seleccion.value, color.hex];
};

const guardarVarios = () => {
  const elegidos = paleta.filter((p) => seleccion.value.includes(p.hex));
  if (elegidos.length === 0) return;

  guardandoVarios.value = true;
  error.value = '';

  router.post(route('admin.catalogo.colores.store-many', props.producto.id), { colores: elegidos }, {
    preserveScroll: true,
    onSuccess: () => {
      cerrarForm();
      cargarColores();
    },
    onError: (errors) => {
      error.value = Object.values(errors)[0] || 'No se pudieron agregar los colores.';
    },
    onFinish: () => {
      guardandoVarios.value = false;
    },
  });
};

const abrirNuevo = () => {
  editandoColor.value = null;
  formNombre.value = '';
  formHex.value = null;
  nombreAutomatico.value = '';
  formImagen.value = null;
  previewUrl.value = null;
  error.value = '';
  seleccion.value = [];
  showForm.value = true;
};

const abrirEditar = (color) => {
  modoVarios.value = false;
  editandoColor.value = color;
  formNombre.value = color.nombre;
  formHex.value = color.hex || null;
  nombreAutomatico.value = '';
  formImagen.value = null;
  previewUrl.value = color.imagenUrl;
  error.value = '';
  showForm.value = true;
};

const elegirDePaleta = (color) => {
  formHex.value = color.hex;

  if (!formNombre.value.trim() || formNombre.value === nombreAutomatico.value) {
    formNombre.value = color.nombre;
    nombreAutomatico.value = color.nombre;
  }
};

const elegirPersonalizado = (hex) => {
  formHex.value = hex;
};

// Nombre del color de referencia elegido, para mostrarlo junto a la paleta.
const nombreReferencia = computed(() => {
  if (!formHex.value) return '';
  return paleta.find((p) => p.hex.toLowerCase() === formHex.value.toLowerCase())?.nombre ?? 'Personalizado';
});

const cerrarForm = () => {
  showForm.value = false;
  modoVarios.value = false;
  seleccion.value = [];
  editandoColor.value = null;
  if (fileInput.value) fileInput.value.value = '';
};

const onImagenSeleccionada = (e) => {
  const file = e.target.files[0];
  formImagen.value = file || null;
  previewUrl.value = file ? URL.createObjectURL(file) : (editandoColor.value?.imagenUrl || null);
};

const guardarColor = (otro = false) => {
  if (!formNombre.value.trim()) {
    error.value = 'Ponle un nombre al color.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  const data = {
    nombre: formNombre.value.trim(),
    hex: formHex.value ?? undefined,
    imagen: formImagen.value,
  };

  const url = editandoColor.value
    ? route('admin.catalogo.colores.update', editandoColor.value.id)
    : route('admin.catalogo.colores.store', props.producto.id);

  router.post(url, data, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      cargarColores();

      // "Guardar y agregar otro": se deja el formulario abierto y limpio para el siguiente color.
      if (otro === true) {
        abrirNuevo();
      } else {
        cerrarForm();
      }
    },
    onError: (errors) => {
      error.value = Object.values(errors)[0] || 'Ocurrió un error al guardar el color.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};

const colorPendienteEliminar = ref(null);
const eliminando = ref(false);
const errorLista = ref('');

const eliminarColor = (color) => {
  errorLista.value = '';
  colorPendienteEliminar.value = color;
};

const confirmarEliminacion = () => {
  eliminando.value = true;

  router.delete(route('admin.catalogo.colores.destroy', colorPendienteEliminar.value.id), {
    preserveScroll: true,
    onSuccess: () => cargarColores(),
    onError: (errors) => {
      errorLista.value = Object.values(errors)[0] || 'No se pudo eliminar el color.';
    },
    onFinish: () => {
      eliminando.value = false;
      colorPendienteEliminar.value = null;
    },
  });
};
</script>

<template>
  <ModalShell
    title="Colores del modelo"
    :subtitle="`${producto.marca} · ${producto.nombre}`"
    icon="fa-solid fa-palette"
    size="lg"
    :show-footer="false"
    @close="emit('close')"
    @submit="guardarColor()"
  >
    <div v-if="loading" class="flex items-center justify-center gap-2 text-sm text-gray-400 py-10">
      <i class="fa-solid fa-spinner fa-spin"></i> Cargando colores...
    </div>

    <template v-else>
      <div v-if="errorLista" role="alert" class="flex items-start gap-2.5 bg-red-50 text-red-700 rounded-xl px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <span>{{ errorLista }}</span>
      </div>

      <div v-if="colores.length === 0 && !showForm" class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/60 py-8 px-6 text-center">
        <div class="w-12 h-12 rounded-2xl bg-white shadow-sm text-gray-300 flex items-center justify-center mx-auto mb-3">
          <i class="fa-solid fa-palette text-xl"></i>
        </div>
        <p class="text-sm font-semibold text-gray-700">Este modelo aún no tiene colores</p>
        <p class="text-xs text-gray-500 mt-1">Agrega el primero para poder registrar compras y ventas.</p>
      </div>

      <ul v-else-if="colores.length > 0" class="space-y-2">
        <li
          v-for="c in colores"
          :key="c.id"
          class="flex items-center gap-3.5 border border-gray-100 rounded-2xl p-3 hover:border-gray-200 hover:bg-gray-50/60 transition-colors"
        >
          <ColorPhoto :color="c" editable @uploaded="cargarColores" />

          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ c.nombre }}</p>
            <p class="flex items-center gap-1.5 text-xs mt-0.5" :class="c.imagenUrl ? 'text-gray-400' : 'text-[#e67e2d] font-semibold'">
              <ColorSwatch :hex="c.hex" class="w-2.5 h-2.5 rounded-full border border-gray-200" />
              {{ c.imagenUrl ? 'Toca la foto para cambiarla' : 'Sin foto · toca el recuadro para subirla' }}
            </p>
          </div>

          <div class="flex items-center gap-1 flex-shrink-0">
            <ActionButton icon="fa-solid fa-pen" label="Editar" tone="blue" @click="abrirEditar(c)" />
            <ActionButton icon="fa-solid fa-trash" label="Eliminar" tone="red" @click="eliminarColor(c)" />
          </div>
        </li>
      </ul>

      <button
        v-if="!showForm"
        @click="abrirNuevo"
        type="button"
        class="w-full flex items-center justify-center gap-2 text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 border-2 border-dashed border-orange-200 hover:border-orange-300 rounded-2xl py-3.5 transition-colors"
      >
        <i class="fa-solid fa-plus text-xs"></i> Agregar color
      </button>

      <div v-if="showForm && !editandoColor" class="inline-flex p-1 bg-gray-100 rounded-xl self-start" role="tablist" aria-label="Cómo agregar colores">
        <button
          v-for="op in [{ v: false, t: 'Uno solo', i: 'fa-solid fa-image' }, { v: true, t: 'Varios a la vez', i: 'fa-solid fa-layer-group' }]"
          :key="String(op.v)"
          type="button"
          role="tab"
          :aria-selected="modoVarios === op.v"
          @click="modoVarios = op.v; error = ''"
          :class="[
            'flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-semibold transition-all',
            modoVarios === op.v ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700',
          ]"
        >
          <i :class="op.i" class="text-xs"></i> {{ op.t }}
        </button>
      </div>

      <!-- Varios a la vez: se marcan colores de la paleta y se crean todos juntos -->
      <section v-if="showForm && modoVarios && !editandoColor" class="rounded-2xl bg-gray-50 border border-gray-100 p-4 space-y-4">
        <div>
          <h3 class="text-sm font-bold text-gray-900">Elige los colores</h3>
          <p class="text-xs text-gray-500 mt-0.5">Marca todos los que quieras, de cualquier grupo; desplázate para ver los demás. Después, toca el recuadro de cada color en la lista para subir su foto.</p>
        </div>

        <div class="max-h-80 overflow-y-auto pr-1 space-y-4">
          <div v-for="g in GRUPOS_COLOR" :key="g.nombre">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">{{ g.nombre }}</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
          <button
            v-for="p in g.colores"
            :key="p.hex"
            type="button"
            @click="alternarSeleccion(p)"
            :disabled="yaRegistrado(p)"
            :aria-pressed="seleccion.includes(p.hex)"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl border text-sm font-semibold text-left transition-all',
              yaRegistrado(p)
                ? 'border-gray-100 bg-gray-100/60 text-gray-400 cursor-not-allowed'
                : seleccion.includes(p.hex)
                  ? 'border-[#ff8c42] bg-orange-50 text-gray-900 ring-2 ring-orange-100'
                  : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300',
            ]"
          >
            <span class="w-5 h-5 rounded-full border border-gray-200 flex-shrink-0 flex items-center justify-center" :style="{ backgroundColor: p.hex }">
              <i v-if="seleccion.includes(p.hex)" :class="['fa-solid fa-check text-[9px]', esColorClaro(p.hex) ? 'text-gray-800' : 'text-white']"></i>
            </span>
            <span class="min-w-0">
              <span class="block truncate">{{ p.nombre }}</span>
              <span v-if="yaRegistrado(p)" class="block text-[10px] font-medium text-gray-400">Ya registrado</span>
            </span>
          </button>
            </div>
          </div>
        </div>

        <div v-if="error" role="alert" class="flex items-start gap-2.5 bg-red-50 text-red-700 rounded-xl px-4 py-3 text-sm">
          <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
          <span>{{ error }}</span>
        </div>

        <div class="flex justify-end gap-2 pt-1">
          <button @click="cerrarForm" :disabled="guardandoVarios" type="button" class="text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-200/60 disabled:opacity-50 px-4 py-2.5 rounded-xl transition-colors">
            Cancelar
          </button>
          <button
            @click="guardarVarios"
            :disabled="guardandoVarios || seleccion.length === 0"
            type="button"
            class="flex items-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm py-2.5 px-5 rounded-xl shadow-sm shadow-orange-200 transition-all"
          >
            <i v-if="guardandoVarios" class="fa-solid fa-spinner fa-spin text-xs"></i>
            {{ guardandoVarios ? 'Agregando...' : seleccion.length === 0 ? 'Elige al menos uno' : `Agregar ${seleccion.length} color${seleccion.length === 1 ? '' : 'es'}` }}
          </button>
        </div>
      </section>

      <section v-if="showForm && (!modoVarios || editandoColor)" class="rounded-2xl bg-gray-50 border border-gray-100 p-4 space-y-4">
        <h3 class="text-sm font-bold text-gray-900">{{ editandoColor ? 'Editar color' : 'Nuevo color' }}</h3>

        <FormField
          label="Nombre"
          hint="¿Tiene detalles de otro color (logo, suela)? Usa el nombre de la combinación y sube la foto real: la identifica mejor que el color de referencia."
        >
          <FormInput v-model="formNombre" type="text" placeholder="Ej: Rojo, o una combinación: Blanco / Negro" />
        </FormField>

        <FormField label="Color de referencia" optional group hint="Al elegir uno de la paleta se completa el nombre; solo se usa para el punto de color en las listas.">
          <div class="flex items-center gap-2 flex-wrap">
            <button
              v-for="p in paleta"
              :key="p.hex"
              type="button"
              @click="elegirDePaleta(p)"
              :aria-label="p.nombre"
              :aria-pressed="formHex === p.hex"
              :title="p.nombre"
              :class="[
                'w-8 h-8 rounded-full border flex items-center justify-center transition-all',
                formHex === p.hex ? 'border-gray-900 ring-2 ring-offset-2 ring-[#ff8c42] scale-105' : 'border-gray-200 hover:scale-110',
              ]"
              :style="{ backgroundColor: p.hex }"
            >
              <i v-if="formHex === p.hex" :class="['fa-solid fa-check text-[10px]', esColorClaro(p.hex) ? 'text-gray-800' : 'text-white']"></i>
            </button>
            <input
              :value="formHex || '#9CA3AF'"
              @input="elegirPersonalizado($event.target.value)"
              type="color"
              class="w-8 h-8 rounded-full border border-gray-200 p-0 cursor-pointer bg-transparent"
              title="Color personalizado"
              aria-label="Color personalizado"
            >
          </div>
          <p v-if="nombreReferencia" class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 mt-2">
            <span class="w-2.5 h-2.5 rounded-full border border-gray-200" :style="{ backgroundColor: formHex }"></span>
            {{ nombreReferencia }}
          </p>
        </FormField>

        <FormField label="Foto del color" optional group>
          <div class="flex items-center gap-3">
            <div class="w-16 h-16 rounded-xl overflow-hidden bg-white border border-gray-200 flex-shrink-0">
              <img v-if="previewUrl" :src="previewUrl" alt="Vista previa" class="w-full h-full object-cover">
              <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                <i class="fa-solid fa-image text-lg"></i>
              </div>
            </div>
            <label class="flex-1 flex items-center justify-center gap-2 h-11 rounded-xl border border-dashed border-gray-300 bg-white text-sm font-semibold text-gray-600 hover:border-[#ff8c42] hover:text-[#ff8c42] hover:bg-orange-50/40 cursor-pointer transition-colors">
              <i class="fa-solid fa-arrow-up-from-bracket text-xs"></i>
              {{ formImagen ? 'Cambiar foto' : (previewUrl ? 'Reemplazar foto' : 'Subir foto') }}
              <input ref="fileInput" type="file" accept="image/*" @change="onImagenSeleccionada" class="sr-only">
            </label>
          </div>
          <p v-if="formImagen" class="text-xs text-gray-500 mt-1.5 truncate">{{ formImagen.name }}</p>
        </FormField>

        <div v-if="error" role="alert" class="flex items-start gap-2.5 bg-red-50 text-red-700 rounded-xl px-4 py-3 text-sm">
          <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
          <span>{{ error }}</span>
        </div>

        <div class="flex flex-wrap justify-end gap-2 pt-1">
          <button @click="cerrarForm" :disabled="isSaving" type="button" class="text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-200/60 disabled:opacity-50 px-4 py-2.5 rounded-xl transition-colors">
            Cancelar
          </button>
          <button
            v-if="!editandoColor"
            @click="guardarColor(true)"
            :disabled="isSaving"
            type="button"
            class="flex items-center gap-2 text-[#ff8c42] hover:bg-orange-50 border border-orange-200 disabled:opacity-60 disabled:cursor-not-allowed font-bold text-sm py-2.5 px-4 rounded-xl transition-colors"
          >
            <i class="fa-solid fa-plus text-xs"></i> Guardar y agregar otro
          </button>
          <button
            type="submit"
            :disabled="isSaving"
            class="flex items-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold text-sm py-2.5 px-5 rounded-xl shadow-sm shadow-orange-200 transition-all"
          >
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin text-xs"></i>
            {{ isSaving ? 'Guardando...' : (editandoColor ? 'Guardar cambios' : 'Guardar color') }}
          </button>
        </div>
      </section>
    </template>
  </ModalShell>

  <ConfirmModal
    v-if="colorPendienteEliminar"
    title="Eliminar color"
    :message="`¿Eliminar el color &quot;${colorPendienteEliminar.nombre}&quot;?`"
    confirm-text="Sí, eliminar"
    cancel-text="Cancelar"
    variant="danger"
    :loading="eliminando"
    @confirm="confirmarEliminacion"
    @close="colorPendienteEliminar = null"
  />
</template>
