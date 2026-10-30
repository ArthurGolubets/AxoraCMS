<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Шаблоны писем</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Готовые письма, которые система отправляет клиентам и сотрудникам. Собираются из блоков: заголовок, текст, кнопка, картинка. В правилах остаётся выбрать нужный шаблон</p>
      </div>
      <ThemeButton variant="primary" @click="$router.push('/message-templates/create')">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Создать шаблон
      </ThemeButton>
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">Загрузка...</div>

    <div v-else-if="!templates.length" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-10 text-center">
      <p class="text-base font-medium text-gray-900 dark:text-white">Шаблонов пока нет</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Нажмите «Создать шаблон» и соберите первое письмо из блоков.</p>
    </div>

    <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-600 dark:text-gray-300">
          <tr>
            <th class="px-5 py-3 font-medium">Название</th>
            <th class="px-5 py-3 font-medium hidden md:table-cell">Тема</th>
            <th class="px-5 py-3 font-medium hidden lg:table-cell">Для события</th>
            <th class="px-5 py-3 font-medium">Где используется</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr v-for="template in templates" :key="template.id">
            <td class="px-5 py-3">
              <button type="button" @click="$router.push(`/message-templates/${template.id}/edit`)" class="font-medium text-gray-900 dark:text-white hover:underline text-left">{{ template.name }}</button>
              <span v-if="!template.is_active" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">выключен</span>
            </td>
            <td class="px-5 py-3 text-gray-600 dark:text-gray-400 hidden md:table-cell">{{ template.subject }}</td>
            <td class="px-5 py-3 text-gray-600 dark:text-gray-400 hidden lg:table-cell">{{ eventLabel(template.event) }}</td>
            <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ template.usage ? `в правилах: ${template.usage}` : 'пока нигде' }}</td>
            <td class="px-5 py-3">
              <div class="flex justify-end gap-1">
                <button type="button" title="Изменить шаблон" @click="$router.push(`/message-templates/${template.id}/edit`)" class="p-2 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" title="Сделать копию" @click="duplicate(template)" class="p-2 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
                <button type="button" title="Удалить" @click="remove(template)" class="p-2 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ThemeButton from '../ThemeButton.vue';
import { useModal } from '../../composables/useModal';
import { api } from '../../composables/useMessageManager';

const { confirm, error } = useModal();

const loading = ref(true);
const templates = ref([]);
const meta = ref({ events: [], common_variables: [] });

const eventLabel = (code) => meta.value.events.find(e => e.code === code)?.label || (code ? code : 'Для любого');

const load = async () => {
  loading.value = true;
  try {
    templates.value = (await api('GET', '/templates')).data;
  } catch (e) {
    await error(e.message);
  } finally {
    loading.value = false;
  }
};

const duplicate = async (template) => {
  try {
    await api('POST', `/templates/${template.id}/duplicate`);
    await load();
  } catch (e) {
    await error(e.message);
  }
};

const remove = async (template) => {
  if (!await confirm('Удаление шаблона', `Удалить шаблон «${template.name}»?`)) return;
  try {
    await api('DELETE', `/templates/${template.id}`);
    await load();
  } catch (e) {
    await error(e.message);
  }
};

onMounted(async () => {
  await load();
  try {
    meta.value = await api('GET', '/meta');
  } catch (e) {
    // Without meta the editor only lacks the event picker.
  }
});
</script>
