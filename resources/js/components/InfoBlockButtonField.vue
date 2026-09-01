<template>
  <div class="space-y-3">
    <div>
      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Текст кнопки</label>
      <input
          v-model="localValue.text"
          @input="updateValue"
          type="text"
          :required="required"
          placeholder="Например: Узнать подробнее"
          class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
      >
    </div>
    <div>
      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Ссылка (URL)</label>
      <input
          v-model="localValue.url"
          @input="updateValue"
          type="text"
          :required="required"
          placeholder="Например: /catalog/products"
          class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
      >
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ text: '', url: '' })
  },
  required: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

const localValue = ref({
  text: props.modelValue?.text || '',
  url: props.modelValue?.url || ''
});

watch(() => props.modelValue, (newValue) => {
  if (newValue && typeof newValue === 'object') {
    localValue.value = {
      text: newValue.text || '',
      url: newValue.url || ''
    };
  }
}, { deep: true });

const updateValue = () => {
  emit('update:modelValue', { ...localValue.value });
};
</script>
