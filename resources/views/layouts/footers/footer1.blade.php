{{-- Footer Template 1: Modern centered footer with gradient and animations --}}
@php
    use HolartWeb\HolartCMS\Models\TPanelSettings;
    use HolartWeb\HolartCMS\Models\Menus\TMenu;

    $logoPath = TPanelSettings::get('logo_path');
    $siteName = TPanelSettings::get('site_name', 'HolartCMS');

    // Get footer1 settings
    $footerSettings = TPanelSettings::get('footer_template_settings', []);
    if ($footerSettings && $footerSettings !== []) $footerSettings = json_decode($footerSettings, 1);
    $footer1Settings = is_array($footerSettings) && isset($footerSettings['footer1']) ? $footerSettings['footer1'] : [];

    $footerMenuId = $footer1Settings['menu_id'] ?? null;
    $footerMenu = $footerMenuId ? TMenu::with(['rootItems' => function ($query) {
        $query->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort');
            }]);
    }])->where('id', $footerMenuId)->where('is_active', true)->first() : null;

    $bgColor = $footer1Settings['bg_color'] ?? '#f8f9fa';
    $textColor = $footer1Settings['text_color'] ?? '#6c757d';
    $linkColor = $footer1Settings['link_color'] ?? '#6c757d';
    $linkHoverColor = $footer1Settings['link_hover_color'] ?? '#0d6efd';
@endphp

<footer class="footer-template-1 py-5 mt-auto position-relative overflow-hidden" style="background: linear-gradient(180deg, {{ $bgColor }} 0%, {{ $bgColor }}dd 100%); color: {{ $textColor }};">
    <!-- Decorative background elements -->
    <div class="footer-decoration"></div>

    <div class="container position-relative">
        <!-- Footer Menu -->
        @if($footerMenu && $footerMenu->rootItems->count() > 0)
        <nav class="footer-nav text-center mb-4">
            <ul class="list-inline mb-0">
                @foreach($footerMenu->rootItems as $item)
                    <li class="list-inline-item px-2 modern-item">
                        <a
                            href="{{ $item->url }}"
                            class="footer-link position-relative"
                            style="color: {{ $linkColor }};"
                            @if($item->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                        >
                            {{ $item->title }}
                        </a>
                    </li>
                    @if(!$loop->last)
                        <li class="list-inline-item separator" style="color: {{ $textColor }};">•</li>
                    @endif
                @endforeach
            </ul>
        </nav>
        @endif

        <!-- Logo/Brand -->
        @if($logoPath)
        <div class="text-center mb-4">
            <div class="logo-wrapper d-inline-block">
                <img
                    src="{{ asset('storage/' . $logoPath) }}"
                    alt="{{ $siteName }}"
                    class="footer-logo"
                    style="max-height: 45px; height: auto; opacity: 0.8;"
                />
            </div>
        </div>
        @endif

        <!-- Copyright -->
        <div class="text-center">
            <p class="mb-0 copyright-text" style="color: {{ $textColor }};">
                © {{ date('Y') }} <span class="fw-semibold">{{ $siteName }}</span>. Все права защищены.
            </p>
        </div>
    </div>
</footer>

<style>
    .footer-template-1 {
        border-top: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 -4px 20px rgba(0,0,0,0.03);
    }

    .footer-template-1 .footer-decoration {
        position: absolute;
        top: -50%;
        left: 50%;
        width: 800px;
        height: 800px;
        background: radial-gradient(circle, {{ $linkHoverColor }}08 0%, transparent 70%);
        transform: translateX(-50%);
        pointer-events: none;
        animation: pulse 15s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.5; }
        50% { transform: translateX(-50%) scale(1.2); opacity: 0.8; }
    }

    .footer-template-1 .footer-link {
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 600;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        display: inline-block;
    }

    .footer-template-1 .footer-link::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, {{ $linkHoverColor }}15, {{ $linkHoverColor }}05);
        border-radius: 8px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .footer-template-1 .footer-link:hover {
        color: {{ $linkHoverColor }} !important;
        transform: translateY(-3px);
    }

    .footer-template-1 .footer-link:hover::before {
        opacity: 1;
    }

    .footer-template-1 .modern-item {
        animation: fadeInUp 0.6s ease-out backwards;
    }

    @for $i from 1 through 10 {
        .footer-template-1 .modern-item:nth-child(#{$i}) {
            animation-delay: #{$i * 0.05}s;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .footer-template-1 .separator {
        opacity: 0.4;
        margin: 0 0.25rem;
    }

    .footer-template-1 .logo-wrapper {
        position: relative;
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .footer-template-1 .logo-wrapper:hover {
        transform: translateY(-5px) scale(1.05);
    }

    .footer-template-1 .footer-logo {
        filter: drop-shadow(0 2px 8px rgba(0,0,0,0.08));
        transition: all 0.3s ease;
    }

    .footer-template-1 .logo-wrapper:hover .footer-logo {
        filter: drop-shadow(0 4px 16px rgba(0,0,0,0.15));
        opacity: 1 !important;
    }

    .footer-template-1 .copyright-text {
        font-size: 0.9rem;
        opacity: 0.8;
        transition: opacity 0.3s ease;
        letter-spacing: 0.3px;
    }

    .footer-template-1 .copyright-text:hover {
        opacity: 1;
    }

    @media (max-width: 767px) {
        .footer-template-1 .list-inline-item.modern-item {
            display: block;
            margin: 0.75rem 0;
            padding: 0 !important;
        }

        .footer-template-1 .list-inline-item.separator {
            display: none;
        }

        .footer-template-1 .footer-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
        }

        .footer-template-1 .footer-decoration {
            width: 400px;
            height: 400px;
        }
    }
</style>
