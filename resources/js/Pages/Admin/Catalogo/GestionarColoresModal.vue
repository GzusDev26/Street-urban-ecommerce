<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import ActionButton from '@/Components/Admin/ActionButton.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';

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
    @submit="guardarColor"
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
          <div class="w-14 h-14 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0 ring-1 ring-black/5">
            <img v-if="c.imagenUrl" :src="c.imagenUrl" :alt="c.nombre" class="w-full h-full object-cover">
            <div v-else class="w-full h-full flex items-center justify-center">
              <ColorSwatch :hex="c.hex" class="w-7 h-7 rounded-full border border-gray-300" />
            </div>
          </div>

          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ c.nombre }}</p>
            <p class="flex items-center gap-1.5 text-xs text-gray-400 mt-0.5">
              <ColorSwatch :hex="c.hex" class="w-2.5 h-2.5 rounded-full border border-gray-200" />
              {{ c.imagenUrl ? 'Con foto' : 'Sin foto' }}
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

      <section v-else class="rounded-2xl bg-gray-50 border border-gray-100 p-4 space-y-4">
        <h3 class="text-sm font-bold text-gray-900">{{ editandoColor ? 'Editar color' : 'Nuevo color' }}</h3>

        <FormField
          label="Nombre"
          hint="¿Tiene detalles de otro color (logo, suela)? Usa el nombre de la combinación y sube la foto real: la identifica mejor que el color de referencia."
        >
          <FormInput v-model="formNombre" type="text" placeholder="Ej: Rojo, o una combinación: Blanco / Negro" />
        </FormField>

        <FormField label="Color de referencia" group hint="Solo se usa para el punto de color en las listas.">
          <div class="flex items-center gap-2 flex-wrap">
            <button
              v-for="p in paleta"
              :key="p.hex"
              type="button"
              @click="formHex = p.hex"
              :aria-label="p.nombre"
              :aria-pressed="formHex === p.hex"
              :title="p.nombre"
              :class="[
                'w-8 h-8 rounded-full border flex items-center justify-center transition-all',
                formHex === p.hex ? 'border-gray-900 ring-2 ring-offset-2 ring-[#ff8c42] scale-105' : 'border-gray-200 hover:scale-110',
              ]"
              :style="{ backgroundColor: p.hex }"
            >
              <i v-if="formHex === p.hex" :class="['fa-solid fa-check text-[10px]', ['#F3F4F6', '#FBBF24'].includes(p.hex) ? 'text-gray-800' : 'text-white']"></i>
            </button>
            <input v-model="formHex" type="color" class="w-8 h-8 rounded-full border border-gray-200 p-0 cursor-pointer bg-transparent" title="Color personalizado" aria-label="Color personalizado">
          </div>
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

        <div class="flex justify-end gap-2 pt-1">
          <button @click="cerrarForm" :disabled="isSaving" type="button" class="text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-200/60 disabled:opacity-50 px-4 py-2.5 rounded-xl transition-colors">
            Cancelar
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
