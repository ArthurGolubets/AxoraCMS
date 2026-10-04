<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Менеджер цен</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Массово повысьте или понизьте цены выбранных товаров и целых категорий</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
      <!-- 1. What to change -->
      <div class="xl:col-span-2 space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
          <div class="px-6 pt-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">1. Что изменить</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Можно сочетать: категории целиком и отдельные товары из любых категорий</p>
          </div>
          <div class="px-6 mt-4 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-6">
              <button
                v-for="tab in [{ id: 'catalogs', label: 'Категории', count: selectedCatalogIds.length }, { id: 'products', label: 'Товары', count: selectedProductList.length }]"
                :key="tab.id"
                type="button"
                @click="activeTab = tab.id"
                :class="['py-3 px-1 border-b-2 text-sm font-medium transition-colors', activeTab === tab.id ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300']"
              >
                {{ tab.label }}
                <span v-if="tab.count" class="ml-1 px-1.5 py-0.5 text-[11px] rounded-full bg-gray-100 dark:bg-gray-700">{{ tab.count }}</span>
              </button>
            </nav>
          </div>

          <!-- Categories -->
          <div v-show="activeTab === 'catalogs'" class="p-6 space-y-4">
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
              <input v-model="catalogSearch" type="text" placeholder="Поиск категории..." :class="[inputClass, 'flex-1']">
              <ToggleSwitch v-model="includeSubcategories" :theme-color="themeColor" label="С подкатегориями" />
            </div>
            <div class="max-h-[28rem] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
              <div v-if="!catalogRows.length" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">Категории не найдены</div>
              <label
                v-for="row in catalogRows"
                :key="row.id"
                class="flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40"
                :class="{ 'opacity-60': row.inherited }"
                :style="{ paddingLeft: `${(catalogSearch ? 0 : row.depth) * 20 + 12}px` }"
              >
                <input
                  type="checkbox"
                  :checked="row.checked || row.inherited"
                  :disabled="row.inherited"
                  @change="toggleCatalog(row.id)"
                  class="w-4 h-4 rounded"
                >
                <span class="flex-1 min-w-0 text-sm text-gray-900 dark:text-white truncate">
                  {{ row.name }}
                  <span v-if="catalogSearch && row.path" class="text-xs text-gray-400"> — {{ row.path }}</span>
                </span>
                <span v-if="row.inherited" class="text-[11px] text-gray-400">через родителя</span>
              </label>
            </div>
          </div>

          <!-- Products -->
          <div v-show="activeTab === 'products'" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <input v-model="productSearch" @input="onProductSearch" type="text" placeholder="Название или артикул..." :class="inputClass">
              <CategorySelect v-model="productCatalogFilter" :categories="catalogs" clearable placeholder="Все категории" picker-title="Показать товары категории" />
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
              <div class="flex items-center justify-between gap-3 px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" :checked="allOnPageSelected" :disabled="!productPage.length" @change="toggleAllOnPage" class="w-4 h-4 rounded">
                  Выбрать все на странице
                </label>
                <span>Найдено: {{ productPagination.total }}</span>
              </div>
              <div v-if="productsLoading" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">Загрузка...</div>
              <div v-else-if="!productPage.length" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">Товары не найдены</div>
              <div v-else class="max-h-[24rem] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                <label v-for="product in productPage" :key="product.id" class="flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40">
                  <input type="checkbox" :checked="!!selectedProducts[product.id]" @change="toggleProduct(product)" class="w-4 h-4 rounded">
                  <div class="flex-1 min-w-0">
                    <div class="text-sm text-gray-900 dark:text-white truncate">{{ product.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                      <span class="font-mono">{{ product.sku }}</span><template v-if="product.catalog"> · {{ product.catalog.name }}</template>
                    </div>
                  </div>
                  <span class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ money(product.price) }}</span>
                </label>
              </div>
              <div v-if="productPagination.last_page > 1" class="flex items-center justify-between px-3 py-2 border-t border-gray-200 dark:border-gray-700 text-sm">
                <button type="button" :disabled="productPagination.current_page <= 1" @click="loadProducts(productPagination.current_page - 1)" class="px-3 py-1 rounded text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40">← Назад</button>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ productPagination.current_page }} из {{ productPagination.last_page }}</span>
                <button type="button" :disabled="productPagination.current_page >= productPagination.last_page" @click="loadProducts(productPagination.current_page + 1)" class="px-3 py-1 rounded text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40">Вперёд →</button>
              </div>
            </div>

            <div v-if="selectedProductList.length">
              <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Выбрано товаров: {{ selectedProductList.length }}</p>
                <button type="button" @click="selectedProducts = {}" class="text-xs text-gray-500 hover:text-red-600">Очистить</button>
              </div>
              <div class="flex flex-wrap gap-2">
                <span v-for="product in selectedProductList" :key="product.id" class="inline-flex items-center gap-1 pl-2.5 pr-1 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                  {{ product.name }}
                  <button type="button" @click="toggleProduct(product)" class="p-0.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600" title="Убрать">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Preview -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">3. Предпросмотр</h3>
            <span v-if="previewLoading" class="text-xs text-gray-500 dark:text-gray-400">Пересчёт...</span>
          </div>
          <div v-if="!hasSelection" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Выберите товары или категории</div>
          <div v-else-if="!valueIsValid" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Укажите, на сколько изменить цену</div>
          <div v-else-if="preview && !preview.rows.length" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">В выбранных категориях нет товаров</div>
          <div v-else-if="preview" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                  <th class="px-6 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Товар</th>
                  <th class="px-6 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Было</th>
                  <th class="px-6 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Станет</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <template v-for="row in preview.rows" :key="row.id">
                  <tr>
                    <td class="px-6 py-2.5">
                      <div class="text-sm text-gray-900 dark:text-white">{{ row.name }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400"><span class="font-mono">{{ row.sku }}</span><template v-if="row.catalog"> · {{ row.catalog }}</template></div>
                    </td>
                    <td class="px-6 py-2.5 text-right text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ money(row.price) }}</td>
                    <td class="px-6 py-2.5 text-right text-sm font-medium whitespace-nowrap" :class="diffClass(row)">{{ money(row.new_price) }}</td>
                  </tr>
                  <tr v-for="variant in row.variants" :key="`v${variant.id}`" class="bg-gray-50/50 dark:bg-gray-900/20">
                    <td class="pl-10 pr-6 py-1.5 text-xs text-gray-600 dark:text-gray-400">↳ {{ variant.name }} <span class="font-mono text-gray-400">{{ variant.sku }}</span></td>
                    <td class="px-6 py-1.5 text-right text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ money(variant.price) }}</td>
                    <td class="px-6 py-1.5 text-right text-xs font-medium whitespace-nowrap" :class="diffClass(variant)">{{ money(variant.new_price) }}</td>
                  </tr>
                </template>
              </tbody>
            </table>
            <p v-if="preview.products > preview.rows.length" class="px-6 py-3 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700">
              Показаны первые {{ preview.rows.length }} из {{ preview.products }} товаров — изменения применятся ко всем.
            </p>
          </div>
        </div>
      </div>

      <!-- 2. How to change + summary -->
      <div class="space-y-6 xl:sticky xl:top-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-5">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">2. Как изменить</h3>

          <div class="grid grid-cols-2 gap-1 p-1 rounded-lg bg-gray-100 dark:bg-gray-900">
            <button
              v-for="option in [{ value: 'increase', label: 'Повысить' }, { value: 'decrease', label: 'Понизить' }]"
              :key="option.value"
              type="button"
              @click="settings.direction = option.value"
              class="py-2 text-sm font-medium rounded-md transition-colors"
              :class="settings.direction === option.value ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
            >{{ option.label }}</button>
          </div>

          <div>
            <label :class="labelClass">На сколько</label>
            <div class="flex">
              <input v-model.number="settings.value" type="number" min="0" step="0.01" placeholder="0" :class="[inputClass, 'rounded-r-none']">
              <div class="flex shrink-0 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg overflow-hidden">
                <button
                  v-for="option in [{ value: 'percent', label: '%' }, { value: 'fixed', label: '₽' }]"
                  :key="option.value"
                  type="button"
                  @click="settings.mode = option.value"
                  class="w-12 text-sm font-medium transition-colors"
                  :class="settings.mode === option.value ? 'text-white' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                  :style="settings.mode === option.value ? { backgroundColor: themeColor } : {}"
                >{{ option.label }}</button>
              </div>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ exampleText }}</p>
          </div>

          <div>
            <label :class="labelClass">Округление</label>
            <select v-model="settings.rounding" :class="inputClass">
              <option value="0">Без округления (до копеек)</option>
              <option value="1">До целых рублей</option>
              <option value="10">До десятков</option>
              <option value="100">До сотен</option>
            </select>
          </div>

          <div>
            <label :class="labelClass">Старая цена</label>
            <select v-model="settings.old_price" :class="inputClass">
              <option value="keep">Не менять</option>
              <option value="previous">Записать текущую цену как старую (зачёркнутую)</option>
              <option value="clear">Очистить</option>
            </select>
          </div>

          <ToggleSwitch v-model="settings.apply_to_variants" :theme-color="themeColor" label="Менять цены вариантов" />
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <p class="text-sm text-gray-500 dark:text-gray-400">Будет изменено</p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ preview?.products ?? 0 }} <span class="text-base font-normal text-gray-500 dark:text-gray-400">товаров</span></p>
          <p v-if="settings.apply_to_variants" class="text-sm text-gray-500 dark:text-gray-400">и {{ preview?.variants ?? 0 }} вариантов</p>
          <ThemeButton variant="primary" class="w-full mt-5" :disabled="!canApply || applying" @click="applyChanges">
            {{ applying ? 'Применение...' : 'Применить изменения' }}
          </ThemeButton>
        </div>
      </div>
    </div>

    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import ThemeButton from './ThemeButton.vue';
