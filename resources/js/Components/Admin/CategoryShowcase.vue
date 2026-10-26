<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

// Vitrina del Home: qué categorías salen en la portada de la tienda y con qué foto.
// Las categorías nuevas entran sin destacar para no romper la composición de portada.
const props = defineProps({
  categorias: Array,
});

// Destacadas primero, en el mismo orden que el Home (por antigüedad).
const ordenadas = computed(() =>
  [...props.categorias].sort((a, b) => (Number(b.featured) - Number(a.featured)) || (a.id - b.id))
);
const destacadas = computed(() => props.categorias.filter((c) => c.featured).length);

const cambiandoVitrina = ref(null);
const alternarVitrina = (categoria) => {
  cambiandoVitrina.value = categoria.id;
  router.patch(route('admin.categories.featured', categoria.id), { featured: !categoria.featured }, {
    preserveScroll: true,
    onFinish: () => {
      cambiandoVitrina.value = null;
    },
  });
};

const subiendo = ref(null);
const errores = ref({});
const inputs = ref({});
const elegirFoto = (categoria) => inputs.value[categoria.id]?.click();

const subirFoto = (categoria, event) => {
  const archivo = event.target.files?.[0];
  event.target.value = ''; // permite volver a elegir el mismo archivo
  if (!archivo) return;

  subiendo.value = categoria.id;
  errores.value = { ...errores.value, [categoria.id]: null };

  router.post(route('admin.categories.image', categoria.id), { imagen: archivo }, {
    forceFormData: true,
    preserveScroll: true,
    onError: (e) => {
      errores.value = { ...errores.value, [categoria.id]: e.imagen || 'No se pudo subir la foto.' };
    },
    onFinish: () => {
      subiendo.value = null;
    },
  });
};
</script>

<template>
  <section class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm" aria-labelledby="vitrina-titulo">
    <header class="flex flex-wrap items-start justify-between gap-4 mb-5">
      <div class="flex items-start gap-3">
        <span class="w-10 h-10 rounded-xl bg-orange-50 text-[#ff8c42] flex items-center justify-center flex-shrink-0" aria-hidden="true">
          <i class="fa-solid fa-store"></i>
        </span>
        <div>
          <h3 id="vitrina-titulo" class="text-lg font-bold text-gray-900 leading-tight">Vitrina del Home</h3>
          <p class="text-sm text-gray-500 mt-0.5 max-w-xl">
            Las categorías que salen en la portada de la tienda. Las nuevas entran ocultas para que puedas elegir una foto que combine antes de mostrarlas.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500"><b class="text-gray-900">{{ destacadas }}</b> en el Home</span>
        <a :href="route('shop.home')" target="_blank" rel="noopener" class="h-10 px-4 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 flex items-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]">
          Ver Home <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i><span class="sr-only">(se abre en otra pestaña)</span>
        </a>
      </div>
    </header>

    <p v-if="destacadas === 0" class="mb-4 flex items-center gap-2 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-700">
      <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
      No hay categorías en el Home: la sección «Explora por categoría» se verá vacía.
    </p>

    <ul v-if="ordenadas.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
      <li v-for="cat in ordenadas" :key="cat.id" :class="['rounded-xl border overflow-hidden transition-colors', cat.featured ? 'border-[#ff8c42]/40' : 'border-gray-200']">
        <!-- Miniatura como en el Home -->
        <div class="theme-shop-dark relative aspect-[4/3] bg-[#0b0b0c] overflow-hidden">
          <img v-if="cat.image" :src="cat.image" :alt="`Foto de ${cat.name}`" :class="['w-full h-full object-cover transition', cat.featured ? '' : 'opacity-50 grayscale']" loading="lazy" />
          <img v-else src="/image/logo/street-urban-icono-dark.png" alt="" aria-hidden="true" class="absolute -right-6 -bottom-6 h-[110%] w-auto max-w-none aspect-square opacity-20 -rotate-12" />
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent" aria-hidden="true"></div>
          <p class="absolute left-3 bottom-2.5 right-3 text-base font-black uppercase italic tracking-tight text-white truncate">{{ cat.name }}</p>
          <span v-if="!cat.image" class="absolute top-2.5 left-2.5 rounded-full bg-black/60 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white/80">Sin foto</span>

          <div v-if="subiendo === cat.id" class="absolute inset-0 bg-black/60 flex items-center justify-center" role="status">
            <i class="fa-solid fa-spinner fa-spin text-white" aria-hidden="true"></i><span class="sr-only">Subiendo foto…</span>
          </div>
        </div>

        <div class="p-3 space-y-3">
          <!-- Interruptor de vitrina -->
          <label class="flex items-center justify-between gap-3 cursor-pointer">
            <span class="min-w-0">
              <span class="block text-sm font-semibold text-gray-900">En el Home</span>
              <span class="block text-xs text-gray-500">{{ cat.productos }} producto{{ cat.productos === 1 ? '' : 's' }}{{ cat.productos === 0 ? ' · Próximamente' : '' }}</span>
            </span>
            <button
              type="button"
              role="switch"
              :aria-checked="cat.featured"
              :aria-label="`Mostrar ${cat.name} en el Home`"
              :disabled="cambiandoVitrina === cat.id"
              :class="['relative w-11 h-6 flex-shrink-0 rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42] focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:opacity-60', cat.featured ? 'bg-[#ff8c42]' : 'bg-gray-300']"
              @click="alternarVitrina(cat)"
            >
              <span :class="['absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform motion-reduce:transition-none', cat.featured ? 'translate-x-5' : '']" aria-hidden="true"></span>
            </button>
          </label>

          <input
            :ref="(el) => (inputs[cat.id] = el)"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            :aria-label="`Foto de ${cat.name}`"
            @change="subirFoto(cat, $event)"
          />
          <button
            type="button"
            :disabled="subiendo === cat.id"
            class="w-full h-9 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 flex items-center justify-center gap-2 transition-colors disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
            @click="elegirFoto(cat)"
          >
            <i class="fa-regular fa-image" aria-hidden="true"></i>
            {{ cat.image ? 'Cambiar foto' : 'Subir foto' }}
          </button>
          <p v-if="errores[cat.id]" class="text-xs text-red-500" role="alert">{{ errores[cat.id] }}</p>
        </div>
      </li>
    </ul>

    <p class="mt-4 text-xs text-gray-400">
      Consejo: usa fotos verticales u horizontales con la prenda al centro y fondo oscuro o neutro; se recortan para llenar la tarjeta. JPG, PNG o WEBP de hasta 4 MB.
    </p>
  </section>
</template>
