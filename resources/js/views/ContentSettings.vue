<template>
  <div class="container mx-auto px-4 py-8">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Настройки контента</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Характеристики и параметры каталога товаров</p>
    </div>

    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-6">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          @click="activeTab = tab.id"
          :class="['py-3 px-1 border-b-2 text-sm font-medium transition-colors', activeTab === tab.id ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300']"
        >
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <CatalogSettings v-if="activeTab === 'catalog'" />
    <CharacteristicDefinitions v-else />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import CharacteristicDefinitions from '../components/CharacteristicDefinitions.vue';
import CatalogSettings from '../components/CatalogSettings.vue';

const tabs = [
  { id: 'catalog', label: 'Каталог и товары' },
  { id: 'characteristics', label: 'Характеристики' },
];
const activeTab = ref('catalog');
</script>
