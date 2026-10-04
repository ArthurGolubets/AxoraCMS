<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Глобальные фильтры</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Фильтры, доступные во всех категориях каталога</p>
      </div>
      <ThemeButton variant="primary" @click="openEditor(null)">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Создать фильтр
      </ThemeButton>
    </div>

    <!-- Toolbar -->
    <div class="mb-4 flex flex-col md:flex-row gap-3">
      <div class="relative flex-1">
        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input
          v-model="query.search"
          @input="onSearch"
          type="text"
          placeholder="Поиск по названию или коду..."
          class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
        >
      </div>
      <select v-model="query.type" @change="loadFilters" class="md:w-56 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
        <option value="">Все типы</option>
        <option v-for="type in FILTER_TYPES" :key="type.value" :value="type.value">{{ type.short }}</option>
      </select>
      <select v-model="query.is_active" @change="loadFilters" class="md:w-44 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
        <option value="">Все статусы</option>
        <option value="1">Активные</option>
        <option value="0">Неактивные</option>
      </select>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-gray-500 dark:text-gray-400">Загрузка фильтров...</div>

      <div v-else-if="filtersList.length === 0" class="p-12 text-center">
        <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
          <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        </div>
        <h3 class="mt-4 text-base font-medium text-gray-900 dark:text-white">{{ hasActiveFilters ? 'Ничего не найдено' : 'Фильтров пока нет' }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ hasActiveFilters ? 'Измените условия поиска' : 'Создайте первый фильтр для каталога' }}</p>
      </div>

      <template v-else>
        <div class="hidden md:flex items-center gap-4 px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
          <span class="w-5"></span>
          <span class="flex-1">Фильтр</span>
          <span class="w-44">Тип</span>
          <span class="w-72">Значения</span>
          <span class="w-24">Статус</span>
          <span class="w-8"></span>
        </div>

        <draggable
          v-model="filtersList"
          item-key="id"
          handle=".drag-handle"
          ghost-class="opacity-40"
          :disabled="hasActiveFilters"
          class="divide-y divide-gray-100 dark:divide-gray-700"
          @end="saveOrder"
        >
          <template #item="{ element: filter }">
            <div
              class="flex flex-wrap md:flex-nowrap items-center gap-x-4 gap-y-2 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer transition-colors"
              @click="$router.push(`/filters/${filter.id}`)"
            >
              <span
                class="drag-handle w-5 shrink-0 text-gray-400"
                :class="hasActiveFilters ? 'opacity-30 cursor-not-allowed' : 'cursor-move hover:text-gray-600 dark:hover:text-gray-300'"
                :title="hasActiveFilters ? 'Сбросьте поиск и фильтры, чтобы менять порядок' : 'Перетащите, чтобы изменить порядок'"
                @click.stop
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
              </span>

              <div class="flex items-center gap-3 flex-1 min-w-0">
                <span class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center" :class="filterType(filter.type).badge">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="filterType(filter.type).icon"/></svg>
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ filter.name }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate">{{ filter.code }}</div>
                </div>
              </div>

              <span class="md:w-44 text-sm text-gray-600 dark:text-gray-300">{{ filterType(filter.type).short }}</span>

              <div class="md:w-72 flex flex-wrap gap-1.5 min-w-0">
                <template v-if="listValues(filter).length">
                  <span
                    v-for="value in listValues(filter).slice(0, 4)"
                    :key="value.id"
                    class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                    :class="{ 'line-through opacity-60': !value.is_active }"
                  >{{ value.value }}</span>
                  <span v-if="listValues(filter).length > 4" class="px-2 py-0.5 text-xs text-gray-500 dark:text-gray-400">+{{ listValues(filter).length - 4 }}</span>
                </template>
                <span v-else class="text-xs text-gray-400">{{ valuesHint(filter) }}</span>
              </div>

              <span class="md:w-24">
                <span class="inline-flex items-center gap-1.5 text-xs" :class="filter.is_active ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                  <span class="w-2 h-2 rounded-full" :class="filter.is_active ? 'bg-green-500' : 'bg-gray-400'"></span>
                  {{ filter.is_active ? 'Активен' : 'Выключен' }}
                </span>
              </span>

              <span class="w-8 text-right" @click.stop>
                <ActionMenu :items="rowActions(filter)" />
              </span>
            </div>
          </template>
        </draggable>
      </template>
    </div>

    <FilterEditorPanel v-if="editor.show" :filter="editor.filter" @saved="onSaved" @close="editor.show = false" />
    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import draggable from 'vuedraggable';
