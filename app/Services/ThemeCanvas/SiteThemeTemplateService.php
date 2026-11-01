<?php

namespace App\Services\ThemeCanvas;

use App\Models\SiteThemeTemplate;
use Illuminate\Support\Str;
use SaaSPlatform\ThemeRuntime\TemplateLibrary;

class SiteThemeTemplateService
{
    /**
     * Canvas overhaul Unit 5 (ADR-075): the package's ready-made library
     * first, then this site's own saved templates, in one card shape.
     */
    public function list(?string $type = null, ?string $category = null, ?string $term = null): array
    {
        $q = SiteThemeTemplate::active()->orderByDesc('usage_count')->orderByDesc('created_at');

        if ($type)     $q->byType($type);
        if ($category) $q->byCategory($category);
        if ($term)     $q->where('name', 'like', "%{$term}%");

        $saved = $q->get(['id','name','description','type','category','thumbnail_url','tags','usage_count'])
            ->map(fn($t) => [
                'id'            => $t->id,
                'source'        => 'saved',
                'name'          => $t->name,
                'description'   => $t->description,
                'type'          => $t->type,
                'type_label'    => $t->typeLabel(),
                'category'      => $t->category,
                'category_label'=> $t->categoryLabel(),
                'zone'          => TemplateLibrary::zone((string) $t->type),
                'thumbnail_url' => $t->thumbnail_url,
                'tags'          => $t->tags ?? [],
                'usage_count'   => $t->usage_count,
            ])->values()->all();

        return array_merge(TemplateLibrary::list($type, $category, $term), $saved);
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

    /**
     * A library ("lib:<key>") or saved template's blocks with fresh ids and
     * the editor zone they belong to.
     *
     * Fail-closed like the platform's insert endpoint: a template naming a
     * block type this site cannot render never reaches the editor (ext:*
     * types are left to the save-time ExtensionBlockReconciler).
     *
     * @return array{zone: string, blocks: list<array>}
     * @throws \DomainException listing the unavailable block types
     */
    public function insertBlocks(int|string $id): array
    {
        if (TemplateLibrary::isLibraryId($id)) {
            ['zone' => $zone, 'blocks' => $blocks] = TemplateLibrary::find($id);
        } else {
            $template = SiteThemeTemplate::findOrFail((int) $id);
            $blocks = $template->blocks ?? [];
            $zone = TemplateLibrary::zone((string) $template->type);
        }

        $unknown = array_values(array_diff($this->types($blocks), array_keys(BlockRegistry::all())));
        if ($unknown !== []) {
            throw new \DomainException(implode(', ', $unknown));
        }
        isset($template) && $template->incrementUsage();

        return ['zone' => $zone, 'blocks' => $this->regenIds($blocks)];
    }

    /** @return list<string> every non-extension block type in the tree */
    private function types(array $blocks): array
    {
        $types = [];
        foreach ($blocks as $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;
            if (is_string($type) && !str_starts_with($type, 'ext:')) {
                $types[] = $type;
            }
            if (is_array($block['children'] ?? null)) {
                $types = array_merge($types, $this->types($block['children']));
            }
        }
        return array_values(array_unique($types));
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
