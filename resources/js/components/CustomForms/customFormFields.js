/**
 * Custom form field types (mirrors TCustomFormField::TYPES).
 */
export const FIELD_TYPES = {
  string: 'Строка',
  text: 'Текст',
  email: 'Email',
  phone: 'Телефон',
  number: 'Число',
  date: 'Дата',
  bool: 'Да/Нет',
  enum: 'Список',
  image: 'Изображение',
  file: 'Файл',
  entity: 'Привязка к элементу',
  user: 'Привязка к пользователю',
};

/**
 * Types that cannot hold several values (mirrors TCustomFormField::SINGLE_ONLY_TYPES).
 */
export const SINGLE_ONLY_TYPES = ['bool', 'user'];

/**
 * Default allowed extensions for "file" fields (mirrors CustomFormService::DEFAULT_FILE_EXTENSIONS).
 */
export const DEFAULT_EXTENSIONS = 'pdf, doc, docx, xls, xlsx, txt, rtf, zip, jpg, jpeg, png, webp';

const TRANSLIT = { а: 'a', б: 'b', в: 'v', г: 'g', д: 'd', е: 'e', ё: 'yo', ж: 'zh', з: 'z', и: 'i', й: 'y', к: 'k', л: 'l', м: 'm', н: 'n', о: 'o', п: 'p', р: 'r', с: 's', т: 't', у: 'u', ф: 'f', х: 'h', ц: 'ts', ч: 'ch', ш: 'sh', щ: 'sch', ъ: '', ы: 'y', ь: '', э: 'e', ю: 'yu', я: 'ya' };

export const translit = (s) => (s || '')
  .toLowerCase()
  .split('')
  .map((c) => TRANSLIT[c] ?? c)
  .join('')
  .replace(/[^a-z0-9]+/g, '_')
  .replace(/^_+|_+$/g, '');
