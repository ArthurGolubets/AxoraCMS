<template>
  <div>
    <div class="mb-6 flex items-center space-x-3">
      <button @click="$router.push('/custom-forms')" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      </button>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isEdit ? 'Редактировать форму' : 'Создать форму' }}</h2>
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <form v-else @submit.prevent="save" class="space-y-6">
      <!-- Main settings -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Основное</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelClass">Название *</label>
            <input v-model="form.name" @input="onNameInput" type="text" required placeholder="Например: Вопрос / ответ" :class="inputClass">
          </div>
          <div>
            <label :class="labelClass">Символьный код *</label>
            <input v-model="form.code" @input="codeTouched = true" type="text" required pattern="[a-z0-9_]+" placeholder="faq" :class="[inputClass, 'font-mono']">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Используется при выводе формы на сайте</p>
          </div>
          <div class="md:col-span-2">
            <label :class="labelClass">Описание</label>
            <textarea v-model="form.description" rows="2" :class="inputClass"></textarea>
          </div>
          <div class="md:col-span-2">
            <label :class="labelClass">Сообщение после отправки</label>
            <input v-model="form.success_message" type="text" placeholder="Спасибо! Ваша заявка отправлена." :class="inputClass">
          </div>
        </div>

        <div class="mt-6 space-y-4 border-t border-gray-100 dark:border-gray-700 pt-5">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Форма активна</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Неактивная форма не принимает записи с сайта</p>
            </div>
            <ToggleSwitch v-model="form.is_active" :theme-color="themeColor" />
          </div>
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Администратор может создавать записи</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Позволяет добавлять и редактировать записи в админ-панели</p>
            </div>
            <ToggleSwitch v-model="form.admin_can_create" :theme-color="themeColor" />
          </div>
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Уведомлять о новых записях</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Новая запись с сайта появится в уведомлениях администратора</p>
            </div>
            <ToggleSwitch v-model="form.notify_admin" :theme-color="themeColor" />
          </div>
        </div>
      </div>

      <!-- Fields -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Поля формы</h3>
          <button type="button" @click="openFieldPanel(null)" :style="buttonStyle" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-white rounded-lg transition-opacity hover:opacity-90">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Добавить поле
          </button>
        </div>

        <div v-if="form.fields.length === 0" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
          Добавьте поля, которые будет заполнять пользователь.
        </div>

        <draggable v-else v-model="form.fields" item-key="_key" handle=".drag-handle" class="divide-y divide-gray-100 dark:divide-gray-700">
          <template #item="{ element: field, index }">
            <div class="flex items-center gap-3 px-6 py-3">
              <span class="drag-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Перетащите, чтобы изменить порядок">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
              </span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">{{ field.name }}</span>
                  <span v-if="field.is_required" class="text-red-500 text-sm">*</span>
                  <span :class="badgeClass">{{ typeLabel(field.type) }}</span>
                  <span v-if="field.is_multiple" :class="badgeClass">множ.</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ field.code }}</div>
              </div>
              <button type="button" @click="openFieldPanel(index)" :class="iconButtonClass" title="Параметры поля">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              </button>
              <button type="button" @click="removeField(index)" class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Удалить поле">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </template>
        </draggable>
      </div>

      <!-- Usage on the site -->
      <div v-if="isEdit" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
        <button type="button" @click="showUsage = !showUsage" class="w-full flex items-center justify-between px-6 py-4 text-left">
          <span class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Как вывести форму на сайте
          </span>
          <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': showUsage }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div v-if="showUsage" class="px-6 pb-6 space-y-4 text-sm text-gray-700 dark:text-gray-300">
          <div v-for="snippet in usageSnippets" :key="snippet.title">
            <p class="font-medium mb-1">{{ snippet.title }}</p>
            <pre class="text-xs font-mono whitespace-pre-wrap break-all p-3 rounded bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200">{{ snippet.code }}</pre>
          </div>
        </div>
      </div>

      <div class="flex justify-end space-x-3">
        <button type="button" @click="$router.push('/custom-forms')" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
          Отмена
        </button>
        <ThemeButton type="submit" variant="primary" :disabled="saving">
          {{ saving ? 'Сохранение...' : (isEdit ? 'Сохранить' : 'Создать') }}
        </ThemeButton>
      </div>
    </form>

    <!-- Field side panel -->
    <SidePanel v-if="fieldPanel.show" :title="fieldPanel.index === null ? 'Новое поле' : 'Параметры поля'" @close="fieldPanel.show = false">
      <form id="custom-form-field-form" @submit.prevent="applyField" class="space-y-4">
        <div>
          <label :class="labelClass">Название *</label>
          <input ref="fieldNameInput" v-model="fieldPanel.form.name" @input="onFieldNameInput" type="text" required :class="inputClass">
        </div>
        <div>
          <label :class="labelClass">Код *</label>
          <input v-model="fieldPanel.form.code" @input="fieldPanel.codeTouched = true" type="text" required pattern="[a-z0-9_]+" :class="[inputClass, 'font-mono']">
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Имя поля ввода (name) в форме на сайте</p>
        </div>
        <div>
          <label :class="labelClass">Тип *</label>
          <select v-model="fieldPanel.form.type" @change="fieldPanel.form.settings = {}" :class="inputClass">
            <option v-for="(label, type) in FIELD_TYPES" :key="type" :value="type">{{ label }}</option>
          </select>
        </div>

        <!-- Enum options -->
        <div v-if="fieldPanel.form.type === 'enum'" class="border-t border-gray-200 dark:border-gray-700 pt-4">
          <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Варианты выбора</label>
            <button type="button" @click="addOption" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700">+ Добавить вариант</button>
          </div>
          <div class="space-y-2">
            <div v-for="(option, i) in fieldPanel.form.settings.options || []" :key="i" class="flex items-center gap-2">
              <input v-model="option.title" @input="onOptionTitleInput(option)" type="text" placeholder="Название" :class="[inputClass, 'flex-1']">
              <input v-model="option.code" type="text" placeholder="Код" :class="[inputClass, 'w-32 font-mono']">
              <button type="button" @click="fieldPanel.form.settings.options.splice(i, 1)" class="p-1 text-gray-400 hover:text-red-600" title="Удалить вариант">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <p v-if="!(fieldPanel.form.settings.options || []).length" class="text-sm text-gray-400">Добавьте хотя бы один вариант</p>
          </div>
        </div>

        <!-- Entity -->
        <div v-if="fieldPanel.form.type === 'entity'" class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
          <div>
            <label :class="labelClass">Тип сущности *</label>
            <select v-model="fieldPanel.form.settings.entity_type" required :class="inputClass">
              <option value="">— Выберите тип —</option>
              <option value="infoblock">Элемент инфоблока</option>
              <option value="product">Товар</option>
              <option value="catalog">Категория</option>
            </select>
          </div>
          <div v-if="fieldPanel.form.settings.entity_type === 'infoblock'">
            <label :class="labelClass">Инфоблок *</label>
            <select v-model="fieldPanel.form.settings.entity_id" required :class="inputClass">
              <option :value="null">— Выберите инфоблок —</option>
              <option v-for="ib in infoBlocks" :key="ib.id" :value="ib.id">{{ ib.name }}</option>
            </select>
          </div>
        </div>

        <!-- Uploads -->
        <div v-if="['image', 'file'].includes(fieldPanel.form.type)" class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
          <div v-if="fieldPanel.form.type === 'file'">
            <label :class="labelClass">Разрешённые расширения</label>
            <input v-model="fieldPanel.form.settings.extensions" type="text" :placeholder="DEFAULT_EXTENSIONS" :class="inputClass">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Через запятую. Исполняемые файлы (php, html, svg, js…) не принимаются никогда.</p>
          </div>
          <div>
            <label :class="labelClass">Максимальный размер, КБ</label>
            <input v-model.number="fieldPanel.form.settings.max_size" type="number" min="1" :placeholder="fieldPanel.form.type === 'image' ? '5120' : '10240'" :class="inputClass">
          </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Обязательное</p>
              <p v-if="fieldPanel.form.type === 'bool'" class="text-xs text-gray-500 dark:text-gray-400">Для «Да/Нет» — галочку нужно отметить (например, согласие)</p>
            </div>
            <ToggleSwitch v-model="fieldPanel.form.is_required" :theme-color="themeColor" />
          </div>
          <div v-if="!SINGLE_ONLY_TYPES.includes(fieldPanel.form.type)" class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Множественное</span>
            <ToggleSwitch v-model="fieldPanel.form.is_multiple" :theme-color="themeColor" />
          </div>
        </div>
      </form>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <button type="button" @click="fieldPanel.show = false" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Отмена</button>
          <ThemeButton type="submit" form="custom-form-field-form" variant="primary">
            {{ fieldPanel.index === null ? 'Добавить' : 'Применить' }}
          </ThemeButton>
        </div>
      </template>
    </SidePanel>

    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, nextTick, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import draggable from 'vuedraggable';