import ThemeButton from './ThemeButton.vue';
import ActionMenu from './ActionMenu.vue';
import ConfirmModal from './ConfirmModal.vue';
import FilterEditorPanel from './FilterEditorPanel.vue';
import { useModal } from '../composables/useModal';
import { FILTER_TYPES, filterType, ENTITY_TYPE_LABELS } from '../utils/filterTypes';

const route = useRoute();
const router = useRouter();
const { error: showError } = useModal();

const filtersList = ref([]);
const loading = ref(false);
const editor = ref({ show: false, filter: null });
const confirmModal = ref(null);
let searchTimer = null;

const query = ref({
  search: '',
  type: '',
  is_active: '',
  catalog_id: 'global', // только глобальные фильтры
});

// Reordering is only meaningful on the full, unfiltered list.
const hasActiveFilters = computed(() => !!(query.value.search || query.value.type || query.value.is_active !== ''));

const csrfHeaders = () => ({
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
  Accept: 'application/json',
});

const listValues = (filter) => (['select', 'checkbox'].includes(filter.type)
  ? [...(filter.values || [])].sort((a, b) => a.sort - b.sort)
  : []);

const valuesHint = (filter) => {
  if (filter.type === 'range') {
    const from = filter.values?.find((v) => v.code === 'from')?.value;
    const to = filter.values?.find((v) => v.code === 'to')?.value;
    return from || to ? `от ${from ?? '…'} до ${to ?? '…'}` : 'Диапазон без границ';
  }
  if (filter.type === 'entity') return ENTITY_TYPE_LABELS[filter.settings?.entity_type] || 'Привязка не настроена';
  if (filter.type === 'string') return 'Значение задаётся в товаре';
  return 'Нет значений';
};

const rowActions = (filter) => [
  { label: 'Открыть', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', action: () => router.push(`/filters/${filter.id}`) },
  { label: 'Редактировать', icon: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z', action: () => openEditor(filter) },
  { divider: true },
  { label: 'Удалить', danger: true, icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', action: () => deleteFilter(filter) },
];

const loadFilters = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    Object.entries(query.value).forEach(([key, value]) => {
      if (value !== '' && value !== null && value !== undefined) params.append(key, value);
    });
    const response = await fetch(`/admin/api/filters?${params}`, { headers: { Accept: 'application/json' } });
    if (response.ok) filtersList.value = await response.json();
  } catch (e) {
    showError('Не удалось загрузить фильтры');
  } finally {
    loading.value = false;
  }
};

const onSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadFilters, 300);
};

const openEditor = (filter) => {
  editor.value = { show: true, filter };
};

const onSaved = () => {
  editor.value.show = false;
  loadFilters();
};

const saveOrder = async () => {
  try {
    const response = await fetch('/admin/api/filters/reorder', {
      method: 'POST',
      headers: csrfHeaders(),
      body: JSON.stringify({ ids: filtersList.value.map((f) => f.id) }),
    });
    if (!response.ok) throw new Error();
  } catch (e) {
    showError('Не удалось сохранить порядок фильтров');
    loadFilters();
  }
};

const deleteFilter = async (filter) => {
  const confirmed = await confirmModal.value.open({
    title: 'Удалить фильтр?',
    message: `Фильтр «${filter.name}», его значения и связи с товарами будут удалены. Действие нельзя отменить.`,
    confirmText: 'Удалить',
    dangerMode: true,
  });
  if (!confirmed) return;

  const response = await fetch(`/admin/api/filters/${filter.id}`, { method: 'DELETE', headers: csrfHeaders() });
  if (response.ok) {
    loadFilters();
  } else {
    const data = await response.json().catch(() => ({}));
    showError(data.message || 'Ошибка при удалении фильтра');
  }
};

/**
 * Old links /filters/create and /filters/:id/edit redirect here with ?create / ?edit.
 */
const openFromQuery = () => {
  if (route.query.create) {
    openEditor(null);
  } else if (route.query.edit) {
    openEditor({ id: Number(route.query.edit) });
  } else {
    return;
  }
  router.replace({ query: {} });
};

watch(() => route.query, openFromQuery);

onMounted(() => {
  loadFilters();
  openFromQuery();
});
</script>
