<template>
  <aside
    class="fixed inset-y-0 left-0 z-50 w-[280px] flex flex-col bg-[#0b0b0c] border-r border-white/[0.06] transition-[width,transform] duration-200 ease-out motion-reduce:transition-none lg:relative lg:translate-x-0"
    :class="[rail ? 'lg:w-[72px]' : 'lg:w-[264px]', mobileOpen ? 'translate-x-0' : '-translate-x-full']"
    aria-label="Menú del panel"
  >
    <!-- Marca (el botón para contraer el aside vive en AdminTopbar) -->
    <div class="h-[72px] flex items-center gap-3 border-b border-white/[0.06]" :class="rail ? 'justify-center px-0' : 'pl-7 pr-5'">
      <img
        src="/image/logo/street-urban-icono-dark.png"
        :alt="`Logo de ${brand.name}`"
        class="w-14 h-12 flex-shrink-0 object-fill"
      />
      <div v-if="!rail" class="min-w-0 flex-1 leading-tight">
        <p class="text-[15px] font-bold tracking-tight text-white truncate">{{ brand.name }}</p>
        <p class="text-[11px] font-medium text-white/50 truncate">Panel de gestión</p>
      </div>
      <button
        v-if="!rail"
        type="button"
        class="lg:hidden w-10 h-10 -mr-2 flex items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/[0.06] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
        aria-label="Cerrar menú"
        @click="emit('close-mobile')"
      >
        <i class="fa-solid fa-xmark text-base" aria-hidden="true"></i>
      </button>
    </div>

    <!-- Buscador rápido + acción principal -->
    <div class="pt-4 pb-2 space-y-2" :class="rail ? 'px-3' : 'px-4'">
      <button
        type="button"
        class="w-full h-10 flex items-center gap-2.5 rounded-lg bg-white/[0.04] border border-white/[0.07] text-white/50 text-[13px] transition-colors duration-150 hover:bg-white/[0.07] hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
        :class="rail ? 'justify-center' : 'px-3'"
        aria-label="Buscar en el panel (Ctrl + K)"
        @click="emit('open-search')"
        @mouseenter="showTip($event, 'Buscar  ·  Ctrl K')"
        @mouseleave="hideTip"
        @focus="showTip($event, 'Buscar  ·  Ctrl K')"
        @blur="hideTip"
      >
        <i class="fa-solid fa-magnifying-glass text-[12px]" aria-hidden="true"></i>
        <template v-if="!rail">
          <span class="flex-1 text-left">Buscar…</span>
          <kbd class="hidden lg:inline-flex items-center gap-0.5 px-1.5 h-5 rounded border border-white/10 bg-white/[0.04] font-sans text-[10px] font-semibold text-white/50">Ctrl K</kbd>
        </template>
      </button>

      <Link
        :href="route('admin.sales.create')"
        class="w-full h-10 flex items-center justify-center gap-2 rounded-lg bg-[#ff8c42] text-[#1a0d03] text-[13px] font-bold shadow-[0_6px_20px_-8px_rgba(255,140,66,0.7)] transition-colors duration-150 hover:bg-[#ff9a57] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b0b0c]"
        aria-label="Nueva venta"
        @mouseenter="showTip($event, 'Nueva venta')"
        @mouseleave="hideTip"
        @focus="showTip($event, 'Nueva venta')"
        @blur="hideTip"
      >
        <i class="fa-solid fa-plus text-[12px]" aria-hidden="true"></i>
        <span v-if="!rail">Nueva venta</span>
      </Link>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 overflow-y-auto overflow-x-hidden sidebar-scroll py-3 px-3" aria-label="Secciones">
      <div v-for="(group, gi) in nav" :key="group.label ?? 'principal'" :class="gi > 0 ? 'mt-5' : ''">
        <p v-if="group.label && !rail" class="px-3 mb-1.5 text-[11px] font-semibold text-white/50 uppercase tracking-[0.12em]">
          {{ group.label }}
        </p>
        <div v-else-if="group.label && rail" class="mx-3 mb-3 h-px bg-white/[0.07]" aria-hidden="true"></div>

        <ul class="space-y-0.5">
          <li v-for="item in group.items" :key="item.name">
            <!-- Ítem simple, o ítem con hijos en modo compacto (lleva a su primera sección) -->
            <Link
              v-if="!item.children || rail"
              :href="item.href ?? item.children[0].href"
              :aria-current="isRouteActive(item.active) ? 'page' : undefined"
              :aria-label="rail ? item.name : undefined"
              class="nav-item"
              :class="[rail ? 'justify-center' : 'px-3', isRouteActive(item.active) ? 'nav-item--active' : 'nav-item--idle']"
              @mouseenter="showTip($event, item.name)"
              @mouseleave="hideTip"
              @focus="showTip($event, item.name)"
              @blur="hideTip"
            >
              <span v-if="isRouteActive(item.active)" class="nav-indicator" aria-hidden="true"></span>
              <i :class="['fa-solid', item.icon, 'nav-icon']" aria-hidden="true"></i>
              <span v-if="!rail" class="truncate">{{ item.name }}</span>
            </Link>

            <!-- Ítem desplegable -->
            <template v-else>
              <button
                type="button"
                class="nav-item w-full px-3"
                :class="isRouteActive(item.active) ? 'text-white font-semibold' : 'nav-item--idle'"
                :aria-expanded="openGroups.has(item.name)"
                :aria-controls="`submenu-${slug(item.name)}`"
                @click="toggleGroup(item.name)"
              >
                <i :class="['fa-solid', item.icon, 'nav-icon', isRouteActive(item.active) ? 'text-[#ff8c42]' : '']" aria-hidden="true"></i>
                <span class="flex-1 text-left truncate">{{ item.name }}</span>
                <i
                  class="fa-solid fa-chevron-down text-[10px] text-white/40 transition-transform duration-200 motion-reduce:transition-none"
                  :class="openGroups.has(item.name) ? 'rotate-180' : ''"
                  aria-hidden="true"
                ></i>
              </button>

              <div
                :id="`submenu-${slug(item.name)}`"
                class="grid transition-[grid-template-rows] duration-200 ease-out motion-reduce:transition-none"
                :class="openGroups.has(item.name) ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
              >
                <ul class="overflow-hidden" :inert="!openGroups.has(item.name) || null">
                  <li v-for="child in item.children" :key="child.name" class="relative ml-[21px] pl-4 border-l border-white/[0.08] first:mt-0.5">
                    <span
                      v-if="isRouteActive(child.active)"
                      class="absolute -left-px top-1.5 bottom-1.5 w-[2px] rounded-full bg-[#ff8c42]"
                      aria-hidden="true"
                    ></span>
                    <Link
                      :href="child.href"
                      :aria-current="isRouteActive(child.active) ? 'page' : undefined"
                      class="flex items-center h-9 px-2.5 rounded-md text-[13px] transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
                      :class="isRouteActive(child.active)
                        ? 'text-white font-semibold bg-white/[0.06]'
                        : 'text-white/60 hover:text-white hover:bg-white/[0.04]'"
                    >
                      {{ child.name }}
                    </Link>
                  </li>
                </ul>
              </div>
            </template>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Usuario -->
    <div ref="userMenuRoot" class="relative p-3 border-t border-white/[0.06]">
      <Transition
        enter-active-class="transition duration-150 ease-out motion-reduce:transition-none"
        enter-from-class="opacity-0 translate-y-1"
        leave-active-class="transition duration-100 ease-in motion-reduce:transition-none"
        leave-to-class="opacity-0 translate-y-1"
      >
        <div
          v-if="userMenuOpen"
          id="user-menu"
          role="menu"
          class="absolute z-20 w-56 rounded-xl bg-[#18181b] border border-white/10 p-1.5 shadow-2xl shadow-black/60"
          :class="rail ? 'left-full bottom-3 ml-3' : 'left-3 right-3 w-auto bottom-full mb-2'"
        >
          <div class="px-3 py-2 mb-1 border-b border-white/[0.06]">
            <p class="text-sm font-semibold text-white truncate">{{ user.name }}</p>
            <p class="text-xs text-white/50 truncate">{{ user.email }}</p>
          </div>
          <Link :href="route('profile.edit')" role="menuitem" class="menu-item">
            <i class="fa-solid fa-circle-user w-4 text-center text-white/50" aria-hidden="true"></i> Mi perfil
          </Link>
          <a :href="route('shop.home')" target="_blank" rel="noopener" role="menuitem" class="menu-item">
            <i class="fa-solid fa-arrow-up-right-from-square w-4 text-center text-white/50" aria-hidden="true"></i> Ver tienda
          </a>
          <div class="my-1 h-px bg-white/[0.06]" aria-hidden="true"></div>
          <Link :href="route('logout')" method="post" as="button" role="menuitem" class="menu-item w-full text-red-400 hover:!text-red-300 hover:!bg-red-500/10">
            <i class="fa-solid fa-right-from-bracket w-4 text-center" aria-hidden="true"></i> Cerrar sesión
          </Link>
        </div>
      </Transition>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-xl p-1.5 text-left transition-colors duration-150 hover:bg-white/[0.05] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
        :class="[rail ? 'justify-center' : 'pr-2.5', userMenuOpen ? 'bg-white/[0.05]' : '']"
        :aria-expanded="userMenuOpen"
        aria-haspopup="menu"
        aria-controls="user-menu"
        :aria-label="`Menú de ${user.name}`"
        @click="userMenuOpen = !userMenuOpen"
      >
        <div class="relative w-9 h-9 flex-shrink-0">
          <div class="w-9 h-9 rounded-full bg-[#ff8c42] flex items-center justify-center text-white font-bold text-sm overflow-hidden ring-2 ring-white/10">
            <img v-if="user.avatarUrl" :src="user.avatarUrl" :alt="user.name" class="w-full h-full object-cover" />
            <template v-else>{{ user.initial }}</template>
          </div>
          <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-[#0b0b0c]" aria-hidden="true"></span>
        </div>
        <template v-if="!rail">
          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-white truncate">{{ user.name }}</p>
            <p class="text-[11px] text-white/50 truncate">{{ user.roleLabel }}</p>
          </div>
          <i class="fa-solid fa-ellipsis text-white/40 text-sm" aria-hidden="true"></i>
        </template>
      </button>
    </div>

    <!-- Tooltip del modo compacto: fijo para que el scroll del nav no lo recorte -->
    <Teleport to="body">
      <div
        v-if="tip.visible"
        class="fixed z-[60] pointer-events-none -translate-y-1/2 px-2.5 py-1.5 rounded-md bg-[#18181b] border border-white/10 text-xs font-semibold text-white shadow-xl shadow-black/50 whitespace-nowrap"
        :style="{ top: `${tip.top}px`, left: `${tip.left}px` }"
        role="tooltip"
      >
        {{ tip.text }}
      </div>
    </Teleport>
  </aside>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { isRouteActive } from './adminNav';

const props = defineProps({
  nav: { type: Array, required: true },
  user: { type: Object, required: true },
  brand: { type: Object, required: true },
  collapsed: Boolean,
  mobileOpen: Boolean,
});

const emit = defineEmits(['close-mobile', 'open-search']);

// En móvil el aside es un panel completo: el modo compacto solo aplica en escritorio.
const desktopQuery = typeof window !== 'undefined' ? window.matchMedia('(min-width: 1024px)') : null;
const isDesktop = ref(desktopQuery?.matches ?? true);
const onMediaChange = (e) => { isDesktop.value = e.matches; };
const rail = computed(() => props.collapsed && isDesktop.value);

// Los desplegables arrancan abiertos si contienen la página actual.
const openGroups = reactive(new Set(
  props.nav.flatMap((g) => g.items).filter((i) => i.children && isRouteActive(i.active)).map((i) => i.name),
));
const toggleGroup = (name) => (openGroups.has(name) ? openGroups.delete(name) : openGroups.add(name));
const slug = (text) => text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, '-');