import ToggleSwitch from './ToggleSwitch.vue';
import ConfirmModal from './ConfirmModal.vue';
import CategorySelect from './CategorySelect.vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';

const { success, error } = useModal();
const { themeColor } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const activeTab = ref('catalogs');
const confirmModal = ref(null);

// --- Categories --------------------------------------------------------------

const catalogs = ref([]);
const catalogSearch = ref('');
const selectedCatalogIds = ref([]);
const includeSubcategories = ref(true);

const childrenByParent = computed(() => {
  const map = {};
  catalogs.value.forEach((cat) => {
    (map[cat.parent_id ?? 'root'] ||= []).push(cat);
  });
  Object.values(map).forEach((list) => list.sort((a, b) => a.name.localeCompare(b.name, 'ru')));
  return map;
});

const catalogById = computed(() => Object.fromEntries(catalogs.value.map((c) => [c.id, c])));

const pathOf = (cat) => {
  const parts = [];
  let parent = catalogById.value[cat.parent_id];
  while (parent) {
    parts.unshift(parent.name);
    parent = catalogById.value[parent.parent_id];
  }
  return parts.join(' / ');
};

/**
 * Categories implicitly included because an ancestor is selected (with subcategories).
 */
const inheritedIds = computed(() => {
  const result = new Set();
  if (!includeSubcategories.value) return result;
  const walk = (id) => (childrenByParent.value[id] || []).forEach((child) => {
    result.add(child.id);
    walk(child.id);
  });
  selectedCatalogIds.value.forEach(walk);
  return result;
});

