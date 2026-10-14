<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';

const props = defineProps({
  producto: { type: Object, required: true }, // { id, nombre, marca }
});

const emit = defineEmits(['close']);

const colores = ref([]);
const loading = ref(true);

const paleta = [
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
const formHex = ref('#9CA3AF');
const formImagen = ref(null);
const previewUrl = ref(null);
const isSaving = ref(false);
const error = ref('');
const fileInput = ref(null);

const abrirNuevo = () => {
  editandoColor.value = null;
  formNombre.value = '';
  formHex.value = '#9CA3AF';
  formImagen.value = null;
  previewUrl.value = null;
  error.value = '';
  showForm.value = true;
};

const abrirEditar = (color) => {
  editandoColor.value = color;
  formNombre.value = color.nombre;
  formHex.value = color.hex || '#9CA3AF';
  formImagen.value = null;
  previewUrl.value = color.imagenUrl;
  error.value = '';
  showForm.value = true;
};

const cerrarForm = () => {
  showForm.value = false;
  editandoColor.value = null;
  if (fileInput.value) fileInput.value.value = '';
};

const onImagenSeleccionada = (e) => {
  const file = e.target.files[0];
  formImagen.value = file || null;
  previewUrl.value = file ? URL.createObjectURL(file) : (editandoColor.value?.imagenUrl || null);
};

const guardarColor = () => {
  if (!formNombre.value.trim()) {
    error.value = 'Ponle un nombre al color.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  const data = {
    nombre: formNombre.value.trim(),
    hex: formHex.value,
    imagen: formImagen.value,
  };

  const url = editandoColor.value
    ? route('admin.catalogo.colores.update', editandoColor.value.id)
    : route('admin.catalogo.colores.store', props.producto.id);

  router.post(url, data, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      cerrarForm();
      cargarColores();
    },
    onError: (errors) => {
      error.value = Object.values(errors)[0] || 'Ocurrió un error al guardar el color.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};

const eliminarColor = (color) => {
  if (!confirm(`¿Eliminar el color "${color.nombre}"?`)) return;

  router.delete(route('admin.catalogo.colores.destroy', color.id), {
    preserveScroll: true,
    onSuccess: () => cargarColores(),
    onError: (errors) => {
      alert(Object.values(errors)[0] || 'No se pudo eliminar el color.');
    },
  });
};
</script>

<template>
  <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="emit('close')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between z-10">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Colores del Modelo</h2>
          <p class="text-xs text-gray-400">{{ producto.marca }} · {{ producto.nombre }}</p>
        </div>
        <button @click="emit('close')" class="p-2 hover:bg-gray-100 rounded-lg transition">
          <i class="fa-solid fa-xmark text-gray-500"></i>
        </button>
      </div>

      <div class="p-6 space-y-4">
        <div v-if="loading" class="text-sm text-gray-400 text-center py-6">Cargando colores...</div>

        <template v-else>
          <div v-if="colores.length === 0" class="bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 p-6 text-center text-sm text-gray-400">
            Este modelo aún no tiene colores registrados.
          </div>

          <div v-else class="space-y-2">
            <div v-for="c in colores" :key="c.id" class="flex items-center gap-3 border border-gray-100 rounded-xl p-3">
              <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 border border-gray-200">
                <img v-if="c.imagenUrl" :src="c.imagenUrl" :alt="c.nombre" class="w-full h-full object-cover">
                <div v-else class="w-full h-full flex items-center justify-center">
                  <ColorSwatch :hex="c.hex" class="w-6 h-6 rounded-full border border-gray-300" />
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ c.nombre }}</p>
                <p v-if="!c.imagenUrl" class="text-xs text-gray-400">Sin foto</p>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0">
                <button @click="abrirEditar(c)" title="Editar" class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-500 hover:text-blue-600 flex items-center justify-center transition-colors">
                  <i class="fa-solid fa-pen text-xs"></i>
                </button>
                <button @click="eliminarColor(c)" title="Eliminar" class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-600 flex items-center justify-center transition-colors">
                  <i class="fa-solid fa-trash text-xs"></i>
                </button>
              </div>
            </div>
          </div>

          <button
            v-if="!showForm"
            @click="abrirNuevo"
            type="button"
            class="w-full flex items-center justify-center gap-2 text-sm font-semibold text-[#ff8c42] hover:bg-orange-50 border-2 border-dashed border-orange-200 rounded-xl py-3 transition-colors"
          >
            <i class="fa-solid fa-plus text-xs"></i> Agregar Color
          </button>

          <div v-if="showForm" class="bg-gray-50 rounded-xl border border-gray-100 p-4 space-y-4">
            <h3 class="text-sm font-bold text-gray-900">{{ editandoColor ? 'Editar color' : 'Nuevo color' }}</h3>

            <div>
              <label for="gestionar-colores-modal-1" class="block text-xs font-medium text-gray-700 mb-1.5">Nombre</label>
              <input id="gestionar-colores-modal-1"
                v-model="formNombre"
                type="text"
                placeholder="Ej: Rojo, o una combinación: Blanco / Negro"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white"
              >
              <p class="text-xs text-gray-400 mt-1">¿Tiene detalles de otro color (logo, suela)? Ponle el nombre de la combinación y sube la foto real — la identifica mejor que el hex.</p>
            </div>

            <div>
              <span class="block text-xs font-medium text-gray-700 mb-1.5">Color de referencia (solo para el punto en la lista)</span>
              <div class="flex items-center gap-2 flex-wrap">
                <button
                  v-for="p in paleta"
                  :key="p.hex"
                  type="button"
                  @click="formHex = p.hex"
                  :class="['w-7 h-7 rounded-full border-2 transition-all', formHex === p.hex ? 'border-gray-900 ring-2 ring-offset-1 ring-[#ff8c42]' : 'border-gray-200 hover:border-gray-400']"
                  :style="{ backgroundColor: p.hex }"
                  :title="p.nombre"
                ></button>
                <input v-model="formHex" type="color" aria-label="Color personalizado" class="w-7 h-7 rounded-full border border-gray-200 cursor-pointer" title="Color personalizado">
              </div>
            </div>

            <div>
              <label for="gestionar-colores-modal-2" class="block text-xs font-medium text-gray-700 mb-1.5">Foto del color (opcional)</label>
              <div class="flex items-center gap-3">
                <div class="w-16 h-16 rounded-lg overflow-hidden bg-white border border-gray-200 flex-shrink-0">
                  <img v-if="previewUrl" :src="previewUrl" alt="Vista previa del color" class="w-full h-full object-cover">
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                    <i class="fa-solid fa-image"></i>
                  </div>
                </div>
                <input id="gestionar-colores-modal-2" ref="fileInput" type="file" accept="image/*" @change="onImagenSeleccionada" class="text-xs text-gray-500 flex-1">
              </div>
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 rounded-lg px-3 py-2 text-xs">
              {{ error }}
            </div>

            <div class="flex justify-end gap-2">
              <button @click="cerrarForm" :disabled="isSaving" type="button" class="text-sm font-semibold text-gray-500 hover:text-gray-700 disabled:opacity-50 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                Cancelar
              </button>
              <button
                @click="guardarColor"
                :disabled="isSaving"
                type="button"
                class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm py-2 px-5 rounded-lg transition-all"
              >
                <i class="fa-solid fa-save"></i> {{ isSaving ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
