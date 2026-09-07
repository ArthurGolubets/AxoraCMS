import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import { useAppConfig } from './composables/useAppConfig';
import Dashboard from './components/Dashboard.vue';
import Administrators from './components/Administrators.vue';
import Settings from './components/Settings.vue';
import EnvironmentSettings from './components/EnvironmentSettings.vue';
import ActivityLogs from './components/ActivityLogs.vue';
import Modules from './components/Modules.vue';
import CheckSystem from './components/CheckSystem.vue';
import CatalogTree from './components/CatalogTree.vue';
import CatalogView from './components/CatalogView.vue';
import CatalogForm from './components/CatalogForm.vue';
import ProductsList from './components/ProductsList.vue';
import ProductView from './components/ProductView.vue';
import ProductForm from './components/ProductForm.vue';
import UsersEmails from './components/UsersEmails.vue';
import UsersEmailView from './components/UsersEmailView.vue';
import Comments from './components/Comments.vue';
import CommentView from './components/CommentView.vue';
import UserRequests from './components/UserRequests.vue';
import UserRequestView from './components/UserRequestView.vue';
import Orders from './components/Orders.vue';
import OrderView from './components/OrderView.vue';
import OrderCreate from './components/OrderCreate.vue';
import OrderEdit from './components/OrderEdit.vue';
import Transactions from './components/Transactions.vue';
import Promocodes from './components/Promocodes.vue';
import OrdersSettings from './components/OrdersSettings.vue';
import InfoBlocksList from './components/InfoBlocksList.vue';
import InfoBlockForm from './components/InfoBlockForm.vue';
import InfoBlockFields from './components/InfoBlockFields.vue';
import InfoBlockElements from './components/InfoBlockElements.vue';
import InfoBlockElementForm from './components/InfoBlockElementForm.vue';
import InfoBlockSections from './components/InfoBlockSections.vue';
import MenuList from './components/Menus/MenuList.vue';
import MenuItems from './components/Menus/MenuItems.vue';
import FiltersList from './components/FiltersList.vue';
import FilterForm from './components/FilterForm.vue';
import FilterView from './components/FilterView.vue';
import PagesList from './components/Pages/PagesList.vue';
import PageForm from './components/Pages/PageForm.vue';
import PagesSettings from './components/Pages/PagesSettings.vue';
import ContentSettings from './views/ContentSettings.vue';
import TelegramSettings from './components/Integrations/TelegramSettings.vue';
import YookassaSettings from './components/Integrations/YookassaSettings.vue';
import CommerceMLSettings from './components/Integrations/CommerceMLSettings.vue';
import Error403 from './components/Error403.vue';
import Error404 from './components/Error404.vue';
import './style.css';

