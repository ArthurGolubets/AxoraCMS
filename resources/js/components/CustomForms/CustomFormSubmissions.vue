<template>
  <div v-if="form">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center space-x-3">
        <button @click="$router.push('/custom-forms')" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </button>
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Записи: {{ form.name }}</h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Всего: {{ pagination.total }}</p>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <ThemeButton variant="secondary" @click="$router.push(`/custom-forms/${form.id}/edit`)">Настройки формы</ThemeButton>
        <ThemeButton variant="secondary" @click="markAllViewed">Отметить все просмотренными</ThemeButton>
        <ThemeButton v-if="form.admin_can_create" variant="primary" @click="openCreate">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Добавить запись
        </ThemeButton>
      </div>
    </div>

    <div class="mb-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex flex-col md:flex-row gap-3">
      <input v-model="search" @input="onSearch" type="text" placeholder="Поиск по значениям..." class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
      <select v-model="status" @change="loadSubmissions(1)" class="md:w-48 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
        <option value="">Все записи</option>
        <option value="new">Только новые</option>
      </select>
      <button v-if="selected.length" type="button" @click="bulkDelete" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg">
        Удалить выбранные ({{ selected.length }})
      </button>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>
      <div v-else-if="submissions.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">Записей пока нет</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th class="px-4 py-3 w-10">
                <input type="checkbox" :checked="allSelected" @change="toggleAll" class="w-4 h-4 rounded">
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">№</th>
              <th v-for="field in previewFields" :key="field.id" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ field.name }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Дата</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действия</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="submission in submissions"
              :key="submission.id"
              @click="openView(submission)"
              class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50"
              :class="{ 'font-semibold': !submission.viewed_at }"
            >
              <td class="px-4 py-3" @click.stop>
                <input type="checkbox" :value="submission.id" v-model="selected" class="w-4 h-4 rounded">
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                <span class="inline-flex items-center gap-2">
                  <span v-if="!submission.viewed_at" class="w-2 h-2 rounded-full bg-red-500" title="Новая"></span>
                  {{ submission.id }}
                </span>
              </td>
              <td v-for="field in previewFields" :key="field.id" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                <div class="max-w-xs truncate">{{ previewValue(field, submission.data?.[field.code]) }}</div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                {{ formatDate(submission.created_at) }}
                <span v-if="submission.administrator_id" class="ml-1 px-1.5 py-0.5 text-[11px] font-normal rounded bg-gray-100 dark:bg-gray-700">админ</span>
              </td>
              <td class="px-4 py-3 text-right" @click.stop>
                <ActionMenu :items="rowActions(submission)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-600 dark:text-gray-400">Показано {{ pagination.from }} – {{ pagination.to }} из {{ pagination.total }}</div>
      <div class="flex space-x-2">
        <button
          v-for="page in pagination.last_page"
          :key="page"
          @click="loadSubmissions(page)"
          :disabled="page === pagination.current_page"
          class="px-3 py-1 text-sm rounded"
          :class="page === pagination.current_page ? 'text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'"
          :style="page === pagination.current_page ? { backgroundColor: themeColor } : {}"
        >
          {{ page }}
        </button>
      </div>
    </div>

    <!-- View / create / edit side panel -->
    <SidePanel v-if="panel.show" :title="panelTitle" width-class="max-w-2xl" @close="panel.show = false">
      <!-- View -->
      <div v-if="panel.mode === 'view'" class="space-y-5">
        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
          <div>Создана: {{ formatDate(panel.submission.created_at) }}{{ panel.submission.administrator_id ? ' · администратором' : '' }}</div>
          <div v-if="panel.submission.ip">IP: {{ panel.submission.ip }}</div>
        </div>
        <div v-for="field in form.fields" :key="field.id" class="border-t border-gray-100 dark:border-gray-700 pt-4">
          <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400 mb-1">{{ field.name }}</p>
          <InfoBlockFieldInput
            v-if="['entity', 'user'].includes(field.type) && hasValue(panel.submission.data?.[field.code])"
            :model-value="panel.submission.data?.[field.code]"
            :field="field"
            disabled
          />
          <template v-else-if="field.type === 'image' && hasValue(panel.submission.data?.[field.code])">
            <div class="flex flex-wrap gap-2">
              <a v-for="path in asList(panel.submission.data[field.code])" :key="path" :href="fileUrl(path)" target="_blank">
                <img :src="fileUrl(path)" alt="" class="h-24 w-24 object-cover rounded border border-gray-200 dark:border-gray-700">
              </a>
            </div>
          </template>
          <template v-else-if="field.type === 'file' && hasValue(panel.submission.data?.[field.code])">
            <div class="space-y-1">
              <a v-for="path in asList(panel.submission.data[field.code])" :key="path" :href="fileUrl(path)" target="_blank" class="block text-sm text-blue-600 dark:text-blue-400 hover:underline">
                {{ path.split('/').pop() }}
              </a>
            </div>
          </template>
          <p v-else class="text-sm text-gray-900 dark:text-white whitespace-pre-line break-words">{{ previewValue(field, panel.submission.data?.[field.code]) || '—' }}</p>
        </div>
      </div>

      <!-- Create / edit -->
      <form v-else id="custom-form-submission-form" @submit.prevent="saveSubmission" class="space-y-4">
        <div v-for="field in form.fields" :key="field.id">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ field.name }}
            <span v-if="field.is_required" class="text-red-500">*</span>
          </label>
          <InfoBlockFieldInput v-model="panel.data[field.code]" :field="field" />
        </div>
      </form>

      <template #footer>
        <div class="flex justify-end gap-3">
          <template v-if="panel.mode === 'view'">
            <button type="button" @click="deleteSubmission(panel.submission)" class="px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-lg">Удалить</button>
            <ThemeButton v-if="form.admin_can_create" variant="primary" @click="openEdit(panel.submission)">Редактировать</ThemeButton>
          </template>
          <template v-else>
            <button type="button" @click="panel.show = false" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">Отмена</button>
            <ThemeButton type="submit" form="custom-form-submission-form" variant="primary" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </ThemeButton>
          </template>
        </div>
      </template>
    </SidePanel>

    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ThemeButton from '../ThemeButton.vue';
