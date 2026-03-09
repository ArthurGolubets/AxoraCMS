{{-- Footer Template 2: Modern multi-column footer with gradient and glass effects --}}
@php
    use HolartWeb\HolartCMS\Models\TPanelSettings;
    use HolartWeb\HolartCMS\Models\Menus\TMenu;

    $logoPath = TPanelSettings::get('logo_path');
    $siteName = TPanelSettings::get('site_name', 'HolartCMS');
    $siteDescription = TPanelSettings::get('site_description');
    $contactEmail = TPanelSettings::get('contact_email');
    $contactPhone = TPanelSettings::get('contact_phone');

    // Get footer2 settings
    $footerSettings = TPanelSettings::get('footer_template_settings', []);
    if ($footerSettings && $footerSettings !== []) $footerSettings = json_decode($footerSettings, 1);
    $footer2Settings = is_array($footerSettings) && isset($footerSettings['footer2']) ? $footerSettings['footer2'] : [];

    $footerMenuId = $footer2Settings['menu_id'] ?? null;
    $footerMenu = $footerMenuId ? TMenu::with(['rootItems' => function ($query) {
        $query->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort');
            }]);
    }])->where('id', $footerMenuId)->where('is_active', true)->first() : null;

    $bgColor = $footer2Settings['bg_color'] ?? '#212529';
    $textColor = $footer2Settings['text_color'] ?? '#ffffff';
    $linkColor = $footer2Settings['link_color'] ?? '#adb5bd';
    $linkHoverColor = $footer2Settings['link_hover_color'] ?? '#ffffff';
@endphp

