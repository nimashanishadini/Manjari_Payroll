<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BiometricBridgeToken extends Command
{
    protected $signature = 'biometric:bridge-token {device : Registered device ID} {--days=90 : Token lifetime in days (1-365)} {--revoke : Revoke without issuing a replacement}';

    protected $description = 'Issue, rotate or revoke the enrollment bridge token for one device';

    public function handle(): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);
        if (! $this->option('revoke') && ($days === false || $days < 1 || $days > 365)) {
            $this->error('Days must be an integer between 1 and 365.');
            return self::FAILURE;
        }

        return DB::transaction(function () use ($days) {
            $device = BiometricDevice::whereKey($this->argument('device'))->lockForUpdate()->first();
            if (! $device) {
                $this->error('Registered device not found.');
                return self::FAILURE;
            }
            if ($this->option('revoke')) {
                $device->forceFill(['bridge_token_hash' => null, 'bridge_token_expires_at' => null])->save();
                $this->info('Bridge token revoked.');
                return self::SUCCESS;
            }
            if (! $device->is_active) {
                $this->error('Enable the device before issuing a token.');
                return self::FAILURE;
            }
            $token = bin2hex(random_bytes(32));
            $expires = now()->addDays($days);
            $device->forceFill(['bridge_token_hash' => hash('sha256', $token), 'bridge_token_expires_at' => $expires])->save();
            $this->info('Previous token invalidated. Store this token securely; it is displayed only now.');
            $this->line($token);
            $this->info('Expires at '.$expires->toIso8601String());
            return self::SUCCESS;
        });
    }
}
