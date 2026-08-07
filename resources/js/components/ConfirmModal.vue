<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="cancel">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full transform transition-all">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ currentTitle }}</h3>
          </div>

          <!-- Body -->
          <div class="px-6 py-4">
            <p class="text-gray-600 dark:text-gray-300">{{ currentMessage }}</p>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 rounded-b-xl flex justify-end gap-3">
            <button
              @click="cancel"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
            >
              {{ currentCancelText }}
            </button>
            <button
              v-if="currentDangerMode"
              @click="confirm"
              class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
            >
              {{ currentConfirmText }}
            </button>
            <button
              v-else
              @click="confirm"
              class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-all hover:opacity-90"
              :style="{
                backgroundColor: themeColor || '#3b82f6',
                color: '#ffffff',
                border: 'none'
              }"
            >
              {{ currentConfirmText }} 1
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import { useTheme } from '../composables/useTheme';

const { themeColor } = useTheme();

const props = defineProps({
  title: {
    type: String,
    default: 'Подтверждение',
  },
  message: {
    type: String,
    default: 'Вы уверены?',
  },
  confirmText: {
    type: String,
    default: 'Подтвердить',
  },
  cancelText: {
    type: String,
    default: 'Отмена',
  },
  dangerMode: {
    type: Boolean,
    default: false,
  },
});

const show = ref(false);
const currentTitle = ref(props.title);
const currentMessage = ref(props.message);
const currentConfirmText = ref(props.confirmText);
const currentCancelText = ref(props.cancelText);
const currentDangerMode = ref(props.dangerMode);
let resolvePromise;

function open(options = {}) {
  currentTitle.value = options.title || props.title;
  currentMessage.value = options.message || props.message;
  currentConfirmText.value = options.confirmText || props.confirmText;
  currentCancelText.value = options.cancelText || props.cancelText;
  currentDangerMode.value = options.dangerMode !== undefined ? options.dangerMode : props.dangerMode;

  console.log('ConfirmModal opened with:', {
    dangerMode: currentDangerMode.value,
    themeColor: themeColor.value || themeColor,
  });

  show.value = true;
  return new Promise((resolve) => {
    resolvePromise = resolve;
  });
}

function confirm() {
  show.value = false;
  resolvePromise(true);
}

function cancel() {
  show.value = false;
  resolvePromise(false);
}

defineExpose({
  open,
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform 0.2s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
  transform: scale(0.95);
}
</style>
