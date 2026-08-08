# AxoraCMS - Admin Panel for Laravel 12

Полнофункциональная админ-панель для Laravel 12 с Vue.js, ролевой системой и современным дизайном на Tailwind CSS.

## ✨ Возможности

- 🚀 **Отдельная админ-панель** с кастомными роутами
- 🔐 **Независимая аутентификация** через модель TAdministrators
- 👥 **Ролевая система** (Супер админ, Администратор, Менеджер)
- 🎨 **Vue.js 3 + Vue Router** SPA frontend
- 🌓 **Тёмная/светлая тема** с сохранением предпочтений в localStorage
- 🎯 **Tailwind CSS** для современного дизайна
- 📦 **Простая установка** через одну команду
- ⚡ **Автоматическая сборка** фронтенда при установке
- 🎯 **Настраиваемое название** панели через конфиг
- 🔄 **Восстановление пароля**
- 📱 **Адаптивный дизайн**
- 🌐 **Полная русификация** интерфейса

## 📋 Требования

- PHP ^8.2
- Laravel ^12.0
- Node.js ^18.0 & NPM

## 🚀 Установка

### Быстрая установка (Рекомендуется)

```bash
# 1. Установить пакет через Composer
composer require holartweb/axora-cms

# 2. Запустить команду установки (выполнит все шаги автоматически)
php artisan axoracms:install
```

Команда `axoracms:install` выполнит:
1. ✅ Публикацию конфигурации
2. ✅ Запуск миграций
3. ✅ Установку npm зависимостей (если нужно)
4. ✅ Сборку фронтенда
5. ✅ Публикацию ассетов
6. ✅ Создание супер-администратора (опционально)

### Локальная разработка

Для локальной разработки добавьте в `composer.json` основного проекта:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/holartweb/axora-cms"
        }
    ]
}
```

Затем:

```bash
composer require holartweb/axora-cms
php artisan axoracms:install
```

### Ручная установка (если нужно)

```bash
# 1. Публикация конфигурации
php artisan vendor:publish --tag=axora-cms-config

# 2. Запуск миграций
php artisan migrate

# 3. Сборка фронтенда
cd packages/holartweb/axora-cms
npm install
npm run build
cd ../../..

# 4. Публикация ассетов
php artisan vendor:publish --tag=axora-cms-assets

# 5. Создание администратора
php artisan tinker
```

```php
\HolartWeb\AxoraCMS\Models\TAdministrator::create([
    'name' => 'Супер Администратор',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'role' => 'super_admin',
    'is_active' => true,
]);
```

## ⚙️ Конфигурация

### Основные настройки

В файле `config/axora-cms.php` или через `.env`:

```php
// Название панели (отображается в заголовках)
'name' => env('HOLART_CMS_NAME', 'AxoraCMS'),

// Префикс роутов
'route_prefix' => env('HOLART_CMS_PREFIX', 'admin'),
```

В `.env`:

```env
HOLART_CMS_NAME="Моя Админка"
HOLART_CMS_PREFIX=admin
```

## 👥 Ролевая система

### Роли и разрешения

**Супер администратор** (`super_admin`):
- ✅ Полный доступ ко всем функциям
- ✅ Управление администраторами
- ✅ Просмотр логов
- ✅ Назначение любых ролей

**Администратор** (`administrator`):
- ✅ Управление заказами, каталогами, товарами
- ✅ Ограниченный доступ к логам
- ✅ Назначение роли "Менеджер"
- ❌ Добавление администраторов
- ❌ Назначение роли "Супер админ"

**Менеджер** (`manager`):
- ✅ Просмотр и редактирование заказов
- ✅ Просмотр и редактирование каталогов и товаров
- ❌ Управление администраторами
- ❌ Доступ к логам

### Использование ролей

В контроллерах:

```php
// Проверка роли
if ($admin->isSuperAdmin()) {
    // Действие только для супер админа
}

// Проверка разрешения
if ($admin->hasPermission('manage_orders')) {
    // Действие для тех, у кого есть разрешение
}

// Проверка возможности назначить роль
use HolartWeb\AxoraCMS\Enums\AdminRole;

if ($admin->canAssignRole(AdminRole::ADMINISTRATOR)) {
    // Может назначить роль администратора
}
```

В роутах с middleware:

```php
use HolartWeb\AxoraCMS\Http\Middleware\CheckAdminRole;
use HolartWeb\AxoraCMS\Http\Middleware\CheckAdminPermission;

