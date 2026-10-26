<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const emit = defineEmits(['close']);

const props = defineProps({
  isOpen: Boolean,
});

// Flujo "primero el correo": el servidor dice si la cuenta existe y se muestra solo el paso que toca.
// 'email' → 'password' (ya tiene cuenta) | 'register' (correo nuevo) → 'sent'
const step = ref('email');
const email = ref('');
const password = ref('');
const showPassword = ref(false);
const acceptTerms = ref(false);
const acceptNewsletter = ref(false);
const acceptMarketing = ref(false);
const isLoading = ref(false);
const error = ref('');
const emailError = ref('');
const termsError = ref('');

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


const inicial = computed(() => (email.value.trim()[0] || '?').toUpperCase());

// Aviso de Bloq Mayús: la causa más común de "contraseña incorrecta".
const mayusActivas = ref(false);
const detectarMayus = (e) => {
  if (typeof e.getModifierState === 'function') mayusActivas.value = e.getModifierState('CapsLock');
};

const PASOS_ACTIVACION = ['Abre el correo de Street Urban', 'Toca el enlace de activación', 'Elige tu contraseña y listo'];

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

// --- Comportamiento de diálogo: Esc cierra, foco al campo del paso y sin scroll del fondo ---
const panel = ref(null);
const onKeydown = (e) => {
  if (e.key === 'Escape') emit('close');
};
const focusField = async () => {
  await nextTick();
  panel.value?.querySelector('[data-autofocus]')?.focus({ preventScroll: true });
};

watch(() => props.isOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : '';
  if (open) {
    document.addEventListener('keydown', onKeydown);
    focusField();
  } else {
    document.removeEventListener('keydown', onKeydown);
  }
}, { immediate: true });
onBeforeUnmount(() => {
  document.body.style.overflow = '';
  document.removeEventListener('keydown', onKeydown);
});

const goTo = (next) => {
  step.value = next;
  error.value = '';
  focusField();
};

// --- Paso 1: correo ----------------------------------------------------------------------
const validateEmail = () => {
  const valor = email.value.trim();
  emailError.value = !valor
    ? 'Escribe tu correo electrónico.'
    : !EMAIL_RE.test(valor)
      ? 'Revisa tu correo, parece incompleto (ej. nombre@gmail.com).'
      : '';
  return !emailError.value;
};

const handleEmailSubmit = async () => {
  email.value = email.value.trim();
  if (!validateEmail()) {
    focusField();
    return;
  }

  isLoading.value = true;
  error.value = '';

  try {
    const response = await fetch('/api/auth/check-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrf() },
      body: JSON.stringify({ email: email.value }),
    });
    const data = await response.json();

    // Antes un 422 (correo inválido para el servidor) no mostraba nada.
    if (!response.ok) {
      emailError.value = data.errors?.email?.[0] || data.message || 'No pudimos revisar ese correo. Intenta de nuevo.';
      return;
    }

    if (data.status === 'exists') {
      password.value = '';
      goTo('password');
    } else if (data.status === 'new') {
      goTo('register');
    } else if (data.status === 'needs-password') {
      // Ya tiene ficha de cliente (compró en tienda) pero aún no creó contraseña.
      router.visit(`/activate-account?email=${encodeURIComponent(email.value)}&skip-verification=true`);
    }
  } catch (e) {
    error.value = 'Error de conexión. Intenta de nuevo.';
    console.error('Error:', e);
  } finally {
    isLoading.value = false;
  }
};

// --- Paso 2a: ya tiene cuenta → contraseña ---------------------------------------------------
const handleLogin = async () => {
  if (!password.value) {
    error.value = 'Escribe tu contraseña.';
    focusField();
    return;
  }

  isLoading.value = true;
  error.value = '';

  try {
    const response = await fetch('/auth/login-action', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrf() },
      body: JSON.stringify({ email: email.value, password: password.value }),
    });
    const data = await response.json();

    if (!response.ok || !data.success) {
      error.value = data.message || 'No se pudo iniciar sesión. Intenta de nuevo.';
      isLoading.value = false;
      focusField();
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

// --- Paso 2b: correo nuevo → términos y enlace de activación ---------------------------------
watch(acceptTerms, (v) => { if (v) termsError.value = ''; });

const handleConfirmSend = async () => {
  if (!acceptTerms.value) {
    termsError.value = 'Para crear tu cuenta necesitas aceptar los términos.';
    return;
  }

  isLoading.value = true;
  error.value = '';

  try {
    const response = await fetch('/api/auth/send-verification-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrf() },
      body: JSON.stringify({
        email: email.value,
        acceptNewsletter: acceptNewsletter.value,
        acceptMarketing: acceptMarketing.value,
      }),
    });
    const data = await response.json();

    if (!response.ok) {
      error.value = data.message || 'Error al enviar el correo. Intenta de nuevo.';
      return;
    }

    goTo('sent');
  } catch (e) {
    error.value = 'Error de conexión. Intenta de nuevo.';
    console.error('Error:', e);
  } finally {
    isLoading.value = false;
  }
};

