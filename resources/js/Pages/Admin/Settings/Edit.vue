<template>
  <Head title="Datos del Negocio" />
  <AdminLayout>
    <template #breadcrumb>Configuración</template>
    <template #header>Datos del Negocio</template>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_440px] 2xl:grid-cols-[minmax(0,1fr)_520px] gap-6 items-start" :class="{ 'pb-24': hayCambios }">
      <!-- Formulario -->
      <form id="form-negocio" class="grid grid-cols-1 2xl:grid-cols-2 gap-6 items-start min-w-0" novalidate @submit.prevent="guardar">
        <Transition
          enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
          enter-from-class="opacity-0 -translate-y-1"
          leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
          leave-to-class="opacity-0"
        >
          <div v-if="aviso" role="status" class="2xl:col-span-2 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span class="flex-1">{{ aviso }}</span>
            <button type="button" class="w-8 h-8 -mr-1 rounded-lg flex items-center justify-center hover:bg-green-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500" aria-label="Cerrar aviso" @click="aviso = null">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
        </Transition>

        <!-- Identidad -->
        <section class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm" aria-labelledby="identidad-titulo">
          <header class="flex items-start gap-3 mb-5">
            <span class="w-10 h-10 rounded-xl bg-orange-50 text-[#ff8c42] flex items-center justify-center flex-shrink-0" aria-hidden="true">
              <i class="fa-solid fa-store"></i>
            </span>
            <div>
              <h3 id="identidad-titulo" class="text-lg font-bold text-gray-900 leading-tight">Identidad</h3>
              <p class="text-sm text-gray-500 mt-0.5">Encabeza las boletas y aparece en toda la tienda.</p>
            </div>
          </header>

          <div class="space-y-5">
            <FormField label="Nombre del negocio">
              <FormInput v-model="form.name" type="text" icon="fa-solid fa-tag" maxlength="255" required :aria-invalid="!!errores.name" />
              <p v-if="errores.name" class="text-red-500 text-xs mt-1.5" role="alert">{{ errores.name }}</p>
            </FormField>

            <div class="grid grid-cols-1 md:grid-cols-[200px_minmax(0,1fr)] gap-5">
              <FormField label="RUC" optional>
                <FormInput
                  v-model="form.ruc"
                  type="text"
                  icon="fa-solid fa-file-invoice"
                  inputmode="numeric"
                  maxlength="11"
                  placeholder="20123456789"
                  :aria-invalid="!!errores.ruc"
                />
                <p v-if="errores.ruc" class="text-red-500 text-xs mt-1.5" role="alert">{{ errores.ruc }}</p>
                <p v-else-if="form.ruc && form.ruc.length !== 11" class="text-amber-600 text-xs mt-1.5">El RUC tiene 11 dígitos ({{ form.ruc.length }}/11).</p>
              </FormField>

              <FormField label="Dirección" optional>
                <FormInput v-model="form.address" type="text" icon="fa-solid fa-location-dot" maxlength="500" placeholder="Jr. Progreso 123, San Ramón" autocomplete="street-address" :aria-invalid="!!errores.address" />
                <p v-if="errores.address" class="text-red-500 text-xs mt-1.5" role="alert">{{ errores.address }}</p>
              </FormField>
            </div>
          </div>
        </section>

        <!-- Contacto -->
        <section class="bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm" aria-labelledby="contacto-titulo">
          <header class="flex items-start gap-3 mb-5">
            <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center flex-shrink-0" aria-hidden="true">
              <i class="fa-solid fa-address-book"></i>
            </span>
            <div>
              <h3 id="contacto-titulo" class="text-lg font-bold text-gray-900 leading-tight">Contacto</h3>
              <p class="text-sm text-gray-500 mt-0.5">Lo que ven los clientes en la página de Contacto de la tienda.</p>
            </div>
          </header>

          <div class="space-y-5">
            <FormField label="Correo de contacto" optional>
              <FormInput v-model="form.email" type="email" icon="fa-regular fa-envelope" maxlength="255" placeholder="contacto@tunegocio.com" autocomplete="email" :aria-invalid="!!errores.email" />
              <p v-if="errores.email" class="text-red-500 text-xs mt-1.5" role="alert">{{ errores.email }}</p>
            </FormField>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <FormField label="Teléfono" optional hint="Sale en la boleta y en Contacto.">
                <FormInput v-model="form.phone" type="tel" icon="fa-solid fa-phone" maxlength="20" placeholder="+51 987 654 321" autocomplete="tel" :aria-invalid="!!errores.phone" />
                <p v-if="errores.phone" class="text-red-500 text-xs mt-1.5" role="alert">{{ errores.phone }}</p>
              </FormField>

              <FormField label="WhatsApp" optional>
                <FormInput
                  v-model="form.whatsapp"
                  type="tel"
                  icon="fa-brands fa-whatsapp"
                  inputmode="numeric"
                  maxlength="20"
                  placeholder="51987654321"
                  :aria-invalid="!!errores.whatsapp"
                  aria-describedby="whatsapp-ayuda"
                />
                <p v-if="errores.whatsapp" class="text-red-500 text-xs mt-1.5" role="alert">{{ errores.whatsapp }}</p>
                <p v-else id="whatsapp-ayuda" class="text-xs text-gray-400 mt-1.5">
                  Con código de país, solo números.
                  <a v-if="form.whatsapp" :href="`https://wa.me/${form.whatsapp}`" target="_blank" rel="noopener" class="font-semibold text-[#e67e2d] hover:underline whitespace-nowrap">
                    Probar enlace <i class="fa-solid fa-arrow-up-right-from-square text-[10px]" aria-hidden="true"></i><span class="sr-only">(se abre en otra pestaña)</span>
                  </a>
                </p>
              </FormField>
            </div>
          </div>
        </section>

        <!-- Inventario -->
        <section class="2xl:col-span-2 bg-surface rounded-2xl border border-gray-100 p-6 shadow-sm" aria-labelledby="inventario-titulo">
          <header class="flex items-start gap-3 mb-5">
            <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0" aria-hidden="true">
              <i class="fa-solid fa-boxes-stacked"></i>
            </span>
            <div>
              <h3 id="inventario-titulo" class="text-lg font-bold text-gray-900 leading-tight">Inventario</h3>
              <p class="text-sm text-gray-500 mt-0.5">Cuándo avisarte que una prenda se está acabando.</p>
            </div>
          </header>

          <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div>
              <label for="umbral" class="block text-sm font-semibold text-gray-700 mb-1.5">Umbral de stock bajo</label>
              <div class="inline-flex items-center rounded-xl border border-gray-200 bg-surface overflow-hidden focus-within:border-[#ff8c42] focus-within:ring-4 focus-within:ring-orange-100 transition">
                <button type="button" class="w-11 h-11 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus-visible:bg-gray-100" aria-label="Restar uno" :disabled="form.low_stock_threshold <= 1" @click="ajustarUmbral(-1)">
                  <i class="fa-solid fa-minus text-xs" aria-hidden="true"></i>
                </button>
                <input
                  id="umbral"
                  v-model.number="form.low_stock_threshold"
                  type="number"
                  min="1"
                  max="1000"
                  inputmode="numeric"
                  class="w-16 h-11 border-0 border-x border-gray-200 bg-transparent text-center text-base font-bold text-gray-900 focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                  :aria-invalid="!!errores.low_stock_threshold"
                  aria-describedby="umbral-ayuda"
                />
                <button type="button" class="w-11 h-11 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus-visible:bg-gray-100" aria-label="Sumar uno" :disabled="form.low_stock_threshold >= 1000" @click="ajustarUmbral(1)">
                  <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                </button>
              </div>
              <p v-if="errores.low_stock_threshold" class="text-red-500 text-xs mt-1.5" role="alert">{{ errores.low_stock_threshold }}</p>
            </div>

            <p id="umbral-ayuda" class="flex-1 flex items-start gap-2.5 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-700 sm:mt-6">
              <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
              <span>Una prenda con <b>menos de {{ umbralTexto }}</b> se marca en amarillo en el inventario de Compras.</span>
            </p>
          </div>
        </section>
      </form>

      <!-- Vista previa -->
      <aside class="xl:sticky xl:top-0 bg-surface rounded-2xl border border-gray-100 shadow-sm overflow-hidden" aria-label="Vista previa">
        <div class="px-5 pt-4 pb-3 flex items-center gap-2">
          <h3 class="text-sm font-bold text-gray-900">Vista previa</h3>
          <span class="ml-auto inline-flex items-center gap-1.5 text-[11px] font-semibold text-green-600">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500 motion-safe:animate-pulse" aria-hidden="true"></span> Se actualiza mientras escribes
          </span>
        </div>

        <div class="px-5">
          <div class="grid grid-cols-2 p-1 bg-gray-100 rounded-xl" role="tablist" aria-label="Qué previsualizar">
            <button
              v-for="t in PESTANAS"
              :id="`tab-${t.id}`"
              :key="t.id"
              type="button"
              role="tab"
              :aria-selected="pestana === t.id"
              :aria-controls="`panel-${t.id}`"
              :tabindex="pestana === t.id ? 0 : -1"
              :class="[
                'h-9 rounded-lg text-xs font-bold flex items-center justify-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]',
                pestana === t.id ? 'bg-surface text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800',
              ]"
              @click="pestana = t.id"
              @keydown.right.prevent="moverPestana(1)"
              @keydown.left.prevent="moverPestana(-1)"
            >
              <i :class="t.icon" aria-hidden="true"></i> {{ t.label }}
            </button>
          </div>
        </div>

        <!-- Boleta real, con una venta de ejemplo -->
        <div v-show="pestana === 'boleta'" id="panel-boleta" role="tabpanel" aria-labelledby="tab-boleta" class="mt-4 bg-gray-100 px-4 py-6 flex justify-center max-h-[640px] overflow-y-auto">
          <BoletaTicket :venta="VENTA_EJEMPLO" :business="negocioPreview" :zoom="1.05" />
        </div>

        <!-- Datos de contacto tal como los verá el cliente en la tienda -->
        <div v-show="pestana === 'contacto'" id="panel-contacto" role="tabpanel" aria-labelledby="tab-contacto" class="mt-4 px-5 pb-5">
          <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
            <p class="text-[11px] uppercase tracking-[0.2em] text-[#e67e2d] font-bold">Ponte en contacto</p>
            <p class="mt-1 text-base font-bold text-gray-900 leading-snug">{{ negocioPreview.name }}</p>

            <ul class="mt-4 space-y-2.5">
              <li
                v-for="c in canalesContacto"
                :key="c.label"
                :class="['flex items-center gap-3 rounded-xl border px-3 py-2.5', c.valor ? 'bg-surface border-gray-200' : 'border-dashed border-gray-200']"
              >
                <span :class="['w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0', c.valor ? 'bg-orange-50 text-[#e67e2d]' : 'bg-gray-100 text-gray-400']" aria-hidden="true">
                  <i :class="c.icon" class="text-sm"></i>
                </span>
                <span class="min-w-0">
                  <span class="block text-[11px] uppercase tracking-wider font-semibold text-gray-500">{{ c.label }}</span>
                  <span :class="['block text-sm truncate', c.valor ? 'text-gray-900 font-semibold' : 'text-gray-400 italic']">{{ c.valor || 'No se mostrará' }}</span>
                </span>
              </li>
            </ul>
          </div>
          <p class="mt-3 text-xs text-gray-500">Los campos vacíos no aparecen en la tienda.</p>
        </div>
      </aside>
    </div>

    <!-- Barra de cambios sin guardar -->
    <Transition
      enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
      enter-from-class="opacity-0 translate-y-4"
      leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div v-if="hayCambios" class="fixed bottom-4 inset-x-4 lg:left-auto lg:right-8 z-40 lg:w-[min(560px,calc(100vw-22rem))]" role="region" aria-label="Cambios sin guardar">
        <div class="flex items-center gap-3 rounded-2xl bg-fg text-canvas pl-5 pr-2 py-2 shadow-2xl shadow-black/30">
          <i class="fa-solid fa-circle-exclamation text-[#ff8c42]" aria-hidden="true"></i>
          <p class="flex-1 text-sm font-medium truncate"><span class="sm:hidden">Sin guardar</span><span class="hidden sm:inline">Tienes cambios sin guardar</span></p>
          <button type="button" class="h-10 px-4 rounded-xl text-sm font-semibold hover:bg-canvas/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42]" @click="descartar">
            Descartar
          </button>
          <button
            type="submit"
            form="form-negocio"
            :disabled="guardando"
            class="h-10 px-5 rounded-xl bg-[#ff8c42] hover:bg-[#f27d30] active:scale-[0.98] text-white text-sm font-bold flex items-center gap-2 transition-all disabled:opacity-60 disabled:cursor-wait focus:outline-none focus-visible:ring-2 focus-visible:ring-canvas"
          >
            <i :class="guardando ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-floppy-disk'" class="text-xs" aria-hidden="true"></i>
            {{ guardando ? 'Guardando…' : 'Guardar' }}
          </button>
        </div>
      </div>
    </Transition>
  </AdminLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BoletaTicket from '@/Components/Admin/BoletaTicket.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  settings: Object,
});

