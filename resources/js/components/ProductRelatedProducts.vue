<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Сопутствующие товары</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Товары, которые продаются вместе с этим (доборы, фурнитура, комплектующие). Можно задать общий список и отдельные списки для вариантов.
      </p>

      <RelatedList
        :links="productLinks"
        @update:links="$emit('update:productLinks', $event)"
        @create="openCreate(null)"
      />
    </div>

    <div
      v-for="variant in variantList"
      :key="variant.sku"
      class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6"
    >
      <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
        Для варианта: {{ variant.name }} <span class="text-xs text-gray-400 font-mono">{{ variant.sku }}</span>
      </h4>
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Дополняет общий список выше.</p>

      <RelatedList
        :links="variantLinks[variant.sku] || []"
        @update:links="setVariantLinks(variant.sku, $event)"
        @create="openCreate(variant.sku)"
      />
    </div>

    <p v-if="variantList.length === 0" class="text-xs text-gray-400 dark:text-gray-500">
      Добавьте варианты на вкладке «Варианты», чтобы задать для них отдельные сопутствующие товары.
    </p>

    <!-- Inline create modal -->
    <div v-if="createModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/50" @click="createModal.open = false"></div>
      <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Создать сопутствующий товар</h3>
        <div class="grid grid-cols-1 gap-3">
          <input v-model="createModal.name" placeholder="Название *" class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white">
          <div class="grid grid-cols-2 gap-3">
            <input v-model="createModal.sku" placeholder="Артикул *" class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white font-mono">
            <input v-model.number="createModal.price" type="number" placeholder="Цена *" class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white">
          </div>
          <select v-model.number="createModal.catalog_id" class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-900 dark:text-white">
            <option :value="null">Категория *</option>
            <option v-for="c in catalogs" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="createModal.open = false" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded">Отмена</button>
          <button type="button" :disabled="createModal.saving" @click="submitCreate" class="px-4 py-2 text-sm bg-blue-600 text-white rounded disabled:opacity-50">
            {{ createModal.saving ? 'Создание…' : 'Создать и добавить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import RelatedList from './RelatedProductsList.vue';

const props = defineProps({
  productLinks: { type: Array, default: () => [] },
  variantLinks: { type: Object, default: () => ({}) },
  variants: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:productLinks', 'update:variantLinks']);

const catalogs = ref([]);
const createModal = ref({ open: false, targetSku: null, name: '', sku: '', price: null, catalog_id: null, saving: false });

const variantList = computed(() =>
  (props.variants || [])
    .filter((v) => v && v.sku)
    .map((v) => ({ sku: v.sku, name: v.name || v.sku }))
);

const setVariantLinks = (sku, links) => {
  emit('update:variantLinks', { ...props.variantLinks, [sku]: links });
};

const openCreate = (targetSku) => {
  createModal.value = { open: true, targetSku, name: '', sku: '', price: null, catalog_id: catalogs.value[0]?.id ?? null, saving: false };
};

const submitCreate = async () => {
  const m = createModal.value;
  if (!m.name || !m.sku || !m.price || !m.catalog_id) {
    alert('Заполните название, артикул, цену и категорию');
    return;
  }
  m.saving = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const res = await fetch('/admin/api/products', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      body: JSON.stringify({ name: m.name, sku: m.sku, price: m.price, catalog_id: m.catalog_id, is_active: true }),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.message || 'Не удалось создать товар');
    }
    const product = await res.json();
    const link = {
      related_product_id: product.id,
      related_variant_sku: null,
      sort: 500,
      related_product: { id: product.id, name: product.name, sku: product.sku, price: product.price, main_image: product.main_image },
    };
    if (m.targetSku) {
      setVariantLinks(m.targetSku, [...(props.variantLinks[m.targetSku] || []), link]);
    } else {
      emit('update:productLinks', [...props.productLinks, link]);
    }
    createModal.value.open = false;
  } catch (e) {
    alert(e.message);
  } finally {
    m.saving = false;
  }
};

onMounted(async () => {
  try {
    const res = await fetch('/admin/api/catalogs/list', { headers: { Accept: 'application/json' } });
    if (res.ok) catalogs.value = await res.json();
  } catch (e) { /* ignore */ }
});
</script>
