<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Импорт/экспорт каталогов</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Загрузка товаров из Excel, CSV и XML с сопоставлением полей и выгрузка каталога</p>
    </div>

    <!-- Running tasks -->
    <div v-for="task in activeTasks" :key="task.id" class="mb-4 flex flex-wrap items-center gap-4 p-4 rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-900/20">
      <svg class="w-5 h-5 text-blue-600 dark:text-blue-300 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 truncate">{{ task.title }}</p>
        <p class="text-xs text-blue-700 dark:text-blue-300">Выполнено {{ task.progress }}% · {{ task.processed }} из {{ task.total }}</p>
      </div>
      <button type="button" @click="openTask(task.id)" class="px-3 py-1.5 text-sm rounded-lg bg-white dark:bg-gray-800 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-gray-700">
        Открыть прогресс
      </button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
      <!-- Import -->
      <div class="xl:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Импорт товаров</h3>
          <ol class="mt-3 flex flex-wrap items-center gap-2 text-xs">
            <li v-for="(step, index) in ['Файл', 'Сопоставление', 'Запуск']" :key="step" class="flex items-center gap-2">
              <span
                class="w-6 h-6 rounded-full flex items-center justify-center font-semibold"
                :class="importStep >= index + 1 ? 'text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                :style="importStep >= index + 1 ? { backgroundColor: themeColor } : {}"
              >{{ index + 1 }}</span>
              <span :class="importStep >= index + 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">{{ step }}</span>
              <span v-if="index < 2" class="w-6 h-px bg-gray-300 dark:bg-gray-600"></span>
            </li>
          </ol>
        </div>

        <!-- Step 1: file -->
        <div v-if="importStep === 1" class="p-6">
          <label
            class="flex flex-col items-center justify-center gap-3 px-6 py-12 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
            :class="dragOver ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
          >
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ uploading ? 'Читаем файл...' : 'Перетащите файл сюда или нажмите, чтобы выбрать' }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">Excel (xlsx, xls), CSV или XML · до {{ maxUploadLabel }} · первая строка — заголовки колонок</span>
            <input type="file" accept=".xlsx,.xls,.csv,.xml" class="hidden" :disabled="uploading" @change="onFileInput">
          </label>
        </div>

        <!-- Step 2: mapping -->
        <div v-else-if="importStep === 2" class="p-6 space-y-6">
          <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
            <span class="text-gray-700 dark:text-gray-300">
              Файл <span class="font-medium">{{ draft.task.original_name }}</span> · строк: {{ draft.task.total }}
            </span>
            <button type="button" @click="resetImport" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Выбрать другой файл</button>
          </div>

          <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <div class="grid grid-cols-12 gap-3 px-4 py-2 bg-gray-50 dark:bg-gray-900/50 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
              <span class="col-span-5">Колонка файла</span>
              <span class="col-span-7">Поле в каталоге</span>
            </div>
            <div class="max-h-[30rem] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
              <div v-for="column in draft.columns" :key="column.key" class="grid grid-cols-12 gap-3 px-4 py-2.5 items-center">
                <div class="col-span-5 min-w-0">
                  <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ column.label }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 truncate" :title="samples(column.key)">{{ samples(column.key) || 'пусто' }}</div>
                </div>
                <select v-model="mapping[column.key]" :class="[inputClass, 'col-span-7', mapping[column.key] ? '' : 'text-gray-400']">
                  <option value="">— Не импортировать —</option>
                  <optgroup v-for="group in draft.targets" :key="group.group" :label="group.group">
                    <option v-for="item in group.items" :key="item.key" :value="item.key" :disabled="isTaken(item.key, column.key)">
                      {{ item.label }}{{ item.hint ? ` (${item.hint})` : '' }}
                    </option>
                  </optgroup>
                </select>
              </div>
            </div>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Свойства применяются, если такое свойство есть в категории товара (или её родителях). Несколько значений в одной ячейке разделяйте «;» или «|».
          </p>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">Что делать</label>
              <select v-model="settings.mode" :class="inputClass">
                <option value="create_update">Создавать новые и обновлять существующие</option>
                <option value="create_only">Только создавать новые</option>
                <option value="update_only">Только обновлять существующие</option>
              </select>
            </div>
            <div>
              <label :class="labelClass">Существующий товар искать по</label>
              <select v-model="settings.match_by" :class="inputClass">
                <option value="sku">Артикулу (SKU)</option>
                <option value="name">Названию</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label :class="labelClass">Категория по умолчанию</label>
              <CategorySelect v-model="settings.default_catalog_id" :categories="catalogs" clearable placeholder="Для строк без категории" picker-title="Категория по умолчанию" />
            </div>
          </div>
          <div class="space-y-3">
            <ToggleSwitch v-model="settings.create_categories" :theme-color="themeColor" label="Создавать категории, которых нет" />
            <ToggleSwitch v-model="settings.download_images" :theme-color="themeColor" label="Скачивать изображения по ссылкам" />
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="resetImport" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Отмена</button>
            <ThemeButton variant="primary" :disabled="starting || !mappedCount" @click="startImport">
              {{ starting ? 'Запуск...' : `Запустить импорт (${mappedCount} полей)` }}
            </ThemeButton>
          </div>
        </div>
      </div>

      <!-- Export -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Экспорт каталога</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Файл можно отредактировать и загрузить обратно — колонки сопоставятся автоматически.</p>

        <div>
          <label :class="labelClass">Категории</label>
          <CategorySelect v-model="exportCatalogPick" :categories="catalogs" clearable placeholder="Все категории — или выберите" picker-title="Добавить категорию" />
          <div v-if="exportCatalogs.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="id in exportCatalogs" :key="id" class="inline-flex items-center gap-1 pl-2.5 pr-1 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
              {{ catalogName(id) }}
              <button type="button" @click="exportCatalogs = exportCatalogs.filter((c) => c !== id)" class="p-0.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </span>
          </div>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Вместе с подкатегориями</p>
        </div>

        <div>
          <label :class="labelClass">Формат</label>
          <div class="grid grid-cols-2 gap-1 p-1 rounded-lg bg-gray-100 dark:bg-gray-900">
            <button v-for="format in ['xlsx', 'csv']" :key="format" type="button" @click="exportFormat = format"
              class="py-1.5 text-sm font-medium rounded-md uppercase"
              :class="exportFormat === format ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">{{ format }}</button>
          </div>
        </div>

        <ThemeButton variant="primary" class="w-full" :disabled="exporting" @click="startExport">
          {{ exporting ? 'Запуск...' : 'Выгрузить каталог' }}
        </ThemeButton>
      </div>
    </div>

    <div class="mt-6">
      <TaskHistory ref="history" scope="catalog" @open="(task) => openTask(task.id)" @loaded="onHistoryLoaded" />
    </div>

    <TaskProgressPanel v-if="openTaskId" :task-id="openTaskId" @updated="onTaskUpdated" @close="openTaskId = null" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from '../ThemeButton.vue';
