<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Характеристики</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Значения характеристик товара/каталога</p>
      </div>
      <router-link
        to="/content-settings"
        class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Настроить характеристики
      </router-link>
    </div>

    <!-- Loading State -->
    <div v-if="loadingDefinitions" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Загрузка характеристик...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="loadError" class="text-center py-8 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
      <svg class="mx-auto h-10 w-10 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <p class="mt-2 text-sm text-yellow-800 dark:text-yellow-200">Ошибка загрузки характеристик</p>
      <p class="text-xs text-yellow-600 dark:text-yellow-300 mt-1">{{ loadError }}</p>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Выполните миграции: <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded">php artisan migrate</code></p>
    </div>

    <!-- No Definitions State -->
    <div v-else-if="definitions.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-900/30 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Нет настроенных характеристик</p>
      <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Сначала настройте характеристики в настройках контента</p>
      <router-link
        to="/content-settings"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
      >
        Перейти к настройкам
      </router-link>
    </div>

    <!-- Characteristics Values List -->
    <div v-else class="space-y-3">
      <div
        v-for="definition in definitions"
        :key="definition.id"
        class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-700/30 dark:to-gray-800/30 border border-gray-200 dark:border-gray-600 rounded-lg p-4"
      >
        <div class="mb-3">
          <div class="flex items-center justify-between">
            <label class="block text-sm font-semibold text-gray-900 dark:text-white">
              {{ definition.name }}
            </label>
            <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ definition.code }}</span>
          </div>
          <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span v-if="definition.type === 'string'">📝 Строка</span>
            <span v-else-if="definition.type === 'number'">🔢 Число</span>
            <span v-else-if="definition.type === 'boolean'">✓ Да/Нет</span>
            <span v-if="definition.multiple" class="text-blue-600 dark:text-blue-400">• Множественное</span>
          </div>
        </div>

        <!-- Boolean type -->
        <div v-if="definition.type === 'boolean'" class="flex items-center">
          <ToggleSwitch
            v-model="values[definition.code]"
            :label="values[definition.code] ? '✓ Да' : '✗ Нет'"
          />
        </div>

        <!-- Single value -->
        <div v-else-if="!definition.multiple">
          <input
            v-model="values[definition.code]"
            :type="definition.type === 'number' ? 'number' : 'text'"
            :step="definition.type === 'number' ? '0.01' : undefined"
            placeholder="Введите значение"
            class="w-full px-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Multiple values -->
        <div v-else class="space-y-2">
          <div v-if="!values[definition.code] || values[definition.code].length === 0" class="text-center py-4 text-sm text-gray-400 dark:text-gray-500">
            Нет значений
          </div>
          <div v-else>
            <div v-for="(val, valIndex) in values[definition.code]" :key="valIndex" class="flex gap-2 items-center mb-2">
              <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-400">
                {{ valIndex + 1 }}
              </div>
              <input
                v-model="values[definition.code][valIndex]"
                :type="definition.type === 'number' ? 'number' : 'text'"
                :step="definition.type === 'number' ? '0.01' : undefined"
                placeholder="Значение"
                class="flex-1 px-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <button
                @click="removeValueFromMultiple(definition.code, valIndex)"
                type="button"
                class="flex-shrink-0 p-2 text-red-600 hover:text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                title="Удалить значение"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          <button
            @click="addValueToMultiple(definition.code, definition.type)"
            type="button"
            class="w-full px-4 py-2.5 text-sm border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-blue-400 hover:text-blue-600 dark:hover:border-blue-500 dark:hover:text-blue-400 rounded-lg transition-colors font-medium"
          >
            + Добавить значение
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import ToggleSwitch from './ToggleSwitch.vue';

const props = defineProps({
  modelValue: {
    type: [Array, Object],
    default: () => ({})
  },
  appliesTo: {
    type: String,
    default: 'product',
    validator: (value) => ['catalog', 'product'].includes(value)
  }
});

const emit = defineEmits(['update:modelValue']);

const definitions = ref([]);
const loadingDefinitions = ref(true);
const loadError = ref(null);
const values = ref({});
const isInitializing = ref(false);

// Load characteristic definitions
onMounted(async () => {
  try {
    loadingDefinitions.value = true;
    loadError.value = null;
    const response = await axios.get('/admin/api/characteristic-definitions', {
      params: {
        applies_to: props.appliesTo
      }
    });
    definitions.value = response.data || [];
    initializeValues();
  } catch (error) {
    console.error('Failed to load characteristic definitions:', error);
    loadError.value = error.response?.data?.message || error.message || 'Не удалось загрузить характеристики';
    // Don't fail - just set empty definitions
    definitions.value = [];
    // Initialize with empty values to not block the form
    values.value = {};
  } finally {
    loadingDefinitions.value = false;
  }
});

// Initialize values from modelValue
function initializeValues() {
  isInitializing.value = true;
  const newValues = {};

  // If modelValue is array (old format), convert to new format
  if (Array.isArray(props.modelValue)) {
    props.modelValue.forEach(char => {
      if (char.code) {
        if (char.multiple && char.values) {
          newValues[char.code] = Array.isArray(char.values) ? [...char.values] : char.values;
        } else if (char.value !== undefined && char.value !== null) {
          newValues[char.code] = char.value;
        }
      }
    });
  } else if (props.modelValue && typeof props.modelValue === 'object') {
    // New format - direct object with code: value pairs
    console.log('ProductCharacteristics: Using object format');
    // Deep copy to avoid proxy issues
    Object.keys(props.modelValue).forEach(key => {
      const val = props.modelValue[key];
      newValues[key] = Array.isArray(val) ? [...val] : val;
    });
  }

  // Initialize empty values for all definitions
  definitions.value.forEach(def => {
    if (!(def.code in newValues)) {
      if (def.type === 'boolean') {
        newValues[def.code] = false;
      } else if (def.multiple) {
        newValues[def.code] = [];
      } else {
        newValues[def.code] = def.type === 'number' ? 0 : '';
      }
    }
  });

  console.log('ProductCharacteristics: Final values:', newValues);

  // Force reactive update by creating new object
  values.value = { ...newValues };

  // Small delay to prevent immediate watch trigger
  setTimeout(() => {
    isInitializing.value = false;
  }, 100);
}

// Watch for prop changes - using getter to ensure deep reactivity
watch(() => JSON.stringify(props.modelValue), (newVal, oldVal) => {
  if (definitions.value.length > 0 && newVal !== oldVal) {
    initializeValues();
  } else {
    console.log('ProductCharacteristics: Skipping re-initialization');
  }
});

// Watch for definitions changes (when they finish loading)
watch(definitions, (newDefs) => {
  if (newDefs.length > 0 && Object.keys(props.modelValue || {}).length > 0) {
    initializeValues();
  }
});

// Watch for value changes and emit
watch(values, (newValues) => {
  if (!isInitializing.value) {
    emit('update:modelValue', { ...newValues });
  }
}, { deep: true });

// Add value to multiple characteristic
function addValueToMultiple(code, type) {
  if (!Array.isArray(values.value[code])) {
    values.value[code] = [];
  }
  values.value[code].push(type === 'number' ? 0 : '');
}

// Remove value from multiple characteristic
function removeValueFromMultiple(code, index) {
  if (Array.isArray(values.value[code])) {
    values.value[code].splice(index, 1);
  }
}
</script>