// Create router
const router = createRouter({
    history: createWebHistory('/admin'),
    routes: [
        {
            path: '/',
            name: 'dashboard',
            component: Dashboard
        },
        {
            path: '/administrators',
            name: 'administrators',
            component: Administrators
        },
        {
            path: '/settings',
            name: 'settings',
            component: Settings
        },
        {
            path: '/environment',
            name: 'environment',
            component: EnvironmentSettings
        },
        {
            path: '/logs',
            name: 'logs',
            component: ActivityLogs
        },
        {
            path: '/modules',
            name: 'modules',
            component: Modules
        },
        {
            path: '/modules/check-system',
            name: 'check-system',
            component: CheckSystem
        },
        {
            path: '/catalog',
            name: 'catalog',
            component: CatalogTree
        },
        {
            path: '/catalog/create',
            name: 'catalog-create',
            component: CatalogForm
        },
        {
            path: '/catalog/:id',
            name: 'catalog-view',
            component: CatalogView
        },
        {
            path: '/catalog/:id/edit',
            name: 'catalog-edit',
            component: CatalogForm
        },
        {
            path: '/products',
            name: 'products',
            component: ProductsList
        },
        {
            path: '/products/create',
            name: 'product-create',
            component: ProductForm
        },
        {
            path: '/products/:id',
            name: 'product-view',
            component: ProductView
        },
        {
            path: '/products/:id/edit',
            name: 'product-edit',
            component: ProductForm
        },
        {
            path: '/users-emails',
            name: 'users-emails',
            component: UsersEmails
        },
        {
            path: '/users-emails/:id',
            name: 'users-email-view',
            component: UsersEmailView
        },
        {
            path: '/comments',
            name: 'comments',
            component: Comments
        },
        {
            path: '/comments/:id',
            name: 'comment-view',
            component: CommentView
        },
        {
            path: '/user-requests',
            name: 'user-requests',
            component: UserRequests
        },
        {
            path: '/user-requests/:id',
            name: 'user-request-view',
            component: UserRequestView
        },
        {
            path: '/orders',
            name: 'orders',
            component: Orders
        },
        {
            path: '/orders/create',
            name: 'order-create',
            component: OrderCreate
        },
        {
            path: '/orders/:id/edit',
            name: 'order-edit',
            component: OrderEdit
        },
        {
            path: '/orders/:id',
            name: 'order-view',
            component: OrderView
        },
        {
            path: '/transactions',
            name: 'transactions',
            component: Transactions
        },
        {
            path: '/promocodes',
            name: 'promocodes',
            component: Promocodes
        },
        {
            path: '/orders-settings',
            name: 'orders-settings',
            component: OrdersSettings
        },
        {
            path: '/infoblocks',
            name: 'infoblocks',
            component: InfoBlocksList
        },
        {
            path: '/infoblocks/create',
            name: 'infoblock-create',
            component: InfoBlockForm
        },
        {
            path: '/infoblocks/:id/edit',
            name: 'infoblock-edit',
            component: InfoBlockForm
        },
        {
            path: '/infoblocks/:id/fields',
            name: 'infoblock-fields',
            component: InfoBlockFields
        },
        {
            path: '/infoblocks/:id/elements',
            name: 'infoblock-elements',
            component: InfoBlockElements
        },
        {
            path: '/infoblocks/:id/sections',
            name: 'infoblock-sections',
            component: InfoBlockSections
        },
        {
            path: '/infoblocks/:infoBlockId/elements/create',
            name: 'infoblock-element-create',
            component: InfoBlockElementForm
        },
        {
            path: '/infoblocks/:infoBlockId/elements/:elementId/edit',
            name: 'infoblock-element-edit',
            component: InfoBlockElementForm
        },
        {
            path: '/menus',
            name: 'menus',
            component: MenuList
        },
        {
            path: '/menus/:id/items',
            name: 'menu-items',
            component: MenuItems
        },
        {
            path: '/filters',
            name: 'filters',
            component: FiltersList
        },
        {
            path: '/filters/create',
            name: 'filter-create',
            component: FilterForm
        },
        {
            path: '/filters/:id',
            name: 'filter-view',
            component: FilterView
        },
        {
            path: '/filters/:id/edit',
            name: 'filter-edit',
            component: FilterForm
        },
        {
            path: '/pages',
            name: 'pages',
            component: PagesList
        },
        {
            path: '/pages/create',
            name: 'pages-create',
            component: PageForm
        },
        {
            path: '/pages/:id/edit',
            name: 'pages-edit',
            component: PageForm
        },
        {
            path: '/pages-settings',
            name: 'pages-settings',
            component: PagesSettings
        },
        {
            path: '/content-settings',
            name: 'content-settings',
            component: ContentSettings
        },
        {
            path: '/integrations/telegram',
            name: 'telegram-settings',
            component: TelegramSettings
        },
        {
            path: '/integrations/yookassa',
            name: 'yookassa-settings',
            component: YookassaSettings
        },
        {
            path: '/integrations/commerceml',
            name: 'commerceml-settings',
            component: CommerceMLSettings
        },
        {
            path: '/403',
            name: 'error-403',
            component: Error403
        },
        {
            path: '/404',
            name: 'error-404',
            component: Error404
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: Error404
        }
    ]
});

// Route -> required module id. A route missing from here has no module gate.
const MODULE_GATED_ROUTES = {
    shop: ['catalog', 'catalog-create', 'catalog-view', 'catalog-edit', 'products', 'product-create', 'product-view', 'product-edit', 'filters', 'filter-create', 'filter-view', 'filter-edit'],
    callback: ['users-emails', 'users-email-view', 'comments', 'comment-view', 'user-requests', 'user-request-view'],
    commerce: ['orders', 'order-create', 'order-edit', 'order-view', 'transactions', 'promocodes', 'orders-settings'],
    infoblocks: ['infoblocks', 'infoblock-create', 'infoblock-edit', 'infoblock-fields', 'infoblock-elements', 'infoblock-sections', 'infoblock-element-create', 'infoblock-element-edit'],
};

const PRIVILEGED_ONLY_ROUTES = ['settings', 'environment', 'logs', 'modules', 'administrators', ...MODULE_GATED_ROUTES.commerce];

// Global navigation guard.
// `me` and `modules/status` come from useAppConfig(), which fetches each once
// per SPA session and dedups concurrent callers, so a single navigation no
// longer fires up to 5x /admin/api/modules/status.
router.beforeEach(async (to, from, next) => {
    try {
        const { loadMe, loadModulesStatus } = useAppConfig();
        const userData = await loadMe();

        if (!userData) {
            next();
            return;
        }

        const isPrivileged = userData.role === 'super_admin' || userData.role === 'administrator';

        if (PRIVILEGED_ONLY_ROUTES.includes(to.name) && !isPrivileged) {
            next({ name: 'error-403' });
            return;
        }

        const gatedModuleId = Object.keys(MODULE_GATED_ROUTES).find(id => MODULE_GATED_ROUTES[id].includes(to.name));

        if (to.name === 'modules' || gatedModuleId) {
            const statusData = await loadModulesStatus();

            if (statusData) {
                if (to.name === 'modules' && !statusData.show_modules_page) {
                    next({ name: 'error-404' });
                    return;
                }

                if (gatedModuleId) {
                    const module = statusData.modules?.find(m => m.id === gatedModuleId);
                    if (!module?.installed) {
                        next({ name: 'error-404' });
                        return;
                    }
                }
            }
        }

        next();
    } catch (error) {
        console.error('Navigation guard error:', error);
        next();
    }
});

// Create app
const app = createApp(App);
app.use(router);
app.mount('#app');
