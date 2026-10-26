<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out motion-reduce:transition-none"
      enter-from-class="opacity-0"
      leave-active-class="transition duration-100 ease-in motion-reduce:transition-none"
      leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 z-[70] flex items-start justify-center px-4 pt-[12vh]" @mousedown.self="close">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" aria-hidden="true" @mousedown="close"></div>

        <div
          role="dialog"
          aria-modal="true"
          aria-label="Buscar en el panel"
          class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-[#141416] border border-white/10 shadow-2xl shadow-black/70"
        >
          <div class="flex items-center gap-3 px-4 border-b border-white/[0.07]">
            <i class="fa-solid fa-magnifying-glass text-white/40 text-sm" aria-hidden="true"></i>
            <input
              ref="input"
              v-model="query"
              type="text"
              role="combobox"
              aria-expanded="true"
              aria-controls="command-results"
              :aria-activedescendant="results[activeIndex] ? `command-${activeIndex}` : undefined"
              aria-label="Buscar sección o acción"
              placeholder="Ir a… (ventas, clientes, reportes)"
              class="flex-1 h-14 bg-transparent border-0 p-0 text-[15px] text-white placeholder-white/35 focus:ring-0 focus:outline-none"
              @keydown.down.prevent="move(1)"
              @keydown.up.prevent="move(-1)"
              @keydown.enter.prevent="run(results[activeIndex])"
              @keydown.esc.prevent="close"
            />
            <kbd class="px-1.5 h-5 inline-flex items-center rounded border border-white/10 text-[10px] font-semibold text-white/45">Esc</kbd>
          </div>

          <ul id="command-results" ref="list" role="listbox" class="max-h-[50vh] overflow-y-auto p-2">
            <template v-for="(entry, index) in results" :key="entry.key">
              <li
                v-if="index === 0 || results[index - 1].section !== entry.section"
                role="presentation"
                class="px-3 pt-3 pb-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-white/40"
              >
                {{ entry.section }}
              </li>
              <li
                :id="`command-${index}`"
                role="option"
                :aria-selected="index === activeIndex"
                class="flex items-center gap-3 h-11 px-3 rounded-lg cursor-pointer text-sm transition-colors duration-100"
                :class="index === activeIndex ? 'bg-[#ff8c42]/[0.12] text-white' : 'text-white/70'"
                @mousemove="activeIndex = index"
                @click="run(entry)"
              >
                <span
                  class="w-7 h-7 flex-shrink-0 rounded-md flex items-center justify-center text-[12px]"
                  :class="index === activeIndex ? 'bg-[#ff8c42] text-[#1a0d03]' : 'bg-white/[0.06] text-white/60'"
                >
                  <i :class="['fa-solid', entry.icon]" aria-hidden="true"></i>
                </span>
                <span class="flex-1 truncate">
                  <span v-if="entry.parent" class="text-white/45">{{ entry.parent }} › </span>{{ entry.name }}
                </span>
                <i v-if="index === activeIndex" class="fa-solid fa-arrow-turn-down rotate-90 text-[11px] text-white/40" aria-hidden="true"></i>
              </li>
            </template>

            <li v-if="!results.length" class="px-3 py-10 text-center text-sm text-white/45">
              No hay resultados para “{{ query }}”.
            </li>
          </ul>

          <div class="flex items-center gap-4 px-4 h-10 border-t border-white/[0.07] text-[11px] text-white/40">
            <span><kbd class="kbd">↑</kbd> <kbd class="kbd">↓</kbd> moverse</span>
            <span><kbd class="kbd">Enter</kbd> abrir</span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { bloquearScroll } from '@/useBloqueoScroll';
import { computed, nextTick, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  open: Boolean,
  nav: { type: Array, required: true },
});

const emit = defineEmits(['close']);

const query = ref('');
const activeIndex = ref(0);
const input = ref(null);
const list = ref(null);

const normalize = (text) => text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

// Las secciones salen del mismo menú del aside, así ambos nunca se desincronizan.
const entries = computed(() => {
  const pages = props.nav.flatMap((group) => group.items.flatMap((item) => (item.children
    ? item.children.map((child) => ({ key: child.href, section: 'Ir a', name: child.name, parent: item.name, icon: item.icon, href: child.href }))
    : [{ key: item.href, section: 'Ir a', name: item.name, icon: item.icon, href: item.href }])));

  const actions = [
    { key: 'action-sale', section: 'Acciones', name: 'Nueva venta', icon: 'fa-plus', href: route('admin.sales.create') },
    { key: 'action-profile', section: 'Acciones', name: 'Mi perfil', icon: 'fa-circle-user', href: route('profile.edit') },
    { key: 'action-shop', section: 'Acciones', name: 'Ver tienda', icon: 'fa-arrow-up-right-from-square', href: route('shop.home'), external: true },
    { key: 'action-logout', section: 'Acciones', name: 'Cerrar sesión', icon: 'fa-right-from-bracket', logout: true },
  ];

  return [...pages, ...actions];
});

const results = computed(() => {
  const q = normalize(query.value.trim());
  if (!q) return entries.value;
  return entries.value.filter((e) => normalize(`${e.parent ?? ''} ${e.name}`).includes(q));
});

watch(results, () => { activeIndex.value = 0; });

let liberarScroll = null;
watch(() => props.open, (isOpen) => {
  if (isOpen) liberarScroll = bloquearScroll();
  else { liberarScroll?.(); liberarScroll = null; }
});

watch(() => props.open, async (isOpen) => {
  if (!isOpen) return;
  query.value = '';
  activeIndex.value = 0;
  await nextTick();
  input.value?.focus();
});

const move = async (step) => {
  if (!results.value.length) return;
  activeIndex.value = (activeIndex.value + step + results.value.length) % results.value.length;
  await nextTick();
  list.value?.querySelector(`#command-${activeIndex.value}`)?.scrollIntoView({ block: 'nearest' });
};

const close = () => emit('close');

const run = (entry) => {
  if (!entry) return;
  close();
  if (entry.logout) router.post(route('logout'));
  else if (entry.external) window.open(entry.href, '_blank', 'noopener');
  else router.visit(entry.href);
};
</script>

<style scoped>
.kbd {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.25rem;
  border-radius: 0.25rem;
  border: 1px solid rgb(255 255 255 / 0.1);
  font-family: inherit;
  font-size: 10px;
  font-weight: 600;
  color: rgb(255 255 255 / 0.5);
}
</style>
