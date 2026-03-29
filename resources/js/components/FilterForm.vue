<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button @click="$router.push('/filters')" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ isEdit ? 'Редактирование фильтра' : 'Создание фильтра' }}
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ isEdit ? 'Изменение параметров фильтра' : 'Добавление нового фильтра' }}
          </p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="text-gray-600 dark:text-gray-400 mt-4">Загрузка...</p>
    </div>

    <form v-else @submit.prevent="saveFilter" class="space-y-6">
      <!-- Basic Info -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Основная информация</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Название фильтра *
            </label>
            <input
              v-model="form.name"
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
              v-model="form.code"
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
              v-model="form.type"
              required
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
              <option value="select">Выбор (dropdown)</option>
              <option value="checkbox">Флажки (multiple)</option>
              <option value="range">Диапазон (min-max)</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Порядок сортировки
            </label>
            <input
              v-model.number="form.sort"
              type="number"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Категория
            </label>
            <select
              v-model="form.catalog_id"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
              <option :value="null">Глобальный фильтр (доступен для всех категорий)</option>
              <option v-for="catalog in catalogs" :key="catalog.id" :value="catalog.id">
                {{ catalog.name }}
              </option>
            </select>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Глобальные фильтры доступны во всех категориях. Категорийные - только в выбранной категории и её дочерних.
            </p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Описание
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              placeholder="Описание фильтра (необязательно)"
            ></textarea>
          </div>

          <div class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            />
            <label for="is_active" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              Фильтр активен
            </label>
          </div>
        </div>
      </div>

      <!-- Filter Values (not needed for range type) -->
      <div v-if="form.type !== 'range'" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Значения фильтра</h3>
          <ThemeButton variant="primary" size="sm" @click="addValue" type="button">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Добавить значение
          </ThemeButton>
        </div>

        <div v-if="form.values.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
          </svg>
          <p>Нет значений</p>
          <p class="text-sm mt-2">Добавьте значения для фильтра</p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(value, index) in form.values"
            :key="index"
            class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700"
          >
            <div class="flex-1 space-y-3">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                    Значение *
                  </label>
                  <input
                    v-model="value.value"
                    type="text"
                    required
                    class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white"
                    placeholder="512 ГБ"
                  />
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                    Код (необязательно)
                  </label>
                  <input
                    v-model="value.code"
                    type="text"
                    class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white font-mono"
                    placeholder="512gb"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                    Сортировка
                  </label>
                  <input
                    v-model.number="value.sort"
                    type="number"
                    class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white"
                    placeholder="500"
                  />
                </div>
                <div class="flex items-end">
                  <label class="flex items-center space-x-2 px-3 py-2 cursor-pointer">
                    <input
                      v-model="value.is_active"
                      type="checkbox"
                      :id="`value_active_${index}`"
                      class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                      Активно
                    </span>
                  </label>
                </div>
              </div>
            </div>

            <button
              @click="removeValue(index)"
              type="button"
              class="flex-shrink-0 p-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
              title="Удалить значение"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between">
        <ThemeButton variant="secondary" @click="$router.push('/filters')" type="button">
          Отмена
        </ThemeButton>
        <ThemeButton variant="primary" type="submit" :disabled="saving">
          <span v-if="saving">Сохранение...</span>
          <span v-else>{{ isEdit ? 'Сохранить изменения' : 'Создать фильтр' }}</span>
        </ThemeButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from './ThemeButton.vue';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const saving = ref(false);
const catalogs = ref([]);

const form = ref({
  name: '',
  code: '',
  type: 'select',
  catalog_id: null,
  sort: 500,
  is_active: true,
  description: '',
  values: [],
});

const loadCatalogs = async () => {
  try {
    const response = await fetch('/admin/api/catalog/list', {
      headers: { 'Accept': 'application/json' },
    });

    if (response.ok) {
      catalogs.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to load catalogs:', error);
  }
};

const loadFilter = async () => {
  if (!isEdit.value) return;

  loading.value = true;
  try {
    const response = await fetch(`/admin/api/filters/${route.params.id}`, {
      headers: { 'Accept': 'application/json' },
    });

    if (response.ok) {
      const filter = await response.json();
      form.value = {
        name: filter.name,
        code: filter.code,
        type: filter.type,
        catalog_id: filter.catalog_id,
        sort: filter.sort || 500,
        is_active: filter.is_active,
        description: filter.description || '',
        values: filter.values || [],
      };
    }
  } catch (error) {
    console.error('Failed to load filter:', error);
  } finally {
    loading.value = false;
  }
};

const generateCodeFromName = () => {
  if (form.value.name) {
    form.value.code = form.value.name
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

const addValue = () => {
  form.value.values.push({
    value: '',
    code: '',
    sort: 500,
    is_active: true,
  });
};

const removeValue = (index) => {
  form.value.values.splice(index, 1);
};

const saveFilter = async () => {
  saving.value = true;
  try {
    const url = isEdit.value
      ? `/admin/api/filters/${route.params.id}`
      : '/admin/api/filters';

    const method = isEdit.value ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify(form.value),
    });

    if (response.ok) {
      router.push('/filters');
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

onMounted(() => {
  loadCatalogs();
  loadFilter();
});
</script>
