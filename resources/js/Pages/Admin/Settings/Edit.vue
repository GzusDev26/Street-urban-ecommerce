<template>
  <Head title="Datos del Negocio" />
  <AdminLayout>
    <template #breadcrumb>Configuración</template>
    <template #header>Datos del Negocio</template>

    <div class="space-y-6">
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- IZQUIERDA: formulario -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-1">
              <i class="fa-solid fa-store text-gray-400 text-sm"></i>
              <h3 class="text-lg font-bold text-gray-900">Identidad y Contacto</h3>
            </div>
            <p class="text-xs text-gray-400 mb-6">
              Aparece en la página de Contacto de la tienda y en las boletas que imprimes desde Ventas.
            </p>

            <form @submit.prevent="handleSubmit" class="space-y-5">
              <div>
                <label for="edit-1" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-2">
                  <i class="fa-solid fa-tag text-gray-300 text-xs"></i> Nombre del negocio
                </label>
                <input id="edit-1"
                  v-model="formData.name"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
                  required
                />
                <p v-if="validationErrors.name" class="text-red-500 text-xs mt-1">{{ validationErrors.name[0] }}</p>
              </div>

              <div>
                <label for="edit-2" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-2">
                  <i class="fa-solid fa-envelope text-gray-300 text-xs"></i> Email de contacto (opcional)
                </label>
                <input id="edit-2"
                  v-model="formData.email"
                  type="email"
                  class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
                />
                <p v-if="validationErrors.email" class="text-red-500 text-xs mt-1">{{ validationErrors.email[0] }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="edit-3" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-phone text-gray-300 text-xs"></i> Teléfono (opcional)
                  </label>
                  <input id="edit-3"
                    v-model="formData.phone"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
                  />
                  <p v-if="validationErrors.phone" class="text-red-500 text-xs mt-1">{{ validationErrors.phone[0] }}</p>
                </div>
                <div>
                  <label for="edit-4" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-brands fa-whatsapp text-gray-300 text-xs"></i> WhatsApp (opcional)
                  </label>
                  <input id="edit-4"
                    v-model="formData.whatsapp"
                    type="text"
                    placeholder="51987654321"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
                  />
                  <p v-if="validationErrors.whatsapp" class="text-red-500 text-xs mt-1">{{ validationErrors.whatsapp[0] }}</p>
                </div>
              </div>
              <p class="text-[11px] text-gray-400 -mt-3">Código de país sin "+" ni espacios (ej. 51987654321) — va directo al link de WhatsApp.</p>

              <div class="border-t border-gray-100 pt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="edit-5" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-location-dot text-gray-300 text-xs"></i> Dirección (opcional)
                  </label>
                  <input id="edit-5"
                    v-model="formData.address"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
                  />
                  <p v-if="validationErrors.address" class="text-red-500 text-xs mt-1">{{ validationErrors.address[0] }}</p>
                </div>

                <div>
                  <label for="edit-6" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-2">
                    <i class="fa-solid fa-file-invoice text-gray-300 text-xs"></i> RUC (opcional)
                  </label>
                  <input id="edit-6"
                    v-model="formData.ruc"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
                  />
                  <p v-if="validationErrors.ruc" class="text-red-500 text-xs mt-1">{{ validationErrors.ruc[0] }}</p>
                </div>
              </div>

              <div class="border-t border-gray-100 pt-5">
                <label for="edit-7" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-2">
                  <i class="fa-solid fa-triangle-exclamation text-gray-300 text-xs"></i> Umbral de stock bajo
                </label>
                <input id="edit-7"
                  v-model.number="formData.low_stock_threshold"
                  type="number"
                  min="1"
                  class="w-full sm:w-40 px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ff8c42]"
                  required
                />
                <p class="text-[11px] text-gray-400 mt-1.5">
                  Cuando una prenda tenga menos unidades que este número, se marcará en amarillo en Catálogo y Compras.
                </p>
                <p v-if="validationErrors.low_stock_threshold" class="text-red-500 text-xs mt-1">{{ validationErrors.low_stock_threshold[0] }}</p>
              </div>

              <div class="pt-2">
                <button
                  type="submit"
                  :disabled="loading"
                  class="flex items-center gap-2 bg-gradient-to-r from-[#ff8c42] to-[#e67e2d] hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:hover:translate-y-0 text-white font-bold text-sm py-2.5 px-6 rounded-xl transition-all duration-200 shadow-sm"
                >
                  <i class="fa-solid fa-save"></i> {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- DERECHA: vista previa en vivo -->
        <div class="lg:sticky lg:top-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <i class="fa-solid fa-eye text-gray-400 text-sm"></i>
            <h3 class="text-sm font-bold text-gray-900">Vista Previa</h3>
            <span class="ml-auto flex items-center gap-1 text-[10px] font-semibold text-green-500">
              <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span> en vivo
            </span>
          </div>

          <!-- Mini boleta, con un item de ejemplo para que se vea como una boleta real -->
          <div class="p-5">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-3">En la Boleta</p>

            <div class="border border-gray-100 rounded-xl p-5 bg-gray-50/60">
              <div class="text-center">
                <div class="w-10 h-10 bg-[#ff8c42] rounded-lg flex items-center justify-center font-black text-white mx-auto mb-2 text-sm">
                  {{ (formData.name || 'S').charAt(0).toUpperCase() }}
                </div>
                <h1 class="text-base font-black tracking-tight text-gray-900">{{ formData.name || 'Nombre del negocio' }}</h1>
                <p v-if="formData.ruc" class="text-[10px] text-gray-400">RUC {{ formData.ruc }}</p>
                <p v-if="formData.address" class="text-[10px] text-gray-400 truncate">{{ formData.address }}</p>
              </div>

              <div class="border-t border-dashed border-gray-300 my-3"></div>

              <div class="flex items-center justify-between text-[11px] text-gray-500 mb-1">
                <span>Zapatillas Urban x1</span>
                <span class="text-gray-900 font-medium">S/ 99.00</span>
              </div>

              <div class="border-t border-dashed border-gray-300 my-3"></div>

              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500">Total</span>
                <span class="text-lg font-black text-gray-900">S/ 99.00</span>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-100"></div>

          <!-- Mini vista de la página de Contacto -->
          <div class="p-5">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-3">En Contacto</p>

            <div class="bg-[#111111] rounded-xl p-5 space-y-4">
              <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-[#ff8c42]/15 flex items-center justify-center flex-shrink-0">
                  <i class="fa-solid fa-envelope text-[#ff8c42] text-xs"></i>
                </span>
                <span class="text-sm text-gray-300 truncate">{{ formData.email || 'Sin email configurado' }}</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-[#ff8c42]/15 flex items-center justify-center flex-shrink-0">
                  <i class="fa-solid fa-phone text-[#ff8c42] text-xs"></i>
                </span>
                <span class="text-sm text-gray-300">{{ formData.phone || 'Sin teléfono configurado' }}</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-[#ff8c42]/15 flex items-center justify-center flex-shrink-0">
                  <i class="fa-brands fa-whatsapp text-[#ff8c42] text-xs"></i>
                </span>
                <div class="min-w-0">
                  <p class="text-sm text-gray-300">{{ formData.whatsapp || 'Sin WhatsApp configurado' }}</p>
                  <p v-if="formData.whatsapp" class="text-[10px] text-gray-500 truncate">wa.me/{{ formData.whatsapp }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  settings: Object,
});

const page = usePage();
const loading = ref(false);
const validationErrors = ref({});

const formData = ref({
  name: props.settings.name,
  email: props.settings.email,
  phone: props.settings.phone,
  whatsapp: props.settings.whatsapp,
  address: props.settings.address,
  ruc: props.settings.ruc,
  low_stock_threshold: props.settings.low_stock_threshold,
});

const handleSubmit = () => {
  loading.value = true;
  validationErrors.value = {};

  router.patch(route('admin.settings.update'), formData.value, {
    onError: (errors) => {
      validationErrors.value = errors;
    },
    onFinish: () => {
      loading.value = false;
    },
  });
};
</script>
