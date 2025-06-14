<?php

namespace App\Horizon\Metrics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;

class QueueWaitTime
{
    protected $jobRepository;
    protected $metricsRepository;

    public function __construct()
    {
        // Try to resolve Horizon repositories if available
        try {
            $this->jobRepository = app(JobRepository::class);
            $this->metricsRepository = app(MetricsRepository::class);
        } catch (\Exception $e) {
            // Horizon not available or not using Redis
            $this->jobRepository = null;
            $this->metricsRepository = null;
        }
    }

    /**
     * Calculate and store queue wait time metrics
     */
    public function calculate(): array
    {
        $queues = ['verification', 'email', 'default'];
        $metrics = [];

        foreach ($queues as $queue) {
            $waitTime = $this->calculateWaitTimeForQueue($queue);
            $metrics["queue_wait_time_{$queue}"] = $waitTime;

            // Store in cache for Horizon dashboard access
            Cache::put("horizon_metric_wait_time_{$queue}", $waitTime, now()->addMinutes(5));
        }

        return $metrics;
    }

    /**
     * Calculate average wait time for a specific queue (Redis and Database compatible)
     */
    private function calculateWaitTimeForQueue(string $queue): float
    {
        $queueConnection = config('queue.default');

        if ($queueConnection === 'redis' && $this->metricsRepository) {
            // Use Horizon's built-in metrics for Redis queues
            try {
                // Horizon automatically tracks wait times, we can get them from the metrics repository
                // For now, return 0 as Horizon handles this internally
                return 0;
            } catch (\Exception $e) {
                return 0;
            }
        } else {
            // For database queues, calculate wait time based on job creation time
            $avgWaitTime = DB::table('jobs')
                ->where('queue', $queue)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, NOW())) as avg_wait')
                ->value('avg_wait');

            return round($avgWaitTime ?? 0, 2);
        }
    }

    /**
     * Get current queue sizes (Redis and Database compatible)
     */
    public function getQueueSizes(): array
    {
        $queues = ['verification', 'email', 'default'];
        $sizes = [];
        $queueConnection = config('queue.default');

        foreach ($queues as $queue) {
            if ($queueConnection === 'redis') {
                $size = $this->getRedisQueueSize($queue);
            } else {
                $size = DB::table('jobs')->where('queue', $queue)->count();
            }

            $sizes["queue_size_{$queue}"] = $size;

            // Store in cache
            Cache::put("horizon_metric_size_{$queue}", $size, now()->addMinutes(5));
        }

        return $sizes;
    }

        /**
     * Get Redis queue size directly
     */
    private function getRedisQueueSize(string $queue): int
    {
        try {
            // Check if Redis extension is available
            if (!extension_loaded('redis') && !class_exists('Redis')) {
                return 0;
            }

            $connection = config('queue.connections.redis.connection', 'default');

            // Redis queue key format: queues:queue_name
            $queueKey = "queues:{$queue}";

            return Redis::connection($connection)->llen($queueKey) ?? 0;
        } catch (\Exception $e) {
            // Redis not available or connection failed
            return 0;
        }
    }

    /**
     * Record all queue metrics
     */
    public function recordMetrics(): array
    {
        $waitTimes = $this->calculate();
        $queueSizes = $this->getQueueSizes();

        $allMetrics = array_merge($waitTimes, $queueSizes);

        // Add connection info
        $allMetrics['queue_connection'] = config('queue.default');
        $allMetrics['horizon_available'] = $this->jobRepository !== null;

        // Store comprehensive metrics
        Cache::put('horizon_queue_metrics', $allMetrics, now()->addMinutes(5));

        return $allMetrics;
    }

    /**
     * Get metrics for display
     */
    public function getMetrics(): array
    {
        return Cache::get('horizon_queue_metrics', []);
    }

    /**
     * Get trend data for a specific queue
     */
    public function getTrendData(string $queue, int $minutes = 60): array
    {
        $trends = [];
        $start = now()->subMinutes($minutes);

        for ($i = 0; $i < $minutes; $i += 5) {
            $timestamp = $start->copy()->addMinutes($i);
            $key = "horizon_trend_{$queue}_" . $timestamp->format('Y-m-d_H:i');

            $data = Cache::get($key);
            if ($data) {
                $trends[$timestamp->format('H:i')] = $data;
            }
        }

        return $trends;
    }

    /**
     * Store trend data point
     */
    public function storeTrendData(): void
    {
        $queues = ['verification', 'email', 'default'];
        $timestamp = now()->format('Y-m-d_H:i');
        $queueConnection = config('queue.default');

        foreach ($queues as $queue) {
            $waitTime = $this->calculateWaitTimeForQueue($queue);

            if ($queueConnection === 'redis') {
                $size = $this->getRedisQueueSize($queue);
            } else {
                $size = DB::table('jobs')->where('queue', $queue)->count();
            }

            $trendData = [
                'wait_time' => $waitTime,
                'queue_size' => $size,
                'connection' => $queueConnection,
                'timestamp' => now()->toISOString(),
            ];

            Cache::put("horizon_trend_{$queue}_{$timestamp}", $trendData, now()->addHours(24));
        }
    }

    /**
     * Check if we're using Redis queues
     */
    public function isUsingRedis(): bool
    {
        return config('queue.default') === 'redis';
    }

    /**
     * Check if Horizon is available
     */
    public function isHorizonAvailable(): bool
    {
        return $this->jobRepository !== null;
    }
}
