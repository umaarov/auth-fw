<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SessionToken;
use Carbon\Carbon;

class PruneExpiredSessions extends Command
{
    protected $signature = 'auth:prune-sessions {days=30 : Number of days of inactivity before pruning}';
    protected $description = 'Prune inactive sessions older than specified days';

    public function handle()
    {
        $days = $this->argument('days');
        $cutoff = Carbon::now()->subDays($days);

        $count = SessionToken::where('last_activity', '<', $cutoff)->delete();

        $this->info("Pruned {$count} expired sessions older than {$days} days.");

        return Command::SUCCESS;
    }
}
