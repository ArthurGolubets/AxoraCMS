<template>
  <div v-if="selectedIds.length" class="mb-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex flex-wrap items-center gap-3">
    <span class="text-sm font-medium text-gray-900 dark:text-white">Выбрано товаров: {{ selectedIds.length }}</span>
    <select v-model="bulkAction" class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
      <option value="">Групповое действие…</option>
      <option value="move">Перенести в категорию</option>
      <option value="delete">Удалить</option>
    </select>
    <div v-if="bulkAction === 'move'" class="w-full sm:w-96">
      <CategorySelect
        v-model="targetCatalogId"
        :categories="categories"
        placeholder="Поиск категории для переноса..."
        picker-title="Перенести в категорию"
        clearable
      />
    </div>
    <ThemeButton
      :variant="bulkAction === 'delete' ? 'danger' : 'primary'"
      :disabled="!canApply || processing"
      @click="apply"
    >
      {{ processing ? 'Выполняется…' : 'Применить' }}
    </ThemeButton>
    <button type="button" @click="reset" class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">Снять выделение</button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useModal } from '../composables/useModal';
import { useCategoryBreadcrumb } from '../composables/useCategoryBreadcrumb';
import ThemeButton from './ThemeButton.vue';
import CategorySelect from './CategorySelect.vue';

const props = defineProps({
  selectedIds: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['clear', 'completed']);

const { confirm, success, error } = useModal();

const categories = ref([]);
const bulkAction = ref('');
const targetCatalogId = ref(null);
const processing = ref(false);

const loadCategories = async () => {
  try {
    const response = await fetch('/admin/api/catalogs/list');
    categories.value = await response.json();
  } catch (err) {
    console.error('Error loading categories:', err);
  }
};

const { breadcrumbLabel } = useCategoryBreadcrumb(categories);

const canApply = computed(() => {
  if (!props.selectedIds.length) return false;
  if (bulkAction.value === 'delete') return true;
  if (bulkAction.value === 'move') return !!targetCatalogId.value;
  return false;
});

const reset = () => {
  bulkAction.value = '';
  targetCatalogId.value = null;
  emit('clear');
};

const postBulk = async (url, payload) => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json',
    },
    body: JSON.stringify(payload)
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.message || 'Request failed');
  }

  return data;
};

const apply = async () => {
  if (!canApply.value) return;

  const count = props.selectedIds.length;
  const isDelete = bulkAction.value === 'delete';
  const targetLabel = targetCatalogId.value ? breadcrumbLabel(targetCatalogId.value) : '';

  const confirmed = isDelete
    ? await confirm('Удалить товары?', `Будет удалено товаров: ${count}. Это действие нельзя отменить.`)
    : await confirm('Перенести товары?', `Перенести товаров: ${count} в категорию "${targetLabel}"?`);
  if (!confirmed) return;

  processing.value = true;
  try {
    if (isDelete) {
      await postBulk('/admin/api/products/bulk-delete', { ids: props.selectedIds });
    } else {
      await postBulk('/admin/api/products/bulk-move', { ids: props.selectedIds, catalog_id: targetCatalogId.value });
    }

    reset();
    emit('completed');
    await success(isDelete ? `Удалено товаров: ${count}` : `Перенесено товаров: ${count} в "${targetLabel}"`);
  } catch (err) {
    console.error('Bulk action error:', err);
    await error(err.message || 'Ошибка при выполнении группового действия');
  } finally {
    processing.value = false;
  }
};

onMounted(() => {
  loadCategories();
});
</script>
