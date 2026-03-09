<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Типы блоков</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Управление типами блоков для конструктора страниц</p>
      </div>
      <ThemeButton variant="primary" @click="openCreateModal">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Создать тип блока
      </ThemeButton>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex gap-4">
      <input
        v-model="searchQuery"
        @input="loadBlockTypes"
        type="text"
        placeholder="Поиск по названию или коду..."
        class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
      >
      <select
        v-model="categoryFilter"
        @change="loadBlockTypes"
        class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
      >
        <option value="">Все категории</option>
        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
      </select>
    </div>

    <!-- Block Types List -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>
      <div v-else-if="blockTypes.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
        Типы блоков не найдены. Создайте первый тип блока.
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Код</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Категория</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Тип</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Статус</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="blockType in blockTypes" :key="blockType.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4">
              <div class="font-medium text-gray-900 dark:text-white">{{ blockType.name }}</div>
              <div class="text-sm text-gray-500 dark:text-gray-400">{{ blockType.description }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ blockType.code }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ blockType.category || '—' }}</td>
            <td class="px-6 py-4 text-sm">
              <span v-if="blockType.is_system" class="px-2 py-1 text-xs bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 rounded">Системный</span>
              <span v-else class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded">Пользовательский</span>
            </td>
            <td class="px-6 py-4 text-sm">
              <span v-if="blockType.is_active" class="px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">Активен</span>
              <span v-else class="px-2 py-1 text-xs bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 rounded">Неактивен</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button
                v-if="!blockType.is_system"
                @click="openEditModal(blockType)"
                class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300"
                title="Редактировать"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
              </button>
              <button
                v-if="!blockType.is_system"
                @click="confirmDelete(blockType)"
                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                title="Удалить"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
              <span v-if="blockType.is_system" class="text-gray-400 text-sm">Защищён</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            {{ editingBlockType ? 'Редактирование типа блока' : 'Создание типа блока' }}
          </h3>

          <form @submit.prevent="saveBlockType" class="space-y-4">
            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
              <nav class="flex -mb-px space-x-4">
                <button
                  type="button"
                  @click="activeTab = 'basic'"
                  :class="[
                    'py-2 px-4 text-sm font-medium border-b-2 transition-colors',
                    activeTab === 'basic'
                      ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                      : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                  ]"
                >
                  Основная информация
                </button>
                <button
                  type="button"
                  @click="activeTab = 'fields'"
                  :class="[
                    'py-2 px-4 text-sm font-medium border-b-2 transition-colors',
                    activeTab === 'fields'
                      ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                      : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                  ]"
                >
                  Схема полей
                </button>
                <button
                  type="button"
                  @click="activeTab = 'template'"
                  :class="[
                    'py-2 px-4 text-sm font-medium border-b-2 transition-colors',
                    activeTab === 'template'
                      ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                      : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                  ]"
                >
                  Шаблон
                </button>
              </nav>
            </div>

            <!-- Tab Content -->
            <div class="py-4">
              <!-- Basic Info Tab -->
              <div v-show="activeTab === 'basic'" class="space-y-4">
                <!-- Code -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Код <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.code"
                    type="text"
                    required
                    pattern="[a-z0-9_]+"
                    :disabled="editingBlockType"
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                    placeholder="hero_section"
                  >
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Только латинские буквы, цифры и подчеркивание</p>
                </div>

                <!-- Name -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Название <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                  >
                </div>

                <!-- Description -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Описание
                  </label>
                  <textarea
                    v-model="form.description"
                    rows="2"
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                  ></textarea>
                </div>

                <!-- Category -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Категория
                  </label>
                  <input
                    v-model="form.category"
                    type="text"
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                    placeholder="hero, content, etc."
                  >
                </div>

                <!-- Icon -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Иконка
                  </label>
                  <input
                    v-model="form.icon"
                    type="text"
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                    placeholder="heroicons:rectangle-stack"
                  >
                </div>

                <!-- Active Status -->
                <div class="flex items-center">
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    id="is_active_modal"
                    class="mr-2 rounded"
                  >
                  <label for="is_active_modal" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                    Активен
                  </label>
                </div>
              </div>

              <!-- Fields Schema Tab -->
              <div v-show="activeTab === 'fields'" class="space-y-4">
                <div class="flex justify-between items-center mb-4">
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Определите поля для этого типа блока
                  </p>
                  <button
                    type="button"
                    @click="addField"
                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg"
                  >
                    + Добавить поле
                  </button>
                </div>

                <div v-if="form.fields_schema.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                  Нет полей. Добавьте первое поле.
                </div>

                <div v-else class="space-y-4">
                  <div
                    v-for="(field, index) in form.fields_schema"
                    :key="index"
                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-3"
                  >
                    <div class="flex justify-between items-start">
                      <h4 class="text-sm font-medium text-gray-900 dark:text-white">Поле #{{ index + 1 }}</h4>
                      <button
                        type="button"
                        @click="removeField(index)"
                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                        title="Удалить поле"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Имя поля <span class="text-red-500">*</span>
                        </label>
                        <input
                          v-model="field.name"
                          type="text"
                          required
                          pattern="[a-z0-9_]+"
                          class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
                          placeholder="title"
                        >
                      </div>

                      <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Тип <span class="text-red-500">*</span>
                        </label>
                        <select
                          v-model="field.type"
                          required
                          class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
                        >
                          <option value="string">Строка (string)</option>
                          <option value="text">Текст (text)</option>
                          <option value="textarea">Большой текст (textarea)</option>
                          <option value="number">Число (number)</option>
                          <option value="boolean">Да/Нет (boolean)</option>
                          <option value="select">Выбор (select)</option>
                          <option value="image">Изображение (image)</option>
                          <option value="file">Файл (file)</option>
                          <option value="date">Дата (date)</option>
                          <option value="datetime">Дата и время (datetime)</option>
                          <option value="color">Цвет (color)</option>
                          <option value="url">URL (url)</option>
                          <option value="email">Email (email)</option>
                          <option value="catalog_relation">Привязка к каталогу</option>
                          <option value="product_relation">Привязка к товару</option>
                          <option value="page_relation">Привязка к странице</option>
                          <option value="catalog_select">Выбор каталогов</option>
                          <option value="infoblocks_select">Выбор инфоблоков</option>
                          <option value="products_select">Выбор товаров</option>
                          <option value="repeater">Повторитель</option>
                        </select>
                      </div>
                    </div>

                    <div>
                      <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Метка (label) <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="field.label"
                        type="text"
                        required
                        class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
                        placeholder="Заголовок"
                      >
                    </div>

                    <div>
                      <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Значение по умолчанию
                      </label>
                      <input
                        v-model="field.default"
                        type="text"
                        class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
                      >
                    </div>

                    <div v-if="field.type === 'select'">
                      <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Варианты выбора (JSON массив)
                      </label>
                      <textarea
                        v-model="field.options"
                        rows="2"
                        class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white font-mono"
                        placeholder='["Вариант 1", "Вариант 2"]'
                      ></textarea>
                    </div>

                    <!-- Multiple select option for catalog/infoblock/product selectors -->
                    <div v-if="['catalog_select', 'infoblocks_select', 'products_select'].includes(field.type)" class="space-y-2">
                      <div class="flex items-center">
                        <input
                          v-model="field.multiple"
                          type="checkbox"
                          :id="'field_multiple_' + index"
                          class="mr-2 rounded"
                        >
                        <label :for="'field_multiple_' + index" class="text-xs font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                          Множественный выбор
                        </label>
                      </div>

                      <div v-if="!field.multiple">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Максимум элементов (пусто = без ограничений)
                        </label>
                        <input
                          v-model.number="field.max_items"
                          type="number"
                          min="1"
                          placeholder="Без ограничений"
                          class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white"
                        >
                      </div>

                      <!-- Product filters for products_select -->
                      <div v-if="field.type === 'products_select'" class="space-y-2 border-t border-gray-200 dark:border-gray-600 pt-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Автоматические фильтры товаров
                        </label>
                        <div class="flex flex-wrap gap-2">
                          <label class="flex items-center">
                            <input
                              v-model="field.filter_new"
                              type="checkbox"
                              :id="'field_filter_new_' + index"
                              class="mr-1 rounded"
                            >
                            <span class="text-xs text-gray-700 dark:text-gray-300">Только новинки</span>
                          </label>
                          <label class="flex items-center">
                            <input
                              v-model="field.filter_recommended"
                              type="checkbox"
                              :id="'field_filter_recommended_' + index"
                              class="mr-1 rounded"
                            >
                            <span class="text-xs text-gray-700 dark:text-gray-300">Только рекомендованные</span>
                          </label>
                          <label class="flex items-center">
                            <input
                              v-model="field.filter_hot"
                              type="checkbox"
                              :id="'field_filter_hot_' + index"
                              class="mr-1 rounded"
                            >
                            <span class="text-xs text-gray-700 dark:text-gray-300">Только хиты</span>
                          </label>
                        </div>
                      </div>
                    </div>

                    <div class="flex items-center">
                      <input
                        v-model="field.required"
                        type="checkbox"
                        :id="'field_required_' + index"
                        class="mr-2 rounded"
                      >
                      <label :for="'field_required_' + index" class="text-xs font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                        Обязательное поле
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Template Tab -->
              <div v-show="activeTab === 'template'" class="space-y-4">
                <div v-if="!editingBlockType" class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <input
                    v-model="form.autoCreateTemplate"
                    type="checkbox"
                    id="auto_create_template"
                    class="mr-3 rounded"
                  >
                  <label for="auto_create_template" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                    Автоматически создать Blade шаблон
                  </label>
                </div>

                <div class="p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <strong>Путь к шаблону:</strong>
                  </p>
                  <p class="text-sm font-mono text-gray-900 dark:text-white">
                    resources/views/components/blocks/{{ form.code || 'block_code' }}.blade.php
                  </p>
                </div>

                <div v-if="form.autoCreateTemplate" class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                  <p class="text-sm text-yellow-800 dark:text-yellow-400">
                    При сохранении будет создан базовый Bootstrap шаблон на основе схемы полей
                  </p>
                </div>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
              <p class="text-red-800 dark:text-red-400">{{ errorMessage }}</p>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
              <ThemeButton type="submit" variant="primary" :disabled="saving">
                {{ saving ? 'Сохранение...' : 'Сохранить' }}
              </ThemeButton>
              <ThemeButton type="button" variant="secondary" @click="closeModal">
                Отмена
              </ThemeButton>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="showDeleteModal = false"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          Удаление типа блока
        </h3>

        <p class="text-gray-700 dark:text-gray-300 mb-4">
          Вы уверены, что хотите удалить тип блока "{{ deletingBlockType?.name }}"?
        </p>

        <div class="flex items-center mb-6 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
          <input
            v-model="deleteTemplate"
            type="checkbox"
            id="delete_template"
            class="mr-3 rounded"
          >
          <label for="delete_template" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
            Удалить также и Blade шаблон
          </label>
        </div>

        <div class="flex gap-3">
          <ThemeButton
            type="button"
            variant="danger"
            @click="confirmDeleteBlockType"
            :disabled="deleting"
          >
            {{ deleting ? 'Удаление...' : 'Удалить' }}
          </ThemeButton>
          <ThemeButton
            type="button"
            variant="secondary"
            @click="showDeleteModal = false"
          >
            Отмена
          </ThemeButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ThemeButton from './ThemeButton.vue';
