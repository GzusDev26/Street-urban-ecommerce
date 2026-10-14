<template>
  <Head title="Mi Perfil" />
  <AdminLayout>
    <template #breadcrumb>Cuenta</template>
    <template #header>Mi Perfil</template>

    <div class="max-w-2xl space-y-6">
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>

      <!-- Avatar -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-1">
          <i class="fa-solid fa-images text-gray-400 text-sm"></i>
          <h3 class="text-lg font-bold text-gray-900">Avatar</h3>
        </div>
        <p class="text-xs text-gray-400 mb-6">Elige uno de los avatares que subió tu administrador</p>

        <div v-if="avatares.length > 0" class="flex flex-wrap gap-3">
          <button
            type="button"
            @click="elegirAvatar(null)"
            :disabled="guardandoAvatar"
            :class="[
              'w-14 h-14 rounded-full border-2 flex items-center justify-center bg-[#ff8c42] text-white font-bold transition-all disabled:opacity-50',
              usuario.avatar_id === null ? 'border-gray-900 ring-2 ring-offset-2 ring-[#ff8c42]' : 'border-transparent hover:border-gray-300',
            ]"
            title="Sin avatar (iniciales)"
          >
            {{ usuario.name.charAt(0).toUpperCase() }}
          </button>
          <button
            v-for="avatar in avatares"
            :key="avatar.id"
            type="button"
            @click="elegirAvatar(avatar.id)"
            :disabled="guardandoAvatar"
            :class="[
              'w-14 h-14 rounded-full border-2 overflow-hidden transition-all disabled:opacity-50',
              usuario.avatar_id === avatar.id ? 'border-gray-900 ring-2 ring-offset-2 ring-[#ff8c42]' : 'border-transparent hover:border-gray-300',
            ]"
          >
            <img :src="avatar.url" alt="Avatar" class="w-full h-full object-cover" />
          </button>
        </div>

        <p v-else class="text-sm text-gray-400">
          Todavía no hay avatares disponibles — pídele a un administrador que suba algunos desde Configuración.
        </p>
      </div>

      <!-- Información Personal -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-1">
          <i class="fa-solid fa-circle-user text-gray-400 text-sm"></i>
          <h3 class="text-lg font-bold text-gray-900">Información Personal</h3>
        </div>
        <p class="text-xs text-gray-400 mb-6">Tu nombre y correo dentro del panel</p>

        <form @submit.prevent="guardarPerfil" class="space-y-5">
          <div>
            <label for="edit-1" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
            <input id="edit-1"
              v-model="perfilForm.name"
              type="text"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
              required
            />
            <p v-if="erroresPerfil.name" class="text-red-500 text-xs mt-1">{{ erroresPerfil.name[0] }}</p>
          </div>

          <div>
            <label for="edit-2" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input id="edit-2"
              v-model="perfilForm.email"
              type="email"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
              required
            />
            <p v-if="erroresPerfil.email" class="text-red-500 text-xs mt-1">{{ erroresPerfil.email[0] }}</p>
          </div>

          <div>
            <span class="block text-sm font-medium text-gray-700 mb-2">Rol</span>
            <span
              :class="[
                'inline-block text-xs font-semibold px-2.5 py-1 rounded-full',
                usuario.role === 'admin' ? 'bg-orange-100 text-[#ff8c42]' : 'bg-blue-100 text-blue-600',
              ]"
            >
              {{ usuario.role === 'admin' ? 'Administrador' : 'Vendedor' }}
            </span>
            <p class="text-[11px] text-gray-400 mt-1.5">Solo un administrador puede cambiar roles, desde Usuarios.</p>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="guardandoPerfil"
              class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:hover:translate-y-0 text-white font-bold text-sm py-2.5 px-6 rounded-xl transition-all duration-200 shadow-sm"
            >
              <i class="fa-solid fa-save"></i> {{ guardandoPerfil ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Cambiar Contraseña -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-1">
          <i class="fa-solid fa-lock text-gray-400 text-sm"></i>
          <h3 class="text-lg font-bold text-gray-900">Cambiar Contraseña</h3>
        </div>
        <p class="text-xs text-gray-400 mb-6">Usa una contraseña larga que no uses en otro lado</p>

        <form @submit.prevent="guardarPassword" class="space-y-5">
          <div>
            <label for="edit-3" class="block text-sm font-medium text-gray-700 mb-2">Contraseña actual</label>
            <input id="edit-3"
              v-model="passwordForm.current_password"
              type="password"
              autocomplete="current-password"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
            />
            <p v-if="erroresPassword.current_password" class="text-red-500 text-xs mt-1">{{ erroresPassword.current_password[0] }}</p>
          </div>

          <div>
            <label for="edit-4" class="block text-sm font-medium text-gray-700 mb-2">Nueva contraseña</label>
            <input id="edit-4"
              v-model="passwordForm.password"
              type="password"
              autocomplete="new-password"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
            />
            <p v-if="erroresPassword.password" class="text-red-500 text-xs mt-1">{{ erroresPassword.password[0] }}</p>
          </div>

          <div>
            <label for="edit-5" class="block text-sm font-medium text-gray-700 mb-2">Confirmar nueva contraseña</label>
            <input id="edit-5"
              v-model="passwordForm.password_confirmation"
              type="password"
              autocomplete="new-password"
              class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
            />
            <p v-if="erroresPassword.password_confirmation" class="text-red-500 text-xs mt-1">{{ erroresPassword.password_confirmation[0] }}</p>
          </div>

          <div class="pt-2 flex items-center gap-3">
            <button
              type="submit"
              :disabled="guardandoPassword"
              class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:hover:translate-y-0 text-white font-bold text-sm py-2.5 px-6 rounded-xl transition-all duration-200 shadow-sm"
            >
              <i class="fa-solid fa-key"></i> {{ guardandoPassword ? 'Guardando...' : 'Actualizar Contraseña' }}
            </button>
            <span v-if="passwordGuardada" class="text-sm text-green-600 font-medium">Guardada.</span>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

defineProps({
  avatares: Array,
});

const page = usePage();
const usuario = page.props.auth.user;

const guardandoAvatar = ref(false);

const elegirAvatar = (avatarId) => {
  guardandoAvatar.value = true;

  router.patch(route('profile.avatar.update'), { avatar_id: avatarId }, {
    preserveScroll: true,
    onFinish: () => {
      guardandoAvatar.value = false;
    },
  });
};

const perfilForm = ref({
  name: usuario.name,
  email: usuario.email,
});
const erroresPerfil = ref({});
const guardandoPerfil = ref(false);

const guardarPerfil = () => {
  guardandoPerfil.value = true;
  erroresPerfil.value = {};

  router.patch(route('profile.update'), perfilForm.value, {
    preserveScroll: true,
    onError: (errors) => {
      erroresPerfil.value = errors;
    },
    onFinish: () => {
      guardandoPerfil.value = false;
    },
  });
};

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
});
const erroresPassword = ref({});
const guardandoPassword = ref(false);
const passwordGuardada = ref(false);

const guardarPassword = () => {
  guardandoPassword.value = true;
  erroresPassword.value = {};
  passwordGuardada.value = false;

  router.put(route('password.update'), passwordForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
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
