<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Модули</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Управление модулями системы</p>
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
            Выполнить команду обновления HolartCMS до последней версии
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
          <svg v-if="updating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ updating ? 'Обновление...' : 'Обновить систему' }}
        </button>
      </div>
    </div>

    <!-- Separator -->
    <div class="mb-6 border-t-2 border-gray-300 dark:border-gray-700"></div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else>
      <!-- Modules Section -->
      <div class="mb-8">
        <div class="mb-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">Модули</h3>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Установка и управление функциональными модулями</p>
        </div>

        <div class="space-y-4">
          <div
            v-for="module in functionalModules"
            :key="module.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center mb-2">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ module.name }}</h3>
                  <span
                    v-if="module.installed"
                    class="ml-3 px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded"
                  >
                    Установлен
                  </span>
                  <span
                    v-else
                    class="ml-3 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded"
                  >
                    Не установлен
                  </span>
                  <span
                    v-if="module.needs_update"
                    class="ml-3 px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded"
                  >
                    Доступно обновление
                  </span>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ module.description }}</p>

                <div v-if="module.installed && (module.installed_version || module.current_version)" class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                  <span v-if="module.installed_version">Установлена: {{ module.installed_version }}</span>
                  <span v-if="module.current_version && module.needs_update"> → Доступна: {{ module.current_version }}</span>
                </div>

                <!-- Module Output -->
                <div v-if="moduleOutputs[module.id]" class="mb-4 p-4 bg-gray-900 text-green-400 rounded-lg font-mono text-xs overflow-x-auto max-h-48 overflow-y-auto">
                  <pre>{{ moduleOutputs[module.id] }}</pre>
                </div>
              </div>

              <div class="ml-4 flex flex-col space-y-2">
                <button
                  v-if="!module.installed"
                  @click="installModule(module)"
                  :disabled="processingModules[module.id]"
                  :style="buttonStyle"
                  class="px-6 py-2.5 disabled:bg-gray-400 text-white rounded-lg font-medium transition-opacity hover:opacity-90 flex items-center"
                >
                  <svg v-if="processingModules[module.id]" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ processingModules[module.id] ? 'Установка...' : 'Установить' }}
                </button>

                <button
                  v-if="module.installed && module.needs_update"
                  @click="updateModule(module)"
                  :disabled="processingModules[module.id]"
                  class="px-6 py-2.5 bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center"
                >
                  <svg v-if="processingModules[module.id]" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ processingModules[module.id] ? 'Обновление...' : 'Обновить' }}
                </button>

                <button
                  v-if="module.installed"
                  @click="showUninstallModal(module)"
                  :disabled="processingModules[module.id]"
                  class="px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center"
                >
                  <svg v-if="processingModules[module.id]" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ processingModules[module.id] ? 'Удаление...' : 'Удалить' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Integrations Section -->
      <div v-if="integrationModules.length > 0">
        <div class="mb-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">Интеграции</h3>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Подключение сторонних сервисов и API</p>
        </div>

        <div class="space-y-4">
          <div
            v-for="module in integrationModules"
            :key="module.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center mb-2">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ module.name }}</h3>
                  <span
                    v-if="module.installed"
                    class="ml-3 px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded"
                  >
                    Установлен
                  </span>
                  <span
                    v-else
                    class="ml-3 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded"
                  >
                    Не установлен
                  </span>
                  <span
                    v-if="module.needs_update"
                    class="ml-3 px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded"
                  >
                    Доступно обновление
                  </span>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ module.description }}</p>

                <div v-if="module.installed && (module.installed_version || module.current_version)" class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                  <span v-if="module.installed_version">Установлена: {{ module.installed_version }}</span>
                  <span v-if="module.current_version && module.needs_update"> → Доступна: {{ module.current_version }}</span>
                </div>

                <!-- Module Output -->
                <div v-if="moduleOutputs[module.id]" class="mb-4 p-4 bg-gray-900 text-green-400 rounded-lg font-mono text-xs overflow-x-auto max-h-48 overflow-y-auto">
                  <pre>{{ moduleOutputs[module.id] }}</pre>
                </div>
              </div>

              <div class="ml-4 flex flex-col space-y-2">
                <button
                  v-if="!module.installed"
                  @click="installModule(module)"
                  :disabled="processingModules[module.id]"
                  :style="buttonStyle"
                  class="px-6 py-2.5 disabled:bg-gray-400 text-white rounded-lg font-medium transition-opacity hover:opacity-90 flex items-center"
                >
                  <svg v-if="processingModules[module.id]" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ processingModules[module.id] ? 'Установка...' : 'Установить' }}
                </button>

                <button
                  v-if="module.installed && module.needs_update"
                  @click="updateModule(module)"
                  :disabled="processingModules[module.id]"
                  class="px-6 py-2.5 bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center"
                >
                  <svg v-if="processingModules[module.id]" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 814 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ processingModules[module.id] ? 'Обновление...' : 'Обновить' }}
                </button>

                <button
                  v-if="module.installed"
                  @click="showUninstallModal(module)"
                  :disabled="processingModules[module.id]"
                  class="px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center"
                >
                  <svg v-if="processingModules[module.id]" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ processingModules[module.id] ? 'Удаление...' : 'Удалить' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Uninstall Modal -->
    <div v-if="uninstallModal.show" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeUninstallModal"></div>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Удаление модуля</h3>
          </div>

          <div class="px-6 py-4">
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
              Вы уверены, что хотите удалить модуль "{{ uninstallModal.module?.name }}"?
            </p>

            <div class="space-y-3">
              <div class="flex items-start space-x-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <input
                  v-model="uninstallModal.preserveDatabase"
                  type="checkbox"
                  id="preserve-db"
                  class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                >
                <label for="preserve-db" class="text-sm text-yellow-800 dark:text-yellow-300">
                  <span class="font-medium">Сохранить данные в базе данных</span>
                  <p class="mt-1 text-xs">Если эта опция включена, таблицы базы данных и все данные будут сохранены. В противном случае все данные будут удалены безвозвратно.</p>
                </label>
              </div>

              <!-- Pages module specific option -->
              <div v-if="uninstallModal.module?.id === 'pages'" class="flex items-start space-x-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <input
                  v-model="uninstallModal.removeComponents"
                  type="checkbox"
                  id="remove-components"
                  class="mt-1 h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                >
                <label for="remove-components" class="text-sm text-red-800 dark:text-red-300">
                  <span class="font-medium">Удалить дефолтные компоненты (header, footer, blocks)</span>
                  <p class="mt-1 text-xs">Если эта опция включена, будут удалены все blade-шаблоны компонентов (header1-3, footer1-3, все блоки). В противном случае шаблоны будут сохранены.</p>
                </label>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 flex justify-end space-x-3">
            <button
              @click="closeUninstallModal"
              type="button"
              class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg font-medium transition"
            >
              Отмена
            </button>
            <button
              @click="confirmUninstall"
              type="button"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition"
            >
              Удалить модуль
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import { useModuleEvents } from '../composables/useModuleEvents';

