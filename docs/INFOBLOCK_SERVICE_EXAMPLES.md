# InfoBlockService - Примеры использования

## Базовые методы для работы с разделами каталога

### 1. Получить список всех разделов (плоский список)

```php
use HolartWeb\AxoraCMS\Services\InfoBlockService;

$service = new InfoBlockService();

// Получить все активные разделы
$sections = $service->getSections('news_catalog');

// Получить все разделы (включая неактивные)
$sections = $service->getSections('news_catalog', false);
```

### 2. Получить дерево разделов с подразделами

```php
// Получить дерево активных разделов
$tree = $service->getSectionsTree('news_catalog');

// Получить дерево всех разделов (включая неактивные)
$tree = $service->getSectionsTree('news_catalog', false);

// Результат:
// [
//     [
//         'id' => 1,
//         'name' => 'Технологии',
//         'code' => 'tech',
//         'image' => '/uploads/tech.jpg',
//         'description' => 'Раздел о технологиях',
//         'is_active' => true,
//         'children' => [
//             [
//                 'id' => 2,
//                 'name' => 'Смартфоны',
//                 'code' => 'smartphones',
//                 'children' => []
//             ]
//         ]
//     ]
// ]
```

### 3. Получить раздел по ID

```php
$section = $service->getSectionById('news_catalog', 1);
```

### 4. Получить раздел по коду

```php
$section = $service->getSectionByCode('news_catalog', 'tech');
```

## Методы для получения элементов из разделов

### 5. Получить элементы конкретного раздела (без подразделов)

```php
// Получить элементы раздела с ID = 1
$elements = $service->getElementsBySection('news_catalog', 1);

// С пагинацией
$elements = $service->getElementsBySection('news_catalog', 1, [], ['sort' => 'asc'], 10, 1);

// С дополнительными фильтрами
$elements = $service->getElementsBySection(
    'news_catalog',
    1,
    ['author' => 'Иван Иванов'],
    ['created_at' => 'desc']
);

// Включая неактивные элементы
$elements = $service->getElementsBySection('news_catalog', 1, [], ['sort' => 'asc'], null, 1, false);
```

### 6. Получить элементы раздела со всеми подразделами (рекурсивно)

```php
// Получить все элементы из раздела и его подразделов
$elements = $service->getElementsBySectionRecursive('news_catalog', 1);

// С пагинацией (10 элементов на странице)
$elements = $service->getElementsBySectionRecursive('news_catalog', 1, [], ['sort' => 'asc'], 10, 1);

// Пример: если раздел "Технологии" (ID=1) имеет подразделы "Смартфоны" (ID=2) и "Ноутбуки" (ID=3),
// то этот метод вернет элементы из всех трех разделов
```

### 7. Получить все элементы каталога (из всех разделов)

```php
// Получить все активные элементы каталога
$elements = $service->getAllCatalogElements('news_catalog');

// С пагинацией
$elements = $service->getAllCatalogElements('news_catalog', [], ['sort' => 'asc'], 20, 1);

// С фильтрами
$elements = $service->getAllCatalogElements(
    'news_catalog',
    ['category' => 'review'],
    ['created_at' => 'desc']
);

// Включая неактивные
$elements = $service->getAllCatalogElements('news_catalog', [], ['sort' => 'asc'], null, 1, false);
```

## Расширенная фильтрация элементов

### 8. Простая фильтрация (точное совпадение)

```php
// Фильтр по одному полю
$elements = $service->getElements('products', ['is_active' => true]);

// Фильтр по свойству (из JSON)
$elements = $service->getElements('products', ['color' => 'red']);

// Множественные фильтры
$elements = $service->getElements('products', [
    'is_active' => true,
    'color' => 'red',
    'brand' => 'Apple'
]);
```

### 9. Фильтрация с операторами сравнения

```php
// Числовые сравнения
$elements = $service->getElements('products', [
    'price' => ['>', 1000]           // цена больше 1000
]);

$elements = $service->getElements('products', [
    'price' => ['>=', 500],          // цена >= 500
    'stock' => ['<', 10]             // остаток < 10
]);

// Неравенство
$elements = $service->getElements('products', [
    'status' => ['!=', 'discontinued']
]);
```

### 10. Фильтрация с LIKE (поиск в строке)

```php
// Поиск по части строки
$elements = $service->getElements('products', [
    'name' => ['LIKE', '%iPhone%']   // название содержит "iPhone"
]);

// Поиск в свойствах (JSON)
$elements = $service->getElements('products', [
    'description' => ['LIKE', '%быстрая доставка%']
]);

// Исключение (NOT LIKE)
$elements = $service->getElements('products', [
    'name' => ['NOT LIKE', '%старый%']
]);
```

