<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TestRetryJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $testId
    ) {
        $this->onQueue('verification');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $currentAttempt = $this->attempts();

        Log::info("🔄 TestRetryJob starting - Test ID: {$this->testId} (Attempt: {$currentAttempt}/{$this->tries})");

        // Fail on attempts 1 and 2, succeed on attempt 3
        if ($currentAttempt < 3) {
            Log::warning("❌ TestRetryJob FAILING - Test ID: {$this->testId} (Attempt: {$currentAttempt}) - Will retry");
            throw new \Exception("Intentional failure for retry test - attempt {$currentAttempt}");
        }

        // Success on attempt 3
        Log::info("✅ TestRetryJob SUCCESS - Test ID: {$this->testId} (Attempt: {$currentAttempt}) - Job completed successfully!");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("💀 TestRetryJob PERMANENTLY FAILED - Test ID: {$this->testId} after {$this->tries} attempts. Error: {$exception->getMessage()}");
    }
}
