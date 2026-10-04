<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Управление меню</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Создавайте и настраивайте меню для вашего сайта</p>
      </div>
      <button @click="openCreate" :style="buttonStyle" class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90">
        + Создать меню
      </button>
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
      <div v-if="hasActiveFilters" class="mt-3 flex justify-end">
        <button @click="resetFilters" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
          Сбросить фильтры
        </button>
      </div>
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
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
              <button type="button" @click="manageItems(menu)" class="font-medium hover:underline text-left">{{ menu.name }}</button>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ menu.code }}</td>
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
            <td class="px-6 py-4 text-right">
              <ActionMenu :items="menuActions(menu)" />
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

    <!-- Create / edit side panel -->
    <SidePanel v-if="panel.show" :title="panel.editingId ? 'Редактировать меню' : 'Создать меню'" @close="closePanel">
      <form id="menu-form" @submit.prevent="saveMenu" class="space-y-4">
        <div>
          <label :class="labelClass">Название *</label>
          <input v-model="form.name" type="text" required :class="inputClass">
        </div>

        <div>
          <label :class="labelClass">Расположение *</label>
          <select v-model="form.location" required :class="inputClass">
            <option value="header">Шапка</option>
            <option value="footer">Подвал</option>
            <option value="custom">Свой код</option>
          </select>
        </div>

        <div v-if="form.location === 'custom'">
          <label :class="labelClass">Свой код</label>
          <input v-model="form.custom_code" type="text" placeholder="my_custom_menu" :class="inputClass">
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Метка расположения для ваших шаблонов</p>
        </div>

        <div>
          <label :class="labelClass">Описание</label>
          <textarea v-model="form.description" rows="3" :class="inputClass"></textarea>
        </div>

        <ToggleSwitch v-model="form.is_active" :theme-color="themeColor" label="Активно" />
      </form>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <button type="button" @click="closePanel" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
            Отмена
          </button>
          <ThemeButton type="submit" form="menu-form" variant="primary" :disabled="saving">
            {{ panel.editingId ? 'Сохранить' : 'Создать' }}
          </ThemeButton>
        </div>
      </template>
    </SidePanel>

    <!-- Blade usage hint -->
    <SidePanel v-if="usageMenu" :title="`Как вывести меню «${usageMenu.name}»`" width-class="max-w-2xl" @close="usageMenu = null">
      <div class="space-y-5 text-sm text-gray-700 dark:text-gray-300">
        <p v-if="usageJustCreated" class="p-3 rounded-lg bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300">
          Меню создано. Добавьте пункты и выведите его в шаблоне одним из способов ниже.
        </p>
        <div v-for="snippet in usageSnippets(usageMenu)" :key="snippet.title">
          <div class="flex items-center justify-between mb-1">
            <p class="font-medium">{{ snippet.title }}</p>
            <button type="button" @click="copy(snippet.code)" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400">Скопировать</button>
          </div>
          <p v-if="snippet.hint" class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ snippet.hint }}</p>
          <pre class="text-xs font-mono whitespace-pre-wrap break-all p-3 rounded bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200">{{ snippet.code }}</pre>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <button type="button" @click="usageMenu = null" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Закрыть</button>
          <ThemeButton variant="primary" @click="manageItems(usageMenu)">Перейти к пунктам меню</ThemeButton>
        </div>
      </template>
    </SidePanel>

    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';
import SidePanel from '../SidePanel.vue';
import ActionMenu from '../ActionMenu.vue';
import ToggleSwitch from '../ToggleSwitch.vue';
import ThemeButton from '../ThemeButton.vue';
import ConfirmModal from '../ConfirmModal.vue';

const router = useRouter();
const { success, error } = useModal();
const { buttonStyle, themeColor } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const allMenus = ref([]);
const panel = ref({ show: false, editingId: null });
const saving = ref(false);
const usageMenu = ref(null);
const usageJustCreated = ref(false);
const confirmModal = ref(null);

