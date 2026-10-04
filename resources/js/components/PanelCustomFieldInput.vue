<template>
  <ToggleSwitch v-if="field.type === 'boolean'" v-model="value" :theme-color="themeColor" :label="value ? 'Да' : 'Нет'" />

  <InfoBlockTableField v-else-if="field.type === 'table'" v-model="value" :rows="3" :cols="3" />

  <!-- file (component handles its own multiplicity) -->
  <InfoBlockFileUpload v-else-if="field.type === 'file'" v-model="value" :is-multiple="field.is_multiple" />

  <template v-else-if="!field.is_multiple">
    <ImageUpload v-if="field.type === 'image'" v-model="value" />
    <TinyMCEEditor v-else-if="field.type === 'html'" v-model="value" />
    <textarea v-else-if="field.type === 'text'" v-model="value" rows="2" :class="inputClass"></textarea>
    <input v-else v-model="value" :type="htmlInputType" :class="inputClass">
  </template>

  <div v-else class="space-y-2">
    <div v-for="(item, index) in items" :key="index" class="flex gap-2 items-start">
      <div class="flex-1">
        <ImageUpload v-if="field.type === 'image'" :model-value="item" @update:model-value="setItem(index, $event)" />
        <TinyMCEEditor v-else-if="field.type === 'html'" :model-value="item" @update:model-value="setItem(index, $event)" />
        <textarea v-else-if="field.type === 'text'" :value="item" @input="setItem(index, $event.target.value)" rows="2" :class="inputClass"></textarea>
        <input v-else :value="item" @input="setItem(index, $event.target.value)" :type="htmlInputType" :class="inputClass">
      </div>
      <button type="button" @click="removeItem(index)" class="p-2 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20" title="Удалить значение">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
      </button>
    </div>
    <button type="button" @click="addItem" class="px-3 py-1.5 text-sm border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded hover:border-blue-400 hover:text-blue-600">
      + Добавить значение
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTheme } from '../composables/useTheme';
import ImageUpload from './ImageUpload.vue';
import TinyMCEEditor from './TinyMCEEditor.vue';
import InfoBlockFileUpload from './InfoBlockFileUpload.vue';
import InfoBlockTableField from './InfoBlockTableField.vue';
import ToggleSwitch from './ToggleSwitch.vue';

/**
 * Value editor for a custom project field ("Пользовательское свойство"),
 * chosen by the field type and multiplicity.
 */
const props = defineProps({
  field: { type: Object, required: true },
  modelValue: { default: null },
});

const emit = defineEmits(['update:modelValue']);

const { themeColor } = useTheme();

const inputClass = 'w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white';

const htmlInputType = computed(() => ({ number: 'number', email: 'email', phone: 'tel' }[props.field.type] || 'text'));

const value = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});

const items = computed(() => {
  const v = props.modelValue;
  if (Array.isArray(v)) return v;
  return v === null || v === '' || v === undefined ? [] : [v];
});

const setItem = (index, itemValue) => {
  const next = [...items.value];
  next[index] = itemValue;
  emit('update:modelValue', next);
};

const removeItem = (index) => {
  emit('update:modelValue', items.value.filter((_, i) => i !== index));
};

const addItem = () => {
  emit('update:modelValue', [...items.value, props.field.type === 'number' ? null : '']);
};
</script>
