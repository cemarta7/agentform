<?php

namespace App\Jobs;

use App\Models\AgentForm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class VerifyEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AgentForm $agentForm
    ) {
        $this->onQueue('verification');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting email verification for AgentForm ID: {$this->agentForm->id} (Attempt: {$this->attempts()})");

        // Simulate verification process (e.g., API call to verification service)
        sleep(2);

        // Simulate failure 2 out of 3 times
        $shouldFail = rand(1, 3) <= 2; // 66% chance of failure

        if ($shouldFail && $this->attempts() < $this->tries) {
            Log::warning("Email verification failed for AgentForm ID: {$this->agentForm->id} (Attempt: {$this->attempts()}) - Simulated failure, will retry");
            throw new \Exception("Simulated verification service failure - attempt {$this->attempts()}");
        }

        // Simulate verification success (either random success or final attempt)
        Log::info("Email verification successful for AgentForm ID: {$this->agentForm->id} (Attempt: {$this->attempts()})");

        // Update the email_verified_at timestamp
        $this->agentForm->update([
            'email_verified_at' => now()
        ]);

        // Dispatch the welcome email job
        SendWelcomeEmailJob::dispatch($this->agentForm)->onQueue('email');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("VerifyEmailJob permanently failed for AgentForm ID: {$this->agentForm->id} after {$this->tries} attempts. Error: {$exception->getMessage()}");
    }
}
