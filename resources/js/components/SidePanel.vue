<template>
  <Teleport to="body">
    <Transition appear name="side-panel-fade">
      <div class="fixed inset-0 z-50 bg-black/40" @click="$emit('close')"></div>
    </Transition>
    <Transition appear name="side-panel-slide">
      <aside
        class="fixed inset-y-0 right-0 z-50 flex flex-col w-full bg-white dark:bg-gray-800 shadow-2xl"
        :class="widthClass"
      >
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
          <button type="button" @click="$emit('close')" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Закрыть">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-5">
          <slot />
        </div>

        <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
          <slot name="footer" />
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue';

defineProps({
  title: { type: String, default: '' },
  widthClass: { type: String, default: 'max-w-xl' },
});

const emit = defineEmits(['close']);

const onKeydown = (e) => {
  if (e.key === 'Escape') emit('close');
};

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<style scoped>
.side-panel-fade-enter-active,
.side-panel-fade-leave-active {
  transition: opacity 0.2s ease;
}
.side-panel-fade-enter-from,
.side-panel-fade-leave-to {
  opacity: 0;
}
.side-panel-slide-enter-active,
.side-panel-slide-leave-active {
  transition: transform 0.25s ease;
}
.side-panel-slide-enter-from,
.side-panel-slide-leave-to {
  transform: translateX(100%);
}
</style>
