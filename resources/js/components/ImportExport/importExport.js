/**
 * Shared helpers of the "Импорт/Экспорт" module UI.
 */
export const STATUS_LABELS = {
  pending: 'В очереди',
  running: 'Выполняется',
  completed: 'Завершено',
  failed: 'Ошибка',
  cancelled: 'Остановлено',
  draft: 'Черновик',
};

export const STATUS_CLASSES = {
  pending: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  running: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
  failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
  cancelled: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
  draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
};

export const csrfHeaders = (json = true) => ({
  ...(json ? { 'Content-Type': 'application/json' } : {}),
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
  Accept: 'application/json',
});

export const downloadUrl = (task) => `/admin/api/import-export/tasks/${task.id}/download`;

export const formatDateTime = (value) => (value ? new Date(value).toLocaleString('ru-RU', {
  day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
}) : '—');

/**
 * First validation message of a failed JSON response.
 */
export const responseError = async (response, fallback) => {
  const data = await response.json().catch(() => ({}));
  const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
  return first || data.message || fallback;
};
