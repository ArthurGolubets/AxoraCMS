<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isEdit ? 'Редактирование правила' : 'Новое правило отправки' }}</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Правило отвечает на три вопроса: когда отправлять, при каких условиях и что именно отправить</p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <ThemeButton variant="secondary" @click="$router.push('/message-manager')">Отмена</ThemeButton>
        <ThemeButton variant="primary" :disabled="saving || loading" @click="save()">{{ saving ? 'Сохранение…' : 'Сохранить' }}</ThemeButton>
      </div>
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <div v-else class="space-y-6">
      <!-- 1. Event -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">1. Когда отправлять</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Выберите, после какого действия на сайте сработает правило</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelClass">Что должно произойти *</label>
            <select v-model="form.event" :class="[inputClass, fieldError('event') ? 'border-red-400' : '']">
              <option value="" disabled>— Выберите событие —</option>
              <optgroup v-for="group in groupedEvents" :key="group.name" :label="group.name">
                <option v-for="event in group.events" :key="event.code" :value="event.code">{{ event.label }}</option>
              </optgroup>
            </select>
            <p v-if="fieldError('event')" class="text-sm text-red-600 mt-1">{{ fieldError('event') }}</p>
            <p v-if="isBeforeEvent" class="text-xs text-amber-600 dark:text-amber-400 mt-1">Это событие срабатывает ещё до сохранения: у заказа или записи пока нет номера, и отменить создание сообщением нельзя. Обычно удобнее выбрать вариант «После…».</p>
          </div>
          <div>
            <label :class="labelClass">Название правила *</label>
            <input v-model="form.name" type="text" :class="[inputClass, fieldError('name') ? 'border-red-400' : '']" placeholder="Например: Сообщить менеджеру о новом заказе">
            <p v-if="fieldError('name')" class="text-sm text-red-600 mt-1">{{ fieldError('name') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Видно только вам — чтобы не путаться в списке</p>
          </div>
        </div>
        <ToggleSwitch v-model="form.is_active" :theme-color="themeColor" label="Правило включено" />
      </div>

      <!-- 2. Conditions -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">2. При каких условиях <span class="text-sm font-normal text-gray-400">необязательно</span></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Если ничего не добавлять, правило сработает каждый раз. Если условий несколько — должны выполниться все сразу</p>
          </div>
          <ThemeButton variant="secondary" size="sm" :disabled="!form.event" @click="addCondition">+ Добавить условие</ThemeButton>
        </div>

        <p v-if="!form.event" class="text-sm text-gray-500 dark:text-gray-400">Сначала выберите событие.</p>
        <div v-for="(condition, index) in form.conditions" :key="index" class="flex flex-wrap items-center gap-2">
          <span class="text-sm text-gray-500 dark:text-gray-400 w-24">{{ index === 0 ? 'Только если' : 'и ещё если' }}</span>
          <select v-model="condition.variable" @change="condition.value = ''" :class="[inputClass, 'w-64 shrink-0']">
            <option v-for="variable in conditionVariables" :key="variable.code" :value="variable.code">{{ variable.label }}</option>
          </select>
          <select v-model="condition.operator" :class="[inputClass, 'w-44 shrink-0']">
            <option v-for="operator in OPERATORS" :key="operator.value" :value="operator.value">{{ operator.label }}</option>
          </select>
          <template v-if="operatorNeedsValue(condition.operator)">
            <select v-if="optionsFor(condition.variable).length" v-model="condition.value" :class="[inputClass, 'w-56']">
              <option value="" disabled>— Выберите —</option>
              <option v-for="option in optionsFor(condition.variable)" :key="option" :value="option">{{ option }}</option>
            </select>
            <input v-else v-model="condition.value" type="text" :class="[inputClass, 'w-56']" placeholder="Значение">
          </template>
          <button type="button" title="Убрать условие" @click="form.conditions.splice(index, 1)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <p v-if="form.conditions.length" class="text-xs text-gray-500 dark:text-gray-400">Например: «Статус оплаты равно Оплачен» — сообщение придёт, только когда заказ оплатили. Для условий «больше/меньше» выбирайте пункт со словом «числом».</p>
      </div>

      <!-- 3. Actions -->
      <div class="space-y-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">3. Что отправить</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Можно добавить несколько шагов — они выполняются сверху вниз. Например: сразу написать менеджеру в Telegram, а клиенту отправить письмо через 10 минут</p>
        </div>

        <p v-if="fieldError('actions')" class="text-sm text-red-600">{{ fieldError('actions') }}</p>

        <div v-if="!form.actions.length" class="bg-white dark:bg-gray-800 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center text-sm text-gray-500 dark:text-gray-400">
          Пока ничего не добавлено. Нажмите одну из кнопок ниже.
        </div>

        <MessageActionCard
          v-for="(action, index) in form.actions"
          :key="action._key"
          :action="action"
          :index="index"
          :total="form.actions.length"
          :event="form.event"
          :variables="variables"
          :meta="meta"
          :errors="errors"
          @move="moveAction(index, $event)"
          @remove="form.actions.splice(index, 1)"
          @create-template="openTemplatePage(action, null)"
          @edit-template="openTemplatePage(action, $event)"
        />

        <div class="flex flex-wrap gap-3">
          <ThemeButton variant="secondary" @click="addAction('telegram')">
            <span class="mr-1.5">+</span> Добавить сообщение в Telegram
          </ThemeButton>
          <ThemeButton variant="secondary" @click="addAction('email')">
            <span class="mr-1.5">+</span> Добавить письмо на почту
          </ThemeButton>
        </div>
      </div>

      <div class="flex justify-end gap-3 pb-6">
        <ThemeButton variant="secondary" @click="$router.push('/message-manager')">Отмена</ThemeButton>
        <ThemeButton variant="primary" :disabled="saving" @click="save()">{{ saving ? 'Сохранение…' : 'Сохранить' }}</ThemeButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from '../ThemeButton.vue';
import ToggleSwitch from '../ToggleSwitch.vue';
import MessageActionCard from './MessageActionCard.vue';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';
import { api, OPERATORS, splitDelay } from '../../composables/useMessageManager';

const route = useRoute();
const router = useRouter();
const { success, error } = useModal();
const { themeColor } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const isEdit = computed(() => !!route.params.id);
const loading = ref(true);
const saving = ref(false);
const errors = ref({});
const meta = ref({ events: [], common_variables: [], channels: { telegram: { installed: false, configured: false, chat_ids: [] }, email: { configured: false } }, templates: [] });
const form = ref({ name: '', event: '', is_active: true, conditions: [], actions: [] });

// A template is created on its own page in a new tab; this remembers which step is waiting for it.
const pendingTemplate = ref({ action: null, knownIds: [] });

let keySeq = 0;
const nextKey = () => `a${++keySeq}`;

const groupedEvents = computed(() => {
  const groups = {};
  meta.value.events.forEach(event => {
    (groups[event.group] ||= { name: event.group, events: [] }).events.push(event);
  });
  return Object.values(groups);
});

const variables = computed(() => meta.value.events.find(e => e.code === form.value.event)?.variables || meta.value.common_variables);
// Dates and site name make no sense as a condition.
const conditionVariables = computed(() => variables.value.filter(v => !['SITE_NAME', 'SITE_URL', 'DATE', 'DATETIME'].includes(v.code) && !/_CODE$/.test(v.code)));
const isBeforeEvent = computed(() => form.value.event.endsWith('.creating'));

const optionsFor = (code) => variables.value.find(v => v.code === code)?.options || [];
const operatorNeedsValue = (operator) => OPERATORS.find(o => o.value === operator)?.needsValue !== false;
const fieldError = (field) => errors.value[field]?.[0] || '';

const hydrateAction = (action) => {
  const delay = splitDelay(action.delay_seconds);

  return {
    _key: nextKey(),
    id: action.id ?? null,
    channel: action.channel,
    is_active: action.is_active ?? true,
    delayValue: delay.value,
    delayUnit: delay.unit,
    config: action.channel === 'telegram'
      ? { text: action.config?.text || '', chat_ids: [...(action.config?.chat_ids || [])] }
      : { template_id: action.config?.template_id ?? null, recipients: action.config?.recipients || '' },
  };
};

const addAction = (channel) => {
  const customer = variables.value.find(v => ['ORDER_EMAIL', 'COMMENT_EMAIL', 'REQUEST_EMAIL'].includes(v.code));
  const recipients = channel === 'email' && customer ? `#${customer.code}#` : '';
  form.value.actions.push(hydrateAction({ channel, config: channel === 'email' ? { recipients } : {} }));
};

const addCondition = () => {
  form.value.conditions.push({ variable: conditionVariables.value[0]?.code, operator: 'eq', value: '' });
};

const moveAction = (index, direction) => {
  const target = index + direction;
  const list = form.value.actions;
  if (target < 0 || target >= list.length) return;
  [list[index], list[target]] = [list[target], list[index]];
};

// --- templates live on their own page ------------------------------------------------------
const openTemplatePage = (action, templateId) => {
  pendingTemplate.value = { action: templateId ? null : action, knownIds: meta.value.templates.map(t => t.id) };

  const location = templateId
    ? { path: `/message-templates/${templateId}/edit` }
    : { path: '/message-templates/create', query: form.value.event ? { event: form.value.event } : {} };

  window.open(router.resolve(location).href, '_blank');
};

// Back from the template tab: reload the list and pick the template that was just made.
const refreshTemplates = async () => {
  try {
    meta.value.templates = (await api('GET', '/meta')).templates;
  } catch (e) {
    return;
  }

  const { action, knownIds } = pendingTemplate.value;
  const created = meta.value.templates.find(t => !knownIds.includes(t.id));

  if (action && created) {
    action.config.template_id = created.id;
    pendingTemplate.value = { action: null, knownIds: [] };
  }
};

// Variables differ between events: drop conditions that point at a variable the new event lacks.
watch(() => form.value.event, () => {
  const known = new Set(variables.value.map(v => v.code));
  form.value.conditions = form.value.conditions.filter(c => known.has(c.variable));
});

const payload = () => ({
  name: form.value.name,
  event: form.value.event,
  is_active: form.value.is_active,
  conditions: form.value.conditions.filter(c => c.variable),
  actions: form.value.actions.map(action => ({
    id: action.id,
    channel: action.channel,
    is_active: action.is_active,
    delay_seconds: Math.max(0, Math.round((Number(action.delayValue) || 0) * action.delayUnit)),
    config: action.config,
  })),
});

const save = async () => {
  saving.value = true;
  errors.value = {};
  try {
    if (!form.value.actions.length) {
      errors.value = { actions: ['Добавьте хотя бы один шаг — иначе правило ничего не отправит'] };
      return;
    }

    isEdit.value
      ? await api('PUT', `/subscriptions/${route.params.id}`, payload())
      : await api('POST', '/subscriptions', payload());

    await success('Правило сохранено');
    router.push('/message-manager');
  } catch (e) {
    errors.value = e.errors || {};
    await error(e.message);
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  window.addEventListener('focus', refreshTemplates);

  try {
    meta.value = await api('GET', '/meta');

    if (isEdit.value) {
      const subscription = (await api('GET', `/subscriptions/${route.params.id}`)).data;
      form.value = {
        name: subscription.name,
        event: subscription.event,
        is_active: subscription.is_active,
        conditions: subscription.conditions.map(c => ({ ...c })),
        actions: subscription.actions.map(hydrateAction),
      };
    } else if (route.query.event) {
      form.value.event = String(route.query.event);
    }
  } catch (e) {
    await error(e.message);
  } finally {
    loading.value = false;
  }
});

onBeforeUnmount(() => window.removeEventListener('focus', refreshTemplates));
</script>
