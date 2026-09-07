<template>
  <div>
    <!-- Confirm Modal -->
    <ConfirmModal
      ref="confirmModal"
      title="Удалить виджет?"
      message="Вы уверены, что хотите удалить этот виджет? Это действие нельзя отменить."
      confirm-text="Удалить"
      cancel-text="Отмена"
      :danger-mode="true"
    />

    <!-- Welcome Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ editMode ? 'Редактирование дашборда' : 'Добро пожаловать!' }}
        </h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          {{ editMode ? 'Перетаскивайте виджеты для изменения порядка' : 'Обзор вашей административной панели' }}
        </p>
      </div>
      <div v-if="userRole === 'super_admin'" class="flex gap-2">
        <button
          v-if="!editMode"
          @click="enableEditMode"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
        >
          Настроить дашборд
        </button>
        <template v-else>
          <button
            @click="saveWidgetsOrder"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors"
          >
            Сохранить
          </button>
          <button
            @click="cancelEditMode"
            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm font-medium transition-colors"
          >
            Отмена
          </button>
          <button
            @click="resetWidgets"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors"
          >
            Сбросить
          </button>
        </template>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Edit Mode: Two Column Layout -->
    <div v-else-if="editMode" class="grid grid-cols-12 gap-6">
      <!-- Left: Available Widgets -->
      <div class="col-span-12 lg:col-span-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 sticky top-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Доступные виджеты</h3>
          <div class="space-y-2 max-h-[calc(100vh-200px)] overflow-y-auto">
            <div
              v-for="(type, key) in availableTypes"
              :key="key"
              @click="addWidget(key)"
              class="p-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 dark:hover:border-blue-500 cursor-pointer transition-colors"
            >
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <div>
                  <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ type.title }}</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ type.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Dashboard Grid (Draggable) -->
      <div class="col-span-12 lg:col-span-8">
        <draggable
          v-model="widgets"
          :animation="200"
          handle=".drag-handle"
          item-key="id"
          class="grid grid-cols-12 gap-4"
        >
          <template #item="{ element: widget }">
            <div :class="['relative group', getColSpanClass(widget.width)]">
              <div class="absolute -top-3 left-0 right-0 flex justify-between items-center z-10 px-2">
                <div class="flex gap-1 bg-gray-900 dark:bg-gray-700 rounded-full px-2 py-1">
                  <!-- Drag Handle -->
                  <button class="drag-handle cursor-move p-1 hover:bg-gray-700 dark:hover:bg-gray-600 rounded text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    </svg>
                  </button>

                  <!-- Width Controls -->
                  <button @click="changeWidgetWidth(widget, -1)" class="p-1 hover:bg-gray-700 dark:hover:bg-gray-600 rounded text-white" title="Уменьшить">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                  </button>
                  <span class="text-xs text-white px-2 py-1">{{ widget.width }}/12</span>
                  <button @click="changeWidgetWidth(widget, 1)" class="p-1 hover:bg-gray-700 dark:hover:bg-gray-600 rounded text-white" title="Увеличить">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                </div>

                <!-- Delete Button -->
                <button
                  @click="removeWidget(widget.id)"
                  class="p-1 bg-red-600 hover:bg-red-700 rounded-full text-white"
                  title="Удалить"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Widget Preview -->
              <div class="opacity-75 pointer-events-none">
                <component :is="getWidgetComponent(widget)" v-bind="getWidgetProps(widget)" />
              </div>
            </div>
          </template>
        </draggable>

        <div v-if="widgets.length === 0" class="text-center py-20 text-gray-500 dark:text-gray-400">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          <p>Дашборд пуст. Добавьте виджеты слева.</p>
        </div>
      </div>
    </div>

    <!-- View Mode: Widgets Grid -->
    <div v-else class="grid grid-cols-12 gap-4">
      <template v-for="widget in widgets" :key="widget.id">
        <div :class="getColSpanClass(widget.width)">
          <component :is="getWidgetComponent(widget)" v-bind="getWidgetProps(widget)" />
        </div>
      </template>

      <div v-if="widgets.length === 0" class="col-span-12">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Дашборд не настроен</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">Нажмите "Настроить дашборд" для добавления виджетов</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, shallowRef } from 'vue';
import axios from 'axios';
import draggable from 'vuedraggable';
import StatWidget from './dashboard/StatWidget.vue';
import ListWidget from './dashboard/ListWidget.vue';
import WidgetCard from './dashboard/WidgetCard.vue';
import ChartWidget from './dashboard/ChartWidget.vue';
import PageVisitsWidget from './dashboard/PageVisitsWidget.vue';
import ConfirmModal from './ConfirmModal.vue';
import { useAppConfig } from '../composables/useAppConfig';

const loading = ref(true);
const loadingMetrics = ref(false);
const widgets = ref([]);
const widgetsBackup = ref([]);
const metrics = ref({});
const availableTypes = ref({});
const userRole = ref('');
const editMode = ref(false);
const confirmModal = ref(null);

