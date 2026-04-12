<template>
  <div class="space-y-4">

    <!-- Groups -->
    <div v-for="(group, gIndex) in groups" :key="group.id || group.temp_id" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">

      <!-- Group Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-3 flex-1">
          <!-- Group name: editable inline -->
          <input
              v-if="editingGroupIndex === gIndex"
              v-model="group.name"
              @blur="editingGroupIndex = null"
              @keyup.enter="editingGroupIndex = null"
              type="text"
              class="px-2 py-1 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white font-semibold text-base"
              ref="groupNameInput"
          >
          <h3 v-else class="text-base font-semibold text-gray-900 dark:text-white">
            {{ group.name || 'Без названия' }}
          </h3>

          <button
              v-if="editingGroupIndex !== gIndex"
              @click="startEditGroup(gIndex)"
              type="button"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
              title="Переименовать группу"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
            </svg>
          </button>
        </div>

        <div class="flex items-center space-x-2">
          <button
              @click="addProperty(group)"
              type="button"
              class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-medium"
          >
            + Свойство
          </button>
          <button
              @click="removeGroup(gIndex)"
              type="button"
              class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-sm font-medium"
              title="Удалить группу"
          >
            Удалить группу
          </button>
        </div>
      </div>

      <!-- Properties in group -->
      <div class="p-4 space-y-3">
        <div v-if="propertiesInGroup(group).length === 0" class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">
          Нет свойств в этой группе
        </div>

        <div
            v-for="(property, pIndex) in propertiesInGroup(group)"
            :key="property.id || `new-${pIndex}`"
            class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Название *</label>
              <input
                  v-model="property.name"
                  @input="generateCode(property)"
                  type="text"
                  required
                  placeholder="Цвет"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
              >
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Код *</label>
              <input
                  v-model="property.code"
                  type="text"
                  required
                  placeholder="color"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm font-mono"
              >
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Тип</label>
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
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Множественное</span>
              </label>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Порядок</label>
              <input
                  v-model.number="property.sort_order"
                  type="number"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
              >
            </div>

            <div class="flex items-end space-x-2">
              <!-- Move to another group -->
              <select
                  v-if="groups.length > 1"
                  @change="moveProperty(property, $event.target.value)"
                  class="w-full px-2 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-xs"
                  title="Перенести в группу"
              >
                <option value="">Перенести...</option>
                <option
                    v-for="g in groups"
                    :key="g.id || g.temp_id"
                    :value="g.id || g.temp_id"
                    :disabled="(g.id || g.temp_id) === (group.id || group.temp_id)"
                >
                  {{ g.name }}
                </option>
              </select>

              <button
                  @click="removeProperty(property)"
                  type="button"
                  class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm font-medium whitespace-nowrap"
              >
                Удалить
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Ungrouped properties -->
    <div v-if="ungroupedProperties.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Без группы</h3>
      </div>
      <div class="p-4 space-y-3">
        <div
            v-for="(property, pIndex) in ungroupedProperties"
            :key="property.id || `ungrouped-${pIndex}`"
            class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Название *</label>
              <input
                  v-model="property.name"
                  @input="generateCode(property)"
                  type="text"
                  required
                  placeholder="Цвет"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
              >
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Код *</label>
              <input
                  v-model="property.code"
                  type="text"
                  required
                  placeholder="color"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm font-mono"
              >
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Тип</label>
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
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Множественное</span>
              </label>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Порядок</label>
              <input
                  v-model.number="property.sort_order"
                  type="number"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
              >
            </div>

            <div class="flex items-end space-x-2">
              <select
                  v-if="groups.length > 0"
                  @change="moveProperty(property, $event.target.value)"
                  class="w-full px-2 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-xs"
              >
                <option value="">Перенести...</option>
                <option v-for="g in groups" :key="g.id || g.temp_id" :value="g.id || g.temp_id">
                  {{ g.name }}
                </option>
              </select>

              <button
                  @click="removeProperty(property)"
                  type="button"
                  class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm font-medium whitespace-nowrap"
              >
                Удалить
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom actions -->
    <div class="flex space-x-3">
      <button
          @click="addGroup"
          type="button"
          class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium"
      >
        + Добавить группу
      </button>
      <button
          @click="addProperty(null)"
          type="button"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium"
      >
        + Добавить свойство
      </button>
    </div>

    <!-- Inherited properties -->
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
let tempIdCounter = 1;

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
    initialGroups: {
      type: Array,
      default: () => []
    },
    inheritedProperties: {
      type: Array,
      default: () => []
    }
  },
  emits: ['update:properties', 'update:groups'],
  data() {
    return {
      properties: [],
      groups: [],
      editingGroupIndex: null,
      isUpdatingFromParent: false,
    }
  },
  computed: {
    ungroupedProperties() {
      return this.properties.filter(p => !p.group_id);
    }
  },
  mounted() {
    this.isUpdatingFromParent = true;
    this.properties = JSON.parse(JSON.stringify(this.initialProperties || []));
    this.groups = JSON.parse(JSON.stringify(this.initialGroups || []));

    // Ensure default group exists
    if (this.groups.length === 0) {
      this.groups.push({
        temp_id: `temp_${tempIdCounter++}`,
        name: 'Основные',
        code: 'main',
        sort_order: 100,
      });
    }

    // Assign ungrouped properties to default group
    const defaultGroup = this.groups[0];
    this.properties.forEach(p => {
      if (!p.group_id) {
        p.group_id = defaultGroup.id || defaultGroup.temp_id;
      }
    });

    this.$nextTick(() => {
      this.isUpdatingFromParent = false;
    });
  },
  watch: {
    initialProperties: {
      handler(newVal) {
        if (!this.isUpdatingFromParent && JSON.stringify(newVal) !== JSON.stringify(this.properties)) {
          this.isUpdatingFromParent = true;
          this.properties = JSON.parse(JSON.stringify(newVal || []));
          this.$nextTick(() => { this.isUpdatingFromParent = false; });
        }
      }
    },
    initialGroups: {
      handler(newVal) {
        if (!this.isUpdatingFromParent && JSON.stringify(newVal) !== JSON.stringify(this.groups)) {
          this.isUpdatingFromParent = true;
          this.groups = JSON.parse(JSON.stringify(newVal || []));
          this.$nextTick(() => { this.isUpdatingFromParent = false; });
        }
      }
    },
    properties: {
      deep: true,
      handler(newVal) {
        if (!this.isUpdatingFromParent) {
          this.$emit('update:properties', newVal);
        }
      }
    },
    groups: {
      deep: true,
      handler(newVal) {
        if (!this.isUpdatingFromParent) {
          this.$emit('update:groups', newVal);
        }
      }
    }
  },
  methods: {
    propertiesInGroup(group) {
      const groupKey = group.id || group.temp_id;
      return this.properties.filter(p => p.group_id === groupKey);
    },

    addGroup() {
      const tempId = `temp_${tempIdCounter++}`;
      this.groups.push({
        temp_id: tempId,
        name: 'Новая группа',
        code: `group_${tempIdCounter}`,
        sort_order: (this.groups.length + 1) * 100,
      });
    },

    startEditGroup(index) {
      this.editingGroupIndex = index;
      this.$nextTick(() => {
        const inputs = this.$refs.groupNameInput;
        if (inputs) {
          const input = Array.isArray(inputs) ? inputs[0] : inputs;
          input?.focus();
        }
      });
    },

    removeGroup(gIndex) {
      const group = this.groups[gIndex];
      const groupKey = group.id || group.temp_id;
      const hasProperties = this.properties.some(p => p.group_id === groupKey);

      if (hasProperties) {
        alert('Нельзя удалить группу, в которой есть свойства. Сначала удалите или перенесите свойства.');
        return;
      }

      this.groups.splice(gIndex, 1);
    },

    addProperty(group) {
      const groupKey = group ? (group.id || group.temp_id) : null;
      this.properties.push({
        code: '',
        name: '',
        type: 'string',
        is_multiple: false,
        sort_order: 500,
        group_id: groupKey,
      });
    },

    removeProperty(property) {
      if (confirm('Вы уверены, что хотите удалить это свойство?')) {
        const index = this.properties.indexOf(property);
        if (index !== -1) {
          this.properties.splice(index, 1);
        }
      }
    },

    moveProperty(property, targetGroupKey) {
      if (!targetGroupKey) return;
      property.group_id = isNaN(targetGroupKey) ? targetGroupKey : parseInt(targetGroupKey);
    },

    generateCode(property) {
      if (!property.id || !property.code) {
        const translitMap = {
          'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo',
          'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm',
          'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
          'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '',
          'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya',
          'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Е': 'E', 'Ё': 'Yo',
          'Ж': 'Zh', 'З': 'Z', 'И': 'I', 'Й': 'Y', 'К': 'K', 'Л': 'L', 'М': 'M',
          'Н': 'N', 'О': 'O', 'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U',
          'Ф': 'F', 'Х': 'H', 'Ц': 'Ts', 'Ч': 'Ch', 'Ш': 'Sh', 'Щ': 'Sch', 'Ъ': '',
          'Ы': 'Y', 'Ь': '', 'Э': 'E', 'Ю': 'Yu', 'Я': 'Ya'
        };

        property.code = property.name
            .split('')
            .map(char => translitMap[char] || char)
            .join('')
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '_')
            .replace(/_+/g, '_')
            .trim();
      }
    },

    getTypeLabel(type) {
      const labels = { string: 'Строка', text: 'Текст', number: 'Число' };
      return labels[type] || type;
    }
  }
}
</script>