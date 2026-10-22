<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import { getItems, setQuantity, removeItem as removeCartItem, validateGuestCart, hydrateCustomerCart } from '@/cart';
import { toggle as toggleWishlistItem } from '@/wishlist';

const page = usePage();
const isLoggedIn = computed(() => page.props.auth?.type === 'customer');

const cartItems = ref([]);
const movidoAFavoritos = ref(null);
const cartSummary = ref({ subtotal: 0, tax: 0, shipping: 0, total: 0, total_items: 0 });
const cartErrors = ref([]);
const busy = ref(false);

window.__streetUrbanCustomerId = isLoggedIn.value ? page.props.auth.user.id : null;

const IGV = 0.18;

// Calcula total y subtotal
const subtotal = computed(() => Number(cartSummary.value.subtotal || 0));

const igvIncluido = computed(() => Number(cartSummary.value.tax || 0));

const refreshFromServer = async () => {
  busy.value = true;

  try {
    const result = isLoggedIn.value
      ? await hydrateCustomerCart(page.props.auth.user.id)
      : await validateGuestCart();

    cartItems.value = getItems();
    cartSummary.value = result.summary || cartSummary.value;
    cartErrors.value = result.errors || [];
  } catch {
    cartErrors.value = [{ message: 'No se pudo validar el carrito. Inténtalo nuevamente.' }];
  } finally {
    busy.value = false;
  }
};

// Quita elementos del carrito
const removeItem = async (id) => {
  busy.value = true;
  const result = await removeCartItem(id);
  cartItems.value = getItems();
  cartSummary.value = result?.summary || cartSummary.value;
  cartErrors.value = result?.errors || [];
  busy.value = false;
};

// Actualiza la cantidad de un elemento a un valor absoluto (selector)
const changeQuantity = async (id, cantidad) => {
  busy.value = true;
  const result = await setQuantity(id, Number(cantidad));
  cartItems.value = getItems();
  cartSummary.value = result?.summary || cartSummary.value;
  cartErrors.value = result?.errors || [];
  busy.value = false;
};

// Mueve un producto del carrito a la lista de deseos
const moverAFavoritos = async (item) => {
  if (!item.colorId) return;

  if (!isLoggedIn.value) {
    window.dispatchEvent(new Event('open-auth-modal'));
    return;
  }

  const added = await toggleWishlistItem(item.colorId);
  if (added) {
    removeItem(item.id);
    movidoAFavoritos.value = item.id;
    setTimeout(() => { movidoAFavoritos.value = null; }, 2000);
  }
};

// Comprueba si no hay elementos en el carrito
const isCartEmpty = computed(() => cartItems.value.length === 0);

const handleCartError = (event) => {
  cartErrors.value = [{ message: event.detail }];
};

onMounted(refreshFromServer);
onMounted(() => window.addEventListener('cart-error', handleCartError));
onUnmounted(() => window.removeEventListener('cart-error', handleCartError));
</script>

