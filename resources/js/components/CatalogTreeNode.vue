<template>
  <div>
    <!-- Category Node -->
    <div
      class="group relative flex items-center py-2.5 px-3 rounded-md transition-all duration-150"
      :class="[
        'hover:bg-blue-50 dark:hover:bg-gray-700/50',
        'border-l-2 border-transparent hover:border-blue-500 dark:hover:border-blue-400'
      ]"
      :style="{ paddingLeft: `${level * 20 + 12}px` }"
    >
      <!-- Expand/Collapse Button -->
      <button
        v-if="hasChildren"
        @click="toggleExpand"
        class="mr-2 w-5 h-5 flex items-center justify-center rounded transition-all"
        :class="[
          'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
          'hover:bg-gray-200 dark:hover:bg-gray-600'
        ]"
        :title="isExpanded ? 'Свернуть' : 'Развернуть'"
      >
        <svg
          class="w-3.5 h-3.5 transition-transform duration-200"
          :class="{ 'rotate-90': isExpanded }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          stroke-width="2.5"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
      <div v-else class="w-5 mr-2"></div>

      <!-- Folder Icon with Active/Inactive State -->
      <div class="relative mr-3 flex-shrink-0">
        <svg
          class="w-5 h-5 transition-colors"
          :class="catalog.is_active ? 'text-yellow-500 dark:text-yellow-400' : 'text-gray-400 dark:text-gray-500'"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
        </svg>
        <!-- Inactive indicator -->
        <div
          v-if="!catalog.is_active"
          class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-red-500 dark:bg-red-600 rounded-full border-2 border-white dark:border-gray-800"
          title="Неактивна"
        ></div>
      </div>

      <!-- Category Name and Stats -->
      <div class="flex-1 min-w-0 mr-3">
        <div class="flex items-center gap-2">
          <span
            class="font-medium text-sm truncate"
            :class="catalog.is_active ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500 line-through'"
          >
            {{ catalog.name }}
          </span>
        </div>
        <div class="flex items-center gap-3 mt-1 text-xs">
          <span
            v-if="catalog.children_count > 0"
            class="flex items-center gap-1 text-gray-600 dark:text-gray-400"
          >
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
            </svg>
            <span class="font-medium">{{ catalog.children_count }}</span>
          </span>
          <span
            v-if="(catalog.products_count || catalog.products?.length) > 0"
            class="flex items-center gap-1 text-gray-600 dark:text-gray-400"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="font-medium">{{ catalog.products_count || catalog.products?.length }}</span>
          </span>
        </div>
      </div>

      <!-- Actions - Hidden by default, shown on hover -->
      <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
        <!-- Toggle Active Status -->
        <button
          @click.stop="$emit('toggle-active', catalog)"
          class="p-1.5 rounded-md transition-colors"
          :class="catalog.is_active
            ? 'text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30'
            : 'text-gray-400 dark:text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700'"
          :title="catalog.is_active ? 'Деактивировать' : 'Активировать'"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path v-if="catalog.is_active" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </button>

        <!-- Add Subcategory -->
        <button
          @click.stop="$emit('create-subcategory', catalog.id)"
          class="p-1.5 rounded-md transition-colors text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30"
          title="Добавить подкатегорию"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
          </svg>
        </button>

        <!-- Add Product -->
        <button
          @click.stop="$emit('create-product', catalog.id)"
          class="p-1.5 rounded-md transition-colors text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30"
          title="Добавить товар"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
        </button>

        <!-- Separator -->
        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600 mx-1"></div>

        <!-- Edit -->
        <button
          @click.stop="$emit('edit', catalog)"
          class="p-1.5 rounded-md transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700"
          title="Редактировать"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </button>

        <!-- Delete -->
        <button
          @click.stop="$emit('delete', catalog)"
          class="p-1.5 rounded-md transition-colors text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30"
          title="Удалить"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Children (Categories and Products) -->
    <div v-if="isExpanded" class="relative">
      <!-- Vertical Line for Tree Structure -->
      <div
        v-if="(catalog.children?.length > 0 || catalog.products?.length > 0)"
        class="absolute left-0 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"
        :style="{ left: `${level * 20 + 22}px` }"
      ></div>

      <!-- Child Categories -->
      <CatalogTreeNode
        v-for="child in catalog.children"
        :key="'cat-' + child.id"
        :catalog="child"
        :level="level + 1"
        @create-subcategory="$emit('create-subcategory', $event)"
        @create-product="$emit('create-product', $event)"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
        @toggle-active="$emit('toggle-active', $event)"
        @view-product="$emit('view-product', $event)"
        @edit-product="$emit('edit-product', $event)"
        @delete-product="$emit('delete-product', $event)"
        @toggle-product-active="$emit('toggle-product-active', $event)"
        @refresh="$emit('refresh')"
      />

      <!-- Products -->
      <div
        v-for="product in catalog.products"
        :key="'prod-' + product.id"
        class="group relative flex items-center py-2.5 px-3 rounded-md transition-all duration-150"
        :class="[
          'hover:bg-indigo-50 dark:hover:bg-indigo-900/20',
          'border-l-2 border-transparent hover:border-indigo-500 dark:hover:border-indigo-400'
        ]"
        :style="{ paddingLeft: `${(level + 1) * 20 + 12}px` }"
      >
        <div class="w-5 mr-2"></div>

        <!-- Product Icon with Active/Inactive State -->
        <div class="relative mr-3 flex-shrink-0">
          <svg
            class="w-5 h-5 transition-colors"
            :class="product.is_active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500'"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            stroke-width="1.5"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
          </svg>
          <!-- Inactive indicator -->
          <div
            v-if="!product.is_active"
            class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-red-500 dark:bg-red-600 rounded-full border-2 border-white dark:border-gray-800"
            title="Неактивен"
          ></div>
        </div>

        <!-- Product Name and Info -->
        <div class="flex-1 min-w-0 mr-3">
          <div class="flex items-center gap-2 flex-wrap">
            <span
              class="font-medium text-sm truncate"
              :class="product.is_active ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500 line-through'"
            >
              {{ product.name }}
            </span>
            <!-- Badges -->
            <div class="flex items-center gap-1">
              <span v-if="product.is_new" class="px-2 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 rounded-full" title="Новинка">NEW</span>
              <span v-if="product.is_hot" class="px-2 py-0.5 text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 rounded-full" title="Хит продаж">HOT</span>
              <span v-if="product.is_recommended" class="px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded-full" title="Рекомендуем">REC</span>
            </div>
          </div>
          <div class="flex items-center gap-3 mt-1 text-xs">
            <span class="font-mono text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">{{ product.sku }}</span>
            <span class="font-bold text-green-700 dark:text-green-400">{{ product.price }} ₽</span>
          </div>
        </div>

        <!-- Product Actions -->
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
          <!-- Toggle Active Status -->
          <button
            @click.stop="$emit('toggle-product-active', product)"
            class="p-1.5 rounded-md transition-colors"
            :class="product.is_active
              ? 'text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30'
              : 'text-gray-400 dark:text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700'"
            :title="product.is_active ? 'Деактивировать' : 'Активировать'"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path v-if="product.is_active" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </button>

          <!-- Edit -->
          <button
            @click.stop="$emit('edit-product', product)"
            class="p-1.5 rounded-md transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700"
            title="Редактировать товар"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>

          <!-- Delete -->
          <button
            @click.stop="$emit('delete-product', product)"
            class="p-1.5 rounded-md transition-colors text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30"
            title="Удалить товар"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  catalog: {
    type: Object,
    required: true
  },
  level: {
    type: Number,
    default: 0
  }
});

defineEmits([
  'create-subcategory',
  'create-product',
  'view',
  'edit',
  'delete',
  'view-product',
  'edit-product',
  'delete-product',
  'toggle-active',
  'toggle-product-active',
  'refresh'
]);

const isExpanded = ref(false);

const hasChildren = computed(() => {
  return (props.catalog.children_count > 0) || (props.catalog.products_count > 0) ||
         (props.catalog.children && props.catalog.children.length > 0) ||
         (props.catalog.products && props.catalog.products.length > 0);
});

const toggleExpand = async () => {
  if (!isExpanded.value && (!props.catalog.children || props.catalog.children.length === 0)) {
    // Load children on demand
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const response = await fetch(`/admin/api/catalogs/${props.catalog.id}/children`, {
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        }
      });
      const children = await response.json();
      props.catalog.children = children;

      // Load products if not already loaded
      if (!props.catalog.products) {
        const catalogResponse = await fetch(`/admin/api/catalogs/${props.catalog.id}`, {
          headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          }
        });
        const data = await catalogResponse.json();
        props.catalog.products = data.catalog.products || [];
      }
    } catch (err) {
      console.error('Error loading children:', err);
    }
  }

  isExpanded.value = !isExpanded.value;
};
</script>
