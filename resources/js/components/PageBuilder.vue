<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Конструктор страницы: {{ page?.title }}</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Перетаскивайте блоки для построения страницы</p>
      </div>
      <div class="flex gap-2">
        <ThemeButton variant="secondary" @click="previewPage">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
          Предпросмотр
        </ThemeButton>
        <ThemeButton
          :variant="page?.is_active ? 'secondary' : 'success'"
          @click="togglePublish"
        >
          <svg v-if="page?.is_active" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
          </svg>
          <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ page?.is_active ? 'Снять с публикации' : 'Опубликовать' }}
        </ThemeButton>
        <ThemeButton variant="secondary" @click="$router.push(`/pages/${pageId}/edit`)">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
          </svg>
          Редактировать
        </ThemeButton>
        <ThemeButton variant="secondary" @click="$router.push('/pages')">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          К списку
        </ThemeButton>
        <ThemeButton variant="primary" @click="showBlockSelectorModal()">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Добавить блок
        </ThemeButton>
      </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
      <!-- Left Panel: Quick Add Blocks -->
      <div class="col-span-3">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 sticky top-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Быстрое добавление</h3>

          <!-- Filter by Category -->
          <select
            v-model="categoryFilter"
            class="w-full mb-4 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm"
          >
            <option value="">Все категории</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>

          <div v-if="loadingBlockTypes" class="text-center text-gray-500 dark:text-gray-400 py-4">
            Загрузка...
          </div>
          <div v-else class="space-y-2 max-h-[calc(100vh-250px)] overflow-y-auto">
            <div
              v-for="blockType in filteredBlockTypes"
              :key="blockType.id"
              @click="addBlock(blockType)"
              class="p-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
            >
              <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-gray-900 dark:text-white text-sm">{{ blockType.name }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ blockType.description }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Panel: Page Builder Canvas -->
      <div class="col-span-9">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <div v-if="loadingBlocks" class="text-center text-gray-500 dark:text-gray-400 py-8">
            Загрузка блоков...
          </div>
          <div v-else-if="rootBlocks.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-lg mb-2">Страница пустая</p>
            <p class="text-sm">Добавьте блоки из левой панели или нажмите "Добавить блок"</p>
          </div>
          <div v-else class="space-y-4">
            <TransitionGroup name="list">
              <div
                v-for="(block, index) in rootBlocks"
                :key="block.id"
                :class="[
                  'group relative border border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-colors',
                  block.block_type?.is_container ? 'bg-purple-50 dark:bg-purple-900/20 hover:border-purple-500 dark:hover:border-purple-500' : 'bg-gray-50 dark:bg-gray-700/50 hover:border-blue-500 dark:hover:border-blue-500'
                ]"
              >
                <!-- Block Header -->
                <div class="flex items-start justify-between mb-3">
                  <div class="flex items-center gap-3 flex-1">
                    <div class="flex flex-col gap-1">
                      <button
                        @click="moveBlockUp(index)"
                        :disabled="index === 0"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                        </svg>
                      </button>
                      <button
                        @click="moveBlockDown(index)"
                        :disabled="index === rootBlocks.length - 1"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                      </button>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        {{ block.block_type?.name }}
                        <span v-if="block.block_type?.is_container" class="text-xs bg-purple-200 dark:bg-purple-700 text-purple-800 dark:text-purple-200 px-2 py-0.5 rounded">
                          Контейнер
                        </span>
                      </h4>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ block.block_type?.description }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      @click="toggleBlockEdit(block)"
                      class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                      :title="block.isEditing ? 'Свернуть' : 'Настроить'"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                    </button>
                    <button
                      @click="deleteBlock(block, index)"
                      class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                      title="Удалить"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Block Settings Form -->
                <div v-if="block.isEditing" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                  <div class="space-y-3">
                    <div v-for="field in block.block_type?.fields_schema" :key="field.name" class="space-y-1">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ field.label }}
                      </label>

                      <!-- String/Number/Email/URL fields -->
                      <input
                        v-if="['string', 'number', 'email', 'url'].includes(field.type)"
                        v-model="block.settings[field.name]"
                        :type="field.type === 'number' ? 'number' : field.type === 'email' ? 'email' : field.type === 'url' ? 'url' : 'text'"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                      >

                      <!-- Textarea -->
                      <textarea
                        v-else-if="field.type === 'textarea' || field.type === 'text'"
                        v-model="block.settings[field.name]"
                        rows="3"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                      ></textarea>

                      <!-- Boolean/Checkbox -->
                      <label v-else-if="field.type === 'boolean' || field.type === 'checkbox'" class="flex items-center">
                        <input
                          v-model="block.settings[field.name]"
                          type="checkbox"
                          class="mr-2 rounded"
                        >
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ field.label }}</span>
                      </label>

                      <!-- Color -->
                      <input
                        v-else-if="field.type === 'color'"
                        v-model="block.settings[field.name]"
                        type="color"
                        class="w-full h-10 px-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"
                      >

                      <!-- Image URL -->
                      <input
                        v-else-if="field.type === 'image'"
                        v-model="block.settings[field.name]"
                        type="text"
                        placeholder="URL изображения"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                      >

                      <!-- Select -->
                      <select
                        v-else-if="field.type === 'select' && field.options"
                        v-model="block.settings[field.name]"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                      >
                        <option v-for="opt in field.options" :key="opt.value" :value="opt.value">
                          {{ opt.label }}
                        </option>
                      </select>

                      <!-- Catalog Relation -->
                      <select
                        v-else-if="field.type === 'catalog_relation'"
                        v-model="block.settings[field.name]"
                        @focus="loadCatalogs"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                      >
                        <option value="">Не выбрано</option>
                        <option v-for="catalog in catalogs" :key="catalog.id" :value="catalog.id">
                          {{ catalog.name }}
                        </option>
                      </select>

                      <!-- Product Relation -->
                      <select
                        v-else-if="field.type === 'product_relation'"
                        v-model="block.settings[field.name]"
                        @focus="loadProducts"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                      >
                        <option value="">Не выбрано</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                          {{ product.name }}
                        </option>
                      </select>

                      <!-- Page Relation -->
                      <select
                        v-else-if="field.type === 'page_relation'"
                        v-model="block.settings[field.name]"
                        @focus="loadPagesForRelation"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                      >
                        <option value="">Не выбрано</option>
                        <option v-for="pg in pagesForRelation" :key="pg.id" :value="pg.id">
                          {{ pg.title }}
                        </option>
                      </select>

                      <!-- Catalog Select -->
                      <div v-else-if="field.type === 'catalog_select'" class="space-y-2">
                        <div class="flex gap-2">
                          <input
                            v-model="catalogSearch"
                            type="text"
                            placeholder="Поиск каталогов..."
                            @input="searchCatalogs"
                            class="flex-1 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                          >
                        </div>
                        <div v-if="field.multiple !== false" class="max-h-40 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-2 space-y-1">
                          <label
                            v-for="catalog in availableCatalogs"
                            :key="catalog.id"
                            class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer"
                            :class="{ 'opacity-50 cursor-not-allowed': !field.multiple && field.max_items && Array.isArray(block.settings[field.name]) && block.settings[field.name].length >= field.max_items && !block.settings[field.name].includes(catalog.id) }"
                          >
                            <input
                              type="checkbox"
                              :value="catalog.id"
                              v-model="block.settings[field.name]"
                              :disabled="!field.multiple && field.max_items && Array.isArray(block.settings[field.name]) && block.settings[field.name].length >= field.max_items && !block.settings[field.name].includes(catalog.id)"
                              class="mr-2 rounded"
                            >
                            <span class="text-sm text-gray-900 dark:text-white">{{ catalog.name }}</span>
                          </label>
                        </div>
                        <select
                          v-else
                          v-model="block.settings[field.name]"
                          class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                        >
                          <option value="">Не выбрано</option>
                          <option v-for="catalog in availableCatalogs" :key="catalog.id" :value="catalog.id">
                            {{ catalog.name }}
                          </option>
                        </select>
                        <p v-if="!field.multiple && field.max_items && Array.isArray(block.settings[field.name])" class="text-xs text-gray-500 dark:text-gray-400">
                          Выбрано: {{ block.settings[field.name].length }} / {{ field.max_items }}
                        </p>
                      </div>

                      <!-- InfoBlocks Select -->
                      <div v-else-if="field.type === 'infoblocks_select'" class="space-y-2">
                        <div class="flex gap-2">
                          <input
                            v-model="infoblockSearch"
                            type="text"
                            placeholder="Поиск инфоблоков..."
                            @input="searchInfoBlocks"
                            class="flex-1 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                          >
                        </div>
                        <div v-if="field.multiple !== false" class="max-h-40 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-2 space-y-1">
                          <label
                            v-for="infoblock in availableInfoBlocks"
                            :key="infoblock.id"
                            class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer"
                            :class="{ 'opacity-50 cursor-not-allowed': !field.multiple && field.max_items && Array.isArray(block.settings[field.name]) && block.settings[field.name].length >= field.max_items && !block.settings[field.name].includes(infoblock.id) }"
                          >
                            <input
                              type="checkbox"
                              :value="infoblock.id"
                              v-model="block.settings[field.name]"
                              :disabled="!field.multiple && field.max_items && Array.isArray(block.settings[field.name]) && block.settings[field.name].length >= field.max_items && !block.settings[field.name].includes(infoblock.id)"
                              class="mr-2 rounded"
                            >
                            <span class="text-sm text-gray-900 dark:text-white">{{ infoblock.name }}</span>
                          </label>
                        </div>
                        <select
                          v-else
                          v-model="block.settings[field.name]"
                          class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                        >
                          <option value="">Не выбрано</option>
                          <option v-for="infoblock in availableInfoBlocks" :key="infoblock.id" :value="infoblock.id">
                            {{ infoblock.name }}
                          </option>
                        </select>
                        <p v-if="!field.multiple && field.max_items && Array.isArray(block.settings[field.name])" class="text-xs text-gray-500 dark:text-gray-400">
                          Выбрано: {{ block.settings[field.name].length }} / {{ field.max_items }}
                        </p>
                      </div>

                      <!-- Products Select -->
                      <div v-else-if="field.type === 'products_select'" class="space-y-2">
                        <div class="flex gap-2 flex-wrap">
                          <select
                            v-model="productFilters.catalog_id"
                            @change="searchProducts"
                            class="flex-1 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                          >
                            <option value="">Все каталоги</option>
                            <option v-for="catalog in availableCatalogs" :key="catalog.id" :value="catalog.id">
                              {{ catalog.name }}
                            </option>
                          </select>
                        </div>
                        <div v-if="!field.filter_new && !field.filter_recommended && !field.filter_hot" class="flex gap-2 flex-wrap text-xs">
                          <label class="flex items-center">
                            <input
                              type="checkbox"
                              v-model="productFilters.is_new"
                              @change="searchProducts(field)"
                              class="mr-1 rounded"
                            >
                            <span class="text-gray-700 dark:text-gray-300">Новинка</span>
                          </label>
                          <label class="flex items-center">
                            <input
                              type="checkbox"
                              v-model="productFilters.is_recommended"
                              @change="searchProducts(field)"
                              class="mr-1 rounded"
                            >
                            <span class="text-gray-700 dark:text-gray-300">Рекомендованный</span>
                          </label>
                          <label class="flex items-center">
                            <input
                              type="checkbox"
                              v-model="productFilters.is_hot"
                              @change="searchProducts(field)"
                              class="mr-1 rounded"
                            >
                            <span class="text-gray-700 dark:text-gray-300">Хит</span>
                          </label>
                        </div>
                        <div v-else class="text-xs text-gray-600 dark:text-gray-400 italic">
                          Активные фильтры:
                          <span v-if="field.filter_new">Новинки</span>
                          <span v-if="field.filter_recommended">{{ field.filter_new ? ', ' : '' }}Рекомендованные</span>
                          <span v-if="field.filter_hot">{{ field.filter_new || field.filter_recommended ? ', ' : '' }}Хиты</span>
                        </div>
                        <input
                          v-model="productSearch"
                          type="text"
                          placeholder="Поиск товаров..."
                          @input="searchProducts"
                          class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                        >
                        <div v-if="field.multiple !== false" class="max-h-40 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-2 space-y-1">
                          <label
                            v-for="product in availableProducts"
                            :key="product.id"
                            class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer"
                            :class="{ 'opacity-50 cursor-not-allowed': !field.multiple && field.max_items && Array.isArray(block.settings[field.name]) && block.settings[field.name].length >= field.max_items && !block.settings[field.name].includes(product.id) }"
                          >
                            <input
                              type="checkbox"
                              :value="product.id"
                              v-model="block.settings[field.name]"
                              :disabled="!field.multiple && field.max_items && Array.isArray(block.settings[field.name]) && block.settings[field.name].length >= field.max_items && !block.settings[field.name].includes(product.id)"
                              class="mr-2 rounded"
                            >
                            <span class="text-sm text-gray-900 dark:text-white">{{ product.name }}</span>
                          </label>
                        </div>
                        <select
                          v-else
                          v-model="block.settings[field.name]"
                          class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-sm"
                        >
                          <option value="">Не выбрано</option>
                          <option v-for="product in availableProducts" :key="product.id" :value="product.id">
                            {{ product.name }}
                          </option>
                        </select>
                        <p v-if="!field.multiple && field.max_items && Array.isArray(block.settings[field.name])" class="text-xs text-gray-500 dark:text-gray-400">
                          Выбрано: {{ block.settings[field.name].length }} / {{ field.max_items }}
                        </p>
                      </div>

                      <!-- Repeater -->
                      <div v-else-if="field.type === 'repeater'" class="space-y-2">
                        <div class="flex justify-between items-center">
                          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Элементы</span>
                          <button
                            type="button"
                            @click="addRepeaterItem(block.settings, field.name)"
                            class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded"
                          >
                            + Добавить
                          </button>
                        </div>
                        <div class="space-y-3">
                          <div
                            v-for="(item, itemIndex) in block.settings[field.name]"
                            :key="itemIndex"
                            class="border border-gray-300 dark:border-gray-600 rounded p-3 space-y-2"
                          >
                            <div class="flex justify-between items-center">
                              <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Элемент #{{ itemIndex + 1 }}</span>
                              <button
                                type="button"
                                @click="removeRepeaterItem(block.settings, field.name, itemIndex)"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                              >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                              </button>
                            </div>
                            <textarea
                              v-model="block.settings[field.name][itemIndex]"
                              rows="2"
                              placeholder="Введите данные..."
                              class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                            ></textarea>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="flex gap-2 mt-4">
                    <ThemeButton @click="saveBlock(block)" variant="primary" size="sm">
                      Сохранить
                    </ThemeButton>
                    <ThemeButton @click="block.isEditing = false" variant="secondary" size="sm">
                      Отмена
                    </ThemeButton>
                  </div>
                </div>

                <!-- Container Block with Columns -->
                <div v-else-if="block.block_type?.is_container" class="mt-4">
                  <div :class="getContainerGridClass(block.block_type.identifier)" class="gap-4">
                    <div
                      v-for="column in getContainerColumns(block.block_type.identifier)"
                      :key="column"
                      class="border-2 border-dashed border-purple-300 dark:border-purple-600 rounded-lg p-3 min-h-[120px]"
                    >
                      <div class="text-xs font-semibold text-purple-700 dark:text-purple-300 mb-3 flex items-center justify-between">
                        <span>{{ getColumnLabel(column) }}</span>
                        <button
                          @click="showBlockSelectorModal(block.id, column)"
                          class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 bg-purple-100 dark:bg-purple-800 rounded p-1"
                          title="Добавить блок в эту колонку"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                          </svg>
                        </button>
                      </div>

                      <!-- Child Blocks in this Column -->
                      <div class="space-y-2">
                        <div
                          v-for="(childBlock, childIndex) in getChildBlocksForColumn(block.id, column)"
                          :key="childBlock.id"
                          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded p-3 text-sm"
                        >
                          <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2 flex-1">
                              <div class="flex flex-col gap-0.5">
                                <button
                                  @click="moveChildBlockUp(block.id, column, childIndex)"
                                  :disabled="childIndex === 0"
                                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                  </svg>
                                </button>
                                <button
                                  @click="moveChildBlockDown(block.id, column, childIndex)"
                                  :disabled="childIndex === getChildBlocksForColumn(block.id, column).length - 1"
                                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                  </svg>
                                </button>
                              </div>
                              <div class="flex-1">
                                <div class="font-medium text-gray-900 dark:text-white text-xs">{{ childBlock.block_type?.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ childBlock.block_type?.description }}</div>
                              </div>
                            </div>
                            <div class="flex items-center gap-1">
                              <button
                                @click="toggleBlockEdit(childBlock)"
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                title="Настроить"
                              >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                              </button>
                              <button
                                @click="deleteChildBlock(childBlock, block.id, column, childIndex)"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                title="Удалить"
                              >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                              </button>
                            </div>
                          </div>

                          <!-- Child Block Settings Form -->
                          <div v-if="childBlock.isEditing" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="space-y-2">
                              <div v-for="field in childBlock.block_type?.fields_schema" :key="field.name" class="space-y-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                  {{ field.label }}
                                </label>

                                <!-- String/Number/Email/URL fields -->
                                <input
                                  v-if="['string', 'number', 'email', 'url'].includes(field.type)"
                                  v-model="childBlock.settings[field.name]"
                                  :type="field.type === 'number' ? 'number' : field.type === 'email' ? 'email' : field.type === 'url' ? 'url' : 'text'"
                                  class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                >

                                <!-- Textarea -->
                                <textarea
                                  v-else-if="field.type === 'textarea' || field.type === 'text'"
                                  v-model="childBlock.settings[field.name]"
                                  rows="2"
                                  class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                ></textarea>

                                <!-- Boolean/Checkbox -->
                                <label v-else-if="field.type === 'boolean' || field.type === 'checkbox'" class="flex items-center">
                                  <input
                                    v-model="childBlock.settings[field.name]"
                                    type="checkbox"
                                    class="mr-2 rounded"
                                  >
                                  <span class="text-xs text-gray-600 dark:text-gray-400">{{ field.label }}</span>
                                </label>

                                <!-- Color -->
                                <input
                                  v-else-if="field.type === 'color'"
                                  v-model="childBlock.settings[field.name]"
                                  type="color"
                                  class="w-full h-8 px-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"
                                >

                                <!-- Image URL -->
                                <input
                                  v-else-if="field.type === 'image'"
                                  v-model="childBlock.settings[field.name]"
                                  type="text"
                                  placeholder="URL изображения"
                                  class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                >

                                <!-- Select -->
                                <select
                                  v-else-if="field.type === 'select' && field.options"
                                  v-model="childBlock.settings[field.name]"
                                  class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                >
                                  <option v-for="opt in field.options" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                  </option>
                                </select>

                                <!-- Catalog Relation -->
                                <select
                                  v-else-if="field.type === 'catalog_relation'"
                                  v-model="childBlock.settings[field.name]"
                                  @focus="loadCatalogs"
                                  class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                >
                                  <option value="">Не выбрано</option>
                                  <option v-for="catalog in catalogs" :key="catalog.id" :value="catalog.id">
                                    {{ catalog.name }}
                                  </option>
                                </select>

                                <!-- Product Relation -->
                                <select
                                  v-else-if="field.type === 'product_relation'"
                                  v-model="childBlock.settings[field.name]"
                                  @focus="loadProducts"
                                  class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                >
                                  <option value="">Не выбрано</option>
                                  <option v-for="product in products" :key="product.id" :value="product.id">
                                    {{ product.name }}
                                  </option>
                                </select>

                                <!-- Page Relation -->
                                <select
                                  v-else-if="field.type === 'page_relation'"
                                  v-model="childBlock.settings[field.name]"
                                  @focus="loadPagesForRelation"
                                  class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                >
                                  <option value="">Не выбрано</option>
                                  <option v-for="pg in pagesForRelation" :key="pg.id" :value="pg.id">
                                    {{ pg.title }}
                                  </option>
                                </select>

                                <!-- Catalog Select (multiple) -->
                                <div v-else-if="field.type === 'catalog_select'" class="space-y-2">
                                  <div class="flex gap-2">
                                    <input
                                      v-model="catalogSearch"
                                      type="text"
                                      placeholder="Поиск каталогов..."
                                      @input="searchCatalogs"
                                      class="flex-1 px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                    >
                                  </div>
                                  <div class="max-h-32 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-2 space-y-1">
                                    <label
                                      v-for="catalog in availableCatalogs"
                                      :key="catalog.id"
                                      class="flex items-center p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer"
                                    >
                                      <input
                                        type="checkbox"
                                        :value="catalog.id"
                                        v-model="childBlock.settings[field.name]"
                                        class="mr-2 rounded"
                                      >
                                      <span class="text-xs text-gray-900 dark:text-white">{{ catalog.name }}</span>
                                    </label>
                                  </div>
                                </div>

                                <!-- InfoBlocks Select (multiple) -->
                                <div v-else-if="field.type === 'infoblocks_select'" class="space-y-2">
                                  <div class="flex gap-2">
                                    <input
                                      v-model="infoblockSearch"
                                      type="text"
                                      placeholder="Поиск инфоблоков..."
                                      @input="searchInfoBlocks"
                                      class="flex-1 px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                    >
                                  </div>
                                  <div class="max-h-32 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-2 space-y-1">
                                    <label
                                      v-for="infoblock in availableInfoBlocks"
                                      :key="infoblock.id"
                                      class="flex items-center p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer"
                                    >
                                      <input
                                        type="checkbox"
                                        :value="infoblock.id"
                                        v-model="childBlock.settings[field.name]"
                                        class="mr-2 rounded"
                                      >
                                      <span class="text-xs text-gray-900 dark:text-white">{{ infoblock.name }}</span>
                                    </label>
                                  </div>
                                </div>

                                <!-- Products Select (multiple with filters) -->
                                <div v-else-if="field.type === 'products_select'" class="space-y-2">
                                  <div class="flex gap-2 flex-wrap">
                                    <select
                                      v-model="productFilters.catalog_id"
                                      @change="searchProducts"
                                      class="flex-1 px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                    >
                                      <option value="">Все каталоги</option>
                                      <option v-for="catalog in availableCatalogs" :key="catalog.id" :value="catalog.id">
                                        {{ catalog.name }}
                                      </option>
                                    </select>
                                  </div>
                                  <div class="flex gap-2 flex-wrap text-xs">
                                    <label class="flex items-center">
                                      <input
                                        type="checkbox"
                                        v-model="productFilters.is_new"
                                        @change="searchProducts"
                                        class="mr-1 rounded"
                                      >
                                      <span class="text-gray-700 dark:text-gray-300">Новинка</span>
                                    </label>
                                    <label class="flex items-center">
                                      <input
                                        type="checkbox"
                                        v-model="productFilters.is_recommended"
                                        @change="searchProducts"
                                        class="mr-1 rounded"
                                      >
                                      <span class="text-gray-700 dark:text-gray-300">Рекомендованный</span>
                                    </label>
                                    <label class="flex items-center">
                                      <input
                                        type="checkbox"
                                        v-model="productFilters.is_hot"
                                        @change="searchProducts"
                                        class="mr-1 rounded"
                                      >
                                      <span class="text-gray-700 dark:text-gray-300">Хит</span>
                                    </label>
                                  </div>
                                  <input
                                    v-model="productSearch"
                                    type="text"
                                    placeholder="Поиск товаров..."
                                    @input="searchProducts"
                                    class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                  >
                                  <div class="max-h-32 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-2 space-y-1">
                                    <label
                                      v-for="product in availableProducts"
                                      :key="product.id"
                                      class="flex items-center p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer"
                                    >
                                      <input
                                        type="checkbox"
                                        :value="product.id"
                                        v-model="childBlock.settings[field.name]"
                                        class="mr-2 rounded"
                                      >
                                      <span class="text-xs text-gray-900 dark:text-white">{{ product.name }}</span>
                                    </label>
                                  </div>
                                </div>

                                <!-- Repeater -->
                                <div v-else-if="field.type === 'repeater'" class="space-y-2">
                                  <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Элементы</span>
                                    <button
                                      type="button"
                                      @click="addRepeaterItem(childBlock.settings, field.name)"
                                      class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded"
                                    >
                                      + Добавить
                                    </button>
                                  </div>
                                  <div class="space-y-2">
                                    <div
                                      v-for="(item, itemIndex) in childBlock.settings[field.name]"
                                      :key="itemIndex"
                                      class="border border-gray-300 dark:border-gray-600 rounded p-2 space-y-1"
                                    >
                                      <div class="flex justify-between items-center">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Элемент #{{ itemIndex + 1 }}</span>
                                        <button
                                          type="button"
                                          @click="removeRepeaterItem(childBlock.settings, field.name, itemIndex)"
                                          class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                          </svg>
                                        </button>
                                      </div>
                                      <textarea
                                        v-model="childBlock.settings[field.name][itemIndex]"
                                        rows="2"
                                        placeholder="Введите данные..."
                                        class="w-full px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white text-xs"
                                      ></textarea>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div class="flex gap-2 mt-3">
                              <ThemeButton @click="saveBlock(childBlock)" variant="primary" size="sm">
                                Сохранить
                              </ThemeButton>
                              <ThemeButton @click="childBlock.isEditing = false" variant="secondary" size="sm">
                                Отмена
                              </ThemeButton>
                            </div>
                          </div>

                          <!-- Child Block Preview -->
                          <div v-else class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                            <div v-for="(value, key) in childBlock.settings" :key="key" class="truncate">
                              <strong>{{ key }}:</strong> {{ typeof value === 'object' ? JSON.stringify(value) : value }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Block Preview (Non-Container) -->
                <div v-else class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                  <div v-for="(value, key) in block.settings" :key="key" class="truncate">
                    <strong>{{ key }}:</strong> {{ typeof value === 'object' ? JSON.stringify(value) : value }}
                  </div>
                </div>
              </div>
            </TransitionGroup>
          </div>
        </div>
      </div>
    </div>

    <!-- Block Selector Modal -->
    <div
      v-if="blockSelectorVisible"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeBlockSelectorModal"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
              Выбрать блок
              <span v-if="selectedParentBlockId" class="text-sm font-normal text-gray-600 dark:text-gray-400 ml-2">
                (для колонки {{ getColumnLabel(selectedContainerColumn) }})
              </span>
            </h3>
            <button
              @click="closeBlockSelectorModal"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Search -->
          <input
            v-model="blockSearchQuery"
            type="text"
            placeholder="Поиск блоков..."
            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
          >
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-hidden flex">
          <!-- Category Tabs -->
          <div class="w-48 border-r border-gray-200 dark:border-gray-700 p-4 overflow-y-auto">
            <div class="space-y-1">
              <button
                @click="selectedModalCategory = ''"
                :class="[
                  'w-full text-left px-3 py-2 rounded-lg text-sm transition-colors',
                  selectedModalCategory === '' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
              >
                Все блоки
              </button>
              <button
                v-for="cat in categories"
                :key="cat"
                @click="selectedModalCategory = cat"
                :class="[
                  'w-full text-left px-3 py-2 rounded-lg text-sm transition-colors',
                  selectedModalCategory === cat ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
              >
                {{ cat }}
              </button>
            </div>
          </div>

          <!-- Block Grid -->
          <div class="flex-1 p-6 overflow-y-auto">
            <div class="grid grid-cols-2 gap-4">
              <div
                v-for="blockType in filteredModalBlockTypes"
                :key="blockType.id"
                @click="selectBlockFromModal(blockType)"
                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 cursor-pointer hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-md transition-all"
              >
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1">{{ blockType.name }}</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">{{ blockType.description }}</p>
                    <div class="mt-2 flex items-center gap-2">
                      <span v-if="blockType.category" class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-0.5 rounded">
                        {{ blockType.category }}
                      </span>
                      <span v-if="blockType.is_container" class="text-xs bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded">
                        Контейнер
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="filteredModalBlockTypes.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
              Блоки не найдены
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal
      v-model="deleteModal.visible"
      type="warning"
      :title="deleteModal.title"
      :message="deleteModal.message"
      confirmText="Удалить"
      cancelText="Отмена"
      :showCancel="true"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from './ThemeButton.vue';
import Modal from './Modal.vue';

const route = useRoute();
const router = useRouter();

const pageId = computed(() => route.params.id);

const page = ref(null);
const blocks = ref([]);
const blockTypes = ref([]);
const loadingBlocks = ref(false);
const loadingBlockTypes = ref(false);
const categoryFilter = ref('');

// Block Selector Modal
const blockSelectorVisible = ref(false);
const selectedParentBlockId = ref(null);
const selectedContainerColumn = ref(null);
const blockSearchQuery = ref('');
const selectedModalCategory = ref('');

// Relation field data
const catalogs = ref([]);
const products = ref([]);
const pagesForRelation = ref([]);

// New field types data
const availableCatalogs = ref([]);
const availableInfoBlocks = ref([]);
const availableProducts = ref([]);
const catalogSearch = ref('');
const infoblockSearch = ref('');
const productSearch = ref('');
const productFilters = ref({
  catalog_id: '',
  is_new: false,
  is_recommended: false,
  is_hot: false
});

// Delete Confirmation Modal
const deleteModal = ref({
  visible: false,
  title: '',
  message: '',
  blockToDelete: null,
  deleteIndex: null,
  deleteType: null, // 'root' or 'child'
  parentBlockId: null,
  column: null
});

const categories = computed(() => {
  const cats = new Set();
  blockTypes.value.forEach(bt => {
    if (bt.category) cats.add(bt.category);
  });
  return Array.from(cats);
});

const filteredBlockTypes = computed(() => {
  if (!categoryFilter.value) return blockTypes.value;
  return blockTypes.value.filter(bt => bt.category === categoryFilter.value);
});

// Root blocks (blocks without parent)
const rootBlocks = computed(() => {
  return blocks.value.filter(block => !block.parent_block_id);
});

// Filtered block types for modal
const filteredModalBlockTypes = computed(() => {
  let filtered = blockTypes.value;

  // Filter by category
  if (selectedModalCategory.value) {
    filtered = filtered.filter(bt => bt.category === selectedModalCategory.value);
  }

  // Filter by search query
  if (blockSearchQuery.value) {
    const query = blockSearchQuery.value.toLowerCase();
    filtered = filtered.filter(bt =>
      bt.name.toLowerCase().includes(query) ||
      bt.description?.toLowerCase().includes(query)
    );
  }

  // If adding to a container, exclude container blocks
  if (selectedParentBlockId.value) {
    filtered = filtered.filter(bt => !bt.is_container);
  }

  return filtered;
});

const loadPage = async () => {
  try {
    const response = await fetch(`/admin/api/pages/${pageId.value}`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      page.value = await response.json();

      if (page.value.type !== 'dynamic') {
        alert('Конструктор доступен только для динамических страниц');
        router.push('/pages');
      }
    }
  } catch (error) {
    console.error('Failed to load page:', error);
  }
};

const loadBlocks = async () => {
  loadingBlocks.value = true;
  try {
    const response = await fetch(`/admin/api/pages/${pageId.value}/blocks?with_children=1`, {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      const rawBlocks = data.data || data;

      // Flatten blocks recursively
      const flattenBlocks = (blocksList) => {
        let result = [];
        blocksList.forEach(block => {
          const flatBlock = {
            ...block,
            isEditing: false,
            settings: block.settings || {},
            child_blocks: undefined // Remove nested structure
          };
          result.push(flatBlock);

          // Add child blocks if present
          if (block.child_blocks && block.child_blocks.length > 0) {
            result = result.concat(flattenBlocks(block.child_blocks));
          }
        });
        return result;
      };

      blocks.value = flattenBlocks(rawBlocks);
    }
  } catch (error) {
    console.error('Failed to load blocks:', error);
  } finally {
    loadingBlocks.value = false;
  }
};

const loadBlockTypes = async () => {
  loadingBlockTypes.value = true;
  try {
    const response = await fetch('/admin/api/page-block-types?is_active=1', {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      blockTypes.value = data.data || data;
    }
  } catch (error) {
    console.error('Failed to load block types:', error);
  } finally {
    loadingBlockTypes.value = false;
  }
};

const addBlock = async (blockType, parentBlockId = null, containerColumn = null) => {
  try {
    const defaultSettings = {};
    if (blockType.fields_schema) {
      blockType.fields_schema.forEach(field => {
        // Initialize array for multiple select fields
        if (['catalog_select', 'infoblocks_select', 'products_select', 'repeater'].includes(field.type)) {
          defaultSettings[field.name] = [];
        } else {
          defaultSettings[field.name] = field.default || '';
        }
      });
    }

    const payload = {
      block_type_id: blockType.id,
      settings: defaultSettings,
      is_active: true,
    };

    if (parentBlockId) {
      payload.parent_block_id = parentBlockId;
      payload.container_column = containerColumn;
      // Sort within container
      const siblingBlocks = blocks.value.filter(
        b => b.parent_block_id === parentBlockId && b.container_column === containerColumn
      );
      payload.sort = siblingBlocks.length * 10;
    } else {
      // Sort at root level
      payload.sort = rootBlocks.value.length * 10;
    }

    const response = await fetch(`/admin/api/pages/${pageId.value}/blocks`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    if (response.ok) {
      loadBlocks();
    }
  } catch (error) {
    console.error('Failed to add block:', error);
    alert('Ошибка при добавлении блока');
  }
};

const saveBlock = async (block) => {
  try {
    const response = await fetch(`/admin/api/pages/${pageId.value}/blocks/${block.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        block_type_id: block.block_type_id,
        settings: block.settings,
        is_active: block.is_active,
        sort: block.sort
      })
    });

    if (response.ok) {
      block.isEditing = false;
      loadBlocks();
    }
  } catch (error) {
    console.error('Failed to save block:', error);
    alert('Ошибка при сохранении блока');
  }
};

const deleteBlock = (block, index) => {
  deleteModal.value = {
    visible: true,
    title: 'Удалить блок?',
    message: `Вы уверены, что хотите удалить блок "${block.block_type?.name}"? Это действие нельзя отменить.`,
    blockToDelete: block,
    deleteIndex: index,
    deleteType: 'root',
    parentBlockId: null,
    column: null
  };
};

const confirmDelete = async () => {
  const { blockToDelete, deleteType, parentBlockId, column } = deleteModal.value;

  try {
    const response = await fetch(`/admin/api/pages/${pageId.value}/blocks/${blockToDelete.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      loadBlocks();
    } else {
      const error = await response.json();
      alert('Ошибка при удалении блока: ' + (error.message || ''));
    }
  } catch (error) {
    console.error('Failed to delete block:', error);
    alert('Ошибка при удалении блока');
  }
};

const moveBlockUp = async (index) => {
  if (index === 0) return;

  const rootBlocksList = rootBlocks.value;
  const newBlocks = [...rootBlocksList];
  [newBlocks[index - 1], newBlocks[index]] = [newBlocks[index], newBlocks[index - 1]];

  await reorderBlocks(newBlocks);
};

const moveBlockDown = async (index) => {
  const rootBlocksList = rootBlocks.value;
  if (index === rootBlocksList.length - 1) return;

  const newBlocks = [...rootBlocksList];
  [newBlocks[index], newBlocks[index + 1]] = [newBlocks[index + 1], newBlocks[index]];

  await reorderBlocks(newBlocks);
};

const reorderBlocks = async (newBlocks) => {
  try {
    const blocks = newBlocks.map((block, index) => ({
      id: block.id,
      sort: index * 10
    }));

    const response = await fetch(`/admin/api/pages/${pageId.value}/blocks/reorder`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify({ blocks })
    });

    if (response.ok) {
      loadBlocks();
    } else {
      const error = await response.json();
      alert('Ошибка при изменении порядка блоков: ' + (error.message || ''));
    }
  } catch (error) {
    console.error('Failed to reorder blocks:', error);
    alert('Ошибка при изменении порядка блоков');
  }
};

const toggleBlockEdit = (block) => {
  block.isEditing = !block.isEditing;

  // Load data for new field types when opening edit mode
  if (block.isEditing) {
    const hasNewFieldTypes = block.block_type?.fields_schema?.some(field =>
      ['catalog_select', 'infoblocks_select', 'products_select'].includes(field.type)
    );

    if (hasNewFieldTypes) {
      searchCatalogs();
      searchInfoBlocks();
      // Find products_select field to apply its filters
      const productsField = block.block_type?.fields_schema?.find(f => f.type === 'products_select');
      searchProducts(productsField);
    }
  }
};

// Container helper functions
const getContainerColumns = (identifier) => {
  const columnMap = {
    'container_50_50': ['col1', 'col2'],
    'container_33_33_33': ['col1', 'col2', 'col3'],
    'container_25_75': ['col1', 'col2'],
  };
  return columnMap[identifier] || [];
};

const getContainerGridClass = (identifier) => {
  const gridMap = {
    'container_50_50': 'grid grid-cols-2',
    'container_33_33_33': 'grid grid-cols-3',
    'container_25_75': 'grid grid-cols-[1fr_3fr]',
  };
  return gridMap[identifier] || 'grid grid-cols-1';
};

const getColumnLabel = (column) => {
  const labelMap = {
    'col1': 'Колонка 1',
    'col2': 'Колонка 2',
    'col3': 'Колонка 3',
  };
  return labelMap[column] || column;
};

const getChildBlocksForColumn = (parentBlockId, column) => {
  return blocks.value
    .filter(block => block.parent_block_id === parentBlockId && block.container_column === column)
    .sort((a, b) => a.sort - b.sort);
};

// Modal functions
const showBlockSelectorModal = (parentBlockId = null, containerColumn = null) => {
  selectedParentBlockId.value = parentBlockId;
  selectedContainerColumn.value = containerColumn;
  blockSelectorVisible.value = true;
  blockSearchQuery.value = '';
  selectedModalCategory.value = '';
};

const closeBlockSelectorModal = () => {
  blockSelectorVisible.value = false;
  selectedParentBlockId.value = null;
  selectedContainerColumn.value = null;
  blockSearchQuery.value = '';
  selectedModalCategory.value = '';
};

const selectBlockFromModal = (blockType) => {
  addBlock(blockType, selectedParentBlockId.value, selectedContainerColumn.value);
  closeBlockSelectorModal();
};

// Delete child block
const deleteChildBlock = (childBlock, parentBlockId, column, index) => {
  deleteModal.value = {
    visible: true,
    title: 'Удалить блок?',
    message: `Вы уверены, что хотите удалить блок "${childBlock.block_type?.name}"? Это действие нельзя отменить.`,
    blockToDelete: childBlock,
    deleteIndex: index,
    deleteType: 'child',
    parentBlockId: parentBlockId,
    column: column
  };
};

// Move child blocks within container
const moveChildBlockUp = async (parentBlockId, column, index) => {
  if (index === 0) return;

  const columnBlocks = getChildBlocksForColumn(parentBlockId, column);
  const newBlocks = [...columnBlocks];
  [newBlocks[index - 1], newBlocks[index]] = [newBlocks[index], newBlocks[index - 1]];

  await reorderChildBlocks(newBlocks);
};

const moveChildBlockDown = async (parentBlockId, column, index) => {
  const columnBlocks = getChildBlocksForColumn(parentBlockId, column);
  if (index === columnBlocks.length - 1) return;

  const newBlocks = [...columnBlocks];
  [newBlocks[index], newBlocks[index + 1]] = [newBlocks[index + 1], newBlocks[index]];

  await reorderChildBlocks(newBlocks);
};

const reorderChildBlocks = async (newBlocks) => {
  try {
    const blocks = newBlocks.map((block, index) => ({
      id: block.id,
      sort: index * 10
    }));

    const response = await fetch(`/admin/api/pages/${pageId.value}/blocks/reorder`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify({ blocks })
    });

    if (response.ok) {
      loadBlocks();
    } else {
      const error = await response.json();
      alert('Ошибка при изменении порядка блоков: ' + (error.message || ''));
    }
  } catch (error) {
    console.error('Failed to reorder blocks:', error);
    alert('Ошибка при изменении порядка блоков');
  }
};

const previewPage = () => {
  if (page.value) {
    window.open(`/admin/viewer/${page.value.slug}`, '_blank');
  }
};

const togglePublish = async () => {
  if (!page.value) return;

  try {
    const response = await fetch(`/admin/api/pages/${pageId.value}/toggle-publish`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      const data = await response.json();
      page.value.is_active = data.is_active;
    } else {
      alert('Ошибка при изменении статуса публикации');
    }
  } catch (error) {
    console.error('Failed to toggle publish:', error);
    alert('Ошибка при изменении статуса публикации');
  }
};

// Load relation data
const loadCatalogs = async () => {
  if (catalogs.value.length > 0) return; // Already loaded

  try {
    const response = await fetch('/admin/api/catalogs/list', {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      catalogs.value = data.data || data;
    }
  } catch (error) {
    console.error('Failed to load catalogs:', error);
  }
};

const loadProducts = async () => {
  if (products.value.length > 0) return; // Already loaded

  try {
    const response = await fetch('/admin/api/products?per_page=1000', {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      products.value = data.data || data;
    }
  } catch (error) {
    console.error('Failed to load products:', error);
  }
};

const loadPagesForRelation = async () => {
  if (pagesForRelation.value.length > 0) return; // Already loaded

  try {
    const response = await fetch('/admin/api/pages?per_page=1000', {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      pagesForRelation.value = data.data || data;
    }
  } catch (error) {
    console.error('Failed to load pages:', error);
  }
};

// New field types functions
const searchCatalogs = async () => {
  try {
    const params = new URLSearchParams();
    if (catalogSearch.value) {
      params.append('search', catalogSearch.value);
    }

    const response = await fetch(`/admin/api/page-block-fields/catalogs?${params}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      availableCatalogs.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to search catalogs:', error);
  }
};

const searchInfoBlocks = async () => {
  try {
    const params = new URLSearchParams();
    if (infoblockSearch.value) {
      params.append('search', infoblockSearch.value);
    }

    const response = await fetch(`/admin/api/page-block-fields/infoblocks?${params}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      availableInfoBlocks.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to search infoblocks:', error);
  }
};

const searchProducts = async (field = null) => {
  try {
    const params = new URLSearchParams();
    if (productSearch.value) {
      params.append('search', productSearch.value);
    }
    if (productFilters.value.catalog_id) {
      params.append('catalog_id', productFilters.value.catalog_id);
    }

    // Apply field-level filters first (from block type configuration)
    if (field) {
      if (field.filter_new) {
        params.append('is_new', '1');
      }
      if (field.filter_recommended) {
        params.append('is_recommended', '1');
      }
      if (field.filter_hot) {
        params.append('is_hot', '1');
      }
    }

    // Then apply user-selected filters (from UI)
    if (!field || !field.filter_new) {
      if (productFilters.value.is_new) {
        params.append('is_new', '1');
      }
    }
    if (!field || !field.filter_recommended) {
      if (productFilters.value.is_recommended) {
        params.append('is_recommended', '1');
      }
    }
    if (!field || !field.filter_hot) {
      if (productFilters.value.is_hot) {
        params.append('is_hot', '1');
      }
    }

    const response = await fetch(`/admin/api/page-block-fields/products?${params}`, {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      availableProducts.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to search products:', error);
  }
};

const addRepeaterItem = (settings, fieldName) => {
  if (!settings[fieldName]) {
    settings[fieldName] = [];
  }
  settings[fieldName].push('');
};

const removeRepeaterItem = (settings, fieldName, index) => {
  if (settings[fieldName] && Array.isArray(settings[fieldName])) {
    settings[fieldName].splice(index, 1);
  }
};

onMounted(() => {
  loadPage();
  loadBlocks();
  loadBlockTypes();

  // Pre-load catalogs for new field types
  searchCatalogs();
  searchInfoBlocks();
  searchProducts();
});
</script>

<style scoped>
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.list-leave-active {
  position: absolute;
}
</style>
