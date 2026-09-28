import { ref } from 'vue';

/**
 * Shared, polled state for the header bell (notifications) and the "new
 * orders" badge on the sidebar's "Список заказов" link. A single interval
 * feeds both — started once from App.vue's onMounted.
 */

const POLL_INTERVAL_MS = 30000;

const notifications = ref([]);
const unreadCount = ref(0);
const newOrdersCount = ref(0);
const loaded = ref(false);

let inflight = null;
let pollTimer = null;

const csrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const jsonHeaders = () => ({
  'X-CSRF-TOKEN': csrfToken(),
  'Content-Type': 'application/json',
  Accept: 'application/json',
});

const fetchNotifications = async () => {
  if (inflight) return inflight;

  inflight = fetch('/admin/api/notifications', { headers: jsonHeaders() })
    .then((response) => (response.ok ? response.json() : null))
    .then((data) => {
      if (data) {
        notifications.value = data.notifications || [];
        unreadCount.value = data.unread_count || 0;
        newOrdersCount.value = data.new_orders_count || 0;
        loaded.value = true;
      }
      return data;
    })
    .catch((error) => {
      console.error('Failed to load notifications:', error);
      return null;
    })
    .finally(() => {
      inflight = null;
    });

  return inflight;
};

/** Idempotent — safe to call from multiple mounted components. */
const startPolling = () => {
  if (!loaded.value) fetchNotifications();
  if (!pollTimer) {
    pollTimer = setInterval(fetchNotifications, POLL_INTERVAL_MS);
  }
};

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
};

const markRead = async (id) => {
  const notification = notifications.value.find((n) => n.id === id);
  if (notification && !notification.is_read) {
    notification.is_read = true;
    unreadCount.value = Math.max(0, unreadCount.value - 1);
  }

  try {
    await fetch(`/admin/api/notifications/${id}/read`, {
      method: 'POST',
      headers: jsonHeaders(),
    });
  } catch (error) {
    console.error('Failed to mark notification as read:', error);
  }
};

const markAllRead = async () => {
  notifications.value.forEach((n) => { n.is_read = true; });
  unreadCount.value = 0;

  try {
    await fetch('/admin/api/notifications/read-all', {
      method: 'POST',
      headers: jsonHeaders(),
    });
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error);
  }
};

/** Deletes every notification (shared across all admins). */
const clearAll = async () => {
  const previousNotifications = notifications.value;
  const previousUnreadCount = unreadCount.value;
  notifications.value = [];
  unreadCount.value = 0;

  try {
    await fetch('/admin/api/notifications', {
      method: 'DELETE',
      headers: jsonHeaders(),
    });
  } catch (error) {
    console.error('Failed to clear notifications:', error);
    notifications.value = previousNotifications;
    unreadCount.value = previousUnreadCount;
  }
};

export function useAdminNotifications() {
  return {
    notifications,
    unreadCount,
    newOrdersCount,
    fetchNotifications,
    startPolling,
    stopPolling,
    markRead,
    markAllRead,
    clearAll,
  };
}
