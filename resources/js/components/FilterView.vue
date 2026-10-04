<template>
  <div>
    <div v-if="loading" class="p-12 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <template v-else-if="filter">
      <!-- Header -->
      <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-4 min-w-0">
          <button @click="$router.push('/filters')" class="mt-1 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" title="К списку фильтров">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          </button>
          <span class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center" :class="type.badge">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="type.icon"/></svg>
          </span>
          <div class="min-w-0">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white truncate">{{ filter.name }}</h2>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
              <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs">{{ filter.code }}</code>
              <span class="inline-flex items-center gap-1.5 text-xs" :class="filter.is_active ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                <span class="w-2 h-2 rounded-full" :class="filter.is_active ? 'bg-green-500' : 'bg-gray-400'"></span>
                {{ filter.is_active ? 'Активен' : 'Выключен' }}
              </span>
            </div>
          </div>
        </div>
        <div class="flex gap-2">
          <ThemeButton variant="primary" @click="editorOpen = true">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            Редактировать
          </ThemeButton>
          <button type="button" @click="deleteFilter" class="px-4 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
            Удалить
          </button>
        </div>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
          <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400">Тип</p>
          <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ type.label }}</p>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ type.description }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
          <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400">Где доступен</p>
          <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ filter.catalog_id ? (filter.catalog?.name || `Категория #${filter.catalog_id}`) : 'Во всех категориях' }}</p>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ filter.catalog_id ? 'В категории и её дочерних' : 'Глобальный фильтр' }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
          <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400">Значения</p>
          <template v-if="hasValueList">
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ activeValuesCount }} из {{ sortedValues.length }} активны</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Варианты для выбора покупателем</p>
          </template>
          <template v-else>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ valuesSummary.title }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ valuesSummary.hint }}</p>
          </template>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
          <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400">Создан</p>
          <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(filter.created_at) }}</p>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Изменён {{ formatDate(filter.updated_at) }}</p>
        </div>
      </div>

      <div v-if="filter.description" class="mb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">Описание</p>
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ filter.description }}</p>
      </div>

      <!-- Values -->
      <div v-if="hasValueList" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Значения фильтра</h3>
          <button type="button" @click="editorOpen = true" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">Изменить значения</button>
        </div>
        <div v-if="sortedValues.length === 0" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Значений пока нет</div>
        <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
          <li v-for="(value, index) in sortedValues" :key="value.id" class="flex items-center gap-4 px-6 py-3">
            <span class="w-6 text-xs text-gray-400 text-right">{{ index + 1 }}</span>
            <span class="flex-1 text-sm" :class="value.is_active ? 'text-gray-900 dark:text-white' : 'text-gray-400 line-through'">{{ value.value }}</span>
            <code class="text-xs text-gray-500 dark:text-gray-400">{{ value.code || '—' }}</code>
            <span v-if="!value.is_active" class="px-2 py-0.5 text-[11px] rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">выключено</span>
          </li>
        </ul>
      </div>
    </template>

    <FilterEditorPanel v-if="editorOpen && filter" :filter="filter" @saved="onSaved" @close="editorOpen = false" />
    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from './ThemeButton.vue';
import ConfirmModal from './ConfirmModal.vue';
import FilterEditorPanel from './FilterEditorPanel.vue';
import { useModal } from '../composables/useModal';
import { filterType, ENTITY_TYPE_LABELS } from '../utils/filterTypes';

const route = useRoute();
const router = useRouter();
const { error: showError } = useModal();

const filterId = route.params.id;
const filter = ref(null);
const loading = ref(false);
const editorOpen = ref(false);
const confirmModal = ref(null);

const type = computed(() => filterType(filter.value?.type));
const hasValueList = computed(() => ['select', 'checkbox'].includes(filter.value?.type));

const sortedValues = computed(() => [...(filter.value?.values || [])].sort((a, b) => (a.sort - b.sort) || (a.id - b.id)));
const activeValuesCount = computed(() => sortedValues.value.filter((v) => v.is_active).length);

const valuesSummary = computed(() => {
  const f = filter.value;
  if (f?.type === 'range') {
    const from = f.values?.find((v) => v.code === 'from')?.value;
    const to = f.values?.find((v) => v.code === 'to')?.value;
    return from || to
      ? { title: `от ${from ?? '…'} до ${to ?? '…'}`, hint: 'Границы диапазона' }
      : { title: 'Без границ', hint: 'Покупатель вводит любые «от» и «до»' };
  }
  if (f?.type === 'entity') {
    return { title: ENTITY_TYPE_LABELS[f.settings?.entity_type] || 'Не настроено', hint: 'Источник значений' };
  }
  return { title: 'Задаётся в товаре', hint: 'Своё значение у каждого товара' };
});

const formatDate = (value) => (value
  ? new Date(value).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' })
  : '—');

const loadFilter = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/admin/api/filters/${filterId}`, { headers: { Accept: 'application/json' } });
    if (!response.ok) {
      showError('Фильтр не найден');
      router.push('/filters');
      return;
    }
    filter.value = await response.json();
  } catch (e) {
    showError('Ошибка при загрузке фильтра');
  } finally {
    loading.value = false;
  }
};

const onSaved = () => {
  editorOpen.value = false;
  loadFilter();
};

const deleteFilter = async () => {
  const confirmed = await confirmModal.value.open({
    title: 'Удалить фильтр?',
    message: `Фильтр «${filter.value.name}», его значения и связи с товарами будут удалены. Действие нельзя отменить.`,
    confirmText: 'Удалить',
    dangerMode: true,
  });
  if (!confirmed) return;

  const response = await fetch(`/admin/api/filters/${filterId}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
      Accept: 'application/json',
    },
  });
  if (response.ok) {
    router.push('/filters');
  } else {
    const data = await response.json().catch(() => ({}));
    showError(data.message || 'Ошибка при удалении фильтра');
  }
};

onMounted(loadFilter);
</script>
