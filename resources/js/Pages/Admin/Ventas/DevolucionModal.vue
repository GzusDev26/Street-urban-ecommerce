<script setup>
import { useBloqueoScroll } from '@/useBloqueoScroll';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';

const props = defineProps({
  item: Object,
});

const emit = defineEmits(['close']);

const cantidad = ref(props.item.pendienteDevolucion);
const motivo = ref('');
const isSaving = ref(false);
const error = ref('');

const guardar = () => {
  if (!cantidad.value || cantidad.value < 1) {
    error.value = 'Ingresa una cantidad válida.';
    return;
  }
  if (cantidad.value > props.item.pendienteDevolucion) {
    error.value = `Solo puedes devolver hasta ${props.item.pendienteDevolucion} unidad${props.item.pendienteDevolucion === 1 ? '' : 'es'}.`;
    return;
  }

  isSaving.value = true;
  error.value = '';

  router.post(route('admin.sales.items.return', props.item.id), {
    cantidad: cantidad.value,
    motivo: motivo.value.trim() || null,
  }, {
    preserveScroll: true,
    onSuccess: () => emit('close'),
    onError: (errors) => {
      error.value = Object.values(errors)[0] || 'Ocurrió un error al registrar la devolución.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  });
};

useBloqueoScroll(); // la página de atrás no se mueve mientras está abierto
</script>

<template>
  <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="emit('close')">
    <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-md">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Registrar Devolución</h2>
          <p class="text-xs text-gray-400">Se devuelve el stock al inventario</p>
        </div>
        <button @click="emit('close')" class="p-2 hover:bg-gray-100 rounded-lg transition">
          <i class="fa-solid fa-xmark text-gray-500"></i>
        </button>
      </div>

      <div class="p-6 space-y-5">
        <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-3">
          <ColorSwatch :hex="item.colorHex" class="w-8 h-8 rounded-full border border-gray-200 flex-shrink-0" />
          <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ item.producto }}</p>
            <p class="text-xs text-gray-400">{{ item.marca }} · {{ item.talla }} · {{ item.color }}</p>
          </div>
          <span class="ml-auto text-xs font-semibold text-gray-500 flex-shrink-0">
            {{ item.pendienteDevolucion }} disponible{{ item.pendienteDevolucion === 1 ? '' : 's' }}
          </span>
        </div>

        <div>
          <label for="devolucion-modal-1" class="block text-sm font-medium text-gray-700 mb-2">Cantidad a devolver</label>
          <input id="devolucion-modal-1"
            v-model.number="cantidad"
            type="number"
            min="1"
            :max="item.pendienteDevolucion"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-surface"
          >
        </div>

        <div>
          <label for="devolucion-modal-2" class="block text-sm font-medium text-gray-700 mb-2">Motivo (opcional)</label>
          <input id="devolucion-modal-2"
            v-model="motivo"
            type="text"
            placeholder="Ej. talla equivocada, prenda defectuosa..."
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42] bg-surface placeholder:text-gray-300"
          >
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
          class="flex items-center gap-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm py-3 px-8 rounded-xl transition-colors shadow-sm"
        >
          <i class="fa-solid fa-rotate-left"></i> {{ isSaving ? 'Guardando...' : 'Confirmar Devolución' }}
        </button>
      </div>
    </div>
  </div>
</template>
