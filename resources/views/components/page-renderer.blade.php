@props(['page'])

@php
    use App\Models\TPage;
    use App\Models\TPageBlock;

    // Load page if slug is provided
    if (is_string($page)) {
        $page = TPage::where('slug', $page)->where('is_active', true)->firstOrFail();
    }

    // Load blocks with relationships
    $blocks = TPageBlock::where('page_id', $page->id)
        ->whereNull('parent_block_id') // Only root blocks
        ->where('is_active', true)
        ->with(['blockType', 'childBlocks.blockType'])
        ->orderBy('sort')
        ->get();
@endphp

<div class="page-content">
    @if($page->type === 'static')
        <!-- Static page content -->
        <div class="container mx-auto px-4 py-8">
            {!! $page->content !!}
        </div>
    @else
        <!-- Dynamic page blocks -->
        @foreach($blocks as $block)
            @php
                // Check if custom template is defined for non-system blocks
                if (!$block->blockType->is_system && !empty($block->blockType->template)) {
                    $componentPath = 'components.blocks.custom.' . $block->blockType->code;
                } else {
                    // Use system block path
                    $blockCode = str_replace('_', '-', $block->blockType->code);
                    $componentPath = 'components.blocks.' . $blockCode;
                }

                $childBlocks = $block->childBlocks ?? collect();
            @endphp

            @if(view()->exists($componentPath))
                @include($componentPath, [
                    'settings' => $block->settings,
                    'block' => $block,
                    'childBlocks' => $childBlocks
                ])
            @else
                <!-- Block template not found: {{ $block->blockType->code }} -->
            @endif
        @endforeach
    @endif
</div>
