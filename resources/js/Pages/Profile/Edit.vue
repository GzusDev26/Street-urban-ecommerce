<template>
  <Head title="Mi Perfil" />
  <AdminLayout>
    <template #breadcrumb>Cuenta</template>
    <template #header>Mi Perfil</template>

    <div class="max-w-6xl grid grid-cols-1 lg:grid-cols-[300px_minmax(0,1fr)] gap-6 items-start">
      <!-- Identidad + navegación de secciones -->
      <aside class="lg:sticky lg:top-0 bg-surface rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="h-20 bg-gradient-to-br from-[#ff8c42] to-[#e67e2d]" aria-hidden="true"></div>
        <div class="px-6 pb-6 -mt-12 text-center">
          <span class="relative inline-flex w-24 h-24 rounded-full ring-4 ring-surface bg-[#ff8c42] text-white text-3xl font-bold items-center justify-center overflow-hidden shadow-md">
            <img v-if="usuario.avatar_url" :src="usuario.avatar_url" :alt="`Avatar de ${usuario.name}`" class="w-full h-full object-cover" />
            <template v-else>{{ inicial }}</template>
          </span>
          <h2 class="mt-3 text-lg font-bold text-gray-900 truncate">{{ usuario.name }}</h2>
          <p class="text-sm text-gray-500 truncate">{{ usuario.email }}</p>

          <div class="mt-3 flex flex-wrap justify-center gap-2">
            <span :class="['inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full', esAdmin ? 'bg-orange-50 text-[#e67e2d]' : 'bg-blue-50 text-blue-600']">
              <i :class="esAdmin ? 'fa-solid fa-shield-halved' : 'fa-solid fa-cash-register'" class="text-[10px]" aria-hidden="true"></i>
              {{ esAdmin ? 'Administrador' : 'Vendedor' }}
            </span>
            <span
              :class="['inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full', usuario.email_verified_at ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700']"
            >
              <i :class="usuario.email_verified_at ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation'" class="text-[10px]" aria-hidden="true"></i>
              {{ usuario.email_verified_at ? 'Correo verificado' : 'Correo sin verificar' }}
            </span>
          </div>

          <p v-if="miembroDesde" class="mt-4 text-xs text-gray-400">Miembro desde {{ miembroDesde }}</p>
        </div>

        <nav class="border-t border-gray-100 p-2 hidden lg:block" aria-label="Secciones del perfil">
          <a
            v-for="s in SECCIONES"
            :key="s.id"
            :href="`#${s.id}`"
            :aria-current="seccionActiva === s.id ? 'true' : undefined"
            :class="[
              'flex items-center gap-3 h-11 px-3 rounded-xl text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]',
              seccionActiva === s.id ? 'bg-orange-50 text-[#e67e2d] font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
            ]"
            @click.prevent="irA(s.id)"
          >
            <i :class="[s.icon, 'w-4 text-center text-sm']" aria-hidden="true"></i>
            {{ s.label }}
          </a>
        </nav>
      </aside>

      <div class="space-y-6 min-w-0">
        <!-- Aviso del servidor -->
        <Transition
          enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
          enter-from-class="opacity-0 -translate-y-1"
          leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
          leave-to-class="opacity-0"
        >
          <div
            v-if="aviso"
            role="status"
            class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium"
          >
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span class="flex-1">{{ aviso }}</span>
            <button type="button" class="w-8 h-8 -mr-1 rounded-lg flex items-center justify-center hover:bg-green-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500" aria-label="Cerrar aviso" @click="aviso = null">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
        </Transition>

        <!-- Avatar -->
        <section id="avatar" class="scroll-mt-6 bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm" aria-labelledby="avatar-titulo">
          <header class="mb-5">
            <h3 id="avatar-titulo" class="text-lg font-bold text-gray-900">Avatar</h3>
            <p class="text-sm text-gray-500 mt-0.5">Así te verán en el panel y en las ventas que registres.</p>
          </header>

          <div v-if="avatares.length > 0" class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3" role="group" aria-label="Avatares disponibles">
            <button
              v-for="opcion in opcionesAvatar"
              :key="opcion.id ?? 'iniciales'"
              type="button"
              :aria-pressed="usuario.avatar_id === opcion.id"
              :aria-label="opcion.id === null ? 'Usar mis iniciales' : `Usar avatar ${opcion.n}`"
              :title="opcion.id === null ? 'Usar mis iniciales' : undefined"
              :disabled="guardandoAvatar !== false"
              :class="[
                'group relative aspect-square rounded-2xl transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42] focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:cursor-wait',
                usuario.avatar_id === opcion.id ? 'ring-2 ring-[#ff8c42] ring-offset-2 ring-offset-surface' : 'hover:-translate-y-0.5',
              ]"
              @click="elegirAvatar(opcion.id)"
            >
              <span class="absolute inset-0 rounded-2xl overflow-hidden bg-gray-100 flex items-center justify-center">
                <img v-if="opcion.url" :src="opcion.url" alt="" class="w-full h-full object-cover" loading="lazy" />
                <span v-else class="w-full h-full bg-[#ff8c42] text-white text-xl font-bold flex items-center justify-center">{{ inicial }}</span>
              </span>
              <span v-if="guardandoAvatar === opcion.id" class="absolute inset-0 rounded-2xl bg-black/40 flex items-center justify-center" aria-hidden="true">
                <i class="fa-solid fa-spinner fa-spin text-white"></i>
              </span>
              <span
                v-else-if="usuario.avatar_id === opcion.id"
                class="absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-[#ff8c42] text-white flex items-center justify-center ring-2 ring-surface"
                aria-hidden="true"
              >
                <i class="fa-solid fa-check text-[10px]"></i>
              </span>
            </button>
          </div>

          <div v-else class="flex items-center gap-4 rounded-xl bg-gray-50 border border-dashed border-gray-200 px-4 py-5">
            <span class="w-10 h-10 rounded-xl bg-surface text-gray-400 flex items-center justify-center flex-shrink-0 shadow-sm" aria-hidden="true">
              <i class="fa-solid fa-images"></i>
            </span>
            <p class="text-sm text-gray-500">
              Todavía no hay avatares. Un administrador puede subirlos desde
              <Link v-if="esAdmin" :href="route('admin.avatars.index')" class="font-semibold text-[#e67e2d] hover:underline">Avatares</Link>
              <span v-else class="font-semibold text-gray-700">Avatares</span>.
            </p>
          </div>
        </section>

        <!-- Datos personales -->
        <section id="datos" class="scroll-mt-6 bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm" aria-labelledby="datos-titulo">
          <header class="mb-5">
            <h3 id="datos-titulo" class="text-lg font-bold text-gray-900">Datos personales</h3>
            <p class="text-sm text-gray-500 mt-0.5">Tu nombre y correo dentro del panel.</p>
          </header>

          <form class="space-y-5" novalidate @submit.prevent="guardarPerfil">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <FormField label="Nombre completo">
                <FormInput v-model="perfilForm.name" type="text" icon="fa-regular fa-user" autocomplete="name" required :aria-invalid="!!erroresPerfil.name" />
                <p v-if="erroresPerfil.name" class="text-red-500 text-xs mt-1.5" role="alert">{{ erroresPerfil.name }}</p>
              </FormField>

              <FormField label="Correo electrónico">
                <FormInput v-model="perfilForm.email" type="email" icon="fa-regular fa-envelope" autocomplete="email" required :aria-invalid="!!erroresPerfil.email" />
                <p v-if="erroresPerfil.email" class="text-red-500 text-xs mt-1.5" role="alert">{{ erroresPerfil.email }}</p>
                <p v-else-if="cambiaEmail" class="text-amber-600 text-xs mt-1.5 flex items-start gap-1.5">
                  <i class="fa-solid fa-circle-info mt-0.5" aria-hidden="true"></i>
                  Al cambiar el correo tendrás que verificarlo de nuevo.
                </p>
              </FormField>
            </div>

            <div class="flex items-center gap-3 rounded-xl bg-gray-50 px-4 py-3">
              <i class="fa-solid fa-user-shield text-gray-400" aria-hidden="true"></i>
              <p class="text-sm text-gray-600">
                Tu rol es <span class="font-semibold text-gray-900">{{ esAdmin ? 'Administrador' : 'Vendedor' }}</span>.
                Solo un administrador puede cambiarlo, desde Usuarios.
              </p>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-1">
              <button
                v-if="perfilCambiado"
                type="button"
                class="h-11 px-5 rounded-xl border border-gray-200 bg-surface text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
                @click="descartarPerfil"
              >
                Descartar
              </button>
              <button
                type="submit"
                :disabled="!perfilCambiado || guardandoPerfil"
                class="h-11 px-6 rounded-xl bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42] focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
              >
                <i :class="guardandoPerfil ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-floppy-disk'" class="text-xs" aria-hidden="true"></i>
                {{ guardandoPerfil ? 'Guardando…' : 'Guardar cambios' }}
              </button>
            </div>
          </form>
        </section>

        <!-- Seguridad -->
        <section id="seguridad" class="scroll-mt-6 bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm" aria-labelledby="seguridad-titulo">
          <header class="mb-5">
            <h3 id="seguridad-titulo" class="text-lg font-bold text-gray-900">Contraseña</h3>
            <p class="text-sm text-gray-500 mt-0.5">Usa una contraseña larga que no uses en otro sitio.</p>
          </header>

          <form class="space-y-5" novalidate @submit.prevent="guardarPassword">
            <!-- Campo oculto para que el gestor de contraseñas asocie el cambio a esta cuenta -->
            <input type="text" :value="usuario.email" autocomplete="username" class="hidden" tabindex="-1" aria-hidden="true" readonly />

            <div v-for="campo in CAMPOS_PASSWORD" :key="campo.key" :class="campo.key === 'current_password' ? 'md:max-w-[calc(50%-0.625rem)]' : ''">
              <label :for="`pw-${campo.key}`" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ campo.label }}</label>
              <div class="relative">
                <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none" aria-hidden="true"></i>
                <input
                  :id="`pw-${campo.key}`"
                  v-model="passwordForm[campo.key]"
                  :type="visibles[campo.key] ? 'text' : 'password'"
                  :autocomplete="campo.autocomplete"
                  :aria-invalid="!!erroresPassword[campo.key]"
                  :aria-describedby="campo.key === 'password' ? 'pw-fuerza' : undefined"
                  class="w-full h-11 pl-10 pr-12 rounded-xl border border-gray-200 bg-surface text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-300 focus:outline-none focus:border-[#ff8c42] focus:ring-4 focus:ring-orange-100"
                />
                <button
                  type="button"
                  class="absolute right-1 top-1/2 -translate-y-1/2 w-10 h-10 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]"
                  :aria-label="visibles[campo.key] ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                  :aria-pressed="visibles[campo.key]"
                  @click="visibles[campo.key] = !visibles[campo.key]"
                >
                  <i :class="visibles[campo.key] ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'" class="text-sm" aria-hidden="true"></i>
                </button>
              </div>
              <p v-if="erroresPassword[campo.key]" class="text-red-500 text-xs mt-1.5" role="alert">{{ erroresPassword[campo.key] }}</p>

              <!-- Fuerza y requisitos, bajo la nueva contraseña -->
              <div v-if="campo.key === 'password' && passwordForm.password" id="pw-fuerza" class="mt-3 space-y-2.5">
                <div class="flex items-center gap-3">
                  <div class="flex-1 grid grid-cols-4 gap-1.5" aria-hidden="true">
                    <span v-for="i in 4" :key="i" :class="['h-1.5 rounded-full transition-colors duration-200', i <= fuerza.nivel ? fuerza.barra : 'bg-gray-200']"></span>
                  </div>
                  <span :class="['text-xs font-semibold w-20 text-right', fuerza.texto]">{{ fuerza.label }}</span>
                </div>
                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1">
                  <li v-for="r in requisitos" :key="r.label" :class="['flex items-center gap-2 text-xs', r.ok ? 'text-green-600' : 'text-gray-500']">
                    <i :class="r.ok ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'" class="text-[11px]" aria-hidden="true"></i>
                    {{ r.label }}<span class="sr-only">{{ r.ok ? ': cumplido' : ': pendiente' }}</span>
                  </li>
                </ul>
              </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-1">
              <p v-if="passwordGuardada" class="text-sm text-green-600 font-medium flex items-center gap-2 sm:mr-auto" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Contraseña actualizada.
              </p>
              <button
                type="submit"
                :disabled="!passwordLista || guardandoPassword"
                class="h-11 px-6 rounded-xl bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42] focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
              >
                <i :class="guardandoPassword ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-key'" class="text-xs" aria-hidden="true"></i>
                {{ guardandoPassword ? 'Guardando…' : 'Actualizar contraseña' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  avatares: Array,
});

