<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permanent, immutable version history for this site's installed/customized
 * theme instance — replaces the single-backup-slot install() rollback and
 * the 5-slot rotating canvas_revision_N Settings keys as the source of
 * truth for recoverability. Those legacy mechanisms are left in place for
 * backward compatibility; every write path they cover now also lands a
 * permanent row here. Mirrors saas-platform's theme_revisions pattern
 * (immutable snapshot + monotonic version number).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_instance_revisions', function (Blueprint $table) {
            $table->id();

            // Groups revisions by theme lineage (the installed marketplace
            // theme's slug, or 'custom' when no marketplace theme is active)
            // so switching to an unrelated theme starts a fresh, separately
            // numbered history without ever deleting the previous lineage.
            $table->string('theme_identifier', 191)->index();

            $table->unsignedBigInteger('source_theme_id')->nullable();
            $table->unsignedInteger('source_theme_version')->nullable();

            // Sequential within a theme_identifier lineage: 1, 2, 3, ...
            $table->unsignedInteger('revision_number');

            // install | customer_edit | publish | update | rollback_snapshot
            $table->string('type', 30);

            $table->json('theme_data');
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['theme_identifier', 'revision_number']);
            $table->index(['theme_identifier', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_instance_revisions');
    }
};
