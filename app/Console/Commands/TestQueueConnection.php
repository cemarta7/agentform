<?php

namespace App\Console\Commands;

use App\Services\HorizonMetricsService;
use App\Horizon\Metrics\QueueWaitTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class TestQueueConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:queue-connection {connection=database : Queue connection to test (database|redis)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test metrics with different queue connections';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = $this->argument('connection');

        if (!in_array($connection, ['database', 'redis'])) {
            $this->error('Invalid connection. Use "database" or "redis"');
            return Command::FAILURE;
        }

        $this->info("🔄 Testing metrics with {$connection} queue connection...");
        $this->newLine();

        // Temporarily set the queue connection
        $originalConnection = config('queue.default');
        Config::set('queue.default', $connection);

        try {
            // Test HorizonMetricsService
            $metricsService = new HorizonMetricsService();

            $this->info('📊 Connection Information:');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Queue Connection', config('queue.default')],
                    ['Using Redis', $metricsService->isUsingRedis() ? 'Yes' : 'No'],
                    ['Horizon Available', $metricsService->isHorizonAvailable() ? 'Yes' : 'No'],
                ]
            );

            $this->newLine();

            // Test queue metrics
            $queueMetrics = new QueueWaitTime();
            $metrics = $queueMetrics->recordMetrics();

            $this->info('🔢 Queue Metrics:');
            $this->table(
                ['Metric', 'Value'],
                collect($metrics)->map(function ($value, $key) {
                    return [str_replace('_', ' ', ucwords($key, '_')), $value];
                })->toArray()
            );

            $this->newLine();

            // Test AgentForm metrics (these should work regardless of queue connection)
            $agentFormMetrics = $metricsService->recordAgentFormMetrics();

            $this->info('📈 AgentForm Metrics (Queue-Independent):');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Verification Success Rate', $agentFormMetrics['verification_success_rate'] . '%'],
                    ['Email Success Rate', $agentFormMetrics['email_success_rate'] . '%'],
                    ['Forms Pending', $agentFormMetrics['forms_pending']],
                    ['Average Processing Time', $agentFormMetrics['average_processing_time'] . 's'],
                ]
            );

            $this->newLine();
            $this->info('✅ Test completed successfully');

            if ($connection === 'redis') {
                $this->newLine();
                $this->warn('⚠️  Note: Redis connection test may show 0 values if Redis is not configured or running.');
                $this->info('💡 To use Redis queues with Horizon:');
                $this->line('   1. Install Redis server');
                $this->line('   2. Install PHP Redis extension');
                $this->line('   3. Set QUEUE_CONNECTION=redis in .env');
                $this->line('   4. Run: php artisan horizon');
            }

        } catch (\Exception $e) {
            $this->error("❌ Error testing {$connection} connection: " . $e->getMessage());
            return Command::FAILURE;
        } finally {
            // Restore original connection
            Config::set('queue.default', $originalConnection);
        }

        return Command::SUCCESS;
    }
}
