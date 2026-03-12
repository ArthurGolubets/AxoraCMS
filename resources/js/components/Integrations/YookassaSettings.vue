<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Настройки ЮКassa</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Настройка интеграции с платежной системой ЮКassa</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <form @submit.prevent="saveSettings">
        <!-- Shop ID -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Shop ID <span class="text-red-500">*</span>
          </label>
          <input
            v-model="settings.shop_id"
            type="text"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            placeholder="123456"
            required
          />
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Идентификатор магазина из личного кабинета ЮКassa
          </p>
        </div>

        <!-- Secret Key -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Секретный ключ <span class="text-red-500">*</span>
          </label>
          <input
            v-model="settings.secret_key"
            type="password"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            placeholder="live_***********"
            required
          />
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Секретный ключ для работы с API ЮКassa
          </p>
        </div>

        <!-- Info Block -->
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-300">
              <p class="font-medium mb-1">Где найти данные для интеграции:</p>
              <ol class="list-decimal list-inside space-y-1">
                <li>Войдите в личный кабинет ЮКassa</li>
                <li>Перейдите в раздел "Настройки" → "Данные для запросов к API"</li>
                <li>Скопируйте Shop ID и создайте секретный ключ</li>
              </ol>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="$router.back()"
            class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg transition"
          >
            Отмена
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useModal } from '../../composables/useModal';

const { success, error } = useModal();

const loading = ref(false);
const saving = ref(false);
const settings = ref({
  shop_id: '',
  secret_key: ''
});

const loadSettings = async () => {
  loading.value = true;
  try {
    const response = await fetch('/admin/api/integrations/yookassa');
    const data = await response.json();

    settings.value = {
      shop_id: data.shop_id || '',
      secret_key: data.secret_key || ''
    };
  } catch (err) {
    console.error('Error loading settings:', err);
    await error('Ошибка при загрузке настроек');
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  if (!settings.value.shop_id || !settings.value.secret_key) {
    await error('Заполните все обязательные поля');
    return;
  }

  saving.value = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/integrations/yookassa', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({
        shop_id: settings.value.shop_id,
        secret_key: settings.value.secret_key
      })
    });

    const result = await response.json();

    if (result.success) {
      await success('Настройки ЮКassa успешно сохранены');
    } else {
      await error(result.message || 'Ошибка при сохранении настроек');
    }
  } catch (err) {
    console.error('Save error:', err);
    await error('Ошибка при сохранении настроек');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadSettings();
});
</script>
