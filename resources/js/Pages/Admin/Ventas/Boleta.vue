<template>
  <Head :title="`Boleta ${venta.numero}`" />

  <div class="min-h-screen bg-gray-200 py-8 px-4 print:bg-surface print:p-0">
    <div class="max-w-md mx-auto print:max-w-none">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-6 print:hidden">
        <Link :href="route('admin.sales.index')" class="text-sm font-semibold text-gray-500 hover:text-gray-800 flex items-center gap-1.5 transition-colors">
          <i class="fa-solid fa-arrow-left text-xs"></i> Volver al Historial
        </Link>

        <div class="flex items-center gap-3">
          <div class="inline-flex p-0.5 bg-surface border border-gray-200 rounded-xl" role="group" aria-label="Ancho del papel">
            <button
              v-for="mm in [58, 80]"
              :key="mm"
              type="button"
              @click="elegirAncho(mm)"
              :aria-pressed="ancho === mm"
              :class="['px-3 h-9 text-xs font-bold rounded-lg transition-colors', ancho === mm ? 'bg-fg text-canvas' : 'text-gray-500 hover:text-gray-800']"
            >{{ mm }} mm</button>
          </div>
          <button
            @click="imprimir"
            class="bg-[#ff8c42] hover:bg-[#f27d30] active:scale-95 text-white text-sm font-bold h-10 px-4 rounded-xl shadow-md shadow-orange-200 transition-all flex items-center gap-2"
          >
            <i class="fa-solid fa-print text-xs"></i> Imprimir
          </button>
        </div>
      </div>

      <div class="flex justify-center print:block">
        <BoletaTicket :venta="venta" :ancho="ancho" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import BoletaTicket from '@/Components/Admin/BoletaTicket.vue';

defineProps({
  venta: Object,
});

// Ancho del papel de la boletera (58 u 80 mm); se recuerda en este equipo.
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

const imprimir = () => window.print();
</script>
