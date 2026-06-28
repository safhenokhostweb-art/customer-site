<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail    = env('ADMIN_EMAIL');
        $adminName     = env('ADMIN_NAME', 'Admin');
        $adminPassword = env('ADMIN_PASSWORD', Str::random(16));

        if (empty($adminEmail)) {
            $this->command->warn('ADMIN_EMAIL not set in .env — skipping admin user creation.');
            return;
        }

        if (User::where('email', $adminEmail)->exists()) {
            $this->command->info("Admin user {$adminEmail} already exists.");
            return;
        }

        $user = new User();
        $user->name                = $adminName;
        $user->email               = $adminEmail;
        $user->password            = Hash::make($adminPassword);
        $user->is_admin            = true;
        $user->email_verified_at   = now();
        $user->save();

        // Assign tenant-admin role if the roles table exists
        try {
            $adminRole = \App\Models\Role::where('slug', 'tenant-admin')->first();
            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }
        } catch (\Throwable) {}

        $this->command->info("Admin user created: {$adminEmail}");
    }
}