import ThemeButton from '../ThemeButton.vue';
import ToggleSwitch from '../ToggleSwitch.vue';
import SidePanel from '../SidePanel.vue';
import ConfirmModal from '../ConfirmModal.vue';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';
import { FIELD_TYPES, SINGLE_ONLY_TYPES, DEFAULT_EXTENSIONS, translit } from './customFormFields';

const route = useRoute();
const router = useRouter();
const { success, error } = useModal();
const { themeColor, buttonStyle } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';
const badgeClass = 'px-1.5 py-0.5 text-[11px] rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400';
const iconButtonClass = 'p-1.5 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition-colors';

const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const saving = ref(false);
const codeTouched = ref(false);
const showUsage = ref(false);
const infoBlocks = ref([]);
const confirmModal = ref(null);
const fieldNameInput = ref(null);
let keySeq = 1;

const form = ref({
  name: '',
  code: '',
  description: '',
  success_message: '',
  is_active: true,
  admin_can_create: false,
  notify_admin: true,
  fields: [],
});

const fieldPanel = reactive({
  show: false,
  index: null,
  codeTouched: false,
  form: {},
});

const typeLabel = (type) => FIELD_TYPES[type] || type;

const usageSnippets = computed(() => {
  const code = form.value.code || 'code';
  return [
    { title: 'Готовая форма (Blade-компонент)', code: `<x-axora-cms::custom-form code="${code}" />` },
    {
      title: 'Своя вёрстка: отправка на готовый маршрут',
      code: `<form method="POST" action="{{ route('axora-cms.forms.submit', '${code}') }}" enctype="multipart/form-data">\n    @csrf\n${form.value.fields.map((f) => `    <input name="${f.code}${f.is_multiple ? '[]' : ''}">  {{-- ${f.name} --}}`).join('\n')}\n    <button type="submit">Отправить</button>\n</form>\n\n@if (session('axora_form_success.code') === '${code}')\n    {{ session('axora_form_success.message') }}\n@endif`,
    },
    {
      title: 'Из своего контроллера (сервис)',
      code: `use HolartWeb\\AxoraCMS\\Services\\CustomFormService;\n\n// Валидирует, сохраняет файлы и уведомляет администратора\napp(CustomFormService::class)->submitFromRequest('${code}', $request);\n\n// Или с готовыми данными\napp(CustomFormService::class)->submit('${code}', ['field_code' => 'value']);`,
    },
  ];
});

