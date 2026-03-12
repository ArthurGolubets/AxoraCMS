<template>
  <div class="flex min-h-screen bg-gray-50 dark:bg-gray-900" :class="{ 'dark': isDark }">
    <!-- Mobile Overlay -->
    <div
      v-if="isMobileMenuOpen"
      @click="isMobileMenuOpen = false"
      class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
    ></div>

    <!-- Sidebar -->
    <aside
      class="fixed lg:sticky top-0 inset-y-0 left-0 z-50 h-screen bg-gray-900 dark:bg-gray-950 text-gray-200 flex flex-col border-r border-gray-800 transition-all duration-300"
      :class="[
        isCollapsed ? 'w-20' : 'w-64',
        isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center justify-between px-6 border-b border-gray-800">
        <h2 v-if="!isCollapsed" class="text-lg font-semibold text-white transition-opacity">{{ panelName }}</h2>
        <h2 v-else class="text-lg font-semibold text-white">HC</h2>
        <button
          @click="toggleSidebar"
          class="hidden lg:block p-1.5 rounded hover:bg-gray-800 transition"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isCollapsed ? 'M13 5l7 7-7 7M5 5l7 7-7 7' : 'M11 19l-7-7 7-7m8 14l-7-7 7-7'"/>
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 py-4 px-3 overflow-y-auto">
        <!-- Dashboard -->
        <router-link to="/" v-slot="{ isActive }" custom>
          <a @click="$router.push('/'); isMobileMenuOpen = false" class="flex items-center px-3 py-2.5 mb-1 rounded-md transition-colors cursor-pointer" :class="[isActive ? 'text-white font-medium' : 'text-gray-300 hover:bg-gray-800 hover:text-white', isCollapsed ? 'justify-center' : '']" :style="isActive ? `background-color: ${themeColor}` : ''" :title="isCollapsed ? 'Главная' : ''">
            <svg class="w-5 h-5" :class="isCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span v-if="!isCollapsed">Главная</span>
          </a>
        </router-link>

        <!-- Content Group (only if shop module is installed) -->
        <div v-if="shopModuleInstalled" class="mb-1">
          <button @click="toggleMenuGroup('content')" class="w-full flex items-center px-3 py-2.5 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors" :class="isCollapsed ? 'justify-center' : 'justify-between'" :title="isCollapsed ? 'Контент' : ''">
            <div class="flex items-center">
              <svg class="w-5 h-5" :class="isCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
              <span v-if="!isCollapsed">Контент</span>
            </div>
            <svg v-if="!isCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': menuGroups.content }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div v-if="menuGroups.content && !isCollapsed" class="ml-3 mt-1 space-y-1">
            <router-link to="/catalog" v-slot="{ isActive }" custom>
              <a @click="$router.push('/catalog'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                Каталог
              </a>
            </router-link>
            <router-link to="/products" v-slot="{ isActive }" custom>
              <a @click="$router.push('/products'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Список товаров
              </a>
            </router-link>
            <router-link to="/filters" v-slot="{ isActive }" custom>
              <a @click="$router.push('/filters'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Фильтры
              </a>
            </router-link>
            <router-link to="/content-settings" v-slot="{ isActive }" custom>
              <a @click="$router.push('/content-settings'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Настройки
              </a>
            </router-link>
          </div>
        </div>

        <!-- Callback Group (only if callback module is installed) -->
        <div v-if="callbackModuleInstalled" class="mb-1">
          <button @click="toggleMenuGroup('callback')" class="w-full flex items-center px-3 py-2.5 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors" :class="isCollapsed ? 'justify-center' : 'justify-between'" :title="isCollapsed ? 'Обратная связь' : ''">
            <div class="flex items-center">
              <svg class="w-5 h-5" :class="isCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <span v-if="!isCollapsed">Обратная связь</span>
            </div>
            <svg v-if="!isCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': menuGroups.callback }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div v-if="menuGroups.callback && !isCollapsed" class="ml-3 mt-1 space-y-1">
            <router-link to="/users-emails" v-slot="{ isActive }" custom>
              <a @click="$router.push('/users-emails'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                Подписки
              </a>
            </router-link>
            <router-link to="/comments" v-slot="{ isActive }" custom>
              <a @click="$router.push('/comments'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                Комментарии
              </a>
            </router-link>
            <router-link to="/user-requests" v-slot="{ isActive }" custom>
              <a @click="$router.push('/user-requests'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                Обращения
              </a>
            </router-link>
          </div>
        </div>

        <!-- Commerce Group (only if commerce module is installed and user is admin/super_admin) -->
        <div v-if="commerceModuleInstalled && canAccessSettings" class="mb-1">
          <button @click="toggleMenuGroup('commerce')" class="w-full flex items-center px-3 py-2.5 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors" :class="isCollapsed ? 'justify-center' : 'justify-between'" :title="isCollapsed ? 'Коммерция' : ''">
            <div class="flex items-center">
              <svg class="w-5 h-5" :class="isCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              <span v-if="!isCollapsed">Коммерция</span>
            </div>
            <svg v-if="!isCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': menuGroups.commerce }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div v-if="menuGroups.commerce && !isCollapsed" class="ml-3 mt-1 space-y-1">
            <router-link to="/orders" v-slot="{ isActive }" custom>
              <a @click="$router.push('/orders'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Список заказов
              </a>
            </router-link>
            <router-link to="/transactions" v-slot="{ isActive }" custom>
              <a @click="$router.push('/transactions'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Список транзакций
              </a>
            </router-link>
            <router-link to="/promocodes" v-slot="{ isActive }" custom>
              <a @click="$router.push('/promocodes'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Промокоды
              </a>
            </router-link>
            <router-link to="/orders-settings" v-slot="{ isActive }" custom>
              <a @click="$router.push('/orders-settings'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                Настройки заказов
              </a>
            </router-link>
          </div>
        </div>

        <!-- InfoBlocks Group (only if infoblocks module is installed) -->
        <div v-if="infoblocksModuleInstalled" class="mb-1">
          <button @click="toggleMenuGroup('infoblocks')" class="w-full flex items-center px-3 py-2.5 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors" :class="isCollapsed ? 'justify-center' : 'justify-between'" :title="isCollapsed ? 'Инфоблоки' : ''">
            <div class="flex items-center">
              <svg class="w-5 h-5" :class="isCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/></svg>
              <span v-if="!isCollapsed">Инфоблоки</span>
            </div>
            <svg v-if="!isCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': menuGroups.infoblocks }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div v-if="menuGroups.infoblocks && !isCollapsed" class="ml-3 mt-1 space-y-1">
            <router-link to="/infoblocks" v-slot="{ isActive }" custom>
              <a @click="$router.push('/infoblocks'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                Список инфоблоков
              </a>
            </router-link>

            <!-- Favorite InfoBlocks -->
            <div v-if="favoriteInfoBlocks.length > 0" class="border-t border-gray-700 pt-2 mt-2">
              <router-link v-for="infoBlock in favoriteInfoBlocks" :key="infoBlock.id" :to="`/infoblocks/${infoBlock.id}/elements`" v-slot="{ isActive }" custom>
                <a @click="$router.push(`/infoblocks/${infoBlock.id}/elements`); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                  <svg class="w-4 h-4 mr-2 text-yellow-500" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                  </svg>
                  <span class="truncate">{{ infoBlock.name }}</span>
                </a>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Pages & SEO Group (only if seo module is installed) -->
        <div v-if="seoModuleInstalled" class="mb-1">
          <button @click="toggleMenuGroup('pages_seo')" class="w-full flex items-center px-3 py-2.5 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors" :class="isCollapsed ? 'justify-center' : 'justify-between'" :title="isCollapsed ? 'Страницы и SEO' : ''">
            <div class="flex items-center">
              <svg class="w-5 h-5" :class="isCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <span v-if="!isCollapsed">Страницы и SEO</span>
            </div>
            <svg v-if="!isCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': menuGroups.pages_seo }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div v-if="menuGroups.pages_seo && !isCollapsed" class="ml-3 mt-1 space-y-1">
            <router-link to="/pages" v-slot="{ isActive }" custom>
              <a @click="$router.push('/pages'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Страницы
              </a>
            </router-link>
            <router-link to="/pages-settings" v-slot="{ isActive }" custom>
              <a @click="$router.push('/pages-settings'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                Настройки
              </a>
            </router-link>
            <router-link v-if="pageBuilderModuleInstalled" to="/page-builder" v-slot="{ isActive }" custom>
              <a @click="$router.push('/page-builder'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/></svg>
                Конструктор страниц
              </a>
            </router-link>
            <router-link v-if="pageBuilderModuleInstalled" to="/page-builder-settings" v-slot="{ isActive }" custom>
              <a @click="$router.push('/page-builder-settings'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Настройки конструктора
              </a>
            </router-link>
            <router-link v-if="pageBuilderModuleInstalled" to="/page-components" v-slot="{ isActive }" custom>
              <a @click="$router.push('/page-components'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
                Настройки компонентов
              </a>
            </router-link>
            <router-link v-if="pageBuilderModuleInstalled" to="/page-templates" v-slot="{ isActive }" custom>
              <a @click="$router.push('/page-templates'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h14a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3z"/></svg>
                Настройки шаблона
              </a>
            </router-link>
          </div>
        </div>

        <!-- Integrations Group (only if integrations are installed) -->
        <div v-if="telegramIntegrationInstalled || yookassaIntegrationInstalled" class="mb-1">
          <button @click="toggleMenuGroup('integrations')" class="w-full flex items-center px-3 py-2.5 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors" :class="isCollapsed ? 'justify-center' : 'justify-between'" :title="isCollapsed ? 'Интеграции' : ''">
            <div class="flex items-center">
              <svg class="w-5 h-5" :class="isCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
              <span v-if="!isCollapsed">Интеграции</span>
            </div>
            <svg v-if="!isCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': menuGroups.integrations }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div v-if="menuGroups.integrations && !isCollapsed" class="ml-3 mt-1 space-y-1">
            <router-link v-if="telegramIntegrationInstalled" to="/integrations/telegram" v-slot="{ isActive }" custom>
              <a @click="$router.push('/integrations/telegram'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.693-1.653-1.124-2.678-1.8-1.185-.781-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.008-1.252-.241-1.865-.44-.752-.244-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635.099-.001.321.023.465.141.121.099.155.232.171.326.016.094.036.307.02.473z"/></svg>
                Telegram
              </a>
            </router-link>
            <router-link v-if="yookassaIntegrationInstalled" to="/integrations/yookassa" v-slot="{ isActive }" custom>
              <a @click="$router.push('/integrations/yookassa'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                ЮКassa
              </a>
            </router-link>
          </div>
        </div>

        <!-- Settings Group (only for super_admin and administrator) -->
        <div v-if="canAccessSettings" class="mb-1">
          <button @click="toggleMenuGroup('settings')" class="w-full flex items-center px-3 py-2.5 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors" :class="isCollapsed ? 'justify-center' : 'justify-between'" :title="isCollapsed ? 'Настройки' : ''">
            <div class="flex items-center">
              <svg class="w-5 h-5" :class="isCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <span v-if="!isCollapsed">Настройки</span>
            </div>
            <svg v-if="!isCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': menuGroups.settings }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div v-if="menuGroups.settings && !isCollapsed" class="ml-3 mt-1 space-y-1">
            <router-link to="/settings" v-slot="{ isActive }" custom>
              <a @click="$router.push('/settings'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                Настройки панели
              </a>
            </router-link>
            <router-link to="/environment" v-slot="{ isActive }" custom>
              <a @click="$router.push('/environment'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Окружение
              </a>
            </router-link>
            <router-link v-if="showModules" to="/modules" v-slot="{ isActive }" custom>
              <a @click="$router.push('/modules'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Модули
              </a>
            </router-link>
            <router-link v-if="loggingModuleInstalled" to="/logs" v-slot="{ isActive }" custom>
              <a @click="$router.push('/logs'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Логи
              </a>
            </router-link>
            <router-link to="/administrators" v-slot="{ isActive }" custom>
              <a @click="$router.push('/administrators'); isMobileMenuOpen = false" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors cursor-pointer" :class="isActive ? 'text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white'" :style="isActive ? `background-color: ${themeColor}` : ''">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Администраторы
              </a>
            </router-link>
          </div>
        </div>
      </nav>

      <!-- User Menu -->
      <div class="p-3 border-t border-gray-800">
        <div v-if="!isCollapsed" class="flex items-center p-3 rounded-md bg-gray-800 mb-2">
          <div class="w-9 h-9 rounded-full bg-gray-700 flex items-center justify-center font-semibold text-sm mr-3">
            {{ userInitials }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-medium text-sm text-white truncate">{{ adminUser.name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ roleLabel }}</p>
          </div>
        </div>
        <div v-else class="flex justify-center mb-2">
          <div class="w-9 h-9 rounded-full bg-gray-700 flex items-center justify-center font-semibold text-sm">
            {{ userInitials }}
          </div>
        </div>

        <button
          @click="logout"
          class="w-full flex items-center px-3 py-2 bg-gray-800 hover:bg-gray-700 rounded-md transition text-sm font-medium"
          :class="isCollapsed ? 'justify-center' : 'justify-center'"
          :title="isCollapsed ? 'Выйти' : ''"
        >
          <svg class="w-4 h-4" :class="isCollapsed ? '' : 'mr-2'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          <span v-if="!isCollapsed">Выйти</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
      <!-- Header -->
      <header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 lg:px-6 flex items-center justify-between">
        <!-- Mobile Menu Button -->
        <button
          @click="isMobileMenuOpen = !isMobileMenuOpen"
          class="lg:hidden p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition"
        >
          <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>

        <GlobalSearch class="hidden lg:block flex-1 max-w-md" />
        <div class="lg:hidden flex-1"></div>

        <!-- Actions -->
        <div class="flex items-center space-x-3">
          <!-- Settings Button (only for super_admin and administrator) -->
          <router-link v-if="canAccessSettings" to="/settings">
            <button class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Настройки">
              <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </button>
          </router-link>

          <!-- Theme Toggle -->
          <button
            @click="toggleTheme"
            class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            title="Сменить тему"
          >
            <svg v-if="!isDark" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg v-else class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
          </button>

          <!-- User Menu -->
          <div class="relative">
            <button
              @click="showUserMenu = !showUserMenu"
              class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            >
              <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center font-semibold text-sm text-white">
                {{ userInitials }}
              </div>
              <span class="hidden md:block text-sm font-medium text-gray-900 dark:text-white">{{ adminUser.name }}</span>
              <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <!-- Dropdown -->
            <div
              v-if="showUserMenu"
              @click.away="showUserMenu = false"
              class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-2 z-50"
            >
              <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ adminUser.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ adminUser.email }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ roleLabel }}</p>
              </div>
              <button
                @click="editProfile"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
              >
                Редактировать профиль
              </button>
              <button
                @click="logout"
                class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
              >
                Выйти
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Content Area -->
      <div class="flex-1 p-6 overflow-auto">
        <router-view></router-view>
      </div>
    </main>

    <!-- Global Modal -->
    <Modal
      v-model="modalState.show"
      :type="modalState.type"
      :title="modalState.title"
      :message="modalState.message"
      :confirmText="modalState.confirmText"
      :cancelText="modalState.cancelText"
      :showCancel="modalState.showCancel"
      @confirm="modalState.onConfirm"
      @cancel="modalState.onCancel"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Modal from './components/Modal.vue';
