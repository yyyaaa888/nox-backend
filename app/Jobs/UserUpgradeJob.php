<?php

namespace App\Jobs;

use App\Services\UserUpgradeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserUpgradeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;

    public function __construct(int $uid)
    {
        $this->uid = $uid;
    }

    public function handle()
    {
        $service = new UserUpgradeService(true);
        try {
            $service->run($this->uid);
        } catch (\Exception $e) {
            Log::error('User upgrade failed', [
                'uid'   => $this->uid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
