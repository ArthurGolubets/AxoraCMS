<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Создание заказа</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Заполните информацию о новом заказе</p>
      </div>
      <button
        @click="$router.push('/orders')"
        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition"
      >
        Отмена
      </button>
    </div>

    <form @submit.prevent="createOrder">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Customer Info -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Информация о клиенте</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Имя *</label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  placeholder="Иван Иванов"
                  class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="email@example.com"
                  class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Телефон</label>
                <input
                  v-model="form.phone"
                  type="tel"
                  placeholder="+7 (999) 123-45-67"
                  class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Товары</h3>
              <button
                type="button"
                @click="addItem"
                :style="buttonStyle"
                class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90 text-sm"
              >
                + Добавить товар
              </button>
            </div>

            <div v-if="form.items.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
              Нет товаров. Добавьте товар в заказ.
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(item, index) in form.items"
                :key="index"
                class="p-4 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg"
              >
                <div class="flex items-start justify-between mb-3">
                  <h4 class="font-medium text-gray-900 dark:text-white">Товар {{ index + 1 }}</h4>
                  <button
                    type="button"
                    @click="removeItem(index)"
                    class="text-red-600 hover:text-red-800 text-sm"
                  >
                    Удалить
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Товар</label>
                    <div class="relative">
                      <input
                        v-model="item.searchQuery"
                        @input="filterProducts(index)"
                        @focus="item.showDropdown = true"
                        type="text"
                        placeholder="Начните вводить название товара..."
                        class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                      >
                      <div
                        v-if="item.showDropdown && item.filteredProducts.length > 0"
                        class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                      >
                        <button
                          v-for="product in item.filteredProducts"
                          :key="product.id"
                          type="button"
                          @click="selectProduct(index, product)"
                          class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-0"
                        >
                          <div class="font-medium">{{ product.name }}</div>
                          <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatPrice(product.price) }} ₽</div>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div v-if="item.product_id && getProductVariants(item.product_id).length > 0" class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Вариация</label>
                    <select
                      v-model="item.variant_id"
                      @change="onVariantSelect(index)"
                      class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                    >
                      <option value="">Выберите вариацию</option>
                      <option v-for="variant in getProductVariants(item.product_id)" :key="variant.id" :value="variant.id">
                        {{ variant.name }} - {{ formatPrice(variant.price) }} ₽
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Цена</label>
                    <input
                      v-model.number="item.price"
                      type="number"
                      step="0.01"
                      required
                      class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                    >
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Количество</label>
                    <input
                      v-model.number="item.amount"
                      type="number"
                      min="1"
                      required
                      class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                    >
                  </div>

                  <div class="md:col-span-2">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                      Итого: <span class="font-semibold">{{ formatPrice(item.price * item.amount) }} ₽</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Order Total -->
            <div v-if="form.items.length > 0" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
              <div class="space-y-2">
                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                  <span>Товары:</span>
                  <span>{{ formatPrice(subtotal) }} ₽</span>
                </div>
                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                  <span>Доставка:</span>
                  <span>{{ formatPrice(form.delivery_price) }} ₽</span>
                </div>
                <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-200 dark:border-gray-700">
                  <span>Всего:</span>
                  <span>{{ formatPrice(total) }} ₽</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Comments -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Комментарий</h3>
            <textarea
              v-model="form.comments"
              rows="4"
              placeholder="Комментарий к заказу..."
              class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            ></textarea>
          </div>

          <!-- Custom Fields -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Дополнительные поля</h3>
              <button
                type="button"
                @click="addCustomField"
                :style="buttonStyle"
                class="px-4 py-2 text-white rounded-lg transition-opacity hover:opacity-90 text-sm"
              >
                + Добавить поле
              </button>
            </div>

            <div v-if="customFields.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
              Нет дополнительных полей
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="(field, index) in customFields"
                :key="index"
                class="flex gap-3"
              >
                <input
                  v-model="field.key"
                  type="text"
                  placeholder="Ключ"
                  class="flex-1 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
                <input
                  v-model="field.value"
                  type="text"
                  placeholder="Значение"
                  class="flex-1 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
                <button
                  type="button"
                  @click="removeCustomField(index)"
                  class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"
                >
                  ×
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Delivery -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Доставка</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Тип доставки</label>
                <select
                  v-model="form.delivery_type"
                  class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
                  <option value="pickup">Самовывоз</option>
                  <option value="courier">Курьер</option>
                  <option value="post">Почта</option>
                </select>
              </div>

              <div v-if="form.delivery_type !== 'pickup'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Адрес доставки</label>
                <textarea
                  v-model="form.delivery_address"
                  rows="3"
                  placeholder="Адрес доставки..."
                  class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Стоимость доставки (₽)</label>
                <input
                  v-model.number="form.delivery_price"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
              </div>
            </div>
          </div>

          <!-- Payment -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Оплата</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Способ оплаты</label>
                <select
                  v-model="form.payment_type"
                  class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
                  <option value="cash">Наличные</option>
                  <option value="online">Онлайн</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Статус оплаты</label>
                <select
                  v-model="form.payment_status"
                  class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                >
                  <option value="pending">Ожидает</option>
                  <option value="paid">Оплачен</option>
                  <option value="failed">Ошибка</option>
                  <option value="refunded">Возврат</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading || form.items.length === 0"
            :style="buttonStyle"
            class="w-full px-6 py-3 text-white rounded-lg font-medium transition-opacity hover:opacity-90 disabled:opacity-50"
          >
            {{ loading ? 'Создание...' : 'Создать заказ' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useModal } from '../composables/useModal';
import { useTheme } from '../composables/useTheme';

const router = useRouter();
const { success, error } = useModal();
const { buttonStyle } = useTheme();

const loading = ref(false);
const products = ref([]);
const customFields = ref([]);

const form = ref({
  name: '',
  email: '',
  phone: '',
  delivery_type: 'pickup',
  delivery_address: '',
  delivery_price: 0,
  payment_type: 'cash',
  payment_status: 'pending',
  comments: '',
  items: []
});

const subtotal = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.price * item.amount), 0);
});

