<?php

namespace App\Services\ThemeCanvas;

use App\Helpers\JsonLd;

/**
 * Builds the public Canvas page's structured-data graph list.
 *
 * SCOPE (SEO audit C-1), deliberately narrow:
 *   - WebPage  — always. Every field comes from SeoService::canvasPage(),
 *                i.e. real resolved metadata, never invented.
 *   - FAQPage  — only when a real `faq` block on the page carries at least
 *                one question/answer pair where BOTH sides are non-empty.
 *
 * DELIBERATELY NOT EMITTED, and why (do not "fix" these by guessing):
 *   - Product / ItemList — the Canvas data-binding resolver hands the
 *     renderer only ['title','url','image','image_alt','price_html','excerpt']
 *     (see ThemeRuntimeDocument::prepareRenderer()). `price_html` is
 *     formatted HTML, not a number, and there is no availability/sku/gtin,
 *     so an Offer would be fabricated. JsonLd::product() is also pinned to
 *     route('shop.show') and IRR, which is wrong for an arbitrary Canvas
 *     listing page.
 *   - Review / AggregateRating — no real rating aggregate is available in
 *     this context. Never guessed.
 *   - BreadcrumbList — the installed breadcrumb block
 *     (theme-runtime BlockRenderer::renderBreadcrumb) explicitly refuses to
 *     fabricate a trail: without an injected breadcrumb_html it renders only
 *     "home". The Canvas page array carries no page hierarchy, so any trail
 *     would be made up.
 *   - Article / LocalBusiness — covered by the legacy theme pages and the
 *     (currently shadowed) seo-advanced module respectively; not this path.
 *
 * ANTI-DUPLICATION: this service only ever returns PAGE-level types
 * (WebPage, FAQPage). The site-level Organization + WebSite pair continues
 * to come solely from <x-seo-meta>, so no second copy is ever emitted.
 *
 * UNTRUSTED INPUT: block trees are author-supplied JSON. Every read is
 * type-guarded, non-string values are ignored, strings are trimmed, and the
 * final JSON is emitted through Blade's @json() — never string-concatenated
 * into a <script> block.
 */
class CanvasStructuredData
{
    /**
     * Block types whose props can contribute FAQ entities, with the prop key
     * holding the repeater. 'faq' is the installed canonical type
     * (see ThemeCanvas\BlockRegistry::faq — items[{question, answer}]).
     * Phase 3 (saas-platform ADR-068): module:faq_dynamic carries the same
     * items; only the `limit` it shows are published (structured data must
     * match visible content).
     */
    private const FAQ_BLOCK_TYPES = ['faq', 'module:faq_dynamic'];

    /**
     * @param  array<int,mixed>  $blocks  the page's block tree
     * @param  array<string,mixed>  $seoMeta  SeoService::canvasPage() output
     * @return array<int,array<string,mixed>> JSON-LD graphs, in a stable order
     */
    public function for(array $blocks, array $seoMeta, ?string $documentLang = null): array
    {
        $graphs = [];

        $webPage = $this->webPage($seoMeta, $documentLang);
        if ($webPage !== []) {
            $graphs[] = $webPage;
        }

        $faqPage = $this->faqPage($blocks);
        if ($faqPage !== []) {
            $graphs[] = $faqPage;
        }

        return $graphs;
    }

    /**
     * Reuses the helper's existing webPage() shape; only inLanguage is added
     * here, and only from a language the document actually resolved to — an
     * absent/blank value omits the field rather than defaulting to a guess.
     *
     * @param  array<string,mixed>  $seoMeta
     * @return array<string,mixed>
     */
    private function webPage(array $seoMeta, ?string $documentLang): array
    {
        $name = trim((string) ($seoMeta['title'] ?? ''));
        $url  = trim((string) ($seoMeta['canonical'] ?? ''));
        if ($name === '' || $url === '') {
            return [];
        }

        $graph = JsonLd::webPage($name, $url, trim((string) ($seoMeta['description'] ?? '')));

        $lang = trim((string) $documentLang);
        if ($lang !== '' && preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})?$/', $lang) === 1) {
            $graph['inLanguage'] = $lang;
        }

        return $graph;
    }

    /**
     * Collects FAQ items from the page's own blocks, then hands them to
     * JsonLd::faqPage() — which is what decides validity. This method only
     * locates and normalizes.
     *
     * @param  array<int,mixed>  $blocks
     * @return array<string,mixed>
     */
    private function faqPage(array $blocks): array
    {
        $items = [];
        $this->collectFaqItems($blocks, $items);

        return JsonLd::faqPage($items);
    }

    /**
     * Depth-first walk of the block tree. Only the exact installed shape is
     * read: a block whose 'type' is a known FAQ type, and whose 'items' prop
     * is either an array or a JSON string (the installed renderer tolerates
     * both, so this must too).
     *
     * @param  array<int,mixed>  $blocks
     * @param  array<int,mixed>  $items
     */
    private function collectFaqItems(array $blocks, array &$items): void
    {
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;
            if (is_string($type) && in_array($type, self::FAQ_BLOCK_TYPES, true)) {
                $props = $block['props'] ?? $block['data'] ?? [];
                if (is_array($props)) {
                    $own = $this->normalizeItems($props['items'] ?? null);
                    if ($type === 'module:faq_dynamic') {
                        $own = array_slice($own, 0, max(1, (int) ($props['limit'] ?? 10)));
                    }
                    foreach ($own as $item) {
                        // The visible accordion also reads title/content as aliases.
                        if (is_array($item) && !isset($item['question']) && isset($item['title'])) {
                            $item['question'] = $item['title'];
                        }
                        if (is_array($item) && !isset($item['answer']) && isset($item['content'])) {
                            $item['answer'] = $item['content'];
                        }
                        $items[] = $item;
                    }
                }
            }

            $children = $block['children'] ?? $block['blocks'] ?? null;
            if (is_array($children)) {
                $this->collectFaqItems($children, $items);
            }
        }
    }

    /**
     * @return array<int,mixed>
     */
    private function normalizeItems(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        // A repeater is a list; reject a keyed map so no arbitrary prop bag
        // can be mistaken for FAQ content.
        if ($raw !== [] && array_keys($raw) !== range(0, count($raw) - 1)) {
            return [];
        }

        return $raw;
    }
}
