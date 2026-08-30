<?php

namespace App\Services\ThemeCanvas;

use App\Models\SiteThemeTemplate;
use Illuminate\Support\Str;

class SiteThemeTemplateService
{
    public function list(?string $type = null, ?string $category = null, ?string $term = null): array
    {
        $q = SiteThemeTemplate::active()->orderByDesc('usage_count')->orderByDesc('created_at');

        if ($type)     $q->byType($type);
        if ($category) $q->byCategory($category);
        if ($term)     $q->where('name', 'like', "%{$term}%");

        return $q->get(['id','name','type','category','thumbnail_url','tags','usage_count'])
            ->map(fn($t) => [
                'id'            => $t->id,
                'name'          => $t->name,
                'type'          => $t->type,
                'type_label'    => $t->typeLabel(),
                'category'      => $t->category,
                'category_label'=> $t->categoryLabel(),
                'thumbnail_url' => $t->thumbnail_url,
                'tags'          => $t->tags ?? [],
                'usage_count'   => $t->usage_count,
            ])->values()->all();
    }

    public function save(array $data): SiteThemeTemplate
    {
        return SiteThemeTemplate::create([
            'name'         => $data['name'],
            'type'         => $data['type'] ?? 'section',
            'category'     => $data['category'] ?? 'hero',
            'description'  => $data['description'] ?? null,
            'tags'         => $data['tags'] ?? [],
            'blocks'       => $data['blocks'] ?? [],
            'global_styles'=> $data['global_styles'] ?? null,
            'is_active'    => true,
        ]);
    }

    public function insertBlocks(int $id): array
    {
        $template = SiteThemeTemplate::findOrFail($id);
        $template->incrementUsage();

        $blocks = $template->blocks ?? [];
        return $this->regenIds($blocks);
    }

    private function regenIds(array $blocks): array
    {
        // Phase 3D-B: inserting a saved reusable block/template creates a
        // fresh customer-owned block instance — 'cust_' namespace, matching
        // the canvas editor's own new-block/duplicate id convention, so a
        // future reconciler can always tell it has no master counterpart.
        foreach ($blocks as &$block) {
            $block['id'] = 'cust_' . Str::random(8);
            if (!empty($block['children'])) {
                $block['children'] = $this->regenIds($block['children']);
            }
        }
        return $blocks;
    }

    public function filterOptions(): array
    {
        return [
            'types'      => SiteThemeTemplate::TYPES,
            'categories' => SiteThemeTemplate::CATEGORIES,
        ];
    }
}
