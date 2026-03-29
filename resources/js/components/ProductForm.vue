<template>
  <div>
    <div class="mb-6 flex items-center space-x-3">
      <button @click="$router.back()" class="text-gray-600 dark:text-gray-400">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </button>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isEdit ? 'Редактировать товар' : 'Создать товар' }}</h2>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          type="button"
          :class="[
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Main Info Tab -->
      <div v-show="activeTab === 'main'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Основная информация</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Категория *</label>
              <input
                v-model="categorySearch"
                @input="filterCategories"
                type="text"
                placeholder="Поиск категории..."
                class="w-full px-4 py-2 mb-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
              <select v-model="form.catalog_id" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label>
              <input v-model="form.name" @input="generateSlug" type="text" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug *</label>
              <input v-model="form.slug" type="text" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Артикул (SKU) *</label>
              <input v-model="form.sku" type="text" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Цена *</label>
              <input v-model.number="form.price" type="number" step="0.01" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Старая цена</label>
              <input v-model.number="form.old_price" type="number" step="0.01" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            </div>
            <div class="md:col-span-2">
              <ImageUpload v-model="form.main_image" label="Главное изображение" />
            </div>
            <div class="md:col-span-2">
              <GalleryUpload v-model="form.gallery" label="Галерея изображений" />
            </div>
            <div class="md:col-span-2 flex items-center space-x-6">
              <ToggleSwitch v-model="form.is_new" label="Новинка" />
              <ToggleSwitch v-model="form.is_hot" label="Хит" />
              <ToggleSwitch v-model="form.is_recommended" label="Рекомендуем" />
              <ToggleSwitch v-model="form.is_active" label="Активен" />
            </div>
          </div>
        </div>
      </div>

      <!-- SEO Tab -->
      <div v-show="activeTab === 'seo'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">SEO</h3>
          <div class="space-y-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title</label><input v-model="form.title" type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label><textarea v-model="form.description" rows="3" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Keywords</label><input v-model="form.keywords" type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></div>
          </div>
        </div>
      </div>

      <!-- Variants Tab -->
      <div v-show="activeTab === 'variants'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Варианты товара</h3>
          <div v-for="(variant, index) in form.variants" :key="index" class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex justify-between items-center mb-2">
              <span class="font-medium text-gray-900 dark:text-white">Вариант {{ index + 1 }}</span>
              <button type="button" @click="removeVariant(index)" class="text-red-600 hover:text-red-800"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <input v-model="variant.name" placeholder="Название" required class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
              <input v-model="variant.sku" placeholder="SKU" required class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
              <input v-model.number="variant.price" placeholder="Цена" type="number" step="0.01" required class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
              <input v-model.number="variant.old_price" placeholder="Старая цена" type="number" step="0.01" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            </div>
          </div>
          <button type="button" @click="addVariant" :style="buttonStyle" class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90 text-sm">+ Добавить вариант</button>
        </div>
      </div>

      <!-- Properties Tab -->
      <div v-if="activeTab === 'properties'" class="space-y-6">
        <ProductPropertiesForm
          :available-properties="availableProperties"
          :initial-values="form.property_values"
          @update:values="form.property_values = $event"
        />
      </div>

      <!-- Content Tab -->
      <div v-show="activeTab === 'content'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <TinyMCEEditor v-model="form.content" label="Контент" :height="400" />
        </div>
      </div>

      <!-- Characteristics Tab -->
      <div v-show="activeTab === 'characteristics'" class="space-y-6">
        <ProductCharacteristics v-model="form.addition_info" applies-to="product" />
      </div>

      <!-- Filters Tab -->
      <div v-show="activeTab === 'filters'" class="space-y-6">
        <ProductFiltersBlock
          v-if="form.catalog_id"
          :catalogId="form.catalog_id"
          :catalogName="selectedCatalogName"
          :initialValues="form.filter_values || []"
          @update:filterValues="form.filter_values = $event"
        />
        <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <p class="text-gray-500 dark:text-gray-400">Выберите категорию, чтобы увидеть доступные фильтры</p>
        </div>
      </div>

      <div class="flex justify-end space-x-3">
        <button type="button" @click="$router.back()" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium">Отмена</button>
        <button type="submit" :disabled="loading" :style="buttonStyle" class="px-6 py-3 text-white rounded-lg font-medium transition-opacity hover:opacity-90 disabled:opacity-50">{{ loading ? 'Сохранение...' : (isEdit ? 'Сохранить' : 'Создать') }}</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import ImageUpload from './ImageUpload.vue';
