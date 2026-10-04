@props(['items', 'level' => 0])

<ul class="axora-menu__list axora-menu__list--level-{{ $level }}">
    @foreach ($items as $item)
        @php($isActive = $item['url'] && url()->current() === url($item['url']))
        <li @class(['axora-menu__item', 'axora-menu__item--active' => $isActive, 'axora-menu__item--has-children' => ! empty($item['children'])])>
            <a href="{{ $item['url'] ?: '#' }}" target="{{ $item['target'] }}" @if ($item['target'] === '_blank') rel="noopener" @endif>{{ $item['title'] }}</a>

            @if (! empty($item['children']))
                <x-axora-cms::menu-items :items="$item['children']" :level="$level + 1" />
            @endif
        </li>
    @endforeach
</ul>
