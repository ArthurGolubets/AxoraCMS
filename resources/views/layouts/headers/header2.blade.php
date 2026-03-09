{{-- Header Template 2: Logo left, menu right (horizontal) --}}
@php
    use HolartWeb\HolartCMS\Models\TPanelSettings;
    use HolartWeb\HolartCMS\Models\Menus\TMenu;

    $logoPath = TPanelSettings::get('logo_path');
    $siteName = TPanelSettings::get('site_name', 'HolartCMS');
    $logoWidth = TPanelSettings::get('logo_width');

    // Get header2 settings
    $headerSettings = TPanelSettings::get('header_template_settings', []);
    if ($headerSettings && $headerSettings !== []) $headerSettings = json_decode($headerSettings, 1);
    $header2Settings = is_array($headerSettings) && isset($headerSettings['header2']) ? $headerSettings['header2'] : [];

    $headerMenuId = $header2Settings['menu_id'] ?? null;
    $headerMenu = $headerMenuId ? TMenu::with(['rootItems' => function ($query) {
        $query->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort');
            }]);
    }])->where('id', $headerMenuId)->where('is_active', true)->first() : null;

    $bgColor = $header2Settings['bg_color'] ?? '#ffffff';
    $textColor = $header2Settings['text_color'] ?? '#495057';
    $linkColor = $header2Settings['link_color'] ?? '#495057';
    $linkHoverColor = $header2Settings['link_hover_color'] ?? '#0d6efd';
    $buttonColor = $header2Settings['button_color'] ?? '#0d6efd';
    $showCart = $header2Settings['show_cart'] ?? false;
    $showAccount = $header2Settings['show_account'] ?? false;
@endphp

