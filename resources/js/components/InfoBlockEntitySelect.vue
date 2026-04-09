<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Entity Type Selection (only when creating field) -->
    <div v-if="!entityTypeFixed" class="mb-3">
      <select
        v-model="selectedEntityType"
        @change="loadEntities"
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

    <!-- Search Input -->
    <div v-if="selectedEntityType" class="relative mb-2">
      <input
        v-model="searchQuery"
        @input="searchEntities"
        type="text"
        placeholder="Поиск..."
        class="w-full px-4 py-2 pr-10 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
      >
      <svg class="absolute right-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
    </div>

    <!-- Selected Entity Display -->
    <div v-if="selectedEntity" class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center justify-between">
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

    <!-- Entities List -->
    <div v-if="selectedEntityType && !selectedEntity && entities.length > 0" class="border border-gray-300 dark:border-gray-600 rounded-lg max-h-64 overflow-y-auto">
      <button
        v-for="entity in entities"
        :key="entity.id"
        @click="selectEntity(entity)"
        type="button"
        class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-600 last:border-b-0 transition"
      >
        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ entity.name }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ entity.id }}</p>
      </button>
    </div>

    <div v-if="selectedEntityType && !selectedEntity && entities.length === 0 && !loading" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
      {{ searchQuery ? 'Ничего не найдено' : 'Начните вводить для поиска' }}
    </div>

    <div v-if="loading" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
      Загрузка...
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  modelValue: [Number, String],
  label: String,
  required: Boolean,
  entityTypeFixed: String, // 'infoblock', 'product', 'catalog'
  infoBlockId: Number // For infoblock entity type
});

const emit = defineEmits(['update:modelValue', 'update:entityType']);

const selectedEntityType = ref(props.entityTypeFixed || '');
const selectedInfoBlock = ref(props.infoBlockId || '');
const selectedEntity = ref(null);
const searchQuery = ref('');
const entities = ref([]);
const infoBlocks = ref([]);
const loading = ref(false);
const catalogModuleInstalled = ref(false);

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
  entities.value = [];

  try {
    let url = '';
    if (selectedEntityType.value === 'infoblock') {
      url = `/admin/api/infoblocks/${selectedInfoBlock.value}/elements`;
    } else if (selectedEntityType.value === 'product') {
      url = '/admin/api/products';
    } else if (selectedEntityType.value === 'catalog') {
      url = '/admin/api/catalogs';
    }

    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      entities.value = data.data || data;
    }
  } catch (error) {
    console.error('Failed to load entities:', error);
  } finally {
    loading.value = false;
  }
};

const searchEntities = () => {
  // Simple client-side search
  if (!searchQuery.value) {
    loadEntities();
    return;
  }

  const query = searchQuery.value.toLowerCase();
  entities.value = entities.value.filter(e =>
    e.name.toLowerCase().includes(query) ||
    e.id.toString().includes(query)
  );
};

const selectEntity = (entity) => {
  selectedEntity.value = entity;
  emit('update:modelValue', entity.id);
  emit('update:entityType', `${selectedEntityType.value}:${selectedInfoBlock.value || ''}`);
};

const clearSelection = () => {
  selectedEntity.value = null;
  emit('update:modelValue', null);
  searchQuery.value = '';
  loadEntities();
};

// Load selected entity if modelValue is set
watch(() => props.modelValue, async (newVal) => {
  if (newVal && !selectedEntity.value) {
    // Try to load entity details
    loading.value = true;
    try {
      let url = '';
      if (selectedEntityType.value === 'infoblock' && selectedInfoBlock.value) {
        url = `/admin/api/infoblocks/${selectedInfoBlock.value}/elements/${newVal}`;
      } else if (selectedEntityType.value === 'product') {
        url = `/admin/api/products/${newVal}`;
      } else if (selectedEntityType.value === 'catalog') {
        url = `/admin/api/catalogs/${newVal}`;
      }

      if (url) {
        const response = await fetch(url, {
          headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
          selectedEntity.value = await response.json();
        }
      }
    } catch (error) {
      console.error('Failed to load entity:', error);
    } finally {
      loading.value = false;
    }
  }
}, { immediate: true });

onMounted(async () => {
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
