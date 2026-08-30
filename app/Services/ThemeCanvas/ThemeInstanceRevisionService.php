<?php

namespace App\Services\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Single write path for active_canvas_theme_data. Every call lands an
 * immutable row in theme_instance_revisions (never overwritten, never
 * deleted) before touching the live Setting, so every published/installed/
 * restored state stays permanently recoverable — unlike the legacy single
 * backup slot and 5-slot rotating window, which this service does not
 * replace but now backs with a permanent record of the same events.
 */
class ThemeInstanceRevisionService
{
    public const DEFAULT_IDENTIFIER = 'custom';

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
     * Record a new immutable revision, then point the live theme at it.
     * theme_data is the decoded (array) theme structure — same shape as
     * json_decode(Setting::get('active_canvas_theme_data'), true).
     */
    public function record(array $themeData, string $type, array $context = []): ThemeInstanceRevision
    {
        $identifier = $context['theme_identifier'] ?? $this->currentIdentifier();

        $nextNumber = (int) ThemeInstanceRevision::where('theme_identifier', $identifier)->max('revision_number') + 1;

        $revision = ThemeInstanceRevision::create([
            'theme_identifier' => $identifier,
            'source_theme_id' => array_key_exists('source_theme_id', $context)
                ? $context['source_theme_id']
                : Setting::get('active_canvas_theme_id'),
            // Ledger-authoritative release identity (Phase 1h) — never
            // fabricated: both stay null unless the caller actually has
            // real ThemeRelease data to propagate (see
            // ThemeMarketplaceController::install()).
            'source_theme_version' => $context['source_theme_version'] ?? null,
            'source_release_id' => $context['source_release_id'] ?? null,
            'revision_number' => $nextNumber,
            'type' => $type,
            'theme_data' => $themeData,
            'metadata' => array_merge(
                ['theme_name' => Setting::get('active_canvas_theme_name')],
                $context['metadata'] ?? []
            ),
            'created_by' => auth()->id(),
        ]);

        Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_theme_revision_id', $revision->id, 'theme');

        return $revision;
    }

    /**
     * Self-healing migration for sites that already had an
     * active_canvas_theme_data value before this feature existed: the very
     * first time this service is asked for history on such a site, it
     * backfills a single revision #1 (type=install, metadata.backfilled)
     * wrapping the existing live data, so history starts continuous instead
     * of empty. Idempotent — does nothing once any revision exists for the
     * current lineage.
     */
    public function ensureBackfilled(): void
    {
        $identifier = $this->currentIdentifier();

        if (ThemeInstanceRevision::where('theme_identifier', $identifier)->exists()) {
            return;
        }

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

        $restored = $this->record($target->theme_data, ThemeInstanceRevision::TYPE_ROLLBACK_SNAPSHOT, [
            'metadata' => [
                'restored_from_revision_id' => $target->id,
                'restored_from_revision_number' => $target->revision_number,
            ],
        ]);

        Setting::set('canvas_draft_data', '', 'theme');

        return $restored;
    }
}
