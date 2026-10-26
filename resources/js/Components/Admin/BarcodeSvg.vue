<script setup>
import { onMounted, ref, watch } from 'vue';
import JsBarcode from 'jsbarcode';

// Dibuja un código de barras Code 128 (lee números de fábrica y códigos propios como SU000123).
const props = defineProps({
  valor: { type: String, required: true },
  alto: { type: Number, default: 40 },
  ancho: { type: Number, default: 1.6 },
});

const svg = ref(null);

const dibujar = () => {
  if (!svg.value) return;
  try {
    JsBarcode(svg.value, props.valor, { format: 'CODE128', displayValue: false, height: props.alto, width: props.ancho, margin: 0 });
  } catch {
    svg.value.innerHTML = '';
  }
};

onMounted(dibujar);
watch(() => [props.valor, props.alto, props.ancho], dibujar);
</script>

<template>
  <svg ref="svg" class="max-w-full" role="img" :aria-label="`Código de barras ${valor}`"></svg>
</template>
