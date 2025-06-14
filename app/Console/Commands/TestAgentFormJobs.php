<?php

namespace App\Console\Commands;

use App\Models\AgentForm;
use App\Jobs\VerifyEmailJob;
use Illuminate\Console\Command;

class TestAgentFormJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:agent-form-jobs {--count=1 : Number of AgentForm records to create and process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the AgentForm verification and email jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');

        $this->info("Creating {$count} test AgentForm record(s)...");
        $this->newLine();

        $createdIds = [];

        for ($i = 1; $i <= $count; $i++) {
            $this->info("Creating AgentForm record {$i}/{$count}...");

            // Create a test AgentForm record
            $agentForm = AgentForm::create([
                'name' => "Test User {$i}",
                'email' => fake()->email(),
                'secret' => 'test-secret-' . now()->timestamp . '-' . $i,
            ]);

            $createdIds[] = $agentForm->id;
            $this->info("✓ Created AgentForm with ID: {$agentForm->id}");

            // Dispatch the verification job
            $this->info("→ Dispatching VerifyEmailJob to verification queue...");
            VerifyEmailJob::dispatch($agentForm)->onQueue('verification');

            $this->info("✓ Job dispatched for AgentForm ID: {$agentForm->id}");
            $this->newLine();
        }

        $this->info('🎉 Summary:');
        $this->info("• Created {$count} AgentForm record(s)");
        $this->info("• IDs: " . implode(', ', $createdIds));
        $this->info("• Dispatched {$count} VerifyEmailJob(s) to verification queue");
        $this->newLine();

        $this->info('📋 Next steps:');
        $this->info('• Jobs will fail ~66% of the time and retry up to 3 times each');
        $this->info('• Check logs with: tail -f storage/logs/laravel-2025-06-14.log');
        $this->info('• Process jobs with: php artisan queue:work database --queue=verification,email --tries=3');

        return Command::SUCCESS;
    }
}
