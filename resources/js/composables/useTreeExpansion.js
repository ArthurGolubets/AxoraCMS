import { reactive, watch } from 'vue';

/**
 * Shared, localStorage-backed expand/collapse state for admin trees
 * (catalog categories, infoblock sections, ...).
 *
 * State survives navigation and full page reloads (new tab) so the user
 * does not have to re-open every folder after editing an item.
 */
const stores = {};

export function useTreeExpansion(treeKey) {
  if (!stores[treeKey]) {
    const storageKey = `axora-cms-tree-${treeKey}`;

    let initial = [];
    try {
      const raw = JSON.parse(localStorage.getItem(storageKey) || '[]');
      if (Array.isArray(raw)) {
        initial = raw;
      }
    } catch (e) {
      // ignore malformed / unavailable storage
    }

    const state = reactive({ expanded: new Set(initial.map(String)) });

    watch(
      () => [...state.expanded],
      (ids) => {
        try {
          localStorage.setItem(storageKey, JSON.stringify(ids));
        } catch (e) {
          // ignore unavailable storage
        }
      }
    );

    stores[treeKey] = {
      isExpanded: (id) => state.expanded.has(String(id)),
      expand: (id) => state.expanded.add(String(id)),
      collapse: (id) => state.expanded.delete(String(id)),
      toggle: (id) => {
        const key = String(id);
        if (state.expanded.has(key)) {
          state.expanded.delete(key);
        } else {
          state.expanded.add(key);
        }
      },
    };
  }

  return stores[treeKey];
}
