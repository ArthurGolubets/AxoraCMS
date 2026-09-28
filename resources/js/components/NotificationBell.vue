<template>
  <div class="relative" ref="root">
    <button
      @click="toggle"
      class="relative p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition"
      title="Уведомления"
    >
      <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <span
        v-if="unreadCount > 0"
        class="absolute top-0.5 right-0.5 flex items-center justify-center min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold leading-none"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <div
      v-if="open"
      class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 overflow-hidden"
    >
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Уведомления</h3>
        <button
          v-if="unreadCount > 0"
          @click="markAllRead"
          class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline"
        >
          Отметить все прочитанными
        </button>
      </div>

      <div class="max-h-96 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
        <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          Нет уведомлений
        </div>

        <button
          v-for="notification in notifications"
          :key="notification.id"
          type="button"
          @click="selectNotification(notification)"
          class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors flex gap-2"
          :class="{ 'bg-blue-50/60 dark:bg-blue-900/10': !notification.is_read }"
        >
          <span
            class="mt-1.5 w-2 h-2 rounded-full shrink-0"
            :class="notification.is_read ? 'bg-transparent' : 'bg-blue-500'"
          ></span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-gray-900 dark:text-white truncate">{{ notification.title }}</span>
            <span v-if="notification.message" class="block text-xs text-gray-600 dark:text-gray-400 truncate">{{ notification.message }}</span>
            <span class="block text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ timeAgo(notification.created_at) }}</span>
          </span>
        </button>
      </div>

      <div v-if="notifications.length > 0" class="border-t border-gray-200 dark:border-gray-700">
        <button
          type="button"
          @click="clearAll"
          class="w-full px-4 py-2.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
        >
          Очистить уведомления
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAdminNotifications } from '../composables/useAdminNotifications';
import { useModal } from '../composables/useModal';

const router = useRouter();
const { confirm } = useModal();
const {
  notifications,
  unreadCount,
  markRead,
  markAllRead: markAllReadShared,
  clearAll: clearAllShared,
} = useAdminNotifications();

const root = ref(null);
const open = ref(false);

const toggle = () => {
  open.value = !open.value;
};

const close = (event) => {
  if (root.value && !root.value.contains(event.target)) {
    open.value = false;
  }
};

onMounted(() => document.addEventListener('mousedown', close));
onUnmounted(() => document.removeEventListener('mousedown', close));

const selectNotification = async (notification) => {
  if (!notification.is_read) {
    await markRead(notification.id);
  }
  open.value = false;
  if (notification.link) {
    router.push(notification.link);
  }
};

const markAllRead = async () => {
  await markAllReadShared();
};

const clearAll = async () => {
  const confirmed = await confirm(
    'Очистить уведомления?',
    'Все уведомления будут удалены — в том числе для других администраторов.'
  );
  if (!confirmed) return;

  await clearAllShared();
};

const timeAgo = (dateString) => {
  const date = new Date(dateString);
  const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

  if (seconds < 60) return 'только что';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes} мин. назад`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} ч. назад`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days} дн. назад`;

  return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
};
</script>
