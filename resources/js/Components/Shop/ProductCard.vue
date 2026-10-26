<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { isInWishlist, toggle as toggleWishlistItem } from '@/wishlist';

const page = usePage();

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
});

const imageLoaded = ref(false);
const imageError = ref(false);
const isFavorite = ref(false);

const isLoggedIn = computed(() => !!page.props.auth?.user);

// La tarjeta no tiene un color "seleccionado" explícito (solo el hover
// temporal sobre los puntitos), así que el corazón favorita el primer color
// del modelo, igual que la imagen que se muestra por defecto.
const favoriteColorId = computed(() => props.product.colores?.[0]?.id ?? null);

const syncFavorite = async () => {
  isFavorite.value = isLoggedIn.value && !!favoriteColorId.value && await isInWishlist(favoriteColorId.value);
};

onMounted(() => {
  syncFavorite();
  window.addEventListener('wishlist-updated', syncFavorite);
});

onUnmounted(() => {
  window.removeEventListener('wishlist-updated', syncFavorite);
});

const toggleFavorite = async () => {
  if (!isLoggedIn.value) {
    window.dispatchEvent(new Event('open-auth-modal'));
    return;
  }

  if (!favoriteColorId.value) return;

  const added = await toggleWishlistItem(favoriteColorId.value);
  if (added !== null) isFavorite.value = added;
};

const handleImageLoad = () => {
  imageLoaded.value = true;
};

const handleImageError = () => {
  imageError.value = true;
};

// "Nuevo" viene del servidor según la fecha real de alta. No hay descuentos: mostrar un
// precio "antes" inventado sería publicidad engañosa.
const isNew = computed(() => !!props.product.isNew);

const hoveredColor = ref(null);

const productImage = computed(() => {
  if (hoveredColor.value?.imagen) {
    return hoveredColor.value.imagen;
  }
  if (imageError.value) {
    return null;
  }
  return props.product.image;
});

// El precio puede variar por color (ej. una edición limitada), así que al pasar el
// mouse por un color se muestra su precio real en vez del precio "desde" genérico.
const displayPrice = computed(() => hoveredColor.value?.precio ?? props.product.price);

const irADetalle = () => {
  router.visit(route('shop.producto', props.product.id));
};

</script>

<template>
  <article
    class="group relative flex flex-col cursor-pointer"
    @click="irADetalle"
  >
    <!-- Foto -->
    <div class="relative aspect-[4/5] overflow-hidden rounded-xl bg-surface">
      <div v-if="productImage && !imageLoaded && !imageError" class="absolute inset-0 bg-gray-800 animate-pulse" aria-hidden="true"></div>

      <!-- Sin foto todavía: portada neutra de marca -->
      <div v-if="imageError || !productImage" class="theme-shop-dark absolute inset-0 bg-gradient-to-br from-[#1c1c1e] to-[#0b0b0c] overflow-hidden" aria-hidden="true">
        <img src="/image/logo/street-urban-icono-dark.png" alt="" class="absolute -right-8 -bottom-10 h-[95%] w-auto max-w-none aspect-square opacity-[0.12] -rotate-12 transition-transform duration-700 group-hover:-rotate-6" />
        <p class="absolute left-4 bottom-4 text-[11px] font-semibold uppercase tracking-[0.2em] text-white/40">Foto próximamente</p>
      </div>

      <img
        v-if="productImage"
        :src="productImage"
        :alt="product.name"
        loading="lazy"
        class="h-full w-full object-cover object-center transition-[transform,opacity] duration-500 group-hover:scale-105"
        :class="imageLoaded ? 'opacity-100' : 'opacity-0'"
        @load="handleImageLoad"
        @error="handleImageError"
      />

      <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-10 pointer-events-none">
        <span v-if="!product.stock" class="bg-white text-black text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full">Agotado</span>
        <span v-else-if="isNew" class="bg-[#ff8c42] text-black text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full">Nuevo</span>
      </div>

      <button
        type="button"
        class="absolute top-2 right-2 z-20 w-11 h-11 rounded-full flex items-center justify-center bg-white/90 text-black shadow-sm hover:bg-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
        :aria-label="isFavorite ? `Quitar ${product.name} de favoritos` : `Añadir ${product.name} a favoritos`"
        :aria-pressed="isFavorite"
        @click.stop.prevent="toggleFavorite"
      >
        <svg xmlns="http://www.w3.org/2000/svg" :fill="isFavorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" :class="['w-5 h-5', isFavorite ? 'text-red-500' : '']" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
        </svg>
      </button>

      <!-- Acción al pasar el mouse (en táctil toda la tarjeta lleva al detalle) -->
      <span class="pointer-events-none absolute inset-x-3 bottom-3 hidden md:flex h-11 items-center justify-center rounded-lg bg-white text-black text-xs font-bold uppercase tracking-widest opacity-0 translate-y-2 transition-all duration-300 group-hover:opacity-100 group-hover:translate-y-0 motion-reduce:transition-none" aria-hidden="true">
        {{ product.stock ? 'Elegir talla' : 'Ver detalle' }}
      </span>
    </div>

    <!-- Datos -->
    <div class="pt-4 flex flex-col gap-1">
      <div class="flex items-start justify-between gap-3">
        <h3 class="text-sm font-semibold text-fg leading-snug">
          <a :href="route('shop.producto', product.id)" class="after:absolute after:inset-0 focus:outline-none focus-visible:underline" @click.prevent>{{ product.name }}</a>
        </h3>
        <span class="text-sm font-bold text-fg whitespace-nowrap">S/ {{ displayPrice.toFixed(2) }}</span>
      </div>
      <p class="text-xs text-gray-500">
        {{ product.brand }}<template v-if="Number(product.sold) > 0"> · {{ product.sold }} vendido{{ Number(product.sold) === 1 ? '' : 's' }}</template>
      </p>

      <!-- Colores: al pasar el mouse se ve su precio, tallas y foto -->
      <div v-if="product.colores?.length" class="relative z-10 mt-2 flex items-center gap-1.5 flex-wrap">
        <div
          v-for="color in product.colores.slice(0, 6)"
          :key="color.nombre"
          class="relative"
          @mouseenter="hoveredColor = color"
          @mouseleave="hoveredColor = null"
        >
          <button
            type="button"
            class="w-5 h-5 rounded-full ring-1 ring-gray-700 ring-offset-2 ring-offset-canvas hover:ring-fg transition"
            :style="{ backgroundColor: color.hex || '#6b7280' }"
            :aria-label="`${color.nombre}, S/ ${color.precio.toFixed(2)}`"
            @click.stop="irADetalle"
            @focus="hoveredColor = color"
            @blur="hoveredColor = null"
          ></button>

          <div
            v-if="hoveredColor === color"
            class="absolute bottom-full left-0 mb-2 w-max max-w-[180px] bg-surface border border-gray-700 rounded-lg px-3 py-2 shadow-xl z-30 pointer-events-none"
          >
            <p class="text-xs font-bold text-fg mb-0.5">{{ color.nombre }} · S/ {{ color.precio.toFixed(2) }}</p>
            <p class="text-[11px] text-gray-400">Tallas: {{ color.tallas.map(t => t.talla).join(', ') }}</p>
          </div>
        </div>
        <span v-if="product.colores.length > 6" class="text-xs text-gray-500">+{{ product.colores.length - 6 }}</span>
      </div>
    </div>
  </article>
</template>
