<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Экспорт сущностей</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Выгрузка заказов, инфоблоков и обратной связи в Excel или CSV</p>
    </div>

    <!-- Running tasks -->
    <div v-for="task in activeTasks" :key="task.id" class="mb-4 flex flex-wrap items-center gap-4 p-4 rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-900/20">
      <svg class="w-5 h-5 text-blue-600 dark:text-blue-300 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 truncate">{{ task.title }}</p>
        <p class="text-xs text-blue-700 dark:text-blue-300">Выполнено {{ task.progress }}% · {{ task.processed }} из {{ task.total }}</p>
      </div>
      <button type="button" @click="openTaskId = task.id" class="px-3 py-1.5 text-sm rounded-lg bg-white dark:bg-gray-800 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-gray-700">
        Открыть прогресс
      </button>
    </div>

    <div v-if="loadingMeta" class="p-12 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <div v-else-if="!cards.length" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-10 text-center">
      <p class="text-base font-medium text-gray-900 dark:text-white">Нечего выгружать</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Экспорт появляется для установленных модулей «Коммерция», «Информационные блоки» и «Обратная связь».</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div v-for="card in cards" :key="card.key" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 flex flex-col">
        <div class="flex items-start gap-3 mb-4">
          <span class="w-10 h-10 shrink-0 rounded-lg flex items-center justify-center" :class="card.iconClass">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon"/></svg>
          </span>
          <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ card.title }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ card.description }}</p>
          </div>
        </div>

        <div class="space-y-3 flex-1">
          <div v-if="card.key === 'infoblock'">
            <label :class="labelClass">Инфоблок *</label>
            <select v-model="forms.infoblock.infoblock_id" :class="inputClass">
              <option :value="null" disabled>— Выберите инфоблок —</option>
              <option v-for="ib in meta.infoblocks" :key="ib.id" :value="ib.id">{{ ib.name }}</option>
            </select>
          </div>

          <div v-if="card.key === 'callback'">
            <label :class="labelClass">Что выгрузить</label>
            <select v-model="forms.callback.kind" :class="inputClass">
              <option v-for="(label, kind) in meta.callback_kinds" :key="kind" :value="kind">{{ label }}</option>
            </select>
          </div>

          <div v-if="card.key === 'custom_form'">
            <label :class="labelClass">Форма *</label>
            <select v-model="forms.custom_form.form_id" :class="inputClass">
              <option :value="null" disabled>— Выберите форму —</option>
              <option v-for="form in meta.custom_forms" :key="form.id" :value="form.id">{{ form.name }}</option>
            </select>
          </div>

          <div v-if="card.key === 'orders'" class="grid grid-cols-2 gap-3">
            <div>
              <label :class="labelClass">Статус</label>
              <select v-model="forms.orders.delivery_status" :class="inputClass">
                <option value="">Все</option>
                <option value="pending">Новые</option>
                <option value="processing">В обработке</option>
                <option value="shipped">Отправлены</option>
                <option value="delivered">Доставлены</option>
                <option value="cancelled">Отменены</option>
              </select>
            </div>
            <div>
              <label :class="labelClass">Оплата</label>
              <select v-model="forms.orders.payment_status" :class="inputClass">
                <option value="">Все</option>
                <option value="pending">Ожидают оплаты</option>
                <option value="paid">Оплачены</option>
                <option value="failed">Ошибка оплаты</option>
                <option value="refunded">Возврат</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label :class="labelClass">С даты</label>
              <input v-model="forms[card.key].date_from" type="date" :class="inputClass">
            </div>
            <div>
              <label :class="labelClass">По дату</label>
              <input v-model="forms[card.key].date_to" type="date" :class="inputClass">
            </div>
          </div>
        </div>

        <div class="mt-5 flex items-center gap-3">
          <div class="grid grid-cols-2 gap-1 p-1 rounded-lg bg-gray-100 dark:bg-gray-900">
            <button v-for="format in ['xlsx', 'csv']" :key="format" type="button" @click="formats[card.key] = format"
              class="px-3 py-1 text-xs font-medium rounded-md uppercase"
              :class="formats[card.key] === format ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">{{ format }}</button>
          </div>
          <ThemeButton variant="primary" class="flex-1" :disabled="startingKey === card.key || !canStart(card.key)" @click="startExport(card.key)">
            {{ startingKey === card.key ? 'Запуск...' : 'Выгрузить' }}
          </ThemeButton>
        </div>
      </div>
    </div>

    <div class="mt-6">
      <TaskHistory ref="history" scope="entities" @open="(task) => (openTaskId = task.id)" @loaded="onHistoryLoaded" />
    </div>

    <TaskProgressPanel v-if="openTaskId" :task-id="openTaskId" @updated="onTaskUpdated" @close="openTaskId = null" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from '../ThemeButton.vue';
