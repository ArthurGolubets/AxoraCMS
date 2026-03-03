{{-- Header Template 3: Dark modern with logo left, centered menu --}}
@php
    $settings = \HolartWeb\HolartCMS\Models\TSetting::first();
    $headerMenu = \HolartWeb\HolartCMS\Models\Menus\TMenu::getByLocation('header')->first();
@endphp

<header class="header-template-3">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
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
            <div class="collapse navbar-collapse justify-content-center" id="mainNav">
                <ul class="navbar-nav">
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
                                <ul class="dropdown-menu dropdown-menu-dark">
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
.header-template-3 .navbar {
    padding: 1rem 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-template-3 .navbar-brand {
    font-size: 1.5rem;
    font-weight: bold;
    color: #fff;
    text-decoration: none;
}

.header-template-3 .logo-text {
    font-size: 1.5rem;
    font-weight: bold;
    color: #fff;
}

.header-template-3 .nav-link {
    padding: 0.5rem 1rem;
    color: rgba(255,255,255,0.85);
    font-weight: 500;
}

.header-template-3 .nav-link:hover {
    color: #fff;
}

.header-template-3 .dropdown-menu-dark {
    background-color: #343a40;
}

@media (max-width: 991px) {
    .header-template-3 .navbar-collapse {
        margin-top: 1rem;
    }

    .header-template-3 .navbar-nav {
        width: 100%;
        text-align: center;
    }
}
</style>
