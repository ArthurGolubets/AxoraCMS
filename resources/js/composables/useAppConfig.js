import { ref } from 'vue';

/**
 * Single source of truth for per-session configuration data that used to be
 * refetched by every component / navigation:
 *
 *   - GET /admin/api/me
 *   - GET /admin/api/modules/status
 *   - GET /admin/api/settings
 *   - GET /admin/api/infoblocks/favorites
 *
 * Each resource is fetched at most once per SPA session. Parallel callers
 * during the first load share the same in-flight promise (dedup). Values are
 * served from memory afterwards. Use the `refresh*` helpers to invalidate a
 * resource after an action that changes it, and `reset()` on login/logout.
 */

const me = ref(null);
const modulesStatus = ref(null);
const settings = ref(null);
const favoriteInfoBlocks = ref([]);

const inflight = {
  me: null,
  modules: null,
  settings: null,
  favorites: null,
};

const csrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const fetchJson = async (url) => {
  const response = await fetch(url, {
    headers: {
      'X-CSRF-TOKEN': csrfToken(),
      Accept: 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Request failed: ${url} (${response.status})`);
  }

  return response.json();
};

/**
 * Generic "load once, dedup in-flight" helper.
 *
 * @param {'me'|'modules'|'settings'|'favorites'} key
 * @param {import('vue').Ref} store
 * @param {string} url
 * @param {boolean} force
 */
const loadOnce = (key, store, url, force = false) => {
  if (!force && store.value !== null && inflight[key] === null) {
    return Promise.resolve(store.value);
  }

  if (inflight[key]) {
    return inflight[key];
  }

  inflight[key] = fetchJson(url)
    .then((data) => {
      store.value = data;
      return data;
    })
    .catch((error) => {
      console.error(`Failed to load ${key}:`, error);
      return store.value;
    })
    .finally(() => {
      inflight[key] = null;
    });

  return inflight[key];
};

export function useAppConfig() {
  const loadMe = (force = false) => loadOnce('me', me, '/admin/api/me', force);

  const loadModulesStatus = (force = false) =>
    loadOnce('modules', modulesStatus, '/admin/api/modules/status', force);

  const loadSettings = (force = false) =>
    loadOnce('settings', settings, '/admin/api/settings', force);

  const loadFavoriteInfoBlocks = (force = false) =>
    loadOnce('favorites', favoriteInfoBlocks, '/admin/api/infoblocks/favorites', force);

  const refreshModulesStatus = () => loadModulesStatus(true);
  const refreshSettings = () => loadSettings(true);
  const refreshFavoriteInfoBlocks = () => loadFavoriteInfoBlocks(true);

  /** Drop every cached value (use on login / logout). */
  const reset = () => {
    me.value = null;
    modulesStatus.value = null;
    settings.value = null;
    favoriteInfoBlocks.value = [];
    inflight.me = null;
    inflight.modules = null;
    inflight.settings = null;
    inflight.favorites = null;
  };

  const findModule = (id) =>
    modulesStatus.value?.modules?.find((m) => m.id === id) || null;

  const isModuleInstalled = (id) => !!findModule(id)?.installed;

  return {
    // reactive state
    me,
    modulesStatus,
    settings,
    favoriteInfoBlocks,
    // loaders (cached + in-flight dedup)
    loadMe,
    loadModulesStatus,
    loadSettings,
    loadFavoriteInfoBlocks,
    // invalidation
    refreshModulesStatus,
    refreshSettings,
    refreshFavoriteInfoBlocks,
    reset,
    // helpers
    findModule,
    isModuleInstalled,
  };
}
