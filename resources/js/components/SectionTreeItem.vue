<template>
  <div>
    <!-- Section Node -->
    <div
      class="group relative flex items-center py-2.5 px-3 rounded-md transition-all duration-150"
      :class="[
        'hover:bg-blue-50 dark:hover:bg-gray-700/50',
        'border-l-2 border-transparent hover:border-blue-500 dark:hover:border-blue-400'
      ]"
      :style="{ paddingLeft: level > 0 ? `${level * 20 + 12}px` : '12px' }"
    >
      <!-- Expand/Collapse Button -->
      <button
        v-if="hasChildren"
        @click="toggleExpand"
        class="mr-2 w-5 h-5 flex items-center justify-center rounded transition-all"
        :class="[
          'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
          'hover:bg-gray-200 dark:hover:bg-gray-600'
        ]"
        :title="isExpanded ? 'Свернуть' : 'Развернуть'"
      >
        <svg
          class="w-3.5 h-3.5 transition-transform duration-200"
          :class="{ 'rotate-90': isExpanded }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          stroke-width="2.5"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
      <div v-else class="w-5 mr-2"></div>

      <!-- Folder Icon with Active/Inactive State -->
      <div class="relative mr-3 flex-shrink-0">
        <svg
          class="w-5 h-5 transition-colors"
          :class="section.is_active ? 'text-yellow-500 dark:text-yellow-400' : 'text-gray-400 dark:text-gray-500'"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
        </svg>
        <!-- Inactive indicator -->
        <div
          v-if="!section.is_active"
          class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-red-500 dark:bg-red-600 rounded-full border-2 border-white dark:border-gray-800"
          title="Неактивен"
        ></div>
      </div>

      <!-- Section Name and Code -->
      <div class="flex-1 min-w-0 mr-3">
        <div class="flex items-center gap-2">
          <span
            class="font-medium text-sm truncate"
            :class="section.is_active ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500 line-through'"
          >
            {{ section.name }}
          </span>
        </div>
        <div class="flex items-center gap-3 mt-1 text-xs">
          <span
            v-if="section.code"
            class="text-gray-500 dark:text-gray-400 font-mono"
          >
            {{ section.code }}
          </span>
          <span
            v-if="(section.children_count || section.children?.length) > 0"
            class="flex items-center gap-1 text-gray-600 dark:text-gray-400"
          >
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
            </svg>
            <span class="font-medium">{{ section.children_count || section.children?.length }}</span>
          </span>
        </div>
      </div>

      <!-- Actions - Hidden by default, shown on hover -->
      <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
        <!-- Toggle Active Status -->
        <button
          @click.stop="$emit('toggle-active', section)"
          class="p-1.5 rounded-md transition-colors"
          :class="section.is_active
            ? 'text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30'
            : 'text-gray-400 dark:text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700'"
          :title="section.is_active ? 'Деактивировать' : 'Активировать'"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path v-if="section.is_active" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </button>

        <!-- Add Subsection -->
        <button
          @click.stop="$emit('create-subsection', section.id)"
          class="p-1.5 rounded-md transition-colors text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30"
          title="Добавить подраздел"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
          </svg>
        </button>

        <!-- Add Element -->
        <button
          @click.stop="$emit('create-element', section.id)"
          class="p-1.5 rounded-md transition-colors text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30"
          title="Добавить элемент"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
        </button>

        <!-- Separator -->
        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600 mx-1"></div>

        <!-- Edit -->
        <button
          @click.stop="$emit('edit', section)"
          class="p-1.5 rounded-md transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700"
          title="Редактировать"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </button>

        <!-- Delete -->
        <button
          @click.stop="$emit('delete', section)"
          class="p-1.5 rounded-md transition-colors text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30"
          title="Удалить"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Recursive Children and Elements -->
    <div v-if="isExpanded">
      <!-- Child Sections (folders first) -->
      <SectionTreeItem
        v-for="child in section.children"
        :key="`section-${child.id}`"
        :section="child"
        :level="level + 1"
        @create-subsection="$emit('create-subsection', $event)"
        @create-element="$emit('create-element', $event)"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
        @toggle-active="$emit('toggle-active', $event)"
      />

      <!-- Elements in this section (after folders) -->
      <div
        v-for="element in section.elements"
        :key="`element-${element.id}`"
        class="group relative flex items-center py-2.5 px-3 rounded-md transition-all duration-150 hover:bg-gray-100 dark:hover:bg-gray-700/50"
        :style="{ paddingLeft: `${(level + 1) * 20 + 12}px` }"
      >
        <div class="w-5 mr-2"></div>

        <!-- Element Icon -->
        <div class="flex items-center justify-center w-5 h-5 mr-3 rounded bg-gray-200 dark:bg-gray-700">
          <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>

        <!-- Element Name -->
        <div class="flex-1 min-w-0 mr-3">
          <div class="flex items-center gap-2">
            <span
              class="text-sm truncate"
              :class="element.is_active ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500 line-through'"
            >
              {{ element.name }}
            </span>
            <span v-if="!element.is_active" class="px-2 py-0.5 text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
              Неактивен
            </span>
          </div>
        </div>

        <!-- Element Actions -->
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
          <button
            @click.stop="$emit('edit-element', element)"
            class="p-1.5 rounded-md transition-colors text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30"
            title="Редактировать"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>
          <button
            @click.stop="$emit('delete-element', element)"
            class="p-1.5 rounded-md transition-colors text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30"
            title="Удалить"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  section: {
    type: Object,
    required: true
  },
  level: {
    type: Number,
    default: 0
  }
});

defineEmits(['create-subsection', 'create-element', 'edit', 'delete', 'toggle-active', 'edit-element', 'delete-element']);

const isExpanded = ref(true);

const hasChildren = computed(() => {
  return (props.section.children && props.section.children.length > 0) ||
         (props.section.elements && props.section.elements.length > 0);
});

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
};
</script>
