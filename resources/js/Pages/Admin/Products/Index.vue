<template>
  <Head title="Productos" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Gestionar Productos
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-bold">Productos</h3>
              <Link
                :href="route('admin.products.create')"
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded"
              >
                + Nuevo Producto
              </Link>
            </div>

            <!-- Búsqueda -->
            <div class="mb-6">
              <input aria-label="Buscar productos"
                v-model="searchQuery"
                type="text"
                placeholder="Buscar productos..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg"
              />
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto">
              <table class="min-w-full border-collapse border border-gray-300">
                <thead class="bg-gray-100">
                  <tr>
                    <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Nombre</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">SKU</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Precio</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Stock</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Categoría</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-gray-50">
                    <td class="border border-gray-300 px-4 py-2">{{ product.id }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ product.name }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ product.sku }}</td>
                    <td class="border border-gray-300 px-4 py-2">S/ {{ product.price }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ product.stock }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ product.category?.name }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                      <Link
                        :href="route('admin.products.edit', product.id)"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded mr-2"
                      >
                        Editar
                      </Link>
                      <button
                        @click="deleteProduct(product.id)"
                        class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded"
                      >
                        Eliminar
                      </button>
                    </td>
                  </tr>
                  <tr v-if="filteredProducts.length === 0">
                    <td colspan="7" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                      No hay productos
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  products: Array,
  categories: Array,
});

const searchQuery = ref('');

const filteredProducts = computed(() => {
  if (!searchQuery.value) return props.products;
  return props.products.filter(p =>
    p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    p.sku.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const deleteProduct = (id) => {
  if (confirm('¿Está seguro de que desea eliminar este producto?')) {
    router.delete(route('admin.products.destroy', id));
  }
};
</script>
