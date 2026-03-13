<template>
  <div>
    <div
      class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-200 dark:border-gray-700"
      :style="{ paddingLeft: `${depth * 32 + 16}px` }"
      draggable="true"
      @dragstart="$emit('drag-start', item)"
      @dragover="$emit('drag-over', $event)"
      @drop.prevent="$emit('drop', item)"
    >
      <!-- Drag handle -->
      <div class="flex flex-col gap-0.5 cursor-move text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 flex-shrink-0">
        <div class="w-1.5 h-1.5 bg-current rounded-full"></div>
        <div class="w-1.5 h-1.5 bg-current rounded-full"></div>
        <div class="w-1.5 h-1.5 bg-current rounded-full"></div>
      </div>

      <!-- Item info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.title }}</span>
          <span v-if="!item.is_active" class="px-2 py-0.5 bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded text-xs flex-shrink-0">Неактивен</span>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ item.url || item.route || 'Нет ссылки' }}</div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-1 flex-shrink-0">
        <!-- Move buttons -->
        <button
          @click="$emit('move-up', item, siblings)"
          :disabled="index === 0"
          class="p-2 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed rounded hover:bg-gray-100 dark:hover:bg-gray-600/50 transition-colors"
          title="Переместить вверх"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
          </svg>
        </button>
        <button
          @click="$emit('move-down', item, siblings)"
          :disabled="index === siblings.length - 1"
          class="p-2 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed rounded hover:bg-gray-100 dark:hover:bg-gray-600/50 transition-colors"
          title="Переместить вниз"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <!-- Action buttons -->
        <button
          @click="$emit('add-child', item)"
          class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded transition-colors"
          title="Добавить подпункт"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
        </button>
        <button
          @click="$emit('edit', item)"
          class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors"
          title="Редактировать"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </button>
        <button
          @click="$emit('toggle-active', item)"
          class="p-2 rounded transition-colors"
          :class="item.is_active ? 'text-yellow-600 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20' : 'text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-600/50'"
          :title="item.is_active ? 'Деактивировать' : 'Активировать'"
        >
          <svg v-if="item.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
          </svg>
        </button>
        <button
          @click="$emit('delete', item)"
          class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
          title="Удалить"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Children -->
    <div v-if="item.children && item.children.length > 0">
      <MenuTreeItem
        v-for="(child, childIndex) in item.children"
        :key="child.id"
        :item="child"
        :index="childIndex"
        :siblings="item.children"
        :depth="depth + 1"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
        @toggle-active="$emit('toggle-active', $event)"
        @move-up="$emit('move-up', $event, item.children)"
        @move-down="$emit('move-down', $event, item.children)"
        @add-child="$emit('add-child', $event)"
        @drag-start="$emit('drag-start', $event)"
        @drag-over="$emit('drag-over', $event)"
        @drop="$emit('drop', $event)"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({
  item: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    required: true
  },
  siblings: {
    type: Array,
    required: true
  },
  depth: {
    type: Number,
    default: 0
  }
});

defineEmits(['edit', 'delete', 'toggle-active', 'move-up', 'move-down', 'add-child', 'drag-start', 'drag-over', 'drop']);
</script>
