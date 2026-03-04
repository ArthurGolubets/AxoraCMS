<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ label }}</label>

    <!-- Preview -->
    <div v-if="previewUrl" class="mb-3">
      <div class="relative inline-block">
        <img :src="previewUrl" :alt="label" class="w-32 h-32 object-cover rounded-lg border-2 border-gray-300 dark:border-gray-600">
        <button
          @click="removeImage"
          type="button"
          class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full p-1 hover:bg-red-700 transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Upload Button -->
    <div v-if="!previewUrl" class="flex items-center justify-center w-full">
      <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 transition">
        <div class="flex flex-col items-center justify-center pt-5 pb-6">
          <svg class="w-8 h-8 mb-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
          </svg>
          <p class="mb-1 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Нажмите для загрузки</span></p>
          <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, WEBP (MAX. 2MB)</p>
        </div>
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          accept="image/*"
          @change="handleFileChange"
        >
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  modelValue: String,
  label: String,
});

const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const imageUrl = ref(props.modelValue || '');

const previewUrl = computed(() => {
  if (!imageUrl.value) return '';

  // If it's a base64 string or full URL, return as is
  if (imageUrl.value.startsWith('data:') || imageUrl.value.startsWith('http')) {
    return imageUrl.value;
  }

  // If it's a storage path, prepend the storage URL
  if (imageUrl.value.startsWith('/storage/') || imageUrl.value.startsWith('storage/')) {
    return imageUrl.value.startsWith('/') ? imageUrl.value : '/' + imageUrl.value;
  }

  return imageUrl.value;
});

watch(() => props.modelValue, (newValue) => {
  imageUrl.value = newValue || '';
});

const handleFileChange = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Validate file size (2MB)
  if (file.size > 2 * 1024 * 1024) {
    alert('Файл слишком большой. Максимальный размер: 2MB');
    return;
  }

  // Create preview URL for immediate display
  const reader = new FileReader();
  reader.onload = (e) => {
    imageUrl.value = e.target.result;
  };
  reader.readAsDataURL(file);

  // Upload file to server
  try {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('folder', 'catalogs');

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/api/upload/image', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
      },
      body: formData,
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Ошибка загрузки');
    }

    // Update with server path
    imageUrl.value = data.path;
    emit('update:modelValue', data.path);
  } catch (error) {
    console.error('Upload error:', error);
    alert('Ошибка при загрузке изображения: ' + error.message);
    // Reset on error
    imageUrl.value = '';
    emit('update:modelValue', '');
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
};

const removeImage = () => {
  imageUrl.value = '';
  emit('update:modelValue', '');
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};
</script>