import ToggleSwitch from '../ToggleSwitch.vue';
import CategorySelect from '../CategorySelect.vue';
import TaskHistory from './TaskHistory.vue';
import TaskProgressPanel from './TaskProgressPanel.vue';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';
import { csrfHeaders, responseError } from './importExport';

const route = useRoute();
const router = useRouter();
const { error } = useModal();
const { themeColor } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
const inputClass = 'w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white';

const history = ref(null);
const activeTasks = ref([]);
const openTaskId = ref(null);
const catalogs = ref([]);

// --- Import --------------------------------------------------------------------

const draft = ref(null);
const mapping = ref({});
const uploading = ref(false);
const starting = ref(false);
const dragOver = ref(false);
const settings = ref({
  mode: 'create_update',
  match_by: 'sku',
  create_categories: true,
  default_catalog_id: null,
  download_images: true,
});

// Limit of the PHP server (upload_max_filesize / post_max_size), from the meta endpoint.
const maxUploadBytes = ref(50 * 1024 * 1024);
const maxUploadLabel = computed(() => `${Math.max(1, Math.floor(maxUploadBytes.value / 1048576))} МБ`);

const importStep = computed(() => (draft.value ? 2 : 1));
const mappedCount = computed(() => Object.values(mapping.value).filter(Boolean).length);

const samples = (key) => (draft.value?.sample || [])
  .map((row) => row[key])
  .filter((v) => v !== '' && v !== null && v !== undefined)
  .slice(0, 3)
  .join(' · ');

const isTaken = (target, columnKey) => Object.entries(mapping.value).some(([key, value]) => value === target && key !== columnKey);