const page = usePage();
// Reactivo: tras guardar, Inertia trae el usuario actualizado y la vista lo refleja.
const usuario = computed(() => page.props.auth.user);
const esAdmin = computed(() => usuario.value.role === 'admin');
const inicial = computed(() => usuario.value.name.charAt(0).toUpperCase());
const miembroDesde = computed(() => {
  if (!usuario.value.created_at) return null;
  return new Intl.DateTimeFormat('es-PE', { month: 'long', year: 'numeric' }).format(new Date(usuario.value.created_at));
});

// Aviso del servidor (perfil/avatar guardado); se puede cerrar y se renueva con cada respuesta.
const aviso = ref(page.props.flash?.success ?? null);
watch(() => page.props.flash?.success, (v) => { if (v) aviso.value = v; });

// --- Navegación por secciones --------------------------------------------------------
const SECCIONES = [
  { id: 'avatar', label: 'Avatar', icon: 'fa-regular fa-image' },
  { id: 'datos', label: 'Datos personales', icon: 'fa-regular fa-id-card' },
  { id: 'seguridad', label: 'Contraseña', icon: 'fa-solid fa-lock' },
];
const seccionActiva = ref('avatar');
let observer = null;

const irA = (id) => {
  const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.getElementById(id)?.scrollIntoView({ behavior: reducir ? 'auto' : 'smooth', block: 'start' });
  seccionActiva.value = id;
};

