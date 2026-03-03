<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Страницы и SEO</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Настройки страниц, внешнего вида сайта и меню</p>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg mb-6">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex -mb-px">
          <button
            @click="activeTab = 'appearance'"
            :class="[
              'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'appearance'
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'
            ]"
          >
            Внешний вид
          </button>
          <button
            @click="activeTab = 'menus'"
            :class="[
              'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'menus'
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'
            ]"
          >
            Меню
          </button>
          <button
            @click="activeTab = 'pages'"
            :class="[
              'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'pages'
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'
            ]"
          >
            Управление страницами
          </button>
        </nav>
      </div>
    </div>

    <!-- Appearance Tab -->
    <div v-if="activeTab === 'appearance'" class="space-y-6">
      <form @submit.prevent="saveAppearance">
        <!-- Logo Settings -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Логотип</h3>

          <div class="space-y-4">
            <!-- Logo Upload -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Логотип сайта</label>

              <div v-if="settings.logo_path" class="mb-3">
                <img :src="getLogoUrl()" alt="Logo" class="max-h-24 mb-2" />
                <button @click="deleteLogo" type="button" class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700">
                  Удалить логотип
                </button>
              </div>

              <input
                ref="logoInput"
                type="file"
                accept="image/*"
                @change="uploadLogo"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
              <p class="mt-1 text-xs text-gray-500">Поддерживаемые форматы: JPEG, PNG, GIF, SVG (макс. 2MB)</p>
            </div>

            <!-- Logo Size -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ширина логотипа (px)</label>
                <input v-model.number="settings.logo_width" type="number" min="0" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Высота логотипа (px)</label>
                <input v-model.number="settings.logo_height" type="number" min="0" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white" />
              </div>
            </div>
          </div>
        </div>

        <!-- Header Template Settings -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Шаблон шапки сайта</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Выберите шаблон для шапки вашего сайта</p>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Header Template 1 -->
            <div
              @click="selectHeaderTemplate('header1')"
              :class="[
                'relative border-2 rounded-lg cursor-pointer transition-all hover:shadow-lg',
                settings.header_template === 'header1'
                  ? 'border-blue-500 shadow-lg'
                  : 'border-gray-300 dark:border-gray-600'
              ]"
            >
              <div class="aspect-video bg-gray-100 dark:bg-gray-700 rounded-t-lg overflow-hidden">
                <!-- Превью шаблона 1 -->
                <div class="p-3 space-y-2">
                  <div class="bg-white dark:bg-gray-600 h-8 rounded flex items-center justify-center">
                    <div class="w-16 h-4 bg-gray-300 dark:bg-gray-500 rounded"></div>
                  </div>
                  <div class="bg-white dark:bg-gray-600 h-6 rounded flex gap-2 px-2">
                    <div class="flex-1 bg-gray-300 dark:bg-gray-500 rounded"></div>
                    <div class="flex-1 bg-gray-300 dark:bg-gray-500 rounded"></div>
                    <div class="flex-1 bg-gray-300 dark:bg-gray-500 rounded"></div>
                  </div>
                </div>
              </div>
              <div class="p-4">
                <div class="flex items-start justify-between">
                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Классический</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Логотип сверху, меню снизу</p>
                  </div>
                  <div v-if="settings.header_template === 'header1'" class="text-blue-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </div>
                <!-- Settings for header1 -->
                <div v-if="settings.header_template === 'header1'" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Меню шапки</label>
                    <select v-model="headerTemplateSettings.header1.menu_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <option :value="null">Без меню</option>
                      <option v-for="menu in headerMenus" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Header Template 2 -->
            <div
              @click="selectHeaderTemplate('header2')"
              :class="[
                'relative border-2 rounded-lg cursor-pointer transition-all hover:shadow-lg',
                settings.header_template === 'header2'
                  ? 'border-blue-500 shadow-lg'
                  : 'border-gray-300 dark:border-gray-600'
              ]"
            >
              <div class="aspect-video bg-gray-100 dark:bg-gray-700 rounded-t-lg overflow-hidden">
                <!-- Превью шаблона 2 -->
                <div class="p-3">
                  <div class="bg-white dark:bg-gray-600 h-10 rounded flex items-center justify-between px-3">
                    <div class="w-12 h-4 bg-gray-300 dark:bg-gray-500 rounded"></div>
                    <div class="flex gap-2">
                      <div class="w-12 h-3 bg-gray-300 dark:bg-gray-500 rounded"></div>
                      <div class="w-12 h-3 bg-gray-300 dark:bg-gray-500 rounded"></div>
                      <div class="w-12 h-3 bg-gray-300 dark:bg-gray-500 rounded"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="p-4">
                <div class="flex items-start justify-between">
                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Горизонтальный</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Логотип слева, меню справа</p>
                  </div>
                  <div v-if="settings.header_template === 'header2'" class="text-blue-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </div>
                <div v-if="settings.header_template === 'header2'" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Меню шапки</label>
                    <select v-model="headerTemplateSettings.header2.menu_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <option :value="null">Без меню</option>
                      <option v-for="menu in headerMenus" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Дополнительные кнопки</label>
                    <div class="space-y-2">
                      <label class="flex items-center">
                        <input v-model="headerTemplateSettings.header2.show_cart" type="checkbox" class="mr-2 rounded">
                        <span class="text-sm">Корзина</span>
                      </label>
                      <label class="flex items-center">
                        <input v-model="headerTemplateSettings.header2.show_account" type="checkbox" class="mr-2 rounded">
                        <span class="text-sm">Личный кабинет</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Header Template 3 -->
            <div
              @click="selectHeaderTemplate('header3')"
              :class="[
                'relative border-2 rounded-lg cursor-pointer transition-all hover:shadow-lg',
                settings.header_template === 'header3'
                  ? 'border-blue-500 shadow-lg'
                  : 'border-gray-300 dark:border-gray-600'
              ]"
            >
              <div class="aspect-video bg-gray-200 dark:bg-gray-800 rounded-t-lg overflow-hidden">
                <!-- Превью шаблона 3 (темный) -->
                <div class="p-3">
                  <div class="bg-gray-800 dark:bg-gray-900 h-10 rounded flex items-center justify-between px-3">
                    <div class="w-12 h-4 bg-gray-600 dark:bg-gray-700 rounded"></div>
                    <div class="flex gap-2 items-center">
                      <div class="w-12 h-3 bg-gray-600 dark:bg-gray-700 rounded"></div>
                      <div class="w-12 h-3 bg-gray-600 dark:bg-gray-700 rounded"></div>
                      <div class="w-6 h-6 bg-blue-600 rounded-full"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="p-4">
                <div class="flex items-start justify-between">
                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Темный</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Современный темный стиль</p>
                  </div>
                  <div v-if="settings.header_template === 'header3'" class="text-blue-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </div>
                <div v-if="settings.header_template === 'header3'" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Меню шапки</label>
                    <select v-model="headerTemplateSettings.header3.menu_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <option :value="null">Без меню</option>
                      <option v-for="menu in headerMenus" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Социальные сети</label>
                    <div class="space-y-2">
                      <input v-model="headerTemplateSettings.header3.social_vk" type="url" placeholder="VK URL" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <input v-model="headerTemplateSettings.header3.social_instagram" type="url" placeholder="Instagram URL" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <input v-model="headerTemplateSettings.header3.social_telegram" type="url" placeholder="Telegram URL" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Template Settings -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Шаблон футера сайта</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Выберите шаблон для футера вашего сайта</p>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Footer Template 1 -->
            <div
              @click="selectFooterTemplate('footer1')"
              :class="[
                'relative border-2 rounded-lg cursor-pointer transition-all hover:shadow-lg',
                settings.footer_template === 'footer1'
                  ? 'border-purple-500 shadow-lg'
                  : 'border-gray-300 dark:border-gray-600'
              ]"
            >
              <div class="aspect-video bg-gray-100 dark:bg-gray-700 rounded-t-lg overflow-hidden flex items-end">
                <div class="w-full p-3">
                  <div class="bg-white dark:bg-gray-600 h-12 rounded flex flex-col items-center justify-center gap-1">
                    <div class="flex gap-2">
                      <div class="w-12 h-2 bg-gray-300 dark:bg-gray-500 rounded"></div>
                      <div class="w-12 h-2 bg-gray-300 dark:bg-gray-500 rounded"></div>
                      <div class="w-12 h-2 bg-gray-300 dark:bg-gray-500 rounded"></div>
                    </div>
                    <div class="w-24 h-2 bg-gray-300 dark:bg-gray-500 rounded"></div>
                  </div>
                </div>
              </div>
              <div class="p-4">
                <div class="flex items-start justify-between">
                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Простой</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Центрированное меню</p>
                  </div>
                  <div v-if="settings.footer_template === 'footer1'" class="text-purple-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </div>
                <div v-if="settings.footer_template === 'footer1'" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Меню футера</label>
                    <select v-model="footerTemplateSettings.footer1.menu_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <option :value="null">Без меню</option>
                      <option v-for="menu in footerMenus" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Template 2 -->
            <div
              @click="selectFooterTemplate('footer2')"
              :class="[
                'relative border-2 rounded-lg cursor-pointer transition-all hover:shadow-lg',
                settings.footer_template === 'footer2'
                  ? 'border-purple-500 shadow-lg'
                  : 'border-gray-300 dark:border-gray-600'
              ]"
            >
              <div class="aspect-video bg-gray-100 dark:bg-gray-700 rounded-t-lg overflow-hidden flex items-end">
                <div class="w-full p-3">
                  <div class="bg-white dark:bg-gray-600 h-12 rounded grid grid-cols-3 gap-2 p-2">
                    <div class="space-y-1">
                      <div class="w-full h-2 bg-gray-300 dark:bg-gray-500 rounded"></div>
                      <div class="w-3/4 h-1 bg-gray-300 dark:bg-gray-500 rounded"></div>
                    </div>
                    <div class="space-y-1">
                      <div class="w-full h-2 bg-gray-300 dark:bg-gray-500 rounded"></div>
                      <div class="w-3/4 h-1 bg-gray-300 dark:bg-gray-500 rounded"></div>
                    </div>
                    <div class="space-y-1">
                      <div class="w-full h-2 bg-gray-300 dark:bg-gray-500 rounded"></div>
                      <div class="w-3/4 h-1 bg-gray-300 dark:bg-gray-500 rounded"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="p-4">
                <div class="flex items-start justify-between">
                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Многоколоночный</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Логотип, меню, контакты</p>
                  </div>
                  <div v-if="settings.footer_template === 'footer2'" class="text-purple-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </div>
                <div v-if="settings.footer_template === 'footer2'" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Меню футера</label>
                    <select v-model="footerTemplateSettings.footer2.menu_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <option :value="null">Без меню</option>
                      <option v-for="menu in footerMenus" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Template 3 -->
            <div
              @click="selectFooterTemplate('footer3')"
              :class="[
                'relative border-2 rounded-lg cursor-pointer transition-all hover:shadow-lg',
                settings.footer_template === 'footer3'
                  ? 'border-purple-500 shadow-lg'
                  : 'border-gray-300 dark:border-gray-600'
              ]"
            >
              <div class="aspect-video bg-gray-100 dark:bg-gray-700 rounded-t-lg overflow-hidden flex items-end">
                <div class="w-full p-3">
                  <div class="bg-white dark:bg-gray-600 h-12 rounded flex items-center justify-between px-3">
                    <div class="w-16 h-2 bg-gray-300 dark:bg-gray-500 rounded"></div>
                    <div class="flex gap-1">
                      <div class="w-4 h-4 bg-gray-300 dark:bg-gray-500 rounded-full"></div>
                      <div class="w-4 h-4 bg-gray-300 dark:bg-gray-500 rounded-full"></div>
                      <div class="w-4 h-4 bg-gray-300 dark:bg-gray-500 rounded-full"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="p-4">
                <div class="flex items-start justify-between">
                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Минималистичный</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">С социальными сетями</p>
                  </div>
                  <div v-if="settings.footer_template === 'footer3'" class="text-purple-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </div>
                <div v-if="settings.footer_template === 'footer3'" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Меню футера</label>
                    <select v-model="footerTemplateSettings.footer3.menu_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <option :value="null">Без меню</option>
                      <option v-for="menu in footerMenus" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Социальные сети</label>
                    <div class="space-y-2">
                      <input v-model="footerTemplateSettings.footer3.social_vk" type="url" placeholder="VK URL" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <input v-model="footerTemplateSettings.footer3.social_instagram" type="url" placeholder="Instagram URL" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                      <input v-model="footerTemplateSettings.footer3.social_telegram" type="url" placeholder="Telegram URL" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
          <ThemeButton type="submit" variant="primary" :disabled="savingAppearance">
            {{ savingAppearance ? 'Сохранение...' : 'Сохранить настройки внешнего вида' }}
          </ThemeButton>
        </div>
      </form>
    </div>

    <!-- Menus Tab -->
    <div v-if="activeTab === 'menus'">
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Меню сайта</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Создавайте и настраивайте меню для шапки и футера сайта</p>
          </div>
          <ThemeButton variant="primary" @click="$router.push('/menus')">
            Управление меню
          </ThemeButton>
        </div>

        <!-- Menus List -->
        <div v-if="loadingMenus" class="text-center py-8 text-gray-500 dark:text-gray-400">
          Загрузка меню...
        </div>
        <div v-else-if="menus.length === 0" class="text-center py-8">
          <p class="text-gray-500 dark:text-gray-400 mb-4">Меню еще не созданы</p>
          <ThemeButton variant="secondary" @click="$router.push('/menus')">
            Создать первое меню
          </ThemeButton>
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="menu in menus"
            :key="menu.id"
            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
          >
            <div class="flex-1">
              <div class="flex items-center gap-3">
                <h4 class="font-medium text-gray-900 dark:text-white">{{ menu.name }}</h4>
                <span
                  :class="[
                    'px-2 py-1 text-xs rounded',
                    menu.location === 'header'
                      ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                      : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
                  ]"
                >
                  {{ menu.location === 'header' ? 'Шапка' : 'Футер' }}
                </span>
                <span
                  :class="[
                    'px-2 py-1 text-xs rounded',
                    menu.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                  ]"
                >
                  {{ menu.is_active ? 'Активно' : 'Неактивно' }}
                </span>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">{{ menu.code }}</code>
                <span v-if="menu.description" class="ml-2">— {{ menu.description }}</span>
              </p>
            </div>
            <ThemeButton variant="secondary" size="sm" @click="$router.push(`/menus/${menu.id}/builder`)">
              Настроить
            </ThemeButton>
          </div>
        </div>
      </div>

      <!-- Menu Help -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Справка по меню</h3>
        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
          <p><strong>Меню шапки:</strong> Отображается в верхней части сайта. Обычно содержит основную навигацию.</p>
          <p><strong>Меню футера:</strong> Отображается в нижней части сайта. Обычно содержит дополнительные ссылки.</p>
          <p><strong>Вложенные пункты:</strong> Можно создавать подпункты меню (выпадающие списки).</p>
          <p><strong>Сортировка:</strong> Используйте drag-and-drop для изменения порядка пунктов меню.</p>
        </div>
      </div>
    </div>

    <!-- Pages Tab -->
    <div v-if="activeTab === 'pages'" class="space-y-6">
      <!-- Statistics -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Статистика</h3>
        <div v-if="loadingStats" class="text-center text-gray-500 dark:text-gray-400 py-4">
          Загрузка...
        </div>
        <div v-else class="grid grid-cols-3 gap-4">
          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_pages || 0 }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Всего страниц</div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active_pages || 0 }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Активных страниц</div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.block_types || 0 }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Типов блоков</div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Быстрые действия</h3>
        <div class="space-y-3">
          <ThemeButton variant="secondary" @click="$router.push('/pages')" class="w-full justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Управление страницами
          </ThemeButton>

          <ThemeButton variant="secondary" @click="$router.push('/page-block-types')" class="w-full justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/>
            </svg>
            Управление типами блоков
          </ThemeButton>

          <ThemeButton variant="secondary" @click="$router.push('/pages/create')" class="w-full justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Создать новую страницу
          </ThemeButton>
        </div>
      </div>

      <!-- Documentation -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Справка</h3>
        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
          <p><strong>Статические страницы:</strong> Простые страницы с HTML-контентом и SEO-метаданными.</p>
          <p><strong>Динамические страницы:</strong> Страницы, построенные с помощью конструктора блоков.</p>
          <p><strong>Типы блоков:</strong> Переиспользуемые компоненты для конструктора страниц (hero, текст, слайдер и т.д.).</p>
          <p class="mt-4">
            <strong>Примечание:</strong> Системные типы блоков защищены от изменений и удаления.
            Вы можете создавать собственные типы блоков для расширения функционала конструктора.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useModal } from '../composables/useModal';
