/**
 * Visual email builder: block definitions and the generator that turns blocks
 * into email-safe HTML (table layout, inline styles). Variables like #ORDER_ID#
 * are left in place — the server replaces them when the message is sent.
 */

export const BLOCK_TYPES = {
  heading: {
    label: 'Заголовок',
    hint: 'Крупная надпись в начале письма или раздела',
    icon: 'M4 6h16M4 12h8m-8 6h16',
    create: () => ({ type: 'heading', text: 'Заголовок письма', size: 'large', align: 'left' }),
  },
  text: {
    label: 'Текст',
    hint: 'Обычный абзац текста',
    icon: 'M4 6h16M4 10h16M4 14h10',
    create: () => ({ type: 'text', text: 'Здравствуйте, #ORDER_NAME#!', size: 'normal', align: 'left' }),
  },
  button: {
    label: 'Кнопка',
    hint: 'Цветная кнопка со ссылкой',
    icon: 'M5 8h14a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4a2 2 0 012-2z',
    create: () => ({ type: 'button', label: 'Перейти на сайт', url: '#SITE_URL#', align: 'center', color: '' }),
  },
  link: {
    label: 'Ссылка',
    hint: 'Подчёркнутая ссылка в тексте',
    icon: 'M10 14a5 5 0 007.07 0l3-3a5 5 0 00-7.07-7.07l-1 1M14 10a5 5 0 00-7.07 0l-3 3a5 5 0 007.07 7.07l1-1',
    create: () => ({ type: 'link', text: 'Открыть на сайте', url: '#SITE_URL#', align: 'left' }),
  },
  image: {
    label: 'Картинка',
    hint: 'Логотип или любое изображение',
    icon: 'M4 16l4-4 4 4 4-6 4 6M4 4h16v16H4z',
    create: () => ({ type: 'image', src: '', alt: '', width: 'full', align: 'center', url: '' }),
  },
  products: {
    label: 'Список товаров',
    hint: 'Таблица с товарами заказа (только для событий с заказом)',
    icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 5h13M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z',
    create: () => ({ type: 'products' }),
  },
  details: {
    label: 'Таблица данных',
    hint: 'Строки «название — значение», например «Номер заказа — #ORDER_ID#»',
    icon: 'M3 10h18M3 14h18M10 4v16M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z',
    create: () => ({ type: 'details', rows: [{ label: 'Номер заказа', value: '#ORDER_ID#' }, { label: 'ИТОГО', value: '#TOTAL#' }] }),
  },
  divider: {
    label: 'Линия',
    hint: 'Тонкая разделительная линия',
    icon: 'M4 12h16',
    create: () => ({ type: 'divider' }),
  },
  spacer: {
    label: 'Отступ',
    hint: 'Пустое место между блоками',
    icon: 'M8 7l4-4 4 4M8 17l4 4 4-4',
    create: () => ({ type: 'spacer', height: 'medium' }),
  },
};

export const DEFAULT_SETTINGS = { pageBg: '#f3f4f6', accent: '#2563eb' };

export const SIZES = {
  heading: { large: 28, medium: 22, small: 18 },
  text: { normal: 15, small: 13 },
  spacer: { small: 12, medium: 24, large: 40 },
};

export const newBlocksDocument = () => ({ mode: 'blocks', settings: { ...DEFAULT_SETTINGS }, items: [BLOCK_TYPES.heading.create(), BLOCK_TYPES.text.create()] });

const escapeHtml = (value) => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;');

// Plain text typed by the user -> safe HTML with line breaks.
const textToHtml = (value) => escapeHtml(value).replace(/\r?\n/g, '<br>');

const safeUrl = (value) => {
  const url = String(value ?? '').trim();
  if (!url || /^\s*(javascript|data|vbscript):/i.test(url)) return '#';
  return escapeHtml(url);
};

// Uploaded files are stored as a path; emails need an absolute address.
const imageUrl = (src) => {
  const value = String(src ?? '').trim();
  if (!value) return '';
  if (/^(https?:)?\/\//i.test(value) || value.startsWith('#') || value.startsWith('data:')) return value;
  return `#SITE_URL#/storage/${value.replace(/^\/?(storage\/)?/, '')}`;
};

const alignOf = (value) => (['left', 'center', 'right'].includes(value) ? value : 'left');

const renderBlock = (block, settings) => {
  const align = alignOf(block.align);

  switch (block.type) {
    case 'heading': {
      const size = SIZES.heading[block.size] || SIZES.heading.large;
      return `<h2 style="margin:0;font-size:${size}px;line-height:1.3;color:#111827;text-align:${align}">${textToHtml(block.text)}</h2>`;
    }
    case 'text': {
      const small = block.size === 'small';
      return `<p style="margin:0;font-size:${small ? 13 : 15}px;line-height:1.6;color:${small ? '#6b7280' : '#374151'};text-align:${align}">${textToHtml(block.text)}</p>`;
    }
    case 'button': {
      const color = block.color || settings.accent;
      return `<div style="text-align:${align}"><a href="${safeUrl(block.url)}" style="display:inline-block;padding:12px 28px;background:${escapeHtml(color)};color:#ffffff;text-decoration:none;border-radius:6px;font-size:15px;font-weight:bold">${textToHtml(block.label)}</a></div>`;
    }
    case 'link':
      return `<p style="margin:0;font-size:15px;text-align:${align}"><a href="${safeUrl(block.url)}" style="color:${escapeHtml(settings.accent)};text-decoration:underline">${textToHtml(block.text)}</a></p>`;
    case 'image': {
      const src = imageUrl(block.src);
      if (!src) return '';
      const width = { full: '100%', medium: '50%', small: '25%' }[block.width] || '100%';
      const image = `<img src="${escapeHtml(src)}" alt="${escapeHtml(block.alt)}" style="display:inline-block;width:${width};max-width:100%;height:auto;border:0">`;
      return `<div style="text-align:${align}">${block.url ? `<a href="${safeUrl(block.url)}">${image}</a>` : image}</div>`;
    }
    case 'products':
      return '<div style="font-size:14px;color:#374151">#PRODUCTS#</div>';
    case 'details': {
      const rows = (block.rows || []).filter(row => row.label || row.value).map(row => `<tr><td style="padding:8px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:14px">${textToHtml(row.label)}</td><td style="padding:8px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:bold;text-align:right">${textToHtml(row.value)}</td></tr>`);
      return rows.length ? `<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">${rows.join('')}</table>` : '';
    }
    case 'divider':
      return '<hr style="border:0;border-top:1px solid #e5e7eb;margin:0">';
    case 'spacer':
      return `<div style="height:${SIZES.spacer[block.height] || 24}px;line-height:${SIZES.spacer[block.height] || 24}px">&nbsp;</div>`;
    default:
      return '';
  }
};

/** Whole email: grey page, white 600px card, blocks stacked with even gaps. */
export function renderBlocks(document) {
  const settings = { ...DEFAULT_SETTINGS, ...(document.settings || {}) };
  const rows = (document.items || [])
    .map(block => renderBlock(block, settings))
    .filter(Boolean)
    .map(html => `<tr><td style="padding:0 32px 18px">${html}</td></tr>`)
    .join('');

  if (!rows) return '';

  return `<div style="background:${escapeHtml(settings.pageBg)};padding:24px 12px;font-family:Arial,Helvetica,sans-serif">`
    + '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">'
    + '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:8px">'
    + `<tr><td style="height:14px;line-height:14px">&nbsp;</td></tr>${rows}`
    + '</table></td></tr></table></div>';
}
