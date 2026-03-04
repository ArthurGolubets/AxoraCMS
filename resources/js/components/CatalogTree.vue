<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Каталог товаров</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Управление категориями и товарами</p>
      </div>
      <div class="flex space-x-3">
        <ThemeButton
          variant="secondary"
          @click="viewMode = viewMode === 'tree' ? 'list' : 'tree'"
        >
          <svg v-if="viewMode === 'tree'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
          </svg>
          {{ viewMode === 'tree' ? 'Список' : 'Дерево' }}
        </ThemeButton>
        <ThemeButton
          variant="primary"
          @click="createRootCategory"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Создать категорию
        </ThemeButton>
        <ImportExportDropdown
          entityType="catalogs"
          entityName="категории"
          @file-selected="handleImportFile"
        />
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
      <div class="flex space-x-4">
        <div class="flex-1">
          <input
            v-model="search"
            @input="handleSearch"
            type="text"
            placeholder="Поиск по названию..."
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
          >
        </div>
      </div>
    </div>

    <!-- Tree View -->
    <div v-if="viewMode === 'tree'" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">
        Загрузка...
      </div>
      <div v-else-if="catalogs.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
        Категории не найдены
      </div>
      <div v-else class="p-4">
        <CatalogTreeNode
          v-for="catalog in catalogs"
          :key="catalog.id"
          :catalog="catalog"
          :level="0"
          @create-subcategory="handleCreateSubcategory"
          @create-product="handleCreateProduct"
          @view="handleViewCatalog"
          @edit="handleEditCatalog"
          @delete="handleDeleteCatalog"
          @toggle-active="handleToggleCatalogActive"
          @view-product="handleViewProduct"
          @edit-product="handleEditProduct"
          @delete-product="handleDeleteProduct"
          @toggle-product-active="handleToggleProductActive"
          @refresh="loadTree"
        />
      </div>
    </div>

    <!-- List View -->
    <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Родитель</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="catalog in flatCatalogs" :key="catalog.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ catalog.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ catalog.parent?.name || '—' }}</td>
            <td class="px-6 py-4 text-right text-sm">
              <button @click="handleViewCatalog(catalog)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">Просмотр</button>
              <button @click="handleEditCatalog(catalog)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">Редактировать</button>
              <button @click="handleDeleteCatalog(catalog)" class="text-red-600 hover:text-red-800 dark:text-red-400">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Import Preview Modal -->
    <ImportPreviewModal
      :show="showImportModal"
      :file="importFile"
      entityType="catalogs"
      @close="closeImportModal"
      @import="handleImport"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useModal } from '../composables/useModal';
import CatalogTreeNode from './CatalogTreeNode.vue';
import ImportExportDropdown from './ImportExportDropdown.vue';
import ThemeButton from './ThemeButton.vue';
import ImportPreviewModal from './ImportPreviewModal.vue';

const { confirm, success, error } = useModal();
const router = useRouter();

const catalogs = ref([]);
const loading = ref(false);
const search = ref('');
const viewMode = ref('tree');

watch(viewMode, () => {
  loadTree();
});

const flatCatalogs = computed(() => {
  // If in list mode, data is already flat with parent loaded
  if (viewMode.value === 'list') {
    return catalogs.value;
  }

  // For tree mode, flatten recursively
  const flatten = (items, parent = null) => {
    let result = [];
    for (const item of items) {
      result.push({ ...item, parent });
      if (item.children && item.children.length > 0) {
        result = result.concat(flatten(item.children, item));
      }
    }
    return result;
  };
  return flatten(catalogs.value);
});

const loadTree = async () => {
  loading.value = true;
  try {
    let url;
    if (search.value) {
      url = `/admin/api/catalogs?search=${encodeURIComponent(search.value)}`;
    } else if (viewMode.value === 'list') {
      url = '/admin/api/catalogs/list';
    } else {
      url = '/admin/api/catalogs/tree';
    }
    const response = await fetch(url);
    catalogs.value = await response.json();
  } catch (err) {
    console.error('Error loading catalogs:', err);
    await error('Ошибка при загрузке каталога');
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  loadTree();
};

const createRootCategory = () => {
  router.push('/catalog/create');
};

const handleCreateSubcategory = (parentId) => {
  router.push(`/catalog/create?parent_id=${parentId}`);
};

const handleCreateProduct = (catalogId) => {
  router.push(`/products/create?catalog_id=${catalogId}`);
};

const handleViewCatalog = (catalog) => {
  router.push(`/catalog/${catalog.id}`);
};

const handleEditCatalog = (catalog) => {
  router.push(`/catalog/${catalog.id}/edit`);
};

const handleToggleCatalogActive = async (catalog) => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/catalogs/${catalog.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        parent_id: catalog.parent_id,
        name: catalog.name,
        slug: catalog.slug,
        title: catalog.title || '',
        description: catalog.description || '',
        keywords: catalog.keywords || '',
        image: catalog.image || '',
        content: catalog.content || '',
        is_active: !catalog.is_active
      })
    });

    if (!response.ok) {
      throw new Error('Failed to toggle catalog status');
    }

    await loadTree();
    await success(catalog.is_active ? 'Категория отключена' : 'Категория включена');
  } catch (err) {
    console.error('Error toggling catalog:', err);
    await error('Ошибка при изменении статуса категории');
  }
};

const handleDeleteCatalog = async (catalog) => {
  const confirmed = await confirm(
    'Удалить категорию?',
    `Вы уверены, что хотите удалить категорию "${catalog.name}"? Это действие нельзя отменить.`
  );

  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/catalogs/${catalog.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      }
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Failed to delete catalog');
    }

    await loadTree();
    await success('Категория удалена');
  } catch (err) {
    console.error('Error deleting catalog:', err);
    await error(err.message || 'Ошибка при удалении категории');
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
    const response = await fetch('/admin/api/catalogs/import', {
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
      await loadTree();
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

const handleViewProduct = (product) => {
  router.push(`/products/${product.id}`);
};

const handleEditProduct = (product) => {
  router.push(`/products/${product.id}/edit`);
};

const handleToggleProductActive = async (product) => {
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
        catalog_id: product.catalog_id,
        name: product.name,
        slug: product.slug,
        title: product.title || '',
        description: product.description || '',
        keywords: product.keywords || '',
        price: product.price,
        old_price: product.old_price || null,
        sku: product.sku,
        tags: product.tags || [],
        is_new: product.is_new || false,
        is_hot: product.is_hot || false,
        is_recommended: product.is_recommended || false,
        is_active: !product.is_active,
        content: product.content || '',
        gallery: product.gallery || [],
        variants: product.variants || []
      })
    });

    if (!response.ok) {
      throw new Error('Failed to toggle product status');
    }

    await loadTree();
    await success(product.is_active ? 'Товар отключен' : 'Товар включен');
  } catch (err) {
    console.error('Error toggling product:', err);
    await error('Ошибка при изменении статуса товара');
  }
};

const handleDeleteProduct = async (product) => {
  const confirmed = await confirm(
    'Удалить товар?',
    `Вы уверены, что хотите удалить товар "${product.name}"?`
  );

  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    await fetch(`/admin/api/products/${product.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token,
      }
    });

    await loadTree();
    await success('Товар удален');
  } catch (err) {
    console.error('Error deleting product:', err);
    await error('Ошибка при удалении товара');
  }
};

onMounted(() => {
  loadTree();
});
</script>
