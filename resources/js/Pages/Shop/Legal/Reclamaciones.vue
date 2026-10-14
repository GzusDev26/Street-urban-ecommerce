<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import ArticleLayout from '@/Layouts/Shop/ArticleLayout.vue';

const now = new Date();
const curDate = now.toLocaleDateString('es-PE', {
  weekday: 'long', 
  year: 'numeric', 
  month: 'long', 
  day: 'numeric'
});

const page = usePage();

const form = useForm({
  claimant_name: '',
  claimant_address: '',
  claimant_national_id: '',
  claimant_email: '',
  claimant_phone: '',

  purchased_item: '',
  claimed_amount: null,

  claim_type: 'Reclamo',
  claim_details: '',
  claim_request: '',

  accept_privacy: false,
});

const success_message = computed(() => page.props.flash?.success);
const complaint_number = computed(() => page.props.flash?.complaint_number);

const submitForm = () => {
  form.post(route('shop.reclamaciones.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};
</script>

<template>
  <Head title="Libro de reclamaciones" />
  <ShopLayout>
    <div class="pt-20"></div>

    <!-- Título de la página -->
    <section class="relative py-16 px-4 md:px-8 lg:px-16 overflow-hidden">
      <!-- Background Gradient -->
      <div class="absolute inset-0 bg-gradient-to-b from-white/5 via-transparent to-transparent"></div>
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>

      <div class="relative max-w-full mx-auto">
        <div>
          <!-- Título Principal -->
          <h1
            class="text-3xl md:text-4xl lg:text-5xl font-black uppercase tracking-tight italic leading-tight mb-4 max-w-4xl">
            Libro <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500">de
              reclamaciones</span>
          </h1>
          <div class="h-1.5 w-24 bg-gradient-to-r from-white via-white to-gray-600 rounded-full"></div>
        </div>
      </div>
    </section>

    <ArticleLayout>
      <h2>Hoja de reclamación</h2>

      <p><strong>De conformidad con lo establecido en el Código de Protección y Defensa del Consumidor, STREET URBAN cuenta con un LIBRO DE RECLAMACIONES VIRTUAL a disposición de los USUARIOS y CLIENTES.</strong></p>

      <p><strong>La formulación del presente reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el Indecopi.</strong></p>

      <p><strong>STREET URBAN</strong> debe dar respuesta al reclamo o queja en un plazo no mayor a <strong>quince (15) días hábiles,</strong> el cual es improrrogable.</p>

      <p>
        Nombre del responsable: <strong>STREET URBAN</strong><br />
        RUC: <strong>RUC_DEL_RESPONSABLE;</strong> dirección: <strong>Lima</strong><br />
        Fecha de la solicitud: <strong>{{ curDate }}</strong>
      </p>

      <hr />

      <form @submit.prevent="submitForm" autocomplete="off" class="space-y-8">
        <!-- 1. IDENTIFICACIÓN DEL CONSUMIDOR -->
        <section class="space-y-6">
          <h2>1. Identificación del consumidor reclamante</h2>

          <div>
            <label for="claimant_name" class="block text-sm uppercase tracking-widest text-gray-400 mb-2">
              Nombres y apellidos completos
            </label>

            <input id="claimant_name" v-model="form.claimant_name" type="text" required
              class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" />
            <p v-if="form.errors.claimant_name" class="mt-2 text-sm text-red-400">{{ form.errors.claimant_name }}</p>
          </div>

          <div>
            <label for="claimant_address" class="block text-sm uppercase tracking-widest text-gray-400 mb-2">
              Domicilio
            </label>

            <input id="claimant_address" v-model="form.claimant_address" type="text" required
              class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" />
            <p v-if="form.errors.claimant_address" class="mt-2 text-sm text-red-400">{{ form.errors.claimant_address }}</p>
          </div>

          <div>
            <label for="claimant_national_id" class="block text-sm uppercase tracking-widest text-gray-400 mb-2">
              Documento de identidad
            </label>

            <input id="claimant_national_id" v-model="form.claimant_national_id" type="text" required
              @input="form.claimant_national_id = form.claimant_national_id.replace(/\s/g, '')"
              class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" />
            <p v-if="form.errors.claimant_national_id" class="mt-2 text-sm text-red-400">{{ form.errors.claimant_national_id }}</p>
          </div>

          <div>
            <label for="claimant_email" class="block text-sm uppercase tracking-widest text-gray-400 mb-2">
              Correo electrónico
            </label>

            <input id="claimant_email" v-model="form.claimant_email" type="email" required
              class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" />
            <p v-if="form.errors.claimant_email" class="mt-2 text-sm text-red-400">{{ form.errors.claimant_email }}</p>
          </div>

          <div>
            <label for="claimant_phone" class="block text-sm uppercase tracking-widest text-gray-400 mb-2">
              Teléfono
            </label>

            <input id="claimant_phone" v-model="form.claimant_phone" type="tel"
              class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" />
            <p v-if="form.errors.claimant_phone" class="mt-2 text-sm text-red-400">{{ form.errors.claimant_phone }}</p>
          </div>
        </section>

        <!-- 2. IDENTIFICACIÓN DEL BIEN -->
        <section class="space-y-6">
          <h2>2. Identificación del bien contratado</h2>

          <div>
            <label for="purchased_item" class="block text-sm uppercase tracking-widest text-gray-400 mb-2">
              Identificación del bien adquirido
            </label>

            <input id="purchased_item" v-model="form.purchased_item" type="text" required
              placeholder="Ej.: Pantalón hombre marca Diskovish talla 32"
              class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" />
            <p v-if="form.errors.purchased_item" class="mt-2 text-sm text-red-400">{{ form.errors.purchased_item }}</p>
          </div>

          <div>
            <label for="claimed_amount" class="block text-sm uppercase tracking-widest text-gray-400 mb-2">
              Monto a reclamar (S/)
            </label>

            <input id="claimed_amount" v-model="form.claimed_amount" type="number" min="0" step="0.01"
              placeholder="0.00"
              class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none" />
            <p class="mt-2 text-xs text-gray-500">Puedes indicar 0.00 si no corresponde un monto económico.</p>
            <p v-if="form.errors.claimed_amount" class="mt-2 text-sm text-red-400">{{ form.errors.claimed_amount }}</p>
          </div>
        </section>

        <!-- 3. DETALLE -->
        <section class="space-y-6">
          <h2>3. Detalle de la reclamación y pedido del consumidor</h2>

          <div class="space-y-2">
            <p>
              <strong>Queja:</strong>
              Disconformidad no relacionada a los productos o servicios, o malestar
              respecto a la atención al público.
              <br />
              <strong>Reclamo:</strong>
              Disconformidad relacionada a los productos o servicios.
            </p>
          </div>

          <div>
            <span class="block text-sm uppercase tracking-widest text-gray-400 mb-3">
              Tipo
            </span>

            <div class="flex gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.claim_type" type="radio" value="Queja" required />
                <span>Queja</span>
              </label>

              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.claim_type" type="radio" value="Reclamo" />
                <span>Reclamo</span>
              </label>
            </div>
          </div>

          <div>
            <label for="claim_details" class="block text-sm uppercase tracking-widest text-gray-400 mb-2">
              Detalle
            </label>

            <textarea id="claim_details" v-model="form.claim_details" rows="5" required
              class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none resize-none"></textarea>
            <p v-if="form.errors.claim_details" class="mt-2 text-sm text-red-400">{{ form.errors.claim_details }}</p>
          </div>

          <div>
            <label for="claim_request" class="block text-sm uppercase tracking-widest text-gray-400 mb-2">
              Pedido
            </label>

            <textarea id="claim_request" v-model="form.claim_request" rows="5" required
              class="w-full bg-[#0a0a0a] border border-gray-800 p-4 focus:border-white outline-none resize-none"></textarea>
            <p v-if="form.errors.claim_request" class="mt-2 text-sm text-red-400">{{ form.errors.claim_request }}</p>
          </div>
        </section>

        <!-- Aceptación -->
        <div class="flex items-start gap-3">
          <input id="privacy" v-model="form.accept_privacy" type="checkbox" required class="mt-1" />

          <label for="privacy" class="leading-relaxed text-gray-400">
            Autorizo que
            <strong class="text-white">STREET URBAN</strong>
            trate mis datos personales conforme a su <a href="/politica-de-privacidad" target="_blank" class="underline text-white hover:text-gray-300">
            Política de privacidad</a>, con la finalidad de atender la reclamación correspondiente.
          </label>
        </div>

        <p v-if="form.errors.accept_privacy" class="text-sm text-red-400">{{ form.errors.accept_privacy }}</p>

        <button type="submit" :disabled="form.processing"
          class="w-full py-4 bg-white text-black font-black uppercase tracking-widest hover:bg-gray-200 transition-all">
          {{ form.processing ? 'Enviando...' : 'Enviar reclamación' }}
        </button>
      </form>

      <div
        v-if="success_message"
        class="mt-6 p-4 border border-gray-800 bg-[#0a0a0a] text-gray-300 text-sm"
      >
        <p>{{ success_message }}</p>
        <p v-if="complaint_number" class="mt-2 font-bold text-white">
          Número de registro: {{ complaint_number }}
        </p>
      </div>
    </ArticleLayout>
  </ShopLayout>
</template>