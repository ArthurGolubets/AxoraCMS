<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <div class="flex items-start justify-between mb-1">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Пользовательские свойства</h3>
        <button type="button" @click="addField" class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg">+ Добавить свойство</button>
      </div>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Произвольные поля проекта. Значения доступны на фронте через <code class="px-1 bg-gray-100 dark:bg-gray-700 rounded">projectSettings['custom_fields']</code> по коду поля.
      </p>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500 dark:text-gray-400">Загрузка…</div>

    <div v-else-if="fields.length === 0" class="bg-white dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center text-sm text-gray-500 dark:text-gray-400">
      Пока нет ни одного свойства. Нажмите «Добавить свойство».
    </div>

    <div
      v-for="(field, idx) in fields"
      :key="field._key"
      class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4"
    >
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
        <div class="md:col-span-4">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Название *</label>
          <input v-model="field.name" @input="onNameInput(field)" type="text" placeholder="Например: Слоган" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white">
        </div>
        <div class="md:col-span-3">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Код</label>
          <input v-model="field.code" @input="field._codeTouched = true" type="text" placeholder="sloganu" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm font-mono text-gray-900 dark:text-white">
        </div>
        <div class="md:col-span-3">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Тип</label>
          <select v-model="field.type" @change="onTypeChange(field)" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white">
            <option v-for="t in TYPE_OPTIONS" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>
        <div class="md:col-span-2 flex items-end justify-between">
          <label v-if="field.type !== 'table'" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="field.is_multiple" @change="onTypeChange(field)" class="w-4 h-4 text-blue-600 rounded">
            Множ.
          </label>
          <span v-else></span>
          <button type="button" @click="removeField(idx)" class="text-red-600 hover:text-red-700 dark:text-red-400 px-2">✕</button>
        </div>
      </div>

      <!-- Value editor -->
      <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Значение</label>

        <!-- table -->
        <InfoBlockTableField v-if="field.type === 'table'" v-model="field.value" :rows="3" :cols="3" />

        <!-- file (component handles its own multiplicity) -->
        <InfoBlockFileUpload v-else-if="field.type === 'file'" v-model="field.value" :is-multiple="field.is_multiple" />

        <!-- single -->
        <template v-else-if="!field.is_multiple">
          <ImageUpload v-if="field.type === 'image'" v-model="field.value" />
          <TinyMCEEditor v-else-if="field.type === 'html'" v-model="field.value" />
          <textarea v-else-if="field.type === 'text'" v-model="field.value" rows="3" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white"></textarea>
          <input v-else v-model="field.value" :type="htmlInputType(field.type)" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white">
        </template>

        <!-- multiple -->
        <div v-else class="space-y-2">
          <div v-for="(val, vi) in asArray(field)" :key="vi" class="flex gap-2 items-start">
            <div class="flex-1">
              <ImageUpload v-if="field.type === 'image'" v-model="field.value[vi]" />
              <TinyMCEEditor v-else-if="field.type === 'html'" v-model="field.value[vi]" />
              <textarea v-else-if="field.type === 'text'" v-model="field.value[vi]" rows="2" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white"></textarea>
              <input v-else v-model="field.value[vi]" :type="htmlInputType(field.type)" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white">
            </div>
            <button type="button" @click="field.value.splice(vi, 1)" class="px-2 py-2 text-red-600 hover:text-red-700 dark:text-red-400">✕</button>
          </div>
          <button type="button" @click="field.value.push(field.type === 'number' ? null : '')" class="px-3 py-1.5 text-sm border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded hover:border-blue-400 hover:text-blue-600">
            + Добавить значение
          </button>
        </div>
      </div>
    </div>

    <div class="flex justify-end">
      <button type="button" @click="save" :disabled="saving" :style="buttonStyle" class="px-6 py-3 text-white rounded-lg font-medium transition-opacity hover:opacity-90 disabled:opacity-50">
        {{ saving ? 'Сохранение…' : 'Сохранить свойства' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import ImageUpload from './ImageUpload.vue';
import TinyMCEEditor from './TinyMCEEditor.vue';
import InfoBlockFileUpload from './InfoBlockFileUpload.vue';
import InfoBlockTableField from './InfoBlockTableField.vue';

const { success, error } = useModal();
const { buttonStyle } = useTheme();

const TYPE_OPTIONS = [
  { value: 'text', label: 'Текст' },
  { value: 'html', label: 'HTML' },
  { value: 'number', label: 'Число' },
  { value: 'image', label: 'Изображение' },
  { value: 'file', label: 'Файл' },
  { value: 'email', label: 'Email' },
  { value: 'phone', label: 'Телефон' },
  { value: 'table', label: 'Таблица' },
];

const fields = ref([]);
const loading = ref(true);
const saving = ref(false);
let keySeq = 1;

const translit = (s) => {
  const m = { а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'yo',ж:'zh',з:'z',и:'i',й:'y',к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'h',ц:'ts',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya' };
  return (s || '').toLowerCase().split('').map((c) => (m[c] ?? c)).join('').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
};

const htmlInputType = (t) => ({ number: 'number', email: 'email', phone: 'tel' }[t] || 'text');

const asArray = (field) => {
  if (!Array.isArray(field.value)) field.value = field.value === null || field.value === '' || field.value === undefined ? [] : [field.value];
  return field.value;
};

const onNameInput = (field) => {
  if (!field._codeTouched && !field.id) field.code = translit(field.name);
};

const onTypeChange = (field) => {
  if (field.type === 'table') {
    field.is_multiple = false;
    if (!Array.isArray(field.value)) field.value = [];
    return;
  }
  if (field.is_multiple || field.type === 'file') {
    if (!Array.isArray(field.value)) field.value = field.value ? [field.value] : [];
  } else if (Array.isArray(field.value)) {
    field.value = field.value[0] ?? (field.type === 'number' ? null : '');
  }
};

const addField = () => {
  fields.value.push({ _key: keySeq++, id: null, code: '', name: '', type: 'text', is_multiple: false, sort: fields.value.length * 10 + 10, value: '', _codeTouched: false });
};

const removeField = (idx) => {
  fields.value.splice(idx, 1);
};

const load = async () => {
  loading.value = true;
  try {
    const res = await fetch('/admin/api/settings/custom-fields', { headers: { Accept: 'application/json' } });
    if (res.ok) {
      const data = await res.json();
      fields.value = (data || []).map((f) => ({
        _key: keySeq++,
        id: f.id,
        code: f.code,
        name: f.name,
        type: f.type,
        is_multiple: !!f.is_multiple,
        sort: f.sort,
        value: f.value ?? (f.type === 'table' ? [] : (f.is_multiple ? [] : '')),
        _codeTouched: true,
      }));
    }
  } catch (e) {
    error('Не удалось загрузить пользовательские свойства');
  } finally {
    loading.value = false;
  }
};

const save = async () => {
  for (const f of fields.value) {
    if (!f.name || !f.name.trim()) {
      error('У каждого свойства должно быть название');
      return;
    }
  }
  saving.value = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const payload = {
      fields: fields.value.map((f, i) => ({
        id: f.id || undefined,
        code: f.code || undefined,
        name: f.name,
        type: f.type,
        is_multiple: f.type === 'table' ? false : !!f.is_multiple,
        sort: (i + 1) * 10,
        value: f.value,
      })),
    };
    const res = await fetch('/admin/api/settings/custom-fields', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      body: JSON.stringify(payload),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.message || 'Ошибка сохранения');
    }
    const data = await res.json();
    fields.value = (data.fields || []).map((f) => ({
      _key: keySeq++, id: f.id, code: f.code, name: f.name, type: f.type,
      is_multiple: !!f.is_multiple, sort: f.sort,
      value: f.value ?? (f.type === 'table' ? [] : (f.is_multiple ? [] : '')),
      _codeTouched: true,
    }));
    await success('Пользовательские свойства сохранены');
  } catch (e) {
    error(e.message);
  } finally {
    saving.value = false;
  }
};

onMounted(load);
</script>
