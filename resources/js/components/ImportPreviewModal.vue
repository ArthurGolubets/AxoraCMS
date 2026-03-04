<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="close"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Предпросмотр импорта
            </h3>
            <button @click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="px-6 py-4 max-h-96 overflow-y-auto">
          <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Анализ файла...</p>
          </div>

          <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-red-800 dark:text-red-400">{{ error }}</p>
          </div>

          <!-- Import Progress -->
          <div v-else-if="importProgress && importing" class="py-8">
            <div class="mb-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Импорт данных...</span>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ importProgress.percentage }}%</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                <div
                  class="h-full bg-blue-600 transition-all duration-300 ease-out"
                  :style="{ width: importProgress.percentage + '%' }"
                ></div>
              </div>
            </div>
            <div class="text-center">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Обработано: {{ importProgress.processed || 0 }} из {{ importProgress.total || 0 }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                Создано: {{ importProgress.created || 0 }} | Обновлено: {{ importProgress.updated || 0 }}
              </p>
              <p v-if="importProgress.status === 'completed'" class="text-sm text-green-600 dark:text-green-400 mt-3 font-medium">
                ✓ Импорт завершен успешно! Создано: {{ importProgress.created || 0 }}, Обновлено: {{ importProgress.updated || 0 }}
              </p>
              <p v-else-if="importProgress.status === 'failed'" class="text-sm text-red-600 dark:text-red-400 mt-3 font-medium">
                ✗ Импорт завершился с ошибкой
              </p>
              <div v-if="importProgress.errors && importProgress.errors.length > 0" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-left">
                <h4 class="text-sm font-semibold text-red-900 dark:text-red-300 mb-2">Ошибки:</h4>
                <ul class="text-xs text-red-700 dark:text-red-400 space-y-1 max-h-32 overflow-y-auto">
                  <li v-for="(err, index) in importProgress.errors" :key="index">• {{ err }}</li>
                </ul>
              </div>
            </div>
          </div>

          <div v-else-if="preview">
            <!-- Summary -->
            <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-blue-900 dark:text-blue-300">
                    Всего записей: {{ preview.total }}
                  </p>
                  <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                    Будет создано: {{ preview.preview.filter(i => i.action === 'create').length }} |
                    Будет обновлено: {{ preview.preview.filter(i => i.action === 'update').length }}
                  </p>
                </div>
                <div v-if="!preview.valid" class="text-red-600 dark:text-red-400">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div v-else class="text-green-600 dark:text-green-400">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Errors -->
            <div v-if="preview.errors && preview.errors.length > 0" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
              <h4 class="text-sm font-semibold text-red-900 dark:text-red-300 mb-2">Ошибки:</h4>
              <ul class="text-xs text-red-700 dark:text-red-400 space-y-1">
                <li v-for="(err, index) in preview.errors" :key="index">• {{ err }}</li>
              </ul>
            </div>

            <!-- Preview Table -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Строка</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действие</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Название</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Статус</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="item in preview.preview" :key="item.row"
                      :class="item.status === 'error' ? 'bg-red-50 dark:bg-red-900/10' : 'bg-white dark:bg-gray-800'">
                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ item.row }}</td>
                    <td class="px-3 py-2 text-sm">
                      <span v-if="item.action === 'create'" class="px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">Создать</span>
                      <span v-else class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded">Обновить</span>
                    </td>
                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-white truncate max-w-xs">{{ item.name }}</td>
                    <td class="px-3 py-2 text-sm">
                      <span v-if="item.status === 'error'" class="text-red-600 dark:text-red-400 text-xs">{{ item.error }}</span>
                      <span v-else class="text-green-600 dark:text-green-400 text-xs">Готово</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 flex justify-end space-x-3">
          <button
            @click="close"
            type="button"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg font-medium transition"
            :disabled="importing && importProgress?.status !== 'completed' && importProgress?.status !== 'failed'"
          >
            {{ (importing && importProgress?.status !== 'completed' && importProgress?.status !== 'failed') ? 'Импорт...' : 'Закрыть' }}
          </button>
          <button
            v-if="preview && preview.valid && !importing"
            @click="confirmImport"
            type="button"
            :style="buttonStyle"
            class="px-4 py-2 text-white rounded-lg font-medium transition-opacity hover:opacity-90"
          >
            Импортировать
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useTheme } from '../composables/useTheme';

const { buttonStyle } = useTheme();

const props = defineProps({
  show: Boolean,
  file: File,
  entityType: String // 'catalogs' or 'products'
});

const emit = defineEmits(['close', 'import']);

const loading = ref(false);
const error = ref('');
const preview = ref(null);
const importing = ref(false);
const importProgress = ref(null);
let progressInterval = null;

watch(() => props.file, async (newFile) => {
  if (newFile && props.show) {
    await loadPreview();
  }
});

watch(() => props.show, async (newShow) => {
  if (newShow && props.file) {
    await loadPreview();
  } else if (!newShow) {
    // Clean up interval when modal closes
    if (progressInterval) {
      clearInterval(progressInterval);
      progressInterval = null;
    }
  }
});

const loadPreview = async () => {
  loading.value = true;
  error.value = '';
  preview.value = null;
  importing.value = false;
  importProgress.value = null;

  try {
    const formData = new FormData();
    formData.append('file', props.file);

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/${props.entityType}/import-preview`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token
      },
      body: formData
    });

    if (!response.ok) {
      throw new Error('Ошибка при анализе файла');
    }

    preview.value = await response.json();
  } catch (err) {
    console.error('Preview error:', err);
    error.value = err.message || 'Ошибка при загрузке предпросмотра';
  } finally {
    loading.value = false;
  }
};

const confirmImport = async () => {
  importing.value = true;
  importProgress.value = {
    status: 'queued',
    created: 0,
    updated: 0,
    total: preview.value.total,
    processed: 0,
    errors: [],
    percentage: 0
  };

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/${props.entityType}/import`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ items: preview.value.preview })
    });

    if (!response.ok) {
      throw new Error('Ошибка при запуске импорта');
    }

    const result = await response.json();

    if (result.import_id) {
      // Start polling for progress
      checkImportProgress(result.import_id);
    } else {
      throw new Error('Не получен ID импорта');
    }
  } catch (err) {
    console.error('Import error:', err);
    error.value = err.message || 'Ошибка при импорте';
    importing.value = false;
  }
};

const checkImportProgress = (importId) => {
  progressInterval = setInterval(async () => {
    try {
      const response = await fetch(`/admin/api/${props.entityType}/import-progress/${importId}`);

      if (!response.ok) {
        throw new Error('Ошибка при получении статуса импорта');
      }

      const progress = await response.json();
      importProgress.value = progress;

      // Stop polling if completed or failed
      if (progress.status === 'completed' || progress.status === 'failed') {
        clearInterval(progressInterval);
        progressInterval = null;

        if (progress.status === 'completed') {
          // Notify parent to refresh data
          emit('import', preview.value);
        }
      }
    } catch (err) {
      console.error('Progress check error:', err);
      clearInterval(progressInterval);
      progressInterval = null;
      error.value = err.message || 'Ошибка при проверке статуса';
      importing.value = false;
    }
  }, 1000); // Check every second
};

const close = () => {
  const canClose = !importing.value ||
    importProgress.value?.status === 'completed' ||
    importProgress.value?.status === 'failed';

  if (canClose) {
    if (progressInterval) {
      clearInterval(progressInterval);
      progressInterval = null;
    }
    emit('close');
    preview.value = null;
    error.value = '';
    importing.value = false;
    importProgress.value = null;
  }
};
</script>
