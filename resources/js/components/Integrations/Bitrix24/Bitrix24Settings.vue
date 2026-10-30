<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Битрикс24</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Заказы, заявки и формы сайта попадают в CRM как лиды и сделки</p>
    </div>

    <div class="mb-6 flex gap-1 border-b border-gray-200 dark:border-gray-700">
      <button
        v-for="item in TABS"
        :key="item.id"
        type="button"
        @click="tab = item.id"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="tab === item.id ? 'text-gray-900 dark:text-white' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
        :style="tab === item.id ? { borderColor: themeColor } : {}"
      >{{ item.label }}</button>
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <!-- Connection -->
    <div v-else-if="tab === 'connection'" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
      <p v-if="connection.configured" class="text-sm text-gray-600 dark:text-gray-300">
        Сейчас подключено: <code class="px-1 rounded bg-gray-100 dark:bg-gray-700">{{ connection.webhook_masked }}</code>
      </p>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ connection.configured ? 'Новый адрес вебхука' : 'Адрес вебхука *' }}</label>
        <input v-model="webhookUrl" type="text" autocomplete="off" :class="inputClass" placeholder="https://портал.bitrix24.ru/rest/1/ключ/">
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          В Битрикс24: «Разработчикам» → «Другое» → «Входящий вебхук». Отметьте право «CRM (crm)» и вставьте сюда адрес. Он хранится в зашифрованном виде.
        </p>
      </div>
      <div class="flex flex-wrap gap-3">
        <ThemeButton variant="secondary" :disabled="testing || (!webhookUrl && !connection.configured)" @click="testConnection">{{ testing ? 'Проверка…' : 'Проверить подключение' }}</ThemeButton>
        <ThemeButton variant="primary" :disabled="saving || !webhookUrl" @click="saveWebhook">{{ saving ? 'Сохранение…' : 'Сохранить' }}</ThemeButton>
      </div>
    </div>

    <!-- Rules -->
    <div v-else-if="tab === 'rules'" class="space-y-4">
      <div class="flex justify-end">
        <ThemeButton variant="primary" @click="$router.push('/integrations/bitrix24/rules/create')">+ Добавить правило</ThemeButton>
      </div>
      <div v-if="!rules.length" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-10 text-center">
        <p class="text-base font-medium text-gray-900 dark:text-white">Правил пока нет</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Например: при создании заказа — создать сделку и контакт.</p>
      </div>
      <ul v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
        <li v-for="rule in rules" :key="rule.id" class="px-5 py-4 flex flex-wrap items-center gap-4">
          <ToggleSwitch :model-value="rule.is_active" :theme-color="themeColor" @update:model-value="toggle(rule)" />
          <div class="flex-1 min-w-[12rem]">
            <button type="button" @click="$router.push(`/integrations/bitrix24/rules/${rule.id}/edit`)" class="text-left font-medium text-gray-900 dark:text-white hover:underline">{{ rule.name }}</button>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ rule.event_label }} → {{ ruleSummary(rule) }}</p>
          </div>
          <button type="button" title="Удалить правило" @click="removeRule(rule)" class="p-2 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </button>
        </li>
      </ul>
    </div>

    <!-- Log -->
    <div v-else class="space-y-4">
      <div class="flex justify-end">
        <ThemeButton variant="secondary" :disabled="!logs.length" @click="clearLogs">Очистить журнал</ThemeButton>
      </div>
      <p v-if="!logs.length" class="p-8 text-center text-gray-500 dark:text-gray-400">Отправок пока не было</p>
      <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs text-gray-500 dark:text-gray-400">
            <tr>
              <th class="px-4 py-3">Дата</th>
              <th class="px-4 py-3">Правило</th>
              <th class="px-4 py-3">Статус</th>
              <th class="px-4 py-3">Результат</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-900 dark:text-gray-200">
            <tr v-for="log in logs" :key="log.id">
              <td class="px-4 py-3 whitespace-nowrap">{{ log.created_at }}</td>
              <td class="px-4 py-3">{{ log.rule_name }}</td>
              <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="STATUSES[log.status]?.badge">{{ STATUSES[log.status]?.label || log.status }}</span></td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-x-3">
                  <a v-for="link in crmLinks(log)" :key="link.label" :href="link.url" target="_blank" rel="noopener" class="text-blue-600 hover:underline dark:text-blue-400">{{ link.label }}</a>
                </div>
                <p v-if="log.message" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ log.message }}</p>
              </td>
              <td class="px-4 py-3 text-right">
                <ThemeButton v-if="log.can_retry" variant="secondary" size="sm" :disabled="retryingId === log.id" @click="retry(log)">Повторить</ThemeButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="lastPage > 1" class="flex justify-center gap-3">
        <ThemeButton variant="secondary" size="sm" :disabled="page <= 1" @click="loadLogs(page - 1)">Назад</ThemeButton>
        <span class="self-center text-sm text-gray-500 dark:text-gray-400">{{ page }} из {{ lastPage }}</span>
        <ThemeButton variant="secondary" size="sm" :disabled="page >= lastPage" @click="loadLogs(page + 1)">Вперёд</ThemeButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import ThemeButton from '../../ThemeButton.vue';
