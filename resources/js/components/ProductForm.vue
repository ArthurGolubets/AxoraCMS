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
          <div v-for="(variant, index) in form.variants" :key="index" class="mb-6 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600">
            <div class="flex justify-between items-center mb-4">
              <span class="font-medium text-gray-900 dark:text-white text-lg">Вариант {{ index + 1 }}</span>
              <button type="button" @click="removeVariant(index)" class="text-red-600 hover:text-red-800"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <!-- Основные поля -->
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label><input v-model="variant.name" required class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></div>
              <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SKU *</label><input v-model="variant.sku" required class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></div>
              <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Цена *</label><input v-model.number="variant.price" type="number" step="0.01" required class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></div>
              <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Старая цена</label><input v-model.number="variant.old_price" type="number" step="0.01" class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></div>
            </div>

            <!-- Изображение -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Изображение варианта</label>
              <ImageUpload v-model="variant.image" />
            </div>

            <!-- Описание -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Описание</label>
              <textarea v-model="variant.description" rows="3" class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></textarea>
            </div>

            <!-- Свойства варианта -->
            <div class="mb-4">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Свойства варианта</h4>
              <ProductPropertiesForm
                  :available-properties="availableProperties"
                  :initial-values="variant.property_values || {}"
                  @update:values="(newValues) => { variant.property_values = newValues; }"
              />
            </div>

            <!-- Характеристики варианта -->
            <div>
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Характеристики варианта</h4>
              <ProductCharacteristics v-model="variant.addition_info" applies-to="variant" />
            </div>
          </div>
          <div class="flex gap-3">
            <button type="button" @click="addVariant" :style="buttonStyle" class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90 text-sm">+ Добавить вариант</button>
            <button type="button" @click="showProductSelectModal = true" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors text-sm">Создать вариант на основании товара</button>
          </div>
        </div>
      </div>

      <!-- Properties and Characteristics Tab -->
      <div v-if="activeTab === 'properties'" class="space-y-6">
        <ProductPropertiesForm
            :available-properties="availableProperties"
            :initial-values="form.property_values"
            @update:values="(newValues) => { console.log('ProductForm received property values:', newValues); form.property_values = newValues; }"
        />
        <ProductCharacteristics v-model="form.addition_info" applies-to="product" />
      </div>

      <!-- Content Tab -->
      <div v-show="activeTab === 'content'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <TinyMCEEditor v-model="form.content" label="Контент" :height="400" />
        </div>
      </div>

      <!-- Filters Tab -->
      <div v-show="activeTab === 'filters'" class="space-y-6">
        <ProductFiltersBlock
            v-if="form.catalog_id"
            :catalogId="form.catalog_id"
            :catalogName="selectedCatalogName"
            :initialValues="form.filter_values || []"
            :initialRangeValues="form.range_filter_values || {}"
            @update:filterValues="form.filter_values = $event"
            @update:rangeFilterValues="form.range_filter_values = $event"
            :initialEntityValues="form.entity_filter_values || {}"
            @update:entityFilterValues="form.entity_filter_values = $event"
            :initialStringValues="form.string_filter_values || {}"
            @update:stringFilterValues="form.string_filter_values = $event"
        />
        <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <p class="text-gray-500 dark:text-gray-400">Выберите категорию, чтобы увидеть доступные фильтры</p>
        </div>
      </div>

      <!-- Integration & Stock Tab -->
      <div v-show="activeTab === 'integration'" class="space-y-6">
        <div v-if="integration.commerceml_installed" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Интеграция и остатки</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Идентификатор 1С (1c_id)</label>
              <input
                  :value="integration.onec_id || '—'"
                  type="text"
                  disabled
                  class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 cursor-not-allowed"
              >
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Заполняется автоматически при обмене с 1С</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Остаток</label>
              <input
                  v-if="integration.can_edit_stock"
                  v-model.number="form.quantity"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
              <input
                  v-else
                  :value="integration.quantity ?? 0"
                  type="text"
                  disabled
                  class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 cursor-not-allowed"
              >
              <p v-if="!integration.can_edit_stock" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Редактирование остатка отключено в настройках сайта
              </p>
            </div>
          </div>
        </div>
        <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <p class="text-gray-500 dark:text-gray-400">У вас не установлена интеграция с остатками</p>
        </div>
      </div>

      <div class="flex justify-end space-x-3">
        <button type="button" @click="$router.back()" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium">Отмена</button>
        <button type="button" @click="handleSubmit(true)" :disabled="loading" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg font-medium disabled:opacity-50">{{ loading ? 'Сохранение...' : 'Сохранить и продолжить' }}</button>
        <button type="submit" :disabled="loading" :style="buttonStyle" class="px-6 py-3 text-white rounded-lg font-medium transition-opacity hover:opacity-90 disabled:opacity-50">{{ loading ? 'Сохранение...' : (isEdit ? 'Сохранить' : 'Создать') }}</button>
      </div>
    </form>

    <!-- Product Select Modal -->
    <teleport to="body">
      <transition name="modal">
        <div v-if="showProductSelectModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div @click="showProductSelectModal = false" class="fixed inset-0 transition-opacity bg-black bg-opacity-50"></div>
            <div class="relative z-10 w-full max-w-2xl p-6 mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-xl transform transition-all">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Выбрать товар для создания варианта</h3>

              <!-- Search -->
              <div class="mb-4">
                <input
                  v-model="productSearchQuery"
                  @input="searchProducts"
                  type="text"
                  placeholder="Начните вводить название товара..."
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                />
              </div>

              <!-- Products List -->
              <div v-if="searchedProducts.length > 0" class="max-h-96 overflow-y-auto mb-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div
                  v-for="product in searchedProducts"
                  :key="product.id"
                  @click="selectedProduct = product"
                  class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition-colors"
                  :class="{'bg-blue-50 dark:bg-blue-900/20': selectedProduct?.id === product.id}"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <img v-if="product.image" :src="product.image" class="w-12 h-12 object-cover rounded" />
                      <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center" v-else>
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                      </div>
                      <div class="text-left">
                        <div class="font-medium text-gray-900 dark:text-white">{{ product.name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ product.sku }}</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-semibold text-gray-900 dark:text-white">{{ product.price }} ₽</div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else-if="productSearchQuery" class="text-center py-8 text-gray-500 dark:text-gray-400">
                Товары не найдены
              </div>

              <!-- Deactivate toggle -->
              <div v-if="selectedProduct" class="mb-4 flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Деактивировать товар после создания варианта</span>
                <ToggleSwitch v-model="deactivateSourceProduct" />
              </div>

              <!-- Duplicate variant toggle -->
              <div v-if="selectedProduct" class="mb-4 flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Создать дубль вариант
                  <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">В выбранном товаре тоже создать вариант на основе текущего товара</span>
                </span>
                <ToggleSwitch v-model="createDuplicateVariant" />
              </div>

              <!-- Actions -->
              <div class="flex space-x-3">
                <button @click="showProductSelectModal = false" type="button" class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg transition">Отмена</button>
                <button @click="createVariantFromProduct" :disabled="!selectedProduct" type="button" :style="buttonStyle" class="flex-1 px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90 disabled:opacity-50">Создать вариант</button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
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
// Numeric id of the product currently being edited (null in create mode)
const currentProductId = computed(() =>
  route.params.id ? String(route.params.id).replace(/\/edit$/, '') : null
);
const categories = ref([]);
const categorySearch = ref('');
const filteredCategories = ref([]);
const activeTab = ref('main');

