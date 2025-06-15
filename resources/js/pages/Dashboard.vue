<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AgentFormMetrics from '@/components/AgentFormMetrics.vue';
import HorizonMetrics from '@/components/HorizonMetrics.vue';
import SystemStatus from '@/components/SystemStatus.vue';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const showHorizonMetrics = ref(false);
const showSystemStatus = ref(false);

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
                <div class="relative rounded-xl" v-if="showHorizonMetrics">
                    <HorizonMetrics />
                </div>

                <!-- System Status -->
                <div class="relative rounded-xl" v-if="showSystemStatus">
                    <SystemStatus />
                </div>
            </div>
            <div class="flex justify-end">
                <button class="bg-blue-500 text-white px-4 py-2 rounded-md" @click="showHorizonMetrics = !showHorizonMetrics">
                    {{ showHorizonMetrics ? 'Hide Horizon Metrics' : 'Show Horizon Metrics' }}
                </button>
                <button class="bg-blue-500 text-white px-4 py-2 rounded-md" @click="showSystemStatus = !showSystemStatus">
                    {{ showSystemStatus ? 'Hide System Status' : 'Show System Status' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
