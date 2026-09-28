<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Page;
use App\Services\ThemeCanvas\ThemeRuntimeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresInstallationLock;
use Tests\TestCase;

class SmartPublicRenderingTest extends TestCase
{
    use EnsuresInstallationLock, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        $this->ensureInstallationLock();
    }

    protected function tearDown(): void
    {
        // Ownership-aware: this class used to unlink the lock unconditionally,
        // which silently broke every class scheduled after it by forcing a 302
        // from RedirectIfNotInstalled. The trait only removes a lock this
        // process created, and only once nothing else is holding it.
        $this->releaseInstallationLock();
        parent::tearDown();
    }

    public function test_builder_page_exposes_public_identity_and_runtime_without_editor_capabilities(): void
    {
        $page = Page::create([
            'title' => 'Smart public page',
            'slug' => 'smart-public-page',
            'body' => '',
            'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'builder_content_v2' => ['blocks' => [[
                'id' => 'public-section',
                'type' => 'section',
                'props' => [],
                'children' => [[
                    'id' => 'public-heading',
                    'type' => 'heading',
                    'props' => ['text' => 'Public runtime heading', 'level' => 2],
                ]],
            ]]],
            'is_published' => true,
            'show_in_menu' => false,
        ]);

        $document = app(ThemeRuntimeDocument::class)
            ->forMode(ThemeRuntimeDocument::MODE_PUBLIC)
            ->buildForPage($page);
        $this->assertArrayHasKey('runtime', $document['capabilityProjection']);
        $this->assertArrayNotHasKey('editor', $document['capabilityProjection']);

        $response = $this->get(route('page.show', $page->slug))->assertOk();

        $response->assertSee('data-tb-block-id="public-section"', false);
        $response->assertSee('data-tb-container="1"', false);
        $response->assertSee('data-tb-block-id="public-heading"', false);
        $response->assertSee('data-tb-block-type="heading"', false);
        $response->assertSee('window.ThemeCanvasRuntime = Object.freeze', false);
        $response->assertSee('root.CanvasIdentity = contract', false);
        $response->assertSee("window.dispatchEvent(new CustomEvent('theme:interaction'", false);
        $response->assertDontSee('window.CanvasEditor', false);
        $response->assertDontSee('data-tb-edit-action=', false);
        $response->assertDontSee('data-tb-control-id=', false);
        $response->assertDontSee('data-tb-editable=', false);
        $response->assertSee('<meta name="description" content="Public runtime heading">', false);
    }
}
