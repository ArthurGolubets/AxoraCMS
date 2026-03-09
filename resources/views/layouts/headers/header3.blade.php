{{-- Header Template 3: Dark modern with logo left, centered menu --}}
@php
    use HolartWeb\HolartCMS\Models\TPanelSettings;
    use HolartWeb\HolartCMS\Models\Menus\TMenu;

    $logoPath = TPanelSettings::get('logo_path');
    $siteName = TPanelSettings::get('site_name', 'HolartCMS');
    $logoWidth = TPanelSettings::get('logo_width');

    // Get header3 settings
    $headerSettings = TPanelSettings::get('header_template_settings', []);
    if ($headerSettings && $headerSettings !== []) $headerSettings = json_decode($headerSettings, 1);
    $header3Settings = is_array($headerSettings) && isset($headerSettings['header3']) ? $headerSettings['header3'] : [];

    $headerMenuId = $header3Settings['menu_id'] ?? null;
    $headerMenu = $headerMenuId ? TMenu::with(['rootItems' => function ($query) {
        $query->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort');
            }]);
    }])->where('id', $headerMenuId)->where('is_active', true)->first() : null;

    $bgColor = $header3Settings['bg_color'] ?? '#212529';
    $textColor = $header3Settings['text_color'] ?? '#ffffff';
    $linkColor = $header3Settings['link_color'] ?? '#adb5bd';
    $linkHoverColor = $header3Settings['link_hover_color'] ?? '#ffffff';

    // Social links
    $socialVk = $header3Settings['social_vk'] ?? '';
    $socialInstagram = $header3Settings['social_instagram'] ?? '';
    $socialTelegram = $header3Settings['social_telegram'] ?? '';
@endphp

