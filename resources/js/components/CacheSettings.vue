<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Кеширование</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Настройте, что сайт берёт из кеша вместо базы данных, чтобы страницы открывались быстрее</p>
      </div>
      <div class="flex items-center gap-3">
        <ThemeButton variant="secondary" :disabled="clearing || loading" @click="clearCache">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          {{ clearing ? 'Очистка…' : 'Очистить кеш' }}
        </ThemeButton>
        <ThemeButton variant="primary" :disabled="saving || loading" @click="save">
          {{ saving ? 'Сохранение…' : 'Сохранить' }}
        </ThemeButton>
      </div>
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <div v-else class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
      <div class="xl:col-span-2 space-y-6">
        <!-- Master switch -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 flex items-start justify-between gap-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Кеширование сайта</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Главный переключатель. Когда он выключен, сайт работает как без модуля.</p>
          </div>
          <ToggleSwitch v-model="form.cache_enabled" :theme-color="themeColor" />
        </div>

        <!-- What to cache -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg" :class="{ 'opacity-60': !form.cache_enabled }">
          <div class="px-6 pt-5 pb-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Что кешировать</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Кеш сбрасывается автоматически, когда вы что-то меняете в админке</p>
          </div>
          <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="option in dataOptions" :key="option.key" class="px-6 py-4 flex items-start justify-between gap-6">
              <div>
                <div class="flex items-center gap-2">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">{{ option.title }}</span>
                  <span v-if="option.recommended" class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Рекомендуется</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ option.description }}</p>
              </div>
              <ToggleSwitch v-model="form[option.key]" :disabled="!form.cache_enabled" :theme-color="themeColor" />
            </div>
          </div>
        </div>

        <!-- Full page cache -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg" :class="{ 'opacity-60': !form.cache_enabled }">
          <div class="px-6 py-5 flex items-start justify-between gap-6">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Кеш страниц целиком (HTML)</h3>
                <span class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Максимальная скорость</span>
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Гости получают готовую страницу без обращения к базе данных. Не кешируются: авторизованные пользователи,
                посетители с товарами в корзине или избранном, страницы после отправки форм и адреса из списка исключений.
              </p>
            </div>
            <ToggleSwitch v-model="form.cache_html" :disabled="!form.cache_enabled" :theme-color="themeColor" />
          </div>

          <div v-if="form.cache_html" class="px-6 pb-6 space-y-5 border-t border-gray-100 dark:border-gray-700 pt-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Время жизни страницы, минут</label>
                <input v-model.number="form.cache_html_ttl" type="number" min="1" max="1440" :class="inputClass">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Через это время страница соберётся заново, даже если ничего не менялось</p>
              </div>
              <div class="flex items-start pt-6">
                <ToggleSwitch v-model="form.cache_html_query" :theme-color="themeColor" label="Кешировать страницы с GET-параметрами (фильтры, сортировка, пагинация)" />
              </div>
            </div>
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Не кешировать адреса</label>
                <button type="button" @click="form.cache_html_excluded = defaultHtmlExcluded" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Вернуть по умолчанию</button>
              </div>
              <textarea v-model="form.cache_html_excluded" rows="6" :class="[inputClass, 'font-mono text-sm']" placeholder="cart*"></textarea>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                По одному адресу на строку, без домена. <code class="px-1 bg-gray-100 dark:bg-gray-700 rounded">*</code> — любые символы, например <code class="px-1 bg-gray-100 dark:bg-gray-700 rounded">account*</code>.
                Страница также не попадёт в кеш, если контроллер отдаст заголовок <code class="px-1 bg-gray-100 dark:bg-gray-700 rounded">X-Axora-No-Cache</code>.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Side column -->
      <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-5" :class="{ 'opacity-60': !form.cache_enabled }">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Параметры</h3>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Время жизни данных, минут</label>
            <input v-model.number="form.cache_ttl" type="number" min="1" max="10080" :class="inputClass">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Страховка на случай изменений в обход админки (например, прямо в базе)</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Хранилище кеша</label>
            <select v-model="form.cache_store" :class="inputClass">
              <option v-for="store in stores" :key="store.name" :value="store.name">{{ storeLabel(store) }}</option>
            </select>
            <p v-if="selectedStoreDriver === 'database'" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
              Кеш в базе данных — это тоже запросы к БД. Для максимальной скорости выберите файлы или Redis.
            </p>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400 mt-1">Хранилища настраиваются в config/cache.php проекта</p>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6" :class="{ 'opacity-60': !form.cache_enabled }">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Статистика посещений</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4">Когда записывать посещение страницы</p>
          <div class="space-y-3">
            <label v-for="mode in visitModes" :key="mode.value" class="flex items-start gap-3 cursor-pointer">
              <input v-model="form.cache_visits_mode" type="radio" :value="mode.value" :disabled="!form.cache_enabled" class="mt-1" :style="{ accentColor: themeColor }">
              <span>
                <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ mode.title }}</span>
                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ mode.description }}</span>
              </span>
            </label>
          </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-sm text-blue-800 dark:text-blue-300 space-y-2">
          <p>Кеш сбрасывается автоматически при сохранении в админке, после обмена с 1С и при установке модулей.</p>
          <p>Если данные меняли напрямую в базе, нажмите «Очистить кеш» или выполните <code class="px-1 bg-blue-100 dark:bg-blue-900/40 rounded">php artisan axoracms:cache-clear</code>.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import ThemeButton from './ThemeButton.vue';
