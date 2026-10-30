<template>
  <div class="relative">
    <input v-model="query" type="text" autocomplete="off" :class="inputClass" :placeholder="placeholder" @input="search">
    <ul v-if="cities.length" class="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg">
      <li v-for="city in cities" :key="city.code">
        <button type="button" class="w-full px-4 py-2 text-left text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600" @click="choose(city)">{{ city.name }}</button>
      </li>
    </ul>
    <p v-if="message" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ message }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { cdekApi } from '../../../composables/useCdekDelivery';

defineProps({
  placeholder: { type: String, default: 'Начните вводить город' },
});

const emit = defineEmits(['select']);

const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const query = ref('');
const cities = ref([]);
const message = ref('');
let timer = null;

const search = () => {
  clearTimeout(timer);
  message.value = '';

  if (query.value.trim().length < 2) {
    cities.value = [];
    return;
  }

  timer = setTimeout(async () => {
    try {
      cities.value = (await cdekApi('GET', `/cities?q=${encodeURIComponent(query.value.trim())}`)).data;
    } catch (e) {
      cities.value = [];
      message.value = e.message;
    }
  }, 300);
};

const choose = (city) => {
  query.value = city.name;
  cities.value = [];
  emit('select', city);
};
</script>
