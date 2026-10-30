const BASE = '/admin/api/integrations/bitrix24';

export const ENTITIES = [
  { value: 'lead', label: 'Лид', hint: 'Новое обращение, которое менеджер обработает и при необходимости превратит в сделку' },
  { value: 'deal', label: 'Сделка', hint: 'Сразу продажа в воронке, без этапа лида' },
  { value: 'none', label: 'Не создавать', hint: 'Только контакт и/или компания, без лида и сделки' },
];

export const MODES = [
  { value: 'none', label: 'Не создавать' },
  { value: 'find_or_create', label: 'Найти существующий или создать' },
  { value: 'create', label: 'Всегда создавать новый' },
];

export const STATUSES = {
  success: { label: 'Отправлено', badge: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' },
  partial: { label: 'Частично', badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' },
  failed: { label: 'Ошибка', badge: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' },
};

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
