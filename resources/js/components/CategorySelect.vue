<template>
  <div class="relative">
    <div class="flex gap-2">
      <div class="relative flex-1">
        <input
          ref="inputEl"
          v-model="query"
          @focus="onFocus"
          @input="onInput"
          @blur="onBlur"
          @keydown.escape="close"
          @keydown.down.prevent="moveHighlight(1)"
          @keydown.up.prevent="moveHighlight(-1)"
          @keydown.enter.prevent="chooseHighlighted"
          type="text"
          autocomplete="off"
          :placeholder="placeholder"
          class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
          :class="{ 'pr-9': clearable && modelValue != null }"
        >

        <button
          v-if="clearable && modelValue != null"
          type="button"
          @mousedown.prevent="clear"
          class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
          title="Очистить"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div
          v-if="open"
          class="absolute z-20 mt-1 w-full max-h-72 overflow-y-auto bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg"
        >
          <div v-if="results.length === 0" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
            Ничего не найдено
          </div>
          <button
            v-for="(cat, idx) in results"
            :key="cat.id"
            type="button"
            @mousedown.prevent="choose(cat)"
            class="w-full text-left px-4 py-2 text-sm border-b border-gray-100 dark:border-gray-600 last:border-0 transition-colors"
            :class="idx === highlightedIndex ? 'bg-gray-100 dark:bg-gray-600' : 'hover:bg-gray-50 dark:hover:bg-gray-600'"
          >
            <div class="font-medium" :class="cat.id === modelValue ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-white'">
              {{ cat.name }}
            </div>
            <div v-if="breadcrumbParts(cat.id).length > 1" class="text-xs text-gray-500 dark:text-gray-400 truncate">
              {{ breadcrumbParts(cat.id).join(' / ') }}
            </div>
          </button>
        </div>
      </div>

      <button
        type="button"
        :title="pickerTitle"
        @click="modalOpen = true"
        class="shrink-0 px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-300 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
      </button>
    </div>

    <CategoryTreePickerModal
      :show="modalOpen"
      :categories="categories"
      :selected-id="modelValue"
      :title="pickerTitle"
      :empty-text="emptyText"
      @select="chooseFromModal"
      @close="modalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, toRef } from 'vue';
import CategoryTreePickerModal from './CategoryTreePickerModal.vue';
import { useCategoryBreadcrumb } from '../composables/useCategoryBreadcrumb';
import { searchWords, matchesAllWords } from '../utils/searchWords';

const props = defineProps({
  modelValue: { type: [Number, String, null], default: null },
  categories: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Поиск категории...' },
  pickerTitle: { type: String, default: 'Выберите категорию' },
  emptyText: { type: String, default: 'Категории не найдены' },
  clearable: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const inputEl = ref(null);
const query = ref('');
const open = ref(false);
const modalOpen = ref(false);
const highlightedIndex = ref(-1);

const { breadcrumbParts, breadcrumbLabel } = useCategoryBreadcrumb(toRef(props, 'categories'));

const syncQueryToSelection = () => {
  query.value = props.modelValue != null ? breadcrumbLabel(props.modelValue) : '';
};

watch(() => props.modelValue, syncQueryToSelection, { immediate: true });
// The label depends on `categories`, which may still be loading when the
// form first renders (e.g. editing an existing product).
watch(() => props.categories, syncQueryToSelection);

const results = computed(() => {
  const q = query.value.trim().toLowerCase();
  const list = props.categories;

  const words = searchWords(q);

  if (!words.length) {
    return list.slice(0, 50);
  }

  const starts = [];
  const nameMatches = [];
  const pathMatches = [];

  for (const cat of list) {
    const name = (cat.name || '').toLowerCase();
    if (name.startsWith(q)) {
      starts.push(cat);
    } else if (matchesAllWords(name, words)) {
      nameMatches.push(cat);
    } else if (matchesAllWords(breadcrumbLabel(cat.id), words)) {
      pathMatches.push(cat);
    }
  }

  return [...starts, ...nameMatches, ...pathMatches].slice(0, 50);
});

const openDropdown = () => {
  open.value = true;
  highlightedIndex.value = -1;
};

const onFocus = () => {
  openDropdown();
  nextTick(() => inputEl.value?.select());
};

const onInput = () => {
  openDropdown();
};

const onBlur = () => {
  // Let a click on a result register (mousedown.prevent) before we close.
  setTimeout(() => {
    open.value = false;
    syncQueryToSelection();
  }, 150);
};

const close = () => {
  open.value = false;
  inputEl.value?.blur();
};

const choose = (cat) => {
  emit('update:modelValue', cat.id);
  query.value = breadcrumbLabel(cat.id);
  open.value = false;
  inputEl.value?.blur();
};

const clear = () => {
  emit('update:modelValue', null);
  query.value = '';
  open.value = false;
};

const chooseFromModal = (cat) => {
  choose(cat);
  modalOpen.value = false;
};

const moveHighlight = (delta) => {
  if (!open.value) {
    openDropdown();
    return;
  }
  const max = results.value.length - 1;
  if (max < 0) return;
  let next = highlightedIndex.value + delta;
  if (next < 0) next = max;
  if (next > max) next = 0;
  highlightedIndex.value = next;
};

const chooseHighlighted = () => {
  const cat = results.value[highlightedIndex.value];
  if (cat) {
    choose(cat);
  }
};
</script>
