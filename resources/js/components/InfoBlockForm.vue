<template>
  <div>
    <div class="mb-6 flex items-center space-x-3">
      <button @click="$router.back()" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </button>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ isEdit ? 'Редактировать инфоблок' : 'Создать инфоблок' }}
      </h2>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Main Info -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Основная информация</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Название *</label>
            <input
              v-model="form.name"
              @input="generateCode"
              type="text"
              required
              placeholder="Например: Баннеры"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Тип инфоблока *</label>
            <select
              v-model="form.type"
              required
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            >
              <option value="list">Список</option>
              <option value="catalog">Каталог</option>
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Список - простой список элементов, Каталог - древовидная структура с разделами
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Символьный код *</label>
            <input
              v-model="form.code"
              type="text"
              required
              placeholder="banners"
              pattern="[a-z0-9_]+"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-mono"
            >
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Только латинские буквы, цифры и нижнее подчеркивание</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Описание</label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Описание инфоблока"
              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            ></textarea>
          </div>

          <div>
            <label class="flex items-center space-x-2 cursor-pointer">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600"
              >
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Активен</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          @click="$router.back()"
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
import ThemeButton from './ThemeButton.vue';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => !!route.params.id);
const saving = ref(false);

const form = ref({
  name: '',
  type: 'list',
  code: '',
  description: '',
  is_active: true
});

const generateCode = () => {
  if (!isEdit.value && form.value.name) {
    // Транслитерация и создание кода
    const translitMap = {
      'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e', 'ж': 'zh',
      'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
      'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts',
      'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu',
      'я': 'ya', ' ': '_'
    };

    let code = form.value.name.toLowerCase();
    for (const [rus, eng] of Object.entries(translitMap)) {
      code = code.replace(new RegExp(rus, 'g'), eng);
    }
    code = code.replace(/[^a-z0-9_]/g, '');
    form.value.code = code;
  }
};

const loadInfoBlock = async () => {
  try {
    const response = await fetch(`/admin/api/infoblocks/${route.params.id}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      form.value = {
        name: data.name || '',
        type: data.type || 'list',
        code: data.code || '',
        description: data.description || '',
        is_active: data.is_active || false
      };
    }
  } catch (error) {
    console.error('Failed to load info block:', error);
  }
};

const handleSubmit = async () => {
  saving.value = true;
  try {
    const url = isEdit.value
      ? `/admin/api/infoblocks/${route.params.id}`
      : '/admin/api/infoblocks';

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
      router.push('/infoblocks');
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при сохранении');
    }
  } catch (error) {
    console.error('Failed to save info block:', error);
    alert('Ошибка при сохранении');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (isEdit.value) {
    loadInfoBlock();
  }
});
</script>
