{{-- Footer Template 3: Minimal footer with social links --}}
@php
    $settings = \HolartWeb\HolartCMS\Models\TSetting::first();
    $footerMenu = \HolartWeb\HolartCMS\Models\Menus\TMenu::getByLocation('footer')->first();
@endphp

<footer class="footer-template-3 bg-white py-4 mt-5 border-top">
    <div class="container">
        <div class="row align-items-center">
            <!-- Logo & Copyright -->
            <div class="col-md-4 mb-3 mb-md-0">
                @if($settings && $settings->logo_path)
                    <img
                        src="{{ asset($settings->logo_path) }}"
                        alt="{{ $settings->site_name ?? 'Logo' }}"
                        class="mb-2"
                        @if($settings->logo_width) style="width: {{ $settings->logo_width }}px;" @endif
                        @if($settings->logo_height) style="height: {{ $settings->logo_height }}px;" @endif
                    />
                @else
                    <strong class="d-block mb-2">{{ $settings->site_name ?? 'HolartCMS' }}</strong>
                @endif
                <p class="text-muted mb-0 small">© {{ date('Y') }} Все права защищены</p>
            </div>

            <!-- Footer Menu -->
            <div class="col-md-4 mb-3 mb-md-0 text-center">
                @if($footerMenu && $footerMenu->rootItems->count() > 0)
                <nav class="footer-nav">
                    <ul class="list-inline mb-0">
                        @foreach($footerMenu->rootItems as $item)
                            <li class="list-inline-item">
                                <a
                                    href="{{ $item->url }}"
                                    class="text-muted small text-decoration-none"
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
            <div class="col-md-4 text-md-end text-center">
                <div class="social-links">
                    @if($settings && isset($settings->social_facebook))
                        <a href="{{ $settings->social_facebook }}" target="_blank" class="text-muted me-3">
                            <i class="bi bi-facebook"></i>
                        </a>
                    @endif
                    @if($settings && isset($settings->social_instagram))
                        <a href="{{ $settings->social_instagram }}" target="_blank" class="text-muted me-3">
                            <i class="bi bi-instagram"></i>
                        </a>
                    @endif
                    @if($settings && isset($settings->social_twitter))
                        <a href="{{ $settings->social_twitter }}" target="_blank" class="text-muted me-3">
                            <i class="bi bi-twitter"></i>
                        </a>
                    @endif
                    @if($settings && isset($settings->social_vk))
                        <a href="{{ $settings->social_vk }}" target="_blank" class="text-muted">
                            <i class="bi bi-vk"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
.footer-template-3 .footer-nav a:hover {
    color: #0d6efd !important;
}

.footer-template-3 .social-links a {
    font-size: 1.25rem;
    transition: color 0.2s;
}

.footer-template-3 .social-links a:hover {
    color: #0d6efd !important;
}

@media (max-width: 767px) {
    .footer-template-3 .col-md-4 {
        text-align: center !important;
    }

    .footer-template-3 .list-inline-item {
        display: block;
        margin: 0.25rem 0;
    }
}
</style>
