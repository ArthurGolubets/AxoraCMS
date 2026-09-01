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
        <!-- Send mode -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Режим отправки
          </label>
          <div class="space-y-2">
            <label class="flex items-start gap-3 p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer"
                   :class="{ 'border-blue-500 ring-1 ring-blue-500': settings.send_mode === 'default' }">
              <input type="radio" value="default" v-model="settings.send_mode" class="mt-1 w-4 h-4 text-blue-600" />
              <span>
                <span class="block text-sm font-medium text-gray-900 dark:text-white">Отправка по умолчанию</span>
                <span class="block text-sm text-gray-500 dark:text-gray-400">Сообщения уходят напрямую через стандартный API Telegram</span>
              </span>
            </label>
            <label class="flex items-start gap-3 p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer"
                   :class="{ 'border-blue-500 ring-1 ring-blue-500': settings.send_mode === 'external' }">
              <input type="radio" value="external" v-model="settings.send_mode" class="mt-1 w-4 h-4 text-blue-600" />
              <span>
                <span class="block text-sm font-medium text-gray-900 dark:text-white">Отправка через внешний адрес</span>
                <span class="block text-sm text-gray-500 dark:text-gray-400">Запрос уходит на указанный внешний сервис, который сам обращается к Telegram</span>
              </span>
            </label>
          </div>
        </div>

        <!-- External service settings -->
        <div v-if="settings.send_mode === 'external'" class="mb-6 p-4 bg-gray-50 dark:bg-gray-900/40 rounded-lg space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Адрес внешнего сервиса <span class="text-red-500">*</span>
            </label>
            <input
              v-model="settings.external_url"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="https://notify.holart-dev.store/api/telegram/sendMessage"
            />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Полный URL эндпоинта. На него будет отправляться POST-запрос с полями token, chat, message
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Защитный токен
            </label>
            <input
              v-model="settings.external_token"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="Bearer-токен для авторизации на внешнем сервисе"
            />
          </div>
        </div>

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
  chat_ids: [''],
  send_mode: 'default',
  external_url: '',
  external_token: ''
});

const loadSettings = async () => {
  loading.value = true;
  try {
    const response = await fetch('/admin/api/integrations/telegram');
    const data = await response.json();

    settings.value = {
      bot_token: data.bot_token || '',
      chat_ids: data.chat_ids && data.chat_ids.length > 0 ? data.chat_ids : [''],
      send_mode: data.send_mode || 'default',
      external_url: data.external_url || '',
      external_token: data.external_token || ''
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

  if (settings.value.send_mode === 'external' && !settings.value.external_url.trim()) {
    await error('Укажите адрес внешнего сервиса');
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
        chat_ids: chatIds,
        send_mode: settings.value.send_mode,
        external_url: settings.value.external_url,
        external_token: settings.value.external_token
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
