<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Определения характеристик</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Настройте типы характеристик для каталогов и товаров</p>
      </div>
      <button
        @click="openCreateModal"
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm hover:shadow"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Добавить характеристику
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="definitions.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-900/30 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Нет определений характеристик</p>
      <p class="text-xs text-gray-400 dark:text-gray-500">Создайте первую характеристику</p>
    </div>

    <!-- Definitions List -->
    <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900/50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Код</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Тип</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Применение</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Множественность</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Действия</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="def in definitions" :key="def.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ def.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">{{ def.code }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
              <span v-if="def.type === 'string'" class="inline-flex items-center gap-1">📝 Строка</span>
              <span v-else-if="def.type === 'number'" class="inline-flex items-center gap-1">🔢 Число</span>
              <span v-else-if="def.type === 'boolean'" class="inline-flex items-center gap-1">✓ Да/Нет</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
              <span v-if="def.applies_to === 'catalog'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">📁 Каталог</span>
              <span v-else-if="def.applies_to === 'product'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">📦 Товар</span>
              <span v-else-if="def.applies_to === 'both'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">🔄 Оба</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
              <span v-if="def.multiple" class="text-green-600 dark:text-green-400">✓ Да</span>
              <span v-else class="text-gray-400">✗ Нет</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="openEditModal(def)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">Изменить</button>
              <button @click="confirmDelete(def)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="closeModal"></div>

        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
          <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
            {{ editingDefinition ? 'Редактировать характеристику' : 'Создать характеристику' }}
          </h3>

          <form @submit.prevent="saveDefinition" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label>
              <input
                v-model="form.name"
                @input="autoGenerateCode"
                type="text"
                required
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                placeholder="Например: Цвет"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Символьный код *</label>
              <input
                v-model="form.code"
                type="text"
                required
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono"
                placeholder="color"
              >
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Генерируется автоматически из названия</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Тип данных *</label>
              <select
                v-model="form.type"
                @change="handleTypeChange"
                required
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
                <option value="string">📝 Строка</option>
                <option value="number">🔢 Число</option>
                <option value="boolean">✓ Да/Нет</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Применение *</label>
              <select
                v-model="form.applies_to"
                required
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
                <option value="catalog">📁 Каталог</option>
                <option value="product">📦 Товар</option>
                <option value="both">🔄 Каталог и Товар</option>
              </select>
            </div>

            <div v-if="form.type !== 'boolean'">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input
                  v-model="form.multiple"
                  type="checkbox"
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                >
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Множественное значение</span>
              </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
              >
                Отмена
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-50"
              >
                {{ saving ? 'Сохранение...' : 'Сохранить' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const definitions = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingDefinition = ref(null);
const saving = ref(false);

const form = ref({
  name: '',
  code: '',
  type: 'string',
  applies_to: 'product',
  multiple: false,
  sort_order: 500,
});

const codeManuallyEdited = ref(false);

onMounted(() => {
  loadDefinitions();
});

async function loadDefinitions() {
  try {
    loading.value = true;
    const response = await axios.get('/admin/api/characteristic-definitions');
    definitions.value = response.data;
  } catch (error) {
    console.error('Failed to load characteristic definitions:', error);
    alert('Ошибка загрузки характеристик');
  } finally {
    loading.value = false;
  }
}

function openCreateModal() {
  editingDefinition.value = null;
  form.value = {
    name: '',
    code: '',
    type: 'string',
    multiple: false,
    sort_order: 500,
  };
  codeManuallyEdited.value = false;
  showModal.value = true;
}

function openEditModal(definition) {
  editingDefinition.value = definition;
  form.value = { ...definition };
  codeManuallyEdited.value = true;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingDefinition.value = null;
}

function autoGenerateCode() {
  if (!codeManuallyEdited.value) {
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

    form.value.code = form.value.name
      .split('')
      .map(c => translitMap[c] || c)
      .join('')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');
  }
}

function handleTypeChange() {
  if (form.value.type === 'boolean') {
    form.value.multiple = false;
  }
}

async function saveDefinition() {
  try {
    saving.value = true;

    if (editingDefinition.value) {
      await axios.put(`/admin/api/characteristic-definitions/${editingDefinition.value.id}`, form.value);
    } else {
      await axios.post('/admin/api/characteristic-definitions', form.value);
    }

    await loadDefinitions();
    closeModal();
  } catch (error) {
    console.error('Failed to save characteristic definition:', error);
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat();
      alert('Ошибка: ' + errors.join(', '));
    } else {
      alert('Ошибка сохранения характеристики');
    }
  } finally {
    saving.value = false;
  }
}

async function confirmDelete(definition) {
  if (confirm(`Удалить характеристику "${definition.name}"?\n\nВнимание: это может повлиять на данные в каталогах и товарах.`)) {
    try {
      await axios.delete(`/admin/api/characteristic-definitions/${definition.id}`);
      await loadDefinitions();
    } catch (error) {
      console.error('Failed to delete characteristic definition:', error);
      alert('Ошибка удаления характеристики');
    }
  }
}
</script>
