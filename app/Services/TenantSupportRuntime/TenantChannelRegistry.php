<?php

namespace App\Services\TenantSupportRuntime;

/**
 * Builds tenant-scoped Reverb channel names.
 *
 * All channels are prefixed with the tenant identifier so that:
 * - Customer A can never subscribe to Customer B channels.
 * - Platform staff cannot subscribe to tenant channels without explicit permission.
 * - No channel names ever overlap across tenants.
 */
class TenantChannelRegistry
{
    private string $tenantSlug;

    public function __construct()
    {
        // Tenant slug derived from config — never from user input
        $this->tenantSlug = (string) config('app.tenant_slug', 'tenant');
    }

    public function conversationChannel(string $conversationId): string
    {
        return "private-tenant.{$this->tenantSlug}.support.conversation.{$conversationId}";
    }

    public function presenceChannel(string $conversationId): string
    {
        return "presence-tenant.{$this->tenantSlug}.support.presence.{$conversationId}";
    }

    public function agentInboxChannel(int $agentId): string
    {
        return "private-tenant.{$this->tenantSlug}.support.agent.{$agentId}";
    }

    public function adminInboxChannel(): string
    {
        return "private-tenant.{$this->tenantSlug}.support.admin";
    }

    public function userChannel(string $userId): string
    {
        return "private-tenant.{$this->tenantSlug}.support.user.{$userId}";
    }

    /**
     * Verify a given channel name belongs to this tenant.
     * Used by middleware to block cross-tenant channel subscriptions.
     */
    public function belongsToThisTenant(string $channelName): bool
    {
        return str_starts_with($channelName, "private-tenant.{$this->tenantSlug}.")
            || str_starts_with($channelName, "presence-tenant.{$this->tenantSlug}.");
    }

    public function getTenantSlug(): string
    {
        return $this->tenantSlug;
    }
}
