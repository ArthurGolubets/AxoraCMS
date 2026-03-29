# Интеграция системы свойств товаров

## Обзор
Реализована система свойств для товаров каталога с наследованием по иерархии категорий.

## Что было реализовано

### 1. База данных
- **Миграция `2026_03_29_000090_create_t_catalog_properties_table.php`**
  - Таблица `t_catalog_properties` для хранения свойств категорий
  - Поля: id, catalog_id, code, name, type, is_multiple, sort_order
  - Типы свойств: string (строка), text (текст), number (число)
  - Поддержка единичных и множественных значений

- **Миграция `2026_03_29_000091_create_t_product_property_values_table.php`**
  - Таблица `t_product_property_values` для хранения значений свойств товаров
  - Поля: id, product_id, property_id, value

### 2. Модели
- **TCatalogProperty** (`src/Models/Shop/TCatalogProperty.php`)
  - Модель для свойств категорий
  - Связи: catalog (belongsTo), propertyValues (hasMany)

- **TProductPropertyValue** (`src/Models/Shop/TProductPropertyValue.php`)
  - Модель для значений свойств товаров
  - Связи: product (belongsTo), property (belongsTo)
  - Метод `getTypedValue()` для получения типизированного значения

### 3. Обновленные модели
- **TCatalog** (`src/Models/Shop/TCatalog.php`)
  - Добавлен метод `properties()` - получение свойств категории
  - Добавлен метод `getAllProperties()` - получение всех свойств включая унаследованные от родительских категорий

- **TProduct** (`src/Models/Shop/TProduct.php`)
  - Добавлен метод `propertyValues()` - получение значений свойств товара
  - Добавлен метод `getPropertiesWithValues()` - получение свойств со значениями

### 4. Сервисы
- **CatalogService** (`src/Services/CatalogService.php`)
  - Метод `searchProductsByProperties($properties, $catalogId, ...)` - поиск товаров по свойствам
    - Поддержка поиска по property_id или property_code
    - Пример: `['color' => 'red']` или `[15 => 'red']`
  - Метод `getCatalogProperties($catalogId)` - получение всех свойств категории

### 5. Контроллеры
- **CatalogController** (`src/Http/Controllers/Shop/CatalogController.php`)
  - `show()` - добавлено: properties, inherited_properties, all_properties
  - `store()` - поддержка создания свойств при создании категории
  - `update()` - поддержка обновления свойств категории

- **ProductController** (`src/Http/Controllers/Shop/ProductController.php`)
  - `show()` - добавлено: available_properties, property_values
  - `store()` - поддержка сохранения значений свойств при создании товара
  - `update()` - поддержка обновления значений свойств товара

### 6. Vue компоненты
- **CatalogPropertiesManager.vue** (`resources/js/components/CatalogPropertiesManager.vue`)
  - Компонент для управления свойствами в карточке категории
  - Добавление/удаление свойств
  - Настройка: код, название, тип, множественность, порядок сортировки
  - Отображение унаследованных свойств из родительских категорий

- **ProductPropertiesForm.vue** (`resources/js/components/ProductPropertiesForm.vue`)
  - Компонент для заполнения значений свойств в карточке товара
  - Автоматическое определение доступных свойств из категории
  - Поддержка всех типов свойств (string, text, number)
  - Поддержка множественных значений

## Как использовать

### 1. Запуск миграций
```bash
php artisan migrate
```

### 2. Интеграция Vue компонентов

#### В форме редактирования категории (CatalogForm.vue)
Добавьте новую вкладку "Свойства":

```vue
<template>
  <!-- В секции tabs добавьте: -->
  <div v-show="activeTab === 'properties'" class="space-y-6">
    <CatalogPropertiesManager
      :catalog-id="catalogId"
      :initial-properties="form.properties"
      :inherited-properties="inheritedProperties"
      @update:properties="form.properties = $event"
    />
  </div>
</template>

<script>
import CatalogPropertiesManager from './CatalogPropertiesManager.vue'

export default {
  components: {
    CatalogPropertiesManager
  },
  data() {
    return {
      tabs: [
        { id: 'main', label: 'Основная информация' },
        { id: 'seo', label: 'SEO' },
        { id: 'properties', label: 'Свойства' }, // Новая вкладка
        { id: 'content', label: 'Контент' }
      ],
      form: {
        // ... существующие поля
        properties: []
      },
      inheritedProperties: []
    }
  },
  methods: {
    async loadCatalog(id) {
      const response = await fetch(`/api/admin/catalogs/${id}`)
      const data = await response.json()
      this.form.properties = data.properties || []
      this.inheritedProperties = data.inherited_properties || []
    }
  }
}
</script>
```

