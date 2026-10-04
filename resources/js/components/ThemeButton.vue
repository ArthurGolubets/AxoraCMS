<template>
  <button
    :type="type"
    :disabled="disabled"
    :class="computedClass"
    :style="buttonStyle"
    @click="$emit('click', $event)"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { useTheme } from '../composables/useTheme';

const props = defineProps({
  type: {
    type: String,
    default: 'button'
  },
  variant: {
    type: String,
    default: 'primary', // primary, secondary, success, danger
    validator: (value) => ['primary', 'secondary', 'success', 'danger'].includes(value)
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

defineEmits(['click']);

const { buttonStyle: themeStyle } = useTheme();

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2',
  lg: 'px-6 py-3 text-lg'
};

const computedClass = computed(() => {
  const baseClass = 'font-medium rounded-lg transition-all inline-flex items-center justify-center';
  const sizeClass = sizeClasses[props.size];
  const disabledClass = props.disabled ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90';

  let variantClass = '';
  if (props.variant === 'secondary') {
    variantClass = 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white';
  } else if (props.variant === 'success') {
    variantClass = 'bg-green-600 hover:bg-green-700 text-white';
  } else if (props.variant === 'danger') {
    variantClass = 'bg-red-600 hover:bg-red-700 text-white';
  } else {
    variantClass = 'text-white';
  }

  return `${baseClass} ${sizeClass} ${variantClass} ${disabledClass}`;
});

const buttonStyle = computed(() => {
  if (props.variant === 'primary' && !props.disabled) {
    return themeStyle.value;
  }
  return {};
});
</script>