import ThemeButton from './ThemeButton.vue';

const router = useRouter();
const { success, error } = useModal();

const activeTab = ref('appearance');
const logoInput = ref(null);
const savingAppearance = ref(false);

const settings = ref({
  logo_path: '',
  logo_width: null,
  logo_height: null,
  header_template: 'header1',
  footer_template: 'footer1'
});

const headerMenus = ref([]);
const footerMenus = ref([]);

const headerTemplateSettings = ref({
  header1: { menu_id: null },
  header2: { menu_id: null, show_cart: false, show_account: false },
  header3: { menu_id: null, social_vk: '', social_instagram: '', social_telegram: '' }
});

const footerTemplateSettings = ref({
  footer1: { menu_id: null },
  footer2: { menu_id: null },
  footer3: { menu_id: null, social_vk: '', social_instagram: '', social_telegram: '' }
});

const loadingStats = ref(false);
const stats = ref({
  total_pages: 0,
  active_pages: 0,
  block_types: 0
});

const loadingMenus = ref(false);
const menus = ref([]);

const loadSettings = async () => {
  try {
    const response = await fetch('/admin/api/settings');
    const data = await response.json();

    settings.value = {
      logo_path: data.logo_path || '',
      logo_width: data.logo_width || null,
      logo_height: data.logo_height || null,
      header_template: data.header_template || 'header1',
      footer_template: data.footer_template || 'footer1'
    };
  } catch (err) {
    console.error('Error fetching settings:', err);
  }
};

