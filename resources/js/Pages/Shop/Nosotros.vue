<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
</script>

<template>
  <Head title="Nosotros" />

  <ShopLayout>
    <!-- Hero Section -->
    <section class="relative min-h-screen pt-20 pb-20 px-4 md:px-8 lg:px-16 overflow-hidden flex items-center">
      <!-- Animated background -->
      <div class="absolute inset-0">
        <div class="absolute top-0 right-0 w-96 h-96 bg-fg/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-fg/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
      </div>

      <div class="relative max-w-6xl mx-auto w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <!-- Left content -->
          <div class="space-y-8">
            <div>
              <p class="text-sm uppercase tracking-[0.3em] text-[#ff8c42] font-semibold mb-4">
                Nuestra Historia
              </p>
              <h1 class="text-5xl md:text-7xl lg:text-8xl font-black uppercase italic leading-tight">
                Street
                <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff8c42] to-fg">
                  Urban
                </span>
              </h1>
            </div>

            <div class="h-1 w-32 bg-gradient-to-r from-[#ff8c42] to-fg rounded-full"></div>

            <p class="text-xl text-gray-300 leading-relaxed max-w-lg">
              Más que una tienda. Una propuesta pensada para quienes encuentran en su estilo una forma de expresarse.
            </p>

            <Link
              :href="route('shop.tienda')"
              class="inline-flex items-center gap-3 px-8 py-4 bg-fg text-canvas font-bold uppercase tracking-wider hover:bg-gray-200 transition-all rounded-lg group"
            >
              Explora Ahora
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:translate-x-1 transition-transform">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </Link>
          </div>

          <!-- Right image -->
          <div class="relative h-[500px] hidden lg:block">
            <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent rounded-2xl z-10"></div>
            <img
              src="/image/shop/nosotros-img.jpg"
              alt="Street Urban"
              class="w-full h-full object-cover rounded-2xl"
            >
          </div>
        </div>
      </div>
    </section>

    <!-- Values Section -->
    <section class="px-4 md:px-8 lg:px-16 py-24 border-y border-gray-800 bg-sunken/60">
      <div class="max-w-6xl mx-auto">
        <div class="mb-16">
          <p class="text-sm uppercase tracking-[0.3em] text-[#ff8c42] font-semibold mb-4">
            Lo que nos define
          </p>
          <h2 class="text-4xl md:text-5xl lg:text-6xl font-black uppercase italic">
            Nuestros <span class="text-gray-500">Valores</span>
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Value 1 -->
          <div class="group border border-gray-800 rounded-2xl p-8 hover:border-[#ff8c42]/50 hover:bg-[#ff8c42]/5 transition-all duration-300 cursor-pointer">
            <div class="w-14 h-14 rounded-lg bg-[#ff8c42]/10 group-hover:bg-[#ff8c42]/20 flex items-center justify-center mb-6 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-[#ff8c42]">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128A2.25 2.25 0 002.75 21h19.5a2.25 2.25 0 002.247-2.128 3 3 0 00-5.78-1.128m0 0a6.001 6.001 0 00-7.08 0m0 0a6 6 0 00-8.914 2.88M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <h3 class="text-2xl font-black uppercase italic mb-3 text-fg">
              Estilo
            </h3>
            <p class="text-gray-400 leading-relaxed">
              Una forma de vestir también es una forma de comunicar. Tu ropa cuenta tu historia sin que tengas que hablar.
            </p>
          </div>

          <!-- Value 2 -->
          <div class="group border border-gray-800 rounded-2xl p-8 hover:border-[#ff8c42]/50 hover:bg-[#ff8c42]/5 transition-all duration-300 cursor-pointer">
            <div class="w-14 h-14 rounded-lg bg-[#ff8c42]/10 group-hover:bg-[#ff8c42]/20 flex items-center justify-center mb-6 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-[#ff8c42]">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="text-2xl font-black uppercase italic mb-3 text-fg">
              Identidad
            </h3>
            <p class="text-gray-400 leading-relaxed">
              Creemos en la libertad de ser tú mismo. De construir una imagen auténtica que refleje quién realmente eres.
            </p>
          </div>

          <!-- Value 3 -->
          <div class="group border border-gray-800 rounded-2xl p-8 hover:border-[#ff8c42]/50 hover:bg-[#ff8c42]/5 transition-all duration-300 cursor-pointer">
            <div class="w-14 h-14 rounded-lg bg-[#ff8c42]/10 group-hover:bg-[#ff8c42]/20 flex items-center justify-center mb-6 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-[#ff8c42]">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-2xl font-black uppercase italic mb-3 text-fg">
              Actitud
            </h3>
            <p class="text-gray-400 leading-relaxed">
              Cada detalle importa. Desde la elección de tus colores hasta tu postura. La actitud es lo que hace la diferencia.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Mission Section -->
    <section class="px-4 md:px-8 lg:px-16 py-24">
      <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <!-- Left content -->
          <div>
            <p class="text-sm uppercase tracking-[0.3em] text-[#ff8c42] font-semibold mb-4">
              Nuestra Misión
            </p>
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black uppercase italic mb-6">
              Democratizar el <span class="text-gray-500">estilo urbano</span>
            </h2>

            <div class="h-1 w-32 bg-gradient-to-r from-[#ff8c42] to-fg rounded-full mb-8"></div>

            <div class="space-y-6 text-gray-400 leading-relaxed text-lg">
              <p>
                Street Urban nace con la misión de traer el estilo urbano a todos. Creemos que no necesitas gastar una fortuna para verte y sentirte bien.
              </p>
              <p>
                Cada prenda que seleccionamos ha sido elegida porque creemos que puede ser parte de tu identidad. Porque el estilo no es sobre marcas, es sobre ti.
              </p>
              <p>
                Queremos que cuando uses nuestros productos, te sientas seguro de quién eres y de la forma en que te expresas.
              </p>
            </div>
          </div>

          <!-- Right stats -->
          <div class="space-y-8">
            <div class="border-l-4 border-[#ff8c42] pl-6 py-4">
              <p class="text-sm text-gray-400 mb-2">Desde</p>
              <p class="text-4xl font-black text-fg">2024</p>
              <p class="text-gray-500 mt-2">Llevando estilo a las calles</p>
            </div>

            <div class="border-l-4 border-[#ff8c42] pl-6 py-4">
              <p class="text-sm text-gray-400 mb-2">Comunidad</p>
              <p class="text-4xl font-black text-fg">+2K</p>
              <p class="text-gray-500 mt-2">Personas viviendo su estilo</p>
            </div>

            <div class="border-l-4 border-[#ff8c42] pl-6 py-4">
              <p class="text-sm text-gray-400 mb-2">Colección</p>
              <p class="text-4xl font-black text-fg">150+</p>
              <p class="text-gray-500 mt-2">Prendas cuidadosamente seleccionadas</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Manifesto Section -->
    <section class="relative py-32 px-4 md:px-8 lg:px-16 overflow-hidden border-y border-gray-800 bg-gradient-to-r from-[#ff8c42]/10 via-transparent to-transparent">
      <div class="relative max-w-6xl mx-auto">
        <p class="text-4xl md:text-6xl lg:text-7xl font-black uppercase italic leading-tight max-w-4xl">
          Define tu estilo.
          <br>
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff8c42] to-fg">
            Hazlo a tu manera.
          </span>
        </p>

        <div class="h-1 w-40 bg-gradient-to-r from-[#ff8c42] to-fg rounded-full mt-8"></div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="px-4 md:px-8 lg:px-16 py-24">
      <div class="max-w-4xl mx-auto text-center">
        <p class="text-sm uppercase tracking-[0.3em] text-[#ff8c42] font-semibold mb-6">
          Comienza ahora
        </p>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black uppercase italic mb-6">
          Encuentra tu <span class="text-gray-500">identidad</span>
        </h2>

        <p class="text-gray-400 text-xl mb-12 leading-relaxed max-w-2xl mx-auto">
          Explora nuestra colección y descubre las prendas que te permitirán expresar quién eres realmente.
        </p>

        <Link
          :href="route('shop.tienda')"
          class="inline-flex items-center gap-3 px-10 py-5 bg-[#ff8c42] text-black font-black uppercase tracking-wider hover:bg-[#ffb380] transition-all rounded-lg group text-lg"
        >
          Explorar Tienda
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6 group-hover:translate-x-1 transition-transform">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
          </svg>
        </Link>
      </div>
    </section>

    <!-- Footer separator -->
    <div class="h-px bg-gradient-to-r from-transparent via-gray-800 to-transparent"></div>
  </ShopLayout>
</template>
