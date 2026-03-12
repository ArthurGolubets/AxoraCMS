<template>
  <div v-if="order">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Заказ #{{ order.id }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ formatDate(order.created_at) }}</p>
      </div>
      <div class="flex space-x-3">
        <button
          @click="$router.push(`/orders/${order.id}/edit`)"
          :style="buttonStyle"
          class="px-4 py-2 text-white rounded-lg font-medium transition-opacity hover:opacity-90"
        >
          Редактировать
        </button>
        <button
          @click="$router.push('/orders')"
          class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition"
        >
          Назад к списку
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Order Status -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Статус заказа</h3>
          <div class="flex items-center space-x-4">
            <div :class="order.delivery_type === 'pickup' ? 'w-full' : 'flex-1'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Статус оплаты</label>
              <select v-model="order.payment_status" @change="updateOrder" class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                <option value="pending">Ожидает</option>
                <option value="paid">Оплачен</option>
                <option value="failed">Ошибка</option>
                <option value="refunded">Возврат</option>
              </select>
            </div>
            <div v-if="order.delivery_type !== 'pickup'" class="flex-1">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Статус доставки</label>
              <select v-model="order.delivery_status" @change="updateOrder" class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                <option value="pending">Ожидает</option>
                <option value="processing">В обработке</option>
                <option value="shipped">Отправлен</option>
                <option value="delivered">Доставлен</option>
                <option value="cancelled">Отменен</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Товары</h3>
          <div class="space-y-4">
            <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700 last:border-0 last:pb-0">
              <div class="flex-1">
                <h4 class="font-medium text-gray-900 dark:text-white">{{ item.product_name }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Количество: {{ item.amount }}</p>
              </div>
              <div class="text-right">
                <p class="font-semibold text-gray-900 dark:text-white">{{ formatPrice(item.total_price / item.amount) }} ₽</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Итого: {{ formatPrice(item.total_price) }} ₽</p>
              </div>
            </div>
          </div>

          <!-- Total -->
          <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-2">
              <span class="text-gray-600 dark:text-gray-400">Товары:</span>
              <span class="text-gray-900 dark:text-white">{{ formatPrice(subtotal) }} ₽</span>
            </div>
            <div v-if="order.delivery_price > 0" class="flex justify-between items-center mb-2">
              <span class="text-gray-600 dark:text-gray-400">Доставка:</span>
              <span class="text-gray-900 dark:text-white">{{ formatPrice(order.delivery_price) }} ₽</span>
            </div>
            <div v-if="order.promocode" class="flex justify-between items-center mb-2">
              <span class="text-gray-600 dark:text-gray-400">Промокод ({{ order.promocode.code }}):</span>
              <span class="text-green-600 dark:text-green-400">-{{ formatPrice(order.promocode_discount || 0) }} ₽</span>
            </div>
            <div class="flex justify-between items-center text-lg font-bold pt-2 border-t border-gray-200 dark:border-gray-700">
              <span class="text-gray-900 dark:text-white">Всего:</span>
              <span class="text-gray-900 dark:text-white">{{ formatPrice(total) }} ₽</span>
            </div>
          </div>
        </div>

        <!-- Comments -->
        <div v-if="order.comments" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Комментарий к заказу</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ order.comments }}</p>
        </div>

        <!-- Custom Fields -->
        <div v-if="order.addition_data && Object.keys(order.addition_data).length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Дополнительные поля</h3>
          <div class="space-y-3">
            <div v-for="(value, key) in order.addition_data" :key="key" class="flex justify-between items-start pb-3 border-b border-gray-200 dark:border-gray-700 last:border-0 last:pb-0">
              <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ key }}:</span>
              <span class="text-gray-900 dark:text-white text-right">{{ value }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Customer Info -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Информация о клиенте</h3>
          <div class="space-y-3">
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Имя</label>
              <p class="text-gray-900 dark:text-white">{{ order.name }}</p>
            </div>
            <div v-if="order.email">
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
              <p class="text-gray-900 dark:text-white">
                <a :href="`mailto:${order.email}`" class="text-blue-600 hover:underline">{{ order.email }}</a>
              </p>
            </div>
            <div v-if="order.phone">
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Телефон</label>
              <p class="text-gray-900 dark:text-white">
                <a :href="`tel:${order.phone}`" class="text-blue-600 hover:underline">{{ order.phone }}</a>
              </p>
            </div>
          </div>
        </div>

        <!-- Delivery Info -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Доставка</h3>
          <div class="space-y-3">
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Тип доставки</label>
              <p class="text-gray-900 dark:text-white">{{ getDeliveryTypeLabel(order.delivery_type) }}</p>
            </div>
            <div v-if="order.delivery_type !== 'pickup'">
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Статус</label>
              <span class="inline-block px-2 py-1 text-xs rounded-full" :class="getDeliveryStatusClass(order.delivery_status)">
                {{ getDeliveryStatusLabel(order.delivery_status) }}
              </span>
            </div>
            <div v-if="order.delivery_address">
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Адрес</label>
              <p class="text-gray-900 dark:text-white">{{ order.delivery_address }}</p>
            </div>
            <div v-if="order.delivery_price > 0">
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Стоимость</label>
              <p class="text-gray-900 dark:text-white">{{ formatPrice(order.delivery_price) }} ₽</p>
            </div>
          </div>
        </div>

        <!-- Payment Info -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Оплата</h3>
          <div class="space-y-3">
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Способ оплаты</label>
              <p class="text-gray-900 dark:text-white">{{ getPaymentTypeLabel(order.payment_type) }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Статус</label>
              <span class="inline-block px-2 py-1 text-xs rounded-full" :class="getPaymentStatusClass(order.payment_status)">
                {{ getPaymentStatusLabel(order.payment_status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Promocode Info -->
        <div v-if="order.promocode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Промокод</h3>
          <div class="space-y-3">
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Код</label>
              <p class="text-gray-900 dark:text-white font-mono font-semibold">{{ order.promocode.code }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Название</label>
              <p class="text-gray-900 dark:text-white">{{ order.promocode.name }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Скидка</label>
              <p class="text-green-600 dark:text-green-400 font-semibold">
                {{ order.promocode.type === 'fiat' ? formatPrice(order.promocode.value) + ' ₽' : order.promocode.value + '%' }}
              </p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Применена скидка</label>
              <p class="text-green-600 dark:text-green-400 font-semibold">{{ formatPrice(order.promocode_discount || 0) }} ₽</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';

const route = useRoute();
const { success, error } = useModal();
const { buttonStyle } = useTheme();

const order = ref(null);

const subtotal = computed(() => {
  if (!order.value || !order.value.items) return 0;
  return order.value.items.reduce((sum, item) => sum + parseFloat(item.total_price || 0), 0);
});

const total = computed(() => {
  return subtotal.value + parseFloat(order.value?.delivery_price || 0);
});

const loadOrder = async () => {
  try {
    const response = await fetch(`/admin/api/orders/${route.params.id}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      order.value = data;
    } else {
      error('Ошибка при загрузке заказа');
    }
  } catch (err) {
    console.error('Error loading order:', err);
    error('Ошибка при загрузке заказа');
  }
};

const updateOrder = async () => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const payload = {
      name: order.value.name,
      email: order.value.email || '',
      phone: order.value.phone || '',
      payment_status: order.value.payment_status,
      delivery_status: order.value.delivery_status || 'pending',
      delivery_type: order.value.delivery_type,
      delivery_address: order.value.delivery_address || '',
      delivery_price: order.value.delivery_price,
      payment_type: order.value.payment_type,
      comments: order.value.comments || ''
    };


    const response = await fetch(`/admin/api/orders/${order.value.id}`, {
      method: 'PUT',
      headers: {
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    console.log('Update response:', result); // Debug

    if (response.ok) {
      success('Статус заказа обновлен');
      await loadOrder(); // Reload order to get fresh data
    } else {
      console.error('Update failed:', result);
      error('Ошибка при обновлении заказа: ' + JSON.stringify(result.errors || result.message));
    }
  } catch (err) {
    console.error('Error updating order:', err);
    error('Ошибка при обновлении заказа');
  }
};

const formatPrice = (price) => {
  return parseFloat(price).toFixed(2);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getDeliveryTypeLabel = (type) => {
  const labels = {
    pickup: 'Самовывоз',
    courier: 'Курьер',
    post: 'Почта'
  };
  return labels[type] || type;
};

const getPaymentTypeLabel = (type) => {
  const labels = {
    cash: 'Наличные',
    card: 'Карта',
    online: 'Онлайн'
  };
  return labels[type] || type;
};

const getPaymentStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    paid: 'Оплачен',
    failed: 'Ошибка',
    refunded: 'Возврат'
  };
  return labels[status] || status;
};

const getPaymentStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    refunded: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  };
  return classes[status] || classes.pending;
};

const getDeliveryStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    processing: 'В обработке',
    shipped: 'Отправлен',
    delivered: 'Доставлен',
    cancelled: 'Отменен'
  };
  return labels[status] || status;
};

const getDeliveryStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    shipped: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    delivered: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
  };
  return classes[status] || classes.pending;
};

onMounted(() => {
  loadOrder();
});
</script>