import GlobalSearch from './components/GlobalSearch.vue';
import { useModal } from './composables/useModal';
import { useTheme } from './composables/useTheme';
import { useModuleEvents } from './composables/useModuleEvents';

const { modalState, success, error } = useModal();
const { themeColor: globalThemeColor, setThemeColor } = useTheme();
const { moduleUpdateCounter } = useModuleEvents();

const isDark = ref(false);
const isCollapsed = ref(false);
const isMobileMenuOpen = ref(false);
const showUserMenu = ref(false);
const panelName = ref('HolartCMS');
const themeColor = globalThemeColor; // Use global theme color
const menuGroups = ref({
  content: false,
  callback: false,
  commerce: false,
  infoblocks: false,
  pages_seo: false,
  integrations: false,
});

const adminUser = ref({
  name: 'Администратор',
  email: 'admin@example.com',
  role: 'super_admin'
});

const shopModuleInstalled = ref(false);
const callbackModuleInstalled = ref(false);
const commerceModuleInstalled = ref(false);
const infoblocksModuleInstalled = ref(false);
const loggingModuleInstalled = ref(false);
const seoModuleInstalled = ref(false);
const pageBuilderModuleInstalled = ref(false);
const showModules = ref(true);
const telegramIntegrationInstalled = ref(false);
const yookassaIntegrationInstalled = ref(false);
const favoriteInfoBlocks = ref([]);

