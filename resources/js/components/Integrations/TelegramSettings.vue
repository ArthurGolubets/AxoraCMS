<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Настройки Telegram</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Настройка интеграции с Telegram Bot для отправки уведомлений</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <form @submit.prevent="saveSettings">
        <!-- Bot Token -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Bot Token <span class="text-red-500">*</span>
          </label>
          <input
            v-model="settings.bot_token"
            type="text"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11"
            required
          />
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Токен бота можно получить у <a href="https://t.me/BotFather" target="_blank" class="text-blue-600 hover:underline">@BotFather</a>
          </p>
        </div>

        <!-- Chat IDs -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Chat IDs <span class="text-red-500">*</span>
          </label>

          <div class="space-y-2 mb-2">
            <div v-for="(chatId, index) in settings.chat_ids" :key="index" class="flex gap-2">
              <input
                v-model="settings.chat_ids[index]"
                type="text"
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="-1001234567890"
              />
              <button
                type="button"
                @click="removeChatId(index)"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"
              >
                Удалить
              </button>
            </div>
          </div>

          <button
            type="button"
            @click="addChatId"
            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition"
          >
            + Добавить Chat ID
          </button>

          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            ID чата или канала, куда будут отправляться уведомления. Для получения ID используйте <a href="https://t.me/userinfobot" target="_blank" class="text-blue-600 hover:underline">@userinfobot</a>
          </p>
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
  bot_token: '',
  chat_ids: ['']
});

const loadSettings = async () => {
  loading.value = true;
  try {
    const response = await fetch('/admin/api/integrations/telegram');
    const data = await response.json();

    settings.value = {
      bot_token: data.bot_token || '',
      chat_ids: data.chat_ids && data.chat_ids.length > 0 ? data.chat_ids : ['']
    };
  } catch (err) {
    console.error('Error loading settings:', err);
    await error('Ошибка при загрузке настроек');
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  // Filter out empty chat IDs
  const chatIds = settings.value.chat_ids.filter(id => id.trim() !== '');

  if (!settings.value.bot_token || chatIds.length === 0) {
    await error('Заполните все обязательные поля');
    return;
  }

  saving.value = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/integrations/telegram', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({
        bot_token: settings.value.bot_token,
        chat_ids: chatIds
      })
    });

    const result = await response.json();

    if (result.success) {
      await success('Настройки Telegram успешно сохранены');
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

const addChatId = () => {
  settings.value.chat_ids.push('');
};

const removeChatId = (index) => {
  if (settings.value.chat_ids.length > 1) {
    settings.value.chat_ids.splice(index, 1);
  }
};

onMounted(() => {
  loadSettings();
});
</script>
