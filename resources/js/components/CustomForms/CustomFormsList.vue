<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Свои формы</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Анкеты, вопросы и ответы, заявки — с любым набором полей</p>
      </div>
      <ThemeButton variant="primary" @click="$router.push('/custom-forms/create')">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Создать форму
      </ThemeButton>
    </div>

    <div class="mb-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
      <input v-model="search" @input="onSearch" type="text" placeholder="Поиск по названию или коду..." class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>
      <div v-else-if="forms.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
        Форм пока нет. Нажмите «Создать форму».
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Название</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Код</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Полей</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Записей</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Статус</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="form in forms" :key="form.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4">
              <button type="button" @click="openSubmissions(form)" class="font-medium text-left text-gray-900 dark:text-white hover:underline">{{ form.name }}</button>
              <div v-if="form.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ form.description }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ form.code }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ form.fields_count }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
              <div class="flex items-center gap-2">
                {{ form.submissions_count }}
                <span v-if="form.new_submissions_count" class="px-1.5 py-0.5 text-[11px] font-semibold rounded-full bg-red-500 text-white">+{{ form.new_submissions_count }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm">
              <span v-if="form.is_active" class="px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">Активна</span>
              <span v-else class="px-2 py-1 text-xs bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 rounded">Отключена</span>
            </td>
            <td class="px-6 py-4 text-right">
              <ActionMenu :items="formActions(form)" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import ThemeButton from '../ThemeButton.vue';
import ActionMenu from '../ActionMenu.vue';
import ConfirmModal from '../ConfirmModal.vue';
import { useModal } from '../../composables/useModal';
import { useAdminNotifications } from '../../composables/useAdminNotifications';

const router = useRouter();
const { error } = useModal();
const { fetchNotifications: refreshBadges } = useAdminNotifications();

const forms = ref([]);
const loading = ref(false);
const search = ref('');
const confirmModal = ref(null);
let searchTimer = null;

const ICONS = {
  submissions: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
  edit: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
  delete: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
};

const formActions = (form) => [
  { label: 'Записи', icon: ICONS.submissions, action: () => openSubmissions(form) },
  { label: 'Редактировать', icon: ICONS.edit, action: () => router.push(`/custom-forms/${form.id}/edit`) },
  { divider: true },
  { label: 'Удалить', icon: ICONS.delete, danger: true, action: () => deleteForm(form) },
];

const openSubmissions = (form) => router.push(`/custom-forms/${form.id}/submissions`);

const loadForms = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    const response = await fetch(`/admin/api/custom-forms?${params}`, { headers: { Accept: 'application/json' } });
    if (response.ok) {
      forms.value = await response.json();
    }
  } catch (e) {
    error('Не удалось загрузить формы');
  } finally {
    loading.value = false;
  }
};

const onSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadForms, 300);
};

const deleteForm = async (form) => {
  const confirmed = await confirmModal.value.open({
    title: 'Удалить форму?',
    message: `Форма «${form.name}», её поля и все записи (${form.submissions_count}) будут удалены без возможности восстановления.`,
    confirmText: 'Удалить',
    dangerMode: true,
  });
  if (!confirmed) return;

  const response = await fetch(`/admin/api/custom-forms/${form.id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
      Accept: 'application/json',
    },
  });
  if (response.ok) {
    loadForms();
    refreshBadges();
  } else {
    error('Не удалось удалить форму');
  }
};

onMounted(loadForms);
</script>
