<template>
  <!-- Celular: se desliza la página entera (así el navegador deja lo último por encima de su barra de abajo) y el menú de
       arriba va pegado. Escritorio (lg+): pantalla fija con el aside y solo el contenido se desliza. -->
  <div class="flex min-h-dvh lg:h-screen lg:overflow-hidden bg-canvas text-gray-900">
    <!-- Fondo oscuro del menú en móvil -->
    <Transition
      enter-active-class="transition-opacity duration-200 motion-reduce:transition-none"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150 motion-reduce:transition-none"
      leave-to-class="opacity-0"
    >
      <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" aria-hidden="true" @click="mobileOpen = false"></div>
    </Transition>

    <AdminSidebar
      :nav="nav"
      :user="sidebarUser"
      :brand="brand"
      :collapsed="collapsed"
      :mobile-open="mobileOpen"
      @close-mobile="mobileOpen = false"
      @open-search="openSearch"
    />

    <CommandPalette :open="searchOpen" :nav="nav" @close="searchOpen = false" />

    <!-- Main Content -->
    <div class="flex-1 min-w-0 flex flex-col lg:min-h-0 lg:overflow-hidden">
      <AdminTopbar
        :user="sidebarUser"
        :collapsed="collapsed"
        @toggle-sidebar="toggleCollapsed"
        @open-mobile="mobileOpen = true"
        @open-search="openSearch"
      >
        <template #breadcrumb><slot name="breadcrumb">Dashboard</slot></template>
        <template #title><slot name="header">Panel Administrativo</slot></template>
      </AdminTopbar>

      <!-- Content -->
      <!-- scroll-region: en escritorio, Inertia sube esta zona al inicio al cambiar de pantalla (en el celular lo hace con la página). -->
      <main class="flex-1 p-4 sm:p-8 pb-[max(2rem,env(safe-area-inset-bottom))] lg:min-h-0 lg:overflow-auto lg:overscroll-contain" scroll-region>
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue';
import AdminTopbar from '@/Components/Admin/AdminTopbar.vue';
import CommandPalette from '@/Components/Admin/CommandPalette.vue';
import { buildAdminNav } from '@/Components/Admin/adminNav';

const page = usePage();

const userName = computed(() => page.props.auth?.user?.name ?? 'Admin');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());
const userAvatarUrl = computed(() => page.props.auth?.user?.avatar_url ?? null);
const userRoleLabel = computed(() => {
  const role = page.props.auth?.user?.role;
  if (role === 'admin') return 'Administrador';
  if (role === 'vendedor') return 'Vendedor';
  return 'Staff';
});

const esAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const nav = computed(() => buildAdminNav(esAdmin.value));

const sidebarUser = computed(() => ({
  name: userName.value,
  email: page.props.auth?.user?.email ?? '',
  initial: userInitial.value,
  avatarUrl: userAvatarUrl.value,
  roleLabel: userRoleLabel.value,
}));

const brand = computed(() => {
  const name = page.props.business?.name || 'Street Urban';
  return { name, initial: name.charAt(0).toUpperCase() };
});

// El layout se vuelve a montar en cada visita, así que el modo compacto se lee al instante
// (sin parpadeo) desde localStorage; si el navegador lo bloquea, el aside queda expandido.
const STORAGE_KEY = 'admin.sidebar.collapsed';
const readCollapsed = () => {
  try { return localStorage.getItem(STORAGE_KEY) === '1'; } catch { return false; }
};
const collapsed = ref(readCollapsed());
const toggleCollapsed = () => {
  collapsed.value = !collapsed.value;
  try { localStorage.setItem(STORAGE_KEY, collapsed.value ? '1' : '0'); } catch { /* sin almacenamiento */ }
};

const mobileOpen = ref(false);

// Con el menú lateral abierto en el celular, la página de atrás no debe moverse.
watch(mobileOpen, (abierto) => {
  document.body.style.overflow = abierto ? 'hidden' : '';
});
const searchOpen = ref(false);
const openSearch = () => {
  mobileOpen.value = false;
  searchOpen.value = true;
};

const onKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    searchOpen.value ? (searchOpen.value = false) : openSearch();
  } else if (e.key === 'Escape' && mobileOpen.value) {
    mobileOpen.value = false;
  }
};

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown);
  document.body.style.overflow = '';
});
</script>

<style scoped>
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: rgb(var(--gray-300));
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgb(var(--gray-400));
}
</style>