#### В форме редактирования товара (ProductForm.vue)
Добавьте новую вкладку "Свойства":

```vue
<template>
  <!-- В секции tabs добавьте: -->
  <div v-show="activeTab === 'properties'" class="space-y-6">
    <ProductPropertiesForm
      :available-properties="availableProperties"
      :initial-values="propertyValues"
      @update:values="propertyValues = $event"
    />
  </div>
</template>

<script>
import ProductPropertiesForm from './ProductPropertiesForm.vue'

export default {
  components: {
    ProductPropertiesForm
  },
  data() {
    return {
      tabs: [
        { id: 'main', label: 'Основная информация' },
        { id: 'properties', label: 'Свойства' }, // Новая вкладка
        { id: 'seo', label: 'SEO' },
        { id: 'variants', label: 'Варианты' }
      ],
      form: {
        // ... существующие поля
        property_values: {}
      },
      availableProperties: [],
      propertyValues: {}
    }
  },
  methods: {
    async loadProduct(id) {
      const response = await fetch(`/api/admin/products/${id}`)
      const data = await response.json()
      this.availableProperties = data.available_properties || []

      // Преобразуем значения свойств в объект {property_id: value}
      const values = {}
      data.property_values?.forEach(pv => {
        values[pv.property.id] = pv.value
      })
      this.propertyValues = values
    },

    async handleSubmit() {
      const formData = {
        ...this.form,
        property_values: this.propertyValues
      }
      // Отправка на сервер
    }
  }
}
</script>
```

### 3. Использование API

#### Поиск товаров по свойствам
```php
use HolartWeb\AxoraCMS\Services\CatalogService;

$catalogService = new CatalogService();

// Поиск по коду свойства
$products = $catalogService->searchProductsByProperties([
    'color' => 'Красный',
    'size' => 'XL'
], $catalogId = 5, $limit = 20);

// Поиск по ID свойства
$products = $catalogService->searchProductsByProperties([
    15 => 'Красный',  // property_id = 15
    16 => 'XL'        // property_id = 16
], $catalogId = 5);
```

#### Получение свойств категории
```php
// Получить все свойства (включая унаследованные)
$catalog = TCatalog::find(5);
$allProperties = $catalog->getAllProperties();

// Получить только свои свойства
$ownProperties = $catalog->properties;
```

#### Получение свойств товара
```php
$product = TProduct::find(10);
$propertiesWithValues = $product->getPropertiesWithValues();

// Результат:
// [
//   ['property' => TCatalogProperty, 'value' => 'Красный', 'value_id' => 123],
//   ['property' => TCatalogProperty, 'value' => 'XL', 'value_id' => 124],
// ]
```

## Особенности наследования

Свойства наследуются по иерархии категорий. Если у категории "Товары для дома" есть свойства, то все дочерние категории ("Посуда", "Мебель" и т.д.) автоматически получат эти свойства.

Пример:
```
Товары для дома (свойства: материал, цвет)
  └── Посуда (свойства: объем)
      └── Кастрюли
```

Товары в категории "Кастрюли" будут иметь доступ к свойствам:
- материал (унаследовано от "Товары для дома")
- цвет (унаследовано от "Товары для дома")
- объем (унаследовано от "Посуда")

## Типы данных свойств

- **string** - короткая строка (например: цвет, размер)
- **text** - длинный текст (например: описание, комментарий)
- **number** - число (например: объем, вес, размер)

## Множественные значения

Свойство может быть помечено как "множественное" (`is_multiple = true`). В этом случае товар может иметь несколько значений для этого свойства (например, несколько цветов).

При сохранении множественные значения автоматически сериализуются в JSON.
