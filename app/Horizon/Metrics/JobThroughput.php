<?php

namespace App\Horizon\Metrics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class JobThroughput
{
    /**
     * Calculate job throughput metrics
     */
    public function calculate(): array
    {
        return [
            'jobs_per_minute_5min' => $this->getJobsPerMinute(5),
            'jobs_per_minute_15min' => $this->getJobsPerMinute(15),
            'jobs_per_minute_1hour' => $this->getJobsPerMinute(60),
            'verification_jobs_per_minute' => $this->getJobsPerMinuteByType('VerifyEmailJob', 15),
            'email_jobs_per_minute' => $this->getJobsPerMinuteByType('SendWelcomeEmailJob', 15),
            'success_rate_verification' => $this->getSuccessRate('VerifyEmailJob'),
            'success_rate_email' => $this->getSuccessRate('SendWelcomeEmailJob'),
        ];
    }

    /**
     * Get jobs processed per minute for a given timeframe
     */
    private function getJobsPerMinute(int $minutes): float
    {
        $completed = DB::table('agent_forms')
            ->where('email_sent_at', '>=', now()->subMinutes($minutes))
            ->count();

        return $minutes > 0 ? round($completed / $minutes, 2) : 0;
    }

    /**
     * Get jobs per minute for a specific job type
     */
    private function getJobsPerMinuteByType(string $jobType, int $minutes): float
    {
        // For verification jobs, count email_verified_at
        if ($jobType === 'VerifyEmailJob') {
            $completed = DB::table('agent_forms')
                ->where('email_verified_at', '>=', now()->subMinutes($minutes))
                ->count();
        } else {
            // For email jobs, count email_sent_at
            $completed = DB::table('agent_forms')
                ->where('email_sent_at', '>=', now()->subMinutes($minutes))
                ->count();
        }

        return $minutes > 0 ? round($completed / $minutes, 2) : 0;
    }

    /**
     * Get success rate for a job type
     */
    private function getSuccessRate(string $jobType): float
    {
        $total = DB::table('agent_forms')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($total === 0) {
            return 100.0;
        }

        if ($jobType === 'VerifyEmailJob') {
            $successful = DB::table('agent_forms')
                ->where('created_at', '>=', now()->subDay())
                ->whereNotNull('email_verified_at')
                ->count();
        } else {
            $successful = DB::table('agent_forms')
                ->where('created_at', '>=', now()->subDay())
                ->whereNotNull('email_verified_at')
                ->whereNotNull('email_sent_at')
                ->count();
        }

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get average job processing time
     */
    public function getAverageProcessingTimes(): array
    {
        // Average time from creation to verification
        $avgVerificationTime = DB::table('agent_forms')
            ->whereNotNull('email_verified_at')
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, email_verified_at)) as avg_time')
            ->value('avg_time');

        // Average time from verification to email sent
        $avgEmailTime = DB::table('agent_forms')
            ->whereNotNull('email_verified_at')
            ->whereNotNull('email_sent_at')
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, email_verified_at, email_sent_at)) as avg_time')
            ->value('avg_time');

        // Total processing time
        $avgTotalTime = DB::table('agent_forms')
            ->whereNotNull('email_verified_at')
            ->whereNotNull('email_sent_at')
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, email_sent_at)) as avg_time')
            ->value('avg_time');

        return [
            'avg_verification_time' => round($avgVerificationTime ?? 0, 2),
            'avg_email_time' => round($avgEmailTime ?? 0, 2),
            'avg_total_time' => round($avgTotalTime ?? 0, 2),
        ];
    }

    /**
     * Record all throughput metrics
     */
    public function recordMetrics(): array
    {
        $throughput = $this->calculate();
        $processingTimes = $this->getAverageProcessingTimes();

        $allMetrics = array_merge($throughput, $processingTimes);

        // Store in cache
        Cache::put('horizon_throughput_metrics', $allMetrics, now()->addMinutes(5));

        return $allMetrics;
    }

    /**
     * Get current throughput metrics
     */
    public function getMetrics(): array
    {
        return Cache::get('horizon_throughput_metrics', []);
    }

    /**
     * Store trend data for throughput
     */
    public function storeTrendData(): void
    {
        $metrics = $this->recordMetrics();
        $timestamp = now()->format('Y-m-d_H:i');

        Cache::put("horizon_throughput_trend_{$timestamp}", $metrics, now()->addHours(24));
    }

    /**
     * Get throughput trend data
     */
    public function getTrendData(int $hours = 6): array
    {
        $trends = [];
        $start = now()->subHours($hours);

        for ($i = 0; $i < $hours * 12; $i++) { // Every 5 minutes
            $timestamp = $start->copy()->addMinutes($i * 5);
            $key = "horizon_throughput_trend_" . $timestamp->format('Y-m-d_H:i');

            $data = Cache::get($key);
            if ($data) {
                $trends[$timestamp->format('H:i')] = $data;
            }
        }

        return $trends;
    }
}
