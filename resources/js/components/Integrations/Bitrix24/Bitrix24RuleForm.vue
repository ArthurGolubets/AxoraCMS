<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isEdit ? 'Редактирование правила' : 'Новое правило' }}</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Когда на сайте что-то происходит, правило создаёт записи в CRM Битрикс24</p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <ThemeButton variant="secondary" @click="goBack">Отмена</ThemeButton>
        <ThemeButton variant="primary" :disabled="saving || loading" @click="save">{{ saving ? 'Сохранение…' : 'Сохранить' }}</ThemeButton>
      </div>
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <div v-else class="space-y-6">
      <div v-if="fieldsError" class="p-4 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 text-sm text-amber-800 dark:text-amber-300">
        Не удалось получить поля из Битрикс24: {{ fieldsError }}. Проверьте подключение на вкладке «Подключение».
      </div>

      <!-- 1. Event -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">1. Когда срабатывает</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelClass">Событие *</label>
            <select v-model="form.event" @change="onEventChange" :class="inputClass">
              <option value="" disabled>— Выберите событие —</option>
              <option v-for="event in meta.events" :key="event.code" :value="event.code">{{ event.label }}</option>
            </select>
            <p v-if="!meta.events.length" class="text-xs text-amber-600 dark:text-amber-400 mt-1">Нет доступных событий: установите модуль «Коммерция» или «Обратная связь».</p>
          </div>
          <div>
            <label :class="labelClass">Название правила *</label>
            <input v-model="form.name" type="text" :class="inputClass" placeholder="Например: Заказы в сделки">
          </div>
          <div v-if="form.event === 'form_submission.created'">
            <label :class="labelClass">Какая форма</label>
            <select v-model="form.form_code" :class="inputClass">
              <option :value="null">Любая форма</option>
              <option v-for="item in meta.forms" :key="item.code" :value="item.code">{{ item.name }}</option>
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Выберите форму, чтобы сопоставить её поля</p>
          </div>
        </div>
        <ToggleSwitch v-model="form.is_active" :theme-color="themeColor" label="Правило включено" />
      </div>

      <!-- 2. What to create -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">2. Что создавать</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <label
            v-for="entity in ENTITIES"
            :key="entity.value"
            class="flex items-start gap-3 p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer"
            :class="{ 'border-blue-500 ring-1 ring-blue-500': form.entity === entity.value }"
          >
            <input type="radio" :value="entity.value" v-model="form.entity" class="mt-1 w-4 h-4 text-blue-600">
            <span>
              <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ entity.label }}</span>
              <span class="block text-xs text-gray-500 dark:text-gray-400">{{ entity.hint }}</span>
            </span>
          </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelClass">Контакт</label>
            <select v-model="form.contact_mode" :class="inputClass">
              <option v-for="mode in MODES" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Существующий контакт ищется по телефону и email</p>
          </div>
          <div>
            <label :class="labelClass">Компания</label>
            <select v-model="form.company_mode" :class="inputClass">
              <option v-for="mode in MODES" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Существующая компания ищется по телефону, email и названию</p>
          </div>
        </div>
        <ToggleSwitch
          v-if="form.event === 'order.created' && form.entity !== 'none'"
          v-model="form.send_products"
          :theme-color="themeColor"
          label="Передавать товары заказа (сумма в Битрикс24 посчитается по ним)"
        />
      </div>

      <!-- 3. Mapping -->
      <template v-if="form.event">
        <VariablePalette :variables="variables" title="3. Данные с сайта" @insert="insertVariable" />
        <Bitrix24Mapping
          v-if="form.entity !== 'none'"
          :title="form.entity === 'deal' ? 'Поля сделки' : 'Поля лида'"
          :rows="form.mapping.main"
          :fields="fields[form.entity] || []"
          @focus-value="rememberInput"
        />
        <Bitrix24Mapping v-if="form.contact_mode !== 'none'" title="Поля контакта" :rows="form.mapping.contact" :fields="fields.contact || []" @focus-value="rememberInput" />
        <Bitrix24Mapping v-if="form.company_mode !== 'none'" title="Поля компании" :rows="form.mapping.company" :fields="fields.company || []" @focus-value="rememberInput" />
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from '../../ThemeButton.vue';
import ToggleSwitch from '../../ToggleSwitch.vue';
import VariablePalette from '../../MessageManager/VariablePalette.vue';
import Bitrix24Mapping from './Bitrix24Mapping.vue';
import { useModal } from '../../../composables/useModal';
import { useTheme } from '../../../composables/useTheme';
import { api, ENTITIES, MODES } from '../../../composables/useBitrix24';

const route = useRoute();
const router = useRouter();
const { success, error } = useModal();
const { themeColor } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const emptyMapping = () => ({ main: [], contact: [], company: [] });

const isEdit = computed(() => !!route.params.id);
const loading = ref(true);
const saving = ref(false);
const meta = ref({ events: [], forms: [] });
const fields = ref({});
const fieldsError = ref('');
const form = ref({
  name: '',
  event: '',
  form_code: null,
  is_active: true,
  entity: 'lead',
  contact_mode: 'find_or_create',
  company_mode: 'none',
  send_products: true,
  mapping: emptyMapping(),
});

const currentEvent = computed(() => meta.value.events.find(event => event.code === form.value.event));
const variables = computed(() => {
  const formVariables = meta.value.forms.find(item => item.code === form.value.form_code)?.variables || [];
  return [...(currentEvent.value?.variables || []), ...formVariables];
});

const onEventChange = () => {
  const defaults = currentEvent.value?.default_mapping || {};
  form.value.mapping = { ...emptyMapping(), ...JSON.parse(JSON.stringify(defaults)) };
};

// The palette writes into the text field the admin clicked last.
let activeRow = null;
let activeInput = null;

const rememberInput = (row, input) => {
  activeRow = row;
  activeInput = input;
};

const insertVariable = async (token) => {
  if (!activeRow) {
    await error('Сначала поставьте курсор в поле значения справа');
    return;
  }

  const value = String(activeRow.value ?? '');
  const position = activeInput?.selectionStart ?? value.length;
  activeRow.value = value.slice(0, position) + token + value.slice(position);

  await nextTick();
  activeInput?.focus();
  activeInput?.setSelectionRange(position + token.length, position + token.length);
};

const goBack = () => router.push({ path: '/integrations/bitrix24', query: { tab: 'rules' } });

const save = async () => {
  saving.value = true;
  try {
    const mapping = {};
    Object.keys(form.value.mapping).forEach(key => {
      mapping[key] = form.value.mapping[key].filter(row => row.field);
    });
    const payload = { ...form.value, mapping };

    if (isEdit.value) {
      await api('PUT', `/rules/${route.params.id}`, payload);
    } else {
      await api('POST', '/rules', payload);
    }

    await success('Правило сохранено');
    goBack();
  } catch (e) {
    await error(e.message);
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  try {
    meta.value = await api('GET', '/meta');

    if (isEdit.value) {
      const { data } = await api('GET', `/rules/${route.params.id}`);
      form.value = { ...form.value, ...data, mapping: { ...emptyMapping(), ...(data.mapping || {}) } };
    }
  } catch (e) {
    await error(e.message);
  }

  try {
    fields.value = (await api('GET', '/fields')).data;
  } catch (e) {
    fieldsError.value = e.message;
  }

  loading.value = false;
});
</script>
