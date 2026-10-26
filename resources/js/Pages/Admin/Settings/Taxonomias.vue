<template>
  <Head title="Categorías y Marcas" />
  <AdminLayout>
    <template #breadcrumb>Configuración</template>
    <template #header>Categorías y Marcas</template>

    <div class="space-y-6">
      <div v-if="page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ page.props.flash.success }}
      </div>
      <div v-if="page.props.errors?.error" class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation"></i> {{ page.props.errors.error }}
      </div>

      <CategoryShowcase :categorias="categorias" />

      <p class="text-xs text-gray-400">
        Las categorías se crean aquí a propósito (no escribiéndolas al comprar) para evitar duplicados como «Polo» y «Polos»; cada una define las tallas que se ofrecen al comprar. Solo puedes eliminar las que no tengan productos asociados.
      </p>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <TaxonomyCard
          title="Categorías"
          icon="fa-layer-group"
          entity-label="categoría"
          with-size-type
          :items="categorias"
          :routes="{
            store: route('admin.categories.store'),
            update: (id) => route('admin.categories.update', id),
            destroy: (id) => route('admin.categories.destroy', id),
          }"
        />

        <TaxonomyCard
          title="Marcas"
          icon="fa-copyright"
          entity-label="marca"
          :items="marcas"
          :routes="{
            store: route('admin.brands.store'),
            update: (id) => route('admin.brands.update', id),
            destroy: (id) => route('admin.brands.destroy', id),
          }"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TaxonomyCard from '@/Components/Admin/TaxonomyCard.vue';
import CategoryShowcase from '@/Components/Admin/CategoryShowcase.vue';

defineProps({
  categorias: Array,
  marcas: Array,
});

const page = usePage();
</script>
