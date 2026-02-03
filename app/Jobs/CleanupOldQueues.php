<?php

namespace App\Jobs;

use App\Models\QueueTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupOldQueues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $deletedCount = QueueTicket::whereDate('service_date', '<', today())->delete();

        Log::info("CleanupOldQueues: Deleted {$deletedCount} old queue tickets from previous days.");
    }
}
