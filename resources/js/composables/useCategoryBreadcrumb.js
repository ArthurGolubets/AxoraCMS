import { computed, unref } from 'vue';

/**
 * Build a breadcrumb resolver for a flat list of catalogs (each with
 * `id`/`name`/`parent_id`), shared by the category combobox and the
 * tree-picker modal so both browse the exact same data.
 *
 * @param {import('vue').Ref<Array>|Array} categoriesRef
 */
export function useCategoryBreadcrumb(categoriesRef) {
  const byId = computed(() => new Map(unref(categoriesRef).map((c) => [c.id, c])));

  const breadcrumbParts = (id) => {
    const path = [];
    let current = byId.value.get(id);
    let guard = 0;
    while (current && guard++ < 20) {
      path.unshift(current.name);
      current = current.parent_id ? byId.value.get(current.parent_id) : null;
    }
    return path;
  };

  const breadcrumbLabel = (id) => breadcrumbParts(id).join(' / ');

  return { byId, breadcrumbParts, breadcrumbLabel };
}
