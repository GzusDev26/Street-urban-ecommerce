<script setup>
import { ref } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineOptions({
  layout: AuthLayout,
});

const props = defineProps({
  token: String,
  email: String,
  isValid: Boolean,
  error: String,
  skipVerification: Boolean,
});

const name = ref('');
const phone = ref('');
const password = ref('');
const passwordConfirm = ref('');
const showPassword = ref(false);
const isLoading = ref(false);
const responseError = ref('');
const responseSuccess = ref('');
const showSplash = ref(false);

const handleActivate = async () => {
  if (!name.value || !password.value || !passwordConfirm.value) {
    responseError.value = 'Por favor completa los campos requeridos';
    return;
  }

  if (password.value !== passwordConfirm.value) {
    responseError.value = 'Las contraseñas no coinciden';
    return;
  }

  if (password.value.length < 8) {
    responseError.value = 'La contraseña debe tener al menos 8 caracteres';
    return;
  }

  isLoading.value = true;
  responseError.value = '';
  responseSuccess.value = '';

  try {
    const response = await fetch('/api/auth/activate-account', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({
        token: props.token,
        email: props.email,
        name: name.value,
        phone: phone.value || null,
        password: password.value,
        password_confirmation: passwordConfirm.value,
        skipVerification: props.skipVerification,
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      responseError.value = data.message || 'Error al activar la cuenta';
      isLoading.value = false;
      return;
    }

    responseSuccess.value = data.message;
    showSplash.value = true;
    setTimeout(() => {
      location.href = data.redirect || '/';
    }, 1800);
  } catch (e) {
    console.error('Falló la activación de la cuenta', e);
    responseError.value = 'Error de conexión. Intenta de nuevo.';
    isLoading.value = false;
  }
};
</script>

<template>
  <!-- Splash de bienvenida tras activar la cuenta -->
  <Transition name="fade">
    <div
      v-if="showSplash"
      class="fixed inset-0 z-50 bg-canvas flex flex-col items-center justify-center px-4"
    >
      <div class="w-16 h-16 rounded-full bg-green-500/20 border-2 border-green-500 flex items-center justify-center mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-green-500">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <h2 class="text-3xl font-black text-fg text-center mb-2">¡BIENVENIDO!</h2>
      <p class="text-gray-400 text-sm text-center">Iniciando sesión, un momento...</p>
    </div>
  </Transition>

  <div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-black mb-2 italic">STREET URBAN</h1>
        <p class="text-gray-400 text-sm tracking-widest uppercase">Activa tu cuenta</p>
      </div>

      <!-- Error al cargar -->
      <div v-if="!isValid" class="bg-red-500/20 border border-red-500 rounded-xl px-6 py-4 text-center mb-8">
        <p class="text-red-300 text-sm">{{ error || 'Token inválido o expirado' }}</p>
      </div>

      <!-- Formulario de activación -->
      <div v-if="isValid" class="space-y-6">
        <!-- Email Display (Read-only) -->
        <div>
          <span class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
            Correo electrónico
          </span>
          <div class="bg-gray-700 border border-gray-600 rounded-xl px-4 py-3 text-fg text-sm">
            {{ email }}
          </div>
        </div>

        <!-- Name Input -->
        <div>
          <label for="activate-account-1" class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
            Nombre completo *
          </label>
          <input id="activate-account-1"
            v-model="name"
            type="text"
            placeholder="Ej: Juan Pérez"
            class="w-full bg-gray-700 border border-gray-600 px-4 py-3 rounded-xl text-fg placeholder-gray-500 focus:outline-none focus:border-fg focus:ring-1 focus:ring-fg transition duration-300"
          />
        </div>

        <!-- Phone Input (Optional) -->
        <div>
          <label for="activate-account-2" class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
            Teléfono
            <span class="text-gray-600">(opcional)</span>
          </label>
          <input id="activate-account-2"
            v-model="phone"
            type="tel"
            placeholder="+51 999 123 456"
            class="w-full bg-gray-700 border border-gray-600 px-4 py-3 rounded-xl text-fg placeholder-gray-400 focus:outline-none focus:border-fg focus:ring-1 focus:ring-fg transition duration-300"
          />
        </div>

        <!-- Password Input -->
        <div>
          <label for="activate-account-3" class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
            Contraseña *
          </label>
          <div class="relative">
            <input id="activate-account-3"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Mínimo 8 caracteres"
              class="w-full bg-gray-700 border border-gray-600 px-4 py-3 pr-12 rounded-xl text-fg placeholder-gray-400 focus:outline-none focus:border-fg focus:ring-1 focus:ring-fg transition duration-300"
            />
            <button
              @click="showPassword = !showPassword"
              type="button"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition"
            >
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Confirm Password Input -->
        <div>
          <label for="activate-account-4" class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
            Confirmar contraseña *
          </label>
          <input id="activate-account-4"
            v-model="passwordConfirm"
            type="password"
            placeholder="Repite tu contraseña"
            class="w-full bg-gray-700 border border-gray-600 px-4 py-3 rounded-xl text-fg placeholder-gray-400 focus:outline-none focus:border-fg focus:ring-1 focus:ring-fg transition duration-300"
          />
        </div>

        <!-- Error Message -->
        <div v-if="responseError" class="bg-red-500/20 border border-red-500 rounded-xl px-4 py-3">
          <p class="text-red-300 text-sm">{{ responseError }}</p>
        </div>

        <!-- Success Message -->
        <div v-if="responseSuccess" class="bg-green-500/20 border border-green-500 rounded-xl px-4 py-3">
          <p class="text-green-300 text-sm">{{ responseSuccess }}</p>
        </div>

        <!-- Activate Button -->
        <button
          @click="handleActivate"
          :disabled="isLoading"
          class="w-full bg-fg text-canvas py-4 font-bold tracking-widest uppercase hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition duration-300 rounded-xl text-sm"
        >
          {{ isLoading ? 'ACTIVANDO...' : 'Activar Cuenta' }}
        </button>

        <!-- Help Text -->
        <p class="text-center text-xs text-gray-500">
          Este enlace expira en 24 horas
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
