<template>
  <div v-if="catalog">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div class="flex-1">
        <div class="flex items-center space-x-3 mb-2">
          <button @click="$router.back()" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </button>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ catalog.name }}</h2>
        </div>
        <nav class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
          <router-link to="/catalog" class="hover:text-gray-900 dark:hover:text-white">Каталог</router-link>
          <span>/</span>
          <span v-for="(crumb, index) in breadcrumbs" :key="crumb.id">
            <span v-if="index < breadcrumbs.length - 1">
              <span>{{ crumb.name }}</span>
              <span class="mx-2">/</span>
            </span>
            <span v-else class="font-medium text-gray-900 dark:text-white">{{ crumb.name }}</span>
          </span>
        </nav>
      </div>
      <button
        @click="$router.push(`/catalog/${catalog.id}/edit`)"
        :style="buttonStyle"
        class="px-4 py-2 text-white rounded-lg font-medium transition-opacity hover:opacity-90"
      >
        Редактировать
      </button>
    </div>

    <!-- Catalog Details -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-if="catalog.image" class="md:col-span-2">
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Изображение категории</h3>
          <img :src="getImageUrl(catalog.image)" :alt="catalog.name" class="w-64 h-64 object-cover rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
        </div>

        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">SEO Заголовок</h3>
          <p class="text-gray-900 dark:text-white">{{ catalog.title || '—' }}</p>
        </div>

        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Родительская категория</h3>
          <p class="text-gray-900 dark:text-white">{{ catalog.parent?.name || 'Корневая категория' }}</p>
        </div>

        <div class="md:col-span-2">
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Описание</h3>
          <p class="text-gray-900 dark:text-white">{{ catalog.description || '—' }}</p>
        </div>

        <div class="md:col-span-2">
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Ключевые слова</h3>
          <p class="text-gray-900 dark:text-white">{{ catalog.keywords || '—' }}</p>
        </div>

        <div v-if="catalog.content" class="md:col-span-2">
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Контент</h3>
          <div class="prose prose-sm dark:prose-invert max-w-none text-gray-900 dark:text-gray-100" v-html="catalog.content"></div>
        </div>
      </div>
    </div>

    <!-- Characteristics -->
    <div v-if="catalog.addition_info && catalog.addition_info.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Характеристики</h3>
      <div class="space-y-3">
        <div v-for="(char, index) in catalog.addition_info" :key="index" class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ char.name }}</span>
          <span class="text-sm text-gray-900 dark:text-white">
            <template v-if="char.type === 'boolean'">
              {{ char.value ? 'Да' : 'Нет' }}
            </template>
            <template v-else-if="char.multiple && char.values && char.values.length > 0">
              {{ char.values.join(', ') }}
            </template>
            <template v-else>
              {{ char.value }}
            </template>
          </span>
        </div>
      </div>
    </div>

    <!-- Filters Block -->
    <div class="mb-6">
      <CatalogFiltersBlock :catalogId="catalog.id" />
    </div>

    <!-- Products List -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Товары в категории</h3>
        <button
          @click="$router.push(`/products/create?catalog_id=${catalog.id}`)"
          :style="buttonStyle"
          class="px-4 py-2 text-white rounded-lg font-medium transition-opacity hover:opacity-90 text-sm"
        >
          Добавить товар
        </button>
      </div>

      <div v-if="catalog.products && catalog.products.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
        <div
          v-for="product in catalog.products"
          :key="product.id"
          @click="$router.push(`/products/${product.id}`)"
          class="py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition flex items-center justify-between"
        >
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <h4 class="font-medium text-gray-900 dark:text-white">{{ product.name }}</h4>
              <div class="flex items-center space-x-1">
                <span v-if="product.is_new" class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">Новинка</span>
                <span v-if="product.is_hot" class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded">Хит</span>
                <span v-if="product.is_recommended" class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded">Рекомендованный</span>
              </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Артикул: {{ product.sku }}</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <div class="flex items-center space-x-2">
                <span class="text-lg font-bold text-gray-900 dark:text-white">{{ product.price }} ₽</span>
                <span v-if="product.old_price" class="text-sm text-gray-500 line-through">{{ product.old_price }} ₽</span>
              </div>
              <span v-if="product.variants && product.variants.length > 0" class="text-xs text-gray-500 dark:text-gray-400">
                {{ product.variants.length }} вариант(ов)
              </span>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
        В этой категории пока нет товаров
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import CatalogFiltersBlock from './CatalogFiltersBlock.vue';

const { error } = useModal();
const { buttonStyle } = useTheme();
const route = useRoute();

const catalog = ref(null);
const breadcrumbs = ref([]);

const getImageUrl = (imageString) => {
  if (!imageString) return '';

  // If it's a base64 string or full URL, return as is
  if (imageString.startsWith('data:') || imageString.startsWith('http')) {
    return imageString;
  }

  // If it's already a storage path
  if (imageString.startsWith('/storage/') || imageString.startsWith('storage/')) {
    return imageString.startsWith('/') ? imageString : '/' + imageString;
  }

  // Otherwise, it's a relative path from storage (e.g., "catalogs/image.jpg")
  // Prepend /storage/
  return '/storage/' + imageString;
};

const loadCatalog = async () => {
  try {
    const response = await fetch(`/admin/api/catalogs/${route.params.id}`);
    const data = await response.json();
    catalog.value = data.catalog;
    breadcrumbs.value = data.breadcrumbs;
  } catch (err) {
    console.error('Error loading catalog:', err);
    await error('Ошибка при загрузке категории');
  }
};

onMounted(() => {
  loadCatalog();
});
</script>