const maxSubscriptionCount = computed(() => {
  if (!metrics.value.subscriptions_chart || metrics.value.subscriptions_chart.length === 0) {
    return 1;
  }
  return Math.max(...metrics.value.subscriptions_chart.map(item => item.count));
});

function getWidgetComponent(widget) {
  const type = widget.widget_type;

  // Special widgets
  if (type === 'page_visits_stats') {
    return PageVisitsWidget;
  }

  // List widgets
  if (['popular_pages', 'recent_logs', 'recent_orders', 'promocodes_usage', 'recent_requests'].includes(type)) {
    return ListWidget;
  }

  // Chart widgets
  if (['subscriptions_chart', 'orders_chart', 'revenue_chart', 'pages_views_chart'].includes(type)) {
    return ChartWidget;
  }

  // Default: stat widgets
  return StatWidget;
}

function getWidgetProps(widget) {
  const type = widget.widget_type;

  // Stat widgets
  const statWidgets = {
    users_stats: {
      title: 'Администраторы',
      value: metrics.value.users_stats?.total || 0,
      subtitle: `Активных: ${metrics.value.users_stats?.active || 0}`,
      icon: 'users',
      color: 'blue'
    },
    orders_stats: {
      title: 'Все заказы',
      value: metrics.value.orders_stats?.total || 0,
      icon: 'shopping',
      color: 'green'
    },
    orders_new: {
      title: 'Новые заказы',
      value: metrics.value.orders_stats?.new || 0,
      icon: 'bell',
      color: 'yellow'
    },
    orders_revenue: {
      title: 'Выручка',
      value: formatCurrency(metrics.value.orders_stats?.revenue || 0),
      icon: 'currency',
      color: 'green'
    },
    orders_pending_payment: {
      title: 'Ожидают оплаты',
      value: formatCurrency(metrics.value.orders_stats?.pending_payment || 0),
      icon: 'currency',
      color: 'yellow'
    },
    subscriptions_count: {
      title: 'Подписки',
      value: metrics.value.subscriptions_count?.total || 0,
      subtitle: `Активных: ${metrics.value.subscriptions_count?.active || 0}`,
      icon: 'mail',
      color: 'purple'
    },
    reviews_count: {
      title: 'Отзывы',
      value: metrics.value.reviews_count?.total || 0,
      subtitle: `Модерированных: ${metrics.value.reviews_count?.moderated || 0}`,
      icon: 'star',
      color: 'yellow'
    },
    requests_count: {
      title: 'Обращения',
      value: metrics.value.requests_count?.total || 0,
      subtitle: `Сегодня: ${metrics.value.requests_count?.today || 0}`,
      icon: 'bell',
      color: 'indigo'
    }
  };

  if (statWidgets[type]) {
    return statWidgets[type];
  }

  // List widgets
  if (type === 'popular_pages') {
    return {
      title: 'Популярные страницы',
      items: metrics.value.popular_pages || [],
      loading: loadingMetrics.value,
      width: widget.width
    };
  }

  if (type === 'recent_logs') {
    return {
      title: 'Последние действия',
      items: metrics.value.recent_logs || [],
      loading: loadingMetrics.value,
      width: widget.width
    };
  }

  if (type === 'recent_orders') {
    return {
      title: 'Последние заказы',
      items: metrics.value.recent_orders || [],
      loading: loadingMetrics.value,
      width: widget.width
    };
  }

  if (type === 'promocodes_usage') {
    return {
      title: 'Популярные промокоды',
      items: metrics.value.promocodes_usage || [],
      loading: loadingMetrics.value,
      width: widget.width
    };
  }

  if (type === 'recent_requests') {
    return {
      title: 'Последние обращения',
      items: metrics.value.recent_requests || [],
      loading: loadingMetrics.value,
      width: widget.width
    };
  }

  if (type === 'page_visits_stats') {
    const topPages = (metrics.value.page_visits_stats?.top_pages || []).map(page => ({
      id: page.id,
      title: page.title,
      subtitle: page.slug,
      visits: page.visits
    }));

    return {
      title: 'Топ-5 страниц по посещениям',
      items: topPages,
      totalVisits: metrics.value.page_visits_stats?.total_visits_30d || 0,
      todayVisits: metrics.value.page_visits_stats?.today_visits || 0,
      loading: loadingMetrics.value,
      width: widget.width
    };
  }

  if (type === 'subscriptions_chart') {
    return {
      title: 'График подписок (30 дней)',
      chartData: metrics.value.subscriptions_chart || [],
      chartType: 'line',
      lineColor: 'text-purple-500',
      areaColor: 'fill-purple-500',
      loading: loadingMetrics.value,
    };
  }

  if (type === 'orders_chart') {
    return {
      title: 'График заказов (30 дней)',
      chartData: metrics.value.orders_chart || [],
      chartType: 'line',
      lineColor: 'text-blue-500',
      areaColor: 'fill-blue-500',
      loading: loadingMetrics.value,
    };
  }

  if (type === 'revenue_chart') {
    return {
      title: 'График выручки (30 дней)',
      chartData: metrics.value.revenue_chart || [],
      chartType: 'line',
      lineColor: 'text-green-500',
      areaColor: 'fill-green-500',
      loading: loadingMetrics.value,
    };
  }

  if (type === 'pages_views_chart') {
    return {
      title: 'Топ-10 страниц по просмотрам',
      chartData: metrics.value.pages_views_chart || [],
      chartType: 'bar',
      loading: loadingMetrics.value,
    };
  }

  return {};
}