const { success, error } = useModal();
const { triggerModuleUpdate } = useModuleEvents();
const { buttonStyle } = useTheme();

const loading = ref(false);
const modules = ref([]);
const processingModules = ref({});
const moduleOutputs = ref({});
const updating = ref(false);
const updateOutput = ref('');

const uninstallModal = ref({
  show: false,
  module: null,
  preserveDatabase: true,
  removeComponents: false
});

// Разделение модулей на функциональные и интеграции
const functionalModules = computed(() => {
  return modules.value.filter(m => m.type !== 'integration');
});

const integrationModules = computed(() => {
  return modules.value.filter(m => m.type === 'integration');
});

const loadModules = async () => {
  loading.value = true;
  try {
    const response = await fetch('/admin/api/modules');
    const data = await response.json();
    modules.value = data.modules;
  } catch (err) {
    console.error('Error loading modules:', err);
    await error('Ошибка при загрузке списка модулей');
  } finally {
    loading.value = false;
  }
};

const executeUpdate = async () => {
  updating.value = true;
  updateOutput.value = '';

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/modules/update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      }
    });

    const result = await response.json();

    if (result.success) {
      updateOutput.value = result.output;
      await success('Система обновлена успешно');
    } else {
      await error(result.message || 'Ошибка при обновлении системы');
    }
  } catch (err) {
    console.error('Update error:', err);
    await error('Ошибка при обновлении системы');
  } finally {
    updating.value = false;
  }
};

