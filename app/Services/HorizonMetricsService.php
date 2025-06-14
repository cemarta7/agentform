<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;

class HorizonMetricsService
{
    protected $jobRepository;
    protected $metricsRepository;

    public function __construct()
    {
        // Only initialize Horizon services if Redis is available and we're using Redis queues
        if ($this->isRedisAvailable() && config('queue.default') === 'redis') {
            try {
                $this->jobRepository = app(JobRepository::class);
                $this->metricsRepository = app(MetricsRepository::class);
            } catch (\Exception $e) {
                // Horizon services not available, continue with database fallback
                $this->jobRepository = null;
                $this->metricsRepository = null;
            }
        } else {
            $this->jobRepository = null;
            $this->metricsRepository = null;
        }
    }

    /**
     * Check if Redis is actually available (extension + class)
     */
    private function isRedisAvailable(): bool
    {
        try {
            // Check if Redis extension is loaded
            if (!extension_loaded('redis')) {
                return false;
            }

            // Check if Redis class exists
            if (!class_exists('\Redis')) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Record AgentForm processing metrics
     */
    public function recordAgentFormMetrics(): array
    {
        $metrics = [
            'verification_queue_size' => $this->getQueueSize('verification'),
            'email_queue_size' => $this->getQueueSize('email'),
            'verification_success_rate' => $this->getVerificationSuccessRate(),
            'email_success_rate' => $this->getEmailSuccessRate(),
            'average_processing_time' => $this->getAverageProcessingTime(),
            'forms_completed_last_hour' => $this->getFormsCompletedLastHour(),
            'forms_pending' => $this->getFormsPending(),
            'failed_jobs_count' => $this->getFailedJobsCount(),
        ];

        // Store metrics in cache for dashboard access
        Cache::put('agentform_metrics', $metrics, now()->addMinutes(5));

        return $metrics;
    }

    /**
     * Get the current size of a specific queue (works with both database and Redis)
     */
    public function getQueueSize(string $queue): int
    {
        $queueConnection = config('queue.default');

        if ($queueConnection === 'redis' && $this->jobRepository) {
            // Use Horizon's job repository for Redis queues
            try {
                // Check if Redis is actually available before using Horizon
                if (!extension_loaded('redis') && !class_exists('Redis')) {
                    return 0;
                }
                return collect($this->jobRepository->getPending())->count();
            } catch (\Exception $e) {
                // Fallback to Redis direct query
                return $this->getRedisQueueSize($queue);
            }
        } else {
            // Use database table for database queues
            return DB::table('jobs')
                ->where('queue', $queue)
                ->count();
        }
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
            $prefix = config('queue.connections.redis.queue', 'default');

            // Redis queue key format: queues:queue_name
            $queueKey = "queues:{$queue}";

            return Redis::connection($connection)->llen($queueKey) ?? 0;
        } catch (\Exception $e) {
            // Redis not available or connection failed
            return 0;
        }
    }

    /**
     * Get verification success rate (last 24 hours)
     */
    public function getVerificationSuccessRate(): float
    {
        $total = DB::table('agent_forms')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($total === 0) {
            return 100.0;
        }

        $verified = DB::table('agent_forms')
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('email_verified_at')
            ->count();

        return round(($verified / $total) * 100, 2);
    }

    /**
     * Get email success rate (last 24 hours)
     */
    public function getEmailSuccessRate(): float
    {
        $verified = DB::table('agent_forms')
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('email_verified_at')
            ->count();

        if ($verified === 0) {
            return 100.0;
        }

        $emailSent = DB::table('agent_forms')
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('email_verified_at')
            ->whereNotNull('email_sent_at')
            ->count();

        return round(($emailSent / $verified) * 100, 2);
    }

    /**
     * Get average processing time from creation to completion
     */
    public function getAverageProcessingTime(): float
    {
        $avgSeconds = DB::table('agent_forms')
            ->whereNotNull('email_verified_at')
            ->whereNotNull('email_sent_at')
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, email_sent_at)) as avg_time')
            ->value('avg_time');