const catalogRows = computed(() => {
  const selected = new Set(selectedCatalogIds.value);
  const decorate = (cat, depth) => ({
    id: cat.id,
    name: cat.name,
    depth,
    path: pathOf(cat),
    checked: selected.has(cat.id),
    inherited: !selected.has(cat.id) && inheritedIds.value.has(cat.id),
  });

  const q = catalogSearch.value.trim().toLowerCase();
  if (q) {
    return catalogs.value.filter((c) => c.name.toLowerCase().includes(q)).map((c) => decorate(c, 0));
  }

  const rows = [];
  const walk = (parentKey, depth) => (childrenByParent.value[parentKey] || []).forEach((cat) => {
    rows.push(decorate(cat, depth));
    walk(cat.id, depth + 1);
  });
  walk('root', 0);
  return rows;
});

const toggleCatalog = (id) => {
  const index = selectedCatalogIds.value.indexOf(id);
  if (index === -1) {
    selectedCatalogIds.value.push(id);
  } else {
    selectedCatalogIds.value.splice(index, 1);
  }
};

const loadCatalogs = async () => {
  try {
    const response = await fetch('/admin/api/catalogs/list', { headers: { Accept: 'application/json' } });
    if (response.ok) catalogs.value = await response.json();
  } catch (e) {
    error('Не удалось загрузить категории');
  }
};

// --- Products ----------------------------------------------------------------

const productSearch = ref('');
const productCatalogFilter = ref(null);
const productPage = ref([]);
const productPagination = ref({ current_page: 1, last_page: 1, total: 0 });
const productsLoading = ref(false);
const selectedProducts = ref({});
let productSearchTimer = null;

const selectedProductList = computed(() => Object.values(selectedProducts.value));
const allOnPageSelected = computed(() => productPage.value.length > 0 && productPage.value.every((p) => selectedProducts.value[p.id]));

const loadProducts = async (page = 1) => {
  productsLoading.value = true;
  try {
    const params = new URLSearchParams({ page, per_page: 30 });
    if (productSearch.value) params.append('search', productSearch.value);
    if (productCatalogFilter.value) params.append('catalog_id', productCatalogFilter.value);
    const response = await fetch(`/admin/api/products?${params}`, { headers: { Accept: 'application/json' } });
    if (response.ok) {
      const data = await response.json();
      productPage.value = data.data || [];
      productPagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total };
    }
  } catch (e) {
    error('Не удалось загрузить товары');
  } finally {
    productsLoading.value = false;
  }
};

const onProductSearch = () => {
  clearTimeout(productSearchTimer);
  productSearchTimer = setTimeout(() => loadProducts(1), 300);
};

