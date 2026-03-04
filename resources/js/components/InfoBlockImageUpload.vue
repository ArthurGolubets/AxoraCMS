<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Multiple Images Gallery -->
    <div v-if="isMultiple && images.length > 0" class="mb-3">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="(img, index) in images" :key="index" class="relative group">
          <img :src="getImageUrl(img)" :alt="`Image ${index + 1}`" class="w-full h-32 object-cover rounded-lg border-2 border-gray-300 dark:border-gray-600">
          <button
            @click="removeImage(index)"
            type="button"
            class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full p-1 hover:bg-red-700 transition opacity-0 group-hover:opacity-100"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Single Image Preview -->
    <div v-if="!isMultiple && previewUrl" class="mb-3">
      <div class="relative inline-block">
        <img :src="getImageUrl(previewUrl)" :alt="label" class="w-48 h-48 object-cover rounded-lg border-2 border-gray-300 dark:border-gray-600">
        <button
          @click="removeImage()"
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
    <div v-if="!previewUrl || isMultiple" class="flex items-center justify-center w-full">
      <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 transition">
        <div class="flex flex-col items-center justify-center pt-5 pb-6">
          <svg class="w-8 h-8 mb-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">
            <span class="font-semibold">{{ isMultiple ? 'Добавить изображения' : 'Нажмите для загрузки' }}</span>
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">JPEG, JPG, PNG, WEBP (MAX. 5MB)</p>
        </div>
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          accept=".jpeg,.jpg,.png,.webp"
          :multiple="isMultiple"
          @change="handleFileChange"
        >
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  modelValue: [String, Array],
  label: String,
  required: Boolean,
  isMultiple: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const images = ref([]);

const previewUrl = computed(() => {
  if (props.isMultiple) return null;
  return props.modelValue || null;
});

// Initialize images array for multiple mode
watch(() => props.modelValue, (newVal) => {
  if (props.isMultiple) {
    images.value = Array.isArray(newVal) ? newVal : (newVal ? [newVal] : []);
  }
}, { immediate: true });

const handleFileChange = async (event) => {
  const files = Array.from(event.target.files);

  if (files.length === 0) return;

  // Validate file types and sizes
  for (const file of files) {
    if (!['image/jpeg', 'image/jpg', 'image/png', 'image/webp'].includes(file.type)) {
      alert(`Файл ${file.name} имеет неподдерживаемый формат`);
      continue;
    }
    if (file.size > 5 * 1024 * 1024) {
      alert(`Файл ${file.name} превышает максимальный размер 5MB`);
      continue;
    }

    // Upload to server
    try {
      const formData = new FormData();
      formData.append('image', file);

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const response = await fetch('/admin/api/upload/image', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: formData
      });

      if (!response.ok) {
        throw new Error('Ошибка при загрузке файла');
      }

      const data = await response.json();
      const imagePath = data.path || data.url;

      if (props.isMultiple) {
        images.value.push(imagePath);
        emit('update:modelValue', [...images.value]);
      } else {
        emit('update:modelValue', imagePath);
      }
    } catch (error) {
      alert(`Ошибка при загрузке файла ${file.name}: ${error.message}`);
    }
  }

  // Reset input
  fileInput.value.value = '';
};

const getImageUrl = (path) => {
  if (!path) return '';
  // If it's already a full URL or base64, return as is
  if (path.startsWith('http') || path.startsWith('data:')) {
    return path;
  }
  // Otherwise, construct storage URL
  return `/storage/${path}`;
};

const removeImage = async (index) => {
  const imagePath = props.isMultiple ? images.value[index] : props.modelValue;

  // Delete image from server if it's not base64
  if (imagePath && !imagePath.startsWith('data:')) {
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      await fetch('/admin/api/upload/image', {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': token,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ path: imagePath })
      });
    } catch (error) {
      console.error('Ошибка при удалении файла:', error);
    }
  }

  if (props.isMultiple) {
    images.value.splice(index, 1);
    emit('update:modelValue', [...images.value]);
  } else {
    emit('update:modelValue', '');
  }
};
</script>
