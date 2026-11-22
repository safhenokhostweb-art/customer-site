<?php

namespace App\Services\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Services\CustomerMediaService;
use App\Support\CanvasReservedSlugs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Single write path for the customer's ACTIVE Theme instance state.
 *
 * Invariant (Phase 3B): any operation that changes the active Theme
 * instance must produce an immutable ThemeInstanceRevision row, and
 * afterward active_theme_revision_id must point at exactly that row. No
 * controller may set active_canvas_theme_data / active_canvas_theme_slug /
 * active_canvas_theme_name / active_canvas_theme_id / active_theme_revision_id
 * directly — every one of those Settings is written only from inside this
 * class (record() or deactivate()), in one DB transaction per operation, so
 * a failure partway through never leaves the live state and the revision
 * pointer disagreeing.
 *
 * Every call to record() lands an immutable row in theme_instance_revisions
 * (never overwritten, never deleted) before touching the live Setting, so
 * every published/installed/restored state stays permanently recoverable —
 * unlike the legacy single backup slot and 5-slot rotating window, which
 * this service does not replace but now backs with a permanent record of
 * the same events.
 */
class ThemeInstanceRevisionService
{
    public const DEFAULT_IDENTIFIER = 'custom';

    /**
     * TASK 3/3 Phase 6.5D (BLD-30) — revision types whose $themeData
     * genuinely represents fresh, wholesale Platform-sourced content (a
     * real install, a marketplace/import update, or a reconciled upgrade)
     * and must therefore pass through media localization before becoming
     * live. Deliberately excludes TYPE_PUBLISH (the customer's own local
     * draft going live — never Platform-sourced), TYPE_ROLLBACK_SNAPSHOT
     * (restoring theme_data that was already localized when originally
     * recorded — re-running is a safe no-op but wasted work), and
     * TYPE_CUSTOMER_EDIT.
     */
    private const LOCALIZABLE_TYPES = [
        ThemeInstanceRevision::TYPE_INSTALL,
        ThemeInstanceRevision::TYPE_UPDATE,
        ThemeInstanceRevision::TYPE_UPGRADE_RECONCILED,
    ];

    public function __construct(
        private ThemeInstanceBaseRelease $baseRelease,
        private CustomerMediaService $media,
        private ThemeMediaLocalizationService $mediaLocalizer,
        private ThemeAssetLocalizationService $assetLocalizer,
    ) {
    }

    /**
     * The lineage the *currently active* theme belongs to. Installing a
     * different marketplace theme changes this going forward; past
     * revisions under the old identifier are untouched and simply stop
     * appearing in the "current" list.
     */
    public function currentIdentifier(): string
    {
        return Setting::get('active_canvas_theme_slug') ?: self::DEFAULT_IDENTIFIER;
    }

