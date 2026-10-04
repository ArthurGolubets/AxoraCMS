<template>
  <draggable
    :list="items"
    item-key="id"
    group="menu-items"
    handle=".drag-handle"
    ghost-class="menu-tree-ghost"
    :class="{ 'min-h-[10px]': dragging && depth > 0 }"
    @start="$emit('drag-state', true)"
    @end="$emit('drag-state', false)"
    @change="$emit('changed')"
  >
    <template #item="{ element: item }">
      <div>
        <div
          class="flex items-center gap-3 py-3 pr-4 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          :style="{ paddingLeft: `${depth * 28 + 16}px` }"
        >
          <span class="drag-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0" title="Перетащите, чтобы изменить порядок или вложенность">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
          </span>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium truncate" :class="item.is_active ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'">{{ item.title }}</span>
              <span v-if="!item.is_active" class="px-2 py-0.5 bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded text-xs flex-shrink-0">Неактивен</span>
              <span v-if="item.target === '_blank'" class="px-1.5 py-0.5 text-[11px] rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex-shrink-0">новое окно</span>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5 font-mono">{{ item.url || item.route || 'Нет ссылки' }}</div>
          </div>

          <ActionMenu :items="actions(item)" />
        </div>

        <MenuTreeList
          :items="item.children"
          :depth="depth + 1"
          :dragging="dragging"
          @drag-state="$emit('drag-state', $event)"
          @changed="$emit('changed')"
          @edit="$emit('edit', $event)"
          @delete="$emit('delete', $event)"
          @toggle-active="$emit('toggle-active', $event)"
          @add-child="$emit('add-child', $event)"
        />
      </div>
    </template>
  </draggable>
</template>

<script setup>
import draggable from 'vuedraggable';
import ActionMenu from '../ActionMenu.vue';

/**
 * Recursive, drag & drop sortable list of menu items. Items can be moved
 * within a level and between levels; "changed" fires after every move and
 * the root component persists the new order.
 */
defineProps({
  items: { type: Array, required: true },
  depth: { type: Number, default: 0 },
  // While an item is being dragged, empty child lists get a drop zone.
  dragging: { type: Boolean, default: false },
});

const emit = defineEmits(['changed', 'drag-state', 'edit', 'delete', 'toggle-active', 'add-child']);

const actions = (item) => [
  { label: 'Добавить подпункт', icon: 'M12 4v16m8-8H4', action: () => emit('add-child', item) },
  { label: 'Редактировать', icon: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z', action: () => emit('edit', item) },
  {
    label: item.is_active ? 'Деактивировать' : 'Активировать',
    icon: item.is_active ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : 'M5 13l4 4L19 7',
    action: () => emit('toggle-active', item),
  },
  { divider: true },
  { label: 'Удалить', danger: true, icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', action: () => emit('delete', item) },
];
</script>

<style scoped>
:deep(.menu-tree-ghost) {
  opacity: 0.4;
}
</style>
