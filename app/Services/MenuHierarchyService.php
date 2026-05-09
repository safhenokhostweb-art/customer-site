<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Collection;

class MenuHierarchyService
{
    public function __construct(
        protected int $maxDepth
    ) {
        $this->maxDepth = $maxDepth > 0 ? $maxDepth : 4;
    }

    public static function fromConfig(): self
    {
        return new self((int) config('menu.max_depth', 4));
    }

    public function wouldCreateCycle(int $itemId, ?int $newParentId, int $menuId): bool
    {
        if ($newParentId === null) {
            return false;
        }
        if ($newParentId === $itemId) {
            return true;
        }
        $current = MenuItem::query()->where('menu_id', $menuId)->find($newParentId);
        while ($current) {
            if ((int) $current->id === $itemId) {
                return true;
            }
            if (! $current->parent_id) {
                break;
            }
            $current = MenuItem::query()->where('menu_id', $menuId)->find($current->parent_id);
        }

        return false;
    }

    public function depthOfItem(MenuItem $item): int
    {
        $d = 1;
        $p = $item->parent;
        while ($p) {
            $d++;
            $p = $p->parent;
        }

        return $d;
    }

    /**
     * ارتفاع زیردرخت از ریشهٔ داده‌شده (خود آیتم سطح ۱ است).
     */
    public function subtreeHeight(MenuItem $item): int
    {
        $children = $item->relationLoaded('children')
            ? $item->children
            : $item->children()->get();
        if ($children->isEmpty()) {
            return 1;
        }

        return 1 + (int) $children->map(fn (MenuItem $c) => $this->subtreeHeight($c))->max();
    }

    /**
     * آیا جابه‌جایی زیردرخت $root زیر $newParent مجاز است؟
     */
    public function canAttachSubtree(MenuItem $root, ?int $newParentId, int $menuId, ?int $ignoreSubtreeRootId = null): bool
    {
        if ($newParentId !== null) {
            if ($this->wouldCreateCycle($root->id, $newParentId, $menuId)) {
                return false;
            }
            $parent = MenuItem::query()->where('menu_id', $menuId)->find($newParentId);
            if (! $parent) {
                return false;
            }
            $parentDepth = $this->depthOfItem($parent);
        } else {
            $parentDepth = 0;
        }

        $height = $this->subtreeHeight($root);

        return ($parentDepth + $height) <= $this->maxDepth;
    }

    /**
     * اعتبارسنجی آرایهٔ flat از parent_id و عمق پس از اعمال.
     *
     * @param  array<int, array{id:int,parent_id:int|null,order:int}>  $nodes
     */
    public function validateTreeAssignment(int $menuId, array $nodes, ?Collection $existingItems = null): ?string
    {
        $existingItems ??= MenuItem::query()->where('menu_id', $menuId)->get()->keyBy('id');
        $ids = collect($nodes)->pluck('id')->all();
        foreach ($nodes as $row) {
            if (! $existingItems->has($row['id'])) {
                return 'شناسهٔ نامعتبر در درخت منو.';
            }
        }
        $byId = collect($nodes)->keyBy('id');
        foreach ($nodes as $row) {
            $pid = $row['parent_id'];
            if ($pid === null) {
                continue;
            }
            if (! $byId->has($pid)) {
                return 'والد باید در همان درخت باشد.';
            }
        }
        foreach ($nodes as $row) {
            if ($this->pathHasCycle((int) $row['id'], $byId)) {
                return 'چرخه در سلسلهٔ والد ممنوع است.';
            }
        }
        foreach ($nodes as $row) {
            $depth = $this->simulatedDepth((int) $row['id'], $byId);
            $height = $this->simulatedSubtreeHeight((int) $row['id'], $byId);
            if ($depth + $height - 1 > $this->maxDepth) {
                return 'حداکثر عمق منو رعایت نشده است.';
            }
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{id:int,parent_id:int|null}>  $byId
     */
    protected function pathHasCycle(int $itemId, Collection $byId): bool
    {
        $seen = [];
        $cur = $itemId;
        while ($cur !== null) {
            if (isset($seen[$cur])) {
                return true;
            }
            $seen[$cur] = true;
            $row = $byId->get($cur);
            if (! $row) {
                break;
            }
            $p = $row['parent_id'];
            $cur = $p !== null ? (int) $p : null;
        }

        return false;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{id:int,parent_id:int|null}>  $byId
     */
    protected function simulatedDepth(int $itemId, Collection $byId): int
    {
        $d = 1;
        $row = $byId->get($itemId);
        while ($row && $row['parent_id'] !== null) {
            $d++;
            $row = $byId->get((int) $row['parent_id']);
        }

        return $d;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{id:int,parent_id:int|null}>  $byId
     */
    protected function simulatedSubtreeHeight(int $itemId, Collection $byId): int
    {
        $children = $byId->filter(fn ($r) => (int) ($r['parent_id'] ?? 0) === $itemId);
        if ($children->isEmpty()) {
            return 1;
        }

        return 1 + (int) $children->map(fn ($c) => $this->simulatedSubtreeHeight((int) $c['id'], $byId))->max();
    }
}
