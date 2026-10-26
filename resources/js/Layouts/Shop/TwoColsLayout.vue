<script setup></script>

<template>
  <section class="px-4 md:px-8 lg:px-16 max-w-[1400px] mx-auto pt-12 pb-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
      <!-- Contenido a la izquierda -->
      <div class="lg:col-span-2 space-y-6">
        <slot name="left-content" />
      </div>
      <!-- Barra lateral derecha -->
      <div class="lg:col-span-1">
        <div class="sticky top-28 bg-sunken p-8 border border-gray-800 rounded-2xl">
          <slot name="right-sidebar" />
        </div>
      </div>
    </div>
  </section>
</template>