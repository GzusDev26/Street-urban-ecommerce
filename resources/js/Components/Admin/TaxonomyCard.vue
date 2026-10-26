<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { GRUPOS_TALLA } from '@/Components/Admin/tallas';

const props = defineProps({
  title: String,
  icon: String,
  entityLabel: String, // 'categoría' | 'marca'
  items: Array,
  routes: Object, // { store, update, destroy }
  withSizeType: { type: Boolean, default: false }, // categorías: cada una define qué tallas se ofrecen al comprar
});

const TIPOS_TALLA = GRUPOS_TALLA;
const nombreTipo = (id) => TIPOS_TALLA.find((t) => t.id === id)?.nombre ?? 'Ropa';

const nuevoTipo = ref('ropa');
const nuevoNombre = ref('');
const nuevaDescripcion = ref('');
const creando = ref(false);

const agregar = () => {
  if (!nuevoNombre.value.trim()) return;
  creando.value = true;

  router.post(props.routes.store, { name: nuevoNombre.value.trim(), description: nuevaDescripcion.value.trim() || null, ...(props.withSizeType ? { size_type: nuevoTipo.value } : {}) }, {
    preserveScroll: true,
    onSuccess: () => {
      nuevoNombre.value = '';
      nuevaDescripcion.value = '';
    },
    onFinish: () => {
      creando.value = false;
    },
  });
};

const editandoId = ref(null);
const editName = ref('');
const editDescription = ref('');
const editTipo = ref('ropa');
const guardando = ref(false);

const editar = (item) => {
  editandoId.value = item.id;
  editName.value = item.name;
  editDescription.value = item.description || '';
  editTipo.value = item.size_type || 'ropa';
};

const cancelarEdicion = () => {
  editandoId.value = null;
};

const guardar = (item) => {
  if (!editName.value.trim()) return;
  guardando.value = true;

  router.patch(props.routes.update(item.id), { name: editName.value.trim(), description: editDescription.value.trim() || null, ...(props.withSizeType ? { size_type: editTipo.value } : {}) }, {
    preserveScroll: true,
    onSuccess: () => {
      editandoId.value = null;
    },
    onFinish: () => {
      guardando.value = false;
    },
  });
};

const itemPendienteEliminar = ref(null);
const eliminando = ref(false);

const eliminar = (item) => {
  itemPendienteEliminar.value = item;
};

const confirmarEliminacion = () => {
  eliminando.value = true;

  router.delete(props.routes.destroy(itemPendienteEliminar.value.id), {
    preserveScroll: true,
    onFinish: () => {
      eliminando.value = false;
      itemPendienteEliminar.value = null;
    },
  });
};
</script>

