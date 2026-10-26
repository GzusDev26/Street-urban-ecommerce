<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  icon: { type: String, default: 'fa-solid fa-pen' },
  submitText: { type: String, default: 'Guardar' },
  savingText: { type: String, default: 'Guardando...' },
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
  size: { type: String, default: 'md' }, // 'md' | 'lg'
  showFooter: { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'submit']);

// El fondo solo cierra el modal si el clic EMPEZÓ en el fondo. Si se pulsó sobre un elemento que desaparece
// al instante (ej. una opción de un menú), algunos navegadores registran el clic sobre el fondo y cerraban todo.
const pulsoEnElFondo = ref(false);

const alTeclear = (e) => {
  if (e.key === 'Escape') emit('close');
};

// Evita que la página de atrás se desplace mientras el modal está abierto.
onMounted(() => {
  document.addEventListener('keydown', alTeclear);
  document.body.style.overflow = 'hidden';
});
onBeforeUnmount(() => {
  document.removeEventListener('keydown', alTeclear);
  document.body.style.overflow = '';
});
</script>

<template>
  <div
    class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4"
    @mousedown="pulsoEnElFondo = $event.target === $event.currentTarget"
    @click.self="pulsoEnElFondo && emit('close')"
  >
    <form
      role="dialog"
      aria-modal="true"
      :aria-label="title"
      :class="[size === 'lg' ? 'sm:max-w-lg' : 'sm:max-w-md']"
      class="modal-panel bg-surface w-full rounded-t-3xl sm:rounded-3xl shadow-2xl ring-1 ring-black/5 flex flex-col max-h-[92vh]"
      @submit.prevent="emit('submit')"
    >
      <header class="flex items-start gap-4 px-6 pt-6 pb-5">
        <div class="w-11 h-11 rounded-2xl bg-orange-50 text-[#ff8c42] flex items-center justify-center flex-shrink-0">
          <i :class="icon"></i>
        </div>
        <div class="min-w-0 flex-1">
          <h2 class="text-lg font-bold text-gray-900 leading-tight">{{ title }}</h2>
          <p v-if="subtitle" class="text-sm text-gray-500 mt-0.5">{{ subtitle }}</p>
        </div>
        <button
          type="button"
          @click="emit('close')"
          aria-label="Cerrar"
          class="-mr-2 -mt-1 w-9 h-9 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center transition-colors"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </header>

      <div class="px-6 pb-6 space-y-4 overflow-y-auto">
        <slot />

        <div
          v-if="error"
          role="alert"
          class="flex items-start gap-2.5 bg-red-50 text-red-700 rounded-xl px-4 py-3 text-sm"
        >
          <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
          <span>{{ error }}</span>
        </div>
      </div>

      <footer v-if="showFooter" class="flex items-center justify-end gap-2 px-6 py-4 bg-gray-50/80 border-t border-gray-100 rounded-b-3xl">
        <button
          type="button"
          @click="emit('close')"
          :disabled="saving"
          class="text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-200/60 disabled:opacity-50 px-5 py-2.5 rounded-xl transition-colors"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="saving"
          class="flex items-center gap-2 bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold text-sm py-2.5 px-6 rounded-xl shadow-sm shadow-orange-200 transition-all"
        >
          <i v-if="saving" class="fa-solid fa-spinner fa-spin text-xs"></i>
          {{ saving ? savingText : submitText }}
        </button>
      </footer>
    </form>
  </div>
</template>

<style scoped>
.modal-panel {
  animation: modal-in 180ms cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modal-in {
  from {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
  }
}

@media (prefers-reduced-motion: reduce) {
  .modal-panel {
    animation: none;
  }
}
</style>
