{{-- Footer Template 2: Multi-column footer --}}
@php
    $settings = \HolartWeb\HolartCMS\Models\TSetting::first();
    $footerMenu = \HolartWeb\HolartCMS\Models\Menus\TMenu::getByLocation('footer')->first();
@endphp

<footer class="footer-template-2 bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- Logo & Description Column -->
            <div class="col-md-4 mb-4 mb-md-0">
                @if($settings && $settings->logo_path)
                    <img
                        src="{{ asset($settings->logo_path) }}"
                        alt="{{ $settings->site_name ?? 'Logo' }}"
                        class="mb-3"
                        @if($settings->logo_width) style="width: {{ $settings->logo_width }}px;" @endif
                        @if($settings->logo_height) style="height: {{ $settings->logo_height }}px;" @endif
                    />
                @else
                    <h5 class="mb-3">{{ $settings->site_name ?? 'HolartCMS' }}</h5>
                @endif
                @if($settings && $settings->site_description)
                    <p class="text-muted">{{ $settings->site_description }}</p>
                @endif
            </div>

            <!-- Navigation Column -->
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3">Навигация</h5>
                @if($footerMenu && $footerMenu->rootItems->count() > 0)
                <ul class="list-unstyled">
                    @foreach($footerMenu->rootItems as $item)
                        <li class="mb-2">
                            <a
                                href="{{ $item->url }}"
                                class="text-muted text-decoration-none"
                                @if($item->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                            >
                                {{ $item->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                @endif
            </div>

            <!-- Contact Column -->
            <div class="col-md-4">
                <h5 class="mb-3">Контакты</h5>
                <ul class="list-unstyled text-muted">
                    @if($settings && $settings->contact_email)
                        <li class="mb-2">Email: {{ $settings->contact_email }}</li>
                    @endif
                    @if($settings && $settings->contact_phone)
                        <li class="mb-2">Телефон: {{ $settings->contact_phone }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <hr class="border-secondary my-4" />

        <!-- Copyright -->
        <div class="text-center text-muted">
            <p class="mb-0">© {{ date('Y') }} {{ $settings->site_name ?? 'HolartCMS' }}. Все права защищены.</p>
        </div>
    </div>
</footer>

<style>
.footer-template-2 a:hover {
    color: #fff !important;
}

@media (max-width: 767px) {
    .footer-template-2 .col-md-4 {
        text-align: center;
    }
}
</style>
