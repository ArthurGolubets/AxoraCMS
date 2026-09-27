<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - {{ config('axora-cms.name', 'AxoraCMS') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        @keyframes wave {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-20px) translateX(10px); }
        }

        .wave {
            position: absolute;
            width: 200%;
            height: 200%;
            opacity: 0.3;
        }

        .wave:nth-child(1) {
            background: radial-gradient(circle, var(--theme-color) 2px, transparent 2px);
            background-size: 50px 50px;
            animation: wave 15s ease-in-out infinite;
        }

        .wave:nth-child(2) {
            background: radial-gradient(circle, var(--theme-color) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: wave 10s ease-in-out infinite reverse;
            animation-delay: -5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in { animation: fadeIn 0.6s ease-out; }
    </style>
</head>
<body class="min-h-screen transition-colors duration-300" id="body">
    <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
        <!-- Animated Waves Background -->
        <div class="wave"></div>
        <div class="wave"></div>

        <!-- Theme Toggle -->
        <button id="theme-toggle" class="absolute top-6 right-6 z-50 p-3 rounded-full bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition-all">
            <svg id="sun-icon" class="w-6 h-6 text-gray-800 hidden" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
            </svg>
            <svg id="moon-icon" class="w-6 h-6 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
            </svg>
        </button>

        <!-- Login Card -->
        <div class="relative z-10 w-full max-w-md fade-in">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 transition-colors duration-300">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ config('axora-cms.name', 'AxoraCMS') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">Панель администратора</p>
                </div>

                <!-- Error Alert -->
                <div id="error-alert" class="hidden mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-sm"></div>

                <!-- Login Form -->
                <form id="login-form" method="POST" action="{{ route('axora-cms.login.post') }}" class="space-y-5">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <input type="email"
                               id="email"
                               name="email"
                               required
                               autofocus
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-opacity-50 transition-colors outline-none text-gray-900 dark:text-white"
                               placeholder="admin@example.com"
                               style="focus:ring-color: var(--theme-color);">
                        <span id="email-error" class="text-red-500 text-sm mt-1 hidden"></span>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Пароль</label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-opacity-50 transition-colors outline-none text-gray-900 dark:text-white"
                               placeholder="••••••••">
                        <span id="password-error" class="text-red-500 text-sm mt-1 hidden"></span>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer select-none">
                            <span class="relative inline-flex items-center shrink-0">
                                <input type="checkbox" name="remember" value="1" class="peer sr-only">
                                <span class="block w-10 h-6 rounded-full bg-gray-300 dark:bg-gray-600 transition-colors peer-checked:bg-[var(--theme-color)]"></span>
                                <span class="absolute left-1 top-1 w-4 h-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                            </span>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Запомнить меня</span>
                        </label>
                        <a href="{{ route('axora-cms.password.request') }}" class="text-sm font-medium hover:underline transition" id="forgot-link">
                            Забыли пароль?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            id="login-btn"
                            class="w-full py-3 px-4 text-white font-semibold rounded-lg hover:opacity-90 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60">
                        <span id="btn-text">Войти</span>
                        <svg id="loader" class="hidden animate-spin h-5 w-5 mx-auto text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Theme color from config
        const themeColor = '{{ config("axora-cms.theme_color", "red") }}';
        const colorMap = {
            red: '#ef4444',
            blue: '#3b82f6',
            green: '#10b981',
            purple: '#a855f7',
            orange: '#f97316',
            pink: '#ec4899'
        };

        document.documentElement.style.setProperty('--theme-color', colorMap[themeColor] || colorMap.red);

        // Apply theme color to elements
        const loginBtn = document.getElementById('login-btn');
        const forgotLink = document.getElementById('forgot-link');
        loginBtn.style.backgroundColor = colorMap[themeColor] || colorMap.red;
        forgotLink.style.color = colorMap[themeColor] || colorMap.red;

        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        const sunIcon = document.getElementById('sun-icon');
        const moonIcon = document.getElementById('moon-icon');
        const body = document.getElementById('body');

        const savedTheme = localStorage.getItem('axora-cms-theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
            body.classList.add('dark:bg-gray-900');
            body.classList.remove('bg-gray-50');
            body.style.backgroundColor = '#111827';
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
        } else {
            body.classList.add('bg-gray-50');
            body.style.backgroundColor = '#f9fafb';
        }

        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            sunIcon.classList.toggle('hidden');
            moonIcon.classList.toggle('hidden');
            localStorage.setItem('axora-cms-theme', isDark ? 'dark' : 'light');
            body.style.backgroundColor = isDark ? '#111827' : '#f9fafb';
        });

        // Form submission
        const form = document.getElementById('login-form');
        const btnText = document.getElementById('btn-text');
        const loader = document.getElementById('loader');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            document.getElementById('error-alert').classList.add('hidden');
            document.getElementById('email-error').classList.add('hidden');
            document.getElementById('password-error').classList.add('hidden');

            loginBtn.disabled = true;
            btnText.classList.add('hidden');
            loader.classList.remove('hidden');

            const formData = new FormData(e.target);

            try {
                const response = await fetch(e.target.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    window.location.href = data.redirect;
                } else {
                    loginBtn.disabled = false;
                    btnText.classList.remove('hidden');
                    loader.classList.add('hidden');

                    if (data.errors) {
                        if (data.errors.email) {
                            const emailError = document.getElementById('email-error');
                            emailError.textContent = data.errors.email[0];
                            emailError.classList.remove('hidden');
                        }
                        if (data.errors.password) {
                            const passwordError = document.getElementById('password-error');
                            passwordError.textContent = data.errors.password[0];
                            passwordError.classList.remove('hidden');
                        }
                    } else if (data.message) {
                        const alert = document.getElementById('error-alert');
                        alert.textContent = data.message;
                        alert.classList.remove('hidden');
                    }
                }
            } catch (error) {
                loginBtn.disabled = false;
                btnText.classList.remove('hidden');
                loader.classList.add('hidden');

                const alert = document.getElementById('error-alert');
                alert.textContent = 'Произошла ошибка. Пожалуйста, попробуйте снова.';
                alert.classList.remove('hidden');
            }
        });
    </script>

    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</body>
</html>
