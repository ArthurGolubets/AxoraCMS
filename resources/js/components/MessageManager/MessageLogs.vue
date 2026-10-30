<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">История отправки</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Все сообщения, которые отправила система: что, кому и дошло ли</p>
      </div>
      <ThemeButton variant="danger" :disabled="!total" @click="clearAll">Очистить историю</ThemeButton>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <input v-model="filters.search" type="text" placeholder="Найти по получателю или тексту" class="w-72 px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white" @input="debouncedLoad">
      <select v-model="filters.status" class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white" @change="load(1)">
        <option value="">Все статусы</option>
        <option value="sent">Отправлено</option>
        <option value="failed">Не отправлено (ошибка)</option>
        <option value="skipped">Пропущено (нечего отправлять)</option>
      </select>
      <select v-model="filters.channel" class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white" @change="load(1)">
        <option value="">Все каналы</option>
        <option value="telegram">Telegram</option>
        <option value="email">Email</option>
      </select>
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>
    <div v-else-if="!logs.length" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-10 text-center text-sm text-gray-500 dark:text-gray-400">Пока ничего не отправлялось</div>

    <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-600 dark:text-gray-300">
          <tr>
            <th class="px-4 py-3 font-medium">Дата</th>
            <th class="px-4 py-3 font-medium">Когда</th>
            <th class="px-4 py-3 font-medium">Канал</th>
            <th class="px-4 py-3 font-medium hidden md:table-cell">Получатель</th>
            <th class="px-4 py-3 font-medium">Статус</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <template v-for="log in logs" :key="log.id">
            <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40" @click="opened = opened === log.id ? null : log.id">
              <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ formatDate(log.created_at) }}</td>
              <td class="px-4 py-3 text-gray-900 dark:text-white">{{ log.event_label }}<span v-if="log.is_test" class="ml-2 px-1.5 py-0.5 text-[11px] rounded bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">тест</span></td>
              <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="CHANNELS[log.channel]?.badge">{{ CHANNELS[log.channel]?.label || log.channel }}</span></td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden md:table-cell truncate max-w-xs">{{ log.recipient || '—' }}</td>
              <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="statuses[log.status].class">{{ statuses[log.status].label }}</span></td>
            </tr>
            <tr v-if="opened === log.id">
              <td colspan="5" class="px-4 py-4 bg-gray-50 dark:bg-gray-900/30 space-y-2">
                <p v-if="log.error" class="text-sm text-red-600 dark:text-red-400">{{ log.error }}</p>
                <p v-if="log.recipient" class="text-sm text-gray-600 dark:text-gray-300"><b>Получатель:</b> {{ log.recipient }}</p>
                <p v-if="log.subject" class="text-sm text-gray-600 dark:text-gray-300"><b>Тема:</b> {{ log.subject }}</p>
                <iframe v-if="log.channel === 'email' && log.body" sandbox="" :srcdoc="log.body" class="w-full bg-white rounded border border-gray-200" style="height: 18rem"></iframe>
                <pre v-else-if="log.body" class="text-sm whitespace-pre-wrap break-words text-gray-800 dark:text-gray-200 font-sans">{{ log.body }}</pre>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div v-if="lastPage > 1" class="mt-4 flex items-center justify-center gap-3 text-sm">
      <ThemeButton variant="secondary" size="sm" :disabled="page <= 1" @click="load(page - 1)">Назад</ThemeButton>
      <span class="text-gray-600 dark:text-gray-400">Страница {{ page }} из {{ lastPage }} · записей: {{ total }}</span>
      <ThemeButton variant="secondary" size="sm" :disabled="page >= lastPage" @click="load(page + 1)">Далее</ThemeButton>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ThemeButton from '../ThemeButton.vue';
import { useModal } from '../../composables/useModal';
import { api, CHANNELS } from '../../composables/useMessageManager';

const { confirm, error } = useModal();

const statuses = {
  sent: { label: 'Отправлено', class: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' },
  failed: { label: 'Не отправлено', class: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' },
  skipped: { label: 'Пропущено', class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' },
};

const loading = ref(true);
const logs = ref([]);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const opened = ref(null);
const filters = ref({ search: '', status: '', channel: '' });
let timer = null;

const formatDate = (value) => (value ? new Date(value).toLocaleString('ru-RU') : '');

const load = async (target = 1) => {
  loading.value = true;
  try {
    const query = new URLSearchParams({ page: target, ...Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v)) });
    const result = await api('GET', `/logs?${query}`);
    logs.value = result.data;
    page.value = result.meta.current_page;
    lastPage.value = result.meta.last_page;
    total.value = result.meta.total;
  } catch (e) {
    await error(e.message);
  } finally {
    loading.value = false;
  }
};

const debouncedLoad = () => {
  clearTimeout(timer);
  timer = setTimeout(() => load(1), 400);
};

const clearAll = async () => {
  if (!await confirm('Очистка истории', 'Удалить всю историю отправки? Это нельзя отменить.')) return;
  try {
    await api('DELETE', '/logs');
    await load(1);
  } catch (e) {
    await error(e.message);
  }
};

onMounted(() => load(1));
</script>