<template>

  <Head title="Carrito" />
  <ShopLayout>
    <div class="pt-20"></div>

    <!-- Título de la página -->
    <section class="relative py-16 px-4 md:px-8 lg:px-16 overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-b from-white/5 via-transparent to-transparent"></div>
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>

      <div class="relative max-w-full mx-auto">
        <div>
          <!-- Título Principal -->
          <h1 class="text-3xl md:text-4xl lg:text-5xl font-black uppercase tracking-tight italic leading-tight mb-4 max-w-4xl">
            Tu <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500">Carrito</span>
          </h1>
          <div class="h-1.5 w-24 bg-gradient-to-r from-white via-white to-gray-600 rounded-full"></div>
        </div>
      </div>
    </section>

    <!-- Contenido en dos columnas -->
    <section class="px-4 md:px-8 lg:px-16 max-w-[1400px] mx-auto pb-16">
      <div v-if="cartErrors.length" class="mb-6 space-y-2">
        <div v-for="(error, index) in cartErrors" :key="`${error.variant_id || 'cart'}-${index}`" class="rounded-xl border border-red-900/50 bg-red-950/30 px-4 py-3 text-sm text-red-200">
          {{ error.message }}
        </div>
      </div>

      <div v-if="busy" class="mb-6 text-sm text-gray-400">Validando disponibilidad y precios...</div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

        <!-- Productos del carrito -->
        <div class="lg:col-span-2">
          <!-- Si el carrito está vacío -->
          <div v-if="cartItems.length === 0" class="py-20 text-center border border-dashed border-gray-800 rounded-2xl">
            <p>Tu carrito está vacío.</p>
            <Link :href="route('shop.tienda')"
              class="mt-4 inline-block text-white border-b border-white hover:opacity-70">Ver productos</Link>
          </div>

          <!-- Recorre los productos -->
          <div v-else class="space-y-4">
            <div v-for="item in cartItems" :key="item.id"
              class="group flex gap-6 p-5 bg-[#141414] border border-gray-800/80 rounded-2xl hover:border-gray-700 hover:shadow-xl hover:shadow-black/30 transition-all duration-300">

              <div class="w-28 h-28 bg-white rounded-xl p-3 flex-shrink-0 shadow-inner">
                <img :src="item.image" :alt="item.name" class="w-full h-full object-contain" />
              </div>

              <div class="flex-1 min-w-0 flex flex-col">
                <div class="flex items-start justify-between gap-4">
                  <h3 class="font-bold text-base uppercase tracking-tight truncate">{{ item.name }}</h3>
                  <button
                    @click="removeItem(item.id)"
                    title="Eliminar"
                    class="w-9 h-9 -mr-2 -mt-2 flex items-center justify-center rounded-full text-gray-500 hover:text-red-400 hover:bg-red-500/10 transition-colors flex-shrink-0"
                  >
                    <i class="fa-solid fa-trash-can text-sm"></i>
                  </button>
                </div>

                <p v-if="item.colorNombre || item.talla" class="text-xs text-gray-400 mt-1 tracking-wide">
                  <span v-if="item.colorNombre">{{ item.colorNombre }}</span>
                  <span v-if="item.colorNombre && item.talla"> · </span>
                  <span v-if="item.talla">Talla {{ item.talla }}</span>
                </p>

                <div class="flex items-end justify-between mt-auto pt-4">
                  <div class="relative">
                    <select aria-label="Cantidad"
                      :value="item.cantidad"
                      @change="changeQuantity(item.id, $event.target.value)"
                      class="appearance-none w-[4.5rem] bg-[#1f1f1f] border border-gray-700 rounded-lg pl-4 pr-8 py-2.5 text-sm font-semibold text-white focus:outline-none focus:border-[#ff8c42] hover:border-gray-500 transition-colors cursor-pointer"
                    >
                      <option v-for="n in Math.max(1, item.stock || 1)" :key="n" :value="n">{{ n }}</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-gray-500 pointer-events-none"></i>
                  </div>

                  <div class="flex items-center gap-3">
                    <button
                      v-if="item.colorId"
                      @click="moverAFavoritos(item)"
                      :title="movidoAFavoritos === item.id ? 'Movido a favoritos' : 'Mover a favoritos'"
                      class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 hover:text-[#ff8c42] hover:bg-white/5 transition-colors"
                    >
                      <i :class="movidoAFavoritos === item.id ? 'fa-solid fa-heart text-[#ff8c42]' : 'fa-regular fa-heart'"></i>
                    </button>
                    <p class="font-mono font-black text-lg tabular-nums">S/ {{ Number(item.subtotal || item.price * item.cantidad).toFixed(2) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Barra lateral derecha -->
        <div class="lg:col-span-1">
          <div class="sticky top-28 bg-[#0a0a0a] p-8 border border-gray-800 rounded-2xl shadow-2xl shadow-black/40">
            <div class="flex items-center gap-2.5 mb-6">
              <i class="fa-solid fa-bag-shopping text-[#ff8c42]"></i>
              <h2 class="text-xl font-black uppercase italic">Resumen del pedido</h2>
            </div>

            <div class="space-y-4 mb-8">
              <div class="flex justify-between text-sm text-gray-400">
                <span>{{ cartItems.length }} {{ cartItems.length === 1 ? 'producto' : 'productos' }}</span>
                <span class="font-mono tabular-nums">S/ {{ subtotal.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm text-gray-400">
                <span>Entrega</span>
                <span class="text-green-400 font-semibold">Gratis</span>
              </div>
              <div class="h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent"></div>
              <div class="flex justify-between items-baseline text-2xl font-black uppercase">
                <span>Total</span>
                <span class="font-mono tabular-nums">S/ {{ subtotal.toFixed(2) }}</span>
              </div>
              <p class="text-xs text-gray-500">(IGV incluido S/ {{ igvIncluido.toFixed(2) }})</p>
            </div>

            <Link :href="route('shop.registrodatos')" :class="{ 'pointer-events-none': isCartEmpty }">
              <button :disabled="isCartEmpty" :class="[
                'w-full py-4 flex items-center justify-center gap-3 font-black uppercase tracking-widest transition-all duration-200 rounded-lg',
                isCartEmpty
                  ? 'bg-gray-800 text-gray-500 cursor-not-allowed'
                  : 'bg-[#ff8c42] text-black hover:bg-[#ffb380] shadow-lg shadow-[#ff8c42]/20 hover:shadow-[#ff8c42]/30 hover:scale-[1.02] active:scale-[0.98]'
              ]">
                Ir a pagar
                <i class="fa-solid fa-arrow-right"></i>
              </button>
            </Link>

            <div class="mt-8 pt-6 border-t border-gray-800">
              <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-3">Opciones de pago</p>
              <div class="flex items-center gap-2">
                <div class="w-11 h-8 rounded-md bg-white/5 border border-gray-700 flex items-center justify-center text-gray-300">
                  <i class="fa-brands fa-cc-visa text-base"></i>
                </div>
                <div class="w-11 h-8 rounded-md bg-white/5 border border-gray-700 flex items-center justify-center text-gray-300">
                  <i class="fa-brands fa-cc-mastercard text-base"></i>
                </div>
                <div class="w-11 h-8 rounded-md bg-white/5 border border-gray-700 flex items-center justify-center text-gray-300">
                  <i class="fa-brands fa-cc-amex text-base"></i>
                </div>
                <div class="w-11 h-8 rounded-md bg-white/5 border border-gray-700 flex items-center justify-center text-gray-300">
                  <i class="fa-brands fa-cc-diners-club text-base"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </ShopLayout>
</template>
