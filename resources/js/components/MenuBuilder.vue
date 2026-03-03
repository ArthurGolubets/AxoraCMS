<template>
  <div class="container-fluid">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Загрузка...</span>
      </div>
    </div>

    <div v-else-if="menu">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="h3 mb-1">{{ menu.name }}</h1>
          <p class="text-muted mb-0">
            <span class="badge" :class="menu.location === 'header' ? 'bg-primary' : 'bg-secondary'">
              {{ menu.location === 'header' ? 'Шапка' : 'Подвал' }}
            </span>
            <code class="ms-2">{{ menu.code }}</code>
          </p>
        </div>
        <div>
          <button @click="addItem(null)" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Добавить пункт
          </button>
          <button @click="$router.push('/menus')" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left"></i> Назад
          </button>
        </div>
      </div>

      <!-- Menu Items -->
      <div class="card">
        <div class="card-body">
          <div v-if="items.length === 0" class="text-center py-5">
            <p class="text-muted">Пункты меню не созданы</p>
            <button @click="addItem(null)" class="btn btn-primary">
              <i class="bi bi-plus-circle"></i> Добавить первый пункт
            </button>
          </div>

          <div v-else class="menu-items-tree">
            <draggable
              v-model="items"
              :options="{ group: 'menu-items', handle: '.drag-handle' }"
              @end="onDragEnd"
            >
              <div v-for="item in items" :key="item.id" class="menu-item-wrapper">
                <div class="menu-item">
                  <div class="menu-item-content">
                    <span class="drag-handle">
                      <i class="bi bi-grip-vertical"></i>
                    </span>
                    <div class="menu-item-info">
                      <strong>{{ item.title }}</strong>
                      <div class="small text-muted">
                        {{ item.url || item.route || '—' }}
                        <span v-if="item.target === '_blank'" class="badge bg-info ms-1">Новая вкладка</span>
                      </div>
                    </div>
                    <div class="menu-item-actions">
                      <button
                        @click="toggleItemActive(item)"
                        class="btn btn-sm"
                        :class="item.is_active ? 'btn-success' : 'btn-warning'"
                        :title="item.is_active ? 'Активно' : 'Неактивно'"
                      >
                        <i class="bi" :class="item.is_active ? 'bi-eye' : 'bi-eye-slash'"></i>
                      </button>
                      <button @click="addItem(item)" class="btn btn-sm btn-outline-primary" title="Добавить подпункт">
                        <i class="bi bi-plus"></i>
                      </button>
                      <button @click="editItem(item)" class="btn btn-sm btn-outline-secondary" title="Редактировать">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button @click="confirmDeleteItem(item)" class="btn btn-sm btn-outline-danger" title="Удалить">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Child Items -->
                <div v-if="item.children && item.children.length > 0" class="menu-children">
                  <draggable
                    v-model="item.children"
                    :options="{ group: 'menu-items', handle: '.drag-handle' }"
                    @end="onDragEnd"
                  >
                    <div v-for="child in item.children" :key="child.id" class="menu-item-wrapper">
                      <div class="menu-item child-item">
                        <div class="menu-item-content">
                          <span class="drag-handle">
                            <i class="bi bi-grip-vertical"></i>
                          </span>
                          <div class="menu-item-info">
                            <strong>{{ child.title }}</strong>
                            <div class="small text-muted">
                              {{ child.url || child.route || '—' }}
                              <span v-if="child.target === '_blank'" class="badge bg-info ms-1">Новая вкладка</span>
                            </div>
                          </div>
                          <div class="menu-item-actions">
                            <button
                              @click="toggleItemActive(child)"
                              class="btn btn-sm"
                              :class="child.is_active ? 'btn-success' : 'btn-warning'"
                              :title="child.is_active ? 'Активно' : 'Неактивно'"
                            >
                              <i class="bi" :class="child.is_active ? 'bi-eye' : 'bi-eye-slash'"></i>
                            </button>
                            <button @click="editItem(child)" class="btn btn-sm btn-outline-secondary" title="Редактировать">
                              <i class="bi bi-pencil"></i>
                            </button>
                            <button @click="confirmDeleteItem(child)" class="btn btn-sm btn-outline-danger" title="Удалить">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </draggable>
                </div>
              </div>
            </draggable>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Item Modal -->
    <Modal v-if="showItemModal" @close="closeItemModal">
      <template #header>
        <h5 class="modal-title">{{ itemFormData.id ? 'Редактировать пункт' : 'Создать пункт' }}</h5>
      </template>
      <template #body>
        <form @submit.prevent="saveItem">
          <div class="mb-3">
            <label class="form-label">Название *</label>
            <input v-model="itemFormData.title" type="text" class="form-control" required />
          </div>

          <div class="mb-3">
            <label class="form-label">URL</label>
            <input v-model="itemFormData.url" type="text" class="form-control" placeholder="/catalog" />
            <small class="form-text text-muted">Абсолютный или относительный URL</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Route (Laravel)</label>
            <input v-model="itemFormData.route" type="text" class="form-control" placeholder="catalog.index" />
            <small class="form-text text-muted">Имя роута Laravel (если указано, URL игнорируется)</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Открывать в</label>
            <select v-model="itemFormData.target" class="form-select">
              <option value="_self">Той же вкладке</option>
              <option value="_blank">Новой вкладке</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Порядок сортировки</label>
            <input v-model.number="itemFormData.sort" type="number" class="form-control" />
          </div>

          <div class="mb-3 form-check">
            <input v-model="itemFormData.is_active" type="checkbox" class="form-check-input" id="itemIsActive" />
            <label class="form-check-label" for="itemIsActive">Активно</label>
          </div>
        </form>
      </template>
      <template #footer>
        <button @click="closeItemModal" type="button" class="btn btn-secondary">Отмена</button>
        <button @click="saveItem" type="button" class="btn btn-primary">Сохранить</button>
      </template>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal v-if="deleteItemModal.show" @close="deleteItemModal.show = false">
      <template #header>
        <h5 class="modal-title">Подтверждение удаления</h5>
      </template>
      <template #body>
        <p>Вы действительно хотите удалить пункт <strong>{{ deleteItemModal.item?.title }}</strong>?</p>
        <p v-if="deleteItemModal.item?.children?.length > 0" class="text-danger">
          Все подпункты также будут удалены.
        </p>
      </template>
      <template #footer>
        <button @click="deleteItemModal.show = false" type="button" class="btn btn-secondary">Отмена</button>
        <button @click="confirmDeleteMenuItem" type="button" class="btn btn-danger">Удалить</button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import draggable from 'vuedraggable';
