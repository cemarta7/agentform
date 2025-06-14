<?php

namespace App\Jobs;

use App\Models\AgentForm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AgentForm $agentForm
    ) {
        $this->onQueue('email');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting welcome email sending for AgentForm ID: {$this->agentForm->id} (Attempt: {$this->attempts()})");

        // Simulate email sending process (e.g., API call to email service)
        sleep(2);

        // Simulate failure 2 out of 3 times
        $shouldFail = rand(1, 3) <= 2; // 66% chance of failure

        if ($shouldFail && $this->attempts() < $this->tries) {
            Log::warning("Welcome email sending failed for AgentForm ID: {$this->agentForm->id} (Attempt: {$this->attempts()}) - Simulated failure, will retry");
            throw new \Exception("Simulated email service failure - attempt {$this->attempts()}");
        }

        // Simulate email sending success (either random success or final attempt)
        Log::info("Welcome email sent successfully for AgentForm ID: {$this->agentForm->id} (Attempt: {$this->attempts()})");

        // Update the email_sent_at timestamp
        $this->agentForm->update([
            'email_sent_at' => now()
        ]);

        Log::info("Email process completed for AgentForm ID: {$this->agentForm->id} - Email: {$this->agentForm->email}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendWelcomeEmailJob permanently failed for AgentForm ID: {$this->agentForm->id} after {$this->tries} attempts. Error: {$exception->getMessage()}");
    }
}
