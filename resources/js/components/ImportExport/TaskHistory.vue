<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">История задач</h3>
      <button type="button" @click="load" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Обновить</button>
    </div>

    <div v-if="loading && !tasks.length" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Загрузка...</div>
    <div v-else-if="!tasks.length" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Задач пока не было</div>

    <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
      <li v-for="task in tasks" :key="task.id" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer" @click="$emit('open', task)">
        <span class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center" :class="task.type === 'import' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="task.type === 'import'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
        </span>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ task.title }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatDateTime(task.created_at) }}</div>
        </div>
        <div v-if="task.is_active" class="w-40">
          <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
            <div class="h-full rounded-full transition-all" :style="{ width: `${Math.max(task.progress, 2)}%`, backgroundColor: themeColor }"></div>
          </div>
          <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400 text-right">{{ task.progress }}%</div>
        </div>
        <span class="px-2 py-0.5 text-xs rounded-full" :class="STATUS_CLASSES[task.status]">{{ STATUS_LABELS[task.status] }}</span>
        <div class="flex items-center gap-1" @click.stop>
          <a v-if="task.download_available" :href="downloadUrl(task)" title="Скачать" class="p-1.5 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          </a>
          <button v-if="!task.is_active" type="button" @click="remove(task)" title="Удалить" class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </button>
        </div>
      </li>
    </ul>

    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import ConfirmModal from '../ConfirmModal.vue';
import { useTheme } from '../../composables/useTheme';
import { STATUS_LABELS, STATUS_CLASSES, csrfHeaders, downloadUrl, formatDateTime } from './importExport';

/**
 * Recent tasks of a scope ("catalog" | "entities"); refreshes itself while
 * something is running. Exposes reload() and the active tasks.
 */
const props = defineProps({
  scope: { type: String, required: true },
});

const emit = defineEmits(['open', 'loaded']);

const { themeColor } = useTheme();

const tasks = ref([]);
const loading = ref(false);
const confirmModal = ref(null);
let timer = null;

const activeTasks = computed(() => tasks.value.filter((t) => t.is_active));

const load = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/admin/api/import-export/tasks?scope=${props.scope}`, { headers: csrfHeaders(false) });
    if (response.ok) {
      tasks.value = await response.json();
      emit('loaded', tasks.value);
    }
  } finally {
    loading.value = false;
  }
  schedule();
};

const schedule = () => {
  clearTimeout(timer);
  if (activeTasks.value.length) timer = setTimeout(load, 3000);
};

const remove = async (task) => {
  const confirmed = await confirmModal.value.open({
    title: 'Удалить задачу?',
    message: `«${task.title}» и её файлы будут удалены из истории.`,
    confirmText: 'Удалить',
    dangerMode: true,
  });
  if (!confirmed) return;

  const response = await fetch(`/admin/api/import-export/tasks/${task.id}`, { method: 'DELETE', headers: csrfHeaders() });
  if (response.ok) load();
};

defineExpose({ reload: load, activeTasks });

onMounted(load);
onBeforeUnmount(() => clearTimeout(timer));
</script>
