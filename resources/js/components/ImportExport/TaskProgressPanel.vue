<template>
  <SidePanel :title="task?.title || 'Задача'" width-class="max-w-xl" @close="$emit('close')">
    <div v-if="!task" class="py-12 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <div v-else class="space-y-6">
      <div class="flex items-center justify-between">
        <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="STATUS_CLASSES[task.status]">{{ STATUS_LABELS[task.status] }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400">Создана {{ formatDateTime(task.created_at) }}</span>
      </div>

      <!-- Progress -->
      <div>
        <div class="flex items-end justify-between mb-2">
          <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ task.progress }}%</span>
          <span class="text-sm text-gray-500 dark:text-gray-400">{{ task.processed }} из {{ task.total }} {{ unitLabel }}</span>
        </div>
        <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-500"
            :class="{ 'animate-pulse': task.is_active }"
            :style="{ width: `${Math.max(task.progress, task.is_active ? 2 : 0)}%`, backgroundColor: barColor }"
          ></div>
        </div>
        <p v-if="stalled" class="mt-3 text-xs text-amber-700 dark:text-amber-400">
          Задача давно в очереди. Проверьте, что запущен обработчик очереди: <code class="px-1 rounded bg-amber-50 dark:bg-amber-900/30">php artisan queue:work</code>
        </p>
      </div>

      <!-- Stats -->
      <div v-if="task.type === 'import'" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div v-for="item in importStats" :key="item.key" class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50">
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.label }}</p>
          <p class="text-lg font-semibold" :class="item.class">{{ task.stats?.[item.key] ?? 0 }}</p>
        </div>
      </div>
      <div v-else-if="task.status === 'completed'" class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-sm text-green-800 dark:text-green-300">
        Файл готов: строк — {{ task.stats?.rows ?? 0 }}.
      </div>

      <div v-if="task.message" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-sm text-red-700 dark:text-red-300 whitespace-pre-line">{{ task.message }}</div>

      <!-- Errors -->
      <div v-if="task.errors?.length">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Ошибки ({{ task.stats?.failed ?? task.errors.length }}<template v-if="(task.stats?.failed ?? 0) > task.errors.length">, показаны первые {{ task.errors.length }}</template>)
        </p>
        <ul class="max-h-64 overflow-y-auto text-xs space-y-1 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300">
          <li v-for="(message, index) in task.errors" :key="index">{{ message }}</li>
        </ul>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <button v-if="task?.is_active" type="button" @click="cancel" class="px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-lg">
          Остановить
        </button>
        <a v-if="task?.download_available" :href="downloadUrl(task)" :style="buttonStyle" class="px-4 py-2 text-sm text-white rounded-lg transition-opacity hover:opacity-90">
          Скачать файл
        </a>
        <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
          {{ task?.is_active ? 'Свернуть' : 'Закрыть' }}
        </button>
      </div>
    </template>
  </SidePanel>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import SidePanel from '../SidePanel.vue';
import { useTheme } from '../../composables/useTheme';
import { STATUS_LABELS, STATUS_CLASSES, csrfHeaders, downloadUrl, formatDateTime } from './importExport';

/**
 * Live progress of an import / export task (polls while it runs).
 * Closing the panel does not stop the task.
 */
const props = defineProps({
  taskId: { type: Number, required: true },
});

const emit = defineEmits(['close', 'updated']);

const { buttonStyle, themeColor } = useTheme();

const task = ref(null);
let timer = null;

const importStats = [
  { key: 'created', label: 'Создано', class: 'text-green-600 dark:text-green-400' },
  { key: 'updated', label: 'Обновлено', class: 'text-blue-600 dark:text-blue-400' },
  { key: 'skipped', label: 'Пропущено', class: 'text-gray-700 dark:text-gray-300' },
  { key: 'failed', label: 'Ошибок', class: 'text-red-600 dark:text-red-400' },
];

const unitLabel = computed(() => (task.value?.type === 'import' ? 'строк' : 'записей'));

const barColor = computed(() => ({
  completed: '#16a34a',
  failed: '#dc2626',
  cancelled: '#d97706',
}[task.value?.status] || themeColor.value));

const stalled = computed(() => task.value?.status === 'pending'
  && Date.now() - new Date(task.value.created_at).getTime() > 30000);

const load = async () => {
  try {
    const response = await fetch(`/admin/api/import-export/tasks/${props.taskId}`, { headers: csrfHeaders(false) });
    if (!response.ok) return;
    task.value = await response.json();
    emit('updated', task.value);
    if (!task.value.is_active) stop();
  } catch (e) {
    // keep polling
  }
};

const stop = () => {
  clearInterval(timer);
  timer = null;
};

const cancel = async () => {
  const response = await fetch(`/admin/api/import-export/tasks/${props.taskId}/cancel`, { method: 'POST', headers: csrfHeaders() });
  if (response.ok) {
    task.value = await response.json();
    emit('updated', task.value);
    stop();
  }
};

onMounted(() => {
  load();
  timer = setInterval(load, 1500);
});

onBeforeUnmount(stop);
</script>
