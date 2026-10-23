<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const emit = defineEmits(['close']);

defineProps({
  isOpen: Boolean,
});

// Estados del flujo
const tab = ref('register'); // 'register' | 'login'
const step = ref('email'); // 'email' | 'verify-send' | 'sent'
const email = ref('');
const password = ref('');
const showPassword = ref(false);
const acceptTerms = ref(false);
const acceptNewsletter = ref(false);
const acceptMarketing = ref(false);
const isLoading = ref(false);
const error = ref('');

// Paso 1: Validar email en BD
const handleEmailSubmit = async () => {
  if (!email.value) {
    error.value = 'Por favor ingresa un email válido';
    return;
  }

  if (!acceptTerms.value) {
    error.value = 'Debes aceptar los términos y condiciones';
    return;
  }

  isLoading.value = true;
  error.value = '';

  try {
    const response = await fetch('/api/auth/check-email', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({ email: email.value })
    });

    const data = await response.json();

    if (data.status === 'new') {
      // Email no existe - mostrar confirmación para enviar verificación
      step.value = 'verify-send';
    } else if (data.status === 'needs-password') {
      // Email existe sin contraseña - ir directo a crear contraseña
      router.visit(`/activate-account?email=${encodeURIComponent(email.value)}&skip-verification=true`);
    } else if (data.status === 'exists') {
      // Email ya existe con contraseña - cambiar a tab login
      tab.value = 'login';
      password.value = '';
    }
  } catch (e) {
    error.value = 'Error de conexión. Intenta de nuevo.';
    console.error('Error:', e);
  } finally {
    isLoading.value = false;
  }
};

// Paso 2: Confirmar y enviar email
const handleConfirmSend = async () => {
  isLoading.value = true;
  error.value = '';

  try {
    const response = await fetch('/api/auth/send-verification-email', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({
        email: email.value,
        acceptNewsletter: acceptNewsletter.value,
        acceptMarketing: acceptMarketing.value,
      })
    });

    const data = await response.json();

    if (!response.ok) {
      error.value = data.message || 'Error al enviar el correo. Intenta de nuevo.';
      step.value = 'verify-send';
      return;
    }

    step.value = 'sent';
  } catch (e) {
    error.value = 'Error de conexión. Intenta de nuevo.';
    step.value = 'verify-send';
    console.error('Error:', e);
  } finally {
    isLoading.value = false;
  }
};

// Volver al email
const goBackToEmail = () => {
  step.value = 'email';
  error.value = '';
};

// Login
const handleLogin = async () => {
  if (!email.value || !password.value) {
    error.value = 'Por favor completa email y contraseña';
    return;
  }

  isLoading.value = true;
  error.value = '';

  try {
    const response = await fetch('/auth/login-action', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({ email: email.value, password: password.value })
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      error.value = data.message || 'No se pudo iniciar sesión. Intenta de nuevo.';
      isLoading.value = false;
      return;
    }

    // Recarga completa: la sesión cambió y la página necesita el nuevo token CSRF y usuario.
    window.location.href = data.redirect;
  } catch (e) {
    error.value = 'Error de conexión. Intenta de nuevo.';
    isLoading.value = false;
    console.error('Error:', e);
  }
};

// Cambiar tab
const switchTab = (newTab) => {
  tab.value = newTab;
  step.value = 'email';
  password.value = '';
  error.value = '';
};

const closeAndReset = () => {
  tab.value = 'register';
  step.value = 'email';
  email.value = '';
  password.value = '';
  showPassword.value = false;
  acceptTerms.value = false;
  acceptNewsletter.value = false;
  acceptMarketing.value = false;
  error.value = '';
  isLoading.value = false;
  emit('close');
};
</script>

