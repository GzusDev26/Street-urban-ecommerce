<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';

const page = usePage();
const business = computed(() => page.props.business);
const whatsappLink = computed(() => business.value.whatsapp ? `https://wa.me/${business.value.whatsapp}` : '#');

const form = ref({
  name: '',
  email: '',
  subject: '',
  message: '',
});

const successMessage = ref('');

const submitForm = () => {
  successMessage.value = 'Gracias por tu mensaje. Nos pondremos en contacto pronto.';
  form.value.name = '';
  form.value.email = '';
  form.value.subject = '';
  form.value.message = '';
  setTimeout(() => {
    successMessage.value = '';
  }, 5000);
};
</script>

<template>
  <Head title="Contacto" />
  <ShopLayout>
    <!-- Premium Hero Section -->
    <section class="relative min-h-screen pt-32 pb-32 px-4 md:px-8 lg:px-16 overflow-hidden flex items-center">
      <!-- Animated background elements -->
      <div class="absolute inset-0">
        <div class="absolute top-1/4 right-0 w-[500px] h-[500px] bg-gradient-to-br from-[#ff8c42]/25 to-transparent rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-[#ff8c42]/15 to-transparent rounded-full blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-fg/[0.03] rounded-full blur-3xl"></div>
      </div>

      <div class="relative max-w-7xl mx-auto w-full">
        <div class="space-y-12 max-w-5xl">
          <!-- Subtitle -->
          <div class="inline-flex items-center gap-3">
            <div class="w-3 h-3 rounded-full bg-[#ff8c42] animate-pulse"></div>
            <p class="text-xs uppercase tracking-[0.4em] text-gray-400 font-bold">Ponte en Contacto con Nosotros</p>
          </div>

          <!-- Main Heading -->
          <div class="space-y-6">
            <h1 class="text-6xl md:text-8xl lg:text-9xl font-black uppercase italic leading-[0.95] tracking-tight">
              <span class="text-fg">Tu opinión</span>
              <br>
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff8c42] via-[#ffb380] to-fg">
                nos importa
              </span>
            </h1>

            <p class="text-xl md:text-2xl text-gray-300 leading-relaxed max-w-3xl font-light">
              En {{ business.name }}, cada mensaje cuenta. Queremos escucharte, responder tus preguntas y conocer tus ideas innovadoras que puedan transformar nuestra comunidad.
            </p>
          </div>

          <!-- Decorative line with dot -->
          <div class="flex items-center gap-4 pt-6">
            <div class="h-px w-24 bg-gradient-to-r from-transparent to-[#ff8c42]"></div>
            <div class="w-2 h-2 rounded-full bg-[#ff8c42]"></div>
          </div>

          <!-- Quick Info -->
          <div class="grid grid-cols-3 gap-8 pt-8 max-w-2xl">
            <div>
              <p class="text-3xl font-black text-[#ff8c42]">24h</p>
              <p class="text-xs text-gray-400 uppercase tracking-widest mt-2">Respuesta Rápida</p>
            </div>
            <div>
              <p class="text-3xl font-black text-[#ff8c42]">∞</p>
              <p class="text-xs text-gray-400 uppercase tracking-widest mt-2">Disponibles</p>
            </div>
            <div>
              <p class="text-3xl font-black text-[#ff8c42]">100%</p>
              <p class="text-xs text-gray-400 uppercase tracking-widest mt-2">Dedicados</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Methods - Premium Grid -->
    <section class="px-4 md:px-8 lg:px-16 py-32 border-t border-gray-800 bg-sunken/60">
      <div class="max-w-7xl mx-auto">
        <div class="mb-20">
          <p class="text-sm uppercase tracking-[0.3em] text-[#ff8c42] font-semibold mb-6">
            Canales de Comunicación
          </p>
          <h2 class="text-5xl md:text-6xl lg:text-7xl font-black uppercase italic mb-6">
            Elige Tu <span class="text-gray-500">Forma</span>
          </h2>
          <div class="h-1 w-40 bg-gradient-to-r from-[#ff8c42] to-fg rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Email Card -->
          <div class="group relative overflow-hidden rounded-3xl border border-gray-800 hover:border-[#ff8c42]/50 transition-all duration-500 bg-gradient-to-br from-gray-900/40 to-sunken/60 p-10 hover:shadow-2xl hover:shadow-[#ff8c42]/10">
            <div class="absolute inset-0 bg-gradient-to-br from-[#ff8c42]/0 to-[#ff8c42]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="relative z-10">
              <div class="w-16 h-16 rounded-2xl bg-[#ff8c42]/15 group-hover:bg-[#ff8c42]/25 flex items-center justify-center mb-8 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-[#ff8c42]">
                  <path stroke-linecap="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5H4.5A2.25 2.25 0 002.25 6.75m19.5 0h-15m0 0L9 12m0 0l4.5 4.5M9 12l-4.5-4.5" />
                </svg>
              </div>
              <h3 class="text-3xl font-black uppercase italic mb-4 text-fg">Email</h3>
              <p class="text-gray-400 leading-relaxed mb-8 text-lg">
                Envíanos tu mensaje y nos pondremos en contacto en las próximas 24 horas.
              </p>
              <a :href="`mailto:${business.email}`" class="inline-flex items-center gap-3 text-[#ff8c42] hover:text-[#ffb380] font-bold transition-all group/link">
                {{ business.email }}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 group-hover/link:translate-x-1 transition-transform">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
              </a>
            </div>
          </div>

          <!-- Phone Card -->
          <div class="group relative overflow-hidden rounded-3xl border border-gray-800 hover:border-[#ff8c42]/50 transition-all duration-500 bg-gradient-to-br from-gray-900/40 to-sunken/60 p-10 hover:shadow-2xl hover:shadow-[#ff8c42]/10">
            <div class="absolute inset-0 bg-gradient-to-br from-[#ff8c42]/0 to-[#ff8c42]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="relative z-10">
              <div class="w-16 h-16 rounded-2xl bg-[#ff8c42]/15 group-hover:bg-[#ff8c42]/25 flex items-center justify-center mb-8 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-[#ff8c42]">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 11.308 8.217 20.25 18.375 20.25A8.625 8.625 0 0020.25 21v-4.875c0-.621.504-1.125 1.125-1.125h4.875c.621 0 1.125.504 1.125 1.125V21a8.625 8.625 0 01-8.375 8.25M9 12c0 1.657 1.343 3 3 3s3-1.343 3-3-1.343-3-3-3-3 1.343-3 3z" />
                </svg>
              </div>
              <h3 class="text-3xl font-black uppercase italic mb-4 text-fg">Teléfono</h3>
              <p class="text-gray-400 leading-relaxed mb-8 text-lg">
                Habla directamente con nuestro equipo para una respuesta inmediata.
              </p>
              <a :href="`tel:${business.phone}`" class="inline-flex items-center gap-3 text-[#ff8c42] hover:text-[#ffb380] font-bold transition-all group/link">
                {{ business.phone }}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 group-hover/link:translate-x-1 transition-transform">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
              </a>
            </div>
          </div>

          <!-- Hours Card -->
          <div class="group relative overflow-hidden rounded-3xl border border-gray-800 hover:border-[#ff8c42]/50 transition-all duration-500 bg-gradient-to-br from-gray-900/40 to-sunken/60 p-10 hover:shadow-2xl hover:shadow-[#ff8c42]/10">
            <div class="absolute inset-0 bg-gradient-to-br from-[#ff8c42]/0 to-[#ff8c42]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="relative z-10">
              <div class="w-16 h-16 rounded-2xl bg-[#ff8c42]/15 group-hover:bg-[#ff8c42]/25 flex items-center justify-center mb-8 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-[#ff8c42]">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5-15a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="text-3xl font-black uppercase italic mb-4 text-fg">Horarios</h3>
              <p class="text-gray-400 leading-relaxed mb-8 text-lg">
                Estamos disponibles en estos horarios para atenderte.
              </p>
              <div class="space-y-3 text-[#ff8c42] font-semibold">
                <p class="flex justify-between"><span>Lunes - Viernes:</span> <span>09:00 AM - 9:00 PM</span></p>
                <p class="flex justify-between"><span>Sábado:</span> <span>10:00 AM - 6:00 PM</span></p>
                <p class="flex justify-between text-gray-500"><span>Domingo:</span> <span>Cerrado</span></p>
              </div>
            </div>
          </div>

          <!-- Redes Card -->
          <div class="group relative overflow-hidden rounded-3xl border border-gray-800 hover:border-[#ff8c42]/50 transition-all duration-500 bg-gradient-to-br from-gray-900/40 to-sunken/60 p-10 hover:shadow-2xl hover:shadow-[#ff8c42]/10">
            <div class="absolute inset-0 bg-gradient-to-br from-[#ff8c42]/0 to-[#ff8c42]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="relative z-10">
              <div class="w-16 h-16 rounded-2xl bg-[#ff8c42]/15 group-hover:bg-[#ff8c42]/25 flex items-center justify-center mb-8 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-[#ff8c42]">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.769-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0c.174.324.277.696.277 1.093s-.103.769-.277 1.093" />
                </svg>
              </div>
              <h3 class="text-3xl font-black uppercase italic mb-4 text-fg">Redes Sociales</h3>
              <p class="text-gray-400 leading-relaxed mb-8 text-lg">
                Síguenos en redes para actualizaciones y contenido exclusivo.
              </p>
              <div class="flex gap-4">
                <a href="#" class="px-6 py-3 border border-[#ff8c42] text-[#ff8c42] hover:bg-[#ff8c42]/10 font-bold rounded-lg transition-all text-sm uppercase tracking-wider">Instagram</a>
                <a :href="whatsappLink" target="_blank" class="px-6 py-3 border border-[#ff8c42] text-[#ff8c42] hover:bg-[#ff8c42]/10 font-bold rounded-lg transition-all text-sm uppercase tracking-wider">WhatsApp</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Form Section -->
    <section class="px-4 md:px-8 lg:px-16 py-32">
      <div class="max-w-2xl mx-auto">
        <div class="mb-20">
          <p class="text-sm uppercase tracking-[0.3em] text-[#ff8c42] font-semibold mb-6">
            Formulario de Contacto
          </p>
          <h2 class="text-5xl md:text-6xl lg:text-7xl font-black uppercase italic mb-6">
            Comparte Tu <span class="text-gray-500">Mensaje</span>
          </h2>
          <div class="h-1 w-40 bg-gradient-to-r from-[#ff8c42] to-fg rounded-full"></div>
        </div>

        <div class="border border-gray-800 rounded-3xl p-8 md:p-12 bg-gradient-to-br from-gray-900/40 to-sunken/60 backdrop-blur-sm hover:border-[#ff8c42]/30 transition-all duration-500">
          <form @submit.prevent="submitForm" class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <!-- Name -->
              <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-[0.15em] text-gray-300 mb-4">
                  Nombre Completo
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  placeholder="Tu nombre"
                  class="w-full bg-fg/[0.05] border border-gray-700/50 rounded-xl px-6 py-4 text-fg placeholder-gray-600 focus:border-[#ff8c42]/60 focus:outline-none transition-all focus:bg-fg/10"
                />
              </div>

              <!-- Email -->
              <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-[0.15em] text-gray-300 mb-4">
                  Correo Electrónico
                </label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="tu@email.com"
                  class="w-full bg-fg/[0.05] border border-gray-700/50 rounded-xl px-6 py-4 text-fg placeholder-gray-600 focus:border-[#ff8c42]/60 focus:outline-none transition-all focus:bg-fg/10"
                />
              </div>
            </div>

            <!-- Subject -->
            <div>
              <label for="subject" class="block text-xs font-bold uppercase tracking-[0.15em] text-gray-300 mb-4">
                Asunto
              </label>
              <input
                id="subject"
                v-model="form.subject"
                type="text"
                placeholder="Cuéntanos el tema de tu mensaje..."
                class="w-full bg-fg/[0.05] border border-gray-700/50 rounded-xl px-6 py-4 text-fg placeholder-gray-600 focus:border-[#ff8c42]/60 focus:outline-none transition-all focus:bg-fg/10"
              />
            </div>

            <!-- Message -->
            <div>
              <label for="message" class="block text-xs font-bold uppercase tracking-[0.15em] text-gray-300 mb-4">
                Mensaje
              </label>
              <textarea
                id="message"
                v-model="form.message"
                rows="8"
                required
                placeholder="Escribe tu mensaje con libertad. Queremos conocer tu perspectiva..."
                class="w-full bg-fg/[0.05] border border-gray-700/50 rounded-xl px-6 py-4 text-fg placeholder-gray-600 focus:border-[#ff8c42]/60 focus:outline-none transition-all focus:bg-fg/10 resize-none"
              ></textarea>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              class="w-full py-5 bg-gradient-to-r from-[#ff8c42] to-[#ffb380] text-black font-black uppercase text-base tracking-widest rounded-xl hover:shadow-2xl hover:shadow-[#ff8c42]/30 transition-all duration-300 flex items-center justify-center gap-3 group hover:scale-105"
            >
              <span>Enviar Mi Mensaje</span>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 group-hover:translate-x-1 transition-transform">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </button>
          </form>

          <!-- Success Message -->
          <transition
            enter-active-class="transition duration-500"
            enter-from-class="opacity-0 translate-y-6 scale-90"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-300"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-6 scale-90"
          >
            <div
              v-if="successMessage"
              class="mt-8 p-6 rounded-2xl border-2 border-[#ff8c42]/60 bg-gradient-to-r from-[#ff8c42]/20 to-[#ff8c42]/5 text-[#ffb380] flex items-center gap-4"
            >
              <div class="flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-6 h-6">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
              </div>
              <div>
                <p class="font-bold text-lg">¡Mensaje Enviado!</p>
                <p class="text-sm">{{ successMessage }}</p>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </section>

    <!-- Manifesto Section -->
    <section class="relative py-32 px-4 md:px-8 lg:px-16 border-y border-gray-800/50 bg-gradient-to-r from-[#ff8c42]/10 via-transparent to-transparent overflow-hidden">
      <div class="relative max-w-6xl mx-auto">
        <p class="text-xl md:text-2xl lg:text-3xl font-black uppercase italic leading-tight max-w-4xl mb-8">
          Tu mensaje nos ayuda a crecer.
          <br>
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff8c42] to-fg">
            Tu voz es importante.
          </span>
        </p>

        <div class="h-1 w-40 bg-gradient-to-r from-[#ff8c42] to-fg rounded-full"></div>
      </div>
    </section>

    <!-- Social & Community Section -->
    <section class="px-4 md:px-8 lg:px-16 py-32">
      <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
          <!-- Left: Text -->
          <div class="space-y-8">
            <div>
              <p class="text-sm uppercase tracking-[0.3em] text-[#ff8c42] font-semibold mb-6">
                Conecta Con Nosotros
              </p>
              <h2 class="text-5xl md:text-6xl lg:text-7xl font-black uppercase italic tracking-tight">
                Síguenos en <span class="text-gray-500">Redes</span>
              </h2>
            </div>

            <p class="text-lg text-gray-400 leading-relaxed max-w-2xl">
              Mantente en el loop con nuestros últimos drops, contenido exclusivo, behind-the-scenes y eventos especiales de {{ business.name }}.
            </p>

            <div class="h-1 w-32 bg-gradient-to-r from-[#ff8c42] to-fg rounded-full"></div>
          </div>

          <!-- Right: Social Buttons -->
          <div class="grid grid-cols-2 gap-6">
            <a href="#" class="group relative overflow-hidden rounded-2xl border border-gray-800 hover:border-[#ff8c42]/50 transition-all duration-500 bg-gradient-to-br from-gray-900/60 to-sunken/40 p-8 hover:shadow-lg hover:shadow-[#ff8c42]/20">
              <div class="absolute inset-0 bg-gradient-to-br from-[#ff8c42]/0 to-[#ff8c42]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div class="relative z-10 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-[#ff8c42] mx-auto mb-4 group-hover:scale-110 transition-transform">
                  <path d="M8.29 20v-7.21H5.5V9.25h2.79V7.69c0-2.86 1.72-4.43 4.51-4.43 1.31 0 2.43.1 2.75.14v3.09h-1.89c-1.4 0-1.74.67-1.74 1.64v2.16h3.44l-.46 3.54h-2.98V20z"/>
                </svg>
                <p class="text-sm font-bold uppercase tracking-widest text-gray-200">Instagram</p>
              </div>
            </a>

            <a :href="whatsappLink" target="_blank" class="group relative overflow-hidden rounded-2xl border border-gray-800 hover:border-[#ff8c42]/50 transition-all duration-500 bg-gradient-to-br from-gray-900/60 to-sunken/40 p-8 hover:shadow-lg hover:shadow-[#ff8c42]/20">
              <div class="absolute inset-0 bg-gradient-to-br from-[#ff8c42]/0 to-[#ff8c42]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div class="relative z-10 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-[#ff8c42] mx-auto mb-4 group-hover:scale-110 transition-transform">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-3.055 2.28-3.756 6.104-1.563 9.248 1.51 2.23 4.105 3.901 7.217 3.901 2.57 0 5.001-.937 6.948-2.645l.6.341c1.913 1.086 3.81 1.044 4.89.141 1.079-.902 1.353-2.437.274-3.782-1.365-1.645-4.356-3.110-8.514-3.114.202-.398.405-.779.605-1.162 2.62-5.555-1.335-11.236-8.267-11.236-1.186 0-2.318.195-3.406.586 3.082 1.614 5.344 4.759 5.342 8.469"/>
                </svg>
                <p class="text-sm font-bold uppercase tracking-widest text-gray-200">WhatsApp</p>
              </div>
            </a>

            <a href="#" class="group relative overflow-hidden rounded-2xl border border-gray-800 hover:border-[#ff8c42]/50 transition-all duration-500 bg-gradient-to-br from-gray-900/60 to-sunken/40 p-8 hover:shadow-lg hover:shadow-[#ff8c42]/20 col-span-2">
              <div class="absolute inset-0 bg-gradient-to-br from-[#ff8c42]/0 to-[#ff8c42]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div class="relative z-10 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-[#ff8c42] mx-auto mb-4 group-hover:scale-110 transition-transform">
                  <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.68v13.67a2.4 2.4 0 1 1-4.77-2.3A2.39 2.39 0 0 1 9.10 9.66c0 .193-.037.38-.1.56a4.27 4.27 0 0 1-7.78 4.3v-3.6a8.05 8.05 0 0 0 8.34 8.48V12.5a4.18 4.18 0 0 0 3.91 2.9 4.13 4.13 0 0 0 4.19-4.1V9.03a8.08 8.08 0 0 0 4.65 1.48v-3.6a4.7 4.7 0 0 1-.5-.05z"/>
                </svg>
                <p class="text-sm font-bold uppercase tracking-widest text-gray-200">TikTok</p>
              </div>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer separator -->
    <div class="h-px bg-gradient-to-r from-transparent via-gray-800 to-transparent"></div>
  </ShopLayout>
</template>