const total = computed(() => {
  return subtotal.value + form.value.delivery_price;
});

const loadProducts = async () => {
  try {
    let allProducts = [];
    let page = 1;
    let hasMore = true;

    while (hasMore) {
      const response = await fetch(`/admin/api/products?page=${page}&per_page=100`, {
        headers: { 'Accept': 'application/json' }
      });

      if (response.ok) {
        const data = await response.json();
        allProducts = [...allProducts, ...(data.data || [])];

        // Check if there are more pages
        hasMore = data.current_page < data.last_page;
        page++;
      } else {
        hasMore = false;
      }
    }

    products.value = allProducts;
  } catch (err) {
    console.error('Failed to load products:', err);
  }
};

const getProductVariants = (productId) => {
  const product = products.value.find(p => p.id === productId);
  return product?.variants || [];
};

const onProductSelect = (index) => {
  const item = form.value.items[index];
  const product = products.value.find(p => p.id === item.product_id);

  if (product) {
    item.price = parseFloat(product.price);
    item.variant_id = '';

    // If product has only one variant, select it automatically
    if (product.variants && product.variants.length === 1) {
      item.variant_id = product.variants[0].id;
      onVariantSelect(index);
    }
  }
};

const onVariantSelect = (index) => {
  const item = form.value.items[index];
  const product = products.value.find(p => p.id === item.product_id);

  if (product && item.variant_id) {
    const variant = product.variants.find(v => v.id === item.variant_id);
    if (variant) {
      item.price = parseFloat(variant.price);
    }
  }
};

const filterProducts = (index) => {
  const item = form.value.items[index];
  const query = item.searchQuery.toLowerCase();

  if (!query) {
    item.filteredProducts = products.value.slice(0, 10);
  } else {
    item.filteredProducts = products.value.filter(p =>
      p.name.toLowerCase().includes(query) ||
      p.sku?.toLowerCase().includes(query)
    ).slice(0, 10);
  }
};

const selectProduct = (index, product) => {
  const item = form.value.items[index];
  item.product_id = product.id;
  item.searchQuery = product.name;
  item.showDropdown = false;
  item.price = parseFloat(product.price);
  item.variant_id = '';

  // If product has only one variant, select it automatically
  if (product.variants && product.variants.length === 1) {
    item.variant_id = product.variants[0].id;
    onVariantSelect(index);
  }
};

const addItem = () => {
  form.value.items.push({
    product_id: '',
    variant_id: '',
    price: 0,
    amount: 1,
    searchQuery: '',
    showDropdown: false,
    filteredProducts: []
  });
};

const removeItem = (index) => {
  form.value.items.splice(index, 1);
};

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2);
};

const addCustomField = () => {
  customFields.value.push({
    key: '',
    value: ''
  });
};

const removeCustomField = (index) => {
  customFields.value.splice(index, 1);
};

const createOrder = async () => {
  if (form.value.items.length === 0) {
    error('Добавьте хотя бы один товар в заказ');
    return;
  }

  loading.value = true;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Format order data with required fields
    const addition_data = {};
    customFields.value.forEach(field => {
      if (field.key && field.value) {
        addition_data[field.key] = field.value;
      }
    });

    const orderData = {
      ...form.value,
      items: form.value.items.map(item => ({
        product_id: item.product_id,
        variant_id: item.variant_id || null,
        product_name: item.searchQuery || '',
        price: item.price,
        amount: item.amount,
        total_price: item.price * item.amount
      })),
      addition_data: addition_data
    };

    const response = await fetch('/admin/api/orders', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(orderData)
    });

    if (response.ok) {
      const result = await response.json();
      success('Заказ успешно создан');
      router.push(`/orders/${result.data.id}`);
    } else {
      const errorData = await response.json();
      if (errorData.errors) {
        console.error('Validation errors:', errorData.errors);
      }
      error('Ошибка при создании заказа');
    }
  } catch (err) {
    console.error('Error creating order:', err);
    error('Ошибка при создании заказа');
  } finally {
    loading.value = false;
  }
};

// Close dropdowns when clicking outside
const handleClickOutside = (event) => {
  form.value.items.forEach(item => {
    if (!event.target.closest('.relative')) {
      item.showDropdown = false;
    }
  });
};

onMounted(() => {
  loadProducts();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
