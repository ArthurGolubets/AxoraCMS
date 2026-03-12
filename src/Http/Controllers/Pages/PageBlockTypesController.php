<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Pages;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use HolartWeb\HolartCMS\Models\Pages\TPageBlockType;
use HolartWeb\HolartCMS\Models\TAdminAction;

class PageBlockTypesController extends Controller
{
    /**
     * Get all block types
     */
    public function index(Request $request)
    {
        $query = TPageBlockType::query();

        // Filter by category
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Filter by active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $blockTypes = $query->orderBy('category')->orderBy('name')->get();

        return response()->json($blockTypes);
    }

    /**
     * Get single block type
     */
    public function show($id)
    {
        $blockType = TPageBlockType::findOrFail($id);
        return response()->json($blockType);
    }

    /**
     * Create new block type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:t_page_block_types,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'template' => 'nullable|string',
            'fields_schema' => 'nullable|array',
            'preview_image' => 'nullable|string',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $blockType = TPageBlockType::create($validated);

        // Log activity
        TAdminAction::log('created', 'page_block_type', $blockType->id,
            'Создан тип блока "' . $blockType->name . '"');

        return response()->json($blockType, 201);
    }

    /**
     * Update block type
     */
    public function update(Request $request, $id)
    {
        $blockType = TPageBlockType::findOrFail($id);

        if ($blockType->is_system) {
            return response()->json([
                'message' => 'Системные блоки нельзя редактировать'
            ], 422);
        }

        $validated = $request->validate([
            'code' => 'required|string|unique:t_page_block_types,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'template' => 'nullable|string',
            'fields_schema' => 'nullable|array',
            'preview_image' => 'nullable|string',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldData = $blockType->getOriginal();
        $blockType->update($validated);

        // Log activity
        TAdminAction::log('updated', 'page_block_type', $blockType->id,
            'Обновлен тип блока "' . $blockType->name . '"', [
            'old' => $oldData,
            'new' => $blockType->getAttributes()
        ]);

        return response()->json($blockType);
    }

    /**
     * Delete block type
     */
    public function destroy($id)
    {
        $blockType = TPageBlockType::findOrFail($id);

        if ($blockType->is_system) {
            return response()->json([
                'message' => 'Системные блоки нельзя удалить'
            ], 422);
        }

        if (!$blockType->canDelete()) {
            return response()->json([
                'message' => 'Нельзя удалить тип блока, который используется на страницах'
            ], 422);
        }

        $blockTypeName = $blockType->name;
        $blockType->delete();

        // Log activity
        TAdminAction::log('deleted', 'page_block_type', $id,
            'Удален тип блока "' . $blockTypeName . '"');

        return response()->json(['message' => 'Тип блока удален']);
    }

    /**
     * Create Blade template for block type
     */
    public function createTemplate(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'fields_schema' => 'array'
        ]);

        $code = $validated['code'];
        $fieldsSchema = $validated['fields_schema'] ?? [];

        // Define template path
        $templatePath = resource_path("views/components/blocks/{$code}.blade.php");

        // Check if template already exists
        if (file_exists($templatePath)) {
            return response()->json([
                'message' => 'Шаблон уже существует',
                'path' => $templatePath
            ], 422);
        }

        // Create directory if it doesn't exist
        $directory = dirname($templatePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate template content
        $templateContent = $this->generateBladeTemplate($code, $fieldsSchema);

        // Write template file
        file_put_contents($templatePath, $templateContent);

        // Log activity
        TAdminAction::log('created', 'page_block_template', null,
            "Создан шаблон блока: {$code}.blade.php");

        return response()->json([
            'message' => 'Шаблон успешно создан',
            'path' => $templatePath
        ]);
    }

    /**
     * Delete Blade template for block type
     */
    public function deleteTemplate($id)
    {
        $blockType = TPageBlockType::findOrFail($id);
        $templatePath = resource_path("views/components/blocks/{$blockType->code}.blade.php");

        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Шаблон не найден'
            ], 404);
        }

        // Delete template file
        unlink($templatePath);

        // Log activity
        TAdminAction::log('deleted', 'page_block_template', $id,
            "Удален шаблон блока: {$blockType->code}.blade.php");

        return response()->json(['message' => 'Шаблон удален']);
    }

    /**
     * Generate Blade template content based on fields schema
     */
    private function generateBladeTemplate(string $code, array $fieldsSchema): string
    {
        $template = "{{-- Block: {$code} --}}\n";
        $template .= "@props(['block'])\n\n";
        $template .= "<div class=\"block-{$code}\">\n";

        if (empty($fieldsSchema)) {
            $template .= "    {{-- Add your template content here --}}\n";
            $template .= "    <p>{{ \$block->data['content'] ?? 'Content' }}</p>\n";
        } else {
            $template .= "    <div class=\"container\">\n";

            foreach ($fieldsSchema as $field) {
                $fieldName = $field['name'] ?? 'field';
                $fieldType = $field['type'] ?? 'string';
                $fieldLabel = $field['label'] ?? ucfirst($fieldName);

                $template .= "\n        {{-- {$fieldLabel} --}}\n";

                switch ($fieldType) {
                    case 'textarea':
                    case 'text':
                        $template .= "        @if(!empty(\$block->data['{$fieldName}']))\n";
                        $template .= "            <div class=\"{$fieldName}\">\n";
                        $template .= "                {!! nl2br(e(\$block->data['{$fieldName}'])) !!}\n";
                        $template .= "            </div>\n";
                        $template .= "        @endif\n";
                        break;

                    case 'image':
                        $template .= "        @if(!empty(\$block->data['{$fieldName}']))\n";
                        $template .= "            <div class=\"{$fieldName}\">\n";
                        $template .= "                <img src=\"{{ \$block->data['{$fieldName}'] }}\" alt=\"{$fieldLabel}\" class=\"img-fluid\">\n";
                        $template .= "            </div>\n";
                        $template .= "        @endif\n";
                        break;

                    case 'url':
                        $template .= "        @if(!empty(\$block->data['{$fieldName}']))\n";
                        $template .= "            <a href=\"{{ \$block->data['{$fieldName}'] }}\" class=\"btn btn-primary\">\n";
                        $template .= "                {{ \$block->data['{$fieldName}_text'] ?? '{$fieldLabel}' }}\n";
                        $template .= "            </a>\n";
                        $template .= "        @endif\n";
                        break;

                    case 'boolean':
                        $template .= "        @if(!empty(\$block->data['{$fieldName}']))\n";
                        $template .= "            <div class=\"{$fieldName}\">\n";
                        $template .= "                {{-- Content when {$fieldName} is true --}}\n";
                        $template .= "            </div>\n";
                        $template .= "        @endif\n";
                        break;

                    case 'catalog_select':
                        $template .= "        @php\n";
                        $template .= "            \${$fieldName}Ids = \$block->data['{$fieldName}'] ?? [];\n";
                        $template .= "            if (!is_array(\${$fieldName}Ids)) \${$fieldName}Ids = [\${$fieldName}Ids];\n";
                        $template .= "            \${$fieldName}Items = \\HolartWeb\\HolartCMS\\Models\\Shop\\TCatalog::whereIn('id', \${$fieldName}Ids)->where('is_active', true)->get();\n";
                        $template .= "        @endphp\n";
                        $template .= "        @if(\${$fieldName}Items->count() > 0)\n";
                        $template .= "            <div class=\"{$fieldName}\">\n";
                        $template .= "                @foreach(\${$fieldName}Items as \$catalog)\n";
                        $template .= "                    <div class=\"catalog-item\">\n";
                        $template .= "                        <h3>{{ \$catalog->name }}</h3>\n";
                        $template .= "                    </div>\n";
                        $template .= "                @endforeach\n";
                        $template .= "            </div>\n";
                        $template .= "        @endif\n";
                        break;

                    case 'infoblocks_select':
                        $template .= "        @php\n";
                        $template .= "            \${$fieldName}Ids = \$block->data['{$fieldName}'] ?? [];\n";
                        $template .= "            if (!is_array(\${$fieldName}Ids)) \${$fieldName}Ids = [\${$fieldName}Ids];\n";
                        $template .= "            \${$fieldName}Items = \\HolartWeb\\HolartCMS\\Models\\InfoBlocks\\TInfoBlockElement::whereIn('info_block_id', \${$fieldName}Ids)->where('is_active', true)->get();\n";
                        $template .= "        @endphp\n";
                        $template .= "        @if(\${$fieldName}Items->count() > 0)\n";
                        $template .= "            <div class=\"{$fieldName}\">\n";
                        $template .= "                @foreach(\${$fieldName}Items as \$element)\n";
                        $template .= "                    <div class=\"element-item\">{{ \$element->name }}</div>\n";
                        $template .= "                @endforeach\n";
                        $template .= "            </div>\n";
                        $template .= "        @endif\n";
                        break;

                    case 'products_select':
                        $template .= "        @php\n";
                        $template .= "            \${$fieldName}Ids = \$block->data['{$fieldName}'] ?? [];\n";
                        $template .= "            if (!is_array(\${$fieldName}Ids)) \${$fieldName}Ids = [\${$fieldName}Ids];\n";
                        $template .= "            \${$fieldName}Items = \\HolartWeb\\HolartCMS\\Models\\Shop\\TProduct::whereIn('id', \${$fieldName}Ids)->where('is_active', true)->get();\n";
                        $template .= "        @endphp\n";
                        $template .= "        @if(\${$fieldName}Items->count() > 0)\n";
                        $template .= "            <div class=\"{$fieldName}\">\n";
                        $template .= "                @foreach(\${$fieldName}Items as \$product)\n";
                        $template .= "                    <div class=\"product-item\">\n";
                        $template .= "                        <h3>{{ \$product->name }}</h3>\n";
                        $template .= "                        <p>{{ number_format(\$product->price, 0, '.', ' ') }} ₽</p>\n";
                        $template .= "                    </div>\n";
                        $template .= "                @endforeach\n";
                        $template .= "            </div>\n";
                        $template .= "        @endif\n";
                        break;

                    case 'repeater':
                        $template .= "        @php\n";
                        $template .= "            \${$fieldName}Items = \$block->data['{$fieldName}'] ?? [];\n";
                        $template .= "        @endphp\n";
                        $template .= "        @if(!empty(\${$fieldName}Items) && is_array(\${$fieldName}Items))\n";
                        $template .= "            <div class=\"{$fieldName}\">\n";
                        $template .= "                @foreach(\${$fieldName}Items as \$item)\n";
                        $template .= "                    <div class=\"{$fieldName}-item\">\n";
                        $template .= "                        {{-- Access: \$item['field_name'] --}}\n";
                        $template .= "                    </div>\n";
                        $template .= "                @endforeach\n";
                        $template .= "            </div>\n";
                        $template .= "        @endif\n";
                        break;

                    default:
                        $template .= "        @if(!empty(\$block->data['{$fieldName}']))\n";
                        $template .= "            <div class=\"{$fieldName}\">\n";
                        $template .= "                {{ \$block->data['{$fieldName}'] }}\n";
                        $template .= "            </div>\n";
                        $template .= "        @endif\n";
                        break;
                }
            }

            $template .= "    </div>\n";
        }

        $template .= "</div>\n";

        return $template;
    }
}
