<template>
  <div class="container mx-auto px-4 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Настройки контента</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Управление характеристиками и настройками контента</p>
    </div>

    <CharacteristicDefinitions />
  </div>
</template>

<script setup>
import CharacteristicDefinitions from '../components/CharacteristicDefinitions.vue';
</script>
