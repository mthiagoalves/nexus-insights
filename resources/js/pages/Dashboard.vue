<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface MetricsProps {
    totalSpend: number;
    sessions: number;
    conversions: number;
    revenue: number;
}

interface WorkspaceSummary {
    id: number;
    name: string;
    slug: string;
}

const page = usePage(); // 👈 sem genérico aqui

const metrics = computed(() => page.props.metrics as MetricsProps);
const currentWorkspace = computed(
    () => page.props.currentWorkspace as WorkspaceSummary | null,
);
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                    <p class="text-xs font-semibold text-white uppercase">
                        Investimento total (ads)
                    </p>
                    <p class="mt-2 text-2xl font-bold text-white">
                        R$ {{ metrics.totalSpend.toFixed(2) }}
                    </p>
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                    <p class="text-xs font-semibold text-white uppercase">
                        Sessões na página de vendas
                    </p>
                    <p class="mt-2 text-2xl font-bold text-white">
                        {{ metrics.sessions }}
                    </p>
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                    <p class="text-xs font-semibold text-white uppercase">
                        Conversões
                    </p>
                    <p class="mt-2 text-2xl font-bold text-white">
                        {{ metrics.conversions }}
                    </p>
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <PlaceholderPattern />
                <p class="text-xs font-semibold text-white uppercase">
                    Receita total
                </p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">
                    R$ {{ metrics.revenue.toFixed(2) }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
