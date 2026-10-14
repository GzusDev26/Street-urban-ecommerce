<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SearchCreateInput from '@/Components/Admin/SearchCreateInput.vue';

const props = defineProps({
  categoriasExistentes: { type: Array, default: () => [] },
  marcasExistentes: { type: Array, default: () => [] },
  editando: { type: Object, default: null }, // { id, nombre, marca, categoria } o null para crear
});

const emit = defineEmits(['close']);

const form = ref({
  nombre: props.editando?.nombre || '',
  marca: props.editando?.marca || '',
  marcaDescripcion: '',
  categoria: props.editando?.categoria || '',
  categoriaDescripcion: '',
  descripcion: props.editando?.descripcion || '',
  color: 'Único',
  colorHex: '#000000',
  talla: 'Única',
  precio: '',
  stock: 0,
});

const isSaving = ref(false);
const error = ref('');

const categoriaEsNueva = computed(() => {
  const q = form.value.categoria.trim();
  return q.length > 0 && !props.categoriasExistentes.some(c => c.toLowerCase() === q.toLowerCase());
});

const marcaEsNueva = computed(() => {
  const q = form.value.marca.trim();
  return q.length > 0 && !props.marcasExistentes.some(m => m.toLowerCase() === q.toLowerCase());
});

const guardar = () => {
  if (!form.value.nombre || !form.value.marca || !form.value.categoria) {
    error.value = 'Completa categoría, marca y modelo.';
    return;
  }

  if (!props.editando && (!form.value.color.trim() || !form.value.talla.trim() || form.value.precio === '' || form.value.stock === '')) {
    error.value = 'Completa color, talla, precio y stock de la variante inicial.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  const opciones = {
    onSuccess: () => emit('close'),
    onError: (errors) => {
      error.value = Object.values(errors)[0] || 'Ocurrió un error al guardar.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  };

  if (props.editando) {
    router.patch(route('admin.catalogo.update', props.editando.id), form.value, opciones);
  } else {
    router.post(route('admin.catalogo.store'), form.value, opciones);
  }
};
</script>

<template>
  <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="emit('close')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-900">{{ editando ? 'Editar Modelo' : 'Registrar Nuevo Modelo' }}</h2>
          <p class="text-xs text-gray-400">Categoría, marca y modelo — la identidad del producto</p>
        </div>
        <button @click="emit('close')" class="p-2 hover:bg-gray-100 rounded-lg transition">
          <i class="fa-solid fa-xmark text-gray-500"></i>
        </button>
      </div>

      <div class="p-6 space-y-5">
        <div>
          <label for="registrar-modelo-modal-1" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
          <SearchCreateInput id="registrar-modelo-modal-1" v-model="form.categoria" :options="categoriasExistentes" placeholder="Ej: Calzado" />
          <textarea aria-label="Descripción de la categoría"
            v-if="categoriaEsNueva"
            v-model="form.categoriaDescripcion"
            placeholder="Descripción de la categoría (opcional)"
            rows="2"
            class="w-full mt-2 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white resize-none"
          ></textarea>
        </div>

        <div>
          <label for="registrar-modelo-modal-2" class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
          <SearchCreateInput id="registrar-modelo-modal-2" v-model="form.marca" :options="marcasExistentes" placeholder="Ej: Adidas" />
          <textarea aria-label="Descripción de la marca"
            v-if="marcaEsNueva"
            v-model="form.marcaDescripcion"
            placeholder="Descripción de la marca (opcional)"
            rows="2"
            class="w-full mt-2 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white resize-none"
          ></textarea>
        </div>

        <div>
          <label for="registrar-modelo-modal-3" class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
          <input id="registrar-modelo-modal-3"
            v-model="form.nombre"
            type="text"
            placeholder="Ej: All Star"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white"
          >
          <textarea
            aria-label="Descripción del modelo"
            v-model="form.descripcion"
            placeholder="Descripción del modelo (opcional)"
            rows="2"
            class="w-full mt-2 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white resize-none"
          ></textarea>
        </div>

        <div v-if="!editando" class="border-t border-gray-100 pt-5 space-y-4">
          <div>
            <h3 class="text-sm font-bold text-gray-800">Variante inicial</h3>
            <p class="text-xs text-gray-400 mt-1">Si el producto es de tipo único, conserva los valores predeterminados.</p>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="registrar-modelo-modal-4" class="block text-sm font-medium text-gray-700 mb-2">Color</label>
              <input id="registrar-modelo-modal-4" v-model="form.color" required maxlength="50" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
            <div>
              <label for="registrar-modelo-modal-5" class="block text-sm font-medium text-gray-700 mb-2">Código de color (opcional)</label>
              <input id="registrar-modelo-modal-5" v-model="form.colorHex" type="color" class="w-full h-10 px-1 border border-gray-200 rounded-lg bg-white">
            </div>
            <div>
              <label for="registrar-modelo-modal-6" class="block text-sm font-medium text-gray-700 mb-2">Talla</label>
              <input id="registrar-modelo-modal-6" v-model="form.talla" required maxlength="20" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
            <div>
              <label for="registrar-modelo-modal-7" class="block text-sm font-medium text-gray-700 mb-2">Precio de venta (S/)</label>
              <input id="registrar-modelo-modal-7" v-model="form.precio" type="number" min="0.01" step="0.01" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
            <div>
              <label for="registrar-modelo-modal-8" class="block text-sm font-medium text-gray-700 mb-2">Stock inicial</label>
              <input id="registrar-modelo-modal-8" v-model="form.stock" type="number" min="0" step="1" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-white">
            </div>
          </div>
        </div>

        <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 rounded-lg px-4 py-3 text-sm">
          {{ error }}
        </div>
      </div>

      <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
        <button @click="emit('close')" :disabled="isSaving" class="text-sm font-semibold text-gray-500 hover:text-gray-700 disabled:opacity-50 px-5 py-3 rounded-xl hover:bg-gray-100 transition-colors">
          Cancelar
        </button>
        <button
          @click="guardar"
          :disabled="isSaving"
          class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm py-3 px-8 rounded-xl transition-all duration-200 shadow-sm"
        >
          <i class="fa-solid fa-save"></i> {{ isSaving ? 'Guardando...' : 'Guardar' }}
        </button>
      </div>
    </div>
  </div>
</template>
