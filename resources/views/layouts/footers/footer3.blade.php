{{-- Footer Template 3: Modern minimal footer with animated social links --}}
@php
    use HolartWeb\HolartCMS\Models\TPanelSettings;
    use HolartWeb\HolartCMS\Models\Menus\TMenu;

    $logoPath = TPanelSettings::get('logo_path');
    $siteName = TPanelSettings::get('site_name', 'HolartCMS');

    // Get footer3 settings
    $footerSettings = TPanelSettings::get('footer_template_settings', []);
    if ($footerSettings && $footerSettings !== []) $footerSettings = json_decode($footerSettings, 1);
    $footer3Settings = is_array($footerSettings) && isset($footerSettings['footer3']) ? $footerSettings['footer3'] : [];

    $footerMenuId = $footer3Settings['menu_id'] ?? null;
    $footerMenu = $footerMenuId ? TMenu::with(['rootItems' => function ($query) {
        $query->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort');
            }]);
    }])->where('id', $footerMenuId)->where('is_active', true)->first() : null;

    $bgColor = $footer3Settings['bg_color'] ?? '#f8f9fa';
    $textColor = $footer3Settings['text_color'] ?? '#6c757d';
    $linkColor = $footer3Settings['link_color'] ?? '#6c757d';
    $linkHoverColor = $footer3Settings['link_hover_color'] ?? '#0d6efd';

    // Social links
    $socialVk = $footer3Settings['social_vk'] ?? '';
    $socialInstagram = $footer3Settings['social_instagram'] ?? '';
    $socialTelegram = $footer3Settings['social_telegram'] ?? '';
@endphp

