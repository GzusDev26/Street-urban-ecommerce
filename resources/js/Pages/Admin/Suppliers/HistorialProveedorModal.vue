<script setup>
import { useBloqueoScroll } from '@/useBloqueoScroll';
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';

const props = defineProps({
  proveedor: Object,
});

const emit = defineEmits(['close']);

const compras = ref([]);
const stats = ref({ totalCompras: 0, totalInvertido: 0 });
const loading = ref(true);

onMounted(async () => {
  try {
    const res = await fetch(`/admin/suppliers/${props.proveedor.id}/historial`);
    const data = await res.json();
    compras.value = data.compras;
    stats.value = data.stats;
  } catch (e) {
    console.error('No se pudo cargar el historial del proveedor', e);
    compras.value = [];
  } finally {
    loading.value = false;
  }
});

useBloqueoScroll(); // la página de atrás no se mueve mientras está abierto
</script>

<template>
  <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="emit('close')">
    <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
        <div class="min-w-0">
          <h2 class="text-xl font-bold text-gray-900 truncate">{{ proveedor.name }}</h2>
          <p class="text-xs text-gray-400">Historial de Compras</p>
        </div>
        <button @click="emit('close')" class="p-2 hover:bg-gray-100 rounded-lg transition flex-shrink-0">
          <i class="fa-solid fa-xmark text-gray-500"></i>
        </button>
      </div>

      <div v-if="!loading && compras.length > 0" class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex-shrink-0">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-400">Compras Realizadas</p>
            <p class="text-2xl font-black text-gray-900">{{ stats.totalCompras }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-400">Total Invertido</p>
            <p class="text-2xl font-black text-[#ff8c42]">S/ {{ stats.totalInvertido.toFixed(2) }}</p>
          </div>
        </div>
      </div>

      <div class="p-6 overflow-y-auto">
        <div v-if="loading" class="text-sm text-gray-400 text-center py-8">
          <i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Cargando...
        </div>

        <div v-else-if="compras.length > 0" class="divide-y divide-gray-100">
          <Link
            v-for="compra in compras"
            :key="compra.id"
            :href="`${route('admin.purchases.historial')}?compra=${compra.id}`"
            class="flex items-center gap-3 py-3.5 -mx-2 px-2 rounded-lg hover:bg-gray-50 transition-colors group"
          >
            <div class="w-10 h-10 rounded-lg border border-gray-200 flex-shrink-0 overflow-hidden bg-gray-50">
              <ColorSwatch
                v-if="compra.colores.length <= 1 && compra.masColores === 0"
                :hex="compra.colores[0]"
                class="w-full h-full"
              />
              <div v-else class="w-full h-full grid grid-cols-2 grid-rows-2 gap-[1px] bg-gray-200">
                <ColorSwatch v-for="(hex, idx) in compra.colores" :key="idx" :hex="hex" />
                <div v-if="compra.masColores > 0" class="flex items-center justify-center bg-gray-700 text-gray-50 text-[9px] font-bold">
                  +{{ compra.masColores }}
                </div>
              </div>
            </div>

            <div class="min-w-0 flex-1">
              <p :class="['text-sm font-medium truncate', compra.cancelada ? 'text-gray-400 line-through' : 'text-gray-900']">
                {{ compra.producto }}
              </p>
              <p class="text-xs text-gray-400">
                <span class="font-mono">{{ compra.numero }}</span> · {{ compra.fecha }}
              </p>
            </div>

            <span :class="['text-sm font-bold flex-shrink-0', compra.cancelada ? 'text-gray-300 line-through' : 'text-gray-900']">
              S/ {{ compra.total.toFixed(2) }}
            </span>

            <span
              :class="[
                'text-[11px] font-semibold px-2 py-1 rounded-full flex items-center gap-1 flex-shrink-0 whitespace-nowrap',
                compra.cancelada ? 'bg-red-50 text-red-400' : 'bg-green-50 text-green-600',
              ]"
            >
              <i :class="['fa-solid text-[9px]', compra.cancelada ? 'fa-ban' : 'fa-circle-check']"></i>
              {{ compra.cancelada ? 'Cancelada' : 'Completada' }}
            </span>

            <i class="fa-solid fa-chevron-right text-gray-200 group-hover:text-gray-400 text-xs transition-colors flex-shrink-0"></i>
          </Link>
        </div>

        <div v-else class="text-center py-8">
          <i class="fa-solid fa-truck-fast text-3xl text-gray-300 mb-2"></i>
          <p class="text-sm text-gray-400">Aún no le has comprado nada a este proveedor.</p>
        </div>
      </div>
    </div>
  </div>
</template>
