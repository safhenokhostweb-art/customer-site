<?php

namespace Tests\Unit;

use App\Services\Seo\HeaderIntegrityService;
use Tests\TestCase;

/**
 * Phase 4.1 — the detector's own contract, with no rendering involved.
 */
class HeaderIntegrityServiceTest extends TestCase
{
    private function service(): HeaderIntegrityService
    {
        return new HeaderIntegrityService();
    }

    public function test_content_with_no_heading_is_reported_as_missing(): void
    {
        $report = $this->service()->inspect('<div class="tb-section"><p>سلام</p></div>');

        $this->assertFalse($report['has_h1']);
        $this->assertSame(0, $report['h1_count']);
        $this->assertFalse($report['is_valid']);
        $this->assertSame(HeaderIntegrityService::VIOLATION_MISSING, $report['violation']);
    }

    public function test_content_with_exactly_one_h1_is_valid(): void
    {
        $report = $this->service()->inspect('<h1>عنوان صفحه</h1><h2>زیرعنوان</h2>');

        $this->assertTrue($report['has_h1']);
        $this->assertSame(1, $report['h1_count']);
        $this->assertTrue($report['is_valid']);
        $this->assertSame(HeaderIntegrityService::VIOLATION_NONE, $report['violation']);
    }

    public function test_content_with_two_h1_tags_is_reported_as_multiple(): void
    {
        $report = $this->service()->inspect('<h1>اول</h1><h1>دوم</h1>');

        $this->assertTrue($report['has_h1']);
        $this->assertSame(2, $report['h1_count']);
        $this->assertFalse($report['is_valid']);
        $this->assertSame(HeaderIntegrityService::VIOLATION_MULTIPLE, $report['violation']);
    }

    public function test_it_counts_nested_h1_tags(): void
    {
        $report = $this->service()->inspect(
            '<section><div><h1>یک</h1></div><div><h1>دو</h1><span><h1>سه</h1></span></div></section>'
        );

        $this->assertSame(3, $report['h1_count']);
    }

    public function test_it_matches_uppercase_and_attributed_h1_tags(): void
    {
        $report = $this->service()->inspect('<H1 class="a">یک</H1><h1 id="b" data-x=\'1\'>دو</h1>');

        $this->assertSame(2, $report['h1_count'], 'HTML parsing is case-insensitive and attribute-tolerant');
    }

    /**
     * The reason this uses a real parser rather than substr_count(): a naive
     * count would report violations for markup that contains no H1 element.
     */
    public function test_it_does_not_count_h1_inside_script_style_comment_or_text(): void
    {
        $report = $this->service()->inspect(<<<'HTML'
            <div>
                <script>var s = "<h1>not real</h1>";</script>
                <style>/* <h1>also not real</h1> */</style>
                <!-- <h1>a comment</h1> -->
                <p>Use the &lt;h1&gt; tag for a page title</p>
                <span data-example="<h1>">attribute</span>
            </div>
            HTML);

        $this->assertSame(0, $report['h1_count'], 'only real elements count');
        $this->assertTrue($report['is_valid'] === false);
        $this->assertSame(HeaderIntegrityService::VIOLATION_MISSING, $report['violation']);
    }

    public function test_an_h1_after_a_decoy_still_counts_exactly_once(): void
    {
        $report = $this->service()->inspect(
            '<script>var t = "<h1>decoy</h1>";</script><h1>real</h1>'
        );

        $this->assertSame(1, $report['h1_count'], 'the decoy must not inflate the count');
        $this->assertTrue($report['is_valid']);
    }

    public function test_empty_and_whitespace_input_are_handled_as_missing(): void
    {
        foreach (['', '   ', "\n\t "] as $html) {
            $report = $this->service()->inspect($html);

            $this->assertSame(0, $report['h1_count']);
            $this->assertSame(HeaderIntegrityService::VIOLATION_MISSING, $report['violation']);
        }
    }

    public function test_malformed_html_does_not_throw(): void
    {
        $report = $this->service()->inspect('<div><h1>unclosed <span><<<> &&& ');

        $this->assertIsInt($report['h1_count']);
        $this->assertArrayHasKey('is_valid', $report);
    }

    public function test_persian_content_is_parsed_without_mangling_the_document(): void
    {
        $report = $this->service()->inspect('<h1>عنوان فارسی</h1><h1>عنوان دوم</h1>');

        $this->assertSame(2, $report['h1_count'], 'UTF-8 bytes must still yield real elements');
    }
}
