<template>
  <Teleport to="body">
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
        <div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <input
            v-model="search"
            type="text"
            placeholder="Поиск..."
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm"
            autofocus
          >
        </div>

        <div class="px-3 py-3 max-h-[28rem] overflow-y-auto text-left">
          <div v-if="!categories.length" class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
            {{ emptyText }}
          </div>

          <!-- Search mode: flat results with full breadcrumb path -->
          <template v-else-if="search.trim()">
            <div v-if="searchResults.length === 0" class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
              Ничего не найдено по запросу «{{ search }}»
            </div>
            <button
              v-for="cat in searchResults"
              :key="cat.id"
              type="button"
              @click="select(cat)"
              class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700/60 transition-colors"
              :class="{ 'bg-blue-50 dark:bg-blue-900/30': cat.id === selectedId }"
            >
              <div class="text-sm font-medium text-gray-900 dark:text-white">{{ cat.name }}</div>
              <div v-if="breadcrumbParts(cat.id).length > 1" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                {{ breadcrumbParts(cat.id).join(' / ') }}
              </div>
            </button>
          </template>

          <!-- Tree mode -->
          <template v-else>
            <CategoryPickerTreeNode
              v-for="root in roots"
              :key="root.id"
              :catalog="root"
              :categories="categories"
              :selected-id="selectedId"
              @select="select"
            />
          </template>
        </div>
      </div>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, toRef } from 'vue';
import CategoryPickerTreeNode from './CategoryPickerTreeNode.vue';
import { useCategoryBreadcrumb } from '../composables/useCategoryBreadcrumb';

const props = defineProps({
  show: { type: Boolean, default: false },
  categories: { type: Array, default: () => [] },
  selectedId: { type: [Number, String, null], default: null },
  title: { type: String, default: 'Выберите категорию' },
  emptyText: { type: String, default: 'Категории не найдены' },
});

const emit = defineEmits(['select', 'close']);

const search = ref('');

watch(() => props.show, (open) => {
  if (open) search.value = '';
});

const roots = computed(() => props.categories.filter((c) => !c.parent_id));

const { breadcrumbParts } = useCategoryBreadcrumb(toRef(props, 'categories'));

const searchResults = computed(() => {
  const q = search.value.trim().toLowerCase();
  if (!q) return [];
  return props.categories
    .filter((c) => c.name?.toLowerCase().includes(q))
    .slice(0, 200);
});

const select = (catalog) => {
  emit('select', catalog);
};
</script>