// Product search modal state
const showProductSelectModal = ref(false);
const productSearchQuery = ref('');
const searchedProducts = ref([]);
const selectedProduct = ref(null);
const deactivateSourceProduct = ref(true);
const createDuplicateVariant = ref(true);
const searchTimeout = ref(null);

const tabs = computed(() => {
  const list = [
    { id: 'main', label: 'Основное' },
    { id: 'seo', label: 'SEO' },
    { id: 'variants', label: 'Варианты' },
    { id: 'properties', label: 'Свойства и характеристики' },
    { id: 'content', label: 'Контент' },
    { id: 'filters', label: 'Фильтры' }
  ];
  if (isEdit.value) {
    list.push({ id: 'integration', label: 'Интеграция и остатки' });
  }
  return list;
});

// Integration / stock info (filled from the product API on edit)
const integration = ref({
  commerceml_installed: false,
  can_edit_stock: false,
  onec_id: null,
  quantity: null
});

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
  quantity: null,
  variants: [],
  filter_values: [],
  range_filter_values: {},
  addition_info: {},
  property_values: {},
  entity_filter_values: {},
  string_filter_values: {},
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
  form.value.variants.push({
    name: '',
    sku: '',
    price: 0,
    old_price: null,
    attributes: {},
    image: '',
    description: '',
    addition_info: {},
    property_values: {}
  });
};

