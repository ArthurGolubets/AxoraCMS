<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg" :class="{ 'opacity-60': !action.is_active }">
    <!-- Header -->
    <div class="px-5 py-3 flex flex-wrap items-center gap-3 border-b border-gray-100 dark:border-gray-700">
      <span class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-sm font-semibold text-white" :style="{ backgroundColor: themeColor }">{{ index + 1 }}</span>
      <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ action.channel === 'telegram' ? 'Сообщение в Telegram' : 'Письмо на почту' }}</span>
      <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
        <span>{{ index === 0 ? 'Отправить через' : 'Подождать' }}</span>
        <input v-model.number="action.delayValue" type="number" min="0" class="w-20 px-2 py-1 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
        <select v-model.number="action.delayUnit" class="px-2 py-1 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
          <option v-for="unit in DELAY_UNITS" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
        </select>
        <span>{{ index === 0 ? 'после события' : 'после предыдущего шага' }}</span>
        <span v-if="!action.delayValue" class="text-xs text-green-600 dark:text-green-400">(сразу)</span>
      </div>

      <div class="ml-auto flex items-center gap-1">
        <ToggleSwitch v-model="action.is_active" :theme-color="themeColor" />
        <button type="button" :disabled="index === 0" @click="$emit('move', -1)" title="Поднять выше" class="p-1.5 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
        </button>
        <button type="button" :disabled="index === total - 1" @click="$emit('move', 1)" title="Опустить ниже" class="p-1.5 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <button type="button" @click="$emit('remove')" title="Убрать этот шаг" class="p-1.5 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </div>
    </div>

    <div class="p-5 space-y-4">
      <p v-if="errorFor('delay')" class="text-sm text-red-600">{{ errorFor('delay') }}</p>

      <!-- Telegram -->
      <template v-if="action.channel === 'telegram'">
        <p v-if="!meta.channels.telegram.installed" class="px-4 py-2 text-sm rounded-lg bg-amber-50 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
          Telegram ещё не подключён к сайту. Пока он не установлен, это сообщение отправляться не будет.
        </p>
        <p v-else-if="!meta.channels.telegram.configured" class="px-4 py-2 text-sm rounded-lg bg-amber-50 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
          Telegram подключён, но не настроен: укажите бота и чаты в разделе «Интеграции → Telegram», иначе сообщение не уйдёт.
        </p>

        <div>
          <label :class="labelClass">В какие чаты отправить</label>
          <div v-if="meta.channels.telegram.chat_ids.length" class="flex flex-wrap gap-2">
            <label v-for="chat in meta.channels.telegram.chat_ids" :key="chat" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm rounded-lg border cursor-pointer"
              :class="action.config.chat_ids.includes(chat) ? 'border-transparent text-white' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'"
              :style="action.config.chat_ids.includes(chat) ? { backgroundColor: themeColor } : {}">
              <input type="checkbox" class="sr-only" :checked="action.config.chat_ids.includes(chat)" @change="toggleChat(chat)">
              <span>Чат {{ chat }}</span>
            </label>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">Ни одного чата не добавлено. Добавьте их в разделе «Интеграции → Telegram».</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Если ничего не выбрать, сообщение получат все подключённые чаты</p>
        </div>

        <div>
          <label :class="labelClass">Текст сообщения *</label>
          <textarea v-model="action.config.text" ref="textEl" rows="7" :class="[inputClass, errorFor('config.text') ? 'border-red-400' : '']" placeholder="Новый заказ №#ORDER_ID#&#10;#PRODUCTS#&#10;ИТОГО: #TOTAL#"></textarea>
          <p v-if="errorFor('config.text')" class="text-sm text-red-600 mt-1">{{ errorFor('config.text') }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Чтобы выделить слово жирным, оберните его так: &lt;b&gt;слово&lt;/b&gt;. Данные из заказа вставляйте кнопками ниже</p>
        </div>

        <VariablePalette :variables="variables" @insert="insertIntoText" />

        <div v-if="preview" class="rounded-lg bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 p-3">
          <p class="text-xs font-medium text-sky-700 dark:text-sky-300 mb-1">Так сообщение увидят в Telegram (на примере данных)</p>
          <div class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-words" v-html="sanitizeHtml(preview)"></div>
        </div>
      </template>

      <!-- Email -->
      <template v-else>
        <p v-if="!meta.channels.email.configured" class="px-4 py-2 text-sm rounded-lg bg-amber-50 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
          Отправка почты не настроена в разделе «Окружение» — письма могут не доходить до клиентов.
        </p>

        <div>
          <label :class="labelClass">Какое письмо отправить *</label>
          <div class="flex flex-wrap gap-2">
            <select v-model="action.config.template_id" :class="[inputClass, 'flex-1 min-w-[14rem]', errorFor('config.template_id') ? 'border-red-400' : '']">
              <option :value="null" disabled>— Выберите шаблон письма —</option>
              <option v-for="template in availableTemplates" :key="template.id" :value="template.id">{{ template.name }}{{ template.is_active ? '' : ' (выключен)' }}</option>
            </select>
            <ThemeButton variant="secondary" @click="$emit('create-template')">
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Создать новый шаблон
            </ThemeButton>
            <ThemeButton v-if="action.config.template_id" variant="secondary" @click="$emit('edit-template', action.config.template_id)">Открыть шаблон</ThemeButton>
          </div>
          <p v-if="errorFor('config.template_id')" class="text-sm text-red-600 mt-1">{{ errorFor('config.template_id') }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Шаблон откроется на отдельной странице в новой вкладке. Когда сохраните его — вернитесь сюда, и он появится в списке</p>
        </div>

        <div>
          <label :class="labelClass">Кому отправить *</label>
          <label v-if="customerToken" class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200 cursor-pointer">
            <input type="checkbox" :checked="sendToCustomer" @change="setSendToCustomer($event.target.checked)" :style="{ accentColor: themeColor }">
            {{ customerLabel }}
          </label>
          <div class="mt-2">
            <input :value="extraRecipients" @input="setExtraRecipients($event.target.value)" type="text" :class="[inputClass, errorFor('config.recipients') ? 'border-red-400' : '']" placeholder="Другие адреса, например: manager@mysite.ru, boss@mysite.ru">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Дополнительные адреса — через запятую. Каждый получит своё письмо</p>
          </div>
          <p v-if="errorFor('config.recipients')" class="text-sm text-red-600 mt-1">{{ errorFor('config.recipients') }}</p>
          <p v-if="!customerToken" class="text-xs text-gray-500 dark:text-gray-400 mt-1">У этого события нет почты клиента — укажите адреса вручную.</p>
        </div>
      </template>

      <!-- Test send -->
      <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-2">
        <input v-if="action.channel === 'email'" v-model="testEmail" type="email" placeholder="Ваша почта для проверки" class="w-64 px-3 py-1.5 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
        <ThemeButton variant="secondary" size="sm" :disabled="testing" @click="sendTest">{{ testing ? 'Отправляем…' : 'Отправить пробное сообщение' }}</ThemeButton>
        <span class="text-xs text-gray-500 dark:text-gray-400">Придёт настоящее сообщение, но с придуманными данными (имя, сумма и т. д.)</span>
        <span v-if="testResult" class="text-sm" :class="testResult.ok ? 'text-green-600' : 'text-red-600'">{{ testResult.message }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import ThemeButton from '../ThemeButton.vue';
import ToggleSwitch from '../ToggleSwitch.vue';
import VariablePalette from './VariablePalette.vue';
import { sanitizeHtml } from '../../utils/sanitizeHtml';
import { useTheme } from '../../composables/useTheme';
import { api, CHANNELS, DELAY_UNITS } from '../../composables/useMessageManager';

const props = defineProps({
  action: { type: Object, required: true },
  index: { type: Number, required: true },
  total: { type: Number, required: true },
  event: { type: String, default: '' },
  variables: { type: Array, default: () => [] },
  meta: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
});

defineEmits(['move', 'remove', 'create-template', 'edit-template']);

const { themeColor } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const textEl = ref(null);
const preview = ref('');
const testEmail = ref('');
const testing = ref(false);
const testResult = ref(null);
let previewTimer = null;

const errorFor = (field) => props.errors[`actions.${props.index}.${field}`]?.[0] || '';

// Email of the customer the event is about (order buyer, comment author...).
const customerVariable = computed(() => props.variables.find(v => ['ORDER_EMAIL', 'COMMENT_EMAIL', 'REQUEST_EMAIL'].includes(v.code)));
const customerToken = computed(() => (customerVariable.value ? `#${customerVariable.value.code}#` : ''));
const customerLabel = computed(() => ({ ORDER_EMAIL: 'Покупателю (на почту из заказа)', COMMENT_EMAIL: 'Автору комментария', REQUEST_EMAIL: 'Автору заявки' }[customerVariable.value?.code] || 'Клиенту'));

const recipientParts = () => (props.action.config.recipients || '').split(/[\s,;]+/).filter(Boolean);
const sendToCustomer = computed(() => recipientParts().includes(customerToken.value));
const extraRecipients = computed(() => recipientParts().filter(part => part !== customerToken.value).join(', '));

const setRecipients = (customer, extras) => {
  props.action.config.recipients = [customer ? customerToken.value : '', extras].filter(Boolean).join(', ');
};
const setSendToCustomer = (checked) => setRecipients(checked, extraRecipients.value);
const setExtraRecipients = (value) => setRecipients(sendToCustomer.value, value.trim());

// Templates made for another event are hidden, "any event" ones are always offered.
const availableTemplates = computed(() => props.meta.templates.filter(t => !t.event || t.event === props.event || t.id === props.action.config.template_id));

const toggleChat = (chat) => {
  const list = props.action.config.chat_ids;
  props.action.config.chat_ids = list.includes(chat) ? list.filter(c => c !== chat) : [...list, chat];
};

const insertIntoText = async (token) => {
  const el = textEl.value;
  const current = props.action.config.text || '';
  const start = el?.selectionStart ?? current.length;
  const end = el?.selectionEnd ?? current.length;

  props.action.config.text = current.slice(0, start) + token + current.slice(end);
  await nextTick();
  el?.focus();
  el?.setSelectionRange(start + token.length, start + token.length);
};

const loadPreview = async () => {
  if (props.action.channel !== 'telegram' || !props.action.config.text) {
    preview.value = '';
    return;
  }
  try {
    const result = await api('POST', '/preview', { event: props.event || null, text: props.action.config.text });
    preview.value = result.text;
  } catch (e) {
    preview.value = '';
  }
};

watch(() => [props.action.config.text, props.event], () => {
  clearTimeout(previewTimer);
  previewTimer = setTimeout(loadPreview, 400);
});
onMounted(loadPreview);

const sendTest = async () => {
  testing.value = true;
  testResult.value = null;
  try {
    const result = await api('POST', '/test', {
      channel: props.action.channel,
      event: props.event || null,
      config: props.action.config,
      email: props.action.channel === 'email' ? testEmail.value : undefined,
    });
    testResult.value = { ok: true, message: result.message };
  } catch (e) {
    testResult.value = { ok: false, message: e.message };
  } finally {
    testing.value = false;
  }
};
</script>
