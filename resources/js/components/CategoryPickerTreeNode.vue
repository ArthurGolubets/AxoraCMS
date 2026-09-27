<template>
  <div>
    <div
      class="group flex items-center py-2 px-2 rounded-md cursor-pointer transition-colors"
      :class="isSelected
        ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
        : 'hover:bg-gray-100 dark:hover:bg-gray-700/60 text-gray-800 dark:text-gray-200'"
      :style="{ paddingLeft: `${level * 18 + 8}px` }"
      @click="$emit('select', catalog)"
    >
      <button
        v-if="children.length"
        type="button"
        @click.stop="expanded = !expanded"
        class="mr-1 w-5 h-5 flex items-center justify-center shrink-0 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
      >
        <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-90': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
      <span v-else class="w-5 mr-1 shrink-0"></span>

      <svg class="w-4 h-4 mr-2 shrink-0" :class="catalog.is_active === false ? 'text-gray-400' : 'text-yellow-500 dark:text-yellow-400'" fill="currentColor" viewBox="0 0 20 20">
        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
      </svg>

      <span class="text-sm truncate" :class="{ 'font-semibold': isSelected, 'line-through text-gray-400 dark:text-gray-500': catalog.is_active === false }">
        {{ catalog.name }}
      </span>

      <svg v-if="isSelected" class="w-4 h-4 ml-2 text-blue-600 dark:text-blue-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
      </svg>
    </div>

    <div v-if="expanded && children.length">
      <CategoryPickerTreeNode
        v-for="child in children"
        :key="child.id"
        :catalog="child"
        :categories="categories"
        :level="level + 1"
        :selected-id="selectedId"
        @select="$emit('select', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  catalog: { type: Object, required: true },
  categories: { type: Array, required: true },
  level: { type: Number, default: 0 },
  selectedId: { type: [Number, String, null], default: null },
});

defineEmits(['select']);

const children = computed(() =>
  props.categories.filter((c) => c.parent_id === props.catalog.id)
);

const isSelected = computed(() => props.selectedId != null && props.catalog.id === props.selectedId);

// Auto-expand branches that contain the currently selected category.
const containsSelected = (catalog) => {
  if (props.selectedId == null) return false;
  const stack = [catalog.id];
  while (stack.length) {
    const id = stack.pop();
    const kids = props.categories.filter((c) => c.parent_id === id);
    for (const kid of kids) {
      if (kid.id === props.selectedId) return true;
      stack.push(kid.id);
    }
  }
  return false;
};

const expanded = ref(isSelected.value || containsSelected(props.catalog));
</script>
