<?php

namespace App\Console\Commands;

use App\Services\HorizonMetricsService;
use Illuminate\Console\Command;

class HorizonMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:metrics {--refresh : Refresh the display every 10 seconds} {--store : Store trend data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display Horizon metrics for AgentForm processing';

    /**
     * Execute the console command.
     */
    public function handle(HorizonMetricsService $metricsService): int
    {
        $refresh = $this->option('refresh');
        $store = $this->option('store');

        if ($store) {
            $metricsService->storeTrendData();
            $this->info('✅ Trend data stored successfully');
            return Command::SUCCESS;
        }

        do {
            if ($refresh) {
                system('clear');
            }

            $this->displayMetrics($metricsService);

            if ($refresh) {
                $this->newLine();
                $this->info('🔄 Refreshing every 10 seconds... (Press Ctrl+C to stop)');
                sleep(10);
            }

        } while ($refresh);

        return Command::SUCCESS;
    }

    /**
     * Display the metrics dashboard
     */
    private function displayMetrics(HorizonMetricsService $metricsService): void
    {
        $dashboard = $metricsService->getDashboardMetrics();
        $basic = $dashboard['basic_metrics'];
        $waitTimes = $dashboard['queue_wait_times'];
        $throughput = $dashboard['throughput'];

        $this->info('📊 Horizon AgentForm Metrics Dashboard');
        $this->info('Generated at: ' . now()->format('Y-m-d H:i:s'));
        $this->info('Queue Connection: ' . $dashboard['queue_connection']);
        $this->info('Horizon Available: ' . ($dashboard['horizon_available'] ? '✅ Yes' : '❌ No'));
        $this->newLine();

        // Basic Metrics
        $this->info('🔢 Basic Metrics');
        $this->table(
            ['Metric', 'Value', 'Description'],
            [
                ['Verification Queue Size', $basic['verification_queue_size'], 'Jobs waiting in verification queue'],
                ['Email Queue Size', $basic['email_queue_size'], 'Jobs waiting in email queue'],
                ['Forms Pending', $basic['forms_pending'], 'Forms not fully processed'],
                ['Failed Jobs', $basic['failed_jobs_count'], 'Total failed AgentForm jobs'],
                ['Completed (Last Hour)', $basic['forms_completed_last_hour'], 'Forms completed in last hour'],
            ]
        );

        $this->newLine();

        // Success Rates
        $this->info('📈 Success Rates (Last 24 Hours)');
        $this->table(
            ['Process', 'Success Rate', 'Status'],
            [
                [
                    'Email Verification',
                    $basic['verification_success_rate'] . '%',
                    $this->getStatusIcon($basic['verification_success_rate'])
                ],
                [
                    'Welcome Email',
                    $basic['email_success_rate'] . '%',
                    $this->getStatusIcon($basic['email_success_rate'])
                ],
            ]
        );

        $this->newLine();

        // Queue Wait Times
        $this->info('⏱️  Queue Wait Times (Average)');
        $this->table(
            ['Queue', 'Wait Time (seconds)', 'Status'],
            [
                ['Verification', $waitTimes['verification'], $this->getWaitTimeStatus($waitTimes['verification'])],
                ['Email', $waitTimes['email'], $this->getWaitTimeStatus($waitTimes['email'])],
                ['Default', $waitTimes['default'], $this->getWaitTimeStatus($waitTimes['default'])],
            ]
        );

        $this->newLine();

        // Throughput Metrics
        $this->info('🚀 Throughput Metrics');
        $this->table(
            ['Timeframe', 'Verifications/min', 'Emails/min'],
            [
                ['Last 5 minutes', $throughput['last_5_minutes']['verifications_per_minute'], $throughput['last_5_minutes']['emails_per_minute']],
                ['Last 15 minutes', $throughput['last_15_minutes']['verifications_per_minute'], $throughput['last_15_minutes']['emails_per_minute']],
                ['Last hour', $throughput['last_hour']['verifications_per_minute'], $throughput['last_hour']['emails_per_minute']],
            ]
        );

        $this->newLine();

        // Performance Summary
        $avgProcessingTime = $basic['average_processing_time'];
        $this->info('⚡ Performance Summary');
        $this->line("   • Average processing time: {$avgProcessingTime} seconds");
        $this->line("   • Total queue load: " . ($basic['verification_queue_size'] + $basic['email_queue_size']) . " jobs");

        if ($basic['failed_jobs_count'] > 0) {
            $this->warn("   ⚠️  {$basic['failed_jobs_count']} failed jobs need attention");
        } else {
            $this->info("   ✅ No failed jobs");
        }
    }

    /**
     * Get status icon based on success rate
     */
    private function getStatusIcon(float $rate): string
    {
        if ($rate >= 95) return '🟢 Excellent';
        if ($rate >= 85) return '🟡 Good';
        if ($rate >= 70) return '🟠 Fair';
        return '🔴 Poor';
    }

    /**
     * Get wait time status
     */
    private function getWaitTimeStatus(float $waitTime): string
    {
        if ($waitTime <= 10) return '🟢 Fast';
        if ($waitTime <= 30) return '🟡 Normal';
        if ($waitTime <= 60) return '🟠 Slow';
        return '🔴 Very Slow';
    }
}
