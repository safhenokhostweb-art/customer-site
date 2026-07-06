<?php

namespace Database\Seeders;

use App\Models\License;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $adminMobile   = env('ADMIN_MOBILE') ?: env('ADMIN_EMAIL');
        $adminName     = env('ADMIN_NAME', 'Admin');
        $adminPassword = env('ADMIN_PASSWORD', Str::random(16));

        if (empty($adminMobile)) {
            $this->command->warn('ADMIN_MOBILE not set in .env — skipping admin user creation.');
            return;
        }

        $existing = User::where('mobile', $adminMobile)->first();

        if ($existing) {
            $this->command->info("Admin user {$adminMobile} already exists.");
            return;
        }

        $user = new User();
        $user->name                  = $adminName;
        $user->mobile                = $adminMobile;
        $user->password              = Hash::make($adminPassword);
        $user->is_admin              = true;
        $user->force_password_change = true;
        $user->save();

        try {
            $adminRole = \App\Models\Role::where('slug', 'tenant-admin')->first();
            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }
        } catch (\Throwable) {}

        $this->command->info("Admin user created: {$adminMobile}");

        // Seed the license record so EnsureLicenseValid middleware passes immediately.
        // LICENSE_KEY is written to .env by ProvisionLicenseStage during CIE pipeline.
        $this->seedLicense();
    }

    private function seedLicense(): void
    {
        $licenseKey = env('LICENSE_KEY');
        if (empty($licenseKey)) {
            $this->command->warn('LICENSE_KEY not set in .env — skipping license seeding.');
            return;
        }

        if (License::where('license_key', $licenseKey)->exists()) {
            $this->command->info('License already seeded.');
            return;
        }

        $appUrl = env('APP_URL', '');
        $domain = '';
        if ($appUrl) {
            $parsed = parse_url($appUrl);
            $domain = $parsed['host'] ?? $appUrl;
        }

        License::create([
            'license_key'    => $licenseKey,
            'domain'         => $domain,
            'platform_valid' => true,
        ]);

        $this->command->info("License seeded: {$licenseKey}");
    }
}