<template>
  <!-- Overlay -->
  <Transition name="fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 bg-black/50 z-40"
      @click="emit('close')"
    />
  </Transition>

  <!-- Modal deslizante desde la derecha -->
  <Transition name="slide-left">
    <div
      v-if="isOpen"
      class="fixed top-0 right-0 w-full max-w-md h-screen bg-[#111111] z-50 shadow-2xl overflow-y-auto"
    >
      <!-- Close Button & Tabs -->
      <div class="sticky top-0 bg-[#111111] z-10">
        <div class="px-8 py-6 flex justify-between items-center border-b border-gray-800">
          <div class="w-6"></div>
          <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest">Street Urban</h3>
          <button
            @click="emit('close')"
            class="p-2 hover:bg-gray-800 rounded-lg transition"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-800">
          <button
            @click="switchTab('register')"
            :class="[
              'flex-1 py-4 px-4 text-sm font-bold uppercase tracking-widest transition-all',
              tab === 'register'
                ? 'text-white border-b-2 border-white'
                : 'text-gray-400 hover:text-gray-300'
            ]"
          >
            Registrarse
          </button>
          <button
            @click="switchTab('login')"
            :class="[
              'flex-1 py-4 px-4 text-sm font-bold uppercase tracking-widest transition-all',
              tab === 'login'
                ? 'text-white border-b-2 border-white'
                : 'text-gray-400 hover:text-gray-300'
            ]"
          >
            Iniciar Sesión
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="px-8 py-12 space-y-8">

        <!-- TAB: REGISTRARSE -->
        <div v-if="tab === 'register'">

        <!-- PASO 1: Email -->
        <div v-if="step === 'email'" class="space-y-8">
          <div>
            <h2 class="text-4xl font-black text-white leading-tight mb-6">
              INICIA SESIÓN O<br />REGÍSTRATE.
            </h2>
            <p class="text-base text-gray-300 leading-relaxed">
              Accede a diseños exclusivos, experiencias, ofertas... ¡Y mucho más!
            </p>
          </div>

          <!-- OAuth Buttons -->
          <div class="space-y-3">
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-lg font-semibold text-sm transition flex items-center justify-center gap-3 shadow-sm">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
              <span>Facebook</span>
            </button>
            <button class="w-full bg-gray-800 hover:bg-gray-700 border-2 border-gray-700 text-white py-4 rounded-lg font-semibold text-sm transition flex items-center justify-center gap-3 shadow-sm">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC04"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
              </svg>
              <span>Google</span>
            </button>
          </div>

          <!-- Divider -->
          <div class="flex items-center gap-4">
            <div class="flex-1 h-px bg-gray-700"></div>
            <span class="text-xs font-semibold text-gray-400 tracking-widest">CORREO ELECTRÓNICO</span>
            <div class="flex-1 h-px bg-gray-700"></div>
          </div>

          <!-- Email Input -->
          <div>
            <input
              v-model="email"
              aria-label="Correo electrónico"
              type="email"
              placeholder="ejemplo@gmail.com"
              class="w-full bg-gradient-to-br from-gray-800 to-gray-700 border-2 border-gray-600 px-5 py-4 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-white focus:ring-2 focus:ring-white/20 transition duration-300 text-base font-medium"
            />
          </div>

          <!-- Checkboxes -->
          <div class="space-y-3">
            <!-- Terms Checkbox -->
            <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-800/50 transition">
              <input
                v-model="acceptTerms"
                type="checkbox"
                id="terms"
                class="w-5 h-5 mt-1 cursor-pointer accent-white border-2 border-gray-600 rounded"
              />
              <label for="terms" class="text-sm text-gray-300 cursor-pointer leading-relaxed">
                He leído y acepto los
                <a :href="route('shop.terminos')" class="text-white underline font-semibold hover:text-gray-200">Términos y Condiciones</a>
                y la
                <a :href="route('shop.privacidad')" class="text-white underline font-semibold hover:text-gray-200">Política de Privacidad</a>
                <span class="text-red-400">*</span>
              </label>
            </div>

            <!-- Newsletter Checkbox -->
            <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-800/50 transition">
              <input
                v-model="acceptNewsletter"
                type="checkbox"
                id="newsletter"
                class="w-5 h-5 mt-1 cursor-pointer accent-white border-2 border-gray-600 rounded"
              />
              <label for="newsletter" class="text-sm text-gray-300 cursor-pointer leading-relaxed">
                Deseo recibir noticias y ofertas especiales de Street Urban
              </label>
            </div>

            <!-- Marketing Checkbox -->
            <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-800/50 transition">
              <input
                v-model="acceptMarketing"
                type="checkbox"
                id="marketing"
                class="w-5 h-5 mt-1 cursor-pointer accent-white border-2 border-gray-600 rounded"
              />
              <label for="marketing" class="text-sm text-gray-300 cursor-pointer leading-relaxed">
                Quiero recibir publicidad personalizada en redes sociales
              </label>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="bg-red-500/20 border border-red-500 rounded-lg px-4 py-3 text-sm text-red-300">
            {{ error }}
          </div>

          <!-- Continue Button -->
          <button
            @click="handleEmailSubmit"
            :disabled="isLoading"
            class="w-full bg-white text-black py-4 font-bold tracking-widest uppercase hover:bg-gray-50 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition duration-300 flex items-center justify-between px-6 rounded-xl shadow-md text-sm"
          >
            <span v-if="!isLoading">Continuar</span>
            <span v-else>Enviando...</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
          </button>
        </div>

        <!-- PASO 2: Verify Send -->
        <div v-if="step === 'verify-send'" class="flex flex-col justify-center items-center py-8 space-y-8">
          <!-- Title -->
          <h2 class="text-3xl font-black text-white text-center leading-tight">
            CONFIRMA TU<br />CORREO
          </h2>

          <!-- Message -->
          <p class="text-base text-gray-300 text-center leading-relaxed">
            Te enviaremos un enlace de activación a:
          </p>

          <!-- Email Display -->
          <div class="w-full bg-gray-800/50 border border-gray-700 rounded-lg px-6 py-4">
            <p class="text-sm text-white font-semibold text-center">{{ email }}</p>
          </div>

          <!-- Confirmation Buttons -->
          <div class="w-full space-y-3">
            <button
              @click="handleConfirmSend"
              :disabled="isLoading"
              class="w-full bg-white text-black py-4 font-bold tracking-widest uppercase hover:bg-gray-50 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition duration-300 rounded-xl text-sm shadow-md"
            >
              {{ isLoading ? 'Enviando...' : 'Sí, Enviar Correo' }}
            </button>
            <button
              @click="goBackToEmail"
              :disabled="isLoading"
              class="w-full bg-gray-800 text-white py-4 font-bold tracking-widest uppercase hover:bg-gray-700 hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed transition duration-300 rounded-xl text-sm border border-gray-700"
            >
              No, Cambiar Email
            </button>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="w-full bg-red-500/20 border border-red-500 rounded-lg px-4 py-3 text-sm text-red-300">
            {{ error }}
          </div>
        </div>

        <!-- PASO 3: Sent Confirmation -->
        <div v-if="step === 'sent'" class="flex flex-col justify-center items-center py-8 space-y-8">
          <!-- Email Display -->
          <div class="w-full bg-gray-800/50 border border-gray-700 rounded-lg px-6 py-4 flex items-center gap-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400 flex-shrink-0">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5H4.5A2.25 2.25 0 002.25 6.75m19.5 0v-1.5a2.25 2.25 0 00-2.25-2.25H4.5a2.25 2.25 0 00-2.25 2.25v1.5m19.5 0h-19.5" />
            </svg>
            <p class="text-sm text-gray-300">{{ email }}</p>
          </div>

          <!-- Title -->
          <h2 class="text-3xl font-black text-white text-center leading-tight">
            ACTIVA TU<br />CUENTA
          </h2>

          <!-- Message -->
          <p class="text-base text-gray-300 text-center leading-relaxed">
            Te hemos enviado un correo electrónico con un enlace para activar tu cuenta. Haz clic en él y crea tu contraseña.
          </p>

          <!-- Confirmation Icon -->
          <div class="w-16 h-16 rounded-full bg-green-500/20 border-2 border-green-500 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-green-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <!-- Close Button -->
          <button
            @click="closeAndReset"
            class="w-full bg-white text-black py-4 font-bold tracking-widest uppercase hover:bg-gray-50 hover:shadow-lg transition duration-300 rounded-xl text-sm shadow-md"
          >
            Entendido
          </button>

          <!-- Help Text -->
          <p class="text-xs text-gray-500 text-center">
            ¿No recibes el correo? Revisa tu carpeta de spam o<br />
            <a href="#" class="text-blue-400 hover:text-blue-300 underline">contacta con soporte</a>
          </p>
        </div>

        </div> <!-- FIN TAB REGISTRARSE -->

        <!-- TAB: INICIAR SESIÓN -->
        <div v-if="tab === 'login'" class="space-y-8">
          <div>
            <h2 class="text-4xl font-black text-white leading-tight mb-6">
              INICIA<br />SESIÓN.
            </h2>
            <p class="text-base text-gray-300 leading-relaxed">
              Accede a tu cuenta y continúa comprando.
            </p>
          </div>

          <!-- Email Input -->
          <div>
            <label for="auth-modal-1" class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">
              Correo electrónico
            </label>
            <input id="auth-modal-1"
              v-model="email"
              type="email"
              placeholder="example@gmail.com"
              class="w-full bg-gradient-to-br from-gray-800 to-gray-700 border-2 border-gray-600 px-5 py-4 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-white focus:ring-2 focus:ring-white/20 transition duration-300 text-base font-medium hover:border-gray-500"
            />
          </div>

          <!-- Password Input -->
          <div>
            <label for="auth-modal-2" class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">
              Contraseña
            </label>
            <div class="relative">
              <input id="auth-modal-2"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Tu contraseña"
                class="w-full bg-gradient-to-br from-gray-800 to-gray-700 border-2 border-gray-600 px-5 py-4 pr-12 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-white focus:ring-2 focus:ring-white/20 transition duration-300 text-base font-medium hover:border-gray-500"
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

          <!-- Error Message -->
          <div v-if="error" class="bg-red-500/20 border border-red-500 rounded-lg px-4 py-3 text-sm text-red-300">
            {{ error }}
          </div>

          <!-- Login Button -->
          <button
            @click="handleLogin"
            :disabled="isLoading"
            class="w-full bg-white text-black py-4 font-bold tracking-widest uppercase hover:bg-gray-50 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition duration-300 rounded-xl text-sm shadow-md"
          >
            {{ isLoading ? 'Iniciando...' : 'Iniciar Sesión' }}
          </button>

          <!-- Forgot Password Link -->
          <div class="text-center">
            <a href="#" class="text-xs text-gray-400 hover:text-white underline transition">
              ¿Olvidaste tu contraseña?
            </a>
          </div>
        </div> <!-- FIN TAB LOGIN -->

      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-left-enter-active, .slide-left-leave-active {
  transition: transform 0.3s ease;
}

.slide-left-enter-from {
  transform: translateX(100%);
}

.slide-left-leave-to {
  transform: translateX(100%);
}

.email-input-wrapper {
  position: relative;
}

.email-input {
  width: 100%;
  padding: 14px 18px;
  font-size: 16px;
  border: 2px solid #333333;
  border-radius: 12px;
  background: #1a1a1a;
  color: #fff;
  outline: none;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

.email-input::placeholder {
  color: #666666;
}

.email-input:focus {
  border-color: #fff;
  background: #0f0f0f;
  box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
}

.email-input:hover:not(:focus) {
  border-color: #444444;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}
</style>