const onNameInput = () => {
  if (!codeTouched.value && !isEdit.value) form.value.code = translit(form.value.name);
};

// --- Fields ----------------------------------------------------------------

const openFieldPanel = (index) => {
  const source = index === null
    ? { name: '', code: '', type: 'string', is_required: false, is_multiple: false, settings: {} }
    : form.value.fields[index];

  fieldPanel.index = index;
  fieldPanel.codeTouched = index !== null;
  fieldPanel.form = JSON.parse(JSON.stringify({ ...source, settings: source.settings || {} }));
  fieldPanel.show = true;
  nextTick(() => fieldNameInput.value?.focus());
};

const onFieldNameInput = () => {
  if (!fieldPanel.codeTouched) fieldPanel.form.code = translit(fieldPanel.form.name);
};

const addOption = () => {
  if (!fieldPanel.form.settings.options) fieldPanel.form.settings.options = [];
  fieldPanel.form.settings.options.push({ title: '', code: '' });
};

const onOptionTitleInput = (option) => {
  if (!option.code || option.code === translit(option._prevTitle || '')) {
    option.code = translit(option.title);
  }
  option._prevTitle = option.title;
};

const applyField = () => {
  const field = fieldPanel.form;
  const duplicate = form.value.fields.some((f, i) => f.code === field.code && i !== fieldPanel.index);
  if (duplicate) {
    error(`Поле с кодом «${field.code}» уже есть в форме`);
    return;
  }
  if (field.type === 'enum' && !(field.settings.options || []).some((o) => o.code)) {
    error('Добавьте хотя бы один вариант выбора');
    return;
  }
  if (SINGLE_ONLY_TYPES.includes(field.type)) field.is_multiple = false;
  if (field.settings.options) {
    field.settings.options = field.settings.options
      .filter((o) => o.code)
      .map(({ title, code }) => ({ title: title || code, code }));
  }

  if (fieldPanel.index === null) {
    form.value.fields.push({ ...field, id: null, _key: keySeq++ });
  } else {
    form.value.fields[fieldPanel.index] = { ...form.value.fields[fieldPanel.index], ...field };
  }
  fieldPanel.show = false;
};