const page = usePage();

const CAMPOS = ['name', 'email', 'phone', 'whatsapp', 'address', 'ruc', 'low_stock_threshold'];
const desdeServidor = () => Object.fromEntries(CAMPOS.map((k) => [k, props.settings[k] ?? (k === 'low_stock_threshold' ? 5 : '')]));

// Lo último guardado; tras guardar, Inertia trae `settings` actualizado y se toma como nueva base.
const base = ref(desdeServidor());
const form = reactive(desdeServidor());
watch(() => props.settings, () => {
  base.value = desdeServidor();
  Object.assign(form, desdeServidor());
});

const hayCambios = computed(() => CAMPOS.some((k) => String(form[k] ?? '') !== String(base.value[k] ?? '')));

const errores = ref({});
const guardando = ref(false);
const aviso = ref(page.props.flash?.success ?? null);
watch(() => page.props.flash?.success, (v) => { if (v) aviso.value = v; });

// RUC y WhatsApp solo llevan números: se limpian al vuelo (espacios, "+", guiones al pegar).
const soloDigitos = (v) => String(v ?? '').replace(/\D/g, '');
watch(() => [form.ruc, form.whatsapp], () => {
  for (const k of ['ruc', 'whatsapp']) {
    const limpio = soloDigitos(form[k]);
    if (limpio !== form[k]) form[k] = limpio;
  }
});

