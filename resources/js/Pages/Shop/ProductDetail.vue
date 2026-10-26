<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import { add } from '@/cart';
import { isInWishlist, toggle as toggleWishlistItem } from '@/wishlist';

const page = usePage();

window.__streetUrbanCustomerId = page.props.auth?.type === 'customer'
  ? page.props.auth.user.id
  : null;

const props = defineProps({
  producto: Object,
});

const selectedColorIndex = ref(0);
const selectedTalla = ref(null);
const agregado = ref(false);
const showComentarios = ref(false);
const enFavoritos = ref(false);

const isLoggedIn = computed(() => !!page.props.auth?.user);

const syncFavorito = async () => {
  const colorId = currentColor.value?.id;
  enFavoritos.value = isLoggedIn.value && !!colorId && await isInWishlist(colorId);
};

const toggleFavorito = async () => {
  if (!isLoggedIn.value) {
    window.dispatchEvent(new Event('open-auth-modal'));
    return;
  }

  const colorId = currentColor.value?.id;
  if (!colorId) return;

  const added = await toggleWishlistItem(colorId);
  if (added !== null) enFavoritos.value = added;
};

onMounted(() => {
  syncFavorito();
  window.addEventListener('wishlist-updated', syncFavorito);
});

onUnmounted(() => {
  window.removeEventListener('wishlist-updated', syncFavorito);
});

const zooming = ref(false);
const zoomX = ref(50);
const zoomY = ref(50);

const onImageMouseMove = (e) => {
  const rect = e.currentTarget.getBoundingClientRect();
  zoomX.value = ((e.clientX - rect.left) / rect.width) * 100;
  zoomY.value = ((e.clientY - rect.top) / rect.height) * 100;
};

const hayColores = computed(() => props.producto.colores.length > 0);

const currentColor = computed(() => hayColores.value ? props.producto.colores[selectedColorIndex.value] : null);

const currentTallaInfo = computed(() => {
  if (!currentColor.value || !selectedTalla.value) return null;
  return currentColor.value.tallas.find(t => t.talla === selectedTalla.value) || null;
});

const currentPrecio = computed(() => {
  if (currentTallaInfo.value) return currentTallaInfo.value.precio;
  if (currentColor.value) return currentColor.value.precio;
  return props.producto.price;
});

const currentImage = computed(() => currentColor.value?.imagen || props.producto.image);

const seleccionarColor = (index) => {
  selectedColorIndex.value = index;
  selectedTalla.value = null;
  agregado.value = false;
  syncFavorito();
};

const seleccionarTalla = (t) => {
  if (t.stock <= 0) return;
  selectedTalla.value = t.talla;
  agregado.value = false;
};

const puedeAgregar = computed(() => !!currentColor.value && !!selectedTalla.value);

const agregarAlCarrito = async () => {
  if (!puedeAgregar.value) return;

  try {
    const result = await add({
      productId: props.producto.id,
      variantId: currentTallaInfo.value.variant_id,
      name: props.producto.name,
      price: currentPrecio.value,
      image: currentImage.value,
      colorId: currentColor.value.id,
      colorNombre: currentColor.value.nombre,
      talla: selectedTalla.value,
    });

    if (result?.valid === false) {
      window.dispatchEvent(new CustomEvent('cart-error', {
        detail: result.errors?.[0]?.message || 'No hay suficiente stock disponible.',
      }));
      return;
    }

    agregado.value = true;
  } catch (error) {
    window.dispatchEvent(new CustomEvent('cart-error', {
      detail: error.response?.data?.message || error.message || 'No se pudo añadir el producto.',
    }));
  }
};
</script>

