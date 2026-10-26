<script setup>
import { computed, ref } from 'vue';
import InitialsAvatar from '@/Components/Admin/InitialsAvatar.vue';
import { dinero, porcentaje } from './format';

// Podio de personas o empresas (clientes, proveedores): avatar, monto y su parte del total.
const props = defineProps({
  titulo: { type: String, required: true },
  icono: { type: String, required: true },
  filas: { type: Array, required: true }, // [{ label, total, detalle }]
  total: { type: Number, required: true },
  limite: { type: Number, default: 5 },
});

const abierto = ref(false);
const visibles = computed(() => (abierto.value ? props.filas : props.filas.slice(0, props.limite)));
const medalla = (i) => ['bg-amber-100 text-amber-700', 'bg-gray-200 text-gray-600', 'bg-orange-100 text-orange-700'][i] ?? null;
</script>

<template>
  <section class="viz bg-surface rounded-2xl border border-gray-100 shadow-sm p-6">
    <header class="flex items-center gap-2.5 mb-4">
      <span class="w-9 h-9 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center" aria-hidden="true"><i :class="icono" class="text-sm"></i></span>
      <h3 class="font-bold text-gray-900">{{ titulo }}</h3>
    </header>

    <ol v-if="filas.length" class="divide-y divide-gray-100">
      <li v-for="(f, i) in visibles" :key="f.label" class="flex items-center gap-3 py-3">
        <div class="relative">
          <InitialsAvatar :name="f.label" :id="i" />
          <span v-if="medalla(i)" :class="['absolute -top-1 -left-1 w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center ring-2 ring-[rgb(var(--surface))]', medalla(i)]" aria-hidden="true">{{ i + 1 }}</span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-gray-900 truncate">{{ f.label }}</p>
          <p v-if="f.detalle" class="text-xs text-gray-500 truncate">{{ f.detalle }}</p>
        </div>
        <div class="text-right tabular-nums">
          <b class="block text-sm text-gray-900">{{ dinero(f.total) }}</b>
          <span class="text-xs text-gray-500">{{ porcentaje(f.total, total) }}</span>
        </div>
      </li>
    </ol>
    <p v-else class="text-sm text-gray-500">Sin datos en este período.</p>

    <button v-if="filas.length > limite" type="button" class="mt-3 text-sm font-semibold text-[#e67e2d] hover:underline" :aria-expanded="abierto" @click="abierto = !abierto">
      {{ abierto ? 'Ver menos' : `Ver los ${filas.length}` }}
    </button>
  </section>
</template>