import { useModal } from '../composables/useModal';

const { confirm } = useModal();

const blockTypes = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const categoryFilter = ref('');
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingBlockType = ref(null);
const deletingBlockType = ref(null);
const saving = ref(false);
const deleting = ref(false);
const errorMessage = ref('');
const activeTab = ref('basic');
const deleteTemplate = ref(false);

const form = ref({
  code: '',
  name: '',
  description: '',
  icon: '',
  category: '',
  is_active: true,
  fields_schema: [],
  autoCreateTemplate: true
});

const categories = computed(() => {
  const cats = new Set();
  blockTypes.value.forEach(bt => {
    if (bt.category) cats.add(bt.category);
  });
  return Array.from(cats);
});

const loadBlockTypes = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    if (categoryFilter.value) params.append('category', categoryFilter.value);

    const response = await fetch(`/admin/api/page-block-types?${params}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      blockTypes.value = data.data || data;
    }
  } catch (error) {
    console.error('Failed to load block types:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  editingBlockType.value = null;
  activeTab.value = 'basic';
  form.value = {
    code: '',
    name: '',
    description: '',
    icon: '',
    category: '',
    is_active: true,
    fields_schema: [],
    autoCreateTemplate: true
  };
  errorMessage.value = '';
  showModal.value = true;
};

const openEditModal = (blockType) => {
  editingBlockType.value = blockType;
  activeTab.value = 'basic';
  form.value = {
    code: blockType.code,
    name: blockType.name,
    description: blockType.description || '',
    icon: blockType.icon || '',
    category: blockType.category || '',
    is_active: blockType.is_active,
    fields_schema: JSON.parse(JSON.stringify(blockType.fields_schema || [])),
    autoCreateTemplate: false
  };
  errorMessage.value = '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingBlockType.value = null;
  errorMessage.value = '';
};

const addField = () => {
  form.value.fields_schema.push({
    name: '',
    type: 'string',
    label: '',
    default: '',
    required: false
  });
};

const removeField = (index) => {
  form.value.fields_schema.splice(index, 1);
};

const saveBlockType = async () => {
  saving.value = true;
  errorMessage.value = '';

  try {
    const payload = {
      code: form.value.code,
      name: form.value.name,
      description: form.value.description,
      icon: form.value.icon,
      category: form.value.category,
      is_active: form.value.is_active,
      fields_schema: form.value.fields_schema
    };

    const url = editingBlockType.value
      ? `/admin/api/page-block-types/${editingBlockType.value.id}`
      : '/admin/api/page-block-types';
    const method = editingBlockType.value ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    if (response.ok) {
      const blockType = await response.json();

      // If auto-create template is enabled, create the template
      if (!editingBlockType.value && form.value.autoCreateTemplate) {
        await createTemplate(blockType.code || blockType.data?.code, form.value.fields_schema);
      }

      closeModal();
      loadBlockTypes();
    } else {
      const error = await response.json();
      errorMessage.value = error.message || 'Ошибка при сохранении типа блока';
    }
  } catch (error) {
    console.error('Failed to save block type:', error);
    errorMessage.value = 'Ошибка при сохранении типа блока';
  } finally {
    saving.value = false;
  }
};

const createTemplate = async (code, fieldsSchema) => {
  try {
    const response = await fetch('/admin/api/page-block-types/create-template', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify({ code, fields_schema: fieldsSchema })
    });

    if (!response.ok) {
      console.error('Failed to create template');
    }
  } catch (error) {
    console.error('Failed to create template:', error);
  }
};

const confirmDelete = (blockType) => {
  deletingBlockType.value = blockType;
  deleteTemplate.value = false;
  showDeleteModal.value = true;
};

const confirmDeleteBlockType = async () => {
  if (!deletingBlockType.value) return;

  deleting.value = true;
  try {
    const response = await fetch(`/admin/api/page-block-types/${deletingBlockType.value.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      // Delete template if requested
      if (deleteTemplate.value) {
        await deleteTemplateFile(deletingBlockType.value.id);
      }

      showDeleteModal.value = false;
      deletingBlockType.value = null;
      loadBlockTypes();
    } else {
      const error = await response.json();
      alert(error.message || 'Ошибка при удалении типа блока');
    }
  } catch (error) {
    console.error('Failed to delete block type:', error);
    alert('Ошибка при удалении типа блока');
  } finally {
    deleting.value = false;
  }
};

const deleteTemplateFile = async (blockTypeId) => {
  try {
    const response = await fetch(`/admin/api/page-block-types/${blockTypeId}/template`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      console.error('Failed to delete template file');
    }
  } catch (error) {
    console.error('Failed to delete template file:', error);
  }
};

onMounted(() => {
  loadBlockTypes();
});
</script>
