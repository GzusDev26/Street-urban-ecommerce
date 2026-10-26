<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AuthModal from './AuthModal.vue';
import { totalItems, hydrateCustomerCart } from '@/cart';
import { totalItems as totalWishlist } from '@/wishlist';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useTheme } from '@/theme';

const page = usePage();

const scrolled = ref(false);
const mobileMenuOpen = ref(false);
const authModalOpen = ref(false);

const { isDark, toggleTheme } = useTheme();

// Arriba del todo en el Home el navbar va sobre la foto del hero: ahí siempre se pinta en oscuro.
const overHero = computed(() => !scrolled.value && route().current('shop.home'));
const logoSrc = computed(() => (isDark.value || overHero.value
  ? '/image/logo/street-urban-logo-dark.png'
  : '/image/logo/street-urban-logo-light.png'));

const handleScroll = () => {
  scrolled.value = window.scrollY > 50;
};

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
};
const cerrarMenu = () => { mobileMenuOpen.value = false; };

// Con el menú a pantalla completa abierto, la página de atrás no debe moverse.
watch(mobileMenuOpen, (abierto) => {
  document.body.style.overflow = abierto ? 'hidden' : '';
});
const alTeclear = (e) => { if (e.key === 'Escape') cerrarMenu(); };

const negocio = computed(() => page.props.business ?? {});
const enlaceWhatsapp = computed(() => (negocio.value.whatsapp ? `https://wa.me/${negocio.value.whatsapp}` : null));
const iniciarSesionDesdeMenu = () => {
  cerrarMenu();
  authModalOpen.value = true;
};
const irAListaDeDeseos = () => {
  cerrarMenu();
  goToWishlist();
};
const cerrarSesionDesdeMenu = () => {
  cerrarMenu();
  handleLogout();
};
const esActivo = (nombreRuta) => route().current(nombreRuta);
const PAGINAS = [
  { nombre: 'Inicio', ruta: 'shop.home' },
  { nombre: 'Tienda', ruta: 'shop.tienda' },
  { nombre: 'Nosotros', ruta: 'shop.nosotros' },
  { nombre: 'Contacto', ruta: 'shop.contacto' },
];

const resetScroll = () => {
  scrolled.value = false;
  window.scrollTo(0, 0);
};

const items = ref(0);
const wishlistItems = ref(0);
const userMenuOpen = ref(false);

const isLoggedIn = computed(() => !!page.props.auth?.user);
const isCustomer = computed(() => page.props.auth?.type === 'customer');

window.__streetUrbanCustomerId = isCustomer.value ? page.props.auth.user.id : null;

const goToWishlist = () => {
  if (isLoggedIn.value) {
    router.visit(route('shop.wishlist'));
  } else {
    authModalOpen.value = true;
  }
};

const refreshWishlistCount = async () => {
  wishlistItems.value = isLoggedIn.value ? await totalWishlist() : 0;
};

onMounted(() => {
  const customerId = isCustomer.value ? page.props.auth.user.id : null;
  if (customerId) {
    hydrateCustomerCart(customerId).then(() => {
      items.value = totalItems();
    });
  }

  if (!customerId) items.value = totalItems();
  refreshWishlistCount();
  window.addEventListener('scroll', handleScroll);
  window.addEventListener('keydown', alTeclear);
  window.addEventListener('cart-updated', () => {
    items.value = totalItems();
  });
  window.addEventListener('wishlist-updated', refreshWishlistCount);
  window.addEventListener('open-auth-modal', () => {
    authModalOpen.value = true;
  });

  // Abrir modal automáticamente solo la primera vez (usar sessionStorage)
  if (!page.props.auth?.user) {
    const modalShownThisSession = sessionStorage.getItem('authModalShown');
    if (!modalShownThisSession) {
      authModalOpen.value = true;
      sessionStorage.setItem('authModalShown', 'true');
    }
  } else {
    // Si está logueado, limpiar el flag
    sessionStorage.removeItem('authModalShown');
  }
});

const handleLogout = () => {
  localStorage.removeItem('shopping_cart');
  localStorage.removeItem('shopping_cart_customer_id');

  router.post('/auth/logout', {}, {
    onSuccess: () => {
      window.location.href = '/';
    }
  });
};