const getLogoUrl = () => {
  if (!settings.value.logo_path) return '';
  return '/storage/' + settings.value.logo_path;
};

const uploadLogo = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('logo', file);

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/settings/upload-logo', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token
      },
      body: formData
    });

    if (!response.ok) {
      throw new Error('Failed to upload logo');
    }

    const data = await response.json();
    settings.value.logo_path = data.path;
    await success('Логотип успешно загружен!');
  } catch (err) {
    console.error('Error uploading logo:', err);
    await error('Ошибка при загрузке логотипа');
  }

  // Clear file input
  if (logoInput.value) {
    logoInput.value.value = '';
  }
};

const deleteLogo = async () => {
  if (!confirm('Вы действительно хотите удалить логотип?')) {
    return;
  }

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/settings/logo', {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token
      }
    });

    if (!response.ok) {
      throw new Error('Failed to delete logo');
    }

    settings.value.logo_path = '';
    await success('Логотип успешно удален!');
  } catch (err) {
    console.error('Error deleting logo:', err);
    await error('Ошибка при удалении логотипа');
  }
};

const saveAppearance = async () => {
  savingAppearance.value = true;
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

    await success('Настройки внешнего вида успешно сохранены!');
  } catch (err) {
    console.error('Error saving settings:', err);
    await error('Ошибка при сохранении настроек');
  } finally {
    savingAppearance.value = false;
  }
};