onMounted(() => {
  observer = new IntersectionObserver((entradas) => {
    const visible = entradas.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
    if (visible) seccionActiva.value = visible.target.id;
  }, { rootMargin: '0px 0px -60% 0px' });
  SECCIONES.forEach((s) => { const el = document.getElementById(s.id); if (el) observer.observe(el); });
});
onBeforeUnmount(() => observer?.disconnect());

// --- Avatar ------------------------------------------------------------------------------
const opcionesAvatar = computed(() => [
  { id: null, url: null },
  ...props.avatares.map((a, i) => ({ ...a, n: i + 1 })),
]);
const guardandoAvatar = ref(false); // false o el id que se está guardando (null = iniciales)

const elegirAvatar = (avatarId) => {
  if (usuario.value.avatar_id === avatarId) return;
  guardandoAvatar.value = avatarId;

  router.patch(route('profile.avatar.update'), { avatar_id: avatarId }, {
    preserveScroll: true,
    onFinish: () => {
      guardandoAvatar.value = false;
    },
  });
};

// --- Datos personales -------------------------------------------------------------------
const perfilForm = reactive({ name: usuario.value.name, email: usuario.value.email });
const erroresPerfil = ref({});
const guardandoPerfil = ref(false);

const perfilCambiado = computed(() =>
  perfilForm.name.trim() !== usuario.value.name || perfilForm.email.trim() !== usuario.value.email
);
const cambiaEmail = computed(() => perfilForm.email.trim() !== usuario.value.email);

