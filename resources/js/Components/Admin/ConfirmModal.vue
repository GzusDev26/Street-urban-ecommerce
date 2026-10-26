<script setup>
import { useBloqueoScroll } from '@/useBloqueoScroll';
defineProps({
  title: { type: String, default: '¿Estás seguro?' },
  message: { type: String, default: '' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  variant: { type: String, default: 'danger' }, // 'danger' | 'primary'
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'close']);

useBloqueoScroll(); // la página de atrás no se mueve mientras está abierto
</script>

<template>
  <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="emit('close')">
    <div class="bg-surface rounded-2xl shadow-2xl w-full max-w-sm p-6">
      <div
        :class="[
          'w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4',
          variant === 'danger' ? 'bg-red-50 text-red-500' : 'bg-orange-50 text-[#ff8c42]',
        ]"
      >
        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
      </div>

      <h3 class="text-lg font-bold text-gray-900 text-center mb-2">{{ title }}</h3>
      <p class="text-sm text-gray-500 text-center mb-6">{{ message }}</p>

      <div class="flex gap-3">
        <button
          @click="emit('close')"
          :disabled="loading"
          class="flex-1 text-sm font-semibold text-gray-600 hover:bg-gray-100 disabled:opacity-50 py-2.5 rounded-xl border border-gray-200 transition-colors"
        >
          {{ cancelText }}
        </button>
        <button
          @click="emit('confirm')"
          :disabled="loading"
          :class="[
            'flex-1 text-sm font-bold text-white py-2.5 rounded-xl transition-colors disabled:opacity-50',
            variant === 'danger' ? 'bg-red-500 hover:bg-red-600' : 'bg-[#ff8c42] hover:bg-[#ff7a24]',
          ]"
        >
          {{ loading ? 'Procesando...' : confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>
