<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Список заказов</h1>
      <button @click="$router.push('/orders/create')" class="px-4 py-2 text-white rounded-md transition-opacity hover:opacity-90" :style="buttonStyle">
        Создать заказ
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Статус оплаты</label>
          <select v-model="filters.payment_status" @change="loadOrders" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md">
            <option value="">Все</option>
            <option value="pending">Ожидает оплаты</option>
            <option value="paid">Оплачен</option>
            <option value="failed">Ошибка оплаты</option>
            <option value="canceled">Отменен</option>
            <option value="refunded">Возврат</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Тип доставки</label>
          <select v-model="filters.delivery_type" @change="loadOrders" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md">
            <option value="">Все</option>
            <option value="pickup">Самовывоз</option>
            <option value="courier">Курьер</option>
            <option value="post">Почта</option>
            <option v-if="cdekAvailable" value="cdek">СДЭК</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Тип оплаты</label>
          <select v-model="filters.payment_type" @change="loadOrders" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md">
            <option value="">Все</option>
            <option value="online">Онлайн</option>
            <option value="cash">Наличные</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Клиент</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Телефон</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Сумма</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Статусы</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Доставка</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Дата</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-if="loading">
            <td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Загрузка...</td>
          </tr>
          <tr v-else-if="orders.length === 0">
            <td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Нет заказов</td>
          </tr>
          <tr v-else v-for="order in orders" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">#{{ order.id }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ order.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ order.email }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ order.phone }}</td>
            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(order.total_price || 0) }}</td>
            <td class="px-6 py-4">
              <div class="flex flex-col gap-1">
                <span class="px-2 py-1 text-xs rounded-full w-fit" :class="getPaymentStatusClass(order.payment_status)">
                  {{ getPaymentStatusLabel(order.payment_status) }}
                </span>
                <span class="px-2 py-1 text-xs rounded-full w-fit" :class="getDeliveryStatusClass(order.delivery_status)">
                  {{ getDeliveryStatusLabel(order.delivery_status) }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ getDeliveryTypeLabel(order.delivery_type) }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ formatDate(order.created_at) }}</td>
            <td class="px-6 py-4 text-sm text-right space-x-2">
              <button @click="$router.push(`/orders/${order.id}`)" class="text-blue-600 hover:text-blue-800">Просмотр</button>
              <button @click="deleteOrder(order.id)" class="text-red-600 hover:text-red-800">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import { useCdekDelivery } from '../composables/useCdekDelivery';

const router = useRouter();
const { success, error, confirm } = useModal();
const { buttonStyle } = useTheme();

const orders = ref([]);
const loading = ref(false);

const filters = ref({
  payment_status: '',
  delivery_type: '',
  payment_type: ''
});

const loadOrders = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.payment_status) params.append('payment_status', filters.value.payment_status);
    if (filters.value.delivery_type) params.append('delivery_type', filters.value.delivery_type);
    if (filters.value.payment_type) params.append('payment_type', filters.value.payment_type);

    const response = await fetch(`/admin/api/orders?${params.toString()}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      orders.value = data.data || [];
    }
  } catch (err) {
    error('Ошибка при загрузке заказов');
  } finally {
    loading.value = false;
  }
};

const deleteOrder = async (id) => {
  const confirmed = await confirm('Вы уверены, что хотите удалить этот заказ?');
  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/orders/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      success('Заказ успешно удален');
      loadOrders();
    } else {
      error('Ошибка при удалении заказа');
    }
  } catch (err) {
    error('Ошибка при удалении заказа');
  }
};

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(price);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' });
};

const getPaymentStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает оплаты',
    paid: 'Оплачен',
    success: 'Оплачен',
    failed: 'Ошибка оплаты',
    canceled: 'Отменен',
    refunded: 'Возврат'
  };
  return labels[status] || status;
};

const getPaymentStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    canceled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    refunded: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
  };
  return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

const getDeliveryStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    processing: 'В обработке',
    shipped: 'Отправлен',
    delivered: 'Доставлен',
    cancelled: 'Отменён'
  };
  return labels[status] || status;
};

const getDeliveryStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    shipped: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    delivered: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  };
  return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

const { cdekAvailable } = useCdekDelivery();

const getDeliveryTypeLabel = (type) => {
  const labels = {
    pickup: 'Самовывоз',
    courier: 'Курьер',
    post: 'Почта',
    cdek: 'СДЭК'
  };
  return labels[type] || type;
};

onMounted(() => {
  loadOrders();
});
</script>
