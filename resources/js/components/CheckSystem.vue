<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Управление системой</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Модули и проверка системы</p>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
      <nav class="flex space-x-4 border-b border-gray-200 dark:border-gray-700">
        <router-link
          to="/modules"
          class="px-4 py-2 text-sm font-medium transition-colors"
          :class="$route.path === '/modules' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
        >
          Модули
        </router-link>
        <router-link
          to="/modules/check-system"
          class="px-4 py-2 text-sm font-medium transition-colors"
          :class="$route.path === '/modules/check-system' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
        >
          Проверка системы
        </router-link>
      </nav>
    </div>

    <!-- System Update Block -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 border-2 border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-8 shadow-lg">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Обновление системы
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Выполнить команду обновления AxoraCMS до последней версии
          </p>

          <!-- Output -->
          <div v-if="updateOutput" class="mb-4 p-4 bg-gray-900 text-green-400 rounded-lg font-mono text-xs overflow-x-auto max-h-48 overflow-y-auto">
            <pre>{{ updateOutput }}</pre>
          </div>
        </div>

        <button
          @click="executeUpdate"
          :disabled="updating"
          :style="buttonStyle"
          class="ml-4 px-6 py-2.5 disabled:bg-gray-400 text-white rounded-lg font-medium transition-opacity hover:opacity-90 flex items-center"
        >
          <svg v-if="updating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white flex-shrink-0" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ updating ? 'Обновление...' : 'Обновить систему' }}
        </button>
      </div>
    </div>

    <!-- Database Check Block -->
    <div class="bg-gradient-to-r from-green-50 to-teal-50 dark:from-green-950/30 dark:to-teal-950/30 border-2 border-green-200 dark:border-green-800 rounded-lg p-6 mb-8 shadow-lg">
      <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="flex-1 min-w-0">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Проверить Базу данных
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Проверить наличие всех необходимых миграций для установленных модулей
          </p>

          <!-- Check Results -->
          <div v-if="dbCheckResults.length > 0" class="mb-4 space-y-2">
            <div
              v-for="result in dbCheckResults"
              :key="result.module"
              class="p-3 rounded-lg"
              :class="result.status === 'ok' ? 'bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800' : 'bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800'"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center">
                    <svg v-if="result.status === 'ok'" class="w-4 h-4 mr-2 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-medium" :class="result.status === 'ok' ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'">
                      {{ getModuleName(result.module) }}
                    </span>
                  </div>
                  <p class="text-xs mt-1" :class="result.status === 'ok' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                    {{ result.installed }}/{{ result.total }} миграций установлено
                  </p>
                  <div v-if="result.missing.length > 0" class="mt-2">
                    <p class="text-xs font-medium text-red-800 dark:text-red-300 mb-1">Отсутствующие миграции:</p>
                    <div class="max-h-32 overflow-y-auto">
                      <ul class="text-xs text-red-700 dark:text-red-400 ml-4 list-disc">
                        <li v-for="migration in result.missing" :key="migration" class="break-all">{{ migration }}</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Overall Message -->
          <div v-if="dbCheckMessage" class="p-3 rounded-lg font-medium text-sm"
            :class="dbCheckHasIssues ? 'bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300' : 'bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300'"
          >
            {{ dbCheckMessage }}
          </div>

          <!-- Install Missing Migrations Output -->
          <div v-if="installMigrationsOutput" class="mt-4 p-4 bg-gray-900 text-green-400 rounded-lg font-mono text-xs overflow-x-auto max-h-48 overflow-y-auto">
            <pre>{{ installMigrationsOutput }}</pre>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 lg:flex-col lg:min-w-[200px]">
          <button
            @click="checkDatabase"
            :disabled="checkingDb || installingMigrations"
            :style="buttonStyle"
            class="px-6 py-2.5 disabled:bg-gray-400 text-white rounded-lg font-medium transition-opacity hover:opacity-90 flex items-center justify-center whitespace-nowrap"
          >
            <svg v-if="checkingDb" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white flex-shrink-0" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ checkingDb ? 'Проверка...' : 'Проверить' }}
          </button>

          <button
            v-if="dbCheckHasIssues"
            @click="installMissingMigrations"
            :disabled="checkingDb || installingMigrations"
            class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center justify-center whitespace-nowrap"
          >
            <svg v-if="installingMigrations" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white flex-shrink-0" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ installingMigrations ? 'Установка...' : 'Установить пропущенные' }}
          </button>
        </div>
      </div>
    </div>

</div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useTheme } from '../composables/useTheme';

const { buttonStyle } = useTheme();

const updating = ref(false);
const updateOutput = ref('');
const checkingDb = ref(false);
const dbCheckResults = ref([]);
const dbCheckMessage = ref('');
const dbCheckHasIssues = ref(false);
const installingMigrations = ref(false);
const installMigrationsOutput = ref('');

const executeUpdate = async () => {
  updating.value = true;
  updateOutput.value = '';

  try {
    const response = await axios.post('/admin/api/modules/update');

    if (response.data.success) {
      updateOutput.value = response.data.output || 'Система успешно обновлена!';
    } else {
      updateOutput.value = 'Ошибка: ' + (response.data.message || 'Неизвестная ошибка');
    }
  } catch (error) {
    console.error('Update error:', error);
    updateOutput.value = 'Ошибка выполнения обновления: ' + (error.response?.data?.message || error.message);
  } finally {
    updating.value = false;
  }
};

const checkDatabase = async () => {
  checkingDb.value = true;
  dbCheckResults.value = [];
  dbCheckMessage.value = '';
  dbCheckHasIssues.value = false;

  try {
    const response = await axios.post('/admin/api/modules/check-database');

    if (response.data.success) {
      dbCheckResults.value = response.data.results || [];
      dbCheckHasIssues.value = response.data.has_issues || false;

      if (dbCheckHasIssues.value) {
        dbCheckMessage.value = 'Обнаружены проблемы с базой данных';
      } else {
        dbCheckMessage.value = 'Все миграции установлены корректно';
      }
    }
  } catch (error) {
    console.error('Database check error:', error);
    dbCheckMessage.value = 'Ошибка проверки: ' + (error.response?.data?.message || error.message);
    dbCheckHasIssues.value = true;
  } finally {
    checkingDb.value = false;
  }
};

const installMissingMigrations = async () => {
  installingMigrations.value = true;
  installMigrationsOutput.value = '';

  try {
    const response = await axios.post('/admin/api/modules/install-missing-migrations');

    if (response.data.success) {
      installMigrationsOutput.value = 'Установлено миграций: ' + (response.data.installed_count || 0);

      // Re-check database after installation
      setTimeout(() => {
        checkDatabase();
      }, 1000);
    } else {
      installMigrationsOutput.value = 'Ошибка: ' + (response.data.message || 'Неизвестная ошибка');
    }
  } catch (error) {
    console.error('Install migrations error:', error);
    installMigrationsOutput.value = 'Ошибка установки: ' + (error.response?.data?.message || error.message);
  } finally {
    installingMigrations.value = false;
  }
};

const getModuleName = (moduleId) => {
  const moduleNames = {
    'shop': 'Магазин',
    'commerce': 'Коммерция',
    'pages': 'Страницы',
    'posts': 'Посты',
    'logging': 'Логирование',
    'seo': 'SEO',
    'sitemap': 'Карта сайта',
    'page_builder': 'Конструктор страниц'
  };

  return moduleNames[moduleId] || moduleId;
};
</script>
