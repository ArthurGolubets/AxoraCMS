<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Журнал активности</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Тип сущности</label>
          <select v-model="filters.entity_type" @change="loadLogs" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white">
            <option value="">Все</option>
            <option value="product">Товар</option>
            <option value="catalog">Категория</option>
            <option value="order">Заказ</option>
            <option value="module">Модуль</option>
            <option value="setting">Настройка</option>
            <option value="administrator">Администратор</option>
            <option value="promocode">Промокод</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Действие</label>
          <select v-model="filters.action" @change="loadLogs" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white">
            <option value="">Все</option>
            <option value="created">Создано</option>
            <option value="updated">Обновлено</option>
            <option value="deleted">Удалено</option>
            <option value="installed">Установлено</option>
            <option value="uninstalled">Удалено</option>
            <option value="changed">Изменено</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Дата от</label>
          <input v-model="filters.date_from" @change="loadLogs" type="date" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Дата до</label>
          <input v-model="filters.date_to" @change="loadLogs" type="date" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white">
        </div>
      </div>
      <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Поиск</label>
        <input v-model="filters.search" @input="debounceSearch" type="text" placeholder="Поиск по названию или пользователю..." class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white">
      </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Дата</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Пользователь</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Действие</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Тип</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">IP</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-if="loading">
            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Загрузка...</td>
          </tr>
          <tr v-else-if="logs.length === 0">
            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Нет записей</td>
          </tr>
          <tr v-else v-for="log in logs" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ formatDate(log.created_at) }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ log.admin?.name || '-' }}</td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 text-xs rounded-full" :class="getActionClass(log.action)">
                {{ getActionLabel(log.action) }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ getEntityTypeLabel(log.entity_type) }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ log.description || '-' }}</td>
            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ log.ip_address }}</td>
            <td class="px-6 py-4 text-sm text-right">
              <button v-if="log.data" @click="showDetails(log)" class="text-blue-600 hover:text-blue-800">Подробнее</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            Показано {{ pagination.from }} - {{ pagination.to }} из {{ pagination.total }}
          </div>
          <div class="flex space-x-2">
            <button @click="loadPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded disabled:opacity-50">Назад</button>
            <button @click="loadPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded disabled:opacity-50">Вперед</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="selectedLog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="selectedLog = null">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full m-4 max-h-[80vh] overflow-y-auto" @click.stop>
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Детали изменения</h2>
          <button @click="selectedLog = null" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">✕</button>
        </div>
        <div class="space-y-4">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Дата</p>
            <p class="text-gray-900 dark:text-white">{{ formatDate(selectedLog.created_at) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Пользователь</p>
            <p class="text-gray-900 dark:text-white">{{ selectedLog.admin?.name || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Email</p>
            <p class="text-gray-900 dark:text-white">{{ selectedLog.admin?.email || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">IP адрес</p>
            <p class="text-gray-900 dark:text-white">{{ selectedLog.ip_address }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">User Agent</p>
            <p class="text-gray-900 dark:text-white text-xs">{{ selectedLog.user_agent }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Описание</p>
            <p class="text-gray-900 dark:text-white">{{ selectedLog.description }}</p>
          </div>
          <div v-if="selectedLog.data">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Данные</p>
            <pre class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 p-4 rounded text-xs overflow-x-auto whitespace-pre-wrap break-words">{{ JSON.stringify(selectedLog.data, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const logs = ref([]);
const loading = ref(false);
const selectedLog = ref(null);
const filters = ref({
  entity_type: '',
  action: '',
  date_from: '',
  date_to: '',
  search: ''
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 50,
  total: 0,
  from: 0,
  to: 0
});

let searchTimeout = null;

const loadLogs = async (page = 1) => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.append('page', page);
    if (filters.value.entity_type) params.append('entity_type', filters.value.entity_type);
    if (filters.value.action) params.append('action', filters.value.action);
    if (filters.value.date_from) params.append('date_from', filters.value.date_from);
    if (filters.value.date_to) params.append('date_to', filters.value.date_to);
    if (filters.value.search) params.append('search', filters.value.search);

    const response = await fetch(`/admin/api/logs?${params.toString()}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      logs.value = data.data || [];
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        per_page: data.per_page,
        total: data.total,
        from: data.from,
        to: data.to
      };
    }
  } catch (err) {
    console.error('Error loading logs:', err);
  } finally {
    loading.value = false;
  }
};

const loadPage = (page) => {
  loadLogs(page);
};

const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadLogs();
  }, 500);
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('ru-RU', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getActionLabel = (action) => {
  const labels = {
    created: 'Создано',
    updated: 'Обновлено',
    deleted: 'Удалено',
    installed: 'Установлено',
    uninstalled: 'Удалено',
    changed: 'Изменено'
  };
  return labels[action] || action;
};

const getActionClass = (action) => {
  const classes = {
    created: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    updated: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    deleted: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    installed: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    uninstalled: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    changed: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  };
  return classes[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const getEntityTypeLabel = (type) => {
  const labels = {
    product: 'Товар',
    catalog: 'Категория',
    order: 'Заказ',
    module: 'Модуль',
    setting: 'Настройка',
    administrator: 'Администратор',
    promocode: 'Промокод'
  };
  return labels[type] || type;
};

const showDetails = (log) => {
  selectedLog.value = log;
};

onMounted(() => {
  loadLogs();
});
</script>