<footer class="footer-template-2 py-5 mt-auto position-relative overflow-hidden" style="background: linear-gradient(135deg, {{ $bgColor }} 0%, {{ $bgColor }}dd 50%, {{ $bgColor }} 100%); color: {{ $textColor }};">
    <!-- Animated background patterns -->
    <div class="footer-bg-pattern"></div>
    <div class="footer-glow-effect"></div>

    <div class="container position-relative">
        <div class="row g-4 mb-5">
            <!-- Logo & Description Column -->
            <div class="col-lg-4 col-md-6 footer-col-animated" style="animation-delay: 0.1s">
                <div class="footer-brand mb-4 position-relative">
                    @if($logoPath)
                        <div class="logo-container">
                            <img
                                src="{{ asset('storage/' . $logoPath) }}"
                                alt="{{ $siteName }}"
                                class="footer-logo mb-3"
                                style="max-height: 55px; height: auto; filter: brightness(0) invert(1);"
                            />
                        </div>
                    @else
                        <h5 class="mb-3 fw-bold modern-title" style="font-size: 1.5rem; letter-spacing: -0.5px;">{{ $siteName }}</h5>
                    @endif
                </div>
                @if($siteDescription)
                    <p class="footer-description" style="color: {{ $linkColor }}; line-height: 1.7; font-size: 0.95rem;">{{ $siteDescription }}</p>
                @endif
            </div>

            <!-- Navigation Column -->
            <div class="col-lg-4 col-md-6 footer-col-animated" style="animation-delay: 0.2s">
                <h6 class="text-uppercase mb-4 fw-bold modern-heading position-relative" style="color: {{ $textColor }}; letter-spacing: 1.5px; font-size: 0.85rem;">
                    Навигация
                    <span class="heading-underline"></span>
                </h6>
                @if($footerMenu && $footerMenu->rootItems->count() > 0)
                <ul class="list-unstyled footer-links">
                    @foreach($footerMenu->rootItems as $item)
                        <li class="mb-2">
                            <a
                                href="{{ $item->url }}"
                                class="footer-nav-link text-decoration-none d-inline-flex align-items-center"
                                style="color: {{ $linkColor }};"
                                @if($item->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                            >
                                <span class="link-arrow">→</span>
                                <span class="link-text">{{ $item->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                @endif
            </div>

            <!-- Contact Column -->
            <div class="col-lg-4 col-md-6 footer-col-animated" style="animation-delay: 0.3s">
                <h6 class="text-uppercase mb-4 fw-bold modern-heading position-relative" style="color: {{ $textColor }}; letter-spacing: 1.5px; font-size: 0.85rem;">
                    Контакты
                    <span class="heading-underline"></span>
                </h6>
                <ul class="list-unstyled contact-list" style="color: {{ $linkColor }};">
                    @if($contactEmail)
                        <li class="mb-3 contact-item">
                            <a href="mailto:{{ $contactEmail }}" class="text-decoration-none d-flex align-items-center" style="color: {{ $linkColor }};">
                                <span class="contact-icon me-3">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/>
                                    </svg>
                                </span>
                                <span class="contact-text">{{ $contactEmail }}</span>
                            </a>
                        </li>
                    @endif
                    @if($contactPhone)
                        <li class="mb-3 contact-item">
                            <a href="tel:{{ $contactPhone }}" class="text-decoration-none d-flex align-items-center" style="color: {{ $linkColor }};">
                                <span class="contact-icon me-3">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
                                    </svg>
                                </span>
                                <span class="contact-text">{{ $contactPhone }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <!-- Divider with gradient -->
        <div class="footer-divider my-4"></div>

        <!-- Copyright -->
        <div class="row">
            <div class="col-12 text-center">
                <p class="mb-0 copyright-text" style="color: {{ $linkColor }}; font-size: 0.9rem;">
                    © {{ date('Y') }} <span class="fw-semibold" style="color: {{ $textColor }};">{{ $siteName }}</span>. Все права защищены.
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer-template-2 {
        box-shadow: 0 -8px 30px rgba(0,0,0,0.2);
    }

    .footer-template-2 .footer-bg-pattern {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image:
            repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,0.02) 35px, rgba(255,255,255,0.02) 70px);
        pointer-events: none;
    }

    .footer-template-2 .footer-glow-effect {
        position: absolute;
        top: -100px;
        left: 50%;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, {{ $linkHoverColor }}15 0%, transparent 70%);
        transform: translateX(-50%);
        pointer-events: none;
        animation: glowPulse 10s ease-in-out infinite;
    }

    @keyframes glowPulse {
        0%, 100% { opacity: 0.3; transform: translateX(-50%) scale(1); }
        50% { opacity: 0.6; transform: translateX(-50%) scale(1.3); }
    }

    .footer-template-2 .footer-col-animated {
        animation: fadeInUp 0.8s ease-out backwards;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .footer-template-2 .logo-container {
        position: relative;
        display: inline-block;
    }

    .footer-template-2 .footer-logo {
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        filter: brightness(0) invert(1) drop-shadow(0 2px 10px rgba(255,255,255,0.2));
    }

    .footer-template-2 .logo-container:hover .footer-logo {
        transform: translateY(-5px) scale(1.05);
        filter: brightness(0) invert(1) drop-shadow(0 6px 20px rgba(255,255,255,0.4));
    }

    .footer-template-2 .modern-heading {
        padding-bottom: 12px;
    }

    .footer-template-2 .heading-underline {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: linear-gradient(90deg, {{ $linkHoverColor }}, transparent);
        border-radius: 2px;
    }

    .footer-template-2 .footer-nav-link {
        padding: 0.5rem 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .footer-template-2 .link-arrow {
        display: inline-block;
        margin-right: 0.75rem;
        opacity: 0;
        transform: translateX(-10px);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        font-size: 1.1rem;
    }

    .footer-template-2 .link-text {
        transition: all 0.3s ease;
        position: relative;
    }

    .footer-template-2 .footer-nav-link:hover {
        color: {{ $linkHoverColor }} !important;
        padding-left: 0.5rem;
    }

    .footer-template-2 .footer-nav-link:hover .link-arrow {
        opacity: 1;
        transform: translateX(0);
    }

    .footer-template-2 .contact-item {
        transition: transform 0.3s ease;
    }

    .footer-template-2 .contact-item:hover {
        transform: translateX(5px);
    }

    .footer-template-2 .contact-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: rgba(255,255,255,0.05);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .footer-template-2 .contact-item:hover .contact-icon {
        background: rgba(255,255,255,0.1);
        transform: rotate(10deg) scale(1.1);
    }

    .footer-template-2 .contact-text {
        transition: color 0.3s ease;
    }

    .footer-template-2 .contact-item a:hover .contact-text {
        color: {{ $linkHoverColor }} !important;
    }

    .footer-template-2 .footer-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        position: relative;
    }

    .footer-template-2 .footer-divider::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 6px;
        height: 6px;
        background: {{ $linkHoverColor }};
        border-radius: 50%;
        transform: translate(-50%, -50%);
        box-shadow: 0 0 10px {{ $linkHoverColor }};
    }

    .footer-template-2 .copyright-text {
        opacity: 0.8;
        transition: opacity 0.3s ease;
        letter-spacing: 0.5px;
    }

    .footer-template-2 .copyright-text:hover {
        opacity: 1;
    }

    .footer-template-2 .footer-description {
        opacity: 0.9;
        transition: opacity 0.3s ease;
    }

    .footer-template-2 .footer-description:hover {
        opacity: 1;
    }

    @media (max-width: 767px) {
        .footer-template-2 .col-lg-4,
        .footer-template-2 .col-md-6 {
            text-align: center;
        }

        .footer-template-2 .footer-links {
            padding-left: 0;
        }

        .footer-template-2 .heading-underline {
            left: 50%;
            transform: translateX(-50%);
        }

        .footer-template-2 .footer-nav-link,
        .footer-template-2 .contact-item a {
            justify-content: center;
        }

        .footer-template-2 .footer-glow-effect {
            width: 300px;
            height: 300px;
        }
    }
</style>