const upload = async (file) => {
  if (!file) return;
  if (file.size > maxUploadBytes.value) {
    error(`Файл весит ${(file.size / 1048576).toFixed(1)} МБ, а сервер принимает не больше ${maxUploadLabel.value}. Увеличьте upload_max_filesize и post_max_size в php.ini или сохраните файл в CSV — он обычно легче.`);
    return;
  }
  uploading.value = true;
  try {
    const body = new FormData();
    body.append('file', file);
    const response = await fetch('/admin/api/import-export/import/upload', { method: 'POST', headers: csrfHeaders(false), body });
    if (!response.ok) throw new Error(await responseError(response, 'Не удалось загрузить файл'));
    draft.value = await response.json();
    mapping.value = Object.fromEntries(draft.value.columns.map((c) => [c.key, draft.value.suggested_mapping[c.key] || '']));
  } catch (e) {
    error(e.message);
  } finally {
    uploading.value = false;
  }
};

const onFileInput = (event) => {
  upload(event.target.files[0]);
  event.target.value = '';
};

const onDrop = (event) => {
  dragOver.value = false;
  upload(event.dataTransfer.files[0]);
};

const resetImport = async () => {
  if (draft.value) {
    fetch(`/admin/api/import-export/tasks/${draft.value.task.id}`, { method: 'DELETE', headers: csrfHeaders() });
  }
  draft.value = null;
  mapping.value = {};
};

const startImport = async () => {
  starting.value = true;
  try {
    const response = await fetch(`/admin/api/import-export/import/${draft.value.task.id}/start`, {
      method: 'POST',
      headers: csrfHeaders(),
      body: JSON.stringify({ mapping: mapping.value, settings: settings.value }),
    });
    if (!response.ok) throw new Error(await responseError(response, 'Не удалось запустить импорт'));
    const task = await response.json();
    draft.value = null;
    mapping.value = {};
    openTask(task.id);
    history.value?.reload();
  } catch (e) {
    error(e.message);
  } finally {
    starting.value = false;
  }
};

// --- Export --------------------------------------------------------------------

const exportCatalogPick = ref(null);
const exportCatalogs = ref([]);
const exportFormat = ref('xlsx');
const exporting = ref(false);

const catalogName = (id) => catalogs.value.find((c) => c.id === id)?.name || `#${id}`;

watch(exportCatalogPick, (id) => {
  if (id && !exportCatalogs.value.includes(id)) exportCatalogs.value.push(id);
  if (id) exportCatalogPick.value = null;
});

const startExport = async () => {
  exporting.value = true;
  try {
    const response = await fetch('/admin/api/import-export/export', {
      method: 'POST',
      headers: csrfHeaders(),
      body: JSON.stringify({ entity: 'products', format: exportFormat.value, options: { catalog_ids: exportCatalogs.value } }),
    });
    if (!response.ok) throw new Error(await responseError(response, 'Не удалось запустить экспорт'));
    const task = await response.json();
    openTask(task.id);
    history.value?.reload();
  } catch (e) {
    error(e.message);
  } finally {
    exporting.value = false;
  }
};

// --- Tasks ---------------------------------------------------------------------

const openTask = (id) => {
  openTaskId.value = id;
};

const onHistoryLoaded = (tasks) => {
  activeTasks.value = tasks.filter((t) => t.is_active);
};

const onTaskUpdated = (task) => {
  if (!task.is_active) history.value?.reload();
};

const loadCatalogs = async () => {
  try {
    const response = await fetch('/admin/api/catalogs/list', { headers: csrfHeaders(false) });
    if (response.ok) catalogs.value = await response.json();
  } catch (e) {
    catalogs.value = [];
  }
};

// Links from notifications: ?task=ID opens its progress.
const openFromQuery = () => {
  const id = Number(route.query.task);
  if (!id) return;
  openTask(id);
  router.replace({ query: {} });
};

watch(() => route.query.task, openFromQuery);

const loadLimits = async () => {
  try {
    const response = await fetch('/admin/api/import-export/meta', { headers: csrfHeaders(false) });
    if (response.ok) maxUploadBytes.value = (await response.json()).max_upload_bytes || maxUploadBytes.value;
  } catch (e) {
    // keep the default
  }
};

onMounted(() => {
  loadCatalogs();
  loadLimits();
  openFromQuery();
});
</script>
