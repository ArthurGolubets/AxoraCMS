<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Свойства товаров</h3>
      <button
        @click="addProperty"
        type="button"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium"
      >
        + Добавить свойство
      </button>
    </div>

    <div v-if="properties.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-8">
      Нет добавленных свойств. Свойства позволяют задавать характеристики товаров.
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="(property, index) in properties"
        :key="property.id || `new-${index}`"
        class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Код *
            </label>
            <input
              v-model="property.code"
              type="text"
              required
              placeholder="color"
              class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Название *
            </label>
            <input
              v-model="property.name"
              type="text"
              required
              placeholder="Цвет"
              class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Тип
            </label>
            <select
              v-model="property.type"
              class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
            >
              <option value="string">Строка</option>
              <option value="text">Текст</option>
              <option value="number">Число</option>
            </select>
          </div>

          <div class="flex items-end">
            <label class="flex items-center cursor-pointer">
              <input
                v-model="property.is_multiple"
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
              >
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Множественное</span>
            </label>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Порядок
            </label>
            <input
              v-model.number="property.sort_order"
              type="number"
              class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
            >
          </div>

          <div class="flex items-end">
            <button
              @click="removeProperty(index)"
              type="button"
              class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm font-medium w-full"
            >
              Удалить
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="inheritedProperties.length > 0" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
      <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
        Унаследованные свойства (из родительских категорий)
      </h4>
      <div class="space-y-2">
        <div
          v-for="property in inheritedProperties"
          :key="property.id"
          class="bg-gray-100 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg p-3 opacity-75"
        >
          <div class="grid grid-cols-1 md:grid-cols-5 gap-2 text-sm">
            <div>
              <span class="text-gray-600 dark:text-gray-400">Код:</span>
              <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ property.code }}</span>
            </div>
            <div>
              <span class="text-gray-600 dark:text-gray-400">Название:</span>
              <span class="ml-2 text-gray-900 dark:text-white">{{ property.name }}</span>
            </div>
            <div>
              <span class="text-gray-600 dark:text-gray-400">Тип:</span>
              <span class="ml-2 text-gray-900 dark:text-white">{{ getTypeLabel(property.type) }}</span>
            </div>
            <div>
              <span class="text-gray-600 dark:text-gray-400">Множ.:</span>
              <span class="ml-2 text-gray-900 dark:text-white">{{ property.is_multiple ? 'Да' : 'Нет' }}</span>
            </div>
            <div class="text-gray-500 dark:text-gray-400 italic text-xs">
              (из категории: {{ property.catalog_name }})
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CatalogPropertiesManager',
  props: {
    catalogId: {
      type: Number,
      default: null
    },
    initialProperties: {
      type: Array,
      default: () => []
    },
    inheritedProperties: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      properties: []
    }
  },
  watch: {
    initialProperties: {
      immediate: true,
      handler(newVal) {
        this.properties = JSON.parse(JSON.stringify(newVal || []))
      }
    },
    properties: {
      deep: true,
      handler(newVal) {
        this.$emit('update:properties', newVal)
      }
    }
  },
  methods: {
    addProperty() {
      this.properties.push({
        code: '',
        name: '',
        type: 'string',
        is_multiple: false,
        sort_order: 500
      })
    },
    removeProperty(index) {
      if (confirm('Вы уверены, что хотите удалить это свойство?')) {
        this.properties.splice(index, 1)
      }
    },
    getTypeLabel(type) {
      const labels = {
        string: 'Строка',
        text: 'Текст',
        number: 'Число'
      }
      return labels[type] || type
    }
  }
}
</script>
