{{-- Header Template 2: Logo left, menu right (horizontal) --}}
@php
    $settings = \HolartWeb\HolartCMS\Models\TSetting::first();
    $headerMenu = \HolartWeb\HolartCMS\Models\Menus\TMenu::getByLocation('header')->first();
@endphp

<header class="header-template-2">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <!-- Logo -->
            <a href="/" class="navbar-brand">
                @if($settings && $settings->logo_path)
                    <img
                        src="{{ asset($settings->logo_path) }}"
                        alt="{{ $settings->site_name ?? 'Logo' }}"
                        @if($settings->logo_width) style="width: {{ $settings->logo_width }}px;" @endif
                        @if($settings->logo_height) style="height: {{ $settings->logo_height }}px;" @endif
                    />
                @else
                    <span class="logo-text">{{ $settings->site_name ?? 'HolartCMS' }}</span>
                @endif
            </a>

            <!-- Mobile Toggle -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav"
                aria-controls="mainNav"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation -->
            @if($headerMenu && $headerMenu->rootItems->count() > 0)
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto">
                    @foreach($headerMenu->rootItems as $item)
                        @if($item->children && $item->children->count() > 0)
                            <li class="nav-item dropdown">
                                <a
                                    class="nav-link dropdown-toggle"
                                    href="#"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    {{ $item->title }}
                                </a>
                                <ul class="dropdown-menu">
                                    @foreach($item->children as $child)
                                        <li>
                                            <a
                                                class="dropdown-item"
                                                href="{{ $child->url }}"
                                                @if($child->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                            >
                                                {{ $child->title }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a
                                    class="nav-link"
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
            @endif
        </div>
    </nav>
</header>

<style>
.header-template-2 .navbar {
    padding: 1rem 0;
}

.header-template-2 .navbar-brand {
    font-size: 1.5rem;
    font-weight: bold;
    color: #212529;
    text-decoration: none;
}

.header-template-2 .logo-text {
    font-size: 1.5rem;
    font-weight: bold;
    color: #212529;
}

.header-template-2 .nav-link {
    padding: 0.5rem 1rem;
    color: #212529;
    font-weight: 500;
}

.header-template-2 .nav-link:hover {
    color: #0d6efd;
}

@media (max-width: 991px) {
    .header-template-2 .navbar-collapse {
        margin-top: 1rem;
    }

    .header-template-2 .navbar-nav {
        width: 100%;
    }

    .header-template-2 .nav-item {
        text-align: center;
    }
}
</style>
