<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

interface AgentFormStats {
    total_forms: number;
    verified_forms: number;
    emails_sent: number;
    verification_success_rate: number;
    email_success_rate: number;
    overall_completion_rate: number;
    pending_verification: number;
    pending_emails: number;
    time_started: number;
    time_ended: number;
    time_duration: number;
}

const stats = ref<AgentFormStats>({
    total_forms: 0,
    verified_forms: 0,
    emails_sent: 0,
    verification_success_rate: 0,
    email_success_rate: 0,
    overall_completion_rate: 0,
    pending_verification: 0,
    pending_emails: 0,
    time_started: 0,
    time_ended: 0,
    time_duration: 0,
});

const loading = ref(true);
const error = ref<string | null>(null);
const lastUpdated = ref<Date | null>(null);
let refreshInterval: ReturnType<typeof setInterval> | null = null;

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/metrics/agentform');
        stats.value = response.data;
        lastUpdated.value = new Date();
        error.value = null;
    } catch (err) {
        error.value = 'Failed to fetch AgentForm metrics';
        console.error('Error fetching AgentForm stats:', err);
    } finally {
        loading.value = false;
    }
};

const formatPercentage = (value: number): string => {
    return `${value.toFixed(1)}%`;
};

const formatNumber = (value: number): string => {
    return value.toLocaleString();
};

const getStatusColor = (rate: number): string => {
    if (rate >= 80) return 'text-green-600 dark:text-green-400';
    if (rate >= 60) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

onMounted(() => {
    fetchStats();
    // Refresh every 15 seconds
    refreshInterval = setInterval(fetchStats, 15000);
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
                📋 AgentForm Metrics
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
            <!-- Summary Stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ formatNumber(stats.total_forms) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Total Forms</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <div class="text-2xl font-bold" :class="getStatusColor(stats.overall_completion_rate)">
                        {{ formatPercentage(stats.overall_completion_rate) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Completion Rate</div>
                </div>
            </div>

            <!-- Processing Stats -->
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Verified Forms</span>
                    <div class="text-right">
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(stats.verified_forms) }}
                        </div>
                        <div class="text-xs" :class="getStatusColor(stats.verification_success_rate)">
                            {{ formatPercentage(stats.verification_success_rate) }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Emails Sent</span>
                    <div class="text-right">
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(stats.emails_sent) }}
                        </div>
                        <div class="text-xs" :class="getStatusColor(stats.email_success_rate)">
                            {{ formatPercentage(stats.email_success_rate) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Work -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pending Work</div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">🔍 Need Verification</span>
                        <span class="font-medium text-orange-600 dark:text-orange-400">
                            {{ formatNumber(stats.pending_verification) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">📧 Need Email</span>
                        <span class="font-medium text-blue-600 dark:text-blue-400">
                            {{ formatNumber(stats.pending_emails) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Time Started -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Started</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ new Date(stats.time_started).toLocaleString() }}
                </div>
            </div>

            <!-- Time Ended -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Ended</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ new Date(stats.time_ended).toLocaleString() }}
                </div>
            </div>

            <!-- Time Duration -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Duration</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ stats.time_duration }}  seconds
                </div>
            </div>
        </div>
    </div>
</template>
