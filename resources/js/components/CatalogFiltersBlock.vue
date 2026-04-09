<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Фильтры категории</h3>

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
      Фильтры, созданные для этой категории, будут доступны для всех товаров в этой категории и её подкатегориях.
      Глобальные фильтры доступны автоматически.
    </p>

    <!-- Category Filters List -->
    <div v-if="categoryFilters.length > 0" class="mb-6">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
        Фильтры этой категории ({{ categoryFilters.length }})
      </h4>
      <div class="space-y-2">
        <div
          v-for="filter in categoryFilters"
          :key="filter.id"
          class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg"
        >
          <div class="flex items-center gap-3 flex-1">
            <span class="px-2 py-1 text-xs font-medium rounded"
              :class="{
                'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200': filter.type === 'select',
                'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200': filter.type === 'checkbox',
                'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200': filter.type === 'range'
              }">
              {{ typeLabels[filter.type] }}
            </span>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ filter.name }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 font-mono">{{ filter.code }}</p>
            </div>
            <span v-if="!filter.is_active" class="px-2 py-1 text-xs font-medium rounded bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
              Неактивен
            </span>
          </div>
          <div class="flex gap-2">
            <button
              @click="editFilter(filter)"
              class="p-2 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors"
              title="Редактировать"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            <button
              @click="confirmDeleteFilter(filter)"
              class="p-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
              title="Удалить"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Inherited Filters -->
    <div v-if="inheritedFilters.length > 0" class="mb-6">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
        Унаследованные фильтры ({{ inheritedFilters.length }})
      </h4>
      <div class="space-y-2">
        <div
          v-for="filter in inheritedFilters"
          :key="filter.id"
          class="flex items-center gap-3 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg"
        >
          <span class="px-2 py-1 text-xs font-medium rounded"
            :class="{
              'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200': filter.type === 'select',
              'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200': filter.type === 'checkbox',
              'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200': filter.type === 'range'
            }">
            {{ typeLabels[filter.type] }}
          </span>
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ filter.name }}</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">
              <span class="font-mono">{{ filter.code }}</span>
              <span class="mx-2">•</span>
              <span>из категории: {{ filter.catalog?.name || 'Родительская' }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Global Filters Info -->
    <div class="mb-6 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
      <p class="text-xs text-gray-600 dark:text-gray-400">
        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Глобальные фильтры автоматически доступны для всех товаров и не отображаются здесь.
        Управлять ими можно в разделе "Фильтры".
      </p>
    </div>

    <!-- Add Filter Button -->
    <ThemeButton variant="primary" size="sm" @click="showFilterModal = true">
      <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Добавить фильтр
    </ThemeButton>

    <!-- Create/Edit Filter Modal -->
    <Modal v-if="showFilterModal" @close="closeFilterModal" size="large">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ editingFilter ? 'Редактировать фильтр' : 'Создать фильтр' }}
        </h3>
      </template>
      <template #body>
        <form @submit.prevent="saveFilter" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Название фильтра *
              </label>
              <input
                v-model="filterForm.name"
                @input="generateCodeFromName"
                type="text"
                required
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                placeholder="Например: Объем данных"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Символьный код *
              </label>
              <input
                v-model="filterForm.code"
                type="text"
                required
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono"
                placeholder="storage_capacity"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Тип фильтра *
              </label>
              <select
                v-model="filterForm.type"
                required
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
                <option value="select">Выбор (dropdown)</option>
                <option value="checkbox">Флажки (multiple)</option>
                <option value="range">Диапазон (min-max)</option>
                <option value="entity">Привязка к сущности (entity)</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Порядок сортировки
              </label>
              <input
                v-model.number="filterForm.sort"
                type="number"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Описание
              </label>
              <textarea
                v-model="filterForm.description"
                rows="2"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                placeholder="Описание фильтра (необязательно)"
              ></textarea>
            </div>

            <div class="flex items-center">
              <input
                v-model="filterForm.is_active"
                type="checkbox"
                id="filter_is_active"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
              />
              <label for="filter_is_active" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                Фильтр активен
              </label>
            </div>
          </div>

          <!-- Filter Values (not needed for range type) -->
          <div v-if="filterForm.type !== 'range' && filterForm.type !== 'entity'" class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex items-center justify-between mb-3">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Значения фильтра
              </label>
              <button
                @click="addFilterValue"
                type="button"
                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
              >
                + Добавить значение
              </button>
            </div>

            <div v-if="filterForm.values.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
              Нет значений. Добавьте хотя бы одно значение.
            </div>

            <div v-else class="space-y-2 max-h-60 overflow-y-auto">
              <div
                v-for="(value, index) in filterForm.values"
                :key="index"
                class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-900 rounded"
              >
                <input
                  v-model="value.value"
                  type="text"
                  required
                  class="flex-1 px-3 py-1 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
                  placeholder="Значение"
                />
                <input
                  v-model="value.code"
                  type="text"
                  class="w-32 px-3 py-1 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white font-mono"
                  placeholder="Код"
                />
                <input
                  v-model.number="value.sort"
                  type="number"
                  class="w-20 px-3 py-1 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
                  placeholder="500"
                />
                <input
                  v-model="value.is_active"
                  type="checkbox"
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                  title="Активно"
                />
                <button
                  @click="removeFilterValue(index)"
                  type="button"
                  class="p-1 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Range Settings -->
          <div v-if="filterForm.type === 'range'" class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex items-center justify-between mb-3">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Настройки диапазона
              </label>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">От</label>
                <input
                    v-model.number="rangeFrom"
                    type="number"
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                    placeholder="0"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">До</label>
                <input
                    v-model.number="rangeTo"
                    type="number"
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                    placeholder="1000000"
                />
              </div>
            </div>
          </div>
          <div v-if="filterForm.type === 'entity'" class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="mb-3">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Тип сущности *
              </label>
              <select v-model="filterForm.settings.entity_type"
                      class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                <option value="">— Выберите тип —</option>
                <option value="infoblock">Элемент инфоблока</option>
                <option value="product">Товар</option>
                <option value="catalog">Категория</option>
              </select>
            </div>
            <div v-if="filterForm.settings.entity_type === 'infoblock'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Инфоблок *
              </label>
              <select v-model="filterForm.settings.entity_id"
                      class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                <option :value="null">— Выберите инфоблок —</option>
                <option v-for="ib in infoBlocks" :key="ib.id" :value="ib.id">
                  {{ ib.name }}
                </option>
              </select>
            </div>
          </div>
        </form>
      </template>
      <template #footer>
        <ThemeButton variant="secondary" @click="closeFilterModal">
          Отмена
        </ThemeButton>
        <ThemeButton variant="primary" @click="saveFilter" :disabled="saving">
          <span v-if="saving">Сохранение...</span>
          <span v-else>{{ editingFilter ? 'Сохранить изменения' : 'Создать фильтр' }}</span>
        </ThemeButton>
      </template>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal v-if="deleteModal.show" @close="deleteModal.show = false">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Подтверждение удаления</h3>
      </template>
      <template #body>
        <p class="text-gray-700 dark:text-gray-300">
          Вы действительно хотите удалить фильтр <strong>{{ deleteModal.filter?.name }}</strong>?
        </p>
        <p class="text-sm text-red-600 dark:text-red-400 mt-2">
          Все значения фильтра и связи с товарами также будут удалены.
        </p>
      </template>
      <template #footer>
        <ThemeButton variant="secondary" @click="deleteModal.show = false">
          Отмена
        </ThemeButton>
        <ThemeButton variant="danger" @click="deleteFilter" :disabled="deleting">
          <span v-if="deleting">Удаление...</span>
          <span v-else>Удалить</span>
        </ThemeButton>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import ThemeButton from './ThemeButton.vue';