const descartarPerfil = () => {
  perfilForm.name = usuario.value.name;
  perfilForm.email = usuario.value.email;
  erroresPerfil.value = {};
};

const guardarPerfil = () => {
  if (!perfilCambiado.value) return;
  guardandoPerfil.value = true;
  erroresPerfil.value = {};

  router.patch(route('profile.update'), { name: perfilForm.name.trim(), email: perfilForm.email.trim() }, {
    preserveScroll: true,
    onError: (errors) => {
      erroresPerfil.value = errors;
    },
    onFinish: () => {
      guardandoPerfil.value = false;
    },
  });
};

// --- Contraseña ---------------------------------------------------------------------------
const CAMPOS_PASSWORD = [
  { key: 'current_password', label: 'Contraseña actual', autocomplete: 'current-password' },
  { key: 'password', label: 'Nueva contraseña', autocomplete: 'new-password' },
  { key: 'password_confirmation', label: 'Confirmar nueva contraseña', autocomplete: 'new-password' },
];
const MIN_PASSWORD = 8; // Password::defaults() de Laravel

const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });
const visibles = reactive({ current_password: false, password: false, password_confirmation: false });
const erroresPassword = ref({});
const guardandoPassword = ref(false);
const passwordGuardada = ref(false);

const requisitos = computed(() => {
  const p = passwordForm.password;
  return [
    { label: `Al menos ${MIN_PASSWORD} caracteres`, ok: p.length >= MIN_PASSWORD },
    { label: 'Mayúsculas y minúsculas', ok: /[a-z]/.test(p) && /[A-Z]/.test(p) },
    { label: 'Al menos un número', ok: /\d/.test(p) },
    { label: 'Las contraseñas coinciden', ok: p.length > 0 && p === passwordForm.password_confirmation },
  ];
});

