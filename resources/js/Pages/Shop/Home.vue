<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import ProductCard from '@/Components/Shop/ProductCard.vue';

const props = defineProps({
  products: Array,
  categories: Array,
  moreCategories: { type: Number, default: 0 },
});

// En escritorio la grilla tiene 6 columnas y cada tarjeta ocupa 2 (3 por fila). Si sobran 1 o 2
// para completar la última fila, las primeras se agrandan: así nunca queda un hueco al final.
const spanCategoria = (i) => {
  const sobrantes = props.categories.length % 3;
  if (sobrantes === 1 && i === 0) return 'lg:col-span-6';
  if (sobrantes === 2 && i < 2) return 'lg:col-span-3';
  return 'lg:col-span-2';
};

// Collage del hero: fotos de las categorías destacadas (si faltan, se completa con las de marca).
const FOTOS_MARCA = ['/image/shop/poleras.jpg', '/image/shop/pantalones.webp', '/image/shop/polos.jpeg'];
const collage = computed(() => {
  const deCategorias = props.categories.filter((c) => c.image).map((c) => ({ src: c.image, label: c.name }));
  const relleno = FOTOS_MARCA.filter((src) => !deCategorias.some((c) => c.src === src)).map((src) => ({ src, label: null }));
  return [...deCategorias, ...relleno].slice(0, 3);
});

const novedades = computed(() => props.products.slice(0, 8));

// Barra de servicio bajo el hero: solo datos y funciones reales (nada de promesas inventadas).
const page = usePage();
const negocio = computed(() => page.props.business ?? {});
const conSesion = computed(() => !!page.props.auth?.user);
const whatsappUrl = computed(() => {
  const numero = negocio.value.whatsapp;
  if (!numero) return null;
  return `https://wa.me/${numero}?text=${encodeURIComponent('Hola Street Urban, quiero consultar por una prenda.')}`;
});
const abrirCuenta = () => window.dispatchEvent(new Event('open-auth-modal'));
</script>