import ToggleSwitch from './ToggleSwitch.vue';

const { confirm, success, error } = useModal();
const { themeColor } = useTheme();

const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const dataOptions = [
  {
    key: 'cache_schema',
    title: 'Структура базы данных',
    description: 'Проверки, какие модули и таблицы установлены, выполняются один раз, а не по 20–30 запросов на каждой странице.',
    recommended: true,
  },
  {
    key: 'cache_settings',
    title: 'Настройки сайта и меню',
    description: 'Контакты, скрипты, пользовательские свойства, меню шапки и подвала, SMTP — одно чтение вместо десятка запросов.',
    recommended: true,
  },
  {
    key: 'cache_pages',
    title: 'SEO-данные страниц',
    description: 'Мета-теги страниц, категорий и товаров и проверка, активна ли открытая страница.',
    recommended: true,
  },
  {
    key: 'cache_catalog',
    title: 'Каталог',
    description: 'Результаты CatalogService: дерево категорий, товары, фильтры, хлебные крошки, сопутствующие товары. Поиск не кешируется.',
  },
  {
    key: 'cache_infoblocks',
    title: 'Инфоблоки',
    description: 'Результаты InfoBlockService и TInfoBlock: элементы, разделы, списки. Случайная выборка не кешируется.',
  },
];

const visitModes = [
  { value: 'deferred', title: 'После загрузки страницы (рекомендуется)', description: 'Посетитель получает страницу сразу, запись делается следом' },
  { value: 'sync', title: 'Во время загрузки страницы', description: 'Как без модуля: посетитель ждёт, пока посещение запишется' },
  { value: 'off', title: 'Не записывать', description: 'Статистика посещений перестанет пополняться' },
];

const loading = ref(true);
const saving = ref(false);
const clearing = ref(false);
const form = ref({});
const stores = ref([]);
const defaultHtmlExcluded = ref('');

const driverLabels = {
  file: 'файлы',
  redis: 'Redis',
  memcached: 'Memcached',
  database: 'база данных',
  dynamodb: 'DynamoDB',
  apc: 'APCu',
  octane: 'Octane',
  failover: 'резервное',
};

const storeLabel = (store) => `${store.name} (${driverLabels[store.driver] || store.driver})`;

const selectedStoreDriver = computed(() => stores.value.find(store => store.name === form.value.cache_store)?.driver);

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const applyPayload = (data) => {
  form.value = { ...data.settings };
  stores.value = data.stores || [];
  defaultHtmlExcluded.value = data.default_html_excluded || '';
};

const load = async () => {
  loading.value = true;
  try {
    const response = await fetch('/admin/api/cache', { headers: { 'Accept': 'application/json' } });
    if (!response.ok) {
      throw new Error('Failed to load cache settings');
    }
    applyPayload(await response.json());
  } catch (err) {
    console.error('Error loading cache settings:', err);
    await error('Ошибка при загрузке настроек кеширования');
  } finally {
    loading.value = false;
  }
};

const save = async () => {
  saving.value = true;
  try {
    const response = await fetch('/admin/api/cache', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify(form.value),
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
      throw new Error(firstError || data.message || 'Failed to save');
    }

    applyPayload(data);
    await success('Настройки кеширования сохранены. Кеш очищен.');
  } catch (err) {
    console.error('Error saving cache settings:', err);
    await error(err.message || 'Ошибка при сохранении настроек');
  } finally {
    saving.value = false;
  }
};

const clearCache = async () => {
  const confirmed = await confirm('Очистить кеш?', 'Сайт заново соберёт данные при следующих открытиях страниц. Первые загрузки будут чуть медленнее.');
  if (!confirmed) return;

  clearing.value = true;
  try {
    const response = await fetch('/admin/api/cache/clear', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
    });
    if (!response.ok) {
      throw new Error('Failed to clear cache');
    }
    await success('Кеш сайта очищен');
  } catch (err) {
    console.error('Error clearing cache:', err);
    await error('Ошибка при очистке кеша');
  } finally {
    clearing.value = false;
  }
};

onMounted(load);
</script>