const toggleProduct = (product) => {
  const next = { ...selectedProducts.value };
  if (next[product.id]) {
    delete next[product.id];
  } else {
    next[product.id] = { id: product.id, name: product.name, sku: product.sku };
  }
  selectedProducts.value = next;
};

const toggleAllOnPage = () => {
  const next = { ...selectedProducts.value };
  const selectAll = !allOnPageSelected.value;
  productPage.value.forEach((p) => {
    if (selectAll) next[p.id] = { id: p.id, name: p.name, sku: p.sku };
    else delete next[p.id];
  });
  selectedProducts.value = next;
};

watch(productCatalogFilter, () => loadProducts(1));

// --- Change settings & preview --------------------------------------------------

const settings = ref({
  direction: 'increase',
  mode: 'percent',
  value: null,
  rounding: '0',
  old_price: 'keep',
  apply_to_variants: true,
});

const preview = ref(null);
const previewLoading = ref(false);
const applying = ref(false);
let previewTimer = null;
let previewRequest = 0;

const hasSelection = computed(() => selectedCatalogIds.value.length > 0 || selectedProductList.value.length > 0);
const valueIsValid = computed(() => Number(settings.value.value) > 0
  && !(settings.value.mode === 'percent' && settings.value.direction === 'decrease' && Number(settings.value.value) > 100));
const canApply = computed(() => hasSelection.value && valueIsValid.value && (preview.value?.products || 0) > 0);

const money = (value) => `${Number(value || 0).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ₽`;

const diffClass = (row) => {
  if (row.new_price > row.price) return 'text-green-600 dark:text-green-400';
  if (row.new_price < row.price) return 'text-red-600 dark:text-red-400';
  return 'text-gray-500 dark:text-gray-400';
};

const exampleText = computed(() => {
  const value = Number(settings.value.value) || 0;
  if (!value) return 'Например: повысить на 5% или понизить на 500 ₽';
  const base = 1000;
  const delta = settings.value.mode === 'percent' ? base * value / 100 : value;
  const result = Math.max(0, settings.value.direction === 'decrease' ? base - delta : base + delta);
  return `Цена ${money(base)} станет ${money(result)}`;
});

const payload = () => ({
  product_ids: selectedProductList.value.map((p) => p.id),
  catalog_ids: selectedCatalogIds.value,
  include_subcategories: includeSubcategories.value,
  direction: settings.value.direction,
  mode: settings.value.mode,
  value: Number(settings.value.value),
  rounding: settings.value.rounding,
  old_price: settings.value.old_price,
  apply_to_variants: settings.value.apply_to_variants,
});

const post = (url) => fetch(url, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    Accept: 'application/json',
  },
  body: JSON.stringify(payload()),
});

const loadPreview = async () => {
  if (!hasSelection.value || !valueIsValid.value) {
    preview.value = null;
    return;
  }
  const requestId = ++previewRequest;
  previewLoading.value = true;
  try {
    const response = await post('/admin/api/price-manager/preview');
    const data = await response.json().catch(() => ({}));
    if (requestId !== previewRequest) return;
    if (!response.ok) throw new Error(data.message || 'Не удалось рассчитать предпросмотр');
    preview.value = data;
  } catch (e) {
    if (requestId === previewRequest) error(e.message);
  } finally {
    if (requestId === previewRequest) previewLoading.value = false;
  }
};

// Recalculate the preview shortly after any change of the selection or settings.
watch([selectedCatalogIds, selectedProducts, includeSubcategories, settings], () => {
  clearTimeout(previewTimer);
  previewTimer = setTimeout(loadPreview, 400);
}, { deep: true });

const applyChanges = async () => {
  const unit = settings.value.mode === 'percent' ? '%' : ' ₽';
  const action = settings.value.direction === 'increase' ? 'повышены' : 'понижены';
  const variantsText = settings.value.apply_to_variants ? ` и ${preview.value.variants} вариантов` : '';
  const confirmed = await confirmModal.value.open({
    title: 'Применить изменение цен?',
    message: `Цены ${preview.value.products} товаров${variantsText} будут ${action} на ${settings.value.value}${unit}. Отменить это действие нельзя.`,
    confirmText: 'Применить',
  });
  if (!confirmed) return;

  applying.value = true;
  try {
    const response = await post('/admin/api/price-manager/apply');
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.message || 'Не удалось изменить цены');

    await success(`Цены обновлены: товаров — ${data.products}, вариантов — ${data.variants}.`);
    settings.value.value = null;
    preview.value = null;
    loadProducts(productPagination.value.current_page);
  } catch (e) {
    error(e.message);
  } finally {
    applying.value = false;
  }
};

onMounted(() => {
  loadCatalogs();
  loadProducts();
});
</script>
