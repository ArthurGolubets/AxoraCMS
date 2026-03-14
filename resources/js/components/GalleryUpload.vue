<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ label }}</label>

    <!-- Gallery Preview -->
    <div v-if="images.length > 0" class="grid grid-cols-6 gap-2 mb-3">
      <div v-for="(image, index) in images" :key="index" class="relative group">
        <img :src="getImageUrl(image)" alt="Gallery image" class="w-20 h-20 object-cover rounded-lg border-2 border-gray-300 dark:border-gray-600">
        <button
          @click="removeImage(index)"
          type="button"
          class="absolute -top-1 -right-1 bg-red-600 text-white rounded-full p-1 hover:bg-red-700 transition opacity-0 group-hover:opacity-100"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Upload Button -->
    <div class="flex items-center justify-center w-full">
      <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 transition">
        <div class="flex flex-col items-center justify-center">
          <svg class="w-6 h-6 mb-1 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          <p class="text-xs text-gray-500 dark:text-gray-400"><span class="font-semibold">Добавить изображения</span></p>
        </div>
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          accept="image/*"
          multiple
          @change="handleFileChange"
        >
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  label: String,
});

const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const images = ref([...(props.modelValue || [])]);

watch(() => props.modelValue, (newValue) => {
  images.value = [...(newValue || [])];
});

const handleFileChange = async (event) => {
  const files = Array.from(event.target.files);

  for (const file of files) {
    if (file.size > 2 * 1024 * 1024) {
      alert('Файл слишком большой. Максимальный размер: 2MB');
      continue;
    }

    // Create preview URL for immediate display
    const reader = new FileReader();
    reader.onload = (e) => {
      images.value.push(e.target.result);
    };
    reader.readAsDataURL(file);

    // Upload file to server
    try {
      const formData = new FormData();
      formData.append('image', file);
      formData.append('folder', 'products/gallery');

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

      // Replace preview with server path
      const previewIndex = images.value.findIndex(img => img.startsWith('data:'));
      if (previewIndex !== -1) {
        images.value[previewIndex] = data.path;
      } else {
        images.value.push(data.path);
      }

      emit('update:modelValue', images.value);
    } catch (error) {
      console.error('Upload error:', error);
      alert('Ошибка при загрузке изображения: ' + error.message);
      // Remove preview on error
      const previewIndex = images.value.findIndex(img => img.startsWith('data:'));
      if (previewIndex !== -1) {
        images.value.splice(previewIndex, 1);
      }
    }
  }

  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const removeImage = (index) => {
  images.value.splice(index, 1);
  emit('update:modelValue', images.value);
};

const getImageUrl = (image) => {
  if (!image) return '';

  // If it's a base64 string or full URL, return as is
  if (image.startsWith('data:') || image.startsWith('http')) {
    return image;
  }

  // If it's already a storage path
  if (image.startsWith('/storage/') || image.startsWith('storage/')) {
    return image.startsWith('/') ? image : '/' + image;
  }

  // Otherwise, it's a relative path from storage (e.g., "products/gallery/image.jpg")
  // Prepend /storage/
  return '/storage/' + image;
};
</script>
