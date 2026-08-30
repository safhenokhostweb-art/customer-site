<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Preview Renderer + Screenshot Pipeline Hardening audit: saas-platform's
 * admin preview already rendered hero_split, features_grid, process_steps,
 * team_cards, skills_bars, testimonials_slider, pricing_cards,
 * newsletter_form, bento_grid, services_cards and contact_split correctly,
 * but customer-site — the app that actually renders installed themes for
 * real, paying customers — had neither a render method NOR a schema entry
 * for any of them. ElementSchemaRegistry::validate() rejected them as
 * "Unknown element type" before renderBlockUnsafe() was ever reached, and
 * the resulting renderPlaceholder()/renderFallback() output meant these
 * sections were silently missing from nearly every catalog theme's live
 * site. This exercises the full renderBlock() path (schema validation
 * included, not just the render method in isolation) to prove the fix.
 */
class PremiumBlockRuntimeParityTest extends TestCase
{
    protected BlockRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new BlockRenderer();
        $this->renderer->setSchemaRegistry(new ElementSchemaRegistry());
    }

    public static function premiumBlockProvider(): array
    {
        return [
            'hero_split' => ['hero_split', ['title' => 'عنوان اصلی', 'subtitle' => 'زیرعنوان'], 'tb-hero-split', 'عنوان اصلی'],
            'hero_gradient' => ['hero_gradient', ['title' => 'قدرت آینده'], 'tb-hero-gradient', 'قدرت آینده'],
            'features_grid' => ['features_grid', ['title' => 'ویژگی‌ها', 'features' => '[{"icon":"⚡","title":"سریع","text":"متن"}]'], 'tb-features-grid', 'سریع'],
            'process_steps' => ['process_steps', ['title' => 'مراحل', 'steps' => '[{"number":"۱","title":"شروع","text":"متن"}]'], 'tb-process-steps', 'شروع'],
            'team_cards' => ['team_cards', ['title' => 'تیم ما', 'members' => '[{"name":"محمد رستمی","role":"مشاور"}]'], 'tb-team-member', 'محمد رستمی'],
            'skills_bars' => ['skills_bars', ['title' => 'مهارت‌ها', 'skills' => '[{"name":"طراحی","percent":90}]'], 'tb-skills', 'طراحی'],
            'testimonials_slider' => ['testimonials_slider', ['title' => 'نظرات', 'items' => '[{"name":"رضا","text":"عالی بود"}]'], 'tb-testimonials', 'عالی بود'],
            'pricing_cards' => ['pricing_cards', ['title' => 'پلن‌ها', 'plans' => '[{"name":"پایه","price":"۱۰۰"}]'], 'tb-pricing-cards', 'پایه'],
            'newsletter_form' => ['newsletter_form', ['title' => 'خبرنامه'], 'tb-newsletter', 'خبرنامه'],
            'bento_grid' => ['bento_grid', ['title' => 'همه چیز', 'items' => '[{"title":"مدیریت","text":"متن"}]'], 'tb-bento', 'مدیریت'],
            'services_cards' => ['services_cards', ['title' => 'خدمات ما', 'services' => '[{"title":"طراحی","text":"متن"}]'], 'tb-services-grid', 'خدمات ما'],
            'contact_split' => ['contact_split', ['title' => 'تماس', 'phone' => '۰۲۱-۱۲۳۴۵۶۷۸'], 'tb-contact-split', '۰۲۱-۱۲۳۴۵۶۷۸'],
        ];
    }

    #[DataProvider('premiumBlockProvider')]
    public function test_premium_block_renders_real_html_through_the_full_validated_pipeline(
        string $type,
        array $props,
        string $expectedClass,
        string $expectedContent
    ): void {
        $html = $this->renderer->renderBlock(['id' => 'blk_' . $type, 'type' => $type, 'props' => $props]);

        $this->assertStringNotContainsString('tb-placeholder', $html, "{$type} was rejected by schema validation instead of rendering");
        $this->assertStringContainsString($expectedClass, $html);
        $this->assertStringContainsString($expectedContent, $html);
    }

    public function test_team_renders_an_honest_placeholder_not_nothing_and_not_raw_json(): void
    {
        $html = $this->renderer->renderBlock(['id' => 'blk_team', 'type' => 'team', 'props' => ['title' => 'پزشکان ما', 'limit' => 6]]);

        $this->assertNotSame('', trim($html));
        $this->assertStringNotContainsString('tb-placeholder', $html);
        $this->assertStringNotContainsString('{"title"', $html);
        $this->assertStringContainsString('پزشکان ما', $html);
    }
}
