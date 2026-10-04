<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Фильтры категории</h3>

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
      Фильтры, созданные для этой категории, будут доступны для всех товаров в этой категории и её подкатегориях.
      Глобальные фильтры доступны автоматически.
    </p>

    <!-- Category Filters List -->
    <div v-if="categoryFilters.length > 0" class="mb-6">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
        Фильтры этой категории ({{ categoryFilters.length }})
      </h4>
      <draggable v-model="categoryFilters" item-key="id" handle=".drag-handle" ghost-class="opacity-40" class="space-y-2" @end="saveOrder">
        <template #item="{ element: filter }">
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <div class="flex items-center gap-3 flex-1">
            <span class="drag-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 shrink-0" title="Перетащите, чтобы изменить порядок">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
            </span>
            <span class="px-2 py-1 text-xs font-medium rounded"
                  :class="{
                'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200': filter.type === 'select',
                'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200': filter.type === 'checkbox',
                'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200': filter.type === 'range',
                'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200': filter.type === 'entity',
                'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200': filter.type === 'string',
              }">
              {{ typeLabels[filter.type] }}
            </span>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ filter.name }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 font-mono">{{ filter.code }}</p>
            </div>
            <span v-if="!filter.is_active" class="px-2 py-1 text-xs font-medium rounded bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
              Неактивен
            </span>
          </div>
          <div class="flex gap-2">
            <button
                @click="openEditor(filter)"
                class="p-2 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors"
                title="Редактировать"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            <button
                @click="confirmDeleteFilter(filter)"
                class="p-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
                title="Удалить"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
        </template>
      </draggable>
    </div>

    <!-- Inherited Filters -->
    <div v-if="inheritedFilters.length > 0" class="mb-6">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
        Унаследованные фильтры ({{ inheritedFilters.length }})
      </h4>
      <div class="space-y-2">
        <div
            v-for="filter in inheritedFilters"
            :key="filter.id"
            class="flex items-center gap-3 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg"
        >
          <span class="px-2 py-1 text-xs font-medium rounded"
                :class="{
              'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200': filter.type === 'select',
              'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200': filter.type === 'checkbox',
              'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200': filter.type === 'range'
            }">
            {{ typeLabels[filter.type] }}
          </span>
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ filter.name }}</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">
              <span class="font-mono">{{ filter.code }}</span>
              <span class="mx-2">•</span>
              <span>из категории: {{ filter.catalog?.name || 'Родительская' }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Global Filters Info -->
    <div class="mb-6 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
      <p class="text-xs text-gray-600 dark:text-gray-400">
        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Глобальные фильтры автоматически доступны для всех товаров и не отображаются здесь.
        Управлять ими можно в разделе "Фильтры".
      </p>
    </div>

    <!-- Add Filter Button -->
    <ThemeButton variant="primary" size="sm" @click="openEditor(null)">
      <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Добавить фильтр
    </ThemeButton>

    <!-- Create / edit filter side panel -->
    <FilterEditorPanel
      v-if="editor.show"
      :filter="editor.filter"
      :fixed-catalog-id="Number(catalogId)"
      @saved="onSaved"
      @close="editor.show = false"
    />

    <!-- Delete Confirmation Modal -->
    <Modal v-if="deleteModal.show" @close="deleteModal.show = false">
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Подтверждение удаления</h3>
      </template>
      <template #body>
        <p class="text-gray-700 dark:text-gray-300">
          Вы действительно хотите удалить фильтр <strong>{{ deleteModal.filter?.name }}</strong>?
        </p>
        <p class="text-sm text-red-600 dark:text-red-400 mt-2">
          Все значения фильтра и связи с товарами также будут удалены.
        </p>
      </template>
      <template #footer>
        <ThemeButton variant="secondary" @click="deleteModal.show = false">
          Отмена
        </ThemeButton>
        <ThemeButton variant="danger" @click="deleteFilter" :disabled="deleting">
          <span v-if="deleting">Удаление...</span>
          <span v-else>Удалить</span>
        </ThemeButton>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import draggable from 'vuedraggable';
import ThemeButton from './ThemeButton.vue';
import Modal from './Modal.vue';
import FilterEditorPanel from './FilterEditorPanel.vue';
import { useModal } from '../composables/useModal';

const { error: showError } = useModal();

const props = defineProps({
  catalogId: {
    type: [Number, String],
    default: null
  }
});

const categoryFilters = ref([]);
const inheritedFilters = ref([]);
const editor = ref({ show: false, filter: null });
const deleteModal = ref({ show: false, filter: null });
const deleting = ref(false);

const typeLabels = {
  select: 'Один вариант',
  checkbox: 'Несколько вариантов',
  range: 'Диапазон',
  entity: 'Привязка',
  string: 'Текст'
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
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        Accept: 'application/json',
      },
      body: JSON.stringify({ ids: categoryFilters.value.map((f) => f.id) }),
    });
    if (!response.ok) throw new Error();
  } catch (e) {
    showError('Не удалось сохранить порядок фильтров');
    loadFilters();
  }
};

const loadFilters = async () => {
  if (!props.catalogId) return;

  try {
    const response = await fetch(`/admin/api/filters/for-catalog/${props.catalogId}`, {
      headers: { 'Accept': 'application/json' },
    });

    if (response.ok) {
      const data = await response.json();

      // Separate category-specific and inherited filters
      categoryFilters.value = data.filter(f => f.catalog_id === parseInt(props.catalogId));
      inheritedFilters.value = data.filter(f => f.catalog_id !== parseInt(props.catalogId) && f.catalog_id !== null);
    }
  } catch (error) {
    console.error('Failed to load filters:', error);
  }
};

const confirmDeleteFilter = (filter) => {
  deleteModal.value = {
    show: true,
    filter,
  };
};

const deleteFilter = async () => {
  deleting.value = true;
  try {
    const response = await fetch(`/admin/api/filters/${deleteModal.value.filter.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
    });

    if (response.ok) {
      deleteModal.value.show = false;
      loadFilters();
    } else {
      const error = await response.json();
      showError(error.message || 'Ошибка при удалении фильтра');
    }
  } catch (error) {
    console.error('Failed to delete filter:', error);
    showError('Ошибка при удалении фильтра');
  } finally {
    deleting.value = false;
  }
};

watch(() => props.catalogId, () => {
  if (props.catalogId) {
    loadFilters();
  }
});

onMounted(() => {
  if (props.catalogId) {
    loadFilters();

  }
});
</script>