        return round($avgSeconds ?? 0, 2);
    }

    /**
     * Get forms completed in the last hour
     */
    public function getFormsCompletedLastHour(): int
    {
        return DB::table('agent_forms')
            ->whereNotNull('email_verified_at')
            ->whereNotNull('email_sent_at')
            ->where('email_sent_at', '>=', now()->subHour())
            ->count();
    }

    /**
     * Get forms that are still pending processing
     */
    public function getFormsPending(): int
    {
        return DB::table('agent_forms')
            ->where(function ($query) {
                $query->whereNull('email_verified_at')
                      ->orWhereNull('email_sent_at');
            })
            ->count();
    }

    /**
     * Get failed jobs count for AgentForm jobs (works with both database and Redis)
     */
    public function getFailedJobsCount(): int
    {
        $queueConnection = config('queue.default');

        if ($queueConnection === 'redis' && $this->jobRepository) {
            // Use Horizon's job repository for Redis queues
            try {
                // Check if Redis is actually available before using Horizon
                if (!extension_loaded('redis') && !class_exists('Redis')) {
                    return $this->getDatabaseFailedJobsCount();
                }
                $failedJobs = $this->jobRepository->getFailed();
                return collect($failedJobs)->filter(function ($job) {
                    return str_contains($job->payload['displayName'] ?? '', 'VerifyEmailJob') ||
                           str_contains($job->payload['displayName'] ?? '', 'SendWelcomeEmailJob');
                })->count();
            } catch (\Exception $e) {
                // Fallback to database failed_jobs table
                return $this->getDatabaseFailedJobsCount();
            }
        } else {
            return $this->getDatabaseFailedJobsCount();
        }
    }

    /**
     * Get failed jobs from database table
     */
    private function getDatabaseFailedJobsCount(): int
    {
        return DB::table('failed_jobs')
            ->where('payload', 'like', '%VerifyEmailJob%')
            ->orWhere('payload', 'like', '%SendWelcomeEmailJob%')
            ->count();
    }

    /**
     * Get queue wait times for different queues (Redis-aware)
     */
    public function getQueueWaitTimes(): array
    {
        $queues = ['verification', 'email', 'default'];
        $waitTimes = [];
        $queueConnection = config('queue.default');

        foreach ($queues as $queue) {
            if ($queueConnection === 'redis' && $this->metricsRepository) {
                // Use Horizon's metrics for Redis queues
                try {
                    $waitTime = $this->getHorizonWaitTime($queue);
                    $waitTimes[$queue] = $waitTime;
                } catch (\Exception $e) {
                    $waitTimes[$queue] = 0;
                }
            } else {
                // Use database calculation for database queues
                $avgWaitTime = DB::table('jobs')
                    ->where('queue', $queue)
                    ->where('created_at', '>=', now()->subMinutes(10))
                    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, NOW())) as avg_wait')
                    ->value('avg_wait');

                $waitTimes[$queue] = round($avgWaitTime ?? 0, 2);
            }
        }

        return $waitTimes;
    }

    /**
     * Get wait time from Horizon metrics
     */
    private function getHorizonWaitTime(string $queue): float
    {
        // This would use Horizon's built-in wait time calculation
        // For now, return 0 as placeholder - Horizon calculates this automatically
        return 0;
    }

    /**
     * Get throughput metrics (jobs processed per minute)
     */
    public function getThroughputMetrics(): array
    {
        $timeframes = [
            'last_5_minutes' => now()->subMinutes(5),
            'last_15_minutes' => now()->subMinutes(15),
            'last_hour' => now()->subHour(),
        ];

        $throughput = [];

        foreach ($timeframes as $label => $since) {
            $verified = DB::table('agent_forms')
                ->where('email_verified_at', '>=', $since)
                ->count();

            $emailsSent = DB::table('agent_forms')
                ->where('email_sent_at', '>=', $since)
                ->count();

            $minutes = now()->diffInMinutes($since);

            $throughput[$label] = [
                'verifications_per_minute' => $minutes > 0 ? round($verified / $minutes, 2) : 0,
                'emails_per_minute' => $minutes > 0 ? round($emailsSent / $minutes, 2) : 0,
            ];
        }

        return $throughput;
    }

    /**
     * Get all metrics as a comprehensive dashboard data
     */
    public function getDashboardMetrics(): array
    {
        $basicMetrics = $this->recordAgentFormMetrics();
        $waitTimes = $this->getQueueWaitTimes();
        $throughput = $this->getThroughputMetrics();

        return [
            // Structure expected by Vue components
            'queue_sizes' => [
                'default' => $this->getQueueSize('default'),
                'verification' => $this->getQueueSize('verification'),
                'email' => $this->getQueueSize('email'),
            ],
            'wait_times' => $waitTimes,
            'throughput' => [
                'jobs_per_minute_1' => $throughput['last_5_minutes']['verifications_per_minute'] + $throughput['last_5_minutes']['emails_per_minute'],
                'jobs_per_minute_5' => $throughput['last_15_minutes']['verifications_per_minute'] + $throughput['last_15_minutes']['emails_per_minute'],
                'jobs_per_minute_15' => $throughput['last_hour']['verifications_per_minute'] + $throughput['last_hour']['emails_per_minute'],
            ],
            'success_rates' => [
                'verification_jobs' => $basicMetrics['verification_success_rate'],
                'email_jobs' => $basicMetrics['email_success_rate'],
                'overall' => ($basicMetrics['verification_success_rate'] + $basicMetrics['email_success_rate']) / 2,
            ],
            'processing_times' => [
                'avg_verification_time' => $basicMetrics['average_processing_time'] / 2, // Rough estimate
                'avg_email_time' => $basicMetrics['average_processing_time'] / 2, // Rough estimate
                'avg_total_time' => $basicMetrics['average_processing_time'],
            ],
            'failed_jobs' => $basicMetrics['failed_jobs_count'],

            // Legacy structure for backward compatibility
            'basic_metrics' => $basicMetrics,
            'queue_wait_times' => $waitTimes,
            'throughput_detailed' => $throughput,
            'queue_connection' => config('queue.default'),
            'horizon_available' => $this->jobRepository !== null,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Store metrics trend data for historical analysis
     */
    public function storeTrendData(): void
    {
        $metrics = $this->recordAgentFormMetrics();
        $timestamp = now()->format('Y-m-d H:i:00'); // Round to minute

        // Store in cache with timestamp key for trend analysis
        Cache::put("agentform_trend:{$timestamp}", $metrics, now()->addDays(7));
    }

    /**
     * Get trend data for the last N hours
     */
    public function getTrendData(int $hours = 24): array
    {
        $trends = [];
        $start = now()->subHours($hours);

        for ($i = 0; $i < $hours * 60; $i += 5) { // Every 5 minutes
            $timestamp = $start->copy()->addMinutes($i)->format('Y-m-d H:i:00');
            $data = Cache::get("agentform_trend:{$timestamp}");

            if ($data) {
                $trends[$timestamp] = $data;
            }
        }

        return $trends;
    }

    /**
     * Check if we're using Redis queues
     */
    public function isUsingRedis(): bool
    {
        return config('queue.default') === 'redis' && $this->isRedisAvailable();
    }

    /**
     * Check if Horizon is available
     */
    public function isHorizonAvailable(): bool
    {
        return $this->jobRepository !== null;
    }
}
