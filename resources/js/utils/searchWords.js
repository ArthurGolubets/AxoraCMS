// Word-based search, mirrors HolartWeb\AxoraCMS\Support\SearchTerms on the backend:
// the query is split into words (quotes are ignored) and every word must occur
// in the text, in any order. So «Фасад POIN» matches «Фасад "POINT" белый».
const SEPARATORS = /[\s"'`«»„“”‘’,;:!?()[\]{}]+/u;

export const searchWords = (query) =>
  String(query ?? '').toLowerCase().split(SEPARATORS).filter(Boolean);

export const matchesAllWords = (text, words) => {
  if (!words.length) return true;
  const haystack = String(text ?? '').toLowerCase();
  return words.every((word) => haystack.includes(word));
};