import GalleryUpload from './GalleryUpload.vue';
import ToggleSwitch from './ToggleSwitch.vue';
import ProductFiltersBlock from './ProductFiltersBlock.vue';
import TinyMCEEditor from './TinyMCEEditor.vue';
import ProductCharacteristics from './ProductCharacteristics.vue';
import ProductPropertiesForm from './ProductPropertiesForm.vue';

const { success, error } = useModal();
const { buttonStyle } = useTheme();
const route = useRoute();
const router = useRouter();

const loading = ref(false);
const isEdit = computed(() => !!route.params.id);
const categories = ref([]);
const categorySearch = ref('');
const filteredCategories = ref([]);
const activeTab = ref('main');

const tabs = [
  { id: 'main', label: 'Основное' },
  { id: 'seo', label: 'SEO' },
  { id: 'variants', label: 'Варианты' },
  { id: 'properties', label: 'Свойства' },
  { id: 'content', label: 'Контент' },
  { id: 'characteristics', label: 'Характеристики' },
  { id: 'filters', label: 'Фильтры' }
];

const form = ref({
  catalog_id: null,
  name: '',
  slug: '',
  title: '',
  description: '',
  keywords: '',
  price: 0,
  old_price: null,
  sku: '',
  main_image: '',
  tags: [],
  is_new: false,
  is_hot: false,
  is_recommended: false,
  is_active: true,
  content: '',
  gallery: [],
  variants: [],
  filter_values: [],
  addition_info: {},
  property_values: {},
});

const availableProperties = ref([]);

const selectedCatalogName = computed(() => {
  const catalog = categories.value.find(c => c.id === form.value.catalog_id);
  return catalog ? catalog.name : '';
});

