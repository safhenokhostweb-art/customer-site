<?php

namespace Tests\Unit\ThemeCanvas;

use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

/**
 * Work unit A (2026-10-30), host side of saas-platform's
 * tests/Unit/ThemeBuilder/LinkFieldUnsafeSchemeSweepTest: the same
 * registry-wide sweep, but through this host's registry and the real
 * storefront renderer (ThemeCanvasRenderer, which also runs the schema
 * validation of the live render path). Before the fix, hero_split/
 * hero_gradient `button2_url` and hero_trust `btn_*_url` printed a
 * `javascript:` href here, and every link field accepted `java<TAB>script:`.
 */
class LinkFieldUnsafeSchemeSweepTest extends TestCase
{
    private const UNSAFE = [
        'javascript:alert(1)',
        'JaVaScRiPt:alert(1)',
        "  javascript:alert(1)",
        "\x01javascript:alert(1)",
        "java\tscript:alert(1)",
        "java\nscript:alert(1)",
        "jav\rascript:alert(1)",
        'vbscript:msgbox(1)',
        'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
    ];

    private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'poster', 'srcset', 'cite', 'background', 'data', 'xlink:href'];

    /** @return list<array{type:string, path:list<string>, storage:?string}> */
    private function targets(): array
    {
        $targets = [];
        foreach (BlockRegistry::all() as $type => $definition) {
            foreach ((array) ($definition['schema'] ?? []) as $key => $field) {
                $this->collect($targets, $type, [(string) $key], $field, null);
            }
        }
        return $targets;
    }

    private function collect(array &$targets, string $type, array $path, mixed $field, ?string $storage): void
    {
        if (!is_array($field)) {
            return;
        }
        $fieldType = $field['type'] ?? '';
        if ($fieldType === 'repeater' && is_array($field['fields'] ?? null)) {
            foreach ($field['fields'] as $sub => $subField) {
                $this->collect($targets, $type, [...$path, (string) $sub], $subField, $storage ?? ($field['storage'] ?? 'array'));
            }
            return;
        }
        $key = end($path);
        if ($fieldType === 'link' || (in_array($fieldType, ['string', 'text'], true) && preg_match('/(^|_)(url|link|href)$/i', $key))) {
            $targets[] = ['type' => $type, 'path' => $path, 'storage' => $storage];
        }
    }

    /** Default props plus labels for empty text fields, with the value at the target path. */
    private function propsWith(array $target, string $value): array
    {
        $props = BlockRegistry::defaultProps($target['type']);
        foreach ((array) (BlockRegistry::get($target['type'])['schema'] ?? []) as $key => $field) {
            if (is_array($field) && in_array($field['type'] ?? '', ['string', 'text', 'textarea'], true)
                && !preg_match('/(^|_)(url|link|href)$/i', (string) $key) && ($props[$key] ?? '') === '') {
                $props[$key] = 'متن نمونه';
            }
        }
        $path = $target['path'];
        if (count($path) === 1) {
            $props[$path[0]] = $value;
            return $props;
        }

        $listKey = array_shift($path);
        $items = $props[$listKey] ?? [];
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }
        $items = is_array($items) ? array_values($items) : [];
        $items[0] = $this->setNested(is_array($items[0] ?? null) ? $items[0] : [], $path, $value);
        $props[$listKey] = $target['storage'] === 'json' ? json_encode($items, JSON_UNESCAPED_UNICODE) : $items;
        return $props;
    }

    private function setNested(array $item, array $path, string $value): array
    {
        $key = array_shift($path);
        if ($path === []) {
            $item[$key] = $value;
            return $item;
        }
        $children = is_array($item[$key] ?? null) ? array_values($item[$key]) : [];
        $children[0] = $this->setNested(is_array($children[0] ?? null) ? $children[0] : [], $path, $value);
        $item[$key] = $children;
        return $item;
    }

    private function render(array $target, string $value): string
    {
        return app(ThemeCanvasRenderer::class)->renderBlock(['type' => $target['type'], 'id' => 'sweep', 'props' => $this->propsWith($target, $value)]);
    }

    /** @return list<string> attribute descriptions carrying an executable scheme */
    private function unsafeAttributes(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $found = [];
        foreach ((new DOMXPath($dom))->query('//@*') as $attr) {
            $name = strtolower($attr->nodeName);
            $isUrlBearing = in_array($name, self::URL_ATTRIBUTES, true)
                || str_starts_with($name, 'on')
                || $name === 'style'
                || preg_match('/(href|url|src)$/', $name);
            if (!$isUrlBearing) {
                continue;
            }
            $normalized = strtolower(preg_replace('/[\x00-\x20]+/', '', $attr->nodeValue));
            if (str_contains($normalized, 'javascript:') || str_contains($normalized, 'vbscript:') || str_contains($normalized, 'data:text/html')) {
                $found[] = $attr->parentNode->nodeName . '[' . $name . ']=' . json_encode($attr->nodeValue);
            }
        }
        return $found;
    }

    public function test_the_sweep_covers_the_hero_button_fields(): void
    {
        $targets = $this->targets();
        $this->assertContains(['type' => 'hero_split', 'path' => ['button2_url'], 'storage' => null], $targets);
        $this->assertContains(['type' => 'hero_gradient', 'path' => ['button2_url'], 'storage' => null], $targets);
        $this->assertContains(['type' => 'hero_trust', 'path' => ['btn_primary_url'], 'storage' => null], $targets);
        $this->assertContains(['type' => 'hero_trust', 'path' => ['btn_secondary_url'], 'storage' => null], $targets);
    }

    public function test_a_safe_url_reaches_the_storefront_output_so_the_sweep_is_not_vacuous(): void
    {
        $reached = 0;
        foreach ($this->targets() as $i => $target) {
            $probe = "https://probe.example/{$i}";
            if (str_contains($this->render($target, $probe), $probe)) {
                $reached++;
            }
        }
        $this->assertGreaterThanOrEqual(intdiv(count($this->targets()), 2), $reached, "only {$reached} targets printed a safe probe URL");
    }

    public function test_no_link_field_renders_an_executable_scheme_on_the_storefront(): void
    {
        $failures = [];
        foreach ($this->targets() as $target) {
            foreach (self::UNSAFE as $payload) {
                foreach ($this->unsafeAttributes($this->render($target, $payload)) as $hit) {
                    $failures[] = $target['type'] . '.' . implode('.', $target['path']) . ' ← ' . json_encode($payload) . ' → ' . $hit;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($failures)));
    }
}
