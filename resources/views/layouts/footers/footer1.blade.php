{{-- Footer Template 1: Simple centered footer --}}
@php
    $settings = \HolartWeb\HolartCMS\Models\TSetting::first();
    $footerMenu = \HolartWeb\HolartCMS\Models\Menus\TMenu::getByLocation('footer')->first();
@endphp

<footer class="footer-template-1 bg-light py-4 mt-5">
    <div class="container">
        <!-- Footer Menu -->
        @if($footerMenu && $footerMenu->rootItems->count() > 0)
        <nav class="footer-nav text-center mb-3">
            <ul class="list-inline mb-0">
                @foreach($footerMenu->rootItems as $item)
                    <li class="list-inline-item">
                        <a
                            href="{{ $item->url }}"
                            @if($item->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                        >
                            {{ $item->title }}
                        </a>
                    </li>
                    @if(!$loop->last)
                        <li class="list-inline-item">|</li>
                    @endif
                @endforeach
            </ul>
        </nav>
        @endif

        <!-- Copyright -->
        <div class="text-center text-muted">
            <p class="mb-0">© {{ date('Y') }} {{ $settings->site_name ?? 'HolartCMS' }}. Все права защищены.</p>
        </div>
    </div>
</footer>

<style>
.footer-template-1 {
    border-top: 1px solid #dee2e6;
}

.footer-template-1 .footer-nav a {
    color: #212529;
    text-decoration: none;
    padding: 0 0.5rem;
}

.footer-template-1 .footer-nav a:hover {
    color: #0d6efd;
    text-decoration: underline;
}

@media (max-width: 767px) {
    .footer-template-1 .list-inline-item {
        display: block;
        margin: 0.5rem 0;
    }

    .footer-template-1 .list-inline-item:has(a) + li {
        display: none;
    }
}
</style>
