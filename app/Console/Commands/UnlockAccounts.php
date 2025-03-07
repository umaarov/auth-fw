<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class UnlockAccounts extends Command
{
    protected $signature = 'auth:unlock-accounts';
    protected $description = 'Unlock accounts that have passed their lockout period';

    public function handle()
    {
        $count = User::where('is_locked', true)
            ->where('locked_until', '<', Carbon::now())
            ->update([
                'is_locked' => false,
                'locked_until' => null,
                'failed_login_attempts' => 0
            ]);

        $this->info("Unlocked {$count} accounts.");

        return Command::SUCCESS;
    }
}
