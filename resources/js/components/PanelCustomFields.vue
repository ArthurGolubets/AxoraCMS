<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Пользовательские свойства</h3>
        <button type="button" @click="openCreateModal" :style="buttonStyle" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-white rounded-lg transition-opacity hover:opacity-90">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Добавить свойство
        </button>
      </div>

      <div v-if="loading" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Загрузка…</div>

      <div v-else-if="fields.length === 0" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
        Пока нет ни одного свойства. Нажмите «Добавить свойство».
      </div>

      <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <div v-for="(field, idx) in fields" :key="field._key" class="px-6 py-5">
          <div class="flex items-center justify-between gap-3 mb-2">
            <div class="flex items-center gap-2 min-w-0">
              <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ field.name }}</span>
              <span class="shrink-0 px-1.5 py-0.5 text-[11px] rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                {{ typeLabel(field.type) }}<template v-if="field.is_multiple"> · множ.</template>
              </span>
            </div>

            <div class="flex items-center gap-1 shrink-0">
              <!-- Usage hint -->
              <div class="relative" data-help-popover>
                <button type="button" @click="toggleHelp(field._key)" :class="iconButtonClass" title="Как вывести на сайте">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
                <div v-if="openHelpKey === field._key" class="absolute right-0 top-full mt-2 z-30 w-[22rem] max-w-[calc(100vw-2rem)] p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg">
                  <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Вывод в Blade-шаблоне</p>
                  <pre class="text-xs font-mono whitespace-pre-wrap break-all p-3 rounded bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200">{{ usageSnippet(field) }}</pre>
                  <button type="button" @click="copySnippet(field)" class="mt-2 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    {{ copiedKey === field._key ? 'Скопировано' : 'Скопировать' }}
                  </button>
                </div>
              </div>

              <!-- Field settings -->
              <button type="button" @click="openEditModal(idx)" :class="iconButtonClass" title="Параметры свойства">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              </button>

              <!-- Delete -->
              <button type="button" @click="removeField(idx)" class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Удалить свойство">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>

          <!-- Value editor -->
          <PanelCustomFieldInput v-model="field.value" :field="field" />
          <button
            v-if="canResetToDefault(field)"
            type="button"
            @click="resetToDefault(field)"
            class="mt-2 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400"
          >
            Вернуть значение по умолчанию
          </button>
        </div>
      </div>
    </div>

    <div v-if="fields.length" class="flex justify-end">
      <button type="button" @click="saveValues" :disabled="saving" :style="buttonStyle" class="px-6 py-3 text-white rounded-lg font-medium transition-opacity hover:opacity-90 disabled:opacity-50">
        {{ saving ? 'Сохранение…' : 'Сохранить значения' }}
      </button>
    </div>

    <!-- Create / edit field side panel -->
    <SidePanel v-if="modal.show" :title="modal.index === null ? 'Новое свойство' : 'Параметры свойства'" @close="closeModal">
      <form id="panel-custom-field-form" @submit.prevent="submitModal" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Название *</label>
          <input ref="nameInput" v-model="modal.form.name" @input="onModalNameInput" type="text" placeholder="Например: Слоган" :class="inputClass">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Код</label>
          <input v-model="modal.form.code" @input="modal.codeTouched = true" type="text" placeholder="slogan" :class="[inputClass, 'font-mono']">
          <p v-if="modal.index !== null" class="mt-1 text-xs text-gray-500 dark:text-gray-400">После смены кода обновите его в шаблонах сайта.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Тип</label>
          <select v-model="modal.form.type" @change="resetModalDefault" :class="inputClass">
            <option v-for="t in TYPE_OPTIONS" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>
        <div v-if="supportsMultiple(modal.form.type)" class="flex items-center justify-between">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Множественное</span>
          <ToggleSwitch :model-value="modal.form.is_multiple" @update:model-value="setModalMultiple" :theme-color="themeColor" />
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Значение по умолчанию</label>
          <PanelCustomFieldInput
            :key="`${modal.form.type}-${modalIsMultiple}`"
            v-model="modal.form.default_value"
            :field="{ type: modal.form.type, is_multiple: modalIsMultiple }"
          />
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Подставляется в новое свойство и выводится на сайте, пока значение не заполнено.</p>
        </div>
      </form>

      <template #footer>
        <div class="flex justify-end gap-3">
          <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
            Отмена
          </button>
          <button type="submit" form="panel-custom-field-form" :disabled="saving" :style="buttonStyle" class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-opacity hover:opacity-90 disabled:opacity-50">
            {{ modal.index === null ? 'Создать' : 'Сохранить' }}
          </button>
        </div>
      </template>
    </SidePanel>

    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import ToggleSwitch from './ToggleSwitch.vue';
