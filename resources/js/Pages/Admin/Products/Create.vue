<template>
  <Head title="Nuevo Producto" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Crear Producto
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <form @submit.prevent="submit">
              <!-- Nombre -->
              <div class="mb-4">
                <label for="create-1" class="block text-gray-700 text-sm font-bold mb-2">
                  Nombre *
                </label>
                <input id="create-1"
                  v-model="form.name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                  required
                />
                <span v-if="errors.name" class="text-red-500 text-sm">{{ errors.name }}</span>
              </div>

              <!-- SKU -->
              <div class="mb-4">
                <label for="create-2" class="block text-gray-700 text-sm font-bold mb-2">
                  SKU *
                </label>
                <input id="create-2"
                  v-model="form.sku"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                  required
                />
                <span v-if="errors.sku" class="text-red-500 text-sm">{{ errors.sku }}</span>
              </div>

              <!-- Descripción -->
              <div class="mb-4">
                <label for="create-3" class="block text-gray-700 text-sm font-bold mb-2">
                  Descripción
                </label>
                <textarea id="create-3"
                  v-model="form.description"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                  rows="4"
                ></textarea>
              </div>

              <!-- Categoría -->
              <div class="mb-4">
                <label for="create-4" class="block text-gray-700 text-sm font-bold mb-2">
                  Categoría *
                </label>
                <select id="create-4"
                  v-model="form.category_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                  required
                >
                  <option value="">Seleccionar categoría</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
                <span v-if="errors.category_id" class="text-red-500 text-sm">{{ errors.category_id }}</span>
              </div>

              <!-- Precio -->
              <div class="mb-4">
                <label for="create-5" class="block text-gray-700 text-sm font-bold mb-2">
                  Precio *
                </label>
                <input id="create-5"
                  v-model="form.price"
                  type="number"
                  step="0.01"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                  required
                />
                <span v-if="errors.price" class="text-red-500 text-sm">{{ errors.price }}</span>
              </div>

              <!-- Costo -->
              <div class="mb-4">
                <label for="create-6" class="block text-gray-700 text-sm font-bold mb-2">
                  Costo
                </label>
                <input id="create-6"
                  v-model="form.cost"
                  type="number"
                  step="0.01"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                />
              </div>

              <!-- Stock -->
              <div class="mb-4">
                <label for="create-7" class="block text-gray-700 text-sm font-bold mb-2">
                  Stock *
                </label>
                <input id="create-7"
                  v-model="form.stock"
                  type="number"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                  required
                />
                <span v-if="errors.stock" class="text-red-500 text-sm">{{ errors.stock }}</span>
              </div>

              <!-- URL de imagen -->
              <div class="mb-4">
                <label for="create-8" class="block text-gray-700 text-sm font-bold mb-2">
                  URL de imagen
                </label>
                <input id="create-8"
                  v-model="form.image_url"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                />
              </div>

              <!-- Activo -->
              <div class="mb-6">
                <label class="inline-flex items-center">
                  <input
                    v-model="form.active"
                    type="checkbox"
                    class="form-checkbox"
                  />
                  <span class="ml-2">Activo</span>
                </label>
              </div>

              <!-- Botones -->
              <div class="flex gap-4">
                <button
                  type="submit"
                  class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded"
                >
                  Crear Producto
                </button>
                <Link
                  :href="route('admin.products.index')"
                  class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                >
                  Cancelar
                </Link>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  categories: Array,
});

const { data: form, errors, post } = useForm({
  name: '',
  sku: '',
  description: '',
  category_id: '',
  price: '',
  cost: '',
  stock: '',
  image_url: '',
  active: true,
});

const submit = () => {
  post(route('admin.products.store'));
};
</script>
