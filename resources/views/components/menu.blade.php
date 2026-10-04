{{--
    Renders an active menu as nested <ul> lists:

        <x-axora-cms::menu code="main" />
        <x-axora-cms::menu code="footer" class="footer-nav" />

    Need custom markup? Use the data directly:
        @foreach (\HolartWeb\AxoraCMS\Models\Menus\TMenu::tree('main') as $item) ... @endforeach

    Publish the views (php artisan vendor:publish --tag=axora-cms-views) to restyle it.
--}}
@props(['code'])

@php($items = \HolartWeb\AxoraCMS\Models\Menus\TMenu::tree($code))

@if (! empty($items))
    <nav {{ $attributes->merge(['class' => 'axora-menu axora-menu--'.$code]) }}>
        <x-axora-cms::menu-items :items="$items" />
    </nav>
@endif
