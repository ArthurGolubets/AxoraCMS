<template>
  <div>
    <div class="mb-6">
      <button @click="$router.push('/menus')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-2">
        ← Назад к списку меню
      </button>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Пункты меню: {{ menu?.name }}</h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Перетаскивайте пункты, чтобы изменить порядок и вложенность</p>
        </div>
        <button @click="openCreate(null)" :style="buttonStyle" class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90">
          + Добавить пункт меню
        </button>
      </div>
    </div>

    <div v-if="items.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow">
      <!-- -mb-px hides the last row's bottom border under the card border -->
      <MenuTreeList
        class="-mb-px"
        :items="items"
        :dragging="dragging"
        @drag-state="dragging = $event"
        @changed="scheduleSaveOrder"
        @edit="openEdit"
        @delete="deleteItem"
        @toggle-active="toggleItemActive"
        @add-child="(item) => openCreate(item.id)"
      />
    </div>

    <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-12 text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Нет пунктов меню</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Начните с создания нового пункта меню</p>
    </div>

    <!-- Create / edit side panel -->
    <SidePanel v-if="panel.show" :title="panel.editingId ? 'Редактировать пункт меню' : 'Новый пункт меню'" @close="panel.show = false">
      <form id="menu-item-form" @submit.prevent="saveItem" class="space-y-4">
        <div>
          <label :class="labelClass">Название *</label>
          <input ref="titleInput" v-model="itemForm.title" type="text" required :class="inputClass">
        </div>

        <div>
          <label :class="labelClass">URL</label>
          <div class="flex gap-2">
            <input v-model="itemForm.url" type="text" placeholder="/about или https://example.com" :class="[inputClass, 'font-mono']">
            <button
              type="button"
              @click="pagePickerOpen = true"
              title="Выбрать страницу сайта"
              class="shrink-0 px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-300 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </button>
          </div>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Введите вручную или выберите страницу — адрес подставится автоматически</p>
        </div>

        <div>
          <label :class="labelClass">Родительский пункт</label>
          <select v-model="itemForm.parent_id" :class="inputClass">
            <option :value="null">Нет (корневой пункт)</option>
            <option v-for="item in parentOptions" :key="item.id" :value="item.id">
              {{ '— '.repeat(item.level) }}{{ item.title }}
            </option>
          </select>
        </div>

        <div>
          <label :class="labelClass">Открывать в</label>
          <select v-model="itemForm.target" :class="inputClass">
            <option value="_self">Том же окне</option>
            <option value="_blank">Новом окне</option>
          </select>
        </div>

        <ToggleSwitch v-model="itemForm.is_active" :theme-color="themeColor" label="Активен" />
      </form>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <button type="button" @click="panel.show = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
            Отмена
          </button>
          <ThemeButton type="submit" form="menu-item-form" variant="primary" :disabled="saving">
            {{ panel.editingId ? 'Сохранить' : 'Создать' }}
          </ThemeButton>
        </div>
      </template>
    </SidePanel>

    <PagePickerModal :show="pagePickerOpen" @select="selectPage" @close="pagePickerOpen = false" />
    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';
import MenuTreeList from './MenuTreeList.vue';
import SidePanel from '../SidePanel.vue';
import ToggleSwitch from '../ToggleSwitch.vue';
import ThemeButton from '../ThemeButton.vue';
import ConfirmModal from '../ConfirmModal.vue';
import PagePickerModal from '../PagePickerModal.vue';

const route = useRoute();
const { error } = useModal();
const { buttonStyle, themeColor } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const menuId = computed(() => route.params.id);
const menu = ref(null);
const items = ref([]);
const saving = ref(false);
const pagePickerOpen = ref(false);
const confirmModal = ref(null);
const titleInput = ref(null);
const panel = ref({ show: false, editingId: null });
const dragging = ref(false);
const itemForm = ref({});
let saveOrderTimer = null;

const blankItem = (parentId = null) => ({
  title: '',
  parent_id: parentId,
  url: '',
  target: '_self',
  is_active: true,
});

const csrfHeaders = () => ({
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
  Accept: 'application/json',
});

// --- Tree ------------------------------------------------------------------

const buildTree = (flatItems) => {
  const sorted = [...flatItems].sort((a, b) => (a.sort - b.sort) || (a.id - b.id));
  const map = {};
  const tree = [];

  sorted.forEach((item) => { map[item.id] = { ...item, children: [] }; });
  sorted.forEach((item) => {
    if (item.parent_id && map[item.parent_id]) {
      map[item.parent_id].children.push(map[item.id]);
    } else {
      tree.push(map[item.id]);
    }
  });

  return tree;
};

const flatten = (list = items.value, level = 0) => list.flatMap((item) => [{ ...item, level }, ...flatten(item.children, level + 1)]);

