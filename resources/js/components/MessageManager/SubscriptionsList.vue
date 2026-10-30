<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Менеджер сообщений</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Правила отправки: когда на сайте что-то происходит (новый заказ, комментарий, заявка), система сама отправляет нужные сообщения</p>
      </div>
      <ThemeButton variant="primary" @click="$router.push('/message-manager/create')">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Добавить правило
      </ThemeButton>
    </div>

    <div v-if="telegramReplaced" class="mb-4 p-4 rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20 text-sm text-blue-800 dark:text-blue-300">
      Уведомления о заказах и оплатах в Telegram теперь настраиваются здесь, в правилах. Старая автоматическая отправка отключена, чтобы сообщения не дублировались.
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <div v-else-if="!subscriptions.length" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-10 text-center">
      <p class="text-base font-medium text-gray-900 dark:text-white">Правил пока нет</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Например: когда создан заказ — написать менеджеру в Telegram и отправить клиенту письмо. Нажмите «Добавить правило».</p>
    </div>

    <div v-else class="space-y-6">
      <div v-for="group in groups" :key="group.event" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
          <p class="text-xs text-gray-500 dark:text-gray-400">Когда</p>
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ group.label }}</h3>
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
          <li v-for="subscription in group.items" :key="subscription.id" class="px-5 py-4 flex flex-wrap items-center gap-4">
            <ToggleSwitch :model-value="subscription.is_active" :theme-color="themeColor" @update:model-value="toggle(subscription)" />
            <div class="flex-1 min-w-[12rem]">
              <button type="button" @click="$router.push(`/message-manager/${subscription.id}/edit`)" class="text-left font-medium text-gray-900 dark:text-white hover:underline">{{ subscription.name }}</button>
              <div class="mt-1 flex flex-wrap items-center gap-1.5">
                <template v-for="(action, index) in subscription.actions" :key="action.id">
                  <span v-if="index > 0" class="text-gray-400 text-xs">→</span>
                  <span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="[CHANNELS[action.channel].badge, { 'opacity-50 line-through': !action.is_active }]">
                    {{ CHANNELS[action.channel].label }}<template v-if="action.delay_seconds"> · через {{ formatDelay(action.delay_seconds) }}</template>
                  </span>
                </template>
                <span v-if="subscription.conditions.length" class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">с условиями</span>
              </div>
            </div>
            <div class="flex items-center gap-1">
              <button type="button" title="Изменить правило" @click="$router.push(`/message-manager/${subscription.id}/edit`)" class="p-2 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button type="button" title="Сделать копию" @click="duplicate(subscription)" class="p-2 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              </button>
              <button type="button" title="Удалить" @click="remove(subscription)" class="p-2 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ThemeButton from '../ThemeButton.vue';
import ToggleSwitch from '../ToggleSwitch.vue';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';
import { api, CHANNELS, DELAY_UNITS } from '../../composables/useMessageManager';

const { confirm, error } = useModal();
const { themeColor } = useTheme();

const loading = ref(true);
const subscriptions = ref([]);
const telegramReplaced = ref(false);

const groups = computed(() => {
  const byEvent = {};
  subscriptions.value.forEach(subscription => {
    (byEvent[subscription.event] ||= { event: subscription.event, label: subscription.event_label, items: [] }).items.push(subscription);
  });
  return Object.values(byEvent);
});

const formatDelay = (seconds) => {
  const unit = [...DELAY_UNITS].reverse().find(u => seconds % u.value === 0) || DELAY_UNITS[0];
  return `${seconds / unit.value} ${unit.label}`;
};

const load = async () => {
  loading.value = true;
  try {
    const [list, meta] = await Promise.all([api('GET', '/subscriptions'), api('GET', '/meta')]);
    subscriptions.value = list.data;
    telegramReplaced.value = meta.channels.telegram.installed;
  } catch (e) {
    await error(e.message);
  } finally {
    loading.value = false;
  }
};

const toggle = async (subscription) => {
  try {
    subscription.is_active = (await api('POST', `/subscriptions/${subscription.id}/toggle`)).is_active;
  } catch (e) {
    await error(e.message);
  }
};

const duplicate = async (subscription) => {
  try {
    await api('POST', `/subscriptions/${subscription.id}/duplicate`);
    await load();
  } catch (e) {
    await error(e.message);
  }
};

const remove = async (subscription) => {
  if (!await confirm('Удаление правила', `Удалить правило «${subscription.name}»? Все его шаги тоже будут удалены.`)) return;
  try {
    await api('DELETE', `/subscriptions/${subscription.id}`);
    await load();
  } catch (e) {
    await error(e.message);
  }
};

onMounted(load);
</script>
