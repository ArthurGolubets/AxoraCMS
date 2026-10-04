<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Обращения пользователей</h1>
      <button
        v-if="canCreate"
        @click="$router.push('/user-requests/create')"
        :style="buttonStyle"
        class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90"
      >
        Добавить обращение
      </button>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <input
          v-model="search"
          @input="loadRequests"
          type="text"
          placeholder="Поиск..."
          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
        />
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Имя</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Телефон</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Комментарий</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Дата</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Действия</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="request in requests" :key="request.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ request.name }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ request.email }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ request.phone }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="max-w-xs truncate">{{ request.comment }}</div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(request.created_at) }}</td>
              <td class="px-6 py-4 text-right text-sm space-x-2">
                <button @click="viewRequest(request.id)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Просмотр</button>
                <button @click="deleteRequest(request.id)" class="text-red-600 hover:text-red-800 dark:text-red-400">Удалить</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.total > pagination.per_page" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            Показано {{ pagination.from }} - {{ pagination.to }} из {{ pagination.total }}
          </div>
          <div class="flex space-x-2">
            <button
              v-for="page in paginationPages"
              :key="page"
              @click="changePage(page)"
              class="px-3 py-1 rounded"
              :class="page === pagination.current_page ? `${bgClass} text-white` : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            >
              {{ page }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import { useAdminNotifications } from '../composables/useAdminNotifications';

const { fetchNotifications: refreshBadges } = useAdminNotifications();

const router = useRouter();
const { success, error, confirm } = useModal();
const { buttonStyle, bgClass } = useTheme();

const requests = ref([]);
const search = ref('');
const pagination = ref({
  current_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
});

const canCreate = computed(() => {
  const userData = JSON.parse(localStorage.getItem('holart-user') || '{}');
  return userData.role === 'super_admin' || userData.role === 'administrator';
});

const paginationPages = computed(() => {
  const pages = [];
  for (let i = 1; i <= Math.ceil(pagination.value.total / pagination.value.per_page); i++) {
    pages.push(i);
  }
  return pages;
});

const formatDate = (date) => {
  return new Date(date).toLocaleString('ru-RU');
};

const loadRequests = async () => {
  try {
    const params = new URLSearchParams({
      search: search.value,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    });

    const response = await fetch(`/admin/api/user-requests?${params}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      requests.value = data.data;
      pagination.value = {
        current_page: data.current_page,
        per_page: data.per_page,
        total: data.total,
        from: data.from,
        to: data.to
      };
    }
  } catch (err) {
    error('Ошибка загрузки данных');
  }
};

const changePage = (page) => {
  pagination.value.current_page = page;
  loadRequests();
};

const viewRequest = (id) => {
  router.push(`/user-requests/${id}`);
};

const deleteRequest = async (id) => {
  const confirmed = await confirm('Вы уверены, что хотите удалить это обращение?');
  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/user-requests/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      success('Обращение успешно удалено');
      loadRequests();
      refreshBadges();
    } else {
      error('Ошибка при удалении');
    }
  } catch (err) {
    error('Ошибка при удалении');
  }
};

onMounted(() => {
  loadRequests();
});
</script>
