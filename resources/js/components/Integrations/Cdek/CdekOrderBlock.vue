<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">СДЭК</h3>

    <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">Загрузка...</p>

    <div v-else-if="isLive" class="space-y-3">
      <div>
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Трек-номер</label>
        <p class="text-gray-900 dark:text-white">
          <a v-if="shipment.track_number" :href="shipment.tracking_url" target="_blank" rel="noopener" class="text-blue-600 hover:underline dark:text-blue-400">{{ shipment.track_number }}</a>
          <span v-else class="text-gray-500 dark:text-gray-400">Ожидаем номер от СДЭК…</span>
        </p>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Статус доставки</label>
        <p class="text-gray-900 dark:text-white">{{ shipment.status || (shipment.state === 'queued' ? 'В очереди на отправку' : 'Передан в СДЭК') }}</p>
        <p v-if="shipment.status_at" class="text-xs text-gray-500 dark:text-gray-400">{{ shipment.status_at }}</p>
      </div>
      <ThemeButton v-if="shipment.state !== 'queued'" variant="secondary" size="sm" :disabled="busy" @click="refresh">{{ busy ? 'Обновление…' : 'Обновить статус' }}</ThemeButton>
    </div>

    <div v-else class="space-y-3">
      <p v-if="shipment?.error" class="text-sm text-red-600 dark:text-red-400">{{ shipment.error }}</p>

      <div>
        <label :class="labelClass">Город</label>
        <CdekCityPicker :placeholder="form.city_code ? `Код города: ${form.city_code}` : undefined" @select="onCity" />
      </div>
      <div v-if="points.length">
        <label :class="labelClass">Пункт выдачи</label>
        <select v-model="form.delivery_point" :class="inputClass">
          <option value="">Доставка до двери</option>
          <option v-for="point in points" :key="point.code" :value="point.code">{{ point.name }}</option>
        </select>
      </div>
      <div v-else-if="form.delivery_point">
        <label :class="labelClass">Пункт выдачи</label>
        <input v-model="form.delivery_point" type="text" :class="inputClass">
      </div>
      <div v-if="!form.delivery_point">
        <label :class="labelClass">Адрес доставки</label>
        <input v-model="form.address" type="text" :class="inputClass">
      </div>
      <div>
        <label :class="labelClass">Вес, г</label>
        <input v-model.number="form.weight" type="number" min="1" :class="inputClass" placeholder="По настройкам модуля">
      </div>
      <div class="grid grid-cols-3 gap-2">
        <div>
          <label :class="labelClass">Д, см</label>
          <input v-model.number="form.length" type="number" min="1" :class="inputClass">
        </div>
        <div>
          <label :class="labelClass">Ш, см</label>
          <input v-model.number="form.width" type="number" min="1" :class="inputClass">
        </div>
        <div>
          <label :class="labelClass">В, см</label>
          <input v-model.number="form.height" type="number" min="1" :class="inputClass">
        </div>
      </div>
      <ThemeButton variant="primary" :disabled="busy" @click="create">{{ shipment?.error ? 'Отправить снова' : 'Оформить в СДЭК' }}</ThemeButton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import ThemeButton from '../../ThemeButton.vue';
import CdekCityPicker from './CdekCityPicker.vue';
import { useModal } from '../../../composables/useModal';
import { cdekApi } from '../../../composables/useCdekDelivery';

const POLL_MS = 5000;

const props = defineProps({
  orderId: { type: Number, required: true },
});

const emit = defineEmits(['order-changed']);

const { error } = useModal();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const loading = ref(true);
const busy = ref(false);
const shipment = ref(null);
const points = ref([]);
const form = ref({ city_code: null, delivery_point: '', address: '', weight: null, length: null, width: null, height: null });

const isLive = computed(() => shipment.value && shipment.value.state !== 'error');

// The jobs work in the background: watch them until the track number arrives.
let timer = null;

const stopPolling = () => {
  clearTimeout(timer);
  timer = null;
};

const load = async () => {
  const data = await cdekApi('GET', `/orders/${props.orderId}`);
  // Only a number that arrives while watching changes the order on the page.
  const numberArrived = shipment.value && !shipment.value.track_number && data.shipment?.track_number;
  shipment.value = data.shipment;

  if (!isLive.value) {
    const source = data.shipment || data.defaults;
    form.value = {
      ...form.value,
      ...data.defaults,
      city_code: source.city_code ?? null,
      delivery_point: source.delivery_point || '',
      address: source.address || '',
    };
  }

  if (numberArrived) {
    emit('order-changed');
  }

  stopPolling();

  if (['queued', 'accepted'].includes(data.shipment?.state)) {
    timer = setTimeout(load, POLL_MS);
  }
};

const onCity = async (city) => {
  form.value.city_code = city.code;
  form.value.delivery_point = '';
  try {
    points.value = (await cdekApi('GET', `/points?city_code=${city.code}`)).data;
  } catch (e) {
    points.value = [];
    await error(e.message);
  }
};

const create = async () => {
  busy.value = true;
  try {
    await cdekApi('POST', `/orders/${props.orderId}`, form.value);
    await load();
  } catch (e) {
    await error(e.message);
  } finally {
    busy.value = false;
  }
};

const refresh = async () => {
  busy.value = true;
  try {
    shipment.value = (await cdekApi('POST', `/orders/${props.orderId}/refresh`)).shipment;
    emit('order-changed');
  } catch (e) {
    await error(e.message);
  } finally {
    busy.value = false;
  }
};

onMounted(async () => {
  try {
    await load();
  } catch (e) {
    await error(e.message);
  } finally {
    loading.value = false;
  }
});

onBeforeUnmount(stopPolling);
</script>