<template>
  <Head title="Inicio" />
  <ShopLayout>
    <!-- Hero editorial: siempre oscuro, como el navbar que va encima -->
    <section class="theme-shop-dark relative bg-[#0b0b0c] text-white overflow-hidden">
      <!-- Textura: palabra gigante en contorno detrás del titular -->
      <p class="hero-outline hidden lg:block pointer-events-none select-none absolute -left-4 bottom-16 lg:bottom-20 text-[22vw] lg:text-[17vw] leading-none font-black uppercase italic tracking-tighter" aria-hidden="true">Street</p>

      <div class="relative max-w-[1600px] mx-auto px-4 md:px-8 lg:px-16 pt-28 pb-14 lg:pt-32 lg:pb-20 grid lg:grid-cols-12 gap-12 lg:gap-10 items-center lg:min-h-[calc(100svh-56px)]">
        <div class="lg:col-span-6 xl:col-span-5">
          <p class="hero-fade text-xs font-semibold uppercase tracking-[0.3em] text-[#ff8c42]" style="--d: 0ms"><span class="hidden sm:inline">Nueva temporada · </span>San Ramón, Chanchamayo</p>

          <!-- Cada línea sube desde detrás de su máscara -->
          <h1 class="mt-5 text-[3.25rem] leading-[0.92] sm:text-7xl lg:text-[clamp(3.5rem,6.4vw,6rem)] font-black uppercase italic tracking-tighter">
            <span class="hero-line"><span style="--d: 80ms">Street</span></span>
            <span class="hero-line"><span style="--d: 160ms" class="text-[#ff8c42]">Urban</span></span>
          </h1>

          <p class="hero-fade mt-6 max-w-md text-base md:text-lg text-white/70 leading-relaxed" style="--d: 320ms">
            Ropa urbana y zapatillas para quienes hacen de su estilo una forma de expresarse.
          </p>

          <div class="hero-fade mt-10 flex flex-col sm:flex-row sm:flex-wrap gap-3" style="--d: 400ms">
            <Link :href="route('shop.tienda')" class="hero-btn group inline-flex h-14 items-center justify-center gap-3 px-8 whitespace-nowrap rounded-lg bg-[#ff8c42] text-black text-sm font-bold uppercase tracking-widest hover:bg-[#ffb380] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-black">
              Comprar ahora <span class="hero-arrow" aria-hidden="true">&rarr;</span>
            </Link>
            <a href="#categorias" class="hero-btn inline-flex h-14 items-center justify-center px-8 whitespace-nowrap rounded-lg border border-white/25 text-white text-sm font-bold uppercase tracking-widest hover:bg-white/10 hover:border-white/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
              Ver categorías
            </a>
          </div>
        </div>

        <!-- Collage con fotos propias: cada foto se revela de abajo hacia arriba y se asienta -->
        <div class="relative lg:col-span-6 xl:col-span-7" aria-hidden="true">
          <div class="grid grid-cols-5 grid-rows-2 gap-3 md:gap-4 h-[420px] sm:h-[520px] lg:h-[min(640px,72svh)]">
            <figure
              v-for="(foto, i) in collage"
              :key="foto.src"
              class="hero-photo group/foto relative overflow-hidden rounded-2xl bg-white/5"
              :class="i === 0 ? 'col-span-3 row-span-2' : 'col-span-2 row-span-1'"
              :style="{ '--d': `${200 + i * 110}ms` }"
            >
              <img :src="foto.src" alt="" class="hero-photo-img w-full h-full object-cover" :loading="i === 0 ? 'eager' : 'lazy'" />
              <!-- Unifica el tono de fotos con fondos distintos -->
              <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/5 to-transparent"></div>
              <figcaption v-if="foto.label" class="absolute left-3 bottom-3 rounded-full bg-black/70 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white">{{ foto.label }}</figcaption>
            </figure>
          </div>

          <!-- Insignia giratoria -->
          <div class="hero-badge-in absolute -left-5 xl:-left-12 bottom-16 hidden lg:block" style="--d: 650ms">
            <div class="relative w-32 h-32 rounded-full bg-[#ff8c42] text-black shadow-2xl shadow-black/50">
              <svg viewBox="0 0 100 100" class="hero-badge absolute inset-0 w-full h-full">
                <defs><path id="hero-circulo" d="M50,50 m-37,0 a37,37 0 1,1 74,0 a37,37 0 1,1 -74,0" /></defs>
                <text class="fill-current text-[9px] font-black uppercase"><textPath href="#hero-circulo" textLength="230" lengthAdjust="spacing">Nueva temporada &#10022; Street Urban &#10022;</textPath></text>
              </svg>
              <img src="/image/logo/street-urban-icono-light.png" alt="" class="absolute inset-0 m-auto w-14 h-14 object-contain brightness-0" />
            </div>
          </div>
        </div>
      </div>

      <!-- Barra de servicio: información útil para comprar, sin movimiento -->
      <div class="relative border-t border-white/10">
        <ul class="max-w-[1600px] mx-auto px-4 md:px-8 lg:px-16 grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-white/10">
          <li>
            <Link :href="route('shop.contacto')" class="servicio group">
              <span class="servicio-icono" aria-hidden="true">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>
              </span>
              <span class="min-w-0">
                <span class="servicio-titulo">Tienda en San Ramón</span>
                <span class="servicio-texto">{{ negocio.address || 'Chanchamayo, Junín' }} · Ver cómo llegar</span>
              </span>
              <span class="servicio-flecha" aria-hidden="true">&rarr;</span>
            </Link>
          </li>
          <li v-if="whatsappUrl">
            <a :href="whatsappUrl" target="_blank" rel="noopener" class="servicio group">
              <span class="servicio-icono" aria-hidden="true">
                <i class="fa-brands fa-whatsapp text-xl"></i>
              </span>
              <span class="min-w-0">
                <span class="servicio-titulo">Compra por WhatsApp</span>
                <span class="servicio-texto">Te ayudamos a elegir tu talla<span class="sr-only"> (se abre en otra pestaña)</span></span>
              </span>
              <span class="servicio-flecha" aria-hidden="true">&rarr;</span>
            </a>
          </li>
          <li>
            <Link v-if="conSesion" :href="route('shop.wishlist')" class="servicio group">
              <span class="servicio-icono" aria-hidden="true">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
              </span>
              <span class="min-w-0">
                <span class="servicio-titulo">Tus favoritos</span>
                <span class="servicio-texto">Revisa lo que guardaste</span>
              </span>
              <span class="servicio-flecha" aria-hidden="true">&rarr;</span>
            </Link>
            <button v-else type="button" class="servicio group w-full text-left" @click="abrirCuenta">
              <span class="servicio-icono" aria-hidden="true">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
              </span>
              <span class="min-w-0">
                <span class="servicio-titulo">Guarda tus favoritos</span>
                <span class="servicio-texto">Crea tu cuenta gratis</span>
              </span>
              <span class="servicio-flecha" aria-hidden="true">&rarr;</span>
            </button>
          </li>
        </ul>
      </div>
    </section>

    <!-- Categorías (vitrina curada desde el admin) -->
    <section id="categorias" class="scroll-mt-24 py-20 md:py-28 px-4 md:px-8 lg:px-16 max-w-[1600px] mx-auto">
      <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#ff8c42] mb-3">Compra por categoría</p>
      <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3 mb-12">
        <h2 class="text-2xl md:text-3xl font-black uppercase tracking-tighter italic">
          Explora por <span class="text-fg/50">Categoría</span>
        </h2>
        <!-- Solo si hay categorías fuera de la vitrina: la Tienda las lista y filtra todas -->
        <Link
          v-if="moreCategories > 0"
          :href="route('shop.tienda')"
          class="group inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-[#ff8c42] transition-colors focus:outline-none focus-visible:text-[#ff8c42] focus-visible:underline underline-offset-4"
        >
          Ver todas las categorías
          <span class="text-gray-500 group-hover:text-[#ff8c42]">({{ categories.length + moreCategories }})</span>
          <span aria-hidden="true" class="transition-transform group-hover:translate-x-1 motion-reduce:transition-none">→</span>
        </Link>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
        <component
          :is="category.count > 0 ? Link : 'div'"
          v-for="(category, i) in categories"
          :key="category.name"
          :href="category.count > 0 ? `${route('shop.tienda')}?category=${encodeURIComponent(category.name)}` : undefined"
          :aria-label="category.count > 0 ? `Ver ${category.name}` : `${category.name}, próximamente`"
          :class="[
            'group relative h-80 rounded-2xl overflow-hidden block theme-shop-dark bg-[#0b0b0c]',
            spanCategoria(i),
            category.count > 0 ? 'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42] focus-visible:ring-offset-2 focus-visible:ring-offset-canvas' : 'cursor-default',
          ]"
        >
          <img
            v-if="category.image"
            :src="category.image"
            :alt="category.name"
            loading="lazy"
            :class="['w-full h-full object-cover transition-transform duration-500', category.count > 0 ? 'group-hover:scale-105' : 'grayscale-[40%]']"
          />
          <!-- Sin foto todavía: portada de marca con el león de fondo -->
          <div v-else class="absolute inset-0 bg-gradient-to-br from-[#1a1a1c] to-[#0b0b0c]" aria-hidden="true">
            <img
              src="/image/logo/street-urban-icono-dark.png"
              alt=""
              :class="['absolute -right-10 -bottom-12 h-[115%] w-auto max-w-none aspect-square object-contain opacity-[0.22] -rotate-12 transition-transform duration-700', category.count > 0 ? 'group-hover:-rotate-6 group-hover:scale-105' : '']"
            />
          </div>

          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent transition-all duration-300" :class="category.count > 0 ? 'group-hover:from-black/90' : ''"></div>

          <span v-if="category.count === 0" class="absolute top-5 left-5 px-3 py-1.5 rounded-full bg-black/60 border border-white/15 text-[11px] font-bold uppercase tracking-[0.2em] text-white/80">
            Próximamente
          </span>

          <div class="absolute bottom-0 left-0 right-0 p-6">
            <h3 class="text-2xl font-black uppercase italic tracking-tight text-white">{{ category.name }}</h3>
            <p v-if="category.count > 0" class="text-sm text-white/80 mt-2 flex items-center gap-2">
              {{ category.count }} {{ category.count === 1 ? 'producto' : 'productos' }}
              <span class="inline-flex items-center gap-1 text-[#ff8c42] font-semibold opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 motion-reduce:transition-none">Ver colección <span aria-hidden="true">→</span></span>
            </p>
            <p v-else class="text-sm text-white/60 mt-2">Muy pronto en tienda</p>
          </div>
        </component>
      </div>
    </section>

    <!-- Novedades -->
    <section id="productos" class="scroll-mt-24 pb-20 md:pb-28 px-4 md:px-8 lg:px-16 max-w-[1600px] mx-auto">
      <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3 mb-10">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#ff8c42] mb-3">Recién llegados</p>
          <h2 class="text-2xl md:text-3xl font-black uppercase tracking-tighter italic">
            Lo más <span class="text-fg/50">nuevo</span>
          </h2>
        </div>
        <Link
          v-if="products.length"
          :href="route('shop.tienda')"
          class="group inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-[#ff8c42] transition-colors focus:outline-none focus-visible:text-[#ff8c42] focus-visible:underline underline-offset-4"
        >
          Ver toda la tienda
          <span aria-hidden="true" class="transition-transform group-hover:translate-x-1 motion-reduce:transition-none">&rarr;</span>
        </Link>
      </div>

      <div v-if="novedades.length" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-x-4 gap-y-10 md:gap-x-6">
        <ProductCard v-for="product in novedades" :key="product.id" :product="product" />
      </div>

      <div v-else class="rounded-2xl border border-dashed border-gray-800 py-20 text-center">
        <p class="text-lg font-black uppercase italic">Se viene el primer drop</p>
        <p class="mt-2 text-sm text-gray-500">Estamos preparando la colección. Vuelve pronto.</p>
      </div>
    </section>

    <!-- Marca -->
    <section class="theme-shop-dark bg-[#0b0b0c] text-white">
      <div class="max-w-[1600px] mx-auto grid lg:grid-cols-2 items-stretch">
        <div class="relative min-h-[360px] lg:min-h-[560px] overflow-hidden">
          <img src="/image/shop/nosotros-img.jpg" alt="Equipo de Street Urban con ropa de la tienda" loading="lazy" class="absolute inset-0 w-full h-full object-cover" />
        </div>
        <div class="flex flex-col justify-center px-6 md:px-12 lg:px-16 py-16">
          <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#ff8c42]">Nuestra historia</p>
          <h2 class="mt-4 text-4xl md:text-6xl font-black uppercase italic tracking-tighter leading-[0.95]">
            Más que<br />una tienda
          </h2>
          <p class="mt-6 max-w-md text-white/70 leading-relaxed">
            Una propuesta pensada para quienes encuentran en su estilo una forma de expresarse. Seleccionamos cada prenda para que tu outfit hable por ti.
          </p>
          <Link :href="route('shop.nosotros')" class="mt-10 self-start inline-flex h-14 items-center gap-3 px-8 rounded-lg border border-white/25 text-sm font-bold uppercase tracking-widest hover:bg-white hover:text-black transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
            Conócenos <span aria-hidden="true">&rarr;</span>
          </Link>
        </div>
      </div>
    </section>
  </ShopLayout>
