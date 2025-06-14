<?php

namespace App\Console\Commands;

use App\Models\AgentForm;
use App\Jobs\VerifyEmailJob;
use App\Jobs\TestRetryJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestJobRetries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:job-retries {--count=1 : Number of test records to create} {--simple : Use simple TestRetryJob instead of AgentForm flow}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test job retry mechanism with simulated failures';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');
        $useSimple = $this->option('simple');

        if ($useSimple) {
            $this->info("Testing simple retry job with {$count} job(s)...");
            $this->info('Jobs will fail on attempts 1 & 2, succeed on attempt 3.');
            $this->newLine();

            for ($i = 1; $i <= $count; $i++) {
                $testId = 'TEST-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $this->info("→ Dispatching TestRetryJob with ID: {$testId}");
                TestRetryJob::dispatch($testId)->onQueue('verification');
                $this->info("✓ Job dispatched!");
            }

            $this->newLine();
            $this->info('🔄 Jobs dispatched! Expected behavior:');
            $this->info('• Attempt 1: FAIL (will retry)');
            $this->info('• Attempt 2: FAIL (will retry)');
            $this->info('• Attempt 3: SUCCESS');
            $this->info('• Check logs with: tail -f storage/logs/laravel.log');

            return Command::SUCCESS;
        }

        $this->info("Testing job retry mechanism with {$count} test record(s)...");
        $this->info('Jobs will fail ~66% of the time and retry up to 3 times each.');
        $this->newLine();

        for ($i = 1; $i <= $count; $i++) {
            $this->info("Creating test AgentForm record {$i}/{$count}...");

            // Create a test AgentForm record
            $agentForm = AgentForm::create([
                'name' => "Retry Test User {$i}",
                'email' => fake()->email(),
                'secret' => 'retry-test-' . now()->timestamp . '-' . $i,
            ]);

            $this->info("✓ Created AgentForm ID: {$agentForm->id}");

            // Dispatch the verification job
            $this->info("→ Dispatching VerifyEmailJob to verification queue...");
            VerifyEmailJob::dispatch($agentForm)->onQueue('verification');

            $this->info("✓ Job dispatched for AgentForm ID: {$agentForm->id}");
            $this->newLine();
        }

        $this->info('🔄 All jobs dispatched! Expected behavior:');
        $this->info('• Each VerifyEmailJob will attempt up to 3 times');
        $this->info('• Each SendWelcomeEmailJob will attempt up to 3 times');
        $this->info('• ~66% chance of failure per attempt');
        $this->info('• Check logs with: tail -f storage/logs/laravel.log');
        $this->newLine();

        $this->info('💡 To monitor job execution:');
        $this->info('• With sync queue: Jobs execute immediately');
        $this->info('• With database queue: Run "php artisan queue:work --tries=3"');
        $this->info('• With Horizon: Jobs will appear in dashboard');

        return Command::SUCCESS;
    }
}
