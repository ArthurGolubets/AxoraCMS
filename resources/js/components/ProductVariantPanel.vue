<template>
  <SidePanel :title="isNew ? 'Новый вариант' : 'Редактировать вариант'" width-class="max-w-3xl" @close="$emit('close')">
    <form id="product-variant-form" @submit.prevent="apply" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label :class="labelClass">Название *</label>
          <input ref="nameInput" v-model="draft.name" required :class="inputClass">
        </div>
        <div>
          <label :class="labelClass">Артикул (SKU) *</label>
          <input v-model="draft.sku" required :class="[inputClass, 'font-mono']">
        </div>
        <div>
          <label :class="labelClass">Цена *</label>
          <input v-model.number="draft.price" type="number" step="0.01" min="0" required :class="inputClass">
        </div>
        <div>
          <label :class="labelClass">Старая цена</label>
          <input v-model.number="draft.old_price" type="number" step="0.01" min="0" :class="inputClass">
        </div>
      </div>

      <div>
        <label :class="labelClass">Изображение варианта</label>
        <ImageUpload v-model="draft.image" />
      </div>

      <div>
        <label :class="labelClass">Описание</label>
        <textarea v-model="draft.description" rows="3" :class="inputClass"></textarea>
      </div>

      <div v-if="availableProperties.length" class="border-t border-gray-200 dark:border-gray-700 pt-5">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Свойства варианта</h4>
        <ProductPropertiesForm
          :available-properties="availableProperties"
          :initial-values="draft.property_values || {}"
          @update:values="(values) => { draft.property_values = values; }"
        />
      </div>

      <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Характеристики варианта</h4>
        <ProductCharacteristics v-model="draft.addition_info" applies-to="variant" />
      </div>

      <div v-if="relatedEnabled" class="border-t border-gray-200 dark:border-gray-700 pt-5">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Сопутствующие товары варианта</h4>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Отдельный список для этого варианта (доборы, фурнитура и т.п.)</p>
        <RelatedProductsList :links="links" @update:links="links = $event" />
      </div>
    </form>

    <template #footer>
      <div class="flex justify-end gap-3">
        <button type="button" @click="$emit('close')" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
          Отмена
        </button>
        <ThemeButton type="submit" form="product-variant-form" variant="primary">
          {{ isNew ? 'Добавить вариант' : 'Применить' }}
        </ThemeButton>
      </div>
      <p class="mt-2 text-xs text-right text-gray-500 dark:text-gray-400">Изменения сохранятся вместе с товаром</p>
    </template>
  </SidePanel>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue';
import SidePanel from './SidePanel.vue';
import ThemeButton from './ThemeButton.vue';
import ImageUpload from './ImageUpload.vue';
import ProductPropertiesForm from './ProductPropertiesForm.vue';
import ProductCharacteristics from './ProductCharacteristics.vue';
import RelatedProductsList from './RelatedProductsList.vue';
import { useModal } from '../composables/useModal';

/**
 * Edits a copy of a product variant; "apply" hands the result back to the
 * product form, which persists it together with the product.
 */
const props = defineProps({
  variant: { type: Object, required: true },
  isNew: { type: Boolean, default: false },
  availableProperties: { type: Array, default: () => [] },
  relatedEnabled: { type: Boolean, default: false },
  relatedLinks: { type: Array, default: () => [] },
  // SKUs of the other variants of this product (must stay unique).
  takenSkus: { type: Array, default: () => [] },
});

const emit = defineEmits(['apply', 'close']);

const { error } = useModal();

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const clone = (value) => JSON.parse(JSON.stringify(value ?? null));

const draft = ref({
  ...clone(props.variant),
  property_values: clone(props.variant.property_values) || {},
  addition_info: clone(props.variant.addition_info) || {},
});
const links = ref(clone(props.relatedLinks) || []);
const nameInput = ref(null);

const apply = () => {
  const sku = String(draft.value.sku || '').trim();
  if (props.takenSkus.includes(sku)) {
    error(`Артикул «${sku}» уже есть у другого варианта этого товара`);
    return;
  }
  emit('apply', { variant: { ...draft.value, sku }, links: links.value });
};

onMounted(() => {
  if (props.isNew) nextTick(() => nameInput.value?.focus());
});
</script>