const installModule = async (module) => {
  processingModules.value[module.id] = true;
  moduleOutputs.value[module.id] = '';

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/modules/${module.id}/install`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({
        module_id: module.id
      })
    });

    const result = await response.json();

    if (result.success) {
      moduleOutputs.value[module.id] = result.output;
      await success('Модуль установлен успешно');
      await loadModules();
      triggerModuleUpdate(); // Notify App.vue to update sidebar
    } else {
      // Show output even on error
      if (result.output) {
        moduleOutputs.value[module.id] = result.output;
      }
      await error(result.message || 'Ошибка при установке модуля');
    }
  } catch (err) {
    console.error('Install error:', err);
    await error('Ошибка при установке модуля');
  } finally {
    processingModules.value[module.id] = false;
  }
};

const showUninstallModal = (module) => {
  uninstallModal.value = {
    show: true,
    module: module,
    preserveDatabase: true,
    removeComponents: false
  };
};

const closeUninstallModal = () => {
  uninstallModal.value = {
    show: false,
    module: null,
    preserveDatabase: true,
    removeComponents: false
  };
};

const updateModule = async (module) => {
  processingModules.value[module.id] = true;
  moduleOutputs.value[module.id] = '';

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/modules/${module.id}/update`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({
        module_id: module.id
      })
    });

    const result = await response.json();

    if (result.success) {
      moduleOutputs.value[module.id] = result.output;
      await success('Модуль обновлен успешно');
      await loadModules();
      triggerModuleUpdate(); // Notify App.vue to update sidebar
    } else {
      // Show output even on error
      if (result.output) {
        moduleOutputs.value[module.id] = result.output;
      }
      await error(result.message || 'Ошибка при обновлении модуля');
    }
  } catch (err) {
    console.error('Update error:', err);
    await error('Ошибка при обновлении модуля');
  } finally {
    processingModules.value[module.id] = false;
  }
};

const confirmUninstall = async () => {
  const module = uninstallModal.value.module;
  const preserveDatabase = uninstallModal.value.preserveDatabase;
  const removeComponents = uninstallModal.value.removeComponents;

  processingModules.value[module.id] = true;
  moduleOutputs.value[module.id] = '';
  closeUninstallModal();

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const requestBody = {
      preserve_database: preserveDatabase
    };

    // Add remove_components parameter for pages module
    if (module.id === 'pages') {
      requestBody.remove_components = removeComponents;
    }

    const response = await fetch(`/admin/api/modules/${module.id}/uninstall`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify(requestBody)
    });

    const result = await response.json();

    if (result.success) {
      moduleOutputs.value[module.id] = result.output;
      await success('Модуль удален успешно');
      await loadModules();
      triggerModuleUpdate(); // Notify App.vue to update sidebar
    } else {
      // Show output even on error
      if (result.output) {
        moduleOutputs.value[module.id] = result.output;
      }
      await error(result.message || 'Ошибка при удалении модуля');
    }
  } catch (err) {
    console.error('Uninstall error:', err);
    await error('Ошибка при удалении модуля');
  } finally {
    processingModules.value[module.id] = false;
  }
};

onMounted(() => {
  loadModules();
});
</script>