    /**
     * Record a new immutable revision, then atomically point the live theme
     * at it. theme_data is the decoded (array) theme structure — same shape
     * as json_decode(Setting::get('active_canvas_theme_data'), true).
     *
     * Identity Settings (active_canvas_theme_slug/name/id) are only touched
     * when the caller explicitly supplies them via $context — a bare
     * publish/rollback of the *currently* active lineage must never disturb
     * which theme is considered installed. Callers that ARE changing which
     * theme is installed (install/update/import/legacy-backup-restore) pass
     * 'theme_identifier' (+ optionally 'theme_name', 'active_theme_id') so
     * the identity switch lands in the same transaction as the revision and
     * the pointer — see the class docblock invariant.
     *
     * Phase 3D-B: release provenance (source_theme_version/source_release_id)
     * follows the same explicit-context-only rule as the identity Settings —
     * see resolveProvenance() below.
     *
     * 2026-09-11 — the reverse half of the Page/Canvas namespace policy
     * (see CanvasReservedSlugs): this is the single write path for
     * anything that can make $themeData's pages live (publish, install,
     * import, rollback, upgrade-reconcile), so it is the one place that
     * can reject ALL of them uniformly if $themeData would silently
     * shadow an existing Page row — ThemeCanvasPageController::show()
     * always resolves a canvas-owned page first, so a colliding Page
     * would become permanently, silently unreachable the moment this
     * became active.
     *
     * @throws ConflictHttpException if any page in $themeData would shadow
     *   an existing Page row — fails closed; existing Page content is
     *   never silently shadowed or auto-renamed.
     */
    public function record(array $themeData, string $type, array $context = []): ThemeInstanceRevision
    {
        $shadowed = CanvasReservedSlugs::collidingWithExistingPages($themeData);
        if ($shadowed !== []) {
            throw new ConflictHttpException(
                'این عملیات صفحه(های) موجود را با این آدرس‌ها نامرئی می‌کند و انجام نشد: '
                . implode('، ', $shadowed)
                . '. ابتدا آن صفحه(ها) را در مدیریت صفحات حذف یا تغییر آدرس دهید.'
            );
        }

        // TASK 3/3 Phase 6.5D (BLD-30) — localization moved to this single
        // canonical write boundary (was previously a side effect of one
        // controller action, PageBuilderController::installTheme(), which
        // meant any OTHER real caller of record() — including the
        // marketplace upgrade flow and the browser test harness — silently
        // bypassed it; certification proved the real canonical Universal
        // Theme fixture had never actually been localized by any test in
        // this suite as a result). Runs BEFORE the revision transaction, on
        // purpose: a localization failure (ThemeMediaLocalizationException,
        // thrown only for a URL that matched the trusted Platform origin —
        // see that service's own docblock) must abort the whole operation
        // atomically, with nothing written at all, never a partially-live
        // revision with some media still hotlinked.
        $identifier = $context['theme_identifier'] ?? $this->currentIdentifier();

        if (in_array($type, self::LOCALIZABLE_TYPES, true)) {
            $themeData = $this->mediaLocalizer->localize($themeData, array_merge($context, [
                'theme_identifier' => $identifier,
            ]));
            if (array_key_exists('theme_assets', $themeData) && !is_array($themeData['theme_assets'])) {
                throw new ThemeAssetLocalizationException('Malformed theme_assets manifest.');
            }
            $themeData['theme_assets'] = $this->assetLocalizer->localize(
                $themeData['theme_assets'] ?? [],
                array_merge($context, ['theme_identifier' => $identifier]),
            );
        }

        return DB::transaction(function () use ($themeData, $type, $context, $identifier) {
            // lockForUpdate() serializes concurrent record() calls for the
            // same lineage (e.g. an autosave-triggered publish racing a
            // rollback) so two writers can never compute the same
            // next revision_number — the unique(theme_identifier,
            // revision_number) constraint would otherwise let the loser
            // fail with a raw DB error instead of just... not being first.
            $nextNumber = (int) ThemeInstanceRevision::where('theme_identifier', $identifier)
                ->lockForUpdate()
                ->max('revision_number') + 1;

            $themeName = array_key_exists('theme_name', $context)
                ? $context['theme_name']
                : Setting::get('active_canvas_theme_name');

            [$sourceThemeVersion, $sourceReleaseId] = $this->resolveProvenance($context);

            $revision = ThemeInstanceRevision::create([
                'theme_identifier' => $identifier,
                'source_theme_id' => array_key_exists('source_theme_id', $context)
                    ? $context['source_theme_id']
                    : Setting::get('active_canvas_theme_id'),
                'source_theme_version' => $sourceThemeVersion,
                'source_release_id' => $sourceReleaseId,
                'revision_number' => $nextNumber,
                'type' => $type,
                'theme_data' => $themeData,
                'metadata' => array_merge(['theme_name' => $themeName], $context['metadata'] ?? []),
                'created_by' => auth()->id(),
            ]);

            // Immutable revisions are usage-bearing: deleting a media asset
            // referenced by any rollback target would silently corrupt it.
            $this->media->registerThemeRevisionUsages($revision->id, $themeData);

            Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
            Setting::set('active_theme_revision_id', $revision->id, 'theme');

            if (array_key_exists('theme_identifier', $context)) {
                Setting::set(
                    'active_canvas_theme_slug',
                    $identifier === self::DEFAULT_IDENTIFIER ? '' : $identifier,
                    'theme'
                );
            }
            if (array_key_exists('theme_name', $context)) {
                Setting::set('active_canvas_theme_name', $context['theme_name'], 'theme');
            }
            if (array_key_exists('active_theme_id', $context)) {
                Setting::set('active_canvas_theme_id', $context['active_theme_id'], 'theme');
            }

            // Index demo static assets for the active theme into customer_media
            // and sync them to the active media disk (e.g. 'arvan' if enabled)
            $themeSlug = (string) ($identifier ?: ($context['active_theme_id'] ?? ''));
            $this->indexThemeStaticAssetsForSlug($themeSlug);

            return $revision;
        });
    }