<template>
  <Head :title="producto.name" />
  <ShopLayout>
    <div class="pt-20"></div>

    <section class="px-4 md:px-8 lg:px-16 max-w-[1400px] mx-auto py-10">
      <!-- Breadcrumb -->
      <div class="text-xs text-gray-400 mb-8 flex items-center gap-2 flex-wrap">
        <Link :href="route('shop.home')" class="hover:text-fg transition-colors">Inicio</Link>
        <span>/</span>
        <Link :href="`${route('shop.tienda')}?category=${producto.category}`" class="hover:text-fg transition-colors">{{ producto.category }}</Link>
        <span>/</span>
        <span class="text-fg">{{ producto.name }}</span>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Imagen con zoom al pasar el mouse -->
        <div
          class="relative aspect-square bg-surface border border-gray-800 rounded-2xl overflow-hidden flex items-center justify-center"
          :class="currentImage ? 'cursor-zoom-in' : ''"
          @mousemove="onImageMouseMove"
          @mouseenter="zooming = true"
          @mouseleave="zooming = false"
        >
          <img
            v-if="currentImage"
            :src="currentImage"
            :alt="producto.name"
            class="w-full h-full object-cover transition-transform duration-150 ease-out"
            :style="zooming ? { transform: 'scale(2.2)', transformOrigin: `${zoomX}% ${zoomY}%` } : {}"
          >
          <div v-else class="flex flex-col items-center text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-16 h-16 mb-2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-15-4.35l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0z" />
            </svg>
            <p class="text-sm">Imagen no disponible</p>
          </div>
        </div>

        <!-- Info -->
        <div>
          <p class="text-xs uppercase tracking-widest text-gray-400 mb-3">{{ producto.brand }} · {{ producto.category }}</p>

          <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight italic mb-4">{{ producto.name }}</h1>

          <p class="text-2xl font-bold text-fg mb-6">S/ {{ currentPrecio.toFixed(2) }}</p>

          <p v-if="producto.description" class="text-sm text-gray-400 leading-relaxed mb-8">{{ producto.description }}</p>

          <!-- Sin stock -->
          <div v-if="!hayColores" class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 text-sm font-semibold mb-6">
            Agotado — no hay stock disponible en ningún color por ahora.
          </div>

          <template v-else>
            <!-- Colores -->
            <div class="mb-8">
              <h3 class="text-sm font-bold uppercase tracking-wider mb-3">Colores</h3>
              <div class="flex items-start gap-3 flex-wrap">
                <button
                  v-for="(color, index) in producto.colores"
                  :key="color.nombre"
                  type="button"
                  @click="seleccionarColor(index)"
                  class="flex flex-col items-center gap-2"
                  :title="color.nombre"
                >
                  <span class="w-16 h-16 rounded-lg overflow-hidden bg-surface border border-gray-800">
                    <img v-if="color.imagen" :src="color.imagen" :alt="color.nombre" class="w-full h-full object-cover">
                    <span v-else class="block w-full h-full" :style="{ backgroundColor: color.hex || '#6b7280' }"></span>
                  </span>
                  <span
                    class="h-0.5 w-16 rounded-full transition-colors"
                    :class="selectedColorIndex === index ? 'bg-[#ff8c42]' : 'bg-transparent'"
                  ></span>
                </button>
              </div>
              <p class="text-sm text-gray-400 mt-3">{{ currentColor?.nombre }}</p>
            </div>

            <!-- Tallas -->
            <div class="mb-6">
              <h3 class="text-sm font-bold uppercase tracking-wider mb-3">Tallas</h3>
              <div class="grid grid-cols-4 gap-2">
                <button
                  v-for="t in currentColor.tallas"
                  :key="t.talla"
                  type="button"
                  @click="seleccionarTalla(t)"
                  :disabled="t.stock <= 0"
                  class="relative py-3 rounded-lg text-sm font-semibold border transition-all"
                  :class="[
                    t.stock <= 0
                      ? 'border-transparent bg-surface text-gray-600 line-through cursor-not-allowed'
                      : selectedTalla === t.talla
                        ? 'bg-[#ff8c42] border-[#ff8c42] text-black'
                        : 'border-gray-700 text-gray-300 hover:border-gray-400 bg-surface'
                  ]"
                >
                  {{ t.talla }}
                  <i v-if="t.stock <= 0" class="fa-regular fa-bell absolute top-1 right-1.5 text-[10px] text-gray-500"></i>
                </button>
              </div>
              <p v-if="currentTallaInfo" class="text-xs text-gray-500 mt-3">{{ currentTallaInfo.stock }} unidades disponibles</p>
              <p v-else class="text-xs text-gray-500 mt-3">Elige una talla para ver el stock disponible.</p>
            </div>

            <!-- Talla real -->
            <div class="flex items-start gap-3 border border-gray-800 rounded-xl px-4 py-3 mb-8">
              <i class="fa-regular fa-circle-question text-gray-400 mt-0.5"></i>
              <p class="text-xs text-gray-400 leading-relaxed">
                <span class="text-fg font-semibold">Talla real.</span>
                Te recomendamos pedir tu talla habitual.
              </p>
            </div>

            <!-- Agregar al carrito -->
            <div class="flex items-stretch gap-3">
              <button
                @click="agregarAlCarrito"
                :disabled="!puedeAgregar"
                class="flex-1 py-4 font-black uppercase tracking-widest rounded-lg transition-all bg-[#ff8c42] text-black hover:bg-[#ffb380] disabled:bg-gray-800 disabled:text-gray-500 disabled:cursor-not-allowed"
              >
                {{ agregado ? '¡Agregado!' : (puedeAgregar ? 'Añadir al carrito' : 'Elige color y talla') }}
              </button>
              <button
                type="button"
                @click="toggleFavorito"
                class="w-14 flex items-center justify-center rounded-lg border transition-colors"
                :class="enFavoritos ? 'border-[#ff8c42] text-[#ff8c42]' : 'border-gray-700 text-gray-400 hover:border-gray-400'"
                title="Añadir a favoritos"
              >
                <i :class="enFavoritos ? 'fa-solid fa-heart' : 'fa-regular fa-heart'"></i>
              </button>
            </div>
          </template>
        </div>
      </div>
    </section>

    <!-- Pestaña lateral fija: Comentarios -->
    <button
      v-if="!showComentarios"
      @click="showComentarios = true"
      class="hidden lg:flex items-center gap-2 fixed top-1/2 right-0 -translate-y-1/2 z-30 bg-surface border border-gray-700 border-r-0 text-gray-300 text-xs font-black uppercase tracking-widest px-3 py-6 rounded-l-lg hover:bg-surface-2 hover:text-fg transition-colors [writing-mode:vertical-rl] rotate-180"
    >
      Comentarios
    </button>

    <!-- Panel deslizable de comentarios -->
    <Teleport to="body">
      <div v-if="showComentarios" class="fixed inset-0 z-50 bg-black/50" @click.self="showComentarios = false">
        <div class="fixed top-0 right-0 h-full w-full max-w-md bg-canvas border-l border-gray-800 shadow-2xl overflow-y-auto">
          <div class="sticky top-0 bg-canvas border-b border-gray-800 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-black uppercase tracking-tight italic">Comentarios</h2>
            <button @click="showComentarios = false" class="p-2 hover:bg-gray-800 rounded-lg transition">
              <i class="fa-solid fa-xmark text-gray-400"></i>
            </button>
          </div>
          <div class="px-6 py-20 text-center text-gray-500">
            <i class="fa-regular fa-comment-dots text-3xl mb-4 block"></i>
            <p class="text-sm">Aún no hay comentarios para este producto.</p>
            <p class="text-sm">Sé el primero en dejar tu opinión.</p>
          </div>
        </div>
      </div>
    </Teleport>
  </ShopLayout>
</template>
