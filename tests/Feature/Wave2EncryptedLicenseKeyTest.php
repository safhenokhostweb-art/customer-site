<?php

namespace Tests\Feature;

use App\Casts\EncryptedOrPlaintext;
use App\Models\License;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Wave 5.4 — Regression tests for Wave 2 / P0-4 (encrypted license_key cast).
 *
 * Behaviour under test:
 *   - Saving a License with a plaintext license_key writes an ENCRYPTED
 *     value to the underlying DB column.
 *   - Reading the model decrypts it back transparently.
 *   - A legacy plaintext row that was inserted before the cast existed is
 *     still readable (decrypt fails → fall back to the raw value).
 *   - On the next save, the legacy row is lazily upgraded to encrypted
 *     storage without any backfill migration.
 *   - Null / empty values are passed through unchanged in both directions.
 */
class Wave2EncryptedLicenseKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_license_persists_encrypted_value_in_db(): void
    {
        $plain = 'AAAA-BBBB-CCCC-DDDD';

        $license = License::create([
            'license_key' => $plain,
            'domain' => 'p04.test',
            'expires_at' => null,
            'modules' => [],
        ]);

        $raw = DB::table('licenses')->where('id', $license->id)->value('license_key');

        $this->assertNotSame($plain, $raw, 'plaintext key must NOT be stored verbatim');
        $this->assertSame($plain, Crypt::decryptString($raw));
    }

    public function test_eloquent_returns_decrypted_value_for_freshly_saved_license(): void
    {
        $plain = 'EEEE-FFFF-GGGG-HHHH';

        $license = License::create([
            'license_key' => $plain,
            'domain' => 'p04-decrypt.test',
        ]);

        $reloaded = License::find($license->id);
        $this->assertSame($plain, $reloaded->license_key);
    }

    public function test_legacy_plaintext_row_is_readable_via_cast(): void
    {
        $plain = 'LEGA-CYPL-AINX-XXXX';

        // Bypass the model cast: write the legacy plaintext value directly.
        $id = DB::table('licenses')->insertGetId([
            'license_key' => $plain,
            'domain' => 'p04-legacy.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = License::find($id);
        $this->assertSame($plain, $row->license_key, 'legacy plaintext row must be readable as-is');
    }

    public function test_legacy_plaintext_row_is_upgraded_to_encrypted_on_next_save(): void
    {
        $plain = 'LEGA-UPGR-ADEX-XXXX';

        $id = DB::table('licenses')->insertGetId([
            'license_key' => $plain,
            'domain' => 'p04-upgrade.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = License::find($id);

        // Touch and save: the cast should re-encrypt the value when set() runs.
        $row->license_key = $plain; // explicit reassignment triggers the cast
        $row->save();

        $raw = DB::table('licenses')->where('id', $id)->value('license_key');
        $this->assertNotSame($plain, $raw, 'after save the DB value must be encrypted, not plaintext');
        $this->assertSame($plain, Crypt::decryptString($raw));

        // Reload and verify the model still reads the original plaintext.
        $reloaded = License::find($id);
        $this->assertSame($plain, $reloaded->license_key);
    }

    public function test_null_and_empty_values_are_passed_through(): void
    {
        $cast = new EncryptedOrPlaintext();
        $model = new License();

        $this->assertNull($cast->set($model, 'license_key', null, []));
        $this->assertSame('', $cast->set($model, 'license_key', '', []));

        $this->assertNull($cast->get($model, 'license_key', null, []));
        $this->assertSame('', $cast->get($model, 'license_key', '', []));
    }

    public function test_maskedKey_works_on_decrypted_value(): void
    {
        $plain = 'MASK-FORD-ISPL-AYXX';
        $license = License::create([
            'license_key' => $plain,
            'domain' => 'p04-mask.test',
        ]);
        $reloaded = License::find($license->id);
        $masked = $reloaded->maskedKey();
        $this->assertSame('MASK-****-****-AYXX', $masked);
    }
}
