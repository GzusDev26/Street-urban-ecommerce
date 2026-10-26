<template>
  <Teleport to="body">
    <div
      class="boleta-modal-raiz fixed inset-0 z-50 bg-black/40 flex justify-end overscroll-none touch-none sm:touch-auto"
      @mousedown="pulsoEnElFondo = $event.target === $event.currentTarget"
      @click.self="pulsoEnElFondo && emit('close')"
    >
      <div
        role="dialog"
        aria-modal="true"
        :aria-label="resumen ? 'Venta registrada' : 'Boleta de venta'"
        class="modal-boleta bg-surface w-full sm:w-[36rem] max-w-full h-[100dvh] max-h-[100dvh] shadow-2xl flex flex-col touch-auto"
      >
        <!-- Cabecera -->
        <header class="flex items-start gap-3 px-5 sm:px-6 pt-5 sm:pt-6 pb-3 sm:pb-4 flex-shrink-0 print:hidden">
          <div v-if="resumen" class="w-11 h-11 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-check"></i>
          </div>
          <div v-else class="w-11 h-11 rounded-2xl bg-orange-50 text-[#ff8c42] flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-receipt"></i>
          </div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg font-bold text-gray-900 leading-tight">{{ resumen ? '¡Venta registrada!' : `Boleta ${venta?.numero ?? ''}` }}</h2>
            <p v-if="total !== null" class="text-sm text-gray-500 mt-0.5">
              <span class="font-black text-[#ff8c42] tabular-nums">S/ {{ total.toFixed(2) }}</span>
              · {{ prendas }} prenda{{ prendas === 1 ? '' : 's' }}<template v-if="cliente"> · {{ cliente }}</template>
            </p>
          </div>
          <button
            type="button"
            @click="emit('close')"
            aria-label="Cerrar"
            class="-mr-2 -mt-1 w-9 h-9 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center transition-colors"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </header>

        <!-- Vista previa de la boleta -->
        <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto overscroll-contain bg-gray-200 px-4 py-6 flex justify-center items-start print:bg-surface print:p-0 print:overflow-visible">
          <div v-if="cargando" class="self-center text-sm text-gray-500 flex items-center gap-2 py-16">
            <i class="fa-solid fa-spinner fa-spin"></i> Cargando boleta...
          </div>
          <p v-else-if="falló" class="self-center text-sm text-red-500 py-16 text-center px-6">
            No se pudo cargar la boleta. Puedes intentarlo de nuevo desde el historial.
          </p>
          <BoletaTicket v-else-if="venta" :venta="venta" :ancho="ancho" />
        </div>

        <!-- Acciones -->
        <footer class="px-5 sm:px-6 pt-3 sm:pt-4 pb-[max(1rem,env(safe-area-inset-bottom))] border-t border-gray-100 space-y-3 flex-shrink-0 print:hidden">
          <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Ancho del papel</p>
            <div class="inline-flex p-0.5 bg-gray-100 rounded-xl" role="group" aria-label="Ancho del papel">
              <button
                v-for="mm in [58, 80]"
                :key="mm"
                type="button"
                @click="elegirAncho(mm)"
                :aria-pressed="ancho === mm"
                :class="['px-4 h-8 text-xs font-bold rounded-lg transition-colors', ancho === mm ? 'bg-fg text-canvas' : 'text-gray-500 hover:text-gray-800']"
              >{{ mm }} mm</button>
            </div>
          </div>

          <button
            type="button"
            @click="imprimir"
            :disabled="!venta"
            class="w-full h-12 rounded-xl bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm shadow-md shadow-orange-200 transition-all flex items-center justify-center gap-2"
          >
            <i class="fa-solid fa-print"></i> Imprimir boleta
          </button>

          <div v-if="resumen" class="grid grid-cols-2 gap-2">
            <Link
              :href="`${route('admin.sales.index')}?venta=${ventaId}`"
              class="h-11 rounded-xl bg-surface border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold text-sm transition-colors flex items-center justify-center gap-2"
            >
              <i class="fa-solid fa-receipt"></i> Ver en historial
            </Link>
            <button
              type="button"
              @click="emit('close')"
              class="h-11 rounded-xl text-gray-500 hover:bg-gray-100 font-semibold text-sm transition-colors"
            >
              Seguir vendiendo
            </button>
          </div>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import BoletaTicket from '@/Components/Admin/BoletaTicket.vue';

