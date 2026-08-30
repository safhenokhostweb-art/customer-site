<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\BlockRenderer;
use ReflectionClass;
use SaaSPlatform\ThemeRuntime\Attributes\HostOwnedOverride;
use Tests\TestCase;

/**
 * Phase 2i (ADR-029) — the durable enforcement Section 8 asked for: a
 * future developer adding an undocumented duplicate renderFoo() to
 * customer-site's BlockRenderer must get a failing test, not silence.
 *
 * Deliberately NOT a manually-maintained allowlist of method names — the
 * classification lives as a #[HostOwnedOverride] PHP attribute on the
 * method itself (self-documenting, co-located, can't drift out of sync
 * with the code it describes). This test only enforces that every
 * overlapping method carries one; it never hardcodes which methods are
 * "allowed" to overlap.
 */
class ThemeRuntimeCanonicalParityTest extends TestCase
{
    public function test_customer_site_never_redefines_a_canonical_shared_method_without_an_explicit_host_owned_reason(): void
    {
        $shared = new ReflectionClass(\SaaSPlatform\ThemeRuntime\BlockRenderer::class);
        $host = new ReflectionClass(BlockRenderer::class);

        $sharedOwnMethods = [];
        foreach ($shared->getMethods() as $m) {
            if ($m->getDeclaringClass()->getName() === $shared->getName()) {
                $sharedOwnMethods[$m->getName()] = true;
            }
        }

        $undocumented = [];
        foreach ($host->getMethods() as $m) {
            if ($m->getDeclaringClass()->getName() !== $host->getName()) {
                continue; // inherited, not redeclared — the whole point of this phase
            }
            if (!isset($sharedOwnMethods[$m->getName()])) {
                continue; // genuinely host-only, no canonical equivalent to diverge from
            }
            if (empty($m->getAttributes(HostOwnedOverride::class))) {
                $undocumented[] = $m->getName();
            }
        }

        $this->assertEmpty(
            $undocumented,
            'These methods redeclare a canonical shared BlockRenderer method without a '
            . '#[HostOwnedOverride] attribute explaining why: ' . implode(', ', $undocumented)
            . '. Either delete the duplicate (inherit the shared implementation) or add the attribute.'
        );
    }

    public function test_every_host_owned_override_declares_a_real_reason_string(): void
    {
        $host = new ReflectionClass(BlockRenderer::class);

        foreach ($host->getMethods() as $m) {
            if ($m->getDeclaringClass()->getName() !== $host->getName()) {
                continue;
            }
            foreach ($m->getAttributes(HostOwnedOverride::class) as $attr) {
                /** @var HostOwnedOverride $instance */
                $instance = $attr->newInstance();
                $this->assertNotSame('', trim($instance->reason), "HostOwnedOverride on {$m->getName()} has an empty reason.");
                $this->assertGreaterThan(20, strlen($instance->reason), "HostOwnedOverride reason on {$m->getName()} is too short to be a real justification.");
            }
        }
    }
}
