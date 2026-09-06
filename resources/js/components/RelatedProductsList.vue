<template>
  <div>
    <!-- Selected companion products -->
    <div v-if="links.length" class="space-y-2 mb-3">
      <div
        v-for="(link, idx) in links"
        :key="`${link.related_product_id}-${link.related_variant_sku || ''}`"
        class="flex items-center gap-3 p-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg"
      >
        <img
          v-if="link.related_product?.main_image"
          :src="imageUrl(link.related_product.main_image)"
          class="w-10 h-10 object-cover rounded border border-gray-200 dark:border-gray-500 shrink-0"
        >
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ link.related_product?.name || `#${link.related_product_id}` }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ link.related_product?.sku }}<span v-if="link.related_product?.price"> · {{ link.related_product.price }} ₽</span>
          </p>
        </div>

        <!-- optional: pick a specific variant of the companion -->
        <select
          v-if="variantOptions(link).length"
          :value="link.related_variant_sku || ''"
          @change="updateLink(idx, 'related_variant_sku', $event.target.value || null)"
          class="text-xs px-2 py-1 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white"
        >
          <option value="">Любой вариант</option>
          <option v-for="vo in variantOptions(link)" :key="vo.sku" :value="vo.sku">{{ vo.name }}</option>
        </select>

        <input
          type="number"
          :value="link.sort ?? 500"
          @input="updateLink(idx, 'sort', Number($event.target.value) || 500)"
          class="w-16 text-xs px-2 py-1 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded text-gray-900 dark:text-white"
          title="Порядок"
        >
        <button type="button" @click="removeLink(idx)" class="text-red-600 hover:text-red-700 dark:text-red-400 shrink-0 px-2">✕</button>
      </div>
    </div>

    <!-- Search + add -->
    <div class="flex gap-2">
      <div class="relative flex-1">
        <input
          v-model="query"
          @input="search"
          type="text"
          placeholder="Найти товар для добавления…"
          class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white"
        >
        <div
          v-if="results.length"
          class="absolute z-10 mt-1 w-full max-h-56 overflow-y-auto bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg"
        >
          <button
            v-for="p in results"
            :key="p.id"
            type="button"
            @click="addLink(p)"
            :disabled="isAdded(p.id)"
            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-40 text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-600 last:border-0"
          >
            {{ p.name }} <span class="text-xs text-gray-400">{{ p.sku }}</span>
          </button>
        </div>
      </div>
      <button type="button" @click="$emit('create')" class="px-3 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded whitespace-nowrap">
        + Создать
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  links: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:links', 'create']);

const query = ref('');
const results = ref([]);
let timer = null;

const imageUrl = (s) => {
  if (!s) return '';
  if (s.startsWith('data:') || s.startsWith('http') || s.startsWith('/')) return s;
  return `/storage/${s}`;
};

const isAdded = (id) => props.links.some((l) => l.related_product_id === id);

const variantOptions = (link) => {
  const v = link.related_product?.variants;
  return Array.isArray(v) ? v.filter((x) => x && x.sku).map((x) => ({ sku: x.sku, name: x.name || x.sku })) : [];
};

const search = () => {
  clearTimeout(timer);
  timer = setTimeout(async () => {
    if (query.value.trim().length < 2) { results.value = []; return; }
    try {
      const res = await fetch(`/admin/api/products/search?q=${encodeURIComponent(query.value)}`, { headers: { Accept: 'application/json' } });
      if (res.ok) {
        const data = await res.json();
        results.value = (data.products || []).slice(0, 20);
      }
    } catch (e) { results.value = []; }
  }, 300);
};

const addLink = (p) => {
  if (isAdded(p.id)) return;
  emit('update:links', [
    ...props.links,
    {
      related_product_id: p.id,
      related_variant_sku: null,
      sort: 500,
      related_product: { id: p.id, name: p.name, sku: p.sku, price: p.price, main_image: p.main_image, variants: p.variants || [] },
    },
  ]);
  query.value = '';
  results.value = [];
};

const updateLink = (idx, key, value) => {
  const next = props.links.map((l, i) => (i === idx ? { ...l, [key]: value } : l));
  emit('update:links', next);
};

const removeLink = (idx) => {
  emit('update:links', props.links.filter((_, i) => i !== idx));
};
</script>
