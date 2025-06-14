<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';

interface SystemStatus {
    agentform: {
        total_forms: number;
        overall_completion_rate: number;
        pending_verification: number;
        pending_emails: number;
    };
    horizon: {
        total_queued: number;
        failed_jobs: number;
        overall_success_rate: number;
        jobs_per_minute: number;
    };
}

const status = ref<SystemStatus>({
    agentform: {
        total_forms: 0,
        overall_completion_rate: 0,
        pending_verification: 0,
        pending_emails: 0,
    },
    horizon: {
        total_queued: 0,
        failed_jobs: 0,
        overall_success_rate: 0,
        jobs_per_minute: 0,
    },
});

const loading = ref(true);
const error = ref<string | null>(null);
const lastUpdated = ref<Date | null>(null);
let refreshInterval: ReturnType<typeof setInterval> | null = null;

const fetchStatus = async () => {
    try {
        const [agentformResponse, horizonResponse] = await Promise.all([
            axios.get('/api/metrics/agentform'),
            axios.get('/api/metrics/horizon')
        ]);

        status.value.agentform = {
            total_forms: agentformResponse.data.total_forms,
            overall_completion_rate: agentformResponse.data.overall_completion_rate,
            pending_verification: agentformResponse.data.pending_verification,
            pending_emails: agentformResponse.data.pending_emails,
        };

        status.value.horizon = {
            total_queued: horizonResponse.data.queue_sizes.default +
                         horizonResponse.data.queue_sizes.verification +
                         horizonResponse.data.queue_sizes.email,
            failed_jobs: horizonResponse.data.failed_jobs,
            overall_success_rate: horizonResponse.data.success_rates.overall,
            jobs_per_minute: horizonResponse.data.throughput.jobs_per_minute_1,
        };

        lastUpdated.value = new Date();
        error.value = null;
    } catch (err) {
        error.value = 'Failed to fetch system status';
        console.error('Error fetching system status:', err);
    } finally {
        loading.value = false;
    }
};

const systemHealth = computed(() => {
    const completionRate = status.value.agentform.overall_completion_rate;
    const successRate = status.value.horizon.overall_success_rate;
    const queueSize = status.value.horizon.total_queued;
    const failedJobs = status.value.horizon.failed_jobs;

    if (completionRate >= 80 && successRate >= 95 && queueSize < 10 && failedJobs === 0) {
        return { status: 'healthy', color: 'text-green-600 dark:text-green-400', icon: '✅' };
    } else if (completionRate >= 60 && successRate >= 85 && queueSize < 50 && failedJobs < 5) {
        return { status: 'warning', color: 'text-yellow-600 dark:text-yellow-400', icon: '⚠️' };
    } else {
        return { status: 'critical', color: 'text-red-600 dark:text-red-400', icon: '🚨' };
    }
});

const formatNumber = (value: number): string => {
    return value.toLocaleString();
};

const formatPercentage = (value: number): string => {
    return `${value.toFixed(1)}%`;
};

onMounted(() => {
    fetchStatus();
    // Refresh every 15 seconds
    refreshInterval = setInterval(fetchStatus, 15000);
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
                📊 System Status
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
            <!-- Overall Health -->
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg text-center">
                <div class="text-3xl mb-2">{{ systemHealth.icon }}</div>
                <div class="text-lg font-semibold" :class="systemHealth.color">
                    {{ systemHealth.status.toUpperCase() }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300">System Health</div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ formatNumber(status.agentform.total_forms) }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Total Forms</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ formatPercentage(status.agentform.overall_completion_rate) }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Completion</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ formatNumber(status.horizon.total_queued) }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Queued Jobs</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ formatNumber(status.horizon.jobs_per_minute) }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Jobs/min</div>
                </div>
            </div>

            <!-- Alerts -->
            <div class="space-y-2">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Alerts</div>

                <div v-if="status.horizon.failed_jobs > 0" class="flex items-center space-x-2 p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <span class="text-red-600 dark:text-red-400">🚨</span>
                    <span class="text-sm text-red-700 dark:text-red-300">
                        {{ formatNumber(status.horizon.failed_jobs) }} failed jobs
                    </span>
                </div>

                <div v-if="status.horizon.total_queued > 50" class="flex items-center space-x-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <span class="text-yellow-600 dark:text-yellow-400">⚠️</span>
                    <span class="text-sm text-yellow-700 dark:text-yellow-300">
                        High queue size: {{ formatNumber(status.horizon.total_queued) }}
                    </span>
                </div>

                <div v-if="status.agentform.pending_verification > 100" class="flex items-center space-x-2 p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                    <span class="text-orange-600 dark:text-orange-400">📋</span>
                    <span class="text-sm text-orange-700 dark:text-orange-300">
                        {{ formatNumber(status.agentform.pending_verification) }} forms need verification
                    </span>
                </div>

                <div v-if="status.agentform.pending_emails > 50" class="flex items-center space-x-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <span class="text-blue-600 dark:text-blue-400">📧</span>
                    <span class="text-sm text-blue-700 dark:text-blue-300">
                        {{ formatNumber(status.agentform.pending_emails) }} emails pending
                    </span>
                </div>

                <div v-if="status.horizon.failed_jobs === 0 && status.horizon.total_queued < 10 && status.agentform.pending_verification < 50"
                     class="flex items-center space-x-2 p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <span class="text-green-600 dark:text-green-400">✅</span>
                    <span class="text-sm text-green-700 dark:text-green-300">
                        All systems operating normally
                    </span>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quick Actions</div>
                <div class="grid grid-cols-2 gap-2">
                    <button class="px-3 py-2 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                        View Logs
                    </button>
                    <button class="px-3 py-2 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                        Requeue Jobs
                    </button>
                    <button class="px-3 py-2 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">
                        Clear Cache
                    </button>
                    <button class="px-3 py-2 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
