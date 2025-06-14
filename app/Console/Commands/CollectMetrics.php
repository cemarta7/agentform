<?php

namespace App\Console\Commands;

use App\Horizon\Metrics\QueueWaitTime;
use App\Horizon\Metrics\JobThroughput;
use App\Services\HorizonMetricsService;
use Illuminate\Console\Command;

class CollectMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:collect {--show : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect and store metrics for Horizon dashboard';

    /**
     * Execute the console command.
     */
    public function handle(HorizonMetricsService $metricsService): int
    {
        $verbose = $this->option('show');

        if ($verbose) {
            $this->info('🔄 Collecting metrics...');
        }

        // Collect queue metrics
        $queueMetrics = new QueueWaitTime();
        $queueData = $queueMetrics->recordMetrics();
        $queueMetrics->storeTrendData();

        // Collect throughput metrics
        $throughputMetrics = new JobThroughput();
        $throughputData = $throughputMetrics->recordMetrics();
        $throughputMetrics->storeTrendData();

        // Collect AgentForm metrics
        $agentFormData = $metricsService->recordAgentFormMetrics();
        $metricsService->storeTrendData();

        if ($verbose) {
            $this->info('📊 Queue Metrics Collected:');
            $this->table(
                ['Metric', 'Value'],
                collect($queueData)->map(function ($value, $key) {
                    return [str_replace('_', ' ', ucwords($key, '_')), $value];
                })->toArray()
            );

            $this->newLine();
            $this->info('🚀 Throughput Metrics Collected:');
            $this->table(
                ['Metric', 'Value'],
                collect($throughputData)->map(function ($value, $key) {
                    return [str_replace('_', ' ', ucwords($key, '_')), $value];
                })->toArray()
            );

            $this->newLine();
            $this->info('📈 AgentForm Metrics Collected:');
            $this->table(
                ['Metric', 'Value'],
                collect($agentFormData)->map(function ($value, $key) {
                    return [str_replace('_', ' ', ucwords($key, '_')), $value];
                })->toArray()
            );

            $this->info('✅ All metrics collected and stored successfully');
        }

        return Command::SUCCESS;
    }
}
