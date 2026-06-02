<?php

namespace App\Console\Commands;

use App\Models\SupportConversation;
use App\Services\TicketConversationBridge;
use Illuminate\Console\Command;
use SaaSPlatform\Modules\Tickets\Models\Ticket;
use SaaSPlatform\Modules\Tickets\Models\TicketReply;

/**
 * One-time backfill: copies all existing user tickets and their replies
 * into support_conversations / support_messages.
 *
 * Safe to run multiple times — tickets that already have a linked
 * SupportConversation are skipped automatically.
 *
 * Usage:
 *   php artisan support:backfill-tickets
 *   php artisan support:backfill-tickets --dry-run
 */
class BackfillTicketConversations extends Command
{
    protected $signature = 'support:backfill-tickets
                            {--dry-run : Preview what would be processed without writing anything}';

    protected $description = 'Backfill existing tickets/replies into support_conversations/support_messages';

    public function __construct(private TicketConversationBridge $bridge)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No data will be written.');
        }

        // ── 1. Collect already-synced ticket IDs ───────────────────────────────
        $syncedIds = SupportConversation::whereNotNull('metadata')
            ->get(['metadata'])
            ->map(fn($c) => (int) ($c->metadata['ticket_id'] ?? 0))
            ->filter()
            ->all();

        // ── 2. Find unsynced user-facing tickets ───────────────────────────────
        $tickets = Ticket::withoutTenantIsolation()
            ->whereNull('site_id')
            ->whereNotIn('id', $syncedIds ?: [0])
            ->orderBy('id')
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('Nothing to backfill — all tickets are already synced.');
            return self::SUCCESS;
        }

        $this->info("Tickets to backfill: {$tickets->count()}");

        if ($dryRun) {
            $this->table(
                ['ID', 'Number', 'Subject', 'Status', 'Customer ID'],
                $tickets->map(fn($t) => [
                    $t->id, $t->ticket_number, mb_strimwidth($t->subject, 0, 50, '…'),
                    $t->status, $t->customer_id ?? '—',
                ])->toArray()
            );
            return self::SUCCESS;
        }

        // ── 3. Sync each ticket ────────────────────────────────────────────────
        $bar    = $this->output->createProgressBar($tickets->count());
        $bar->start();

        $synced = 0;
        $errors = 0;

        foreach ($tickets as $ticket) {
            try {
                // Determine original creator context
                if ($ticket->customer_id) {
                    $creatorId   = (string) $ticket->customer_id;
                    $creatorType = 'user';
                } else {
                    // Admin-only ticket (no linked user)
                    $creatorId   = '0';
                    $creatorType = 'admin';
                }

                $this->bridge->syncNewTicket(
                    ticket: $ticket,
                    creatorId: $creatorId,
                    creatorType: $creatorType,
                );

                // Verify the conversation was created
                $conversation = SupportConversation::whereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.ticket_id')) = ?",
                    [$ticket->id]
                )->whereNull('deleted_at')->first();

                if (!$conversation) {
                    $errors++;
                    $bar->advance();
                    continue;
                }

                // ── 3a. Sync all replies ───────────────────────────────────────
                $replies = TicketReply::where('ticket_id', $ticket->id)
                    ->orderBy('created_at')
                    ->get();

                foreach ($replies as $reply) {
                    $senderType = $reply->author_type === 'staff' ? 'admin' : 'user';
                    $senderId   = $senderType === 'admin'
                        ? (string) ($reply->user_id ?? 0)
                        : (string) ($ticket->customer_id ?? 0);

                    $this->bridge->syncReply(
                        reply: $reply,
                        ticket: $ticket,
                        senderId: $senderId,
                        senderType: $senderType,
                        attachments: is_array($reply->attachments) ? $reply->attachments : [],
                    );
                }

                // ── 3b. Mirror closed status ───────────────────────────────────
                if ($ticket->status === 'closed') {
                    $conversation->update([
                        'status'    => SupportConversation::STATUS_CLOSED,
                        'closed_at' => $ticket->closed_at ?? now(),
                    ]);
                } elseif ($ticket->status === 'in_progress' && $ticket->assigned_to) {
                    $conversation->update([
                        'status'            => SupportConversation::STATUS_ASSIGNED,
                        'assigned_agent_id' => $ticket->assigned_to,
                    ]);
                }

                $synced++;
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->error("Ticket #{$ticket->id} failed: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Completed — synced: {$synced}, errors: {$errors}");

        if ($errors > 0) {
            $this->warn('Some tickets failed to sync. Check laravel.log for details.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
