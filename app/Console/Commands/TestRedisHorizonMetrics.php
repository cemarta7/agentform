<?php

namespace App\Console\Commands;

use App\Services\HorizonMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;

class TestRedisHorizonMetrics extends Command
{
    protected $signature = 'test:redis-horizon-metrics {--verbose : Show detailed output}';
    protected $description = 'Test Redis and Horizon metrics functionality';

    public function handle()
    {
        $this->info('🧪 Testing Redis and Horizon Metrics Functionality');
        $this->newLine();

        // Test 1: Environment Detection
        $this->testEnvironmentDetection();

        // Test 2: Redis Connection
        $this->testRedisConnection();

        // Test 3: Horizon Services
        $this->testHorizonServices();

        // Test 4: Queue Size Detection
        $this->testQueueSizeDetection();

        // Test 5: Metrics Service
        $this->testMetricsService();

        $this->newLine();
        $this->info('✅ Redis and Horizon metrics testing completed!');
    }

    private function testEnvironmentDetection()
    {
        $this->info('📋 1. Environment Detection');

        $queueConnection = config('queue.default');
        $this->line("   Queue Connection: {$queueConnection}");

        $redisClient = config('database.redis.client');
        $this->line("   Redis Client: {$redisClient}");

        $redisExtension = extension_loaded('redis');
        $this->line("   Redis Extension: " . ($redisExtension ? '✅ Available' : '❌ Not Available'));

        $redisClass = class_exists('Redis');
        $this->line("   Redis Class: " . ($redisClass ? '✅ Available' : '❌ Not Available'));

        $this->newLine();
    }

    private function testRedisConnection()
    {
        $this->info('🔗 2. Redis Connection Test');

        try {
            if (config('queue.default') === 'redis') {
                // Test Redis connection
                Redis::connection()->ping();
                $this->line("   ✅ Redis connection successful");

                // Test queue operations
                $testKey = 'test:horizon:metrics:' . time();
                Redis::connection()->set($testKey, 'test-value', 'EX', 10);
                $value = Redis::connection()->get($testKey);

                if ($value === 'test-value') {
                    $this->line("   ✅ Redis read/write operations working");
                    Redis::connection()->del($testKey);
                } else {
                    $this->line("   ⚠️  Redis read/write operations failed");
                }
            } else {
                $this->line("   ℹ️  Not using Redis queue connection");
            }
        } catch (\Exception $e) {
            $this->line("   ❌ Redis connection failed: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function testHorizonServices()
    {
        $this->info('🚀 3. Horizon Services Test');

        try {
            $jobRepository = app(JobRepository::class);
            $this->line("   ✅ JobRepository available");

            if ($this->option('verbose')) {
                $pending = $jobRepository->getPending();
                $this->line("   📊 Pending jobs count: " . count($pending));
            }
        } catch (\Exception $e) {
            $this->line("   ❌ JobRepository failed: " . $e->getMessage());
        }

        try {
            $metricsRepository = app(MetricsRepository::class);
            $this->line("   ✅ MetricsRepository available");
        } catch (\Exception $e) {
            $this->line("   ❌ MetricsRepository failed: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function testQueueSizeDetection()
    {
        $this->info('📊 4. Queue Size Detection');

        $service = app(HorizonMetricsService::class);
        $queues = ['default', 'verification', 'email'];

        foreach ($queues as $queue) {
            try {
                $size = $service->getQueueSize($queue);
                $this->line("   {$queue}: {$size} jobs");
            } catch (\Exception $e) {
                $this->line("   ❌ {$queue}: Error - " . $e->getMessage());
            }
        }

        $this->newLine();
    }

    private function testMetricsService()
    {
        $this->info('📈 5. Metrics Service Test');

        try {
            $service = app(HorizonMetricsService::class);

            // Test basic methods
            $this->line("   ✅ Service instantiated");

            $isRedis = $service->isUsingRedis();
            $this->line("   Using Redis: " . ($isRedis ? 'Yes' : 'No'));

            $isHorizon = $service->isHorizonAvailable();
            $this->line("   Horizon Available: " . ($isHorizon ? 'Yes' : 'No'));

            // Test dashboard metrics
            $metrics = $service->getDashboardMetrics();
            $this->line("   ✅ Dashboard metrics retrieved");

            if ($this->option('verbose')) {
                $this->line("   Queue Sizes: " . json_encode($metrics['queue_sizes']));
                $this->line("   Failed Jobs: " . $metrics['failed_jobs']);
            }

        } catch (\Exception $e) {
            $this->line("   ❌ Metrics service failed: " . $e->getMessage());
        }

        $this->newLine();
    }
}
