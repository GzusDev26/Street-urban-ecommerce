<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import ProductCard from '@/Components/Shop/ProductCard.vue';

defineProps({
  products: Array,
  categories: Array,
});

const visible = ref(false);

onMounted(() => {
  requestAnimationFrame(() => { visible.value = true; });
});
</script>

<template>
  <Head title="Home" />
  <ShopLayout>
    <!-- Hero -->
    <section class="relative w-full h-[80vh] flex items-center justify-center overflow-hidden">
      <img
        src="/image/shop/hero-bg.jpg"
        alt="Hero Background"
        class="absolute inset-0 w-full h-full object-cover scale-105 transition-transform duration-1000"
      >
      <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-[#111111]"></div>

      <div class="relative z-10 text-center px-4 max-w-4xl">
        <h1
          class="text-5xl md:text-7xl font-black uppercase tracking-tighter mb-6 text-white italic transition-all duration-1000"
          :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
        >
          Street <span class="text-white/50">Urban</span>
        </h1>
        <p
          class="text-lg md:text-xl text-gray-300 mb-10 font-light tracking-wide max-w-2xl mx-auto transition-all duration-1000 delay-200"
          :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
        >
          Define tu estilo. Domina las calles con nuestra nueva colección de temporada.
        </p>
        <div
          class="flex flex-col sm:flex-row items-center justify-center gap-4 transition-all duration-1000 delay-500"
          :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
        >
          <a
            href="#productos"
            class="w-full sm:w-auto px-10 py-4 bg-[#ff8c42] text-black font-bold uppercase tracking-widest hover:bg-[#ffb380] transition-all duration-300 text-sm rounded-lg"
          >
            Comprar Ahora
          </a>
          <a
            href="#"
            class="w-full sm:w-auto px-10 py-4 border border-white/30 text-white font-bold uppercase tracking-widest hover:bg-white/10 transition-all duration-300 text-sm"
          >
            Nueva Colección
          </a>
        </div>
      </div>
    </section>

    <!-- Categories Preview -->
    <section class="py-32 px-4 md:px-8 lg:px-16 max-w-[1600px] mx-auto">
      <div class="space-y-2 mb-12">
        <div class="h-px bg-gradient-to-r from-transparent via-[#ff8c42]/30 to-transparent"></div>
      </div>
      <h2 class="text-2xl md:text-3xl font-black uppercase tracking-tighter mb-12 italic">
        Explora por <span class="text-white/50">Categoría</span>
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <Link
          v-for="category in categories"
          :key="category.name"
          :href="`${route('shop.tienda')}?category=${encodeURIComponent(category.name)}`"
          class="group relative h-80 rounded-2xl overflow-hidden block bg-[#1a1a1a]"
        >
          <img
            v-if="category.image"
            :src="category.image"
            :alt="category.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent group-hover:from-black/90 transition-all duration-300"></div>
          <div class="absolute bottom-0 left-0 right-0 p-6">
            <h3 class="text-2xl font-black uppercase tracking-tight text-white">{{ category.name }}</h3>
            <p class="text-sm text-gray-300 mt-2 opacity-0 group-hover:opacity-100 transition-opacity">Ver colección</p>
          </div>
        </Link>
      </div>
    </section>

    <!-- Product Grid -->
    <section id="productos" class="py-24 px-4 md:px-8 lg:px-16 max-w-[1600px] mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-4">
        <div>
          <h2 class="text-3xl md:text-5xl font-black uppercase tracking-tighter italic">
            Colección <span class="text-white/50">Featured</span>
          </h2>
          <div class="h-1.5 w-32 bg-gradient-to-r from-[#ff8c42] to-white/20 mt-6"></div>
        </div>
        <p class="text-gray-400 text-xs uppercase tracking-widest font-mono border border-white/10 px-4 py-2 rounded">
          {{ products.length }} Items en Stock
        </p>
      </div>

      <div v-if="products.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <ProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
        />
      </div>

      <div v-else class="py-20 text-center">
        <p class="text-gray-500 text-xl italic">No hay productos disponibles en este momento.</p>
      </div>
    </section>

    <!-- Footer CTA - Newsletter -->
    <section class="relative py-32 px-4 md:px-8 border-t border-gray-800 overflow-hidden">
      <!-- Animated background -->
      <div class="absolute inset-0">
        <div class="absolute top-1/2 right-0 w-96 h-96 bg-[#ff8c42]/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
      </div>

      <div class="relative max-w-3xl mx-auto text-center">
        <p class="text-sm uppercase tracking-[0.3em] text-[#ff8c42] font-semibold mb-4">
          Sé el primero
        </p>
        <h2 class="text-3xl md:text-5xl font-black uppercase tracking-tight italic mb-6">
          Recibe nuestros <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff8c42] to-white">Drops</span> Primero
        </h2>
        <p class="text-gray-400 mb-10 text-lg leading-relaxed">Suscríbete y obtén acceso exclusivo a nuevos lanzamientos, ofertas especiales y contenido detrás de cámaras.</p>

        <form class="flex flex-col sm:flex-row gap-3 mb-8 max-w-lg mx-auto">
          <input aria-label="Correo electrónico"
            type="email"
            placeholder="tu@email.com"
            required
            class="flex-1 px-6 py-4 bg-white/10 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-[#ff8c42] focus:outline-none transition-colors"
          />
          <button
            type="submit"
            class="px-8 py-4 bg-[#ff8c42] text-black font-bold uppercase text-sm rounded-lg hover:bg-[#ffb380] transition-colors whitespace-nowrap"
          >
            Suscribir
          </button>
        </form>

        <div class="flex justify-center gap-6 pt-8 border-t border-gray-800">
          <!-- Facebook -->
          <a :href="route('social.facebook')" class="w-12 h-12 rounded-lg border border-gray-800 flex items-center justify-center hover:border-[#ff8c42] hover:bg-[#ff8c42]/10 transition-all group" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" fill="currentColor" class="w-5 h-5 text-[#ff8c42] group-hover:scale-110 transition-transform">
              <!-- Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc. -->
              <path d="M80 299.3l0 212.7 116 0 0-212.7 86.5 0 18-97.8-104.5 0 0-34.6c0-51.7 20.3-71.5 72.7-71.5 16.3 0 29.4 .4 37 1.2l0-88.7C291.4 4 256.4 0 236.2 0 129.3 0 80 50.5 80 159.4l0 42.1-66 0 0 97.8 66 0z"/>
            </svg>
          </a>
          <!-- X (Twitter) -->
          <a :href="route('social.twitter')" class="w-12 h-12 rounded-lg border border-gray-800 flex items-center justify-center hover:border-[#ff8c42] hover:bg-[#ff8c42]/10 transition-all group" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" class="w-5 h-5 text-[#ff8c42] group-hover:scale-110 transition-transform">
              <!-- Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc. -->
              <path d="M357.2 48L427.8 48 273.6 224.2 455 464 313 464 201.7 318.6 74.5 464 3.8 464 168.7 275.5-5.2 48 140.4 48 240.9 180.9 357.2 48zM332.4 421.8l39.1 0-252.4-333.8-42 0 255.3 333.8z"/>
            </svg>
          </a>
          <!-- Instagram -->
          <a :href="route('social.instagram')" class="w-12 h-12 rounded-lg border border-gray-800 flex items-center justify-center hover:border-[#ff8c42] hover:bg-[#ff8c42]/10 transition-all group" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor" class="w-5 h-5 text-[#ff8c42] group-hover:scale-110 transition-transform">
              <!-- Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc. -->
              <path d="M224.3 141a115 115 0 1 0 -.6 230 115 115 0 1 0 .6-230zm-.6 40.4a74.6 74.6 0 1 1 .6 149.2 74.6 74.6 0 1 1 -.6-149.2zm93.4-45.1a26.8 26.8 0 1 1 53.6 0 26.8 26.8 0 1 1 -53.6 0zm129.7 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM399 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/>
            </svg>
          </a>
        </div>
      </div>
    </section>
  </ShopLayout>
</template>
