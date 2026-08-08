<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Информационные блоки</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Управление инфоблоками</p>
      </div>
      <ThemeButton variant="primary" @click="$router.push('/infoblocks/create')">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Создать инфоблок
      </ThemeButton>
    </div>

    <!-- Filters -->
    <div class="mb-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
      <input v-model="searchQuery" @input="loadInfoBlocks" type="text" placeholder="Поиск по названию или коду..." class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
    </div>

    <!-- InfoBlocks Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>
      <div v-else-if="infoBlocks.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">Инфоблоки не найдены</div>
      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-12"></th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Код</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Тип</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Полей</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Элементов</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Статус</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="block in infoBlocks" :key="block.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4">
              <button @click="toggleFavorite(block)" class="text-gray-400 hover:text-yellow-500 transition" :class="{ 'text-yellow-500': block.is_favorite }" title="Добавить в избранное">
                <svg class="w-5 h-5" :fill="block.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
              </button>
            </td>
            <td class="px-6 py-4">
              <div class="font-medium text-gray-900 dark:text-white">{{ block.name }}</div>
              <div v-if="block.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ block.description }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ block.code }}</td>
            <td class="px-6 py-4 text-sm">
              <span v-if="block.type === 'catalog'" class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded">Каталог</span>
              <span v-else class="px-2 py-1 text-xs bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 rounded">Список</span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ block.fields_count || 0 }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ block.elements_count || 0 }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
              <span v-if="block.is_active" class="px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">Активен</span>
              <span v-else class="px-2 py-1 text-xs bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 rounded">Неактивен</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="viewFields(block)" class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300" title="Поля">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
              </button>
              <button v-if="block.type === 'catalog'" @click="viewSections(block)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="Разделы">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
              </button>
              <button v-else @click="viewElements(block)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="Элементы">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
              </button>
              <button @click="editInfoBlock(block)" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300" title="Редактировать">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
              </button>
              <button @click="confirmDeleteInfoBlock(block)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Удалить">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-600 dark:text-gray-400">
        Показано {{ pagination.from }} - {{ pagination.to }} из {{ pagination.total }}
      </div>
      <div class="flex space-x-2">
        <button
          v-for="page in paginationPages"
          :key="page"
          @click="changePage(page)"
          :disabled="page === pagination.current_page"
          class="px-3 py-1 text-sm rounded"
          :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ThemeButton from './ThemeButton.vue';
import { useTheme } from '../composables/useTheme';
import { useModal } from '../composables/useModal';

const { themeColor } = useTheme();
const { confirm } = useModal();

const infoBlocks = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const pagination = ref({
  current_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
});

const paginationPages = computed(() => {
  const pages = [];
  const total = Math.ceil(pagination.value.total / pagination.value.per_page);
  for (let i = 1; i <= total; i++) {
    pages.push(i);
  }
  return pages;
});

const loadInfoBlocks = async (page = 1) => {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      page,
      per_page: pagination.value.per_page
    });

    if (searchQuery.value) {
      params.append('search', searchQuery.value);
    }

    const response = await fetch(`/admin/api/infoblocks?${params}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      infoBlocks.value = data.data || [];
      pagination.value = {
        current_page: data.current_page || 1,
        per_page: data.per_page || 15,
        total: data.total || 0,
        from: data.from || 0,
        to: data.to || 0
      };
    }
  } catch (error) {
    console.error('Failed to load info blocks:', error);
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  loadInfoBlocks(page);
};

const viewFields = (block) => {
  window.location.href = `/admin/infoblocks/${block.id}/fields`;
};

const viewSections = (block) => {
  window.location.href = `/admin/infoblocks/${block.id}/sections`;
};

const viewElements = (block) => {
  window.location.href = `/admin/infoblocks/${block.id}/elements`;
};

const editInfoBlock = (block) => {
  window.location.href = `/admin/infoblocks/${block.id}/edit`;
};

const confirmDeleteInfoBlock = async (block) => {
  const result = await confirm(
    'Удаление инфоблока',
    `Вы уверены, что хотите удалить инфоблок "${block.name}"? Это также удалит все поля и элементы.`,
    'Удалить',
    'Отмена'
  );

  if (result) {
    deleteInfoBlock(block);
  }
};

const deleteInfoBlock = async (block) => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${block.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      loadInfoBlocks(pagination.value.current_page);
    }
  } catch (error) {
    console.error('Failed to delete info block:', error);
  }
};

const toggleFavorite = async (block) => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${block.id}/favorite`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });

    if (response.ok) {
      const data = await response.json();
      block.is_favorite = data.is_favorite;

      // Emit event to update sidebar
      window.dispatchEvent(new CustomEvent('infoblocks-favorites-updated'));
    } else {
      const errorData = await response.json().catch(() => ({}));
      console.error('Failed to toggle favorite:', response.status, errorData);
      alert('Ошибка при изменении избранного: ' + (errorData.message || 'Неизвестная ошибка'));
    }
  } catch (error) {
    console.error('Failed to toggle favorite:', error);
    alert('Ошибка при изменении избранного');
  }
};

onMounted(() => {
  loadInfoBlocks();
});
</script>