const generateSlug = () => {
  if (!isEdit.value) {
    const translitMap = {
      'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo',
      'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm',
      'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
      'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '',
      'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya',
      'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Е': 'E', 'Ё': 'Yo',
      'Ж': 'Zh', 'З': 'Z', 'И': 'I', 'Й': 'Y', 'К': 'K', 'Л': 'L', 'М': 'M',
      'Н': 'N', 'О': 'O', 'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U',
      'Ф': 'F', 'Х': 'H', 'Ц': 'Ts', 'Ч': 'Ch', 'Ш': 'Sh', 'Щ': 'Sch', 'Ъ': '',
      'Ы': 'Y', 'Ь': '', 'Э': 'E', 'Ю': 'Yu', 'Я': 'Ya'
    };

    const slug = form.value.name
      .split('')
      .map(char => translitMap[char] || char)
      .join('')
      .toLowerCase()
      .replace(/[^\w\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim();
    form.value.slug = slug;
  }
};

const addVariant = () => {
  form.value.variants.push({ name: '', sku: '', price: 0, old_price: null, attributes: {} });
};

const removeVariant = (index) => {
  form.value.variants.splice(index, 1);
};

const loadCategories = async () => {
  try {
    const response = await fetch('/admin/api/catalogs/list');
    const data = await response.json();
    categories.value = data;
    filteredCategories.value = categories.value;
  } catch (err) {
    console.error('Error loading categories:', err);
  }
};

const filterCategories = () => {
  if (!categorySearch.value) {
    filteredCategories.value = categories.value;
  } else {
    const search = categorySearch.value.toLowerCase();
    filteredCategories.value = categories.value.filter(cat =>
      cat.name.toLowerCase().includes(search)
    );
  }
};

// Load properties when catalog changes
const loadCatalogProperties = async (catalogId) => {
  if (!catalogId) {
    availableProperties.value = [];
    return;
  }

  try {
    const response = await fetch(`/admin/api/catalogs/${catalogId}`);
    if (!response.ok) return;

    const data = await response.json();
    availableProperties.value = data.all_properties || [];
  } catch (err) {
    console.error('Error loading catalog properties:', err);
  }
};

// Watch for catalog_id changes to load properties
watch(() => form.value.catalog_id, async (newCatalogId, oldCatalogId) => {
  if (newCatalogId && newCatalogId !== oldCatalogId) {
    await loadCatalogProperties(newCatalogId);
    // Clear property values when catalog changes
    if (oldCatalogId) {
      form.value.property_values = {};
    }
  }
});

const loadProduct = async () => {
  try {
    const response = await fetch(`/admin/api/products/${route.params.id}`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    // API возвращает объект с ключом 'product'
    const product = data.product || data;

    // Extract filter_value_ids from filter_values relationship or assigned_filters
    const filterValueIds = product.filter_values?.map(fv => fv.id) ||
                          data.assigned_filters?.flatMap(f => f.values?.map(v => v.id) || []) || [];

    // Parse addition_info if it's a string
    let additionInfo = {};
    if (product.addition_info) {
      if (typeof product.addition_info === 'string') {
        try {
          const parsed = JSON.parse(product.addition_info);
          additionInfo = Array.isArray(parsed) ? {} : parsed;
        } catch (e) {
          additionInfo = {};
        }
      } else if (typeof product.addition_info === 'object') {
        // Could be array (old format) or object (new format)
        additionInfo = Array.isArray(product.addition_info) ? {} : product.addition_info;

      }
    }

    // Parse property values into object {property_id: value}
    const propertyValues = {};
    if (data.property_values) {
      data.property_values.forEach(pv => {
        const value = pv.value;
        // Try to parse JSON for multiple values
        try {
          propertyValues[pv.property.id] = JSON.parse(value);
        } catch (e) {
          propertyValues[pv.property.id] = value;
        }
      });
    }

    // Set available properties
    availableProperties.value = data.available_properties || [];

    form.value = {
      catalog_id: product.catalog_id,
      name: product.name || '',
      slug: product.slug || '',
      title: product.title || '',
      description: product.description || '',
      keywords: product.keywords || '',
      price: product.price || 0,
      old_price: product.old_price || null,
      sku: product.sku || '',
      main_image: product.main_image || '',
      tags: product.tags || [],
      is_new: product.is_new || false,
      is_hot: product.is_hot || false,
      is_recommended: product.is_recommended || false,
      is_active: product.is_active !== undefined ? product.is_active : true,
      content: product.content || '',
      gallery: product.gallery || [],
      variants: product.variants || [],
      filter_values: filterValueIds,
      addition_info: additionInfo,
      property_values: propertyValues
    };
  } catch (err) {
    await error('Ошибка при загрузке товара');
  }
};

const handleSubmit = async () => {
  loading.value = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = isEdit.value ? `/admin/api/products/${route.params.id}` : '/admin/api/products';
    const method = isEdit.value ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify(form.value),
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Failed to save product');
    }

    await success(isEdit.value ? 'Товар обновлен' : 'Товар создан');
    router.push('/catalog');
  } catch (err) {
    console.error('Error saving product:', err);
    await error(err.message || 'Ошибка при сохранении товара');
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadCategories();
  if (isEdit.value) {
    await loadProduct();
  } else if (route.query.catalog_id) {
    form.value.catalog_id = parseInt(route.query.catalog_id);
    await loadCatalogProperties(form.value.catalog_id);
  }
});
</script>