const NIVELES = [
  { label: 'Muy débil', barra: 'bg-red-500', texto: 'text-red-600' },
  { label: 'Débil', barra: 'bg-red-500', texto: 'text-red-600' },
  { label: 'Aceptable', barra: 'bg-amber-500', texto: 'text-amber-600' },
  { label: 'Buena', barra: 'bg-green-500', texto: 'text-green-600' },
  { label: 'Fuerte', barra: 'bg-green-500', texto: 'text-green-600' },
];
const fuerza = computed(() => {
  const p = passwordForm.password;
  let nivel = 0;
  if (p.length >= MIN_PASSWORD) nivel++;
  if (p.length >= 12) nivel++;
  if (/[a-z]/.test(p) && /[A-Z]/.test(p)) nivel++;
  if (/\d/.test(p) && /[^A-Za-z0-9]/.test(p)) nivel++;
  if (p.length < MIN_PASSWORD) nivel = Math.min(nivel, 1);
  return { nivel, ...NIVELES[nivel] };
});

// Lo mínimo que acepta el servidor; las otras pistas solo orientan.
const passwordLista = computed(() =>
  passwordForm.current_password.length > 0
  && passwordForm.password.length >= MIN_PASSWORD
  && passwordForm.password === passwordForm.password_confirmation
);

watch(() => [passwordForm.current_password, passwordForm.password], () => { passwordGuardada.value = false; });

const guardarPassword = () => {
  if (!passwordLista.value) return;
  guardandoPassword.value = true;
  erroresPassword.value = {};
  passwordGuardada.value = false;

  router.put(route('password.update'), { ...passwordForm }, {
    preserveScroll: true,
    onSuccess: () => {
      Object.assign(passwordForm, { current_password: '', password: '', password_confirmation: '' });
      Object.assign(visibles, { current_password: false, password: false, password_confirmation: false });
      passwordGuardada.value = true;
    },
    onError: (errors) => {
      erroresPassword.value = errors;
    },
    onFinish: () => {
      guardandoPassword.value = false;
    },
  });
};
</script>