</template>

<style scoped>
/*
 * Entrada del hero: se ve una vez por visita (marketing), así que puede ser más larga que la UI.
 * Todo es animación CSS (corre fuera del hilo principal mientras carga la página) y solo mueve
 * transform, opacity y clip-path. Cada pieza recibe su retraso en --d para escalonar la lectura:
 * etiqueta → titular → texto → botones → fotos → insignia.
 */

/* Titular: cada línea sube desde detrás de su máscara */
.hero-line {
  display: block;
  overflow: hidden;
  white-space: nowrap;
  /* Deja espacio a la cursiva y los acentos para que la máscara no los corte */
  padding: 0.04em 0.12em 0.06em 0;
  margin-right: -0.12em;
}
.hero-line > span {
  display: inline-block;
  animation: hero-line 900ms var(--ease-out) backwards;
  animation-delay: var(--d, 0ms);
}
@keyframes hero-line {
  from { transform: translateY(105%); }
  to { transform: none; }
}

/* Etiqueta, texto y botones */
.hero-fade {
  animation: hero-fade 700ms var(--ease-out) backwards;
  animation-delay: var(--d, 0ms);
}
@keyframes hero-fade {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: none; }
}

/* Fotos: revelado de abajo hacia arriba y la imagen se asienta de un leve zoom */
.hero-photo {
  animation: hero-reveal 1100ms var(--ease-in-out) backwards;
  animation-delay: var(--d, 0ms);
}
.hero-photo-img {
  animation: hero-settle 1600ms var(--ease-out) backwards;
  animation-delay: var(--d, 0ms);
}
@keyframes hero-reveal {
  from { clip-path: inset(100% 0 0 0 round 1rem); }
  to { clip-path: inset(0 0 0 0 round 1rem); }
}
@keyframes hero-settle {
  from { transform: scale(1.12); }
  to { transform: none; }
}

