<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
      <span v-if="isMultiple" class="text-xs text-gray-500 ml-2">(множественный выбор)</span>
    </label>

    <!-- Entity Type Selection (only when creating field) -->
    <div v-if="!entityTypeFixed" class="mb-3">
      <select
        v-model="selectedEntityType"
        @change="onEntityTypeChange"
        class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
      >
        <option value="">Выберите тип сущности</option>
        <option value="infoblock">Элемент инфоблока</option>
        <option value="product" v-if="catalogModuleInstalled">Товар</option>
        <option value="catalog" v-if="catalogModuleInstalled">Категория</option>
      </select>
    </div>

    <!-- InfoBlock Selection (if entity type is infoblock) -->
    <div v-if="selectedEntityType === 'infoblock' && !entityTypeFixed" class="mb-3">
      <select
        v-model="selectedInfoBlock"
        @change="loadEntities"
        class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
      >
        <option value="">Выберите инфоблок</option>
        <option v-for="ib in infoBlocks" :key="ib.id" :value="ib.id">{{ ib.name }}</option>
      </select>
    </div>

    <!-- Debug Info -->
    <div v-if="true" class="mb-3 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 rounded text-xs font-mono">
      <p><strong>🔍 Debug Info:</strong></p>
      <p>📋 isMultiple: <span class="font-bold">{{ isMultiple }}</span></p>
      <p>💾 modelValue: <span class="text-blue-600">{{ JSON.stringify(modelValue) }}</span></p>
      <p>📊 selectedEntities.length: <span class="font-bold">{{ selectedEntities.length }}</span></p>
      <p>📝 selectedEntities IDs: <span class="text-green-600">[{{ selectedEntities.map(e => e.id).join(', ') }}]</span></p>
      <p>✏️ selectedEntities Names: <span class="text-purple-600">{{ selectedEntities.map(e => e.name).join(', ') || '(empty)' }}</span></p>
      <p>🎯 selectedEntity: {{ selectedEntity?.name || 'null' }}</p>
      <p>🏷️ entityType: {{ selectedEntityType }}</p>
      <p>📦 infoBlockId: {{ selectedInfoBlock }}</p>
    </div>

    <!-- Selected Entities Display (for multiple selection) -->
    <div v-if="isMultiple && selectedEntities.length > 0" class="mb-3 space-y-2">
      <div
        v-for="entity in selectedEntities"
        :key="entity.id"
        class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center justify-between"
      >
        <div>
          <p class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ entity.name }}</p>
          <p class="text-xs text-blue-700 dark:text-blue-300">ID: {{ entity.id }}</p>
        </div>
        <button
          @click="removeEntity(entity.id)"
          type="button"
          class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Selected Entity Display (for single selection) -->
    <div v-else-if="!isMultiple && selectedEntity" class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ selectedEntity.name }}</p>
        <p class="text-xs text-blue-700 dark:text-blue-300">ID: {{ selectedEntity.id }}</p>
      </div>
      <button
        @click="clearSelection"
        type="button"
        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Search Input -->
    <div v-if="selectedEntityType && (isMultiple || !selectedEntity)" class="relative mb-2">
      <input
        v-model="searchQuery"
        @input="debouncedSearch"
        type="text"
        placeholder="Начните вводить для поиска..."
        class="w-full px-4 py-2 pr-10 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
      >
      <svg class="absolute right-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
    </div>

    <!-- Entities List -->
    <div v-if="selectedEntityType && (isMultiple || !selectedEntity) && filteredEntities.length > 0"
         class="border border-gray-300 dark:border-gray-600 rounded-lg max-h-64 overflow-y-auto">
      <button
        v-for="entity in filteredEntities"
        :key="entity.id"
        @click="selectEntity(entity)"
        type="button"
        :disabled="isMultiple && isEntitySelected(entity.id)"
        :class="[
          'w-full text-left px-4 py-3 border-b border-gray-200 dark:border-gray-600 last:border-b-0 transition',
          isMultiple && isEntitySelected(entity.id)
            ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-50'
            : 'hover:bg-gray-50 dark:hover:bg-gray-700'
        ]"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ entity.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ entity.id }}</p>
          </div>
          <svg v-if="isMultiple && isEntitySelected(entity.id)" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
        </div>
      </button>
    </div>

    <div v-if="selectedEntityType && (isMultiple || !selectedEntity) && filteredEntities.length === 0 && !loading" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4 border border-gray-300 dark:border-gray-600 rounded-lg">
      {{ searchQuery ? 'Ничего не найдено' : 'Введите запрос для поиска' }}
    </div>

    <div v-if="loading" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
      <div class="flex items-center justify-center space-x-2">
        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Загрузка...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';

