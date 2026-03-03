<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isEdit ? 'Редактирование страницы' : 'Создание страницы' }}</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ isEdit ? 'Обновите данные страницы' : 'Заполните информацию о странице' }}</p>
      </div>
      <ThemeButton variant="secondary" @click="$router.push('/pages')">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Назад к списку
      </ThemeButton>
    </div>

    <form @submit.prevent="savePage" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-6">
      <!-- Basic Information -->
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Основная информация</h3>

        <div class="space-y-4">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Название <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              @input="onTitleChange"
              type="text"
              required
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              placeholder="Введите название страницы"
            >
          </div>

          <!-- Slug -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Символьный код (slug) <span class="text-red-500">*</span>
            </label>
            <div class="flex gap-2">
              <input
                v-model="form.slug"
                type="text"
                required
                pattern="[a-z0-9_]+"
                class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono"
                placeholder="nazvanie_stranicy"
              >
              <ThemeButton type="button" variant="secondary" @click="generateSlug">
                Сгенерировать
              </ThemeButton>
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Только латинские буквы, цифры и подчеркивание</p>
          </div>

          <!-- Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Тип страницы <span class="text-red-500">*</span>
            </label>
            <div class="flex gap-4">
              <label class="flex items-center cursor-pointer">
                <input
                  v-model="form.type"
                  type="radio"
                  value="static"
                  :disabled="isEdit"
                  class="mr-2"
                >
                <span class="text-gray-900 dark:text-white">Статическая</span>
              </label>
              <label class="flex items-center cursor-pointer">
                <input
                  v-model="form.type"
                  type="radio"
                  value="dynamic"
                  :disabled="isEdit"
                  class="mr-2"
                >
                <span class="text-gray-900 dark:text-white">Динамическая</span>
              </label>
            </div>
            <p v-if="isEdit" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Тип страницы нельзя изменить после создания</p>
          </div>

          <!-- Content (only for static pages) -->
          <div v-if="form.type === 'static'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Содержимое
            </label>
            <textarea
              v-model="form.content"
              rows="10"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono"
              placeholder="HTML содержимое страницы"
            ></textarea>
          </div>

          <!-- Active Status -->
          <div class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="mr-2 rounded"
            >
            <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
              Активна
            </label>
          </div>

          <!-- Sort -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Сортировка
            </label>
            <input
              v-model.number="form.sort"
              type="number"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
          </div>
        </div>
      </div>

      <!-- SEO -->
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">SEO</h3>

        <div class="space-y-4">
          <!-- Meta Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Meta заголовок
            </label>
            <input
              v-model="form.meta_title"
              type="text"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              placeholder="Заголовок для поисковиков"
            >
          </div>

          <!-- Meta Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Meta описание
            </label>
            <textarea
              v-model="form.meta_description"
              rows="3"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              placeholder="Описание для поисковиков"
            ></textarea>
          </div>

          <!-- Meta Keywords -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Meta ключевые слова
            </label>
            <input
              v-model="form.meta_keywords"
              type="text"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
              placeholder="ключевое слово, другое слово"
            >
          </div>
        </div>
      </div>

      <!-- Page Appearance -->
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Внешний вид</h3>

        <div class="space-y-4">
          <!-- Header Template -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Шаблон шапки
            </label>
            <select
              v-model="form.header_template"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
              <option value="">По умолчанию (из настроек)</option>
              <option value="header1">Шаблон 1 - Классический (логотип сверху, меню снизу)</option>
              <option value="header2">Шаблон 2 - Горизонтальный (логотип слева, меню справа)</option>
              <option value="header3">Шаблон 3 - Темный (современный темный стиль)</option>
            </select>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Оставьте пустым для использования шаблона из настроек</p>
          </div>

          <!-- Footer Template -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Шаблон футера
            </label>
            <select
              v-model="form.footer_template"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
              <option value="">По умолчанию (из настроек)</option>
              <option value="footer1">Шаблон 1 - Простой (центрированное меню)</option>
              <option value="footer2">Шаблон 2 - Многоколоночный (логотип, меню, контакты)</option>
              <option value="footer3">Шаблон 3 - Минималистичный (с социальными сетями)</option>
            </select>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Оставьте пустым для использования шаблона из настроек</p>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
        <p class="text-red-800 dark:text-red-400">{{ errorMessage }}</p>
      </div>

      <!-- Actions -->
      <div class="flex gap-3">
        <ThemeButton type="submit" variant="primary" :disabled="saving">
          {{ saving ? 'Сохранение...' : 'Сохранить' }}
        </ThemeButton>

        <ThemeButton
          v-if="isEdit && form.type === 'dynamic'"
          type="button"
          variant="secondary"
          @click="$router.push(`/pages/${pageId}/builder`)"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/>
          </svg>
          Конструктор страницы
        </ThemeButton>

        <ThemeButton type="button" variant="secondary" @click="$router.push('/pages')">
          Отмена
        </ThemeButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import ThemeButton from './ThemeButton.vue';

