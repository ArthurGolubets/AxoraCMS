<template>
  <div v-if="infoBlock">
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <button @click="$router.back()" class="text-gray-600 dark:text-gray-400">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </button>
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Элементы: {{ infoBlock.name }}</h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Управление элементами</p>
        </div>
      </div>
      <div class="flex items-center space-x-3">
        <ThemeButton v-if="infoBlock.type === 'catalog'" variant="secondary" @click="$router.push(`/infoblocks/${route.params.id}/sections`)">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
          </svg>
          Разделы
        </ThemeButton>
        <ThemeButton variant="primary" @click="$router.push(`/infoblocks/${route.params.id}/elements/create`)">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Добавить элемент
        </ThemeButton>
      </div>
    </div>

    <!-- Section Filter for Catalog -->
    <div v-if="infoBlock.type === 'catalog'" class="mb-4">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Фильтр по разделу</label>
      <select
        v-model="selectedSectionId"
        @change="loadElements"
        class="w-full max-w-md px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
      >
        <option :value="null">Все элементы</option>
        <option :value="''">Без раздела</option>
        <option v-for="section in sections" :key="section.id" :value="section.id">
          {{ section.name }}
        </option>
      </select>
    </div>

    <!-- Elements List -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>
      <div v-else-if="elements.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
        Элементы не созданы. Добавьте первый элемент.
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Код</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Статус</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Сортировка</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="element in elements" :key="element.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4">
              <div class="font-medium text-gray-900 dark:text-white">{{ element.name }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ element.code || '—' }}</td>
            <td class="px-6 py-4 text-sm">
              <span v-if="element.is_active" class="px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">Активен</span>
              <span v-else class="px-2 py-1 text-xs bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 rounded">Неактивен</span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ element.sort }}</td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="$router.push(`/infoblocks/${route.params.id}/elements/${element.id}/edit`)" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300" title="Редактировать">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
              </button>
              <button @click="confirmDeleteElement(element)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Удалить">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import ThemeButton from './ThemeButton.vue';
import { useModal } from '../composables/useModal';

const route = useRoute();
const { confirm } = useModal();

const infoBlock = ref(null);
const elements = ref([]);
const sections = ref([]);
const selectedSectionId = ref(null);
const loading = ref(false);

const loadInfoBlock = async () => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${route.params.id}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      infoBlock.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to load info block:', error);
  }
};

const loadSections = async () => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${route.params.id}/sections/list`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      sections.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to load sections:', error);
  }
};

const loadElements = async () => {
  loading.value = true;
  try {
    let url = `/admin/api/infoblocks/${route.params.id}/elements`;

    // Add section filter if catalog type
    if (infoBlock.value?.type === 'catalog' && selectedSectionId.value !== null) {
      url += `?section_id=${selectedSectionId.value}`;
    }

    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      // Handle both paginated and non-paginated responses
      elements.value = data.data || data;
    } else {
      console.error('Failed to load elements, status:', response.status);
    }
  } catch (error) {
    console.error('Failed to load elements:', error);
  } finally {
    loading.value = false;
  }
};

const confirmDeleteElement = async (element) => {
  const result = await confirm(
    'Удаление элемента',
    `Вы уверены, что хотите удалить элемент "${element.name}"?`,
    'Удалить',
    'Отмена'
  );

  if (result) {
    deleteElement(element);
  }
};

const deleteElement = async (element) => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${route.params.id}/elements/${element.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      loadElements();
    }
  } catch (error) {
    console.error('Failed to delete element:', error);
  }
};

onMounted(async () => {
  await loadInfoBlock();
  if (infoBlock.value?.type === 'catalog') {
    await loadSections();
  }
  loadElements();
});
</script>
