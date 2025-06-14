<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AgentFormMetrics from '@/components/AgentFormMetrics.vue';
import HorizonMetrics from '@/components/HorizonMetrics.vue';
import SystemStatus from '@/components/SystemStatus.vue';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- AgentForm Metrics -->
                <div class="relative rounded-xl">
                    <AgentFormMetrics />
                </div>

                <!-- Horizon Metrics -->
                <div class="relative rounded-xl">
                    <HorizonMetrics />
                </div>

                <!-- System Status -->
                <div class="relative rounded-xl">
                    <SystemStatus />
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <div class="h-full p-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            AgentForm Dashboard
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Monitor your form processing pipeline in real-time. Metrics refresh every 15 seconds.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Recent Activity -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                📈 Recent Activity
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-green-600 dark:text-green-400">✅</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Email verification completed</span>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">2 min ago</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-blue-600 dark:text-blue-400">📧</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Welcome email sent</span>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">5 min ago</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-orange-600 dark:text-orange-400">🔄</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Jobs requeued automatically</span>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">12 min ago</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                ⚡ Quick Actions
                            </h3>
                            <div class="grid grid-cols-2 gap-3">
                                <button class="p-4 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors text-left">
                                    <div class="text-lg mb-1">📊</div>
                                    <div class="text-sm font-medium">View Metrics</div>
                                    <div class="text-xs opacity-75">Detailed analytics</div>
                                </button>
                                <button class="p-4 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors text-left">
                                    <div class="text-lg mb-1">🔄</div>
                                    <div class="text-sm font-medium">Requeue Jobs</div>
                                    <div class="text-xs opacity-75">Recover failed jobs</div>
                                </button>
                                <button class="p-4 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors text-left">
                                    <div class="text-lg mb-1">📝</div>
                                    <div class="text-sm font-medium">View Logs</div>
                                    <div class="text-xs opacity-75">System activity</div>
                                </button>
                                <button class="p-4 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-left">
                                    <div class="text-lg mb-1">⚙️</div>
                                    <div class="text-sm font-medium">Settings</div>
                                    <div class="text-xs opacity-75">Configure system</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Insights -->
                    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            💡 Performance Insights
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg">
                                <div class="text-blue-600 dark:text-blue-400 text-sm font-medium mb-1">Processing Speed</div>
                                <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">Fast</div>
                                <div class="text-xs text-blue-600 dark:text-blue-400">Average queue time under 30s</div>
                            </div>
                            <div class="p-4 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg">
                                <div class="text-green-600 dark:text-green-400 text-sm font-medium mb-1">Success Rate</div>
                                <div class="text-2xl font-bold text-green-700 dark:text-green-300">High</div>
                                <div class="text-xs text-green-600 dark:text-green-400">95%+ job completion rate</div>
                            </div>
                            <div class="p-4 bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg">
                                <div class="text-purple-600 dark:text-purple-400 text-sm font-medium mb-1">System Health</div>
                                <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">Optimal</div>
                                <div class="text-xs text-purple-600 dark:text-purple-400">All systems operational</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
