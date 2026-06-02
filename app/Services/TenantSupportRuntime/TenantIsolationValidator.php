<?php

namespace App\Services\TenantSupportRuntime;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Validates DOMAIN A — Tenant Support Runtime isolation.
 * Used by: support:validate-tenant-isolation artisan command.
 */
class TenantIsolationValidator
{
    private array $results = [];
    private int   $score   = 0;

    public function validate(): array
    {
        $this->checkSupportTablesExist();
        $this->checkNoCrossPlatformForeignKeys();
        $this->checkChannelNamingConvention();
        $this->checkAttachmentsStoredLocally();
        $this->checkNoOutboundReplication();
        $this->checkLicenseValidationActive();

        return [
            'score'   => $this->score,
            'results' => $this->results,
            'passed'  => $this->score >= 95,
        ];
    }

    private function checkSupportTablesExist(): void
    {
        $required = [
            'support_conversations',
            'support_conversation_participants',
            'support_messages',
            'support_message_attachments',
            'support_presence',
            'support_read_receipts',
        ];

        $missing = [];
        foreach ($required as $table) {
            if (!Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        if (empty($missing)) {
            $this->pass('support_tables_exist', 'All 6 support tables exist in tenant database.');
        } else {
            $this->fail('support_tables_exist', 'Missing tables: ' . implode(', ', $missing));
        }
    }

    private function checkNoCrossPlatformForeignKeys(): void
    {
        // Support tables must NOT reference platform tables (mobile_conversations, users from platform, etc.)
        $platformTables = [
            'mobile_conversations',
            'mobile_conversation_participants',
            'mobile_conversation_messages',
            'sites',
            'resellers',
            'platform_conversations',
        ];

        $violations = [];

        // Check support_conversations columns for any stored platform IDs pattern
        if (Schema::hasTable('support_conversations')) {
            $cols = Schema::getColumnListing('support_conversations');
            foreach ($cols as $col) {
                foreach ($platformTables as $pt) {
                    if (str_contains($col, rtrim($pt, 's') . '_id') || str_contains($col, 'platform_')) {
                        $violations[] = "support_conversations.{$col} may reference platform table {$pt}";
                    }
                }
            }
        }

        if (empty($violations)) {
            $this->pass('no_cross_platform_fk', 'No cross-platform foreign key references detected.');
        } else {
            $this->fail('no_cross_platform_fk', implode('; ', $violations));
        }
    }

    private function checkChannelNamingConvention(): void
    {
        $tenantSlug = config('app.tenant_slug', '');

        if (empty($tenantSlug)) {
            $this->fail('channel_naming', 'app.tenant_slug not configured — channels cannot be tenant-scoped.');
            return;
        }

        $registry = new TenantChannelRegistry();
        $sample   = $registry->conversationChannel('test-id');

        if (str_contains($sample, "tenant.{$tenantSlug}")) {
            $this->pass('channel_naming', "Channels correctly scoped to tenant: {$tenantSlug}");
        } else {
            $this->fail('channel_naming', "Channel naming does not include tenant slug: {$sample}");
        }
    }

    private function checkAttachmentsStoredLocally(): void
    {
        $disk = config('filesystems.support_disk', 'local');
        $allowed = ['local', 'private'];

        if (in_array($disk, $allowed, true)) {
            $this->pass('attachments_local', "Support attachments stored on local disk: {$disk}");
        } else {
            // Still pass if disk is configured but it's an S3-like disk isolated per tenant
            // Flag it as a warning that should be reviewed
            $this->partial('attachments_local',
                "Support attachment disk is '{$disk}' — verify it is tenant-isolated and not shared with platform.",
                5
            );
        }
    }

    private function checkNoOutboundReplication(): void
    {
        // Check that no queue jobs exist that would replicate support messages to platform
        $suspiciousClasses = [
            'SyncToPlatform',
            'ReplicateSupportMessage',
            'SendConversationToPlatform',
            'ForwardToSaasPlatform',
        ];

        $violations = [];
        foreach ($suspiciousClasses as $class) {
            if (class_exists("App\\Jobs\\{$class}") || class_exists("App\\Listeners\\{$class}")) {
                $violations[] = $class;
            }
        }

        if (empty($violations)) {
            $this->pass('no_outbound_replication', 'No outbound replication classes detected.');
        } else {
            $this->fail('no_outbound_replication', 'Suspected replication classes: ' . implode(', ', $violations));
        }
    }

    private function checkLicenseValidationActive(): void
    {
        $gateClass = \App\Services\SupportModuleLicenseGate::class;

        if (class_exists($gateClass)) {
            /** @var \App\Services\SupportModuleLicenseGate $gate */
            $gate = app($gateClass);
            $active = $gate->isModuleEnabled();

            $this->pass(
                'license_validation_active',
                'SupportModuleLicenseGate loaded. Module enabled: ' . ($active ? 'YES' : 'NO (inactive license)')
            );
        } else {
            $this->fail('license_validation_active', 'SupportModuleLicenseGate class not found.');
        }
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function pass(string $key, string $message): void
    {
        $this->results[$key] = ['status' => 'pass', 'message' => $message];
        $this->score += 100 / 6; // 6 checks, equal weight
    }

    private function fail(string $key, string $message): void
    {
        $this->results[$key] = ['status' => 'fail', 'message' => $message];
    }

    private function partial(string $key, string $message, int $partialScore): void
    {
        $this->results[$key] = ['status' => 'warn', 'message' => $message];
        $this->score += $partialScore;
    }
}
