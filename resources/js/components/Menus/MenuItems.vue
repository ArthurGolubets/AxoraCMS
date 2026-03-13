<template>
  <div>
    <div class="mb-6">
      <button @click="$router.push('/menus')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-2">
        ← Назад к списку меню
      </button>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Пункты меню: {{ menu?.name }}</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Управляйте пунктами меню и их структурой</p>
    </div>

    <!-- Actions -->
    <div class="mb-6">
      <button @click="showCreateModal = true" :style="buttonStyle" class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90">
        + Добавить пункт меню
      </button>
    </div>

    <!-- Menu Items Tree -->
    <div v-if="items.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow">
      <MenuTreeItem
        v-for="(item, index) in items"
        :key="item.id"
        :item="item"
        :index="index"
        :siblings="items"
        :depth="0"
        @edit="editItem"
        @delete="deleteItem"
        @toggle-active="toggleItemActive"
        @move-up="moveItemUp"
        @move-down="moveItemDown"
        @add-child="openCreateChildModal"
        @drag-start="handleDragStart"
        @drag-over="handleDragOver"
        @drop="handleDrop"
      />
    </div>

    <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-12 text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Нет пунктов меню</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Начните с создания нового пункта меню</p>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeModals">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
          {{ showCreateModal ? 'Создать пункт меню' : 'Редактировать пункт меню' }}
        </h3>

        <form @submit.prevent="saveItem" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label>
            <input v-model="itemForm.title" type="text" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Родительский пункт</label>
            <select v-model="itemForm.parent_id" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
              <option :value="null">Нет (корневой пункт)</option>
              <option v-for="item in getAllItemsFlat()" :key="item.id" :value="item.id" :disabled="editingItemId === item.id">
                {{ '—'.repeat(item.level) }} {{ item.title }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">URL</label>
            <input v-model="itemForm.url" type="text" placeholder="/about" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Относительный или абсолютный URL</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Открывать в</label>
            <select v-model="itemForm.target" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
              <option value="_self">Том же окне</option>
              <option value="_blank">Новом окне</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Порядок сортировки</label>
            <input v-model.number="itemForm.sort" type="number" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Меньшее значение = выше в списке</p>
          </div>

          <div class="flex items-center">
            <input v-model="itemForm.is_active" type="checkbox" id="item_active" class="mr-2">
            <label for="item_active" class="text-sm text-gray-700 dark:text-gray-300">Активен</label>
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
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';
import MenuTreeItem from './MenuTreeItem.vue';

const route = useRoute();
const { success, error, confirm } = useModal();
const { buttonStyle } = useTheme();

const menuId = computed(() => route.params.id);
const menu = ref(null);
const items = ref([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const itemForm = ref({
  title: '',
  parent_id: null,
  url: '',
  target: '_self',
  sort: 500,
  is_active: true
});
const editingItemId = ref(null);
const draggedItem = ref(null);

const rootItems = computed(() => items.value.filter(item => !item.parent_id));

const fetchMenu = async () => {
  try {
    const response = await fetch(`/admin/api/menus/${menuId.value}`);
    const data = await response.json();
    menu.value = data.menu;
  } catch (err) {
    console.error('Error fetching menu:', err);
  }
};

const fetchItems = async () => {
  try {
    const response = await fetch(`/admin/api/menus/${menuId.value}/items`);
    const data = await response.json();
    // Sort items properly by sort field and nest children
    items.value = buildTree(data);
  } catch (err) {
    console.error('Error fetching items:', err);
    await error('Ошибка при загрузке пунктов меню');
  }
};

const buildTree = (flatItems) => {
  // Sort by sort field
  const sorted = [...flatItems].sort((a, b) => a.sort - b.sort);

  // Build tree structure
  const itemMap = {};
  const tree = [];

  sorted.forEach(item => {
    itemMap[item.id] = { ...item, children: [] };
  });

  sorted.forEach(item => {
    if (item.parent_id && itemMap[item.parent_id]) {
      itemMap[item.parent_id].children.push(itemMap[item.id]);
    } else {
      tree.push(itemMap[item.id]);
    }
  });

  return tree;
};

const getAllItemsFlat = (itemsList = items.value, level = 0) => {
  let result = [];
  itemsList.forEach(item => {
    result.push({ ...item, level });
    if (item.children && item.children.length > 0) {
      result = result.concat(getAllItemsFlat(item.children, level + 1));
    }
  });
  return result;
};

const editItem = (item) => {
  editingItemId.value = item.id;
  itemForm.value = {
    title: item.title,
    parent_id: item.parent_id,
    url: item.url || '',
    target: item.target,
    sort: item.sort,
    is_active: item.is_active
  };
  showEditModal.value = true;
};

const saveItem = async () => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = showCreateModal.value ? `/admin/api/menus/${menuId.value}/items` : `/admin/api/menu-items/${editingItemId.value}`;
    const method = showCreateModal.value ? 'POST' : 'PUT';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify(itemForm.value)
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Ошибка сохранения');
    }

    await success(showCreateModal.value ? 'Пункт меню создан!' : 'Пункт меню обновлен!');
    closeModals();
    fetchItems();
  } catch (err) {
    console.error('Error saving item:', err);
    await error(err.message || 'Ошибка при сохранении пункта меню');
  }
};