import ConfirmModal from './ConfirmModal.vue';
import SidePanel from './SidePanel.vue';
import PanelCustomFieldInput from './PanelCustomFieldInput.vue';

const { success, error } = useModal();
const { buttonStyle, themeColor } = useTheme();

const TYPE_OPTIONS = [
  { value: 'text', label: 'Текст' },
  { value: 'html', label: 'HTML' },
  { value: 'number', label: 'Число' },
  { value: 'boolean', label: 'Да/Нет' },
  { value: 'image', label: 'Изображение' },
  { value: 'file', label: 'Файл' },
  { value: 'email', label: 'Email' },
  { value: 'phone', label: 'Телефон' },
  { value: 'table', label: 'Таблица' },
];
const SINGLE_ONLY_TYPES = ['table', 'boolean'];

const inputClass = 'w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white';
const iconButtonClass = 'p-1.5 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition-colors';

const fields = ref([]);
const loading = ref(true);
const saving = ref(false);
const openHelpKey = ref(null);
const copiedKey = ref(null);
const confirmModal = ref(null);
const nameInput = ref(null);
let keySeq = 1;

const modal = reactive({
  show: false,
  index: null,
  codeTouched: false,
  form: { name: '', code: '', type: 'text', is_multiple: false, default_value: '' },
});

const modalIsMultiple = computed(() => supportsMultiple(modal.form.type) && modal.form.is_multiple);

