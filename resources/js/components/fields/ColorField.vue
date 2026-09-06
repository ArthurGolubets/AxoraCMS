<template>
  <div>
    <!-- Single -->
    <div v-if="!isMultiple" class="flex items-center gap-3">
      <span
        class="inline-block w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-500 shrink-0"
        :style="{ backgroundColor: isValidColor(single) ? single : 'transparent' }"
      ></span>
      <input
        type="color"
        :value="toHex(single)"
        @input="setSingle($event.target.value)"
        class="w-12 h-10 p-1 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg cursor-pointer shrink-0"
      >
      <input
        type="text"
        :value="single"
        @input="setSingle($event.target.value)"
        placeholder="#0d6efd или rgba(...)"
        class="flex-1 px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-gray-900 dark:text-white text-sm font-mono"
      >
      <button v-if="single" type="button" @click="setSingle('')"
        class="px-3 py-2 text-red-600 hover:text-red-700 dark:text-red-400 shrink-0">✕</button>
    </div>

    <!-- Multiple -->
    <div v-else class="space-y-2">
      <div v-for="(color, idx) in list" :key="idx" class="flex items-center gap-3">
        <span
          class="inline-block w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-500 shrink-0"
          :style="{ backgroundColor: isValidColor(color) ? color : 'transparent' }"
        ></span>
        <input
          type="color"
          :value="toHex(color)"
          @input="setAt(idx, $event.target.value)"
          class="w-12 h-10 p-1 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg cursor-pointer shrink-0"
        >
        <input
          type="text"
          :value="color"
          @input="setAt(idx, $event.target.value)"
          placeholder="#0d6efd или rgba(...)"
          class="flex-1 px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-gray-900 dark:text-white text-sm font-mono"
        >
        <button type="button" @click="removeAt(idx)"
          class="px-3 py-2 text-red-600 hover:text-red-700 dark:text-red-400 shrink-0">✕</button>
      </div>
      <button type="button" @click="add"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">
        + Добавить цвет
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Array], default: '' },
  isMultiple: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const single = computed(() => (typeof props.modelValue === 'string' ? props.modelValue : ''));
const list = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : (props.modelValue ? [props.modelValue] : [])));

function isValidColor(v) {
  if (!v || typeof v !== 'string') return false;
  return /^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test(v.trim()) || /^(rgb|hsl)a?\(/i.test(v.trim());
}
function toHex(v) {
  return /^#([0-9a-f]{6})$/i.test((v || '').trim()) ? v.trim() : '#000000';
}
function setSingle(v) { emit('update:modelValue', v); }
function setAt(idx, v) {
  const next = [...list.value];
  next[idx] = v;
  emit('update:modelValue', next);
}
function removeAt(idx) {
  const next = [...list.value];
  next.splice(idx, 1);
  emit('update:modelValue', next);
}
function add() { emit('update:modelValue', [...list.value, '#000000']); }
</script>