const ICONS = {
  items: 'M4 6h16M4 12h16M4 18h16',
  edit: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
  code: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
  toggleOn: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
  toggleOff: 'M5 13l4 4L19 7',
  delete: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
};

const menuActions = (menu) => [
  { label: 'Пункты меню', icon: ICONS.items, action: () => manageItems(menu) },
  { label: 'Редактировать', icon: ICONS.edit, action: () => editMenu(menu) },
  { label: 'Как вывести в Blade', icon: ICONS.code, action: () => showUsage(menu) },
  { label: menu.is_active ? 'Деактивировать' : 'Активировать', icon: menu.is_active ? ICONS.toggleOn : ICONS.toggleOff, action: () => toggleActive(menu) },
  { divider: true },
  { label: 'Удалить', icon: ICONS.delete, danger: true, action: () => deleteMenu(menu) },
];

const usageSnippets = (menu) => [
  {
    title: 'Готовая разметка (Blade-компонент)',
    hint: 'Выводит активные пункты вложенными списками <ul>, с классами axora-menu__*.',
    code: `<x-axora-cms::menu code="${menu.code}" />`,
  },
  {
    title: 'Своя разметка',
    hint: 'TMenu::tree() возвращает активные пункты любой вложенности: id, title, url, target, children.',
    code: `@php($menu = \\HolartWeb\\AxoraCMS\\Models\\Menus\\TMenu::tree('${menu.code}'))

<ul>
    @foreach ($menu as $item)
        <li>
            <a href="{{ $item['url'] }}" target="{{ $item['target'] }}">{{ $item['title'] }}</a>

            @if ($item['children'])
                <ul>
                    @foreach ($item['children'] as $child)
                        <li><a href="{{ $child['url'] }}" target="{{ $child['target'] }}">{{ $child['title'] }}</a></li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>`,
  },
];

const showUsage = (menu, justCreated = false) => {
  usageJustCreated.value = justCreated;
  usageMenu.value = menu;
};

const copy = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    success('Скопировано');
  } catch (e) {
    error('Не удалось скопировать');
  }
};
const form = ref({
  name: '',
  location: 'header',
  custom_code: '',
  description: '',
  is_active: true
});

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

const openCreate = () => {
  resetForm();
  panel.value = { show: true, editingId: null };
};

const editMenu = (menu) => {
  panel.value = { show: true, editingId: menu.id };
  form.value = {
    name: menu.name,
    location: menu.location,
    custom_code: menu.custom_code || '',
    description: menu.description || '',
    is_active: menu.is_active
  };
};

const saveMenu = async () => {
  saving.value = true;
  try {
    const isCreate = !panel.value.editingId;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(isCreate ? '/admin/api/menus' : `/admin/api/menus/${panel.value.editingId}`, {
      method: isCreate ? 'POST' : 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        Accept: 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.message || 'Ошибка сохранения');
    }

    closePanel();
    await fetchMenus();

    if (isCreate) {
      const created = data.menu || data.data || data;
      if (created?.code) showUsage(created, true);
    } else {
      success('Меню обновлено');
    }
  } catch (err) {
    console.error('Error saving menu:', err);
    await error(err.message || 'Ошибка при сохранении меню');
  } finally {
    saving.value = false;
  }
};

const deleteMenu = async (menu) => {
  const confirmed = await confirmModal.value.open({
    title: 'Удалить меню?',
    message: `Меню «${menu.name}» и все его пункты будут удалены. Если меню выводится на сайте, вывод перестанет работать.`,
    confirmText: 'Удалить',
    dangerMode: true,
  });

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

    fetchMenus();
  } catch (err) {
    console.error('Error toggling active:', err);
    await error('Ошибка при изменении статуса');
  }
};

const manageItems = (menu) => {
  router.push(`/menus/${menu.id}/items`);
};

const closePanel = () => {
  panel.value = { show: false, editingId: null };
};

const resetForm = () => {
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