const translit = (s) => {
  const m = { а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'yo',ж:'zh',з:'z',и:'i',й:'y',к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'h',ц:'ts',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya' };
  return (s || '').toLowerCase().split('').map((c) => (m[c] ?? c)).join('').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
};

const typeLabel = (type) => TYPE_OPTIONS.find((t) => t.value === type)?.label || type;
const supportsMultiple = (type) => !SINGLE_ONLY_TYPES.includes(type);
const emptyValue = (type, isMultiple) => {
  if (type === 'boolean') return false;
  if (type === 'table' || isMultiple) return [];
  return type === 'number' ? null : '';
};

/**
 * Coerce an existing value to the shape required by the field's type / multiplicity.
 */
const coerceValue = (field) => {
  if (field.type === 'boolean') {
    field.value = field.value === true;
    return;
  }
  if (field.type === 'table') {
    if (!Array.isArray(field.value)) field.value = [];
    return;
  }
  if (typeof field.value === 'boolean') {
    field.value = emptyValue(field.type, field.is_multiple);
  }
  if (field.is_multiple || field.type === 'file') {
    if (!Array.isArray(field.value)) field.value = field.value ? [field.value] : [];
  } else if (Array.isArray(field.value)) {
    field.value = field.value[0] ?? emptyValue(field.type, false);
  }
};

const hydrate = (list) => (list || []).map((f) => ({
  _key: keySeq++,
  id: f.id,
  code: f.code,
  name: f.name,
  type: f.type,
  is_multiple: !!f.is_multiple,
  sort: f.sort,
  value: f.value ?? emptyValue(f.type, !!f.is_multiple),
  default_value: f.default_value ?? null,
}));

const clone = (v) => JSON.parse(JSON.stringify(v));

const isEmptyValue = (v) => v === null || v === undefined || v === '' || (Array.isArray(v) && v.length === 0);

const canResetToDefault = (field) => !isEmptyValue(field.default_value)
  && JSON.stringify(field.value) !== JSON.stringify(field.default_value);

const resetToDefault = (field) => {
  field.value = clone(field.default_value);
};

// --- Usage hint -------------------------------------------------------------

const usageSnippet = (field) => {
  const path = `$projectSettings['custom_fields']['${field.code || 'code'}']`;
  if (field.type === 'boolean') {
    return `@if(!empty(${path}))\n    ...\n@endif`;
  }
  if (field.type === 'table') {
    return `<table>\n    @foreach(${path} ?? [] as $row)\n        <tr>\n            @foreach($row as $cell)\n                <td>{{ $cell }}</td>\n            @endforeach\n        </tr>\n    @endforeach\n</table>`;
  }
  const item = (v) => ({
    html: `{!! ${v} !!}`,
    image: `<img src="{{ asset('storage/' . ${v}) }}" alt="">`,
    file: `<a href="{{ asset('storage/' . ${v}) }}">Скачать</a>`,
  }[field.type] || `{{ ${v} }}`);
  if (field.is_multiple) {
    return `@foreach(${path} ?? [] as $item)\n    ${item('$item')}\n@endforeach`;
  }
  if (field.type === 'image' || field.type === 'file') {
    return `@if(!empty(${path}))\n    ${item(path)}\n@endif`;
  }
  return field.type === 'html' ? `{!! ${path} ?? '' !!}` : `{{ ${path} ?? '' }}`;
};

const toggleHelp = (key) => {
  openHelpKey.value = openHelpKey.value === key ? null : key;
  copiedKey.value = null;
};

const copySnippet = async (field) => {
  try {
    await navigator.clipboard.writeText(usageSnippet(field));
    copiedKey.value = field._key;
  } catch (e) {
    error('Не удалось скопировать');
  }
};

const onDocumentClick = (e) => {
  if (openHelpKey.value !== null && !e.target.closest('[data-help-popover]')) {
    openHelpKey.value = null;
  }
};

// --- Create / edit modal ----------------------------------------------------

const openCreateModal = () => {
  modal.index = null;
  modal.codeTouched = false;
  modal.form = { name: '', code: '', type: 'text', is_multiple: false, default_value: emptyValue('text', false) };
  modal.show = true;
  nextTick(() => nameInput.value?.focus());
};

const openEditModal = (idx) => {
  const f = fields.value[idx];
  modal.index = idx;
  modal.codeTouched = true;
  modal.form = {
    name: f.name,
    code: f.code,
    type: f.type,
    is_multiple: f.is_multiple,
    default_value: f.default_value === null ? emptyValue(f.type, f.is_multiple) : clone(f.default_value),
  };
  modal.show = true;
};

const resetModalDefault = () => {
  modal.form.default_value = emptyValue(modal.form.type, modalIsMultiple.value);
};

const setModalMultiple = (value) => {
  modal.form.is_multiple = value;
  resetModalDefault();
};

const closeModal = () => {
  modal.show = false;
};

const onModalNameInput = () => {
  if (!modal.codeTouched) modal.form.code = translit(modal.form.name);
};

const submitModal = async () => {
  const form = modal.form;
  if (!form.name.trim()) {
    error('Укажите название свойства');
    return;
  }
  const isMultiple = modalIsMultiple.value;
  const defaultValue = isEmptyValue(form.default_value) ? null : clone(form.default_value);
  const next = fields.value.map((f) => ({ ...f }));

  if (modal.index === null) {
    next.push({
      _key: keySeq++,
      id: null,
      name: form.name.trim(),
      code: form.code,
      type: form.type,
      is_multiple: isMultiple,
      value: defaultValue === null ? emptyValue(form.type, isMultiple) : clone(defaultValue),
      default_value: defaultValue,
    });
  } else {
    const field = next[modal.index];
    Object.assign(field, { name: form.name.trim(), code: form.code, type: form.type, is_multiple: isMultiple, default_value: defaultValue });
    coerceValue(field);
  }

  if (await persist(next)) {
    closeModal();
  }
};

// --- Delete -----------------------------------------------------------------

const removeField = async (idx) => {
  const field = fields.value[idx];
  const confirmed = await confirmModal.value.open({
    title: 'Удалить свойство?',
    message: `Свойство «${field.name}» и его значение будут удалены без возможности восстановления. Если оно выводится на сайте, вывод перестанет работать.`,
    confirmText: 'Удалить',
    dangerMode: true,
  });
  if (!confirmed) return;

  await persist(fields.value.filter((_, i) => i !== idx));
};

// --- Persistence ------------------------------------------------------------

const load = async () => {
  loading.value = true;
  try {
    const res = await fetch('/admin/api/settings/custom-fields', { headers: { Accept: 'application/json' } });
    if (res.ok) {
      fields.value = hydrate(await res.json());
    }
  } catch (e) {
    error('Не удалось загрузить пользовательские свойства');
  } finally {
    loading.value = false;
  }
};

/**
 * Save the given set of fields (the endpoint replaces the whole set).
 * Local state is only replaced once the server accepted it.
 */
const persist = async (list) => {
  saving.value = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const payload = {
      fields: list.map((f, i) => ({
        id: f.id || undefined,
        code: f.code || undefined,
        name: f.name,
        type: f.type,
        is_multiple: supportsMultiple(f.type) && !!f.is_multiple,
        sort: (i + 1) * 10,
        value: f.value,
        default_value: f.default_value,
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
    fields.value = hydrate(data.fields);
    return true;
  } catch (e) {
    error(e.message);
    return false;
  } finally {
    saving.value = false;
  }
};

const saveValues = async () => {
  if (await persist(fields.value)) {
    await success('Значения свойств сохранены');
  }
};

onMounted(() => {
  load();
  document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
});
</script>