const removeField = async (index) => {
  const field = form.value.fields[index];
  const confirmed = await confirmModal.value.open({
    title: 'Удалить поле?',
    message: field.id
      ? `Поле «${field.name}» будет удалено после сохранения формы. Уже собранные значения этого поля перестанут отображаться в записях.`
      : `Удалить поле «${field.name}»?`,
    confirmText: 'Удалить',
    dangerMode: true,
  });
  if (confirmed) form.value.fields.splice(index, 1);
};

// --- Persistence -----------------------------------------------------------

const hydrate = (data) => ({
  name: data.name,
  code: data.code,
  description: data.description || '',
  success_message: data.success_message || '',
  is_active: !!data.is_active,
  admin_can_create: !!data.admin_can_create,
  notify_admin: !!data.notify_admin,
  fields: (data.fields || []).map((f) => ({ ...f, settings: f.settings || {}, _key: keySeq++ })),
});

const load = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/admin/api/custom-forms/${route.params.id}`, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error();
    form.value = hydrate(await response.json());
  } catch (e) {
    error('Не удалось загрузить форму');
    router.push('/custom-forms');
  } finally {
    loading.value = false;
  }
};

const loadInfoBlocks = async () => {
  try {
    const response = await fetch('/admin/api/infoblocks?per_page=100', { headers: { Accept: 'application/json' } });
    if (response.ok) {
      const data = await response.json();
      infoBlocks.value = data.data || data;
    }
  } catch (e) {
    infoBlocks.value = [];
  }
};

const save = async () => {
  saving.value = true;
  try {
    const payload = {
      ...form.value,
      fields: form.value.fields.map(({ _key, ...field }) => ({
        id: field.id || undefined,
        name: field.name,
        code: field.code,
        type: field.type,
        is_required: !!field.is_required,
        is_multiple: !!field.is_multiple,
        settings: field.settings,
      })),
    };
    const response = await fetch(isEdit.value ? `/admin/api/custom-forms/${route.params.id}` : '/admin/api/custom-forms', {
      method: isEdit.value ? 'PUT' : 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        Accept: 'application/json',
      },
      body: JSON.stringify(payload),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
      throw new Error(firstError || data.message || 'Ошибка при сохранении');
    }
    if (isEdit.value) {
      form.value = hydrate(data);
      success('Форма сохранена');
    } else {
      router.push(`/custom-forms/${data.id}/edit`);
    }
  } catch (e) {
    error(e.message);
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (isEdit.value) load();
  loadInfoBlocks();
});
</script>
