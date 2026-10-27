<?php

namespace App\Console\Commands;

use App\Models\QrEvent;
use Illuminate\Console\Command;

class PruneQrEvents extends Command
{
    protected $signature = 'qr:prune-events {--days=}';

    protected $description = 'Delete old QR analytics events';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('qr.event_retention_days', 90));
        $count = QrEvent::where('created_at', '<', now()->subDays($days))->delete();
        $this->info("Pruned {$count} events.");

        return self::SUCCESS;
    }
}
