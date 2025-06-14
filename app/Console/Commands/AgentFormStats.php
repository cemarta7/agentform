<?php

namespace App\Console\Commands;

use App\Services\AgentFormService;
use Illuminate\Console\Command;

class AgentFormStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent-form:stats {--refresh : Refresh the display every 5 seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display AgentForm processing statistics';

    /**
     * Execute the console command.
     */
    public function handle(AgentFormService $agentFormService): int
    {
        $refresh = $this->option('refresh');

        do {
            if ($refresh) {
                // Clear the screen for refresh mode
                system('clear');
            }

            $stats = $agentFormService->getStatistics();

            $this->info("📊 AgentForm Processing Statistics");
            $this->info("Generated at: " . now()->format('Y-m-d H:i:s'));
            $this->newLine();

            $this->table(
                ['Metric', 'Count', 'Percentage'],
                [
                    ['Total Forms', $stats['total'], '100%'],
                    ['Email Verified', $stats['verified'], $stats['verification_rate'] . '%'],
                    ['Welcome Email Sent', $stats['email_sent'], ($stats['total'] > 0 ? round(($stats['email_sent'] / $stats['total']) * 100, 2) : 0) . '%'],
                    ['Fully Completed', $stats['completed'], $stats['completion_rate'] . '%'],
                ]
            );

            $this->newLine();

            // Show processing efficiency
            if ($stats['total'] > 0) {
                $pending = $stats['total'] - $stats['completed'];
                $this->info("🔄 Processing Status:");
                $this->line("   • {$stats['completed']} forms fully processed");
                $this->line("   • {$pending} forms still pending");

                if ($stats['verified'] > $stats['email_sent']) {
                    $emailPending = $stats['verified'] - $stats['email_sent'];
                    $this->line("   • {$emailPending} forms waiting for welcome email");
                }
            } else {
                $this->info("ℹ️  No AgentForm records found. Create some with:");
                $this->line("   php artisan test:agent-form-jobs --count=5");
            }

            if ($refresh) {
                $this->newLine();
                $this->info("🔄 Refreshing every 5 seconds... (Press Ctrl+C to stop)");
                sleep(5);
            }

        } while ($refresh);

        return Command::SUCCESS;
    }
}
