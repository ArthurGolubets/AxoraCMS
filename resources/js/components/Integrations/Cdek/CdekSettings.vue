<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Настройки СДЭК</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Заказы с доставкой СДЭК оформляются в фоне, трек-номер и статус появляются в карточке заказа</p>
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <form v-else class="space-y-6" @submit.prevent="save">
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Подключение</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelClass">Client ID (Account) *</label>
            <input v-model="form.client_id" type="text" autocomplete="off" :class="inputClass">
          </div>
          <div>
            <label :class="labelClass">Client Secret (Secure password) {{ form.has_secret ? '' : '*' }}</label>
            <input v-model="form.client_secret" type="password" autocomplete="new-password" :class="inputClass" :placeholder="form.has_secret ? 'Сохранён. Заполните, чтобы заменить' : ''">
          </div>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Ключи выдаются в личном кабинете СДЭК: «Интеграция» → «Ключи для API». Secret хранится в зашифрованном виде.</p>
        <ToggleSwitch v-model="form.test_mode" :theme-color="themeColor" label="Тестовый режим (заказы не уходят в реальную доставку)" />
        <div>
          <ThemeButton variant="secondary" :disabled="testing || !form.client_id" @click="test">{{ testing ? 'Проверка…' : 'Проверить подключение' }}</ThemeButton>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Откуда отправлять</h3>
        <div>
          <label :class="labelClass">Код пункта приёма СДЭК</label>
          <input v-model="form.shipment_point" type="text" :class="inputClass" placeholder="Например: MSK123">
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Если сдаёте заказы в пункт СДЭК. Иначе укажите город и адрес ниже: курьер заберёт их сам, для этого нужен подходящий тариф</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelClass">Город склада</label>
            <CdekCityPicker :placeholder="form.from_city_code ? `Код города: ${form.from_city_code}` : undefined" @select="form.from_city_code = $event.code" />
          </div>
          <div>
            <label :class="labelClass">Адрес склада</label>
            <input v-model="form.from_address" type="text" :class="inputClass">
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Тарифы и посылка</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="labelClass">Код тарифа до пункта выдачи *</label>
            <input v-model.number="form.tariff_pvz" type="number" min="1" :class="inputClass">
          </div>
          <div>
            <label :class="labelClass">Код тарифа до двери *</label>
            <input v-model.number="form.tariff_door" type="number" min="1" :class="inputClass">
          </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label :class="labelClass">Вес единицы товара, г *</label>
            <input v-model.number="form.default_weight" type="number" min="1" :class="inputClass">
          </div>
          <div>
            <label :class="labelClass">Длина, см *</label>
            <input v-model.number="form.default_length" type="number" min="1" :class="inputClass">
          </div>
          <div>
            <label :class="labelClass">Ширина, см *</label>
            <input v-model.number="form.default_width" type="number" min="1" :class="inputClass">
          </div>
          <div>
            <label :class="labelClass">Высота, см *</label>
            <input v-model.number="form.default_height" type="number" min="1" :class="inputClass">
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Оформление</h3>
        <ToggleSwitch v-model="form.auto_create" :theme-color="themeColor" label="Оформлять отправление сразу при создании заказа" />
        <p class="text-sm text-gray-500 dark:text-gray-400">Если выключено, менеджер оформляет отправление кнопкой в карточке заказа.</p>
        <ToggleSwitch v-model="form.cash_on_delivery" :theme-color="themeColor" label="Брать оплату при получении за неоплаченные заказы" />
        <p class="text-sm text-gray-500 dark:text-gray-400">Если выключено, СДЭК выдаёт заказ без оплаты, даже когда он ещё не оплачен.</p>
      </div>

      <div class="flex justify-end">
        <ThemeButton type="submit" variant="primary" :disabled="saving">{{ saving ? 'Сохранение…' : 'Сохранить' }}</ThemeButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ThemeButton from '../../ThemeButton.vue';
import ToggleSwitch from '../../ToggleSwitch.vue';
import CdekCityPicker from './CdekCityPicker.vue';
import { useModal } from '../../../composables/useModal';
import { useTheme } from '../../../composables/useTheme';
import { cdekApi } from '../../../composables/useCdekDelivery';

const { success, error } = useModal();
const { themeColor } = useTheme();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const loading = ref(true);
const saving = ref(false);
const testing = ref(false);
const form = ref({});

const load = async () => {
  try {
    form.value = { ...(await cdekApi('GET', '')), client_secret: '' };
  } catch (e) {
    await error(e.message);
  } finally {
    loading.value = false;
  }
};

const test = async () => {
  testing.value = true;
  try {
    const { client_id, client_secret, test_mode } = form.value;
    await success((await cdekApi('POST', '/test', { client_id, client_secret, test_mode })).message);
  } catch (e) {
    await error(e.message);
  } finally {
    testing.value = false;
  }
};

const save = async () => {
  saving.value = true;
  try {
    await cdekApi('POST', '', form.value);
    await load();
    await success('Настройки СДЭК сохранены');
  } catch (e) {
    await error(e.message);
  } finally {
    saving.value = false;
  }
};

onMounted(load);
</script>
