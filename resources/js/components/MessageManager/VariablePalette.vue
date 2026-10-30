<template>
  <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/20 p-3">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
      <div>
        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ title }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Нажмите на название — в текст вставится метка (например <code class="px-1 rounded bg-gray-100 dark:bg-gray-700">#ORDER_ID#</code>).
          При отправке она заменится на реальные данные.
        </p>
      </div>
      <button type="button" @click="detailed = !detailed" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 whitespace-nowrap">
        {{ detailed ? 'Показать кратко' : 'Что означает каждая' }}
      </button>
    </div>

    <div class="space-y-3">
      <div v-for="group in groups" :key="group.name">
        <p class="text-[11px] uppercase tracking-wide font-semibold text-gray-400 dark:text-gray-500 mb-1">{{ group.name }}</p>

        <div v-if="!detailed" class="flex flex-wrap gap-1.5">
          <button
            v-for="variable in group.items"
            :key="variable.code"
            type="button"
            :title="hint(variable)"
            @click="$emit('insert', `#${variable.code}#`)"
            class="px-2.5 py-1 text-xs rounded-full bg-white border border-gray-300 text-gray-700 hover:border-blue-400 hover:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:border-blue-400 transition-colors"
          >{{ shortLabel(variable) }}</button>
        </div>

        <div v-else class="rounded-md border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
          <button
            v-for="variable in group.items"
            :key="variable.code"
            type="button"
            @click="$emit('insert', `#${variable.code}#`)"
            class="w-full grid grid-cols-1 sm:grid-cols-[1.4fr_1fr_1.2fr] gap-x-3 gap-y-0.5 px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-gray-700/50"
          >
            <span class="text-gray-900 dark:text-gray-100">{{ variable.label }}</span>
            <span class="font-mono text-gray-500 dark:text-gray-400">#{{ variable.code }}#</span>
            <span class="text-gray-500 dark:text-gray-400 truncate">{{ variable.sample ? `например: ${oneLine(variable.sample)}` : '' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  variables: { type: Array, default: () => [] },
  title: { type: String, default: 'Вставить данные в текст' },
});

defineEmits(['insert']);

const detailed = ref(false);

// Technical variables go last so the everyday ones are not buried.
const groups = computed(() => {
  const byName = {};
  props.variables.forEach(variable => {
    (byName[variable.group || 'Данные события'] ||= { name: variable.group || 'Данные события', technical: !!variable.technical, items: [] }).items.push(variable);
  });
  return Object.values(byName).sort((a, b) => Number(a.technical) - Number(b.technical));
});

const oneLine = (text) => String(text).replace(/\s*\n\s*/g, ' · ');

// "Список товаров (в письме — таблицей)" style notes stay in the hint, the chip itself stays short.
const shortLabel = (variable) => variable.label.replace(/\s*\(.*\)\s*$/, '');

const hint = (variable) => {
  const example = variable.sample ? `\nНапример: ${oneLine(variable.sample)}` : '';
  return `${variable.label}\nМетка: #${variable.code}#${example}`;
};
</script>