const removeVariant = (index) => {
  form.value.variants.splice(index, 1);
};

const searchProducts = () => {
  // Clear previous timeout
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }

  // Debounce search
  searchTimeout.value = setTimeout(async () => {
    if (!productSearchQuery.value || productSearchQuery.value.length < 2) {
      searchedProducts.value = [];
      return;
    }

    try {
      let searchUrl = `/admin/api/products/search?q=${encodeURIComponent(productSearchQuery.value)}`;
      // Never offer the product being edited as a source for its own variant
      if (currentProductId.value) {
        searchUrl += `&exclude_id=${currentProductId.value}`;
      }
      const response = await fetch(searchUrl);
      if (!response.ok) throw new Error('Search failed');
      const data = await response.json();
      searchedProducts.value = data.products || [];
    } catch (err) {
      console.error('Error searching products:', err);
      error('Ошибка поиска товаров');
    }
  }, 300);
};

const createVariantFromProduct = async () => {
  if (!selectedProduct.value) return;

  // A product cannot be a variant of itself
  if (currentProductId.value && String(selectedProduct.value.id) === String(currentProductId.value)) {
    await error('Нельзя сделать товар вариантом самого себя');
    return;
  }

  try {
    // Create variant from selected product
    const newVariant = {
      name: selectedProduct.value.name,
      sku: selectedProduct.value.sku + '-variant',
      price: selectedProduct.value.price,
      old_price: selectedProduct.value.old_price,
      attributes: {},
      image: selectedProduct.value.main_image || '',
      description: selectedProduct.value.description || '',
      addition_info: selectedProduct.value.addition_info || {},
      property_values: selectedProduct.value.property_values || {}
    };

    form.value.variants.push(newVariant);

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Deactivate source product if toggle is on
    if (deactivateSourceProduct.value) {
      try {
        const deactivateResponse = await fetch(`/admin/api/products/${selectedProduct.value.id}/deactivate`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          }
        });

        if (!deactivateResponse.ok) {
          console.warn('Failed to deactivate product');
        }
      } catch (deactivateErr) {
        console.error('Error deactivating product:', deactivateErr);
      }
    }

    // Create the mirrored variant: the current product becomes a variant of the
    // selected product too.
    let duplicateWarning = false;
    if (createDuplicateVariant.value) {
      if (isEdit.value && currentProductId.value) {
        try {
          const dupResponse = await fetch(`/admin/api/products/${selectedProduct.value.id}/variants/from-product`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ based_on_product_id: Number(currentProductId.value) })
          });

          if (!dupResponse.ok) {
            console.warn('Failed to create duplicate variant');
          }
        } catch (dupErr) {
          console.error('Error creating duplicate variant:', dupErr);
        }
      } else {
        duplicateWarning = true;
      }
    }

    // Reset modal state
    showProductSelectModal.value = false;
    productSearchQuery.value = '';
    searchedProducts.value = [];
    selectedProduct.value = null;
    deactivateSourceProduct.value = true;
    createDuplicateVariant.value = true;

    if (duplicateWarning) {
      await success('Вариант добавлен. Дубль-вариант не создан: сначала сохраните текущий товар, затем добавьте вариант повторно.');
    } else {
      await success('Вариант успешно создан');
    }
  } catch (err) {
    console.error('Error creating variant:', err);
    error('Ошибка создания варианта');
  }
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

    // Extract range filter values
    const rangeFilterValues = product.range_filter_values || {};

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

    // Property values already formatted as {property_id: value} from backend
    const propertyValues = data.property_values || {};

    console.log('=== LOADING PRODUCT ===');
    console.log('API response property_values:', data.property_values);
    console.log('Available properties:', data.available_properties);
    console.log('Parsed propertyValues:', propertyValues);

    // Set available properties
    availableProperties.value = data.available_properties || [];

    // Process variants to extract property values
    const processedVariants = (product.variants || []).map(variant => {
      // Convert variant property_values from array format to object format
      const variantPropertyValues = {};
      if (variant.property_values && Array.isArray(variant.property_values)) {
        variant.property_values.forEach(pv => {
          if (pv.property_id && pv.value !== undefined) {
            // Try to parse JSON values (for multiple values)
            try {
              variantPropertyValues[pv.property_id] = JSON.parse(pv.value);
            } catch (e) {
              variantPropertyValues[pv.property_id] = pv.value;
            }
          }
        });
      }

      // Parse addition_info if needed
      let variantAdditionInfo = {};
      if (variant.addition_info) {
        if (typeof variant.addition_info === 'string') {
          try {
            const parsed = JSON.parse(variant.addition_info);
            variantAdditionInfo = Array.isArray(parsed) ? {} : parsed;
          } catch (e) {
            variantAdditionInfo = {};
          }
        } else if (typeof variant.addition_info === 'object') {
          variantAdditionInfo = Array.isArray(variant.addition_info) ? {} : variant.addition_info;
        }
      }

      return {
        ...variant,
        property_values: variantPropertyValues,
        addition_info: variantAdditionInfo,
        image: variant.image || '',
        description: variant.description || ''
      };
    });

    console.log('Processed variants:', processedVariants);

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
      variants: processedVariants,
      filter_values: filterValueIds,
      range_filter_values: rangeFilterValues,
      addition_info: additionInfo,
      property_values: propertyValues,
      entity_filter_values: product.entity_filter_values || {},
      string_filter_values: product.string_filter_values || {},
      quantity: data.integration?.quantity ?? null,
    };

    // Integration / stock block
    integration.value = data.integration || {
      commerceml_installed: false,
      can_edit_stock: false,
      onec_id: null,
      quantity: null
    };
  } catch (err) {
    await error('Ошибка при загрузке товара');
  }
};