const deleteItem = async (item) => {
  const confirmed = await confirm(
    'Удаление пункта меню',
    `Вы уверены, что хотите удалить пункт "${item.title}"?`
  );

  if (!confirmed) return;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/menu-items/${item.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token
      }
    });

    if (!response.ok) throw new Error('Ошибка удаления');

    await success('Пункт меню удален!');
    fetchItems();
  } catch (err) {
    console.error('Error deleting item:', err);
    await error('Ошибка при удалении пункта меню');
  }
};

const toggleItemActive = async (item) => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/menu-items/${item.id}/toggle-active`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token
      }
    });

    if (!response.ok) throw new Error('Ошибка изменения статуса');

    await success('Статус изменен!');
    fetchItems();
  } catch (err) {
    console.error('Error toggling active:', err);
    await error('Ошибка при изменении статуса');
  }
};

const moveItemUp = async (item, siblings) => {
  const currentIndex = siblings.findIndex(s => s.id === item.id);
  if (currentIndex <= 0) return;

  const prevItem = siblings[currentIndex - 1];

  // Build reorder data
  const reorderData = siblings.map((sibling, idx) => {
    if (sibling.id === item.id) {
      return { id: sibling.id, sort: prevItem.sort, parent_id: sibling.parent_id };
    } else if (sibling.id === prevItem.id) {
      return { id: sibling.id, sort: item.sort, parent_id: sibling.parent_id };
    } else {
      return { id: sibling.id, sort: sibling.sort, parent_id: sibling.parent_id };
    }
  });

  await reorderItems(reorderData);
};

const moveItemDown = async (item, siblings) => {
  const currentIndex = siblings.findIndex(s => s.id === item.id);
  if (currentIndex >= siblings.length - 1) return;

  const nextItem = siblings[currentIndex + 1];

  // Build reorder data
  const reorderData = siblings.map((sibling, idx) => {
    if (sibling.id === item.id) {
      return { id: sibling.id, sort: nextItem.sort, parent_id: sibling.parent_id };
    } else if (sibling.id === nextItem.id) {
      return { id: sibling.id, sort: item.sort, parent_id: sibling.parent_id };
    } else {
      return { id: sibling.id, sort: sibling.sort, parent_id: sibling.parent_id };
    }
  });

  await reorderItems(reorderData);
};

const reorderItems = async (reorderData) => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/api/menus/${menuId.value}/items/reorder`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({ items: reorderData })
    });

    if (!response.ok) throw new Error('Ошибка при изменении порядка');

    await fetchItems();
  } catch (err) {
    console.error('Error reordering items:', err);
    await error('Ошибка при изменении порядка пунктов меню');
  }
};

const handleDragStart = (item) => {
  draggedItem.value = item;
};

const handleDragOver = (event) => {
  event.preventDefault();
};

const handleDrop = async (targetItem) => {
  if (!draggedItem.value || draggedItem.value.id === targetItem.id) {
    draggedItem.value = null;
    return;
  }

  // Move dragged item to target position
  const dragged = draggedItem.value;
  const target = targetItem;

  // If dragging to same parent, just swap positions
  if (dragged.parent_id === target.parent_id) {
    const reorderData = [];
    const allFlat = getAllItemsFlat();

    // Get siblings
    const siblings = allFlat.filter(item => item.parent_id === dragged.parent_id);
    const draggedIndex = siblings.findIndex(s => s.id === dragged.id);
    const targetIndex = siblings.findIndex(s => s.id === target.id);

    siblings.forEach((sibling, idx) => {
      let newSort = sibling.sort;
      if (sibling.id === dragged.id) {
        newSort = target.sort;
      } else if (draggedIndex < targetIndex) {
        // Moving down
        if (idx > draggedIndex && idx <= targetIndex) {
          newSort = siblings[idx - 1].sort;
        }
      } else {
        // Moving up
        if (idx >= targetIndex && idx < draggedIndex) {
          newSort = siblings[idx + 1].sort;
        }
      }
      reorderData.push({ id: sibling.id, sort: newSort, parent_id: sibling.parent_id });
    });

    await reorderItems(reorderData);
  } else {
    // Different parent - move to new parent
    await error('Перемещение между разными родителями пока не поддерживается. Используйте редактирование.');
  }

  draggedItem.value = null;
};

const openCreateChildModal = (parentItem) => {
  itemForm.value = {
    title: '',
    parent_id: parentItem.id,
    url: '',
    target: '_self',
    sort: 500,
    is_active: true
  };
  showCreateModal.value = true;
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingItemId.value = null;
  itemForm.value = {
    title: '',
    parent_id: null,
    url: '',
    target: '_self',
    sort: 500,
    is_active: true
  };
};

onMounted(() => {
  fetchMenu();
  fetchItems();
});
</script>