function enableEditMode() {
  editMode.value = true;
  widgetsBackup.value = JSON.parse(JSON.stringify(widgets.value));
}

function cancelEditMode() {
  editMode.value = false;
  widgets.value = JSON.parse(JSON.stringify(widgetsBackup.value));
}

async function saveWidgetsOrder() {
  try {
    // Save each widget individually to include width
    for (let i = 0; i < widgets.value.length; i++) {
      const widget = widgets.value[i];
      await axios.put(`/admin/api/dashboard/widgets/${widget.id}`, {
        position: i + 1,
        width: widget.width
      });
    }

    editMode.value = false;
    await loadWidgets();
  } catch (error) {
    console.error('Ошибка сохранения порядка:', error);
    alert('Ошибка сохранения порядка виджетов');
  }
}

function changeWidgetWidth(widget, delta) {
  const newWidth = widget.width + delta;
  if (newWidth >= 1 && newWidth <= 12) {
    widget.width = newWidth;
  }
}

async function loadWidgets() {
  try {
    const response = await axios.get('/admin/api/dashboard/widgets');
    if (response.data.success) {
      widgets.value = response.data.data;
    }
  } catch (error) {
    console.error('Ошибка загрузки виджетов:', error);
  }
}

async function loadMetrics() {
  loadingMetrics.value = true;
  try {
    const response = await axios.get('/admin/api/dashboard/metrics');
    if (response.data.success) {
      metrics.value = response.data.data;
    }
  } catch (error) {
    console.error('Ошибка загрузки метрик:', error);
  } finally {
    loadingMetrics.value = false;
  }
}

async function loadAvailableTypes() {
  try {
    const response = await axios.get('/admin/api/dashboard/widgets/available-types');
    if (response.data.success) {
      availableTypes.value = response.data.data;
    }
  } catch (error) {
    console.error('Ошибка загрузки типов виджетов:', error);
  }
}

async function addWidget(widgetType) {
  try {
    const response = await axios.post('/admin/api/dashboard/widgets', {
      widget_type: widgetType,
      width: 6,
    });
    if (response.data.success) {
      await loadWidgets();
    }
  } catch (error) {
    console.error('Ошибка добавления виджета:', error);
    alert('Ошибка добавления виджета');
  }
}

async function removeWidget(widgetId) {
  const confirmed = await confirmModal.value.open();
  if (!confirmed) {
    return;
  }

  try {
    const response = await axios.delete(`/admin/api/dashboard/widgets/${widgetId}`);
    if (response.data.success) {
      await loadWidgets();
    }
  } catch (error) {
    console.error('Ошибка удаления виджета:', error);
    await confirmModal.value.open();
  }
}

async function resetWidgets() {
  const confirmed = await confirmModal.value.open({
    title: 'Сбросить дашборд?',
    message: 'Вы уверены, что хотите сбросить виджеты к настройкам по умолчанию? Текущая конфигурация будет утеряна.',
    confirmText: 'Сбросить',
    dangerMode: true,
  });

  if (!confirmed) {
    return;
  }

  try {
    const response = await axios.post('/admin/api/dashboard/widgets/reset');
    if (response.data.success) {
      await loadWidgets();
      await loadMetrics();
      editMode.value = false;
    }
  } catch (error) {
    console.error('Ошибка сброса виджетов:', error);
  }
}

async function loadUserRole() {
  try {
    const user = await useAppConfig().loadMe();
    if (user) {
      userRole.value = user.role;
    }
  } catch (error) {
    console.error('Ошибка загрузки роли:', error);
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
}

function getColSpanClass(width) {
  const colSpanMap = {
    1: 'col-span-12 md:col-span-1',
    2: 'col-span-12 md:col-span-2',
    3: 'col-span-12 md:col-span-3',
    4: 'col-span-12 md:col-span-4',
    5: 'col-span-12 md:col-span-5',
    6: 'col-span-12 md:col-span-6',
    7: 'col-span-12 md:col-span-7',
    8: 'col-span-12 md:col-span-8',
    9: 'col-span-12 md:col-span-9',
    10: 'col-span-12 md:col-span-10',
    11: 'col-span-12 md:col-span-11',
    12: 'col-span-12'
  };
  return colSpanMap[width] || 'col-span-12 md:col-span-6';
}

onMounted(async () => {
  loading.value = true;
  await loadUserRole();
  await Promise.all([
    loadWidgets(),
    loadMetrics(),
    loadAvailableTypes(),
  ]);
  loading.value = false;
});
</script>
