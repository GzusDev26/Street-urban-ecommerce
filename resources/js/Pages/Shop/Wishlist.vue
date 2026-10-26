<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import { toggle } from '@/wishlist';

const props = defineProps({
  items: Array,
});

const items = ref(props.items);

const removeItem = async (id) => {
  await toggle(id);
  items.value = items.value.filter(item => item.id !== id);
};
</script>

<template>
  <Head title="Mi lista de deseos" />
  <ShopLayout>
    <div class="pt-20"></div>

    <section class="px-4 md:px-8 lg:px-16 max-w-[1400px] mx-auto py-10">
      <h1 class="text-2xl md:text-3xl font-black uppercase tracking-tight italic mb-8">
        Mi lista de deseos <span class="text-gray-500 font-normal not-italic text-lg">({{ items.length }} {{ items.length === 1 ? 'producto' : 'productos' }})</span>
      </h1>

      <!-- Vacío -->
      <div v-if="items.length === 0" class="py-20 text-center border border-dashed border-gray-800 rounded-2xl">
        <i class="fa-regular fa-heart text-3xl text-gray-600 mb-4 block"></i>
        <p class="text-gray-400">Aún no has agregado productos a tu lista de deseos.</p>
        <Link :href="route('shop.tienda')" class="mt-4 inline-block text-fg border-b border-fg hover:opacity-70">
          Ver productos
        </Link>
      </div>

      <!-- Grid de productos -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="item in items"
          :key="item.id"
          class="relative bg-surface border border-gray-800 rounded-xl overflow-hidden flex flex-col"
        >
          <button
            @click="removeItem(item.id)"
            class="absolute top-3 right-3 z-10 w-8 h-8 flex items-center justify-center bg-canvas/80 border border-gray-700 rounded-full hover:bg-surface-2 transition-colors"
            aria-label="Quitar de la lista de deseos"
          >
            <i class="fa-solid fa-xmark text-gray-300 text-sm"></i>
          </button>

          <Link :href="route('shop.producto', item.productId)" class="aspect-square bg-sunken flex items-center justify-center overflow-hidden">
            <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover">
            <i v-else class="fa-regular fa-image text-3xl text-gray-700"></i>
          </Link>

          <div class="p-4 flex flex-col flex-1">
            <Link :href="route('shop.producto', item.productId)" class="text-sm font-semibold text-fg hover:text-gray-300 transition-colors line-clamp-2 mb-1">
              {{ item.name }}
            </Link>
            <p v-if="item.colorNombre" class="text-xs text-gray-500 mb-2">{{ item.colorNombre }}</p>
            <p class="text-base font-bold text-fg mb-4 mt-auto">S/ {{ item.price.toFixed(2) }}</p>

            <Link
              :href="route('shop.producto', item.productId)"
              class="w-full py-3 flex items-center justify-center gap-2 font-black uppercase text-xs tracking-widest rounded-lg bg-[#ff8c42] text-black hover:bg-[#ffb380] transition-colors"
            >
              Ver producto
              <i class="fa-solid fa-bag-shopping text-xs"></i>
            </Link>
          </div>
        </div>
      </div>
    </section>
  </ShopLayout>
</template>