const handleSubmit = async (stayParam) => {
  // stayParam is `true` only when triggered by "Сохранить и продолжить";
  // the native form submit passes an Event object instead.
  const stay = stayParam === true;

  loading.value = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Extract just the numeric ID from route params
    const productId = currentProductId.value;

    console.log('=== SUBMITTING PRODUCT ===');
    console.log('route.params.id raw:', route.params.id);
    console.log('productId cleaned:', productId);
    console.log('isEdit:', isEdit.value);
    console.log('route.path:', route.path);
    console.log('route.params:', route.params);

    // Use absolute URL to prevent redirects
    const baseUrl = window.location.origin;
    const apiPath = isEdit.value ? `/admin/api/products/${productId}` : '/admin/api/products';
    const url = `${baseUrl}${apiPath}`;
    const method = isEdit.value ? 'PUT' : 'POST';

    console.log('Base URL:', baseUrl);
    console.log('API Path:', apiPath);
    console.log('Final URL:', url);
    console.log('Method:', method);

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value),
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Failed to save product');
    }

    const saved = await response.json().catch(() => null);

    if (stay) {
      await success(isEdit.value ? 'Товар сохранён' : 'Товар создан');

      if (!isEdit.value && saved && saved.id) {
        // Switch the form into edit mode for the freshly created product
        await router.replace(`/products/${saved.id}/edit`);
      } else {
        await loadProduct();
      }
      return;
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

// Reload when the route id changes (e.g. after "Сохранить и продолжить" on a new
// product switches the form from create mode to edit mode without a remount).
watch(() => route.params.id, async (newId, oldId) => {
  if (newId && newId !== oldId) {
    await loadProduct();
  }
});

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