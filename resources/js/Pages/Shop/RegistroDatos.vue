<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import TwoColsLayout from '@/Layouts/Shop/TwoColsLayout.vue';
import { getItems } from '@/cart';

const cartItems = ref([]);
const form = reactive({
  docType: 'DNI',
  docNumber: '',
  name: '',
  email: '',
  phone: ''
});

// Validación simple: todos los campos requeridos deben estar llenos
const isFormValid = computed(() => {
  return form.docNumber && form.name && form.email && form.phone;
});

const subtotal = computed(() => {
  return cartItems.value.reduce((acc, item) => acc + (item.price * item.cantidad), 0);
});

const submitForm = () => {
  if (!isFormValid.value) return;

  // 1. Guardar datos
  localStorage.setItem('checkout_details', JSON.stringify(form));
  
  // 2. Crear token de seguridad para el siguiente paso
  localStorage.setItem('checkout_token', btoa(Date.now().toString()));

  // 3. Enviar al backend para validación
  router.post(route('shop.validateRegisterForm'), form);
};

onMounted(() => {
  cartItems.value = getItems();
});
</script>

<template>

  <Head title="Registro de datos" />
  <ShopLayout>
    <div class="pt-20"></div>

    <!-- Título de la página -->
    <section class="relative py-16 px-4 md:px-8 lg:px-16 overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-b from-white/5 via-transparent to-transparent"></div>
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>

      <div class="relative max-w-full mx-auto">
        <div>
          <!-- Título Principal -->
          <h1
            class="text-3xl md:text-4xl lg:text-5xl font-black uppercase tracking-tight italic leading-tight mb-4 max-w-4xl">
            Registro <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500">de Datos</span>
          </h1>
          <div class="h-1.5 w-24 bg-gradient-to-r from-white via-white to-gray-600 rounded-full"></div>
        </div>
      </div>
    </section>

    <TwoColsLayout>
      <template #left-content>
        <div class="gap-6 p-8 border border-gray-800 rounded-xl transition-colors">
          <h2 class="text-3xl font-black uppercase italic mb-8">Información de Facturación</h2>
          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="registro-datos-1" class="block text-xs uppercase tracking-widest text-gray-400 mb-2">Tipo de Documento</label>
                <select id="registro-datos-1" v-model="form.docType" class="w-full bg-[#0a0a0a] border border-gray-800 p-4 uppercase font-bold focus:border-white outline-none">
                  <option value="DNI">DNI</option>
                  <option value="RUC">RUC</option>
                </select>
              </div>
              <div>
                <label for="registro-datos-2" class="block text-xs uppercase tracking-widest text-gray-400 mb-2">Número</label>
                <input id="registro-datos-2" v-model="form.docNumber" type="text" class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" required>
              </div>
            </div>

            <div>
              <label for="registro-datos-3" class="block text-xs uppercase tracking-widest text-gray-400 mb-2">Nombre / Razón Social</label>
              <input id="registro-datos-3" v-model="form.name" type="text" class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="registro-datos-4" class="block text-xs uppercase tracking-widest text-gray-400 mb-2">Correo</label>
                <input id="registro-datos-4" v-model="form.email" type="email" class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" required>
              </div>
              <div>
                <label for="registro-datos-5" class="block text-xs uppercase tracking-widest text-gray-400 mb-2">Teléfono</label>
                <input id="registro-datos-5" v-model="form.phone" type="tel" class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" required>
              </div>
            </div>
          </form>
        </div>
      </template>

      <template #right-sidebar>
        <h2 class="text-xl font-black uppercase mb-6 italic">Resumen del Pedido</h2>
        <div class="space-y-4 mb-8 text-sm">
          <div v-for="item in cartItems" :key="item.id" class="flex justify-between">
            <span>{{ item.cantidad }}x {{ item.name }}</span>
            <span>S/ {{ (item.price * item.cantidad).toFixed(2) }}</span>
          </div>
          <div class="h-px bg-gray-800 my-4"></div>
          <div class="flex justify-between text-xl font-black uppercase">
            <span>Total</span>
            <span>S/ {{ subtotal.toFixed(2) }}</span>
          </div>
        </div>
        <button 
          @click="submitForm"
          :disabled="!isFormValid"
          :class="[
            'w-full py-4 font-black uppercase tracking-widest transition-all rounded-lg',
            isFormValid
              ? 'bg-street-orange-600 text-black hover:bg-street-orange-300'
              : 'bg-gray-800 text-gray-500 cursor-not-allowed'
          ]"
        >
          Confirmar Datos
        </button>
      </template>
    </TwoColsLayout>
  </ShopLayout>
</template>