### 11. Фильтрация с IN (множественный выбор)

```php
// Фильтр по массиву значений
$elements = $service->getElements('products', [
    'color' => ['red', 'blue', 'green']    // цвет red ИЛИ blue ИЛИ green
]);

// Для колонок таблицы
$elements = $service->getElements('products', [
    'id' => [1, 5, 10, 15]
]);

// Для свойств (JSON)
$elements = $service->getElements('products', [
    'brand' => ['Apple', 'Samsung', 'Xiaomi']
]);
```

### 12. Комбинированная фильтрация

```php
$elements = $service->getElements('products', [
    'is_active' => true,                    // точное совпадение
    'price' => ['>=', 500],                 // больше или равно
    'price' => ['<', 2000],                 // меньше (можно комбинировать)
    'color' => ['red', 'blue'],             // IN clause
    'name' => ['LIKE', '%Pro%'],            // поиск в строке
    'category' => 'electronics'             // точное совпадение свойства
], ['price' => 'asc'], 10, 1);
```

### 13. Фильтрация элементов раздела с расширенными фильтрами

```php
// Элементы раздела с фильтрами
$elements = $service->getElementsBySection('products_catalog', 5, [
    'price' => ['>', 1000],
    'brand' => ['Apple', 'Samsung'],
    'in_stock' => true
], ['price' => 'desc'], 20, 1);

// Элементы раздела и подразделов с фильтрами
$elements = $service->getElementsBySectionRecursive('products_catalog', 5, [
    'rating' => ['>=', 4],
    'name' => ['LIKE', '%Pro%']
]);
```

## Примеры типовых сценариев

### Каталог товаров с фильтрами

```php
// Получить все товары категории "Смартфоны" дороже 30000 руб
$smartphones = $service->getElementsBySection('products', 10, [
    'price' => ['>', 30000],
    'is_active' => true
], ['price' => 'asc'], 20, 1);

// Получить товары со скидкой
$discounted = $service->getAllCatalogElements('products', [
    'discount' => ['>', 0],
    'is_active' => true
], ['discount' => 'desc']);
```

### Новости по категориям

```php
// Все новости раздела "Технологии" и его подразделов за последний месяц
$news = $service->getElementsBySectionRecursive('news', 3, [
    'is_active' => true
], ['created_at' => 'desc'], 10, 1);

// Популярные новости (по количеству просмотров)
$popular = $service->getPopularElements('news', 'views', 5, [
    'is_active' => true
]);
```

### Статьи блога с тегами

```php
// Статьи с определенными тегами
$articles = $service->getElements('blog', [
    'tags' => ['Laravel', 'PHP', 'Vue.js']  // статьи с любым из этих тегов
], ['created_at' => 'desc']);

// Статьи автора
$authorArticles = $service->getElements('blog', [
    'author' => 'Иван Петров',
    'is_active' => true
], ['created_at' => 'desc'], 10, 1);
```

## Дополнительные методы

### Поиск элементов

```php
// Поиск по названию
$results = $service->searchElements('products', 'iPhone', ['is_active' => true], 10, 1);
```

### Получение хлебных крошек

```php
// Хлебные крошки для раздела каталога
$breadcrumbs = $service->getBreadcrumbs('news_catalog', null);

// Хлебные крошки для элемента
$breadcrumbs = $service->getBreadcrumbs('news_catalog', 123);
```

### Получение enum опций

```php
// Получить все варианты для полей типа enum
$enums = $service->getEnumOptions('products');
// Результат: ['color' => [['code' => 'red', 'title' => 'Красный'], ...], ...]
```

## Важные примечания

1. **Типы инфоблоков**: Методы для работы с разделами (`getSections`, `getElementsBySection` и т.д.) работают только с инфоблоками типа "Каталог".

2. **Фильтрация JSON свойств**: Все фильтры, не являющиеся стандартными колонками (`id`, `code`, `name`, `is_active`, `section_id`), автоматически применяются к JSON свойствам.

3. **Операторы**: Поддерживаются операторы `>`, `<`, `>=`, `<=`, `=`, `!=`, `LIKE`, `NOT LIKE`.

4. **Пагинация**: Если `$perPage = null`, возвращается Collection, иначе - LengthAwarePaginator.

5. **Активные элементы**: По умолчанию большинство методов возвращают только активные элементы. Используйте параметр `$activeOnly = false` для получения всех элементов.
