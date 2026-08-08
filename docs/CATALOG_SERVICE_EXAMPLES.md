# CatalogService - Примеры использования расширенной фильтрации

## Новые методы для работы с каталогами

### 1. Получить товары каталога со всеми подкаталогами (рекурсивно)

```php
use HolartWeb\AxoraCMS\Services\CatalogService;

$service = new CatalogService();

// Получить все товары из каталога и всех его подкаталогов
$products = $service->getProductsByCatalogRecursive(5);

// С пагинацией
$products = $service->getProductsByCatalogRecursive(5, [], ['price' => 'asc'], 20, 1);

// С фильтрами
$products = $service->getProductsByCatalogRecursive(5, [
    'price' => ['>', 1000],
    'is_active' => true
], ['created_at' => 'desc'], 10, 1);

// Включая неактивные товары
$products = $service->getProductsByCatalogRecursive(5, [], ['name' => 'asc'], null, 1, false);
```

### 2. Получить каталог по коду

```php
$catalog = $service->getCatalogByCode('electronics');
```

### 3. Получить все каталоги (плоский список)

```php
// Все активные каталоги
$catalogs = $service->getAllCatalogs();

// С фильтрами
$catalogs = $service->getAllCatalogs(
    ['parent_id' => null],  // только корневые
    ['name' => 'asc']
);

// Включая неактивные
$catalogs = $service->getAllCatalogs([], ['name' => 'asc'], false);
```

### 4. Получить корневые каталоги

```php
// Каталоги без родителя (верхний уровень)
$rootCatalogs = $service->getRootCatalogs();

// Включая неактивные
$rootCatalogs = $service->getRootCatalogs(false);
```

### 5. Получить дочерние каталоги

```php
// Получить все подкаталоги каталога с ID = 5
$childCatalogs = $service->getChildCatalogs(5);

// Включая неактивные
$childCatalogs = $service->getChildCatalogs(5, false);
```

## Расширенная фильтрация каталогов

### 6. Фильтрация каталогов с операторами

```php
// Простая фильтрация
$catalogs = $service->getCatalogsWithFilters([
    'is_active' => true,
    'parent_id' => null
]);

// С операторами LIKE
$catalogs = $service->getCatalogsWithFilters([
    'name' => ['LIKE', '%Электроника%']
]);

// Фильтр по массиву значений (IN)
$catalogs = $service->getCatalogsWithFilters([
    'id' => [1, 5, 10, 15]
]);

// С пагинацией
$catalogs = $service->getCatalogsWithFilters(
    ['is_active' => true],
    20,  // limit
    1,   // page
    ['name' => 'asc']  // ordering
);
```

### 7. Фильтрация по JSON полю addition_info

```php
// Фильтр по характеристике
$catalogs = $service->getCatalogsWithFilters([
    'show_on_main' => true,  // JSON поле
    'is_active' => true
]);

// С операторами сравнения
$catalogs = $service->getCatalogsWithFilters([
    'priority' => ['>', 5]  // JSON поле priority > 5
]);

// Поиск в JSON строке
$catalogs = $service->getCatalogsWithFilters([
    'meta_keywords' => ['LIKE', '%технологии%']
]);
```

## Расширенная фильтрация товаров

### 8. Фильтрация товаров с операторами сравнения

```php
// Товары дороже 1000
$products = $service->getProductsWithFilters([
    'price' => ['>', 1000]
]);

// Товары в диапазоне цен
$products = $service->getProductsWithFilters([
    'price' => ['>=', 500],
], null, 20, 1, ['price' => 'asc']);

// Добавить второй фильтр для верхней границы нужно через отдельный метод
// или использовать whereRaw напрямую
```

### 9. Фильтрация товаров по нескольким критериям

```php
// Активные горячие товары дороже 5000
$products = $service->getProductsWithFilters([
    'is_active' => true,
    'is_hot' => true,
    'price' => ['>', 5000]
], null, 10, 1, ['price' => 'desc']);

// Новые товары определенных каталогов
$products = $service->getProductsWithFilters([
    'is_new' => true,
    'catalog_id' => [5, 10, 15]
]);

// Поиск товаров по артикулу
$products = $service->getProductsWithFilters([
    'sku' => ['LIKE', '%IPHONE%']
]);

// Поиск по названию
$products = $service->getProductsWithFilters([
    'name' => ['LIKE', '%Pro%']
]);
```

### 10. Фильтрация товаров по JSON характеристикам

