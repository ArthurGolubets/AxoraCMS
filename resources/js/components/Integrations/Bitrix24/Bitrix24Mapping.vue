<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-3">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Слева поле в Битрикс24, справа что в него записать. Пустые значения не отправляются</p>
      </div>
      <ThemeButton variant="secondary" size="sm" @click="addRow">+ Добавить поле</ThemeButton>
    </div>

    <p v-if="missingRequired.length" class="text-sm text-amber-600 dark:text-amber-400">
      Обязательные поля в Битрикс24: {{ missingRequired.map(field => field.label).join(', ') }}. Без них запись может не создаться.
    </p>

    <div v-for="(row, index) in rows" :key="index" class="flex flex-col md:flex-row md:items-center gap-2">
      <select v-model="row.field" @change="onFieldChange(row)" :class="[inputClass, 'md:w-80 md:shrink-0']">
        <option value="" disabled>— Поле Битрикс24 —</option>
        <option v-if="row.field && !fieldOf(row)" :value="row.field">{{ row.field }} (нет на портале)</option>
        <optgroup v-if="standardFields.length" label="Стандартные поля">
          <option v-for="field in standardFields" :key="field.code" :value="field.code">{{ field.label }}{{ field.required ? ' *' : '' }}</option>
        </optgroup>
        <optgroup v-if="customFields.length" label="Пользовательские поля">
          <option v-for="field in customFields" :key="field.code" :value="field.code">{{ field.label }}{{ field.required ? ' *' : '' }}</option>
        </optgroup>
      </select>

      <span class="hidden md:inline text-gray-400">←</span>

      <select v-if="fieldOf(row)?.options.length" v-model="row.value" :class="inputClass">
        <option value="">— Выберите значение —</option>
        <option v-if="row.value && !hasOption(row)" :value="row.value">{{ row.value }}</option>
        <option v-for="option in fieldOf(row).options" :key="option.id" :value="option.id">{{ option.label }}</option>
      </select>
      <input
        v-else
        v-model="row.value"
        type="text"
        :class="inputClass"
        placeholder="Текст и/или метки, например Заказ №#ORDER_ID#"
        @focus="$emit('focus-value', row, $event.target)"
      >

      <button type="button" title="Убрать поле" @click="rows.splice(index, 1)" class="self-end md:self-auto p-2 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <p v-if="!rows.length" class="text-sm text-gray-500 dark:text-gray-400">Поля не выбраны. Нажмите «Добавить поле».</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ThemeButton from '../../ThemeButton.vue';

const props = defineProps({
  title: { type: String, required: true },
  rows: { type: Array, required: true },
  fields: { type: Array, default: () => [] },
});

defineEmits(['focus-value']);

const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const standardFields = computed(() => props.fields.filter(field => !field.custom));
const customFields = computed(() => props.fields.filter(field => field.custom));
const missingRequired = computed(() => props.fields.filter(field => field.required && !props.rows.some(row => row.field === field.code)));

const fieldOf = (row) => props.fields.find(field => field.code === row.field);
const hasOption = (row) => fieldOf(row)?.options.some(option => option.id === String(row.value));

const addRow = () => props.rows.push({ field: '', value: '' });

// A label left from another field would be sent as a list value the portal does not know.
const onFieldChange = (row) => {
  if (fieldOf(row)?.options.length) {
    row.value = '';
  }
};
</script>