const loadStats = async () => {
  loadingStats.value = true;
  try {
    // Load pages count
    const pagesResponse = await fetch('/admin/api/pages', {
      headers: { 'Accept': 'application/json' }
    });
    if (pagesResponse.ok) {
      const pagesData = await pagesResponse.json();
      const pages = pagesData.data || pagesData;
      stats.value.total_pages = pages.length;
      stats.value.active_pages = pages.filter(p => p.is_active).length;
    }

    // Load block types count
    const blockTypesResponse = await fetch('/admin/api/page-block-types', {
      headers: { 'Accept': 'application/json' }
    });
    if (blockTypesResponse.ok) {
      const blockTypesData = await blockTypesResponse.json();
      const blockTypes = blockTypesData.data || blockTypesData;
      stats.value.block_types = blockTypes.length;
    }
  } catch (error) {
    console.error('Failed to load stats:', error);
  } finally {
    loadingStats.value = false;
  }
};

const loadMenus = async () => {
  loadingMenus.value = true;
  try {
    const response = await fetch('/admin/api/menus?is_active=1', {
      headers: { 'Accept': 'application/json' }
    });
    if (response.ok) {
      const data = await response.json();
      const allMenus = data.data || [];
      menus.value = allMenus;
      headerMenus.value = allMenus.filter(m => m.location === 'header');
      footerMenus.value = allMenus.filter(m => m.location === 'footer');
    }
  } catch (error) {
    console.error('Failed to load menus:', error);
  } finally {
    loadingMenus.value = false;
  }
};

const selectHeaderTemplate = (template) => {
  settings.value.header_template = template;
};

const selectFooterTemplate = (template) => {
  settings.value.footer_template = template;
};

onMounted(() => {
  loadSettings();
  loadStats();
  loadMenus();
});
</script>
