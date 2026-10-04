<template>
  <SidePanel :title="filter ? 'Редактировать фильтр' : 'Создать фильтр'" width-class="max-w-2xl" @close="$emit('close')">
    <div v-if="loading" class="py-12 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <form v-else id="filter-editor-form" @submit.prevent="save" class="space-y-5">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label :class="labelClass">Название фильтра *</label>
          <input ref="nameInput" v-model="form.name" @input="onNameInput" type="text" required placeholder="Например: Объём памяти" :class="inputClass">
        </div>
        <div>
          <label :class="labelClass">Символьный код *</label>
          <input v-model="form.code" @input="codeTouched = true" type="text" required placeholder="storage_capacity" :class="[inputClass, 'font-mono']">
        </div>
      </div>

      <!-- Type -->
      <div>
        <label :class="labelClass">Как покупатель будет фильтровать *</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <button
            v-for="type in FILTER_TYPES"
            :key="type.value"
            type="button"
            @click="form.type = type.value"
            class="text-left p-3 rounded-lg border transition-colors"
            :class="form.type === type.value
              ? 'border-transparent ring-2 bg-gray-50 dark:bg-gray-700/60'
              : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
            :style="form.type === type.value ? { '--tw-ring-color': themeColor } : {}"
          >
            <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ type.label }}</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ type.description }}</span>
            <span class="block text-xs text-gray-400 dark:text-gray-500 mt-1 italic">{{ type.example }}</span>
          </button>
        </div>
      </div>

      <!-- Category -->
      <div v-if="fixedCatalogId === null">
        <label :class="labelClass">Категория</label>
        <CategorySelect
          v-model="form.catalog_id"
          :categories="catalogs"
          clearable
          placeholder="Глобальный фильтр — для всех категорий"
          picker-title="Выберите категорию фильтра"
        />
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Пусто — фильтр глобальный и доступен во всех категориях. С категорией — только в ней и её дочерних.
        </p>
      </div>

      <div>
        <label :class="labelClass">Описание</label>
        <textarea v-model="form.description" rows="2" placeholder="Необязательно" :class="inputClass"></textarea>
      </div>

      <ToggleSwitch v-model="form.is_active" :theme-color="themeColor" label="Фильтр активен" />

      <!-- Values for select / checkbox -->
      <div v-if="hasValueList" class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Значения фильтра</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Перетаскивайте, чтобы задать порядок. Код создаётся автоматически.</p>
          </div>
          <button type="button" @click="addValue" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700">+ Добавить значение</button>
        </div>

        <div v-if="form.values.length === 0" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
          Добавьте хотя бы одно значение
        </div>

        <draggable v-else v-model="form.values" item-key="_key" handle=".drag-handle" ghost-class="opacity-40" class="space-y-2">
          <template #item="{ element: value, index }">
            <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-900/60 rounded-lg border border-gray-200 dark:border-gray-700">
              <span class="drag-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 shrink-0" title="Перетащите, чтобы изменить порядок">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
              </span>
              <input
                :ref="(el) => setValueInput(el, index)"
                v-model="value.value"
                @input="onValueInput(value)"
                @keydown.enter.prevent="addValue"
                type="text"
                required
                placeholder="Значение, например 512 ГБ"
                class="flex-1 min-w-0 px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
              >
              <input
                v-model="value.code"
                @input="value._codeTouched = true"
                type="text"
                placeholder="код"
                title="Код значения (создаётся автоматически)"
                class="w-32 px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-500 dark:text-gray-400 font-mono"
              >
              <ToggleSwitch v-model="value.is_active" :theme-color="themeColor" :title="value.is_active ? 'Активно' : 'Неактивно'" />
              <button type="button" @click="form.values.splice(index, 1)" class="p-1.5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20" title="Удалить значение">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </template>
        </draggable>
      </div>

      <!-- Range bounds -->
      <div v-if="form.type === 'range'" class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Границы диапазона</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Необязательно: подсказка для полей «от» и «до» на сайте</p>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">От</label>
            <input v-model="rangeFrom" type="number" placeholder="0" :class="inputClass">
          </div>
          <div>
            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">До</label>
            <input v-model="rangeTo" type="number" placeholder="100000" :class="inputClass">
          </div>
        </div>
      </div>

      <!-- Entity settings -->
      <div v-if="form.type === 'entity'" class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
        <div>
          <label :class="labelClass">К чему привязывать *</label>
          <select v-model="form.settings.entity_type" required :class="inputClass">
            <option value="">— Выберите —</option>
            <option value="infoblock">Элемент инфоблока</option>
            <option value="product">Товар</option>
            <option value="catalog">Категория</option>
          </select>
        </div>
        <div v-if="form.settings.entity_type === 'infoblock'">
          <label :class="labelClass">Инфоблок *</label>
          <select v-model="form.settings.entity_id" required :class="inputClass">
            <option :value="null">— Выберите инфоблок —</option>
            <option v-for="ib in infoBlocks" :key="ib.id" :value="ib.id">{{ ib.name }}</option>
          </select>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="flex justify-end gap-3">
        <button type="button" @click="$emit('close')" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
          Отмена
        </button>
        <ThemeButton type="submit" form="filter-editor-form" variant="primary" :disabled="saving || loading">
          {{ saving ? 'Сохранение...' : (filter ? 'Сохранить' : 'Создать фильтр') }}
        </ThemeButton>
      </div>
    </template>
  </SidePanel>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';
import draggable from 'vuedraggable';
import SidePanel from './SidePanel.vue';
import ToggleSwitch from './ToggleSwitch.vue';
import ThemeButton from './ThemeButton.vue';
import CategorySelect from './CategorySelect.vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import { FILTER_TYPES } from '../utils/filterTypes';

