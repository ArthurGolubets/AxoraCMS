{{-- Header Template 1: Classic centered logo with menu below --}}
@php
    use HolartWeb\HolartCMS\Models\TPanelSettings;
    use HolartWeb\HolartCMS\Models\Menus\TMenu;

    $logoPath = TPanelSettings::get('logo_path');
    $siteName = TPanelSettings::get('site_name', 'HolartCMS');
    $logoWidth = TPanelSettings::get('logo_width');

    // Get header1 settings
    $headerSettings = TPanelSettings::get('header_template_settings', []);
    if ($headerSettings && $headerSettings !== []) $headerSettings = json_decode($headerSettings, 1);
    $header1Settings = is_array($headerSettings) && isset($headerSettings['header1']) ? $headerSettings['header1'] : [];

    $headerMenuId = $header1Settings['menu_id'] ?? null;
    $headerMenu = $headerMenuId ? TMenu::with(['rootItems' => function ($query) {
        $query->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort');
            }]);
    }])->where('id', $headerMenuId)->where('is_active', true)->first() : null;

    $bgColor = $header1Settings['bg_color'] ?? '#ffffff';
    $textColor = $header1Settings['text_color'] ?? '#495057';
    $linkColor = $header1Settings['link_color'] ?? '#495057';
    $linkHoverColor = $header1Settings['link_hover_color'] ?? '#0d6efd';
@endphp

<header class="header-template-1 shadow-lg sticky-top" style="background: linear-gradient(135deg, {{ $bgColor }} 0%, {{ $bgColor }}f5 100%); color: {{ $textColor }}; backdrop-filter: blur(10px);">
    <div class="container">
        <!-- Logo Row -->
        <div class="logo-row text-center py-5 position-relative">
            <div class="logo-wrapper">
                <a href="/" class="logo d-inline-block position-relative">
                    @if($logoPath)
                        <img
                            src="{{ asset($logoPath) }}"
                            alt="{{ $siteName }}"
                            class="logo-img"
                            style="max-height: 70px; height: auto; @if($logoWidth) width: {{ $logoWidth }}px; @endif"
                        />
                    @else
                        <span class="logo-text">{{ $siteName }}</span>
                    @endif
                    <div class="logo-glow"></div>
                </a>
            </div>
        </div>

        <!-- Navigation Row -->
        @if($headerMenu && $headerMenu->rootItems->count() > 0)
        <nav class="navbar navbar-expand-lg navbar-light modern-nav" style="background: linear-gradient(90deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.05) 100%); border-radius: 15px; padding: 0.5rem 1rem; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);">
            <div class="container-fluid justify-content-center">
                <button
                    class="navbar-toggler border-0 shadow-sm"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNav"
                    aria-controls="mainNav"
                    aria-expanded="false"
                    aria-label="Открыть меню"
                    style="background: {{ $linkColor }}20; border-radius: 10px;"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-center" id="mainNav">
                    <ul class="navbar-nav gap-2">
                        @foreach($headerMenu->rootItems as $item)
                            @if($item->children && $item->children->count() > 0)
                                <li class="nav-item dropdown">
                                    <a
                                        class="nav-link dropdown-toggle px-4 py-2 rounded-pill"
                                        href="#"
                                        role="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        {{ $item->title }}
                                    </a>
                                    <ul class="dropdown-menu modern-dropdown shadow-lg border-0 rounded-4 mt-2">
                                        @foreach($item->children as $child)
                                            <li>
                                                <a
                                                    class="dropdown-item rounded-3"
                                                    href="{{ $child->url }}"
                                                    @if($child->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                                >
                                                    <span class="dropdown-icon">→</span>
                                                    {{ $child->title }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a
                                        class="nav-link px-4 py-2 rounded-pill"
                                        href="{{ $item->url }}"
                                        @if($item->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                    >
                                        {{ $item->title }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
        </nav>
        @endif
    </div>
</header>

<style>
.header-template-1 {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.header-template-1 .logo-wrapper {
    position: relative;
    display: inline-block;
}

.header-template-1 .logo {
    text-decoration: none;
    position: relative;
    z-index: 2;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.header-template-1 .logo:hover {
    transform: translateY(-5px) scale(1.05);
}

.header-template-1 .logo-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, {{ $linkHoverColor }}40 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.4s ease;
    z-index: 1;
    filter: blur(20px);
}

.header-template-1 .logo:hover .logo-glow {
    opacity: 1;
}

.header-template-1 .logo-img {
    max-width: 100%;
    object-fit: contain;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
    transition: filter 0.3s ease;
}

.header-template-1 .logo:hover .logo-img {
    filter: drop-shadow(0 8px 16px rgba(0,0,0,0.15));
}

.header-template-1 .logo-text {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, {{ $linkColor }} 0%, {{ $linkHoverColor }} 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -1px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.header-template-1 .modern-nav {
    margin-bottom: 1rem;
}

.header-template-1 .navbar-nav {
    align-items: center;
}

.header-template-1 .nav-link {
    color: {{ $linkColor }};
    font-weight: 600;
    font-size: 0.95rem;
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
}

.header-template-1 .nav-link:hover,
.header-template-1 .nav-link:focus {
    color: {{ $linkHoverColor }};
    background: {{ $linkHoverColor }}15;
    transform: translateY(-2px);
}

.header-template-1 .nav-link::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%) scaleX(0);
    width: 80%;
    height: 3px;
    background: linear-gradient(90deg, transparent, {{ $linkHoverColor }}, transparent);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    border-radius: 10px;
}

.header-template-1 .nav-link:hover::before {
    transform: translateX(-50%) scaleX(1);
}

.header-template-1 .modern-dropdown {
    margin-top: 0.75rem;
    background: {{ $bgColor }};
    backdrop-filter: blur(20px);
    animation: dropdownFadeIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    overflow: hidden;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.header-template-1 .dropdown-item {
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: {{ $textColor }};
    position: relative;
    overflow: hidden;
}

.header-template-1 .dropdown-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: {{ $linkHoverColor }};
    transform: scaleY(0);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.header-template-1 .dropdown-item:hover::before {
    transform: scaleY(1);
}

.header-template-1 .dropdown-item:hover {
    background: linear-gradient(90deg, {{ $linkHoverColor }}15 0%, transparent 100%);
    color: {{ $linkHoverColor }};
    padding-left: 2rem;
}

.header-template-1 .dropdown-icon {
    display: inline-block;
    margin-right: 0.5rem;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    opacity: 0.6;
}

.header-template-1 .dropdown-item:hover .dropdown-icon {
    transform: translateX(5px);
    opacity: 1;
}

@media (max-width: 991px) {
    .header-template-1 .logo-text {
        font-size: 2rem;
    }

    .header-template-1 .navbar-collapse {
        margin-top: 1rem;
        text-align: center;
        background: {{ $bgColor }}f0;
        border-radius: 15px;
        padding: 1rem;
        margin-top: 0.5rem;
    }

    .header-template-1 .navbar-nav {
        width: 100%;
        gap: 0.5rem !important;
    }

    .header-template-1 .nav-link::before {
        display: none;
    }

    .header-template-1 .modern-dropdown {
        text-align: center;
        border: none;
        background: {{ $bgColor }}e0;
        margin-top: 0.5rem;
    }

    .header-template-1 .dropdown-item:hover {
        padding-left: 1.5rem;
    }
}
</style>
