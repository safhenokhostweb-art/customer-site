<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature   = 'push:vapid-keys';
    protected $description = 'Generate VAPID keys for Web Push Notifications and add to .env';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('VAPID Keys generated successfully.');
        $this->line('');
        $this->line('Add the following to your <comment>.env</comment> file:');
        $this->line('');
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('');

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);

            if (!str_contains($env, 'VAPID_PUBLIC_KEY=')) {
                file_put_contents($envPath, $env . "\nVAPID_PUBLIC_KEY=" . $keys['publicKey'] . "\nVAPID_PRIVATE_KEY=" . $keys['privateKey'] . "\n");
                $this->info('.env file updated automatically.');
            } else {
                $this->warn('VAPID_PUBLIC_KEY already exists in .env — update manually if needed.');
            }
        }

        return self::SUCCESS;
    }
}
