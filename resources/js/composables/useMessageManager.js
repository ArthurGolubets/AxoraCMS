const BASE = '/admin/api/message-manager';

export const CHANNELS = {
  telegram: { label: 'Telegram', badge: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' },
  email: { label: 'Почта', badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' },
};

export const OPERATORS = [
  { value: 'eq', label: 'равно', needsValue: true },
  { value: 'neq', label: 'не равно', needsValue: true },
  { value: 'contains', label: 'содержит', needsValue: true },
  { value: 'not_contains', label: 'не содержит', needsValue: true },
  { value: 'gt', label: 'больше', needsValue: true },
  { value: 'lt', label: 'меньше', needsValue: true },
  { value: 'empty', label: 'пусто', needsValue: false },
  { value: 'not_empty', label: 'не пусто', needsValue: false },
];

export const DELAY_UNITS = [
  { value: 1, label: 'сек' },
  { value: 60, label: 'мин' },
  { value: 3600, label: 'ч' },
  { value: 86400, label: 'дн' },
];

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

/**
 * JSON request to the module API. Rejects with an Error whose message is the first
 * validation message (or the server message) and `errors` holds all field errors.
 */
export async function api(method, path, body) {
  const response = await fetch(`${BASE}${path}`, {
    method,
    headers: {
      'Accept': 'application/json',
      ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });
  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
    const error = new Error(firstError || data.message || 'Не удалось выполнить запрос');
    error.errors = data.errors || {};
    throw error;
  }

  return data;
}

/** seconds -> { value, unit } using the largest unit that divides evenly. */
export function splitDelay(seconds) {
  const total = Number(seconds) || 0;
  const unit = [...DELAY_UNITS].reverse().find(u => total > 0 && total % u.value === 0) || DELAY_UNITS[1];
  return { value: Math.round(total / unit.value), unit: unit.value };
}