const roleLabel = computed(() => {
  const roles = {
    'super_admin': 'Супер администратор',
    'administrator': 'Администратор',
    'manager': 'Менеджер'
  };
  return roles[adminUser.value.role] || 'Пользователь';
});

const userInitials = computed(() => {
  const names = adminUser.value.name.split(' ');
  return names.map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const canAccessSettings = computed(() => {
  return adminUser.value.role === 'super_admin' || adminUser.value.role === 'administrator';
});

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value;
  localStorage.setItem('holart-cms-sidebar', isCollapsed.value ? 'collapsed' : 'expanded');
};

const toggleMenuGroup = (group) => {
  menuGroups.value[group] = !menuGroups.value[group];
};

const toggleTheme = () => {
  isDark.value = !isDark.value;
  localStorage.setItem('holart-cms-theme', isDark.value ? 'dark' : 'light');

  if (isDark.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
};

const editProfile = () => {
  showUserMenu.value = false;
  // TODO: Implement profile editing modal or page
  success('Функция редактирования профиля будет реализована позже');
};

const logout = async () => {
  showUserMenu.value = false;
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  try {
    const response = await fetch('/admin/logout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      }
    });

    if (response.ok || response.redirected) {
      window.location.href = '/admin/login';
    }
  } catch (error) {
    console.error('Logout error:', error);
    window.location.href = '/admin/login';
  }
};

const loadCurrentUser = async () => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/me', {
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      const user = await response.json();
      adminUser.value = user;
    }
  } catch (error) {
    console.error('Failed to load current user:', error);
  }
};

