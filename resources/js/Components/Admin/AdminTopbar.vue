<template>
  <!-- 72px de alto, igual que la marca del aside: así sus bordes inferiores forman una sola línea.
       relative z-30: backdrop-blur crea su propio contexto de apilamiento, y sin esto el menú de usuario
       queda detrás de las tarjetas del contenido. -->
  <header class="relative z-30 h-[72px] flex-shrink-0 bg-white/90 backdrop-blur border-b border-gray-200/80">
    <div class="h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
      <!-- Izquierda: control del aside + ubicación -->
      <div class="flex items-center gap-3 sm:gap-4 min-w-0">
        <button
          type="button"
          class="icon-btn flex lg:hidden"
          aria-label="Abrir menú"
          @click="emit('open-mobile')"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h10" />
          </svg>
        </button>
        <button
          type="button"
          class="icon-btn hidden lg:flex"
          :aria-label="collapsed ? 'Expandir menú lateral' : 'Contraer menú lateral'"
          :aria-pressed="collapsed"
          :title="collapsed ? 'Expandir menú' : 'Contraer menú'"
          @click="emit('toggle-sidebar')"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="16" rx="3" />
            <path d="M9 4v16" />
            <path :d="collapsed ? 'M13.5 10l2 2-2 2' : 'M15.5 10l-2 2 2 2'" stroke-linecap="round" />
          </svg>
        </button>

        <div class="hidden sm:block w-px h-8 bg-gray-200" aria-hidden="true"></div>

        <div class="min-w-0">
          <nav aria-label="Ruta" class="flex items-center gap-1.5 text-xs font-medium text-gray-500">
            <Link :href="route('admin.dashboard')" class="hover:text-gray-900 transition-colors duration-150 focus:outline-none focus-visible:underline">
              Inicio
            </Link>
            <svg class="w-3 h-3 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
            <span class="truncate text-gray-500"><slot name="breadcrumb">Dashboard</slot></span>
          </nav>
          <h1 class="mt-0.5 text-lg sm:text-xl font-bold tracking-tight text-gray-900 truncate leading-tight">
            <slot name="title">Panel Administrativo</slot>
          </h1>
        </div>
      </div>

      <!-- Derecha: fecha, buscador, tienda, usuario -->
      <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
        <div class="hidden xl:flex items-center gap-2 h-10 px-3 rounded-lg text-[13px] font-medium text-gray-500">
          <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" /><path d="M3.5 10h17M8 3v4M16 3v4" />
          </svg>
          <span class="first-letter:uppercase">{{ today }}</span>
        </div>

        <button
          type="button"
          class="hidden md:flex items-center gap-2.5 h-10 w-56 lg:w-64 px-3 rounded-lg bg-gray-100/80 border border-transparent text-[13px] text-gray-500 transition-colors duration-150 hover:bg-gray-100 hover:border-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
          aria-label="Buscar en el panel (Ctrl + K)"
          @click="emit('open-search')"
        >
          <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" />
          </svg>
          <span class="flex-1 text-left">Buscar…</span>
          <kbd class="inline-flex items-center h-5 px-1.5 rounded border border-gray-200 bg-white font-sans text-[10px] font-semibold text-gray-500">Ctrl K</kbd>
        </button>
        <button type="button" class="icon-btn flex md:hidden" aria-label="Buscar en el panel" @click="emit('open-search')">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" />
          </svg>
        </button>

        <a
          :href="route('shop.home')"
          target="_blank"
          rel="noopener"
          class="icon-btn hidden sm:flex"
          aria-label="Ver tienda (se abre en otra pestaña)"
          title="Ver tienda"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 9.5l1.6-4.8A1 1 0 016.55 4h10.9a1 1 0 01.95.7L20 9.5" /><path d="M4 9.5h16v1a3 3 0 01-5.33 1.89A3 3 0 0112 13.5a3 3 0 01-2.67-1.11A3 3 0 014 10.5v-1z" /><path d="M5.5 13v6.5h13V13" /><path d="M10 19.5v-3.5h4v3.5" />
          </svg>
        </a>

        <div class="hidden sm:block w-px h-8 bg-gray-200 mx-1" aria-hidden="true"></div>

        <!-- Menú de usuario -->
        <div ref="menuRoot" class="relative">
          <button
            type="button"
            class="flex items-center gap-3 h-11 pl-1 pr-1 sm:pr-2.5 rounded-full sm:rounded-xl transition-colors duration-150 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
            :class="menuOpen ? 'bg-gray-100' : ''"
            aria-haspopup="menu"
            :aria-expanded="menuOpen"
            aria-controls="topbar-user-menu"
            :aria-label="`Menú de ${user.name}`"
            @click="menuOpen = !menuOpen"
          >
            <span class="w-9 h-9 rounded-full bg-[#ff8c42] flex items-center justify-center text-white font-bold text-sm overflow-hidden ring-2 ring-white shadow-sm">
              <img v-if="user.avatarUrl" :src="user.avatarUrl" :alt="user.name" class="w-full h-full object-cover" />
              <template v-else>{{ user.initial }}</template>
            </span>
            <span class="hidden sm:block text-left leading-tight">
              <span class="block text-sm font-semibold text-gray-900 max-w-[140px] truncate">{{ user.name }}</span>
              <span class="block text-[11px] font-medium text-gray-500">{{ user.roleLabel }}</span>
            </span>
            <svg
              class="hidden sm:block w-4 h-4 text-gray-400 transition-transform duration-200 motion-reduce:transition-none"
              :class="menuOpen ? 'rotate-180' : ''"
              viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"
            >
              <path d="M6 9l6 6 6-6" />
            </svg>
          </button>

          <Transition
            enter-active-class="transition duration-150 ease-out motion-reduce:transition-none"
            enter-from-class="opacity-0 -translate-y-1 scale-[0.98]"
            leave-active-class="transition duration-100 ease-in motion-reduce:transition-none"
            leave-to-class="opacity-0 -translate-y-1 scale-[0.98]"
          >
            <div
              v-if="menuOpen"
              id="topbar-user-menu"
              role="menu"
              class="absolute right-0 top-full mt-2 z-30 w-64 origin-top-right rounded-xl bg-white border border-gray-200 p-1.5 shadow-xl shadow-gray-900/10"
            >
              <div class="flex items-center gap-3 px-3 py-2.5 mb-1 border-b border-gray-100">
                <span class="w-10 h-10 rounded-full bg-[#ff8c42] flex items-center justify-center text-white font-bold overflow-hidden flex-shrink-0">
                  <img v-if="user.avatarUrl" :src="user.avatarUrl" :alt="user.name" class="w-full h-full object-cover" />
                  <template v-else>{{ user.initial }}</template>
                </span>
                <span class="min-w-0">
                  <span class="block text-sm font-semibold text-gray-900 truncate">{{ user.name }}</span>
                  <span class="block text-xs text-gray-500 truncate">{{ user.email }}</span>
                </span>
              </div>
              <Link :href="route('profile.edit')" role="menuitem" class="menu-item">
                <i class="fa-solid fa-circle-user w-4 text-center text-gray-400" aria-hidden="true"></i> Mi perfil
              </Link>
              <a :href="route('shop.home')" target="_blank" rel="noopener" role="menuitem" class="menu-item">
                <i class="fa-solid fa-arrow-up-right-from-square w-4 text-center text-gray-400" aria-hidden="true"></i> Ver tienda
              </a>
              <div class="my-1 h-px bg-gray-100" aria-hidden="true"></div>
              <Link :href="route('logout')" method="post" as="button" role="menuitem" class="menu-item w-full text-red-600 hover:!bg-red-50 hover:!text-red-700">
                <i class="fa-solid fa-right-from-bracket w-4 text-center" aria-hidden="true"></i> Cerrar sesión
              </Link>
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  user: { type: Object, required: true },
  collapsed: Boolean,
});