// Доступ только для супер админа
Route::middleware(['auth:admin', CheckAdminRole::class.':super_admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
});

// Доступ по разрешению
Route::middleware(['auth:admin', CheckAdminPermission::class.':manage_orders'])->group(function () {
    Route::resource('/admin/orders', OrderController::class);
});
```

## 🎨 Интерфейс

### Возможности UI

- **Современный дизайн** на Tailwind CSS с градиентами
- **Переключение темы** (светлая/тёмная) с автосохранением в localStorage
- **Адаптивная верстка** для всех устройств
- **Информация о пользователе** с аватаром и ролью в сайдбаре
- **Левое боковое меню** с навигацией
- **Красивые формы** авторизации и восстановления пароля
- **SVG иконки** для всех элементов интерфейса

### Страницы

- `/admin/login` - Авторизация
- `/admin/forgot-password` - Восстановление пароля
- `/admin/` - Главная панель (Dashboard)

## 🛠️ Разработка

### Сборка frontend

Разработка с hot-reload:

```bash
cd packages/holartweb/axora-cms
npm run dev
```

Production сборка:

```bash
npm run build
```

### Структура проекта

```
packages/holartweb/axora-cms/
├── config/
│   └── axora-cms.php (конфигурация с названием панели)
├── database/
│   └── migrations/
│       └── 2024_01_01_000001_create_t_administrators_table.php
├── resources/
│   ├── js/
│   │   ├── App.vue (Главный компонент с темной/светлой темой)
│   │   ├── app.js
│   │   ├── style.css (Tailwind CSS)
│   │   └── components/
│   │       └── Dashboard.vue
│   └── views/
│       ├── auth/
│       │   └── login.blade.php (Красивая страница входа с Tailwind)
│       └── dashboard.blade.php
├── routes/
│   └── admin.php
├── src/
│   ├── Console/
│   │   └── InstallCommand.php (с автосборкой фронта)
│   ├── Enums/
│   │   └── AdminRole.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php
│   │   │   │   └── ForgotPasswordController.php
│   │   │   └── DashboardController.php
│   │   └── Middleware/
│   │       ├── CheckAdminRole.php
│   │       └── CheckAdminPermission.php
│   ├── Models/
│   │   └── TAdministrator.php
│   └── AxoraCMSServiceProvider.php
├── tailwind.config.js
├── postcss.config.js
├── composer.json
├── package.json
├── vite.config.js
└── README.md
```

## 📝 Примеры использования

### Добавление кастомных роутов

```php
// routes/web.php
use HolartWeb\AxoraCMS\Enums\AdminRole;

Route::middleware(['auth:admin'])->group(function () {
    // Доступно для всех авторизованных админов
    Route::get('/admin/profile', [ProfileController::class, 'show']);
});

// Только для супер админа и администратора
Route::middleware(['auth:admin', 'admin.role:super_admin,administrator'])->group(function () {
    Route::resource('/admin/products', ProductController::class);
});
```

### Получение текущего админа

```php
use Illuminate\Support\Facades\Auth;

$admin = Auth::guard('admin')->user();
echo $admin->name; // Имя администратора
echo $admin->role->label(); // Название роли на русском
```

### Переключение темы из кода

```javascript
// В Vue компонентах
const toggleTheme = () => {
  isDark.value = !isDark.value;
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light');

  if (isDark.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
};
```

## 🔒 Безопасность

- Пароли хешируются с помощью bcrypt
- CSRF защита на всех формах
- Session-based аутентификация
- Middleware для проверки ролей и разрешений
- Проверка активности пользователя (`is_active`)

## 🔄 Обновление

```bash
composer update holartweb/axora-cms
php artisan axoracms:install --force
```

## 🐛 Troubleshooting

### Проблемы со сборкой фронтенда

Если автоматическая сборка не сработала:

```bash
cd packages/holartweb/axora-cms
npm install
npm run build
php artisan vendor:publish --tag=axora-cms-assets --force
```

### Не работает темная тема

Убедитесь, что в `tailwind.config.js` включен `darkMode: 'class'`:

```javascript
export default {
  darkMode: 'class',
  // ...
}
```

## 📄 Лицензия

MIT License

## 👨‍💻 Автор

**HolartWeb**
Email: info@holartweb.com

---

💡 **Совет:** Команда `php artisan axoracms:install` автоматически выполнит все необходимые шаги для установки!
