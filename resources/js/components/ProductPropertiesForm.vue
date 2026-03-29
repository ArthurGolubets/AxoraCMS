<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Свойства товара</h3>

    <div v-if="availableProperties.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-8">
      Для этой категории не настроены свойства. Добавьте свойства в настройках категории.
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="property in availableProperties"
        :key="property.id"
        class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4"
      >
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          {{ property.name }}
          <span class="text-gray-500 text-xs ml-1">({{ property.code }})</span>
          <span v-if="property.is_inherited" class="ml-2 text-xs text-blue-600 dark:text-blue-400">
            унаследовано
          </span>
        </label>

        <!-- String type -->
        <div v-if="property.type === 'string'">
          <input
            v-if="!property.is_multiple"
            v-model="propertyValues[property.id]"
            type="text"
            :placeholder="`Введите ${property.name.toLowerCase()}`"
            class="w-full px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-gray-900 dark:text-white"
          >
          <div v-else class="space-y-2">
            <div
              v-for="(value, idx) in (ensureArrayExists(property.id), propertyValues[property.id])"
              :key="idx"
              class="flex gap-2"
            >
              <input
                v-model="propertyValues[property.id][idx]"
                type="text"
                :placeholder="`Значение ${idx + 1}`"
                class="flex-1 px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-gray-900 dark:text-white"
              >
              <button
                @click="removeMultipleValue(property.id, idx)"
                type="button"
                class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg"
              >
                ✕
              </button>
            </div>
            <button
              @click="addMultipleValue(property.id)"
              type="button"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm"
            >
              + Добавить значение
            </button>
          </div>
        </div>

        <!-- Text type -->
        <div v-else-if="property.type === 'text'">
          <textarea
            v-if="!property.is_multiple"
            v-model="propertyValues[property.id]"
            rows="4"
            :placeholder="`Введите ${property.name.toLowerCase()}`"
            class="w-full px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-gray-900 dark:text-white"
          ></textarea>
          <div v-else class="space-y-2">
            <div
              v-for="(value, idx) in (ensureArrayExists(property.id), propertyValues[property.id])"
              :key="idx"
              class="flex gap-2"
            >
              <textarea
                v-model="propertyValues[property.id][idx]"
                rows="3"
                :placeholder="`Значение ${idx + 1}`"
                class="flex-1 px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-gray-900 dark:text-white"
              ></textarea>
              <button
                @click="removeMultipleValue(property.id, idx)"
                type="button"
                class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg self-start"
              >
                ✕
              </button>
            </div>
            <button
              @click="addMultipleValue(property.id)"
              type="button"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm"
            >
              + Добавить значение
            </button>
          </div>
        </div>

        <!-- Number type -->
        <div v-else-if="property.type === 'number'">
          <input
            v-if="!property.is_multiple"
            v-model.number="propertyValues[property.id]"
            type="number"
            step="any"
            :placeholder="`Введите ${property.name.toLowerCase()}`"
            class="w-full px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-gray-900 dark:text-white"
          >
          <div v-else class="space-y-2">
            <div
              v-for="(value, idx) in (ensureArrayExists(property.id), propertyValues[property.id])"
              :key="idx"
              class="flex gap-2"
            >
              <input
                v-model.number="propertyValues[property.id][idx]"
                type="number"
                step="any"
                :placeholder="`Значение ${idx + 1}`"
                class="flex-1 px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg text-gray-900 dark:text-white"
              >
              <button
                @click="removeMultipleValue(property.id, idx)"
                type="button"
                class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg"
              >
                ✕
              </button>
            </div>
            <button
              @click="addMultipleValue(property.id)"
              type="button"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm"
            >
              + Добавить значение
            </button>
          </div>
        </div>

        <p v-if="property.is_inherited" class="text-xs text-gray-500 dark:text-gray-400 mt-2">
          Свойство унаследовано из родительской категории
        </p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProductPropertiesForm',
  props: {
    availableProperties: {
      type: Array,
      default: () => []
    },
    initialValues: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      propertyValues: {},
      isUpdatingFromParent: false
    }
  },
  mounted() {
    // Initialize values from prop on mount
    this.isUpdatingFromParent = true
    this.propertyValues = JSON.parse(JSON.stringify(this.initialValues || {}))

    console.log('ProductPropertiesForm mounted with initialValues:', this.initialValues)
    console.log('ProductPropertiesForm propertyValues after init:', this.propertyValues)

    // Ensure arrays exist for multiple-value properties before first render
    this.availableProperties.forEach(prop => {
      if (prop.is_multiple && this.propertyValues[prop.id] !== undefined) {
        // Convert to array if needed, but DON'T create empty array if no value
        if (!Array.isArray(this.propertyValues[prop.id])) {
          this.propertyValues[prop.id] = [this.propertyValues[prop.id]]
        }
      }
    })

    console.log('ProductPropertiesForm after array conversion:', this.propertyValues)

    this.$nextTick(() => {
      this.isUpdatingFromParent = false
    })
  },
  watch: {
    initialValues: {
      handler(newVal) {
        // Only update if change comes from parent
        if (!this.isUpdatingFromParent && JSON.stringify(newVal) !== JSON.stringify(this.propertyValues)) {
          this.isUpdatingFromParent = true
          this.propertyValues = JSON.parse(JSON.stringify(newVal || {}))
          this.$nextTick(() => {
            this.isUpdatingFromParent = false
          })
        }
      }
    },
    propertyValues: {
      deep: true,
      handler(newVal) {
        // Don't emit if we're updating from parent
        if (!this.isUpdatingFromParent) {
          console.log('ProductPropertiesForm emitting updated values:', newVal)
          this.$emit('update:values', newVal)
        }
      }
    }
  },
  methods: {
    getMultipleValues(propertyId) {
      // Don't mutate during render - just return what exists or create array
      const value = this.propertyValues[propertyId]

      // If no value, return array with empty string for new entries
      if (value === undefined || value === null || value === '') {
        return ['']
      }

      // If already array, return as is
      if (Array.isArray(value)) {
        return value
      }

      // If single value, wrap in array
      return [value]
    },
    ensureArrayExists(propertyId) {
      // Only mutate when explicitly called (not during render)
      if (!this.propertyValues[propertyId]) {
        this.propertyValues[propertyId] = ['']
      } else if (!Array.isArray(this.propertyValues[propertyId])) {
        this.propertyValues[propertyId] = [this.propertyValues[propertyId]]
      }
    },
    addMultipleValue(propertyId) {
      this.ensureArrayExists(propertyId)
      this.propertyValues[propertyId].push('')
    },
    removeMultipleValue(propertyId, index) {
      this.ensureArrayExists(propertyId)
      const values = this.propertyValues[propertyId]
      if (values.length > 1) {
        values.splice(index, 1)
      }
    },
    updateMultipleValue(propertyId, index, newValue) {
      this.ensureArrayExists(propertyId)
      this.propertyValues[propertyId][index] = newValue
    }
  }
}
</script>
