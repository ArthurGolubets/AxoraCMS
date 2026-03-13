import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import Dashboard from './components/Dashboard.vue';
import Administrators from './components/Administrators.vue';
import Settings from './components/Settings.vue';
import EnvironmentSettings from './components/EnvironmentSettings.vue';
import ActivityLogs from './components/ActivityLogs.vue';
import Modules from './components/Modules.vue';
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

// Global navigation guard
router.beforeEach(async (to, from, next) => {
    // Get current user data
    try {
        const response = await fetch('/admin/api/me', {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            next();
            return;
        }

        const userData = await response.json();

        // Check access to settings pages (only for super_admin and administrator)
        const settingsRoutes = ['settings', 'environment', 'logs', 'modules', 'administrators'];
        if (settingsRoutes.includes(to.name)) {
            if (userData.role !== 'super_admin' && userData.role !== 'administrator') {
                next({ name: 'error-403' });
                return;
            }
        }

        // Check if accessing modules page
        if (to.name === 'modules') {
            // Check if modules page is enabled
            const modulesResponse = await fetch('/admin/api/modules/status', {
                headers: { 'Accept': 'application/json' }
            });

            if (modulesResponse.ok) {
                const statusData = await modulesResponse.json();
                if (!statusData.show_modules_page) {
                    next({ name: 'error-404' });
                    return;
                }
            }
        }

        // Check if accessing shop module routes
        const shopRoutes = ['catalog', 'catalog-create', 'catalog-view', 'catalog-edit', 'products', 'product-create', 'product-view', 'product-edit', 'filters', 'filter-create', 'filter-view', 'filter-edit'];
        if (shopRoutes.includes(to.name)) {
            // Check if shop module is installed
            const modulesResponse = await fetch('/admin/api/modules/status', {
                headers: { 'Accept': 'application/json' }
            });

            if (modulesResponse.ok) {
                const modulesData = await modulesResponse.json();
                const shopModule = modulesData.modules?.find(m => m.id === 'shop');

                if (!shopModule?.installed) {
                    next({ name: 'error-404' });
                    return;
                }
            }
        }

        // Check if accessing callback module routes
        const callbackRoutes = ['users-emails', 'users-email-view', 'comments', 'comment-view', 'user-requests', 'user-request-view'];
        if (callbackRoutes.includes(to.name)) {
            // Check if callback module is installed
            const modulesResponse = await fetch('/admin/api/modules/status', {
                headers: { 'Accept': 'application/json' }
            });

            if (modulesResponse.ok) {
                const modulesData = await modulesResponse.json();
                const callbackModule = modulesData.modules?.find(m => m.id === 'callback');

                if (!callbackModule?.installed) {
                    next({ name: 'error-404' });
                    return;
                }
            }
        }

        // Check if accessing commerce module routes (only for super_admin and administrator)
        const commerceRoutes = ['orders', 'order-create', 'order-edit', 'order-view', 'transactions', 'promocodes', 'orders-settings'];
        if (commerceRoutes.includes(to.name)) {
            // Check role first
            if (userData.role !== 'super_admin' && userData.role !== 'administrator') {
                next({ name: 'error-403' });
                return;
            }

            // Check if commerce module is installed
            const modulesResponse = await fetch('/admin/api/modules/status', {
                headers: { 'Accept': 'application/json' }
            });

            if (modulesResponse.ok) {
                const modulesData = await modulesResponse.json();
                const commerceModule = modulesData.modules?.find(m => m.id === 'commerce');

                if (!commerceModule?.installed) {
                    next({ name: 'error-404' });
                    return;
                }
            }
        }

        // Check if accessing infoblocks module routes
        const infoblocksRoutes = ['infoblocks', 'infoblock-create', 'infoblock-edit', 'infoblock-fields', 'infoblock-elements', 'infoblock-element-create', 'infoblock-element-edit'];
        if (infoblocksRoutes.includes(to.name)) {
            // Check if infoblocks module is installed
            const modulesResponse = await fetch('/admin/api/modules/status', {
                headers: { 'Accept': 'application/json' }
            });

            if (modulesResponse.ok) {
                const modulesData = await modulesResponse.json();
                const infoblocksModule = modulesData.modules?.find(m => m.id === 'infoblocks');

                if (!infoblocksModule?.installed) {
                    next({ name: 'error-404' });
                    return;
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