import ToggleSwitch from '../../ToggleSwitch.vue';
import { useModal } from '../../../composables/useModal';
import { useTheme } from '../../../composables/useTheme';
import { api, ENTITIES, MODES, STATUSES } from '../../../composables/useBitrix24';

const TABS = [
  { id: 'connection', label: 'Подключение' },
  { id: 'rules', label: 'Правила' },
  { id: 'logs', label: 'Журнал' },
];

const route = useRoute();
const { success, error, confirm } = useModal();
const { themeColor } = useTheme();

const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const tab = ref(TABS.some(item => item.id === route.query.tab) ? route.query.tab : 'connection');
const loading = ref(true);
const saving = ref(false);
const testing = ref(false);
const retryingId = ref(null);
const connection = ref({ configured: false, webhook_masked: null });
const webhookUrl = ref('');
const rules = ref([]);
const logs = ref([]);
const page = ref(1);
const lastPage = ref(1);

const ruleSummary = (rule) => {
  const parts = [];
  if (rule.entity !== 'none') parts.push(ENTITIES.find(entity => entity.value === rule.entity).label);
  if (rule.contact_mode !== 'none') parts.push('контакт');
  if (rule.company_mode !== 'none') parts.push('компания');
  return parts.join(' + ');
};

const crmLinks = (log) => {
  const portal = connection.value.webhook_masked ? new URL(connection.value.webhook_masked).origin : null;
  const links = [
    log.entity_id && { label: `${log.entity === 'deal' ? 'Сделка' : 'Лид'} №${log.entity_id}`, path: `${log.entity}/details/${log.entity_id}` },
    log.contact_id && { label: `Контакт №${log.contact_id}`, path: `contact/details/${log.contact_id}` },
    log.company_id && { label: `Компания №${log.company_id}`, path: `company/details/${log.company_id}` },
  ].filter(Boolean);
  return portal ? links.map(link => ({ label: link.label, url: `${portal}/crm/${link.path}/` })) : [];
};

const loadRules = async () => {
  rules.value = (await api('GET', '/rules')).data;
};

const loadLogs = async (target = 1) => {
  const response = await api('GET', `/logs?page=${target}`);
  logs.value = response.data;
  page.value = response.current_page;
  lastPage.value = response.last_page;
};

const load = async () => {
  loading.value = true;
  try {
    connection.value = await api('GET', '');
    if (tab.value === 'rules') await loadRules();
    if (tab.value === 'logs') await loadLogs();
  } catch (e) {
    await error(e.message);
  } finally {
    loading.value = false;
  }
};

const testConnection = async () => {
  testing.value = true;
  try {
    await success((await api('POST', '/test', { webhook_url: webhookUrl.value || null })).message);
  } catch (e) {
    await error(e.message);
  } finally {
    testing.value = false;
  }
};

const saveWebhook = async () => {
  saving.value = true;
  try {
    await api('POST', '', { webhook_url: webhookUrl.value });
    webhookUrl.value = '';
    connection.value = await api('GET', '');
    await success('Вебхук сохранён');
  } catch (e) {
    await error(e.message);
  } finally {
    saving.value = false;
  }
};

const toggle = async (rule) => {
  try {
    rule.is_active = (await api('POST', `/rules/${rule.id}/toggle`)).is_active;
  } catch (e) {
    await error(e.message);
  }
};

const removeRule = async (rule) => {
  if (!await confirm('Удаление правила', `Удалить правило «${rule.name}»?`)) return;
  try {
    await api('DELETE', `/rules/${rule.id}`);
    await loadRules();
  } catch (e) {
    await error(e.message);
  }
};

const retry = async (log) => {
  retryingId.value = log.id;
  try {
    await api('POST', `/logs/${log.id}/retry`);
    await loadLogs(page.value);
  } catch (e) {
    await error(e.message);
  } finally {
    retryingId.value = null;
  }
};

const clearLogs = async () => {
  if (!await confirm('Очистка журнала', 'Удалить все записи журнала? Повторить неудачные отправки будет нельзя.')) return;
  try {
    await api('DELETE', '/logs');
    await loadLogs();
  } catch (e) {
    await error(e.message);
  }
};

watch(tab, load);
onMounted(load);
</script>
