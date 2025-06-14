<?php

namespace App\Console\Commands;

use App\Jobs\VerifyEmailJob;
use App\Models\AgentForm;
use App\Services\AgentFormService;
use Illuminate\Console\Command;

class TestAgentFormJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:agent-form-jobs {--count=1 : Number of forms to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AgentForm job processing by creating forms and dispatching jobs';

    /**
     * Execute the console command.
     */
    public function handle(AgentFormService $agentFormService): int
    {
        $count = (int) $this->option('count');

        $this->info("🚀 Creating {$count} AgentForm(s) and dispatching jobs...");

        // Show initial statistics
        $initialStats = $agentFormService->getStatistics();
        $this->info("📊 Initial Statistics:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Forms', $initialStats['total']],
                ['Verified', $initialStats['verified']],
                ['Emails Sent', $initialStats['email_sent']],
                ['Completed', $initialStats['completed']],
                ['Verification Rate', $initialStats['verification_rate'] . '%'],
                ['Completion Rate', $initialStats['completion_rate'] . '%'],
            ]
        );

        $createdForms = [];
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            try {
                // Create AgentForm with fake data
                $agentForm = AgentForm::create([
                    'name' => fake()->name(),
                    'email' => fake()->email(),
                    'secret' => fake()->password(10),
                ]);

                $createdForms[] = $agentForm;

                // Dispatch the verification job
                VerifyEmailJob::dispatch($agentForm);

                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("❌ Failed to create form {$i}: " . $e->getMessage());
                continue;
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show summary
        $this->info("✅ Successfully created {$count} AgentForm(s)");
        $this->info("📋 Created Form IDs: " . collect($createdForms)->pluck('id')->implode(', '));

        // Show updated statistics
        $finalStats = $agentFormService->getStatistics();
        $this->info("📊 Updated Statistics:");
        $this->table(
            ['Metric', 'Value', 'Change'],
            [
                ['Total Forms', $finalStats['total'], '+' . ($finalStats['total'] - $initialStats['total'])],
                ['Verified', $finalStats['verified'], '+' . ($finalStats['verified'] - $initialStats['verified'])],
                ['Emails Sent', $finalStats['email_sent'], '+' . ($finalStats['email_sent'] - $initialStats['email_sent'])],
                ['Completed', $finalStats['completed'], '+' . ($finalStats['completed'] - $initialStats['completed'])],
                ['Verification Rate', $finalStats['verification_rate'] . '%', ($finalStats['verification_rate'] - $initialStats['verification_rate']) . '%'],
                ['Completion Rate', $finalStats['completion_rate'] . '%', ($finalStats['completion_rate'] - $initialStats['completion_rate']) . '%'],
            ]
        );

        $this->info("🔄 Jobs have been dispatched to the queue. Monitor with:");
        $this->line("   php artisan queue:work --verbose");
        $this->line("   tail -f storage/logs/laravel.log");

        return Command::SUCCESS;
    }
}
