<?php

namespace App\Jobs;

use App\Models\AgentForm;
use App\Services\AgentFormService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    public $backoff = [5, 15, 30]; // 5s, 15s, 30s delays between retries

    protected AgentForm $agentForm;

    /**
     * Create a new job instance.
     */
    public function __construct(AgentForm $agentForm)
    {
        $this->agentForm = $agentForm;
        $this->onQueue('email');
        $this->delay(now()->addSeconds(2));
    }

    /**
     * Execute the job.
     */
    public function handle(AgentFormService $agentFormService): void
    {
        $attempt = $this->attempts();

        Log::info("🚀 SendWelcomeEmailJob: Starting email sending for AgentForm ID: {$this->agentForm->id} (Attempt: {$attempt})");

        try {
            // Use the service to send welcome email
            $agentFormService->sendWelcomeEmail($this->agentForm, $attempt);

        } catch (\Exception $e) {
            Log::error("❌ SendWelcomeEmailJob: Failed for AgentForm ID: {$this->agentForm->id} (Attempt: {$attempt}) - {$e->getMessage()}");
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("💀 SendWelcomeEmailJob: Final failure for AgentForm ID: {$this->agentForm->id} after {$this->tries} attempts - {$exception->getMessage()}");
    }
}
