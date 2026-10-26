<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { BrowserMultiFormatReader } from '@zxing/browser';

// Lector de códigos de barras / QR con la cámara del celular. Emite `codigo` por cada lectura.
// Con `continuo` se queda abierto (para escanear varias prendas seguidas); si no, se cierra tras la primera.
// En modo continuo, tras cada lectura se pausa y muestra el `respuesta` que da quien la recibe
// ({ error, texto }) con «Seguir escaneando» o «Terminar»; `resumen` es el estado del ticket.
const props = defineProps({
  continuo: { type: Boolean, default: false },
  titulo: { type: String, default: 'Escanear código' },
  respuesta: { type: Object, default: null },
  resumen: { type: String, default: '' },
});
const emit = defineEmits(['codigo', 'close', 'terminar']);

const pausado = ref(false);

// Vibra cuando llega el resultado: corto si se agregó, doble si hubo un problema.
watch(() => props.respuesta, (r) => {
  if (r) navigator.vibrate?.(r.error ? [80, 60, 80] : 120);
});

const seguir = () => {
  pausado.value = false;
  ultimo.value = { texto: '', en: 0 }; // permite volver a leer la misma prenda
};

const video = ref(null);
const error = ref('');
const iniciando = ref(true);
const ultimo = ref({ texto: '', en: 0 });
let controles = null;
let cerrado = false;

const iniciar = async () => {
  // Los navegadores solo dan cámara en HTTPS (o localhost).
  if (!navigator.mediaDevices?.getUserMedia) {
    error.value = 'Este navegador no permite usar la cámara aquí. Abre el sistema desde una dirección segura (https).';
    iniciando.value = false;
    return;
  }

  try {
    const lector = new BrowserMultiFormatReader();
    controles = await lector.decodeFromConstraints(
      { video: { facingMode: { ideal: 'environment' } } },
      video.value,
      (resultado) => {
        if (!resultado || cerrado || pausado.value) return;

        const texto = resultado.getText();
        const ahora = Date.now();

        // El mismo código sigue en cuadro varios fotogramas: se ignora la repetición inmediata.
        if (texto === ultimo.value.texto && ahora - ultimo.value.en < 2000) return;
        ultimo.value = { texto, en: ahora };

        if (props.continuo) {
          pausado.value = true;
        }

        emit('codigo', texto);
        if (!props.continuo) emit('close');
      },
    );
  } catch (e) {
    error.value = e?.name === 'NotAllowedError'
      ? 'Permite el acceso a la cámara en el navegador para poder escanear.'
      : 'No se pudo abrir la cámara.';
  } finally {
    iniciando.value = false;
  }
};

const detener = () => {
  cerrado = true;
  controles?.stop();
  controles = null;
};

const alTeclear = (e) => {
  if (e.key === 'Escape') emit('close');
};

onMounted(() => {
  document.addEventListener('keydown', alTeclear);
  iniciar();
});
onBeforeUnmount(() => {
  document.removeEventListener('keydown', alTeclear);
  detener();
});
</script>

<template>
  <!-- Va en <body> y sin backdrop-blur: en iOS el desenfoque junto a un <video> deja todo el panel apagado,
       y los botones parecían desactivados. -->
  <Teleport to="body">
  <div class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <!-- El fondo oscuro es un hermano detrás del panel (no el fondo del contenedor): en iOS, con un <video>
         en el panel, el fondo del contenedor se pintaba por encima del panel y lo dejaba gris. -->
    <div class="absolute inset-0 bg-black/80" aria-hidden="true" @click="emit('close')"></div>
    <div role="dialog" aria-modal="true" :aria-label="titulo" class="theme-fixed relative z-10 bg-white text-gray-900 w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden pb-[env(safe-area-inset-bottom)] [transform:translateZ(0)]">
      <header class="flex items-center gap-3 px-5 py-4">
        <h2 class="flex-1 text-lg font-bold text-gray-900">{{ titulo }}</h2>
        <button type="button" class="w-9 h-9 rounded-full text-gray-500 hover:bg-gray-100 flex items-center justify-center" aria-label="Cerrar" @click="emit('close')">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </header>

      <!-- overflow-hidden: la sombra del marco de enfoque es enorme y, sin recortarla, oscurece todo el panel. -->
      <div class="relative overflow-hidden bg-black aspect-[4/3]">
        <video ref="video" class="w-full h-full object-cover" muted playsinline></video>

        <div v-if="!error && !iniciando && !pausado" class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
          <div class="w-3/4 h-1/2 rounded-2xl border-2 border-white/80 shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>
        </div>

        <!-- Confirmación de la lectura: la cámara queda en pausa hasta elegir -->
        <div v-if="pausado" class="absolute inset-0 bg-black/70 flex items-center justify-center p-5" role="status" aria-live="polite">
          <div class="w-full rounded-2xl bg-surface p-5 text-center">
            <span
              :class="['mx-auto w-12 h-12 rounded-full flex items-center justify-center text-xl', !respuesta ? 'bg-gray-100 text-gray-400' : respuesta.error ? 'bg-red-50 text-red-500' : 'bg-green-50 text-green-600']"
              aria-hidden="true"
            >
              <i :class="!respuesta ? 'fa-solid fa-spinner fa-spin' : respuesta.error ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-check'"></i>
            </span>
            <p class="mt-3 font-bold text-gray-900 leading-snug">{{ !respuesta ? 'Leyendo…' : respuesta.error ? 'No se agregó' : 'Agregado al ticket' }}</p>
            <p v-if="respuesta" class="mt-1 text-sm text-gray-600">{{ respuesta.texto }}</p>
            <p v-if="respuesta && resumen" class="mt-2 text-xs font-semibold text-gray-500">En el ticket: {{ resumen }}</p>
          </div>
        </div>

        <p v-if="iniciando" class="absolute inset-0 flex items-center justify-center text-sm text-white/80">Abriendo cámara…</p>
        <p v-if="error" role="alert" class="absolute inset-0 flex items-center justify-center p-6 text-center text-sm text-white">{{ error }}</p>
      </div>

      <div v-if="pausado" class="p-4 space-y-3">
        <p class="text-center text-sm font-semibold text-gray-700">¿Qué quieres hacer?</p>
        <button type="button" class="w-full h-14 rounded-2xl bg-[#ff8c42] active:bg-[#e67e2d] text-white text-base font-bold flex items-center justify-center gap-2.5 shadow-lg shadow-orange-200 transition-colors disabled:opacity-60" :disabled="!respuesta" @click="seguir">
          <i class="fa-solid fa-barcode" aria-hidden="true"></i> Seguir escaneando
        </button>
        <button type="button" class="w-full h-14 rounded-2xl bg-gray-900 active:bg-black text-white text-base font-bold flex items-center justify-center gap-2.5 transition-colors" @click="emit('terminar')">
          <i class="fa-solid fa-receipt" aria-hidden="true"></i> Ir a completar la venta
        </button>
      </div>
      <p v-else class="px-5 py-4 text-sm text-gray-500">
        {{ continuo ? 'Apunta al código de cada prenda: se agrega y te pregunta si sigues.' : 'Apunta al código de barras de la etiqueta.' }}
      </p>
    </div>
  </div>
  </Teleport>
</template>
