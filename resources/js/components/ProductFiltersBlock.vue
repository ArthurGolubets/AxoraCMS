<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Фильтры товара</h3>

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
      Выберите значения фильтров для этого товара. Доступны глобальные фильтры и фильтры категории.
    </p>

    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
      <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Загрузка фильтров...</p>
    </div>

    <div v-else-if="availableFilters.length === 0" class="text-center py-8">
      <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <p class="text-gray-600 dark:text-gray-400">
        Нет доступных фильтров для этой категории
      </p>
      <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
        Создайте глобальные фильтры или фильтры для категории товара
      </p>
    </div>

    <div v-else class="space-y-6">
      <!-- Global Filters -->
      <div v-if="globalFilters.length > 0">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
          <span class="px-2 py-1 text-xs font-medium rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
            Глобальные
          </span>
          Фильтры доступные для всех товаров
        </h4>
        <div class="space-y-4">
          <div v-for="filter in globalFilters" :key="filter.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
              {{ filter.name }}
              <span class="text-xs text-gray-500 dark:text-gray-400 font-mono ml-2">{{ filter.code }}</span>
            </label>

            <!-- Select type -->
            <div v-if="filter.type === 'select'" class="space-y-2">
              <label
                v-for="value in filter.values"
                :key="value.id"
                class="flex items-center p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded cursor-pointer"
              >
                <input
                  type="radio"
                  :name="`filter_${filter.id}`"
                  :value="value.id"
                  v-model="selectedValues[filter.id]"
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ value.value }}
                  <span v-if="value.code" class="text-xs text-gray-500 dark:text-gray-400 font-mono ml-1">{{ value.code }}</span>
                </span>
              </label>
            </div>

            <!-- Entity type -->
            <div v-else-if="filter.type === 'entity'">
              <InfoBlockEntitySelect
                  v-model="selectedValues[filter.id]"
                  :entity-type-fixed="filter.settings?.entity_type"
                  :info-block-id="filter.settings?.entity_id"
              />
            </div>

            <!-- Checkbox type -->
            <div v-else-if="filter.type === 'checkbox'" class="space-y-2">
              <label
                v-for="value in filter.values"
                :key="value.id"
                class="flex items-center p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="value.id"
                  v-model="selectedValues[filter.id]"
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ value.value }}
                  <span v-if="value.code" class="text-xs text-gray-500 dark:text-gray-400 font-mono ml-1">{{ value.code }}</span>
                </span>
              </label>
            </div>

            <!-- Range type -->
            <div v-else-if="filter.type === 'range'">
              <input
                type="number"
                step="any"
                v-model.number="selectedValues[filter.id]"
                :placeholder="`Введите значение для ${filter.name}`"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              />
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Введите числовое значение для фильтра диапазона
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Category Filters -->
      <div v-if="categoryFilters.length > 0">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
          <span class="px-2 py-1 text-xs font-medium rounded bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
            Категорийные
          </span>
          Фильтры категории {{ catalogName }}
        </h4>
        <div class="space-y-4">
          <div v-for="filter in categoryFilters" :key="filter.id" class="border border-indigo-200 dark:border-indigo-700 rounded-lg p-4 bg-indigo-50/50 dark:bg-indigo-900/10">
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
              {{ filter.name }}
              <span class="text-xs text-gray-500 dark:text-gray-400 font-mono ml-2">{{ filter.code }}</span>
            </label>

            <!-- Select type -->
            <div v-if="filter.type === 'select'" class="space-y-2">
              <label
                v-for="value in filter.values"
                :key="value.id"
                class="flex items-center p-2 hover:bg-white dark:hover:bg-gray-900 rounded cursor-pointer"
              >
                <input
                  type="radio"
                  :name="`filter_${filter.id}`"
                  :value="value.id"
                  v-model="selectedValues[filter.id]"
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ value.value }}
                  <span v-if="value.code" class="text-xs text-gray-500 dark:text-gray-400 font-mono ml-1">{{ value.code }}</span>
                </span>
              </label>
            </div>

            <!-- Checkbox type -->
            <div v-else-if="filter.type === 'checkbox'" class="space-y-2">
              <label
                v-for="value in filter.values"
                :key="value.id"
                class="flex items-center p-2 hover:bg-white dark:hover:bg-gray-900 rounded cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="value.id"
                  v-model="selectedValues[filter.id]"
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ value.value }}
                  <span v-if="value.code" class="text-xs text-gray-500 dark:text-gray-400 font-mono ml-1">{{ value.code }}</span>
                </span>
              </label>
            </div>

            <!-- Range type -->
            <div v-else-if="filter.type === 'range'">
              <input
                type="number"
                step="any"
                v-model.number="selectedValues[filter.id]"
                :placeholder="`Введите значение для ${filter.name}`"
                class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-indigo-300 dark:border-indigo-600 rounded-lg text-gray-900 dark:text-white"
              />
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Введите числовое значение для фильтра диапазона
              </p>
            </div>

            <!-- Entity type -->
            <div v-else-if="filter.type === 'entity'">
              <InfoBlockEntitySelect
                  v-model="selectedValues[filter.id]"
                  :entity-type-fixed="filter.settings?.entity_type"
                  :info-block-id="filter.settings?.entity_id"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div v-if="selectedValuesCount > 0" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <p class="text-sm text-blue-900 dark:text-blue-100">
          <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Выбрано значений: <strong>{{ selectedValuesCount }}</strong>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import InfoBlockEntitySelect from './InfoBlockEntitySelect.vue';