import TaskHistory from './TaskHistory.vue';
import TaskProgressPanel from './TaskProgressPanel.vue';
import { useModal } from '../../composables/useModal';
import { csrfHeaders, responseError } from './importExport';

const route = useRoute();
const router = useRouter();
const { error } = useModal();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5';
const inputClass = 'w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white';

const CARD_DEFINITIONS = [
  { key: 'orders', title: 'Заказы', description: 'С товарами: одна строка — одна позиция заказа', icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', iconClass: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' },
  { key: 'infoblock', title: 'Инфоблоки', description: 'Элементы инфоблока со всеми полями', icon: 'M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z', iconClass: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' },
  { key: 'callback', title: 'Обратная связь', description: 'Комментарии, обращения или подписки', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', iconClass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' },
  { key: 'custom_form', title: 'Свои формы', description: 'Записи выбранной формы', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', iconClass: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300' },
];

const meta = ref({ exports: {}, infoblocks: [], custom_forms: [], callback_kinds: {} });
const loadingMeta = ref(true);
const history = ref(null);
const activeTasks = ref([]);
const openTaskId = ref(null);
const startingKey = ref(null);

const forms = ref({
  orders: { delivery_status: '', payment_status: '', date_from: '', date_to: '' },
  infoblock: { infoblock_id: null, date_from: '', date_to: '' },
  callback: { kind: 'comments', date_from: '', date_to: '' },
  custom_form: { form_id: null, date_from: '', date_to: '' },
});
const formats = ref({ orders: 'xlsx', infoblock: 'xlsx', callback: 'xlsx', custom_form: 'xlsx' });

// Only entities of installed modules are shown.
const cards = computed(() => CARD_DEFINITIONS.filter((card) => meta.value.exports[card.key]));

const canStart = (key) => ({
  infoblock: !!forms.value.infoblock.infoblock_id,
  custom_form: !!forms.value.custom_form.form_id,
}[key] ?? true);

const loadMeta = async () => {
  try {
    const response = await fetch('/admin/api/import-export/meta', { headers: csrfHeaders(false) });
    if (response.ok) meta.value = await response.json();
  } finally {
    loadingMeta.value = false;
  }
};

const startExport = async (key) => {
  startingKey.value = key;
  try {
    const options = Object.fromEntries(Object.entries(forms.value[key]).filter(([, value]) => value !== '' && value !== null));
    const response = await fetch('/admin/api/import-export/export', {
      method: 'POST',
      headers: csrfHeaders(),
      body: JSON.stringify({ entity: key, format: formats.value[key], options }),
    });
    if (!response.ok) throw new Error(await responseError(response, 'Не удалось запустить экспорт'));
    const task = await response.json();
    openTaskId.value = task.id;
    history.value?.reload();
  } catch (e) {
    error(e.message);
  } finally {
    startingKey.value = null;
  }
};

const onHistoryLoaded = (tasks) => {
  activeTasks.value = tasks.filter((t) => t.is_active);
};

const onTaskUpdated = (task) => {
  if (!task.is_active) history.value?.reload();
};

// Links from notifications: ?task=ID opens its progress.
const openFromQuery = () => {
  const id = Number(route.query.task);
  if (!id) return;
  openTaskId.value = id;
  router.replace({ query: {} });
};

watch(() => route.query.task, openFromQuery);

onMounted(() => {
  loadMeta();
  openFromQuery();
});
</script>
