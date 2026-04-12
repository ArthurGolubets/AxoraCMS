<template>
  <div>
    <div class="mb-6 flex items-center space-x-3">
      <button @click="$router.back()" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </button>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ isEdit ? 'Редактировать категорию' : 'Создать категорию' }}
      </h2>
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
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Родительская категория</label>
              <input
                  v-model="categorySearch"
                  @input="filterCategories"
                  type="text"
                  placeholder="Поиск категории..."
                  class="w-full px-4 py-2 mb-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
              <select
                  v-model="form.parent_id"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
                <option :value="null">Корневая категория</option>
                <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label>
              <input
                  v-model="form.name"
                  @input="generateSlug"
                  type="text"
                  required
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug *</label>
              <input
                  v-model="form.slug"
                  type="text"
                  required
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
            </div>

            <div>
              <ImageUpload v-model="form.image" label="Изображение категории" />
            </div>

            <div>
              <ToggleSwitch v-model="form.is_active" label="Активна" />
            </div>
          </div>
        </div>
      </div>

      <!-- SEO Tab -->
      <div v-show="activeTab === 'seo'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">SEO</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title</label>
              <input
                  v-model="form.title"
                  type="text"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
              <textarea
                  v-model="form.description"
                  rows="3"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Keywords</label>
              <input
                  v-model="form.keywords"
                  type="text"
                  placeholder="Через запятую"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Content Tab -->
      <div v-show="activeTab === 'content'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <TinyMCEEditor v-model="form.content" label="Описание категории" :height="400" />
        </div>
      </div>

      <!-- Properties Tab -->
      <div v-show="activeTab === 'properties'" class="space-y-6">
        <CatalogPropertiesManager
            :catalog-id="route.params.id ? parseInt(route.params.id) : null"
            :initial-properties="form.properties"
            :inherited-properties="inheritedProperties"
            @update:properties="form.properties = $event"
        />
      </div>

      <!-- Characteristics Tab -->
      <div v-show="activeTab === 'characteristics'" class="space-y-6">
        <ProductCharacteristics v-model="form.addition_info" applies-to="catalog" />
      </div>

      <!-- Filters Tab (only for edit mode) -->
      <div v-show="activeTab === 'filters'" class="space-y-6">
        <CatalogFiltersBlock v-if="isEdit && route.params.id" :catalogId="route.params.id" />
        <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <p class="text-gray-500 dark:text-gray-400">Сохраните категорию, чтобы управлять фильтрами</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3">
        <button
            type="button"
            @click="$router.back()"
            class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition"
        >
          Отмена
        </button>
        <button
            type="submit"
            :disabled="loading"
            :style="buttonStyle"
            class="px-6 py-3 text-white rounded-lg font-medium transition-opacity hover:opacity-90 disabled:opacity-50"
        >
          {{ loading ? 'Сохранение...' : (isEdit ? 'Сохранить' : 'Создать') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import ImageUpload from './ImageUpload.vue';
import ToggleSwitch from './ToggleSwitch.vue';
import TinyMCEEditor from './TinyMCEEditor.vue';
import CatalogFiltersBlock from './CatalogFiltersBlock.vue';
import ProductCharacteristics from './ProductCharacteristics.vue';
import CatalogPropertiesManager from './CatalogPropertiesManager.vue';

const { success, error } = useModal();
const { buttonStyle } = useTheme();
const route = useRoute();
const router = useRouter();

const loading = ref(false);
const isEdit = computed(() => !!route.params.id);
const availableCategories = ref([]);
const categorySearch = ref('');
const filteredCategories = ref([]);
const activeTab = ref('main');

const tabs = [
  { id: 'main', label: 'Основное' },
  { id: 'seo', label: 'SEO' },
  { id: 'content', label: 'Контент' },
  { id: 'properties', label: 'Свойства' },
  { id: 'characteristics', label: 'Характеристики' },
  { id: 'filters', label: 'Фильтры' }
];

const form = ref({
  parent_id: null,
  name: '',
  slug: '',
  title: '',
  description: '',
  keywords: '',
  image: '',
  content: '',
  is_active: true,
  addition_info: {},
  properties: [],
});

const inheritedProperties = ref([]);

const generateSlug = () => {
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
};

const loadCategories = async () => {
  try {
    const response = await fetch('/admin/api/catalogs/list');
    const data = await response.json();
    availableCategories.value = data;
    filteredCategories.value = data;
  } catch (err) {
    console.error('Error loading categories:', err);
  }
};

const filterCategories = () => {
  if (!categorySearch.value) {
    filteredCategories.value = availableCategories.value;
  } else {
    const search = categorySearch.value.toLowerCase();
    filteredCategories.value = availableCategories.value.filter(cat =>
        cat.name.toLowerCase().includes(search)
    );
  }
};

const loadCatalog = async () => {
  try {
    const response = await fetch(`/admin/api/catalogs/${route.params.id}`);
    const data = await response.json();
    console.log('CatalogForm: Loaded catalog data:', data);

    // Parse addition_info if it's a string
    let additionInfo = {};
    if (data.catalog.addition_info) {
      console.log('CatalogForm: Raw addition_info:', data.catalog.addition_info, 'Type:', typeof data.catalog.addition_info);
      if (typeof data.catalog.addition_info === 'string') {
        try {
          const parsed = JSON.parse(data.catalog.addition_info);
          additionInfo = Array.isArray(parsed) ? {} : parsed;
          console.log('CatalogForm: Parsed addition_info:', additionInfo);
        } catch (e) {
          console.error('Failed to parse addition_info:', e);
          additionInfo = {};
        }
      } else if (typeof data.catalog.addition_info === 'object') {
        // Could be array (old format) or object (new format)
        additionInfo = Array.isArray(data.catalog.addition_info) ? {} : data.catalog.addition_info;
        console.log('CatalogForm: addition_info is object:', additionInfo);
      }
    }

    form.value = {
      parent_id: data.catalog.parent_id,
      name: data.catalog.name,
      slug: data.catalog.slug || '',
      title: data.catalog.title || '',
      description: data.catalog.description || '',
      keywords: data.catalog.keywords || '',
      image: data.catalog.image || '',
      content: data.catalog.content || '',
      is_active: data.catalog.is_active !== undefined ? data.catalog.is_active : true,
      addition_info: additionInfo,
      properties: data.properties || [],
    };
    console.log('[loadCatalog] done', {
      properties: data.properties,
      property_groups: data.property_groups,
    });
    inheritedProperties.value = data.inherited_properties || [];
    console.log('CatalogForm: Final form.value.addition_info:', form.value.addition_info);
  } catch (err) {
    console.error('Error loading catalog:', err);
    await error('Ошибка при загрузке категории');
  }
};

const handleSubmit = async () => {
  loading.value = true;
  try {
    // Ensure slug is generated if empty
    if (!form.value.slug || form.value.slug.trim() === '') {
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

      form.value.slug = form.value.name
          .split('')
          .map(char => translitMap[char] || char)
          .join('')
          .toLowerCase()
          .replace(/[^\w\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
          .trim();
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = isEdit.value ? `/admin/api/catalogs/${route.params.id}` : '/admin/api/catalogs';
    const method = isEdit.value ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
      },
      body: JSON.stringify(form.value),
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Failed to save catalog');
    }

    await success(isEdit.value ? 'Категория обновлена' : 'Категория создана');
    router.push('/catalog');
  } catch (err) {
    console.error('Error saving catalog:', err);
    await error(err.message || 'Ошибка при сохранении категории');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadCategories();

  if (isEdit.value) {
    loadCatalog();
  }

  // Set parent_id from query parameter
  if (route.query.parent_id) {
    form.value.parent_id = parseInt(route.query.parent_id);
  }
});
</script>