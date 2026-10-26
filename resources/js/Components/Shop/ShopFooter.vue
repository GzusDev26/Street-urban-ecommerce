<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const business = computed(() => page.props.business);
</script>

<template>
  <footer class="bg-sunken text-fg py-16 border-t border-fg/5">
    <div class="max-w-[1600px] mx-auto px-4 md:px-8 lg:px-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
      
      <!-- Brand Section -->
      <div class="space-y-6">
        <img src="/image/logo/street-urban-logo-light.png" alt="Street Urban" class="h-20 w-auto dark:hidden" />
        <img src="/image/logo/street-urban-logo-dark.png" alt="Street Urban" class="h-20 w-auto hidden dark:block" />
        <p class="text-gray-400 text-sm leading-relaxed">
          Llevamos la cultura urbana a otro nivel. Calidad premium, diseño disruptivo y la esencia de la calle en cada prenda.
        </p>
        <div class="flex gap-4">
          <a :href="route('social.instagram')" class="text-gray-400 hover:text-fg transition-colors" target="_blank">
            <span class="sr-only">Instagram</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 512 512">
              <!-- Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc. -->
              <path d="M224.3 141a115 115 0 1 0 -.6 230 115 115 0 1 0 .6-230zm-.6 40.4a74.6 74.6 0 1 1 .6 149.2 74.6 74.6 0 1 1 -.6-149.2zm93.4-45.1a26.8 26.8 0 1 1 53.6 0 26.8 26.8 0 1 1 -53.6 0zm129.7 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM399 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/>
            </svg>
          </a>
          <a :href="route('social.twitter')" class="text-gray-400 hover:text-fg transition-colors" target="_blank">
            <span class="sr-only">X (Twitter)</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 448 512">
              <!-- Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc. -->
              <path d="M357.2 48L427.8 48 273.6 224.2 455 464 313 464 201.7 318.6 74.5 464 3.8 464 168.7 275.5-5.2 48 140.4 48 240.9 180.9 357.2 48zM332.4 421.8l39.1 0-252.4-333.8-42 0 255.3 333.8z"/>
            </svg>
          </a>
        </div>
      </div>

      <!-- Links Section -->
      <div>
        <h3 class="text-sm font-bold uppercase tracking-widest mb-6 text-fg">Explorar</h3>
        <ul class="space-y-4">
          <li><Link href="/shop" class="text-gray-400 hover:text-fg transition-colors text-sm">Todos los Productos</Link></li>
          <li><Link href="#" class="text-gray-400 hover:text-fg transition-colors text-sm">Novedades</Link></li>
          <li><Link href="#" class="text-gray-400 hover:text-fg transition-colors text-sm">Más Vendidos</Link></li>
          <li><Link href="#" class="text-gray-400 hover:text-fg transition-colors text-sm">Ofertas</Link></li>
        </ul>
      </div>

      <!-- Support Section -->
      <div>
        <h3 class="text-sm font-bold uppercase tracking-widest mb-6 text-fg">Ayuda</h3>
        <ul class="space-y-4">
          <li><Link href="#" class="text-gray-400 hover:text-fg transition-colors text-sm">Envíos y Entregas</Link></li>
          <li><Link href="#" class="text-gray-400 hover:text-fg transition-colors text-sm">Cambios y Devoluciones</Link></li>
          <li><Link href="#" class="text-gray-400 hover:text-fg transition-colors text-sm">Guía de Tallas</Link></li>
          <li><Link :href="route('shop.contacto')" class="text-gray-400 hover:text-fg transition-colors text-sm">Contacto</Link></li>
        </ul>
      </div>

      <!-- Newsletter Section -->
      <div>
        <h3 class="text-sm font-bold uppercase tracking-widest mb-6 text-fg">Newsletter</h3>
        <p class="text-gray-400 text-sm mb-6">Únete para recibir lanzamientos exclusivos.</p>
        <form @submit.prevent class="flex gap-2">
          <input 
            type="email" 
            aria-label="Correo electrónico" 
            placeholder="Email" 
            class="bg-transparent border border-fg/20 px-4 py-2 text-sm focus:outline-none focus:border-fg transition-colors w-full"
          />
          <button type="submit" class="bg-fg text-canvas px-4 py-2 text-xs font-bold uppercase hover:bg-gray-200 transition-colors">
            OK
          </button>
        </form>
      </div>
    </div>

    <div class="max-w-[1600px] mx-auto px-4 md:px-8 lg:px-16 mt-16 pt-8 border-t border-fg/5 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
      <p>&copy; 2026 {{ business.name }}. All rights reserved.</p>
      <div class="flex gap-6">
        <Link :href="route('shop.terminos')" class="hover:text-fg transition-colors">Términos de Servicio</Link>
        <Link :href="route('shop.privacidad')" class="hover:text-fg transition-colors">Política de Privacidad</Link>
        <a href="#" class="hover:text-fg transition-colors">Cookies</a>
      </div>
    </div>
  </footer>
</template>