const props = defineProps({
  modelValue: [Number, String, Array],
  label: String,
  required: Boolean,
  entityTypeFixed: String, // 'infoblock', 'product', 'catalog'
  infoBlockId: Number, // For infoblock entity type
  isMultiple: Boolean // Enable multiple selection
});

const emit = defineEmits(['update:modelValue', 'update:entityType']);

const selectedEntityType = ref(props.entityTypeFixed || '');
const selectedInfoBlock = ref(props.infoBlockId || null);

// Watch for changes in props
watch(() => props.entityTypeFixed, (newVal) => {
  if (newVal && newVal !== selectedEntityType.value) {
    console.log('entityTypeFixed changed to:', newVal);
    selectedEntityType.value = newVal;
  }
});

watch(() => props.infoBlockId, (newVal) => {
  if (newVal && newVal !== selectedInfoBlock.value) {
    console.log('infoBlockId changed to:', newVal);
    selectedInfoBlock.value = newVal;
  }
});
const selectedEntity = ref(null);
const selectedEntities = ref([]);
const searchQuery = ref('');
const entities = ref([]);
const allEntities = ref([]);
const infoBlocks = ref([]);
const loading = ref(false);
const catalogModuleInstalled = ref(false);
let debounceTimeout = null;

// Computed filtered entities based on search query
const filteredEntities = computed(() => {
  if (!searchQuery.value) {
    return entities.value;
  }

  const query = searchQuery.value.toLowerCase();
  return allEntities.value.filter(e =>
    e.name.toLowerCase().includes(query) ||
    e.id.toString().includes(query)
  );
});

// Check if catalog module is installed
const checkCatalogModule = async () => {
  try {
    const response = await fetch('/admin/api/modules', {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      const shopModule = data.modules?.find(m => m.id === 'shop');
      catalogModuleInstalled.value = shopModule?.installed || false;
    }
  } catch (error) {
    console.error('Failed to check catalog module:', error);
  }
};

// Load infoBlocks list
const loadInfoBlocks = async () => {
  try {
    const response = await fetch('/admin/api/infoblocks', {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      infoBlocks.value = data.data || data;
    }
  } catch (error) {
    console.error('Failed to load infoblocks:', error);
  }
};

const loadEntities = async () => {
  if (!selectedEntityType.value) return;
  if (selectedEntityType.value === 'infoblock' && !selectedInfoBlock.value) return;

  loading.value = true;

  try {
    let url = '';
    const params = new URLSearchParams();

    if (selectedEntityType.value === 'infoblock') {
      url = `/admin/api/infoblocks/${selectedInfoBlock.value}/elements`;
    } else if (selectedEntityType.value === 'product') {
      url = '/admin/api/products';
    } else if (selectedEntityType.value === 'catalog') {
      url = '/admin/api/catalogs';
    }

    // Add search parameter if exists
    if (searchQuery.value) {
      params.append('search', searchQuery.value);
    }

    const fullUrl = params.toString() ? `${url}?${params.toString()}` : url;

    const response = await fetch(fullUrl, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      const loadedEntities = data.data || data;

      if (!searchQuery.value) {
        allEntities.value = loadedEntities;
      }
      entities.value = loadedEntities;
    }
  } catch (error) {
    console.error('Failed to load entities:', error);
  } finally {
    loading.value = false;
  }
};

// Debounced search
const debouncedSearch = () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    if (searchQuery.value.length >= 2 || searchQuery.value.length === 0) {
      loadEntities();
    }
  }, 300);
};

const onEntityTypeChange = () => {
  searchQuery.value = '';
  entities.value = [];
  allEntities.value = [];
  selectedEntity.value = null;
  selectedEntities.value = [];
  loadEntities();
};

const selectEntity = (entity) => {
  console.log('selectEntity called', { entity, isMultiple: props.isMultiple });

  if (props.isMultiple) {
    // Add to selected entities array
    if (!isEntitySelected(entity.id)) {
      selectedEntities.value.push(entity);
      // Filter out null/undefined values
      const newValue = selectedEntities.value
        .map(e => e.id)
        .filter(id => id !== null && id !== undefined);
      console.log('Emitting multiple values:', newValue);
      emit('update:modelValue', newValue);
      emit('update:entityType', `${selectedEntityType.value}:${selectedInfoBlock.value || ''}`);
    }
  } else {
    // Single selection
    selectedEntity.value = entity;
    console.log('Emitting single value:', entity.id);
    emit('update:modelValue', entity.id);
    emit('update:entityType', `${selectedEntityType.value}:${selectedInfoBlock.value || ''}`);
  }
};