<header class="header-template-3 sticky-top" style="background: linear-gradient(135deg, {{ $bgColor }} 0%, {{ $bgColor }}ee 100%); box-shadow: 0 4px 30px rgba(0,0,0,0.3);">
    <nav class="navbar navbar-expand-lg dark-navbar">
        <div class="container">
            <!-- Logo -->
            <a href="/" class="navbar-brand dark-brand position-relative" style="color: {{ $textColor }};">
                @if($logoPath)
                    <div class="logo-badge">
                        <img
                            src="{{ asset('storage/' . $logoPath) }}"
                            alt="{{ $siteName }}"
                            class="logo-img"
                            style="max-height: 55px; height: auto; @if($logoWidth) width: {{ $logoWidth }}px; @endif filter: brightness(0) invert(1) drop-shadow(0 0 10px rgba(255,255,255,0.3));"
                        />
                    </div>
                @else
                    <span class="logo-text">{{ $siteName }}</span>
                @endif
            </a>

            <!-- Mobile Toggle -->
            <button
                class="navbar-toggler dark-toggler border-0 shadow"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav"
                aria-controls="mainNav"
                aria-expanded="false"
                aria-label="Открыть меню"
                style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 12px; padding: 0.5rem 0.75rem;"
            >
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>

            <!-- Navigation -->
            <div class="collapse navbar-collapse justify-content-end" id="mainNav">
                @if($headerMenu && $headerMenu->rootItems->count() > 0)
                    <ul class="navbar-nav gap-1">
                        @foreach($headerMenu->rootItems as $item)
                            @if($item->children && $item->children->count() > 0)
                                <li class="nav-item dropdown">
                                    <a
                                        class="nav-link dropdown-toggle px-3 py-2 rounded-3 dark-link"
                                        href="#"
                                        role="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                        style="color: {{ $linkColor }};"
                                    >
                                        {{ $item->title }}
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-dark dark-dropdown shadow-lg border-0 rounded-4" style="background: linear-gradient(180deg, {{ $bgColor }} 0%, {{ $bgColor }}dd 100%); backdrop-filter: blur(20px);">
                                        @foreach($item->children as $child)
                                            <li>
                                                <a
                                                    class="dropdown-item rounded-3 dark-dropdown-item"
                                                    href="{{ $child->url }}"
                                                    style="color: {{ $linkColor }};"
                                                    @if($child->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                                >
                                                    <span class="item-dot"></span>
                                                    {{ $child->title }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a
                                        class="nav-link px-3 py-2 rounded-3 dark-link"
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

                <!-- Social Links -->
                @if($socialVk || $socialInstagram || $socialTelegram)
                    <div class="d-flex gap-2 ms-lg-4 social-links-modern">
                        @if($socialVk)
                            <a href="{{ $socialVk }}" target="_blank" class="social-link-dark" style="color: {{ $linkColor }}; border-color: {{ $linkColor }}40;">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M15.07 2H8.93C3.33 2 2 3.33 2 8.93v6.14C2 20.67 3.33 22 8.93 22h6.14c5.6 0 6.93-1.33 6.93-6.93V8.93C22 3.33 20.67 2 15.07 2zm3.15 14.1c-.37.48-.88.88-1.52 1.21-1.41.72-2.37.57-3.05-.08-.53-.51-.77-1.25-.77-2.31 0-.53-.1-1.01-.29-1.43-.47-.98-1.43-1.43-2.6-1.43-.55 0-1.08.11-1.55.32l-.13-.57c.02-.12.03-.23.03-.35 0-.53-.21-1.01-.61-1.4-.39-.39-.88-.59-1.4-.59-1.01 0-1.84.83-1.84 1.84 0 .55.24 1.05.61 1.42.35.35.81.55 1.29.55.04 0 .08 0 .12-.01l.13.58c-.47.21-.99.32-1.54.32-1.16 0-2.12.45-2.59 1.43-.19.42-.29.9-.29 1.43 0 1.06-.24 1.8-.77 2.31-.68.65-1.64.8-3.05.08-.64-.33-1.15-.73-1.52-1.21-.32-.41-.48-.86-.48-1.35 0-.93.48-1.84 1.29-2.45.5-.38 1.09-.64 1.73-.76l.45 1.96c-.24.08-.47.2-.67.36-.37.28-.56.64-.56 1.01 0 .15.04.3.13.43.26.38.72.57 1.37.57.48 0 .88-.13 1.19-.39.32-.27.48-.63.48-1.08v-1.72c0-.77.25-1.43.76-1.98.51-.55 1.17-.83 1.98-.83s1.47.28 1.98.83c.51.55.76 1.21.76 1.98v1.72c0 .45.16.81.48 1.08.31.26.71.39 1.19.39.65 0 1.11-.19 1.37-.57.09-.13.13-.28.13-.43 0-.37-.19-.73-.56-1.01-.2-.16-.43-.28-.67-.36l.45-1.96c.64.12 1.23.38 1.73.76.81.61 1.29 1.52 1.29 2.45 0 .49-.16.94-.48 1.35z"/>
                                </svg>
                            </a>
                        @endif
                        @if($socialInstagram)
                            <a href="{{ $socialInstagram }}" target="_blank" class="social-link-dark" style="color: {{ $linkColor }}; border-color: {{ $linkColor }}40;">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/>
                                </svg>
                            </a>
                        @endif
                        @if($socialTelegram)
                            <a href="{{ $socialTelegram }}" target="_blank" class="social-link-dark" style="color: {{ $linkColor }}; border-color: {{ $linkColor }}40;">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </nav>
</header>

<style>
    .header-template-3 .dark-navbar {
        padding: 1.25rem 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .header-template-3 .logo-badge {
        position: relative;
        display: inline-block;
        padding: 0.5rem;
        background: rgba(255,255,255,0.05);
        border-radius: 15px;
        backdrop-filter: blur(10px);
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .header-template-3 .dark-brand:hover .logo-badge {
        background: rgba(255,255,255,0.1);
        transform: translateY(-3px) rotate(-2deg);
        box-shadow: 0 10px 30px rgba(255,255,255,0.15);
    }

    .header-template-3 .dark-brand {
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .header-template-3 .logo-img {
        max-width: 100%;
        object-fit: contain;
        transition: all 0.3s ease;
    }

    .header-template-3 .logo-text {
        font-size: 1.75rem;
        font-weight: 900;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 10px rgba(255,255,255,0.2);
    }

    .header-template-3 .dark-link {
        font-weight: 600;
        font-size: 0.9rem;
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
    }

    .header-template-3 .dark-link::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.15));
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: inherit;
    }

    .header-template-3 .dark-link:hover,
    .header-template-3 .dark-link:focus {
        color: {{ $linkHoverColor }} !important;
        transform: translateY(-2px);
    }

    .header-template-3 .dark-link:hover::before {
        opacity: 1;
    }

    .header-template-3 .dark-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%) scaleX(0);
        width: 80%;
        height: 2px;
        background: linear-gradient(90deg, transparent, {{ $linkHoverColor }}, transparent);
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        border-radius: 10px;
    }

    .header-template-3 .dark-link:hover::after {
        transform: translateX(-50%) scaleX(1);
    }

    .header-template-3 .dark-dropdown {
        margin-top: 0.75rem;
        padding: 0.5rem;
        animation: darkFadeIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes darkFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .header-template-3 .dark-dropdown-item {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        margin-bottom: 0.25rem;
    }

    .header-template-3 .item-dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        background: {{ $linkHoverColor }};
        border-radius: 50%;
        margin-right: 0.75rem;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 0 8px {{ $linkHoverColor }}80;
    }

    .header-template-3 .dark-dropdown-item:hover,
    .header-template-3 .dark-dropdown-item:focus {
        background: linear-gradient(90deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
        color: {{ $linkHoverColor }} !important;
        transform: translateX(5px);
    }

    .header-template-3 .dark-dropdown-item:hover .item-dot {
        transform: scale(1.5) translateX(5px);
        box-shadow: 0 0 15px {{ $linkHoverColor }};
    }

    .header-template-3 .social-link-dark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 2px solid;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        text-decoration: none;
        position: relative;
        overflow: hidden;
        background: rgba(255,255,255,0.03);
        backdrop-filter: blur(5px);
    }

    .header-template-3 .social-link-dark::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at center, {{ $linkHoverColor }}30, transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .header-template-3 .social-link-dark:hover {
        color: {{ $linkHoverColor }} !important;
        border-color: {{ $linkHoverColor }} !important;
        transform: translateY(-5px) rotate(360deg);
        box-shadow: 0 10px 25px {{ $linkHoverColor }}40;
    }

    .header-template-3 .social-link-dark:hover::before {
        opacity: 1;
    }

    .header-template-3 .social-link-dark svg {
        position: relative;
        z-index: 1;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .header-template-3 .social-link-dark:hover svg {
        transform: scale(1.2);
    }

    @media (max-width: 991px) {
        .header-template-3 .navbar-collapse {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .header-template-3 .navbar-nav {
            width: 100%;
            text-align: center;
        }

        .header-template-3 .nav-item {
            padding: 0.25rem 0;
        }

        .header-template-3 .dark-link::after {
            display: none;
        }

        .header-template-3 .dark-link:hover {
            transform: none;
        }

        .header-template-3 .dark-dropdown {
            text-align: center;
            border: none;
        }

        .header-template-3 .dark-dropdown-item:hover {
            transform: none;
        }

        .header-template-3 .social-links-modern {
            justify-content: center;
            margin-top: 1rem;
            margin-left: 0 !important;
        }

        .header-template-3 .social-link-dark:hover {
            transform: translateY(-3px) rotate(15deg);
        }
    }
</style>
