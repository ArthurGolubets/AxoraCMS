# Типы полей для блоков страниц

## Доступные типы полей

### 1. `catalog_select` - Выбор каталогов

Позволяет выбирать один или несколько каталогов товаров.

**Пример схемы поля:**
```json
{
  "name": "catalogs",
  "label": "Выберите каталоги",
  "type": "catalog_select",
  "multiple": true,
  "required": false
}
```

**Параметры:**
- `multiple` (boolean) - разрешить выбор нескольких каталогов
- `only_root` (boolean) - показывать только корневые каталоги

**Использование в шаблоне:**
```blade
@php
    $catalogsIds = $block->data['catalogs'] ?? [];
    if (!is_array($catalogsIds)) $catalogsIds = [$catalogsIds];
    $catalogsItems = \App\Models\TCatalog::whereIn('id', $catalogsIds)->where('is_active', true)->get();
@endphp
@if($catalogsItems->count() > 0)
    <div class="catalogs">
        @foreach($catalogsItems as $catalog)
            <div class="catalog-item">
                <h3>{{ $catalog->name }}</h3>
                <a href="{{ route('catalog.show', $catalog->slug) }}">Перейти в каталог</a>
            </div>
        @endforeach
    </div>
@endif
```

---

### 2. `infoblocks_select` - Выбор инфоблоков

Позволяет выбирать один или несколько инфоблоков.

**Пример схемы поля:**
```json
{
  "name": "infoblocks",
  "label": "Выберите инфоблоки",
  "type": "infoblocks_select",
  "multiple": true,
  "required": false
}
```

**Параметры:**
- `multiple` (boolean) - разрешить выбор нескольких инфоблоков

**Использование в шаблоне:**
```blade
@php
    $infoblocksIds = $block->data['infoblocks'] ?? [];
    if (!is_array($infoblocksIds)) $infoblocksIds = [$infoblocksIds];
    $infoblocksItems = \App\Models\TInfoBlockElement::whereIn('info_block_id', $infoblocksIds)
        ->where('is_active', true)
        ->get();
@endphp
@if($infoblocksItems->count() > 0)
    <div class="infoblocks">
        @foreach($infoblocksItems as $element)
            <div class="element-item">
                <h3>{{ $element->name }}</h3>
                @foreach($element->fieldValues as $fieldValue)
                    <p><strong>{{ $fieldValue->field->name }}:</strong> {{ $fieldValue->value }}</p>
                @endforeach
            </div>
        @endforeach
    </div>
@endif
```

---

### 3. `products_select` - Выбор товаров

Позволяет выбирать товары несколькими способами:
- Ручной выбор конкретных товаров
- Выбор по каталогу
- Фильтр по атрибутам (новинка, рекомендованный, хит)

**Пример схемы поля:**
```json
{
  "name": "products",
  "label": "Товары",
  "type": "products_select",
  "multiple": true,
  "selection_mode": "manual",
  "filters": {
    "by_catalog": true,
    "by_attributes": true
  }
}
```

**Параметры:**
- `multiple` (boolean) - разрешить выбор нескольких товаров
- `selection_mode` (string) - режим выбора: `manual`, `by_catalog`, `by_attributes`
- `filters.by_catalog` (boolean) - включить фильтр по каталогу
- `filters.by_attributes` (boolean) - включить фильтр по атрибутам (новинка/хит/рекомендованный)

**Использование в шаблоне:**
```blade
@php
    $productsIds = $block->data['products'] ?? [];
    if (!is_array($productsIds)) $productsIds = [$productsIds];
    $productsItems = \App\Models\TProduct::whereIn('id', $productsIds)
        ->where('is_active', true)
        ->get();
@endphp
@if($productsItems->count() > 0)
    <div class="products">
        @foreach($productsItems as $product)
            <div class="product-item">
                @if($product->image)
                    <img src="{{ $product->image }}" alt="{{ $product->name }}">
                @endif
                <h3>{{ $product->name }}</h3>
                <p class="price">{{ number_format($product->price, 0, '.', ' ') }} ₽</p>
                @if($product->is_new)
                    <span class="badge">Новинка</span>
                @endif
                @if($product->is_hit)
                    <span class="badge">Хит продаж</span>
                @endif
                <a href="{{ route('product.show', $product->slug) }}">Подробнее</a>
            </div>
        @endforeach
    </div>
@endif
```

---

### 4. `repeater` - Множественные поля (повторитель)

Позволяет создавать множественные группы полей.

**Пример схемы поля:**
```json
{
  "name": "features",
  "label": "Преимущества",
  "type": "repeater",
  "fields": [
    {
      "name": "title",
      "label": "Заголовок",
      "type": "text"
    },
    {
      "name": "description",
      "label": "Описание",
      "type": "textarea"
    },
    {
      "name": "icon",
      "label": "Иконка",
      "type": "image"
    }
  ]
}
```

**Параметры:**
- `fields` (array) - массив вложенных полей, каждое со своим типом

**Использование в шаблоне:**
```blade
@php
    $featuresItems = $block->data['features'] ?? [];
@endphp
@if(!empty($featuresItems) && is_array($featuresItems))
    <div class="features">
        @foreach($featuresItems as $item)
            <div class="feature-item">
                @if(!empty($item['icon']))
                    <img src="{{ $item['icon'] }}" alt="{{ $item['title'] ?? '' }}">
                @endif
                <h3>{{ $item['title'] ?? '' }}</h3>
                <p>{{ $item['description'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
@endif
```

---

## API Endpoints для получения данных

### Получить список каталогов
```
GET /admin/api/page-block-fields/catalogs?search=название&only_root=true
```

### Получить список инфоблоков
```
GET /admin/api/page-block-fields/infoblocks?search=название
```

### Получить список товаров
```
GET /admin/api/page-block-fields/products?search=название&catalog_id=1&is_new=true&is_hit=true&limit=50
```

### Получить элементы инфоблока
```
GET /admin/api/page-block-fields/infoblocks/{infoBlockId}/elements?search=название&limit=50
```

---

## Полный пример создания кастомного блока

```json
{
  "code": "products_showcase",
  "name": "Витрина товаров",
  "description": "Блок для отображения подборки товаров с фильтрами",
  "category": "Товары",
  "fields_schema": [
    {
      "name": "title",
      "label": "Заголовок блока",
      "type": "text",
      "required": true
    },
    {
      "name": "description",
      "label": "Описание",
      "type": "textarea",
      "required": false
    },
    {
      "name": "products",
      "label": "Выберите товары",
      "type": "products_select",
      "multiple": true,
      "selection_mode": "manual",
      "filters": {
        "by_catalog": true,
        "by_attributes": true
      }
    },
    {
      "name": "show_prices",
      "label": "Показывать цены",
      "type": "boolean"
    }
  ]
}
```

Сгенерированный Blade-шаблон автоматически создаст корректную структуру для работы с этими полями.