/**
 * Create / edit a product filter in a side panel. Used by the global filters
 * list and by the category form ("Фильтры категории", with a fixed category).
 */
const props = defineProps({
  // Filter to edit (needs at least an id); null — create a new one.
  filter: { type: Object, default: null },
  // Category the filter belongs to when created from a category; null — choose in the form.
  fixedCatalogId: { type: Number, default: null },
});

const emit = defineEmits(['saved', 'close']);

const { error } = useModal();
const { themeColor } = useTheme();


const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const loading = ref(false);
const saving = ref(false);
const catalogs = ref([]);
const infoBlocks = ref([]);
const codeTouched = ref(false);
const nameInput = ref(null);
const valueInputs = [];
let keySeq = 1;

const form = ref({
  name: '',
  code: '',
  type: 'select',
  catalog_id: props.fixedCatalogId,
  is_active: true,
  description: '',
  values: [],
  settings: {},
});

const hasValueList = computed(() => ['select', 'checkbox'].includes(form.value.type));

const translit = (s) => {
  const m = { а: 'a', б: 'b', в: 'v', г: 'g', д: 'd', е: 'e', ё: 'yo', ж: 'zh', з: 'z', и: 'i', й: 'y', к: 'k', л: 'l', м: 'm', н: 'n', о: 'o', п: 'p', р: 'r', с: 's', т: 't', у: 'u', ф: 'f', х: 'h', ц: 'ts', ч: 'ch', ш: 'sh', щ: 'sch', ъ: '', ы: 'y', ь: '', э: 'e', ю: 'yu', я: 'ya' };
  return (s || '').toLowerCase().split('').map((c) => m[c] ?? c).join('').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
};

const onNameInput = () => {
  if (!codeTouched.value && !props.filter) form.value.code = translit(form.value.name);
};

// --- Values ------------------------------------------------------------------

const setValueInput = (el, index) => {
  valueInputs[index] = el;
};

const onValueInput = (value) => {
  if (!value._codeTouched) value.code = translit(value.value);
};

const addValue = () => {
  form.value.values.push({ _key: keySeq++, value: '', code: '', is_active: true, _codeTouched: false });
  nextTick(() => valueInputs[form.value.values.length - 1]?.focus());
};

// Range bounds are stored as values with the "from" / "to" codes.
const boundValue = (code) => computed({
  get: () => form.value.values.find((v) => v.code === code)?.value ?? '',
  set: (val) => {
    const existing = form.value.values.find((v) => v.code === code);
    if (existing) {
      existing.value = val === '' ? '' : String(val);
    } else if (val !== '') {
      form.value.values.push({ _key: keySeq++, value: String(val), code, is_active: true, _codeTouched: true });
    }
  },
});
const rangeFrom = boundValue('from');
const rangeTo = boundValue('to');

// --- Loading -----------------------------------------------------------------

const loadFilter = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/admin/api/filters/${props.filter.id}`, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error();
    const data = await response.json();
    form.value = {
      name: data.name,
      code: data.code,
      type: data.type,
      catalog_id: data.catalog_id,
      is_active: !!data.is_active,
      description: data.description || '',
      values: (data.values || [])
        .slice()
        .sort((a, b) => (a.sort - b.sort) || (a.id - b.id))
        .map((v) => ({ ...v, _key: keySeq++, _codeTouched: true })),
      settings: data.settings || {},
    };
    codeTouched.value = true;
  } catch (e) {
    error('Не удалось загрузить фильтр');
    emit('close');
  } finally {
    loading.value = false;
  }
};

const loadCatalogs = async () => {
  try {
    const response = await fetch('/admin/api/catalogs/list', { headers: { Accept: 'application/json' } });
    if (response.ok) catalogs.value = await response.json();
  } catch (e) {
    catalogs.value = [];
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

// --- Save --------------------------------------------------------------------

const buildValues = () => {
  if (hasValueList.value) {
    return form.value.values
      .filter((v) => String(v.value).trim() !== '')
      .map((v, index) => ({ id: v.id || undefined, value: String(v.value).trim(), code: v.code || null, sort: (index + 1) * 10, is_active: !!v.is_active }));
  }
  if (form.value.type === 'range') {
    return form.value.values
      .filter((v) => ['from', 'to'].includes(v.code) && v.value !== '')
      .map((v) => ({ id: v.id || undefined, value: String(v.value), code: v.code, sort: v.code === 'from' ? 10 : 20, is_active: true }));
  }
  return [];
};

const save = async () => {
  const values = buildValues();
  if (hasValueList.value && values.length === 0) {
    error('Добавьте хотя бы одно значение фильтра');
    return;
  }

  saving.value = true;
  try {
    const payload = {
      name: form.value.name,
      code: form.value.code,
      type: form.value.type,
      catalog_id: props.fixedCatalogId ?? form.value.catalog_id ?? null,
      is_active: form.value.is_active,
      description: form.value.description,
      settings: form.value.type === 'entity' ? form.value.settings : null,
      values,
    };
    const response = await fetch(props.filter ? `/admin/api/filters/${props.filter.id}` : '/admin/api/filters', {
      method: props.filter ? 'PUT' : 'POST',
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
      throw new Error(firstError || data.message || 'Ошибка при сохранении фильтра');
    }
    emit('saved', data);
  } catch (e) {
    error(e.message);
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (props.filter) {
    loadFilter();
  } else {
    nextTick(() => nameInput.value?.focus());
  }
  if (props.fixedCatalogId === null) loadCatalogs();
  loadInfoBlocks();
});
</script>
