<template>
  <div v-if="infoBlock">
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <button @click="$router.back()" class="text-gray-600 dark:text-gray-400">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </button>
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Поля инфоблока: {{ infoBlock.name }}</h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Управление полями</p>
        </div>
      </div>
      <ThemeButton variant="primary" @click="showFieldModal = true">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Добавить поле
      </ThemeButton>
    </div>

    <!-- Fields List -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>
      <div v-else-if="fields.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
        Поля не созданы. Добавьте первое поле.
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Код</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Тип</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Обязательное</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Множественное</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Сортировка</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="field in fields" :key="field.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4">
              <div class="font-medium text-gray-900 dark:text-white">{{ field.name }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ field.code }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ getFieldTypeLabel(field.type) }}</td>
            <td class="px-6 py-4 text-sm">
              <span v-if="field.is_required" class="text-green-600 dark:text-green-400">Да</span>
              <span v-else class="text-gray-400">Нет</span>
            </td>
            <td class="px-6 py-4 text-sm">
              <span v-if="field.is_multiple" class="text-blue-600 dark:text-blue-400">Да</span>
              <span v-else class="text-gray-400">Нет</span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ field.sort }}</td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="editField(field)" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300" title="Редактировать">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </button>
              <button @click="confirmDeleteField(field)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Удалить">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Field Modal -->
    <div v-if="showFieldModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ editingField ? 'Редактировать поле' : 'Добавить поле' }}
          </h3>
        </div>
        <form @submit.prevent="saveField" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label>
            <input v-model="fieldForm.name" @input="generateFieldCode" type="text" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Код *</label>
            <input v-model="fieldForm.code" type="text" required pattern="[a-z0-9_]+" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Тип *</label>
            <select v-model="fieldForm.type" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
              <option v-for="type in fieldTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
          </div>
          <div v-if="fieldForm.type === 'enum'" class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex items-center justify-between mb-3">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Варианты выбора
              </label>
              <button type="button" @click="addEnumOption"
                      class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                + Добавить вариант
              </button>
            </div>
            <div class="space-y-2">
              <div v-for="(option, index) in fieldForm.options" :key="index"
                   class="flex items-center space-x-2">
                <input v-model="option.title" type="text" placeholder="Название"
                       class="flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm" />
                <input v-model="option.code" type="text" placeholder="Код"
                       class="w-32 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm font-mono" />
                <button type="button" @click="removeEnumOption(index)"
                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
              <p v-if="fieldForm.options.length === 0" class="text-sm text-gray-400 dark:text-gray-500">
                Добавьте хотя бы один вариант
              </p>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Сортировка</label>
            <input v-model.number="fieldForm.sort" type="number" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
          </div>
          <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 cursor-pointer">
              <input v-model="fieldForm.is_required" type="checkbox" class="w-4 h-4 rounded">
              <span class="text-sm text-gray-700 dark:text-gray-300">Обязательное</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
              <input v-model="fieldForm.is_multiple" type="checkbox" class="w-4 h-4 rounded">
              <span class="text-sm text-gray-700 dark:text-gray-300">Множественное</span>
            </label>
          </div>
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeFieldModal" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
              Отмена
            </button>
            <ThemeButton type="submit" variant="primary" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </ThemeButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import ThemeButton from './ThemeButton.vue';
import { useModal } from '../composables/useModal';

const route = useRoute();
const { confirm } = useModal();

const infoBlock = ref(null);
const fields = ref([]);
const loading = ref(false);
const showFieldModal = ref(false);
const editingField = ref(null);
const saving = ref(false);

const fieldForm = ref({
  name: '',
  code: '',
  type: 'string',
  sort: 500,
  is_required: false,
  is_multiple: false,
  options: []
});

const fieldTypes = [
  { value: 'string', label: 'Строка' },
  { value: 'text', label: 'Текст' },
  { value: 'number', label: 'Число' },
  { value: 'double', label: 'Дробное число' },
  { value: 'bool', label: 'Да/Нет' },
  { value: 'date', label: 'Дата' },
  { value: 'datetime', label: 'Дата и время' },
  { value: 'image', label: 'Изображение' },
  { value: 'file', label: 'Файл' },
  { value: 'entity', label: 'Привязка к сущности' },
  { value: 'user', label: 'Пользователь' },
  { value: 'enum', label: 'Список' }
];

const getFieldTypeLabel = (type) => {
  return fieldTypes.find(t => t.value === type)?.label || type;
};

const generateFieldCode = () => {
  if (!editingField.value && fieldForm.value.name) {
    const translitMap = {
      'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e', 'ж': 'zh',
      'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
      'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts',
      'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu',
      'я': 'ya', ' ': '_'
    };

    let code = fieldForm.value.name.toLowerCase();
    for (const [rus, eng] of Object.entries(translitMap)) {
      code = code.replace(new RegExp(rus, 'g'), eng);
    }
    code = code.replace(/[^a-z0-9_]/g, '');
    fieldForm.value.code = code;
  }
};

const addEnumOption = () => {
  fieldForm.value.options.push({ code: '', title: '' });
};

const removeEnumOption = (index) => {
  fieldForm.value.options.splice(index, 1);
};

const loadInfoBlock = async () => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${route.params.id}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      infoBlock.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to load info block:', error);
  }
};

const loadFields = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/admin/api/infoblocks/${route.params.id}/fields`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      fields.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to load fields:', error);
  } finally {
    loading.value = false;
  }
};

const editField = (field) => {
  editingField.value = field;
  fieldForm.value = {
    name: field.name,
    code: field.code,
    type: field.type,
    sort: field.sort,
    is_required: field.is_required,
    is_multiple: field.is_multiple,
    options: field.settings?.options || []
  };
  showFieldModal.value = true;
};

const closeFieldModal = () => {
  showFieldModal.value = false;
  editingField.value = null;
  fieldForm.value = {
    name: '',
    code: '',
    type: 'string',
    sort: 500,
    is_required: false,
    is_multiple: false,
    options: []
  };
};

const saveField = async () => {
  saving.value = true;
  try {

    const payload = { ...fieldForm.value };

    if (payload.type === 'enum') {
      payload.settings = { options: payload.options };
    }
    delete payload.options;

    const url = editingField.value
      ? `/admin/api/infoblocks/${route.params.id}/fields/${editingField.value.id}`
      : `/admin/api/infoblocks/${route.params.id}/fields`;

    const method = editingField.value ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    if (response.ok) {
      closeFieldModal();
      loadFields();
    }
  } catch (error) {
    console.error('Failed to save field:', error);
  } finally {
    saving.value = false;
  }
};

const confirmDeleteField = async (field) => {
  const result = await confirm(
    'Удаление поля',
    `Вы уверены, что хотите удалить поле "${field.name}"?`,
    'Удалить',
    'Отмена'
  );

  if (result) {
    deleteField(field);
  }
};

const deleteField = async (field) => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${route.params.id}/fields/${field.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      loadFields();
    }
  } catch (error) {
    console.error('Failed to delete field:', error);
  }
};

onMounted(() => {
  loadInfoBlock();
  loadFields();
});
</script>