import Modal from './Modal.vue';

const route = useRoute();
const menuId = route.params.id;

const menu = ref(null);
const items = ref([]);
const loading = ref(true);
const showItemModal = ref(false);
const deleteItemModal = ref({ show: false, item: null });

const itemFormData = ref({
  id: null,
  parent_id: null,
  title: '',
  url: '',
  route: '',
  target: '_self',
  sort: 500,
  is_active: true,
});

const loadMenu = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/admin/api/menus/${menuId}`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    if (response.ok) {
      const data = await response.json();
      menu.value = data.menu;
      items.value = data.items || [];
    }
  } catch (error) {
    console.error('Failed to load menu:', error);
  } finally {
    loading.value = false;
  }
};

const addItem = (parentItem) => {
  itemFormData.value = {
    id: null,
    parent_id: parentItem?.id || null,
    title: '',
    url: '',
    route: '',
    target: '_self',
    sort: 500,
    is_active: true,
  };
  showItemModal.value = true;
};

const editItem = (item) => {
  itemFormData.value = {
    id: item.id,
    parent_id: item.parent_id,
    title: item.title,
    url: item.url || '',
    route: item.route || '',
    target: item.target || '_self',
    sort: item.sort || 500,
    is_active: item.is_active,
  };
  showItemModal.value = true;
};

const closeItemModal = () => {
  showItemModal.value = false;
  itemFormData.value = {
    id: null,
    parent_id: null,
    title: '',
    url: '',
    route: '',
    target: '_self',
    sort: 500,
    is_active: true,
  };
};

const saveItem = async () => {
  try {
    const url = itemFormData.value.id
      ? `/admin/api/menu-items/${itemFormData.value.id}`
      : '/admin/api/menu-items';

    const method = itemFormData.value.id ? 'PUT' : 'POST';

    const payload = {
      ...itemFormData.value,
      menu_id: menuId,
    };

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (response.ok) {
      closeItemModal();
      loadMenu();
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при сохранении пункта меню');
    }
  } catch (error) {
    console.error('Failed to save menu item:', error);
    alert('Ошибка при сохранении пункта меню');
  }
};

const confirmDeleteItem = (item) => {
  deleteItemModal.value = {
    show: true,
    item,
  };
};

const confirmDeleteMenuItem = async () => {
  try {
    const response = await fetch(`/admin/api/menu-items/${deleteItemModal.value.item.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
    });

    if (response.ok) {
      deleteItemModal.value.show = false;
      loadMenu();
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при удалении пункта меню');
    }
  } catch (error) {
    console.error('Failed to delete menu item:', error);
    alert('Ошибка при удалении пункта меню');
  }
};

const toggleItemActive = async (item) => {
  try {
    const response = await fetch(`/admin/api/menu-items/${item.id}/toggle-active`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
    });

    if (response.ok) {
      const data = await response.json();
      item.is_active = data.is_active;
    }
  } catch (error) {
    console.error('Failed to toggle item active:', error);
  }
};

const onDragEnd = async () => {
  // Flatten all items with their new positions and parents
  const allItems = [];

  items.value.forEach((item, index) => {
    allItems.push({
      id: item.id,
      sort: index * 10,
      parent_id: null,
    });

    if (item.children) {
      item.children.forEach((child, childIndex) => {
        allItems.push({
          id: child.id,
          sort: childIndex * 10,
          parent_id: item.id,
        });
      });
    }
  });

  try {
    await fetch('/admin/api/menu-items/reorder', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ items: allItems }),
    });
  } catch (error) {
    console.error('Failed to reorder items:', error);
  }
};

onMounted(() => {
  loadMenu();
});
</script>

<style scoped>
.menu-items-tree {
  min-height: 200px;
}

.menu-item-wrapper {
  margin-bottom: 8px;
}

.menu-item {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 12px;
  transition: box-shadow 0.2s;
}

.menu-item:hover {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.child-item {
  background: #f8f9fa;
}

.menu-item-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.drag-handle {
  cursor: move;
  color: #6c757d;
  font-size: 1.2rem;
}

.menu-item-info {
  flex: 1;
}

.menu-item-actions {
  display: flex;
  gap: 4px;
}

.menu-children {
  margin-left: 40px;
  margin-top: 8px;
  padding-left: 20px;
  border-left: 2px solid #dee2e6;
}
</style>
