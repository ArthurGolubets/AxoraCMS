<template>
  <div class="inline-block">
    <button
      ref="trigger"
      type="button"
      @click.stop="toggle"
      class="p-1.5 rounded-md text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors"
      :class="{ 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-white': open }"
      title="Действия"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>

    <Teleport to="body">
      <div
        v-if="open"
        ref="menu"
        class="fixed z-50 min-w-[12rem] py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg"
        :style="menuStyle"
      >
        <template v-for="(item, idx) in visibleItems" :key="idx">
          <div v-if="item.divider" class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
          <button
            v-else
            type="button"
            @click="select(item)"
            class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-left transition-colors"
            :class="item.danger
              ? 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20'
              : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700'"
          >
            <svg v-if="item.icon" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
            </svg>
            {{ item.label }}
          </button>
        </template>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onBeforeUnmount } from 'vue';

/**
 * Burger-style row actions menu.
 *
 * items: Array<{ label: string, icon?: string (svg path d), danger?: boolean,
 *               hidden?: boolean, divider?: boolean, action?: Function }>
 */
const props = defineProps({
  items: { type: Array, required: true },
});

const trigger = ref(null);
const menu = ref(null);
const open = ref(false);
const menuStyle = ref({});

const visibleItems = computed(() => props.items.filter((item) => !item.hidden));

const position = () => {
  const rect = trigger.value.getBoundingClientRect();
  const menuHeight = menu.value?.offsetHeight || 0;
  const spaceBelow = window.innerHeight - rect.bottom;
  const top = spaceBelow < menuHeight + 8 ? rect.top - menuHeight - 4 : rect.bottom + 4;

  menuStyle.value = {
    top: `${Math.max(8, top)}px`,
    right: `${Math.max(8, window.innerWidth - rect.right)}px`,
  };
};

const onOutside = (e) => {
  if (!menu.value?.contains(e.target) && !trigger.value?.contains(e.target)) {
    close();
  }
};

const close = () => {
  open.value = false;
  document.removeEventListener('click', onOutside);
  window.removeEventListener('scroll', close, true);
  window.removeEventListener('resize', close);
};

const toggle = async () => {
  if (open.value) {
    close();
    return;
  }
  open.value = true;
  await nextTick();
  position();
  document.addEventListener('click', onOutside);
  window.addEventListener('scroll', close, true);
  window.addEventListener('resize', close);
};

const select = (item) => {
  close();
  item.action?.();
};

onBeforeUnmount(close);
</script>
