<?php

namespace Tests\Unit\Builder;

use App\Builder\Definition\ElementDefinitionException;
use App\Builder\Definition\ThemeBuilderControlDefinition;
use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\ElementRegistry;
use App\Builder\Elements\ButtonElement;
use App\Builder\Elements\ColumnElement;
use App\Builder\Elements\ColumnsElement;
use App\Builder\Elements\ContainerElement;
use App\Builder\Elements\DividerElement;
use App\Builder\Elements\GridElement;
use App\Builder\Elements\HeadingElement;
use App\Builder\Elements\HeroElement;
use App\Builder\Elements\ImageElement;
use App\Builder\Elements\RowElement;
use App\Builder\Elements\SectionElement;
use App\Builder\Elements\SpacerElement;
use App\Builder\Elements\TextElement;
use App\Builder\Rendering\ElementRenderer;
use App\Builder\Validation\ElementValidator;
use Tests\TestCase;

/**
 * Phase 3E-D — proves the new declarative ThemeBuilderElementDefinition/
 * ThemeBuilderControlDefinition contract formalizes the EXISTING Element SDK
 * without changing its behavior: same 13 persisted types, same rendering,
 * same validation semantics, same registration/collision rules from 3E-B.
 */
class ThemeBuilderElementDefinitionTest extends TestCase
{
    // A. valid element definition construction
    public function test_a_valid_element_definition_construction(): void
    {
        $def = ThemeBuilderElementDefinition::fromControls(
            type: 'test_widget',
            label: 'Test Widget',
            category: 'content',
            icon: '✓',
            controls: [
                ThemeBuilderControlDefinition::fromSchemaField('title', ['type' => 'string', 'default' => '']),
            ],
        );

        $this->assertSame('test_widget', $def->type);
        $this->assertArrayHasKey('title', $def->controls);
        $this->assertFalse($def->isContainer);
    }

    // B. invalid/duplicate control keys rejected
    public function test_b_duplicate_control_keys_rejected(): void
    {
        $this->expectException(ElementDefinitionException::class);
        ThemeBuilderElementDefinition::fromControls(
            type: 'dup_test',
            label: 'Dup',
            category: 'content',
            icon: '',
            controls: [
                ThemeBuilderControlDefinition::fromSchemaField('title', ['type' => 'string']),
                ThemeBuilderControlDefinition::fromSchemaField('title', ['type' => 'string']),
            ],
        );
    }

    // C. unknown control type rejected
    public function test_c_unknown_control_type_rejected(): void
    {
        $this->expectException(ElementDefinitionException::class);
        ThemeBuilderControlDefinition::fromSchemaField('x', ['type' => 'string', 'control' => 'nonsense_widget']);
    }

    // D. select options contract validated
    public function test_d_select_control_without_options_is_rejected(): void
    {
        $this->expectException(ElementDefinitionException::class);
        ThemeBuilderControlDefinition::fromSchemaField('style', ['type' => 'string', 'control' => 'select']);
    }

    public function test_d_select_control_with_options_is_accepted(): void
    {
        $control = ThemeBuilderControlDefinition::fromSchemaField('align', ['type' => 'enum', 'values' => ['left', 'right']]);
        $this->assertSame(ThemeBuilderControlDefinition::SELECT, $control->controlType);
        $this->assertSame(['left', 'right'], $control->options);
    }

    // E. defaults preserved
    public function test_e_defaults_preserved_from_schema_field(): void
    {
        $control = ThemeBuilderControlDefinition::fromSchemaField('text', ['type' => 'string', 'default' => 'دکمه']);
        $this->assertSame('دکمه', $control->default);
    }

    // F. validator consumes definition schema
    public function test_f_validator_consumes_definition_schema(): void
    {
        $def = ThemeBuilderElementDefinition::fromElementClass(ButtonElement::class);
        $result = ElementValidator::validateDefinition($def, ['text' => 'خرید', 'url' => 'https://example.com', 'style' => 'primary']);

        $this->assertSame('خرید', $result['props']['text']);
        $this->assertSame('https://example.com', $result['props']['url']);
        $this->assertEmpty($result['errors']);
    }

