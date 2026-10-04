/**
 * Field types whose multiple values are edited through InfoBlockMultipleField.
 */
export const MULTIPLE_INPUT_TYPES = ['string', 'text', 'email', 'phone', 'number', 'double', 'date', 'datetime'];

/**
 * Empty value matching the shape an info block field expects.
 */
export const emptyFieldValue = (field) => {
  if (field.is_multiple) return [];
  if (field.type === 'bool') return false;
  if (field.type === 'button') return { text: '', url: '' };
  if (field.type === 'table') return [];
  return '';
};

/**
 * Initial value for a new element: the field default (deep-cloned) or an empty value.
 */
export const initialFieldValue = (field) => {
  const value = field.default_value;
  if (value === null || value === undefined || value === '') {
    return emptyFieldValue(field);
  }
  return JSON.parse(JSON.stringify(value));
};
