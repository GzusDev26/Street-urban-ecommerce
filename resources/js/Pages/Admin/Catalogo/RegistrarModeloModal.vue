<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SearchCreateInput from '@/Components/Admin/SearchCreateInput.vue';
import ModalShell from '@/Components/Admin/ModalShell.vue';
import FormField from '@/Components/Admin/FormField.vue';
import FormInput from '@/Components/Admin/FormInput.vue';
import FormTextarea from '@/Components/Admin/FormTextarea.vue';

const props = defineProps({
  categoriasExistentes: { type: Array, default: () => [] },
  marcasExistentes: { type: Array, default: () => [] },
  editando: { type: Object, default: null }, // { id, nombre, marca, categoria } o null para crear
});

const emit = defineEmits(['close']);

const form = ref({
  nombre: props.editando?.nombre || '',
  marca: props.editando?.marca || '',
  marcaDescripcion: '',
  categoria: props.editando?.categoria || '',
  categoriaDescripcion: '',
  descripcion: props.editando?.descripcion || '',
  color: 'Único',
  colorHex: '#000000',
  talla: 'Única',
  precio: '',
  stock: 0,
});

const isSaving = ref(false);
const error = ref('');

const categoriaEsNueva = computed(() => {
  const q = form.value.categoria.trim();
  return q.length > 0 && !props.categoriasExistentes.some(c => c.toLowerCase() === q.toLowerCase());
});

const marcaEsNueva = computed(() => {
  const q = form.value.marca.trim();
  return q.length > 0 && !props.marcasExistentes.some(m => m.toLowerCase() === q.toLowerCase());
});

const guardar = () => {
  if (!form.value.nombre || !form.value.marca || !form.value.categoria) {
    error.value = 'Completa categoría, marca y modelo.';
    return;
  }

  if (!props.editando && (!form.value.color.trim() || !form.value.talla.trim() || form.value.precio === '' || form.value.stock === '')) {
    error.value = 'Completa color, talla, precio y stock de la variante inicial.';
    return;
  }

  isSaving.value = true;
  error.value = '';

  const opciones = {
    onSuccess: () => emit('close'),
    onError: (errors) => {
      error.value = Object.values(errors)[0] || 'Ocurrió un error al guardar.';
    },
    onFinish: () => {
      isSaving.value = false;
    },
  };

  if (props.editando) {
    router.patch(route('admin.catalogo.update', props.editando.id), form.value, opciones);
  } else {
    router.post(route('admin.catalogo.store'), form.value, opciones);
  }
};
</script>

<template>
  <ModalShell
    :title="editando ? 'Editar modelo' : 'Nuevo modelo'"
    subtitle="Categoría, marca y modelo: la identidad del producto"
    :icon="editando ? 'fa-solid fa-pen' : 'fa-solid fa-shirt'"
    :submit-text="editando ? 'Guardar cambios' : 'Registrar modelo'"
    :saving="isSaving"
    :error="error"
    @close="emit('close')"
    @submit="guardar"
  >
    <FormField label="Categoría">
      <SearchCreateInput v-model="form.categoria" :options="categoriasExistentes" placeholder="Ej: Calzado" />
      <FormTextarea
        v-if="categoriaEsNueva"
        v-model="form.categoriaDescripcion"
        aria-label="Descripción de la categoría"
        placeholder="Descripción de la categoría (opcional)"
        rows="2"
        class="mt-2"
      />
    </FormField>

    <FormField label="Marca">
      <SearchCreateInput v-model="form.marca" :options="marcasExistentes" placeholder="Ej: Adidas" />
      <FormTextarea
        v-if="marcaEsNueva"
        v-model="form.marcaDescripcion"
        aria-label="Descripción de la marca"
        placeholder="Descripción de la marca (opcional)"
        rows="2"
        class="mt-2"
      />
    </FormField>

    <FormField label="Modelo">
      <FormInput v-model="form.nombre" type="text" placeholder="Ej: All Star" />
      <FormTextarea
        v-model="form.descripcion"
        aria-label="Descripción del modelo"
        placeholder="Descripción del modelo (opcional)"
        rows="2"
        class="mt-2"
      />
    </FormField>

    <section v-if="!editando" class="rounded-2xl bg-gray-50 border border-gray-100 p-4 space-y-4">
      <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg bg-white text-[#ff8c42] shadow-sm flex items-center justify-center flex-shrink-0">
          <i class="fa-solid fa-layer-group text-sm"></i>
        </div>
        <div>
          <h3 class="text-sm font-bold text-gray-900">Variante inicial</h3>
          <p class="text-xs text-gray-500 mt-0.5">Si el producto es de tipo único, conserva los valores predeterminados.</p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <FormField label="Color">
          <FormInput v-model="form.color" required maxlength="50" />
        </FormField>

        <FormField label="Código de color" optional group>
          <label class="flex items-center gap-2.5 h-11 px-2.5 rounded-xl border border-gray-200 bg-white hover:border-gray-300 cursor-pointer transition">
            <input v-model="form.colorHex" type="color" aria-label="Código de color" class="w-7 h-7 rounded-lg border-0 p-0 bg-transparent cursor-pointer">
            <span class="text-sm text-gray-600 font-mono uppercase">{{ form.colorHex }}</span>
          </label>
        </FormField>

        <FormField label="Talla">
          <FormInput v-model="form.talla" required maxlength="20" />
        </FormField>

        <FormField label="Precio de venta">
          <FormInput v-model="form.precio" type="number" min="0.01" step="0.01" required placeholder="0.00" prefix="S/" />
        </FormField>

        <FormField label="Stock inicial" class="col-span-2">
          <FormInput v-model="form.stock" type="number" min="0" step="1" required />
        </FormField>
      </div>
    </section>
  </ModalShell>
</template>