    // G. ordinary invalid values preserve existing coercion/default behavior
    public function test_g_invalid_enum_value_falls_back_to_default_not_an_exception(): void
    {
        $def = ThemeBuilderElementDefinition::fromElementClass(ButtonElement::class);
        $result = ElementValidator::validateDefinition($def, ['style' => 'not-a-real-style']);

        $this->assertSame('primary', $result['props']['style']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_g_direct_validate_still_never_throws_on_bad_input(): void
    {
        $result = ElementValidator::validate(['n' => ['type' => 'array']], ['n' => 'not-an-array']);
        $this->assertNotEmpty($result['errors']);
        $this->assertSame([], $result['props']['n']);
    }

    // H. is_container=false behavior
    public function test_h_non_container_element_definition_is_container_false(): void
    {
        $def = ThemeBuilderElementDefinition::fromElementClass(HeadingElement::class);
        $this->assertFalse($def->isContainer);
        $this->assertNull($def->allowedChildren);
    }

    // I. is_container=true behavior
    public function test_i_container_element_definition_is_container_true(): void
    {
        $def = ThemeBuilderElementDefinition::fromElementClass(SectionElement::class);
        $this->assertTrue($def->isContainer);
    }

    // J. allowed_children metadata preserved
    public function test_j_allowed_children_metadata_preserved_for_row(): void
    {
        $def = ThemeBuilderElementDefinition::fromElementClass(RowElement::class);
        $this->assertTrue($def->isContainer);
        $this->assertSame(['column'], $def->allowedChildren);
    }

    public function test_j_allowed_children_null_means_unrestricted_for_column(): void
    {
        $def = ThemeBuilderElementDefinition::fromElementClass(ColumnElement::class);
        $this->assertTrue($def->isContainer);
        $this->assertNull($def->allowedChildren);
    }

    // K. existing row/column/container/section/grid/columns recognized through
    // definitions, not a hardcoded list — this is the real 3E-A gap closed.
    public function test_k_all_six_known_container_elements_recognized_via_definitions(): void
    {
        foreach ([SectionElement::class, ContainerElement::class, GridElement::class, ColumnsElement::class, RowElement::class, ColumnElement::class] as $class) {
            $def = ThemeBuilderElementDefinition::fromElementClass($class);
            $this->assertTrue($def->isContainer, "{$class} should be recognized as a container via its definition.");
        }
    }

    // L. existing non-container element remains non-container
    public function test_l_non_container_elements_remain_non_container(): void
    {
        foreach ([HeadingElement::class, TextElement::class, ImageElement::class, ButtonElement::class, DividerElement::class, HeroElement::class, SpacerElement::class] as $class) {
            $def = ThemeBuilderElementDefinition::fromElementClass($class);
            $this->assertFalse($def->isContainer, "{$class} should NOT be recognized as a container.");
        }
    }

    // M. existing 13 persisted type strings unchanged
    public function test_m_all_13_persisted_type_strings_unchanged(): void
    {
        $expected = [
            'heading', 'text', 'image', 'button', 'divider', 'spacer',
            'section', 'row', 'column', 'container', 'grid', 'columns', 'hero',
        ];
        $actual = array_keys(ElementRegistry::definitions());
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }

    // N. canonical-delegating renderer behavior unchanged
    public function test_n_canonical_delegating_renderer_output_unchanged(): void
    {
        $html = (new ElementRenderer())->render('button', ['text' => 'x', 'url' => '#'], ['id' => 'b1']);
        $this->assertStringContainsString('tb-btn', $html);
        $this->assertStringContainsString('x', $html);
    }

    // O. host-only Spacer/Row/Column behavior unchanged
    public function test_o_host_only_row_and_column_render_unchanged(): void
    {
        $rowHtml = (new ElementRenderer())->render('row', ['gap' => 'md'], ['id' => 'r1', 'inner' => 'INNER']);
        $this->assertStringContainsString('tb-row', $rowHtml);
        $this->assertStringContainsString('INNER', $rowHtml);

        $colHtml = (new ElementRenderer())->render('column', ['width' => '1/2'], ['id' => 'c1', 'inner' => 'INNER']);
        $this->assertStringContainsString('tb-column', $colHtml);

        $spacerHtml = (new ElementRenderer())->render('spacer', [], ['id' => 's1']);
        $this->assertIsString($spacerHtml);
    }

    // P. unknown serialized block still round-trips without a definition
    public function test_p_unknown_type_has_no_definition_but_renderer_still_falls_back_safely(): void
    {
        $this->assertNull(ElementRegistry::definition('totally_unknown_type'));
        $html = (new ElementRenderer())->render('totally_unknown_type', ['x' => 1], ['id' => 'u1']);
        $this->assertStringContainsString('tb-placeholder', $html);
    }

    // Q. dynamic-data capability preserved
    public function test_q_dynamic_data_capability_preserved(): void
    {
        // None of the 13 current elements opt into dynamic data — definitions
        // must reflect that accurately (false), not assume it.
        $def = ThemeBuilderElementDefinition::fromElementClass(TextElement::class);
        $this->assertFalse($def->supportsDynamicData);
    }

    // R. asset declaration behavior preserved
    public function test_r_asset_declaration_behavior_preserved(): void
    {
        // None of the 13 current elements implement HasAssets — definitions
        // must reflect that accurately.
        $def = ThemeBuilderElementDefinition::fromElementClass(TextElement::class);
        $this->assertFalse($def->hasAssets);
    }

    // S. identity/collision contract from 3E-B still applies to the
    // registry the new definitions() method reads from.
    public function test_s_definitions_respect_the_3e_b_identity_contract(): void
    {
        $this->assertTrue(ElementRegistry::has('row'));
        $this->assertFalse(ElementRegistry::has('ext:acme:not_registered'));
        $this->assertNull(ElementRegistry::definition('ext:acme:not_registered'));
    }

    public function test_definitions_are_deterministic_across_calls(): void
    {
        $first = array_keys(ElementRegistry::definitions());
        $second = array_keys(ElementRegistry::definitions());
        $this->assertSame($first, $second);
    }

    public function test_to_legacy_schema_round_trips_validation_identically(): void
    {
        $def = ThemeBuilderElementDefinition::fromElementClass(HeroElement::class);
        $legacy = HeroElement::getSchema();

        $viaDefinition = ElementValidator::validateDefinition($def, ['title' => 'X', 'align' => 'left']);
        $viaLegacy = ElementValidator::validate($legacy, ['title' => 'X', 'align' => 'left']);

        $this->assertSame($viaLegacy['props'], $viaDefinition['props']);
    }
}
