<template>
  <form @submit.prevent="save" class="space-y-6">
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Каталог и товары</h3>

      <div class="space-y-5">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Список товаров</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Показывать пункт меню «Список товаров». Отключите, если пользуетесь только деревом каталога.</p>
          </div>
          <ToggleSwitch v-model="settings.products_list_enabled" :theme-color="themeColor" />
        </div>

        <div class="flex items-start justify-between gap-4 border-t border-gray-100 dark:border-gray-700 pt-5">
          <div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Варианты товаров</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Показывать вкладку «Варианты» в карточке товара.</p>
          </div>
          <ToggleSwitch v-model="settings.product_variants_enabled" :theme-color="themeColor" />
        </div>

        <div class="flex items-start justify-between gap-4 border-t border-gray-100 dark:border-gray-700 pt-5">
          <div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Сопутствующие товары</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Вкладка «Сопутствующие товары» в карточке товара и варианта — наборы, доборы, фурнитура и т.п.</p>
          </div>
          <ToggleSwitch v-model="settings.related_products_enabled" :theme-color="themeColor" />
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700 pt-5">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Если артикул (SKU) уже занят при создании товара</p>
          <p class="mt-1 mb-3 text-xs text-gray-500 dark:text-gray-400">Когда действие выбрано, окно с вопросом при создании товара не показывается.</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <label
              v-for="option in DUPLICATE_SKU_OPTIONS"
              :key="option.value"
              class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
              :class="settings.duplicate_sku_action === option.value
                ? 'border-transparent ring-2 bg-gray-50 dark:bg-gray-700/60'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
              :style="settings.duplicate_sku_action === option.value ? { '--tw-ring-color': themeColor } : {}"
            >
              <input v-model="settings.duplicate_sku_action" type="radio" :value="option.value" class="mt-1">
              <span>
                <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ option.label }}</span>
                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ option.description }}</span>
              </span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Stock (only with the CommerceML integration) -->
    <div v-if="hasCommerceMl" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Товары и остатки</h3>
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Можно редактировать остаток</p>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Если включено — поле «Остаток» в карточке товара доступно для ручного редактирования.
            Если выключено — остаток только для просмотра (управляется интеграцией).
          </p>
        </div>
        <ToggleSwitch v-model="settings.can_edit_product_stock" :theme-color="themeColor" />
      </div>
    </div>

    <div class="flex justify-end">
      <ThemeButton type="submit" variant="primary" :disabled="saving">
        {{ saving ? 'Сохранение...' : 'Сохранить настройки' }}
      </ThemeButton>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ToggleSwitch from './ToggleSwitch.vue';
import ThemeButton from './ThemeButton.vue';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';
import { useAppConfig } from '../composables/useAppConfig';

/**
 * Catalog & product settings (Контент → Настройки → «Каталог и товары»).
 */
const DUPLICATE_SKU_OPTIONS = [
  { value: '', label: 'Не задано', description: 'Спрашивать каждый раз: открыть существующий товар или добавить к артикулу номер' },
  { value: 'edit', label: 'Редактировать', description: 'Открыть существующий товар с этим артикулом вместо создания нового' },
  { value: 'prefix', label: 'Добавлять номер автоматически', description: 'Создать товар с артикулом вида ABC-2, ABC-3…' },
  { value: 'skip', label: 'Пропускать', description: 'Создать товар с тем же артикулом' },
];

const { success, error } = useModal();
const { themeColor } = useTheme();
const appConfig = useAppConfig();

const saving = ref(false);
const hasCommerceMl = ref(false);
const settings = ref({
  products_list_enabled: true,
  product_variants_enabled: true,
  related_products_enabled: false,
  can_edit_product_stock: false,
  duplicate_sku_action: '',
});

const load = async () => {
  const [data] = await Promise.all([appConfig.loadSettings(), appConfig.loadModulesStatus()]);
  hasCommerceMl.value = appConfig.isModuleInstalled('commerceml');
  if (!data) return;

  settings.value = {
    products_list_enabled: data.products_list_enabled !== false,
    product_variants_enabled: data.product_variants_enabled !== false,
    related_products_enabled: data.related_products_enabled === true,
    can_edit_product_stock: data.can_edit_product_stock === true,
    duplicate_sku_action: data.duplicate_sku_action || '',
  };
};

const save = async () => {
  saving.value = true;
  try {
    const payload = { ...settings.value };
    if (!hasCommerceMl.value) delete payload.can_edit_product_stock;

    const response = await fetch('/admin/api/settings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        Accept: 'application/json',
      },
      body: JSON.stringify(payload),
    });
    if (!response.ok) throw new Error();

    await appConfig.refreshSettings();
    success('Настройки каталога сохранены');
  } catch (e) {
    error('Ошибка при сохранении настроек');
  } finally {
    saving.value = false;
  }
};

onMounted(load);
</script>
