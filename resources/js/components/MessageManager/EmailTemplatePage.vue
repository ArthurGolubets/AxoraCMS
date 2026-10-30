<template>
  <div ref="root">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isEdit ? 'Редактирование шаблона письма' : 'Новый шаблон письма' }}</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Соберите письмо из готовых блоков — как в конструкторе. Справа видно, как его получит клиент</p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <ThemeButton variant="secondary" @click="openCode">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
          Код письма
        </ThemeButton>
        <ThemeButton variant="secondary" @click="$router.push('/message-templates')">Отмена</ThemeButton>
        <ThemeButton variant="primary" :disabled="saving || loading" @click="save">{{ saving ? 'Сохранение…' : 'Сохранить' }}</ThemeButton>
      </div>
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <div v-else class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_26rem] gap-6 items-start">
      <!-- Left: settings and blocks -->
      <div class="space-y-6 min-w-0">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
          <p v-if="errorMessage" class="px-4 py-2 text-sm rounded-lg bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300">{{ errorMessage }}</p>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">Название шаблона *</label>
              <input v-model="form.name" type="text" :class="inputClass" placeholder="Например: Подтверждение заказа">
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Видно только вам — чтобы быстро найти шаблон в списке</p>
            </div>
            <div>
              <label :class="labelClass">Для какого события письмо</label>
              <select v-model="form.event" :class="inputClass">
                <option :value="null">Для любого</option>
                <optgroup v-for="group in groupedEvents" :key="group.name" :label="group.name">
                  <option v-for="event in group.events" :key="event.code" :value="event.code">{{ event.label }}</option>
                </optgroup>
              </select>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">От этого зависит, какие данные можно вставить (номер заказа, имя клиента и т. д.)</p>
            </div>
          </div>

          <div>
            <label :class="labelClass">Тема письма *</label>
            <input v-model="form.subject" data-var-target type="text" :class="inputClass" placeholder="Ваш заказ №#ORDER_ID# принят">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Это увидит клиент в списке писем. Данные можно вставить справа</p>
          </div>

          <ToggleSwitch v-model="form.is_active" :theme-color="themeColor" label="Шаблон включён" />
        </div>

        <!-- Code mode notice -->
        <div v-if="doc.mode === 'html'" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-6">
          <h3 class="text-base font-semibold text-amber-900 dark:text-amber-200">Письмо задано кодом</h3>
          <p class="text-sm text-amber-800 dark:text-amber-300 mt-1">Это письмо создано или изменено в виде кода, поэтому блоки недоступны. Посмотреть и поправить код можно кнопкой «Код письма» вверху.</p>
          <div class="mt-3 flex flex-wrap gap-3">
            <ThemeButton variant="secondary" size="sm" @click="openCode">Открыть код письма</ThemeButton>
            <ThemeButton variant="secondary" size="sm" @click="backToBlocks">Собрать заново из блоков</ThemeButton>
          </div>
        </div>

        <template v-else>
          <!-- Blocks -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Содержимое письма</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Блоки идут сверху вниз так же, как в письме. Нажмите на блок, чтобы изменить его</p>

            <div v-if="!doc.items.length" class="mt-4 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
              В письме пока нет блоков. Добавьте первый ниже.
            </div>

            <div class="mt-4 space-y-3">
              <div v-for="(block, index) in doc.items" :key="block._key" class="rounded-lg border" :class="selected === block._key ? 'border-blue-400 dark:border-blue-500 ring-1 ring-blue-200 dark:ring-blue-900' : 'border-gray-200 dark:border-gray-700'">
                <div class="flex items-center gap-3 px-4 py-3 cursor-pointer" @click="toggleBlock(block)">
                  <span class="w-8 h-8 shrink-0 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="BLOCK_TYPES[block.type].icon"/></svg>
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ BLOCK_TYPES[block.type].label }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ summary(block) }}</p>
                  </div>
                  <div class="flex items-center" @click.stop>
                    <button type="button" title="Поднять выше" :disabled="index === 0" @click="moveBlock(index, -1)" :class="iconButton">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <button type="button" title="Опустить ниже" :disabled="index === doc.items.length - 1" @click="moveBlock(index, 1)" :class="iconButton">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <button type="button" title="Копировать блок" @click="duplicateBlock(index)" :class="iconButton">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                    <button type="button" title="Удалить блок" @click="removeBlock(index)" :class="[iconButton, 'text-red-500']">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </div>

                <!-- Block editor -->
                <div v-if="selected === block._key" class="px-4 pb-4 pt-1 space-y-4 border-t border-gray-100 dark:border-gray-700">
                  <template v-if="block.type === 'heading'">
                    <div><label :class="labelClass">Текст заголовка</label><input v-model="block.text" data-var-target type="text" :class="inputClass"></div>
                    <div class="flex flex-wrap gap-6">
                      <Segmented label="Размер" v-model="block.size" :options="[['large', 'Крупный'], ['medium', 'Средний'], ['small', 'Мелкий']]" />
                      <Segmented label="Расположение" v-model="block.align" :options="alignOptions" />
                    </div>
                  </template>

                  <template v-else-if="block.type === 'text'">
                    <div><label :class="labelClass">Текст</label><textarea v-model="block.text" data-var-target rows="4" :class="inputClass"></textarea></div>
                    <div class="flex flex-wrap gap-6">
                      <Segmented label="Размер" v-model="block.size" :options="[['normal', 'Обычный'], ['small', 'Мелкий серый']]" />
                      <Segmented label="Расположение" v-model="block.align" :options="alignOptions" />
                    </div>
                  </template>

                  <template v-else-if="block.type === 'button'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div><label :class="labelClass">Надпись на кнопке</label><input v-model="block.label" data-var-target type="text" :class="inputClass"></div>
                      <div>
                        <label :class="labelClass">Куда ведёт кнопка</label>
                        <input v-model="block.url" data-var-target type="text" :class="inputClass" placeholder="https://… или выберите справа">
                      </div>
                    </div>
                    <div class="flex flex-wrap items-end gap-6">
                      <Segmented label="Расположение" v-model="block.align" :options="alignOptions" />
                      <div>
                        <label :class="labelClass">Цвет кнопки</label>
                        <div class="flex items-center gap-2">
                          <input :value="block.color || doc.settings.accent" @input="block.color = $event.target.value" type="color" class="h-9 w-12 rounded border border-gray-300 dark:border-gray-600 bg-transparent">
                          <button v-if="block.color" type="button" class="text-xs text-blue-600 dark:text-blue-400" @click="block.color = ''">Вернуть основной цвет письма</button>
                        </div>
                      </div>
                    </div>
                  </template>

                  <template v-else-if="block.type === 'link'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div><label :class="labelClass">Текст ссылки</label><input v-model="block.text" data-var-target type="text" :class="inputClass"></div>
                      <div><label :class="labelClass">Куда ведёт ссылка</label><input v-model="block.url" data-var-target type="text" :class="inputClass" placeholder="https://… или выберите справа"></div>
                    </div>
                    <Segmented label="Расположение" v-model="block.align" :options="alignOptions" />
                  </template>

                  <template v-else-if="block.type === 'image'">
                    <ImageUpload v-model="block.src" label="Картинка" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div><label :class="labelClass">Описание картинки</label><input v-model="block.alt" type="text" :class="inputClass" placeholder="Показывается, если картинка не загрузилась"></div>
                      <div><label :class="labelClass">Ссылка при нажатии <span class="font-normal text-gray-400">необязательно</span></label><input v-model="block.url" data-var-target type="text" :class="inputClass" placeholder="https://…"></div>
                    </div>
                    <div class="flex flex-wrap gap-6">
                      <Segmented label="Ширина" v-model="block.width" :options="[['full', 'Во всю ширину'], ['medium', 'Половина'], ['small', 'Маленькая']]" />
                      <Segmented label="Расположение" v-model="block.align" :options="alignOptions" />
                    </div>
                  </template>

                  <template v-else-if="block.type === 'details'">
                    <div class="space-y-2">
                      <div v-for="(row, rowIndex) in block.rows" :key="rowIndex" class="flex items-center gap-2">
                        <input v-model="row.label" data-var-target type="text" :class="inputClass" placeholder="Название, например: Телефон">
                        <input v-model="row.value" data-var-target type="text" :class="inputClass" placeholder="Значение, например: #ORDER_PHONE#">
                        <button type="button" title="Убрать строку" @click="block.rows.splice(rowIndex, 1)" :class="[iconButton, 'text-red-500']">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                      </div>
                    </div>
                    <ThemeButton variant="secondary" size="sm" @click="block.rows.push({ label: '', value: '' })">+ Добавить строку</ThemeButton>
                  </template>

                  <template v-else-if="block.type === 'spacer'">
                    <Segmented label="Высота отступа" v-model="block.height" :options="[['small', 'Маленький'], ['medium', 'Средний'], ['large', 'Большой']]" />
                  </template>

                  <p v-else-if="block.type === 'products'" class="text-sm text-gray-500 dark:text-gray-400">Здесь появится таблица с товарами из заказа: название, количество и сумма. Настраивать ничего не нужно.</p>
                  <p v-else class="text-sm text-gray-500 dark:text-gray-400">У этого блока нет настроек.</p>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">Добавить блок</p>
              <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                <button v-for="(type, key) in BLOCK_TYPES" :key="key" type="button" :disabled="key === 'products' && !hasProducts" :title="key === 'products' && !hasProducts ? 'Доступно для событий с заказом — выберите событие выше' : type.hint" @click="addBlock(key)"
                  class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:border-blue-400 hover:text-blue-700 dark:hover:border-blue-400 transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:border-gray-200">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="type.icon"/></svg>
                  <span class="text-xs">{{ type.label }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Look -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Оформление</h3>
            <div class="mt-3 flex flex-wrap gap-8">
              <div>
                <label :class="labelClass">Цвет фона письма</label>
                <input v-model="doc.settings.pageBg" type="color" class="h-9 w-14 rounded border border-gray-300 dark:border-gray-600 bg-transparent">
              </div>
              <div>
                <label :class="labelClass">Основной цвет (кнопки и ссылки)</label>
                <input v-model="doc.settings.accent" type="color" class="h-9 w-14 rounded border border-gray-300 dark:border-gray-600 bg-transparent">
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Right: preview + variables -->
      <div class="space-y-6 xl:sticky xl:top-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">Так увидит клиент</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">На примере данных, обновляется само</p>
          </div>
          <p class="px-4 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 truncate">
            <span class="text-gray-500 dark:text-gray-400">Тема: </span>{{ preview.subject || '—' }}
          </p>
          <iframe sandbox="" :srcdoc="previewDocument" class="w-full bg-white block" style="height: 28rem" title="Предпросмотр письма"></iframe>
        </div>

        <VariablePalette :variables="variables" title="Вставить данные в письмо" @insert="onInsert" />
      </div>
    </div>

    <!-- Email code -->
    <SidePanel v-if="codeOpen" title="Код письма" width-class="max-w-3xl" @close="codeOpen = false">
      <div class="space-y-4">
        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4 text-sm text-blue-900 dark:text-blue-200">
          Это готовый код письма (HTML). Если вы не знаете, что это, ничего менять не нужно — письмо собирается из блоков автоматически.
          <template v-if="doc.mode === 'blocks'"> Если вы измените код вручную, блоки для этого письма отключатся.</template>
        </div>
        <textarea v-model="codeDraft" rows="26" spellcheck="false" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 font-mono text-xs leading-relaxed"></textarea>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <ThemeButton variant="secondary" @click="codeOpen = false">Закрыть</ThemeButton>
          <ThemeButton variant="primary" @click="applyCode">Применить изменения</ThemeButton>
        </div>
      </template>
    </SidePanel>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, defineComponent, h } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from '../ThemeButton.vue';