import Modal from './Modal.vue';

const props = defineProps({
  catalogId: {
    type: [Number, String],
    default: null
  }
});

const categoryFilters = ref([]);
const inheritedFilters = ref([]);
const showFilterModal = ref(false);
const editingFilter = ref(null);
const saving = ref(false);
const deleteModal = ref({ show: false, filter: null });
const deleting = ref(false);
const infoBlocks = ref([])

const typeLabels = {
  select: 'Выбор',
  checkbox: 'Флажки',
  range: 'Диапазон',
  entity: 'Привязка к сущности'
};

const filterForm = ref({
  name: '',
  code: '',
  type: 'select',
  sort: 500,
  is_active: true,
  description: '',
  values: [],
  settings: {},
});

const rangeFrom = computed({
  get: () => filterForm.value.values.find(v => v.code === 'from')?.value ?? '',
  set: (val) => {
    const idx = filterForm.value.values.findIndex(v => v.code === 'from');
    if (idx !== -1) {
      filterForm.value.values[idx].value = val;
    } else {
      filterForm.value.values.push({ value: val, code: 'from', sort: 100, is_active: true });
    }
  }
});

const rangeTo = computed({
  get: () => filterForm.value.values.find(v => v.code === 'to')?.value ?? '',
  set: (val) => {
    const idx = filterForm.value.values.findIndex(v => v.code === 'to');
    if (idx !== -1) {
      filterForm.value.values[idx].value = val;
    } else {
      filterForm.value.values.push({ value: val, code: 'to', sort: 200, is_active: true });
    }
  }
});

