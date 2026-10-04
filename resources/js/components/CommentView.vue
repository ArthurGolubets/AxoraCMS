<template>
  <div v-if="comment">
    <div class="flex items-center mb-6">
      <button
        @click="$router.push('/comments')"
        class="mr-4 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
      >
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Просмотр комментария</h1>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
      <div class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Имя</label>
          <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
            {{ comment.name }}
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
              {{ comment.email || 'Не указан' }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Телефон</label>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
              {{ comment.phone || 'Не указан' }}
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Рейтинг</label>
          <div class="flex items-center text-2xl">
            <span v-for="i in 5" :key="i" class="text-yellow-400">
              {{ i <= comment.rating ? '★' : '☆' }}
            </span>
            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">({{ comment.rating }} из 5)</span>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Комментарий</label>
          <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white whitespace-pre-wrap">
            {{ comment.comment }}
          </div>
        </div>

        <div v-if="comment.product_id">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Товар</label>
          <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
            {{ comment.product?.name || `ID: ${comment.product_id}` }}
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Статус модерации</label>
          <div>
            <span
              class="px-3 py-2 rounded-lg text-sm font-medium inline-block"
              :class="comment.is_moderated ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'"
            >
              {{ comment.is_moderated ? 'Одобрен' : 'На модерации' }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Дата создания</label>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
              {{ formatDate(comment.created_at) }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Дата обновления</label>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white">
              {{ formatDate(comment.updated_at) }}
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 flex space-x-3">
        <button
          @click="toggleModeration"
          :style="buttonStyle"
          class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90"
        >
          {{ comment.is_moderated ? 'Снять с модерации' : 'Одобрить' }}
        </button>
        <button
          v-if="canEdit"
          @click="$router.push(`/comments/${comment.id}/edit`)"
          :style="buttonStyle"
          class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90"
        >
          Редактировать
        </button>
        <button
          @click="deleteComment"
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

const comment = ref(null);

const canEdit = computed(() => {
  const userData = JSON.parse(localStorage.getItem('holart-user') || '{}');
  return userData.role === 'super_admin' || userData.role === 'administrator';
});

const formatDate = (date) => {
  return new Date(date).toLocaleString('ru-RU');
};

const loadComment = async () => {
  try {
    const response = await fetch(`/admin/api/comments/${route.params.id}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      comment.value = await response.json();
    } else {
      error('Ошибка загрузки данных');
      router.push('/comments');
    }
  } catch (err) {
    error('Ошибка загрузки данных');
    router.push('/comments');
  }
};

const toggleModeration = async () => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/comments/${route.params.id}/toggle-moderation`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      success('Статус модерации изменен');
      loadComment();
      refreshBadges();
    } else {
      error('Ошибка при изменении статуса');
    }
  } catch (err) {
    error('Ошибка при изменении статуса');
  }
};

const deleteComment = async () => {
  const confirmed = await confirm('Вы уверены, что хотите удалить этот комментарий?');
  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/comments/${route.params.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      success('Комментарий успешно удален');
      refreshBadges();
      router.push('/comments');
    } else {
      error('Ошибка при удалении');
    }
  } catch (err) {
    error('Ошибка при удалении');
  }
};

onMounted(() => {
  loadComment();
});
</script>
