<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import ShopLayout from '@/Layouts/Shop/ShopLayout.vue';
import ProductCard from '@/Components/Shop/ProductCard.vue';

const props = defineProps({
  products: Array
});

const page = usePage();
const selectedCategory = ref('Todos');
const selectedBrand = ref(null);
const currentPage = ref(1);
const itemsPerPage = 9;

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  const categoryParam = params.get('category');
  if (categoryParam) {
    selectedCategory.value = categoryParam;
  }
  currentPage.value = 1;
});

const categories = computed(() => {
  const nombres = [...new Set(props.products.map(p => p.category))].sort();
  return [
    { name: 'Todos', count: props.products.length },
    ...nombres.map(nombre => ({
      name: nombre,
      count: props.products.filter(p => p.category === nombre).length,
    })),
  ];
});

const availableBrands = computed(() => {
  let filtered = props.products;
  if (selectedCategory.value !== 'Todos') {
    filtered = filtered.filter(p => p.category === selectedCategory.value);
  }

  const brands = [...new Set(filtered.map(p => p.brand))];
  return brands.sort().map(brand => ({
    name: brand,
    count: filtered.filter(p => p.brand === brand).length
  }));
});

const filteredProducts = computed(() => {
  let filtered = props.products;

  if (selectedCategory.value !== 'Todos') {
    filtered = filtered.filter(p => p.category === selectedCategory.value);
  }

  if (selectedBrand.value) {
    filtered = filtered.filter(p => p.brand === selectedBrand.value);
  }

  return filtered;
});

const resetBrandFilter = () => {
  selectedBrand.value = null;
};

const handleCategoryChange = (category) => {
  selectedCategory.value = category;
  resetBrandFilter();
  currentPage.value = 1;
};

const totalPages = computed(() => {
  return Math.ceil(filteredProducts.value.length / itemsPerPage);
});

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredProducts.value.slice(start, end);
});

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};
</script>

<template>
  <Head title="Tienda" />
  <ShopLayout>
    <!-- Padding para el navbar fijo -->
    <div class="pt-20"></div>

    <!-- Tienda Header Premium -->
    <section class="relative py-16 px-4 md:px-8 lg:px-16 overflow-hidden">
      <!-- Background Gradient -->
      <div class="absolute inset-0 bg-gradient-to-b from-fg/5 via-transparent to-transparent"></div>
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-fg/5 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-fg/5 rounded-full blur-3xl"></div>

      <div class="relative max-w-full mx-auto">
        <div>
          <!-- Título Principal -->
          <h1 class="text-3xl md:text-4xl lg:text-5xl font-black uppercase tracking-tight italic leading-tight mb-4 max-w-4xl">
            Todos <span class="text-transparent bg-clip-text bg-gradient-to-r from-fg to-gray-500">Nuestros Productos</span>
          </h1>
          <div class="h-1.5 w-24 bg-gradient-to-r from-[#ff8c42] to-fg rounded-full"></div>
        </div>
      </div>
    </section>

    <!-- Contenedor Principal con Sidebar y Productos -->
    <section class="px-4 md:px-8 lg:px-16 max-w-[1600px] mx-auto pb-24">
      <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-8">

        <!-- Sidebar Filtros -->
        <aside class="md:col-span-1">
          <div class="sticky top-28 space-y-8">
            <!-- Filtro de Categorías -->
            <div>
              <h3 class="text-lg font-black uppercase tracking-tight mb-4 italic">
                Categorías
              </h3>

              <div class="space-y-2">
                <button
                  v-for="category in categories"
                  :key="category.name"
                  @click="handleCategoryChange(category.name)"
                  :class="[
                    'w-full text-left px-4 py-3 rounded-lg font-semibold text-sm transition-all',
                    selectedCategory === category.name
                      ? 'bg-[#ff8c42] text-black'
                      : 'bg-transparent border border-gray-700 text-gray-300 hover:border-[#ff8c42]/50 hover:text-fg'
                  ]"
                >
                  <div class="flex justify-between items-center">
                    <span>{{ category.name }}</span>
                    <span class="text-xs opacity-70">({{ category.count }})</span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Filtro de Marcas -->
            <div v-if="selectedCategory !== 'Todos' && availableBrands.length > 0">
              <h3 class="text-lg font-black uppercase tracking-tight mb-4 italic">
                Marcas
              </h3>

              <div class="space-y-2">
                <button
                  @click="resetBrandFilter"
                  :class="[
                    'w-full text-left px-4 py-3 rounded-lg font-semibold text-sm transition-all',
                    !selectedBrand
                      ? 'bg-[#ff8c42] text-black'
                      : 'bg-transparent border border-gray-700 text-gray-300 hover:border-[#ff8c42]/50 hover:text-fg'
                  ]"
                >
                  <span>Ver todas</span>
                </button>

                <button
                  v-for="brand in availableBrands"
                  :key="brand.name"
                  @click="selectedBrand = brand.name"
                  :class="[
                    'w-full text-left px-4 py-3 rounded-lg font-semibold text-sm transition-all',
                    selectedBrand === brand.name
                      ? 'bg-[#ff8c42] text-black'
                      : 'bg-transparent border border-gray-700 text-gray-300 hover:border-[#ff8c42]/50 hover:text-fg'
                  ]"
                >
                  <div class="flex justify-between items-center">
                    <span>{{ brand.name }}</span>
                    <span class="text-xs opacity-70">({{ brand.count }})</span>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </aside>

        <!-- Grid de Productos -->
        <div class="md:col-span-3 lg:col-span-4">
          <div class="mb-6">
            <p class="text-sm text-gray-400">
              Mostrando <span class="text-fg font-semibold">{{ filteredProducts.length }}</span>
              producto<span v-if="filteredProducts.length !== 1">s</span>
              <span v-if="selectedCategory !== 'Todos'" class="text-gray-500">
                en {{ selectedCategory }}<span v-if="selectedBrand"> - {{ selectedBrand }}</span>
              </span>
            </p>
          </div>

          <div v-if="filteredProducts.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <ProductCard
              v-for="product in paginatedProducts"
              :key="product.id"
              :product="product"
            />
          </div>

          <div v-else class="py-20 text-center">
            <p class="text-gray-500 text-lg">No hay productos en esta categoría</p>
          </div>

          <!-- Paginación -->
          <div v-if="totalPages > 1" class="mt-12 flex flex-col items-center gap-6">
            <div class="text-sm text-gray-400">
              Página <span class="text-fg font-semibold">{{ currentPage }}</span> de <span class="text-fg font-semibold">{{ totalPages }}</span>
            </div>

            <div class="flex gap-2 items-center">
              <!-- Botón Anterior -->
              <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-4 py-2 border border-gray-700 text-gray-300 rounded-lg hover:border-gray-500 hover:text-fg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                ← Anterior
              </button>

              <!-- Números de página -->
              <div class="flex gap-1">
                <button
                  v-for="page in totalPages"
                  :key="page"
                  @click="goToPage(page)"
                  :class="[
                    'px-3 py-2 rounded-lg font-semibold text-sm transition-colors',
                    currentPage === page
                      ? 'bg-[#ff8c42] text-black'
                      : 'border border-gray-700 text-gray-300 hover:border-[#ff8c42]/50 hover:text-fg'
                  ]"
                >
                  {{ page }}
                </button>
              </div>

              <!-- Botón Siguiente -->
              <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-4 py-2 border border-gray-700 text-gray-300 rounded-lg hover:border-gray-500 hover:text-fg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Siguiente →
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </ShopLayout>
</template>
