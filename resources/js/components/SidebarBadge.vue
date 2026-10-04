<template>
  <span
    v-if="count > 0"
    class="ml-2 flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold leading-none"
  >
    {{ count > 99 ? '99+' : count }}
  </span>
</template>

<script setup>
defineProps({
  count: { type: Number, default: 0 },
});
</script>