/* Insignia: aparece (nunca desde scale 0) y luego gira lento, como movimiento constante */
.hero-badge-in {
  animation: hero-badge-in 600ms var(--ease-out) backwards;
  animation-delay: var(--d, 0ms);
}
@keyframes hero-badge-in {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: none; }
}
.hero-badge { animation: hero-spin 18s linear infinite; }
@keyframes hero-spin { to { transform: rotate(360deg); } }

/* Palabra de fondo: contorno apenas visible */
.hero-outline {
  color: transparent;
  -webkit-text-stroke: 1px rgb(255 255 255 / 0.06);
}

/* Botones: respuesta al presionar; la flecha solo se mueve con mouse (no en táctil) */
.hero-btn {
  transition: transform 160ms var(--ease-out), background-color 200ms ease, border-color 200ms ease;
}
.hero-btn:active { transform: scale(0.97); }
.hero-arrow { display: inline-block; transition: transform 200ms var(--ease-out); }
@media (hover: hover) and (pointer: fine) {
  .hero-btn:hover .hero-arrow { transform: translateX(4px); }
  .hero-photo-img { transition: transform 600ms var(--ease-out); }
  .group\/foto:hover .hero-photo-img { transform: scale(1.04); }
}

/* Barra de servicio: estática; el feedback es de color y una flecha que avanza con mouse */
.servicio {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem 0;
  min-height: 5.5rem;
  transition: color 200ms ease;
}
@media (min-width: 768px) {
  li:not(:first-child) > .servicio { padding-left: 2rem; }
  li:not(:last-child) > .servicio { padding-right: 2rem; }
}
.servicio:focus { outline: none; }
.servicio:focus-visible { box-shadow: inset 0 0 0 2px #ff8c42; border-radius: 0.5rem; }
.servicio-icono {
  width: 2.75rem;
  height: 2.75rem;
  flex-shrink: 0;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ff8c42;
  background: rgb(255 140 66 / 0.1);
  transition: background-color 200ms ease, color 200ms ease;
}
.servicio-titulo {
  display: block;
  font-size: 0.875rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
.servicio-texto {
  display: block;
  margin-top: 0.125rem;
  font-size: 0.8125rem;
  color: rgb(255 255 255 / 0.55);
}
.servicio-flecha {
  margin-left: auto;
  color: rgb(255 255 255 / 0.35);
  transition: transform 200ms var(--ease-out), color 200ms ease;
}
.servicio:active .servicio-icono { transform: scale(0.95); }
@media (hover: hover) and (pointer: fine) {
  .servicio:hover .servicio-icono { background: #ff8c42; color: #000; }
  .servicio:hover .servicio-flecha { transform: translateX(4px); color: #ff8c42; }
}

/* Menos movimiento: sin desplazamientos ni giros; solo un fundido corto */
@media (prefers-reduced-motion: reduce) {
  .hero-line > span, .hero-photo, .hero-photo-img, .hero-badge { animation: none; }
  .hero-fade, .hero-badge-in { animation: hero-fade-only 300ms ease backwards; animation-delay: 0ms; }
}
@keyframes hero-fade-only {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