const tip = reactive({ visible: false, text: '', top: 0, left: 0 });
const showTip = (event, text) => {
  if (!rail.value) return;
  const rect = event.currentTarget.getBoundingClientRect();
  Object.assign(tip, { visible: true, text, top: rect.top + rect.height / 2, left: rect.right + 12 });
};
const hideTip = () => { tip.visible = false; };
watch(rail, hideTip);

const userMenuOpen = ref(false);
const userMenuRoot = ref(null);
const onDocumentPointer = (e) => {
  if (userMenuOpen.value && userMenuRoot.value && !userMenuRoot.value.contains(e.target)) userMenuOpen.value = false;
};
const onKeydown = (e) => {
  if (e.key === 'Escape') userMenuOpen.value = false;
};

onMounted(() => {
  desktopQuery?.addEventListener('change', onMediaChange);
  document.addEventListener('mousedown', onDocumentPointer);
  document.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
  desktopQuery?.removeEventListener('change', onMediaChange);
  document.removeEventListener('mousedown', onDocumentPointer);
  document.removeEventListener('keydown', onKeydown);
});
</script>

<style scoped>
.nav-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  height: 2.5rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: color 150ms ease, background-color 150ms ease;
}

.nav-item:focus { outline: none; }
.nav-item:focus-visible { box-shadow: 0 0 0 2px #ff8c42; }

.nav-item--idle {
  color: rgb(255 255 255 / 0.65);
  font-weight: 500;
}

.nav-item--idle:hover {
  color: #fff;
  background-color: rgb(255 255 255 / 0.04);
}

.nav-item--active {
  color: #fff;
  font-weight: 600;
  background: linear-gradient(90deg, rgb(255 140 66 / 0.14), rgb(255 140 66 / 0.03));
}

.nav-item--active .nav-icon { color: #ff8c42; }

.nav-icon {
  width: 1.25rem;
  flex-shrink: 0;
  text-align: center;
  font-size: 14px;
}

.nav-indicator {
  position: absolute;
  left: -0.75rem;
  top: 0.5rem;
  bottom: 0.5rem;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background: #ff8c42;
  box-shadow: 0 0 12px rgb(255 140 66 / 0.6);
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  height: 2.5rem;
  padding: 0 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: rgb(255 255 255 / 0.8);
  transition: color 150ms ease, background-color 150ms ease;
}

.menu-item:hover { color: #fff; background-color: rgb(255 255 255 / 0.06); }
.menu-item:focus { outline: none; }
.menu-item:focus-visible { box-shadow: 0 0 0 2px #ff8c42; }

.sidebar-scroll::-webkit-scrollbar { width: 6px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgb(255 255 255 / 0.08); border-radius: 3px; }
.sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgb(255 255 255 / 0.16); }

@media (prefers-reduced-motion: reduce) {
  .nav-item, .menu-item { transition: none; }
}
</style>
