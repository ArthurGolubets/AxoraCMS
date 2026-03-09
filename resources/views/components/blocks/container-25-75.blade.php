@props(['settings' => [], 'childBlocks' => []])

@php
    $gap = $settings['gap'] ?? 'medium';
    $gapClass = match($gap) {
        'small' => 'g-3',
        'medium' => 'g-4',
        'large' => 'g-5',
        default => 'g-4',
    };

    $col1Blocks = $childBlocks->where('container_column', 'col1');
    $col2Blocks = $childBlocks->where('container_column', 'col2');

@endphp

<section class="container-25-75 py-5">
    <div class="container">
        <div class="row {{ $gapClass }}">
            <!-- Column 1 (25%) -->
            <div class="col-md-3 container-column">
                @foreach($col1Blocks as $block)
                    @php
                        $childBlockChildren = $block->childBlocks ?? collect();

                        // Check if custom template is defined for non-system blocks
                        if (!$block->blockType->is_system && !empty($block->blockType->template)) {
                            $childViewPath = 'components.blocks.custom.' . $block->blockType->code;
                        } else {
                            // Use system block path
                            $blockCode = str_replace('_', '-', $block->blockType->code);
                            $childViewPath = 'components.blocks.' . $blockCode;
                        }
                    @endphp
                    @if(view()->exists($childViewPath))
                        @include($childViewPath, ['settings' => $block->settings, 'block' => $block, 'childBlocks' => $childBlockChildren])
                    @endif
                @endforeach
            </div>

            <!-- Column 2 (75%) -->
            <div class="col-md-9 container-column">
                @foreach($col2Blocks as $block)
                    @php
                        $childBlockChildren = $block->childBlocks ?? collect();

                        // Check if custom template is defined for non-system blocks
                        if (!$block->blockType->is_system && !empty($block->blockType->template)) {
                            $childViewPath = 'components.blocks.custom.' . $block->blockType->code;
                        } else {
                            // Use system block path
                            $blockCode = str_replace('_', '-', $block->blockType->code);
                            $childViewPath = 'components.blocks.' . $blockCode;
                        }
                    @endphp
                    @if(view()->exists($childViewPath))
                        @include($childViewPath, ['settings' => $block->settings, 'block' => $block, 'childBlocks' => $childBlockChildren])
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</section>
