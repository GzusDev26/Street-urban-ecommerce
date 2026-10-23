<template>
  <div class="flex h-screen bg-[#f4f5f7] text-gray-900">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
      <!-- Logo Section -->
      <div class="p-6 border-b border-gray-100">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-[#ff8c42] rounded-lg flex items-center justify-center font-black text-white shadow-sm">
            S
          </div>
          <div>
            <h1 class="text-lg font-black tracking-tight leading-none">Street Urban</h1>
          </div>
        </div>
      </div>

      <!-- Storage (decorative) -->
      <div class="px-6 pt-5">
        <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
          <span>Almacenamiento</span>
          <span>45 de 100 GB</span>
        </div>
        <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full bg-[#ff8c42] rounded-full" style="width: 45%"></div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-4 py-6 space-y-5 overflow-y-auto">
        <div v-for="group in menuGroups" :key="group.label" class="space-y-1">
          <p class="px-3 text-[11px] font-bold text-gray-400 uppercase tracking-widest">
            {{ group.label }}
          </p>
          <component
            :is="item.href ? Link : 'div'"
            v-for="item in group.items"
            :key="item.name"
            :href="item.href"
            class="group flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 text-sm"
            :class="[
              item.href
                ? (route().current(item.active)
                    ? 'bg-[#ff8c42]/10 text-[#ff8c42] font-semibold'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium')
                : 'text-gray-300 cursor-not-allowed font-medium',
            ]"
          >
            <i :class="['fa-solid', item.icon, 'w-4 text-center text-[13px]']"></i>
            <span>{{ item.name }}</span>
            <span v-if="!item.href" class="ml-auto text-[10px] bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded-full">Pronto</span>
          </component>
        </div>
      </nav>

      <!-- Bottom Section: user + logout -->
      <div class="px-4 py-4 border-t border-gray-100">
        <div class="flex items-center gap-3 px-3 py-2 mb-1">
          <div class="w-9 h-9 rounded-full bg-[#ff8c42] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 overflow-hidden">
            <img v-if="userAvatarUrl" :src="userAvatarUrl" :alt="userName" class="w-full h-full object-cover" />
            <template v-else>{{ userInitial }}</template>
          </div>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 truncate">{{ userName }}</p>
            <p class="text-xs text-gray-400 truncate">{{ userRoleLabel }}</p>
          </div>
        </div>
        <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 text-red-500 hover:bg-red-50 text-sm font-medium"
        >
          <i class="fa-solid fa-right-from-bracket w-4 text-center text-[13px]"></i>
          <span>Cerrar Sesión</span>
        </Link>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 overflow-auto flex flex-col">
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200">
        <div class="px-8 py-4 flex items-center justify-between">
          <div>
            <p class="text-xs text-gray-400">
              Inicio <i class="fa-solid fa-chevron-right text-[9px] mx-1"></i> <slot name="breadcrumb">Dashboard</slot>
            </p>
            <h2 class="text-2xl font-black tracking-tight text-gray-900 mt-0.5">
              <slot name="header">Panel Administrativo</slot>
            </h2>
          </div>

          <div class="flex items-center gap-4">
            <div class="flex items-center bg-gray-100 rounded-lg p-1 text-xs font-semibold text-gray-500">
              <button class="px-3 py-1.5 rounded-md bg-white text-gray-900 shadow-sm">Hoy</button>
              <button class="px-3 py-1.5 rounded-md hover:text-gray-700">7D</button>
              <button class="px-3 py-1.5 rounded-md hover:text-gray-700">2S</button>
              <button class="px-3 py-1.5 rounded-md hover:text-gray-700">1M</button>
            </div>

            <div class="flex items-center gap-2">
              <div class="w-9 h-9 rounded-full bg-[#ff8c42] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 overflow-hidden">
                <img v-if="userAvatarUrl" :src="userAvatarUrl" :alt="userName" class="w-full h-full object-cover" />
                <template v-else>{{ userInitial }}</template>
              </div>
              <span class="text-sm font-semibold text-gray-800 hidden sm:block">{{ userName }}</span>
            </div>
          </div>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 p-8 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

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

const menuGroups = computed(() => [
  {
    label: 'General',
    items: [
      { name: 'Dashboard', href: route('admin.dashboard'), active: 'admin.dashboard', icon: 'fa-gauge' },
    ],
  },
  {
    label: 'Inventario',
    items: [
      { name: 'Compras', href: route('admin.purchases.index'), active: 'admin.purchases.*', icon: 'fa-cart-shopping' },
      { name: 'Órdenes', href: null, active: null, icon: 'fa-receipt' },
    ],
  },
  {
    label: 'Ventas',
    items: [
      { name: 'Ventas', href: route('admin.sales.create'), active: 'admin.sales.*', icon: 'fa-cash-register' },
    ],
  },
  {
    label: 'Relaciones',
    items: [
      { name: 'Clientes', href: route('admin.customers.index'), active: 'admin.customers.*', icon: 'fa-people-group' },
      { name: 'Proveedores', href: route('admin.suppliers.index'), active: 'admin.suppliers.*', icon: 'fa-truck-fast' },
    ],
  },
  ...(esAdmin.value ? [{
    label: 'Administración',
    items: [
      { name: 'Usuarios', href: route('admin.users.index'), active: 'admin.users.*', icon: 'fa-user-shield' },
      { name: 'Datos del Negocio', href: route('admin.settings.edit'), active: 'admin.settings.*', icon: 'fa-sliders' },
      { name: 'Categorías y Marcas', href: route('admin.taxonomies.index'), active: 'admin.taxonomies.*', icon: 'fa-tags' },
      { name: 'Avatares', href: route('admin.avatars.index'), active: 'admin.avatars.*', icon: 'fa-images' },
    ],
  }] : []),
  {
    label: 'Cuenta',
    items: [
      { name: 'Perfil', href: route('profile.edit'), active: 'profile.*', icon: 'fa-circle-user' },
    ],
  },
]);
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
  background: rgba(0, 0, 0, 0.1);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.2);
}
</style>
