<?php

namespace App\Jobs;

use App\Models\AgentForm;
use App\Services\AgentFormService;
use App\Services\UtilService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public $timeout = 300;

    public $backoff = [10, 30, 60]; // 10s, 30s, 60s delays between retries

    protected AgentForm $agentForm;

    /**
     * Create a new job instance.
     */
    public function __construct(AgentForm $agentForm)
    {
        $this->agentForm = $agentForm;
        $this->onQueue('verification');
    }

    /**
     * Execute the job.
     */
    public function handle(AgentFormService $agentFormService): void
    {
        $attempt = $this->attempts();

        Log::info("🚀 VerifyEmailJob: Starting verification for AgentForm ID: {$this->agentForm->id} (Attempt: {$attempt})");

        try {
            // Use the service to verify email
            $agentFormService->verifyEmail($this->agentForm, $attempt);

            // If verification successful, dispatch the welcome email job
            Log::info("📤 VerifyEmailJob: Dispatching SendWelcomeEmailJob for AgentForm ID: {$this->agentForm->id}");
            SendWelcomeEmailJob::dispatch($this->agentForm);

        } catch (\Exception $e) {
            Log::error("❌ VerifyEmailJob: Failed for AgentForm ID: {$this->agentForm->id} (Attempt: {$attempt}) - {$e->getMessage()}");
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("💀 VerifyEmailJob: Final failure for AgentForm ID: {$this->agentForm->id} after {$this->tries} attempts - {$exception->getMessage()}");
    }
}
