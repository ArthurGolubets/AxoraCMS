<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Настройки заказов</h1>
      <button @click="saveSettings" :disabled="saving" :style="buttonStyle" class="px-4 py-2 text-white rounded-md transition-opacity hover:opacity-90 disabled:opacity-50">
        {{ saving ? 'Сохранение...' : 'Сохранить' }}
      </button>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="text-gray-500 dark:text-gray-400">Загрузка настроек...</div>
    </div>

    <div v-else class="space-y-6">
      <!-- General Settings -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Общие настройки</h2>

        <div class="space-y-4">
          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Включить уведомления о новых заказах</span>
              <button @click="settings.order_notifications_enabled = !settings.order_notifications_enabled" type="button" :class="settings.order_notifications_enabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.order_notifications_enabled ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email для уведомлений</label>
            <input v-model="settings.notification_email" type="email" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="orders@example.com">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Email, на который будут приходить уведомления о новых заказах</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Минимальная сумма заказа (₽)</label>
            <input v-model="settings.min_order_amount" type="number" step="0.01" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="0">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Минимальная сумма для оформления заказа (0 = без ограничений)</p>
          </div>
        </div>
      </div>

      <!-- Delivery Settings -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Настройки доставки</h2>

        <div class="space-y-4">
          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Включить самовывоз</span>
              <button @click="settings.delivery_pickup_enabled = !settings.delivery_pickup_enabled" type="button" :class="settings.delivery_pickup_enabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.delivery_pickup_enabled ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>

          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Включить доставку курьером</span>
              <button @click="settings.delivery_courier_enabled = !settings.delivery_courier_enabled" type="button" :class="settings.delivery_courier_enabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.delivery_courier_enabled ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>

          <div v-if="settings.delivery_courier_enabled">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Стоимость доставки курьером (₽)</label>
            <input v-model="settings.delivery_courier_price" type="number" step="0.01" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="300">
          </div>

          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Включить доставку почтой</span>
              <button @click="settings.delivery_post_enabled = !settings.delivery_post_enabled" type="button" :class="settings.delivery_post_enabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.delivery_post_enabled ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>

          <div v-if="settings.delivery_post_enabled">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Стоимость доставки почтой (₽)</label>
            <input v-model="settings.delivery_post_price" type="number" step="0.01" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="500">
          </div>

          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Районы доставки фиксированные</span>
              <button @click="settings.delivery_zones_enabled = !settings.delivery_zones_enabled" type="button" :class="settings.delivery_zones_enabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.delivery_zones_enabled ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>

          <div v-if="settings.delivery_zones_enabled" class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
            <div class="flex justify-between items-center mb-3">
              <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Районы доставки</h3>
              <button @click="addDeliveryZone" type="button" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                + Добавить район
              </button>
            </div>
            <div v-if="deliveryZones.length === 0" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
              Районы не добавлены
            </div>
            <div v-else class="space-y-3">
              <div v-for="(zone, index) in deliveryZones" :key="zone.id" class="bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-3 gap-3">
                  <div>
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Название района</label>
                    <input v-model="zone.name" @input="updateZoneCode(zone)" type="text" class="w-full px-2 py-1 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded" placeholder="Центральный">
                  </div>
                  <div>
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Код</label>
                    <input v-model="zone.code" type="text" class="w-full px-2 py-1 text-sm bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-600 rounded" readonly>
                  </div>
                  <div>
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Цена (₽)</label>
                    <div class="flex gap-2">
                      <input v-model="zone.price" type="number" step="0.01" class="w-full px-2 py-1 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded" placeholder="0">
                      <button @click="removeDeliveryZone(index)" type="button" class="px-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                        ×
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Бесплатная доставка от (₽)</label>
            <input v-model="settings.free_delivery_from" type="number" step="0.01" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md" placeholder="3000">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Сумма заказа, от которой доставка становится бесплатной (0 = нет бесплатной доставки)</p>
          </div>
        </div>
      </div>

      <!-- Payment Settings -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Настройки оплаты</h2>

        <div class="space-y-4">
          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Включить онлайн оплату</span>
              <button @click="settings.payment_online_enabled = !settings.payment_online_enabled" type="button" :class="settings.payment_online_enabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.payment_online_enabled ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>

          <div v-if="settings.payment_online_enabled">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Провайдер онлайн оплаты</label>
            <select v-model="settings.payment_provider" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md">
              <option value="transfer">Оплата переводом</option>
              <option value="yookassa" v-if="availableProviders.includes('yookassa')">ЮKassa</option>
              <option value="sberbank" v-if="availableProviders.includes('sberbank')">СберПэй</option>
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Дополнительные провайдеры появятся после установки соответствующих модулей</p>
          </div>

          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Включить оплату наличными</span>
              <button @click="settings.payment_cash_enabled = !settings.payment_cash_enabled" type="button" :class="settings.payment_cash_enabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.payment_cash_enabled ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>
        </div>
      </div>

      <!-- Order Form Fields -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Поля формы заказа</h2>

        <div class="space-y-4">
          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Телефон обязателен</span>
              <button @click="settings.require_phone = !settings.require_phone" type="button" :class="settings.require_phone ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.require_phone ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>

          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Email обязателен</span>
              <button @click="settings.require_email = !settings.require_email" type="button" :class="settings.require_email ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.require_email ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>

          <div>
            <label class="flex items-center justify-between cursor-pointer">
              <span class="text-sm text-gray-700 dark:text-gray-300">Показывать поле для комментариев</span>
              <button @click="settings.show_comments_field = !settings.show_comments_field" type="button" :class="settings.show_comments_field ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                <span :class="settings.show_comments_field ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
              </button>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';

