<template>
  <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white/50 dark:bg-gray-800/50">
      <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ title }}</h3>
    </div>

    <div class="p-6">
      <div v-if="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-blue-200 border-t-blue-600"></div>
      </div>

      <div v-else-if="items.length === 0" class="text-center py-8">
        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ emptyText }}</p>
      </div>

      <div v-else class="space-y-3">
        <div v-for="(item, index) in items" :key="index"
             class="p-4 bg-gradient-to-r from-gray-50 to-white dark:from-gray-700/30 dark:to-gray-800/30 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 transition-colors duration-200">
          <slot name="item" :item="item" :index="index">
            <!-- Default rendering based on item structure -->
            <div v-if="item.payment_status && item.delivery_status" class="space-y-1">
              <!-- Recent orders -->
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.name }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ item.created_at }}</span>
                  </div>
                  <div class="flex items-center gap-3 text-xs mb-1">
                    <span v-if="item.email" class="text-gray-600 dark:text-gray-400">{{ item.email }}</span>
                    <span v-if="item.phone" class="text-gray-600 dark:text-gray-400">{{ item.phone }}</span>
                  </div>
                  <div class="flex items-center gap-3 text-xs">
                    <span class="px-2 py-1 rounded-full text-white"
                          :class="item.payment_status === 'paid' ? 'bg-green-500' : item.payment_status === 'pending' ? 'bg-yellow-500' : 'bg-red-500'">
                      {{ translatePaymentStatus(item.payment_status) }}
                    </span>
                    <span class="px-2 py-1 rounded-full bg-blue-500 text-white">
                      {{ translateDeliveryStatus(item.delivery_status) }}
                    </span>
                    <span class="font-semibold text-gray-900 dark:text-white ml-auto">{{ formatPrice(item.total_price) }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else-if="item.admin_name" class="space-y-1">
              <!-- Recent logs -->
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ item.admin_name }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ item.created_at }}</span>
                  </div>
                  <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                    <span class="font-medium">{{ item.action }}</span>
                    <span v-if="item.entity_type" class="text-gray-500 dark:text-gray-400"> • {{ item.entity_type }}</span>
                  </p>
                  <p v-if="item.description" class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">{{ item.description }}</p>
                </div>
              </div>
            </div>
            <div v-else-if="item.code && item.current_usage !== undefined" class="space-y-1">
              <!-- Promocodes -->
              <div class="flex items-center justify-between gap-3">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.name }}</span>
                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs rounded font-mono">{{ item.code }}</span>
                  </div>
                  <div class="flex items-center gap-3 text-xs">
                    <span class="text-gray-600 dark:text-gray-400">
                      Использований: <span class="font-semibold">{{ item.current_usage }}</span> / {{ item.max_usage || '∞' }}
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">
                      Скидка: <span class="font-semibold">{{ item.type === 'percent' ? `${item.value}%` : formatPrice(item.value) }}</span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else-if="item.comment !== undefined" class="space-y-1">
              <!-- User requests -->
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.name }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ item.created_at }}</span>
                  </div>
                  <div class="flex items-center gap-3 text-xs mb-1">
                    <span v-if="item.email" class="text-gray-600 dark:text-gray-400">{{ item.email }}</span>
                    <span v-if="item.phone" class="text-gray-600 dark:text-gray-400">{{ item.phone }}</span>
                  </div>
                  <p v-if="item.comment" class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ item.comment }}</p>
                </div>
              </div>
            </div>
            <component :is="item.link ? 'router-link' : 'div'" v-else-if="item.label" :to="item.link" class="flex items-center justify-between gap-3" :class="item.link ? 'hover:opacity-75' : ''">
              <!-- Simple row: label, hint, value -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.label }}</p>
                <p v-if="item.hint" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ item.hint }}</p>
              </div>
              <span class="text-sm font-semibold text-blue-600 dark:text-blue-400 whitespace-nowrap">{{ item.price ? formatPrice(item.value) : item.value }}</span>
            </component>
            <div v-else>
              <!-- Fallback to JSON -->
              <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ item }}</pre>
            </div>
          </slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

defineProps({
  title: {
    type: String,
    required: true,
  },
  items: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  width: {
    type: Number,
    default: 6,
  },
  emptyText: {
    type: String,
    default: 'Нет данных',
  },
});

function formatPrice(value) {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value);
}

function translatePaymentStatus(status) {
  const translations = {
    'pending': 'Ожидает',
    'paid': 'Оплачен',
    'failed': 'Ошибка',
    'refunded': 'Возврат'
  };
  return translations[status] || status;
}

function translateDeliveryStatus(status) {
  const translations = {
    'pending': 'Ожидает',
    'processing': 'В обработке',
    'shipped': 'Отправлен',
    'delivered': 'Доставлен',
    'cancelled': 'Отменён'
  };
  return translations[status] || status;
}
</script>