const router = useRouter();
const route = useRoute();

const pageId = computed(() => route.params.id);
const isEdit = computed(() => !!pageId.value);

const form = ref({
  title: '',
  slug: '',
  type: 'static',
  content: '',
  meta_title: '',
  meta_description: '',
  meta_keywords: '',
  is_active: true,
  sort: 500,
  header_template: '',
  footer_template: '',
});

const saving = ref(false);
const errorMessage = ref('');
const slugManuallyChanged = ref(false);

// Transliteration map for Russian to Latin
const transliterationMap = {
  'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo', 'ж': 'zh',
  'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
  'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts',
  'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya',
  'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Е': 'E', 'Ё': 'Yo', 'Ж': 'Zh',
  'З': 'Z', 'И': 'I', 'Й': 'Y', 'К': 'K', 'Л': 'L', 'М': 'M', 'Н': 'N', 'О': 'O',
  'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U', 'Ф': 'F', 'Х': 'H', 'Ц': 'Ts',
  'Ч': 'Ch', 'Ш': 'Sh', 'Щ': 'Sch', 'Ъ': '', 'Ы': 'Y', 'Ь': '', 'Э': 'E', 'Ю': 'Yu', 'Я': 'Ya'
};

const transliterate = (text) => {
  return text.split('').map(char => transliterationMap[char] || char).join('');
};

const makeSlug = (text) => {
  let slug = transliterate(text);
  slug = slug.toLowerCase();
  slug = slug.replace(/[^a-z0-9]+/g, '_');
  slug = slug.replace(/^_+|_+$/g, '');
  return slug;
};

const onTitleChange = () => {
  if (!slugManuallyChanged.value && !isEdit.value) {
    form.value.slug = makeSlug(form.value.title);
  }
};

const generateSlug = async () => {
  if (!form.value.title) {
    errorMessage.value = 'Сначала введите название';
    return;
  }

  try {
    const response = await fetch('/admin/api/pages/generate-slug', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        title: form.value.title,
        exclude_id: isEdit.value ? pageId.value : null
      })
    });

    if (response.ok) {
      const data = await response.json();
      form.value.slug = data.slug;
      slugManuallyChanged.value = true;
      errorMessage.value = '';
    }
  } catch (error) {
    console.error('Failed to generate slug:', error);
    errorMessage.value = 'Ошибка при генерации slug';
  }
};

const loadPage = async () => {
  try {
    const response = await fetch(`/admin/api/pages/${pageId.value}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      form.value = {
        title: data.title,
        slug: data.slug,
        type: data.type,
        content: data.content || '',
        meta_title: data.meta_title || '',
        meta_description: data.meta_description || '',
        meta_keywords: data.meta_keywords || '',
        is_active: data.is_active,
        sort: data.sort,
      };
      slugManuallyChanged.value = true;
    }
  } catch (error) {
    console.error('Failed to load page:', error);
    errorMessage.value = 'Ошибка при загрузке страницы';
  }
};

const savePage = async () => {
  saving.value = true;
  errorMessage.value = '';

  try {
    const url = isEdit.value ? `/admin/api/pages/${pageId.value}` : '/admin/api/pages';
    const method = isEdit.value ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    if (response.ok) {
      const data = await response.json();

      if (isEdit.value) {
        router.push('/pages');
      } else if (data.type === 'dynamic') {
        // For new dynamic pages, redirect to builder
        router.push(`/pages/${data.id}/builder`);
      } else {
        router.push('/pages');
      }
    } else {
      const error = await response.json();
      errorMessage.value = error.message || 'Ошибка при сохранении страницы';
    }
  } catch (error) {
    console.error('Failed to save page:', error);
    errorMessage.value = 'Ошибка при сохранении страницы';
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (isEdit.value) {
    loadPage();
  }
});
</script>
