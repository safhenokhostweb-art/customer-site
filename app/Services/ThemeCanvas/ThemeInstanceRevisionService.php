<?php

namespace App\Services\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
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

    public function __construct(private ThemeInstanceBaseRelease $baseRelease)
    {
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
     */
    public function record(array $themeData, string $type, array $context = []): ThemeInstanceRevision
    {
        $identifier = $context['theme_identifier'] ?? $this->currentIdentifier();

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

            return $revision;
        });
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
}