import ActionMenu from '../ActionMenu.vue';
import SidePanel from '../SidePanel.vue';
import ConfirmModal from '../ConfirmModal.vue';
import InfoBlockFieldInput from '../InfoBlockFieldInput.vue';
import { useModal } from '../../composables/useModal';
import { useTheme } from '../../composables/useTheme';
import { useAdminNotifications } from '../../composables/useAdminNotifications';
import { emptyFieldValue } from '../../utils/infoBlockFields';

const route = useRoute();
const router = useRouter();
const { error } = useModal();
const { themeColor } = useTheme();
const { fetchNotifications: refreshBadges } = useAdminNotifications();

const formId = computed(() => route.params.id);
const form = ref(null);
const submissions = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const status = ref('');
const selected = ref([]);
const confirmModal = ref(null);
const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 });
let searchTimer = null;

const panel = reactive({ show: false, mode: 'view', submission: null, data: {} });

const panelTitle = computed(() => ({
  view: `Запись №${panel.submission?.id}`,
  create: 'Новая запись',
  edit: `Редактирование записи №${panel.submission?.id}`,
}[panel.mode]));

const previewFields = computed(() => (form.value?.fields || [])
  .filter((f) => !['image', 'file', 'entity', 'user'].includes(f.type))
  .slice(0, 3));

const allSelected = computed(() => submissions.value.length > 0 && selected.value.length === submissions.value.length);

const csrfHeaders = () => ({
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
  Accept: 'application/json',
});

// --- Formatting ------------------------------------------------------------

const hasValue = (v) => !(v === null || v === undefined || v === '' || (Array.isArray(v) && v.length === 0));
const asList = (v) => (Array.isArray(v) ? v : (hasValue(v) ? [v] : []));
const fileUrl = (path) => (/^(https?:)?\/\//.test(path) || path.startsWith('/') ? path : `/storage/${path}`);
const formatDate = (date) => (date ? new Date(date).toLocaleString('ru-RU') : '');

const previewValue = (field, value) => {
  if (field.type === 'bool') return value ? 'Да' : 'Нет';
  if (!hasValue(value)) return '';
  const single = (v) => {
    if (field.type === 'enum') return field.settings?.options?.find((o) => o.code === v)?.title || v;
    if (field.type === 'date') return new Date(v).toLocaleDateString('ru-RU');
    if (['image', 'file'].includes(field.type)) return String(v).split('/').pop();
    if (['entity', 'user'].includes(field.type)) return `#${v}`;
    return String(v);
  };
  return asList(value).map(single).join(', ');
};

// --- Loading ---------------------------------------------------------------

const loadForm = async () => {
  const response = await fetch(`/admin/api/custom-forms/${formId.value}`, { headers: { Accept: 'application/json' } });
  if (!response.ok) {
    error('Форма не найдена');
    router.push('/custom-forms');
    return;
  }
  form.value = await response.json();
};

const loadSubmissions = async (page = 1) => {
  loading.value = true;
  selected.value = [];
  try {
    const params = new URLSearchParams({ page });
    if (search.value) params.append('search', search.value);
    if (status.value) params.append('status', status.value);
    const response = await fetch(`/admin/api/custom-forms/${formId.value}/submissions?${params}`, { headers: { Accept: 'application/json' } });
    if (response.ok) {
      const data = await response.json();
      submissions.value = data.data || [];
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
        from: data.from || 0,
        to: data.to || 0,
      };
    }
  } catch (e) {
    error('Не удалось загрузить записи');
  } finally {
    loading.value = false;
  }
};

const onSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadSubmissions(1), 300);
};

const toggleAll = () => {
  selected.value = allSelected.value ? [] : submissions.value.map((s) => s.id);
};

// --- Panel -----------------------------------------------------------------

const openView = async (submission) => {
  panel.mode = 'view';
  panel.submission = submission;
  panel.show = true;

  const response = await fetch(`/admin/api/custom-forms/${formId.value}/submissions/${submission.id}`, { headers: { Accept: 'application/json' } });
  if (response.ok) {
    const fresh = await response.json();
    panel.submission = fresh;
    const row = submissions.value.find((s) => s.id === fresh.id);
    if (row && !row.viewed_at) {
      row.viewed_at = fresh.viewed_at;
      refreshBadges();
    }
  }
};

const blankData = () => Object.fromEntries(form.value.fields.map((f) => [f.code, emptyFieldValue(f)]));

const openCreate = () => {
  panel.mode = 'create';
  panel.submission = null;
  panel.data = blankData();
  panel.show = true;
};

const openEdit = (submission) => {
  panel.mode = 'edit';
  panel.submission = submission;
  panel.data = { ...blankData(), ...JSON.parse(JSON.stringify(submission.data || {})) };
  panel.show = true;
};

const rowActions = (submission) => [
  { label: 'Просмотр', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', action: () => openView(submission) },
  { label: 'Редактировать', hidden: !form.value.admin_can_create, icon: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z', action: () => openEdit(submission) },
  { divider: true },
  { label: 'Удалить', danger: true, icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', action: () => deleteSubmission(submission) },
];

// --- Mutations -------------------------------------------------------------

const saveSubmission = async () => {
  saving.value = true;
  try {
    const isCreate = panel.mode === 'create';
    const url = isCreate
      ? `/admin/api/custom-forms/${formId.value}/submissions`
      : `/admin/api/custom-forms/${formId.value}/submissions/${panel.submission.id}`;
    const response = await fetch(url, {
      method: isCreate ? 'POST' : 'PUT',
      headers: csrfHeaders(),
      body: JSON.stringify({ data: panel.data }),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
      throw new Error(firstError || data.message || 'Ошибка при сохранении');
    }
    panel.show = false;
    loadSubmissions(isCreate ? 1 : pagination.value.current_page);
  } catch (e) {
    error(e.message);
  } finally {
    saving.value = false;
  }
};

const deleteSubmission = async (submission) => {
  const confirmed = await confirmModal.value.open({
    title: 'Удалить запись?',
    message: `Запись №${submission.id} будет удалена без возможности восстановления.`,
    confirmText: 'Удалить',
    dangerMode: true,
  });
  if (!confirmed) return;

  const response = await fetch(`/admin/api/custom-forms/${formId.value}/submissions/${submission.id}`, { method: 'DELETE', headers: csrfHeaders() });
  if (response.ok) {
    panel.show = false;
    loadSubmissions(pagination.value.current_page);
    refreshBadges();
  } else {
    error('Не удалось удалить запись');
  }
};

const bulkDelete = async () => {
  const confirmed = await confirmModal.value.open({
    title: 'Удалить выбранные записи?',
    message: `Будет удалено записей: ${selected.value.length}. Действие нельзя отменить.`,
    confirmText: 'Удалить',
    dangerMode: true,
  });
  if (!confirmed) return;

  const response = await fetch(`/admin/api/custom-forms/${formId.value}/submissions/bulk-delete`, {
    method: 'POST',
    headers: csrfHeaders(),
    body: JSON.stringify({ ids: selected.value }),
  });
  if (response.ok) {
    loadSubmissions(1);
    refreshBadges();
  } else {
    error('Не удалось удалить записи');
  }
};

const markAllViewed = async () => {
  const response = await fetch(`/admin/api/custom-forms/${formId.value}/submissions/mark-viewed`, { method: 'POST', headers: csrfHeaders() });
  if (response.ok) {
    submissions.value.forEach((s) => { s.viewed_at = s.viewed_at || new Date().toISOString(); });
    refreshBadges();
  }
};

/**
 * ?open=<id> (links from the notification bell) opens that record.
 */
const openFromQuery = () => {
  const openId = Number(route.query.open);
  if (!openId || !form.value) return;
  openView(submissions.value.find((s) => s.id === openId) || { id: openId, data: {} });
  router.replace({ query: {} });
};

watch(() => route.query.open, openFromQuery);

onMounted(async () => {
  await loadForm();
  if (!form.value) return;
  await loadSubmissions();
  openFromQuery();
});
</script>
