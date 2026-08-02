<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Настройки CommerceML</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Настройка интеграции с 1С через протокол CommerceML</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Settings Form -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form @submit.prevent="saveSettings">
          <!-- Enable Integration -->
          <div class="mb-6">
            <label class="flex items-center space-x-3 cursor-pointer">
              <input
                v-model="settings.is_enabled"
                type="checkbox"
                class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Включить интеграцию с 1С
              </span>
            </label>
          </div>

          <!-- Login -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Логин <span class="text-red-500">*</span>
            </label>
            <input
              v-model="settings.login"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="admin"
              required
            />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Логин для авторизации запросов от 1С
            </p>
          </div>

          <!-- Password -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Пароль <span class="text-red-500">*</span>
            </label>
            <input
              v-model="settings.password"
              type="password"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="••••••••"
              required
            />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Пароль для авторизации запросов от 1С
            </p>
          </div>

          <!-- Import Type -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Тип импорта
            </label>
            <select
              v-model="settings.import_type"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="separate">Файлы по отдельности (рекомендуется)</option>
              <option value="monolith">Монолитный файл</option>
            </select>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Способ загрузки данных от 1С
            </p>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="testConnection"
              :disabled="saving || testing"
              class="px-6 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white rounded-lg transition"
            >
              {{ testing ? 'Проверка...' : 'Тест соединения' }}
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition"
            >
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Integration Info -->
      <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
          Информация для настройки 1С
        </h3>
        <div class="space-y-2 text-sm">
          <div class="flex items-start">
            <span class="font-medium text-blue-900 dark:text-blue-200 w-32">URL для обмена:</span>
            <code class="flex-1 bg-white dark:bg-gray-800 px-3 py-1 rounded text-gray-900 dark:text-gray-100 font-mono">
              {{ exchangeUrl }}
            </code>
          </div>
          <div class="flex items-start">
            <span class="font-medium text-blue-900 dark:text-blue-200 w-32">Логин:</span>
            <code class="flex-1 bg-white dark:bg-gray-800 px-3 py-1 rounded text-gray-900 dark:text-gray-100 font-mono">
              {{ settings.login || '(не указан)' }}
            </code>
          </div>
          <div class="flex items-start">
            <span class="font-medium text-blue-900 dark:text-blue-200 w-32">Пароль:</span>
            <code class="flex-1 bg-white dark:bg-gray-800 px-3 py-1 rounded text-gray-900 dark:text-gray-100 font-mono">
              {{ settings.password ? '••••••••' : '(не указан)' }}
            </code>
          </div>
        </div>
      </div>

      <!-- Statistics (if installed) -->
      <div v-if="settings.is_enabled" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Статистика синхронизации
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Товаров с 1c_id</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
              {{ stats.products || 0 }}
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Категорий с 1c_id</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
              {{ stats.catalogs || 0 }}
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Последняя синхронизация</div>
            <div class="text-sm font-medium text-gray-900 dark:text-white mt-1">
              {{ stats.last_sync || 'Никогда' }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useModal } from '../../composables/useModal';
import axios from 'axios';

const { success, error } = useModal();

const loading = ref(false);
const saving = ref(false);
const testing = ref(false);
const settings = ref({
  login: '',
  password: '',
  import_type: 'separate',
  is_enabled: false,
});

const stats = ref({
  products: 0,
  catalogs: 0,
  last_sync: null,
});

const exchangeUrl = computed(() => {
  return `${window.location.origin}/api/1c/exchange`;
});

async function loadSettings() {
  loading.value = true;
  try {
    const response = await axios.get('/admin/api/integrations/commerceml');
    settings.value = response.data.settings;
    stats.value = {
      products: response.data.statistics.products_count,
      catalogs: response.data.statistics.catalogs_count,
      last_sync: response.data.statistics.last_sync,
    };
  } catch (err) {
    error('Ошибка загрузки настроек');
    console.error(err);
  } finally {
    loading.value = false;
  }
}

async function saveSettings() {
  saving.value = true;
  try {
    const response = await axios.post('/admin/api/integrations/commerceml', settings.value);
    settings.value = response.data.settings;
    success('Настройки успешно сохранены');
    // Reload statistics after save
    await loadSettings();
  } catch (err) {
    error(err.response?.data?.message || 'Ошибка сохранения настроек');
    console.error(err);
  } finally {
    saving.value = false;
  }
}

async function testConnection() {
  testing.value = true;
  try {
    const response = await axios.post('/admin/api/integrations/commerceml/test');
    success(response.data.message || 'Соединение успешно');
  } catch (err) {
    error(err.response?.data?.message || 'Ошибка соединения');
    console.error(err);
  } finally {
    testing.value = false;
  }
}

onMounted(() => {
  loadSettings();
});
</script>