const changeEmail = () => {
  mayusActivas.value = false;
  password.value = '';
  showPassword.value = false;
  termsError.value = '';
  goTo('email');
};

const closeAndReset = () => {
  step.value = 'email';
  email.value = '';
  password.value = '';
  showPassword.value = false;
  acceptTerms.value = false;
  acceptNewsletter.value = false;
  acceptMarketing.value = false;
  error.value = '';
  emailError.value = '';
  termsError.value = '';
  isLoading.value = false;
  emit('close');
};
</script>


<template>
  <!-- Fondo -->
  <Transition name="fade">
    <div v-if="isOpen" class="fixed inset-0 bg-black/60 z-40" aria-hidden="true" @click="emit('close')" />
  </Transition>

  <!-- Panel deslizante desde la derecha -->
  <Transition name="drawer">
    <div
      v-if="isOpen"
      ref="panel"
      role="dialog"
      aria-modal="true"
      aria-labelledby="auth-titulo"
      class="fixed top-0 right-0 w-full max-w-[440px] h-[100dvh] bg-canvas text-fg z-50 shadow-2xl shadow-black/50 flex flex-col"
    >
      <!-- Barra superior: volver / cerrar -->
      <div class="flex-shrink-0 h-16 px-3 flex items-center justify-between">
        <button
          v-if="step === 'password' || step === 'register'"
          type="button"
          class="auth-icon-btn"
          aria-label="Volver y cambiar el correo"
          @click="changeEmail"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        </button>
        <span v-else aria-hidden="true"></span>
        <button type="button" class="auth-icon-btn" aria-label="Cerrar" @click="emit('close')">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto overscroll-contain flex flex-col">
        <div class="px-7 sm:px-10 pt-2 pb-10">
          <img src="/image/logo/street-urban-logo-light.png" alt="Street Urban" width="1608" height="874" class="h-24 w-auto mx-auto dark:hidden" />
          <img src="/image/logo/street-urban-logo-dark.png" alt="Street Urban" width="1608" height="874" class="h-24 w-auto mx-auto hidden dark:block" />

          <Transition name="step" mode="out-in">
            <!-- ===== Correo ===== -->
            <form v-if="step === 'email'" key="email" class="mt-10" novalidate @submit.prevent="handleEmailSubmit">
              <p class="auth-kicker">Tu cuenta</p>
              <h2 id="auth-titulo" class="auth-title">Inicia sesión<br />o regístrate</h2>
              <p class="mt-3 text-sm text-gray-400 leading-relaxed">Ingresa tu correo y te llevamos al paso que sigue.</p>

              <label for="auth-email" class="auth-label mt-8">Correo electrónico</label>
              <input
                id="auth-email"
                v-model="email"
                data-autofocus
                type="email"
                inputmode="email"
                autocomplete="email"
                autocapitalize="off"
                spellcheck="false"
                placeholder="nombre@correo.com"
                :aria-invalid="!!emailError"
                :aria-describedby="emailError ? 'auth-email-error' : undefined"
                :class="['auth-input px-4', { 'auth-input--error': emailError }]"
                @blur="email && validateEmail()"
                @input="emailError && validateEmail()"
              />
              <p v-if="emailError" id="auth-email-error" class="auth-field-error" role="alert">{{ emailError }}</p>
              <p v-if="error" role="alert" class="auth-field-error">{{ error }}</p>

              <button type="submit" :disabled="isLoading" class="auth-primary mt-6">
                <span v-if="isLoading" class="auth-spinner" aria-hidden="true"></span>
                {{ isLoading ? 'Revisando' : 'Continuar' }}
              </button>

              <div class="mt-8 flex items-center gap-4 text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500" aria-hidden="true">
                <span class="flex-1 h-px bg-gray-800"></span>o<span class="flex-1 h-px bg-gray-800"></span>
              </div>

              <div class="mt-8 grid grid-cols-2 gap-3">
                <button type="button" class="auth-secondary">
                  <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC04"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                  </svg>
                  Google
                </button>
                <button type="button" class="auth-secondary">
                  <svg class="w-[18px] h-[18px] text-[#1877F2]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                  </svg>
                  Facebook
                </button>
              </div>
            </form>

            <!-- ===== Contraseña (ya tiene cuenta) ===== -->
            <form v-else-if="step === 'password'" key="password" class="mt-10" novalidate @submit.prevent="handleLogin">
              <p class="auth-kicker">Bienvenido</p>
              <h2 id="auth-titulo" class="auth-title">Hola<br />de nuevo</h2>

              <div class="auth-account mt-6">
                <span class="auth-avatar" aria-hidden="true">{{ inicial }}</span>
                <span class="min-w-0 flex-1">
                  <span class="block text-[11px] font-semibold uppercase tracking-[0.15em] text-gray-500">Entrando como</span>
                  <span class="block truncate font-semibold" :title="email">{{ email }}</span>
                </span>
                <button type="button" class="auth-account-btn" @click="changeEmail">Cambiar</button>
              </div>

              <div class="mt-8 mb-2 flex items-baseline justify-between">
                <label for="auth-password" class="auth-label !mb-0">Contraseña</label>
                <a href="#" class="auth-inline-link">¿La olvidaste?</a>
              </div>
              <!-- El correo en un campo oculto deja que el gestor de contraseñas asocie la cuenta -->
              <input type="email" :value="email" autocomplete="username" class="hidden" tabindex="-1" aria-hidden="true" readonly />
              <div class="relative">
                <input
                  id="auth-password"
                  v-model="password"
                  data-autofocus
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  :aria-invalid="!!error"
                  @keydown="detectarMayus"
                  @keyup="detectarMayus"
                  @blur="mayusActivas = false"
                  :aria-describedby="error ? 'auth-password-error' : undefined"
                  :class="['auth-input pl-4 pr-14', { 'auth-input--error': error }]"
                />
                <button
                  type="button"
                  class="absolute right-1 top-1/2 -translate-y-1/2 w-11 h-11 rounded-md flex items-center justify-center text-gray-500 hover:text-fg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
                  :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                  :aria-pressed="showPassword"
                  @click="showPassword = !showPassword"
                >
                  <svg v-if="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                  </svg>
                </button>
              </div>
              <p v-if="error" id="auth-password-error" class="auth-field-error" role="alert">{{ error }}</p>
              <p v-else-if="mayusActivas" class="mt-2 flex items-center gap-1.5 text-[13px] text-amber-500" role="status">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" /></svg>
                Bloq Mayús está activado
              </p>

              <button type="submit" :disabled="isLoading" class="auth-primary mt-6">
                <span v-if="isLoading" class="auth-spinner" aria-hidden="true"></span>
                {{ isLoading ? 'Entrando' : 'Iniciar sesión' }}
              </button>

              <p class="mt-6 text-center text-sm text-gray-500">
                ¿No eres tú? <button type="button" class="auth-inline-link !text-sm" @click="changeEmail">Usa otro correo</button>
              </p>
            </form>

            <!-- ===== Cuenta nueva ===== -->
            <form v-else-if="step === 'register'" key="register" class="mt-10" novalidate @submit.prevent="handleConfirmSend">
              <p class="auth-kicker">Cuenta nueva</p>
              <h2 id="auth-titulo" class="auth-title">Crea tu<br />cuenta</h2>

              <p class="mt-4 text-sm text-gray-400 leading-relaxed">Te enviaremos un enlace a este correo para que elijas tu contraseña.</p>

              <div class="auth-account mt-5">
                <span class="auth-avatar" aria-hidden="true">{{ inicial }}</span>
                <span class="min-w-0 flex-1 truncate font-semibold" :title="email">{{ email }}</span>
                <button type="button" class="auth-account-btn" @click="changeEmail">Cambiar</button>
              </div>

              <fieldset class="mt-8 border-t border-gray-800">
                <legend class="sr-only">Condiciones y preferencias</legend>
                <label class="auth-check">
                  <input v-model="acceptTerms" data-autofocus type="checkbox" class="auth-checkbox" :aria-invalid="!!termsError" :aria-describedby="termsError ? 'auth-terms-error' : undefined" />
                  <span>Acepto los <a :href="route('shop.terminos')" target="_blank" rel="noopener" class="auth-link">Términos</a> y la <a :href="route('shop.privacidad')" target="_blank" rel="noopener" class="auth-link">Política de Privacidad</a><span class="text-[#ff8c42]" aria-hidden="true">&nbsp;*</span></span>
                </label>
                <p v-if="termsError" id="auth-terms-error" class="auth-field-error !mt-0 pb-3 pl-8" role="alert">{{ termsError }}</p>
                <label class="auth-check">
                  <input v-model="acceptNewsletter" type="checkbox" class="auth-checkbox" />
                  <span>Quiero recibir novedades y ofertas por correo</span>
                </label>
                <label class="auth-check">
                  <input v-model="acceptMarketing" type="checkbox" class="auth-checkbox" />
                  <span>Acepto publicidad personalizada en redes sociales</span>
                </label>
              </fieldset>

              <p v-if="error" role="alert" class="auth-field-error">{{ error }}</p>

              <button type="submit" :disabled="isLoading" class="auth-primary mt-6">
                <span v-if="isLoading" class="auth-spinner" aria-hidden="true"></span>
                {{ isLoading ? 'Enviando' : 'Crear cuenta' }}
              </button>
            </form>

            <!-- ===== Enlace enviado ===== -->
            <div v-else key="sent" class="mt-10">
              <p class="auth-kicker">Casi listo</p>
              <h2 id="auth-titulo" class="auth-title">Revisa tu<br />correo</h2>
              <p class="mt-4 text-sm text-gray-400 leading-relaxed">
                Enviamos un enlace de activación a <span class="font-semibold text-fg break-all">{{ email }}</span>.
              </p>

              <ol class="mt-8 border-t border-gray-800">
                <li v-for="(paso, i) in PASOS_ACTIVACION" :key="paso" class="flex items-center gap-4 py-4 border-b border-gray-800 text-sm">
                  <span class="w-6 font-black italic text-[#ff8c42]">{{ String(i + 1).padStart(2, '0') }}</span>
                  {{ paso }}
                </li>
              </ol>

              <button type="button" class="auth-primary mt-8" @click="closeAndReset">Entendido</button>
              <p class="mt-4 text-center text-xs text-gray-500">
                ¿No te llegó? Revisa spam o <button type="button" class="auth-inline-link" @click="changeEmail">usa otro correo</button>
              </p>
            </div>
          </Transition>
        </div>
      </div>

      <p class="flex-shrink-0 px-6 py-4 text-center text-[11px] text-gray-500">
        Tus datos están protegidos y nunca los compartimos.
      </p>
    </div>
  </Transition>