```php
// Товары с определенным цветом
$products = $service->getProductsWithFilters([
    'color' => 'red',  // JSON поле
    'is_active' => true
]);

// Товары с характеристикой > значения
$products = $service->getProductsWithFilters([
    'screen_size' => ['>', 6.5],  // JSON поле screen_size > 6.5
    'is_active' => true
]);

// Множественный выбор характеристик
$products = $service->getProductsWithFilters([
    'brand' => ['Apple', 'Samsung', 'Xiaomi'],  // JSON поле
    'is_active' => true
]);

// Поиск в JSON строке
$products = $service->getProductsWithFilters([
    'specifications' => ['LIKE', '%водонепроницаемый%']
]);
```

### 11. Комбинированная фильтрация товаров

```php
// Сложный фильтр: активные товары, цена от 10000 до 50000,
// определенные бренды, в наличии
$products = $service->getProductsWithFilters([
    'is_active' => true,
    'price' => ['>=', 10000],
    'quantity' => ['>', 0],
    'brand' => ['Apple', 'Samsung'],  // JSON поле
], null, 20, 1, ['price' => 'asc']);
```

### 12. Фильтрация товаров конкретного каталога

```php
// Товары каталога с фильтрами
$products = $service->getProductsWithFilters(
    [
        'price' => ['>', 1000],
        'is_active' => true
    ],
    5,  // catalogId
    20,
    1,
    ['price' => 'desc']
);
```

## Примеры типовых сценариев

### Интернет-магазин электроники

```php
// Получить все смартфоны дороже 30000 с рейтингом > 4
$smartphones = $service->getProductsWithFilters([
    'is_active' => true,
    'price' => ['>', 30000],
    'rating' => ['>', 4.0],  // JSON поле
    'category' => 'smartphone'  // JSON поле
], null, 20, 1, ['price' => 'asc']);

// Товары со скидкой
$discounted = $service->getProductsWithFilters([
    'old_price' => ['>', 0],  // есть старая цена
    'is_active' => true
], null, 10, 1, ['price' => 'asc']);

// Популярные товары (горячие предложения)
$hotDeals = $service->getProductsWithFilters([
    'is_hot' => true,
    'is_active' => true,
    'quantity' => ['>', 0]
], null, 6, 1, ['created_at' => 'desc']);
```

### Каталог одежды

```php
// Женская одежда определенных размеров и цветов
$womenClothes = $service->getProductsByCatalogRecursive(3, [
    'gender' => 'female',
    'size' => ['S', 'M', 'L'],
    'color' => ['black', 'white', 'red'],
    'is_active' => true
], ['price' => 'asc'], 20, 1);

// Товары с бесплатной доставкой
$freeShipping = $service->getProductsWithFilters([
    'free_shipping' => true,  // JSON поле
    'is_active' => true
]);
```

### Каталог книг

```php
// Книги определенного автора и жанра
$books = $service->getProductsWithFilters([
    'author' => ['LIKE', '%Толстой%'],  // JSON поле
    'genre' => ['роман', 'повесть'],     // JSON поле
    'is_active' => true
], null, 20, 1, ['name' => 'asc']);

// Новинки книг
$newBooks = $service->getProductsWithFilters([
    'is_new' => true,
    'is_active' => true
], null, 10, 1, ['created_at' => 'desc']);
```

## Работа с характеристиками (существующие методы)

### Поиск по характеристикам

```php
// Товары с определенными характеристиками (старый метод)
$products = $service->getProductsByCharacteristics([
    'color' => 'red',
    'size' => 'XL'
], 5, 20, 1);

// Каталоги с характеристиками
$catalogs = $service->getCatalogsByCharacteristics([
    'show_on_main' => true,
    'featured' => true
], 10, 1);
```

## Важные примечания

1. **Фильтрация JSON полей**: Все фильтры, не являющиеся стандартными колонками таблицы, автоматически применяются к JSON полю `addition_info`.

2. **Стандартные колонки каталогов**: `id`, `name`, `slug`, `is_active`, `parent_id`, `description`

3. **Стандартные колонки товаров**: `id`, `name`, `sku`, `slug`, `price`, `old_price`, `quantity`, `is_active`, `is_hot`, `is_new`, `is_recommended`, `catalog_id`, `description`

4. **Операторы**: Поддерживаются `>`, `<`, `>=`, `<=`, `=`, `!=`, `LIKE`, `NOT LIKE`

5. **Автоматический LIKE**: Для полей `name`, `description`, `sku` при передаче строки автоматически применяется LIKE с `%value%`

6. **Сортировка**: Передается как массив `['field' => 'asc|desc']`

7. **Пагинация**: Если `$limit = null`, возвращается Collection, иначе - LengthAwarePaginator

8. **Рекурсивный поиск**: Метод `getProductsByCatalogRecursive` автоматически ищет товары во всех подкаталогах