// Cerrar menu cuando hace click afuera
const closeUserMenu = () => {
  userMenuOpen.value = false;
};

// /profile solo existe para el staff (admin/vendedor); el cliente aún no tiene página de perfil,
// así que a cada uno se le ofrecen solo destinos que existen.
const ICON_USER = 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z';
const ICON_PANEL = 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z';
const ICON_HEART = 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z';
const ICON_CART = 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z';

const userMenuItems = computed(() => (isCustomer.value
  ? [
      { label: 'Lista de deseos', hint: 'Productos que guardaste', href: route('shop.wishlist'), icon: ICON_HEART },
      { label: 'Mi carrito', hint: 'Revisa tu compra', href: route('shop.carrito'), icon: ICON_CART },
    ]
  : [
      { label: 'Mi Perfil', hint: 'Administra tu información', href: route('profile.edit'), icon: ICON_USER },
      { label: 'Panel de gestión', hint: 'Ventas, compras e inventario', href: route('admin.dashboard'), icon: ICON_PANEL },
    ]));

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('keydown', alTeclear);
  document.body.style.overflow = '';
});

watch(() => page.url, () => {
  resetScroll();
  cerrarMenu();
  // Cerrar el modal al navegar
  authModalOpen.value = false;
});
</script>

<template>
  <nav
    class="fixed top-0 left-0 w-full z-50 transition-all duration-500 flex justify-between items-center"
    :class="[
      scrolled ? 'py-4 pr-5 pl-6 md:pl-12 bg-canvas/95 backdrop-blur-md shadow-lg shadow-black/20' : 'py-6 pr-7 pl-6 md:pl-12 bg-transparent',
      { 'theme-shop-dark': overHero },
    ]"
  >

    <Link :href="route('shop.home')" class="shrink-0 block" aria-label="Street Urban - Inicio">
      <img
        :src="logoSrc"
        alt="Street Urban"
        width="1608"
        height="874"
        class="w-auto object-contain transition-[height] duration-500"
        :class="scrolled ? 'h-14 md:h-16' : 'h-16 md:h-[4.5rem]'"
      />
    </Link>

    <!-- Desktop Menu -->
    <div class="hidden lg:flex space-x-12 text-xs font-semibold tracking-widest uppercase text-gray-300">
      <Link :href="route('shop.home')" class="hover:text-fg transition-colors relative group">
        Inicio
        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-fg group-hover:w-full transition-all duration-300"></span>
      </Link>
      <Link :href="route('shop.tienda')" class="hover:text-fg transition-colors relative group">
        Tienda
        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-fg group-hover:w-full transition-all duration-300"></span>
      </Link>
      <a href="#" class="hover:text-fg transition-colors relative group">
        Ofertas
        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-fg group-hover:w-full transition-all duration-300"></span>
      </a>
      <Link :href="route('shop.nosotros')" class="hover:text-fg transition-colors relative group">
        Nosotros
        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-fg group-hover:w-full transition-all duration-300"></span>
      </Link>
      <Link :href="route('shop.contacto')" class="hover:text-fg transition-colors relative group">
        Contacto
        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-fg group-hover:w-full transition-all duration-300"></span>
      </Link>
    </div>

    <!-- Right Section -->
    <div class="flex items-center gap-0.5 sm:gap-2 lg:gap-4 text-sm text-fg">
      <ThemeToggle class="max-lg:hidden p-2 hover:bg-fg/10 rounded-full transition-all" />

      <!-- Search Button -->
      <button class="max-lg:hidden p-2 hover:bg-fg/10 rounded-full transition-all group">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 group-hover:scale-110 transition-transform">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
      </button>

      <!-- Wishlist Button -->
      <button type="button" @click="goToWishlist" class="max-lg:hidden flex items-center p-2 hover:bg-fg/10 rounded-full transition-all group relative">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :fill="wishlistItems > 0 ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" class="w-5 h-5 group-hover:scale-110 transition-transform">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
        </svg>
        <span v-if="wishlistItems > 0" class="absolute -top-2 -right-2 bg-fg text-canvas text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">{{ wishlistItems }}</span>
      </button>

      <!-- Cart Button -->
      <Link :href="route('shop.carrito')" class="flex items-center justify-center w-11 h-11 lg:w-auto lg:h-auto lg:p-2 hover:bg-fg/10 rounded-full transition-all group relative" :aria-label="`Carrito, ${items} ${items === 1 ? 'prenda' : 'prendas'}`">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5 group-hover:scale-110 transition-transform">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="absolute top-0 right-0 lg:-top-2 lg:-right-2 bg-[#ff8c42] lg:bg-fg text-black lg:text-canvas text-[11px] lg:text-xs font-bold min-w-[1.25rem] h-5 px-1 rounded-full flex items-center justify-center">{{ items }}</span>
      </Link>

      <!-- User Profile / Login Button -->
      <button
        v-if="!page.props.auth?.user"
        @click="authModalOpen = true"
        class="max-lg:hidden flex items-center justify-center p-2 hover:bg-fg/10 rounded-full transition-all group relative"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5 group-hover:scale-110 transition-transform">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <!-- Badge flotante -->
        <span class="absolute top-0 right-0 w-5 h-5 bg-fg text-canvas text-xs font-bold rounded-full flex items-center justify-center animate-bounce">
          1
        </span>
      </button>

      <!-- User Profile Dropdown cuando está autenticado -->
      <div v-else class="relative max-lg:hidden">
        <button
          @click="userMenuOpen = !userMenuOpen"
          class="hidden md:flex items-center gap-2 px-4 py-2 hover:bg-fg/10 rounded-full transition-all group text-sm font-semibold text-fg"
        >
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5 group-hover:scale-110 transition-transform text-[#ff8c42]">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <span class="truncate max-w-[100px]">{{ page.props.auth.user.name }}</span>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" :class="['w-4 h-4 transition-transform', userMenuOpen ? 'rotate-180' : '']">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
          </svg>
        </button>

        <!-- Mobile Avatar Button -->
        <button
          @click="userMenuOpen = !userMenuOpen"
          class="md:hidden flex items-center justify-center p-2 hover:bg-fg/10 rounded-full transition-all group"
        >
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#ff8c42] to-[#e67e2d] flex items-center justify-center flex-shrink-0">
            <span class="text-xs font-bold text-black">{{ page.props.auth.user.name.charAt(0).toUpperCase() }}</span>
          </div>
        </button>

        <!-- Dropdown Menu -->
        <transition name="fade">
          <div v-if="userMenuOpen" class="absolute right-0 mt-3 w-64 md:w-72 bg-sunken border border-gray-800 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-sm">
            <!-- Header con Avatar -->
            <div class="px-6 py-6 bg-gradient-to-br from-gray-900 to-sunken border-b border-gray-800/50">
              <div class="flex items-center gap-4">
                <!-- Avatar -->
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#ff8c42] to-[#e67e2d] flex items-center justify-center flex-shrink-0">
                  <span class="text-xl font-bold text-black">{{ page.props.auth.user.name.charAt(0).toUpperCase() }}</span>
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-fg truncate">{{ page.props.auth.user.name }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ page.props.auth.user.email }}</p>
                  <div class="flex items-center gap-2 mt-2">
                    <div class="w-2 h-2 rounded-full bg-[#ff8c42]"></div>
                    <p class="text-xs text-gray-400">Activo</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Menu Items -->
            <div class="py-3 space-y-1 px-2">
              <Link
                v-for="item in userMenuItems"
                :key="item.label"
                :href="item.href"
                class="w-full px-4 py-3 text-left text-sm text-gray-300 hover:bg-fg/10 rounded-lg transition-all duration-200 flex items-center gap-3 group"
                @click="closeUserMenu"
              >
                <div class="w-9 h-9 rounded-lg bg-fg/5 group-hover:bg-fg/10 transition-colors flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400 group-hover:text-fg transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="font-medium text-fg group-hover:text-fg">{{ item.label }}</p>
                  <p class="text-xs text-gray-500">{{ item.hint }}</p>
                </div>
              </Link>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-800/50"></div>

            <!-- Logout -->
            <div class="p-2">
              <button
                @click="handleLogout"
                class="w-full px-4 py-3 text-left text-sm text-red-400 hover:bg-red-500/10 rounded-lg transition-all duration-200 flex items-center gap-3 group"
              >
                <div class="w-9 h-9 rounded-lg bg-red-500/10 group-hover:bg-red-500/20 transition-colors flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="font-medium text-red-400 group-hover:text-red-300">Cerrar Sesión</p>
                </div>
              </button>
            </div>
          </div>
        </transition>
      </div>


      <!-- Menú del celular: botón de hamburguesa -->
      <button
        type="button"
        class="lg:hidden w-11 h-11 -mr-2 flex items-center justify-center rounded-full hover:bg-fg/10 transition-colors"
        :aria-expanded="mobileMenuOpen"
        aria-controls="menu-movil"
        aria-label="Abrir menú"
        @click="toggleMobileMenu"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7h16.5M3.75 12h16.5M3.75 17h10" />
        </svg>
      </button>
    </div>
  </nav>

  <!-- Menú del celular: panel a pantalla completa -->
  <transition name="menu">
    <div
      v-if="mobileMenuOpen"
      id="menu-movil"
      role="dialog"
      aria-modal="true"
      aria-label="Menú de navegación"
      class="lg:hidden fixed inset-0 z-[60] bg-canvas text-fg flex flex-col overflow-y-auto overscroll-contain pb-[env(safe-area-inset-bottom)]"
    >
      <div class="flex items-center justify-between pl-6 pr-4 py-4 flex-shrink-0">
        <Link :href="route('shop.home')" class="block" aria-label="Street Urban - Inicio" @click="cerrarMenu">
          <img :src="isDark ? '/image/logo/street-urban-logo-dark.png' : '/image/logo/street-urban-logo-light.png'" alt="Street Urban" width="1608" height="874" class="h-14 w-auto object-contain" />
        </Link>
        <button type="button" class="w-11 h-11 flex items-center justify-center rounded-full hover:bg-fg/10 transition-colors" aria-label="Cerrar menú" @click="cerrarMenu">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>

      <!-- Accesos rápidos -->
      <div class="px-6 grid grid-cols-3 gap-3 flex-shrink-0">
        <button type="button" class="relative flex flex-col items-center justify-center gap-1.5 h-20 rounded-2xl bg-fg/5 hover:bg-fg/10 transition-colors text-xs font-semibold" @click="irAListaDeDeseos">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :fill="wishlistItems > 0 ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" class="w-6 h-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="ICON_HEART" /></svg>
          Deseos
          <span v-if="wishlistItems > 0" class="absolute top-2 right-3 bg-[#ff8c42] text-black text-[11px] font-bold min-w-[1.25rem] h-5 px-1 rounded-full flex items-center justify-center">{{ wishlistItems }}</span>
        </button>
        <Link :href="route('shop.carrito')" class="relative flex flex-col items-center justify-center gap-1.5 h-20 rounded-2xl bg-fg/5 hover:bg-fg/10 transition-colors text-xs font-semibold" @click="cerrarMenu">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-6 h-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="ICON_CART" /></svg>
          Carrito
          <span v-if="items > 0" class="absolute top-2 right-3 bg-[#ff8c42] text-black text-[11px] font-bold min-w-[1.25rem] h-5 px-1 rounded-full flex items-center justify-center">{{ items }}</span>
        </Link>
        <button type="button" class="flex flex-col items-center justify-center gap-1.5 h-20 rounded-2xl bg-fg/5 hover:bg-fg/10 transition-colors text-xs font-semibold" @click="toggleTheme">
          <svg v-if="isDark" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4L6 18M18 6l1.4-1.4" /></svg>
          <svg v-else class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8 8 0 019.5 4a8 8 0 1010.5 10.5z" /></svg>
          {{ isDark ? 'Modo claro' : 'Modo oscuro' }}
        </button>
      </div>

      <!-- Cuenta -->
      <div class="px-6 mt-3 flex-shrink-0">
        <button v-if="!isLoggedIn" type="button" class="w-full h-14 rounded-2xl border border-fg/15 hover:border-fg/40 flex items-center justify-center gap-2.5 text-sm font-bold uppercase tracking-widest transition-colors" @click="iniciarSesionDesdeMenu">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          Iniciar sesión o registrarme
        </button>
        <div v-else class="rounded-2xl bg-fg/5 p-4">
          <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-full bg-gradient-to-br from-[#ff8c42] to-[#e67e2d] flex items-center justify-center text-base font-bold text-black flex-shrink-0">{{ page.props.auth.user.name.charAt(0).toUpperCase() }}</span>
            <div class="min-w-0">
              <p class="font-semibold truncate">{{ page.props.auth.user.name }}</p>
              <p class="text-xs text-gray-400 truncate">{{ page.props.auth.user.email }}</p>
            </div>
          </div>
          <div class="mt-3 grid grid-cols-1 gap-1">
            <Link v-for="item in userMenuItems" :key="item.label" :href="item.href" class="flex items-center gap-3 h-12 px-2 rounded-xl hover:bg-fg/10 text-sm font-medium transition-colors" @click="cerrarMenu">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" /></svg>
              {{ item.label }}
            </Link>
            <button type="button" class="flex items-center gap-3 h-12 px-2 rounded-xl hover:bg-red-500/10 text-sm font-medium text-red-400 transition-colors text-left" @click="cerrarSesionDesdeMenu">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
              Cerrar sesión
            </button>
          </div>
        </div>
      </div>

      <!-- Páginas -->
      <nav class="px-6 mt-4 flex-1" aria-label="Páginas de la tienda">
        <Link
          v-for="pag in PAGINAS"
          :key="pag.ruta"
          :href="route(pag.ruta)"
          :aria-current="esActivo(pag.ruta) ? 'page' : undefined"
          :class="['flex items-center justify-between min-h-[3.75rem] border-b border-fg/10 text-[1.7rem] leading-none font-black uppercase tracking-tight transition-colors', esActivo(pag.ruta) ? 'text-[#ff8c42]' : 'text-fg hover:text-[#ff8c42]']"
          @click="cerrarMenu"
        >
          {{ pag.nombre }}
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 opacity-50" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
        </Link>
      </nav>

      <!-- Acciones y contacto -->
      <div class="px-6 pt-6 pb-8 space-y-3 flex-shrink-0">
        <Link :href="route('shop.tienda')" class="flex h-14 items-center justify-center gap-2 rounded-xl bg-[#ff8c42] hover:bg-[#f27d30] text-black text-sm font-bold uppercase tracking-widest transition-colors" @click="cerrarMenu">
          Comprar ahora <span aria-hidden="true">&rarr;</span>
        </Link>
        <Link :href="route('shop.home') + '#categorias'" class="flex h-14 items-center justify-center rounded-xl border border-fg/20 hover:border-fg/50 text-sm font-bold uppercase tracking-widest transition-colors" @click="cerrarMenu">
          Ver categorías
        </Link>
        <a v-if="enlaceWhatsapp" :href="enlaceWhatsapp" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 h-12 text-sm text-gray-400 hover:text-fg transition-colors">
          <i class="fa-brands fa-whatsapp text-lg" aria-hidden="true"></i> Escríbenos por WhatsApp
        </a>
      </div>
    </div>
  </transition>

  <!-- Auth Modal -->
  <AuthModal :is-open="authModalOpen" @close="authModalOpen = false" />

  <!-- Click outside to close user menu -->
  <div v-if="userMenuOpen" @click="closeUserMenu" class="fixed inset-0 z-40"></div>
</template>

<style scoped>
.menu-enter-active, .menu-leave-active {
  transition: transform 0.28s var(--ease-drawer, cubic-bezier(0.32, 0.72, 0, 1)), opacity 0.2s ease;
}
.menu-enter-from, .menu-leave-to {
  opacity: 0;
  transform: translateX(8%);
}
@media (prefers-reduced-motion: reduce) {
  .menu-enter-active, .menu-leave-active { transition: none; }
}

.slide-enter-active, .slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-enter-active, .fade-leave-active {
  transition: all 0.2s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@keyframes float-bounce {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-8px);
  }
}

:deep(.animate-bounce) {
  animation: float-bounce 2s infinite !important;
}
</style>
