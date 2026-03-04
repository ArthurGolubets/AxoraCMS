<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Список товаров</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Управление товарами</p>
      </div>
      <div class="flex items-center space-x-3">
        <ThemeButton variant="primary" @click="$router.push('/products/create')">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Создать товар
        </ThemeButton>
        <ImportExportDropdown
          entityType="products"
          entityName="товары"
          @file-selected="handleImportFile"
        />
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input v-model="filters.search" @input="() => loadProducts(true)" type="text" placeholder="Поиск по названию, SKU..." class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
        <select v-model="filters.catalog_id" @change="() => loadProducts(true)" class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
          <option :value="null">Все категории</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>
      <div class="flex items-center space-x-6 mt-4">
        <ToggleSwitch v-model="filters.is_new" @update:modelValue="() => loadProducts(true)" label="Новинки" />
        <ToggleSwitch v-model="filters.is_hot" @update:modelValue="() => loadProducts(true)" label="Хиты" />
        <ToggleSwitch v-model="filters.is_recommended" @update:modelValue="() => loadProducts(true)" label="Рекомендованные" />
      </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>
      <div v-else-if="products.data && products.data.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">Товары не найдены</div>
      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Товар</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">SKU</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Категория</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Цена</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Статус</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4">
              <div class="font-medium text-gray-900 dark:text-white">{{ product.name }}</div>
              <div class="flex items-center space-x-2 mt-1">
                <span v-if="product.is_new" class="px-2 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">Новинка</span>
                <span v-if="product.is_hot" class="px-2 py-0.5 text-xs bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded">Хит</span>
                <span v-if="product.is_recommended" class="px-2 py-0.5 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded">Рекомендуем</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ product.sku }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ product.catalog?.name || '—' }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ product.price }} ₽</div>
              <div v-if="product.old_price" class="text-xs text-gray-500 line-through">{{ product.old_price }} ₽</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
              <button
                @click="handleToggleActive(product)"
                :class="product.is_active ? 'text-green-600' : 'text-gray-400'"
                class="hover:opacity-75 transition"
                :title="product.is_active ? 'Активен' : 'Неактивен'"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="product.is_active" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </button>
            </td>
            <td class="px-6 py-4 text-right text-sm">
              <button @click="$router.push(`/products/${product.id}`)" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 mr-3">Просмотр</button>
              <button @click="$router.push(`/products/${product.id}/edit`)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">Редактировать</button>
              <button @click="handleDelete(product)" class="text-red-600 hover:text-red-800 dark:text-red-400">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="products.data && products.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Показано {{ products.from }} - {{ products.to }} из {{ products.total }}
        </div>
        <div class="flex space-x-2">
          <button @click="changePage(products.current_page - 1)" :disabled="products.current_page === 1" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">Назад</button>
          <button @click="changePage(products.current_page + 1)" :disabled="products.current_page === products.last_page" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">Вперед</button>
        </div>
      </div>
    </div>

    <!-- Import Preview Modal -->
    <ImportPreviewModal
      :show="showImportModal"
      :file="importFile"
      entityType="products"
      @close="closeImportModal"
      @import="handleImport"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import ToggleSwitch from './ToggleSwitch.vue';
import ImportExportDropdown from './ImportExportDropdown.vue';
import ThemeButton from './ThemeButton.vue';
import ImportPreviewModal from './ImportPreviewModal.vue';

const { confirm, success, error } = useModal();
const { themeColor } = useTheme();

const loading = ref(false);
const products = ref({ data: [] });
const categories = ref([]);
const filters = ref({
  search: '',
  catalog_id: null,
  is_new: false,
  is_hot: false,
  is_recommended: false,
  page: 1
});

const loadProducts = async (resetPage = false) => {
  if (resetPage) {
    filters.value.page = 1;
  }

  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.search) params.append('search', filters.value.search);
    if (filters.value.catalog_id) params.append('catalog_id', filters.value.catalog_id);
    if (filters.value.is_new) params.append('is_new', '1');
    if (filters.value.is_hot) params.append('is_hot', '1');
    if (filters.value.is_recommended) params.append('is_recommended', '1');
    params.append('page', filters.value.page);

    const response = await fetch(`/admin/api/products?${params}`);
    products.value = await response.json();
  } catch (err) {
    console.error('Error loading products:', err);
    await error('Ошибка при загрузке товаров');
  } finally {
    loading.value = false;
  }
};

const loadCategories = async () => {
  try {
    const response = await fetch('/admin/api/catalogs/list');
    const data = await response.json();
    categories.value = data;
  } catch (err) {
    console.error('Error loading categories:', err);
  }
};

const changePage = (page) => {
  filters.value.page = page;
  loadProducts();
};

const handleToggleActive = async (product) => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/products/${product.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        ...product,
        is_active: !product.is_active
      })
    });

    if (!response.ok) {
      throw new Error('Failed to toggle product status');
    }

    await success(product.is_active ? 'Товар отключен' : 'Товар включен');
    loadProducts();
  } catch (err) {
    console.error('Error toggling product:', err);
    await error('Ошибка при изменении статуса товара');
  }
};

const handleDelete = async (product) => {
  const confirmed = await confirm('Удалить товар?', `Вы уверены, что хотите удалить товар "${product.name}"?`);
  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    await fetch(`/admin/api/products/${product.id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': token }
    });
    await success('Товар удален');
    loadProducts();
  } catch (err) {
    console.error('Error deleting product:', err);
    await error('Ошибка при удалении товара');
  }
};

const showImportModal = ref(false);
const importFile = ref(null);

const handleImportFile = (file) => {
  importFile.value = file;
  showImportModal.value = true;
};

const handleImport = async (previewData) => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/products/import', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({
        items: previewData.preview
      })
    });

    const result = await response.json();

    if (result.success) {
      await success(`Импорт завершен! Создано: ${result.created}, Обновлено: ${result.updated}`);
      showImportModal.value = false;
      importFile.value = null;
      await loadProducts();
    } else {
      await error('Ошибка при импорте');
    }
  } catch (err) {
    console.error('Import error:', err);
    await error('Ошибка при импорте');
  }
};

const closeImportModal = () => {
  showImportModal.value = false;
  importFile.value = null;
};

onMounted(() => {
  loadProducts();
  loadCategories();
});
</script>