const ajustarUmbral = (delta) => {
  const actual = Number(form.low_stock_threshold) || 1;
  form.low_stock_threshold = Math.min(1000, Math.max(1, actual + delta));
};
const umbralTexto = computed(() => {
  const n = Number(form.low_stock_threshold) || 1;
  return `${n} ${n === 1 ? 'unidad' : 'unidades'}`;
});

const descartar = () => {
  Object.assign(form, base.value);
  errores.value = {};
};

const guardar = () => {
  if (!hayCambios.value || guardando.value) return;
  guardando.value = true;
  errores.value = {};

  router.patch(route('admin.settings.update'), { ...form }, {
    preserveScroll: true,
    onError: (e) => {
      errores.value = e;
    },
    onFinish: () => {
      guardando.value = false;
    },
  });
};

// --- Vista previa ----------------------------------------------------------------------------
const PESTANAS = [
  { id: 'boleta', label: 'Boleta', icon: 'fa-solid fa-receipt' },
  { id: 'contacto', label: 'Contacto', icon: 'fa-solid fa-address-card' },
];
const pestana = ref('boleta');
const moverPestana = (paso) => {
  const i = PESTANAS.findIndex((t) => t.id === pestana.value);
  pestana.value = PESTANAS[(i + paso + PESTANAS.length) % PESTANAS.length].id;
  document.getElementById(`tab-${pestana.value}`)?.focus();
};

