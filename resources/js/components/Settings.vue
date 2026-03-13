<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Настройки системы</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Управление параметрами административной панели</p>
    </div>

    <form @submit.prevent="saveSettings" class="space-y-6">
      <!-- Основные настройки -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Основные настройки</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название системы</label>
            <input v-model="settings.panel_name" type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Цвет темы</label>
            <select v-model="settings.theme_color" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
              <option value="red">Красный</option>
              <option value="blue">Синий</option>
              <option value="green">Зеленый</option>
              <option value="purple">Фиолетовый</option>
              <option value="orange">Оранжевый</option>
              <option value="pink">Розовый</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Компания -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Информация о компании</h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название компании</label>
            <input v-model="settings.company_name" type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Время работы</label>
            <input v-model="settings.work_hours" type="text" placeholder="Пн-Пт: 9:00-18:00" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Телефоны</label>
            <div v-for="(phone, index) in settings.phones" :key="index" class="flex mb-2">
              <input v-model="settings.phones[index]" type="text" class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-l-lg text-gray-900 dark:text-white">
              <button @click.prevent="removePhone(index)" type="button" class="px-4 py-2 bg-red-600 text-white rounded-r-lg hover:bg-red-700">×</button>
            </div>
            <button @click.prevent="addPhone" type="button" :style="buttonStyle" class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90 text-sm">+ Добавить телефон</button>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Адреса</label>
            <div v-for="(address, index) in settings.addresses" :key="index" class="mb-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Адрес</label>
                  <input v-model="address.address" type="text" placeholder="г. Москва, ул. Ленина, д. 1" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Время работы</label>
                    <input v-model="address.hours" type="text" placeholder="Пн-Пт: 9:00-18:00" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm">
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Телефон</label>
                    <input v-model="address.phone" type="text" placeholder="+7 (999) 123-45-67" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm">
                  </div>
                </div>
                <button @click.prevent="removeAddress(index)" type="button" class="w-full px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">Удалить адрес</button>
              </div>
            </div>
            <button @click.prevent="addAddress" type="button" :style="buttonStyle" class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90 text-sm">+ Добавить адрес</button>
          </div>
        </div>
      </div>

      <!-- SEO по умолчанию -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">SEO по умолчанию</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title по умолчанию</label>
            <input v-model="settings.default_meta_title" type="text" placeholder="Заголовок сайта" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Используется, если у страницы, каталога или товара не указан meta title
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description по умолчанию</label>
            <textarea v-model="settings.default_meta_description" rows="3" placeholder="Описание вашего сайта" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></textarea>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Используется, если у страницы, каталога или товара не указан meta description
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Keywords по умолчанию</label>
            <input v-model="settings.default_meta_keywords" type="text" placeholder="ключевые, слова, через, запятую" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Используется, если у страницы, каталога или товара не указаны meta keywords
            </p>
          </div>
        </div>
      </div>

      <!-- Код для вставки -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Код для вставки</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Код в &lt;head&gt;</label>
            <div class="relative">
              <textarea
                v-model="settings.header_code"
                rows="8"
                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-green-400 font-mono text-sm leading-relaxed focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                style="tab-size: 2;"
                spellcheck="false"
              ></textarea>
              <div class="absolute top-2 right-2 text-xs text-gray-500 font-mono">HTML/JS</div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              Например: Google Analytics, Yandex Metrika, Open Graph теги
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Код перед &lt;/body&gt;</label>
            <div class="relative">
              <textarea
                v-model="settings.footer_code"
                rows="8"
                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-green-400 font-mono text-sm leading-relaxed focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                style="tab-size: 2;"
                spellcheck="false"
              ></textarea>
              <div class="absolute top-2 right-2 text-xs text-gray-500 font-mono">HTML/JS</div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              Например: скрипты чатов, пиксели отслеживания, дополнительные виджеты
            </p>
          </div>
        </div>
      </div>

      <!-- Кнопка сохранения -->
      <div class="flex justify-end">
        <button type="submit" :disabled="loading" :style="buttonStyle" class="px-6 py-3 text-white rounded-lg font-medium transition-opacity hover:opacity-90 disabled:opacity-50">
          {{ loading ? 'Сохранение...' : 'Сохранить изменения' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';

const { success, error } = useModal();
const { buttonStyle } = useTheme();

const loading = ref(false);
const settings = ref({
  panel_name: '',
  theme_color: 'red',
  company_name: '',
  phones: [],
  work_hours: '',
  addresses: [],
  default_meta_title: '',
  default_meta_description: '',
  default_meta_keywords: '',
  header_code: '',
  footer_code: ''
});

const fetchSettings = async () => {
  try {
    const response = await fetch('/admin/api/settings');
    const data = await response.json();

    // Преобразование старого формата адресов в новый
    let addresses = data.addresses || [];
    if (addresses.length > 0 && typeof addresses[0] === 'string') {
      addresses = addresses.map(addr => ({
        address: addr,
        hours: '',
        phone: ''
      }));
    }

    settings.value = {
      ...settings.value,
      ...data,
      phones: data.phones || [],
      addresses: addresses
    };
  } catch (err) {
    console.error('Error fetching settings:', err);
    await error('Ошибка при загрузке настроек');
  }
};

const saveSettings = async () => {
  loading.value = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/settings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify(settings.value)
    });

    if (!response.ok) {
      throw new Error('Failed to save settings');
    }

    await success('Настройки успешно сохранены!');

    // Reload page to apply theme color changes
    setTimeout(() => {
      window.location.reload();
    }, 1500);
  } catch (err) {
    console.error('Error saving settings:', err);
    await error('Ошибка при сохранении настроек');
  } finally {
    loading.value = false;
  }
};

const addPhone = () => {
  settings.value.phones.push('');
};

const removePhone = (index) => {
  settings.value.phones.splice(index, 1);
};

const addAddress = () => {
  settings.value.addresses.push({
    address: '',
    hours: '',
    phone: ''
  });
};

const removeAddress = (index) => {
  settings.value.addresses.splice(index, 1);
};

onMounted(() => {
  fetchSettings();
});
</script>
