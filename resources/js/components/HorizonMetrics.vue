<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

interface QueueMetrics {
    queue_sizes: {
        default: number;
        verification: number;
        email: number;
    };
    wait_times: {
        default: number;
        verification: number;
        email: number;
    };
    processes: {
        verification: number;
        email: number;
        default: number;
    };
    success_rates: {
        verification_jobs: number;
        email_jobs: number;
        overall: number;
    };
    processing_times: {
        avg_verification_time: number;
        avg_email_time: number;
        avg_total_time: number;
    };
    failed_jobs: number;
}

const metrics = ref<QueueMetrics>({
    queue_sizes: { default: 0, verification: 0, email: 0 },
    wait_times: { default: 0, verification: 0, email: 0 },
    processes: { verification: 0, email: 0, default: 0 },
    success_rates: { verification_jobs: 0, email_jobs: 0, overall: 0 },
    processing_times: { avg_verification_time: 0, avg_email_time: 0, avg_total_time: 0 },
    failed_jobs: 0,
});

const loading = ref(true);
const error = ref<string | null>(null);
const lastUpdated = ref<Date | null>(null);
let refreshInterval: ReturnType<typeof setInterval> | null = null;

const fetchMetrics = async () => {
    try {
        const response = await axios.get('/api/metrics/horizon');
        metrics.value = response.data;
        lastUpdated.value = new Date();
        error.value = null;
    } catch (err) {
        error.value = 'Failed to fetch Horizon metrics';
        console.error('Error fetching Horizon metrics:', err);
    } finally {
        loading.value = false;
    }
};

const formatNumber = (value: number): string => {
    return value.toLocaleString();
};

const formatPercentage = (value: number): string => {
    return `${value.toFixed(1)}%`;
};



const getQueueStatusColor = (size: number): string => {
    if (size === 0) return 'text-green-600 dark:text-green-400';
    if (size < 10) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const getSuccessRateColor = (rate: number): string => {
    if (rate >= 95) return 'text-green-600 dark:text-green-400';
    if (rate >= 85) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const getTotalQueueSize = (): number => {
    return metrics.value.queue_sizes.default +
           metrics.value.queue_sizes.verification +
           metrics.value.queue_sizes.email;
};

onMounted(() => {
    fetchMetrics();
    // Refresh every 15 seconds
    refreshInterval = setInterval(fetchMetrics, 15000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<template>
    <div class="h-full p-4 bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                🚀 Horizon Metrics
            </h3>
            <div class="flex items-center space-x-2">
                <div v-if="loading" class="animate-spin h-4 w-4 border-2 border-blue-500 border-t-transparent rounded-full"></div>
                <span v-if="lastUpdated" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ lastUpdated.toLocaleTimeString() }}
                </span>
            </div>
        </div>

        <div v-if="error" class="text-red-600 dark:text-red-400 text-sm mb-4">
            {{ error }}
        </div>

        <div class="space-y-4">
            <!-- Queue Status -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <div class="text-2xl font-bold" :class="getQueueStatusColor(getTotalQueueSize())">
                        {{ formatNumber(getTotalQueueSize()) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Total Queued</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ formatNumber(metrics.failed_jobs) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Failed Jobs</div>
                </div>
            </div>

            <!-- Queue Breakdown -->
            <div class="space-y-2">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Queue Sizes</div>
                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Default</span>
                        <span class="font-medium" :class="getQueueStatusColor(metrics.queue_sizes.default)">
                            {{ formatNumber(metrics.queue_sizes.default) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Verification</span>
                        <span class="font-medium" :class="getQueueStatusColor(metrics.queue_sizes.verification)">
                            {{ formatNumber(metrics.queue_sizes.verification) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Email</span>
                        <span class="font-medium" :class="getQueueStatusColor(metrics.queue_sizes.email)">
                            {{ formatNumber(metrics.queue_sizes.email) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Processes -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Processes</div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(metrics.processes.verification) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Verification</div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(metrics.processes.email) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Email</div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(metrics.processes.default) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Default</div>
                    </div>
                </div>
            </div>

            <!-- Success Rates -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Success Rates</div>
                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Overall</span>
                        <span class="font-medium" :class="getSuccessRateColor(metrics.success_rates.overall)">
                            {{ formatPercentage(metrics.success_rates.overall) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Verification</span>
                        <span class="font-medium" :class="getSuccessRateColor(metrics.success_rates.verification_jobs)">
                            {{ formatPercentage(metrics.success_rates.verification_jobs) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Email</span>
                        <span class="font-medium" :class="getSuccessRateColor(metrics.success_rates.email_jobs)">
                            {{ formatPercentage(metrics.success_rates.email_jobs) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