const negocioPreview = computed(() => ({ ...form, name: form.name.trim() || 'Nombre del negocio' }));

const canalesContacto = computed(() => [
  { label: 'Email', icon: 'fa-solid fa-envelope', valor: form.email },
  { label: 'Teléfono', icon: 'fa-solid fa-phone', valor: form.phone },
  { label: 'WhatsApp', icon: 'fa-brands fa-whatsapp', valor: form.whatsapp ? `wa.me/${form.whatsapp}` : '' },
]);

const hoy = new Intl.DateTimeFormat('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date());
const VENTA_EJEMPLO = {
  numero: 'V-00000',
  fecha: hoy,
  registradoPor: page.props.auth.user.name,
  cliente: 'Cliente de ejemplo',
  clienteDocumento: null,
  cancelada: false,
  notas: null,
  items: [
    { producto: 'Polo Oversize', marca: 'Street', talla: 'M', color: 'Negro', cantidad: 1, cantidadDevuelta: 0, precioUnitario: 59, subtotal: 59 },
    { producto: 'Jogger Cargo', marca: 'Urban', talla: 'L', color: 'Beige', cantidad: 1, cantidadDevuelta: 0, precioUnitario: 89, subtotal: 89 },
  ],
  totalPrendas: 2,
  prendasDevueltas: 0,
  total: 148,
  totalNeto: 148,
};
</script>