const removeEntity = (entityId) => {
  selectedEntities.value = selectedEntities.value.filter(e => e.id !== entityId);
  const newValue = selectedEntities.value
    .map(e => e.id)
    .filter(id => id !== null && id !== undefined);
  console.log('Removing entity, new values:', newValue);
  emit('update:modelValue', newValue);
};

const clearSelection = () => {
  if (props.isMultiple) {
    selectedEntities.value = [];
    emit('update:modelValue', []);
  } else {
    selectedEntity.value = null;
    emit('update:modelValue', null);
  }
  searchQuery.value = '';
};

const isEntitySelected = (entityId) => {
  return selectedEntities.value.some(e => e.id === entityId);
};

// Load entity details by ID
const loadEntityById = async (id) => {
  if (!id) {
    console.log('loadEntityById: id is null/undefined');
    return null;
  }

  try {
    let url = '';
    if (selectedEntityType.value === 'infoblock' && selectedInfoBlock.value) {
      url = `/admin/api/infoblocks/${selectedInfoBlock.value}/elements/${id}`;
    } else if (selectedEntityType.value === 'product') {
      url = `/admin/api/products/${id}`;
    } else if (selectedEntityType.value === 'catalog') {
      url = `/admin/api/catalogs/${id}`;
    }

    console.log('loadEntityById:', { id, url, entityType: selectedEntityType.value, infoBlockId: selectedInfoBlock.value });

    if (url) {
      const response = await fetch(url, {
        headers: { 'Accept': 'application/json' }
      });
      console.log('Response status:', response.status);
      if (response.ok) {
        const data = await response.json();
        console.log('Loaded entity data:', data);
        console.log('Entity structure:', {
          hasId: 'id' in data,
          hasName: 'name' in data,
          keys: Object.keys(data),
          id: data.id,
          name: data.name,
          fullData: data
        });
        return data;
      } else {
        console.error('Response not OK:', response.status, await response.text());
      }
    }
  } catch (error) {
    console.error('Failed to load entity:', error);
  }
  return null;
};

// Load selected entities if modelValue is set
watch(() => props.modelValue, async (newVal) => {
  console.log('modelValue changed:', { newVal, isMultiple: props.isMultiple, isArray: Array.isArray(newVal) });

  if (!newVal || (Array.isArray(newVal) && newVal.length === 0)) {
    selectedEntity.value = null;
    selectedEntities.value = [];
    return;
  }

  // Don't reload if we already have the entities loaded
  if (props.isMultiple && Array.isArray(newVal)) {
    const currentIds = selectedEntities.value.map(e => e.id).sort().join(',');
    const newIds = newVal.filter(id => id !== null && id !== undefined).sort().join(',');
    if (currentIds === newIds && selectedEntities.value.length > 0) {
      console.log('Entities already loaded, skipping');
      return;
    }
  }

  loading.value = true;

  try {
    if (props.isMultiple && Array.isArray(newVal)) {
      // Filter out null/undefined values
      const validIds = newVal.filter(id => id !== null && id !== undefined);
      console.log('Loading multiple entities:', validIds);

      const loadedEntities = [];
      for (const id of validIds) {
        const entity = await loadEntityById(id);
        console.log('Entity loaded for ID', id, ':', entity);
        if (entity && entity.id && entity.name) {
          loadedEntities.push(entity);
        } else {
          console.warn('Entity loaded but missing id or name:', entity);
        }
      }
      console.log('All loaded entities:', loadedEntities);
      selectedEntities.value = loadedEntities;
    } else if (!props.isMultiple && newVal) {
      // Load single entity
      console.log('Loading single entity:', newVal);
      if (!selectedEntity.value || selectedEntity.value.id !== newVal) {
        const entity = await loadEntityById(newVal);
        if (entity) {
          console.log('Loaded entity:', entity);
          selectedEntity.value = entity;
        }
      }
    }
  } catch (error) {
    console.error('Failed to load selected entities:', error);
  } finally {
    loading.value = false;
  }
}, { immediate: true });

onMounted(async () => {
  console.log('InfoBlockEntitySelect mounted', {
    entityTypeFixed: props.entityTypeFixed,
    infoBlockId: props.infoBlockId,
    isMultiple: props.isMultiple,
    modelValue: props.modelValue
  });

  await checkCatalogModule();
  if (selectedEntityType.value === 'infoblock') {
    await loadInfoBlocks();
    if (selectedInfoBlock.value) {
      await loadEntities();
    }
  } else if (selectedEntityType.value) {
    await loadEntities();
  }
});
</script>