const { success, error } = useModal();
const { buttonStyle } = useTheme();

const loading = ref(false);
const saving = ref(false);

const settings = ref({
  // General
  order_notifications_enabled: true,
  notification_email: '',
  min_order_amount: 0,

  // Delivery
  delivery_pickup_enabled: true,
  delivery_courier_enabled: true,
  delivery_courier_price: 0,
  delivery_post_enabled: false,
  delivery_post_price: 0,
  delivery_zones_enabled: false,
  free_delivery_from: 0,

  // Payment
  payment_online_enabled: true,
  payment_provider: 'transfer',
  payment_cash_enabled: true,

  // Form fields
  require_phone: true,
  require_email: true,
  show_comments_field: true
});

const deliveryZones = ref([]);
const availableProviders = ref(['transfer']);

let nextZoneId = 1;

const generateSlug = (text) => {
  return text
    .toLowerCase()
    .replace(/[^a-zа-яё0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .trim();
};

const updateZoneCode = (zone) => {
  zone.code = generateSlug(zone.name);
};

const addDeliveryZone = () => {
  deliveryZones.value.push({
    id: nextZoneId++,
    name: '',
    code: '',
    price: 0
  });
};

const removeDeliveryZone = (index) => {
  deliveryZones.value.splice(index, 1);
};

const loadSettings = async () => {
  loading.value = true;
  try {
    const response = await fetch('/admin/api/orders-settings', {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      settings.value = { ...settings.value, ...data };

      if (data.delivery_zones) {
        deliveryZones.value = data.delivery_zones;
        nextZoneId = Math.max(...deliveryZones.value.map(z => z.id), 0) + 1;
      }

      if (data.available_providers) {
        availableProviders.value = data.available_providers;
      }
    }
  } catch (err) {
    error('Ошибка при загрузке настроек');
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/orders-settings', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        settings: settings.value,
        delivery_zones: deliveryZones.value
      })
    });

    if (response.ok) {
      success('Настройки успешно сохранены');
    } else {
      error('Ошибка при сохранении настроек');
    }
  } catch (err) {
    error('Ошибка при сохранении настроек');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadSettings();
});
</script>
