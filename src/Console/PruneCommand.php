<?php

namespace IndieSystems\Notifications\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneCommand extends Command
{
    protected $signature = 'indie-notifications:prune
        {--days=30 : Delete read notifications older than this many days}
        {--all : Delete ALL notifications (read and unread) older than --days}';

    protected $description = 'Delete old read notifications to keep the table clean';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $query = DB::table('notifications')
            ->where('created_at', '<', $cutoff);

        if (!$this->option('all')) {
            $query->whereNotNull('read_at');
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No notifications to prune.');
            return 0;
        }

        $type = $this->option('all') ? 'all' : 'read';
        $this->info("Deleting {$count} {$type} notifications older than {$days} days...");

        $query->delete();

        $this->info("Pruned {$count} notifications.");

        return 0;
    }
}