<header class="header-template-2 sticky-top" style="background: linear-gradient(180deg, {{ $bgColor }} 0%, {{ $bgColor }}f8 100%); box-shadow: 0 2px 20px rgba(0,0,0,0.08);">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <!-- Logo -->
            <a href="/" class="navbar-brand modern-brand position-relative" style="color: {{ $textColor }};">
                @if($logoPath)
                    <div class="logo-container">
                        <img
                            src="{{ asset('storage/' . $logoPath) }}"
                            alt="{{ $siteName }}"
                            class="logo-img"
                            style="max-height: 55px; height: auto; @if($logoWidth) width: {{ $logoWidth }}px; @endif"
                        />
                        <div class="logo-shine"></div>
                    </div>
                @else
                    <span class="logo-text">{{ $siteName }}</span>
                @endif
            </a>

            <!-- Mobile Toggle -->
            <button
                class="navbar-toggler modern-toggler border-0 shadow-sm"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav"
                aria-controls="mainNav"
                aria-expanded="false"
                aria-label="Открыть меню"
                style="background: linear-gradient(135deg, {{ $buttonColor }}20, {{ $buttonColor }}40); border-radius: 12px; padding: 0.5rem 0.75rem;"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation -->
            <div class="collapse navbar-collapse" id="mainNav">
                @if($headerMenu && $headerMenu->rootItems->count() > 0)
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                        @foreach($headerMenu->rootItems as $item)
                            @if($item->children && $item->children->count() > 0)
                                <li class="nav-item dropdown">
                                    <a
                                        class="nav-link dropdown-toggle px-lg-3 py-2 rounded-3 modern-link"
                                        href="#"
                                        role="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                        style="color: {{ $linkColor }};"
                                    >
                                        {{ $item->title }}
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end modern-dropdown-2 shadow-lg border-0 rounded-4">
                                        @foreach($item->children as $child)
                                            <li>
                                                <a
                                                    class="dropdown-item rounded-3 modern-dropdown-item"
                                                    href="{{ $child->url }}"
                                                    @if($child->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                                >
                                                    <span class="item-bullet">●</span>
                                                    {{ $child->title }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a
                                        class="nav-link px-lg-3 py-2 rounded-3 modern-link"
                                        href="{{ $item->url }}"
                                        style="color: {{ $linkColor }};"
                                        @if($item->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                    >
                                        {{ $item->title }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif

                <!-- Action Buttons -->
                @if($showCart || $showAccount)
                    <div class="d-flex gap-2 ms-3 action-buttons">
                        @if($showCart)
                            <a href="/cart" class="btn btn-sm modern-btn d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm" style="background: linear-gradient(135deg, {{ $buttonColor }}, {{ $buttonColor }}dd); color: white; border: none;">
                                <svg class="btn-icon" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                                </svg>
                                <span class="btn-text d-none d-md-inline fw-semibold">Корзина</span>
                            </a>
                        @endif
                        @if($showAccount)
                            <a href="/account" class="btn btn-sm modern-btn d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm" style="background: linear-gradient(135deg, {{ $buttonColor }}, {{ $buttonColor }}dd); color: white; border: none;">
                                <svg class="btn-icon" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                </svg>
                                <span class="btn-text d-none d-md-inline fw-semibold">Аккаунт</span>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </nav>
</header>

<style>
    .header-template-2 .navbar {
        padding: 1.25rem 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .header-template-2 .logo-container {
        position: relative;
        display: inline-block;
    }

    .header-template-2 .logo-shine {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.3) 50%, transparent 70%);
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
        transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .header-template-2 .modern-brand:hover .logo-shine {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }

    .header-template-2 .modern-brand {
        text-decoration: none;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .header-template-2 .modern-brand:hover {
        transform: translateY(-3px);
    }

    .header-template-2 .logo-img {
        max-width: 100%;
        object-fit: contain;
        filter: drop-shadow(0 2px 6px rgba(0,0,0,0.08));
        transition: filter 0.3s ease;
    }

    .header-template-2 .modern-brand:hover .logo-img {
        filter: drop-shadow(0 4px 12px rgba(0,0,0,0.12));
    }

    .header-template-2 .logo-text {
        font-size: 1.75rem;
        font-weight: 800;
        background: linear-gradient(135deg, {{ $linkColor }}, {{ $linkHoverColor }});
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.5px;
    }

    .header-template-2 .modern-link {
        font-weight: 600;
        font-size: 0.9rem;
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .header-template-2 .modern-link::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, {{ $linkHoverColor }}10, {{ $linkHoverColor }}20);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .header-template-2 .modern-link:hover,
    .header-template-2 .modern-link:focus {
        color: {{ $linkHoverColor }} !important;
        transform: translateY(-2px);
    }

    .header-template-2 .modern-link:hover::before {
        opacity: 1;
    }

    .header-template-2 .modern-dropdown-2 {
        margin-top: 0.75rem;
        padding: 0.5rem;
        background: {{ $bgColor }};
        backdrop-filter: blur(10px);
        animation: slideDown 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        min-width: 220px;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .header-template-2 .modern-dropdown-item {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: {{ $textColor }};
        position: relative;
        margin-bottom: 0.25rem;
    }

    .header-template-2 .item-bullet {
        display: inline-block;
        margin-right: 0.75rem;
        color: {{ $linkHoverColor }};
        font-size: 0.6rem;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .header-template-2 .modern-dropdown-item:hover {
        background: linear-gradient(135deg, {{ $linkHoverColor }}15, {{ $linkHoverColor }}05);
        color: {{ $linkHoverColor }};
        transform: translateX(5px);
    }

    .header-template-2 .modern-dropdown-item:hover .item-bullet {
        transform: translateX(5px) scale(1.3);
    }

    .header-template-2 .modern-btn {
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }

    .header-template-2 .modern-btn::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
        transform: translateX(-100%);
        transition: transform 0.6s ease;
    }

    .header-template-2 .modern-btn:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 8px 20px {{ $buttonColor }}40 !important;
    }

    .header-template-2 .modern-btn:hover::before {
        transform: translateX(100%);
    }

    .header-template-2 .btn-icon {
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .header-template-2 .modern-btn:hover .btn-icon {
        transform: rotate(15deg) scale(1.1);
    }

    @media (max-width: 991px) {
        .header-template-2 .navbar-collapse {
            margin-top: 1rem;
            padding: 1rem;
            background: {{ $bgColor }}f5;
            border-radius: 15px;
        }

        .header-template-2 .navbar-nav {
            width: 100%;
        }

        .header-template-2 .nav-item {
            text-align: center;
            padding: 0.25rem 0;
        }

        .header-template-2 .modern-link:hover {
            transform: none;
        }

        .header-template-2 .modern-dropdown-2 {
            text-align: center;
            border: none;
            background: {{ $bgColor }}e5;
        }

        .header-template-2 .modern-dropdown-item:hover {
            transform: none;
        }

        .header-template-2 .action-buttons {
            justify-content: center;
            margin-top: 1rem;
            margin-left: 0 !important;
        }
    }
</style>