</template>

<style scoped>
/* Fondo y panel */
.fade-enter-active { transition: opacity 0.3s ease; }
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.drawer-enter-active { transition: transform 0.36s cubic-bezier(0.22, 1, 0.36, 1); }
.drawer-leave-active { transition: transform 0.22s ease-in; }
.drawer-enter-from, .drawer-leave-to { transform: translateX(100%); }

/* Cambio de paso: entra desde la derecha y sale más rápido */
.step-enter-active { transition: opacity 0.22s ease-out, transform 0.22s ease-out; }
.step-leave-active { transition: opacity 0.12s ease-in; }
.step-enter-from { opacity: 0; transform: translateX(12px); }
.step-leave-to { opacity: 0; }

@media (prefers-reduced-motion: reduce) {
  .fade-enter-active, .fade-leave-active, .drawer-enter-active, .drawer-leave-active,
  .step-enter-active, .step-leave-active { transition: none; }
}

.auth-icon-btn {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgb(var(--gray-400));
  transition: color 150ms ease, background-color 150ms ease;
}
.auth-icon-btn:hover { color: rgb(var(--fg)); background: rgb(var(--fg) / 0.06); }
.auth-icon-btn:focus-visible { outline: 2px solid #ff8c42; outline-offset: 1px; }

/* Misma voz que la tienda: antetítulo naranja espaciado + titular en negrita cursiva */
.auth-kicker {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  color: #ff8c42;
}
.auth-title {
  margin-top: 0.75rem;
  font-size: 2.5rem;
  font-weight: 900;
  font-style: italic;
  text-transform: uppercase;
  line-height: 0.95;
  letter-spacing: -0.03em;
}

.auth-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: rgb(var(--gray-400));
}

