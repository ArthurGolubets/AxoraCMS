<template>
  <div class="space-y-3">
    <div v-if="!tableData.length" class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg p-4">
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Настройте размер таблицы</p>
      <div class="grid grid-cols-2 gap-3 mb-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Строк</label>
          <input
              v-model.number="tableRows"
              type="number"
              min="1"
              max="20"
              placeholder="3"
              class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
          >
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Колонок</label>
          <input
              v-model.number="tableCols"
              type="number"
              min="1"
              max="20"
              placeholder="3"
              class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
          >
        </div>
      </div>
      <button
          type="button"
          @click="generateTable"
          class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        Создать таблицу
      </button>
    </div>

    <div v-else class="space-y-3">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
          Таблица {{ tableData.length }}x{{ tableData[0]?.length || 0 }}
        </span>
        <button
            type="button"
            @click="clearTable"
            class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
        >
          Очистить
        </button>
      </div>

      <div class="overflow-x-auto border border-gray-300 dark:border-gray-600 rounded-lg">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(row, rowIndex) in tableData" :key="rowIndex">
              <td
                  v-for="(cell, colIndex) in row"
                  :key="colIndex"
                  class="p-0"
              >
                <input
                    v-model="tableData[rowIndex][colIndex]"
                    @input="updateValue"
                    type="text"
                    class="w-full px-3 py-2 bg-white dark:bg-gray-800 border-0 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                    :placeholder="`R${rowIndex + 1}C${colIndex + 1}`"
                >
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-center space-x-2">
        <button
            type="button"
            @click="addRow"
            class="flex-1 px-3 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700 transition"
        >
          + Добавить строку
        </button>
        <button
            type="button"
            @click="addColumn"
            class="flex-1 px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition"
        >
          + Добавить колонку
        </button>
      </div>

      <div v-if="tableData.length > 1 || tableData[0]?.length > 1" class="flex items-center space-x-2">
        <button
            v-if="tableData.length > 1"
            type="button"
            @click="removeRow"
            class="flex-1 px-3 py-2 text-sm bg-red-600 text-white rounded hover:bg-red-700 transition"
        >
          - Удалить строку
        </button>
        <button
            v-if="tableData[0]?.length > 1"
            type="button"
            @click="removeColumn"
            class="flex-1 px-3 py-2 text-sm bg-red-600 text-white rounded hover:bg-red-700 transition"
        >
          - Удалить колонку
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  required: {
    type: Boolean,
    default: false
  },
  rows: {
    type: Number,
    default: 3
  },
  cols: {
    type: Number,
    default: 3
  }
});

const emit = defineEmits(['update:modelValue']);

const tableRows = ref(props.rows || 3);
const tableCols = ref(props.cols || 3);
const tableData = ref([]);

// Initialize table from modelValue
if (props.modelValue && Array.isArray(props.modelValue) && props.modelValue.length > 0) {
  tableData.value = JSON.parse(JSON.stringify(props.modelValue));
}

watch(() => props.modelValue, (newValue) => {
  if (newValue && Array.isArray(newValue) && newValue.length > 0) {
    tableData.value = JSON.parse(JSON.stringify(newValue));
  }
}, { deep: true });

const generateTable = () => {
  const rows = tableRows.value || 3;
  const cols = tableCols.value || 3;
  tableData.value = Array.from({ length: rows }, () => Array.from({ length: cols }, () => ''));
  updateValue();
};

const clearTable = () => {
  tableData.value = [];
  tableRows.value = props.rows || 3;
  tableCols.value = props.cols || 3;
  updateValue();
};

const addRow = () => {
  const cols = tableData.value[0]?.length || 0;
  tableData.value.push(Array.from({ length: cols }, () => ''));
  updateValue();
};

const addColumn = () => {
  tableData.value.forEach(row => row.push(''));
  updateValue();
};

const removeRow = () => {
  if (tableData.value.length > 1) {
    tableData.value.pop();
    updateValue();
  }
};

const removeColumn = () => {
  if (tableData.value[0]?.length > 1) {
    tableData.value.forEach(row => row.pop());
    updateValue();
  }
};

const updateValue = () => {
  emit('update:modelValue', JSON.parse(JSON.stringify(tableData.value)));
};
</script>
