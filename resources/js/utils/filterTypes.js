/**
 * Product filter types: labels, explanations and badge colours, shared by the
 * filters list, the filter page, the category filters block and the editor.
 */
export const FILTER_TYPES = [
  {
    value: 'select',
    label: 'Один вариант из списка',
    short: 'Один вариант',
    description: 'Покупатель выбирает одно значение в выпадающем списке',
    example: 'Бренд: Apple',
    badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    icon: 'M19 9l-7 7-7-7',
  },
  {
    value: 'checkbox',
    label: 'Несколько вариантов',
    short: 'Несколько вариантов',
    description: 'Можно отметить галочками сразу несколько значений',
    example: 'Цвет: чёрный и белый',
    badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    icon: 'M9 12l2 2 4-4M7 4h10a3 3 0 013 3v10a3 3 0 01-3 3H7a3 3 0 01-3-3V7a3 3 0 013-3z',
  },
  {
    value: 'range',
    label: 'Диапазон чисел',
    short: 'Диапазон',
    description: 'Поля «от» и «до», значения заранее не задаются',
    example: 'Вес от 1 до 5 кг',
    badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
    icon: 'M4 12h16M8 8l-4 4 4 4M16 8l4 4-4 4',
  },
  {
    value: 'string',
    label: 'Текстовое значение',
    short: 'Текст',
    description: 'У каждого товара вводится своё значение, без списка вариантов',
    example: 'Артикул производителя',
    badge: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    icon: 'M4 6h16M4 12h10M4 18h7',
  },
  {
    value: 'entity',
    label: 'Привязка к элементу',
    short: 'Привязка',
    description: 'Значения берутся из инфоблока, товаров или категорий',
    example: 'Производитель из инфоблока «Бренды»',
    badge: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
  },
];

export const filterType = (value) => FILTER_TYPES.find((t) => t.value === value) || {
  value,
  label: value,
  short: value,
  description: '',
  example: '',
  badge: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
  icon: 'M4 6h16M4 12h16M4 18h16',
};

export const ENTITY_TYPE_LABELS = {
  infoblock: 'Элементы инфоблока',
  product: 'Товары',
  catalog: 'Категории',
};