const loadFilters = async () => {
  if (!props.catalogId) return;

  try {
    const response = await fetch(`/admin/api/filters/for-catalog/${props.catalogId}`, {
      headers: { 'Accept': 'application/json' },
    });

    if (response.ok) {
      const data = await response.json();

      // Separate category-specific and inherited filters
      categoryFilters.value = data.filter(f => f.catalog_id === parseInt(props.catalogId));
      inheritedFilters.value = data.filter(f => f.catalog_id !== parseInt(props.catalogId) && f.catalog_id !== null);
    }
  } catch (error) {
    console.error('Failed to load filters:', error);
  }
};

const loadInfoBlocks = async () => {
  try {
    const response = await fetch('/admin/api/infoblocks', {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      infoBlocks.value = data.data || data;
    }
  } catch (error) {
    console.error('Failed to load infoblocks:', error);
  }
};


const generateCodeFromName = () => {
  if (filterForm.value.name) {
    filterForm.value.code = filterForm.value.name
      .toLowerCase()
      .replace(/[а-яё]/g, (char) => {
        const translit = {
          'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo',
          'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm',
          'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
          'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch',
          'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya'
        };
        return translit[char] || char;
      })
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');
  }
};

const addFilterValue = () => {
  filterForm.value.values.push({
    value: '',
    code: '',
    sort: 500,
    is_active: true,
  });
};

const removeFilterValue = (index) => {
  filterForm.value.values.splice(index, 1);
};

const editFilter = (filter) => {
  editingFilter.value = filter;
  filterForm.value = {
    name: filter.name,
    code: filter.code,
    type: filter.type,
    sort: filter.sort || 500,
    is_active: filter.is_active,
    description: filter.description || '',
    values: filter.values ? [...filter.values] : [],
    settings: filter.settings || {}
  };
  showFilterModal.value = true;
};

const closeFilterModal = () => {
  showFilterModal.value = false;
  editingFilter.value = null;
  filterForm.value = {
    name: '',
    code: '',
    type: 'select',
    sort: 500,
    is_active: true,
    description: '',
    values: [],
    settings: {},
  };
};

const saveFilter = async () => {
  console.log('=== SAVING FILTER ===');
  console.log('filterForm.value.type:', filterForm.value.type);
  console.log('filterForm.value.values.length:', filterForm.value.values.length);

  // Range type doesn't need values
  if (filterForm.value.type !== 'range' && filterForm.value.type !== 'entity' && filterForm.value.values.length === 0) {
    console.log('VALIDATION FAILED: No values for non-range filter');
    alert('Добавьте хотя бы одно значение фильтра');
    return;
  }

  console.log('VALIDATION PASSED');

  saving.value = true;
  try {
    const url = editingFilter.value
      ? `/admin/api/filters/${editingFilter.value.id}`
      : '/admin/api/filters';

    const method = editingFilter.value ? 'PUT' : 'POST';

    const payload = {
      ...filterForm.value,
      catalog_id: props.catalogId ? parseInt(props.catalogId) : null,
    };

    console.log('Request URL:', url);
    console.log('Request method:', method);
    console.log('Payload:', payload);

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (response.ok) {
      closeFilterModal();
      loadFilters();
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при сохранении фильтра');
    }
  } catch (error) {
    console.error('Failed to save filter:', error);
    alert('Ошибка при сохранении фильтра');
  } finally {
    saving.value = false;
  }
};

const confirmDeleteFilter = (filter) => {
  deleteModal.value = {
    show: true,
    filter,
  };
};

const deleteFilter = async () => {
  deleting.value = true;
  try {
    const response = await fetch(`/admin/api/filters/${deleteModal.value.filter.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
    });

    if (response.ok) {
      deleteModal.value.show = false;
      loadFilters();
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при удалении фильтра');
    }
  } catch (error) {
    console.error('Failed to delete filter:', error);
    alert('Ошибка при удалении фильтра');
  } finally {
    deleting.value = false;
  }
};

watch(() => props.catalogId, () => {
  if (props.catalogId) {
    loadFilters();
  }
});

onMounted(() => {
  if (props.catalogId) {
    loadFilters();
    loadInfoBlocks();

  }
});
</script>
