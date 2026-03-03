{{-- Header Template 1: Classic centered logo with menu below --}}
@php
    $settings = \HolartWeb\HolartCMS\Models\TSetting::first();
    $headerMenu = \HolartWeb\HolartCMS\Models\Menus\TMenu::getByLocation('header')->first();
@endphp

<header class="header-template-1">
    <div class="container">
        <!-- Logo Row -->
        <div class="logo-row text-center py-3">
            <a href="/" class="logo">
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
        </div>

        <!-- Navigation Row -->
        @if($headerMenu && $headerMenu->rootItems->count() > 0)
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid justify-content-center">
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
            </div>
        </nav>
        @endif
    </div>
</header>

<style>
.header-template-1 .logo-row {
    border-bottom: 1px solid #dee2e6;
}

.header-template-1 .logo {
    display: inline-block;
    text-decoration: none;
}

.header-template-1 .logo-text {
    font-size: 2rem;
    font-weight: bold;
    color: #212529;
}

.header-template-1 .navbar {
    border-bottom: 1px solid #dee2e6;
}

.header-template-1 .nav-link {
    padding: 0.5rem 1rem;
    color: #212529;
    font-weight: 500;
}

.header-template-1 .nav-link:hover {
    color: #0d6efd;
}

@media (max-width: 991px) {
    .header-template-1 .navbar-collapse {
        text-align: center;
    }

    .header-template-1 .navbar-nav {
        width: 100%;
    }

    .header-template-1 .dropdown-menu {
        text-align: center;
        border: none;
        background: transparent;
    }
}
</style>
