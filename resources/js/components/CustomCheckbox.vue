<template>
  <label class="inline-flex items-center cursor-pointer select-none" :title="title" @click.stop>
    <input
      type="checkbox"
      :checked="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
      class="peer sr-only"
    >
    <span
      class="w-3.5 h-3.5 flex-shrink-0 rounded border flex items-center justify-center transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-offset-1 peer-focus-visible:ring-gray-400 dark:peer-focus-visible:ring-offset-gray-800"
      :class="modelValue ? '' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-500 hover:border-gray-400 dark:hover:border-gray-400'"
      :style="modelValue ? { backgroundColor: themeColor, borderColor: themeColor } : {}"
    >
      <svg v-if="modelValue" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
      </svg>
    </span>
    <span v-if="label" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ label }}</span>
  </label>
</template>

<script setup>
import { useTheme } from '../composables/useTheme';

defineProps({
  modelValue: Boolean,
  label: String,
  title: String,
});

defineEmits(['update:modelValue']);

const { themeColor } = useTheme();
</script>
