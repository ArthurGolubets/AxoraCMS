<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <!-- Overlay -->
          <div
            @click="close"
            class="fixed inset-0 transition-opacity bg-black bg-opacity-50"
          ></div>

          <!-- Modal -->
          <div
            class="relative z-10 w-full p-6 mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-xl transform transition-all"
            :class="sizeClass"
          >
            <!-- Header slot or title -->
            <div v-if="$slots.header || title" class="mb-4">
              <slot name="header">
                <!-- Icon -->
                <div v-if="type" class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full" :class="iconBgClass">
                  <svg class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    <path v-else-if="type === 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    <path v-else-if="type === 'warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    <path v-else-if="type === 'info'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>

                <!-- Title -->
                <h3 v-if="title" class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ title }}
                </h3>
              </slot>
            </div>

            <!-- Body slot or message -->
            <div v-if="$slots.body || message || $slots.default">
              <slot name="body">
                <!-- Message -->
                <p v-if="message" class="text-gray-600 dark:text-gray-400 mb-6">
                  {{ message }}
                </p>

                <!-- Default slot for custom content -->
                <slot></slot>
              </slot>
            </div>

            <!-- Footer slot or actions -->
            <div v-if="$slots.footer || showCancel || confirmText" class="mt-6">
              <slot name="footer">
                <!-- Actions -->
                <div class="flex space-x-3">
                  <button
                    v-if="showCancel"
                    @click="cancel"
                    type="button"
                    class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg transition"
                  >
                    {{ cancelText }}
                  </button>
                  <button
                    @click="confirm"
                    type="button"
                    class="flex-1 px-4 py-2 text-white rounded-lg transition"
                    :class="confirmButtonClass"
                    :style="confirmButtonStyle"
                  >
                    {{ confirmText }}
                  </button>
                </div>
              </slot>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: true  // Default to true so modals with v-if work
  },
  type: {
    type: String,
    default: 'info', // success, error, warning, info, confirm
    validator: (value) => ['success', 'error', 'warning', 'info', 'confirm'].includes(value)
  },
  size: {
    type: String,
    default: 'medium', // small, medium, large
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  title: {
    type: String,
    default: ''
  },
  message: {
    type: String,
    default: ''
  },
  confirmText: {
    type: String,
    default: 'OK'
  },
  cancelText: {
    type: String,
    default: 'Отмена'
  },
  showCancel: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel', 'close']);

const sizeClass = computed(() => {
  const classes = {
    small: 'max-w-sm',
    medium: 'max-w-md',
    large: 'max-w-4xl'
  };
  return classes[props.size] || classes.medium;
});

import { useTheme } from '../composables/useTheme';
const { themeColor } = useTheme();

const iconBgClass = computed(() => {
  const classes = {
    success: 'bg-green-100 dark:bg-green-900/30',
    error: 'bg-red-100 dark:bg-red-900/30',
    warning: 'bg-yellow-100 dark:bg-yellow-900/30',
    info: 'bg-blue-100 dark:bg-blue-900/30',
    confirm: 'bg-blue-100 dark:bg-blue-900/30'
  };
  return classes[props.type] || classes.info;
});

const iconColorClass = computed(() => {
  const classes = {
    success: 'text-green-600 dark:text-green-400',
    error: 'text-red-600 dark:text-red-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    info: 'text-blue-600 dark:text-blue-400',
    confirm: 'text-blue-600 dark:text-blue-400'
  };
  return classes[props.type] || classes.info;
});

const confirmButtonClass = computed(() => {
  const classes = {
    success: 'bg-green-600 hover:bg-green-700',
    error: 'bg-red-600 hover:bg-red-700',
    warning: 'bg-yellow-600 hover:bg-yellow-700',
    info: 'hover:opacity-90',
    confirm: 'hover:opacity-90'
  };
  return classes[props.type] || classes.info;
});

const confirmButtonStyle = computed(() => {
  if (props.type === 'info' || props.type === 'confirm') {
    return {
      backgroundColor: themeColor.value,
    };
  }
  return {};
});

const close = () => {
  emit('close');
  emit('update:modelValue', false);
};

const confirm = () => {
  emit('confirm');
  close();
};

const cancel = () => {
  emit('cancel');
  close();
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
