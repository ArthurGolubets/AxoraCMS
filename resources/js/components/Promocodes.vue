<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Промокоды</h1>
      <button @click="showCreateModal = true" class="px-4 py-2 text-white rounded-md transition-opacity hover:opacity-90" :style="buttonStyle">
        Создать промокод
      </button>
    </div>

    <!-- Promocodes Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Код</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Тип</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Значение</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Использовано</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Макс использований</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Период действия</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-if="loading">
            <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Загрузка...</td>
          </tr>
          <tr v-else-if="promocodes.length === 0">
            <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Нет промокодов</td>
          </tr>
          <tr v-else v-for="promo in promocodes" :key="promo.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ promo.name }}</td>
            <td class="px-6 py-4 text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ promo.code }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ getTypeLabel(promo.type) }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ formatValue(promo.type, promo.value) }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ promo.current_usage }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ promo.max_usage > 0 ? promo.max_usage : 'Без ограничений' }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
              <div v-if="promo.date_active_from || promo.date_active_to">
                <div v-if="promo.date_active_from">С: {{ formatDate(promo.date_active_from) }}</div>
                <div v-if="promo.date_active_to">До: {{ formatDate(promo.date_active_to) }}</div>
              </div>
              <span v-else class="text-gray-400">Без ограничений</span>
            </td>
            <td class="px-6 py-4 text-sm text-right space-x-2">
              <button @click="editPromocode(promo)" class="text-blue-600 hover:text-blue-800">Редактировать</button>
              <button @click="deletePromocode(promo.id)" class="text-red-600 hover:text-red-800">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          {{ showCreateModal ? 'Создать промокод' : 'Редактировать промокод' }}
        </h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название промокода</label>
            <input v-model="form.name" type="text" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="Скидка на летний сезон">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Код промокода</label>
            <input v-model="form.code" type="text" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="PROMO2024">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Тип</label>
              <select v-model="form.type" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md">
                <option value="fiat">Фиксированная сумма</option>
                <option value="percent">Процент</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ form.type === 'fiat' ? 'Сумма (₽)' : 'Процент (%)' }}
              </label>
              <input v-model="form.value" type="number" step="0.01" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md">
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Максимальное количество использований (0 = без ограничений)</label>
            <input v-model="form.max_usage" type="number" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Дата начала (опционально)</label>
              <input v-model="form.date_active_from" type="date" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Дата окончания (опционально)</label>
              <input v-model="form.date_active_to" type="date" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white">
            </div>
          </div>
        </div>
        <div class="flex justify-end space-x-3 mt-6">
          <button @click="closeModal" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Отмена</button>
          <button @click="savePromocode" class="px-4 py-2 text-white rounded-md transition-opacity hover:opacity-90" :style="buttonStyle">Сохранить</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';

const { success, error, confirm } = useModal();
const { buttonStyle } = useTheme();

const promocodes = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingId = ref(null);

const form = ref({
  name: '',
  code: '',
  type: 'fiat',
  value: 0,
  max_usage: 0,
  date_active_from: '',
  date_active_to: ''
});

const loadPromocodes = async () => {
  loading.value = true;
  try {
    const response = await fetch('/admin/api/promocodes', {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      promocodes.value = data.data || [];
    }
  } catch (err) {
    error('Ошибка при загрузке промокодов');
  } finally {
    loading.value = false;
  }
};

const formatDateForInput = (dateString) => {
  if (!dateString) return '';

  // Handle datetime string (YYYY-MM-DD HH:MM:SS or YYYY-MM-DDTHH:MM:SS.000000Z)
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '';

  // Format to YYYY-MM-DD for input type="date"
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
};

const editPromocode = (promo) => {
  editingId.value = promo.id;
  form.value = {
    name: promo.name,
    code: promo.code,
    type: promo.type,
    value: promo.value,
    max_usage: promo.max_usage,
    date_active_from: formatDateForInput(promo.date_active_from),
    date_active_to: formatDateForInput(promo.date_active_to)
  };
  showEditModal.value = true;
};

const savePromocode = async () => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = showEditModal.value
      ? `/admin/api/promocodes/${editingId.value}`
      : '/admin/api/promocodes';
    const method = showEditModal.value ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: {
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    if (response.ok) {
      success(showEditModal.value ? 'Промокод обновлен' : 'Промокод создан');
      closeModal();
      loadPromocodes();
    } else {
      error('Ошибка при сохранении промокода');
    }
  } catch (err) {
    error('Ошибка при сохранении промокода');
  }
};

const deletePromocode = async (id) => {
  const confirmed = await confirm('Вы уверены, что хотите удалить этот промокод?');
  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/promocodes/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      success('Промокод успешно удален');
      loadPromocodes();
    } else {
      error('Ошибка при удалении промокода');
    }
  } catch (err) {
    error('Ошибка при удалении промокода');
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingId.value = null;
  form.value = {
    name: '',
    code: '',
    type: 'fiat',
    value: 0,
    max_usage: 0,
    date_active_from: '',
    date_active_to: ''
  };
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' });
};

const getTypeLabel = (type) => {
  return type === 'fiat' ? 'Фиксированная сумма' : 'Процент';
};

const formatValue = (type, value) => {
  return type === 'fiat' ? `${value} ₽` : `${value}%`;
};

onMounted(() => {
  loadPromocodes();
});
</script>