<footer class="footer-template-3 py-4 mt-auto position-relative overflow-hidden" style="background: linear-gradient(180deg, {{ $bgColor }} 0%, {{ $bgColor }}f0 100%); color: {{ $textColor }}; border-top: 2px solid transparent; border-image: linear-gradient(90deg, transparent, {{ $linkHoverColor }}30, transparent) 1;">
    <!-- Decorative elements -->
    <div class="footer-wave"></div>

    <div class="container position-relative">
        <div class="row align-items-center g-4">
            <!-- Logo & Copyright -->
            <div class="col-lg-4 col-md-6 text-lg-start text-center footer-section" style="animation-delay: 0.1s">
                @if($logoPath)
                    <div class="logo-wrapper mb-3">
                        <img
                            src="{{ asset('storage/' . $logoPath) }}"
                            alt="{{ $siteName }}"
                            class="footer-logo"
                            style="max-height: 45px; height: auto;"
                        />
                    </div>
                @else
                    <strong class="d-block mb-3 h5 modern-brand-text" style="color: {{ $textColor }}; font-weight: 700; letter-spacing: -0.5px;">{{ $siteName }}</strong>
                @endif
                <p class="mb-0 copyright-minimal" style="color: {{ $textColor }}; font-size: 0.9rem;">
                    © {{ date('Y') }} <span class="fw-semibold">Все права защищены</span>
                </p>
            </div>

            <!-- Footer Menu -->
            <div class="col-lg-4 col-md-6 text-center footer-section" style="animation-delay: 0.2s">
                @if($footerMenu && $footerMenu->rootItems->count() > 0)
                <nav class="footer-nav">
                    <ul class="list-inline mb-0 d-flex flex-wrap justify-content-center gap-2">
                        @foreach($footerMenu->rootItems as $item)
                            <li class="list-inline-item">
                                <a
                                    href="{{ $item->url }}"
                                    class="footer-link text-decoration-none position-relative"
                                    style="color: {{ $linkColor }};"
                                    @if($item->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                >
                                    {{ $item->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                @endif
            </div>

            <!-- Social Links -->
            <div class="col-lg-4 text-lg-end text-center footer-section" style="animation-delay: 0.3s">
                @if($socialVk || $socialInstagram || $socialTelegram)
                <div class="d-flex gap-3 justify-content-lg-end justify-content-center social-container">
                    @if($socialVk)
                    <a href="{{ $socialVk }}" target="_blank" class="social-link" style="color: {{ $linkColor }};" title="VK">
                        <div class="social-icon-wrapper">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M15.07 2H8.93C3.33 2 2 3.33 2 8.93v6.14C2 20.67 3.33 22 8.93 22h6.14c5.6 0 6.93-1.33 6.93-6.93V8.93C22 3.33 20.67 2 15.07 2zm3.15 14.1c-.37.48-.88.88-1.52 1.21-1.41.72-2.37.57-3.05-.08-.53-.51-.77-1.25-.77-2.31 0-.53-.1-1.01-.29-1.43-.47-.98-1.43-1.43-2.6-1.43-.55 0-1.08.11-1.55.32l-.13-.57c.02-.12.03-.23.03-.35 0-.53-.21-1.01-.61-1.4-.39-.39-.88-.59-1.4-.59-1.01 0-1.84.83-1.84 1.84 0 .55.24 1.05.61 1.42.35.35.81.55 1.29.55.04 0 .08 0 .12-.01l.13.58c-.47.21-.99.32-1.54.32-1.16 0-2.12.45-2.59 1.43-.19.42-.29.9-.29 1.43 0 1.06-.24 1.8-.77 2.31-.68.65-1.64.8-3.05.08-.64-.33-1.15-.73-1.52-1.21-.32-.41-.48-.86-.48-1.35 0-.93.48-1.84 1.29-2.45.5-.38 1.09-.64 1.73-.76l.45 1.96c-.24.08-.47.2-.67.36-.37.28-.56.64-.56 1.01 0 .15.04.3.13.43.26.38.72.57 1.37.57.48 0 .88-.13 1.19-.39.32-.27.48-.63.48-1.08v-1.72c0-.77.25-1.43.76-1.98.51-.55 1.17-.83 1.98-.83s1.47.28 1.98.83c.51.55.76 1.21.76 1.98v1.72c0 .45.16.81.48 1.08.31.26.71.39 1.19.39.65 0 1.11-.19 1.37-.57.09-.13.13-.28.13-.43 0-.37-.19-.73-.56-1.01-.2-.16-.43-.28-.67-.36l.45-1.96c.64.12 1.23.38 1.73.76.81.61 1.29 1.52 1.29 2.45 0 .49-.16.94-.48 1.35z"/>
                            </svg>
                        </div>
                    </a>
                    @endif
                    @if($socialInstagram)
                    <a href="{{ $socialInstagram }}" target="_blank" class="social-link" style="color: {{ $linkColor }};" title="Instagram">
                        <div class="social-icon-wrapper">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/>
                            </svg>
                        </div>
                    </a>
                    @endif
                    @if($socialTelegram)
                    <a href="{{ $socialTelegram }}" target="_blank" class="social-link" style="color: {{ $linkColor }};" title="Telegram">
                        <div class="social-icon-wrapper">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"/>
                            </svg>
                        </div>
                    </a>
                    @endif
                </div>
                @else
                    <p class="mb-0 small follow-text" style="color: {{ $textColor }}; opacity: 0.8;">Следите за нами в социальных сетях</p>
                @endif
            </div>
        </div>
    </div>
</footer>

<style>
    .footer-template-3 {
        box-shadow: 0 -4px 20px rgba(0,0,0,0.05);
    }

    .footer-template-3 .footer-wave {
        position: absolute;
        top: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, {{ $linkHoverColor }}, transparent);
        animation: waveMove 3s ease-in-out infinite;
    }

    @keyframes waveMove {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.8; }
    }

    .footer-template-3 .footer-section {
        animation: fadeInUp 0.6s ease-out backwards;
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

    .footer-template-3 .logo-wrapper {
        display: inline-block;
        position: relative;
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .footer-template-3 .logo-wrapper:hover {
        transform: translateY(-5px) scale(1.05);
    }

    .footer-template-3 .footer-logo {
        filter: drop-shadow(0 2px 8px rgba(0,0,0,0.08));
        transition: filter 0.3s ease;
    }

    .footer-template-3 .logo-wrapper:hover .footer-logo {
        filter: drop-shadow(0 4px 16px rgba(0,0,0,0.15));
    }

    .footer-template-3 .modern-brand-text {
        background: linear-gradient(135deg, {{ $textColor }}, {{ $linkHoverColor }});
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: inline-block;
        transition: transform 0.3s ease;
    }

    .footer-template-3 .modern-brand-text:hover {
        transform: scale(1.05);
    }

    .footer-template-3 .copyright-minimal {
        opacity: 0.85;
        transition: opacity 0.3s ease;
        letter-spacing: 0.3px;
    }

    .footer-template-3 .copyright-minimal:hover {
        opacity: 1;
    }

    .footer-template-3 .footer-link {
        font-size: 0.9rem;
        font-weight: 600;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-block;
    }

    .footer-template-3 .footer-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: {{ $linkHoverColor }};
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateX(-50%);
        border-radius: 2px;
    }

    .footer-template-3 .footer-link:hover {
        color: {{ $linkHoverColor }} !important;
        transform: translateY(-2px);
    }

    .footer-template-3 .footer-link:hover::after {
        width: 80%;
    }

    .footer-template-3 .social-container {
        animation: fadeIn 0.8s ease-out 0.4s backwards;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .footer-template-3 .social-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        position: relative;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .footer-template-3 .social-icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(0,0,0,0.03), rgba(0,0,0,0.05));
        border: 2px solid currentColor;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }

    .footer-template-3 .social-icon-wrapper::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, {{ $linkHoverColor }}20, {{ $linkHoverColor }}40);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .footer-template-3 .social-link:hover {
        color: {{ $linkHoverColor }} !important;
        transform: translateY(-5px) rotate(5deg);
    }

    .footer-template-3 .social-link:hover .social-icon-wrapper {
        border-color: {{ $linkHoverColor }};
        box-shadow: 0 8px 20px {{ $linkHoverColor }}40;
        transform: scale(1.1);
    }

    .footer-template-3 .social-link:hover .social-icon-wrapper::before {
        opacity: 1;
    }

    .footer-template-3 .social-link svg {
        position: relative;
        z-index: 1;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .footer-template-3 .social-link:hover svg {
        transform: scale(1.15);
    }

    .footer-template-3 .follow-text {
        transition: opacity 0.3s ease;
    }

    .footer-template-3 .follow-text:hover {
        opacity: 1 !important;
    }

    @media (max-width: 991px) {
        .footer-template-3 .row > div {
            margin-bottom: 1.5rem;
        }

        .footer-template-3 .row > div:last-child {
            margin-bottom: 0;
        }

        .footer-template-3 .footer-link::after {
            display: none;
        }

        .footer-template-3 .social-icon-wrapper {
            width: 48px;
            height: 48px;
        }
    }
</style>
