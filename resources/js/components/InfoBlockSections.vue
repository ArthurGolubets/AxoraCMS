<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <button @click="$router.back()" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </button>
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Разделы каталога: {{ infoBlock?.name }}</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Управление разделами и подразделами</p>
        </div>
      </div>
      <div class="flex space-x-3">
        <ThemeButton
          variant="secondary"
          @click="$router.push(`/infoblocks/${infoBlockId}/elements`)"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
          </svg>
          Элементы
        </ThemeButton>
        <ThemeButton
          variant="secondary"
          @click="viewMode = viewMode === 'tree' ? 'list' : 'tree'"
        >
          <svg v-if="viewMode === 'tree'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
          </svg>
          {{ viewMode === 'tree' ? 'Список' : 'Дерево' }}
        </ThemeButton>
        <ThemeButton @click="handleCreateElement(null)" variant="success">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Создать элемент
        </ThemeButton>
        <ThemeButton @click="showCreateForm(null)" variant="primary">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Создать раздел
        </ThemeButton>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex gap-3">
      <!-- Search -->
      <div class="flex-1">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Поиск разделов и элементов..."
            class="w-full px-4 py-2 pl-10 pr-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Status Filter -->
      <select
        v-model="statusFilter"
        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
        <option value="all">Все</option>
        <option value="active">Активные</option>
        <option value="inactive">Неактивные</option>
      </select>
    </div>

    <!-- Tree View -->
    <div v-if="viewMode === 'tree'" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">
        Загрузка...
      </div>
      <div v-else-if="filteredSections.length === 0 && filteredElementsWithoutSection.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
        {{ searchQuery || statusFilter !== 'all' ? 'Ничего не найдено' : 'Разделы не найдены' }}
      </div>
      <div v-else>
        <!-- Sections Tree (folders first) -->
        <SectionTreeItem
          v-for="section in filteredSections"
          :key="`section-${section.id}`"
          :section="section"
          :level="0"
          @create-subsection="showCreateForm"
          @create-element="handleCreateElement"
          @edit="editSection"
          @delete="deleteSection"
          @toggle-active="handleToggleActive"
          @edit-element="editElement"
          @delete-element="deleteElement"
        />

        <!-- Elements Without Section (displayed after folders at root level) -->
        <div v-for="element in filteredElementsWithoutSection" :key="`element-${element.id}`">
          <div class="group flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex items-center gap-3">
              <div class="flex items-center justify-center w-6 h-6 rounded bg-gray-200 dark:bg-gray-700">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-900 dark:text-white">{{ element.name }}</span>
                <span v-if="!element.is_active" class="px-2 py-0.5 text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                  Неактивен
                </span>
              </div>
            </div>
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                @click="editElement(element)"
                class="p-1.5 rounded-md transition-colors text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30"
                title="Редактировать"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </button>
              <button
                @click="deleteElement(element)"
                class="p-1.5 rounded-md transition-colors text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30"
                title="Удалить"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Код</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Родитель</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="section in flatSections" :key="section.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ section.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ section.code }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ section.parent?.name || '—' }}</td>
            <td class="px-6 py-4 text-right text-sm space-x-3">
              <button @click="editSection(section)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Редактировать</button>
              <button @click="deleteSection(section)" class="text-red-600 hover:text-red-800 dark:text-red-400">Удалить</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Section Form Modal -->
    <InfoBlockSectionForm
      v-if="showForm"
      :info-block-id="infoBlockId"
      :section="editingSection"
      :parent-id="createParentId"
      @close="closeForm"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import ThemeButton from './ThemeButton.vue';
import InfoBlockSectionForm from './InfoBlockSectionForm.vue';
import SectionTreeItem from './SectionTreeItem.vue';

const route = useRoute();

const infoBlockId = computed(() => route.params.id);
const loading = ref(false);
const infoBlock = ref(null);
const sections = ref([]);
const elementsWithoutSection = ref([]);
const showFormModal = ref(false);
const editingSection = ref(null);
const createParentId = ref(null);
const viewMode = ref('tree');
const searchQuery = ref('');
const statusFilter = ref('all');

const showForm = computed({
  get: () => showFormModal.value,
  set: (val) => showFormModal.value = val
});

watch(viewMode, () => {
  loadSections();
});

const filterByStatus = (item) => {
  if (statusFilter.value === 'all') return true;
  if (statusFilter.value === 'active') return item.is_active === true;
  if (statusFilter.value === 'inactive') return item.is_active === false;
  return true;
};