const props = defineProps({
  ventaId: { type: Number, required: true },
  // Solo al registrar una venta: { total, prendas, cliente }. Sin él (historial) se muestra la boleta a secas.
  resumen: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const venta = ref(null);
const cargando = ref(true);
const falló = ref(false);

const total = computed(() => props.resumen?.total ?? (venta.value ? venta.value.totalNeto : null));
const prendas = computed(() => props.resumen?.prendas ?? venta.value?.totalPrendas ?? 0);
const cliente = computed(() => props.resumen ? props.resumen.cliente : venta.value?.cliente);

// El fondo solo cierra el modal si el clic EMPEZÓ en el fondo (igual que ModalShell).
const pulsoEnElFondo = ref(false);

// Mismo ancho de papel que la página de boleta (se recuerda en este equipo).
const ANCHO_KEY = 'boleta.ancho';
const leerAncho = () => {
  try {
    return localStorage.getItem(ANCHO_KEY) === '80' ? 80 : 58;
  } catch {
    return 58;
  }
};
const ancho = ref(leerAncho());
const elegirAncho = (mm) => {
  ancho.value = mm;
  try {
    localStorage.setItem(ANCHO_KEY, String(mm));
  } catch { /* sin almacenamiento: se usa solo en esta sesión */ }
};

// Al imprimir, el estilo global oculta el resto de la página y deja solo la boleta.
const imprimir = () => window.print();

// Si la página quedó ampliada (iOS hace zoom al escribir en un campo), el modal fijo se sale de la pantalla. Fijar un momento
// el zoom máximo en 1 devuelve la página a su tamaño; enseguida se restaura la configuración normal.
const restablecerZoom = () => {
  const meta = document.querySelector('meta[name="viewport"]');
  if (!meta) return;

  const original = meta.getAttribute('content');
  meta.setAttribute('content', `${original}, maximum-scale=1`);
  setTimeout(() => meta.setAttribute('content', original), 350);
};

// En iOS, `overflow: hidden` en el body no impide que la página de atrás se mueva al deslizar: se fija el body en su
// lugar y se restaura la posición al cerrar. Así solo se desliza la boleta, y la cabecera y los botones se quedan fijos.
let scrollGuardado = 0;
const bloquearPagina = () => {
  scrollGuardado = window.scrollY;
  const b = document.body.style;
  b.position = 'fixed';
  b.top = `-${scrollGuardado}px`;
  b.left = '0';
  b.right = '0';
  b.width = '100%';
  b.overflow = 'hidden';
};
const liberarPagina = () => {
  const b = document.body.style;
  b.position = b.top = b.left = b.right = b.width = b.overflow = '';
  window.scrollTo(0, scrollGuardado);
};

const alTeclear = (e) => {
  if (e.key === 'Escape') emit('close');
};

onMounted(async () => {
  document.addEventListener('keydown', alTeclear);
  restablecerZoom();
  bloquearPagina();
  document.body.classList.add('boleta-modal-abierta');

  try {
    const { data } = await axios.get(route('admin.sales.boleta.datos', props.ventaId));
    venta.value = data;
  } catch (e) {
    falló.value = true;
  } finally {
    cargando.value = false;
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('keydown', alTeclear);
  liberarPagina();
  document.body.classList.remove('boleta-modal-abierta');
});
</script>

<style>
.modal-boleta {
  animation: modal-boleta-in 180ms cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modal-boleta-in {
  from { opacity: 0; transform: translateX(24px); }
}
@media (prefers-reduced-motion: reduce) {
  .modal-boleta { animation: none; }
}

@media print {
  /* Solo sale la boleta: se oculta la app y el modal pasa a flujo normal. */
  /* El body se fija mientras el modal está abierto (ver bloquearPagina): al imprimir vuelve a su flujo normal. */
  body.boleta-modal-abierta { position: static !important; top: auto !important; width: auto !important; overflow: visible !important; }
  body.boleta-modal-abierta #app { display: none !important; }
  body.boleta-modal-abierta .boleta-modal-raiz {
    position: static !important;
    background: none !important;
    backdrop-filter: none !important;
    padding: 0 !important;
    display: block !important;
  }
  body.boleta-modal-abierta .modal-boleta {
    max-height: none !important;
    box-shadow: none !important;
    width: auto !important;
    height: auto !important;
    display: block !important;
    animation: none;
  }
}
</style>
