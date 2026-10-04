<template>
  <div v-if="request">
    <div class="flex items-center mb-6">
      <button
        @click="$router.push('/user-requests')"
        class="mr-4 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
      >
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Просмотр обращения</h1>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
      <div class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Имя</label>
          <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
            {{ request.name }}
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
              {{ request.email }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Телефон</label>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
              {{ request.phone }}
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Комментарий</label>
          <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white whitespace-pre-wrap">
            {{ request.comment }}
          </div>
        </div>

        <div v-if="request.user_id">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ID пользователя</label>
          <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
            {{ request.user_id }}
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Дата создания</label>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
              {{ formatDate(request.created_at) }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Дата обновления</label>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
              {{ formatDate(request.updated_at) }}
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 flex space-x-3">
        <button
          v-if="canEdit"
          @click="$router.push(`/user-requests/${request.id}/edit`)"
          :style="buttonStyle"
          class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90"
        >
          Редактировать
        </button>
        <button
          @click="deleteRequest"
          class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"
        >
          Удалить
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import { useAdminNotifications } from '../composables/useAdminNotifications';

const { fetchNotifications: refreshBadges } = useAdminNotifications();

const router = useRouter();
const route = useRoute();
const { success, error, confirm } = useModal();
const { buttonStyle } = useTheme();

const request = ref(null);

const canEdit = computed(() => {
  const userData = JSON.parse(localStorage.getItem('holart-user') || '{}');
  return userData.role === 'super_admin' || userData.role === 'administrator';
});

const formatDate = (date) => {
  return new Date(date).toLocaleString('ru-RU');
};

const loadRequest = async () => {
  try {
    const response = await fetch(`/admin/api/user-requests/${route.params.id}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      request.value = await response.json();
      refreshBadges();
    } else {
      error('Ошибка загрузки данных');
      router.push('/user-requests');
    }
  } catch (err) {
    error('Ошибка загрузки данных');
    router.push('/user-requests');
  }
};

const deleteRequest = async () => {
  const confirmed = await confirm('Вы уверены, что хотите удалить это обращение?');
  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/user-requests/${route.params.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      success('Обращение успешно удалено');
      router.push('/user-requests');
    } else {
      error('Ошибка при удалении');
    }
  } catch (err) {
    error('Ошибка при удалении');
  }
};

onMounted(() => {
  loadRequest();
});
</script>
