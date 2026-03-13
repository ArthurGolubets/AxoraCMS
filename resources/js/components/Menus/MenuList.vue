<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Управление меню</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Создавайте и настраивайте меню для вашего сайта</p>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Поиск</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Название или код..."
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm"
          >
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Расположение</label>
          <select
            v-model="filters.location"
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm"
          >
            <option value="">Все</option>
            <option value="header">Шапка</option>
            <option value="footer">Подвал</option>
            <option value="custom">Свой код</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Статус</label>
          <select
            v-model="filters.is_active"
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm"
          >
            <option value="">Все</option>
            <option value="1">Активные</option>
            <option value="0">Неактивные</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="mb-6 flex justify-between items-center">
      <button @click="showCreateModal = true" :style="buttonStyle" class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90">
        + Создать меню
      </button>
      <button
        v-if="hasActiveFilters"
        @click="resetFilters"
        class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-white rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 text-sm"
      >
        Сбросить фильтры
      </button>
    </div>

    <!-- Menus List -->
    <div v-if="menus.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow">
      <table class="w-full">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Код</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Расположение</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Статус</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Действия</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="menu in menus" :key="menu.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ menu.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ menu.code }}</td>
            <td class="px-6 py-4 text-sm">
              <span v-if="menu.location === 'header'" class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded text-xs">Шапка</span>
              <span v-else-if="menu.location === 'footer'" class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded text-xs">Подвал</span>
              <span v-else class="px-2 py-1 bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 rounded text-xs">{{ menu.custom_code || 'Свой код' }}</span>
            </td>
            <td class="px-6 py-4">
              <span :class="menu.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'" class="px-2 py-1 rounded text-xs">
                {{ menu.is_active ? 'Активно' : 'Неактивно' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="editMenu(menu)" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">Редактировать</button>
              <button @click="manageItems(menu)" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">Пункты меню</button>
              <button @click="toggleActive(menu)" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300">
                {{ menu.is_active ? 'Деактивировать' : 'Активировать' }}
              </button>
              <button @click="deleteMenu(menu)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-12 text-center shadow">
      <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Нет меню</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Начните с создания нового меню</p>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeModals">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
          {{ showCreateModal ? 'Создать меню' : 'Редактировать меню' }}
        </h3>

        <form @submit.prevent="saveMenu" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label>
            <input v-model="form.name" type="text" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Расположение *</label>
            <select v-model="form.location" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
              <option value="header">Шапка</option>
              <option value="footer">Подвал</option>
              <option value="custom">Свой код</option>
            </select>
          </div>

          <div v-if="form.location === 'custom'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Свой код</label>
            <input v-model="form.custom_code" type="text" placeholder="my_custom_menu" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Укажите код для использования меню в своих шаблонах</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Описание</label>
            <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></textarea>
          </div>

          <div class="flex items-center">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="mr-2">
            <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Активно</label>
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="closeModals" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-white rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500">
              Отмена
            </button>
            <button type="submit" :style="buttonStyle" class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90">
              {{ showCreateModal ? 'Создать' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';

const router = useRouter();
const { success, error, confirm } = useModal();
const { buttonStyle } = useTheme();

const allMenus = ref([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const form = ref({
  name: '',
  location: 'header',
  custom_code: '',
  description: '',
  is_active: true
});
const editingId = ref(null);

const filters = ref({
  search: '',
  location: '',
  is_active: ''
});

const menus = computed(() => {
  let result = allMenus.value;

  // Filter by search
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(menu =>
      menu.name.toLowerCase().includes(search) ||
      menu.code.toLowerCase().includes(search)
    );
  }

  // Filter by location
  if (filters.value.location) {
    result = result.filter(menu => menu.location === filters.value.location);
  }

  // Filter by active status
  if (filters.value.is_active !== '') {
    const isActive = filters.value.is_active === '1';
    result = result.filter(menu => menu.is_active === isActive);
  }

  return result;
});

const hasActiveFilters = computed(() => {
  return filters.value.search || filters.value.location || filters.value.is_active !== '';
});

const resetFilters = () => {
  filters.value = {
    search: '',
    location: '',
    is_active: ''
  };
};

const fetchMenus = async () => {
  try {
    const response = await fetch('/admin/api/menus');
    const data = await response.json();
    allMenus.value = data.data || data;
  } catch (err) {
    console.error('Error fetching menus:', err);
    await error('Ошибка при загрузке меню');
  }
};

const editMenu = (menu) => {
  editingId.value = menu.id;
  form.value = {
    name: menu.name,
    location: menu.location,
    custom_code: menu.custom_code || '',
    description: menu.description || '',
    is_active: menu.is_active
  };
  showEditModal.value = true;
};

const saveMenu = async () => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = showCreateModal.value ? '/admin/api/menus' : `/admin/api/menus/${editingId.value}`;
    const method = showCreateModal.value ? 'POST' : 'PUT';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify(form.value)
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Ошибка сохранения');
    }

    await success(showCreateModal.value ? 'Меню создано!' : 'Меню обновлено!');
    closeModals();
    fetchMenus();
  } catch (err) {
    console.error('Error saving menu:', err);
    await error(err.message || 'Ошибка при сохранении меню');
  }
};

const deleteMenu = async (menu) => {
  const confirmed = await confirm(
    'Удаление меню',
    `Вы уверены, что хотите удалить меню "${menu.name}"? Все пункты меню также будут удалены.`
  );

  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/menus/${menu.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token
      }
    });

    if (!response.ok) throw new Error('Ошибка удаления');

    await success('Меню удалено!');
    fetchMenus();
  } catch (err) {
    console.error('Error deleting menu:', err);
    await error('Ошибка при удалении меню');
  }
};

const toggleActive = async (menu) => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/menus/${menu.id}/toggle-active`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token
      }
    });

    if (!response.ok) throw new Error('Ошибка изменения статуса');

    await success('Статус изменен!');
    fetchMenus();
  } catch (err) {
    console.error('Error toggling active:', err);
    await error('Ошибка при изменении статуса');
  }
};

const manageItems = (menu) => {
  router.push(`/menus/${menu.id}/items`);
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingId.value = null;
  form.value = {
    name: '',
    location: 'header',
    custom_code: '',
    description: '',
    is_active: true
  };
};

onMounted(() => {
  fetchMenus();
});
</script>