const emit = defineEmits(['toggle-sidebar', 'open-mobile', 'open-search']);

const today = new Intl.DateTimeFormat('es-PE', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());

const menuOpen = ref(false);
const menuRoot = ref(null);
const onDocumentPointer = (e) => {
  if (menuOpen.value && menuRoot.value && !menuRoot.value.contains(e.target)) menuOpen.value = false;
};
const onKeydown = (e) => {
  if (e.key === 'Escape') menuOpen.value = false;
};

onMounted(() => {
  document.addEventListener('mousedown', onDocumentPointer);
  document.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentPointer);
  document.removeEventListener('keydown', onKeydown);
});
</script>

<style scoped>
.icon-btn {
  width: 2.5rem;
  height: 2.5rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 0.625rem;
  color: rgb(75 85 99);
  transition: color 150ms ease, background-color 150ms ease;
}

.icon-btn:hover { color: rgb(17 24 39); background-color: rgb(243 244 246); }
.icon-btn:focus { outline: none; }
.icon-btn:focus-visible { box-shadow: 0 0 0 2px #ff8c42; }

.menu-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  height: 2.5rem;
  padding: 0 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: rgb(55 65 81);
  transition: color 150ms ease, background-color 150ms ease;
}

.menu-item:hover { color: rgb(17 24 39); background-color: rgb(249 250 251); }
.menu-item:focus { outline: none; }
.menu-item:focus-visible { box-shadow: 0 0 0 2px #ff8c42; }
</style>