    /**
     * Locate and index demo assets for any given theme slug (e.g. solar-industry -> solar-assets)
     */
    public function indexThemeStaticAssetsForSlug(string $themeSlug): int
    {
        if (empty($themeSlug)) {
            return 0;
        }

        $candidates = [
            $themeSlug . '-assets',
            'theme-assets/' . $themeSlug,
            preg_replace('/-theme$/', '', $themeSlug) . '-assets',
        ];

        // Known alias mappings
        if ($themeSlug === 'solar-industry' || $themeSlug === '8') {
            $candidates[] = 'solar-assets';
        }

        $totalIndexed = 0;
        foreach (array_unique($candidates) as $folder) {
            $dir = storage_path('app/public/' . $folder);
            if (is_dir($dir)) {
                $totalIndexed += $this->indexThemeStaticAssets($folder, $themeSlug);
            }
        }

        return $totalIndexed;
    }

    /**
     * Index a static theme asset folder (e.g. storage/app/public/solar-assets)
     * into customer_media on the active media disk (MEDIA_DISK, e.g. arvan)
     * if not already indexed, so they appear in Media Library and are served properly.
     */
    public function indexThemeStaticAssets(?string $folder = null, ?string $themeSlug = null): int
    {
        if ($folder === null) {
            return 0;
        }

        try {
            $siteId = (int) config('site.platform_site_id', 0);
            if ($siteId <= 0) {
                return 0;
            }

            $dir = storage_path('app/public/' . $folder);
            if (!is_dir($dir)) {
                return 0;
            }

            $mediaStorage = app(\App\Services\MediaStorage::class);
            $targetDisk = $mediaStorage->diskNameFor('uploads');

            $dirName = basename($dir);
            $files = glob($dir . '/*.*') ?: [];
            $indexedCount = 0;

            foreach ($files as $filePath) {
                if (is_dir($filePath)) {
                    continue;
                }
                $filename = basename($filePath);
                $storageKey = $dirName . '/' . $filename;
                $sourceUrl = '/storage/' . $dirName . '/' . $filename;

                $existing = \App\Models\CustomerMedia::where('site_id', $siteId)
                    ->where(function ($q) use ($storageKey, $sourceUrl) {
                        $q->where('storage_key', $storageKey)
                          ->orWhereJsonContains('metadata->source_url', $sourceUrl);
                    })
                    ->first();

                $fileBytes = @file_get_contents($filePath);
                if ($fileBytes === false || $fileBytes === '') {
                    continue;
                }

                // If targetDisk is not local public disk (e.g. 'arvan' or 's3'), ensure physical file exists on targetDisk
                if ($targetDisk !== 'public' && !\Illuminate\Support\Facades\Storage::disk($targetDisk)->exists($storageKey)) {
                    \Illuminate\Support\Facades\Storage::disk($targetDisk)->put($storageKey, $fileBytes, ['visibility' => 'public']);
                }

                if ($existing) {
                    // If media record exists but points to local public while active disk is now arvan, sync disk
                    if ($existing->disk !== $targetDisk) {
                        $existing->update(['disk' => $targetDisk]);
                    }
                    continue;
                }

                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png'         => 'image/png',
                    'webp'        => 'image/webp',
                    'gif'         => 'image/gif',
                    'svg'         => 'image/svg+xml',
                    'mp4'         => 'video/mp4',
                    'webm'        => 'video/webm',
                    default       => 'application/octet-stream',
                };
                $dims = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? @getimagesize($filePath) : null;
                $category = in_array($mime, ['video/mp4', 'video/webm'], true) ? 'video' : 'image';

                \App\Models\CustomerMedia::create([
                    'site_id'           => $siteId,
                    'uuid'              => (string) \Illuminate\Support\Str::uuid(),
                    'disk'              => $targetDisk,
                    'storage_key'       => $storageKey,
                    'original_filename' => $filename,
                    'extension'         => $ext,
                    'category'          => $category,
                    'mime_type'         => $mime,
                    'size_bytes'        => filesize($filePath) ?: strlen($fileBytes),
                    'title'             => pathinfo($filename, PATHINFO_FILENAME),
                    'alt_text'          => pathinfo($filename, PATHINFO_FILENAME),
                    'width'             => $dims[0] ?? null,
                    'height'            => $dims[1] ?? null,
                    'metadata'          => [
                        'source_url'       => $sourceUrl,
                        'theme_identifier' => $themeSlug ?: $dirName,
                    ],
                ]);
                $indexedCount++;
            }

            return $indexedCount;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('indexThemeStaticAssets skipped: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ledger-authoritative release identity (Phase 1h, extended Phase 3D-B).
     *
     * Never fabricated: 'source_theme_version'/'source_release_id' in
     * $context — even explicitly passed as null — are always respected
     * exactly as given (this is how install()/update()/import() assert
     * "no release exists yet" or "verified provenance", and must never be
     * second-guessed). Only when the caller omits BOTH keys entirely does
     * this fall back to 'resolve_provenance_from': an explicit revision id
     * the caller has already decided is the correct provenance source for
     * this new row (publish resolves from the revision being replaced;
     * rollback resolves from the revision being restored — see
     * ThemeInstanceBaseRelease's docblock for why those must differ). If
     * that resolution is NON_RECONCILABLE, or neither mechanism applies,
     * both fields stay null — never guessed.
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function resolveProvenance(array $context): array
    {
        if (array_key_exists('source_theme_version', $context) || array_key_exists('source_release_id', $context)) {
            return [$context['source_theme_version'] ?? null, $context['source_release_id'] ?? null];
        }

        if (isset($context['resolve_provenance_from'])) {
            $resolution = $this->baseRelease->resolveForRevisionId((int) $context['resolve_provenance_from']);
            if ($resolution->isResolved()) {
                return [$resolution->sourceThemeVersion, $resolution->sourceReleaseId];
            }
        }

        return [null, null];
    }

    /**
     * Transition to "no active Theme" — deliberately NOT a
     * ThemeInstanceRevision (there is no theme_data to snapshot; an empty
     * canvas isn't a content state worth version-controlling as if it were
     * one). All prior revisions for the deactivated lineage remain exactly
     * as they were, permanently browsable — only the live pointer changes,
     * so a subsequent install/import/rollback starts clean and the history
     * UI correctly shows nothing as "active" in the meantime.
     */
    public function deactivate(): void
    {
        DB::transaction(function () {
            Setting::set('active_canvas_theme_data', '', 'theme');
            Setting::set('active_canvas_theme_name', '', 'theme');
            Setting::set('active_canvas_theme_slug', '', 'theme');
            Setting::set('active_canvas_theme_id', '', 'theme');
            Setting::set('active_theme_revision_id', '', 'theme');
        });
    }

    /**
     * Idempotent self-healing reconciliation for the current lineage. Two
     * distinct existing-installation problems, both safe to run on every
     * history read:
     *
     * 1. No revision rows exist yet for this lineage (a site customized
     *    before this feature shipped) — backfill a single revision #1
     *    (type=install, metadata.backfilled) wrapping whatever is live, so
     *    history starts continuous instead of empty.
     * 2. Revision rows DO exist, but active_theme_revision_id is missing or
     *    points outside this lineage (possible on data written before
     *    Phase 3B closed the canvasImport()/canvasDeactivate()/legacy
     *    rollback() bypasses) — repair the pointer to the latest revision
     *    of the current lineage rather than leaving a dangling/foreign id
     *    for the history UI to misreport.
     *
     * Never touches theme_data, never creates a second backfill once one
     * exists, and never overwrites an already-valid pointer.
     */
    public function ensureBackfilled(): void
    {
        $identifier = $this->currentIdentifier();

        $latest = ThemeInstanceRevision::where('theme_identifier', $identifier)
            ->orderByDesc('revision_number')
            ->first();

        if (! $latest) {
            $raw = Setting::get('active_canvas_theme_data');
            if (empty($raw)) {
                return;
            }

            $data = json_decode($raw, true);
            if (! is_array($data)) {
                return;
            }

            $this->record($data, ThemeInstanceRevision::TYPE_INSTALL, [
                'theme_identifier' => $identifier,
                'metadata' => ['backfilled' => true],
            ]);

            return;
        }

        $activeId = (int) Setting::get('active_theme_revision_id');
        if ($activeId === $latest->id) {
            return;
        }

        $pointerStillValid = $activeId && ThemeInstanceRevision::where('id', $activeId)
            ->where('theme_identifier', $identifier)
            ->exists();

        if (! $pointerStillValid) {
            Setting::set('active_theme_revision_id', $latest->id, 'theme');
        }
    }

    public function listForCurrentTheme(int $limit = 50): Collection
    {
        $this->ensureBackfilled();

        return ThemeInstanceRevision::where('theme_identifier', $this->currentIdentifier())
            ->orderByDesc('revision_number')
            ->limit($limit)
            ->get();
    }

    public function current(): ?ThemeInstanceRevision
    {
        $this->ensureBackfilled();

        $id = Setting::get('active_theme_revision_id');

        return $id ? ThemeInstanceRevision::find($id) : null;
    }

    /**
     * Restore a prior revision as the new live state. Never rewinds/deletes
     * history — creates a new rollback_snapshot revision carrying the
     * target's data forward, then activates that new revision. A second,
     * third, Nth rollback all work the same way, indefinitely.
     *
     * @throws NotFoundHttpException if the revision doesn't exist or
     *         belongs to a different theme lineage than the one currently
     *         active on this site (cross-lineage restore is refused).
     */
    public function rollbackTo(int $revisionId): ThemeInstanceRevision
    {
        try {
            $target = ThemeInstanceRevision::findOrFail($revisionId);
        } catch (ModelNotFoundException) {
            throw new NotFoundHttpException('نسخه مورد نظر یافت نشد.');
        }

        if ($target->theme_identifier !== $this->currentIdentifier()) {
            // 404, not 403: don't confirm to the caller that a revision ID
            // belonging to another lineage exists.
            throw new NotFoundHttpException('نسخه مورد نظر یافت نشد.');
        }

        // Phase 3D-B: resolve provenance from the TARGET being restored, not
        // from whatever was active before this rollback — restoring an
        // older release's state must resolve to THAT release, never
        // falsely inherit a newer one just because it was active a moment
        // ago (see ThemeInstanceBaseRelease's docblock).
        $restored = $this->record($target->theme_data, ThemeInstanceRevision::TYPE_ROLLBACK_SNAPSHOT, [
            'resolve_provenance_from' => $target->id,
            'metadata' => [
                'restored_from_revision_id' => $target->id,
                'restored_from_revision_number' => $target->revision_number,
            ],
        ]);

        Setting::set('canvas_draft_data', '', 'theme');

        return $restored;
    }

    /**
     * Phase 3D-F — the concurrency/idempotency guard a stale-fingerprint
     * check alone cannot provide: two Apply requests computed against the
     * exact same pre-apply state (same fingerprint, both genuinely valid
     * at the moment each was checked) must never both succeed and create
     * two upgrade revisions. A `lockForUpdate()` read of the active-pointer
     * Setting row inside this transaction serializes concurrent callers —
     * the second one blocks until the first commits, then sees the
     * ALREADY-ADVANCED pointer, no longer matches
     * $expectedActiveRevisionId, and is correctly told "stale" (returns
     * null) rather than writing a duplicate revision. record() itself
     * (called only once the lock has confirmed nothing changed) remains
     * the single, unmodified write boundary from Phase 3B.
     *
     * @return ThemeInstanceRevision|null null means the active revision
     *   was no longer $expectedActiveRevisionId at write time — nothing
     *   was written; the caller must treat this as a stale Apply.
     */
    public function recordUpgradeIfActiveRevisionUnchanged(int $expectedActiveRevisionId, array $themeData, array $context): ?ThemeInstanceRevision
    {
        return DB::transaction(function () use ($expectedActiveRevisionId, $themeData, $context) {
            $currentActiveId = (int) (
                Setting::where('key', 'active_theme_revision_id')->lockForUpdate()->value('value') ?? 0
            );

            if ($currentActiveId !== $expectedActiveRevisionId) {
                return null;
            }

            return $this->record($themeData, ThemeInstanceRevision::TYPE_UPGRADE_RECONCILED, $context);
        });
    }
}