import ToggleSwitch from '../ToggleSwitch.vue';
import SidePanel from '../SidePanel.vue';
import ImageUpload from '../ImageUpload.vue';
import VariablePalette from './VariablePalette.vue';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';
import { api } from '../../composables/useMessageManager';
import { BLOCK_TYPES, DEFAULT_SETTINGS, newBlocksDocument, renderBlocks } from '../../utils/emailBlocks';

/** Row of mutually exclusive buttons (alignment, size...). */
const Segmented = defineComponent({
  props: { modelValue: String, options: Array, label: String },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () => h('div', [
      h('label', { class: 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1' }, props.label),
      h('div', { class: 'inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden' }, props.options.map(([value, text]) => h('button', {
        type: 'button',
        onClick: () => emit('update:modelValue', value),
        class: ['px-3 py-1.5 text-sm transition-colors', props.modelValue === value ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600'],
      }, text))),
    ]);
  },
});

const route = useRoute();
const router = useRouter();
const { success, confirm } = useModal();
const { themeColor } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';
const iconButton = 'p-1.5 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed';
const alignOptions = [['left', 'Слева'], ['center', 'По центру'], ['right', 'Справа']];

const isEdit = computed(() => !!route.params.id);
const root = ref(null);
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const meta = ref({ events: [], common_variables: [] });
const form = ref({ name: '', event: null, subject: '', is_active: true });
const doc = ref({ mode: 'blocks', settings: { ...DEFAULT_SETTINGS }, items: [], html: '' });
const selected = ref(null);
const codeOpen = ref(false);
const codeDraft = ref('');
const preview = ref({ subject: '', body: '' });
let previewTimer = null;
let activeField = null;
let keySeq = 0;

const keyed = (block) => ({ ...block, _key: `b${++keySeq}` });

const groupedEvents = computed(() => {
  const groups = {};
  meta.value.events.forEach(event => {
    (groups[event.group] ||= { name: event.group, events: [] }).events.push(event);
  });
  return Object.values(groups);
});

const variables = computed(() => meta.value.events.find(e => e.code === form.value.event)?.variables || meta.value.common_variables);
const hasProducts = computed(() => variables.value.some(v => v.code === 'PRODUCTS'));

const currentHtml = computed(() => (doc.value.mode === 'blocks' ? renderBlocks(doc.value) : doc.value.html));
const previewDocument = computed(() => `<!doctype html><meta charset="utf-8"><body style="margin:0">${preview.value.body || ''}</body>`);

const summary = (block) => {
  const text = block.text || block.label || block.src || (block.rows || []).map(r => r.label).filter(Boolean).join(', ');
  return String(text || BLOCK_TYPES[block.type].hint).replace(/\s+/g, ' ');
};

const addBlock = (type) => {
  const block = keyed(BLOCK_TYPES[type].create());
  doc.value.items.push(block);
  selected.value = block._key;
};

const toggleBlock = (block) => {
  selected.value = selected.value === block._key ? null : block._key;
};

const moveBlock = (index, direction) => {
  const list = doc.value.items;
  const target = index + direction;
  if (target < 0 || target >= list.length) return;
  [list[index], list[target]] = [list[target], list[index]];
};

const duplicateBlock = (index) => {
  const copy = keyed(JSON.parse(JSON.stringify(doc.value.items[index], (key, value) => (key === '_key' ? undefined : value))));
  doc.value.items.splice(index + 1, 0, copy);
  selected.value = copy._key;
};

const removeBlock = async (index) => {
  if (!await confirm('Удалить блок?', 'Блок исчезнет из письма.')) return;
  doc.value.items.splice(index, 1);
};

// --- variables: insert into whichever text field was used last -----------------------------
const rememberField = (event) => {
  const el = event.target;
  if (el && el.matches?.('[data-var-target]')) activeField = el;
};

const insertVariable = (token) => {
  const el = activeField && root.value?.contains(activeField) ? activeField : null;
  if (!el) return false;

  const start = el.selectionStart ?? el.value.length;
  const end = el.selectionEnd ?? el.value.length;
  el.setRangeText(token, start, end, 'end');
  el.dispatchEvent(new Event('input', { bubbles: true }));
  el.focus();
  return true;
};

const onInsert = (token) => {
  if (!insertVariable(token)) {
    errorMessage.value = 'Сначала нажмите на поле (тему письма или текст блока), куда нужно вставить данные';
  } else {
    errorMessage.value = '';
  }
};

// --- code panel -----------------------------------------------------------------------------
const openCode = () => {
  codeDraft.value = currentHtml.value;
  codeOpen.value = true;
};

const applyCode = async () => {
  if (codeDraft.value === currentHtml.value) {
    codeOpen.value = false;
    return;
  }

  if (doc.value.mode === 'blocks' && !await confirm('Изменить код вручную?', 'После этого письмо перестанет собираться из блоков. Вернуться к блокам можно, но правки кода пропадут.')) return;
  doc.value.mode = 'html';
  doc.value.html = codeDraft.value;
  codeOpen.value = false;
};

const backToBlocks = async () => {
  if (!await confirm('Собрать письмо заново?', 'Текущий код письма будет заменён блоками. Это действие нельзя отменить.')) return;
  const fresh = doc.value.items.length ? doc.value.items : newBlocksDocument().items.map(keyed);
  doc.value = { ...doc.value, mode: 'blocks', items: fresh };
};

// --- preview --------------------------------------------------------------------------------
const loadPreview = async () => {
  try {
    preview.value = await api('POST', '/preview', { event: form.value.event, subject: form.value.subject, body: currentHtml.value });
  } catch (e) {
    // The preview is a convenience; saving reports real problems.
  }
};

watch([() => form.value.subject, () => form.value.event, currentHtml], () => {
  clearTimeout(previewTimer);
  previewTimer = setTimeout(loadPreview, 350);
});

// --- save -----------------------------------------------------------------------------------
const save = async () => {
  errorMessage.value = '';

  if (!currentHtml.value.trim()) {
    errorMessage.value = 'Добавьте в письмо хотя бы один блок';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    return;
  }

  saving.value = true;
  try {
    const payload = {
      name: form.value.name,
      event: form.value.event,
      subject: form.value.subject,
      is_active: form.value.is_active,
      body: currentHtml.value,
      blocks: {
        mode: doc.value.mode,
        settings: doc.value.settings,
        items: doc.value.items.map(({ _key, ...block }) => block),
      },
    };

    if (isEdit.value) {
      await api('PUT', `/templates/${route.params.id}`, payload);
    } else {
      await api('POST', '/templates', payload);
    }

    await success('Шаблон сохранён');
    router.push('/message-templates');
  } catch (e) {
    errorMessage.value = e.message;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  root.value?.addEventListener('focusin', rememberField);

  try {
    meta.value = await api('GET', '/meta');

    if (isEdit.value) {
      const template = (await api('GET', `/templates/${route.params.id}`)).data;
      form.value = { name: template.name, event: template.event, subject: template.subject, is_active: template.is_active };

      if (template.blocks && Array.isArray(template.blocks.items)) {
        doc.value = {
          mode: template.blocks.mode === 'html' ? 'html' : 'blocks',
          settings: { ...DEFAULT_SETTINGS, ...(template.blocks.settings || {}) },
          items: template.blocks.items.map(keyed),
          html: template.body,
        };
      } else {
        // Created as code (or by the starting set): keep the code, offer blocks on demand.
        doc.value = { mode: 'html', settings: { ...DEFAULT_SETTINGS }, items: [], html: template.body };
      }
    } else {
      const fresh = newBlocksDocument();
      doc.value = { ...fresh, items: fresh.items.map(keyed), html: '' };
      form.value.event = route.query.event ? String(route.query.event) : null;
    }
  } catch (e) {
    errorMessage.value = e.message;
  } finally {
    loading.value = false;
    loadPreview();
  }
});

onBeforeUnmount(() => {
  root.value?.removeEventListener('focusin', rememberField);
  clearTimeout(previewTimer);
});

defineExpose({ insertVariable });
</script>