const props = defineProps({
  catalogId: {
    type: [Number, String],
    required: true
  },
  catalogName: {
    type: String,
    default: ''
  },
  initialValues: {
    type: Array,
    default: () => []
  },
  initialRangeValues: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:filterValues', 'update:rangeFilterValues']);

const availableFilters = ref([]);
const selectedValues = ref({});
const loading = ref(false);
const isUpdatingFromParent = ref(false); // Flag to prevent circular updates

const globalFilters = computed(() => {
  return availableFilters.value.filter(f => f.catalog_id === null);
});

const categoryFilters = computed(() => {
  return availableFilters.value.filter(f => f.catalog_id !== null);
});

const selectedValuesArray = computed(() => {
  const values = [];
  Object.keys(selectedValues.value).forEach(filterId => {
    const filterValue = selectedValues.value[filterId];
    const filter = availableFilters.value.find(f => f.id == filterId);

    // Skip range filters - they are handled separately
    if (filter && filter.type === 'range') {
      return;
    }

    if (Array.isArray(filterValue)) {
      // Multiple selection (checkbox)
      values.push(...filterValue);
    } else if (filterValue !== null && filterValue !== '') {
      // Single selection (select/radio)
      values.push(filterValue);
    }
  });
  return values;
});

const rangeFilterValues = computed(() => {
  const ranges = {};
  Object.keys(selectedValues.value).forEach(filterId => {
    const filterValue = selectedValues.value[filterId];
    const filter = availableFilters.value.find(f => f.id == filterId);

    if (filter && filter.type === 'range' && filterValue !== null && filterValue !== '') {
      ranges[filterId] = filterValue;
    }
  });
  return ranges;
});

const selectedValuesCount = computed(() => {
  return selectedValuesArray.value.length + Object.keys(rangeFilterValues.value).length;
});

const loadFilters = async () => {
  if (!props.catalogId) return;

  loading.value = true;
  try {
    const response = await fetch(`/admin/api/filters/for-catalog/${props.catalogId}`, {
      headers: { 'Accept': 'application/json' },
    });

    if (response.ok) {
      const data = await response.json();
      // Filter only active filters - range type doesn't need values
      availableFilters.value = data.filter(f => f.is_active && (f.type === 'range' || f.type === 'entity' || (f.values && f.values.length > 0)));

      // Set flag to prevent emit during initial load
      isUpdatingFromParent.value = true;

      // Initialize selectedValues structure
      const newSelectedValues = {};
      availableFilters.value.forEach(filter => {
        if (filter.type === 'select') {
          newSelectedValues[filter.id] = null;
        } else if (filter.type === 'entity') {
            newSelectedValues[filter.id] = null;
        } else if (filter.type === 'range') {
          newSelectedValues[filter.id] = null; // Numeric value for range
        } else {
          newSelectedValues[filter.id] = [];
        }
      });

      // Apply initial values if provided
      if (props.initialValues && props.initialValues.length > 0) {
        props.initialValues.forEach(valueId => {
          const filter = availableFilters.value.find(f =>
            f.values && f.values.some(v => v.id === valueId)
          );

          if (filter) {
            if (filter.type === 'select') {
              newSelectedValues[filter.id] = valueId;
            } else {
              if (!newSelectedValues[filter.id]) {
                newSelectedValues[filter.id] = [];
              }
              newSelectedValues[filter.id].push(valueId);
            }
          }
        });
      }

      // Apply initial range values if provided
      if (props.initialRangeValues && Object.keys(props.initialRangeValues).length > 0) {
        Object.keys(props.initialRangeValues).forEach(filterId => {
          const filter = availableFilters.value.find(f => f.id == filterId && f.type === 'range');
          if (filter) {
            newSelectedValues[filterId] = props.initialRangeValues[filterId];
          }
        });
      }

      selectedValues.value = newSelectedValues;

      // Reset flag after a tick to ensure Vue has finished updating
      setTimeout(() => {
        isUpdatingFromParent.value = false;
      }, 0);
    } else {
      console.error('Failed to fetch filters, status:', response.status);
      availableFilters.value = [];
    }
  } catch (error) {
    console.error('Failed to load filters:', error);
    availableFilters.value = [];
  } finally {
    loading.value = false;
  }
};

// Watch for changes in selected values and emit to parent
watch(selectedValues, () => {
  // Don't emit if we're currently updating from parent to prevent circular updates
  if (!isUpdatingFromParent.value) {
    emit('update:filterValues', selectedValuesArray.value);
    emit('update:rangeFilterValues', rangeFilterValues.value);
  }
}, { deep: true });

// Watch for catalog changes
watch(() => props.catalogId, () => {
  if (props.catalogId) {
    loadFilters();
  }
});

// Watch for initial values changes (when editing existing product)
watch(() => props.initialValues, (newValues) => {
  if (!availableFilters.value.length) return;

  // Check if current selected values are already the same as newValues
  const currentValues = selectedValuesArray.value.sort();
  const incomingValues = (newValues || []).sort();

  // If values are the same, don't update to prevent infinite loop
  if (JSON.stringify(currentValues) === JSON.stringify(incomingValues)) {
    return;
  }

  // Set flag to prevent emit during update
  isUpdatingFromParent.value = true;

  const newSelectedValues = {};

  // Initialize structure for all filters
  availableFilters.value.forEach(filter => {
    if (filter.type === 'select') {
      newSelectedValues[filter.id] = null;
    } else if (filter.type === 'range') {
      newSelectedValues[filter.id] = null; // Numeric value for range
    } else {
      newSelectedValues[filter.id] = [];
    }
  });

  // Apply new initial values
  if (newValues && newValues.length > 0) {
    newValues.forEach(valueId => {
      const filter = availableFilters.value.find(f =>
        f.values && f.values.some(v => v.id === valueId)
      );

      if (filter) {
        if (filter.type === 'select') {
          newSelectedValues[filter.id] = valueId;
        } else {
          if (!newSelectedValues[filter.id].includes(valueId)) {
            newSelectedValues[filter.id].push(valueId);
          }
        }
      }
    });
  }

  selectedValues.value = newSelectedValues;

  // Reset flag after update
  isUpdatingFromParent.value = false;
});

onMounted(() => {
  if (props.catalogId) {
    loadFilters();
  }
});
</script>
