<template>
  <!-- Botón sol/luna. El estilo (tamaño, colores, hover) lo pone quien lo usa. -->
  <button
    type="button"
    :aria-label="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
    :title="isDark ? 'Modo claro' : 'Modo oscuro'"
    @click="toggleTheme"
  >
    <svg v-if="isDark" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
      <circle cx="12" cy="12" r="4" />
      <path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4L6 18M18 6l1.4-1.4" />
    </svg>
    <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M20 14.5A8 8 0 019.5 4a8 8 0 1010.5 10.5z" />
    </svg>
  </button>
</template>

<script setup>
import { useTheme } from '@/theme';

const { isDark, toggleTheme } = useTheme();
</script>
