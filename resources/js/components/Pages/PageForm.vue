<template>
  <div class="p-6">
    <div class="mb-6 flex items-center space-x-3">
      <button @click="$router.push('/pages')" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </button>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ isEdit ? 'Редактировать страницу' : 'Создать страницу' }}
      </h2>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Tabs -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="border-b border-gray-200 dark:border-gray-700">
          <nav class="flex -mb-px">
            <button
              type="button"
              @click="activeTab = 'main'"
              class="px-6 py-3 text-sm font-medium transition-colors"
              :class="activeTab === 'main'
                ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            >
              Основное
            </button>
            <button
              type="button"
              @click="activeTab = 'content'"
              class="px-6 py-3 text-sm font-medium transition-colors"
              :class="activeTab === 'content'
                ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            >
              Контент
            </button>
            <button
              type="button"
              @click="activeTab = 'seo'"
              class="px-6 py-3 text-sm font-medium transition-colors"
              :class="activeTab === 'seo'
                ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            >
              SEO
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
          <!-- Main Tab -->
          <div v-show="activeTab === 'main'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название страницы *</label>
              <input
                v-model="form.title"
                @input="onTitleChange"
                type="text"
                required
                placeholder="Например: О компании"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">URL (slug) *</label>
              <input
                v-model="form.slug"
                type="text"
                required
                placeholder="about-company"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono"
              >
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Генерируется автоматически из названия</p>
            </div>


            <ToggleSwitch v-model="form.is_active" :theme-color="themeColor" label="Активна" />

            <!-- Add to menu (creation only) -->
            <div v-if="!isEdit && menus.length" class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
              <div class="flex items-center justify-between gap-4">
                <div>
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Добавить в меню</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">После создания ссылка на страницу появится в конце выбранного меню</p>
                </div>
                <ToggleSwitch v-model="addToMenu" :theme-color="themeColor" />
              </div>
              <div v-if="addToMenu" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Меню *</label>
                  <select v-model="menuId" @change="loadMenuItems" required class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                    <option :value="null" disabled>— Выберите меню —</option>
                    <option v-for="menu in menus" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Родительский пункт</label>
                  <select v-model="menuParentId" :disabled="!menuId" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white disabled:opacity-50">
                    <option :value="null">Нет (корневой пункт)</option>
                    <option v-for="item in menuItems" :key="item.id" :value="item.id">{{ '— '.repeat(item.level) }}{{ item.title }}</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Content Tab -->
          <div v-show="activeTab === 'content'" class="space-y-4">
            <TinyMCEEditor v-model="form.content" label="Контент страницы" :height="400" />
          </div>

          <!-- SEO Tab -->
          <div v-show="activeTab === 'seo'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Title</label>
              <input
                v-model="form.meta_title"
                type="text"
                placeholder="SEO заголовок"
                maxlength="255"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ form.meta_title?.length || 0 }} / 255 символов</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Description</label>
              <textarea
                v-model="form.meta_description"
                rows="3"
                placeholder="SEO описание"
                maxlength="500"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              ></textarea>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ form.meta_description?.length || 0 }} / 500 символов</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Keywords</label>
              <input
                v-model="form.meta_keywords"
                type="text"
                placeholder="ключевые, слова, через, запятую"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          @click="$router.push('/pages')"
          class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
        >
          Отмена
        </button>
        <ThemeButton type="submit" variant="primary" :disabled="saving">
          {{ saving ? 'Сохранение...' : (isEdit ? 'Сохранить' : 'Создать') }}
        </ThemeButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import TinyMCEEditor from '../TinyMCEEditor.vue';
import ToggleSwitch from '../ToggleSwitch.vue';
import ThemeButton from '../ThemeButton.vue';
import { useTheme } from '../../composables/useTheme';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => !!route.params.id);
const saving = ref(false);
const activeTab = ref('main');
const isSlugManuallyEdited = ref(false);
const { themeColor } = useTheme();

const menus = ref([]);
const menuItems = ref([]);
const addToMenu = ref(false);
const menuId = ref(null);
const menuParentId = ref(null);

const form = ref({
  title: '',
  slug: '',
  type: 'dynamic',
  route_name: '',
  content: '',
  meta_title: '',
  meta_description: '',
  meta_keywords: '',
  is_active: true
});

const slugify = (text) => {
  const translitMap = {
    'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo',
    'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm',
    'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
    'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '',
    'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya'
  };

  return text
    .toLowerCase()
    .split('')
    .map(char => translitMap[char] || char)
    .join('')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
};

const onTitleChange = () => {
  if (!isEdit.value && !isSlugManuallyEdited.value) {
    form.value.slug = slugify(form.value.title);
  }
};

onMounted(() => {
  if (isEdit.value) {
    loadPage();
  } else {
    loadMenus();
  }
});

/**
 * Menus are available only with the menus tables installed — otherwise the
 * "Добавить в меню" block simply stays hidden.
 */
const loadMenus = async () => {
  try {
    const response = await axios.get('/admin/api/menus');
    menus.value = response.data.data || response.data || [];
  } catch (error) {
    menus.value = [];
  }
};

const loadMenuItems = async () => {
  menuParentId.value = null;
  menuItems.value = [];
  if (!menuId.value) return;
  try {
    const response = await axios.get(`/admin/api/menus/${menuId.value}/items`);
    const flat = response.data || [];
    const walk = (parentId, level) => flat
      .filter((item) => (item.parent_id ?? null) === parentId)
      .sort((a, b) => a.sort - b.sort)
      .flatMap((item) => [{ ...item, level }, ...walk(item.id, level + 1)]);
    menuItems.value = walk(null, 0);
  } catch (error) {
    menuItems.value = [];
  }
};

const loadPage = async () => {
  try {
    const response = await axios.get(`/admin/api/pages/${route.params.id}`);
    Object.assign(form.value, response.data);
    isSlugManuallyEdited.value = true;
  } catch (error) {
    console.error('Error loading page:', error);
    alert('Ошибка загрузки страницы');
  }
};

const handleSubmit = async () => {
  saving.value = true;

  try {
    if (isEdit.value) {
      await axios.put(`/admin/api/pages/${route.params.id}`, form.value);
    } else {
      await axios.post('/admin/api/pages', {
        ...form.value,
        menu_id: addToMenu.value ? menuId.value : null,
        menu_parent_id: addToMenu.value ? menuParentId.value : null,
      });
    }

    router.push('/pages');
  } catch (error) {
    console.error('Error saving page:', error);
    alert(error.response?.data?.message || 'Ошибка сохранения страницы');
  } finally {
    saving.value = false;
  }
};
</script>
