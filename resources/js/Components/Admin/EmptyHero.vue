<script setup>
// Pantalla de bienvenida para un nivel vacío del inventario: un icono grande, qué hacer y el botón
// (y las sugerencias) para empezar. Reemplaza a "tarjeta de agregar + recuadro vacío" juntos.
defineProps({
  icon: { type: String, default: 'fa-solid fa-box-open' },
  title: { type: String, required: true },
  text: { type: String, default: '' },
});
</script>

<template>
  <div class="relative overflow-hidden rounded-3xl border border-orange-100 bg-gradient-to-br from-orange-50 via-white to-amber-50 px-6 py-14 text-center">
    <div class="pointer-events-none absolute -top-16 -right-16 w-60 h-60 rounded-full bg-orange-100/60 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-16 w-60 h-60 rounded-full bg-amber-100/60 blur-3xl"></div>

    <div class="relative">
      <div class="mx-auto mb-6 w-20 h-20 rounded-3xl bg-white shadow-lg shadow-orange-100 ring-1 ring-orange-100 flex items-center justify-center text-3xl text-[#ff8c42]">
        <i :class="icon"></i>
      </div>

      <h4 class="text-xl font-bold text-gray-900">{{ title }}</h4>
      <p v-if="text" class="text-sm text-gray-500 mt-2 max-w-md mx-auto leading-relaxed">{{ text }}</p>

      <div v-if="$slots.default" class="mt-7"><slot /></div>
    </div>
  </div>
</template>
