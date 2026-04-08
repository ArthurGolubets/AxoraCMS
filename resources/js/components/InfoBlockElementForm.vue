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

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Main Info -->
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

            <div class="flex items-end">
              <label class="flex items-center space-x-2 cursor-pointer pb-2">
                <input
                  v-model="form.is_active"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300 dark:border-gray-600"
                >
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Активен</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Dynamic Fields -->
      <div v-if="fields.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Свойства</h3>

        <div class="space-y-4">
          <div v-for="field in fields" :key="field.id">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ field.name }}
              <span v-if="field.is_required" class="text-red-500">*</span>
              <span v-if="field.is_multiple" class="text-xs text-gray-500">(множественное)</span>
            </label>

            <!-- Multiple Simple Fields (String, Text, Number, Double, Date, DateTime) -->
            <InfoBlockMultipleField
              v-if="(field.type === 'string' || field.type === 'text' || field.type === 'number' || field.type === 'double' || field.type === 'date' || field.type === 'datetime') && field.is_multiple"
              v-model="form.properties[field.code]"
              :field-type="field.type"
              :required="field.is_required"
            />

            <!-- Single String -->
            <input
              v-else-if="field.type === 'string'"
              v-model="form.properties[field.code]"
              type="text"
              :required="field.is_required"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >

            <!-- Single Text -->
            <textarea
              v-else-if="field.type === 'text'"
              v-model="form.properties[field.code]"
              :required="field.is_required"
              rows="4"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            ></textarea>

            <!-- Single Number -->
            <input
              v-else-if="field.type === 'number'"
              v-model.number="form.properties[field.code]"
              type="number"
              :required="field.is_required"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >

            <!-- Single Double -->
            <input
              v-else-if="field.type === 'double'"
              v-model.number="form.properties[field.code]"
              type="number"
              step="0.01"
              :required="field.is_required"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >

            <!-- Bool -->
            <ToggleSwitch
              v-else-if="field.type === 'bool'"
              v-model="form.properties[field.code]"
              label="Да"
            />

            <!-- Single Date -->
            <input
              v-else-if="field.type === 'date'"
              v-model="form.properties[field.code]"
              type="date"
              :required="field.is_required"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >

            <!-- Single DateTime -->
            <input
              v-else-if="field.type === 'datetime'"
              v-model="form.properties[field.code]"
              type="datetime-local"
              :required="field.is_required"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >

            <!-- Image -->
            <InfoBlockImageUpload
              v-else-if="field.type === 'image'"
              v-model="form.properties[field.code]"
              :required="field.is_required"
              :is-multiple="field.is_multiple"
            />

            <!-- File -->
            <InfoBlockFileUpload
              v-else-if="field.type === 'file'"
              v-model="form.properties[field.code]"
              :required="field.is_required"
              :is-multiple="field.is_multiple"
            />

            <!-- Entity -->
            <InfoBlockEntitySelect
              v-else-if="field.type === 'entity'"
              v-model="form.properties[field.code]"
              :required="field.is_required"
            />

            <!-- User -->
            <InfoBlockUserSelect
              v-else-if="field.type === 'user'"
              v-model="form.properties[field.code]"
              :required="field.is_required"
            />

            <!-- Enum -->
            <select
                v-else-if="field.type === 'enum'"
                v-model="form.properties[field.code]"
                :required="field.is_required"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
              <option value="">— Выберите значение —</option>
              <option
                  v-for="option in (field.settings?.options || [])"
                  :key="option.code"
                  :value="option.code"
              >
                {{ option.title }}
              </option>
            </select>

            <!-- Default -->
            <input
              v-else
              v-model="form.properties[field.code]"
              type="text"
              :required="field.is_required"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
          </div>
        </div>
      </div>

      <div v-else class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <p class="text-yellow-800 dark:text-yellow-300">
          Поля не настроены для данного инфоблока. <router-link :to="`/infoblocks/${infoBlockId}/fields`" class="underline">Добавить поля</router-link>
        </p>
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
import InfoBlockImageUpload from './InfoBlockImageUpload.vue';
import InfoBlockFileUpload from './InfoBlockFileUpload.vue';
import InfoBlockEntitySelect from './InfoBlockEntitySelect.vue';
import InfoBlockUserSelect from './InfoBlockUserSelect.vue';
import InfoBlockMultipleField from './InfoBlockMultipleField.vue';

const route = useRoute();
const router = useRouter();

const infoBlockId = computed(() => route.params.infoBlockId);
const elementId = computed(() => route.params.elementId);
const isEdit = computed(() => !!elementId.value);

const infoBlock = ref(null);
const fields = ref([]);
const saving = ref(false);
const codeManuallyEdited = ref(false);

const form = ref({
  name: '',
  code: '',
  sort: 500,
  is_active: true,
  properties: {}
});

// Auto-generate code from name
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

// Watch name field and auto-generate code
watch(() => form.value.name, (newName) => {
  if (!isEdit.value && !codeManuallyEdited.value) {
    form.value.code = generateCode(newName);
  }
});

// Track manual code edits
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

      // Initialize properties with default values
      fields.value.forEach(field => {
        if (!(field.code in form.value.properties)) {
          if (field.is_multiple && ['string', 'text', 'number', 'double', 'date', 'datetime'].includes(field.type)) {
            form.value.properties[field.code] = [];
          } else if (field.type === 'bool') {
            form.value.properties[field.code] = false;
          } else {
            form.value.properties[field.code] = '';
          }
        }
      });
    }
  } catch (error) {
    console.error('Failed to load fields:', error);
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
        sort: element.sort,
        is_active: element.is_active,
        properties: element.properties ? { ...element.properties } : {}
      };
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
      router.push(`/infoblocks/${infoBlockId.value}/elements`);
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
  await loadFields();
  if (isEdit.value) {
    await loadElement();
  }
});
</script>
