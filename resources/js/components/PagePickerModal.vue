<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" @click.self="$emit('close')">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-xl flex flex-col max-h-[80vh]">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Выберите страницу</h3>
            <button type="button" @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <input
            ref="searchInput"
            v-model="search"
            @input="onSearch"
            type="text"
            placeholder="Поиск по названию или адресу..."
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm"
          >
        </div>

        <div class="flex-1 overflow-y-auto p-2">
          <div v-if="loading" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">Загрузка...</div>
          <div v-else-if="pages.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">Страницы не найдены</div>
          <button
            v-for="page in pages"
            v-else
            :key="page.id"
            type="button"
            @click="$emit('select', page)"
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-md text-left hover:bg-gray-100 dark:hover:bg-gray-700/60 transition-colors"
          >
            <div class="min-w-0">
              <div class="text-sm font-medium truncate" :class="page.is_active ? 'text-gray-900 dark:text-white' : 'text-gray-400'">{{ page.title }}</div>
              <div class="text-xs font-mono text-gray-500 dark:text-gray-400 truncate">{{ page.public_url }}</div>
            </div>
            <span v-if="!page.is_active" class="shrink-0 px-1.5 py-0.5 text-[11px] rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">неактивна</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';

/**
 * Searchable list of site pages (module "Страницы и SEO"); emits the chosen page.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
});

defineEmits(['select', 'close']);

const pages = ref([]);
const loading = ref(false);
const search = ref('');
const searchInput = ref(null);
let timer = null;

const load = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams({ per_page: 50, sort_by: 'title', sort_order: 'asc' });
    if (search.value) params.append('search', search.value);
    const response = await fetch(`/admin/api/pages?${params}`, { headers: { Accept: 'application/json' } });
    if (response.ok) {
      const data = await response.json();
      pages.value = data.data || [];
    } else {
      pages.value = [];
    }
  } catch (e) {
    pages.value = [];
  } finally {
    loading.value = false;
  }
};

const onSearch = () => {
  clearTimeout(timer);
  timer = setTimeout(load, 300);
};

watch(() => props.show, (open) => {
  if (!open) return;
  search.value = '';
  load();
  nextTick(() => searchInput.value?.focus());
});
</script>
