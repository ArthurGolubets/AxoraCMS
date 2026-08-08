<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ isEdit ? 'Редактировать раздел' : 'Создать раздел' }}
          </h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Parent Section -->
          <div v-if="!isEdit">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Родительский раздел</label>
            <select
              v-model="form.parent_id"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
              <option :value="null">Корневой раздел</option>
              <option v-for="sec in allSections" :key="sec.id" :value="sec.id">
                {{ sec.name }}
              </option>
            </select>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label>
            <input
              v-model="form.name"
              @input="generateCode"
              type="text"
              required
              placeholder="Например: Электроника"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
          </div>

          <!-- Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Системное имя *</label>
            <input
              v-model="form.code"
              type="text"
              required
              placeholder="elektronika"
              pattern="[a-z0-9_]+"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono"
            >
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Только латинские буквы, цифры и нижнее подчеркивание</p>
          </div>

          <!-- Image -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Аватар</label>
            <InfoBlockImageUpload
              v-model="form.image"
              :is-multiple="false"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Описание</label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Описание раздела"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            ></textarea>
          </div>

          <!-- Sort -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Сортировка</label>
            <input
              v-model.number="form.sort"
              type="number"
              placeholder="500"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
          </div>

          <!-- Active -->
          <div>
            <label class="flex items-center space-x-2 cursor-pointer">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600"
              >
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Активен</span>
            </label>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
            >
              Отмена
            </button>
            <ThemeButton type="submit" variant="primary" :disabled="saving">
              {{ saving ? 'Сохранение...' : (isEdit ? 'Сохранить' : 'Создать') }}
            </ThemeButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ThemeButton from './ThemeButton.vue';
import InfoBlockImageUpload from './InfoBlockImageUpload.vue';

const props = defineProps({
  infoBlockId: {
    type: [String, Number],
    required: true
  },
  section: {
    type: Object,
    default: null
  },
  parentId: {
    type: [String, Number],
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const isEdit = computed(() => !!props.section);
const saving = ref(false);
const allSections = ref([]);

const form = ref({
  parent_id: props.parentId,
  name: '',
  code: '',
  image: '',
  description: '',
  sort: 500,
  is_active: true
});

const generateCode = () => {
  if (!isEdit.value && form.value.name) {
    const translitMap = {
      'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e', 'ж': 'zh',
      'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
      'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts',
      'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu',
      'я': 'ya', ' ': '_'
    };

    let code = form.value.name.toLowerCase();
    for (const [rus, eng] of Object.entries(translitMap)) {
      code = code.replace(new RegExp(rus, 'g'), eng);
    }
    code = code.replace(/[^a-z0-9_]/g, '');
    form.value.code = code;
  }
};

const loadSections = async () => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${props.infoBlockId}/sections/list`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      allSections.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to load sections:', error);
  }
};

const handleSubmit = async () => {
  saving.value = true;
  try {
    const url = isEdit.value
      ? `/admin/api/infoblocks/${props.infoBlockId}/sections/${props.section.id}`
      : `/admin/api/infoblocks/${props.infoBlockId}/sections`;

    const method = isEdit.value ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    if (response.ok) {
      emit('saved');
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при сохранении');
    }
  } catch (error) {
    console.error('Failed to save section:', error);
    alert('Ошибка при сохранении');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadSections();

  if (isEdit.value && props.section) {
    form.value = {
      parent_id: props.section.parent_id,
      name: props.section.name || '',
      code: props.section.code || '',
      image: props.section.image || '',
      description: props.section.description || '',
      sort: props.section.sort || 500,
      is_active: props.section.is_active !== undefined ? props.section.is_active : true
    };
  }
});
</script>
