<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ColorSwatch from '@/Components/Admin/ColorSwatch.vue';
import { avisar } from '@/alerts';

// Foto de un color que se sube con un clic (o arrastrando la imagen encima), sin abrir formularios.
// Sin `editable` es solo la miniatura.
const props = defineProps({
  color: { type: Object, required: true }, // { id, nombre, hex, imagenUrl }
  editable: Boolean,
  size: { type: String, default: 'md' }, // 'md' (56px) | 'lg' (80px)
});

const emit = defineEmits(['uploaded']);

const MAX_BYTES = 4 * 1024 * 1024;
const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

const input = ref(null);
const subiendo = ref(false);
const arrastrando = ref(false);
const vistaPrevia = ref(null); // se muestra al instante mientras sube

const imagen = computed(() => vistaPrevia.value ?? props.color.imagenUrl);
const medida = computed(() => (props.size === 'lg' ? 'w-20 h-20' : 'w-14 h-14'));

const elegir = () => {
  if (props.editable && !subiendo.value) input.value?.click();
};

const subir = (file) => {
  if (!file) return;

  if (!TIPOS.includes(file.type)) {
    avisar('Usa una foto JPG, PNG o WEBP.', { titulo: 'Formato no válido' });
    return;
  }
  if (file.size > MAX_BYTES) {
    avisar(`La foto pesa ${(file.size / 1024 / 1024).toFixed(1)} MB y el máximo es 4 MB.`, { titulo: 'Foto muy pesada' });
    return;
  }

  vistaPrevia.value = URL.createObjectURL(file);
  subiendo.value = true;

  router.post(route('admin.catalogo.colores.foto', props.color.id), { imagen: file }, {
    forceFormData: true,
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => emit('uploaded'),
    onError: (errors) => {
      avisar(Object.values(errors)[0] || 'No se pudo subir la foto. Intenta de nuevo.', { titulo: 'No se subió la foto', tipo: 'error' });
    },
    onFinish: () => {
      subiendo.value = false;
      if (vistaPrevia.value) URL.revokeObjectURL(vistaPrevia.value);
      vistaPrevia.value = null;
      if (input.value) input.value.value = '';
    },
  });
};

const onSoltar = (e) => {
  arrastrando.value = false;
  if (props.editable) subir(e.dataTransfer?.files?.[0]);
};
</script>

<template>
  <!-- Solo miniatura -->
  <span
    v-if="!editable"
    :class="[medida, 'rounded-xl overflow-hidden bg-gray-100 ring-1 ring-black/5 flex-shrink-0 flex items-center justify-center']"
  >
    <img v-if="imagen" :src="imagen" :alt="color.nombre" class="w-full h-full object-cover" loading="lazy">
    <ColorSwatch v-else :hex="color.hex" class="w-6 h-6 rounded-full border border-gray-300" />
  </span>

  <!-- Editable: clic o arrastrar para subir -->
  <button
    v-else
    type="button"
    :class="[
      medida,
      'group relative rounded-xl overflow-hidden flex-shrink-0 transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8c42] focus-visible:ring-offset-2',
      imagen
        ? 'ring-1 ring-black/5'
        : 'border-2 border-dashed border-orange-300 bg-orange-50/60 hover:border-[#ff8c42] hover:bg-orange-50',
      arrastrando ? '!border-solid !border-[#ff8c42] ring-4 ring-orange-100 scale-105' : '',
    ]"
    :aria-label="imagen ? `Cambiar la foto de ${color.nombre}` : `Subir foto de ${color.nombre}`"
    :title="imagen ? 'Clic para cambiar la foto (o arrastra una imagen aquí)' : 'Clic para subir la foto (o arrastra una imagen aquí)'"
    :disabled="subiendo"
    @click="elegir"
    @dragover.prevent="arrastrando = true"
    @dragleave.prevent="arrastrando = false"
    @drop.prevent="onSoltar"
  >
    <img v-if="imagen" :src="imagen" :alt="color.nombre" class="w-full h-full object-cover">

    <!-- Vacío: invita a subir -->
    <span v-else class="w-full h-full flex flex-col items-center justify-center gap-0.5 text-[#ff8c42]">
      <i class="fa-solid fa-camera text-base" aria-hidden="true"></i>
      <span class="text-[10px] font-bold leading-none">Subir</span>
    </span>

    <!-- Con foto: al pasar el mouse muestra que se puede cambiar -->
    <span
      v-if="imagen && !subiendo"
      class="absolute inset-0 flex flex-col items-center justify-center gap-0.5 bg-black/55 text-white opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 transition-opacity duration-150"
      aria-hidden="true"
    >
      <i class="fa-solid fa-camera text-sm"></i>
      <span class="text-[10px] font-bold leading-none">Cambiar</span>
    </span>

    <!-- Subiendo -->
    <span v-if="subiendo" class="absolute inset-0 flex items-center justify-center bg-surface/70" aria-hidden="true">
      <i class="fa-solid fa-spinner fa-spin text-[#ff8c42]"></i>
    </span>

    <input ref="input" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1" @click.stop @change="subir($event.target.files[0])">
  </button>
</template>
