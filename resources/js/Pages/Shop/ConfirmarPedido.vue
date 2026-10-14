<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import TwoColsLayout from '@/Layouts/Shop/TwoColsLayout.vue';
import { getItems, saveItems, validateGuestCart, hydrateCustomerCart } from '@/cart';

const compraData = ref(null);
const carritoItems = ref([]);
const page = usePage();
const processing = ref(false);
const error = ref('');
const success = ref(null);
const isCustomer = computed(() => page.props.auth?.type === 'customer');

const guestPayloadItems = () => getItems().map(item => ({
  variant_id: Number(item.variantId ?? item.variant_id),
  quantity: Number(item.cantidad ?? item.quantity),
}));

// Calculamos el total de forma reactiva
const totalCarrito = computed(() => {
  return carritoItems.value.reduce((acc, item) => acc + (item.price * item.cantidad), 0);
});

onMounted(() => {
  // 1. Verificación de seguridad: ¿Existe el token?
  const token = localStorage.getItem('checkout_token');
  if (!token) {
    window.location.href = '/';
    return;
  }

  // 2. Recuperación de datos del localStorage
  const rawCompra = localStorage.getItem('checkout_details');
  const rawCarrito = localStorage.getItem('shopping_cart');

  if (rawCompra) {
    compraData.value = JSON.parse(rawCompra);
  }

  if (rawCarrito) {
    carritoItems.value = JSON.parse(rawCarrito);
  }
});

const procesarPago = async () => {
  processing.value = true;
  error.value = '';

  try {
    const { data } = await axios.post('/api/cart/confirm', {
      ...(!isCustomer.value && { items: guestPayloadItems() }),
      ...compraData.value,
    });

    success.value = data;
    saveItems([]);
    carritoItems.value = [];
    localStorage.removeItem('checkout_token');
    localStorage.removeItem('checkout_details');
  } catch (exception) {
    if (exception.response?.status === 409) {
      error.value = exception.response.data.message;
      await refreshCart();
    } else {
      error.value = exception.response?.data?.message || 'No se pudo confirmar el pedido.';
    }
  } finally {
    processing.value = false;
  }
};

const refreshCart = async () => {
  const result = isCustomer.value
    ? await hydrateCustomerCart(page.props.auth.user.id)
    : await validateGuestCart();

  carritoItems.value = result.items || [];
};
</script>

<template>
  <Head title="Confirmación del pedido" />
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
            Confirmación <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500">del
              Pedido</span>
          </h1>
          <div class="h-1.5 w-24 bg-gradient-to-r from-white via-white to-gray-600 rounded-full"></div>
        </div>
      </div>
    </section>

    <TwoColsLayout>
      <template #left-content>
        <h1 class="text-3xl font-black uppercase tracking-tighter border-b border-gray-800 pb-4">
          Resumen de validación
        </h1>

        <div v-if="error" class="bg-red-950/40 border border-red-800 text-red-200 p-4 rounded-lg">{{ error }}</div>
        <div v-if="success" class="bg-green-950/40 border border-green-800 text-green-200 p-4 rounded-lg">
          {{ success.message }} Número de venta: {{ success.sale_number }}.
        </div>

        <section class="bg-gray-900 p-6 rounded-lg border border-gray-800">
          <h2 class="text-xl font-bold mb-4">Datos del comprador</h2>
          <div v-if="compraData" class="space-y-2 text-gray-400 text-sm">
            <p><strong>Nombre:</strong> {{ compraData.name }}</p>
            <p><strong>Documento:</strong> {{ compraData.docNumber }} ({{ compraData.docType }})</p>
            <p><strong>Correo:</strong> {{ compraData.email }}</p>
            <p><strong>Teléfono:</strong> {{ compraData.phone }}</p>
          </div>
          <p v-else class="text-gray-500">No se encontraron datos de contacto.</p>
        </section>

        <section class="bg-gray-900 p-6 rounded-lg border border-gray-800">
          <h2 class="text-xl font-bold mb-4">Productos en el carrito</h2>
          <div v-if="carritoItems.length > 0" class="space-y-4">
            <div v-for="item in carritoItems" :key="item.id"
              class="flex justify-between items-center border-b border-gray-800 pb-2">
              <div class="flex items-center gap-4">
                <span class="text-gray-400 text-sm">x{{ item.cantidad }}</span>
                <span>{{ item.name }}</span>
              </div>
              <span class="font-mono">S/&nbsp;{{ (item.price * item.cantidad).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between pt-4 text-lg font-bold">
              <span>Total a pagar</span>
              <span>S/&nbsp;{{ totalCarrito.toFixed(2) }}</span>
            </div>
          </div>
          <p v-else class="text-gray-500">El carrito está vacío.</p>
        </section>
      </template>

      <template #right-sidebar>
        <h2 class="text-xl font-bold mb-6">Confirmación</h2>
        <button @click="procesarPago" :disabled="processing || success || carritoItems.length === 0"
          class="w-full py-4 font-black uppercase tracking-widest transition-all rounded-lg bg-street-orange-600 text-black hover:bg-street-orange-300">
          {{ processing ? 'Confirmando...' : (success ? 'Pedido confirmado' : 'Confirmar pedido') }}
        </button>
        <p class="text-xs text-gray-500 mt-4 text-center">
          Al confirmar, aceptas nuestros <Link :href="route('shop.terminos')">Términos y condiciones</Link> y nuestra <Link :href="route('shop.privacidad')">Política de privacidad</Link>.
        </p>
      </template>
    </TwoColsLayout>
  </ShopLayout>
</template>