/**
 * Items that may become the parent of the edited one: not itself and not
 * one of its descendants.
 */
const parentOptions = computed(() => {
  const editingId = panel.value.editingId;
  if (!editingId) return flatten();

  const excluded = new Set([editingId]);
  flatten().forEach((item) => {
    if (excluded.has(item.parent_id)) excluded.add(item.id);
  });
  return flatten().filter((item) => !excluded.has(item.id));
});

// --- Loading ---------------------------------------------------------------

const fetchMenu = async () => {
  try {
    const response = await fetch(`/admin/api/menus/${menuId.value}`, { headers: { Accept: 'application/json' } });
    const data = await response.json();
    menu.value = data.menu;
  } catch (err) {
    console.error('Error fetching menu:', err);
  }
};

const fetchItems = async () => {
  try {
    const response = await fetch(`/admin/api/menus/${menuId.value}/items`, { headers: { Accept: 'application/json' } });
    items.value = buildTree(await response.json());
  } catch (err) {
    console.error('Error fetching items:', err);
    await error('Ошибка при загрузке пунктов меню');
  }
};

// --- Drag & drop order -----------------------------------------------------

/**
 * A move between two lists fires "change" on both — coalesce into one save.
 */
const scheduleSaveOrder = () => {
  clearTimeout(saveOrderTimer);
  saveOrderTimer = setTimeout(saveOrder, 50);
};

const saveOrder = async () => {
  const payload = [];
  const walk = (list, parentId) => list.forEach((item, index) => {
    item.parent_id = parentId;
    item.sort = (index + 1) * 10;
    payload.push({ id: item.id, sort: item.sort, parent_id: parentId });
    walk(item.children, item.id);
  });
  walk(items.value, null);

  try {
    const response = await fetch(`/admin/api/menus/${menuId.value}/items/reorder`, {
      method: 'POST',
      headers: csrfHeaders(),
      body: JSON.stringify({ items: payload }),
    });
    if (!response.ok) throw new Error();
  } catch (err) {
    await error('Не удалось сохранить порядок пунктов меню');
    fetchItems();
  }
};

// --- Create / edit ---------------------------------------------------------

const openCreate = (parentId) => {
  itemForm.value = blankItem(parentId);
  panel.value = { show: true, editingId: null };
  nextTick(() => titleInput.value?.focus());
};

const openEdit = (item) => {
  itemForm.value = {
    title: item.title,
    parent_id: item.parent_id,
    url: item.url || '',
    target: item.target || '_self',
    is_active: item.is_active,
  };
  panel.value = { show: true, editingId: item.id };
};

const selectPage = (page) => {
  itemForm.value.url = page.public_url;
  if (!itemForm.value.title.trim()) itemForm.value.title = page.title;
  pagePickerOpen.value = false;
};

const saveItem = async () => {
  saving.value = true;
  try {
    const isCreate = !panel.value.editingId;
    const response = await fetch(isCreate ? `/admin/api/menus/${menuId.value}/items` : `/admin/api/menu-items/${panel.value.editingId}`, {
      method: isCreate ? 'POST' : 'PUT',
      headers: csrfHeaders(),
      body: JSON.stringify(itemForm.value),
    });

    if (!response.ok) {
      const data = await response.json().catch(() => ({}));
      throw new Error(data.message || 'Ошибка сохранения');
    }

    panel.value.show = false;
    fetchItems();
  } catch (err) {
    await error(err.message || 'Ошибка при сохранении пункта меню');
  } finally {
    saving.value = false;
  }
};

// --- Other actions ---------------------------------------------------------

const deleteItem = async (item) => {
  const confirmed = await confirmModal.value.open({
    title: 'Удалить пункт меню?',
    message: item.children?.length
      ? `Пункт «${item.title}» будет удалён вместе со всеми вложенными пунктами (${flatten(item.children).length}).`
      : `Пункт «${item.title}» будет удалён.`,
    confirmText: 'Удалить',
    dangerMode: true,
  });
  if (!confirmed) return;

  try {
    const response = await fetch(`/admin/api/menu-items/${item.id}`, { method: 'DELETE', headers: csrfHeaders() });
    if (!response.ok) throw new Error();
    fetchItems();
  } catch (err) {
    await error('Ошибка при удалении пункта меню');
  }
};

const toggleItemActive = async (item) => {
  try {
    const response = await fetch(`/admin/api/menu-items/${item.id}/toggle-active`, { method: 'POST', headers: csrfHeaders() });
    if (!response.ok) throw new Error();
    item.is_active = !item.is_active;
  } catch (err) {
    await error('Ошибка при изменении статуса');
  }
};

onMounted(() => {
  fetchMenu();
  fetchItems();
});
</script>