/* Sin padding horizontal aquí: lo ponen las clases px-4 / pl-4 pr-14 de cada campo. */
.auth-input {
  width: 100%;
  height: 3.5rem;
  border-radius: 0.5rem;
  border: 1px solid rgb(var(--gray-700));
  background: transparent;
  color: rgb(var(--fg));
  font-size: 1rem;
  transition: border-color 150ms ease;
}
.auth-input::placeholder { color: rgb(var(--gray-600)); }
.auth-input:hover { border-color: rgb(var(--gray-500)); }
.auth-input:focus { outline: none; border-color: rgb(var(--fg)); box-shadow: inset 0 0 0 1px rgb(var(--fg)); }
.auth-input--error, .auth-input--error:hover, .auth-input--error:focus { border-color: rgb(239 68 68); box-shadow: inset 0 0 0 1px rgb(239 68 68); }

.auth-field-error {
  margin-top: 0.5rem;
  font-size: 0.8125rem;
  color: rgb(var(--red-400));
}

.auth-inline-link {
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgb(var(--gray-400));
  text-decoration: underline;
  text-underline-offset: 3px;
  transition: color 150ms ease;
}
.auth-inline-link:hover { color: rgb(var(--fg)); }

.auth-account {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.75rem 0.5rem 0.75rem 0.75rem;
  border-radius: 0.75rem;
  border: 1px solid rgb(var(--gray-800));
  background: rgb(var(--surface));
  font-size: 0.9375rem;
}
.auth-avatar {
  width: 2.5rem;
  height: 2.5rem;
  flex-shrink: 0;
  border-radius: 9999px;
  background: #ff8c42;
  color: #000;
  font-weight: 900;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.auth-account-btn {
  flex-shrink: 0;
  height: 2.5rem;
  padding: 0 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 700;
  color: rgb(var(--fg));
  transition: background-color 150ms ease;
}
.auth-account-btn:hover { background: rgb(var(--fg) / 0.08); }
.auth-account-btn:focus-visible { outline: 2px solid #ff8c42; outline-offset: 1px; }

.auth-check {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
  padding: 1rem 0;
  border-bottom: 1px solid rgb(var(--gray-800));
  font-size: 0.875rem;
  line-height: 1.5;
  color: rgb(var(--gray-300));
  cursor: pointer;
}

.auth-checkbox {
  width: 1.125rem;
  height: 1.125rem;
  margin-top: 0.15rem;
  flex-shrink: 0;
  border-radius: 0.25rem;
  border: 1.5px solid rgb(var(--gray-600));
  background-color: transparent;
  color: #ff8c42;
  cursor: pointer;
}
.auth-checkbox:focus { outline: none; box-shadow: 0 0 0 3px rgb(255 140 66 / 0.35); }
.auth-checkbox[aria-invalid='true'] { border-color: rgb(239 68 68); }

.auth-link {
  font-weight: 600;
  color: rgb(var(--fg));
  text-decoration: underline;
  text-underline-offset: 3px;
}
.auth-link:hover { color: #ff8c42; }

/* CTA como los de la tienda ("Comprar ahora"): naranja plano, mayúsculas espaciadas */
.auth-primary {
  width: 100%;
  height: 3.5rem;
  border-radius: 0.5rem;
  background: #ff8c42;
  color: #000;
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  transition: background-color 150ms ease;
}
.auth-primary:hover:not(:disabled) { background: #ffb380; }
.auth-primary:disabled { opacity: 0.6; cursor: wait; }
.auth-primary:focus-visible { outline: 2px solid rgb(var(--fg)); outline-offset: 3px; }

.auth-secondary {
  height: 3rem;
  border-radius: 0.5rem;
  border: 1px solid rgb(var(--gray-700));
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  transition: border-color 150ms ease;
}
.auth-secondary:hover { border-color: rgb(var(--fg)); }
.auth-secondary:focus-visible { outline: 2px solid #ff8c42; outline-offset: 2px; }

.auth-spinner {
  width: 1rem;
  height: 1rem;
  border-radius: 9999px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  animation: auth-spin 0.7s linear infinite;
}
@keyframes auth-spin { to { transform: rotate(360deg); } }
</style>
