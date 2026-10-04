<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Values List -->
    <div v-if="localValues.length > 0" class="space-y-2 mb-3">
      <div v-for="(value, index) in localValues" :key="index" class="flex items-center gap-2">
        <!-- Input based on type -->
        <input
          v-if="['string', 'email', 'phone'].includes(fieldType)"
          :value="value"
          @input="updateValue(index, $event.target.value)"
          :type="{ email: 'email', phone: 'tel' }[fieldType] || 'text'"
          :placeholder="`Значение ${index + 1}`"
          class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
        >
        <textarea
          v-else-if="fieldType === 'text'"
          :value="value"
          @input="updateValue(index, $event.target.value)"
          :placeholder="`Значение ${index + 1}`"
          rows="2"
          class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
        ></textarea>
        <input
          v-else-if="fieldType === 'number'"
          :value="value"
          @input="updateValue(index, parseFloat($event.target.value) || 0)"
          type="number"
          :placeholder="`Значение ${index + 1}`"
          class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
        >
        <input
          v-else-if="fieldType === 'double'"
          :value="value"
          @input="updateValue(index, parseFloat($event.target.value) || 0)"
          type="number"
          step="0.01"
          :placeholder="`Значение ${index + 1}`"
          class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
        >
        <input
          v-else-if="fieldType === 'date'"
          :value="value"
          @input="updateValue(index, $event.target.value)"
          type="date"
          class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
        >
        <input
          v-else-if="fieldType === 'datetime'"
          :value="value"
          @input="updateValue(index, $event.target.value)"
          type="datetime-local"
          class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
        >
        <input
          v-else
          :value="value"
          @input="updateValue(index, $event.target.value)"
          type="text"
          :placeholder="`Значение ${index + 1}`"
          class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
        >

        <!-- Remove Button -->
        <button
          @click="removeValue(index)"
          type="button"
          class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
          title="Удалить"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Add Button -->
    <button
      @click.prevent="addValue"
      type="button"
      class="w-full flex items-center justify-center gap-2 px-4 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-400 hover:border-blue-500 hover:text-blue-500 dark:hover:border-blue-400 dark:hover:text-blue-400 transition"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      <span class="text-sm font-medium">Добавить значение</span>
    </button>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: [Array, String],
  label: String,
  required: Boolean,
  fieldType: {
    type: String,
    default: 'string'
  }
});

const emit = defineEmits(['update:modelValue']);

const localValues = ref([]);
let isInternalUpdate = false;

// Sync from modelValue to localValues (one-way)
watch(() => props.modelValue, (newVal) => {
  // Don't update if this change came from us
  if (isInternalUpdate) {
    return;
  }

  if (Array.isArray(newVal)) {
    localValues.value = [...newVal];
  } else if (newVal !== null && newVal !== undefined && newVal !== '') {
    localValues.value = [newVal];
  } else {
    localValues.value = [];
  }
}, { immediate: true });

// Emit changes when localValues change
const emitChanges = () => {
  const filtered = localValues.value.filter(v => v !== '' && v !== null && v !== undefined);
  isInternalUpdate = true;
  emit('update:modelValue', filtered);
  // Reset flag on next tick
  setTimeout(() => {
    isInternalUpdate = false;
  }, 0);
};

const updateValue = (index, newValue) => {
  localValues.value[index] = newValue;
  emitChanges();
};

const addValue = () => {
  console.log('addValue called! fieldType:', props.fieldType);
  console.log('localValues before:', localValues.value);

  if (props.fieldType === 'number' || props.fieldType === 'double') {
    localValues.value.push(0);
  } else if (props.fieldType === 'bool') {
    localValues.value.push(false);
  } else {
    localValues.value.push('');
  }

  console.log('localValues after push:', localValues.value);
  emitChanges();
  console.log('emitChanges called');
};

const removeValue = (index) => {
  localValues.value.splice(index, 1);
  emitChanges();
};
</script>
