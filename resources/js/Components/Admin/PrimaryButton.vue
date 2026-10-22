<script setup>
import { Link } from '@inertiajs/vue3';

// Con `href` se comporta como enlace de Inertia; sin él, como botón.
defineProps({
  href: { type: String, default: null },
  icon: { type: String, default: 'fa-solid fa-plus' },
});

const CLASES = 'bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white text-sm font-bold py-2.5 px-5 rounded-xl shadow-sm shadow-orange-200 transition-all flex items-center gap-2';
</script>

<template>
  <Link v-if="href" :href="href" :class="CLASES">
    <i :class="icon" class="text-xs"></i>
    <slot />
  </Link>
  <button v-else type="button" :class="CLASES">
    <i :class="icon" class="text-xs"></i>
    <slot />
  </button>
</template>
