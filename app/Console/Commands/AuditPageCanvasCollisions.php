<?php

namespace App\Console\Commands;

use App\Support\CanvasReservedSlugs;
use Illuminate\Console\Command;

/**
 * 2026-09-11 — read-only detection for the Page/Canvas namespace policy
 * (see CanvasReservedSlugs). The mutation boundaries (Page create/rename,
 * Canvas publish/install/import/rollback) now reject any NEW collision, but
 * cannot retroactively fix a collision that already existed on disk before
 * this fix shipped. This command only detects and reports; it never
 * renames, deletes, or otherwise mutates a Page or the active canvas theme
 * — remediation (renaming the Page, or removing the conflicting entry from
 * the canvas theme via Theme Design Canvas) is a deliberate admin decision,
 * never automatic.
 */
class AuditPageCanvasCollisions extends Command
{
    protected $signature = 'pages:audit-canvas-collisions';

    protected $description = 'Read-only: list existing Page rows whose slug is already owned by the active canvas theme and are therefore silently unreachable at /page/{slug}.';

    public function handle(): int
    {
        $shadowed = CanvasReservedSlugs::all();
        if ($shadowed === []) {
            $this->info('No active canvas theme, or it owns no page slugs. Nothing to check.');
            return self::SUCCESS;
        }

        $colliding = \App\Models\Page::whereIn('slug', $shadowed)->get(['id', 'title', 'slug', 'is_published']);

        if ($colliding->isEmpty()) {
            $this->info('No existing Page rows collide with the active canvas theme\'s page slugs.');
            return self::SUCCESS;
        }

        $this->warn(sprintf('%d existing Page row(s) are shadowed by the active canvas theme and are NOT publicly reachable at /page/{slug}:', $colliding->count()));
        $this->table(
            ['Page ID', 'Title', 'Slug', 'Published'],
            $colliding->map(fn ($p) => [$p->id, $p->title, $p->slug, $p->is_published ? 'yes' : 'no'])->all(),
        );
        $this->line('Remediation (manual, not automatic): either rename the Page\'s slug in Admin > Pages, or remove the colliding page from the active canvas theme in Theme Design Canvas.');

        return self::FAILURE;
    }
}
