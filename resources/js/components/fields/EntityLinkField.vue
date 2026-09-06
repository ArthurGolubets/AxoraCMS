<template>
  <div>
    <!-- Selected chips -->
    <div v-if="selected.length" class="space-y-2 mb-2">
      <div
        v-for="(item, idx) in selected"
        :key="`${item.type}-${item.infoblock_id || 0}-${item.id}`"
        class="flex items-center justify-between p-2.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg"
      >
        <div class="min-w-0">
          <p class="text-sm font-medium text-blue-900 dark:text-blue-100 truncate">{{ item.title || `#${item.id}` }}</p>
          <p class="text-xs text-blue-700 dark:text-blue-300">{{ typeLabel(item.type) }} · ID: {{ item.id }}</p>
        </div>
        <button type="button" @click="removeAt(idx)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 shrink-0 ml-2">✕</button>
      </div>
    </div>

    <div v-if="isMultiple || selected.length === 0" class="space-y-2 p-3 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg">
      <!-- Entity type -->
      <select
        v-if="effectiveTypes.length > 1"
        v-model="activeType"
        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
      >
        <option v-for="t in effectiveTypes" :key="t" :value="t">{{ typeLabel(t) }}</option>
      </select>

      <!-- InfoBlock picker -->
      <select
        v-if="activeType === 'infoblock' && !lockedInfoblockId"
        v-model.number="activeInfoblockId"
        @change="onSearch"
        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
      >
        <option :value="0">— выберите инфоблок —</option>
        <option v-for="ib in infoBlocks" :key="ib.id" :value="ib.id">{{ ib.name }}</option>
      </select>

      <input
        v-model="search"
        @input="onSearch"
        type="text"
        placeholder="Поиск по названию…"
        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white text-sm"
      >

      <div v-if="loading" class="text-xs text-gray-500 dark:text-gray-400 py-2">Загрузка…</div>
      <div v-else-if="results.length" class="max-h-56 overflow-y-auto border border-gray-200 dark:border-gray-500 rounded">
        <button
          v-for="r in results"
          :key="r.id"
          type="button"
          @click="pick(r)"
          :disabled="isPicked(r.id)"
          class="w-full text-left px-3 py-2 text-sm border-b border-gray-100 dark:border-gray-600 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed text-gray-900 dark:text-white"
        >
          {{ r.name }} <span class="text-xs text-gray-400">#{{ r.id }}</span>
        </button>
      </div>
      <div v-else-if="search.length >= 2" class="text-xs text-gray-500 dark:text-gray-400 py-2">Ничего не найдено</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  isMultiple: { type: Boolean, default: false },
  allowedTypes: { type: Array, default: () => ['product', 'catalog', 'infoblock'] },
  lockedInfoblockId: { type: [Number, null], default: null },
});
const emit = defineEmits(['update:modelValue']);

const LABELS = { product: 'Товар', catalog: 'Категория', infoblock: 'Элемент инфоблока' };
const typeLabel = (t) => LABELS[t] || t;

const selected = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));
const effectiveTypes = computed(() => {
  const allowed = (props.allowedTypes && props.allowedTypes.length) ? props.allowedTypes : ['product', 'catalog', 'infoblock'];
  return allowed.filter((t) => ['product', 'catalog', 'infoblock'].includes(t));
});

const activeType = ref(effectiveTypes.value[0] || 'product');
const activeInfoblockId = ref(props.lockedInfoblockId || 0);
const search = ref('');
const results = ref([]);
const infoBlocks = ref([]);
const loading = ref(false);
let debounce = null;

watch(effectiveTypes, (t) => {
  if (!t.includes(activeType.value)) activeType.value = t[0] || 'product';
});
watch(() => props.lockedInfoblockId, (v) => { if (v) activeInfoblockId.value = v; });
watch(activeType, () => { results.value = []; search.value = ''; if (activeType.value === 'infoblock') loadInfoBlocks(); });

const isPicked = (id) => selected.value.some((s) => s.type === activeType.value && s.id === id
  && (activeType.value !== 'infoblock' || s.infoblock_id === (props.lockedInfoblockId || activeInfoblockId.value)));

async function loadInfoBlocks() {
  if (infoBlocks.value.length) return;
  try {
    const res = await fetch('/admin/api/infoblocks', { headers: { Accept: 'application/json' } });
    if (res.ok) { const d = await res.json(); infoBlocks.value = d.data || d; }
  } catch (e) { console.error(e); }
}

function onSearch() {
  clearTimeout(debounce);
  debounce = setTimeout(fetchResults, 300);
}

async function fetchResults() {
  if (search.value.length < 2) { results.value = []; return; }
  const ib = props.lockedInfoblockId || activeInfoblockId.value;
  if (activeType.value === 'infoblock' && !ib) { results.value = []; return; }
  loading.value = true;
  try {
    let url;
    if (activeType.value === 'product') url = `/admin/api/products?search=${encodeURIComponent(search.value)}`;
    else if (activeType.value === 'catalog') url = `/admin/api/catalogs?search=${encodeURIComponent(search.value)}`;
    else url = `/admin/api/infoblocks/${ib}/elements?search=${encodeURIComponent(search.value)}`;
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (res.ok) {
      const d = await res.json();
      const rows = d.data || d;
      results.value = (Array.isArray(rows) ? rows : []).map((r) => ({ id: r.id, name: r.name })).slice(0, 30);
    }
  } catch (e) { console.error(e); } finally { loading.value = false; }
}

function pick(r) {
  const entry = { type: activeType.value, id: r.id, title: r.name };
  if (activeType.value === 'infoblock') entry.infoblock_id = props.lockedInfoblockId || activeInfoblockId.value;
  const next = props.isMultiple ? [...selected.value, entry] : [entry];
  emit('update:modelValue', next);
  if (!props.isMultiple) { search.value = ''; results.value = []; }
}

function removeAt(idx) {
  const next = [...selected.value];
  next.splice(idx, 1);
  emit('update:modelValue', next);
}

onMounted(() => { if (activeType.value === 'infoblock') loadInfoBlocks(); });
</script>