<template>
  <div class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm">
    <div class="flex items-center gap-2 mb-1">
      <i :class="['fa-solid', icon, 'text-gray-400 text-sm']"></i>
      <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
      <span class="text-xs text-gray-400 font-medium">({{ items.length }})</span>
    </div>

    <form @submit.prevent="agregar" class="flex flex-wrap items-start gap-2 mt-4 mb-5">
      <input
        :aria-label="`Nueva ${entityLabel}`"
        v-model="nuevoNombre"
        type="text"
        :placeholder="`Nueva ${entityLabel}...`"
        class="flex-1 min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
      />
      <input
        :aria-label="`Descripción de la nueva ${entityLabel}`"
        v-model="nuevaDescripcion"
        type="text"
        placeholder="Descripción (opcional)"
        class="flex-1 min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
      />
      <select
        v-if="withSizeType"
        v-model="nuevoTipo"
        aria-label="Tipo de tallas de la nueva categoría"
        class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-surface"
      >
        <option v-for="t in TIPOS_TALLA" :key="t.id" :value="t.id">Tallas de {{ t.nombre.toLowerCase() }} ({{ t.ejemplo }})</option>
      </select>
      <button
        type="submit"
        :disabled="creando || !nuevoNombre.trim()"
        class="bg-[#ff8c42] hover:bg-[#ff7a24] disabled:opacity-40 text-white text-sm font-bold py-2 px-4 rounded-lg transition flex items-center gap-1.5 flex-shrink-0"
      >
        <i class="fa-solid fa-plus text-xs"></i> Agregar
      </button>
    </form>

    <div v-if="items.length > 0" class="divide-y divide-gray-50">
      <div v-for="item in items" :key="item.id" class="py-3 flex items-start gap-3">
        <template v-if="editandoId === item.id">
          <div class="flex-1 space-y-2">
            <input
              aria-label="Nombre"
              v-model="editName"
              type="text"
              class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
              @keyup.enter="guardar(item)"
            />
            <input
              aria-label="Descripción"
              v-model="editDescription"
              type="text"
              placeholder="Descripción (opcional)"
              class="w-full px-3 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
              @keyup.enter="guardar(item)"
            />
            <select
              v-if="withSizeType"
              v-model="editTipo"
              aria-label="Tipo de tallas"
              class="w-full px-3 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-surface"
            >
              <option v-for="t in TIPOS_TALLA" :key="t.id" :value="t.id">Tallas de {{ t.nombre.toLowerCase() }} ({{ t.ejemplo }})</option>
            </select>
          </div>
          <div class="flex items-center gap-1.5 flex-shrink-0 pt-1">
            <button
              @click="guardar(item)"
              :disabled="guardando"
              title="Guardar"
              class="w-8 h-8 rounded-lg bg-green-50 hover:bg-green-100 text-green-500 hover:text-green-600 flex items-center justify-center transition-colors disabled:opacity-50"
            >
              <i class="fa-solid fa-check text-xs"></i>
            </button>
            <button
              @click="cancelarEdicion"
              title="Cancelar"
              class="w-8 h-8 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-400 hover:text-gray-600 flex items-center justify-center transition-colors"
            >
              <i class="fa-solid fa-xmark text-xs"></i>
            </button>
          </div>
        </template>

        <template v-else>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ item.name }}</p>
            <p v-if="item.description" class="text-xs text-gray-400 truncate">{{ item.description }}</p>
          </div>
          <span v-if="withSizeType" class="text-[11px] font-semibold px-2 py-1 rounded-full whitespace-nowrap flex-shrink-0 bg-orange-50 text-[#e67e2d]" title="Tallas que se ofrecen al comprar">
            Tallas: {{ nombreTipo(item.size_type) }}
          </span>
          <span
            :class="[
              'text-[11px] font-semibold px-2 py-1 rounded-full whitespace-nowrap flex-shrink-0',
              item.productos > 0 ? 'bg-gray-100 text-gray-500' : 'bg-gray-50 text-gray-300',
            ]"
          >
            {{ item.productos }} producto{{ item.productos === 1 ? '' : 's' }}
          </span>
          <div class="flex items-center gap-1.5 flex-shrink-0">
            <button
              @click="editar(item)"
              title="Editar"
              class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-500 hover:text-blue-600 flex items-center justify-center transition-colors"
            >
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            <button
              @click="eliminar(item)"
              title="Eliminar"
              class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-600 flex items-center justify-center transition-colors"
            >
              <i class="fa-solid fa-trash text-xs"></i>
            </button>
          </div>
        </template>
      </div>
    </div>

    <div v-else class="bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 p-8 text-center">
      <p class="text-sm text-gray-400">Aún no hay {{ entityLabel }}s registradas.</p>
    </div>
  </div>

  <ConfirmModal
    v-if="itemPendienteEliminar"
    title="Eliminar"
    :message="`¿Eliminar la ${entityLabel} “${itemPendienteEliminar.name}”? Esta acción no se puede deshacer.`"
    confirm-text="Sí, Eliminar"
    cancel-text="Cancelar"
    variant="danger"
    :loading="eliminando"
    @confirm="confirmarEliminacion"
    @close="itemPendienteEliminar = null"
  />
</template>
