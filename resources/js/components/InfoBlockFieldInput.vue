<template>
  <fieldset :disabled="disabled" :class="{ 'pointer-events-none opacity-60': disabled }">
    <InfoBlockMultipleField
      v-if="MULTIPLE_INPUT_TYPES.includes(field.type) && field.is_multiple"
      v-model="value"
      :field-type="field.type"
      :required="isRequired"
    />

    <input v-else-if="field.type === 'string'" v-model="value" type="text" :required="isRequired" :class="inputClass">

    <input v-else-if="field.type === 'email'" v-model="value" type="email" :required="isRequired" :class="inputClass">

    <input v-else-if="field.type === 'phone'" v-model="value" type="tel" :required="isRequired" :class="inputClass">

    <textarea v-else-if="field.type === 'text'" v-model="value" :required="isRequired" rows="4" :class="inputClass"></textarea>

    <input v-else-if="field.type === 'number'" v-model.number="value" type="number" :required="isRequired" :class="inputClass">

    <input v-else-if="field.type === 'double'" v-model.number="value" type="number" step="0.01" :required="isRequired" :class="inputClass">

    <ToggleSwitch v-else-if="field.type === 'bool'" v-model="value" :theme-color="themeColor" :label="value ? 'Да' : 'Нет'" />

    <input v-else-if="field.type === 'date'" v-model="value" type="date" :required="isRequired" :class="inputClass">

    <input v-else-if="field.type === 'datetime'" v-model="value" type="datetime-local" :required="isRequired" :class="inputClass">

    <InfoBlockImageUpload v-else-if="field.type === 'image'" v-model="value" :required="isRequired" :is-multiple="field.is_multiple" />

    <InfoBlockFileUpload v-else-if="field.type === 'file'" v-model="value" :required="isRequired" :is-multiple="field.is_multiple" />

    <InfoBlockEntitySelect
      v-else-if="field.type === 'entity'"
      v-model="value"
      :required="isRequired"
      :entity-type-fixed="field.settings?.entity_type"
      :info-block-id="field.settings?.entity_id ? parseInt(field.settings.entity_id) : null"
      :is-multiple="field.is_multiple"
    />

    <InfoBlockUserSelect v-else-if="field.type === 'user'" v-model="value" :required="isRequired" />

    <select v-else-if="field.type === 'enum' && field.is_multiple" v-model="value" multiple :required="isRequired" :class="inputClass">
      <option v-for="option in (field.settings?.options || [])" :key="option.code" :value="option.code">
        {{ option.title }}
      </option>
    </select>

    <select v-else-if="field.type === 'enum'" v-model="value" :required="isRequired" :class="inputClass">
      <option value="">— Выберите значение —</option>
      <option v-for="option in (field.settings?.options || [])" :key="option.code" :value="option.code">
        {{ option.title }}
      </option>
    </select>

    <InfoBlockButtonField v-else-if="field.type === 'button'" v-model="value" :required="isRequired" />

    <InfoBlockTableField
      v-else-if="field.type === 'table'"
      v-model="value"
      :required="isRequired"
      :rows="field.settings?.rows"
      :cols="field.settings?.cols"
    />

    <input v-else v-model="value" type="text" :required="isRequired" :class="inputClass">
  </fieldset>
</template>

<script setup>
import { computed } from 'vue';
import { useTheme } from '../composables/useTheme';
import { MULTIPLE_INPUT_TYPES } from '../utils/infoBlockFields';
import ToggleSwitch from './ToggleSwitch.vue';
import InfoBlockImageUpload from './InfoBlockImageUpload.vue';
import InfoBlockFileUpload from './InfoBlockFileUpload.vue';
import InfoBlockEntitySelect from './InfoBlockEntitySelect.vue';
import InfoBlockUserSelect from './InfoBlockUserSelect.vue';
import InfoBlockMultipleField from './InfoBlockMultipleField.vue';
import InfoBlockButtonField from './InfoBlockButtonField.vue';
import InfoBlockTableField from './InfoBlockTableField.vue';

/**
 * Value editor for a single info block field, chosen by the field type.
 */
const props = defineProps({
  field: { type: Object, required: true },
  modelValue: { default: null },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const { themeColor } = useTheme();

const inputClass = 'w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white';

const isRequired = computed(() => !props.disabled && !!props.field.is_required);

const value = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});
</script>
