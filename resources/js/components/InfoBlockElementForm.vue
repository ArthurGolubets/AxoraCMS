<template>
  <div v-if="infoBlock">
    <div class="mb-6 flex items-center space-x-3">
      <button @click="$router.back()" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </button>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ isEdit ? 'Редактировать элемент' : 'Создать элемент' }}: {{ infoBlock.name }}
      </h2>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            type="button"
            :class="[
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Main Tab -->
      <div v-show="activeTab === 'main'">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Основная информация</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label>
              <input
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
            </div>

            <div v-if="infoBlock.type === 'catalog'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Раздел</label>
              <select
                  v-model="form.section_id"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
                <option :value="null">Без раздела</option>
                <option v-for="section in sections" :key="section.id" :value="section.id">
                  {{ section.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Символьный код</label>
              <input
                  v-model="form.code"
                  type="text"
                  pattern="[a-z0-9_]*"
                  placeholder="Оставьте пустым для автогенерации"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono"
              >
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Сортировка</label>
                <input
                    v-model.number="form.sort"
                    type="number"
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
              </div>

              <div class="flex items-end pb-2">
                <ToggleSwitch v-model="form.is_active" :theme-color="themeColor" label="Активен" />
              </div>
            </div>

            <div v-for="field in visibleFields" :key="field.id" class="pt-4 border-t border-gray-100 dark:border-gray-700">
              <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <span>
                  {{ field.name }}
                  <span v-if="field.is_required && !field.is_system" class="text-red-500">*</span>
                </span>
                <span v-if="field.is_multiple" class="text-xs font-normal text-gray-500">(множественное)</span>
                <span v-if="field.is_system" class="px-1.5 py-0.5 text-[11px] font-normal rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">Системное</span>
              </label>
              <InfoBlockFieldInput v-model="form.properties[field.code]" :field="field" :disabled="field.is_system" />
            </div>
          </div>
        </div>
      </div>

      <!-- Content Tab -->
      <div v-show="activeTab === 'content'">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <TinyMCEEditor v-model="form.content" label="Контент" :height="400" />
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3">
        <button
            type="button"
            @click="$router.back()"
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
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from './ThemeButton.vue';
import ToggleSwitch from './ToggleSwitch.vue';
import TinyMCEEditor from './TinyMCEEditor.vue';
import InfoBlockFieldInput from './InfoBlockFieldInput.vue';
import { useTheme } from '../composables/useTheme';
import { emptyFieldValue, initialFieldValue } from '../utils/infoBlockFields';

const route = useRoute();
const router = useRouter();
const { themeColor } = useTheme();

const infoBlockId = computed(() => route.params.infoBlockId);
const elementId = computed(() => route.params.elementId);
const isEdit = computed(() => !!elementId.value);

const infoBlock = ref(null);
const fields = ref([]);
const sections = ref([]);
const saving = ref(false);
const codeManuallyEdited = ref(false);
const activeTab = ref('main');

const tabs = [
  { id: 'main', label: 'Основное' },
  { id: 'content', label: 'Контент' },
];

const visibleFields = computed(() => fields.value.filter((field) => !field.is_hidden));

const form = ref({
  name: '',
  code: '',
  section_id: null,
  sort: 500,
  is_active: true,
  content: '',
  properties: {}
});

const generateCode = (name) => {
  return name
      .toLowerCase()
      .replace(/[^a-zа-яё0-9\s]/gi, '')
      .replace(/[а-яё]/gi, (char) => {
        const ru = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';
        const en = ['a','b','v','g','d','e','yo','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya'];
        const index = ru.indexOf(char);
        return index >= 0 ? en[index] : char;
      })
      .trim()
      .replace(/\s+/g, '_');
};

watch(() => form.value.name, (newName) => {
  if (!isEdit.value && !codeManuallyEdited.value) {
    form.value.code = generateCode(newName);
  }
});

watch(() => form.value.code, () => {
  if (!isEdit.value && form.value.code !== generateCode(form.value.name)) {
    codeManuallyEdited.value = true;
  }
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

const loadFields = async () => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${infoBlockId.value}/fields`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      fields.value = Array.isArray(data) ? data : (data.data || []);

      fields.value.forEach(field => {
        if (!(field.code in form.value.properties)) {
          form.value.properties[field.code] = initialFieldValue(field);
        }
      });
    }
  } catch (error) {
    console.error('Failed to load fields:', error);
  }
};

const loadSections = async () => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${infoBlockId.value}/sections/list`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      sections.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to load sections:', error);
  }
};

const loadElement = async () => {
  if (!isEdit.value) return;

  try {
    const response = await fetch(`/admin/api/infoblocks/${infoBlockId.value}/elements/${elementId.value}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const element = await response.json();
      form.value = {
        name: element.name,
        code: element.code || '',
        section_id: element.section_id || null,
        sort: element.sort,
        is_active: element.is_active,
        content: element.content || '',
        properties: element.properties ? { ...element.properties } : {}
      };
      fields.value.forEach((field) => {
        if (!(field.code in form.value.properties)) {
          form.value.properties[field.code] = emptyFieldValue(field);
        }
      });
    }
  } catch (error) {
    console.error('Failed to load element:', error);
  }
};

const handleSubmit = async () => {
  saving.value = true;
  try {
    const url = isEdit.value
        ? `/admin/api/infoblocks/${infoBlockId.value}/elements/${elementId.value}`
        : `/admin/api/infoblocks/${infoBlockId.value}/elements`;

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
      // Redirect based on info block type
      if (infoBlock.value?.type === 'catalog') {
        router.push(`/infoblocks/${infoBlockId.value}/sections`);
      } else {
        router.push(`/infoblocks/${infoBlockId.value}/elements`);
      }
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при сохранении');
    }
  } catch (error) {
    console.error('Failed to save element:', error);
    alert('Ошибка при сохранении');
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadInfoBlock();
  if (infoBlock.value?.type === 'catalog') {
    await loadSections();

    // Set section_id from URL query parameter
    const urlParams = new URLSearchParams(window.location.search);
    const sectionIdFromUrl = urlParams.get('section_id');
    if (sectionIdFromUrl && !isEdit.value) {
      form.value.section_id = parseInt(sectionIdFromUrl);
    }
  }
  await loadFields();
  if (isEdit.value) {
    await loadElement();
  }
});
</script>