const loadSettings = async () => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/settings', {
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      const settings = await response.json();
      panelName.value = settings.panel_name || 'HolartCMS';

      if (settings.theme_color) {
        const colorMap = {
          red: '#ef4444',
          blue: '#3b82f6',
          green: '#10b981',
          purple: '#a855f7',
          orange: '#f97316',
          pink: '#ec4899'
        };
        const color = colorMap[settings.theme_color] || '#ef4444';
        themeColor.value = color;
        setThemeColor(color);
      }
    }
  } catch (error) {
    console.error('Failed to load settings:', error);
  }
};

const loadModulesStatus = async () => {
  try {
    const response = await fetch('/admin/api/modules/status', {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      const data = await response.json();
      showModules.value = data.show_modules_page || false;

      const shopModule = data.modules?.find(m => m.id === 'shop');
      shopModuleInstalled.value = shopModule?.installed || false;

      const callbackModule = data.modules?.find(m => m.id === 'callback');
      callbackModuleInstalled.value = callbackModule?.installed || false;

      const commerceModule = data.modules?.find(m => m.id === 'commerce');
      commerceModuleInstalled.value = commerceModule?.installed || false;

      const infoblocksModule = data.modules?.find(m => m.id === 'infoblocks');
      infoblocksModuleInstalled.value = infoblocksModule?.installed || false;

      const loggingModule = data.modules?.find(m => m.id === 'logging');
      loggingModuleInstalled.value = loggingModule?.installed || false;

      const seoModule = data.modules?.find(m => m.id === 'seo');
      seoModuleInstalled.value = seoModule?.installed || false;

      const pageBuilderModule = data.modules?.find(m => m.id === 'pagebuilder');
      pageBuilderModuleInstalled.value = pageBuilderModule?.installed || false;

      const telegramIntegration = data.modules?.find(m => m.id === 'telegram');
      telegramIntegrationInstalled.value = telegramIntegration?.installed || false;

      const yookassaIntegration = data.modules?.find(m => m.id === 'yookassa');
      yookassaIntegrationInstalled.value = yookassaIntegration?.installed || false;

      // Load favorite infoblocks if module is installed
      if (infoblocksModuleInstalled.value) {
        loadFavoriteInfoBlocks();
      }
    }
  } catch (error) {
    console.error('Failed to load modules status:', error);
  }
};

const loadFavoriteInfoBlocks = async () => {
  try {
    const response = await fetch('/admin/api/infoblocks/favorites', {
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      favoriteInfoBlocks.value = await response.json();
    }
  } catch (error) {
    console.error('Failed to load favorite infoblocks:', error);
  }
};

onMounted(() => {
  const savedTheme = localStorage.getItem('holart-cms-theme');
  isDark.value = savedTheme === 'dark';

  if (isDark.value) {
    document.documentElement.classList.add('dark');
  }

  const savedSidebar = localStorage.getItem('holart-cms-sidebar');
  isCollapsed.value = savedSidebar === 'collapsed';

  loadCurrentUser();
  loadSettings();
  loadModulesStatus();

  // Listen for favorites update event
  window.addEventListener('infoblocks-favorites-updated', loadFavoriteInfoBlocks);
});

// Watch for module updates and reload sidebar
watch(moduleUpdateCounter, () => {
  loadModulesStatus();
});
</script>

<style scoped>
/* Nova-inspired dark sidebar */
</style>
