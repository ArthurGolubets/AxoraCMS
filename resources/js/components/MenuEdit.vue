<template>
  <div v-if="loading" class="text-center py-12">
    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    <p class="text-gray-600 dark:text-gray-400 mt-4">Загрузка меню...</p>
  </div>

  <div v-else-if="form">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button @click="$router.push(`/menus/${$route.params.id}`)" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Редактирование меню</h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">{{ form.name || 'Новое меню' }}</p>
        </div>
      </div>
      <div class="flex gap-3">
        <ThemeButton variant="secondary" @click="$router.push(`/menus/${$route.params.id}`)">
          Отмена
        </ThemeButton>
        <ThemeButton variant="primary" @click="saveMenu">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          Сохранить
        </ThemeButton>
      </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <form @submit.prevent="saveMenu" class="space-y-6">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Название <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            @input="generateCodeFromName"
            type="text"
            required
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Главное меню"
          />
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Отображаемое название меню</p>
        </div>

        <!-- Code -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Символьный код
          </label>
          <input
            v-model="form.code"
            type="text"
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="main_menu"
          />
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Используется для программного обращения к меню. Оставьте пустым для автоматической генерации.
          </p>
        </div>

        <!-- Location -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Расположение <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label
              class="relative flex items-start p-5 border-2 rounded-lg cursor-pointer transition-all hover:shadow-md"
              :class="form.location === 'header' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-md' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'"
            >
              <input
                v-model="form.location"
                type="radio"
                value="header"
                class="mt-1 mr-4 w-5 h-5 text-blue-600 focus:ring-blue-500"
              />
              <div class="flex-1">
                <div class="flex items-center mb-2">
                  <svg class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z"/>
                  </svg>
                  <span class="font-semibold text-gray-900 dark:text-white">Шапка сайта</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Меню будет отображаться в верхней части сайта</p>
              </div>
            </label>

            <label
              class="relative flex items-start p-5 border-2 rounded-lg cursor-pointer transition-all hover:shadow-md"
              :class="form.location === 'footer' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 shadow-md' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'"
            >
              <input
                v-model="form.location"
                type="radio"
                value="footer"
                class="mt-1 mr-4 w-5 h-5 text-purple-600 focus:ring-purple-500"
              />
              <div class="flex-1">
                <div class="flex items-center mb-2">
                  <svg class="w-6 h-6 mr-2 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z"/>
                  </svg>
                  <span class="font-semibold text-gray-900 dark:text-white">Футер сайта</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Меню будет отображаться в нижней части сайта</p>
              </div>
            </label>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Описание
          </label>
          <textarea
            v-model="form.description"
            rows="4"
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Краткое описание меню и его назначения..."
          ></textarea>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Необязательное поле для внутренних заметок о назначении меню
          </p>
        </div>

        <!-- Active Status -->
        <div class="flex items-start">
          <div class="flex items-center h-5">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="isActive"
              class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            />
          </div>
          <div class="ml-3">
            <label for="isActive" class="font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
              Меню активно
            </label>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Неактивные меню не отображаются на сайте
            </p>
          </div>
        </div>

        <!-- Validation Errors -->
        <div v-if="errors.length > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
              <h4 class="font-medium text-red-800 dark:text-red-300 mb-2">Ошибки валидации</h4>
              <ul class="list-disc list-inside space-y-1 text-sm text-red-700 dark:text-red-400">
                <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div v-else class="text-center py-12">
    <p class="text-red-600 dark:text-red-400">Меню не найдено</p>
    <ThemeButton variant="secondary" @click="$router.push('/menus')" class="mt-4">
      Вернуться к списку
    </ThemeButton>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from './ThemeButton.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const form = ref(null);
const errors = ref([]);
const codeManuallyEdited = ref(false);

const loadMenu = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/admin/api/menus/${route.params.id}`, {
      headers: { 'Accept': 'application/json' },
    });

    if (response.ok) {
      const data = await response.json();
      form.value = {
        name: data.menu.name,
        code: data.menu.code,
        location: data.menu.location,
        description: data.menu.description || '',
        is_active: data.menu.is_active,
      };
      codeManuallyEdited.value = true; // Don't auto-generate for existing menus
    }
  } catch (error) {
    console.error('Failed to load menu:', error);
  } finally {
    loading.value = false;
  }
};

const generateCodeFromName = () => {
  if (!codeManuallyEdited.value && form.value.name) {
    form.value.code = form.value.name
      .toLowerCase()
      .replace(/[^a-zа-яё0-9\s]/g, '')
      .replace(/\s+/g, '_')
      .substring(0, 50);
  }
};

const saveMenu = async () => {
  errors.value = [];

  if (!form.value.name) {
    errors.value.push('Название меню обязательно для заполнения');
    return;
  }

  if (!form.value.location) {
    errors.value.push('Необходимо выбрать расположение меню');
    return;
  }

  try {
    const response = await fetch(`/admin/api/menus/${route.params.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify(form.value),
    });

    if (response.ok) {
      router.push(`/menus/${route.params.id}`);
    } else {
      const data = await response.json();
      if (data.errors) {
        errors.value = Object.values(data.errors).flat();
      } else {
        errors.value = [data.message || 'Ошибка при сохранении меню'];
      }
    }
  } catch (error) {
    console.error('Failed to save menu:', error);
    errors.value = ['Ошибка при сохранении меню'];
  }
};

onMounted(() => {
  loadMenu();
});
</script>
