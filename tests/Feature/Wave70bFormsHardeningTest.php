<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase 2 / Wave 7.0b — Forms Hardening regression suite.
 *
 * Covers every write path on the forms module plus the new defensive
 * behaviors introduced in this wave:
 *   - intro_text is persisted as TinyMCE HTML and rendered with a strict
 *     allowlist via strip_tags() on both the public page and admin preview.
 *   - intro_text validation max bumped to 65,000 so realistic WYSIWYG
 *     output is no longer truncated by the legacy 5,000-char cap.
 *   - public POST /forms/{slug} is throttled to 10/min per identity+slug;
 *     captcha (when enabled) is the second wall of defence.
 *
 * The suite reuses `Tests\Concerns\SeedsValidLicenseWithShop` to make the
 * forms module enabled in the License row (host-aware seeding). It does
 * not touch any global setup; each test is self-contained.
 */
class Wave70bFormsHardeningTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
        $this->seedValidLicense(['forms']);
    }

    /** Helper: create a published, active form with a single required text field. */
    private function makeFormWithField(string $slug = 'contact-us'): Form
    {
        /** @var Form $form */
        $form = Form::query()->create([
            'title' => 'تماس با ما',
            'slug' => $slug,
            'is_active' => true,
            'intro_text' => '<p>پاسخ ظرف ۲۴ ساعت داده می‌شود.</p>',
        ]);
        FormField::query()->create([
            'form_id' => $form->id,
            'label' => 'نام و نام خانوادگی',
            'name' => 'full_name',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
        ]);

        return $form;
    }

    /**
     * Build a payload that matches what the real admin UI sends. Browsers
     * always include all <input>/<textarea> fields in a POST — empty ones
     * arrive as `""`, never absent. The legacy `Admin\FormController::store`
     * accesses several optional keys with `$data['key'] ?: null` (no `??`
     * fallback), so tests must seed every key the form has even when the
     * value is empty.
     */
    private function adminFormPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'فرم تست',
            'slug' => '',
            'is_active' => '1',
            'intro_text' => '',
            'meta_title' => '',
            'meta_description' => '',
            'canonical_url' => '',
            'meta_robots' => '',
        ], $overrides);
    }

    public function test_admin_can_create_form_with_html_intro_text(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $html = '<p>متن <strong>پررنگ</strong> با <a href="https://example.com">لینک</a>.</p>';

        $response = $this->actingAs($admin)->post(
            route('admin.forms.store'),
            $this->adminFormPayload([
                'slug' => 'fa-test-form',
                'intro_text' => $html,
            ])
        );

        $form = Form::query()->where('slug', 'fa-test-form')->first();
        $this->assertNotNull($form);
        $this->assertSame($html, $form->intro_text);
        $response->assertRedirect(route('admin.forms.edit', $form));
    }

    public function test_admin_form_index_renders_guide_partial(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.forms.index'));

        $response->assertOk();
        $response->assertSee('راهنمای ماژول فرم‌ساز');
        $response->assertSee('۱۴۰۵/۰۲/۲۳');
    }

    public function test_admin_can_update_form_settings_and_seo(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $form = $this->makeFormWithField('seo-form');

        $response = $this->actingAs($admin)->put(route('admin.forms.update', $form), [
            'title' => 'فرم به‌روزشده',
            'slug' => 'seo-form',
            'is_active' => '1',
            'meta_title' => 'عنوان متا تست',
            'meta_description' => 'توضیح متا تست',
            'canonical_url' => 'https://example.test/forms/seo-form',
            'meta_robots' => 'index,follow',
            'intro_text' => '<p>متن جدید</p>',
        ]);

        $response->assertRedirect();
        $form->refresh();
        $this->assertSame('فرم به‌روزشده', $form->title);
        $this->assertSame('عنوان متا تست', $form->meta_title);
        $this->assertSame('توضیح متا تست', $form->meta_description);
        $this->assertSame('https://example.test/forms/seo-form', $form->canonical_url);
        $this->assertSame('index,follow', $form->meta_robots);
    }

    /**
     * Mirror the real admin field form: every nullable text input is
     * submitted as an empty string by the browser, so tests must too.
     */
    private function adminFieldPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'فیلد تست',
            'name' => 'test_field',
            'type' => 'text',
            'placeholder' => '',
            'help_text' => '',
            'options_raw' => '',
        ], $overrides);
    }

    public function test_admin_can_add_text_field_to_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $form = Form::query()->create([
            'title' => 'فرم ساده',
            'slug' => 'simple-form',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.forms.fields.store', $form),
            $this->adminFieldPayload([
                'label' => 'ایمیل',
                'name' => 'email',
                'type' => 'email',
                'is_required' => '1',
            ])
        );

        $response->assertRedirect();
        $this->assertSame(1, $form->fields()->count());
        $field = $form->fields()->first();
        $this->assertSame('email', $field->name);
        $this->assertSame('email', $field->type);
        $this->assertTrue((bool) $field->is_required);
        $this->assertSame(1, (int) $field->position);
    }

    public function test_field_options_required_for_select_radio_checkbox(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $form = Form::query()->create([
            'title' => 'فرم انتخابی',
            'slug' => 'select-form',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.forms.fields.store', $form),
            $this->adminFieldPayload([
                'label' => 'موضوع',
                'name' => 'subject',
                'type' => 'select',
                // options_raw left empty deliberately
            ])
        );

        $response->assertSessionHasErrors(['options_raw']);
        $this->assertSame(0, $form->fields()->count());
    }

    public function test_admin_can_toggle_field_active_state(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $form = $this->makeFormWithField('toggle-form');
        $field = $form->fields()->first();
        $this->assertTrue((bool) $field->is_active);

        $this->actingAs($admin)->patch(route('admin.forms.fields.toggle', $field))->assertRedirect();
        $field->refresh();
        $this->assertFalse((bool) $field->is_active);

        $this->actingAs($admin)->patch(route('admin.forms.fields.toggle', $field))->assertRedirect();
        $field->refresh();
        $this->assertTrue((bool) $field->is_active);
    }

    public function test_admin_can_delete_form_and_cascade_clears_submissions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $form = $this->makeFormWithField('to-delete');
        $field = $form->fields()->first();
        FormSubmission::query()->create([
            'form_id' => $form->id,
            'data' => ['field_' . $field->id => 'محمد'],
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.forms.destroy', $form));

        $response->assertRedirect(route('admin.forms.index'));
        $this->assertNull(Form::query()->find($form->id));
        $this->assertSame(0, FormField::query()->where('form_id', $form->id)->count());
        $this->assertSame(0, FormSubmission::query()->where('form_id', $form->id)->count());
    }

    public function test_public_form_show_renders_intro_text_as_safe_html(): void
    {
        $form = Form::query()->create([
            'title' => 'فرم انتشار HTML',
            'slug' => 'html-render',
            'is_active' => true,
            'intro_text' => '<p><strong>راهنما</strong>: مدارک را آماده کنید.</p>'
                . '<script>alert(1)</script>'
                . '<p>قدم بعد را بزنید.</p>',
        ]);
        FormField::query()->create([
            'form_id' => $form->id,
            'label' => 'پیام',
            'name' => 'message',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
            'position' => 1,
        ]);

        $response = $this->get(route('forms.show', $form->slug));

        $response->assertOk();
        // Whitelisted tags survive.
        $response->assertSee('<strong>راهنما</strong>', false);
        $response->assertSee('<p>قدم بعد را بزنید.</p>', false);
        // The malicious wrapper is stripped. (`strip_tags` keeps text
        // *between* tags — so the literal string "alert(1)" remains as
        // harmless plain text. The XSS risk is the executable <script>
        // element, which must not survive.)
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_public_form_submit_persists_submission(): void
    {
        $form = $this->makeFormWithField('submit-ok');
        $field = $form->fields()->first();

        $response = $this->post(route('forms.submit', $form->slug), [
            'field_' . $field->id => 'علی موسوی',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->id,
        ]);
        $row = FormSubmission::query()->where('form_id', $form->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('علی موسوی', $row->data['field_' . $field->id] ?? null);
        $this->assertNotEmpty($row->ip);
    }

    public function test_public_form_submit_validates_required_fields(): void
    {
        $form = $this->makeFormWithField('submit-validate');

        $response = $this->post(route('forms.submit', $form->slug), [
            // Required field intentionally omitted.
        ]);

        $field = $form->fields()->first();
        $response->assertSessionHasErrors(['field_' . $field->id]);
        $this->assertSame(0, FormSubmission::query()->where('form_id', $form->id)->count());
    }

    public function test_public_form_submit_returns_404_for_inactive_form(): void
    {
        $form = Form::query()->create([
            'title' => 'فرم غیرفعال',
            'slug' => 'inactive-form',
            'is_active' => false,
        ]);
        FormField::query()->create([
            'form_id' => $form->id,
            'label' => 'پیام',
            'name' => 'msg',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
        ]);

        $this->post(route('forms.submit', $form->slug), [
            'field_1' => 'anything',
        ])->assertNotFound();

        $this->assertSame(0, FormSubmission::query()->where('form_id', $form->id)->count());
    }

    public function test_public_form_submit_is_throttled_after_ten_per_minute(): void
    {
        RateLimiter::clear('forms-submit');
        $form = $this->makeFormWithField('throttle-form');
        $field = $form->fields()->first();
        $payload = ['field_' . $field->id => 'پاسخ تست'];

        // First 10 submissions succeed (302 redirect after store).
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('forms.submit', $form->slug), $payload);
            $this->assertSame(302, $response->status(), "Request #{$i} should succeed");
        }

        // The 11th hits the limiter → 429.
        $response = $this->post(route('forms.submit', $form->slug), $payload);
        $this->assertSame(429, $response->status());
        $this->assertSame(10, FormSubmission::query()->where('form_id', $form->id)->count());
    }

    public function test_slug_collision_produces_unique_slug(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.forms.store'), $this->adminFormPayload(['title' => 'فرم همگن']))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.forms.store'), $this->adminFormPayload(['title' => 'فرم همگن']))
            ->assertRedirect();

        $slugs = Form::query()->where('title', 'فرم همگن')->pluck('slug');
        $this->assertCount(2, $slugs);
        $this->assertNotSame($slugs[0], $slugs[1]);
    }
}