const filterBySearch = (item, query) => {
  if (!query) return true;
  const lowerQuery = query.toLowerCase();
  return item.name?.toLowerCase().includes(lowerQuery) ||
         item.code?.toLowerCase().includes(lowerQuery);
};

const filterSection = (section) => {
  // Filter children recursively
  const filteredChildren = section.children
    ? section.children.map(filterSection).filter(Boolean)
    : [];

  // Filter elements
  const filteredElements = section.elements
    ? section.elements.filter(el => filterByStatus(el) && filterBySearch(el, searchQuery.value))
    : [];

  // Check if section itself matches or has matching children/elements
  const sectionMatches = filterByStatus(section) && filterBySearch(section, searchQuery.value);
  const hasMatchingChildren = filteredChildren.length > 0 || filteredElements.length > 0;

  // Show section if it matches or has matching children/elements
  if (!sectionMatches && !hasMatchingChildren) return null;

  return {
    ...section,
    children: filteredChildren,
    elements: filteredElements
  };
};

const filteredSections = computed(() => {
  return sections.value.map(filterSection).filter(Boolean);
});

const filteredElementsWithoutSection = computed(() => {
  return elementsWithoutSection.value.filter(el =>
    filterByStatus(el) && filterBySearch(el, searchQuery.value)
  );
});

const flatSections = computed(() => {
  if (viewMode.value === 'list') {
    return sections.value;
  }

  const flatten = (items, parent = null) => {
    let result = [];
    for (const item of items) {
      result.push({ ...item, parent });
      if (item.children && item.children.length > 0) {
        result = result.concat(flatten(item.children, item));
      }
    }
    return result;
  };
  return flatten(sections.value);
});

const loadInfoBlock = async () => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${infoBlockId.value}`, {
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
  loading.value = true;
  try {
    let url;
    if (viewMode.value === 'list') {
      url = `/admin/api/infoblocks/${infoBlockId.value}/sections/list`;
    } else {
      url = `/admin/api/infoblocks/${infoBlockId.value}/sections`;
    }
    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      if (viewMode.value === 'tree') {
        sections.value = data.sections || [];
        elementsWithoutSection.value = data.elementsWithoutSection || [];
      } else {
        sections.value = data;
        elementsWithoutSection.value = [];
      }
    }
  } catch (error) {
    console.error('Failed to load sections:', error);
  } finally {
    loading.value = false;
  }
};

const showCreateForm = (parentId = null) => {
  editingSection.value = null;
  createParentId.value = parentId;
  showFormModal.value = true;
};

const editSection = (section) => {
  editingSection.value = section;
  createParentId.value = null;
  showFormModal.value = true;
};

const closeForm = () => {
  showFormModal.value = false;
  editingSection.value = null;
  createParentId.value = null;
};

const handleSaved = () => {
  closeForm();
  loadSections();
};

const handleCreateElement = (sectionId) => {
  window.location.href = `/admin/infoblocks/${infoBlockId.value}/elements/create?section_id=${sectionId}`;
};

const editElement = (element) => {
  window.location.href = `/admin/infoblocks/${infoBlockId.value}/elements/${element.id}/edit`;
};

const deleteElement = async (element) => {
  if (!confirm(`Вы уверены, что хотите удалить элемент "${element.name}"?`)) {
    return;
  }

  try {
    const response = await fetch(`/admin/api/infoblocks/${infoBlockId.value}/elements/${element.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      loadSections();
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при удалении элемента');
    }
  } catch (error) {
    console.error('Failed to delete element:', error);
    alert('Ошибка при удалении элемента');
  }
};

const handleToggleActive = async (section) => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${infoBlockId.value}/sections/${section.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        parent_id: section.parent_id,
        name: section.name,
        code: section.code,
        image: section.image || '',
        description: section.description || '',
        sort: section.sort,
        is_active: !section.is_active
      })
    });

    if (response.ok) {
      await loadSections();
    }
  } catch (error) {
    console.error('Failed to toggle section status:', error);
    alert('Ошибка при изменении статуса раздела');
  }
};

const deleteSection = async (section) => {
  if (!confirm(`Вы уверены, что хотите удалить раздел "${section.name}"? Все вложенные разделы и элементы также будут удалены.`)) {
    return;
  }

  try {
    const response = await fetch(`/admin/api/infoblocks/${infoBlockId.value}/sections/${section.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      loadSections();
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при удалении раздела');
    }
  } catch (error) {
    console.error('Failed to delete section:', error);
    alert('Ошибка при удалении раздела');
  }
};

onMounted(() => {
  loadInfoBlock();
  loadSections();
});
</script>
