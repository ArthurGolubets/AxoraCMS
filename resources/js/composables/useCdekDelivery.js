import { ref, onMounted } from 'vue';

const BASE = '/admin/api/integrations/cdek';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

/**
 * JSON request to the module API. Rejects with an Error whose message is the first
 * validation message (or the server message).
 */
export async function cdekApi(method, path, body) {
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
    throw new Error(firstError || data.message || 'Не удалось выполнить запрос');
  }

  return data;
}

/**
 * Whether the module is installed and whether delivery is switched on in the order settings.
 */
export function useCdekDelivery() {
  const cdekAvailable = ref(false);
  const cdekEnabled = ref(false);

  onMounted(async () => {
    try {
      const response = await fetch('/admin/api/orders-settings', { headers: { 'Accept': 'application/json' } });
      const settings = response.ok ? await response.json() : {};
      cdekAvailable.value = !!settings.cdek_available;
      cdekEnabled.value = cdekAvailable.value && !!settings.delivery_cdek_enabled;
    } catch {
      // Without the settings the order pages simply do not offer CDEK.
    }
  });

  return { cdekAvailable, cdekEnabled };
}
